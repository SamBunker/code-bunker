<?php
/**
 * Task Detail View Page
 * Code Bunker
 */

$pageTitle = 'Task Details';
require_once dirname(__FILE__) . '/../includes/header.php';

// Require login
requireLogin();

// Get current user
$currentUser = getCurrentUser();

// Get task ID from URL
$taskId = intval($_GET['id'] ?? 0);

if (!$taskId) {
    header('Location: tasks.php');
    exit;
}

// Get task details
$task = getTask($taskId);
if (!$task) {
    header('Location: tasks.php?error=Task not found');
    exit;
}

// Get project details
$project = getProject($task['project_id']);
if (!$project) {
    header('Location: tasks.php?error=Project not found');
    exit;
}

// Check permissions (basic check - admins can view all, users can view assigned tasks or tasks in their projects)
if (!isAdmin() && $task['assigned_to'] != $currentUser['id'] && $project['created_by'] != $currentUser['id'] && $project['assigned_to'] != $currentUser['id']) {
    header('Location: tasks.php?error=Access denied');
    exit;
}

// Get task notes
$notes = getNotes($task['project_id'], $taskId);
if ($notes === false) $notes = [];

// Handle form submissions
$message = '';
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_task':
            $taskData = [
                'project_id' => $task['project_id'],
                'title' => sanitizeInput($_POST['task_title']),
                'description' => sanitizeInput($_POST['task_description']),
                'task_type' => sanitizeInput($_POST['task_type']),
                'priority' => sanitizeInput($_POST['task_priority']),
                'status' => sanitizeInput($_POST['task_status']),
                'assigned_to' => intval($_POST['task_assigned_to']) ?: null,
                'start_date' => $_POST['task_start_date'] ?: null,
                'due_date' => $_POST['task_due_date'] ?: null,
                'estimated_hours' => floatval($_POST['task_estimated_hours']),
                'actual_hours' => floatval($_POST['task_actual_hours'])
            ];
            
            $result = updateTask($taskId, $taskData, $currentUser['id']);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            
            if ($result['success']) {
                // Refresh task data
                $task = getTask($taskId);
            }
            break;
            
        case 'update_task_status':
            $newStatus = sanitizeInput($_POST['status']);
            
            if ($task) {
                $data = [
                    'status' => $newStatus,
                    'completed_at' => ($newStatus === 'completed') ? date('Y-m-d H:i:s') : null
                ];
                
                // Handle actual hours based on status change
                if ($newStatus === 'completed' && (floatval($task['actual_hours']) == 0)) {
                    // If marking as completed and actual hours is 0, set it to estimated hours
                    $data['actual_hours'] = floatval($task['estimated_hours']);
                } elseif ($task['status'] === 'completed' && $newStatus !== 'completed') {
                    // If changing from completed to any other status, reset actual hours to 0
                    $data['actual_hours'] = 0;
                }
                
                $result = updateTaskStatus($taskId, $data, $currentUser['id']);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                
                if ($result['success']) {
                    // Refresh task data
                    $task = getTask($taskId);
                }
            }
            break;
            
        case 'add_note':
            $noteData = [
                'project_id' => $task['project_id'],
                'task_id' => $taskId,
                'title' => sanitizeInput($_POST['note_title']),
                'content' => sanitizeInput($_POST['note_content']),
                'note_type' => sanitizeInput($_POST['note_type']),
                'is_private' => isset($_POST['is_private']) ? 1 : 0,
                'user_id' => $currentUser['id']
            ];
            
            $result = createNote($noteData);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            
            if ($result['success']) {
                // Refresh notes
                $notes = getNotes($task['project_id'], $taskId);
                if ($notes === false) $notes = [];
            }
            break;
            
        case 'update_note':
            $noteId = intval($_POST['note_id']);
            $noteData = [
                'title' => sanitizeInput($_POST['note_title']),
                'content' => sanitizeInput($_POST['note_content']),
                'note_type' => sanitizeInput($_POST['note_type']),
                'is_private' => isset($_POST['is_private']) ? 1 : 0
            ];
            
            $result = updateNote($noteId, $noteData, $currentUser['id']);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            
            if ($result['success']) {
                // Refresh notes
                $notes = getNotes($task['project_id'], $taskId);
                if ($notes === false) $notes = [];
            }
            break;
            
        case 'delete_note':
            $noteId = intval($_POST['note_id']);
            $result = deleteNote($noteId, $currentUser['id']);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            
            if ($result['success']) {
                // Refresh notes
                $notes = getNotes($task['project_id'], $taskId);
                if ($notes === false) $notes = [];
            }
            break;
    }
}

