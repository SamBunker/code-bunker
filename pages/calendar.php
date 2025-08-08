<?php
/**
 * Calendar View Page
 * Web Application Modernization Tracker
 * 
 * Interactive calendar showing project deadlines and task timelines
 */

// Redirect to current month/year if no parameters provided (MUST be before any output)
if (!isset($_GET['month']) || !isset($_GET['year'])) {
    $currentMonth = date('n');
    $currentYear = date('Y');
    
    // Preserve any existing URL parameters (like debug)
    $queryParams = $_GET;
    $queryParams['month'] = $currentMonth;
    $queryParams['year'] = $currentYear;
    
    $redirectUrl = $_SERVER['PHP_SELF'] . '?' . http_build_query($queryParams);
    
    // DEBUG: Log the redirect for debugging
    error_log("Calendar redirect: " . $_SERVER['REQUEST_URI'] . " -> " . $redirectUrl);
    
    header('Location: ' . $redirectUrl);
    exit();
}

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

// Also include upcoming deadlines from next month to ensure they're visible
$extendedEndDate = date('Y-m-t', mktime(0, 0, 0, $currentMonth + 1, 1, $currentYear));

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

// Also get upcoming deadlines to show on sidebar
$upcomingEvents = executeQuery($calendarQuery, [date('Y-m-d'), $extendedEndDate, date('Y-m-d'), $extendedEndDate]) ?: [];

// Group events by date
$eventsByDate = [];
foreach ($calendarEvents as $event) {
    $date = $event['date'];
    if (!isset($eventsByDate[$date])) {
        $eventsByDate[$date] = [];
    }
    $eventsByDate[$date][] = $event;
}

