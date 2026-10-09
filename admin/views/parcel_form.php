<?php
/**
 * GaaTiTrack Admin - Consignment Intake & Edit Form
 * Supports multi-item consignment booking and reliable 12-digit reference generation.
 */

$editId    = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEditing = ($editId > 0);
$pageTitle = $isEditing ? 'Edit Consignment' : 'Book New Parcel';
require_once __DIR__ . '/../header.php';

function generate_unique_reference($pdo) {
    for ($i = 0; $i < 50; $i++) {
        $ref  = (string)random_int(100000000000, 999999999999);
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM parcels WHERE reference_number = :ref");
        $stmt->execute([':ref' => $ref]);
        if ((int)$stmt->fetchColumn() === 0) return $ref;
    }
    return (string)time() . mt_rand(10, 99);
}

$branches = $pdo->query("SELECT id, branch_code, city, state, street, zip_code FROM branches ORDER BY city ASC")->fetchAll();

$formData = [
    'sender_name'      => '', 'sender_address'    => '', 'sender_contact'    => '',
    'recipient_name'   => '', 'recipient_address' => '', 'recipient_contact' => '',
    'type'             => 1,
    'from_branch_id'   => is_admin() ? '' : user_branch_id(),
    'to_branch_id'     => '',
    'items'            => [['weight' => '', 'height' => '', 'width' => '', 'length' => '', 'price' => '']]
];

