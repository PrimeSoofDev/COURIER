<?php
/**
 * GaaTiTrack Admin - System Settings & Branding Console
 */

require_admin();

$pageTitle = 'System Settings';

// Load existing settings
$settings = [
    'name'              => 'GaaTiTrack Logistics USA',
    'email'             => 'support@gaatitrack.com',
    'contact'           => '+1 (800) 555-0199',
    'address'           => '1250 Broadway, Suite 3200, New York, NY 10001, United States',
    'cover_img'         => '',
    'api_key'           => '',
    'smtp_host'         => 'sandbox.smtp.mailtrap.io',
    'smtp_port'         => '2525',
    'smtp_user'         => '',
    'smtp_pass'         => '',
    'smtp_security'     => 'tls',
    'mail_from_address' => 'no-reply@gaatitrack.com',
    'mail_from_name'    => 'GaaTiTrack Logistics',
    'mail_driver'       => 'smtp',
];

try {
    $sStmt = $pdo->query("SELECT * FROM system_settings WHERE id = 1 LIMIT 1");
    if ($row = $sStmt->fetch()) {
        $settings = array_merge($settings, $row);
    } else {
        $pdo->query("INSERT INTO system_settings (id, name, email, contact, address, cover_img, api_key, smtp_host, smtp_port, smtp_user, smtp_pass, smtp_security, mail_from_address, mail_from_name, mail_driver) VALUES (1, 'GaaTiTrack Logistics USA', 'support@gaatitrack.com', '+1 (800) 555-0199', '1250 Broadway, Suite 3200, New York, NY 10001, United States', '', '', 'sandbox.smtp.mailtrap.io', '2525', '', '', 'tls', 'no-reply@gaatitrack.com', 'GaaTiTrack Logistics', 'smtp')");
    }
} catch (PDOException $e) {
    app_log("Error reading system settings: " . $e->getMessage(), 'ERROR');
}

// Handle AJAX / direct test email dispatch to Mailtrap
if (isset($_GET['action']) && $_GET['action'] === 'test_mailtrap') {
    header('Content-Type: application/json');
    require_once __DIR__ . '/../../includes/mailer.php';
    $to = trim($_POST['test_email'] ?? $_GET['test_email'] ?? '');
    if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please provide a valid test recipient email address.']);
        exit;
    }

    $tempCfg = get_mail_config();
    if (!empty($_POST['smtp_host']))         $tempCfg['host']         = trim($_POST['smtp_host']);
    if (!empty($_POST['smtp_port']))         $tempCfg['port']         = (int)$_POST['smtp_port'];
    if (isset($_POST['smtp_user']))          $tempCfg['user']         = trim($_POST['smtp_user']);
    if (isset($_POST['smtp_pass']))          $tempCfg['pass']         = trim($_POST['smtp_pass']);
    if (!empty($_POST['smtp_security']))     $tempCfg['security']     = trim($_POST['smtp_security']);
    if (!empty($_POST['mail_from_address'])) $tempCfg['from_address'] = trim($_POST['mail_from_address']);
    if (!empty($_POST['mail_from_name']))    $tempCfg['from_name']    = trim($_POST['mail_from_name']);
    $tempCfg['driver'] = 'smtp';

    $subject = "✅ Mailtrap SMTP Connection Test – " . (defined('APP_NAME') ? APP_NAME : 'GaaTiTrack');
    $body = "<div style='font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;padding:24px;border:1px solid #bfdbfe;border-radius:10px;background:#f8fafc;max-width:550px;'>"
          . "<h2 style='color:#1e3a8a;margin-top:0;'>🎉 Mailtrap SMTP is Connected!</h2>"
          . "<p style='color:#334155;font-size:14px;line-height:1.6;'>This is a verified test dispatch from your <strong>GaaTiTrack Logistics</strong> system settings. Your Mailtrap credentials and SMTP socket are fully operational.</p>"
          . "<div style='background:#eff6ff;padding:12px;border-radius:6px;font-size:12px;color:#1e40af;font-family:monospace;'>"
          . "Host: " . htmlspecialchars($tempCfg['host']) . ":" . $tempCfg['port'] . "<br>"
          . "Security: " . strtoupper($tempCfg['security']) . "<br>"
          . "From: " . htmlspecialchars($tempCfg['from_name']) . " &lt;" . htmlspecialchars($tempCfg['from_address']) . "&gt;<br>"
          . "Timestamp: " . date('Y-m-d H:i:s')
          . "</div></div>";

    $res = send_smtp_socket($to, 'Mailtrap Tester', $subject, $body, $tempCfg);
    echo json_encode($res);
    exit;
}

