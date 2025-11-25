-- Code Bunker Database Backup
-- Generated: 2025-11-24 19:30:20
-- Database: web_app_tracker
-- Application Version: 1.0.1
-- 

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- 
-- Table structure for table `activity_log`
-- 

DROP TABLE IF EXISTS `activity_log`;
CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` enum('project','task','note','user') NOT NULL,
  `entity_id` int(11) NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_activity_log_created_at_desc` (`created_at`),
  CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 
-- Dumping data for table `activity_log`
-- 

INSERT INTO `activity_log` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `old_values`, `new_values`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES ('1', '1', 'login', 'user', '1', NULL, NULL, 'User logged in', '127.0.0.1', NULL, '2025-08-10 08:13:36');
INSERT INTO `activity_log` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `old_values`, `new_values`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES ('2', '1', 'create', 'project', '1', NULL, NULL, 'Created new project', '127.0.0.1', NULL, '2025-08-10 08:15:36');
INSERT INTO `activity_log` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `old_values`, `new_values`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES ('3', '1', 'update', 'task', '1', NULL, NULL, 'Updated task status', '127.0.0.1', NULL, '2025-08-10 08:17:36');
INSERT INTO `activity_log` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `old_values`, `new_values`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES ('19', '1', 'backup_deleted', '', '0', NULL, '{\"filename\":\"code_bunker_backup_2025-11-24_19-28-09.sql\"}', 'Database backup deleted', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 19:29:42');
INSERT INTO `activity_log` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `old_values`, `new_values`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES ('20', '1', 'backup_deleted', '', '0', NULL, '{\"filename\":\"code_bunker_backup_2025-11-24_19-20-09.sql\"}', 'Database backup deleted', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 19:29:44');
INSERT INTO `activity_log` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `old_values`, `new_values`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES ('21', '1', 'backup_deleted', '', '0', NULL, '{\"filename\":\"code_bunker_backup_2025-11-24_19-17-29.sql\"}', 'Database backup deleted', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 19:29:47');
INSERT INTO `activity_log` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `old_values`, `new_values`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES ('22', '1', 'backup_deleted', '', '0', NULL, '{\"filename\":\"code_bunker_backup_2025-11-24_19-28-24.sql\"}', 'Database backup deleted', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 19:29:50');
INSERT INTO `activity_log` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `old_values`, `new_values`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES ('23', '1', 'backup_created', '', '0', NULL, '{\"filename\":\"code_bunker_backup_2025-11-24_19-29-53.sql\",\"size\":40056}', 'Database backup created', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 19:29:53');
INSERT INTO `activity_log` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `old_values`, `new_values`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES ('24', '1', 'delete', 'project', '3', '{\"id\":3,\"name\":\"Customer Mobile App v2.0\",\"description\":\"Native iOS and Android app development for customer self-service portal. Features include real-time notifications, biometric authentication, and offline mode.\",\"category\":\"Mobile Development\",\"priority\":\"high\",\"status\":\"planning\",\"current_version\":null,\"target_version\":null,\"start_date\":\"2025-08-12\",\"due_date\":\"2025-09-24\",\"completion_date\":null,\"estimated_hours\":\"480.00\",\"actual_hours\":\"72.00\",\"budget\":null,\"created_by\":1,\"assigned_to\":2,\"created_at\":\"2025-08-10 20:03:42\",\"updated_at\":\"2025-08-10 20:03:42\",\"estimated_cost\":\"120000.00\",\"actual_cost\":\"18000.00\",\"progress_percentage\":\"15.00\",\"health_status\":\"green\",\"risk_level\":\"low\",\"created_by_name\":\"Admin User\",\"assigned_to_name\":\"Unassigned User\"}', NULL, 'Project deleted', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 19:30:14');

-- 
-- Table structure for table `notes`
-- 

DROP TABLE IF EXISTS `notes`;
CREATE TABLE `notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `task_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `content` text NOT NULL,
  `note_type` enum('general','technical','meeting','decision') DEFAULT 'general',
  `is_private` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_task_id` (`task_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `notes_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notes_ibfk_2` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notes_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 
-- Dumping data for table `notes`
-- 

INSERT INTO `notes` (`id`, `project_id`, `task_id`, `user_id`, `title`, `content`, `note_type`, `is_private`, `created_at`, `updated_at`) VALUES ('3', '4', NULL, '1', 'Urgent: Compliance Deadline', 'Enterprise client contract depends on SOC 2 certification. Must complete by deadline or risk losing $2M annual contract.', 'decision', '0', '2025-08-10 20:03:42', '2025-08-10 20:03:42');
INSERT INTO `notes` (`id`, `project_id`, `task_id`, `user_id`, `title`, `content`, `note_type`, `is_private`, `created_at`, `updated_at`) VALUES ('5', '1', '25', '1', 'Testing', 'Note testing', 'general', '0', '2025-11-24 19:03:31', '2025-11-24 19:03:31');

-- 
-- Table structure for table `project_phases`
-- 

DROP TABLE IF EXISTS `project_phases`;
CREATE TABLE `project_phases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `order_index` int(11) DEFAULT 0,
  `is_collapsed` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_order` (`order_index`),
  CONSTRAINT `project_phases_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 
-- Dumping data for table `project_phases`
-- 

INSERT INTO `project_phases` (`id`, `project_id`, `name`, `description`, `order_index`, `is_collapsed`, `created_at`) VALUES ('1', '1', 'Implementation Phase', 'Main development and implementation tasks', '1', '0', '2025-08-10 12:18:36');

-- 
-- Table structure for table `project_templates`
-- 

DROP TABLE IF EXISTS `project_templates`;
CREATE TABLE `project_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT 'General',
  `default_priority` enum('critical','high','medium','low') DEFAULT 'medium',
  `estimated_duration_days` int(11) DEFAULT 0,
  `estimated_hours` decimal(6,2) DEFAULT 0.00,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_name` (`name`),
  KEY `idx_category` (`category`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `project_templates_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 
-- Dumping data for table `project_templates`
-- 

INSERT INTO `project_templates` (`id`, `name`, `description`, `category`, `default_priority`, `estimated_duration_days`, `estimated_hours`, `created_by`, `created_at`, `updated_at`, `is_active`) VALUES ('1', 'Web Application Update', 'Standard web application modernization and security update template', 'Web Development', 'high', '30', '120.00', '1', '2025-08-10 12:18:36', '2025-08-10 12:18:36', '1');
INSERT INTO `project_templates` (`id`, `name`, `description`, `category`, `default_priority`, `estimated_duration_days`, `estimated_hours`, `created_by`, `created_at`, `updated_at`, `is_active`) VALUES ('2', 'Mobile App Development', 'Complete mobile application development lifecycle template', 'Mobile Development', 'medium', '90', '360.00', '1', '2025-08-10 12:18:36', '2025-08-10 12:18:36', '1');

-- 
-- Table structure for table `projects`
-- 

DROP TABLE IF EXISTS `projects`;
CREATE TABLE `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT 'Web Application',
  `priority` enum('critical','high','medium','low') DEFAULT 'medium',
  `status` enum('planning','in_progress','testing','completed','on_hold') DEFAULT 'planning',
  `current_version` varchar(50) DEFAULT NULL,
  `target_version` varchar(50) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `estimated_hours` decimal(6,2) DEFAULT 0.00,
  `actual_hours` decimal(6,2) DEFAULT 0.00,
  `budget` decimal(10,2) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `estimated_cost` decimal(12,2) DEFAULT 0.00,
  `actual_cost` decimal(12,2) DEFAULT 0.00,
  `progress_percentage` decimal(5,2) DEFAULT 0.00,
  `health_status` enum('green','yellow','red') DEFAULT 'green',
  `risk_level` enum('low','medium','high','critical') DEFAULT 'low',
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `assigned_to` (`assigned_to`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_due_date` (`due_date`),
  KEY `idx_category` (`category`),
  KEY `idx_health_status` (`health_status`),
  KEY `idx_progress` (`progress_percentage`),
  CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `projects_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 
-- Dumping data for table `projects`
-- 

INSERT INTO `projects` (`id`, `name`, `description`, `category`, `priority`, `status`, `current_version`, `target_version`, `start_date`, `due_date`, `completion_date`, `estimated_hours`, `actual_hours`, `budget`, `created_by`, `assigned_to`, `created_at`, `updated_at`, `estimated_cost`, `actual_cost`, `progress_percentage`, `health_status`, `risk_level`) VALUES ('1', 'Sample Web Application Update', 'Modernize legacy web application with latest security patches', 'Web Application', 'high', 'in_progress', NULL, NULL, '2025-08-10', '2025-09-09', NULL, '0.00', '0.00', NULL, '1', '2', '2025-08-10 12:18:36', '2025-08-10 12:18:36', '0.00', '0.00', '0.00', 'green', 'low');
INSERT INTO `projects` (`id`, `name`, `description`, `category`, `priority`, `status`, `current_version`, `target_version`, `start_date`, `due_date`, `completion_date`, `estimated_hours`, `actual_hours`, `budget`, `created_by`, `assigned_to`, `created_at`, `updated_at`, `estimated_cost`, `actual_cost`, `progress_percentage`, `health_status`, `risk_level`) VALUES ('4', 'SOC 2 Compliance Audit', 'Comprehensive security audit and compliance certification for SOC 2 Type II. Required for enterprise client contracts.', 'Security & Compliance', 'critical', 'in_progress', NULL, NULL, '2025-08-07', '2025-08-20', NULL, '200.00', '60.00', NULL, '1', '1', '2025-08-10 20:03:42', '2025-08-10 20:03:42', '50000.00', '15000.00', '30.00', 'red', 'high');
INSERT INTO `projects` (`id`, `name`, `description`, `category`, `priority`, `status`, `current_version`, `target_version`, `start_date`, `due_date`, `completion_date`, `estimated_hours`, `actual_hours`, `budget`, `created_by`, `assigned_to`, `created_at`, `updated_at`, `estimated_cost`, `actual_cost`, `progress_percentage`, `health_status`, `risk_level`) VALUES ('5', 'RESTful API v3.0', 'Complete REST API rebuild with GraphQL support, OAuth 2.0 authentication, and comprehensive documentation.', 'Backend Development', 'medium', 'completed', NULL, NULL, '2025-07-11', '2025-08-08', '2025-08-08', '240.00', '235.00', NULL, '1', '2', '2025-08-10 20:03:42', '2025-08-10 20:03:42', '60000.00', '58750.00', '100.00', 'green', 'low');
INSERT INTO `projects` (`id`, `name`, `description`, `category`, `priority`, `status`, `current_version`, `target_version`, `start_date`, `due_date`, `completion_date`, `estimated_hours`, `actual_hours`, `budget`, `created_by`, `assigned_to`, `created_at`, `updated_at`, `estimated_cost`, `actual_cost`, `progress_percentage`, `health_status`, `risk_level`) VALUES ('6', 'AWS Cloud Migration', 'Migrate on-premise infrastructure to AWS cloud with auto-scaling, disaster recovery, and multi-region deployment.', 'Infrastructure', 'medium', 'planning', NULL, NULL, '2025-08-17', '2025-10-09', NULL, '360.00', '18.00', NULL, '1', '2', '2025-08-10 20:03:42', '2025-08-10 20:03:42', '90000.00', '4500.00', '5.00', 'green', 'medium');

-- 
-- Table structure for table `report_templates`
-- 

DROP TABLE IF EXISTS `report_templates`;
CREATE TABLE `report_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` enum('executive','operational','financial','resource','custom','wbs') DEFAULT 'custom',
  `report_type` varchar(100) NOT NULL,
  `template_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`template_config`)),
  `filters_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filters_config`)),
  `is_public` tinyint(1) DEFAULT 0,
  `is_system_template` tinyint(1) DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_category` (`category`),
  KEY `idx_report_type` (`report_type`),
  CONSTRAINT `report_templates_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 
-- Dumping data for table `report_templates`
-- 

INSERT INTO `report_templates` (`id`, `name`, `description`, `category`, `report_type`, `template_config`, `filters_config`, `is_public`, `is_system_template`, `created_by`, `created_at`, `updated_at`) VALUES ('1', 'Executive Dashboard', 'High-level overview for executives and stakeholders', 'executive', 'executive_dashboard', '{\"metrics\": [\"total_projects\", \"budget_utilization\", \"schedule_performance\", \"team_productivity\"], \"charts\": [\"project_status_pie\", \"budget_trend_line\", \"milestone_timeline\"], \"format\": \"dashboard\"}', NULL, '1', '1', '1', '2025-08-10 20:02:33', '2025-08-10 20:02:33');
INSERT INTO `report_templates` (`id`, `name`, `description`, `category`, `report_type`, `template_config`, `filters_config`, `is_public`, `is_system_template`, `created_by`, `created_at`, `updated_at`) VALUES ('2', 'Work Breakdown Structure', 'Hierarchical project breakdown with progress tracking', 'wbs', 'work_breakdown_structure', '{\"display_type\": \"hierarchical\", \"show_progress\": true, \"show_costs\": true, \"show_timeline\": true, \"export_formats\": [\"pdf\", \"excel\"]}', NULL, '1', '1', '1', '2025-08-10 20:02:33', '2025-08-10 20:02:33');

-- 
-- Table structure for table `settings`
-- 

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('boolean','string','number','json') DEFAULT 'string',
  `description` text DEFAULT NULL,
  `is_editable` tinyint(1) DEFAULT 1,
  `category` varchar(50) DEFAULT 'general',
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `updated_by` (`updated_by`),
  KEY `idx_setting_key` (`setting_key`),
  CONSTRAINT `settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 
-- Dumping data for table `settings`
-- 

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `is_editable`, `category`, `updated_by`, `updated_at`) VALUES ('1', 'enable_budget_tracking', 'false', 'boolean', 'Enable budget tracking for projects and reporting', '1', 'features', '1', '2025-08-10 12:18:36');
INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `is_editable`, `category`, `updated_by`, `updated_at`) VALUES ('2', 'enable_time_tracking', 'true', 'boolean', 'Enable time tracking for tasks', '1', 'features', '1', '2025-08-10 12:18:36');
INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `is_editable`, `category`, `updated_by`, `updated_at`) VALUES ('3', 'enable_notifications', 'true', 'boolean', 'Enable email notifications for task updates', '1', 'features', '1', '2025-08-10 12:18:36');
INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `is_editable`, `category`, `updated_by`, `updated_at`) VALUES ('4', 'default_priority', 'medium', 'string', 'Default priority for new projects and tasks', '1', 'defaults', '1', '2025-08-10 12:18:36');
INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `is_editable`, `category`, `updated_by`, `updated_at`) VALUES ('5', 'items_per_page', '25', 'number', 'Number of items to display per page in lists', '1', 'general', '1', '2025-08-10 12:18:36');

-- 
-- Table structure for table `tasks`
-- 

DROP TABLE IF EXISTS `tasks`;
CREATE TABLE `tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `phase_id` int(11) DEFAULT NULL,
  `order_index` int(11) DEFAULT 0,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `task_type` varchar(100) DEFAULT 'General',
  `priority` enum('critical','high','medium','low') DEFAULT 'medium',
  `status` enum('pending','in_progress','testing','completed','blocked') DEFAULT 'pending',
  `assigned_to` int(11) DEFAULT NULL,
  `depends_on_task_id` int(11) DEFAULT NULL,
  `estimated_hours` decimal(5,2) DEFAULT 0.00,
  `actual_hours` decimal(5,2) DEFAULT 0.00,
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `depends_on_task_id` (`depends_on_task_id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_phase_id` (`phase_id`),
  KEY `idx_order_index` (`order_index`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_due_date` (`due_date`),
  KEY `idx_assigned_to` (`assigned_to`),
  CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`phase_id`) REFERENCES `project_phases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tasks_ibfk_3` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tasks_ibfk_4` FOREIGN KEY (`depends_on_task_id`) REFERENCES `tasks` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 
-- Dumping data for table `tasks`
-- 

INSERT INTO `tasks` (`id`, `project_id`, `phase_id`, `order_index`, `title`, `description`, `task_type`, `priority`, `status`, `assigned_to`, `depends_on_task_id`, `estimated_hours`, `actual_hours`, `start_date`, `due_date`, `created_at`, `updated_at`, `completed_at`) VALUES ('1', '1', '1', '0', 'Update PHP to latest version', 'Upgrade PHP from 7.4 to 8.2 for security and performance improvements', 'General', 'high', 'in_progress', '2', NULL, '0.00', '0.00', NULL, '2025-08-17', '2025-08-10 12:18:36', '2025-08-10 12:18:36', NULL);
INSERT INTO `tasks` (`id`, `project_id`, `phase_id`, `order_index`, `title`, `description`, `task_type`, `priority`, `status`, `assigned_to`, `depends_on_task_id`, `estimated_hours`, `actual_hours`, `start_date`, `due_date`, `created_at`, `updated_at`, `completed_at`) VALUES ('14', '4', NULL, '0', 'Access Control Review', 'Audit user access controls and permissions', 'General', 'critical', 'completed', '1', NULL, '24.00', '28.00', NULL, '2025-08-09', '2025-08-10 20:03:42', '2025-08-10 20:03:42', NULL);
INSERT INTO `tasks` (`id`, `project_id`, `phase_id`, `order_index`, `title`, `description`, `task_type`, `priority`, `status`, `assigned_to`, `depends_on_task_id`, `estimated_hours`, `actual_hours`, `start_date`, `due_date`, `created_at`, `updated_at`, `completed_at`) VALUES ('15', '4', NULL, '0', 'Data Encryption Audit', 'Verify encryption at rest and in transit', 'General', 'critical', 'in_progress', '2', NULL, '32.00', '16.00', NULL, '2025-08-12', '2025-08-10 20:03:42', '2025-08-10 20:03:42', NULL);
INSERT INTO `tasks` (`id`, `project_id`, `phase_id`, `order_index`, `title`, `description`, `task_type`, `priority`, `status`, `assigned_to`, `depends_on_task_id`, `estimated_hours`, `actual_hours`, `start_date`, `due_date`, `created_at`, `updated_at`, `completed_at`) VALUES ('16', '4', NULL, '0', 'Incident Response Plan', 'Document and test incident response procedures', 'General', 'high', 'pending', '1', NULL, '40.00', '0.00', NULL, '2025-08-16', '2025-08-10 20:03:42', '2025-08-10 20:03:42', NULL);
INSERT INTO `tasks` (`id`, `project_id`, `phase_id`, `order_index`, `title`, `description`, `task_type`, `priority`, `status`, `assigned_to`, `depends_on_task_id`, `estimated_hours`, `actual_hours`, `start_date`, `due_date`, `created_at`, `updated_at`, `completed_at`) VALUES ('17', '4', NULL, '0', 'Vulnerability Assessment', 'Complete penetration testing and remediation', 'General', 'critical', 'pending', '2', NULL, '48.00', '0.00', NULL, '2025-08-18', '2025-08-10 20:03:42', '2025-08-10 20:03:42', NULL);
INSERT INTO `tasks` (`id`, `project_id`, `phase_id`, `order_index`, `title`, `description`, `task_type`, `priority`, `status`, `assigned_to`, `depends_on_task_id`, `estimated_hours`, `actual_hours`, `start_date`, `due_date`, `created_at`, `updated_at`, `completed_at`) VALUES ('18', '5', NULL, '0', 'API Architecture Design', 'Design RESTful endpoints and data models', 'General', 'high', 'completed', '2', NULL, '40.00', '38.00', NULL, '2025-07-21', '2025-08-10 20:03:42', '2025-08-10 20:03:42', '2025-07-21 20:03:42');
INSERT INTO `tasks` (`id`, `project_id`, `phase_id`, `order_index`, `title`, `description`, `task_type`, `priority`, `status`, `assigned_to`, `depends_on_task_id`, `estimated_hours`, `actual_hours`, `start_date`, `due_date`, `created_at`, `updated_at`, `completed_at`) VALUES ('19', '5', NULL, '0', 'OAuth 2.0 Implementation', 'Implement secure authentication flow', 'General', 'critical', 'completed', '1', NULL, '48.00', '52.00', NULL, '2025-07-26', '2025-08-10 20:03:42', '2025-08-10 20:03:42', '2025-07-27 20:03:42');
INSERT INTO `tasks` (`id`, `project_id`, `phase_id`, `order_index`, `title`, `description`, `task_type`, `priority`, `status`, `assigned_to`, `depends_on_task_id`, `estimated_hours`, `actual_hours`, `start_date`, `due_date`, `created_at`, `updated_at`, `completed_at`) VALUES ('20', '5', NULL, '0', 'GraphQL Integration', 'Add GraphQL endpoint alongside REST', 'General', 'medium', 'completed', '2', NULL, '60.00', '58.00', NULL, '2025-08-02', '2025-08-10 20:03:42', '2025-08-10 20:03:42', '2025-08-01 20:03:42');
INSERT INTO `tasks` (`id`, `project_id`, `phase_id`, `order_index`, `title`, `description`, `task_type`, `priority`, `status`, `assigned_to`, `depends_on_task_id`, `estimated_hours`, `actual_hours`, `start_date`, `due_date`, `created_at`, `updated_at`, `completed_at`) VALUES ('21', '5', NULL, '0', 'API Documentation', 'Swagger/OpenAPI documentation', 'General', 'high', 'completed', '1', NULL, '24.00', '22.00', NULL, '2025-08-07', '2025-08-10 20:03:42', '2025-08-10 20:03:42', '2025-08-06 20:03:42');
INSERT INTO `tasks` (`id`, `project_id`, `phase_id`, `order_index`, `title`, `description`, `task_type`, `priority`, `status`, `assigned_to`, `depends_on_task_id`, `estimated_hours`, `actual_hours`, `start_date`, `due_date`, `created_at`, `updated_at`, `completed_at`) VALUES ('22', '6', NULL, '0', 'Infrastructure Assessment', 'Document current infrastructure and dependencies', 'General', 'high', 'pending', '1', NULL, '40.00', '0.00', NULL, '2025-08-22', '2025-08-10 20:03:42', '2025-08-10 20:03:42', NULL);
INSERT INTO `tasks` (`id`, `project_id`, `phase_id`, `order_index`, `title`, `description`, `task_type`, `priority`, `status`, `assigned_to`, `depends_on_task_id`, `estimated_hours`, `actual_hours`, `start_date`, `due_date`, `created_at`, `updated_at`, `completed_at`) VALUES ('23', '6', NULL, '0', 'AWS Architecture Design', 'Design VPC, subnets, and security groups', 'General', 'high', 'pending', '2', NULL, '48.00', '0.00', NULL, '2025-08-28', '2025-08-10 20:03:42', '2025-08-10 20:03:42', NULL);
INSERT INTO `tasks` (`id`, `project_id`, `phase_id`, `order_index`, `title`, `description`, `task_type`, `priority`, `status`, `assigned_to`, `depends_on_task_id`, `estimated_hours`, `actual_hours`, `start_date`, `due_date`, `created_at`, `updated_at`, `completed_at`) VALUES ('24', '6', NULL, '0', 'Database Migration Plan', 'Plan RDS migration with minimal downtime', 'General', 'critical', 'pending', '1', NULL, '32.00', '0.00', NULL, '2025-09-01', '2025-08-10 20:03:42', '2025-08-10 20:03:42', NULL);
INSERT INTO `tasks` (`id`, `project_id`, `phase_id`, `order_index`, `title`, `description`, `task_type`, `priority`, `status`, `assigned_to`, `depends_on_task_id`, `estimated_hours`, `actual_hours`, `start_date`, `due_date`, `created_at`, `updated_at`, `completed_at`) VALUES ('25', '1', '1', '0', 'Code Review', 'Review the current code and note critically deprecated functions', 'General', 'medium', 'in_progress', '1', NULL, '4.00', '0.00', NULL, NULL, '2025-08-11 10:09:47', '2025-11-24 18:53:29', NULL);

-- 
-- Table structure for table `template_phases`
-- 

DROP TABLE IF EXISTS `template_phases`;
CREATE TABLE `template_phases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `order_index` int(11) DEFAULT 0,
  `is_collapsed` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_template_id` (`template_id`),
  KEY `idx_order` (`order_index`),
  CONSTRAINT `template_phases_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `project_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 
-- Dumping data for table `template_phases`
-- 

INSERT INTO `template_phases` (`id`, `template_id`, `name`, `description`, `order_index`, `is_collapsed`, `created_at`) VALUES ('1', '1', 'Planning & Assessment', 'Initial analysis and planning phase', '1', '0', '2025-08-10 12:18:36');
INSERT INTO `template_phases` (`id`, `template_id`, `name`, `description`, `order_index`, `is_collapsed`, `created_at`) VALUES ('2', '1', 'Development & Updates', 'Core development and system updates', '2', '0', '2025-08-10 12:18:36');
INSERT INTO `template_phases` (`id`, `template_id`, `name`, `description`, `order_index`, `is_collapsed`, `created_at`) VALUES ('3', '1', 'Testing & Deployment', 'Quality assurance and production deployment', '3', '0', '2025-08-10 12:18:36');

-- 
-- Table structure for table `template_tasks`
-- 

DROP TABLE IF EXISTS `template_tasks`;
CREATE TABLE `template_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_id` int(11) NOT NULL,
  `phase_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `task_type` varchar(100) DEFAULT 'General',
  `priority` enum('critical','high','medium','low') DEFAULT 'medium',
  `estimated_hours` decimal(5,2) DEFAULT 0.00,
  `order_index` int(11) DEFAULT 0,
  `depends_on_template_task_id` int(11) DEFAULT NULL,
  `days_after_start` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `depends_on_template_task_id` (`depends_on_template_task_id`),
  KEY `idx_template_id` (`template_id`),
  KEY `idx_phase_id` (`phase_id`),
  KEY `idx_order_index` (`order_index`),
  CONSTRAINT `template_tasks_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `project_templates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `template_tasks_ibfk_2` FOREIGN KEY (`phase_id`) REFERENCES `template_phases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `template_tasks_ibfk_3` FOREIGN KEY (`depends_on_template_task_id`) REFERENCES `template_tasks` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 
-- Dumping data for table `template_tasks`
-- 

INSERT INTO `template_tasks` (`id`, `template_id`, `phase_id`, `title`, `description`, `task_type`, `priority`, `estimated_hours`, `order_index`, `depends_on_template_task_id`, `days_after_start`, `created_at`) VALUES ('1', '1', '1', 'Security Assessment', 'Perform comprehensive security audit', 'Security Updates', 'critical', '16.00', '1', NULL, '0', '2025-08-10 12:18:36');
INSERT INTO `template_tasks` (`id`, `template_id`, `phase_id`, `title`, `description`, `task_type`, `priority`, `estimated_hours`, `order_index`, `depends_on_template_task_id`, `days_after_start`, `created_at`) VALUES ('2', '1', '1', 'Requirements Analysis', 'Analyze current system and define upgrade requirements', 'Documentation Updates', 'high', '12.00', '2', NULL, '1', '2025-08-10 12:18:36');
INSERT INTO `template_tasks` (`id`, `template_id`, `phase_id`, `title`, `description`, `task_type`, `priority`, `estimated_hours`, `order_index`, `depends_on_template_task_id`, `days_after_start`, `created_at`) VALUES ('3', '1', '2', 'Update Framework Dependencies', 'Update all framework and library dependencies to latest secure versions', 'Version Upgrades', 'high', '24.00', '1', NULL, '3', '2025-08-10 12:18:36');
INSERT INTO `template_tasks` (`id`, `template_id`, `phase_id`, `title`, `description`, `task_type`, `priority`, `estimated_hours`, `order_index`, `depends_on_template_task_id`, `days_after_start`, `created_at`) VALUES ('4', '1', '2', 'Database Migration', 'Update database schema and migrate data', 'Version Upgrades', 'high', '20.00', '2', NULL, '7', '2025-08-10 12:18:36');
INSERT INTO `template_tasks` (`id`, `template_id`, `phase_id`, `title`, `description`, `task_type`, `priority`, `estimated_hours`, `order_index`, `depends_on_template_task_id`, `days_after_start`, `created_at`) VALUES ('5', '1', '2', 'UI/UX Modernization', 'Update user interface with modern design patterns', 'UI/UX Improvements', 'medium', '32.00', '3', NULL, '10', '2025-08-10 12:18:36');
INSERT INTO `template_tasks` (`id`, `template_id`, `phase_id`, `title`, `description`, `task_type`, `priority`, `estimated_hours`, `order_index`, `depends_on_template_task_id`, `days_after_start`, `created_at`) VALUES ('6', '1', '2', 'Performance Optimization', 'Optimize application performance and loading times', 'Performance Optimization', 'medium', '16.00', '4', NULL, '15', '2025-08-10 12:18:36');
INSERT INTO `template_tasks` (`id`, `template_id`, `phase_id`, `title`, `description`, `task_type`, `priority`, `estimated_hours`, `order_index`, `depends_on_template_task_id`, `days_after_start`, `created_at`) VALUES ('7', '1', '3', 'Testing & QA', 'Comprehensive testing including unit, integration, and user acceptance tests', 'Testing', 'high', '24.00', '1', NULL, '20', '2025-08-10 12:18:36');
INSERT INTO `template_tasks` (`id`, `template_id`, `phase_id`, `title`, `description`, `task_type`, `priority`, `estimated_hours`, `order_index`, `depends_on_template_task_id`, `days_after_start`, `created_at`) VALUES ('8', '1', '3', 'Documentation Update', 'Update user and technical documentation', 'Documentation Updates', 'medium', '8.00', '2', NULL, '25', '2025-08-10 12:18:36');
INSERT INTO `template_tasks` (`id`, `template_id`, `phase_id`, `title`, `description`, `task_type`, `priority`, `estimated_hours`, `order_index`, `depends_on_template_task_id`, `days_after_start`, `created_at`) VALUES ('9', '1', '3', 'Production Deployment', 'Deploy updated application to production environment', 'Deployment', 'critical', '12.00', '3', NULL, '27', '2025-08-10 12:18:36');

-- 
-- Table structure for table `users`
-- 

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_username` (`username`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 
-- Dumping data for table `users`
-- 

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `role`, `first_name`, `last_name`, `created_at`, `last_login`, `is_active`) VALUES ('1', 'admin', 'admin@example.com', '$2y$10$1VNUNnavoSUz9wG83JoTBudjmJmYwJ13NSuo8jJtSDG0uqE1FdAVG', 'admin', 'Admin', 'User', '2025-08-10 12:18:36', '2025-11-24 18:52:54', '1');
INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `role`, `first_name`, `last_name`, `created_at`, `last_login`, `is_active`) VALUES ('2', 'user', 'user@example.com', '$2y$10$x09RWm1..lfHFa4gcDGuCOdSztwPIZ2Glwn1tTYtm2GQBaGvo6Ph2', 'user', 'Unassigned', 'User', '2025-08-10 12:18:36', NULL, '1');

-- 
-- Table structure for table `work_breakdown_structure`
-- 

DROP TABLE IF EXISTS `work_breakdown_structure`;
CREATE TABLE `work_breakdown_structure` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `wbs_code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `work_package_type` enum('project','deliverable','work_package','task') DEFAULT 'work_package',
  `estimated_hours` decimal(8,2) DEFAULT 0.00,
  `actual_hours` decimal(8,2) DEFAULT 0.00,
  `estimated_cost` decimal(12,2) DEFAULT 0.00,
  `actual_cost` decimal(12,2) DEFAULT 0.00,
  `progress_percentage` decimal(5,2) DEFAULT 0.00,
  `status` enum('not_started','in_progress','completed','on_hold','cancelled') DEFAULT 'not_started',
  `priority` enum('critical','high','medium','low') DEFAULT 'medium',
  `assigned_to` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_wbs_code_per_project` (`project_id`,`wbs_code`),
  KEY `assigned_to` (`assigned_to`),
  KEY `created_by` (`created_by`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_wbs_code` (`wbs_code`),
  KEY `idx_status` (`status`),
  CONSTRAINT `work_breakdown_structure_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `work_breakdown_structure_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `work_breakdown_structure` (`id`) ON DELETE CASCADE,
  CONSTRAINT `work_breakdown_structure_ibfk_3` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`),
  CONSTRAINT `work_breakdown_structure_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;
