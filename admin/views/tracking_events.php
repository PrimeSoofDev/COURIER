<?php
/**
 * GaaTiTrack Admin - Tracking Events & Milestone Management
 */

$pageTitle = 'Update Tracking';
$presetRef = trim($_GET['ref'] ?? '');
$selectedParcel = null;
$recentEvents   = [];

// Handle Event Submission
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_event') {
    $csrf      = $_POST['csrf_token'] ?? '';
    $parcelId  = (int)($_POST['parcel_id'] ?? 0);
    $newStatus = isset($_POST['status']) && $_POST['status'] !== '' ? (int)$_POST['status'] : null;

    if (!verify_csrf_token($csrf)) {
        $_SESSION['flash_error'] = 'Security validation failed (CSRF mismatch).';
    } elseif ($parcelId <= 0 || $newStatus === null || $newStatus < 0 || $newStatus > 9) {
        $_SESSION['flash_error'] = 'Please select a valid consignment and milestone status.';
    } else {
        try {
            $checkStmt = $pdo->prepare("SELECT id, reference_number, status, from_branch_id, to_branch_id FROM parcels WHERE id = :id");
            $checkStmt->execute([':id' => $parcelId]);
            $targetParcel = $checkStmt->fetch();

            if (!$targetParcel) {
                $_SESSION['flash_error'] = 'Consignment not found.';
            } else {
                $canUpdate = true;
                if (!is_admin() && user_branch_id() > 0) {
                    $uBid = (int)user_branch_id();
                    if ((int)$targetParcel['from_branch_id'] !== $uBid && (int)$targetParcel['to_branch_id'] !== $uBid) {
                        $canUpdate = false;
                        $_SESSION['flash_error'] = 'Access denied: Consignment not routed through your branch.';
                    }
                }
                if ($canUpdate) {
                    $pdo->beginTransaction();
                    $pdo->prepare("UPDATE parcels SET status = :status WHERE id = :id")->execute([':status' => $newStatus, ':id' => $parcelId]);
                    $pdo->prepare("INSERT INTO parcel_tracks (parcel_id, status, date_created) VALUES (:pid, :status, NOW())")->execute([':pid' => $parcelId, ':status' => $newStatus]);
                    $pdo->commit();
                    app_log("Consignment {$targetParcel['reference_number']} status → {$newStatus} by User " . current_user_id());
                    $_SESSION['flash_success'] = "Tracking milestone for #{$targetParcel['reference_number']} updated to \"" . status_label($newStatus) . "\" successfully.";

                    header("Location: " . APP_URL . "/admin/index.php?page=tracking_events&ref=" . urlencode($targetParcel['reference_number']));
                    exit;
                }
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            app_log("Failed to log tracking milestone: " . $e->getMessage(), 'ERROR');
            $_SESSION['flash_error'] = 'Database error while logging milestone.';
        }
    }
}