$errors = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf)) {
        $errors[] = 'Security validation failed (CSRF token expired or mismatch).';
    } else {
        $name            = trim($_POST['name'] ?? '');
        $email           = trim($_POST['email'] ?? '');
        $contact         = trim($_POST['contact'] ?? '');
        $address         = trim($_POST['address'] ?? '');
        $apiKey          = trim($_POST['api_key'] ?? '');
        $smtpHost        = trim($_POST['smtp_host'] ?? 'sandbox.smtp.mailtrap.io');
        $smtpPort        = trim($_POST['smtp_port'] ?? '2525');
        $smtpUser        = trim($_POST['smtp_user'] ?? '');
        $smtpPass        = trim($_POST['smtp_pass'] ?? '');
        $smtpSecurity    = trim($_POST['smtp_security'] ?? 'tls');
        $mailFromAddress = trim($_POST['mail_from_address'] ?? 'no-reply@gaatitrack.com');
        $mailFromName    = trim($_POST['mail_from_name'] ?? 'GaaTiTrack Logistics');
        $mailDriver      = trim($_POST['mail_driver'] ?? 'smtp');
        $coverImgPath    = $settings['cover_img'] ?? '';

        if (empty($name))  $errors[] = 'Company or portal system name is required.';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid official contact email address is required.';
        if (empty($contact)) $errors[] = 'Direct contact telephone is required.';
        if (empty($address)) $errors[] = 'Headquarters street address is required.';

        if (empty($errors) && isset($_FILES['cover_img']) && $_FILES['cover_img']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['cover_img'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'File upload error (code ' . $file['error'] . ').';
            } elseif ($file['size'] > 2 * 1024 * 1024) {
                $errors[] = 'Cover image exceeds the 2MB size limit.';
            } else {
                $allowedMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($file['tmp_name']);
                if (!array_key_exists($mimeType, $allowedMimes)) {
                    $errors[] = 'Invalid image format. Only JPG, PNG, and WebP are allowed.';
                } else {
                    $ext = $allowedMimes[$mimeType];
                    $safeFileName = 'cover_' . bin2hex(random_bytes(8)) . '.' . $ext;
                    $uploadDir = __DIR__ . '/../../assets/uploads/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    if (move_uploaded_file($file['tmp_name'], $uploadDir . $safeFileName)) {
                        if (!empty($coverImgPath) && strpos($coverImgPath, 'cover_') === 0 && file_exists($uploadDir . $coverImgPath)) {
                            @unlink($uploadDir . $coverImgPath);
                        }
                        $coverImgPath = $safeFileName;
                    } else {
                        $errors[] = 'Failed to move uploaded file.';
                    }
                }
            }
        }

        if (empty($errors)) {
            try {
                $upd = $pdo->prepare("UPDATE system_settings SET
                    name=:name, email=:email, contact=:contact, address=:address, cover_img=:cover, api_key=:api_key,
                    smtp_host=:smtp_host, smtp_port=:smtp_port, smtp_user=:smtp_user, smtp_pass=:smtp_pass,
                    smtp_security=:smtp_security, mail_from_address=:mail_from_address, mail_from_name=:mail_from_name,
                    mail_driver=:mail_driver WHERE id=1");
                $upd->execute([
                    ':name'              => $name,
                    ':email'             => $email,
                    ':contact'           => $contact,
                    ':address'           => $address,
                    ':cover'             => $coverImgPath,
                    ':api_key'           => $apiKey,
                    ':smtp_host'         => $smtpHost,
                    ':smtp_port'         => $smtpPort,
                    ':smtp_user'         => $smtpUser,
                    ':smtp_pass'         => $smtpPass,
                    ':smtp_security'     => $smtpSecurity,
                    ':mail_from_address' => $mailFromAddress,
                    ':mail_from_name'    => $mailFromName,
                    ':mail_driver'       => $mailDriver,
                ]);
                app_log("Updated system settings including Mailtrap/SMTP by Admin " . current_user_id());
                $_SESSION['flash_success'] = 'System and Mailtrap settings updated successfully.';
                header("Location: " . APP_URL . "/admin/index.php?page=settings#mailtrap");
                exit;
            } catch (PDOException $e) {
                app_log("Settings update error: " . $e->getMessage(), 'ERROR');
                $errors[] = 'Database error while saving settings: ' . $e->getMessage();
            }
        }
    }
}


require_once __DIR__ . '/../header.php';
?>

