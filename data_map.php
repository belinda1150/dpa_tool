<?php
/**
 * DPA Tool - Data Mapping / Flow Diagram
 * Visual representation of data flows using ECharts
 */

require_once 'config/config.php';
require_once 'includes/auth.php';
require_login();

$org_id = get_current_org_id();

// Fetch departments with ROPA
$stmt = db_query("SELECT d.dept_id, d.dept_name, COUNT(pa.ropa_id) as activity_count
                  FROM departments d
                  LEFT JOIN processing_activities pa ON d.dept_id = pa.dept_id AND pa.org_id = d.org_id
                  WHERE d.org_id = ?
                  GROUP BY d.dept_id
                  ORDER BY d.dept_name", [$org_id]);
$departments = db_fetch_all($stmt);

// Fetch processing activities with relationships
$stmt = db_query("SELECT pa.ropa_id, pa.activity_name, pa.dept_id, pa.has_cross_border,
                         d.dept_name, p.purpose_name
                  FROM processing_activities pa
                  LEFT JOIN departments d ON pa.dept_id = d.dept_id
                  LEFT JOIN purposes p ON pa.purpose_id = p.purpose_id
                  WHERE pa.org_id = ? AND pa.status != 'archived'", [$org_id]);
$activities = db_fetch_all($stmt);

// Fetch recipients linked to ROPA
$stmt = db_query("SELECT rr.ropa_id, r.recipient_id, r.recipient_name, r.recipient_type, r.country
                  FROM ropa_recipients rr
                  INNER JOIN recipients r ON rr.recipient_id = r.recipient_id
                  INNER JOIN processing_activities pa ON rr.ropa_id = pa.ropa_id
                  WHERE pa.org_id = ?", [$org_id]);
$ropa_recipients = db_fetch_all($stmt);

// Fetch vendors (active)
$stmt = db_query("SELECT vendor_id, vendor_name, vendor_type, country
                  FROM vendors WHERE org_id = ? AND status = 'active'", [$org_id]);
$vendors = db_fetch_all($stmt);

// Fetch storage locations linked to ROPA
$stmt = db_query("SELECT rsl.ropa_id, sl.location_id, sl.location_name, sl.location_type, sl.provider, sl.country
                  FROM ropa_storage_locations rsl
                  INNER JOIN storage_locations sl ON rsl.location_id = sl.location_id
                  INNER JOIN processing_activities pa ON rsl.ropa_id = pa.ropa_id
                  WHERE pa.org_id = ?", [$org_id]);
$ropa_storage = db_fetch_all($stmt);

// Fetch cross-border transfers
$stmt = db_query("SELECT cb_id, ropa_id, destination_country, recipient_org
                  FROM cross_border_transfers
                  WHERE org_id = ? AND status IN ('approved','active','pending')", [$org_id]);
$cb_transfers = db_fetch_all($stmt);

// Build nodes and edges for ECharts
$nodes = [];
$edges = [];
$node_ids = [];

// Helper to add node
function add_node(&$nodes, &$node_ids, $id, $name, $category, $size = 30, $extra = []) {
    if (!isset($node_ids[$id])) {
        $node = [
            'id' => $id,
            'name' => $name,
            'category' => $category,
            'symbolSize' => $size,
            'value' => $name,
        ];
        $nodes[] = array_merge($node, $extra);
        $node_ids[$id] = true;
    }
}

// Add organization as center node
add_node($nodes, $node_ids, 'org_center', $GLOBALS['org_name'] ?? 'Organization', 0, 50);

// Add departments
foreach ($departments as $d) {
    $did = 'dept_' . $d['dept_id'];
    add_node($nodes, $node_ids, $did, $d['dept_name'], 1, max(20, min(40, 15 + $d['activity_count'] * 5)));
    $edges[] = ['source' => 'org_center', 'target' => $did];
}

// Add recipients
$recipient_ids_added = [];
foreach ($ropa_recipients as $rr) {
    $rid = 'recip_' . $rr['recipient_id'];
    add_node($nodes, $node_ids, $rid, $rr['recipient_name'], 2, 25);
    $recipient_ids_added[$rr['recipient_id']] = true;

    // Link via department
    $activity = null;
    foreach ($activities as $a) {
        if ($a['ropa_id'] == $rr['ropa_id']) { $activity = $a; break; }
    }
    if ($activity && $activity['dept_id']) {
        $source = 'dept_' . $activity['dept_id'];
        $edges[] = ['source' => $source, 'target' => $rid, 'label' => ['show' => false]];
    }
}

// Add vendors
foreach ($vendors as $v) {
    $vid = 'vendor_' . $v['vendor_id'];
    add_node($nodes, $node_ids, $vid, $v['vendor_name'], 3, 25);
    $edges[] = ['source' => 'org_center', 'target' => $vid];
}

// Add storage locations
$storage_ids_added = [];
foreach ($ropa_storage as $rs) {
    $sid = 'storage_' . $rs['location_id'];
    $label = $rs['location_name'] . ($rs['provider'] ? ' (' . $rs['provider'] . ')' : '');
    add_node($nodes, $node_ids, $sid, $label, 4, 22);
    $storage_ids_added[$rs['location_id']] = true;

    $activity = null;
    foreach ($activities as $a) {
        if ($a['ropa_id'] == $rs['ropa_id']) { $activity = $a; break; }
    }
    if ($activity && $activity['dept_id']) {
        $source = 'dept_' . $activity['dept_id'];
        $edges[] = ['source' => $source, 'target' => $sid];
    }
}

// Add cross-border transfer destinations
foreach ($cb_transfers as $cb) {
    $cbid = 'country_' . md5($cb['destination_country']);
    add_node($nodes, $node_ids, $cbid, $cb['destination_country'], 5, 28);
    if ($cb['ropa_id']) {
        $activity = null;
        foreach ($activities as $a) {
            if ($a['ropa_id'] == $cb['ropa_id']) { $activity = $a; break; }
        }
        if ($activity && $activity['dept_id']) {
            $edges[] = ['source' => 'dept_' . $activity['dept_id'], 'target' => $cbid];
        }
    } else {
        $edges[] = ['source' => 'org_center', 'target' => $cbid];
    }
}

// Stats
$total_nodes = count($nodes);
$total_edges = count($edges);

// Fetch org name for center
$stmt = db_query("SELECT org_name FROM organizations WHERE org_id = ?", [$org_id]);
$org_data = db_fetch_one($stmt);
// Update the org center node name
foreach ($nodes as &$n) {
    if ($n['id'] === 'org_center') {
        $n['name'] = $org_data['org_name'] ?? 'Organization';
        $n['value'] = $n['name'];
    }
}
unset($n);
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Data Map</title>
    <script src="assets/js/theme.js"></script>
    <link href="assets/css/theme-variables.css" rel="stylesheet" />
    <link href="assets/css/bootstrap5.min.css" rel="stylesheet" />
    <link href="assets/css/css/all.min.css" rel="stylesheet" />
    <link href="assets/css/css/v4-shims.min.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
    <link href="assets/css/module-styles.css" rel="stylesheet" />
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
</head>
<body>
    <div id="wrapper">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>

        <div id="page-wrapper">
            <div id="page-inner">

                <div class="row">
                    <div class="col-md-12">
                        <h2>Data Flow Map</h2>
                        <h5>Visual overview of how personal data flows through your organization</h5>
                    </div>
                </div>
                <hr />

                <!-- Stats -->
                <div class="row mb-3">
                    <div class="col-md-2">
                        <div class="card border-primary">
                            <div class="card-body text-center py-2">
                                <h4 class="mb-0"><?php echo count($departments); ?></h4>
                                <small class="text-muted">Departments</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card border-success">
                            <div class="card-body text-center py-2">
                                <h4 class="mb-0"><?php echo count($activities); ?></h4>
                                <small class="text-muted">Activities</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card border-info">
                            <div class="card-body text-center py-2">
                                <h4 class="mb-0"><?php echo count($recipient_ids_added); ?></h4>
                                <small class="text-muted">Recipients</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card border-warning">
                            <div class="card-body text-center py-2">
                                <h4 class="mb-0"><?php echo count($vendors); ?></h4>
                                <small class="text-muted">Vendors</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card border-secondary">
                            <div class="card-body text-center py-2">
                                <h4 class="mb-0"><?php echo count($storage_ids_added); ?></h4>
                                <small class="text-muted">Storage</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card border-danger">
                            <div class="card-body text-center py-2">
                                <h4 class="mb-0"><?php echo count($cb_transfers); ?></h4>
                                <small class="text-muted">Cross-Border</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chart -->
                <div class="card">
                    <div class="card-header">
                        <i class="fa fa-sitemap"></i> Data Flow Diagram
                        <small class="text-muted ms-2"><?php echo $total_nodes; ?> nodes, <?php echo $total_edges; ?> connections</small>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <?php if ($total_nodes <= 1): ?>
                            <div class="alert alert-info m-3">
                                <i class="fa fa-info-circle"></i> No data flow connections found. Add ROPA entries with departments, recipients, and storage locations to generate the data map.
                            </div>
                        <?php else: ?>
                            <div id="dataMapChart" style="width: 100%; height: 600px;"></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Legend -->
                <div class="card mt-3">
                    <div class="card-body">
                        <strong>Legend:</strong>
                        <span class="ms-3"><i class="fa fa-circle" style="color:#1B1464;"></i> Organization</span>
                        <span class="ms-3"><i class="fa fa-circle" style="color:#667eea;"></i> Departments</span>
                        <span class="ms-3"><i class="fa fa-circle" style="color:#28a745;"></i> Recipients</span>
                        <span class="ms-3"><i class="fa fa-circle" style="color:#fd7e14;"></i> Vendors</span>
                        <span class="ms-3"><i class="fa fa-circle" style="color:#6c757d;"></i> Storage</span>
                        <span class="ms-3"><i class="fa fa-circle" style="color:#dc3545;"></i> Cross-Border</span>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="assets/js/jquery-3.7.1.min.js"></script>
    <script src="assets/js/bootstrap5.bundle.min.js"></script>
    <script src="assets/js/sidebar-menu.js"></script>
    <script src="assets/js/echarts.min.js"></script>
    <script src="assets/js/custom.js"></script>
    <script src="assets/js/global-search.js"></script>

    <?php if ($total_nodes > 1): ?>
    <script>
    var chartDom = document.getElementById('dataMapChart');
    var myChart = echarts.init(chartDom);

    var categories = [
        { name: 'Organization' },
        { name: 'Department' },
        { name: 'Recipient' },
        { name: 'Vendor' },
        { name: 'Storage' },
        { name: 'Cross-Border' }
    ];

    var categoryColors = ['#1B1464', '#667eea', '#28a745', '#fd7e14', '#6c757d', '#dc3545'];

    var graphData = {
        nodes: <?php echo json_encode(array_values($nodes)); ?>,
        edges: <?php echo json_encode($edges); ?>
    };

    // Apply colors
    graphData.nodes.forEach(function(node) {
        node.itemStyle = { color: categoryColors[node.category] || '#999' };
        node.label = {
            show: node.symbolSize >= 25,
            fontSize: node.category === 0 ? 14 : 11,
            fontWeight: node.category === 0 ? 'bold' : 'normal'
        };
    });

    var option = {
        tooltip: {
            trigger: 'item',
            formatter: function(params) {
                if (params.dataType === 'node') {
                    return '<strong>' + params.data.name + '</strong><br>' + categories[params.data.category].name;
                }
                return params.data.source + ' → ' + params.data.target;
            }
        },
        legend: {
            data: categories.map(function(c) { return c.name; }),
            bottom: 10,
            textStyle: { color: 'var(--text-primary)' }
        },
        series: [{
            type: 'graph',
            layout: 'force',
            data: graphData.nodes,
            links: graphData.edges,
            categories: categories,
            roam: true,
            draggable: true,
            force: {
                repulsion: 300,
                edgeLength: [80, 200],
                gravity: 0.1
            },
            lineStyle: {
                color: '#aaa',
                width: 1.5,
                curveness: 0.1,
                opacity: 0.6
            },
            emphasis: {
                focus: 'adjacency',
                lineStyle: { width: 3 }
            },
            edgeSymbol: ['none', 'arrow'],
            edgeSymbolSize: 8
        }]
    };

    myChart.setOption(option);
    window.addEventListener('resize', function() { myChart.resize(); });
    </script>
    <?php endif; ?>
</body>
</html>
