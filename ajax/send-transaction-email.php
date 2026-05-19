<?php

function auragold_tr_send_mail_meta_sql(string $type, int $id): string
{
    $id = (int) $id;
    switch ($type) {
        case 'sale_order':
            return 'SELECT order_no AS doc_no, customer_name AS party FROM tbl_sale_orders WHERE id = ' . $id . ' LIMIT 1';
        case 'sale_return':
            return 'SELECT return_no AS doc_no, customer_name AS party FROM tbl_sale_returns WHERE id = ' . $id . ' LIMIT 1';
        case 'sale_quotation':
            return 'SELECT quotation_no AS doc_no, customer_name AS party FROM tbl_sale_quotations WHERE id = ' . $id . ' LIMIT 1';
        case 'purchase_invoice':
            return 'SELECT invoice_no AS doc_no, supplier_name AS party FROM tbl_purchase_invoices WHERE id = ' . $id . ' LIMIT 1';
        case 'purchase_return':
            return 'SELECT return_no AS doc_no, supplier_name AS party FROM tbl_purchase_returns WHERE id = ' . $id . ' LIMIT 1';
        case 'purchase_quotation':
            return 'SELECT quotation_no AS doc_no, supplier_name AS party FROM tbl_purchase_quotations WHERE id = ' . $id . ' LIMIT 1';
        default:
            return 'SELECT "" AS doc_no, "" AS party FROM tbl_sale_invoices WHERE 1=0 LIMIT 1';
    }
}

function auragold_tr_send_mail_print_path(string $type, int $id): string
{
    $id = (int) $id;
    switch ($type) {
        case 'sale_order':
            return '/sale-order-print.php?id=' . $id;
        case 'sale_return':
            return '/sale-return.php?id=' . $id . '&print=1';
        case 'sale_quotation':
            return '/sale-quotations.php?id=' . $id . '&print=1';
        case 'purchase_invoice':
            return '/purchase-invoice-print.php?id=' . $id;
        case 'purchase_return':
            return '/purchase-return.php?id=' . $id . '&print=1';
        case 'purchase_quotation':
            return '/purchase-quotation.php?id=' . $id . '&print=1';
        default:
            return '/';
    }
}

require_once dirname(__DIR__) . '/includes/session_init.php';
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auragold_require_login.php';
require_once dirname(__DIR__) . '/includes/auragold_mail_settings_schema.php';
require_once dirname(__DIR__) . '/includes/auragold_smtp_mail_send.php';
require_once dirname(__DIR__) . '/includes/auragold_transaction_report_party_email.php';

header('Content-Type: application/json; charset=utf-8');

auragold_require_login_or_exit();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    echo json_encode(['ok' => false, 'message' => 'Invalid method']);
    exit;
}