// Debug output - remove in production
if (isset($_GET['debug'])) {
    echo "<div class='alert alert-info'>";
    echo "<h4>Debug Information</h4>";
    echo "<p>Current Month: $currentMonth, Year: $currentYear</p>";
    echo "<p>URL Parameters: month=" . ($_GET['month'] ?? 'not set') . ", year=" . ($_GET['year'] ?? 'not set') . "</p>";
    echo "<p>Date Range: $startDate to $endDate</p>";
    echo "<p>Found " . count($calendarEvents) . " events</p>";
    echo "<p>Events by date: " . count($eventsByDate) . " dates with events</p>";
    if (!empty($calendarEvents)) {
        echo "<p><strong>Calendar Events Array:</strong></p>";
        echo "<pre>" . print_r($calendarEvents, true) . "</pre>";
    }
    echo "<p><strong>Events by Date Array:</strong></p>";
    echo "<pre>" . print_r($eventsByDate, true) . "</pre>";
    echo "</div>";
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
                    <?php if (isset($_GET['debug']) && !empty($events)): ?>
                        <small class="text-info">[<?php echo count($events); ?>]</small>
                    <?php endif; ?>
                    <!-- DEBUG: Add visible marker for days with events -->
                    <?php if (!empty($events)): ?>
                        <div style="position: absolute; top: 0; right: 0; width: 10px; height: 10px; background: lime; border-radius: 50%;"></div>
                    <?php endif; ?>
                    <?php foreach (array_slice($events, 0, 3) as $event): ?>
                    <?php 
                    // Check if this is an upcoming deadline (within 7 days)
                    $eventDate = new DateTime($event['date']);
                    $today = new DateTime();
                    $futureDate = new DateTime('+7 days');
                    $isUpcoming = ($eventDate >= $today && $eventDate <= $futureDate);
                    $upcomingClass = $isUpcoming ? ' upcoming-deadline' : '';
                    ?>
                    <div class="calendar-event priority-<?php echo $event['priority']; ?><?php echo $upcomingClass; ?>" 
                         title="<?php echo htmlspecialchars($event['title']) . ($isUpcoming ? ' (Upcoming Deadline)' : ''); ?>"
                         style="<?php if (isset($_GET['debug'])): ?>background-color: red !important; color: white !important; font-weight: bold; border: 2px solid yellow;<?php endif; ?>">
                        <i class="bi bi-<?php echo $event['type'] === 'project' ? 'folder' : 'check-square'; ?>"></i>
                        <?php if ($isUpcoming): ?>
                        <i class="bi bi-exclamation-circle text-warning ms-1" style="font-size: 10px;"></i>
                        <?php endif; ?>
                        <?php echo htmlspecialchars(substr($event['title'], 0, 12) . (strlen($event['title']) > 12 ? '...' : '')); ?>
                        <?php if (isset($_GET['debug'])): ?>
                        <br><small>[<?php echo $event['type']; ?>]</small>
                        <?php endif; ?>
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
                            <div class="calendar-event priority-critical me-2 d-flex align-items-center justify-content-center" style="width: 20px; height: 16px;">
                                <i class="bi bi-exclamation-triangle" style="font-size: 10px;"></i>
                            </div>
                            <span>Critical Priority</span>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <div class="calendar-event priority-high me-2 d-flex align-items-center justify-content-center" style="width: 20px; height: 16px;">
                                <i class="bi bi-chevron-up" style="font-size: 10px;"></i>
                            </div>
                            <span>High Priority</span>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <div class="calendar-event priority-medium me-2 d-flex align-items-center justify-content-center" style="width: 20px; height: 16px;">
                                <i class="bi bi-dash" style="font-size: 10px;"></i>
                            </div>
                            <span>Medium Priority</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="calendar-event priority-low me-2 d-flex align-items-center justify-content-center" style="width: 20px; height: 16px;">
                                <i class="bi bi-chevron-down" style="font-size: 10px;"></i>
                            </div>
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
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-clock"></i> Upcoming Deadlines
                </h5>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="showDeadlinesToggle" checked onchange="toggleUpcomingDeadlines()">
                    <label class="form-check-label" for="showDeadlinesToggle">
                        Show on calendar
                    </label>
                </div>
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
                                onclick="navigateMonth(<?php echo $currentYear; ?>, <?php echo $month; ?>)"
                                data-month="<?php echo $month; ?>">
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

async function navigateMonth(year, month) {
    // Ensure we maintain any existing URL parameters
    const url = new URL(window.location);
    url.searchParams.set('month', month);
    url.searchParams.set('year', year);
    
    // Debug logging
    console.log('Navigating to:', year, month, 'without page refresh');
    
    // Update URL without page refresh
    window.history.pushState({month: month, year: year}, '', url.toString());
    
    // Try to load new calendar data via AJAX
    try {
        // Add a loading indicator
        const calendarContainer = document.querySelector('.calendar-container');
        const originalHTML = calendarContainer.innerHTML;
        calendarContainer.innerHTML = '<div class="text-center p-5"><i class="bi bi-hourglass-split"></i> Loading calendar...</div>';
        
        // Fetch new calendar data
        const response = await fetch(url.toString());
        if (response.ok) {
            const html = await response.text();
            
            // Extract just the calendar section from the response
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newCalendarContent = doc.querySelector('.calendar-container');
            
            if (newCalendarContent) {
                calendarContainer.innerHTML = newCalendarContent.innerHTML;
                console.log('Calendar updated successfully without page refresh!');
            } else {
                throw new Error('Calendar content not found in response');
            }
        } else {
            throw new Error('Failed to load calendar data');
        }
    } catch (error) {
        console.log('AJAX load failed, falling back to page reload:', error);
        
        // Fallback: Store scroll position and do page reload
        sessionStorage.setItem('calendarScrollY', window.scrollY);
        window.location.href = url.toString();
    }
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
    const calendarGrid = document.querySelector('.calendar-grid').parentElement;
    
    if (view === 'agenda') {
        showAgendaView();
    } else {
        showMonthView();
    }
    
    // Update view button text
    const viewButton = document.querySelector('.dropdown-toggle');
    if (view === 'agenda') {
        viewButton.innerHTML = '<i class="bi bi-list-ul"></i> Agenda View';
    } else {
        viewButton.innerHTML = '<i class="bi bi-calendar-month"></i> Month View';
    }
    
    // Save view preference
    localStorage.setItem('calendarView', view);
}

function showAgendaView() {
    const calendarContainer = document.querySelector('.calendar-container');
    const calendarGrid = calendarContainer.querySelector('.calendar-grid').parentElement;
    
    // Hide calendar grid
    calendarGrid.style.display = 'none';
    
    // Create agenda view if it doesn't exist
    let agendaView = calendarContainer.querySelector('.agenda-view');
    if (!agendaView) {
        agendaView = document.createElement('div');
        agendaView.className = 'agenda-view';
        agendaView.innerHTML = generateAgendaHTML();
        calendarContainer.appendChild(agendaView);
    }
    
    // Show agenda view
    agendaView.style.display = 'block';
}

function showMonthView() {
    const calendarContainer = document.querySelector('.calendar-container');
    const calendarGrid = calendarContainer.querySelector('.calendar-grid').parentElement;
    const agendaView = calendarContainer.querySelector('.agenda-view');
    
    // Show calendar grid
    calendarGrid.style.display = 'block';
    
    // Hide agenda view
    if (agendaView) {
        agendaView.style.display = 'none';
    }
}

function generateAgendaHTML() {
    // Sort events by date
    const sortedEvents = [...calendarEvents].sort((a, b) => new Date(a.date) - new Date(b.date));
    
    if (sortedEvents.length === 0) {
        return '<div class="text-center p-4"><i class="bi bi-calendar-x fs-1 text-muted"></i><p class="text-muted mt-2">No events this month</p></div>';
    }
    
    let html = '<div class="list-group list-group-flush">';
    let currentDate = '';
    
    sortedEvents.forEach(event => {
        const eventDate = new Date(event.date);
        const dateStr = eventDate.toLocaleDateString('en-US', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
        
        // Add date header if different from previous
        if (dateStr !== currentDate) {
            if (currentDate !== '') {
                html += '</div></div>'; // Close previous day's events
            }
            html += `<div class="agenda-date-group">
                        <h6 class="agenda-date-header bg-light p-2 mb-0">${dateStr}</h6>
                        <div class="agenda-events">`;
            currentDate = dateStr;
        }
        
        const icon = event.type === 'project' ? 'folder' : 'check-square';
        const priorityClass = `priority-${event.priority}`;
        
        html += `
            <div class="list-group-item agenda-event-item">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="agenda-event-content">
                        <div class="d-flex align-items-center mb-1">
                            <i class="bi bi-${icon} me-2"></i>
                            <h6 class="mb-0">${escapeHtml(event.title)}</h6>
                            <span class="badge bg-${getPriorityColor(event.priority)} ms-2">${event.priority}</span>
                        </div>
                        <p class="text-muted mb-0 small">${escapeHtml(event.description || '')}</p>
                    </div>
                    <div class="agenda-event-type">
                        <span class="badge bg-${event.type === 'project' ? 'primary' : 'info'}">${event.type}</span>
                    </div>
                </div>
            </div>
        `;
    });
    
    if (currentDate !== '') {
        html += '</div></div>'; // Close last day's events
    }
    
    html += '</div>';
    return html;
}

function toggleUpcomingDeadlines() {
    const toggle = document.getElementById('showDeadlinesToggle');
    const calendarEvents = document.querySelectorAll('.calendar-event');
    
    // Save the toggle state
    localStorage.setItem('showUpcomingDeadlines', toggle.checked ? '1' : '0');
    
    // Show/hide events on calendar based on toggle
    calendarEvents.forEach(event => {
        if (toggle.checked) {
            event.style.display = 'block';
        } else {
            // Only hide if it's an upcoming deadline (could be enhanced with better detection)
            const today = new Date();
            const eventDate = new Date(event.closest('.calendar-day').dataset.date);
            const daysDiff = Math.ceil((eventDate - today) / (1000 * 60 * 60 * 24));
            
            if (daysDiff >= 0 && daysDiff <= 30) {
                event.style.display = 'none';
            }
        }
    });
    
    AppTracker.showToast(
        toggle.checked ? 'Upcoming deadlines shown on calendar' : 'Upcoming deadlines hidden from calendar',
        'info'
    );
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

// TEMPORARILY DISABLED ALL DOM READY JAVASCRIPT TO DEBUG NAVIGATION ISSUE
// Add hover effects and tooltips
document.addEventListener('DOMContentLoaded', function() {
    console.log('Calendar loaded - Current month:', <?php echo $currentMonth; ?>, 'year:', <?php echo $currentYear; ?>);
    console.log('URL:', window.location.href);
    console.log('DOMContentLoaded fired - no auto-navigation should happen');
    
    // Restore scroll position if we navigated via calendar navigation
    const savedScrollY = sessionStorage.getItem('calendarScrollY');
    if (savedScrollY) {
        console.log('Restoring scroll position to:', savedScrollY);
        window.scrollTo(0, parseInt(savedScrollY));
        sessionStorage.removeItem('calendarScrollY');
    }
    
    // Add tooltips to calendar events
    const events = document.querySelectorAll('.calendar-event');
    console.log('Found calendar events:', events.length);
    
    // DEBUG: Let's see what calendar days have events
    const calendarDays = document.querySelectorAll('.calendar-day');
    console.log('Total calendar days:', calendarDays.length);
    
    calendarDays.forEach((day, index) => {
        const dayEvents = day.querySelectorAll('.calendar-event');
        const dateAttr = day.getAttribute('data-date');
        if (dayEvents.length > 0) {
            console.log(`Day ${dateAttr} has ${dayEvents.length} events:`, dayEvents);
        }
    });
    
    // DEBUG: Check if we have the events data from PHP
    console.log('Calendar events from PHP:', calendarEvents);
    
    // Temporarily disabled tooltips
    // events.forEach(event => {
    //     new bootstrap.Tooltip(event);
    // });
    
    // ALL AUTO-INITIALIZATION DISABLED FOR DEBUGGING
    
    // // Initialize upcoming deadlines toggle state
    // const showDeadlines = localStorage.getItem('showUpcomingDeadlines') !== '0';
    // const toggle = document.getElementById('showDeadlinesToggle');
    // if (toggle) {
    //     toggle.checked = showDeadlines;
    // }
    
    // // Initialize calendar view preference  
    // const savedView = localStorage.getItem('calendarView') || 'month';
    
    // // Highlight today if it's in the current month - but don't auto-scroll or navigate
    // const today = new Date();
    // const currentDate = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
    // const todayCell = document.querySelector(`[data-date="${currentDate}"]`);
    // if (todayCell) {
    //     // Only scroll if we're viewing the current month, don't force navigation
    //     const currentMonth = <?php echo $currentMonth; ?>;
    //     const currentYear = <?php echo $currentYear; ?>;
    //     if (today.getFullYear() === currentYear && (today.getMonth() + 1) === currentMonth) {
    //         todayCell.scrollIntoView({ behavior: 'smooth', block: 'center' });
    //     }
    // }
});
</script>

    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>
</body>
</html>