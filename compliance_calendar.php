<?php
/**
 * DPA Tool - Compliance Calendar
 * Consolidated view of all upcoming compliance deadlines
 */

require_once 'config/config.php';
require_once 'includes/auth.php';
require_login();

$org_id = get_current_org_id();
$user_id = get_current_user_id();

// Month navigation
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));

if ($month < 1) { $month = 12; $year--; }
if ($month > 12) { $month = 1; $year++; }

$filter_type = $_GET['type'] ?? 'all';

$first_day = mktime(0, 0, 0, $month, 1, $year);
$days_in_month = date('t', $first_day);
$start_weekday = date('w', $first_day); // 0=Sun
$month_name = date('F Y', $first_day);

$month_start = date('Y-m-01', $first_day);
$month_end = date('Y-m-t', $first_day);
$today = date('Y-m-d');
$week_end = date('Y-m-d', strtotime('+7 days'));

// Collect all events
$events = [];

// DSR due dates
if ($filter_type === 'all' || $filter_type === 'dsr') {
    $stmt = db_query("SELECT dsr_id, subject_name, due_at, status
                      FROM dsr_requests
                      WHERE org_id = ? AND status NOT IN ('completed','rejected')
                      AND DATE(due_at) BETWEEN ? AND ?",
        [$org_id, $month_start, $month_end]);
    foreach (db_fetch_all($stmt) as $r) {
        $events[] = [
            'date' => date('Y-m-d', strtotime($r['due_at'])),
            'title' => 'DSR: ' . $r['subject_name'],
            'type' => 'dsr',
            'icon' => 'fa-user',
            'color' => '#7f00ff',
            'url' => 'dsr_view.php?id=' . $r['dsr_id'],
            'status' => $r['status'],
        ];
    }
}

// Vendor review dates
if ($filter_type === 'all' || $filter_type === 'vendor') {
    $stmt = db_query("SELECT vendor_id, vendor_name, next_review_date
                      FROM vendors
                      WHERE org_id = ? AND status = 'active' AND next_review_date BETWEEN ? AND ?",
        [$org_id, $month_start, $month_end]);
    foreach (db_fetch_all($stmt) as $r) {
        $events[] = [
            'date' => $r['next_review_date'],
            'title' => 'Vendor Review: ' . $r['vendor_name'],
            'type' => 'vendor',
            'icon' => 'fa-briefcase',
            'color' => '#fd7e14',
            'url' => 'vendor_view.php?id=' . $r['vendor_id'],
        ];
    }

    // Vendor DPA expiry
    $stmt = db_query("SELECT vendor_id, vendor_name, dpa_expiry_date
                      FROM vendors
                      WHERE org_id = ? AND status = 'active' AND dpa_status = 'signed' AND dpa_expiry_date BETWEEN ? AND ?",
        [$org_id, $month_start, $month_end]);
    foreach (db_fetch_all($stmt) as $r) {
        $events[] = [
            'date' => $r['dpa_expiry_date'],
            'title' => 'DPA Expiry: ' . $r['vendor_name'],
            'type' => 'vendor',
            'icon' => 'fa-file-text-o',
            'color' => '#dc3545',
            'url' => 'vendor_view.php?id=' . $r['vendor_id'],
        ];
    }

    // Vendor contract end
    $stmt = db_query("SELECT vendor_id, vendor_name, contract_end_date
                      FROM vendors
                      WHERE org_id = ? AND status = 'active' AND contract_end_date BETWEEN ? AND ?",
        [$org_id, $month_start, $month_end]);
    foreach (db_fetch_all($stmt) as $r) {
        $events[] = [
            'date' => $r['contract_end_date'],
            'title' => 'Contract End: ' . $r['vendor_name'],
            'type' => 'vendor',
            'icon' => 'fa-handshake-o',
            'color' => '#fd7e14',
            'url' => 'vendor_view.php?id=' . $r['vendor_id'],
        ];
    }
}