if (!$conn instanceof mysqli) {
    echo json_encode(['ok' => false, 'message' => 'Database connection failed.']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    echo json_encode(['ok' => false, 'message' => 'Invalid JSON']);
    exit;
}

$type = isset($data['type']) ? strtolower(trim((string) $data['type'])) : '';
$id = isset($data['id']) ? (int) $data['id'] : 0;

$allowed = [
    'sale_invoice', 'sale_order', 'sale_return', 'sale_quotation',
    'purchase_invoice', 'purchase_return', 'purchase_quotation',
];
if ($id <= 0 || !in_array($type, $allowed, true)) {
    echo json_encode(['ok' => false, 'message' => 'Invalid transaction.']);
    exit;
}

$to = auragold_transaction_report_party_email($conn, $type, $id);
if ($to === '') {
    echo json_encode(['ok' => false, 'message' => 'Customer email not found']);
    exit;
}

auragold_ensure_mail_settings_table($conn);
$cfg = auragold_get_mail_settings_row($conn);
if (trim((string) ($cfg['smtp_host'] ?? '')) === '') {
    echo json_encode(['ok' => false, 'message' => 'Mail is not configured. Open Settings → Mail Setting and save SMTP details.']);
    exit;
}

$company = defined('COMPANY_NAME') ? (string) COMPANY_NAME : 'Gold Matrix';
$attachments = [];
$subject = 'Document #' . $id;
$htmlBody = '';

if ($type === 'sale_invoice') {
    $rowMeta = @getRecord('SELECT invoice_no AS doc_no, customer_name AS party FROM tbl_sale_invoices WHERE id = ' . $id . ' LIMIT 1');
    $invNo = is_array($rowMeta) ? trim((string) ($rowMeta['doc_no'] ?? '')) : '';
    $custName = is_array($rowMeta) ? trim((string) ($rowMeta['party'] ?? '')) : '';
    $subject = ($invNo !== '' ? 'Sale Invoice ' . $invNo : 'Sale Invoice') . ' — ' . $company;

    $GLOBALS['AURAGOLD_SALE_INVOICE_MAIL_CAPTURE'] = true;
    $GLOBALS['auragold_sale_invoice_mail_html'] = '';
    $_GET['id'] = (string) $id;
    if (!isset($_GET['lang'])) {
        $_GET['lang'] = 'en';
    }
    include dirname(__DIR__) . '/sale-invoice-print.php';
    $htmlInvoice = (string) ($GLOBALS['auragold_sale_invoice_mail_html'] ?? '');
    unset($GLOBALS['AURAGOLD_SALE_INVOICE_MAIL_CAPTURE'], $GLOBALS['auragold_sale_invoice_mail_html']);

    if ($htmlInvoice === '') {
        echo json_encode(['ok' => false, 'message' => 'Could not build invoice HTML for attachment.']);
        exit;
    }

    $pdfBinary = null;
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    if (is_file($autoload)) {
        require_once $autoload;
    }
    if (class_exists('\Dompdf\Dompdf') && class_exists('\Dompdf\Options')) {
        try {
            $root = realpath(dirname(__DIR__));
            if ($root !== false) {
                $options = new \Dompdf\Options();
                $options->set('isRemoteEnabled', true);
                $options->setChroot($root);
                $dompdf = new \Dompdf\Dompdf($options);
                $baseUri = 'file:///' . str_replace(DIRECTORY_SEPARATOR, '/', $root) . '/';
                if (stripos($htmlInvoice, '<head') !== false) {
                    $htmlInvoice = preg_replace('/<head([^>]*)>/i', '<head$1><base href="' . htmlspecialchars($baseUri, ENT_QUOTES, 'UTF-8') . '">', $htmlInvoice, 1);
                }
                $dompdf->loadHtml($htmlInvoice, 'UTF-8');
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();
                $pdfBinary = $dompdf->output();
            }
        } catch (Throwable $e) {
            $pdfBinary = null;
        }
    }

    $safeFile = preg_replace('/[^A-Za-z0-9._-]+/', '_', $invNo !== '' ? $invNo : 'invoice') . '.pdf';
    if (is_string($pdfBinary) && $pdfBinary !== '') {
        $attachments[] = [
            'filename' => $safeFile,
            'mime'     => 'application/pdf',
            'data'     => $pdfBinary,
        ];
    } else {
        $attachments[] = [
            'filename' => preg_replace('/\.pdf$/i', '', $safeFile) . '-invoice.html',
            'mime'     => 'text/html',
            'data'     => $htmlInvoice,
        ];
    }

    $htmlBody = '<p>Dear ' . htmlspecialchars($custName !== '' ? $custName : 'Customer', ENT_QUOTES, 'UTF-8') . ',</p>'
        . '<p>Please find attached a copy of your <strong>Sale Invoice' . ($invNo !== '' ? ' ' . htmlspecialchars($invNo, ENT_QUOTES, 'UTF-8') : '') . '</strong>.</p>'
        . '<p>Thank you for your business.</p>'
        . '<p>— ' . htmlspecialchars($company, ENT_QUOTES, 'UTF-8') . '</p>';
} else {
    $doc = @getRecord(auragold_tr_send_mail_meta_sql($type, $id));
    $docNo = is_array($doc) ? trim((string) ($doc['doc_no'] ?? '')) : '';
    $party = is_array($doc) ? trim((string) ($doc['party'] ?? '')) : '';
    $subject = str_replace('_', ' ', ucwords($type, '_')) . ($docNo !== '' ? ' — ' . $docNo : '') . ' — ' . $company;
    $printPath = auragold_tr_send_mail_print_path($type, $id);
    $printUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
        . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
        . $printPath;
    $htmlBody = '<p>Dear ' . htmlspecialchars($party !== '' ? $party : 'Customer', ENT_QUOTES, 'UTF-8') . ',</p>'
        . '<p>Please refer to your <strong>' . htmlspecialchars(str_replace('_', ' ', $type), ENT_QUOTES, 'UTF-8') . '</strong>'
        . ($docNo !== '' ? ' <strong>' . htmlspecialchars($docNo, ENT_QUOTES, 'UTF-8') . '</strong>' : '') . '.</p>'
        . '<p>You can open or print the voucher from:<br><a href="' . htmlspecialchars($printUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($printUrl, ENT_QUOTES, 'UTF-8') . '</a></p>'
        . '<p>— ' . htmlspecialchars($company, ENT_QUOTES, 'UTF-8') . '</p>';
}

$result = auragold_smtp_send_message($cfg, $to, $subject, $htmlBody, $attachments);
if (!empty($result['ok'])) {
    $result['recipient'] = $to;
}
echo json_encode($result);
