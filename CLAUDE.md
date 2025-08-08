# Code Bunker

## Project Overview
A comprehensive, enterprise-grade project management system designed to track and manage development projects securely. This system addresses the core needs of development teams managing multiple projects that require updates, security patches, and enhancements. Features configurable tracking options for both time-focused and budget-focused project management.

## Purpose
This project showcases full-stack web development skills aligned with modern enterprise requirements:
- Full-cycle development from requirements to deployment
- Security-first approach with proper authentication and data protection  
- Interactive dashboards for decision making and reporting
- Scalable architecture supporting multiple projects and users
- Modern web technologies with responsive design
- **WCAG 2.1 AA accessibility compliance**
- **Lighthouse performance score 95+**
- **Configurable feature system for different organizational needs**

## Technology Stack

### Backend
- **PHP 8.x**: Server-side scripting with object-oriented programming
- **MySQL**: Relational database for data persistence with normalized schema
- **PDO**: Secure database connections with prepared statements
- **bcrypt**: Password hashing for user authentication
- **Session Management**: Secure user sessions with proper timeout handling
- **CSRF Protection**: Cross-site request forgery prevention

### Frontend  
- **Bootstrap 5**: Responsive CSS framework with accessibility features
- **JavaScript ES6+**: Modern client-side interactivity
- **Calendar.js**: Interactive timeline visualization for project schedules
- **Chart.js**: Dynamic data visualization for reporting dashboard
- **Service Worker**: PWA capabilities with offline caching
- **Intersection Observer**: Performance-optimized lazy loading

### Accessibility & Performance
- **WCAG 2.1 AA Compliance**: Skip links, ARIA attributes, keyboard navigation
- **Lighthouse Optimized**: 95+ performance score with optimized assets
- **Progressive Web App**: Installable with offline capabilities
- **Resource Optimization**: Minified CSS/JS, image optimization, caching

### Development Environment
- **XAMPP**: Local development server (Apache, MySQL, PHP)
- **Git**: Version control with branching strategy
- **Modern Development**: ES6+ JavaScript, CSS Grid/Flexbox

## Core Features

### 1. User Authentication & Security
- Secure user registration with email validation
- bcrypt password hashing with salt rounds
- Session management with timeout and regeneration
- Role-based access control (Admin/User) with permissions
- SQL injection prevention with prepared statements
- CSRF token protection on forms
- Input validation and sanitization with whitelisting
- Secure password requirements and strength validation

### 2. Project Management
- **Full CRUD Operations**: Create, Read, Update, Delete projects with validation
- **Configurable Categories**: Customizable project types via admin settings
- **Priority Management**: Critical, High, Medium, Low with color coding
- **Advanced Status Tracking**: Planning, In Progress, Testing, Completed, On Hold
- **Timeline Management**: Start dates, due dates with calendar integration
- **Progress Calculation**: Automated progress based on task completion
- **Assignment System**: Multi-user assignment with notification system
- **Bulk Operations**: Mass update projects with filtering

### 3. Task Management
- **Hierarchical Task System**: Tasks linked to projects with full CRUD
- **Configurable Task Types**: Admin-customizable task categories:
  - Security Updates (CVE patches, SSL certificates)
  - Version Upgrades (PHP, framework, library updates)
  - UI/UX Improvements (accessibility, responsive design)
  - Performance Optimization (caching, database tuning)
  - Documentation Updates (user guides, technical docs)
  - Testing (unit, integration, user acceptance)
  - Deployment (staging, production rollouts)
- **Task Dependencies**: Link prerequisite tasks with validation
- **Time Tracking**: Estimated vs actual hours with reporting
- **Advanced Assignment**: User assignment with workload balancing
- **Status Workflow**: Pending → In Progress → Testing → Completed
- **Due Date Management**: Individual task deadlines with alerts

### 4. Interactive Calendar & Timeline
- **Calendar.js Integration**: Full-featured interactive calendar
- **Project Timeline View**: Gantt-style visualization
- **Milestone Tracking**: Key deliverables and deadlines
- **Resource Planning**: Team workload visualization
- **Deadline Management**: Color-coded urgency indicators
- **Date Range Filtering**: Focus on specific time periods
- **Drag-and-Drop**: Task rescheduling (future enhancement)

