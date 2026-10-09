<?php
/**
 * GaaTiTrack Admin - Consignments Listing View
 */

$pageTitle = 'All Shipments';

// Handle Delete
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_parcel') {
    $delId = (int)($_POST['id'] ?? 0);
    $csrf  = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $_SESSION['flash_error'] = 'Security validation failed.';
    } elseif ($delId <= 0) {
        $_SESSION['flash_error'] = 'Invalid consignment ID.';
    } else {
        try {
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM parcel_tracks WHERE parcel_id = :id")->execute([':id' => $delId]);
            $pdo->prepare("DELETE FROM parcels WHERE id = :id")->execute([':id' => $delId]);
            $pdo->commit();
            app_log("Deleted consignment ID: {$delId} by User ID: " . current_user_id(), 'WARNING');
            $_SESSION['flash_success'] = 'Consignment and tracking history deleted.';
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['flash_error'] = 'Database error while deleting.';
        }
    }
    header("Location: " . APP_URL . "/admin/index.php?page=parcels");
    exit;
}

require_once __DIR__ . '/../header.php';

$searchQuery  = trim($_GET['q'] ?? '');
$statusFilter = isset($_GET['s']) && $_GET['s'] !== '' ? (int)$_GET['s'] : null;
$pageNumber   = max(1, (int)($_GET['p'] ?? 1));
$perPage      = 15;
$offset       = ($pageNumber - 1) * $perPage;

$whereClauses = [];
$params = [];

if (!is_admin() && user_branch_id() > 0) {
    $whereClauses[] = "(p.from_branch_id = :user_bid OR p.to_branch_id = :user_bid)";
    $params[':user_bid'] = user_branch_id();
}
if (!empty($searchQuery)) {
    $whereClauses[] = "(p.reference_number LIKE :search OR p.sender_name LIKE :search OR p.recipient_name LIKE :search)";
    $params[':search'] = '%' . $searchQuery . '%';
}
if ($statusFilter !== null && $statusFilter >= 0 && $statusFilter <= 9) {
    $whereClauses[] = "p.status = :status";
    $params[':status'] = $statusFilter;
}

$whereSql = !empty($whereClauses) ? ' WHERE ' . implode(' AND ', $whereClauses) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM parcels p" . $whereSql);
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();
$totalPages   = max(1, ceil($totalRecords / $perPage));

$dataSql = "SELECT p.*, b_from.city as from_city, b_to.city as to_city
    FROM parcels p
    LEFT JOIN branches b_from ON b_from.id = p.from_branch_id
    LEFT JOIN branches b_to   ON b_to.id   = p.to_branch_id
    {$whereSql} ORDER BY p.date_created DESC
    LIMIT " . (int)$perPage . " OFFSET " . (int)$offset;

$dataStmt = $pdo->prepare($dataSql);
$dataStmt->execute($params);
$parcels = $dataStmt->fetchAll();

// Status colours map for inline badges
$statusColors = [
    0 => ['bg' => '#f1f5f9', 'text' => '#475569'],
    1 => ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
    2 => ['bg' => '#e0f2fe', 'text' => '#0369a1'],
    3 => ['bg' => '#fef9c3', 'text' => '#854d0e'],
    4 => ['bg' => '#fef3c7', 'text' => '#92400e'],
    5 => ['bg' => '#fed7aa', 'text' => '#c2410c'],
    6 => ['bg' => '#fce7f3', 'text' => '#9d174d'],
    7 => ['bg' => '#dcfce7', 'text' => '#15803d'],
    8 => ['bg' => '#fee2e2', 'text' => '#dc2626'],
    9 => ['bg' => '#f3e8ff', 'text' => '#7e22ce'],
];
?>

