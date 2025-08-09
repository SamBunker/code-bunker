<?php
/**
 * Common Functions
 * Code Bunker
 * 
 * Utility functions used throughout the application.
 */

require_once dirname(__FILE__) . '/../config/config.php';

/**
 * Project Management Functions
 */

/**
 * Get all projects with optional filtering
 * @param array $filters Filter criteria
 * @param int $limit Number of results to return
 * @param int $offset Offset for pagination
 * @return array Projects data
 */
function getProjects($filters = [], $limit = null, $offset = 0) {
    $query = "SELECT p.*, 
              CONCAT(u1.first_name, ' ', u1.last_name) as created_by_name,
              CONCAT(u2.first_name, ' ', u2.last_name) as assigned_to_name,
              COUNT(t.id) as total_tasks,
              SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks
              FROM projects p
              LEFT JOIN users u1 ON p.created_by = u1.id
              LEFT JOIN users u2 ON p.assigned_to = u2.id
              LEFT JOIN tasks t ON p.id = t.project_id";
    
    $whereConditions = [];
    $params = [];
    
    // Apply filters
    if (!empty($filters['status'])) {
        $whereConditions[] = "p.status = ?";
        $params[] = $filters['status'];
    }
    
    if (!empty($filters['priority'])) {
        $whereConditions[] = "p.priority = ?";
        $params[] = $filters['priority'];
    }
    
    if (!empty($filters['category'])) {
        $whereConditions[] = "p.category = ?";
        $params[] = $filters['category'];
    }
    
    if (!empty($filters['assigned_to'])) {
        $whereConditions[] = "p.assigned_to = ?";
        $params[] = $filters['assigned_to'];
    }
    
    if (!empty($filters['search'])) {
        $whereConditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
        $params[] = '%' . $filters['search'] . '%';
        $params[] = '%' . $filters['search'] . '%';
    }
    
    if (!empty($whereConditions)) {
        $query .= " WHERE " . implode(' AND ', $whereConditions);
    }
    
    $query .= " GROUP BY p.id ORDER BY p.created_at DESC";
    
    if ($limit) {
        $query .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
    }
    
    return executeQuery($query, $params) ?: [];
}

/**
 * Get single project by ID
 * @param int $projectId Project ID
 * @return array|false Project data or false if not found
 */
function getProject($projectId) {
    $query = "SELECT p.*, 
              CONCAT(u1.first_name, ' ', u1.last_name) as created_by_name,
              CONCAT(u2.first_name, ' ', u2.last_name) as assigned_to_name
              FROM projects p
              LEFT JOIN users u1 ON p.created_by = u1.id
              LEFT JOIN users u2 ON p.assigned_to = u2.id
              WHERE p.id = ?";
    
    return executeQuerySingle($query, [$projectId]);
}

/**
 * Create new project
 * @param array $data Project data
 * @return array Result with success status and project ID
 */
function createProject($data) {
    try {
        $query = "INSERT INTO projects (name, description, category, priority, status, current_version, 
                  target_version, start_date, due_date, estimated_hours, budget, created_by, assigned_to) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['name'],
            $data['description'] ?? '',
            $data['category'] ?? 'Web Application',
            $data['priority'] ?? 'medium',
            $data['status'] ?? 'planning',
            $data['current_version'] ?? '',
            $data['target_version'] ?? '',
            $data['start_date'] ?? null,
            $data['due_date'] ?? null,
            $data['estimated_hours'] ?? 0,
            $data['budget'] ?? null,
            $data['created_by'],
            $data['assigned_to'] ?? null
        ];
        
        $result = executeUpdate($query, $params);
        
        if ($result) {
            $projectId = getDatabase()->getLastInsertId();
            logActivity($data['created_by'], 'create', 'project', $projectId, null, $data, 'Project created');
            
            return [
                'success' => true,
                'message' => 'Project created successfully',
                'project_id' => $projectId
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to create project'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Create project error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred while creating the project'
        ];
    }
}

/**
 * Update project
 * @param int $projectId Project ID
 * @param array $data Updated project data
 * @param int $userId User making the update
 * @return array Result with success status
 */
