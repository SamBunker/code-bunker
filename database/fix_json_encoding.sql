-- Fix double-encoded JSON in settings
-- Code Bunker

-- Reset project_categories to clean JSON
UPDATE settings
SET setting_value = '["Web Application","Mobile Application","API/Service","Database","Infrastructure"]'
WHERE setting_key = 'project_categories';

-- Reset task_types to clean JSON
UPDATE settings
SET setting_value = '["Security Update","Version Upgrade","Bug Fix","Feature Enhancement","Performance Optimization","Documentation","Testing","Deployment"]'
WHERE setting_key = 'task_types';

-- Reset project_statuses to clean JSON
UPDATE settings
SET setting_value = '{"planning":"Planning","in_progress":"In Progress","testing":"Testing","completed":"Completed","on_hold":"On Hold"}'
WHERE setting_key = 'project_statuses';

-- Reset task_statuses to clean JSON
UPDATE settings
SET setting_value = '{"pending":"Pending","in_progress":"In Progress","completed":"Completed","blocked":"Blocked","cancelled":"Cancelled"}'
WHERE setting_key = 'task_statuses';

-- Verify the results
SELECT setting_key, setting_value, setting_type
FROM settings
WHERE setting_key IN ('project_categories', 'task_types', 'project_statuses', 'task_statuses')
ORDER BY setting_key;