<style>
  .settings-grid { display: grid; grid-template-columns: 280px 1fr; gap: 2rem; align-items: start; max-width: 1100px; }
  @media (max-width: 860px) { .settings-grid { grid-template-columns: 1fr; } }

  .settings-nav { position: sticky; top: 1rem; }
  .settings-nav-link {
    display: flex; align-items: center; gap: 10px;
    padding: 9px 14px; border-radius: 8px;
    font-size: 0.84rem; font-weight: 500; color: #64748b;
    text-decoration: none; margin-bottom: 2px;
    transition: background 0.15s, color 0.15s;
    border: none; background: none; cursor: pointer; width: 100%; text-align: left;
  }
  .settings-nav-link:hover { background: #f1f5f9; color: #0f172a; }
  .settings-nav-link.active { background: #eff6ff; color: #1d4ed8; font-weight: 600; }
  .settings-nav-link .nav-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #cbd5e1; flex-shrink: 0;
    transition: background 0.15s;
  }
  .settings-nav-link.active .nav-dot { background: #3b82f6; }

  .settings-section {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    overflow: hidden;
    margin-bottom: 1.5rem;
  }
  .settings-section-header {
    display: flex; align-items: center; gap: 12px;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #f1f5f9;
    background: #fafbfd;
  }
  .settings-section-icon {
    width: 38px; height: 38px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
  }
  .settings-section-body { padding: 1.5rem; }

  .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 1.25rem; }
  @media (max-width: 640px) { .form-row { grid-template-columns: 1fr; } }

  .field-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 1.25rem; }
  .field-label {
    font-size: 0.8125rem; font-weight: 600; color: #374151;
    display: flex; align-items: center; gap: 4px;
  }
  .field-label .required { color: #ef4444; }
  .field-hint { font-size: 0.75rem; color: #9ca3af; margin-top: 2px; }

  .modern-input {
    width: 100%; padding: 10px 14px;
    border: 1.5px solid #e5e7eb; border-radius: 9px;
    font-size: 0.875rem; color: #111827;
    background: #fff;
    transition: border-color 0.18s, box-shadow 0.18s;
    outline: none; font-family: inherit;
  }
  .modern-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.12); }
  .modern-input::placeholder { color: #9ca3af; }
  textarea.modern-input { resize: vertical; min-height: 90px; }

  .upload-zone {
    display: block;
    width: 100%;
    box-sizing: border-box;
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
    padding: 1.5rem 1rem;
    text-align: center;
    background: #f8fafc;
    cursor: pointer;
    transition: border-color 0.18s, background 0.18s;
  }
  .upload-zone:hover { border-color: #3b82f6; background: #eff6ff; }

  .save-bar {
    display: flex; align-items: center; justify-content: space-between;
    gap: 1rem; padding: 1.25rem 1.5rem;
    background: #f8fafc; border-top: 1px solid #e2e8f0;
  }
  .btn-save {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 10px 24px; border-radius: 9px;
    background: linear-gradient(135deg,#1d4ed8,#2563eb);
    color: #fff; font-size: 0.875rem; font-weight: 600;
    border: none; cursor: pointer;
    box-shadow: 0 2px 8px rgba(37,99,235,.35);
    transition: opacity 0.18s, transform 0.18s;
  }
  .btn-save:hover { opacity: .92; transform: translateY(-1px); }
  .btn-cancel {
    padding: 10px 20px; border-radius: 9px;
    border: 1.5px solid #e5e7eb; background: #fff;
    color: #6b7280; font-size: 0.875rem; font-weight: 500;
    text-decoration: none; display: inline-flex; align-items: center;
    transition: border-color 0.15s, color 0.15s;
  }
  .btn-cancel:hover { border-color: #94a3b8; color: #374151; }

  .error-list {
    background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px;
    padding: 1rem 1.25rem; margin-bottom: 1.5rem;
    display: flex; gap: 10px; align-items: flex-start;
  }
  .error-list ul { margin: 4px 0 0 1rem; padding: 0; color: #dc2626; font-size: 0.85rem; }
</style>

<!-- Page Header -->
<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:2rem;flex-wrap:wrap;gap:1rem;">
  <div>
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
      <div style="width:32px;height:32px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:8px;display:flex;align-items:center;justify-content:center;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
      </div>
      <h1 style="font-size:1.5rem;font-weight:800;color:#0f172a;margin:0;">System Settings</h1>
    </div>
    <p style="margin:0;font-size:0.875rem;color:#64748b;">Configure global company identity, contact details, and branding imagery.</p>
  </div>
</div>

<?php if (!empty($errors)): ?>
<div class="error-list">
  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:2px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
  <div>
    <div style="font-weight:700;color:#dc2626;font-size:.85rem;">Please fix the following errors:</div>
    <ul><?php foreach ($errors as $err): ?><li><?php echo e($err); ?></li><?php endforeach; ?></ul>
  </div>
</div>
<?php endif; ?>

<form method="POST" action="<?php echo APP_URL; ?>/admin/index.php?page=settings" enctype="multipart/form-data">
<input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

<div class="settings-grid">

  <!-- Left: Nav sidebar -->
  <div class="settings-nav">
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:10px;">
      <div style="padding:10px 14px 8px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#94a3b8;">Configuration</div>
      <a class="settings-nav-link active" href="#identity">
        <span class="nav-dot"></span> Company Identity
      </a>
      <a class="settings-nav-link" href="#contact">
        <span class="nav-dot"></span> Contact Details
      </a>
      <a class="settings-nav-link" href="#branding">
        <span class="nav-dot"></span> Brand Imagery
      </a>
      <a class="settings-nav-link" href="#apikeys">
        <span class="nav-dot"></span> API Configuration
      </a>
      <a class="settings-nav-link" href="#mailtrap">
        <span class="nav-dot"></span> Email & Mailtrap (SMTP)
      </a>
    </div>

    <!-- Info card -->
    <div style="margin-top:1rem;background:linear-gradient(135deg,#eff6ff,#f0fdf4);border:1px solid #bfdbfe;border-radius:12px;padding:1rem 1.125rem;">
      <div style="font-size:0.78rem;font-weight:700;color:#1d4ed8;margin-bottom:6px;">
        ⚙️ Settings Scope
      </div>
      <div style="font-size:0.77rem;color:#475569;line-height:1.6;">
        Changes here affect all printed waybills, freight manifests, system headers, and email footers across the platform.
      </div>
    </div>
  </div>

  <!-- Right: Form sections -->
  <div>

    <!-- Company Identity -->
    <div class="settings-section" id="identity">
      <div class="settings-section-header">
        <div class="settings-section-icon" style="background:#eff6ff;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        </div>
        <div>
          <div style="font-size:.9rem;font-weight:700;color:#0f172a;">Company Identity</div>
          <div style="font-size:.78rem;color:#64748b;">Name and official email used across all system outputs</div>
        </div>
      </div>
      <div class="settings-section-body">
        <div class="form-row">
          <div class="field-group">
            <label for="name" class="field-label">Portal / System Name <span class="required">*</span></label>
            <input type="text" id="name" name="name" class="modern-input"
                   value="<?php echo e($_POST['name'] ?? $settings['name']); ?>"
                   placeholder="GaaTiTrack Logistics" required>
            <div class="field-hint">Shown on waybills, manifests, and system headers.</div>
          </div>
          <div class="field-group">
            <label for="email" class="field-label">Operations Email <span class="required">*</span></label>
            <input type="email" id="email" name="email" class="modern-input"
                   value="<?php echo e($_POST['email'] ?? $settings['email']); ?>"
                   placeholder="operations@gaatitrack.com" required>
          </div>
        </div>
      </div>
    </div>

    <!-- Contact Details -->
    <div class="settings-section" id="contact">
      <div class="settings-section-header">
        <div class="settings-section-icon" style="background:#f0fdf4;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.77 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 8.91a16 16 0 0 0 6 6l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
        </div>
        <div>
          <div style="font-size:.9rem;font-weight:700;color:#0f172a;">Contact Details</div>
          <div style="font-size:.78rem;color:#64748b;">Phone and address printed on official documents</div>
        </div>
      </div>
      <div class="settings-section-body">
        <div class="field-group">
          <label for="contact" class="field-label">Support Hotline <span class="required">*</span></label>
          <input type="text" id="contact" name="contact" class="modern-input"
                 value="<?php echo e($_POST['contact'] ?? $settings['contact']); ?>"
                 placeholder="+1 (800) 555-0199" required>
        </div>
        <div class="field-group" style="margin-bottom:0;">
          <label for="address" class="field-label">Headquarters Address <span class="required">*</span></label>
          <textarea id="address" name="address" class="modern-input" rows="3"
                    placeholder="1250 Broadway, Suite 3200, New York, NY 10001..."
                    required><?php echo e($_POST['address'] ?? $settings['address']); ?></textarea>
        </div>
      </div>
    </div>

    <!-- Brand Imagery -->
    <div class="settings-section" id="branding">
      <div class="settings-section-header">
        <div class="settings-section-icon" style="background:#fdf4ff;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#a855f7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
        </div>
        <div>
          <div style="font-size:.9rem;font-weight:700;color:#0f172a;">Brand Imagery</div>
          <div style="font-size:.78rem;color:#64748b;">Cover image or logo shown on public pages</div>
        </div>
      </div>
      <div class="settings-section-body">
        <?php
          $hasImg = !empty($settings['cover_img']) &&
                    file_exists(__DIR__ . '/../../assets/uploads/' . $settings['cover_img']);
        ?>
        <div style="display:flex;gap:1.5rem;align-items:flex-start;flex-wrap:wrap;">
          <div style="flex-shrink:0;">
            <div id="brand-preview-container" style="width:130px;height:90px;border-radius:10px;border:1.5px solid #e2e8f0;display:flex;align-items:center;justify-content:center;background:#f8fafc;overflow:hidden;position:relative;">
              <img id="brand-preview-img"
                   src="<?php echo $hasImg ? APP_URL . '/assets/uploads/' . e($settings['cover_img']) : ''; ?>"
                   alt="Current brand image"
                   style="width:100%;height:100%;object-fit:cover;<?php echo $hasImg ? '' : 'display:none;'; ?>">
              <div id="brand-no-image" style="text-align:center;color:#94a3b8;font-size:0.75rem;padding:8px;<?php echo $hasImg ? 'display:none;' : ''; ?>">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 4px;display:block;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                No image
              </div>
            </div>
            <div style="font-size:0.72rem;color:#94a3b8;text-align:center;margin-top:6px;">Current preview</div>
          </div>
          <div style="flex:1;min-width:240px;">
            <label class="upload-zone" for="cover_img">
              <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="display:block;margin:0 auto 8px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
              <div style="font-size:.84rem;font-weight:600;color:#374151;margin-bottom:2px;">Click to upload a new image</div>
              <div style="font-size:.75rem;color:#9ca3af;">JPG, PNG, WebP — max 2 MB</div>
              <div id="file-name-indicator" style="display:none;margin-top:10px;font-size:0.78rem;color:#1d4ed8;font-weight:600;background:#eff6ff;border:1px solid #bfdbfe;padding:5px 12px;border-radius:8px;align-items:center;justify-content:center;gap:6px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                <span id="file-name-text"></span>
              </div>
              <input type="file" id="cover_img" name="cover_img" accept="image/jpeg,image/png,image/webp" style="display:none;">
            </label>
          </div>
        </div>
      </div>
    </div>

    <!-- API Configuration -->
    <div class="settings-section" id="apikeys">
      <div class="settings-section-header">
        <div class="settings-section-icon" style="background:#fff7ed;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
        </div>
        <div>
          <div style="font-size:.9rem;font-weight:700;color:#0f172a;">API Configuration</div>
          <div style="font-size:.78rem;color:#64748b;">Third-party service API keys for maps, logistics, and integrations</div>
        </div>
      </div>
      <div class="settings-section-body">
        <div class="field-group" style="margin-bottom:0;">
          <label for="api_key" class="field-label">Service API Key</label>
          <div style="position:relative;">
            <input type="password" id="api_key" name="api_key" class="modern-input"
                   value="<?php echo e($_POST['api_key'] ?? $settings['api_key'] ?? ''); ?>"
                   placeholder="Paste your API key here..." autocomplete="off"
                   style="padding-right:44px;font-family:monospace;letter-spacing:0.03em;">
            <button type="button" id="toggleApiKeyBtn" onclick="toggleApiKeyVisibility()"
                    title="Toggle API Key visibility"
                    style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:6px;display:flex;align-items:center;color:#64748b;">
              <svg id="eyeIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
          <div class="field-hint">Paste your API key here (e.g., Geoapify / Mapbox / OpenRouteService / Webhook key). Stored securely in database.</div>
        </div>
      </div>
    </div>

    <!-- Email & Mailtrap (SMTP) Configuration -->
    <div class="settings-section" id="mailtrap">
      <div class="settings-section-header" style="justify-content:space-between;flex-wrap:wrap;gap:10px;">
        <div style="display:flex;align-items:center;gap:12px;">
          <div class="settings-section-icon" style="background:#e0e7ff;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#4338ca" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
          </div>
          <div>
            <div style="font-size:.9rem;font-weight:700;color:#0f172a;display:flex;align-items:center;gap:8px;">
              Email & Mailtrap (SMTP) Settings
              <span style="font-size:0.7rem;background:#dbeafe;color:#1e40af;padding:2px 8px;border-radius:20px;font-weight:600;">Mailtrap Ready</span>
            </div>
            <div style="font-size:.78rem;color:#64748b;">Configure Mailtrap or external SMTP for booking confirmation emails</div>
          </div>
        </div>
        <button type="button" onclick="fillMailtrapSandboxDefaults()" style="display:inline-flex;align-items:center;gap:6px;background:#f1f5f9;border:1px solid #cbd5e1;padding:6px 12px;border-radius:8px;font-size:0.78rem;font-weight:600;color:#1e293b;cursor:pointer;transition:background 0.15s;">
          ⚡ Autofill Mailtrap Sandbox
        </button>
      </div>

      <div class="settings-section-body">
        
        <!-- Mailtrap helper banner -->
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-left:4px solid #4f46e5;border-radius:8px;padding:12px 16px;margin-bottom:1.5rem;display:flex;align-items:flex-start;gap:12px;">
          <div style="font-size:1.2rem;line-height:1;">📬</div>
          <div style="font-size:0.8125rem;color:#475569;line-height:1.5;">
            <strong>Using Mailtrap:</strong> Sign in to <a href="https://mailtrap.io" target="_blank" style="color:#4f46e5;font-weight:600;text-decoration:none;">mailtrap.io</a>, open your <em>Email Testing Inbox &rarr; Integrations</em>, choose <em>SMTP</em>, and copy your username &amp; password below. All booking confirmation emails to sender and recipient will be captured safely!
          </div>
        </div>

        <div class="form-row">
          <div class="field-group">
            <label for="mail_driver" class="field-label">Email Driver / Transport</label>
            <select id="mail_driver" name="mail_driver" class="modern-input">
              <option value="smtp" <?php echo ($settings['mail_driver'] ?? 'smtp') === 'smtp' ? 'selected' : ''; ?>>Mailtrap / SMTP Socket (Recommended)</option>
              <option value="mail" <?php echo ($settings['mail_driver'] ?? '') === 'mail' ? 'selected' : ''; ?>>PHP Native mail()</option>
            </select>
            <div class="field-hint">Select SMTP for Mailtrap test sandbox or live production relays.</div>
          </div>
          <div class="field-group">
            <label for="smtp_security" class="field-label">Encryption Protocol</label>
            <select id="smtp_security" name="smtp_security" class="modern-input">
              <option value="tls" <?php echo ($settings['smtp_security'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS / STARTTLS (Default for Mailtrap 2525/587)</option>
              <option value="ssl" <?php echo ($settings['smtp_security'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL (Port 465)</option>
              <option value="none" <?php echo ($settings['smtp_security'] ?? '') === 'none' ? 'selected' : ''; ?>>None (Plaintext)</option>
            </select>
            <div class="field-hint">Mailtrap supports TLS on ports 2525, 587, and 25.</div>
          </div>
        </div>

        <div class="form-row">
          <div class="field-group">
            <label for="smtp_host" class="field-label">SMTP Host <span class="required">*</span></label>
            <input type="text" id="smtp_host" name="smtp_host" class="modern-input"
                   value="<?php echo e($_POST['smtp_host'] ?? $settings['smtp_host'] ?? 'sandbox.smtp.mailtrap.io'); ?>"
                   placeholder="sandbox.smtp.mailtrap.io" required>
            <div class="field-hint">Mailtrap Sandbox: <code>sandbox.smtp.mailtrap.io</code> (or live: <code>live.smtp.mailtrap.io</code>)</div>
          </div>
          <div class="field-group">
            <label for="smtp_port" class="field-label">SMTP Port <span class="required">*</span></label>
            <input type="text" id="smtp_port" name="smtp_port" class="modern-input"
                   value="<?php echo e($_POST['smtp_port'] ?? $settings['smtp_port'] ?? '2525'); ?>"
                   placeholder="2525" required>
            <div class="field-hint">Typically <code>2525</code>, <code>587</code>, or <code>25</code> for Mailtrap.</div>
          </div>
        </div>

        <div class="form-row">
          <div class="field-group">
            <label for="smtp_user" class="field-label">SMTP Username (Mailtrap)</label>
            <input type="text" id="smtp_user" name="smtp_user" class="modern-input"
                   value="<?php echo e($_POST['smtp_user'] ?? $settings['smtp_user'] ?? ''); ?>"
                   placeholder="e.g. 1a2b3c4d5e6f7g" autocomplete="off">
            <div class="field-hint">Your Mailtrap inbox username.</div>
          </div>
          <div class="field-group">
            <label for="smtp_pass" class="field-label">SMTP Password (Mailtrap)</label>
            <div style="position:relative;">
              <input type="password" id="smtp_pass" name="smtp_pass" class="modern-input"
                     value="<?php echo e($_POST['smtp_pass'] ?? $settings['smtp_pass'] ?? ''); ?>"
                     placeholder="Paste Mailtrap SMTP password" autocomplete="off"
                     style="padding-right:44px;font-family:monospace;">
              <button type="button" onclick="toggleSmtpPassVisibility()"
                      title="Toggle password visibility"
                      style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:6px;display:flex;align-items:center;color:#64748b;">
                <svg id="smtpEyeIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
            <div class="field-hint">Your Mailtrap inbox password.</div>
          </div>
        </div>

        <div class="form-row">
          <div class="field-group">
            <label for="mail_from_address" class="field-label">Default Sender / From Email <span class="required">*</span></label>
            <input type="email" id="mail_from_address" name="mail_from_address" class="modern-input"
                   value="<?php echo e($_POST['mail_from_address'] ?? $settings['mail_from_address'] ?? 'no-reply@gaatitrack.com'); ?>"
                   placeholder="no-reply@gaatitrack.com" required>
            <div class="field-hint">Displayed as the sender address on outgoing parcel booking notifications.</div>
          </div>
          <div class="field-group">
            <label for="mail_from_name" class="field-label">Default Sender / From Name <span class="required">*</span></label>
            <input type="text" id="mail_from_name" name="mail_from_name" class="modern-input"
                   value="<?php echo e($_POST['mail_from_name'] ?? $settings['mail_from_name'] ?? 'GaaTiTrack Logistics'); ?>"
                   placeholder="GaaTiTrack Logistics" required>
            <div class="field-hint">Company name displayed in the recipient's inbox.</div>
          </div>
        </div>

        <!-- Live Mailtrap Test Sandbox Dispatch Box -->
        <div style="background:#f1f5f9;border:1.5px solid #cbd5e1;border-radius:12px;padding:1.25rem;margin-top:1rem;">
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
            <div style="width:24px;height:24px;background:#4f46e5;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:12px;">🚀</div>
            <div style="font-size:0.875rem;font-weight:700;color:#0f172a;">Live Mailtrap Connection Tester</div>
          </div>
          <p style="font-size:0.8rem;color:#64748b;margin:0 0 12px;line-height:1.5;">
            Send a live test message to verify your Mailtrap credentials right now. The email will show up instantly in your Mailtrap inbox.
          </p>

          <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <div style="flex:1;min-width:240px;">
              <input type="email" id="test_email_recipient" class="modern-input"
                     value="<?php echo e(current_user()['email'] ?? 'test@example.com'); ?>"
                     placeholder="Recipient address (e.g. test@example.com)">
            </div>
            <button type="button" id="btnTestMailtrap" onclick="runMailtrapTest()"
                    style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:#4f46e5;color:#fff;border:none;border-radius:9px;font-size:0.84rem;font-weight:700;cursor:pointer;transition:background 0.15s,transform 0.15s;white-space:nowrap;">
              <span id="testMailtrapBtnText">✉️ Send Test to Mailtrap</span>
              <span id="testMailtrapSpinner" style="display:none;">⏳ Testing...</span>
            </button>
          </div>

          <div id="mailtrapTestResult" style="display:none;margin-top:12px;padding:12px 14px;border-radius:8px;font-size:0.82rem;"></div>
        </div>

      </div>
    </div>

    <!-- Save Bar -->
    <div class="save-bar" style="border-radius:14px;border:1px solid #e2e8f0;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
      <div style="font-size:.83rem;color:#64748b;">All fields marked <span style="color:#ef4444;font-weight:700;">*</span> are required.</div>
      <div style="display:flex;gap:10px;align-items:center;">
        <a href="<?php echo APP_URL; ?>/admin/index.php" class="btn-cancel">Cancel</a>
        <button type="submit" class="btn-save">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          Save Settings
        </button>
      </div>
    </div>

  </div>
</div>

</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var fileInput = document.getElementById('cover_img');
  var previewImg = document.getElementById('brand-preview-img');
  var noImgBox = document.getElementById('brand-no-image');
  var fileIndicator = document.getElementById('file-name-indicator');
  var fileNameText = document.getElementById('file-name-text');

  if (fileInput) {
    fileInput.addEventListener('change', function(e) {
      var file = e.target.files && e.target.files[0];
      if (file) {
        if (fileNameText && fileIndicator) {
          fileNameText.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
          fileIndicator.style.display = 'inline-flex';
        }
        var reader = new FileReader();
        reader.onload = function(evt) {
          if (previewImg) {
            previewImg.src = evt.target.result;
            previewImg.style.display = 'block';
          }
          if (noImgBox) {
            noImgBox.style.display = 'none';
          }
        };
        reader.readAsDataURL(file);
      }
    });
  }
});

function toggleApiKeyVisibility() {
  var input = document.getElementById('api_key');
  var icon = document.getElementById('eyeIcon');
  if (!input) return;
  if (input.type === 'password') {
    input.type = 'text';
    if (icon) {
      icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
    }
  } else {
    input.type = 'password';
    if (icon) {
      icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
    }
  }
}

function toggleSmtpPassVisibility() {
  var input = document.getElementById('smtp_pass');
  var icon = document.getElementById('smtpEyeIcon');
  if (!input) return;
  if (input.type === 'password') {
    input.type = 'text';
    if (icon) {
      icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
    }
  } else {
    input.type = 'password';
    if (icon) {
      icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
    }
  }
}

function fillMailtrapSandboxDefaults() {
  document.getElementById('smtp_host').value = 'sandbox.smtp.mailtrap.io';
  document.getElementById('smtp_port').value = '2525';
  document.getElementById('smtp_security').value = 'tls';
  document.getElementById('mail_driver').value = 'smtp';
  var userField = document.getElementById('smtp_user');
  userField.focus();
  userField.style.borderColor = '#4f46e5';
  userField.style.boxShadow = '0 0 0 3px rgba(79,70,229,0.2)';
  setTimeout(function() {
    userField.style.borderColor = '';
    userField.style.boxShadow = '';
  }, 2000);
}

function runMailtrapTest() {
  var recipient = document.getElementById('test_email_recipient').value.trim();
  var resultBox = document.getElementById('mailtrapTestResult');
  var btn = document.getElementById('btnTestMailtrap');
  var btnText = document.getElementById('testMailtrapBtnText');
  var spinner = document.getElementById('testMailtrapSpinner');

  if (!recipient) {
    alert('Please enter a recipient email address to test.');
    return;
  }

  btn.disabled = true;
  btnText.style.display = 'none';
  spinner.style.display = 'inline';
  resultBox.style.display = 'none';

  var formData = new FormData();
  formData.append('test_email', recipient);
  formData.append('smtp_host', document.getElementById('smtp_host').value);
  formData.append('smtp_port', document.getElementById('smtp_port').value);
  formData.append('smtp_user', document.getElementById('smtp_user').value);
  formData.append('smtp_pass', document.getElementById('smtp_pass').value);
  formData.append('smtp_security', document.getElementById('smtp_security').value);
  formData.append('mail_from_address', document.getElementById('mail_from_address').value);
  formData.append('mail_from_name', document.getElementById('mail_from_name').value);

  fetch('<?php echo APP_URL; ?>/admin/index.php?page=settings&action=test_mailtrap', {
    method: 'POST',
    body: formData
  })
  .then(function(res) { return res.json(); })
  .then(function(data) {
    btn.disabled = false;
    btnText.style.display = 'inline';
    spinner.style.display = 'none';
    resultBox.style.display = 'block';

    if (data.success) {
      resultBox.style.background = '#ecfdf5';
      resultBox.style.border = '1px solid #a7f3d0';
      resultBox.style.color = '#065f46';
      resultBox.innerHTML = '<strong>✅ Success!</strong> ' + (data.message || 'Test email dispatched to Mailtrap inbox.');
    } else {
      resultBox.style.background = '#fef2f2';
      resultBox.style.border = '1px solid #fecaca';
      resultBox.style.color = '#991b1b';
      resultBox.innerHTML = '<strong>❌ Connection Failed:</strong> ' + (data.message || 'Unable to connect to Mailtrap SMTP.');
    }
  })
  .catch(function(err) {
    btn.disabled = false;
    btnText.style.display = 'inline';
    spinner.style.display = 'none';
    resultBox.style.display = 'block';
    resultBox.style.background = '#fef2f2';
    resultBox.style.border = '1px solid #fecaca';
    resultBox.style.color = '#991b1b';
    resultBox.innerHTML = '<strong>❌ Network Error:</strong> ' + err.message;
  });
}
</script>

<?php require_once __DIR__ . '/../footer.php'; ?>
