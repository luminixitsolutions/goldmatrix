<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/jewelry_catalogue_create_include.php';

header('Content-Type: application/json; charset=utf-8');

$authed = (isset($_SESSION['Admin']['id']) && (int) $_SESSION['Admin']['id'] > 0)
    || (isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] > 0);
if (!$authed) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

$designNo = isset($_GET['design_no']) ? trim((string) $_GET['design_no']) : '';
$catalogueId = isset($_GET['catalogue_id']) ? (int) $_GET['catalogue_id'] : 0;
if ($designNo === '' && isset($_GET['id'])) {
    $catalogueId = (int) $_GET['id'];
}

if ($designNo === '' && $catalogueId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Design No. is required.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$result = auragold_jewelry_catalogue_get_for_modal($conn, $designNo, $catalogueId);
if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Catalogue not found for this Design No.'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(array_merge(['success' => true], $result), JSON_UNESCAPED_UNICODE);