if ($isEditing) {
    $stmt = $pdo->prepare("SELECT * FROM parcels WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $editId]);
    $existing = $stmt->fetch();
    if ($existing) {
        $formData = array_merge($formData, [
            'sender_name'    => $existing['sender_name'],
            'sender_address' => $existing['sender_address'],
            'sender_contact' => $existing['sender_contact'],
            'recipient_name'    => $existing['recipient_name'],
            'recipient_address' => $existing['recipient_address'],
            'recipient_contact' => $existing['recipient_contact'],
            'type'           => (int)$existing['type'],
            'from_branch_id' => $existing['from_branch_id'],
            'to_branch_id'   => $existing['to_branch_id'],
            'items' => [['weight'=>$existing['weight'],'height'=>$existing['height'],
                         'width'=>$existing['width'],'length'=>$existing['length'],'price'=>$existing['price']]],
        ]);
    } else {
        $_SESSION['flash_error'] = 'Consignment not found.';
        header("Location: " . APP_URL . "/admin/index.php?page=parcels");
        exit;
    }
}

$errorMsg = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errorMsg = 'Security validation failed. Please try again.';
    } else {
        $sender_name      = trim($_POST['sender_name']      ?? '');
        $sender_address   = trim($_POST['sender_address']   ?? '');
        $sender_contact   = trim($_POST['sender_contact']   ?? '');
        $recipient_name   = trim($_POST['recipient_name']   ?? '');
        $recipient_address= trim($_POST['recipient_address']?? '');
        $recipient_contact= trim($_POST['recipient_contact']?? '');
        $type             = (int)($_POST['type'] ?? 1);
        $from_branch_id   = is_admin() ? ($_POST['from_branch_id'] ?? '') : user_branch_id();
        $to_branch_id     = $_POST['to_branch_id'] ?? '';
        $weights = $_POST['weight'] ?? [];
        $heights = $_POST['height'] ?? [];
        $widths  = $_POST['width']  ?? [];
        $lengths = $_POST['length'] ?? [];
        $prices  = $_POST['price']  ?? [];

        if (empty($sender_name) || empty($sender_contact) || empty($recipient_name) || empty($recipient_contact)) {
            $errorMsg = 'Please complete all required sender and recipient contact fields.';
        } elseif (empty($from_branch_id)) {
            $errorMsg = 'Please designate the origin processing branch.';
        } elseif ($type == 2 && empty($to_branch_id)) {
            $errorMsg = 'Please select a destination pickup branch for counter collection.';
        } elseif (empty($weights) || empty($prices)) {
            $errorMsg = 'Please include at least one parcel item specification.';
        } else {
            try {
                $pdo->beginTransaction();
                if ($isEditing) {
                    $priceVal = (float)str_replace(',', '', $prices[0] ?? 0);
                    $upStmt = $pdo->prepare("UPDATE parcels SET
                        sender_name=:sname,sender_address=:saddr,sender_contact=:scontact,
                        recipient_name=:rname,recipient_address=:raddr,recipient_contact=:rcontact,
                        type=:type,from_branch_id=:fbid,to_branch_id=:tbid,
                        weight=:weight,height=:height,width=:width,length=:length,price=:price WHERE id=:id");
                    $upStmt->execute([
                        ':sname'=>$sender_name,':saddr'=>$sender_address,':scontact'=>$sender_contact,
                        ':rname'=>$recipient_name,':raddr'=>$recipient_address,':rcontact'=>$recipient_contact,
                        ':type'=>$type,':fbid'=>$from_branch_id,':tbid'=>$to_branch_id,
                        ':weight'=>trim($weights[0]??''),':height'=>trim($heights[0]??''),
                        ':width'=>trim($widths[0]??''),':length'=>trim($lengths[0]??''),
                        ':price'=>$priceVal,':id'=>$editId
                    ]);
                    $pdo->commit();
                    app_log("Consignment updated: ID {$editId} by user " . current_user_id());
                    $_SESSION['flash_success'] = "Consignment #{$existing['reference_number']} updated successfully.";
                    header("Location: " . APP_URL . "/admin/index.php?page=view_parcel&id=" . $editId);
                    exit;
                } else {
                    $createdIds = []; $lastRef = '';
                    $inStmt = $pdo->prepare("INSERT INTO parcels (reference_number,sender_name,sender_address,sender_contact,recipient_name,recipient_address,recipient_contact,type,from_branch_id,to_branch_id,weight,height,width,length,price,status,date_created) VALUES (:ref,:sname,:saddr,:scontact,:rname,:raddr,:rcontact,:type,:fbid,:tbid,:weight,:height,:width,:length,:price,0,NOW())");
                    $trStmt = $pdo->prepare("INSERT INTO parcel_tracks (parcel_id,status,date_created) VALUES (:pid,0,NOW())");
                    foreach ($weights as $k => $w) {
                        $refNum = generate_unique_reference($pdo); $lastRef = $refNum;
                        $pVal   = (float)str_replace(',', '', $prices[$k] ?? 0);
                        $inStmt->execute([':ref'=>$refNum,':sname'=>$sender_name,':saddr'=>$sender_address,':scontact'=>$sender_contact,':rname'=>$recipient_name,':raddr'=>$recipient_address,':rcontact'=>$recipient_contact,':type'=>$type,':fbid'=>$from_branch_id,':tbid'=>$to_branch_id,':weight'=>trim($w),':height'=>trim($heights[$k]??''),':width'=>trim($widths[$k]??''),':length'=>trim($lengths[$k]??''),':price'=>$pVal]);
                        $newId = (int)$pdo->lastInsertId();
                        $createdIds[] = $newId;
                        $trStmt->execute([':pid' => $newId]);
                    }
                    $pdo->commit();
                    app_log("Created " . count($createdIds) . " consignment(s). Last: {$lastRef}");
                    $_SESSION['flash_success'] = "Consignment booked! Reference: #{$lastRef}";
                    header("Location: " . APP_URL . "/admin/index.php?page=view_parcel&id=" . $createdIds[0]);
                    exit;
                }
            } catch (PDOException $e) {
                $pdo->rollBack();
                app_log("Consignment save error: " . $e->getMessage(), 'ERROR');
                $errorMsg = 'Database error while saving consignment.';
            }
        }
    }
}
?>

