<?php

require_once dirname(__DIR__) . '/includes/session_init.php';
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auragold_require_login.php';
require_once dirname(__DIR__) . '/includes/auragold_mail_settings_schema.php';
require_once dirname(__DIR__) . '/includes/auragold_smtp_mail_send.php';

header('Content-Type: application/json; charset=utf-8');

auragold_require_login_or_exit();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    echo json_encode(['ok' => false, 'message' => 'Invalid method']);
    exit;
}

if (!$conn instanceof mysqli) {
    echo json_encode(['ok' => false, 'message' => 'Database unavailable']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    echo json_encode(['ok' => false, 'message' => 'Invalid JSON']);
    exit;
}

$to = isset($data['to']) ? trim((string) $data['to']) : '';
if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok' => false, 'message' => 'Enter a valid test recipient email.']);
    exit;
}

auragold_ensure_mail_settings_table($conn);
$cfg = auragold_get_mail_settings_row($conn);

$subject = 'GoldMatrix test email — ' . date('d-m-Y H:i:s');
$body = '<p>This is a test email from <strong>GoldMatrix</strong>.</p>'
    . '<p>If you received this, SMTP is working. Time: ' . htmlspecialchars(date('c'), ENT_QUOTES, 'UTF-8') . '</p>';

$result = auragold_smtp_send_message($cfg, $to, $subject, $body, []);
if (!empty($result['ok'])) {
    $result['recipient'] = $to;
}
echo json_encode($result);
