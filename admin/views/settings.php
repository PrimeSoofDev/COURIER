<?php
/**
 * GaaTiTrack Admin - System Settings & Branding Console
 */

require_admin();

$pageTitle = 'System Settings';

// Load existing settings
$settings = [
    'name'      => 'GaaTiTrack Logistics USA',
    'email'     => 'support@gaatitrack.com',
    'contact'   => '+1 (800) 555-0199',
    'address'   => '1250 Broadway, Suite 3200, New York, NY 10001, United States',
    'cover_img' => '',
    'api_key'   => '',
];

try {
    $sStmt = $pdo->query("SELECT * FROM system_settings WHERE id = 1 LIMIT 1");
    if ($row = $sStmt->fetch()) {
        $settings = array_merge($settings, $row);
    } else {
        $pdo->query("INSERT INTO system_settings (id, name, email, contact, address, cover_img, api_key) VALUES (1, 'GaaTiTrack Logistics USA', 'support@gaatitrack.com', '+1 (800) 555-0199', '1250 Broadway, Suite 3200, New York, NY 10001, United States', '', '')");
    }
} catch (PDOException $e) {
    app_log("Error reading system settings: " . $e->getMessage(), 'ERROR');
}

$errors = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf)) {
        $errors[] = 'Security validation failed (CSRF token expired or mismatch).';
    } else {
        $name       = trim($_POST['name'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $contact    = trim($_POST['contact'] ?? '');
        $address    = trim($_POST['address'] ?? '');
        $apiKey     = trim($_POST['api_key'] ?? '');
        $coverImgPath = $settings['cover_img'] ?? '';

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
                $upd = $pdo->prepare("UPDATE system_settings SET name=:name,email=:email,contact=:contact,address=:address,cover_img=:cover,api_key=:api_key WHERE id=1");
                $upd->execute([':name'=>$name,':email'=>$email,':contact'=>$contact,':address'=>$address,':cover'=>$coverImgPath,':api_key'=>$apiKey]);
                app_log("Updated system settings by Admin " . current_user_id());
                $_SESSION['flash_success'] = 'System settings updated successfully.';
                header("Location: " . APP_URL . "/admin/index.php?page=settings");
                exit;
            } catch (PDOException $e) {
                app_log("Settings update error: " . $e->getMessage(), 'ERROR');
                $errors[] = 'Database error while saving settings.';
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
</script>

<?php require_once __DIR__ . '/../footer.php'; ?>
