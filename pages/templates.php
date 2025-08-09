<?php
/**
 * Project Templates Management Page
 * Code Bunker
 */

$pageTitle = 'Project Templates';
require_once dirname(__FILE__) . '/../includes/header.php';

// Require admin access for template management
requireLogin();
if (!isAdmin()) {
    header('Location: dashboard.php?error=Access denied');
    exit;
}

// Get current user
$currentUser = getCurrentUser();

// Handle form submissions
$message = '';
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $data = [
                'name' => sanitizeInput($_POST['name']),
                'description' => sanitizeInput($_POST['description']),
                'category' => sanitizeInput($_POST['category']),
                'default_priority' => sanitizeInput($_POST['default_priority']),
                'estimated_duration_days' => intval($_POST['estimated_duration_days']),
                'estimated_hours' => floatval($_POST['estimated_hours']),
                'created_by' => $currentUser['id']
            ];
            
            $result = createProjectTemplate($data);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            break;
            
        case 'delete':
            $templateId = intval($_POST['template_id']);
            $result = deleteProjectTemplate($templateId);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            break;
    }
}

// Get all templates
$templates = getProjectTemplates();

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1><i class="bi bi-file-earmark-text"></i> Project Templates</h1>
        <p class="text-muted">Create and manage reusable project templates</p>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#templateModal" onclick="openCreateModal()">
        <i class="bi bi-plus-lg"></i> New Template
    </button>
</div>

<!-- Flash message -->
<?php if ($message): ?>
<div class="alert alert-<?php echo $messageType === 'error' ? 'danger' : $messageType; ?> alert-dismissible fade show">
    <?php echo htmlspecialchars($message); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Templates Grid -->
<div class="row">
    <?php if (empty($templates)): ?>
    <div class="col-12">
        <div class="text-center p-5">
            <i class="bi bi-file-earmark-text fs-1 text-muted"></i>
            <h5 class="text-muted mt-3">No templates found</h5>
            <p class="text-muted">Create your first project template to get started.</p>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#templateModal" onclick="openCreateModal()">
                <i class="bi bi-plus-lg"></i> Create Template
            </button>
        </div>
    </div>
    <?php else: ?>
    <?php foreach ($templates as $template): ?>
    <div class="col-md-6 col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h5 class="card-title"><?= htmlspecialchars($template['name']) ?></h5>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" 
                                data-bs-toggle="dropdown" data-bs-container="body">
                            <i class="bi bi-three-dots"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="template_view.php?id=<?= $template['id'] ?>">
                                <i class="bi bi-eye"></i> View Tasks
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><button class="dropdown-item text-danger" 
                                       onclick="deleteTemplate(<?= $template['id'] ?>, '<?= htmlspecialchars($template['name']) ?>')">
                                <i class="bi bi-trash"></i> Delete
                            </button></li>
                        </ul>
                    </div>
                </div>
                
                <p class="card-text text-muted small"><?= htmlspecialchars($template['description']) ?></p>
                
                <div class="row text-center mt-3">
                    <div class="col-4">
                        <div class="text-primary fw-bold"><?= $template['task_count'] ?? 0 ?></div>
                        <div class="small text-muted">Tasks</div>
                    </div>
                    <div class="col-4">
                        <div class="text-info fw-bold"><?= $template['estimated_hours'] ?>h</div>
                        <div class="small text-muted">Est. Hours</div>
                    </div>
                    <div class="col-4">
                        <div class="text-warning fw-bold"><?= $template['estimated_duration_days'] ?>d</div>
                        <div class="small text-muted">Duration</div>
                    </div>
                </div>
            </div>
            
            <div class="card-footer bg-light">
                <small class="text-muted">
                    <span class="badge bg-secondary"><?= htmlspecialchars($template['category']) ?></span>
                    <?= getPriorityBadge($template['default_priority']) ?>
                </small>
                <small class="text-muted float-end">
                    Created <?= formatDate($template['created_at']) ?>
                </small>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Template Modal -->
<div class="modal fade" id="templateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="templateForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="templateModalTitle">Create Template</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="name" class="form-label">Template Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" 
                                     placeholder="Describe what this template is used for..."></textarea>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category">
                                <option value="General">General</option>
                                <option value="Web Development">Web Development</option>
                                <option value="Mobile Development">Mobile Development</option>
                                <option value="UI/UX Design">UI/UX Design</option>
                                <option value="DevOps">DevOps</option>
                                <option value="Testing">Testing</option>
                                <option value="Maintenance">Maintenance</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="default_priority" class="form-label">Default Priority</label>
                            <select class="form-select" id="default_priority" name="default_priority">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="estimated_duration_days" class="form-label">Estimated Duration (Days)</label>
                            <input type="number" class="form-control" id="estimated_duration_days" 
                                   name="estimated_duration_days" min="1" value="30">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="estimated_hours" class="form-label">Estimated Hours</label>
                            <input type="number" class="form-control" id="estimated_hours" 
                                   name="estimated_hours" min="0" step="0.5" value="40">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Create Template
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('templateModalTitle').textContent = 'Create Template';
    document.getElementById('templateForm').reset();
}

function deleteTemplate(id, name) {
    if (confirm('Are you sure you want to delete the template "' + name + '"? This action cannot be undone and will also delete all associated template tasks.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="template_id" value="${id}">
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