// Get data for dropdowns
$users = getUsers();
$taskTypes = getTaskTypes();

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="projects.php">Projects</a></li>
                <li class="breadcrumb-item"><a href="project_view.php?id=<?= $task['project_id'] ?>"><?= htmlspecialchars($project['name']) ?></a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($task['title']) ?></li>
            </ol>
        </nav>
        <h1><i class="bi bi-check-square"></i> <?= htmlspecialchars($task['title']) ?></h1>
        <p class="text-muted">Task details and management</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editTaskModal">
            <i class="bi bi-pencil"></i> Edit Task
        </button>
        <a href="project_view.php?id=<?= $task['project_id'] ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Project
        </a>
    </div>
</div>

<!-- Flash message -->
<?php if ($message): ?>
<div class="alert alert-<?php echo $messageType === 'error' ? 'danger' : $messageType; ?> alert-dismissible fade show">
    <?php echo htmlspecialchars($message); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Task Information Cards -->
<div class="row g-4 mb-4">
    <!-- Task Details Card -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Task Information</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Status</label>
                        <div>
                            <button type="button" class="btn btn-sm dropdown-toggle border-0 p-0" 
                                    data-bs-toggle="dropdown" 
                                    data-bs-container="body"
                                    data-bs-boundary="clippingParents"
                                    data-bs-placement="bottom-start"
                                    style="background: none;">
                                <?= getStatusBadge($task['status'], 'task') ?>
                            </button>
                            <ul class="dropdown-menu" style="z-index: 9999;">
                                <?php foreach (['pending', 'in_progress', 'testing', 'completed', 'blocked'] as $statusKey): ?>
                                <li>
                                    <button class="dropdown-item <?= $task['status'] === $statusKey ? 'active' : '' ?>" 
                                            onclick="updateTaskStatus('<?= $statusKey ?>')">
                                        <?= getStatusBadge($statusKey, 'task') ?>
                                    </button>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Priority</label>
                        <div><?= getPriorityBadge($task['priority']) ?></div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Task Type</label>
                        <div><?= htmlspecialchars($task['task_type']) ?></div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Assigned To</label>
                        <div><?= $task['assigned_to_name'] ?: '<span class="text-muted">Unassigned</span>' ?></div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Start Date</label>
                        <div><?= $task['start_date'] ? formatDate($task['start_date']) : '<span class="text-muted">Not set</span>' ?></div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Due Date</label>
                        <div>
                            <?php if ($task['due_date']): ?>
                                <?= formatDate($task['due_date']) ?>
                                <?php if ($task['due_date'] < date('Y-m-d') && $task['status'] !== 'completed'): ?>
                                    <i class="bi bi-exclamation-triangle text-danger ms-1" title="Overdue"></i>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">Not set</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label text-muted small">Description</label>
                        <div class="p-3 bg-light rounded">
                            <?= $task['description'] ? nl2br(htmlspecialchars($task['description'])) : '<em class="text-muted">No description provided</em>' ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Hours Tracking Card -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clock"></i> Hours Tracking</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small">Estimated Hours</label>
                    <div class="fs-4 text-primary"><?= number_format($task['estimated_hours'], 1) ?>h</div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label text-muted small">Actual Hours</label>
                    <div class="fs-4 <?= floatval($task['actual_hours']) > floatval($task['estimated_hours']) && floatval($task['estimated_hours']) > 0 ? 'text-danger' : 'text-success' ?>">
                        <?= number_format($task['actual_hours'], 1) ?>h
                    </div>
                </div>
                
                <?php if (floatval($task['estimated_hours']) > 0): ?>
                <div class="mb-3">
                    <label class="form-label text-muted small">Progress</label>
                    <?php 
                    $hoursProgress = min(100, (floatval($task['actual_hours']) / floatval($task['estimated_hours'])) * 100);
                    $isOverBudget = floatval($task['actual_hours']) > floatval($task['estimated_hours']);
                    ?>
                    <div class="progress mb-1" style="height: 8px;">
                        <div class="progress-bar <?= $isOverBudget ? 'bg-danger' : 'bg-primary' ?>" 
                             style="width: <?= min(100, $hoursProgress) ?>%"></div>
                    </div>
                    <small class="<?= $isOverBudget ? 'text-danger' : 'text-muted' ?>">
                        <?= number_format($hoursProgress, 1) ?>% 
                        <?= $isOverBudget ? '(Over Budget)' : '' ?>
                    </small>
                </div>
                <?php endif; ?>
                
                <?php if ($task['completed_at']): ?>
                <div class="mb-3">
                    <label class="form-label text-muted small">Completed</label>
                    <div><?= formatDateTime($task['completed_at']) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Task Notes -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-journal-text"></i> Task Notes (<?= count($notes) ?>)</h5>
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#noteModal">
            <i class="bi bi-plus-lg"></i> Add Note
        </button>
    </div>
    <div class="card-body">
        <?php if (empty($notes)): ?>
        <div class="text-center py-4">
            <i class="bi bi-journal fs-1 text-muted"></i>
            <h6 class="text-muted mt-2">No notes yet</h6>
            <p class="text-muted small">Add notes to track progress, decisions, and important information.</p>
        </div>
        <?php else: ?>
        <div class="notes-list">
            <?php foreach ($notes as $note): ?>
            <div class="note-item mb-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="note-header flex-grow-1">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h6 class="note-title mb-0"><?= htmlspecialchars($note['title'] ?: 'Untitled Note') ?></h6>
                            <?php if ($note['is_private']): ?>
                            <i class="bi bi-lock-fill text-muted" style="font-size: 0.8rem;" title="Private Note"></i>
                            <?php endif; ?>
                        </div>
                        <div class="note-meta text-muted small">
                            <span class="note-type"><?= htmlspecialchars($note['note_type']) ?></span>
                            • <span class="note-author"><?= htmlspecialchars($note['user_name']) ?></span>
                            • <span class="note-date"><?= formatDateTime($note['created_at']) ?></span>
                        </div>
                    </div>
                    <?php if ($note['user_id'] == $currentUser['id'] || isAdmin()): ?>
                    <div class="note-actions">
                        <button type="button" class="btn btn-link btn-sm text-muted p-1" 
                                onclick="editNote(<?= $note['id'] ?>)"
                                data-bs-toggle="modal" 
                                data-bs-target="#editNoteModal"
                                title="Edit Note">
                            <i class="bi bi-pencil" style="font-size: 0.9rem;"></i>
                        </button>
                        <button type="button" class="btn btn-link btn-sm text-muted p-1" 
                                onclick="deleteNote(<?= $note['id'] ?>, '<?= htmlspecialchars($note['title'] ?: 'this note') ?>')"
                                title="Delete Note">
                            <i class="bi bi-trash" style="font-size: 0.9rem;"></i>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="note-content">
                    <?= nl2br(htmlspecialchars($note['content'])) ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Task Modal -->
