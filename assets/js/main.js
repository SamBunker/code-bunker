/**
 * Main JavaScript Functions
 * Web Application Modernization Tracker
 */

// Global utility functions
window.AppTracker = {
    // CSRF token for AJAX requests
    csrfToken: null,
    
    // Initialize the application
    init: function() {
        this.setupAjax();
        this.setupTooltips();
        this.setupConfirmDialogs();
        this.setupFormValidation();
    },
    
    // Setup AJAX defaults
    setupAjax: function() {
        // Set CSRF token if available
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        if (csrfMeta) {
            this.csrfToken = csrfMeta.getAttribute('content');
        }
        
        // Setup default headers for fetch requests
        const originalFetch = window.fetch;
        window.fetch = function(url, options = {}) {
            options.headers = options.headers || {};
            if (AppTracker.csrfToken && options.method && options.method.toUpperCase() !== 'GET') {
                options.headers['X-CSRF-Token'] = AppTracker.csrfToken;
            }
            return originalFetch(url, options);
        };
    },
    
    // Setup Bootstrap tooltips
    setupTooltips: function() {
        const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltips.forEach(function(tooltip) {
            new bootstrap.Tooltip(tooltip);
        });
    },
    
    // Setup confirmation dialogs
    setupConfirmDialogs: function() {
        document.addEventListener('click', function(e) {
            const confirmElement = e.target.closest('[data-confirm]');
            if (confirmElement) {
                const message = confirmElement.getAttribute('data-confirm');
                if (!confirm(message)) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            }
        });
    },
    
    // Setup form validation
    setupFormValidation: function() {
        const forms = document.querySelectorAll('.needs-validation');
        forms.forEach(function(form) {
            form.addEventListener('submit', function(e) {
                if (!form.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });
    },
    
    // Show loading spinner
    showLoading: function(element) {
        const spinner = document.createElement('span');
        spinner.className = 'spinner me-2';
        spinner.setAttribute('data-loading-spinner', 'true');
        
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        
        if (element) {
            element.insertBefore(spinner, element.firstChild);
            element.disabled = true;
        }
    },
    
    // Hide loading spinner
    hideLoading: function(element) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        
        if (element) {
            const spinner = element.querySelector('[data-loading-spinner]');
            if (spinner) {
                spinner.remove();
            }
            element.disabled = false;
        }
    },
    
    // Show toast notification
    showToast: function(message, type = 'info') {
        const toastContainer = this.getOrCreateToastContainer();
        const toastId = 'toast-' + Date.now();
        
        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-white bg-${type === 'error' ? 'danger' : type} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        
        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, { delay: 5000 });
        toast.show();
        
        // Remove toast element after it's hidden
        toastElement.addEventListener('hidden.bs.toast', function() {
            toastElement.remove();
        });
    },
    
    // Get or create toast container
    getOrCreateToastContainer: function() {
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            container.style.zIndex = '1070';
            document.body.appendChild(container);
        }
        return container;
    },
    
    // Format date
    formatDate: function(dateString) {
        if (!dateString) return 'Not set';
        const date = new Date(dateString);
        return date.toLocaleDateString();
    },
    
    // Format datetime
    formatDateTime: function(dateString) {
        if (!dateString) return 'Not set';
        const date = new Date(dateString);
        return date.toLocaleString();
    },
    
    // Calculate days between dates
    daysBetween: function(date1, date2) {
        const oneDay = 24 * 60 * 60 * 1000;
        const firstDate = new Date(date1);
        const secondDate = new Date(date2);
        return Math.round(Math.abs((firstDate - secondDate) / oneDay));
    },
    
    // Debounce function for search inputs
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};

// Project Management Functions
window.ProjectManager = {
    // Load project data via AJAX
    loadProject: function(projectId, callback) {
        fetch(`/juniata/api/projects.php?id=${projectId}`)
            .then(response => response.json())
            .then(data => {
                if (callback) callback(data);
            })
            .catch(error => {
                console.error('Error loading project:', error);
                AppTracker.showToast('Error loading project data', 'error');
            });
    },
    
    // Update project status
    updateStatus: function(projectId, newStatus, callback) {
        AppTracker.showLoading('#status-btn-' + projectId);
        
        fetch('/juniata/api/projects.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: projectId,
                status: newStatus
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                AppTracker.showToast('Project status updated', 'success');
                if (callback) callback(data);
            } else {
                AppTracker.showToast(data.message || 'Error updating status', 'error');
            }
        })
        .catch(error => {
            console.error('Error updating status:', error);
            AppTracker.showToast('Error updating project status', 'error');
        })
        .finally(() => {
            AppTracker.hideLoading('#status-btn-' + projectId);
        });
    }
};

// Task Management Functions
window.TaskManager = {
    // Load task data
    loadTask: function(taskId, callback) {
        fetch(`/juniata/api/tasks.php?id=${taskId}`)
            .then(response => response.json())
            .then(data => {
                if (callback) callback(data);
            })
            .catch(error => {
                console.error('Error loading task:', error);
                AppTracker.showToast('Error loading task data', 'error');
            });
    },
    
    // Update task status
    updateStatus: function(taskId, newStatus, callback) {
        fetch('/juniata/api/tasks.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: taskId,
                status: newStatus
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                AppTracker.showToast('Task status updated', 'success');
                if (callback) callback(data);
            } else {
                AppTracker.showToast(data.message || 'Error updating status', 'error');
            }
        })
        .catch(error => {
            console.error('Error updating status:', error);
            AppTracker.showToast('Error updating task status', 'error');
        });
    }
};