// Lookup parcel by reference
if (!empty($presetRef)) {
    try {
        $pStmt = $pdo->prepare("
            SELECT p.*, bf.city as from_city, bf.branch_code as from_code,
                   bt.city as to_city, bt.branch_code as to_code
            FROM parcels p
            LEFT JOIN branches bf ON p.from_branch_id = bf.id
            LEFT JOIN branches bt ON p.to_branch_id   = bt.id
            WHERE p.reference_number = :ref
        ");
        $pStmt->execute([':ref' => $presetRef]);
        $selectedParcel = $pStmt->fetch();

        if ($selectedParcel && !is_admin() && user_branch_id() > 0) {
            $uBid = (int)user_branch_id();
            if ((int)$selectedParcel['from_branch_id'] !== $uBid && (int)$selectedParcel['to_branch_id'] !== $uBid) {
                $selectedParcel = null;
            }
        }
    } catch (PDOException $e) {
        app_log("Error looking up parcel: " . $e->getMessage(), 'ERROR');
    }
}

// Fetch recent 10 events
try {
    $recentSql = "SELECT pt.*, p.reference_number, p.sender_name, p.recipient_name,
                         bf.city as from_city, bt.city as to_city
                  FROM parcel_tracks pt
                  JOIN parcels p ON pt.parcel_id = p.id
                  LEFT JOIN branches bf ON p.from_branch_id = bf.id
                  LEFT JOIN branches bt ON p.to_branch_id = bt.id";
    $recentParams = [];
    if (!is_admin() && user_branch_id() > 0) {
        $recentSql .= " WHERE (p.from_branch_id = :bid OR p.to_branch_id = :bid)";
        $recentParams[':bid'] = user_branch_id();
    }
    $recentSql .= " ORDER BY pt.date_created DESC, pt.id DESC LIMIT 10";
    $rStmt = $pdo->prepare($recentSql);
    $rStmt->execute($recentParams);
    $recentEvents = $rStmt->fetchAll();
} catch (PDOException $e) {}

$statusOptions = [
    0 => ['label' => 'Item Accepted',                'desc' => 'Intake at warehouse',                   'color' => '#64748b'],
    1 => ['label' => 'Collected / Picked Up',         'desc' => 'Picked up from origin facility',        'color' => '#3b82f6'],
    2 => ['label' => 'Shipped / In Transit',          'desc' => 'Moving between transfer hubs',          'color' => '#0ea5e9'],
    3 => ['label' => 'In Delivery Hub',               'desc' => 'At destination sorting center',         'color' => '#f59e0b'],
    4 => ['label' => 'Out for Delivery',              'desc' => 'With courier driver for final delivery', 'color' => '#f97316'],
    5 => ['label' => 'Ready for Pickup',              'desc' => 'Available at destination hub',           'color' => '#8b5cf6'],
    6 => ['label' => 'Delivery In Transit',           'desc' => 'En route to recipient address',          'color' => '#ec4899'],
    7 => ['label' => 'Delivered',                     'desc' => 'Successfully delivered and signed',      'color' => '#22c55e'],
    8 => ['label' => 'Picked-up by Consignee',        'desc' => 'Customer collected from branch',         'color' => '#16a34a'],
    9 => ['label' => 'Delivery Attempt Unsuccessful', 'desc' => 'Failed delivery — reschedule required',  'color' => '#dc2626'],
];

require_once __DIR__ . '/../header.php';
?>

<style>
  .track-page { display:grid;grid-template-columns:380px 1fr;gap:1.75rem;align-items:start; }
  @media (max-width:900px) { .track-page { grid-template-columns:1fr; } }

  .track-panel {
    background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;
  }
  .track-panel-header {
    display:flex;align-items:center;gap:10px;
    padding:14px 18px;border-bottom:1px solid #f1f5f9;background:#fafbfd;
  }
  .track-panel-num {
    width:26px;height:26px;border-radius:50%;
    background:linear-gradient(135deg,#1d4ed8,#2563eb);
    display:flex;align-items:center;justify-content:center;
    font-size:.75rem;font-weight:800;color:#fff;flex-shrink:0;
  }
  .track-panel-title { font-size:.9rem;font-weight:700;color:#0f172a; }
  .track-panel-body  { padding:18px; }

  .ref-search-row { display:flex;gap:8px; }
  .ref-input {
    flex:1;padding:10px 14px;font-family:monospace;
    border:1.5px solid #e5e7eb;border-radius:9px;font-size:.9rem;
    outline:none;transition:border-color .18s,box-shadow .18s;color:#111;
  }
  .ref-input:focus { border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.1); }
  .ref-search-btn {
    padding:10px 18px;border-radius:9px;background:#0f172a;color:#fff;
    font-size:.84rem;font-weight:700;border:none;cursor:pointer;
    white-space:nowrap;transition:opacity .18s;
  }
  .ref-search-btn:hover { opacity:.85; }

  .parcel-found-card {
    background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px;margin-top:14px;
  }
  .parcel-found-ref { font-family:monospace;font-size:1.05rem;font-weight:800;color:#2563eb; }
  .parcel-found-row { font-size:.82rem;color:#475569;margin-top:5px; }
  .parcel-found-row strong { color:#111827; }
  .parcel-meta-row {
    display:flex;justify-content:space-between;align-items:center;
    border-top:1px solid #e2e8f0;padding-top:10px;margin-top:10px;
  }
  .parcel-meta-row a { font-size:.78rem;color:#2563eb;font-weight:700;text-decoration:none; }

  .status-grid { display:flex;flex-direction:column;gap:6px;margin-top:2px; }
  .status-option {
    display:flex;align-items:center;gap:10px;
    padding:10px 12px;border-radius:9px;border:1.5px solid #e5e7eb;
    cursor:pointer;transition:border-color .15s,background .15s;
    position:relative;
  }
  .status-option:has(input:checked) { border-color:#3b82f6;background:#eff6ff; }
  .status-option input[type=radio] { position:absolute;opacity:0;pointer-events:none; }
  .status-dot-big { width:10px;height:10px;border-radius:50%;flex-shrink:0; }
  .status-opt-label { font-size:.83rem;font-weight:600;color:#111827; }
  .status-opt-desc  { font-size:.72rem;color:#94a3b8;margin-top:1px; }

  .commit-btn {
    width:100%;padding:11px;border-radius:9px;
    background:linear-gradient(135deg,#1d4ed8,#2563eb);
    color:#fff;font-size:.875rem;font-weight:700;border:none;cursor:pointer;
    margin-top:14px;box-shadow:0 2px 8px rgba(37,99,235,.3);
    transition:opacity .18s,transform .18s;
    display:flex;align-items:center;justify-content:center;gap:8px;
  }
  .commit-btn:hover { opacity:.9;transform:translateY(-1px); }
  .commit-btn:disabled { opacity:.4;cursor:not-allowed;transform:none; }

  .empty-hint {
    text-align:center;padding:2rem 1rem;
    border:2px dashed #e2e8f0;border-radius:10px;
    color:#94a3b8;font-size:.84rem;line-height:1.6;
  }

  .log-table { width:100%;border-collapse:collapse; }
  .log-table th {
    padding:8px 10px;text-align:left;font-size:.63rem;font-weight:700;
    text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;
    background:#f8fafc;border-bottom:1px solid #f1f5f9;white-space:nowrap;
  }
  .log-table td {
    padding:9px 10px;border-bottom:1px solid #f8fafc;
    font-size:.75rem;color:#374151;vertical-align:middle;
  }
  .log-table tr:last-child td { border-bottom:none; }
  .log-table tbody tr:hover { background:#fafbff; }

  .log-ref { font-family:monospace;font-weight:700;color:#2563eb;text-decoration:none;font-size:.78rem;white-space:nowrap; }
  .log-ref:hover { text-decoration:underline; }
  .log-time { font-size:.7rem;color:#94a3b8;white-space:nowrap; }
  .log-route { font-size:.72rem;color:#64748b;white-space:nowrap; }

  .milestone-pill {
    display:inline-flex;align-items:center;gap:4px;
    padding:2px 8px;border-radius:99px;font-size:.65rem;font-weight:700;
    white-space:nowrap;
  }
  .milestone-dot { width:5px;height:5px;border-radius:50%;background:currentColor;opacity:.7; }
</style>

<!-- Page Header -->
<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:1.75rem;flex-wrap:wrap;gap:1rem;">
  <div>
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
      <div style="width:32px;height:32px;background:linear-gradient(135deg,#f59e0b,#ef4444);border-radius:8px;display:flex;align-items:center;justify-content:center;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      </div>
      <h1 style="font-size:1.5rem;font-weight:800;color:#0f172a;margin:0;">Update Tracking</h1>
    </div>
    <p style="margin:0;font-size:.875rem;color:#64748b;">Log milestone status updates and delivery checkpoints with atomic database auditing.</p>
  </div>
  <a href="<?php echo APP_URL; ?>/admin/index.php?page=parcels"
     style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border:1.5px solid #e5e7eb;border-radius:9px;color:#374151;font-size:.84rem;font-weight:600;text-decoration:none;background:#fff;transition:background .15s;">
    ← All Shipments
  </a>
</div>

<div class="track-page">

  <!-- Left column -->
  <div style="display:flex;flex-direction:column;gap:1.25rem;">

    <!-- Step 1: Find -->
    <div class="track-panel">
      <div class="track-panel-header">
        <div class="track-panel-num">1</div>
        <div class="track-panel-title">Find Consignment</div>
      </div>
      <div class="track-panel-body">
        <form method="GET" action="<?php echo APP_URL; ?>/admin/index.php">
          <input type="hidden" name="page" value="tracking_events">
          <div class="ref-search-row">
            <input type="text" id="ref-input" name="ref" class="ref-input"
                   placeholder="12-digit reference #"
                   value="<?php echo e($presetRef); ?>"
                   pattern="[a-zA-Z0-9\-_]{6,30}" required autocomplete="off">
            <button type="submit" class="ref-search-btn">Lookup</button>
          </div>
        </form>

        <?php if ($selectedParcel): ?>
          <div class="parcel-found-card">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:6px;">
              <div class="parcel-found-ref">#<?php echo e($selectedParcel['reference_number']); ?></div>
              <span class="milestone-pill" style="background:#dcfce7;color:#15803d;">
                <span class="milestone-dot"></span>
                <?php echo e(status_label((int)$selectedParcel['status'])); ?>
              </span>
            </div>
            <div class="parcel-found-row"><strong>Sender:</strong> <?php echo e($selectedParcel['sender_name']); ?> — <?php echo e($selectedParcel['from_city'] ?? 'Hub'); ?></div>
            <div class="parcel-found-row"><strong>Recipient:</strong> <?php echo e($selectedParcel['recipient_name']); ?> — <?php echo e($selectedParcel['to_city'] ?? 'Hub'); ?></div>
            <div class="parcel-meta-row">
              <span style="font-size:.75rem;color:#94a3b8;">Booked <?php echo date('M d, Y', strtotime($selectedParcel['date_created'])); ?></span>
              <a href="<?php echo APP_URL; ?>/admin/index.php?page=view_parcel&id=<?php echo $selectedParcel['id']; ?>" target="_blank">View Waybill ↗</a>
            </div>
          </div>
        <?php elseif (!empty($presetRef)): ?>
          <div style="margin-top:12px;padding:12px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;font-size:.82rem;color:#dc2626;">
            No consignment found matching "<strong><?php echo e($presetRef); ?></strong>".
          </div>
        <?php else: ?>
          <div class="empty-hint" style="margin-top:12px;">
            Enter a reference number above, or click<br>
            <strong>"Update Tracking"</strong> on any shipment.
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Step 2: Record milestone -->
    <div class="track-panel">
      <div class="track-panel-header">
        <div class="track-panel-num">2</div>
        <div class="track-panel-title">Record Milestone</div>
      </div>
      <div class="track-panel-body">
        <?php if (!$selectedParcel): ?>
          <div class="empty-hint">Load a consignment first to record a new tracking milestone.</div>
        <?php else: ?>
          <form method="POST" action="<?php echo APP_URL; ?>/admin/index.php?page=tracking_events" id="milestone-form">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="action" value="add_event">
            <input type="hidden" name="parcel_id" value="<?php echo $selectedParcel['id']; ?>">

            <div style="font-size:.8rem;font-weight:600;color:#374151;margin-bottom:8px;">Select New Status</div>
            <div class="status-grid">
              <?php foreach ($statusOptions as $val => $opt): ?>
                <label class="status-option">
                  <input type="radio" name="status" value="<?php echo $val; ?>"
                    <?php echo (int)$selectedParcel['status'] === $val ? 'checked' : ''; ?>>
                  <span class="status-dot-big" style="background:<?php echo $opt['color']; ?>;"></span>
                  <div>
                    <div class="status-opt-label"><?php echo e($opt['label']); ?></div>
                    <div class="status-opt-desc"><?php echo e($opt['desc']); ?></div>
                  </div>
                </label>
              <?php endforeach; ?>
            </div>

            <button type="submit" class="commit-btn">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
              Commit Milestone Update
            </button>
          </form>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- /left -->

  <!-- Right: Audit log -->
  <div class="track-panel" style="overflow:hidden;">
    <div class="track-panel-header">
      <div style="display:flex;align-items:center;gap:8px;flex:1;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        <span class="track-panel-title">Recent Milestone Activity</span>
      </div>
      <span style="font-size:.72rem;color:#94a3b8;">Last 10 events</span>
    </div>

    <?php if (empty($recentEvents)): ?>
      <div class="empty-hint" style="margin:1.5rem;">No recent milestone transitions found.</div>
    <?php else: ?>
    <div style="overflow-x:auto;">
      <table class="log-table">
        <thead>
          <tr>
            <th>Time</th>
            <th>Reference</th>
            <th>Route</th>
            <th>Milestone</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentEvents as $ev):
            $sc = $statusOptions[(int)$ev['status']] ?? $statusOptions[0];
          ?>
            <tr>
              <td class="log-time"><?php echo date('M d · h:i A', strtotime($ev['date_created'])); ?></td>
              <td>
                <a href="<?php echo APP_URL; ?>/admin/index.php?page=tracking_events&ref=<?php echo urlencode($ev['reference_number']); ?>"
                   class="log-ref">#<?php echo e($ev['reference_number']); ?></a>
              </td>
              <td class="log-route">
                <?php echo e($ev['from_city'] ?? '—'); ?> → <?php echo e($ev['to_city'] ?? '—'); ?>
              </td>
              <td>
                <span class="milestone-pill" style="background:<?php echo $sc['color']; ?>22;color:<?php echo $sc['color']; ?>;">
                  <span class="milestone-dot"></span>
                  <?php echo e($sc['label']); ?>
                </span>
              </td>
              <td>
                <a href="<?php echo APP_URL; ?>/admin/index.php?page=view_parcel&id=<?php echo $ev['parcel_id']; ?>"
                   style="font-size:.75rem;color:#2563eb;font-weight:600;text-decoration:none;">View →</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

</div><!-- /track-page -->

<?php require_once __DIR__ . '/../footer.php'; ?>