<div class="modal fade" id="editTaskModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="editTaskForm">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_task">
                    
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="task_title" class="form-label">Title *</label>
                            <input type="text" class="form-control" id="task_title" name="task_title" 
                                   value="<?= htmlspecialchars($task['title']) ?>" required>
                        </div>
                        
                        <div class="col-12">
                            <label for="task_description" class="form-label">Description</label>
                            <textarea class="form-control" id="task_description" name="task_description" rows="3"><?= htmlspecialchars($task['description']) ?></textarea>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="task_type" class="form-label">Task Type</label>
                            <select class="form-select" id="task_type" name="task_type">
                                <?php foreach ($taskTypes as $type): ?>
                                <option value="<?= htmlspecialchars($type) ?>" <?= $task['task_type'] === $type ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($type) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="task_priority" class="form-label">Priority</label>
                            <select class="form-select" id="task_priority" name="task_priority">
                                <?php foreach (['low', 'medium', 'high', 'critical'] as $priority): ?>
                                <option value="<?= $priority ?>" <?= $task['priority'] === $priority ? 'selected' : '' ?>>
                                    <?= ucfirst($priority) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="task_status" class="form-label">Status</label>
                            <select class="form-select" id="task_status" name="task_status">
                                <?php foreach (['pending', 'in_progress', 'testing', 'completed', 'blocked'] as $status): ?>
                                <option value="<?= $status ?>" <?= $task['status'] === $status ? 'selected' : '' ?>>
                                    <?= ucwords(str_replace('_', ' ', $status)) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="task_assigned_to" class="form-label">Assigned To</label>
                            <select class="form-select" id="task_assigned_to" name="task_assigned_to">
                                <option value="">Unassigned</option>
                                <?php foreach ($users as $user): ?>
                                <option value="<?= $user['id'] ?>" <?= $task['assigned_to'] == $user['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="task_start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="task_start_date" name="task_start_date" 
                                   value="<?= $task['start_date'] ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="task_due_date" class="form-label">Due Date</label>
                            <input type="date" class="form-control" id="task_due_date" name="task_due_date" 
                                   value="<?= $task['due_date'] ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="task_estimated_hours" class="form-label">Estimated Hours</label>
                            <input type="number" class="form-control" id="task_estimated_hours" name="task_estimated_hours" 
                                   min="0" step="0.5" value="<?= $task['estimated_hours'] ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="task_actual_hours" class="form-label">Actual Hours</label>
                            <input type="number" class="form-control" id="task_actual_hours" name="task_actual_hours" 
                                   min="0" step="0.5" value="<?= $task['actual_hours'] ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Update Task
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Note Modal -->
<div class="modal fade" id="noteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add Note</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_note">
                    
                    <div class="mb-3">
                        <label for="note_title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="note_title" name="note_title" placeholder="Note title (optional)">
                    </div>
                    
                    <div class="mb-3">
                        <label for="note_content" class="form-label">Content *</label>
                        <textarea class="form-control" id="note_content" name="note_content" rows="4" required 
                                  placeholder="Enter your note content..."></textarea>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="note_type" class="form-label">Type</label>
                            <select class="form-select" id="note_type" name="note_type">
                                <option value="general">General</option>
                                <option value="technical">Technical</option>
                                <option value="meeting">Meeting</option>
                                <option value="decision">Decision</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-check mt-4">
                                <input type="checkbox" class="form-check-input" id="is_private" name="is_private">
                                <label class="form-check-label" for="is_private">
                                    Private Note
                                    <small class="text-muted d-block">Only visible to you</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Add Note
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Note Modal -->
<div class="modal fade" id="editNoteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Note</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_note">
                    <input type="hidden" name="note_id" id="edit_note_id">
                    
                    <div class="mb-3">
                        <label for="edit_note_title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="edit_note_title" name="note_title" placeholder="Note title (optional)">
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_note_content" class="form-label">Content *</label>
                        <textarea class="form-control" id="edit_note_content" name="note_content" rows="4" required 
                                  placeholder="Enter your note content..."></textarea>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="edit_note_type" class="form-label">Type</label>
                            <select class="form-select" id="edit_note_type" name="note_type">
                                <option value="general">General</option>
                                <option value="technical">Technical</option>
                                <option value="meeting">Meeting</option>
                                <option value="decision">Decision</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-check mt-4">
                                <input type="checkbox" class="form-check-input" id="edit_is_private" name="is_private">
                                <label class="form-check-label" for="edit_is_private">
                                    Private Note
                                    <small class="text-muted d-block">Only visible to you</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Update Note
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Clean Notes Design - 2024 Minimal UI */
.note-item {
    background: #fafafa;
    border-radius: 8px;
    padding: 1.25rem;
    border: 1px solid #f0f0f0;
    transition: all 0.15s ease;
    position: relative;
}

.note-item:hover {
    background: #f8f9fa;
    border-color: #e9ecef;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.note-title {
    color: #2c3e50;
    font-weight: 600;
    font-size: 0.95rem;
    letter-spacing: -0.01em;
}

.note-meta {
    color: #6c757d;
    font-size: 0.8rem;
    line-height: 1.4;
}

.note-meta .task-ref {
    color: #0d6efd;
    font-weight: 500;
}

.note-content {
    color: #495057;
    line-height: 1.6;
    font-size: 0.9rem;
    margin-top: 0.75rem;
    white-space: pre-line;
    word-wrap: break-word;
}

.note-actions {
    opacity: 0;
    transition: opacity 0.2s ease;
}

.note-item:hover .note-actions {
    opacity: 1;
}

.note-actions .btn-link {
    border: none;
    text-decoration: none;
    margin-left: 0.25rem;
}

.note-actions .btn-link:hover {
    color: #0d6efd !important;
    background: rgba(13, 110, 253, 0.1);
    border-radius: 4px;
}

.note-actions .btn-link:hover .bi-trash {
    color: #dc3545 !important;
}

/* Private note styling */
.note-item .bi-lock-fill {
    opacity: 0.6;
}
</style>

<script>
// Notes data for edit modal
const notesData = <?= json_encode($notes) ?>;

function editNote(noteId) {
    const note = notesData.find(n => n.id == noteId);
    if (!note) return;
    
    // Populate edit modal with note data
    document.getElementById('edit_note_id').value = note.id;
    document.getElementById('edit_note_title').value = note.title || '';
    document.getElementById('edit_note_content').value = note.content || '';
    document.getElementById('edit_note_type').value = note.note_type || 'general';
    document.getElementById('edit_is_private').checked = note.is_private == 1;
}

function updateTaskStatus(newStatus) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="update_task_status">
        <input type="hidden" name="status" value="${newStatus}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function deleteNote(noteId, noteTitle) {
    if (confirm('Are you sure you want to delete "' + noteTitle + '"? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_note">
            <input type="hidden" name="note_id" value="${noteId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Fix dropdown positioning
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.dropdown-toggle').forEach(function(toggle) {
        toggle.addEventListener('show.bs.dropdown', function() {
            const dropdown = this.nextElementSibling;
            if (dropdown && dropdown.classList.contains('dropdown-menu')) {
                document.body.appendChild(dropdown);
                
                const rect = this.getBoundingClientRect();
                dropdown.style.position = 'fixed';
                dropdown.style.top = (rect.bottom + 2) + 'px';
                dropdown.style.left = rect.left + 'px';
                dropdown.style.zIndex = '9999';
            }
        });
        
        toggle.addEventListener('hide.bs.dropdown', function() {
            const dropdown = document.body.querySelector('.dropdown-menu[style*="position: fixed"]');
            if (dropdown) {
                this.parentNode.appendChild(dropdown);
                dropdown.style.position = '';
                dropdown.style.top = '';
                dropdown.style.left = '';
            }
        });
    });
});
</script>

<?php require_once dirname(__FILE__) . '/../includes/footer.php'; ?>