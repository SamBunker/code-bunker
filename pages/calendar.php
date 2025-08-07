<?php
/**
 * Calendar View Page
 * Web Application Modernization Tracker
 * 
 * Interactive calendar showing project deadlines and task timelines
 */

$pageTitle = 'Calendar';
require_once dirname(__FILE__) . '/../includes/header.php';

// Require login
requireLogin();

// Get current user
$currentUser = getCurrentUser();

// Get current month/year from URL parameters or use current date
$currentMonth = intval($_GET['month'] ?? date('n'));
$currentYear = intval($_GET['year'] ?? date('Y'));

// Ensure month is valid
if ($currentMonth < 1) {
    $currentMonth = 12;
    $currentYear--;
} elseif ($currentMonth > 12) {
    $currentMonth = 1;
    $currentYear++;
}

// Calculate previous and next month
$prevMonth = $currentMonth - 1;
$prevYear = $currentYear;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $currentMonth + 1;
$nextYear = $currentYear;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

// Get calendar data for current month
$startDate = date('Y-m-01', mktime(0, 0, 0, $currentMonth, 1, $currentYear));
$endDate = date('Y-m-t', mktime(0, 0, 0, $currentMonth, 1, $currentYear));

// Get projects and tasks for the current month
$calendarQuery = "
    SELECT 
        'project' as type,
        p.id,
        p.name as title,
        p.due_date as date,
        p.priority,
        p.status,
        p.category as description
    FROM projects p 
    WHERE p.due_date BETWEEN ? AND ?
    
    UNION ALL
    
    SELECT 
        'task' as type,
        t.id,
        t.title,
        t.due_date as date,
        t.priority,
        t.status,
        p.name as description
    FROM tasks t
    JOIN projects p ON t.project_id = p.id
    WHERE t.due_date BETWEEN ? AND ?
    
    ORDER BY date ASC
";

$calendarEvents = executeQuery($calendarQuery, [$startDate, $endDate, $startDate, $endDate]) ?: [];

// Group events by date
$eventsByDate = [];
foreach ($calendarEvents as $event) {
    $date = $event['date'];
    if (!isset($eventsByDate[$date])) {
        $eventsByDate[$date] = [];
    }
    $eventsByDate[$date][] = $event;
}

// Get month name
$monthName = date('F Y', mktime(0, 0, 0, $currentMonth, 1, $currentYear));

// Calendar grid calculation
$firstDayOfMonth = mktime(0, 0, 0, $currentMonth, 1, $currentYear);
$daysInMonth = date('t', $firstDayOfMonth);
$firstWeekday = date('w', $firstDayOfMonth); // 0 = Sunday
$today = date('Y-m-d');

// Get upcoming deadlines (next 30 days)
$upcomingQuery = "
    SELECT 
        'project' as type,
        p.id,
        p.name as title,
        p.due_date as date,
        p.priority,
        p.status,
        DATEDIFF(p.due_date, CURDATE()) as days_until_due
    FROM projects p 
    WHERE p.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    AND p.status != 'completed'
    
    UNION ALL
    
    SELECT 
        'task' as type,
        t.id,
        t.title,
        t.due_date as date,
        t.priority,
        t.status,
        DATEDIFF(t.due_date, CURDATE()) as days_until_due
    FROM tasks t
    WHERE t.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    AND t.status != 'completed'
    
    ORDER BY date ASC
";

$upcomingDeadlines = executeQuery($upcomingQuery) ?: [];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1><i class="bi bi-calendar3"></i> Calendar</h1>
        <p class="text-muted">View project timelines and task deadlines</p>
    </div>
    <div class="btn-group">
        <button type="button" class="btn btn-outline-primary" onclick="showToday()">
            <i class="bi bi-calendar-day"></i> Today
        </button>
        <div class="btn-group">
            <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                <i class="bi bi-eye"></i> View
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#" onclick="switchView('month')">
                    <i class="bi bi-calendar-month"></i> Month View
                </a></li>
                <li><a class="dropdown-item" href="#" onclick="switchView('agenda')">
                    <i class="bi bi-list-ul"></i> Agenda View
                </a></li>
            </ul>
        </div>
    </div>
</div>

