<?php
/**
 * GaaTiTrack Admin - Freight Reports & Manifest Generator
 */

$pageTitle = 'Reports & Manifests';
require_once __DIR__ . '/../header.php';

$dateFrom     = trim($_GET['date_from'] ?? date('Y-m-01'));
$dateTo       = trim($_GET['date_to']   ?? date('Y-m-d'));
$statusFilter = isset($_GET['status']) && $_GET['status'] !== '' ? (int)$_GET['status'] : null;
$branchFilter = isset($_GET['branch_id']) && $_GET['branch_id'] !== '' ? (int)$_GET['branch_id'] : null;

if (!is_admin() && user_branch_id() > 0) {
    $branchFilter = (int)user_branch_id();
}

$branches = [];
try {
    $bStmt = $pdo->query("SELECT id, city, branch_code FROM branches ORDER BY city ASC");
    $branches = $bStmt->fetchAll();
} catch (PDOException $e) {}

$where  = [];
$params = [];
if (!empty($dateFrom)) { $where[] = "DATE(p.date_created) >= :date_from"; $params[':date_from'] = $dateFrom; }
if (!empty($dateTo))   { $where[] = "DATE(p.date_created) <= :date_to";   $params[':date_to']   = $dateTo;   }
if ($statusFilter !== null && $statusFilter >= 0 && $statusFilter <= 9) {
    $where[] = "p.status = :status"; $params[':status'] = $statusFilter;
}
if ($branchFilter !== null && $branchFilter > 0) {
    $where[] = "(p.from_branch_id = :bid OR p.to_branch_id = :bid)"; $params[':bid'] = $branchFilter;
}

$whereSql     = !empty($where) ? ' WHERE ' . implode(' AND ', $where) : '';
$reportData   = [];
$totalParcels = $totalRevenue = $deliveredCount = $inTransitCount = 0;

