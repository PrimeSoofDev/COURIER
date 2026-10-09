<?php
/**
 * GaaTiTrack – Mail & Mailtrap (SMTP) Helper
 *
 * Supports native SMTP with AUTH LOGIN / STARTTLS (Mailtrap, Sendgrid, Gmail, etc.)
 * Configurable via Admin System Settings or .env file.
 */

if (!defined('GAATITRACK_CONFIG_LOADED')) {
    require_once __DIR__ . '/../config.php';
}

/**
 * Fetch current email & SMTP configuration.
 * Prioritizes database `system_settings` table, falls back to .env / defaults.
 */
function get_mail_config(): array {
    $config = [
        'driver'       => env('MAIL_DRIVER', 'smtp'),
        'host'         => env('MAIL_SMTP_HOST', 'sandbox.smtp.mailtrap.io'),
        'port'         => (int)env('MAIL_SMTP_PORT', 2525),
        'user'         => env('MAIL_SMTP_USER', ''),
        'pass'         => env('MAIL_SMTP_PASS', ''),
        'security'     => env('MAIL_SMTP_SECURITY', 'tls'),
        'from_address' => env('MAIL_FROM_ADDRESS', 'no-reply@gaatitrack.com'),
        'from_name'    => env('MAIL_FROM_NAME', 'GaaTiTrack Logistics'),
    ];

    try {
        $row = null;
        if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
            $stmt = $GLOBALS['pdo']->query("SELECT smtp_host, smtp_port, smtp_user, smtp_pass, smtp_security, mail_from_address, mail_from_name, mail_driver FROM system_settings WHERE id = 1 LIMIT 1");
            $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        } elseif (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli) {
            $res = $GLOBALS['conn']->query("SELECT smtp_host, smtp_port, smtp_user, smtp_pass, smtp_security, mail_from_address, mail_from_name, mail_driver FROM system_settings WHERE id = 1 LIMIT 1");
            $row = $res ? $res->fetch_assoc() : null;
        }

        if (!empty($row)) {
            if (!empty($row['smtp_host']))         $config['host']         = trim($row['smtp_host']);
            if (!empty($row['smtp_port']))         $config['port']         = (int)$row['smtp_port'];
            if (isset($row['smtp_user']))          $config['user']         = trim($row['smtp_user']);
            if (isset($row['smtp_pass']))          $config['pass']         = trim($row['smtp_pass']);
            if (!empty($row['smtp_security']))     $config['security']     = trim($row['smtp_security']);
            if (!empty($row['mail_from_address'])) $config['from_address'] = trim($row['mail_from_address']);
            if (!empty($row['mail_from_name']))    $config['from_name']    = trim($row['mail_from_name']);
            if (!empty($row['mail_driver']))       $config['driver']       = trim($row['mail_driver']);
        }
    } catch (\Throwable $e) {
        // Fall back to environment variables
    }

    return $config;
}

/**
 * Reads response line(s) from an SMTP socket.
 */
function smtp_get_response($socket): array {
    $data = '';
    while (!feof($socket)) {
        $str = fgets($socket, 512);
        if ($str === false) break;
        $data .= $str;
        // Format of multiline reply is "XYZ-message\r\n", final line is "XYZ message\r\n"
        if (isset($str[3]) && $str[3] === ' ') {
            break;
        }
    }
    $code = (int)substr($data, 0, 3);
    return ['code' => $code, 'text' => trim($data)];
}

/**
 * Send an email directly via SMTP socket with AUTH LOGIN and TLS/STARTTLS support.
 */