<style>
  .form-page { max-width:1050px; }
  .form-page-header {
    display:flex;align-items:flex-start;justify-content:space-between;
    margin-bottom:1.75rem;flex-wrap:wrap;gap:1rem;
  }

  .form-section {
    background:#fff;border:1px solid #e2e8f0;border-radius:14px;
    overflow:hidden;margin-bottom:1.25rem;
  }
  .form-section-header {
    display:flex;align-items:center;gap:12px;
    padding:14px 20px;border-bottom:1px solid #f1f5f9;background:#fafbfd;
  }
  .form-section-icon {
    width:32px;height:32px;border-radius:8px;
    display:flex;align-items:center;justify-content:center;flex-shrink:0;
  }
  .form-section-title { font-size:.9rem;font-weight:700;color:#0f172a; }
  .form-section-sub   { font-size:.76rem;color:#64748b;margin-top:1px; }
  .form-section-body  { padding:20px; }

  .contact-grid {
    display:grid;grid-template-columns:1fr 1fr;gap:16px;
    padding:18px 20px;border-right:1px solid #f1f5f9;
  }
  @media (max-width:640px) { .contact-grid { grid-template-columns:1fr; } }
  .sender-recipient-grid {
    display:grid;grid-template-columns:1fr 1fr;
    border-bottom:1px solid #f1f5f9;
  }
  @media (max-width:760px) { .sender-recipient-grid { grid-template-columns:1fr; } }
  .sender-col { padding:18px 20px;border-right:1px solid #f1f5f9; }
  .recipient-col { padding:18px 20px; }

  .field-block { margin-bottom:14px; }
  .field-block:last-child { margin-bottom:0; }
  .f-label {
    display:block;font-size:.78rem;font-weight:700;color:#374151;margin-bottom:5px;
  }
  .f-label .req { color:#ef4444; }
  .f-input {
    width:100%;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;
    font-size:.875rem;color:#111;outline:none;font-family:inherit;
    transition:border-color .18s,box-shadow .18s;background:#fff;
  }
  .f-input:focus { border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.1); }
  textarea.f-input { resize:vertical;min-height:76px; }
  select.f-input {
    appearance:none;cursor:pointer;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
    background-repeat:no-repeat;background-position:right 10px center;padding-right:32px;
  }
  .f-hint { font-size:.72rem;color:#94a3b8;margin-top:3px; }

  /* Delivery type toggle */
  .dtype-row { display:flex;gap:10px;flex-wrap:wrap; }
  .dtype-card {
    flex:1;min-width:160px;padding:12px 14px;border:1.5px solid #e5e7eb;border-radius:10px;
    cursor:pointer;transition:border-color .15s,background .15s;position:relative;
  }
  .dtype-card:has(input:checked) { border-color:#3b82f6;background:#eff6ff; }
  .dtype-card input { position:absolute;opacity:0;pointer-events:none; }
  .dtype-card-title { font-size:.84rem;font-weight:700;color:#111827;margin-bottom:2px; }
  .dtype-card-sub   { font-size:.72rem;color:#94a3b8; }

  /* Items table */
  .items-table { width:100%;border-collapse:collapse; }
  .items-table th {
    padding:9px 12px;text-align:left;font-size:.7rem;font-weight:700;
    text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;
    background:#f8fafc;border-bottom:1px solid #f1f5f9;white-space:nowrap;
  }
  .items-table td {
    padding:8px 10px;border-bottom:1px solid #f8fafc;vertical-align:middle;
  }
  .items-table tr:last-child td { border-bottom:none; }
  .items-table tfoot td { background:#f1f5f9; }

  .add-row-btn {
    display:inline-flex;align-items:center;gap:6px;
    padding:7px 14px;border-radius:8px;border:1.5px solid #e5e7eb;
    background:#fff;color:#374151;font-size:.8rem;font-weight:600;
    cursor:pointer;transition:background .15s,border-color .15s;
  }
  .add-row-btn:hover { background:#f8fafc;border-color:#cbd5e1; }

  .remove-row-btn {
    width:28px;height:28px;border-radius:6px;border:1.5px solid #fca5a5;
    background:transparent;color:#dc2626;font-size:1rem;cursor:pointer;
    display:flex;align-items:center;justify-content:center;
    transition:background .15s;
  }
  .remove-row-btn:hover { background:#fef2f2; }

  .total-bar {
    display:flex;justify-content:flex-end;align-items:center;gap:12px;
    padding:12px 16px;background:#f8fafc;border-top:1px solid #f1f5f9;
    font-size:.84rem;
  }
  .total-val { font-size:1.1rem;font-weight:800;color:#2563eb; }

  .submit-bar {
    display:flex;gap:10px;align-items:center;
    padding:16px 20px;border-top:1px solid #e2e8f0;background:#fff;border-radius:0 0 14px 14px;
  }
  .submit-btn {
    display:inline-flex;align-items:center;gap:8px;
    padding:11px 24px;border-radius:9px;
    background:linear-gradient(135deg,#1d4ed8,#2563eb);
    color:#fff;font-size:.875rem;font-weight:700;border:none;cursor:pointer;
    box-shadow:0 2px 8px rgba(37,99,235,.3);
    transition:opacity .18s,transform .18s;
  }
  .submit-btn:hover { opacity:.9;transform:translateY(-1px); }
  .cancel-btn {
    padding:11px 20px;border-radius:9px;border:1.5px solid #e5e7eb;
    background:#fff;color:#6b7280;font-size:.875rem;font-weight:600;
    text-decoration:none;display:inline-flex;align-items:center;
    transition:border-color .15s,color .15s;
  }
  .cancel-btn:hover { border-color:#94a3b8;color:#374151; }

  .error-banner {
    display:flex;align-items:flex-start;gap:10px;
    background:#fef2f2;border:1px solid #fecaca;border-radius:10px;
    padding:12px 16px;margin-bottom:1.25rem;font-size:.84rem;color:#dc2626;
  }
</style>

<div class="form-page">

  <!-- Page Header -->
  <div class="form-page-header">
    <div>
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
        <div style="width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;
                    background:<?php echo $isEditing ? 'linear-gradient(135deg,#f59e0b,#ef4444)' : 'linear-gradient(135deg,#22c55e,#16a34a)'; ?>">
          <?php if ($isEditing): ?>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          <?php else: ?>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          <?php endif; ?>
        </div>
        <h1 style="font-size:1.5rem;font-weight:800;color:#0f172a;margin:0;">
          <?php echo $isEditing ? 'Edit Consignment #' . e($existing['reference_number']) : 'Book New Parcel'; ?>
        </h1>
      </div>
      <p style="margin:0;font-size:.875rem;color:#64748b;">
        <?php echo $isEditing ? 'Update package parameters and destination details.' : 'Intake parcel details, set dimensions, and generate a tracking reference.'; ?>
      </p>
    </div>
    <a href="<?php echo APP_URL; ?>/admin/index.php?page=parcels"
       style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border:1.5px solid #e5e7eb;border-radius:9px;color:#374151;font-size:.84rem;font-weight:600;text-decoration:none;background:#fff;">
      ← All Shipments
    </a>
  </div>

  <?php if (!empty($errorMsg)): ?>
    <div class="error-banner">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <?php echo e($errorMsg); ?>
    </div>
  <?php endif; ?>

  <form action="" method="POST" id="parcel-form">
    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

    <!-- Sender & Recipient -->
    <div class="form-section">
      <div class="form-section-header">
        <div class="form-section-icon" style="background:#eff6ff;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </div>
        <div>
          <div class="form-section-title">Sender & Recipient</div>
          <div class="form-section-sub">Contact details for both parties of this consignment</div>
        </div>
      </div>
      <div class="sender-recipient-grid">
        <!-- Sender -->
        <div class="sender-col">
          <div style="display:flex;align-items:center;gap:6px;margin-bottom:14px;">
            <div style="width:24px;height:24px;background:#dbeafe;border-radius:6px;display:flex;align-items:center;justify-content:center;">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            </div>
            <span style="font-size:.8rem;font-weight:700;color:#1d4ed8;text-transform:uppercase;letter-spacing:.05em;">Sender</span>
          </div>
          <div class="field-block">
            <label class="f-label">Full Name <span class="req">*</span></label>
            <input type="text" name="sender_name" class="f-input" value="<?php echo e($formData['sender_name']); ?>" required placeholder="e.g. Michael Anderson">
          </div>
          <div class="field-block">
            <label class="f-label">Contact Number <span class="req">*</span></label>
            <input type="text" name="sender_contact" class="f-input" value="<?php echo e($formData['sender_contact']); ?>" required placeholder="+1 (212) 555-0143">
          </div>
          <div class="field-block">
            <label class="f-label">Address <span class="req">*</span></label>
            <textarea name="sender_address" class="f-input" rows="3" required placeholder="Street address, suite/apt, city, state, ZIP..."><?php echo e($formData['sender_address']); ?></textarea>
          </div>
        </div>
        <!-- Recipient -->
        <div class="recipient-col">
          <div style="display:flex;align-items:center;gap:6px;margin-bottom:14px;">
            <div style="width:24px;height:24px;background:#dcfce7;border-radius:6px;display:flex;align-items:center;justify-content:center;">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            </div>
            <span style="font-size:.8rem;font-weight:700;color:#15803d;text-transform:uppercase;letter-spacing:.05em;">Recipient</span>
          </div>
          <div class="field-block">
            <label class="f-label">Full Name <span class="req">*</span></label>
            <input type="text" name="recipient_name" class="f-input" value="<?php echo e($formData['recipient_name']); ?>" required placeholder="e.g. Sarah Jenkins">
          </div>
          <div class="field-block">
            <label class="f-label">Contact Number <span class="req">*</span></label>
            <input type="text" name="recipient_contact" class="f-input" value="<?php echo e($formData['recipient_contact']); ?>" required placeholder="+1 (312) 555-0182">
          </div>
          <div class="field-block">
            <label class="f-label">Delivery Address <span class="req">*</span></label>
            <textarea name="recipient_address" class="f-input" rows="3" required placeholder="Full destination address, state, and ZIP..."><?php echo e($formData['recipient_address']); ?></textarea>
          </div>
        </div>
      </div>
    </div>

    <!-- Routing -->
    <div class="form-section">
      <div class="form-section-header">
        <div class="form-section-icon" style="background:#f0fdf4;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
        </div>
        <div>
          <div class="form-section-title">Routing & Delivery</div>
          <div class="form-section-sub">Origin hub, delivery modality, and destination</div>
        </div>
      </div>
      <div class="form-section-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;">

          <!-- Delivery type -->
          <div class="field-block">
            <label class="f-label">Delivery Modality</label>
            <div class="dtype-row">
              <label class="dtype-card">
                <input type="radio" name="type" value="1" <?php echo $formData['type'] == 1 ? 'checked' : ''; ?>>
                <div class="dtype-card-title">🚚 Doorstep</div>
                <div class="dtype-card-sub">Direct delivery to recipient address</div>
              </label>
              <label class="dtype-card">
                <input type="radio" name="type" value="2" id="pickup-type" <?php echo $formData['type'] == 2 ? 'checked' : ''; ?>>
                <div class="dtype-card-title">🏢 Branch Pickup</div>
                <div class="dtype-card-sub">Customer collects from hub</div>
              </label>
            </div>
          </div>

          <!-- Origin branch -->
          <div class="field-block">
            <label class="f-label" for="from_branch">Origin Intake Branch <span class="req">*</span></label>
            <?php if (is_admin()): ?>
              <select name="from_branch_id" id="from_branch" class="f-input" required>
                <option value="">Select Origin Hub</option>
                <?php foreach ($branches as $b): ?>
                  <option value="<?php echo $b['id']; ?>" <?php echo $formData['from_branch_id'] == $b['id'] ? 'selected' : ''; ?>>
                    <?php echo e($b['city']); ?> Hub (<?php echo e($b['branch_code']); ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            <?php else: ?>
              <input type="text" class="f-input" value="Your Assigned Branch" disabled style="background:#f8fafc;color:#94a3b8;">
              <input type="hidden" name="from_branch_id" value="<?php echo user_branch_id(); ?>">
            <?php endif; ?>
          </div>

          <!-- Destination branch -->
          <div class="field-block" id="dest-branch-wrap">
            <label class="f-label" for="to_branch">Destination Hub</label>
            <select name="to_branch_id" id="to_branch" class="f-input">
              <option value="">Select Destination Hub</option>
              <?php foreach ($branches as $b): ?>
                <option value="<?php echo $b['id']; ?>" <?php echo $formData['to_branch_id'] == $b['id'] ? 'selected' : ''; ?>>
                  <?php echo e($b['city']); ?> Hub (<?php echo e($b['branch_code']); ?>)
                </option>
              <?php endforeach; ?>
            </select>
            <div class="f-hint">Required for Branch Pickup routing.</div>
          </div>

        </div>
      </div>
    </div>

    <!-- Package Dimensions & Pricing -->
    <div class="form-section">
      <div class="form-section-header">
        <div class="form-section-icon" style="background:#fef9c3;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ca8a04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
        </div>
        <div style="flex:1;">
          <div class="form-section-title">Package Dimensions & Pricing</div>
          <div class="form-section-sub">Enter weight, dimensions (inches), and freight price per item</div>
        </div>
        <?php if (!$isEditing): ?>
          <button type="button" class="add-row-btn" id="add-item-btn">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Item
          </button>
        <?php endif; ?>
      </div>

      <div style="overflow-x:auto;">
        <table class="items-table" id="items-table">
          <thead>
            <tr>
              <th>Weight (kg)</th>
              <th>Height (in)</th>
              <th>Width (in)</th>
              <th>Length (in)</th>
              <th>Price ($)</th>
              <?php if (!$isEditing): ?><th style="width:40px;"></th><?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($formData['items'] as $item): ?>
              <tr class="item-row">
                <td><input type="text" name="weight[]" class="f-input" value="<?php echo e($item['weight']); ?>" required placeholder="e.g. 2.5"></td>
                <td><input type="text" name="height[]" class="f-input" value="<?php echo e($item['height']); ?>" required placeholder="e.g. 10"></td>
                <td><input type="text" name="width[]"  class="f-input" value="<?php echo e($item['width']); ?>"  required placeholder="e.g. 8"></td>
                <td><input type="text" name="length[]" class="f-input" value="<?php echo e($item['length']); ?>" required placeholder="e.g. 12"></td>
                <td><input type="number" step="0.01" name="price[]" class="f-input price-input" value="<?php echo e($item['price']); ?>" required placeholder="0.00" min="0"></td>
                <?php if (!$isEditing): ?>
                  <td><button type="button" class="remove-row-btn" title="Remove row">×</button></td>
                <?php endif; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="4" style="text-align:right;padding:10px 12px;font-size:.82rem;font-weight:700;color:#374151;">Total Consignment Value</td>
              <td style="padding:10px 12px;"><span id="total-price" class="total-val">$0.00</span></td>
              <?php if (!$isEditing): ?><td></td><?php endif; ?>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- Submit bar -->
      <div class="submit-bar">
        <button type="submit" class="submit-btn">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          <?php echo $isEditing ? 'Save Changes' : 'Confirm & Book Consignment'; ?>
        </button>
        <a href="<?php echo APP_URL; ?>/admin/index.php?page=parcels" class="cancel-btn">Cancel</a>
      </div>
    </div>

  </form>
</div><!-- /.form-page -->

<script>
(function () {
  function calcTotal() {
    let sum = 0;
    document.querySelectorAll('.price-input').forEach(function(el) {
      sum += parseFloat(el.value) || 0;
    });
    const el = document.getElementById('total-price');
    if (el) el.textContent = '$' + sum.toLocaleString('en-US', {minimumFractionDigits:2,maximumFractionDigits:2});
  }

  document.addEventListener('input', function(e) {
    if (e.target.classList.contains('price-input')) calcTotal();
  });

  const addBtn   = document.getElementById('add-item-btn');
  const tblBody  = document.querySelector('#items-table tbody');

  if (addBtn && tblBody) {
    addBtn.addEventListener('click', function () {
      const tpl = tblBody.querySelector('.item-row').cloneNode(true);
      tpl.querySelectorAll('input').forEach(function(i) { i.value = ''; });
      tblBody.appendChild(tpl);
      calcTotal();
    });
  }

  document.addEventListener('click', function(e) {
    const btn = e.target.closest('.remove-row-btn');
    if (!btn) return;
    const rows = document.querySelectorAll('.item-row');
    if (rows.length > 1) {
      btn.closest('.item-row').remove();
      calcTotal();
    } else {
      alert('At least one item is required.');
    }
  });

  calcTotal();
})();
</script>

<?php require_once __DIR__ . '/../footer.php'; ?>
