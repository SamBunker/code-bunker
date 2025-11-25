-- Add configurable status settings to the database
-- Code Bunker - Categories & Statuses Configuration

-- Insert project_statuses setting if it doesn't exist
INSERT INTO settings (setting_key, setting_value, setting_type, description, is_editable, category, updated_at)
SELECT 'project_statuses',
       '{"planning":"Planning","in_progress":"In Progress","testing":"Testing","completed":"Completed","on_hold":"On Hold"}',
       'json',
       'Available status options for projects',
       1,
       'data',
       CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE setting_key = 'project_statuses');

-- Insert task_statuses setting if it doesn't exist
INSERT INTO settings (setting_key, setting_value, setting_type, description, is_editable, category, updated_at)
SELECT 'task_statuses',
       '{"pending":"Pending","in_progress":"In Progress","completed":"Completed","blocked":"Blocked","cancelled":"Cancelled"}',
       'json',
       'Available status options for tasks',
       1,
       'data',
       CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE setting_key = 'task_statuses');

-- Update project_categories if it exists but is empty
UPDATE settings
SET setting_value = '["Web Application","Mobile Application","API/Service","Database","Infrastructure"]',
    setting_type = 'json',
    description = 'Available category options for projects',
    category = 'data',
    updated_at = CURRENT_TIMESTAMP
WHERE setting_key = 'project_categories'
AND (setting_value IS NULL OR setting_value = '' OR setting_value = '[]');

-- Insert project_categories if it doesn't exist
INSERT INTO settings (setting_key, setting_value, setting_type, description, is_editable, category, updated_at)
SELECT 'project_categories',
       '["Web Application","Mobile Application","API/Service","Database","Infrastructure"]',
       'json',
       'Available category options for projects',
       1,
       'data',
       CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE setting_key = 'project_categories');

-- Update task_types if it exists but is empty
UPDATE settings
SET setting_value = '["Security Update","Version Upgrade","Bug Fix","Feature Enhancement","Performance Optimization","Documentation","Testing","Deployment"]',
    setting_type = 'json',
    description = 'Available type options for tasks',
    category = 'data',
    updated_at = CURRENT_TIMESTAMP
WHERE setting_key = 'task_types'
AND (setting_value IS NULL OR setting_value = '' OR setting_value = '[]');

-- Insert task_types if it doesn't exist
INSERT INTO settings (setting_key, setting_value, setting_type, description, is_editable, category, updated_at)
SELECT 'task_types',
       '["Security Update","Version Upgrade","Bug Fix","Feature Enhancement","Performance Optimization","Documentation","Testing","Deployment"]',
       'json',
       'Available type options for tasks',
       1,
       'data',
       CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE setting_key = 'task_types');

-- Verify the settings were created
SELECT setting_key, setting_type, category, is_editable,
       CASE
           WHEN LENGTH(setting_value) > 50 THEN CONCAT(LEFT(setting_value, 50), '...')
           ELSE setting_value
       END AS setting_value_preview
FROM settings
WHERE setting_key IN ('project_statuses', 'task_statuses', 'project_categories', 'task_types')
ORDER BY setting_key;