try {
    $repStmt = $pdo->prepare("
        SELECT p.*, bf.city as from_city, bf.branch_code as from_code,
               bt.city as to_city, bt.branch_code as to_code
        FROM parcels p
        LEFT JOIN branches bf ON p.from_branch_id = bf.id
        LEFT JOIN branches bt ON p.to_branch_id = bt.id
        {$whereSql} ORDER BY p.date_created DESC
    ");
    $repStmt->execute($params);
    $reportData   = $repStmt->fetchAll();
    $totalParcels = count($reportData);
    foreach ($reportData as $row) {
        $totalRevenue += (float)$row['price'];
        $s = (int)$row['status'];
        if ($s === 7 || $s === 8) $deliveredCount++;
        elseif ($s >= 1 && $s <= 6) $inTransitCount++;
    }
} catch (PDOException $e) {
    app_log("Report error: " . $e->getMessage(), 'ERROR');
}

$deliveryRate = $totalParcels > 0 ? round(($deliveredCount / $totalParcels) * 100) : 0;
?>

<style>
@media print {
  body * { visibility:hidden; }
  .print-area, .print-area * { visibility:visible; }
  .print-area { position:absolute;left:0;top:0;width:100%;padding:20px;background:#fff; }
  .no-print { display:none !important; }
}

.report-filter-bar {
  background:#fff;border:1px solid #e2e8f0;border-radius:12px;
  padding:16px 18px;margin-bottom:1.5rem;
}
.report-filter-grid {
  display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;align-items:end;
}
.rfield-label { font-size:.76rem;font-weight:700;color:#374151;display:block;margin-bottom:5px; }
.rfield-input {
  width:100%;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;
  font-size:.84rem;color:#111;outline:none;
  transition:border-color .18s,box-shadow .18s;background:#fff;font-family:inherit;
}
.rfield-input:focus { border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.1); }
select.rfield-input {
  appearance:none;
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
  background-repeat:no-repeat;background-position:right 8px center;padding-right:30px;cursor:pointer;
}
.filter-actions { display:flex;gap:8px;align-items:flex-end; }
.apply-btn {
  flex:1;padding:9px 14px;border-radius:8px;background:#0f172a;color:#fff;
  font-size:.84rem;font-weight:700;border:none;cursor:pointer;transition:opacity .18s;white-space:nowrap;
}
.apply-btn:hover { opacity:.85; }
.reset-link {
  padding:9px 12px;border-radius:8px;border:1.5px solid #e5e7eb;background:#fff;
  color:#64748b;font-size:.84rem;font-weight:600;text-decoration:none;white-space:nowrap;
  display:inline-flex;align-items:center;
}

.kpi-grid { display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem; }
@media (max-width:800px) { .kpi-grid { grid-template-columns:repeat(2,1fr); } }
@media (max-width:440px) { .kpi-grid { grid-template-columns:1fr; } }

.kpi-card {
  background:#fff;border:1px solid #e2e8f0;border-radius:12px;
  padding:16px 18px;position:relative;overflow:hidden;
}
.kpi-card::before {
  content:'';position:absolute;top:0;left:0;right:0;height:3px;border-radius:12px 12px 0 0;
}
.kpi-card.blue::before  { background:linear-gradient(90deg,#3b82f6,#06b6d4); }
.kpi-card.green::before { background:linear-gradient(90deg,#22c55e,#16a34a); }
.kpi-card.amber::before { background:linear-gradient(90deg,#f59e0b,#ef4444); }
.kpi-card.indigo::before{ background:linear-gradient(90deg,#6366f1,#8b5cf6); }
.kpi-label { font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin-bottom:6px; }
.kpi-value { font-size:1.85rem;font-weight:800;color:#0f172a;line-height:1; }
.kpi-sub   { font-size:.75rem;color:#94a3b8;margin-top:4px; }

.manifest-card {
  background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;
}
.manifest-header {
  display:flex;align-items:center;justify-content:space-between;
  padding:14px 20px;border-bottom:1px solid #f1f5f9;background:#fafbfd;flex-wrap:wrap;gap:8px;
}
.print-btn {
  display:inline-flex;align-items:center;gap:6px;
  padding:8px 16px;border-radius:8px;border:1.5px solid #e5e7eb;
  background:#fff;color:#374151;font-size:.82rem;font-weight:600;cursor:pointer;
  transition:background .15s,border-color .15s;
}
.print-btn:hover { background:#f8fafc;border-color:#cbd5e1; }

.manifest-table { width:100%;border-collapse:collapse; }
.manifest-table th {
  padding:10px 14px;text-align:left;font-size:.68rem;font-weight:700;
  text-transform:uppercase;letter-spacing:.07em;color:#94a3b8;
  background:#f8fafc;border-bottom:1px solid #f1f5f9;white-space:nowrap;
}
.manifest-table td {
  padding:11px 14px;border-bottom:1px solid #f8fafc;font-size:.83rem;color:#374151;vertical-align:middle;
}
.manifest-table tr:last-child td { border-bottom:none; }
.manifest-table tbody tr:hover   { background:#fafbff; }

.mref { font-family:monospace;font-weight:700;color:#2563eb;text-decoration:none;font-size:.88rem; }
.mref:hover { text-decoration:underline; }
.mdate { font-size:.75rem;color:#94a3b8; }
.mname { font-weight:600;font-size:.84rem;color:#111827; }
.mcity { font-size:.72rem;color:#94a3b8; }
.mprice { font-weight:700;color:#111827;font-size:.9rem; }
.mprice-sym { font-size:.75rem;color:#64748b; }

.type-d { display:inline-flex;padding:2px 8px;border-radius:99px;font-size:.68rem;font-weight:700;background:#dbeafe;color:#1d4ed8; }
.type-p { display:inline-flex;padding:2px 8px;border-radius:99px;font-size:.68rem;font-weight:700;background:#dcfce7;color:#15803d; }

.total-row td { background:#f1f5f9;font-weight:800;color:#0f172a;padding:13px 14px; }

.manifest-empty {
  text-align:center;padding:4rem 2rem;
}
.manifest-empty-icon {
  width:56px;height:56px;background:#f1f5f9;border-radius:12px;
  display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;
}
</style>

<!-- Page Header -->
<div class="no-print" style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:1.75rem;flex-wrap:wrap;gap:1rem;">
  <div>
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
      <div style="width:32px;height:32px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:8px;display:flex;align-items:center;justify-content:center;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
      </div>
      <h1 style="font-size:1.5rem;font-weight:800;color:#0f172a;margin:0;">Reports & Manifests</h1>
    </div>
    <p style="margin:0;font-size:.875rem;color:#64748b;">
      Audit shipment throughput, delivery milestones, and branch revenue for
      <strong><?php echo date('M d', strtotime($dateFrom)); ?></strong> –
      <strong><?php echo date('M d, Y', strtotime($dateTo)); ?></strong>
    </p>
  </div>
</div>

<!-- Filters -->
<div class="report-filter-bar no-print">
  <form method="GET" action="<?php echo APP_URL; ?>/admin/index.php" class="report-filter-grid">
    <input type="hidden" name="page" value="reports">
    <div>
      <label class="rfield-label" for="date-from">From Date</label>
      <input type="date" id="date-from" name="date_from" class="rfield-input" value="<?php echo e($dateFrom); ?>">
    </div>
    <div>
      <label class="rfield-label" for="date-to">To Date</label>
      <input type="date" id="date-to" name="date_to" class="rfield-input" value="<?php echo e($dateTo); ?>">
    </div>
    <div>
      <label class="rfield-label" for="status-f">Status</label>
      <select id="status-f" name="status" class="rfield-input">
        <option value="">All Statuses</option>
        <?php for ($s = 0; $s <= 9; $s++): ?>
          <option value="<?php echo $s; ?>" <?php echo $statusFilter === $s ? 'selected' : ''; ?>>
            <?php echo status_label($s); ?>
          </option>
        <?php endfor; ?>
      </select>
    </div>
    <?php if (is_admin()): ?>
    <div>
      <label class="rfield-label" for="branch-f">Branch Hub</label>
      <select id="branch-f" name="branch_id" class="rfield-input">
        <option value="">All Branches</option>
        <?php foreach ($branches as $b): ?>
          <option value="<?php echo $b['id']; ?>" <?php echo $branchFilter === (int)$b['id'] ? 'selected' : ''; ?>>
            <?php echo e($b['city']); ?> (<?php echo e($b['branch_code']); ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <div class="filter-actions">
      <button type="submit" class="apply-btn">Apply Filter</button>
      <a href="<?php echo APP_URL; ?>/admin/index.php?page=reports" class="reset-link">Reset</a>
    </div>
  </form>
</div>

<!-- KPI Cards -->
<div class="kpi-grid no-print">
  <div class="kpi-card blue">
    <div class="kpi-label">Total Consignments</div>
    <div class="kpi-value"><?php echo number_format($totalParcels); ?></div>
    <div class="kpi-sub">in selected period</div>
  </div>
  <div class="kpi-card indigo">
    <div class="kpi-label">Total Freight Value</div>
    <div class="kpi-value" style="font-size:1.5rem;">$<?php echo number_format($totalRevenue, 0); ?></div>
    <div class="kpi-sub">$<?php echo number_format($totalRevenue, 2); ?> exact</div>
  </div>
  <div class="kpi-card green">
    <div class="kpi-label">Delivered</div>
    <div class="kpi-value"><?php echo number_format($deliveredCount); ?></div>
    <div class="kpi-sub"><?php echo $deliveryRate; ?>% delivery rate</div>
  </div>
  <div class="kpi-card amber">
    <div class="kpi-label">Active In-Transit</div>
    <div class="kpi-value"><?php echo number_format($inTransitCount); ?></div>
    <div class="kpi-sub">shipments in motion</div>
  </div>
</div>

<!-- Manifest Table -->
<div class="manifest-card print-area">

  <!-- Printable header (hidden on screen via print CSS) -->
  <div style="display:none;" class="print-only">
    <div style="border-bottom:2px solid #0f172a;padding-bottom:12px;margin-bottom:16px;display:flex;justify-content:space-between;align-items:flex-end;">
      <div>
        <div style="font-size:1.25rem;font-weight:800;color:#0f172a;">GaaTiTrack Logistics — Freight Manifest</div>
        <div style="font-size:.8rem;color:#64748b;margin-top:3px;">Period: <?php echo e($dateFrom); ?> to <?php echo e($dateTo); ?><?php if ($statusFilter !== null): ?> · Status: <?php echo status_label($statusFilter); ?><?php endif; ?></div>
      </div>
      <div style="text-align:right;font-size:.75rem;color:#64748b;">
        Generated <?php echo date('M d, Y · h:i A'); ?><br>
        By <?php echo e($_SESSION['login_name'] ?? 'Staff'); ?>
      </div>
    </div>
    <!-- Print KPIs -->
    <div style="display:flex;gap:20px;margin-bottom:16px;font-size:.82rem;">
      <div><strong>Consignments:</strong> <?php echo $totalParcels; ?></div>
      <div><strong>Revenue:</strong> $<?php echo number_format($totalRevenue, 2); ?></div>
      <div><strong>Delivered:</strong> <?php echo $deliveredCount; ?> (<?php echo $deliveryRate; ?>%)</div>
      <div><strong>In-Transit:</strong> <?php echo $inTransitCount; ?></div>
    </div>
  </div>

  <div class="manifest-header no-print">
    <div>
      <div style="font-size:.9rem;font-weight:700;color:#0f172a;">Freight Manifest</div>
      <div style="font-size:.75rem;color:#94a3b8;"><?php echo $totalParcels; ?> record<?php echo $totalParcels !== 1 ? 's' : ''; ?> · $<?php echo number_format($totalRevenue, 2); ?> total</div>
    </div>
    <button type="button" onclick="window.print()" class="print-btn">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
      Print Manifest
    </button>
  </div>

  <?php if (!empty($reportData)): ?>
  <div style="overflow-x:auto;">
    <table class="manifest-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Reference</th>
          <th>Date</th>
          <th>Sender / Origin</th>
          <th>Recipient / Dest.</th>
          <th>Mode</th>
          <th>Status</th>
          <th style="text-align:right;">Amount</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($reportData as $i => $row): ?>
          <tr>
            <td style="color:#cbd5e1;font-size:.78rem;font-weight:600;"><?php echo $i + 1; ?></td>
            <td>
              <a href="<?php echo APP_URL; ?>/admin/index.php?page=view_parcel&id=<?php echo $row['id']; ?>" class="mref">
                #<?php echo e($row['reference_number']); ?>
              </a>
            </td>
            <td class="mdate"><?php echo date('M d, Y', strtotime($row['date_created'])); ?></td>
            <td>
              <div class="mname"><?php echo e($row['sender_name']); ?></div>
              <div class="mcity"><?php echo e($row['from_city'] ?? '—'); ?></div>
            </td>
            <td>
              <div class="mname"><?php echo e($row['recipient_name']); ?></div>
              <div class="mcity"><?php echo e($row['to_city'] ?? '—'); ?></div>
            </td>
            <td>
              <?php if ((int)$row['type'] === 1): ?>
                <span class="type-d">Doorstep</span>
              <?php else: ?>
                <span class="type-p">Pickup</span>
              <?php endif; ?>
            </td>
            <td><?php echo render_status_badge((int)$row['status']); ?></td>
            <td style="text-align:right;">
              <span class="mprice"><span class="mprice-sym">$</span><?php echo number_format((float)$row['price'], 2); ?></span>
            </td>
          </tr>
        <?php endforeach; ?>
        <!-- Total row -->
        <tr class="total-row">
          <td colspan="7" style="text-align:right;font-size:.84rem;letter-spacing:.02em;">MANIFEST TOTAL FREIGHT VALUE</td>
          <td style="text-align:right;font-size:1rem;">$<?php echo number_format($totalRevenue, 2); ?></td>
        </tr>
      </tbody>
    </table>
  </div>
  <?php else: ?>
    <div class="manifest-empty">
      <div class="manifest-empty-icon">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      </div>
      <h3 style="font-size:1rem;font-weight:700;color:#374151;margin:0 0 6px;">No data for this period</h3>
      <p style="font-size:.84rem;color:#94a3b8;margin:0;">Adjust your date range or filters to see consignment records.</p>
    </div>
  <?php endif; ?>
</div>

<style>
  @media print { .print-only { display:block !important; } }
  .print-only { display:none; }
</style>

<?php require_once __DIR__ . '/../footer.php'; ?>