// Consent expiry
if ($filter_type === 'all' || $filter_type === 'consent') {
    $stmt = db_query("SELECT consent_id, purpose, subject_name, DATE(expires_at) as expiry_date
                      FROM consents
                      WHERE org_id = ? AND status = 'granted' AND DATE(expires_at) BETWEEN ? AND ?",
        [$org_id, $month_start, $month_end]);
    foreach (db_fetch_all($stmt) as $r) {
        $events[] = [
            'date' => $r['expiry_date'],
            'title' => 'Consent Expiry: ' . ($r['subject_name'] ?? $r['purpose']),
            'type' => 'consent',
            'icon' => 'fa-check-square-o',
            'color' => '#17a2b8',
            'url' => 'consent_list.php',
        ];
    }
}

// Training due dates
if ($filter_type === 'all' || $filter_type === 'training') {
    $stmt = db_query("SELECT ta.assign_id, ta.due_at, ta.status, t.training_title,
                             u.first_name, u.last_name
                      FROM training_assignments ta
                      INNER JOIN training t ON ta.training_id = t.training_id
                      INNER JOIN users u ON ta.user_id = u.user_id
                      WHERE u.org_id = ? AND ta.status NOT IN ('completed')
                      AND DATE(ta.due_at) BETWEEN ? AND ?",
        [$org_id, $month_start, $month_end]);
    foreach (db_fetch_all($stmt) as $r) {
        $events[] = [
            'date' => date('Y-m-d', strtotime($r['due_at'])),
            'title' => 'Training: ' . $r['training_title'] . ' (' . $r['first_name'] . ')',
            'type' => 'training',
            'icon' => 'fa-graduation-cap',
            'color' => '#28a745',
            'url' => 'training_list.php',
        ];
    }
}

// Policy review dates
if ($filter_type === 'all' || $filter_type === 'policy') {
    $stmt = db_query("SELECT policy_id, policy_title, review_due_date
                      FROM policies
                      WHERE org_id = ? AND status = 'published' AND review_due_date BETWEEN ? AND ?",
        [$org_id, $month_start, $month_end]);
    foreach (db_fetch_all($stmt) as $r) {
        $events[] = [
            'date' => $r['review_due_date'],
            'title' => 'Policy Review: ' . $r['policy_title'],
            'type' => 'policy',
            'icon' => 'fa-book',
            'color' => '#6610f2',
            'url' => 'policy_view.php?id=' . $r['policy_id'],
        ];
    }
}

// Organize events by date
$events_by_date = [];
foreach ($events as $e) {
    $events_by_date[$e['date']][] = $e;
}

// Summary stats (across all time, not just this month)
$overdue_count = 0;
$due_week_count = 0;
$due_month_count = count($events);

// Count overdue across all sources
$overdue_queries = [
    "SELECT COUNT(*) as c FROM dsr_requests WHERE org_id = ? AND status NOT IN ('completed','rejected') AND due_at < NOW()",
    "SELECT COUNT(*) as c FROM vendors WHERE org_id = ? AND status = 'active' AND next_review_date < CURDATE() AND next_review_date IS NOT NULL",
    "SELECT COUNT(*) as c FROM policies WHERE org_id = ? AND status = 'published' AND review_due_date < CURDATE() AND review_due_date IS NOT NULL",
];
foreach ($overdue_queries as $oq) {
    $stmt = db_query($oq, [$org_id]);
    $overdue_count += db_fetch_one($stmt)['c'];
}

// Due this week
foreach ($events as $e) {
    if ($e['date'] >= $today && $e['date'] <= $week_end) $due_week_count++;
    if ($e['date'] < $today) $overdue_count; // already counted above
}

