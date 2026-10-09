<?php
/**
 * GaaTiTrack Admin - Branch Hubs Directory
 */

require_admin();

$pageTitle = 'Branch Hubs Management';

// Handle Branch Deletion
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_branch') {
    $delId = (int)($_POST['id'] ?? 0);
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf)) {
        $_SESSION['flash_error'] = 'Security validation failed (CSRF mismatch).';
    } elseif ($delId <= 0) {
        $_SESSION['flash_error'] = 'Invalid branch ID.';
    } else {
        try {
            // Check if parcels are associated with this branch
            $pCheck = $pdo->prepare("SELECT COUNT(*) FROM parcels WHERE from_branch_id = :id OR to_branch_id = :id");
            $pCheck->execute([':id' => $delId]);
            $parcelCount = (int)$pCheck->fetchColumn();

            // Check if staff users are assigned to this branch
            $uCheck = $pdo->prepare("SELECT COUNT(*) FROM users WHERE branch_id = :id");
            $uCheck->execute([':id' => $delId]);
            $userCount = (int)$uCheck->fetchColumn();

            if ($parcelCount > 0 || $userCount > 0) {
                $_SESSION['flash_error'] = "Cannot delete branch hub: {$parcelCount} consignment(s) and {$userCount} staff account(s) are actively linked to this hub. Reassign them before deleting.";
            } else {
                $delStmt = $pdo->prepare("DELETE FROM branches WHERE id = :id");
                $delStmt->execute([':id' => $delId]);

                app_log("Deleted branch ID {$delId} by Admin ID " . current_user_id(), 'WARNING');
                $_SESSION['flash_success'] = 'Branch hub deleted successfully.';
            }
        } catch (PDOException $e) {
            app_log("Failed to delete branch: " . $e->getMessage(), 'ERROR');
            $_SESSION['flash_error'] = 'Database error while deleting branch hub.';
        }
    }
    header("Location: " . APP_URL . "/admin/index.php?page=branches");
    exit;
}

require_once __DIR__ . '/../header.php';

// Fetch all branches with consignment count & staff count
$searchQuery = trim($_GET['q'] ?? '');
$params = [];
$whereSql = '';

if (!empty($searchQuery)) {
    $whereSql = " WHERE branch_code LIKE :search OR city LIKE :search OR state LIKE :search OR contact LIKE :search";
    $params[':search'] = '%' . $searchQuery . '%';
}