function updateProject($projectId, $data, $userId) {
    try {
        // Get old values for logging
        $oldProject = getProject($projectId);
        if (!$oldProject) {
            return [
                'success' => false,
                'message' => 'Project not found'
            ];
        }
        
        $query = "UPDATE projects SET name = ?, description = ?, category = ?, priority = ?, 
                  status = ?, current_version = ?, target_version = ?, start_date = ?, 
                  due_date = ?, estimated_hours = ?, budget = ?, assigned_to = ?, 
                  updated_at = NOW() WHERE id = ?";
        
        $params = [
            $data['name'],
            $data['description'] ?? '',
            $data['category'] ?? 'Web Application',
            $data['priority'] ?? 'medium',
            $data['status'] ?? 'planning',
            $data['current_version'] ?? '',
            $data['target_version'] ?? '',
            $data['start_date'] ?? null,
            $data['due_date'] ?? null,
            $data['estimated_hours'] ?? 0,
            $data['budget'] ?? null,
            $data['assigned_to'] ?? null,
            $projectId
        ];
        
        $result = executeUpdate($query, $params);
        
        if ($result) {
            logActivity($userId, 'update', 'project', $projectId, $oldProject, $data, 'Project updated');
            
            return [
                'success' => true,
                'message' => 'Project updated successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to update project'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Update project error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred while updating the project'
        ];
    }
}

/**
 * Delete project
 * @param int $projectId Project ID
 * @param int $userId User making the deletion
 * @return array Result with success status
 */
function deleteProject($projectId, $userId) {
    try {
        $project = getProject($projectId);
        if (!$project) {
            return [
                'success' => false,
                'message' => 'Project not found'
            ];
        }
        
        $query = "DELETE FROM projects WHERE id = ?";
        $result = executeUpdate($query, [$projectId]);
        
        if ($result) {
            logActivity($userId, 'delete', 'project', $projectId, $project, null, 'Project deleted');
            
            return [
                'success' => true,
                'message' => 'Project deleted successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to delete project'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Delete project error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred while deleting the project'
        ];
    }
}

/**
 * Task Management Functions
 */

/**
 * Get tasks for a project
 * @param int $projectId Project ID
 * @param array $filters Filter criteria
 * @return array Tasks data
 */
function getTasks($projectId = null, $filters = []) {
    $query = "SELECT t.*, p.name as project_name,
              CONCAT(u.first_name, ' ', u.last_name) as assigned_to_name,
              DATEDIFF(t.due_date, CURDATE()) as days_until_due
              FROM tasks t
              JOIN projects p ON t.project_id = p.id
              LEFT JOIN users u ON t.assigned_to = u.id";
    
    $whereConditions = [];
    $params = [];
    
    if ($projectId) {
        $whereConditions[] = "t.project_id = ?";
        $params[] = $projectId;
    }
    
    // Apply filters
    if (!empty($filters['status'])) {
        $whereConditions[] = "t.status = ?";
        $params[] = $filters['status'];
    }
    
    if (!empty($filters['priority'])) {
        $whereConditions[] = "t.priority = ?";
        $params[] = $filters['priority'];
    }
    
    if (!empty($filters['assigned_to'])) {
        $whereConditions[] = "t.assigned_to = ?";
        $params[] = $filters['assigned_to'];
    }
    
    if (!empty($whereConditions)) {
        $query .= " WHERE " . implode(' AND ', $whereConditions);
    }
    
    $query .= " ORDER BY t.due_date ASC, t.priority DESC, t.created_at DESC";
    
    return executeQuery($query, $params) ?: [];
}

/**
 * Get single task by ID
 * @param int $taskId Task ID
 * @return array|false Task data or false if not found
 */
function getTask($taskId) {
    $query = "SELECT t.*, p.name as project_name,
              CONCAT(u.first_name, ' ', u.last_name) as assigned_to_name
              FROM tasks t
              JOIN projects p ON t.project_id = p.id
              LEFT JOIN users u ON t.assigned_to = u.id
              WHERE t.id = ?";
    
    return executeQuerySingle($query, [$taskId]);
}

/**
 * Create new task
 * @param array $data Task data
 * @return array Result with success status and task ID
 */
function createTask($data) {
    try {
        $query = "INSERT INTO tasks (project_id, title, description, task_type, priority, status, 
                  assigned_to, depends_on_task_id, estimated_hours, start_date, due_date) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['project_id'],
            $data['title'],
            $data['description'] ?? '',
            $data['task_type'] ?? 'General',
            $data['priority'] ?? 'medium',
            $data['status'] ?? 'pending',
            $data['assigned_to'] ?? null,
            $data['depends_on_task_id'] ?? null,
            $data['estimated_hours'] ?? 0,
            $data['start_date'] ?? null,
            $data['due_date'] ?? null
        ];
        
        $result = executeUpdate($query, $params);
        
        if ($result) {
            $taskId = getDatabase()->getLastInsertId();
            logActivity($data['assigned_to'] ?? 1, 'create', 'task', $taskId, null, $data, 'Task created');
            
            return [
                'success' => true,
                'message' => 'Task created successfully',
                'task_id' => $taskId
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to create task'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Create task error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred while creating the task'
        ];
    }
}

/**
 * Update task
 * @param int $taskId Task ID
 * @param array $data Updated task data
 * @param int $userId User making the update
 * @return array Result with success status
 */
function updateTask($taskId, $data, $userId) {
    try {
        // Get old values for logging
        $oldTask = getTask($taskId);
        if (!$oldTask) {
            return [
                'success' => false,
                'message' => 'Task not found'
            ];
        }
        
        $query = "UPDATE tasks SET project_id = ?, title = ?, description = ?, task_type = ?, 
                  priority = ?, status = ?, assigned_to = ?, depends_on_task_id = ?, 
                  estimated_hours = ?, actual_hours = ?, start_date = ?, due_date = ?, 
                  updated_at = NOW() WHERE id = ?";
        
        $params = [
            $data['project_id'],
            $data['title'],
            $data['description'] ?? '',
            $data['task_type'] ?? 'General',
            $data['priority'] ?? 'medium',
            $data['status'] ?? 'pending',
            $data['assigned_to'] ?? null,
            $data['depends_on_task_id'] ?? null,
            $data['estimated_hours'] ?? 0,
            $data['actual_hours'] ?? 0,
            $data['start_date'] ?? null,
            $data['due_date'] ?? null,
            $taskId
        ];
        
        // If task is being marked as completed, set completion time
        if ($data['status'] === 'completed' && $oldTask['status'] !== 'completed') {
            $completionQuery = "UPDATE tasks SET completed_at = NOW() WHERE id = ?";
            executeUpdate($completionQuery, [$taskId]);
        }
        
        $result = executeUpdate($query, $params);
        
        if ($result) {
            logActivity($userId, 'update', 'task', $taskId, $oldTask, $data, 'Task updated');
            
            return [
                'success' => true,
                'message' => 'Task updated successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to update task'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Update task error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred while updating the task'
        ];
    }
}

/**
 * Update task status
 * @param int $taskId Task ID
 * @param array $data Status update data
 * @param int $userId User making the update
 * @return array Result with success status
 */
function updateTaskStatus($taskId, $data, $userId) {
    try {
        $oldTask = getTask($taskId);
        if (!$oldTask) {
            return [
                'success' => false,
                'message' => 'Task not found'
            ];
        }
        
        $query = "UPDATE tasks SET status = ?";
        $params = [$data['status']];
        
        if (isset($data['completed_at'])) {
            $query .= ", completed_at = ?";
            $params[] = $data['completed_at'];
        }
        
        if (isset($data['actual_hours'])) {
            $query .= ", actual_hours = ?";
            $params[] = $data['actual_hours'];
        }
        
        $query .= " WHERE id = ?";
        $params[] = $taskId;
        
        $result = executeUpdate($query, $params);
        
        if ($result) {
            logActivity($userId, 'status_update', 'task', $taskId, 
                       ['status' => $oldTask['status']], 
                       ['status' => $data['status']], 
                       'Task status updated to ' . $data['status']);
            
            return [
                'success' => true,
                'message' => 'Task status updated successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to update task status'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Update task status error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred while updating the task status'
        ];
    }
}

/**
 * Delete task
 * @param int $taskId Task ID
 * @param int $userId User making the deletion
 * @return array Result with success status
 */
function deleteTask($taskId, $userId) {
    try {
        $task = getTask($taskId);
        if (!$task) {
            return [
                'success' => false,
                'message' => 'Task not found'
            ];
        }
        
        $query = "DELETE FROM tasks WHERE id = ?";
        $result = executeUpdate($query, [$taskId]);
        
        if ($result) {
            logActivity($userId, 'delete', 'task', $taskId, $task, null, 'Task deleted');
            
            return [
                'success' => true,
                'message' => 'Task deleted successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to delete task'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Delete task error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred while deleting the task'
        ];
    }
}

/**
 * Get dashboard statistics
 * @param int $userId Optional user ID to filter by assigned tasks
 * @return array Dashboard stats
 */
function getDashboardStats($userId = null) {
    $stats = [];
    
    // Project stats
    $projectQuery = "SELECT 
        COUNT(*) as total_projects,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_projects,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as active_projects,
        SUM(CASE WHEN status = 'on_hold' THEN 1 ELSE 0 END) as on_hold_projects,
        SUM(CASE WHEN priority = 'critical' THEN 1 ELSE 0 END) as critical_projects
        FROM projects";
    
    $projectStats = executeQuerySingle($projectQuery);
    $stats['projects'] = $projectStats ?: [];
    
    // Task stats
    $taskQuery = "SELECT 
        COUNT(*) as total_tasks,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as active_tasks,
        SUM(CASE WHEN status = 'blocked' THEN 1 ELSE 0 END) as blocked_tasks,
        SUM(CASE WHEN due_date < CURDATE() AND status != 'completed' THEN 1 ELSE 0 END) as overdue_tasks
        FROM tasks" . ($userId ? " WHERE assigned_to = $userId" : "");
    
    $taskStats = executeQuerySingle($taskQuery);
    $stats['tasks'] = $taskStats ?: [];
    
    // Recent activity
    $activityQuery = "SELECT al.*, CONCAT(u.first_name, ' ', u.last_name) as user_name
        FROM activity_log al
        JOIN users u ON al.user_id = u.id
        ORDER BY al.created_at DESC
        LIMIT 10";
    
    $stats['recent_activity'] = executeQuery($activityQuery) ?: [];
    
    // Upcoming deadlines
    $deadlineQuery = "SELECT p.id, p.name, p.due_date, p.status, p.priority
        FROM projects p
        WHERE p.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        AND p.status != 'completed'
        ORDER BY p.due_date ASC
        LIMIT 5";
    
    $stats['upcoming_deadlines'] = executeQuery($deadlineQuery) ?: [];
    
    return $stats;
}

/**
 * Note Management Functions
 */

/**
 * Get notes for a project or task
 * @param int $projectId Project ID
 * @param int $taskId Task ID (optional)
 * @return array Notes data
 */
function getNotes($projectId, $taskId = null) {
    $query = "SELECT n.*, CONCAT(u.first_name, ' ', u.last_name) as user_name,
              u.username
              FROM notes n
              JOIN users u ON n.user_id = u.id
              WHERE n.project_id = ?";
    
    $params = [$projectId];
    
    if ($taskId) {
        $query .= " AND n.task_id = ?";
        $params[] = $taskId;
    } else {
        $query .= " AND n.task_id IS NULL";
    }
    
    $query .= " ORDER BY n.created_at DESC";
    
    return executeQuery($query, $params) ?: [];
}

/**
 * Create new note
 * @param array $data Note data
 * @return array Result with success status and note ID
 */
function createNote($data) {
    try {
        $query = "INSERT INTO notes (project_id, task_id, user_id, title, content, note_type, is_private) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['project_id'],
            $data['task_id'] ?? null,
            $data['user_id'],
            $data['title'] ?? '',
            $data['content'],
            $data['note_type'] ?? 'general',
            $data['is_private'] ?? false
        ];
        
        $result = executeUpdate($query, $params);
        
        if ($result) {
            $noteId = getDatabase()->getLastInsertId();
            logActivity($data['user_id'], 'create', 'note', $noteId, null, $data, 'Note created');
            
            return [
                'success' => true,
                'message' => 'Note created successfully',
                'note_id' => $noteId
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to create note'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Create note error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred while creating the note'
        ];
    }
}

/**
 * Update note
 * @param int $noteId Note ID
 * @param array $data Updated note data
 * @param int $userId User making the update
 * @return array Result with success status
 */
function updateNote($noteId, $data, $userId) {
    try {
        // Get old values for logging
        $oldNote = getNote($noteId);
        if (!$oldNote) {
            return [
                'success' => false,
                'message' => 'Note not found'
            ];
        }
        
        // Check permission
        if ($oldNote['user_id'] != $userId && !isAdmin()) {
            return [
                'success' => false,
                'message' => 'You can only edit your own notes'
            ];
        }
        
        $query = "UPDATE notes SET title = ?, content = ?, note_type = ?, is_private = ?, 
                  updated_at = NOW() WHERE id = ?";
        
        $params = [
            $data['title'] ?? '',
            $data['content'],
            $data['note_type'] ?? 'general',
            $data['is_private'] ?? false,
            $noteId
        ];
        
        $result = executeUpdate($query, $params);
        
        if ($result) {
            logActivity($userId, 'update', 'note', $noteId, $oldNote, $data, 'Note updated');
            
            return [
                'success' => true,
                'message' => 'Note updated successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to update note'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Update note error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred while updating the note'
        ];
    }
}

/**
 * Delete note
 * @param int $noteId Note ID
 * @param int $userId User making the deletion
 * @return array Result with success status
 */
function deleteNote($noteId, $userId) {
    try {
        $note = getNote($noteId);
        if (!$note) {
            return [
                'success' => false,
                'message' => 'Note not found'
            ];
        }
        
        // Check permission
        if ($note['user_id'] != $userId && !isAdmin()) {
            return [
                'success' => false,
                'message' => 'You can only delete your own notes'
            ];
        }
        
        $query = "DELETE FROM notes WHERE id = ?";
        $result = executeUpdate($query, [$noteId]);
        
        if ($result) {
            logActivity($userId, 'delete', 'note', $noteId, $note, null, 'Note deleted');
            
            return [
                'success' => true,
                'message' => 'Note deleted successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to delete note'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Delete note error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred while deleting the note'
        ];
    }
}

/**
 * Get single note by ID
 * @param int $noteId Note ID
 * @return array|false Note data or false if not found
 */
function getNote($noteId) {
    $query = "SELECT n.*, CONCAT(u.first_name, ' ', u.last_name) as user_name
              FROM notes n
              JOIN users u ON n.user_id = u.id
              WHERE n.id = ?";
    
    return executeQuerySingle($query, [$noteId]);
}

/**
 * Get all users for dropdowns
 * @param bool $activeOnly Only return active users
 * @return array Users data
 */
function getUsers($activeOnly = true) {
    $query = "SELECT id, username, email, first_name, last_name, role 
              FROM users" . ($activeOnly ? " WHERE is_active = 1" : "") . "
              ORDER BY first_name, last_name";
    
    return executeQuery($query) ?: [];
}

/**
 * Reporting Functions
 */

/**
 * Generate project status report
 * @param array $filters Date and status filters
 * @return array Report data
 */
function generateProjectStatusReport($filters = []) {
    $whereConditions = [];
    $params = [];
    
    $budgetTracking = isFeatureEnabled('budget_tracking');
    
    $query = "SELECT 
        p.status,
        COUNT(DISTINCT p.id) as project_count,
        AVG(CASE WHEN p.due_date IS NOT NULL THEN DATEDIFF(p.due_date, p.created_at) END) as avg_duration_days";
    
    if ($budgetTracking) {
        $query .= ",
        SUM(p.budget) as total_budget,
        AVG(p.budget) as avg_budget";
    } else {
        $query .= ",
        COUNT(t.id) as total_tasks,
        0 as total_budget,
        0 as avg_budget";
    }
    
    $query .= " FROM projects p";
    
    if (!$budgetTracking) {
        $query .= " LEFT JOIN tasks t ON p.id = t.project_id";
    }
    
    if (!empty($filters['start_date'])) {
        $whereConditions[] = "p.created_at >= ?";
        $params[] = $filters['start_date'];
    }
    
    if (!empty($filters['end_date'])) {
        $whereConditions[] = "p.created_at <= ?";
        $params[] = $filters['end_date'];
    }
    
    if (!empty($whereConditions)) {
        $query .= " WHERE " . implode(' AND ', $whereConditions);
    }
    
    $query .= " GROUP BY p.status ORDER BY project_count DESC";
    
    return executeQuery($query, $params) ?: [];
}

/**
 * Generate task completion report
 * @param array $filters Date and assignment filters
 * @return array Report data
 */
function generateTaskCompletionReport($filters = []) {
    $whereConditions = [];
    $params = [];
    
    $query = "SELECT 
        t.status,
        t.priority,
        COUNT(*) as task_count,
        AVG(t.actual_hours) as avg_hours,
        SUM(t.actual_hours) as total_hours,
        AVG(CASE WHEN t.completed_at IS NOT NULL AND t.created_at IS NOT NULL 
                 THEN DATEDIFF(t.completed_at, t.created_at) END) as avg_completion_days
        FROM tasks t
        JOIN projects p ON t.project_id = p.id";
    
    if (!empty($filters['start_date'])) {
        $whereConditions[] = "t.created_at >= ?";
        $params[] = $filters['start_date'];
    }
    
    if (!empty($filters['end_date'])) {
        $whereConditions[] = "t.created_at <= ?";
        $params[] = $filters['end_date'];
    }
    
    if (!empty($filters['assigned_to'])) {
        $whereConditions[] = "t.assigned_to = ?";
        $params[] = $filters['assigned_to'];
    }
    
    if (!empty($whereConditions)) {
        $query .= " WHERE " . implode(' AND ', $whereConditions);
    }
    
    $query .= " GROUP BY t.status, t.priority ORDER BY t.status, t.priority";
    
    return executeQuery($query, $params) ?: [];
}

/**
 * Generate productivity report by user
 * @param array $filters Date filters
 * @return array Report data
 */
function generateProductivityReport($filters = []) {
    $whereConditions = [];
    $params = [];
    
    $query = "SELECT 
        u.id,
        CONCAT(u.first_name, ' ', u.last_name) as user_name,
        COUNT(DISTINCT t.project_id) as projects_worked_on,
        COUNT(t.id) as total_tasks,
        SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
        SUM(t.actual_hours) as total_hours,
        AVG(CASE WHEN t.status = 'completed' AND t.completed_at IS NOT NULL AND t.created_at IS NOT NULL 
                 THEN DATEDIFF(t.completed_at, t.created_at) END) as avg_task_completion_days
        FROM users u
        LEFT JOIN tasks t ON u.id = t.assigned_to";
    
    if (!empty($filters['start_date'])) {
        $whereConditions[] = "t.created_at >= ?";
        $params[] = $filters['start_date'];
    }
    
    if (!empty($filters['end_date'])) {
        $whereConditions[] = "t.created_at <= ?";
        $params[] = $filters['end_date'];
    }
    
    if (!empty($whereConditions)) {
        $query .= " WHERE " . implode(' AND ', $whereConditions);
    }
    
    $query .= " GROUP BY u.id, u.first_name, u.last_name 
                HAVING total_tasks > 0 
                ORDER BY completed_tasks DESC, total_hours DESC";
    
    return executeQuery($query, $params) ?: [];
}

/**
 * Generate timeline report for projects
 * @param array $filters Project and date filters
 * @return array Report data
 */
function generateTimelineReport($filters = []) {
    $whereConditions = [];
    $params = [];
    
    $query = "SELECT 
        p.id,
        p.name,
        p.status,
        p.priority,
        p.created_at,
        p.start_date,
        p.due_date,
        CASE 
            WHEN p.due_date IS NOT NULL THEN DATEDIFF(p.due_date, CURDATE())
            ELSE NULL
        END as days_until_due,
        COUNT(t.id) as total_tasks,
        SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
        CASE 
            WHEN COUNT(t.id) > 0 THEN ROUND((SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) / COUNT(t.id)) * 100, 1)
            ELSE 0
        END as progress_percentage
        FROM projects p
        LEFT JOIN tasks t ON p.id = t.project_id";
    
    if (!empty($filters['status'])) {
        $whereConditions[] = "p.status = ?";
        $params[] = $filters['status'];
    }
    
    if (!empty($filters['priority'])) {
        $whereConditions[] = "p.priority = ?";
        $params[] = $filters['priority'];
    }
    
    if (!empty($whereConditions)) {
        $query .= " WHERE " . implode(' AND ', $whereConditions);
    }
    
    $query .= " GROUP BY p.id ORDER BY p.due_date ASC, p.priority DESC";
    
    return executeQuery($query, $params) ?: [];
}

/**
 * Generate summary statistics for reports
 * @param array $filters Date filters
 * @return array Summary statistics
 */
function generateReportSummary($filters = []) {
    $whereClause = "";
    $params = [];
    
    if (!empty($filters['start_date']) || !empty($filters['end_date'])) {
        $conditions = [];
        if (!empty($filters['start_date'])) {
            $conditions[] = "created_at >= ?";
            $params[] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $conditions[] = "created_at <= ?";
            $params[] = $filters['end_date'];
        }
        $whereClause = " WHERE " . implode(' AND ', $conditions);
    }
    
    // Project summary
    $projectQuery = "SELECT 
        COUNT(*) as total_projects,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_projects,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as active_projects,
        SUM(budget) as total_budget,
        AVG(budget) as avg_budget
        FROM projects" . $whereClause;
    
    $projectStats = executeQuerySingle($projectQuery, $params);
    
    // Task summary
    $taskQuery = "SELECT 
        COUNT(*) as total_tasks,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
        SUM(actual_hours) as total_hours,
        AVG(actual_hours) as avg_hours_per_task
        FROM tasks" . $whereClause;
    
    $taskStats = executeQuerySingle($taskQuery, $params);
    
    return [
        'projects' => $projectStats ?: [],
        'tasks' => $taskStats ?: [],
        'completion_rate' => $projectStats && $projectStats['total_projects'] > 0 
            ? round(($projectStats['completed_projects'] / $projectStats['total_projects']) * 100, 1)
            : 0
    ];
}

/**
 * Settings Management Functions
 */

/**
 * Get application setting value
 * @param string $key Setting key
 * @param mixed $default Default value if setting not found
 * @return mixed Setting value
 */
function getSetting($key, $default = null) {
    $query = "SELECT setting_value, setting_type FROM settings WHERE setting_key = ?";
    $result = executeQuerySingle($query, [$key]);
    
    if (!$result) {
        return $default;
    }
    
    $value = $result['setting_value'];
    
    // Convert based on type
    switch ($result['setting_type']) {
        case 'boolean':
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        case 'number':
            return is_numeric($value) ? (float)$value : $default;
        case 'json':
            $decoded = json_decode($value, true);
            return $decoded !== null ? $decoded : $default;
        default:
            return $value;
    }
}

/**
 * Update application setting
 * @param string $key Setting key
 * @param mixed $value Setting value
 * @param int $userId User making the change
 * @return bool Success status
 */
function updateSetting($key, $value, $userId) {
    try {
        // Get setting type
        $settingQuery = "SELECT setting_type, is_editable FROM settings WHERE setting_key = ?";
        $setting = executeQuerySingle($settingQuery, [$key]);
        
        if (!$setting) {
            return false;
        }
        
        if (!$setting['is_editable']) {
            return false;
        }
        
        // Convert value based on type
        switch ($setting['setting_type']) {
            case 'boolean':
                $value = $value ? 'true' : 'false';
                break;
            case 'json':
                $value = json_encode($value);
                break;
            default:
                $value = (string)$value;
        }
        
        $query = "UPDATE settings SET setting_value = ?, updated_by = ? WHERE setting_key = ?";
        return executeUpdate($query, [$value, $userId, $key]);
        
    } catch (Exception $e) {
        error_log("Update setting error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all settings by category
 * @param string $category Settings category
 * @return array Settings data
 */
function getSettingsByCategory($category = null) {
    $query = "SELECT setting_key, setting_value, setting_type, description, is_editable, category 
              FROM settings";
    $params = [];
    
    if ($category) {
        $query .= " WHERE category = ?";
        $params[] = $category;
    }
    
    $query .= " ORDER BY category, setting_key";
    
    return executeQuery($query, $params) ?: [];
}

/**
 * Check if a feature is enabled
 * @param string $feature Feature name (without 'enable_' prefix)
 * @return bool Feature status
 */
function isFeatureEnabled($feature) {
    return getSetting('enable_' . $feature, false);
}

/**
 * Utility Functions
 */

/**
 * Calculate project progress percentage
 * @param int $projectId Project ID
 * @return float Progress percentage
 */
function getProjectProgress($projectId) {
    $query = "SELECT 
        COUNT(*) as total_tasks,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks
        FROM tasks 
        WHERE project_id = ?";
    
    $result = executeQuerySingle($query, [$projectId]);
    
    if (!$result || $result['total_tasks'] == 0) {
        return 0;
    }
    
    return round(($result['completed_tasks'] / $result['total_tasks']) * 100, 1);
}

/**
 * Get project categories from settings
 * @return array Project categories
 */
function getProjectCategories() {
    $query = "SELECT setting_value FROM settings WHERE setting_key = 'project_categories'";
    $result = executeQuerySingle($query);
    
    if ($result) {
        return json_decode($result['setting_value'], true) ?: [];
    }
    
    return ['Web Application', 'Mobile Application', 'API/Service', 'Database', 'Infrastructure'];
}

/**
 * Get task types from settings
 * @return array Task types
 */
function getTaskTypes() {
    $query = "SELECT setting_value FROM settings WHERE setting_key = 'task_types'";
    $result = executeQuerySingle($query);
    
    if ($result) {
        return json_decode($result['setting_value'], true) ?: [];
    }
    
    return ['Security Update', 'Version Upgrade', 'Bug Fix', 'Feature Enhancement', 'Performance Optimization'];
}

/**
 * Generate breadcrumb navigation
 * @param array $items Breadcrumb items
 * @return string HTML for breadcrumbs
 */
function generateBreadcrumb($items) {
    if (empty($items)) {
        return '';
    }
    
    $html = '<nav aria-label="breadcrumb"><ol class="breadcrumb">';
    
    $count = count($items);
    foreach ($items as $index => $item) {
        $isLast = ($index === $count - 1);
        
        $html .= '<li class="breadcrumb-item' . ($isLast ? ' active' : '') . '">';
        
        if (!$isLast && isset($item['url'])) {
            $html .= '<a href="' . $item['url'] . '">' . htmlspecialchars($item['text']) . '</a>';
        } else {
            $html .= htmlspecialchars($item['text']);
        }
        
        $html .= '</li>';
    }
    
    $html .= '</ol></nav>';
    
    return $html;
}

/**
 * Generate pagination HTML
 * @param int $currentPage Current page number
 * @param int $totalPages Total number of pages
 * @param string $baseUrl Base URL for pagination links
 * @return string HTML for pagination
 */
function generatePagination($currentPage, $totalPages, $baseUrl) {
    if ($totalPages <= 1) {
        return '';
    }
    
    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous button
    $prevDisabled = ($currentPage <= 1) ? 'disabled' : '';
    $prevPage = max(1, $currentPage - 1);
    $html .= '<li class="page-item ' . $prevDisabled . '">';
    $html .= '<a class="page-link" href="' . $baseUrl . '&page=' . $prevPage . '">Previous</a>';
    $html .= '</li>';
    
    // Page numbers
    $startPage = max(1, $currentPage - 2);
    $endPage = min($totalPages, $currentPage + 2);
    
    for ($i = $startPage; $i <= $endPage; $i++) {
        $active = ($i === $currentPage) ? 'active' : '';
        $html .= '<li class="page-item ' . $active . '">';
        $html .= '<a class="page-link" href="' . $baseUrl . '&page=' . $i . '">' . $i . '</a>';
        $html .= '</li>';
    }
    
    // Next button
    $nextDisabled = ($currentPage >= $totalPages) ? 'disabled' : '';
    $nextPage = min($totalPages, $currentPage + 1);
    $html .= '<li class="page-item ' . $nextDisabled . '">';
    $html .= '<a class="page-link" href="' . $baseUrl . '&page=' . $nextPage . '">Next</a>';
    $html .= '</li>';
    
    $html .= '</ul></nav>';
    
    return $html;
}

/**
 * Project Template Functions
 */

/**
 * Get all project templates
 * @return array|false Array of templates or false on error
 */
function getProjectTemplates() {
    $query = "SELECT pt.*, 
                     CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
                     (SELECT COUNT(*) FROM template_tasks tt WHERE tt.template_id = pt.id) as task_count
              FROM project_templates pt
              LEFT JOIN users u ON pt.created_by = u.id
              WHERE pt.is_active = 1
              ORDER BY pt.created_at DESC";
    
    return executeQuery($query) ?: [];
}

/**
 * Get a single project template
 * @param int $templateId Template ID
 * @return array|false Template data or false if not found
 */
function getProjectTemplate($templateId) {
    $query = "SELECT pt.*, 
                     CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
                     (SELECT COUNT(*) FROM template_tasks tt WHERE tt.template_id = pt.id) as task_count
              FROM project_templates pt
              LEFT JOIN users u ON pt.created_by = u.id
              WHERE pt.id = ? AND pt.is_active = 1";
    
    $result = executeQuery($query, [$templateId]);
    return $result ? $result[0] : false;
}

/**
 * Create a new project template
 * @param array $data Template data
 * @return array Result with success status and message
 */
function createProjectTemplate($data) {
    try {
        $query = "INSERT INTO project_templates 
                  (name, description, category, default_priority, estimated_duration_days, estimated_hours, created_by) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $result = executeUpdate($query, [
            $data['name'],
            $data['description'],
            $data['category'],
            $data['default_priority'],
            $data['estimated_duration_days'],
            $data['estimated_hours'],
            $data['created_by']
        ]);
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Template created successfully',
                'template_id' => getDB()->lastInsertId()
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to create template'
            ];
        }
    } catch (Exception $e) {
        error_log("Error creating template: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Database error occurred'
        ];
    }
}

/**
 * Delete a project template and its tasks
 * @param int $templateId Template ID
 * @return array Result with success status and message
 */
function deleteProjectTemplate($templateId) {
    try {
        // First delete template tasks (cascade should handle this, but being explicit)
        $deleteTasksQuery = "DELETE FROM template_tasks WHERE template_id = ?";
        executeUpdate($deleteTasksQuery, [$templateId]);
        
        // Then delete the template
        $deleteTemplateQuery = "UPDATE project_templates SET is_active = 0 WHERE id = ?";
        $result = executeUpdate($deleteTemplateQuery, [$templateId]);
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Template deleted successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Template not found or already deleted'
            ];
        }
    } catch (Exception $e) {
        error_log("Error deleting template: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to delete template'
        ];
    }
}

/**
 * Get template tasks for a template
 * @param int $templateId Template ID
 * @return array|false Array of template tasks or false on error
 */
function getTemplateTasks($templateId) {
    $query = "SELECT * FROM template_tasks 
              WHERE template_id = ? 
              ORDER BY order_index ASC, created_at ASC";
    
    return executeQuery($query, [$templateId]) ?: [];
}

/**
 * Apply a template to create a new project
 * @param int $templateId Template ID
 * @param array $projectData Additional project data
 * @return array Result with success status and message
 */
function applyProjectTemplate($templateId, $projectData) {
    try {
        $template = getProjectTemplate($templateId);
        if (!$template) {
            return [
                'success' => false,
                'message' => 'Template not found'
            ];
        }
        
        // Create the project with template defaults
        $projectCreateData = array_merge([
            'category' => $template['category'],
            'priority' => $template['default_priority'],
            'estimated_hours' => $template['estimated_hours']
        ], $projectData);
        
        $projectResult = createProject($projectCreateData);
        if (!$projectResult['success']) {
            return $projectResult;
        }
        
        $projectId = $projectResult['project_id'];
        
        // Get template tasks and create actual tasks
        $templateTasks = getTemplateTasks($templateId);
        $createdTasks = 0;
        
        foreach ($templateTasks as $templateTask) {
            $taskData = [
                'project_id' => $projectId,
                'title' => $templateTask['title'],
                'description' => $templateTask['description'],
                'task_type' => $templateTask['task_type'],
                'priority' => $templateTask['priority'],
                'status' => 'pending',
                'estimated_hours' => $templateTask['estimated_hours'],
                'start_date' => null,
                'due_date' => null
            ];
            
            // Calculate dates based on project start date and days_after_start
            if ($projectData['start_date'] && $templateTask['days_after_start'] > 0) {
                $startDate = new DateTime($projectData['start_date']);
                $startDate->add(new DateInterval('P' . $templateTask['days_after_start'] . 'D'));
                $taskData['start_date'] = $startDate->format('Y-m-d');
            }
            
            $taskResult = createTask($taskData, $projectData['created_by']);
            if ($taskResult['success']) {
                $createdTasks++;
            }
        }
        
        return [
            'success' => true,
            'message' => "Project created successfully from template with {$createdTasks} tasks",
            'project_id' => $projectId
        ];
        
    } catch (Exception $e) {
        error_log("Error applying template: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to apply template'
        ];
    }
}

?>