<style>
  .page-header { display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:1.75rem;flex-wrap:wrap;gap:1rem; }
  .page-title-group h1 { font-size:1.5rem;font-weight:800;color:#0f172a;margin:0 0 4px; }
  .page-title-group p  { margin:0;font-size:.875rem;color:#64748b; }

  .filter-bar {
    background:#fff;border:1px solid #e2e8f0;border-radius:12px;
    padding:14px 16px;margin-bottom:1.5rem;
    display:flex;align-items:center;gap:10px;flex-wrap:wrap;
  }
  .filter-input {
    flex:1;min-width:220px;padding:9px 14px;
    border:1.5px solid #e5e7eb;border-radius:8px;font-size:.84rem;
    color:#111;outline:none;transition:border-color .18s,box-shadow .18s;
    font-family:inherit;
  }
  .filter-input:focus { border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.1); }
  .filter-select {
    padding:9px 32px 9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;
    font-size:.84rem;color:#374151;outline:none;background:#fff;
    appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
    background-repeat:no-repeat;background-position:right 8px center;
    cursor:pointer;font-family:inherit;
  }
  .filter-btn {
    padding:9px 20px;border-radius:8px;background:#0f172a;color:#fff;
    font-size:.84rem;font-weight:600;border:none;cursor:pointer;
    transition:opacity .18s;
  }
  .filter-btn:hover { opacity:.85; }
  .reset-link {
    padding:9px 16px;border-radius:8px;border:1.5px solid #e5e7eb;
    background:#fff;color:#64748b;font-size:.84rem;font-weight:500;
    text-decoration:none;transition:border-color .15s,color .15s;
  }
  .reset-link:hover { border-color:#94a3b8;color:#374151; }

  .shipments-card {
    background:#fff;border:1px solid #e2e8f0;border-radius:14px;
    overflow:hidden;margin-bottom:1.5rem;
  }
  .shipments-card-header {
    display:flex;align-items:center;justify-content:space-between;
    padding:14px 20px;border-bottom:1px solid #f1f5f9;
    background:#fafbfd;
  }
  .ship-table { width:100%;border-collapse:collapse; }
  .ship-table th {
    padding:9px 12px;text-align:left;font-size:.62rem;font-weight:700;
    text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;
    background:#f8fafc;border-bottom:1px solid #f1f5f9;white-space:nowrap;
  }
  .ship-table td {
    padding:10px 12px;border-bottom:1px solid #f8fafc;
    font-size:.76rem;color:#374151;vertical-align:middle;
  }
  .ship-table tr:last-child td { border-bottom:none; }
  .ship-table tbody tr { transition:background .12s; }
  .ship-table tbody tr:hover { background:#fafbff; }

  .ref-link {
    font-weight:700;color:#2563eb;text-decoration:none;
    font-family:monospace;font-size:.78rem;white-space:nowrap;
  }
  .ref-link:hover { color:#1d4ed8;text-decoration:underline; }
  .ref-date { font-size:.68rem;color:#94a3b8;margin-top:2px;white-space:nowrap; }

  .person-name { font-weight:600;color:#111827;font-size:.76rem;white-space:nowrap; }
  .person-sub  { font-size:.68rem;color:#94a3b8;margin-top:2px;white-space:nowrap; }

  .route-chip {
    display:inline-flex;align-items:center;gap:4px;
    font-size:.73rem;font-weight:600;color:#475569;white-space:nowrap;
  }
  .route-arrow { color:#cbd5e1;font-size:.85rem; }

  .type-badge {
    display:inline-flex;padding:2px 7px;border-radius:99px;
    font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;
    white-space:nowrap;
  }
  .type-doorstep { background:#dbeafe;color:#1d4ed8; }
  .type-pickup   { background:#dcfce7;color:#15803d; }

  .price-cell { font-weight:700;color:#111827;font-size:.82rem;white-space:nowrap; }
  .price-symbol { font-size:.72rem;color:#64748b; }

  .status-pill {
    display:inline-flex;align-items:center;gap:4px;
    padding:3px 8px;border-radius:99px;font-size:.65rem;font-weight:700;
    white-space:nowrap;
  }
  .status-dot { width:5px;height:5px;border-radius:50%;background:currentColor;opacity:.7; }

  .action-group { display:inline-flex;gap:4px;align-items:center; }
  .act-btn {
    padding:4px 9px;border-radius:6px;font-size:.7rem;font-weight:600;
    text-decoration:none;border:1.5px solid #e5e7eb;color:#374151;background:#fff;
    cursor:pointer;transition:background .15s,border-color .15s;display:inline-flex;align-items:center;gap:3px;
    white-space:nowrap;
  }
  .act-btn:hover { background:#f1f5f9;border-color:#cbd5e1; }
  .act-btn.danger { color:#dc2626;border-color:#fca5a5; }
  .act-btn.danger:hover { background:#fef2f2;border-color:#ef4444; }

  .empty-state {
    text-align:center;padding:4rem 2rem;
  }
  .empty-icon {
    width:60px;height:60px;background:#f1f5f9;border-radius:14px;
    display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;
  }
  .empty-state h3 { font-size:1rem;font-weight:700;color:#374151;margin:0 0 6px; }
  .empty-state p  { font-size:.84rem;color:#94a3b8;margin:0 0 1.25rem; }

  .pagination-bar {
    display:flex;justify-content:space-between;align-items:center;
    padding:14px 20px;border-top:1px solid #f1f5f9;flex-wrap:wrap;gap:10px;
  }
  .pag-info { font-size:.8rem;color:#94a3b8; }
  .pag-btn {
    display:inline-flex;align-items:center;gap:6px;
    padding:7px 15px;border-radius:8px;border:1.5px solid #e5e7eb;
    background:#fff;color:#374151;font-size:.82rem;font-weight:600;
    text-decoration:none;transition:background .15s,border-color .15s;
  }
  .pag-btn:hover { background:#f1f5f9;border-color:#cbd5e1; }

  .primary-btn {
    display:inline-flex;align-items:center;gap:7px;
    padding:9px 18px;border-radius:9px;
    background:linear-gradient(135deg,#1d4ed8,#2563eb);
    color:#fff;font-size:.84rem;font-weight:700;text-decoration:none;
    box-shadow:0 2px 8px rgba(37,99,235,.3);
    transition:opacity .18s,transform .18s;
    border:none;cursor:pointer;
  }
  .primary-btn:hover { opacity:.9;transform:translateY(-1px); }
</style>

<!-- Page Header -->
<div class="page-header">
  <div class="page-title-group">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
      <div style="width:32px;height:32px;background:linear-gradient(135deg,#3b82f6,#06b6d4);border-radius:8px;display:flex;align-items:center;justify-content:center;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
      </div>
      <h1 style="font-size:1.5rem;font-weight:800;color:#0f172a;margin:0;">All Shipments</h1>
    </div>
    <p style="margin:0;font-size:.875rem;color:#64748b;">
      <?php echo number_format($totalRecords); ?> consignment<?php echo $totalRecords !== 1 ? 's' : ''; ?> found
      <?php if (!empty($searchQuery) || $statusFilter !== null): ?>
        <span style="color:#94a3b8;">· filtered</span>
      <?php endif; ?>
    </p>
  </div>
  <a href="<?php echo APP_URL; ?>/admin/index.php?page=new_parcel" class="primary-btn">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Book New Parcel
  </a>
</div>

<!-- Filters -->
<form action="<?php echo APP_URL; ?>/admin/index.php" method="GET" class="filter-bar">
  <input type="hidden" name="page" value="parcels">
  <input type="search" name="q" class="filter-input"
         placeholder="Search reference #, sender, or recipient..."
         value="<?php echo e($searchQuery); ?>">

  <select name="s" class="filter-select">
    <option value="">All Statuses</option>
    <?php foreach (get_status_dictionary() as $sCode => $sLabel): ?>
      <option value="<?php echo $sCode; ?>" <?php echo ($statusFilter !== null && $statusFilter === $sCode) ? 'selected' : ''; ?>>
        <?php echo e($sLabel); ?>
      </option>
    <?php endforeach; ?>
  </select>

  <button type="submit" class="filter-btn">Filter</button>
  <?php if (!empty($searchQuery) || $statusFilter !== null): ?>
    <a href="<?php echo APP_URL; ?>/admin/index.php?page=parcels" class="reset-link">Clear</a>
  <?php endif; ?>
</form>

<!-- Table -->
<div class="shipments-card">
  <div class="shipments-card-header">
    <div style="font-size:.84rem;font-weight:600;color:#374151;">
      Consignment Records
      <?php if ($totalPages > 1): ?>
        <span style="font-weight:400;color:#94a3b8;margin-left:6px;">Page <?php echo $pageNumber; ?> / <?php echo $totalPages; ?></span>
      <?php endif; ?>
    </div>
    <div style="font-size:.75rem;color:#94a3b8;"><?php echo $totalRecords; ?> total</div>
  </div>

  <?php if (!empty($parcels)): ?>
  <div style="overflow-x:auto;">
    <table class="ship-table">
      <thead>
        <tr>
          <th>Reference</th>
          <th>Sender</th>
          <th>Recipient</th>
          <th>Route</th>
          <th>Mode</th>
          <th>Price</th>
          <th>Status</th>
          <th style="text-align:right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($parcels as $p): ?>
          <?php
            $sc = $statusColors[(int)$p['status']] ?? $statusColors[0];
            $fromCity = e($p['from_city'] ?: 'Hub #' . $p['from_branch_id']);
            $toCity   = e($p['type'] == 1 ? ($p['to_city'] ?: 'Doorstep') : ($p['to_city'] ?: 'Hub #' . $p['to_branch_id']));
          ?>
          <tr>
            <td>
              <a href="<?php echo APP_URL; ?>/admin/index.php?page=view_parcel&id=<?php echo $p['id']; ?>"
                 class="ref-link">#<?php echo e($p['reference_number']); ?></a>
              <div class="ref-date"><?php echo date('M d, Y', strtotime($p['date_created'])); ?></div>
            </td>
            <td>
              <div class="person-name"><?php echo e($p['sender_name']); ?></div>
              <div class="person-sub"><?php echo e($p['sender_contact']); ?></div>
            </td>
            <td>
              <div class="person-name"><?php echo e($p['recipient_name']); ?></div>
              <div class="person-sub"><?php echo e($p['recipient_contact']); ?></div>
            </td>
            <td>
              <div class="route-chip">
                <?php echo $fromCity; ?>
                <span class="route-arrow">→</span>
                <?php echo $toCity; ?>
              </div>
            </td>
            <td>
              <?php if ($p['type'] == 1): ?>
                <span class="type-badge type-doorstep">Doorstep</span>
              <?php else: ?>
                <span class="type-badge type-pickup">Pickup</span>
              <?php endif; ?>
            </td>
            <td class="price-cell">
              <span class="price-symbol">$</span><?php echo number_format((float)$p['price'], 2); ?>
            </td>
            <td>
              <span class="status-pill" style="background:<?php echo $sc['bg']; ?>;color:<?php echo $sc['text']; ?>;">
                <span class="status-dot"></span>
                <?php echo e(status_label((int)$p['status'])); ?>
              </span>
            </td>
            <td style="text-align:right;">
              <div class="action-group">
                <a href="<?php echo APP_URL; ?>/admin/index.php?page=view_parcel&id=<?php echo $p['id']; ?>" class="act-btn" title="View details">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  View
                </a>
                <a href="<?php echo APP_URL; ?>/admin/index.php?page=edit_parcel&id=<?php echo $p['id']; ?>" class="act-btn" title="Edit">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  Edit
                </a>
                <form method="POST" action="<?php echo APP_URL; ?>/admin/index.php?page=parcels" style="display:inline;"
                      onsubmit="return confirm('Delete this consignment and all tracking history? This cannot be undone.');">
                  <input type="hidden" name="action" value="delete_parcel">
                  <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                  <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                  <button type="submit" class="act-btn danger" title="Delete">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                    Del
                  </button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
    <div class="empty-state">
      <div class="empty-icon">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
      </div>
      <h3>No shipments found</h3>
      <p>No consignments match your current search or filter criteria.</p>
      <a href="<?php echo APP_URL; ?>/admin/index.php?page=new_parcel" class="primary-btn" style="display:inline-flex;">
        Book a Shipment
      </a>
    </div>
  <?php endif; ?>

  <?php if ($totalPages > 1): ?>
    <div class="pagination-bar">
      <div class="pag-info">
        Showing <?php echo (($pageNumber - 1) * $perPage) + 1; ?>–<?php echo min($pageNumber * $perPage, $totalRecords); ?> of <?php echo $totalRecords; ?> shipments
      </div>
      <div style="display:flex;gap:6px;">
        <?php if ($pageNumber > 1): ?>
          <a href="<?php echo APP_URL; ?>/admin/index.php?page=parcels&p=<?php echo $pageNumber-1; ?><?php echo !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : ''; ?><?php echo $statusFilter !== null ? '&s=' . $statusFilter : ''; ?>" class="pag-btn">
            ← Previous
          </a>
        <?php endif; ?>
        <?php if ($pageNumber < $totalPages): ?>
          <a href="<?php echo APP_URL; ?>/admin/index.php?page=parcels&p=<?php echo $pageNumber+1; ?><?php echo !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : ''; ?><?php echo $statusFilter !== null ? '&s=' . $statusFilter : ''; ?>" class="pag-btn">
            Next →
          </a>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