function send_smtp_socket(string $to_email, string $to_name, string $subject, string $html_body, array $cfg = null): array {
    $cfg = $cfg ?: get_mail_config();

    $host     = $cfg['host'] ?: 'sandbox.smtp.mailtrap.io';
    $port     = $cfg['port'] ?: 2525;
    $user     = $cfg['user'] ?? '';
    $pass     = $cfg['pass'] ?? '';
    $security = strtolower($cfg['security'] ?? 'tls');
    $fromMail = $cfg['from_address'] ?: 'no-reply@gaatitrack.com';
    $fromName = $cfg['from_name'] ?: 'GaaTiTrack Logistics';

    $isSSL = ($security === 'ssl' || $port === 465);
    $target = ($isSSL ? 'ssl://' : 'tcp://') . $host . ':' . $port;

    $context = stream_context_create([
        'ssl' => [
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true,
        ]
    ]);

    $socket = @stream_socket_client($target, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);
    if (!$socket) {
        $msg = "SMTP connect error to {$target}: {$errstr} ({$errno})";
        app_log($msg, 'ERROR');
        return ['success' => false, 'message' => $msg];
    }

    stream_set_timeout($socket, 15);

    // 1. Initial Greeting
    $res = smtp_get_response($socket);
    if ($res['code'] !== 220) {
        fclose($socket);
        return ['success' => false, 'message' => "SMTP greeting failed: " . $res['text']];
    }

    // 2. EHLO
    fputs($socket, "EHLO localhost\r\n");
    $res = smtp_get_response($socket);
    if ($res['code'] !== 250) {
        fputs($socket, "HELO localhost\r\n");
        $res = smtp_get_response($socket);
    }

    // 3. STARTTLS if configured and not already SSL
    if (!$isSSL && ($security === 'tls' || in_array($port, [2525, 587, 25]))) {
        fputs($socket, "STARTTLS\r\n");
        $res = smtp_get_response($socket);
        if ($res['code'] === 220) {
            $crypto = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if (!$crypto) {
                // If TLS negotiation fails, close
                fclose($socket);
                return ['success' => false, 'message' => "STARTTLS cryptographic handshake failed."];
            }
            // Send EHLO again after TLS
            fputs($socket, "EHLO localhost\r\n");
            $res = smtp_get_response($socket);
        }
    }

    // 4. Authentication (AUTH LOGIN) if username provided
    if (!empty($user)) {
        fputs($socket, "AUTH LOGIN\r\n");
        $res = smtp_get_response($socket);
        if ($res['code'] !== 334) {
            fclose($socket);
            return ['success' => false, 'message' => "SMTP AUTH LOGIN rejected: " . $res['text']];
        }

        fputs($socket, base64_encode($user) . "\r\n");
        $res = smtp_get_response($socket);
        if ($res['code'] !== 334) {
            fclose($socket);
            return ['success' => false, 'message' => "SMTP Username rejected: " . $res['text']];
        }

        fputs($socket, base64_encode($pass) . "\r\n");
        $res = smtp_get_response($socket);
        if ($res['code'] !== 235) {
            fclose($socket);
            return ['success' => false, 'message' => "SMTP Password authentication failed: " . $res['text']];
        }
    }

    // 5. MAIL FROM
    fputs($socket, "MAIL FROM: <{$fromMail}>\r\n");
    $res = smtp_get_response($socket);
    if ($res['code'] !== 250) {
        fclose($socket);
        return ['success' => false, 'message' => "MAIL FROM rejected: " . $res['text']];
    }

    // 6. RCPT TO
    fputs($socket, "RCPT TO: <{$to_email}>\r\n");
    $res = smtp_get_response($socket);
    if ($res['code'] !== 250 && $res['code'] !== 251) {
        fclose($socket);
        return ['success' => false, 'message' => "RCPT TO rejected ({$to_email}): " . $res['text']];
    }

    // 7. DATA
    fputs($socket, "DATA\r\n");
    $res = smtp_get_response($socket);
    if ($res['code'] !== 354) {
        fclose($socket);
        return ['success' => false, 'message' => "DATA start rejected: " . $res['text']];
    }

    // 8. Headers & Body
    $messageId = sprintf("<%s.%s@%s>", time(), bin2hex(random_bytes(6)), parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST) ?: 'gaatitrack.local');
    $date      = date('r');
    $toHeader  = empty($to_name) ? "<{$to_email}>" : '"' . addcslashes($to_name, '"\\') . '" <' . $to_email . '>';
    $fromHead  = empty($fromName) ? "<{$fromMail}>" : '"' . addcslashes($fromName, '"\\') . '" <' . $fromMail . '>';

    $msg  = "Date: {$date}\r\n";
    $msg .= "Message-ID: {$messageId}\r\n";
    $msg .= "From: {$fromHead}\r\n";
    $msg .= "Reply-To: {$fromHead}\r\n";
    $msg .= "To: {$toHeader}\r\n";
    $msg .= "Subject: {$subject}\r\n";
    $msg .= "MIME-Version: 1.0\r\n";
    $msg .= "Content-Type: text/html; charset=UTF-8\r\n";
    $msg .= "Content-Transfer-Encoding: 8bit\r\n";
    $msg .= "X-Mailer: GaaTiTrack Mailtrap-SMTP\r\n\r\n";
    $msg .= $html_body . "\r\n.\r\n";

    fputs($socket, $msg);
    $res = smtp_get_response($socket);

    // 9. QUIT
    fputs($socket, "QUIT\r\n");
    fclose($socket);

    if ($res['code'] === 250) {
        app_log("Email dispatched successfully to {$to_email} via SMTP ({$host})");
        return ['success' => true, 'message' => 'Email sent successfully via Mailtrap/SMTP.'];
    }

    return ['success' => false, 'message' => "SMTP body delivery failed: " . $res['text']];
}

