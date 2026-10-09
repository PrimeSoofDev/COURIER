<?php
/**
 * GaaTiTrack Admin - Detailed Consignment View & Printable Waybill
 */

$parcelId = (int)($_GET['id'] ?? 0);

if ($parcelId <= 0) {
    $_SESSION['flash_error'] = 'Invalid consignment ID specified.';
    header("Location: " . APP_URL . "/admin/index.php?page=parcels");
    exit;
}

// Fetch parcel details with origin and destination branch names
try {
    $stmt = $pdo->prepare("
        SELECT p.*,
               bf.city as from_city, bf.branch_code as from_code, bf.street as from_street, bf.contact as from_contact,
               bt.city as to_city, bt.branch_code as to_code, bt.street as to_street, bt.contact as to_contact
        FROM parcels p
        LEFT JOIN branches bf ON p.from_branch_id = bf.id
        LEFT JOIN branches bt ON p.to_branch_id = bt.id
        WHERE p.id = :id
    ");
    $stmt->execute([':id' => $parcelId]);
    $parcel = $stmt->fetch();

    if (!$parcel) {
        $_SESSION['flash_error'] = 'Consignment record not found.';
        header("Location: " . APP_URL . "/admin/index.php?page=parcels");
        exit;
    }

    // Role-based branch scoping for staff
    if (!is_admin() && user_branch_id() > 0) {
        $userBid = (int)user_branch_id();
        if ((int)$parcel['from_branch_id'] !== $userBid && (int)$parcel['to_branch_id'] !== $userBid) {
            $_SESSION['flash_error'] = 'Access restricted: This consignment is not assigned to your branch hub.';
            header("Location: " . APP_URL . "/admin/index.php?page=parcels");
            exit;
        }
    }

    // Fetch tracking timeline history
    $trackStmt = $pdo->prepare("
        SELECT * FROM parcel_tracks 
        WHERE parcel_id = :id 
        ORDER BY date_created DESC, id DESC
    ");
    $trackStmt->execute([':id' => $parcelId]);
    $tracks = $trackStmt->fetchAll();

} catch (PDOException $e) {
    app_log("Error viewing parcel {$parcelId}: " . $e->getMessage(), 'ERROR');
    $_SESSION['flash_error'] = 'Failed to load consignment details.';
    header("Location: " . APP_URL . "/admin/index.php?page=parcels");
    exit;
}

$pageTitle = 'Consignment #' . $parcel['reference_number'];
require_once __DIR__ . '/../header.php';
?>

<!-- Print-Specific Stylesheet -->
<style>
@media print {
  body * {
    visibility: hidden;
  }
  .gt-print-waybill, .gt-print-waybill * {
    visibility: visible;
  }
  .gt-print-waybill {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    margin: 0;
    padding: 20px;
    background: #fff;
    color: #000;
  }
  .no-print {
    display: none !important;
  }
}

.gt-waybill-card {
  background: #ffffff;
  border: 1px solid var(--gt-border);
  border-radius: var(--gt-radius-lg);
  padding: 2rem;
  box-shadow: var(--gt-shadow-sm);
  margin-bottom: 2rem;
}

.gt-timeline-item {
  position: relative;
  padding-left: 28px;
  padding-bottom: 1.5rem;
}

.gt-timeline-item:last-child {
  padding-bottom: 0;
}

.gt-timeline-item::before {
  content: '';
  position: absolute;
  left: 7px;
  top: 14px;
  bottom: 0;
  width: 2px;
  background-color: var(--gt-border);
}

.gt-timeline-item:last-child::before {
  display: none;
}

.gt-timeline-dot {
  position: absolute;
  left: 0;
  top: 4px;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  border: 3px solid #ffffff;
  box-shadow: 0 0 0 1px var(--gt-border);
  background-color: var(--gt-primary);
}
</style>

<!-- Action Bar & Navigation -->
<div class="no-print" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
  <div>
    <a href="<?php echo APP_URL; ?>/admin/index.php?page=parcels" class="gt-btn gt-btn-ghost gt-btn-sm" style="margin-bottom: 0.5rem;">
      &larr; Back to Consignments
    </a>
    <h1 style="font-size: 1.6rem; font-weight: 800; color: var(--gt-navy-950); margin: 0; display: flex; align-items: center; gap: 10px;">
      Reference: <span style="font-family: monospace; color: var(--gt-primary);"><?php echo e($parcel['reference_number']); ?></span>
      <?php echo get_status_badge((int)$parcel['status']); ?>
    </h1>
  </div>

  <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
    <button type="button" onclick="window.print()" class="gt-btn gt-btn-secondary">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
        <polyline points="6 9 6 2 18 2 18 9"></polyline>
        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
        <rect x="6" y="14" width="12" height="8"></rect>
      </svg>
      Print Waybill
    </button>
    
    <a href="<?php echo APP_URL; ?>/admin/index.php?page=tracking_events&ref=<?php echo urlencode($parcel['reference_number']); ?>" class="gt-btn gt-btn-primary">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
        <polyline points="23 4 23 10 17 10"></polyline>
        <polyline points="1 20 1 14 7 14"></polyline>
        <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
      </svg>
      Update Tracking Status
    </a>

    <a href="<?php echo APP_URL; ?>/admin/index.php?page=edit_parcel&id=<?php echo $parcel['id']; ?>" class="gt-btn gt-btn-outline">
      Edit Details
    </a>
  </div>
</div>

<!-- Primary Consignment Waybill & Overview -->
<div class="gt-waybill-card gt-print-waybill">
  
  <!-- Waybill Header -->
  <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid var(--gt-navy-950); padding-bottom: 1.25rem; margin-bottom: 1.5rem;">
    <div>
      <div style="font-size: 1.5rem; font-weight: 800; color: var(--gt-navy-950); letter-spacing: -0.02em;">
        GaaTi<span style="color: var(--gt-primary);">Track</span> Logistics
      </div>
      <div style="font-size: 0.8125rem; color: var(--gt-navy-500); margin-top: 2px;">
        Official Consignment Manifest & Bill of Lading
      </div>
    </div>
    <div style="text-align: right;">
      <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--gt-navy-400); font-weight: 700;">
        Tracking Waybill #
      </div>
      <div style="font-family: monospace; font-size: 1.25rem; font-weight: 800; color: var(--gt-navy-900); letter-spacing: 1px;">
        <?php echo e($parcel['reference_number']); ?>
      </div>
      <div style="font-size: 0.75rem; color: var(--gt-navy-500); margin-top: 2px;">
        Booked: <?php echo date('M d, Y h:i A', strtotime($parcel['date_created'])); ?>
      </div>
    </div>
  </div>

  <!-- Route & Dispatch Overview -->
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 1.75rem;">
    
    <!-- Origin / Shipper -->
    <div style="border: 1px solid var(--gt-border); border-radius: var(--gt-radius-md); padding: 1.25rem; background: var(--gt-bg-body);">
      <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--gt-primary); margin-bottom: 0.5rem; letter-spacing: 0.05em;">
        Origin Hub & Sender
      </div>
      <div style="font-size: 1.05rem; font-weight: 700; color: var(--gt-navy-950); margin-bottom: 4px;">
        <?php echo e($parcel['sender_name']); ?>
      </div>
      <div style="font-size: 0.875rem; color: var(--gt-navy-700); margin-bottom: 4px;">
        <strong>Phone:</strong> <?php echo e($parcel['sender_contact']); ?>
      </div>
      <div style="font-size: 0.8125rem; color: var(--gt-navy-600); margin-bottom: 8px;">
        <strong>Address:</strong> <?php echo e($parcel['sender_address']); ?>
      </div>
      <div style="font-size: 0.75rem; background: #ffffff; padding: 6px 10px; border-radius: var(--gt-radius-sm); border: 1px solid var(--gt-border); color: var(--gt-navy-700);">
        <strong>Dispatching Hub:</strong> <?php echo e($parcel['from_city'] ?? 'Central'); ?> 
        <?php if (!empty($parcel['from_code'])): ?> (<?php echo e($parcel['from_code']); ?>)<?php endif; ?>
      </div>
    </div>

    <!-- Destination / Consignee -->
    <div style="border: 1px solid var(--gt-border); border-radius: var(--gt-radius-md); padding: 1.25rem; background: var(--gt-bg-body);">
      <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--gt-accent); margin-bottom: 0.5rem; letter-spacing: 0.05em;">
        Destination Hub & Recipient
      </div>
      <div style="font-size: 1.05rem; font-weight: 700; color: var(--gt-navy-950); margin-bottom: 4px;">
        <?php echo e($parcel['recipient_name']); ?>
      </div>
      <div style="font-size: 0.875rem; color: var(--gt-navy-700); margin-bottom: 4px;">
        <strong>Phone:</strong> <?php echo e($parcel['recipient_contact']); ?>
      </div>
      <div style="font-size: 0.8125rem; color: var(--gt-navy-600); margin-bottom: 8px;">
        <strong>Delivery Address:</strong> <?php echo e($parcel['recipient_address']); ?>
      </div>
      <div style="font-size: 0.75rem; background: #ffffff; padding: 6px 10px; border-radius: var(--gt-radius-sm); border: 1px solid var(--gt-border); color: var(--gt-navy-700);">
        <strong>Destination Hub:</strong> <?php echo e($parcel['to_city'] ?? 'Direct Delivery'); ?> 
        <?php if (!empty($parcel['to_code'])): ?> (<?php echo e($parcel['to_code']); ?>)<?php endif; ?>
      </div>
    </div>

  </div>

  <?php if (!empty($parcel['parcel_image']) && file_exists(__DIR__ . '/../../assets/uploads/parcels/' . $parcel['parcel_image'])): ?>
    <!-- Verified Consignment Cargo Photo -->
    <div style="margin-bottom: 1.75rem; background: var(--gt-bg-muted); border: 1px solid var(--gt-border); border-radius: var(--gt-radius-md); padding: 1.25rem;">
      <div style="font-size: 0.8125rem; font-weight: 700; color: var(--gt-navy-900); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 6px;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
        Verified Consignment Cargo Photo
      </div>
      <div style="display: flex; gap: 1.25rem; align-items: center; flex-wrap: wrap;">
        <a href="<?php echo APP_URL . '/assets/uploads/parcels/' . e($parcel['parcel_image']); ?>" target="_blank" title="Click to view full size">
          <img src="<?php echo APP_URL . '/assets/uploads/parcels/' . e($parcel['parcel_image']); ?>" 
               alt="Consignment Cargo Photo" 
               style="max-width: 220px; max-height: 160px; object-fit: cover; border-radius: 8px; border: 1px solid var(--gt-border); box-shadow: 0 2px 6px rgba(0,0,0,0.06);">
        </a>
        <div style="font-size: 0.8125rem; color: var(--gt-navy-600); line-height: 1.5;">
          <div><strong>Verification:</strong> Intake visual inspection complete &bull; Attached to manifest</div>
          <div style="color: var(--gt-navy-400); font-size: 0.75rem; margin-top: 4px;">Click photo to open full-resolution inspection view in a new window.</div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Package Specifications & Charges -->
  <div style="margin-bottom: 1.75rem;">
    <div style="font-size: 0.875rem; font-weight: 700; color: var(--gt-navy-900); margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.03em;">
      Shipment Specifications & Freight Pricing
    </div>
    <div class="gt-table-container">
      <table class="gt-table">
        <thead>
          <tr>
            <th>Delivery Mode</th>
            <th>Billed Weight</th>
            <th>Dimensions (H &times; W &times; L)</th>
            <th>Current Status</th>
            <th style="text-align: right;">Freight Charges</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <span class="gt-badge gt-badge-primary">
                <?php echo (int)$parcel['type'] === 1 ? 'Doorstep Delivery' : 'Hub Pickup'; ?>
              </span>
            </td>
            <td><strong><?php echo e($parcel['weight']); ?></strong></td>
            <td><?php echo e($parcel['height']); ?> &times; <?php echo e($parcel['width']); ?> &times; <?php echo e($parcel['length']); ?></td>
            <td><?php echo get_status_badge((int)$parcel['status']); ?></td>
            <td style="text-align: right; font-weight: 800; font-size: 1.15rem; color: var(--gt-navy-950);">
              $<?php echo number_format((float)$parcel['price'], 2); ?>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Signatures block for printing -->
  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; margin-top: 3rem; padding-top: 1.5rem; border-top: 1px dashed var(--gt-border);">
    <div>
      <div style="border-bottom: 1px solid #000; height: 35px; margin-bottom: 6px;"></div>
      <div style="font-size: 0.75rem; color: var(--gt-navy-600); text-transform: uppercase; font-weight: 600;">
        Dispatcher / Agent Signature & Date
      </div>
    </div>
    <div>
      <div style="border-bottom: 1px solid #000; height: 35px; margin-bottom: 6px;"></div>
      <div style="font-size: 0.75rem; color: var(--gt-navy-600); text-transform: uppercase; font-weight: 600;">
        Consignee / Receiver Signature & Date
      </div>
    </div>
  </div>