// Prev/next month links
$prev_month = $month - 1;
$prev_year = $year;
if ($prev_month < 1) { $prev_month = 12; $prev_year--; }
$next_month = $month + 1;
$next_year = $year;
if ($next_month > 12) { $next_month = 1; $next_year++; }
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Compliance Calendar</title>
    <script src="assets/js/theme.js"></script>
    <link href="assets/css/theme-variables.css" rel="stylesheet" />
    <link href="assets/css/bootstrap5.min.css" rel="stylesheet" />
    <link href="assets/css/css/all.min.css" rel="stylesheet" />
    <link href="assets/css/css/v4-shims.min.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
    <link href="assets/css/module-styles.css" rel="stylesheet" />
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
    <style>
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background: var(--border-color);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
        }
        .calendar-header-cell {
            background: var(--bg-tertiary);
            padding: 10px;
            text-align: center;
            font-weight: 600;
            font-size: 13px;
            color: var(--text-secondary);
        }
        .calendar-cell {
            background: var(--bg-card);
            min-height: 100px;
            padding: 6px 8px;
            vertical-align: top;
            position: relative;
        }
        .calendar-cell.empty {
            background: var(--bg-secondary);
        }
        .calendar-cell.today {
            background: rgba(27, 20, 100, 0.05);
            box-shadow: inset 0 0 0 2px #1B1464;
        }
        [data-theme="dark"] .calendar-cell.today {
            background: rgba(91, 159, 230, 0.1);
            box-shadow: inset 0 0 0 2px #5b9fe6;
        }
        .calendar-day-num {
            font-weight: 600;
            font-size: 14px;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        .calendar-event {
            display: block;
            font-size: 11px;
            padding: 2px 6px;
            margin-bottom: 2px;
            border-radius: 3px;
            color: #fff;
            text-decoration: none;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            transition: opacity 0.2s;
        }
        .calendar-event:hover {
            opacity: 0.85;
            color: #fff;
            text-decoration: none;
        }
        .calendar-event.overdue {
            opacity: 0.7;
            text-decoration: line-through;
        }
        .calendar-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .calendar-nav h3 {
            margin: 0;
            color: var(--text-heading);
        }
        .filter-pills {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }
        .filter-pills a {
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 13px;
            text-decoration: none;
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
            background: var(--bg-card);
        }
        .filter-pills a.active {
            background: #1B1464;
            color: #fff;
            border-color: #1B1464;
        }
        .more-events {
            font-size: 10px;
            color: var(--text-muted);
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div id="wrapper">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>

        <div id="page-wrapper">
            <div id="page-inner">

                <div class="row">
                    <div class="col-md-12">
                        <h2>Compliance Calendar</h2>
                        <h5>Upcoming deadlines and review dates</h5>
                    </div>
                </div>
                <hr />

                <!-- Summary Stats -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="card border-danger">
                            <div class="card-body text-center py-2">
                                <h3 class="mb-0 text-danger"><?php echo $overdue_count; ?></h3>
                                <small class="text-muted">Overdue</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-warning">
                            <div class="card-body text-center py-2">
                                <h3 class="mb-0" style="color:#fd7e14;"><?php echo $due_week_count; ?></h3>
                                <small class="text-muted">Due This Week</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-primary">
                            <div class="card-body text-center py-2">
                                <h3 class="mb-0 text-primary"><?php echo $due_month_count; ?></h3>
                                <small class="text-muted">This Month</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="filter-pills">
                    <?php
                    $base_url = "compliance_calendar.php?year=$year&month=$month";
                    $types = [
                        'all' => 'All',
                        'dsr' => 'DSR',
                        'vendor' => 'Vendors',
                        'consent' => 'Consents',
                        'training' => 'Training',
                        'policy' => 'Policies',
                    ];
                    foreach ($types as $k => $v): ?>
                    <a href="<?php echo $base_url . '&type=' . $k; ?>" class="<?php echo $filter_type === $k ? 'active' : ''; ?>"><?php echo $v; ?></a>
                    <?php endforeach; ?>
                </div>

                <!-- Calendar Navigation -->
                <div class="calendar-nav">
                    <a href="compliance_calendar.php?year=<?php echo $prev_year; ?>&month=<?php echo $prev_month; ?>&type=<?php echo $filter_type; ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="fa fa-chevron-left"></i> Prev
                    </a>
                    <h3><?php echo $month_name; ?></h3>
                    <a href="compliance_calendar.php?year=<?php echo $next_year; ?>&month=<?php echo $next_month; ?>&type=<?php echo $filter_type; ?>" class="btn btn-outline-secondary btn-sm">
                        Next <i class="fa fa-chevron-right"></i>
                    </a>
                </div>

                <!-- Calendar Grid -->
                <div class="calendar-grid">
                    <!-- Day headers -->
                    <?php foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $day): ?>
                    <div class="calendar-header-cell"><?php echo $day; ?></div>
                    <?php endforeach; ?>

                    <!-- Empty cells before month starts -->
                    <?php for ($i = 0; $i < $start_weekday; $i++): ?>
                    <div class="calendar-cell empty"></div>
                    <?php endfor; ?>

                    <!-- Day cells -->
                    <?php for ($d = 1; $d <= $days_in_month; $d++):
                        $cell_date = sprintf('%04d-%02d-%02d', $year, $month, $d);
                        $is_today = ($cell_date === $today);
                        $day_events = $events_by_date[$cell_date] ?? [];
                        $max_show = 3;
                    ?>
                    <div class="calendar-cell <?php echo $is_today ? 'today' : ''; ?>">
                        <div class="calendar-day-num"><?php echo $d; ?></div>
                        <?php $shown = 0; foreach ($day_events as $evt):
                            if ($shown >= $max_show) break;
                            $is_overdue = $cell_date < $today;
                        ?>
                        <a href="<?php echo htmlspecialchars($evt['url']); ?>"
                           class="calendar-event <?php echo $is_overdue ? 'overdue' : ''; ?>"
                           style="background: <?php echo $evt['color']; ?>;"
                           title="<?php echo htmlspecialchars($evt['title']); ?>">
                            <i class="fa <?php echo $evt['icon']; ?>"></i>
                            <?php echo htmlspecialchars(mb_strimwidth($evt['title'], 0, 25, '...')); ?>
                        </a>
                        <?php $shown++; endforeach; ?>
                        <?php if (count($day_events) > $max_show): ?>
                        <span class="more-events">+<?php echo count($day_events) - $max_show; ?> more</span>
                        <?php endif; ?>
                    </div>
                    <?php endfor; ?>

                    <!-- Empty cells after month ends -->
                    <?php
                    $total_cells = $start_weekday + $days_in_month;
                    $remaining = (7 - ($total_cells % 7)) % 7;
                    for ($i = 0; $i < $remaining; $i++): ?>
                    <div class="calendar-cell empty"></div>
                    <?php endfor; ?>
                </div>

                <!-- Event List for this month -->
                <?php if (!empty($events)): ?>
                <div class="card mt-3">
                    <div class="card-header">
                        <i class="fa fa-list"></i> Events This Month (<?php echo count($events); ?>)
                    </div>
                    <div class="card-body">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Event</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                usort($events, function($a, $b) { return strcmp($a['date'], $b['date']); });
                                foreach ($events as $evt):
                                    $is_overdue = $evt['date'] < $today;
                                    $is_soon = !$is_overdue && $evt['date'] <= $week_end;
                                ?>
                                <tr>
                                    <td><?php echo date('d M', strtotime($evt['date'])); ?></td>
                                    <td>
                                        <a href="<?php echo htmlspecialchars($evt['url']); ?>">
                                            <i class="fa <?php echo $evt['icon']; ?>" style="color:<?php echo $evt['color']; ?>;"></i>
                                            <?php echo htmlspecialchars($evt['title']); ?>
                                        </a>
                                    </td>
                                    <td><span class="badge" style="background:<?php echo $evt['color']; ?>;"><?php echo ucfirst($evt['type']); ?></span></td>
                                    <td>
                                        <?php if ($is_overdue): ?>
                                            <span class="badge bg-danger">Overdue</span>
                                        <?php elseif ($is_soon): ?>
                                            <span class="badge bg-warning text-dark">Due Soon</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary">Upcoming</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <script src="assets/js/jquery-3.7.1.min.js"></script>
    <script src="assets/js/bootstrap5.bundle.min.js"></script>
    <script src="assets/js/sidebar-menu.js"></script>
    <script src="assets/js/custom.js"></script>
    <script src="assets/js/global-search.js"></script>
</body>
</html>
