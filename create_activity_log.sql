-- Create missing activity_log table for Recent Activity feature
-- This table tracks user actions like login, logout, and CRUD operations

CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type ENUM('project', 'task', 'note', 'user') NOT NULL,
    entity_id INT NOT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create index for better performance on recent activity queries
CREATE INDEX IF NOT EXISTS idx_activity_log_created_at_desc ON activity_log(created_at DESC);

-- Add some sample activity data for demonstration
INSERT IGNORE INTO activity_log (user_id, action, entity_type, entity_id, description, ip_address, created_at) VALUES
(1, 'login', 'user', 1, 'User logged in', '127.0.0.1', NOW() - INTERVAL 5 MINUTE),
(1, 'create', 'project', 1, 'Created new project', '127.0.0.1', NOW() - INTERVAL 3 MINUTE),
(1, 'update', 'task', 1, 'Updated task status', '127.0.0.1', NOW() - INTERVAL 1 MINUTE);