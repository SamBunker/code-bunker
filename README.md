# Code Bunker

A comprehensive, enterprise-grade project management system designed to securely track and manage development projects. Built with PHP, MySQL, and modern web technologies.

## 🚀 Features

- **Project Management**: Full CRUD operations with status tracking and priority management
- **Task Management**: Hierarchical task system with dependencies and time tracking
- **Interactive Dashboard**: Real-time KPIs and data visualization with Chart.js
- **Calendar Integration**: Timeline visualization for project schedules
- **Advanced Reporting**: 5 comprehensive report types with CSV export
- **User Authentication**: Secure login with bcrypt hashing and session management
- **Role-Based Access**: Admin and user roles with permission control
- **Progressive Web App**: Installable with offline capabilities
- **WCAG 2.1 AA Compliance**: Full accessibility support
- **Responsive Design**: Mobile-friendly Bootstrap 5 interface

## 🛠️ Technology Stack

### Backend
- **PHP 8.x** - Server-side scripting with OOP
- **MySQL** - Relational database with normalized schema
- **PDO** - Secure database connections with prepared statements
- **Apache** - Web server (via XAMPP)

### Frontend
- **HTML5 & CSS3** - Modern semantic markup and styling
- **Bootstrap 5** - Responsive CSS framework
- **JavaScript ES6+** - Modern client-side functionality
- **Chart.js** - Dynamic data visualization
- **Calendar.js** - Interactive timeline visualization
- **SortableJS** - Drag-and-drop functionality

### Security & Performance
- **bcrypt** - Password hashing
- **CSRF Protection** - Cross-site request forgery prevention
- **Session Management** - Secure user sessions
- **Service Workers** - PWA capabilities and caching
- **Input Validation** - Comprehensive data sanitization

## 📋 Prerequisites

Before setting up Code Bunker, ensure you have:

- **XAMPP** (or similar LAMP stack) with:
  - PHP 8.0 or higher
  - MySQL 5.7 or higher
  - Apache 2.4 or higher
- **Web browser** (Chrome, Firefox, Safari, Edge)
- **Git** (for version control)

## 🔧 Installation

### 1. Download and Setup XAMPP

1. Download XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. Install XAMPP and start Apache and MySQL services
3. Verify installation by visiting `http://localhost`

### 2. Clone the Repository

```bash
# Navigate to your XAMPP htdocs directory
cd /Applications/XAMPP/xamppfiles/htdocs  # macOS
cd C:\xampp\htdocs                        # Windows
cd /opt/lampp/htdocs                      # Linux

# Clone the repository
git clone https://github.com/SamBunker/code-bunker.git
cd code-bunker
```

### 3. Configure the Database

#### Option A: Import the Complete Schema
```bash
# Access MySQL via XAMPP
mysql -u root -p

# Import the database
source database/schema.sql
```

#### Option B: Manual Database Setup
1. Open phpMyAdmin at `http://localhost/phpmyadmin`
2. Create a new database named `code_bunker`
3. Import the `database/schema.sql` file
4. Verify all tables are created successfully

### 4. Configure the Application

1. **Update Base URL** (if needed):
   ```php
   // In config/config.php, update the BASE_URL constant
   define('BASE_URL', 'http://localhost/code-bunker/');
   ```

2. **Database Connection** (if needed):
   ```php
   // In config/database.php, update connection details if different
   private $host = 'localhost';
   private $dbname = 'code_bunker';
   private $username = 'root';
   private $password = '';
   ```

### 5. Create Admin User

Run the setup script to create your first admin user:

```bash
# Visit in your browser
http://localhost/code-bunker/setup_admin.php
```

Follow the prompts to create your administrator account.

### 6. Access the Application

Visit `http://localhost/code-bunker/` and log in with your admin credentials.

## 📁 Directory Structure

```
code-bunker/
├── config/
│   ├── database.php          # Database connection class
│   └── config.php           # Application configuration
├── includes/
│   ├── auth.php             # Authentication functions
│   ├── functions.php        # Core utility functions
│   ├── header.php           # Common header
│   └── footer.php           # Common footer
├── assets/
│   ├── css/
│   │   └── style.css       # Custom styles
│   ├── js/
│   │   └── main.js         # JavaScript functionality
│   └── uploads/            # File uploads
├── pages/
│   ├── dashboard.php        # Main dashboard
│   ├── projects.php         # Project management
│   ├── tasks.php           # Task management
│   ├── calendar.php        # Timeline view
│   ├── reports.php         # Reporting system
│   └── settings.php        # Admin settings
├── database/
│   └── schema.sql          # Database schema
├── api/                    # Future API endpoints
└── logs/                   # Application logs
```

