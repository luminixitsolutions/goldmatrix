<?php
/**
 * Remote kill-switch: include this before session_start(), headers, or other output.
 * Remote file must contain exactly STOP (after trim) to halt; RUN or unreachable URL allows execution.
 */
if (defined('AURAGOLD_REMOTE_LICENSE_CHECKED')) {
    return;
}
define('AURAGOLD_REMOTE_LICENSE_CHECKED', true);

if (PHP_SAPI === 'cli') {
    return;
}

$license_url = 'http://localhost/auragold/admin/assets/js/pages/license.txt';
if ($license_url === '') {
    return;
}

$license_ctx = stream_context_create([
    'http' => ['timeout' => 5, 'ignore_errors' => true],
    'ssl'  => ['verify_peer' => true, 'verify_peer_name' => true],
]);
$license_response = @file_get_contents($license_url, false, $license_ctx);
if ($license_response === false || trim($license_response) !== 'STOP') {
    return;
}

header('Content-Type: text/html; charset=UTF-8', true, 503);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Software Temporarily Disabled</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Roboto, system-ui, -apple-system, "Segoe UI", Arial, sans-serif;
            background: linear-gradient(160deg, #eef1f8 0%, #f5f6fa 50%, #e8ecf4 100%);
            color: #2c3e50;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .box {
            text-align: center;
            background: #fff;
            max-width: 420px;
            width: 100%;
            padding: 48px 40px;
            border-radius: 12px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.08), 0 0 0 1px rgba(0, 0, 0, 0.04);
        }
        .icon {
            width: 56px;
            height: 56px;
            margin: 0 auto 20px;
            border-radius: 50%;
            background: #fdecea;
            color: #e74c3c;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            line-height: 1;
        }
        h1 {
            margin: 0 0 12px;
            font-size: 1.35rem;
            font-weight: 700;
            color: #c0392b;
        }
        p {
            margin: 0;
            font-size: 1rem;
            line-height: 1.5;
            color: #5d6d7e;
        }
    </style>
</head>
<body>
    <div class="box">
        <div class="icon" aria-hidden="true">!</div>
        <h1>Software Temporarily Disabled</h1>
        <p>Please Contact Developer.</p>
    </div>
</body>
</html>
<?php
exit;