### 5. Comprehensive Notes & Documentation
- **Project Documentation**: Rich text notes with formatting
- **Task-Level Comments**: Progress updates and technical details
- **File Attachment System**: Upload documents, screenshots, specs
- **Activity Logging**: Automatic tracking of all modifications
- **Version History**: Track changes over time
- **Search Integration**: Full-text search across all notes
- **Privacy Controls**: Private vs public notes

### 6. Advanced Reporting & Analytics
- **Executive Dashboard**: Real-time KPI visualization with Chart.js
- **5 Report Types**:
  - Summary Dashboard: Overview statistics and trends
  - Project Status Reports: Breakdown by status with metrics
  - Task Completion Analysis: Performance by priority/status
  - Team Productivity Reports: Individual performance metrics
  - Timeline Reports: Project deadlines and progress tracking
- **Dynamic Filtering**: Date ranges, status, priority, assignments
- **Export Functionality**: CSV exports for all report types
- **Visual Charts**: Pie charts, bar graphs, progress indicators
- **Custom Date Ranges**: Flexible reporting periods

### 7. System Configuration & Administration
- **Feature Toggle System**: Enable/disable major features
- **Budget Tracking Control**: Toggle budget fields system-wide
- **Configurable Settings**: 
  - Application preferences (timezone, pagination)
  - Data options (categories, task types, colors)
  - Default values (priorities, auto-assignment)
  - Feature flags (notifications, calendar, file uploads)
- **Admin Panel**: Comprehensive settings management interface
- **User Management**: Role assignment and permission control
- **System Monitoring**: Activity logs and performance tracking

### 8. Performance & Accessibility Features
- **WCAG 2.1 AA Compliance**: 
  - Skip navigation links
  - ARIA labels and roles
  - Keyboard navigation support
  - Screen reader optimization
  - Color contrast compliance
- **Performance Optimization**:
  - Service Worker for caching
  - Lazy loading with Intersection Observer
  - Minified and compressed assets
  - Database query optimization
  - Image optimization and lazy loading
- **Progressive Web App**: Installable with offline capabilities
- **Mobile Responsive**: Bootstrap 5 with custom responsive enhancements

## Database Schema

### Users Table
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- username (VARCHAR(50), UNIQUE, NOT NULL)
- email (VARCHAR(100), UNIQUE, NOT NULL)
- password_hash (VARCHAR(255), NOT NULL)
- role (ENUM: 'admin', 'user', DEFAULT 'user')
- first_name (VARCHAR(50))
- last_name (VARCHAR(50))
- created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- last_login (TIMESTAMP, NULL)
- is_active (BOOLEAN, DEFAULT TRUE)
- Indexes: username, email
```

### Projects Table
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- name (VARCHAR(255), NOT NULL)
- description (TEXT)
- category (VARCHAR(100), DEFAULT 'Development Project')
- priority (ENUM: 'critical', 'high', 'medium', 'low', DEFAULT 'medium')
- status (ENUM: 'planning', 'in_progress', 'testing', 'completed', 'on_hold', DEFAULT 'planning')
- current_version (VARCHAR(50))
- target_version (VARCHAR(50))
- start_date (DATE)
- due_date (DATE)
- completion_date (DATE, NULL)
- estimated_hours (DECIMAL(6,2), DEFAULT 0)
- actual_hours (DECIMAL(6,2), DEFAULT 0)
- budget (DECIMAL(10,2)) # Toggleable via settings
- created_by (INT, FOREIGN KEY to users.id, NOT NULL)
- assigned_to (INT, FOREIGN KEY to users.id)
- created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- updated_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)
- Indexes: status, priority, due_date, category
```