/**
 * Universal email dispatcher (tries SMTP socket first, falls back to PHP mail() if driver='mail').
 */
function send_email(string $to_email, string $to_name, string $subject, string $html_body): bool {
    $cfg = get_mail_config();

    if ($cfg['driver'] === 'smtp' || !empty($cfg['user'])) {
        $res = send_smtp_socket($to_email, $to_name, $subject, $html_body, $cfg);
        if ($res['success']) return true;
        app_log("SMTP failed for {$to_email}: " . $res['message'] . ". Trying fallback...", 'WARNING');
    }

    // Fallback to PHP mail()
    $from_address = $cfg['from_address'];
    $from_name    = $cfg['from_name'];
    $to_header    = empty($to_name) ? $to_email : '"' . addslashes($to_name) . '" <' . $to_email . '>';
    $from_header  = '"' . addslashes($from_name) . '" <' . $from_address . '>';

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: $from_header\r\n";
    $headers .= "Reply-To: $from_header\r\n";
    $headers .= "X-Mailer: GaaTiTrack-PHP\r\n";

    $result = @mail($to_header, $subject, $html_body, $headers);
    app_log("Fallback mail() to $to_email – " . ($result ? 'OK' : 'FAILED'), $result ? 'INFO' : 'ERROR');
    return (bool)$result;
}

/**
 * Build modern branded HTML email body for parcel confirmation.
 */