</div>

<!-- Milestone Audit Trail Timeline (On-Screen & Print) -->
<div class="gt-card" style="margin-bottom: 2rem;">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid var(--gt-border); padding-bottom: 0.75rem;">
    <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--gt-navy-950); margin: 0;">
      Milestone Audit Trail & Tracking Log
    </h2>
    <span style="font-size: 0.8125rem; color: var(--gt-navy-500);">
      <?php echo count($tracks); ?> recorded milestone(s)
    </span>
  </div>

  <?php if (empty($tracks)): ?>
    <div style="text-align: center; padding: 2rem; color: var(--gt-navy-500);">
      <p style="margin: 0; font-size: 0.9375rem;">No milestone transitions logged yet. Consignment is currently at intake status.</p>
    </div>
  <?php else: ?>
    <div style="margin-left: 0.5rem;">
      <?php foreach ($tracks as $index => $tr): ?>
        <div class="gt-timeline-item">
          <div class="gt-timeline-dot" style="<?php echo $index === 0 ? 'background-color: var(--gt-accent);' : ''; ?>"></div>
          <div>
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 4px;">
              <?php echo get_status_badge((int)$tr['status']); ?>
              <span style="font-size: 0.8125rem; color: var(--gt-navy-500);">
                <?php echo date('l, M d, Y - h:i A', strtotime($tr['date_created'])); ?>
              </span>
              <?php if ($index === 0): ?>
                <span class="gt-badge gt-badge-0" style="font-size: 0.65rem; padding: 2px 6px;">Latest Milestone</span>
              <?php endif; ?>
            </div>
            <div style="font-size: 0.875rem; color: var(--gt-navy-700);">
              Status transitioned to <strong><?php echo e(status_label((int)$tr['status'])); ?></strong>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../footer.php';