### Tasks Table
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- project_id (INT, FOREIGN KEY to projects.id, NOT NULL)
- title (VARCHAR(255), NOT NULL)
- description (TEXT)
- task_type (VARCHAR(100), DEFAULT 'General')
- priority (ENUM: 'critical', 'high', 'medium', 'low', DEFAULT 'medium')
- status (ENUM: 'pending', 'in_progress', 'testing', 'completed', 'blocked', DEFAULT 'pending')
- assigned_to (INT, FOREIGN KEY to users.id)
- depends_on_task_id (INT, FOREIGN KEY to tasks.id) # Task dependencies
- estimated_hours (DECIMAL(5,2), DEFAULT 0)
- actual_hours (DECIMAL(5,2), DEFAULT 0)
- start_date (DATE)
- due_date (DATE)
- created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- updated_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)
- completed_at (TIMESTAMP, NULL)
- Indexes: project_id, status, priority, due_date, assigned_to
```

### Notes Table
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- project_id (INT, FOREIGN KEY to projects.id, NOT NULL)
- task_id (INT, FOREIGN KEY to tasks.id, NULL) # NULL for project notes
- user_id (INT, FOREIGN KEY to users.id, NOT NULL)
- title (VARCHAR(255))
- content (TEXT, NOT NULL)
- note_type (ENUM: 'general', 'technical', 'meeting', 'decision', DEFAULT 'general')
- is_private (BOOLEAN, DEFAULT FALSE)
- created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- updated_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)
- Indexes: project_id, task_id, user_id, created_at
```

### Settings Table (New)
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- setting_key (VARCHAR(100), UNIQUE, NOT NULL)
- setting_value (TEXT)
- setting_type (ENUM: 'boolean', 'string', 'number', 'json', DEFAULT 'string')
- description (TEXT)
- is_editable (BOOLEAN, DEFAULT TRUE)
- category (VARCHAR(50), DEFAULT 'general')
- updated_by (INT, FOREIGN KEY to users.id)
- updated_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)
- Index: setting_key
```

### Activity Log Table (New)
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- user_id (INT, FOREIGN KEY to users.id, NOT NULL)
- action (VARCHAR(50), NOT NULL) # create, update, delete, login, etc.
- entity_type (VARCHAR(50)) # project, task, note, user
- entity_id (INT) # ID of the affected entity
- old_values (JSON) # Previous values for updates
- new_values (JSON) # New values for updates
- description (TEXT)
- ip_address (VARCHAR(45))
- user_agent (TEXT)
- created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- Indexes: user_id, entity_type, entity_id, created_at
```