function build_parcel_email_html(array $parcel, string $role): string {
    $app_name   = defined('APP_NAME') ? APP_NAME : 'GaaTiTrack';
    $app_url    = defined('APP_URL')  ? APP_URL  : 'http://localhost/courer';
    $ref        = htmlspecialchars($parcel['reference_number'] ?? 'N/A');
    $s_name     = htmlspecialchars(ucwords($parcel['sender_name']    ?? ''));
    $r_name     = htmlspecialchars(ucwords($parcel['recipient_name'] ?? ''));
    $s_address  = htmlspecialchars($parcel['sender_address']    ?? '');
    $r_address  = htmlspecialchars($parcel['recipient_address'] ?? '');
    $s_contact  = htmlspecialchars($parcel['sender_contact']    ?? 'N/A');
    $r_contact  = htmlspecialchars($parcel['recipient_contact'] ?? 'N/A');
    $price      = number_format((float)($parcel['price'] ?? 0), 2);
    $greeting   = $role === 'sender' ? "Dear $s_name" : "Dear $r_name";
    $track_url  = $app_url . '/tracking.php?ref=' . urlencode($ref);

    if ($role === 'sender') {
        $intro = "Your parcel has been <strong>successfully booked</strong> with $app_name. Below are your shipment and tracking details.";
    } else {
        $intro = "A parcel has been sent to you via <strong>$app_name</strong>. Use the tracking number below to follow its delivery progress.";
    }

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Consignment Confirmation – {$app_name}</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:30px 10px;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 16px rgba(0,0,0,0.06);max-width:100%;">

          <!-- Top Brand Banner -->
          <tr>
            <td style="background:linear-gradient(135deg,#1e3a8a,#2563eb);padding:32px 30px;text-align:center;">
              <div style="font-size:28px;line-height:1;margin-bottom:8px;">📦</div>
              <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:800;letter-spacing:0.5px;">{$app_name}</h1>
              <p style="margin:6px 0 0;color:#bfdbfe;font-size:13px;font-weight:500;">Consignment Booking Confirmation</p>
            </td>
          </tr>

          <!-- Main Content -->
          <tr>
            <td style="padding:32px 30px;">
              <p style="margin:0 0 12px;font-size:16px;color:#1e293b;font-weight:600;">{$greeting},</p>
              <p style="margin:0 0 24px;font-size:14px;color:#475569;line-height:1.6;">{$intro}</p>

              <!-- Reference Badge -->
              <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                <tr>
                  <td style="background:#eff6ff;border:1.5px solid #bfdbfe;border-left:5px solid #2563eb;padding:16px 20px;border-radius:8px;">
                    <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:1px;">Tracking / Reference Number</div>
                    <div style="font-size:24px;font-weight:800;color:#1e3a8a;letter-spacing:2px;margin-top:4px;font-family:monospace;">{$ref}</div>
                  </td>
                </tr>
              </table>

              <!-- Shipment Table -->
              <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;margin-bottom:24px;">
                <tr style="background:#f8fafc;">
                  <td colspan="2" style="padding:12px 16px;font-weight:700;font-size:13px;color:#1e293b;border-bottom:1px solid #e2e8f0;">
                    📋 Consignment Information
                  </td>
                </tr>
                <tr>
                  <td style="padding:10px 16px;font-size:13px;color:#64748b;width:38%;border-bottom:1px solid #f1f5f9;">Sender</td>
                  <td style="padding:10px 16px;font-size:13px;color:#0f172a;font-weight:600;border-bottom:1px solid #f1f5f9;">{$s_name}</td>
                </tr>
                <tr style="background:#fafafa;">
                  <td style="padding:10px 16px;font-size:13px;color:#64748b;border-bottom:1px solid #f1f5f9;">Sender Address</td>
                  <td style="padding:10px 16px;font-size:13px;color:#334155;border-bottom:1px solid #f1f5f9;">{$s_address}</td>
                </tr>
                <tr>
                  <td style="padding:10px 16px;font-size:13px;color:#64748b;border-bottom:1px solid #f1f5f9;">Sender Contact</td>
                  <td style="padding:10px 16px;font-size:13px;color:#334155;border-bottom:1px solid #f1f5f9;">{$s_contact}</td>
                </tr>
                <tr style="background:#fafafa;">
                  <td style="padding:10px 16px;font-size:13px;color:#64748b;border-bottom:1px solid #f1f5f9;">Recipient</td>
                  <td style="padding:10px 16px;font-size:13px;color:#0f172a;font-weight:600;border-bottom:1px solid #f1f5f9;">{$r_name}</td>
                </tr>
                <tr>
                  <td style="padding:10px 16px;font-size:13px;color:#64748b;border-bottom:1px solid #f1f5f9;">Delivery Address</td>
                  <td style="padding:10px 16px;font-size:13px;color:#334155;border-bottom:1px solid #f1f5f9;">{$r_address}</td>
                </tr>
                <tr style="background:#fafafa;">
                  <td style="padding:10px 16px;font-size:13px;color:#64748b;border-bottom:1px solid #f1f5f9;">Recipient Contact</td>
                  <td style="padding:10px 16px;font-size:13px;color:#334155;border-bottom:1px solid #f1f5f9;">{$r_contact}</td>
                </tr>
                <tr>
                  <td style="padding:10px 16px;font-size:13px;color:#64748b;">Consignment Total</td>
                  <td style="padding:10px 16px;font-size:15px;color:#2563eb;font-weight:800;">\${$price}</td>
                </tr>
              </table>

              <!-- CTA Button -->
              <table width="100%" cellpadding="0" cellspacing="0" style="margin:28px 0 20px;">
                <tr>
                  <td align="center">
                    <a href="{$track_url}" target="_blank" style="display:inline-block;background:linear-gradient(135deg,#1e3a8a,#2563eb);color:#ffffff;text-decoration:none;padding:14px 34px;border-radius:8px;font-size:14px;font-weight:700;box-shadow:0 3px 10px rgba(37,99,235,0.3);">
                      🔍 Track This Parcel Live
                    </a>
                  </td>
                </tr>
              </table>

              <p style="font-size:12px;color:#94a3b8;line-height:1.6;margin:24px 0 0;text-align:center;">
                Have questions or need assistance? Reach out to support at <a href="mailto:{$s_address}" style="color:#2563eb;text-decoration:none;">{$app_url}</a>.
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background:#f8fafc;padding:18px 30px;border-top:1px solid #e2e8f0;text-align:center;">
              <p style="margin:0;font-size:12px;color:#94a3b8;">
                &copy; {$app_name} Logistics. Automated shipment notification.<br>
                <a href="{$app_url}" style="color:#2563eb;text-decoration:none;">Visit Website</a>
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
}

/**
 * Send parcel booking confirmation to both sender and recipient.
 */
function send_parcel_booking_emails(array $parcel): void {
    $app_name = defined('APP_NAME') ? APP_NAME : 'GaaTiTrack';
    $ref      = $parcel['reference_number'] ?? '';

    // 1. Email to Sender
    $sender_email = trim($parcel['sender_email'] ?? '');
    if (!empty($sender_email) && filter_var($sender_email, FILTER_VALIDATE_EMAIL)) {
        $subject = "[$app_name] Consignment Booked – Ref# $ref";
        $html    = build_parcel_email_html($parcel, 'sender');
        send_email($sender_email, ucwords($parcel['sender_name'] ?? ''), $subject, $html);
    }

    // 2. Email to Recipient
    $recipient_email = trim($parcel['recipient_email'] ?? '');
    if (!empty($recipient_email) && filter_var($recipient_email, FILTER_VALIDATE_EMAIL)) {
        $subject = "[$app_name] Parcel Dispatched to You – Ref# $ref";
        $html    = build_parcel_email_html($parcel, 'recipient');
        send_email($recipient_email, ucwords($parcel['recipient_name'] ?? ''), $subject, $html);
    }
}
