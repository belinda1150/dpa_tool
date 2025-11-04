<?php
/**
 * DPA Tool - Risk Heat Map
 * Version: 1.0
 * Date: October 2025
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$org_id = get_current_org_id();

// Get filter parameters
$filter_status = $_GET['status'] ?? '';
$filter_dept = $_GET['dept_id'] ?? '';

// Build query
$where_conditions = ["r.org_id = ?"];
$params = [$org_id];

if (!empty($filter_status)) {
    $where_conditions[] = "r.status = ?";
    $params[] = $filter_status;
}

if (!empty($filter_dept)) {
    $where_conditions[] = "r.dept_id = ?";
    $params[] = $filter_dept;
}

$where_clause = implode(' AND ', $where_conditions);

// Get all risks with their scores
$query = "SELECT r.*, d.dept_name, u.first_name, u.last_name
          FROM risks r
          LEFT JOIN departments d ON r.dept_id = d.dept_id
          LEFT JOIN users u ON r.owner_id = u.user_id
          WHERE $where_clause
          ORDER BY r.inherent_score DESC";

$stmt = db_query($query, $params);
$risks = db_fetch_all($stmt);

// Organize risks by likelihood and impact for heatmap
$heatmap = array_fill(1, 5, array_fill(1, 5, []));

foreach ($risks as $risk) {
    $likelihood = $risk['likelihood'];
    $impact = $risk['impact'];
    $heatmap[$likelihood][$impact][] = $risk;
}

// Get departments for filter
$dept_query = "SELECT * FROM departments WHERE org_id = ? ORDER BY dept_name";
$dept_stmt = db_query($dept_query, [$org_id]);
$departments = db_fetch_all($dept_stmt);

// Calculate statistics
$total_risks = count($risks);
$high_risks = count(array_filter($risks, function($r) { return $r['inherent_score'] >= 15; }));
$medium_risks = count(array_filter($risks, function($r) { return $r['inherent_score'] >= 8 && $r['inherent_score'] < 15; }));
$low_risks = count(array_filter($risks, function($r) { return $r['inherent_score'] < 8; }));

// Get flash message
$flash = get_flash_message();
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Risk Heat Map</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
    <style>
        .heatmap-container {
            margin: 20px 0;
            overflow-x: auto;
        }
        .heatmap-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 2px;
            table-layout: fixed;
        }
        .heatmap-table th {
            background: #34495e;
            color: white;
            padding: 10px;
            text-align: center;
            font-weight: bold;
        }
        .heatmap-table td {
            width: 18%;
            height: 120px;
            padding: 5px;
            text-align: center;
            vertical-align: middle;
            border: 2px solid #ddd;
            cursor: pointer;
            position: relative;
            transition: all 0.3s;
        }
        .heatmap-table td:hover {
            transform: scale(1.05);
            z-index: 10;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .heatmap-cell-content {
            font-size: 24px;
            font-weight: bold;
            color: white;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }
        .heatmap-cell-label {
            font-size: 11px;
            color: white;
            margin-top: 5px;
        }

        /* Risk level colors */
        .risk-extreme { background: #c0392b; }
        .risk-high { background: #e74c3c; }
        .risk-medium { background: #f39c12; }
        .risk-low { background: #f1c40f; }
        .risk-minimal { background: #27ae60; }

        .axis-label {
            background: #34495e;
            color: white;
            font-weight: bold;
            text-align: center;
            padding: 10px;
        }

        .legend {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .legend-item {
            display: inline-block;
            margin-right: 20px;
            margin-bottom: 10px;
        }
        .legend-color {
            display: inline-block;
            width: 30px;
            height: 20px;
            margin-right: 5px;
            vertical-align: middle;
            border: 1px solid #ddd;
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
                        <h2>Risk Heat Map</h2>
                        <h5>Visual representation of risk distribution by likelihood and impact</h5>
                    </div>
                </div>
                <hr />

                <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type'] == 'success' ? 'success' : 'danger'; ?> alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
                <?php endif; ?>

                <!-- Statistics -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="panel panel-default">
                            <div class="panel-body">
                                <div class="stat-panel text-center">
                                    <h3 class="text-primary"><?php echo $total_risks; ?></h3>
                                    <p>Total Risks</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-danger">
                            <div class="panel-body">
                                <div class="stat-panel text-center">
                                    <h3><?php echo $high_risks; ?></h3>
                                    <p>High Risks (≥15)</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-warning">
                            <div class="panel-body">
                                <div class="stat-panel text-center">
                                    <h3><?php echo $medium_risks; ?></h3>
                                    <p>Medium Risks (8-14)</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-success">
                            <div class="panel-body">
                                <div class="stat-panel text-center">
                                    <h3><?php echo $low_risks; ?></h3>
                                    <p>Low Risks (<8)</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <i class="fa fa-filter"></i> Filters
                            </div>
                            <div class="panel-body">
                                <form method="get" action="risk_heatmap.php" class="form-inline">
                                    <div class="form-group">
                                        <label>Status:</label>
                                        <select name="status" class="form-control">
                                            <option value="">All Statuses</option>
                                            <option value="open" <?php echo $filter_status == 'open' ? 'selected' : ''; ?>>Open</option>
                                            <option value="mitigating" <?php echo $filter_status == 'mitigating' ? 'selected' : ''; ?>>Mitigating</option>
                                            <option value="closed" <?php echo $filter_status == 'closed' ? 'selected' : ''; ?>>Closed</option>
                                        </select>
                                    </div>
                                    <div class="form-group" style="margin-left: 10px;">
                                        <label>Department:</label>
                                        <select name="dept_id" class="form-control">
                                            <option value="">All Departments</option>
                                            <?php foreach ($departments as $dept): ?>
                                                <option value="<?php echo $dept['dept_id']; ?>" <?php echo $filter_dept == $dept['dept_id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($dept['dept_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary" style="margin-left: 10px;">
                                        <i class="fa fa-filter"></i> Apply
                                    </button>
                                    <a href="risk_heatmap.php" class="btn btn-default">
                                        <i class="fa fa-refresh"></i> Reset
                                    </a>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Heat Map -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <div class="row">
                                    <div class="col-md-6">
                                        <i class="fa fa-th"></i> Risk Heat Map (Likelihood × Impact)
                                    </div>
                                    <div class="col-md-6 text-right">
                                        <a href="risk_list.php" class="btn btn-default btn-sm">
                                            <i class="fa fa-list"></i> List View
                                        </a>
                                        <a href="risk_add.php" class="btn btn-primary btn-sm">
                                            <i class="fa fa-plus"></i> Add Risk
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="panel-body">
                                <div class="heatmap-container">
                                    <table class="heatmap-table">
                                        <tr>
                                            <th rowspan="6" class="axis-label" style="width: 5%; writing-mode: vertical-lr; transform: rotate(180deg);">
                                                LIKELIHOOD →
                                            </th>
                                            <th style="width: 5%;"></th>
                                            <th>1 - Rare</th>
                                            <th>2 - Unlikely</th>
                                            <th>3 - Possible</th>
                                            <th>4 - Likely</th>
                                            <th>5 - Almost Certain</th>
                                        </tr>
                                        <?php for ($impact = 5; $impact >= 1; $impact--): ?>
                                        <tr>
                                            <td class="axis-label">
                                                <?php
                                                $impact_labels = [1 => 'Insignificant', 2 => 'Minor', 3 => 'Moderate', 4 => 'Major', 5 => 'Catastrophic'];
                                                echo $impact . ' - ' . $impact_labels[$impact];
                                                ?>
                                            </td>
                                            <?php for ($likelihood = 1; $likelihood <= 5; $likelihood++): ?>
                                                <?php
                                                $score = $likelihood * $impact;
                                                $risk_class = '';
                                                if ($score >= 20) $risk_class = 'risk-extreme';
                                                elseif ($score >= 15) $risk_class = 'risk-high';
                                                elseif ($score >= 8) $risk_class = 'risk-medium';
                                                elseif ($score >= 4) $risk_class = 'risk-low';
                                                else $risk_class = 'risk-minimal';

                                                $cell_risks = $heatmap[$likelihood][$impact] ?? [];
                                                $count = count($cell_risks);
                                                ?>
                                                <td class="<?php echo $risk_class; ?>"
                                                    onclick="showRisks(<?php echo $likelihood; ?>, <?php echo $impact; ?>)"
                                                    data-likelihood="<?php echo $likelihood; ?>"
                                                    data-impact="<?php echo $impact; ?>">
                                                    <div class="heatmap-cell-content"><?php echo $count; ?></div>
                                                    <div class="heatmap-cell-label">Score: <?php echo $score; ?></div>
                                                </td>
                                            <?php endfor; ?>
                                        </tr>
                                        <?php endfor; ?>
                                        <tr>
                                            <td colspan="7" class="axis-label">IMPACT →</td>
                                        </tr>
                                    </table>
                                </div>

                                <!-- Legend -->
                                <div class="legend">
                                    <strong>Risk Levels:</strong><br>
                                    <div class="legend-item">
                                        <span class="legend-color risk-extreme"></span> Extreme (20-25): Immediate action required
                                    </div>
                                    <div class="legend-item">
                                        <span class="legend-color risk-high"></span> High (15-19): Senior management attention
                                    </div>
                                    <div class="legend-item">
                                        <span class="legend-color risk-medium"></span> Medium (8-14): Management oversight
                                    </div>
                                    <div class="legend-item">
                                        <span class="legend-color risk-low"></span> Low (4-7): Monitor
                                    </div>
                                    <div class="legend-item">
                                        <span class="legend-color risk-minimal"></span> Minimal (1-3): Accept
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Risk Details Modal Placeholder -->
                <div id="riskDetailsContainer"></div>

            </div>
        </div>
    </div>

    <script src="assets/js/jquery-1.10.2.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/jquery.metisMenu.js"></script>
    <script src="assets/js/custom.js"></script>
    <script>
        // Store risks data for JavaScript access
        var risksData = <?php echo json_encode($heatmap); ?>;

        function showRisks(likelihood, impact) {
            var risks = risksData[likelihood][impact];

            if (risks.length === 0) {
                alert('No risks in this category');
                return;
            }

            var html = '<div class="panel panel-info"><div class="panel-heading">';
            html += '<h4>Risks: Likelihood ' + likelihood + ' × Impact ' + impact + ' (Score: ' + (likelihood * impact) + ')</h4>';
            html += '</div><div class="panel-body"><table class="table table-striped">';
            html += '<thead><tr><th>Risk Title</th><th>Category</th><th>Owner</th><th>Status</th><th>Actions</th></tr></thead><tbody>';

            risks.forEach(function(risk) {
                html += '<tr>';
                html += '<td>' + escapeHtml(risk.risk_title) + '</td>';
                html += '<td><span class="label label-default">' + risk.risk_category + '</span></td>';
                html += '<td>' + (risk.first_name ? escapeHtml(risk.first_name + ' ' + risk.last_name) : 'N/A') + '</td>';
                html += '<td><span class="label label-' + getStatusClass(risk.status) + '">' + risk.status + '</span></td>';
                html += '<td><a href="risk_view.php?id=' + risk.risk_id + '" class="btn btn-info btn-xs">View</a></td>';
                html += '</tr>';
            });

            html += '</tbody></table></div></div>';

            $('#riskDetailsContainer').html(html);
            $('html, body').animate({
                scrollTop: $("#riskDetailsContainer").offset().top - 100
            }, 500);
        }

        function getStatusClass(status) {
            switch(status) {
                case 'open': return 'danger';
                case 'mitigating': return 'warning';
                case 'closed': return 'success';
                default: return 'default';
            }
        }

        function escapeHtml(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    </script>
</body>
</html>
