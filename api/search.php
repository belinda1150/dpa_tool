<?php
/**
 * DPA Tool - Global Search API
 * Searches across all modules and returns JSON results
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$org_id = get_current_org_id();
$q = trim($_GET['q'] ?? '');
$type = $_GET['type'] ?? 'all';

if (strlen($q) < 2) {
    echo json_encode(['results' => [], 'query' => $q, 'count' => 0]);
    exit;
}

$search_term = '%' . $q . '%';
$results = [];
$limit = 5;

// ROPA - Processing Activities
if ($type === 'all' || $type === 'ropa') {
    $stmt = db_query(
        "SELECT ropa_id AS id, activity_name AS title, status
         FROM processing_activities
         WHERE org_id = ? AND (activity_name LIKE ? OR description LIKE ?)
         ORDER BY created_at DESC LIMIT ?",
        [$org_id, $search_term, $search_term, $limit]
    );
    $rows = db_fetch_all($stmt);
    foreach ($rows as $r) {
        $r['url'] = 'ropa_view.php?id=' . $r['id'];
        $r['icon'] = 'fa-list-alt';
        $r['module'] = 'ROPA';
        $results[] = $r;
    }
}

// DPIA
if ($type === 'all' || $type === 'dpia') {
    $stmt = db_query(
        "SELECT dpia_id AS id, dpia_title AS title, status
         FROM dpia
         WHERE org_id = ? AND (dpia_title LIKE ? OR description LIKE ?)
         ORDER BY created_at DESC LIMIT ?",
        [$org_id, $search_term, $search_term, $limit]
    );
    $rows = db_fetch_all($stmt);
    foreach ($rows as $r) {
        $r['url'] = 'dpia_view.php?id=' . $r['id'];
        $r['icon'] = 'fa-shield';
        $r['module'] = 'DPIA';
        $results[] = $r;
    }
}

// Incidents
if ($type === 'all' || $type === 'incidents') {
    $stmt = db_query(
        "SELECT incident_id AS id, incident_title AS title, status
         FROM incidents
         WHERE org_id = ? AND (incident_title LIKE ? OR description LIKE ? OR incident_ref LIKE ?)
         ORDER BY detected_at DESC LIMIT ?",
        [$org_id, $search_term, $search_term, $search_term, $limit]
    );
    $rows = db_fetch_all($stmt);
    foreach ($rows as $r) {
        $r['url'] = 'incident_view.php?id=' . $r['id'];
        $r['icon'] = 'fa-bolt';
        $r['module'] = 'Incidents';
        $results[] = $r;
    }
}

// DSR Requests
if ($type === 'all' || $type === 'dsr') {
    $stmt = db_query(
        "SELECT dsr_id AS id, subject_name AS title, status
         FROM dsr_requests
         WHERE org_id = ? AND (subject_name LIKE ? OR subject_email LIKE ? OR request_ref LIKE ? OR request_type LIKE ?)
         ORDER BY received_at DESC LIMIT ?",
        [$org_id, $search_term, $search_term, $search_term, $search_term, $limit]
    );
    $rows = db_fetch_all($stmt);
    foreach ($rows as $r) {
        $r['url'] = 'dsr_view.php?id=' . $r['id'];
        $r['icon'] = 'fa-user';
        $r['module'] = 'DSR';
        $results[] = $r;
    }
}

// Vendors
if ($type === 'all' || $type === 'vendors') {
    $stmt = db_query(
        "SELECT vendor_id AS id, vendor_name AS title, status
         FROM vendors
         WHERE org_id = ? AND (vendor_name LIKE ? OR vendor_ref LIKE ? OR vendor_type LIKE ?)
         ORDER BY created_at DESC LIMIT ?",
        [$org_id, $search_term, $search_term, $search_term, $limit]
    );
    $rows = db_fetch_all($stmt);
    foreach ($rows as $r) {
        $r['url'] = 'vendor_view.php?id=' . $r['id'];
        $r['icon'] = 'fa-briefcase';
        $r['module'] = 'Vendors';
        $results[] = $r;
    }
}

// Policies
if ($type === 'all' || $type === 'policies') {
    $stmt = db_query(
        "SELECT policy_id AS id, policy_title AS title, status
         FROM policies
         WHERE org_id = ? AND (policy_title LIKE ? OR description LIKE ?)
         ORDER BY created_at DESC LIMIT ?",
        [$org_id, $search_term, $search_term, $limit]
    );
    $rows = db_fetch_all($stmt);
    foreach ($rows as $r) {
        $r['url'] = 'policy_view.php?id=' . $r['id'];
        $r['icon'] = 'fa-book';
        $r['module'] = 'Policies';
        $results[] = $r;
    }
}

// Documents
if ($type === 'all' || $type === 'documents') {
    $stmt = db_query(
        "SELECT doc_id AS id, doc_name AS title, status
         FROM documents
         WHERE org_id = ? AND (doc_name LIKE ? OR description LIKE ?)
         ORDER BY created_at DESC LIMIT ?",
        [$org_id, $search_term, $search_term, $limit]
    );
    $rows = db_fetch_all($stmt);
    foreach ($rows as $r) {
        $r['url'] = 'documents_view.php?id=' . $r['id'];
        $r['icon'] = 'fa-folder-open';
        $r['module'] = 'Documents';
        $results[] = $r;
    }
}

// Risks
if ($type === 'all' || $type === 'risks') {
    $stmt = db_query(
        "SELECT risk_id AS id, risk_title AS title, status
         FROM risks
         WHERE org_id = ? AND (risk_title LIKE ? OR risk_description LIKE ? OR risk_category LIKE ?)
         ORDER BY created_at DESC LIMIT ?",
        [$org_id, $search_term, $search_term, $search_term, $limit]
    );
    $rows = db_fetch_all($stmt);
    foreach ($rows as $r) {
        $r['url'] = 'risk_view.php?id=' . $r['id'];
        $r['icon'] = 'fa-exclamation-triangle';
        $r['module'] = 'Risks';
        $results[] = $r;
    }
}

// CDPA Compliance Checklist
if ($type === 'all' || $type === 'checklist') {
    require_once dirname(__DIR__) . '/config/checklist_items.php';
    $q_lower = strtolower($q);
    $match_count = 0;
    foreach ($CHECKLIST_ITEMS as $cat_key => $category) {
        if ($match_count >= $limit) break;
        // Search category name and item text
        if (stripos($category['label'], $q) !== false || stripos($category['cdpa_section'], $q) !== false) {
            $results[] = [
                'id' => $cat_key,
                'title' => $category['label'] . ' (' . $category['cdpa_section'] . ')',
                'status' => 'checklist',
                'url' => 'compliance_checklist.php',
                'icon' => 'fa-clipboard-check',
                'module' => 'CDPA Checklist',
            ];
            $match_count++;
            continue;
        }
        foreach ($category['items'] as $item_key => $item_text) {
            if ($match_count >= $limit) break;
            if (stripos($item_text, $q) !== false) {
                $results[] = [
                    'id' => $item_key,
                    'title' => $item_text,
                    'status' => 'checklist',
                    'url' => 'compliance_checklist.php',
                    'icon' => 'fa-clipboard-check',
                    'module' => 'CDPA Checklist',
                ];
                $match_count++;
            }
        }
    }
}

echo json_encode([
    'query' => $q,
    'count' => count($results),
    'results' => $results
]);