$branchesList = [];
try {
    $bQuery = "
        SELECT b.*,
               (SELECT COUNT(*) FROM parcels p WHERE p.from_branch_id = b.id OR p.to_branch_id = b.id) as total_parcels,
               (SELECT COUNT(*) FROM users u WHERE u.branch_id = b.id) as total_staff
        FROM branches b
        {$whereSql}
        ORDER BY b.city ASC
    ";
    $bStmt = $pdo->prepare($bQuery);
    $bStmt->execute($params);
    $branchesList = $bStmt->fetchAll();
} catch (PDOException $e) {
    app_log("Error listing branches: " . $e->getMessage(), 'ERROR');
    $flashError = "Failed to load branch hubs.";
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
  <div>
    <h1 style="font-size: 1.6rem; font-weight: 800; color: var(--gt-navy-950); margin: 0 0 0.25rem 0;">
      Branch Hubs Directory
    </h1>
    <p style="color: var(--gt-navy-600); margin: 0; font-size: 0.9375rem;">
      Manage physical distribution centers, intake stations, and hub routing codes.
    </p>
  </div>

  <div style="display: flex; gap: 0.75rem;">
    <a href="<?php echo APP_URL; ?>/admin/index.php?page=new_branch" class="gt-btn gt-btn-primary">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 4px;">
        <line x1="12" y1="5" x2="12" y2="19"></line>
        <line x1="5" y1="12" x2="19" y2="12"></line>
      </svg>
      Add New Branch Hub
    </a>
  </div>
</div>

<!-- Search Filter -->
<div class="gt-card" style="margin-bottom: 1.5rem; padding: 1.25rem;">
  <form method="GET" action="<?php echo APP_URL; ?>/admin/index.php" style="display: flex; gap: 10px; max-width: 500px;">
    <input type="hidden" name="page" value="branches">
    <input 
      type="search" 
      name="q" 
      class="gt-input" 
      placeholder="Search by city, branch code, or contact..." 
      value="<?php echo e($searchQuery); ?>"
    >
    <button type="submit" class="gt-btn gt-btn-primary">Search</button>
    <?php if (!empty($searchQuery)): ?>
      <a href="<?php echo APP_URL; ?>/admin/index.php?page=branches" class="gt-btn gt-btn-ghost">Reset</a>
    <?php endif; ?>
  </form>
</div>

<!-- Branches Table -->
<div class="gt-card">
  <div class="gt-table-container">
    <table class="gt-table">
      <thead>
        <tr>
          <th>Branch Code</th>
          <th>Location & City</th>
          <th>Full Physical Address</th>
          <th>Contact</th>
          <th style="text-align: center;">Active Shipments</th>
          <th style="text-align: center;">Assigned Staff</th>
          <th style="text-align: right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($branchesList)): ?>
          <tr>
            <td colspan="7" style="text-align: center; padding: 2.5rem; color: var(--gt-navy-500);">
              No branch hubs found.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($branchesList as $b): ?>
            <tr>
              <td>
                <span style="font-family: monospace; font-weight: 700; color: var(--gt-primary); background: var(--gt-primary-subtle); padding: 3px 8px; border-radius: var(--gt-radius-sm); border: 1px solid var(--gt-primary-light);">
                  <?php echo e($b['branch_code']); ?>
                </span>
              </td>
              <td>
                <div style="font-weight: 700; color: var(--gt-navy-950); font-size: 0.9375rem;">
                  <?php echo e($b['city']); ?>
                </div>
                <div style="font-size: 0.75rem; color: var(--gt-navy-500);">
                  <?php echo e($b['state']); ?>, <?php echo e($b['country']); ?>
                </div>
              </td>
              <td style="font-size: 0.8125rem; color: var(--gt-navy-700); max-width: 250px;">
                <?php echo e($b['street']); ?>
                <?php if (!empty($b['zip_code'])): ?>
                  <span style="color: var(--gt-navy-400);">(ZIP: <?php echo e($b['zip_code']); ?>)</span>
                <?php endif; ?>
              </td>
              <td style="font-size: 0.8125rem; font-weight: 500;">
                <?php echo e($b['contact']); ?>
              </td>
              <td style="text-align: center;">
                <span class="gt-badge gt-badge-primary">
                  <?php echo number_format($b['total_parcels']); ?>
                </span>
              </td>
              <td style="text-align: center;">
                <span class="gt-badge gt-badge-0">
                  <?php echo number_format($b['total_staff']); ?>
                </span>
              </td>
              <td style="text-align: right; white-space: nowrap;">
                <a href="<?php echo APP_URL; ?>/admin/index.php?page=edit_branch&id=<?php echo $b['id']; ?>" class="gt-btn gt-btn-outline gt-btn-sm" style="padding: 3px 8px; font-size: 0.8125rem;">
                  Edit
                </a>
                
                <form 
                  method="POST" 
                  action="<?php echo APP_URL; ?>/admin/index.php?page=branches" 
                  style="display: inline-block; margin-left: 4px;"
                  onsubmit="return confirm('Are you sure you want to delete branch hub \'<?php echo addslashes($b['city']); ?>\' (<?php echo e($b['branch_code']); ?>)?');"
                >
                  <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                  <input type="hidden" name="action" value="delete_branch">
                  <input type="hidden" name="id" value="<?php echo $b['id']; ?>">
                  <button type="submit" class="gt-btn gt-btn-ghost gt-btn-sm" style="color: #ef4444; padding: 3px 8px; font-size: 0.8125rem;">
                    Delete
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
require_once __DIR__ . '/../footer.php';
