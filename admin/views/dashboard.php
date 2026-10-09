<?php
/**
 * GaaTiTrack Admin - Real-Time Operations Dashboard View
 * All metrics computed dynamically from active database records.
 */

$pageTitle = 'Operations Dashboard';
require_once __DIR__ . '/../header.php';

// Role Filtering: Staff only view parcels originating from or destined to their branch
$branchConstraint = '';
$branchParams = [];
if (!is_admin() && user_branch_id() > 0) {
    $branchConstraint = " WHERE (from_branch_id = :bid OR to_branch_id = :bid) ";
    $branchParams[':bid'] = user_branch_id();
}

// 1. Fetch Real KPI Counts
try {
    // Total Shipments
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM parcels" . $branchConstraint);
    $stmt->execute($branchParams);
    $totalShipments = (int)$stmt->fetchColumn();

    // Accepted / Collected (Status 0, 1)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM parcels " . (empty($branchConstraint) ? "WHERE" : $branchConstraint . " AND") . " status IN (0, 1)");
    $stmt->execute($branchParams);
    $pendingShipments = (int)$stmt->fetchColumn();

    // In-Transit / Shipped / Hub Arrival (Status 2, 3, 4, 5)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM parcels " . (empty($branchConstraint) ? "WHERE" : $branchConstraint . " AND") . " status IN (2, 3, 4, 5)");
    $stmt->execute($branchParams);
    $inTransitShipments = (int)$stmt->fetchColumn();

    // Delivered / Collected (Status 7, 8)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM parcels " . (empty($branchConstraint) ? "WHERE" : $branchConstraint . " AND") . " status IN (7, 8)");
    $stmt->execute($branchParams);
    $deliveredShipments = (int)$stmt->fetchColumn();

    // Delayed / Unsuccessful (Status 9)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM parcels " . (empty($branchConstraint) ? "WHERE" : $branchConstraint . " AND") . " status = 9");
    $stmt->execute($branchParams);
    $delayedShipments = (int)$stmt->fetchColumn();

    // Admin-only Global Metrics
    $totalBranches = 0;
    $totalStaff = 0;
    if (is_admin()) {
        $totalBranches = (int)$pdo->query("SELECT COUNT(*) FROM branches")->fetchColumn();
        $totalStaff = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE type = 2")->fetchColumn();
    }

    // 2. Status Distribution Counts (Real database aggregation)
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as cnt FROM parcels" . $branchConstraint . " GROUP BY status");
    $stmt->execute($branchParams);
    $statusCounts = array_fill(0, 10, 0);
    while ($row = $stmt->fetch()) {
        $statusCounts[(int)$row['status']] = (int)$row['cnt'];
    }

    // 3. Recent Consignments (Top 6 latest)
    $rStmt = $pdo->prepare("SELECT id, reference_number, sender_name, recipient_name, type, status, price, date_created FROM parcels" . $branchConstraint . " ORDER BY date_created DESC LIMIT 6");
    $rStmt->execute($branchParams);
    $recentParcels = $rStmt->fetchAll();

    // 4. Recent Tracking Milestones (Latest 6 tracks)
    $tStmt = $pdo->prepare("SELECT pt.status, pt.date_created, p.reference_number, p.id as parcel_id 
        FROM parcel_tracks pt 
        INNER JOIN parcels p ON p.id = pt.parcel_id 
        " . (empty($branchConstraint) ? "" : " WHERE (p.from_branch_id = :bid OR p.to_branch_id = :bid) ") . "
        ORDER BY pt.date_created DESC LIMIT 6");
    $tStmt->execute($branchParams);
    $recentTracks = $tStmt->fetchAll();

} catch (PDOException $e) {
    app_log("Dashboard metric calculation error: " . $e->getMessage(), 'ERROR');
    $totalShipments = 0;
    $pendingShipments = 0;
    $inTransitShipments = 0;
    $deliveredShipments = 0;
    $delayedShipments = 0;
    $recentParcels = [];
    $recentTracks = [];
}
?>

<!-- Dashboard Header & Actions -->
<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
  <div>
    <h1 style="font-size: 1.35rem; font-weight: 800; margin-bottom: 0.25rem; color: var(--gt-navy-950);">Operations Overview</h1>
    <div style="font-size: 0.8rem; color: var(--gt-navy-500);">
      Real-time consignment status metrics calculated directly from database records.
    </div>
  </div>
  <div style="display: flex; gap: 0.6rem;">
    <a href="<?php echo APP_URL; ?>/admin/index.php?page=new_parcel" class="gt-btn gt-btn-primary gt-btn-sm" style="font-size: 0.78rem; padding: 6px 12px;">
      <span>➕</span> Book New Parcel
    </a>
    <a href="<?php echo APP_URL; ?>/admin/index.php?page=tracking_events" class="gt-btn gt-btn-secondary gt-btn-sm" style="font-size: 0.78rem; padding: 6px 12px;">
      <span>🚚</span> Update Status
    </a>
  </div>
</div>

<!-- Primary Real-Time Stat Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
  
  <!-- Total Consignments -->
  <?php 
    echo render_stat_card('Total Consignments', $totalShipments, '📦', 'default');
    echo render_stat_card('In-Transit & Shipped', $inTransitShipments, '🚚', 'accent');
    echo render_stat_card('Successfully Delivered', $deliveredShipments, '✅', 'success');
    echo render_stat_card('Pending Intake', $pendingShipments, '⏳', 'warning');
    if ($delayedShipments > 0 || is_admin()) {
        echo render_stat_card('Unsuccessful Delivery', $delayedShipments, '⚠️', $delayedShipments > 0 ? 'failed' : 'default');
    }
  ?>

</div>

<?php if (is_admin()): ?>
  <!-- Secondary Admin Network Metrics -->
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.75rem;">
    <div class="gt-card" style="padding: 1rem 1.25rem; display: flex; align-items: center; justify-content: space-between;">
      <div>
        <div style="font-size: 0.75rem; color: var(--gt-navy-500); font-weight: 600;">Active Hub Branches</div>
        <div style="font-size: 1.25rem; font-weight: 700; color: var(--gt-navy-950); margin-top: 2px;"><?php echo $totalBranches; ?> Hubs</div>
      </div>
      <a href="<?php echo APP_URL; ?>/admin/index.php?page=branches" class="gt-btn gt-btn-secondary gt-btn-sm" style="font-size: 0.75rem; padding: 4px 10px;">Manage &rarr;</a>
    </div>

    <div class="gt-card" style="padding: 1rem 1.25rem; display: flex; align-items: center; justify-content: space-between;">
      <div>
        <div style="font-size: 0.75rem; color: var(--gt-navy-500); font-weight: 600;">Registered Branch Staff</div>
        <div style="font-size: 1.25rem; font-weight: 700; color: var(--gt-navy-950); margin-top: 2px;"><?php echo $totalStaff; ?> Operators</div>
      </div>
      <a href="<?php echo APP_URL; ?>/admin/index.php?page=staff" class="gt-btn gt-btn-secondary gt-btn-sm" style="font-size: 0.75rem; padding: 4px 10px;">Staff &rarr;</a>
    </div>
  </div>
<?php endif; ?>

<!-- Real Status Distribution & Activity Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2rem; margin-bottom: 2.5rem;">
  
  <!-- Status Distribution Breakdown -->
  <div class="gt-card">
    <div class="gt-card-header" style="padding: 1rem 1.25rem;">
      <h3 style="font-size: 0.95rem; font-weight: 700; margin: 0;">Live Status Breakdown</h3>
      <span style="font-size: 0.75rem; color: var(--gt-navy-500);">Real DB Record Counts</span>
    </div>
    <div class="gt-card-body" style="padding: 1rem 1.25rem;">
      <div style="display: flex; flex-direction: column; gap: 0.75rem;">
        <?php 
          $dict = get_status_dictionary();
          foreach ($dict as $statusCode => $statusLabel): 
            $count = $statusCounts[$statusCode] ?? 0;
            $percent = $totalShipments > 0 ? round(($count / $totalShipments) * 100, 1) : 0;
            if ($count == 0 && !in_array($statusCode, [0, 2, 3, 5, 7], true)) continue; // Compact display
        ?>
          <div>
            <div style="display: flex; justify-content: space-between; font-size: 0.75rem; margin-bottom: 0.3rem;">
              <span style="display: flex; align-items: center; gap: 6px;">
                <span class="gt-badge gt-badge-<?php echo $statusCode; ?>" style="font-size: 0.65rem; padding: 2px 6px;">
                  <?php echo e($statusLabel); ?>
                </span>
              </span>
              <strong style="color: var(--gt-navy-900); font-size: 0.75rem;"><?php echo $count; ?> (<?php echo $percent; ?>%)</strong>
            </div>
            <!-- Progress Bar -->
            <div style="height: 5px; background: var(--gt-bg-muted); border-radius: var(--gt-radius-full); overflow: hidden;">
              <div style="width: <?php echo $percent; ?>%; height: 100%; background: var(--gt-primary); border-radius: var(--gt-radius-full);"></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Recent Tracking Activity Feed -->
  <div class="gt-card">
    <div class="gt-card-header" style="padding: 1rem 1.25rem;">
      <h3 style="font-size: 0.95rem; font-weight: 700; margin: 0;">Recent Tracking Milestones</h3>
      <a href="<?php echo APP_URL; ?>/admin/index.php?page=tracking_events" style="font-size: 0.75rem; font-weight: 600;">View All &rarr;</a>
    </div>
    <div class="gt-card-body" style="padding: 0.85rem 1.25rem;">
      <?php if (!empty($recentTracks)): ?>
        <div style="display: flex; flex-direction: column; gap: 0.85rem;">
          <?php foreach ($recentTracks as $track): ?>
            <div style="display: flex; align-items: flex-start; justify-content: space-between; border-bottom: 1px solid var(--gt-border); padding-bottom: 0.65rem;">
              <div>
                <div style="font-size: 0.78rem; font-weight: 700; color: var(--gt-navy-950);">
                  <a href="<?php echo APP_URL; ?>/admin/index.php?page=view_parcel&id=<?php echo $track['parcel_id']; ?>" style="color: #2563eb; text-decoration: none;">
                    #<?php echo e($track['reference_number']); ?>
                  </a>
                </div>
                <div style="font-size: 0.68rem; color: var(--gt-navy-500); margin-top: 2px;">
                  <?php echo date('M d, Y - h:i A', strtotime($track['date_created'])); ?>
                </div>
              </div>
              <div>
                <?php echo render_status_badge((int)$track['status']); ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <?php echo render_empty_state('No Milestones Logged', 'Tracking events will appear here as parcels transition.'); ?>
      <?php endif; ?>
    </div>
  </div>

</div>

<!-- Recent Consignments Table -->
<div class="gt-card" style="margin-bottom: 2rem;">
  <div class="gt-card-header" style="padding: 1rem 1.25rem;">
    <h3 style="font-size: 0.95rem; font-weight: 700; margin: 0;">Latest Consignment Bookings</h3>
    <a href="<?php echo APP_URL; ?>/admin/index.php?page=parcels" class="gt-btn gt-btn-secondary gt-btn-sm" style="font-size: 0.75rem; padding: 4px 10px;">
      View All Shipments &rarr;
    </a>
  </div>
  <div class="gt-table-container" style="border: none; border-radius: 0;">
    <table class="gt-table" style="font-size: 0.76rem;">
      <thead>
        <tr>
          <th style="font-size: 0.65rem; padding: 8px 12px; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8;">Reference #</th>
          <th style="font-size: 0.65rem; padding: 8px 12px; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8;">Sender</th>
          <th style="font-size: 0.65rem; padding: 8px 12px; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8;">Recipient</th>
          <th style="font-size: 0.65rem; padding: 8px 12px; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8;">Delivery Mode</th>
          <th style="font-size: 0.65rem; padding: 8px 12px; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8;">Price</th>
          <th style="font-size: 0.65rem; padding: 8px 12px; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8;">Status</th>
          <th style="font-size: 0.65rem; padding: 8px 12px; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; text-align: right;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($recentParcels)): ?>
          <?php foreach ($recentParcels as $p): ?>
            <tr>
              <td style="padding: 8px 12px;">
                <a href="<?php echo APP_URL; ?>/admin/index.php?page=view_parcel&id=<?php echo $p['id']; ?>" style="font-weight: 700; color: #2563eb; text-decoration: none; font-size: 0.76rem;">
                  #<?php echo e($p['reference_number']); ?>
                </a>
              </td>
              <td style="padding: 8px 12px;"><?php echo e($p['sender_name']); ?></td>
              <td style="padding: 8px 12px;"><?php echo e($p['recipient_name']); ?></td>
              <td style="padding: 8px 12px;">
                <span class="gt-badge gt-badge-neutral" style="font-size: 0.68rem; padding: 2px 7px;">
                  <?php echo $p['type'] == 1 ? 'Doorstep Delivery' : 'Branch Pickup'; ?>
                </span>
              </td>
              <td style="padding: 8px 12px; font-weight: 600;">$<?php echo number_format((float)$p['price'], 2); ?></td>
              <td style="padding: 8px 12px;"><?php echo render_status_badge((int)$p['status']); ?></td>
              <td style="padding: 8px 12px; text-align: right;">
                <a href="<?php echo APP_URL; ?>/admin/index.php?page=view_parcel&id=<?php echo $p['id']; ?>" class="gt-btn gt-btn-secondary gt-btn-sm" style="font-size: 0.72rem; padding: 3px 8px;">
                  View
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" style="text-align: center; padding: 2rem;">
              No consignment records found.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