<div class="row">
    <!-- Calendar View -->
    <div class="col-lg-8">
        <div class="card calendar-container">
            <div class="calendar-header">
                <div class="calendar-nav">
                    <button class="btn btn-outline-secondary btn-sm btn-prev-month" 
                            onclick="navigateMonth(<?php echo $prevYear; ?>, <?php echo $prevMonth; ?>)">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                </div>
                <div class="calendar-month"><?php echo $monthName; ?></div>
                <div class="calendar-nav">
                    <button class="btn btn-outline-secondary btn-sm btn-next-month"
                            onclick="navigateMonth(<?php echo $nextYear; ?>, <?php echo $nextMonth; ?>)">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>
            
            <div class="calendar-grid">
                <!-- Day headers -->
                <div class="calendar-day-header">Sun</div>
                <div class="calendar-day-header">Mon</div>
                <div class="calendar-day-header">Tue</div>
                <div class="calendar-day-header">Wed</div>
                <div class="calendar-day-header">Thu</div>
                <div class="calendar-day-header">Fri</div>
                <div class="calendar-day-header">Sat</div>
                
                <!-- Empty cells for days before month starts -->
                <?php for ($i = 0; $i < $firstWeekday; $i++): ?>
                <div class="calendar-day other-month"></div>
                <?php endfor; ?>
                
                <!-- Days of the month -->
                <?php for ($day = 1; $day <= $daysInMonth; $day++): ?>
                <?php 
                $dateStr = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $day);
                $isToday = $dateStr === $today;
                $events = $eventsByDate[$dateStr] ?? [];
                ?>
                <div class="calendar-day <?php echo $isToday ? 'today' : ''; ?>" 
                     data-date="<?php echo $dateStr; ?>" 
                     onclick="showDayEvents('<?php echo $dateStr; ?>')">
                    <div class="calendar-day-number"><?php echo $day; ?></div>
                    <?php foreach (array_slice($events, 0, 3) as $event): ?>
                    <div class="calendar-event priority-<?php echo $event['priority']; ?>" 
                         title="<?php echo htmlspecialchars($event['title']); ?>">
                        <i class="bi bi-<?php echo $event['type'] === 'project' ? 'folder' : 'check-square'; ?>"></i>
                        <?php echo htmlspecialchars(substr($event['title'], 0, 15) . (strlen($event['title']) > 15 ? '...' : '')); ?>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if (count($events) > 3): ?>
                    <div class="calendar-event-more">
                        +<?php echo count($events) - 3; ?> more
                    </div>
                    <?php endif; ?>
                </div>
                <?php endfor; ?>
            </div>
        </div>
        
        <!-- Legend -->
        <div class="card mt-3">
            <div class="card-body">
                <h6 class="card-title">Legend</h6>
                <div class="row">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-folder text-primary me-2"></i>
                            <span>Project Deadlines</span>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-check-square text-info me-2"></i>
                            <span>Task Due Dates</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-2">
                            <div class="calendar-event priority-critical me-2" style="width: 20px; height: 16px;"></div>
                            <span>Critical Priority</span>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <div class="calendar-event priority-high me-2" style="width: 20px; height: 16px;"></div>
                            <span>High Priority</span>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <div class="calendar-event priority-medium me-2" style="width: 20px; height: 16px;"></div>
                            <span>Medium Priority</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="calendar-event priority-low me-2" style="width: 20px; height: 16px;"></div>
                            <span>Low Priority</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Upcoming Deadlines -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-clock"></i> Upcoming Deadlines
                </h5>
            </div>
            <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                <?php if (empty($upcomingDeadlines)): ?>
                <div class="text-center p-4">
                    <i class="bi bi-calendar-check fs-1 text-muted"></i>
                    <p class="text-muted mt-2">No upcoming deadlines</p>
                </div>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($upcomingDeadlines as $deadline): ?>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1">
                                    <i class="bi bi-<?php echo $deadline['type'] === 'project' ? 'folder' : 'check-square'; ?> me-1"></i>
                                    <?php echo htmlspecialchars($deadline['title']); ?>
                                </h6>
                                <p class="mb-1 text-muted small">
                                    <?php echo formatDate($deadline['date']); ?>
                                </p>
                                <small>
                                    <?php echo getStatusBadge($deadline['status'], $deadline['type']); ?>
                                    <?php echo getPriorityBadge($deadline['priority']); ?>
                                </small>
                            </div>
                            <div class="text-end">
                                <small class="<?php echo $deadline['days_until_due'] <= 3 ? 'text-danger' : ($deadline['days_until_due'] <= 7 ? 'text-warning' : 'text-muted'); ?>">
                                    <?php 
                                    $days = $deadline['days_until_due'];
                                    if ($days == 0) {
                                        echo 'Today';
                                    } elseif ($days == 1) {
                                        echo 'Tomorrow';
                                    } else {
                                        echo $days . ' days';
                                    }
                                    ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-bar-chart"></i> This Month
                </h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-4">
                        <div class="h4 text-primary"><?php echo count(array_filter($calendarEvents, fn($e) => $e['type'] === 'project')); ?></div>
                        <small class="text-muted">Projects</small>
                    </div>
                    <div class="col-4">
                        <div class="h4 text-info"><?php echo count(array_filter($calendarEvents, fn($e) => $e['type'] === 'task')); ?></div>
                        <small class="text-muted">Tasks</small>
                    </div>
                    <div class="col-4">
                        <div class="h4 text-warning"><?php echo count(array_filter($calendarEvents, fn($e) => $e['date'] < date('Y-m-d') && $e['status'] !== 'completed')); ?></div>
                        <small class="text-muted">Overdue</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Current Month Navigation -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-calendar-date"></i> Quick Navigation
                </h6>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <?php for ($month = 1; $month <= 12; $month++): ?>
                    <div class="col-4">
                        <button class="btn btn-outline-secondary btn-sm w-100 <?php echo $month === $currentMonth ? 'active' : ''; ?>"
                                onclick="navigateMonth(<?php echo $currentYear; ?>, <?php echo $month; ?>)">
                            <?php echo date('M', mktime(0, 0, 0, $month, 1)); ?>
                        </button>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Day Events Modal -->