// Search and Filter Functions
window.SearchManager = {
    // Initialize search functionality
    init: function() {
        const searchInputs = document.querySelectorAll('[data-search]');
        searchInputs.forEach(input => {
            const debouncedSearch = AppTracker.debounce(this.performSearch.bind(this), 300);
            input.addEventListener('input', debouncedSearch);
        });
        
        const filterSelects = document.querySelectorAll('[data-filter]');
        filterSelects.forEach(select => {
            select.addEventListener('change', this.performSearch.bind(this));
        });
    },
    
    // Perform search/filter
    performSearch: function() {
        const searchTerm = document.querySelector('[data-search]')?.value || '';
        const filters = {};
        
        document.querySelectorAll('[data-filter]').forEach(filter => {
            const filterName = filter.getAttribute('data-filter');
            const filterValue = filter.value;
            if (filterValue) {
                filters[filterName] = filterValue;
            }
        });
        
        this.updateResults(searchTerm, filters);
    },
    
    // Update search results
    updateResults: function(searchTerm, filters) {
        // FIXED: Preserve existing URL parameters (like month/year for calendar)
        const params = new URLSearchParams(window.location.search);
        
        // Remove old search/filter params but keep others (month, year, debug, etc.)
        const searchFilterKeys = ['search', 'status', 'priority', 'category', 'assigned_to'];
        searchFilterKeys.forEach(key => params.delete(key));
        
        // Add new search/filter params
        if (searchTerm) params.append('search', searchTerm);
        
        Object.keys(filters).forEach(key => {
            if (filters[key]) params.append(key, filters[key]);
        });
        
        const currentPage = window.location.pathname;
        const newUrl = currentPage + '?' + params.toString();
        
        // Update URL without page reload
        window.history.replaceState({}, '', newUrl);
        
        // TEMPORARILY DISABLED: Don't reload if we're on calendar page to preserve navigation
        if (currentPage.includes('calendar.php')) {
            console.log('Calendar page detected - skipping reload to preserve navigation');
        } else {
            // Reload page content (in a real app, this would be AJAX)
            window.location.reload();
        }
    }
};

// Calendar Functions
window.CalendarManager = {
    currentDate: new Date(),
    
    // Initialize calendar
    init: function() {
        this.renderCalendar();
        this.setupEventHandlers();
    },
    
    // Render calendar
    renderCalendar: function() {
        const calendar = document.querySelector('.calendar-grid');
        if (!calendar) return;
        
        const year = this.currentDate.getFullYear();
        const month = this.currentDate.getMonth();
        
        // Update month display
        const monthName = this.currentDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        document.querySelector('.calendar-month').textContent = monthName;
        
        // Clear calendar
        calendar.innerHTML = '';
        
        // Add day headers
        const dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        dayHeaders.forEach(day => {
            const dayHeader = document.createElement('div');
            dayHeader.className = 'calendar-day-header';
            dayHeader.textContent = day;
            calendar.appendChild(dayHeader);
        });
        
        // Get first day of month and number of days
        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        
        // Add empty cells for days before month starts
        for (let i = 0; i < firstDay; i++) {
            const emptyDay = document.createElement('div');
            emptyDay.className = 'calendar-day other-month';
            calendar.appendChild(emptyDay);
        }
        
        // Add days of month
        for (let day = 1; day <= daysInMonth; day++) {
            const dayElement = document.createElement('div');
            dayElement.className = 'calendar-day';
            
            const today = new Date();
            if (year === today.getFullYear() && month === today.getMonth() && day === today.getDate()) {
                dayElement.classList.add('today');
            }
            
            dayElement.innerHTML = `<div class="calendar-day-number">${day}</div>`;
            dayElement.addEventListener('click', () => this.onDayClick(year, month, day));
            
            calendar.appendChild(dayElement);
        }
    },
    
    // Setup event handlers
    setupEventHandlers: function() {
        document.querySelector('.btn-prev-month')?.addEventListener('click', () => {
            this.currentDate.setMonth(this.currentDate.getMonth() - 1);
            this.renderCalendar();
        });
        
        document.querySelector('.btn-next-month')?.addEventListener('click', () => {
            this.currentDate.setMonth(this.currentDate.getMonth() + 1);
            this.renderCalendar();
        });
    },
    
    // Handle day click
    onDayClick: function(year, month, day) {
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        console.log('Day clicked:', dateStr);
        // Here you would show events for this day or create new events
    }
};

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    AppTracker.init();
    
    // Don't initialize SearchManager on calendar page to prevent navigation interference
    if (!window.location.pathname.includes('calendar.php')) {
        SearchManager.init();
    } else {
        console.log('Calendar page detected - SearchManager disabled to preserve navigation');
    }
    
    // TEMPORARILY DISABLED - Calendar initialization causing navigation issues
    // Initialize calendar if on calendar page
    // if (document.querySelector('.calendar-grid')) {
    //     CalendarManager.init();
    // }
});

// Export for use in other scripts
window.App = {
    Tracker: AppTracker,
    ProjectManager: ProjectManager,
    TaskManager: TaskManager,
    SearchManager: SearchManager,
    CalendarManager: CalendarManager
};