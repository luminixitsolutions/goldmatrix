<?php

/**
 * Send mail using Mail Setting (tbl_auragold_mail_settings).
 * Prefers PHPMailer when installed (composer); falls back to a minimal SMTP client.
 *
 * @param array<string, mixed> $cfg Keys: smtp_host, smtp_port, smtp_encryption, smtp_username, smtp_password, from_name, from_email
 * @param array<int, array{filename:string, mime:string, data:string}> $attachments Binary strings
 * @return array{ok:bool, message:string}
 */
function auragold_smtp_send_message(array $cfg, string $to, string $subject, string $htmlBody, array $attachments = []): array
{
    $host = trim((string) ($cfg['smtp_host'] ?? ''));
    $port = (int) ($cfg['smtp_port'] ?? 465);
    $enc = strtolower(trim((string) ($cfg['smtp_encryption'] ?? 'ssl')));
    $user = (string) ($cfg['smtp_username'] ?? '');
    $pass = (string) ($cfg['smtp_password'] ?? '');
    $fromEmail = trim((string) ($cfg['from_email'] ?? ''));
    $fromName = trim((string) ($cfg['from_name'] ?? ''));

    if ($host === '' || $port < 1 || $port > 65535) {
        return ['ok' => false, 'message' => 'SMTP host or port is not configured.'];
    }
    if ($user === '' || $pass === '') {
        return ['ok' => false, 'message' => 'SMTP username or password is missing in Mail Setting.'];
    }
    if ($fromEmail === '') {
        return ['ok' => false, 'message' => 'From email is not set in Mail Setting.'];
    }
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'message' => 'Invalid recipient email address.'];
    }

    $envelopeFrom = filter_var($user, FILTER_VALIDATE_EMAIL) ? $user : $fromEmail;
    if (!filter_var($envelopeFrom, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'message' => 'SMTP username must be a valid email address (used as envelope sender), or set From email to a valid address.'];
    }

    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    if (is_file($autoload)) {
        require_once $autoload;
    }
    if (class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
        return auragold_smtp_send_message_phpmailer(
            $host,
            $port,
            $enc,
            $user,
            $pass,
            $envelopeFrom,
            $fromName,
            $fromEmail,
            $to,
            $subject,
            $htmlBody,
            $attachments
        );
    }

    return auragold_smtp_send_message_native(
        $host,
        $port,
        $enc,
        $user,
        $pass,
        $envelopeFrom,
        $fromName,
        $fromEmail,
        $to,
        $subject,
        $htmlBody,
        $attachments
    );
}

/**
 * @param array<int, array{filename:string, mime:string, data:string}> $attachments
 * @return array{ok:bool, message:string}
 */
function auragold_smtp_send_message_phpmailer(
    string $host,
    int $port,
    string $enc,
    string $user,
    string $pass,
    string $envelopeFrom,
    string $fromName,
    string $fromEmail,
    string $to,
    string $subject,
    string $htmlBody,
    array $attachments
): array {
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->Port = $port;
        $mail->SMTPAuth = true;
        $mail->Username = $user;
        $mail->Password = $pass;
        $mail->Timeout = 90;
        $mail->CharSet = \PHPMailer\PHPMailer\PHPMailer::CHARSET_UTF8;
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ],
        ];

        if ($enc === 'none') {
            $mail->SMTPAutoTLS = false;
            $mail->SMTPSecure = '';
        } elseif ($port === 465 || $enc === 'ssl') {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mail->setFrom($envelopeFrom, $fromName !== '' ? $fromName : '');
        $mail->addAddress($to);
        if (filter_var($fromEmail, FILTER_VALIDATE_EMAIL) && strcasecmp($envelopeFrom, $fromEmail) !== 0) {
            $mail->addReplyTo($fromEmail, $fromName !== '' ? $fromName : '');
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $plain = @trim(strip_tags(preg_replace('/\s+/', ' ', $htmlBody)));
        $mail->AltBody = $plain !== '' ? $plain : ' ';

        foreach ($attachments as $att) {
            $fn = preg_replace('/[\r\n]+/', '', (string) ($att['filename'] ?? 'attachment.bin'));
            $mimeType = preg_replace('/[\r\n]+/', '', (string) ($att['mime'] ?? 'application/octet-stream'));
            $data = (string) ($att['data'] ?? '');
            if ($fn !== '' && $data !== '') {
                $mail->addStringAttachment($data, $fn, \PHPMailer\PHPMailer\PHPMailer::ENCODING_BASE64, $mimeType);
            }
        }

        $mail->send();

        return [
            'ok'      => true,
            'message' => 'Message sent via SMTP. If the recipient does not see it, ask them to check Spam/Junk and Promotions (Gmail).',
        ];
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        $detail = $mail->ErrorInfo !== '' ? $mail->ErrorInfo : $e->getMessage();

        return ['ok' => false, 'message' => 'SMTP error: ' . $detail];
    } catch (\Throwable $e) {
        return ['ok' => false, 'message' => 'Mail error: ' . $e->getMessage()];
    }
}