<div class="modal fade" id="dayEventsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dayEventsModalTitle">Events</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="dayEventsModalBody">
                <!-- Events will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
// Calendar events data (passed from PHP)
const calendarEvents = <?php echo json_encode($calendarEvents); ?>;

function navigateMonth(year, month) {
    window.location.href = `?month=${month}&year=${year}`;
}

function showToday() {
    const today = new Date();
    navigateMonth(today.getFullYear(), today.getMonth() + 1);
}

function showDayEvents(date) {
    const events = calendarEvents.filter(event => event.date === date);
    const modal = document.getElementById('dayEventsModal');
    const title = document.getElementById('dayEventsModalTitle');
    const body = document.getElementById('dayEventsModalBody');
    
    // Format date for display
    const dateObj = new Date(date + 'T00:00:00');
    const formattedDate = dateObj.toLocaleDateString('en-US', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
    
    title.textContent = `Events for ${formattedDate}`;
    
    if (events.length === 0) {
        body.innerHTML = '<p class="text-muted text-center">No events scheduled for this day.</p>';
    } else {
        let html = '';
        events.forEach(event => {
            const icon = event.type === 'project' ? 'folder' : 'check-square';
            const priorityClass = `priority-${event.priority}`;
            const statusBadge = getStatusBadgeHtml(event.status, event.type);
            const priorityBadge = getPriorityBadgeHtml(event.priority);
            
            html += `
                <div class="border-start border-4 border-${getPriorityColor(event.priority)} ps-3 mb-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1">
                                <i class="bi bi-${icon} me-1"></i>
                                ${escapeHtml(event.title)}
                            </h6>
                            <p class="mb-1 text-muted small">${escapeHtml(event.description)}</p>
                            <div>
                                ${statusBadge}
                                ${priorityBadge}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        body.innerHTML = html;
    }
    
    new bootstrap.Modal(modal).show();
}

function switchView(view) {
    // For future implementation of different calendar views
    console.log('Switch to', view, 'view');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function getStatusBadgeHtml(status, type) {
    const colors = {
        'planning': 'secondary',
        'in_progress': 'primary', 
        'testing': 'info',
        'completed': 'success',
        'on_hold': 'warning',
        'blocked': 'danger',
        'pending': 'secondary',
        'cancelled': 'secondary'
    };
    
    const labels = {
        'planning': 'Planning',
        'in_progress': 'In Progress',
        'testing': 'Testing', 
        'completed': 'Completed',
        'on_hold': 'On Hold',
        'blocked': 'Blocked',
        'pending': 'Pending',
        'cancelled': 'Cancelled'
    };
    
    const color = colors[status] || 'secondary';
    const label = labels[status] || status;
    
    return `<span class="badge bg-${color}">${label}</span>`;
}

function getPriorityBadgeHtml(priority) {
    const colors = {
        'critical': 'danger',
        'high': 'warning',
        'medium': 'info',
        'low': 'success'
    };
    
    const labels = {
        'critical': 'Critical',
        'high': 'High',
        'medium': 'Medium', 
        'low': 'Low'
    };
    
    const color = colors[priority] || 'secondary';
    const label = labels[priority] || priority;
    
    return `<span class="badge bg-${color}">${label}</span>`;
}

function getPriorityColor(priority) {
    const colors = {
        'critical': 'danger',
        'high': 'warning', 
        'medium': 'info',
        'low': 'success'
    };
    
    return colors[priority] || 'secondary';
}

// Add hover effects and tooltips
document.addEventListener('DOMContentLoaded', function() {
    // Add tooltips to calendar events
    const events = document.querySelectorAll('.calendar-event');
    events.forEach(event => {
        new bootstrap.Tooltip(event);
    });
    
    // Highlight today if it's in the current month
    const today = new Date();
    const currentDate = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
    const todayCell = document.querySelector(`[data-date="${currentDate}"]`);
    if (todayCell) {
        todayCell.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
});
</script>

    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>
</body>
</html>