## 🚦 Quick Start Guide

### For Administrators

1. **Login** at `http://localhost/code-bunker/`
2. **Configure Settings** - Visit Settings page to customize features
3. **Create Users** - Add team members with appropriate roles
4. **Set up Projects** - Create your first project with tasks
5. **Customize Categories** - Define project and task categories

### For Users

1. **Login** with credentials provided by administrator
2. **View Dashboard** - See overview of your assigned projects and tasks
3. **Manage Tasks** - Update task status and log time spent
4. **Add Notes** - Document progress and important information
5. **Generate Reports** - Create progress reports for stakeholders

## ⚙️ Configuration Options

### Feature Toggles
Access the Settings page to enable/disable features:
- Budget tracking
- Calendar view
- File uploads
- Notifications
- Advanced reporting

### Customization
- **Project Categories**: Define custom project types
- **Task Types**: Create specific task categories
- **Priority Levels**: Customize priority settings
- **Status Workflows**: Modify available statuses

## 🔒 Security Features

- **Password Hashing**: bcrypt with salt rounds
- **SQL Injection Prevention**: PDO prepared statements
- **CSRF Protection**: Token-based form protection
- **Session Security**: Timeout and regeneration
- **Input Validation**: Comprehensive sanitization
- **Role-Based Access**: Admin/User permission levels

## 📊 Reporting Capabilities

Code Bunker includes 5 comprehensive report types:

1. **Summary Dashboard** - Overview statistics and trends
2. **Project Status Reports** - Breakdown by status with metrics
3. **Task Completion Analysis** - Performance by priority/status
4. **Team Productivity Reports** - Individual performance metrics
5. **Timeline Reports** - Project deadlines and progress tracking

All reports support:
- Dynamic date range filtering
- CSV export functionality
- Visual charts and graphs
- Real-time data updates

## 🎯 Accessibility

Code Bunker is built with accessibility in mind:
- **WCAG 2.1 AA compliant**
- Skip navigation links
- ARIA attributes and roles
- Keyboard navigation support
- Screen reader optimization
- High contrast color schemes

## 📱 Progressive Web App

- **Installable** on desktop and mobile devices
- **Offline capabilities** with service worker caching
- **Push notifications** support (configurable)
- **Responsive design** for all screen sizes

## 🛠️ Development

### Local Development Setup

1. Enable PHP error reporting in `config/config.php`:
   ```php
   define('DEBUG_MODE', true);
   ```

2. Monitor logs in the `logs/` directory

3. Use the test utilities:
   - `test.php` - General testing
   - `test_db.php` - Database connectivity testing

### Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/new-feature`)
3. Commit your changes (`git commit -am 'Add new feature'`)
4. Push to the branch (`git push origin feature/new-feature`)
5. Create a Pull Request

## 📝 License

This project is open source and available under the [MIT License](LICENSE).

## 👨‍💻 Developer

**Samuel Bunker**
- GitHub: [github.com/SamBunker](https://github.com/SamBunker)
- Project: [Code Bunker](https://github.com/SamBunker/code-bunker)

## 🆘 Troubleshooting

### Common Issues

**"Database connection failed"**
- Verify MySQL service is running in XAMPP
- Check database credentials in `config/database.php`
- Ensure `code_bunker` database exists

**"Permission denied"**
- Check file permissions on uploads directory
- Verify web server has write access to logs directory

**"Page not found"**
- Verify BASE_URL setting in `config/config.php`
- Check Apache virtual host configuration

**"Login not working"**
- Run `setup_admin.php` to create initial user
- Check session configuration in PHP

### Getting Help

1. Check the [Issues](https://github.com/SamBunker/code-bunker/issues) page
2. Review configuration settings
3. Check application logs in `logs/error.log`
4. Verify XAMPP services are running

## 🔄 Updates

To update Code Bunker:

1. Backup your database and files
2. Pull latest changes: `git pull origin main`
3. Run any database migrations if provided
4. Clear browser cache and restart services

---

**Code Bunker** - Secure project management for development teams.