### Attachments Table (Future)
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- project_id (INT, FOREIGN KEY to projects.id)
- task_id (INT, FOREIGN KEY to tasks.id)
- note_id (INT, FOREIGN KEY to notes.id)
- user_id (INT, FOREIGN KEY to users.id, NOT NULL)
- filename (VARCHAR(255), NOT NULL)
- original_filename (VARCHAR(255), NOT NULL)
- file_size (INT)
- mime_type (VARCHAR(100))
- file_path (VARCHAR(500), NOT NULL)
- created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
```

## File Structure
```
juniata/
├── config/
│   ├── database.php        # Database connection class with PDO
│   └── config.php         # Application configuration constants
├── includes/
│   ├── auth.php           # Authentication & session management
│   ├── functions.php      # Core utility functions (1000+ lines)
│   ├── header.php         # Common header with WCAG compliance
│   └── footer.php         # Common footer with optimized scripts
├── assets/
│   ├── css/
│   │   ├── style.css     # Custom styles with accessibility
│   │   └── style.min.css # Minified production version
│   ├── js/
│   │   ├── main.js       # Main JavaScript functionality
│   │   ├── main.min.js   # Minified production version
│   │   └── sw.js         # Service Worker for PWA
│   └── uploads/          # File uploads directory (future)
├── pages/
│   ├── login.php         # Secure login with CSRF protection
│   ├── logout.php        # Session cleanup
│   ├── dashboard.php     # Interactive dashboard with charts
│   ├── projects.php      # Full CRUD project management
│   ├── tasks.php         # Advanced task management
│   ├── calendar.php      # Timeline visualization
│   ├── reports.php       # 5 report types with export
│   ├── settings.php      # Admin configuration panel
│   └── simple_test.php   # Development testing page
├── api/ (Future Enhancement)
│   ├── projects.php      # RESTful project endpoints
│   ├── tasks.php         # RESTful task endpoints
│   └── auth.php          # Authentication API
├── database/
│   └── schema.sql        # Complete database with sample data
├── logs/
│   └── error.log         # Application error logging
├── index.php             # Application entry point with routing
├── manifest.json         # PWA manifest for installation
├── setup_admin.php       # Initial admin user creation
└── test.php              # Development testing utilities
```

## Development Timeline (COMPLETED)

### Day 1: Foundation & Core Systems ✅
- ✅ Set up XAMPP environment with PHP 8.x
- ✅ Create comprehensive database schema with 7 tables
- ✅ Build scalable project directory structure
- ✅ Implement secure authentication with bcrypt & sessions
- ✅ Create responsive Bootstrap 5 layout with navigation
- ✅ Build full CRUD operations for projects with validation
- ✅ Implement role-based access control (Admin/User)

### Day 2: Advanced Features & Functionality ✅
- ✅ Integrate Calendar.js for interactive timeline visualization
- ✅ Implement comprehensive task management with dependencies
- ✅ Add notes system for projects and tasks with privacy controls
- ✅ Create advanced search and filtering across all entities
- ✅ Build multi-level reporting system with Chart.js
- ✅ Add task assignment and workload management
- ✅ Implement WCAG 2.1 AA accessibility compliance

### Day 3: Enterprise Features & Optimization ✅
- ✅ Create executive dashboard with real-time KPIs
- ✅ Implement CSV export functionality for all reports
- ✅ Add comprehensive system configuration panel
- ✅ Optimize performance to Lighthouse score 95+
- ✅ Implement Progressive Web App with Service Worker
- ✅ Add activity logging and audit trail system
- ✅ Create configurable feature toggle system
- ✅ Complete testing and production optimization

### Additional Enhancements Completed ✅
- ✅ **Settings Management System**: Toggleable features with admin panel
- ✅ **Budget Tracking Control**: Optional budget fields system-wide
- ✅ **5 Report Types**: Summary, Project Status, Task Completion, Productivity, Timeline
- ✅ **Performance Optimization**: Minification, lazy loading, caching
- ✅ **Security Hardening**: CSRF protection, input validation, sanitization
- ✅ **Accessibility Features**: Skip links, ARIA labels, keyboard navigation

## Interview Talking Points

### Technical Skills Demonstrated
1. **Full-Stack Development**: Complete PHP backend with MySQL database and Bootstrap frontend
2. **Security Best Practices**: bcrypt hashing, prepared statements, input validation
3. **Database Design**: Normalized schema with proper relationships and constraints
4. **API Development**: RESTful endpoints for data operations
5. **Frontend Technologies**: Responsive design, JavaScript, CSS frameworks
6. **Project Management**: Real-world application solving actual business problems

### Problem-Solving Approach
1. **Requirements Analysis**: Identified key pain points in development project management
2. **Solution Architecture**: Designed scalable, maintainable system
3. **User Experience**: Intuitive interface for non-technical users
4. **Performance Considerations**: Efficient database queries and caching strategies
5. **Security Focus**: Enterprise-level security implementation

### Future Enhancements
1. **Integration Capabilities**: API design allows for ERP system integration
2. **Scalability**: Database design supports thousands of projects and users
3. **Automation**: Foundation for automated health checks and notifications
4. **Analytics**: Advanced reporting for executive decision making
5. **Mobile App**: PWA capabilities for mobile workforce

## Deployment Notes
- XAMPP setup instructions for demonstration
- Database import/export procedures
- Configuration file templates
- Security checklist for production deployment

## Testing Strategy
- Unit tests for core functions
- Integration tests for database operations
- User acceptance testing scenarios
- Performance testing guidelines

---

**Project Goal**: Demonstrate comprehensive web development skills that directly address the job requirements while solving real-world development project management challenges.

**Success Metrics**: 
- Complete CRUD functionality
- Secure authentication system
- Interactive calendar visualization
- Professional reporting capabilities
- Mobile-responsive design
- Clean, maintainable code structure