/**
 * @param array<int, array{filename:string, mime:string, data:string}> $attachments
 * @return array{ok:bool, message:string}
 */
function auragold_smtp_send_message_native(
    string $host,
    int $port,
    string $enc,
    string $user,
    string $pass,
    string $envelopeFrom,
    string $fromName,
    string $fromEmail,
    string $to,
    string $subject,
    string $htmlBody,
    array $attachments
): array {
    $ehloHost = 'localhost';
    if (preg_match('/@([a-zA-Z0-9][a-zA-Z0-9.-]*[a-zA-Z0-9])$/', $envelopeFrom, $m)) {
        $ehloHost = $m[1];
    } elseif (function_exists('gethostname')) {
        $hn = (string) gethostname();
        if ($hn !== '' && $hn !== 'localhost') {
            $ehloHost = preg_replace('/[^a-zA-Z0-9.-]+/', '-', $hn);
        }
    }

    $ctx = stream_context_create([
        'ssl' => [
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true,
        ],
    ]);

    $useImplicitSsl = ($enc === 'ssl') || ($enc === 'tls' && $port === 465);
    $remote = $useImplicitSsl ? 'ssl://' . $host . ':' . $port : 'tcp://' . $host . ':' . $port;

    $errno = 0;
    $errstr = '';
    $socket = @stream_socket_client($remote, $errno, $errstr, 60, STREAM_CLIENT_CONNECT, $ctx);
    if (!$socket) {
        return ['ok' => false, 'message' => $errstr !== '' ? $errstr : 'Could not connect to mail server (' . $errno . ').'];
    }
    stream_set_timeout($socket, 120);

    $read = static function ($s): string {
        $data = '';
        while (($line = @fgets($s, 8192)) !== false) {
            if ($line === '') {
                break;
            }
            $data .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') {
                break;
            }
        }
        if ($data === '') {
            $meta = @stream_get_meta_data($s);
            if (!empty($meta['timed_out'])) {
                return '000 SMTP read timeout';
            }
        }

        return $data;
    };

    $expect = static function ($s, array $codes, string $ctxLabel) use ($read): ?string {
        $resp = $read($s);
        if ($resp === '' || strlen($resp) < 3) {
            return $ctxLabel . ': empty or closed connection (check firewall, port, and SSL/TLS mode).';
        }
        $code = (int) substr($resp, 0, 3);
        if (!in_array($code, $codes, true)) {
            return $ctxLabel . ': ' . trim(preg_replace("/\r\n?|\n/", ' ', $resp));
        }

        return null;
    };

    $write = static function ($s, string $line): void {
        fwrite($s, $line);
        fflush($s);
    };

    if (($e = $expect($socket, [220], 'Banner')) !== null) {
        fclose($socket);

        return ['ok' => false, 'message' => $e];
    }

    $write($socket, 'EHLO ' . $ehloHost . "\r\n");
    if (($e = $expect($socket, [250], 'EHLO')) !== null) {
        fclose($socket);

        return ['ok' => false, 'message' => $e];
    }

    if ($enc === 'tls' && !$useImplicitSsl) {
        $write($socket, "STARTTLS\r\n");
        if (($e = $expect($socket, [220], 'STARTTLS')) !== null) {
            fclose($socket);

            return ['ok' => false, 'message' => $e];
        }
        $cryptoMethod = STREAM_CRYPTO_METHOD_TLS_CLIENT;
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
            $cryptoMethod = (int) constant('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT');
        }
        $cryptoOk = @stream_socket_enable_crypto($socket, true, $cryptoMethod);
        if (!$cryptoOk) {
            fclose($socket);

            return ['ok' => false, 'message' => 'TLS negotiation failed.'];
        }
        $write($socket, 'EHLO ' . $ehloHost . "\r\n");
        if (($e = $expect($socket, [250], 'EHLO after TLS')) !== null) {
            fclose($socket);

            return ['ok' => false, 'message' => $e];
        }
    }

    $write($socket, "AUTH LOGIN\r\n");
    if (($e = $expect($socket, [334], 'AUTH LOGIN')) !== null) {
        fclose($socket);

        return ['ok' => false, 'message' => $e];
    }
    $write($socket, base64_encode($user) . "\r\n");
    if (($e = $expect($socket, [334], 'AUTH user')) !== null) {
        fclose($socket);

        return ['ok' => false, 'message' => $e];
    }
    $write($socket, base64_encode($pass) . "\r\n");
    if (($e = $expect($socket, [235], 'AUTH pass')) !== null) {
        fclose($socket);

        return ['ok' => false, 'message' => $e];
    }

    $write($socket, 'MAIL FROM:<' . $envelopeFrom . ">\r\n");
    if (($e = $expect($socket, [250], 'MAIL FROM')) !== null) {
        fclose($socket);

        return ['ok' => false, 'message' => $e];
    }
    $write($socket, 'RCPT TO:<' . $to . ">\r\n");
    if (($e = $expect($socket, [250, 251, 252], 'RCPT TO')) !== null) {
        fclose($socket);

        return ['ok' => false, 'message' => $e];
    }
    $write($socket, "DATA\r\n");
    if (($e = $expect($socket, [354], 'DATA')) !== null) {
        fclose($socket);

        return ['ok' => false, 'message' => $e];
    }

    $boundary = 'bnd_' . bin2hex(random_bytes(12));
    $subjEnc = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $fromHdr = $fromName !== ''
        ? ('=?UTF-8?B?' . base64_encode($fromName) . '?= <' . $envelopeFrom . '>')
        : $envelopeFrom;

    $msgDomain = 'localhost';
    if (preg_match('/@([a-zA-Z0-9][a-zA-Z0-9.-]*[a-zA-Z0-9])$/', $envelopeFrom, $md)) {
        $msgDomain = $md[1];
    }
    $messageId = '<' . bin2hex(random_bytes(16)) . '@' . $msgDomain . '>';

    $headers = [];
    $headers[] = 'Date: ' . gmdate('D, d M Y H:i:s') . ' +0000';
    $headers[] = 'Message-ID: ' . $messageId;
    $headers[] = 'From: ' . $fromHdr;
    $headers[] = 'To: <' . $to . '>';
    if (filter_var($fromEmail, FILTER_VALIDATE_EMAIL) && strcasecmp($envelopeFrom, $fromEmail) !== 0) {
        $headers[] = 'Reply-To: ' . ($fromName !== ''
            ? ('=?UTF-8?B?' . base64_encode($fromName) . '?= <' . $fromEmail . '>')
            : $fromEmail);
    }
    $headers[] = 'Subject: ' . $subjEnc;
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';

    $mime = implode("\r\n", $headers) . "\r\n\r\n";
    $mime .= 'This is a multi-part message in MIME format.' . "\r\n\r\n";

    $mime .= '--' . $boundary . "\r\n";
    $mime .= "Content-Type: text/html; charset=UTF-8\r\n";
    $mime .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $mime .= chunk_split(base64_encode($htmlBody)) . "\r\n";

    foreach ($attachments as $att) {
        $fn = preg_replace('/[\r\n]+/', '', (string) ($att['filename'] ?? 'attachment.bin'));
        $mimeType = preg_replace('/[\r\n]+/', '', (string) ($att['mime'] ?? 'application/octet-stream'));
        $data = (string) ($att['data'] ?? '');
        $mime .= '--' . $boundary . "\r\n";
        $mime .= 'Content-Type: ' . $mimeType . '; name="' . $fn . "\"\r\n";
        $mime .= "Content-Transfer-Encoding: base64\r\n";
        $mime .= 'Content-Disposition: attachment; filename="' . $fn . "\"\r\n\r\n";
        $mime .= chunk_split(base64_encode($data)) . "\r\n";
    }
    $mime .= '--' . $boundary . "--\r\n";
    $mime = preg_replace('/^\./m', '..', $mime);
    $mime .= "\r\n.\r\n";

    /** Chunk DATA to avoid rare server buffer limits */
    $chunkSize = 16384;
    $len = strlen($mime);
    for ($o = 0; $o < $len; $o += $chunkSize) {
        $written = @fwrite($socket, substr($mime, $o, $chunkSize));
        if ($written === false || $written === 0) {
            fclose($socket);

            return ['ok' => false, 'message' => 'SMTP: failed while sending message body.'];
        }
    }
    fflush($socket);

    if (($e = $expect($socket, [250], 'Message body')) !== null) {
        fclose($socket);

        return ['ok' => false, 'message' => $e];
    }
    $write($socket, "QUIT\r\n");
    fclose($socket);

    return [
        'ok'      => true,
        'message' => 'Message accepted by the mail server (native SMTP). If the recipient does not see it, ask them to check Spam/Junk.',
    ];
}
