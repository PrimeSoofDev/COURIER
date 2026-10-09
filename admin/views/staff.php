<?php
/**
 * GaaTiTrack Admin - Staff & User Directory
 */

require_admin();

$pageTitle = 'Staff Accounts Management';

// Handle Staff Account Deletion
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_staff') {
    $delId = (int)($_POST['id'] ?? 0);
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf)) {
        $_SESSION['flash_error'] = 'Security validation failed (CSRF mismatch).';
    } elseif ($delId <= 0) {
        $_SESSION['flash_error'] = 'Invalid user ID.';
    } elseif ($delId === (int)current_user_id()) {
        $_SESSION['flash_error'] = 'Self-deletion is forbidden: You cannot delete your own active administrator account.';
    } else {
        try {
            // Check if user exists
            $uStmt = $pdo->prepare("SELECT id, firstname, lastname, email, type FROM users WHERE id = :id");
            $uStmt->execute([':id' => $delId]);
            $targetUser = $uStmt->fetch();

            if (!$targetUser) {
                $_SESSION['flash_error'] = 'Staff account not found.';
            } else {
                $delStmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
                $delStmt->execute([':id' => $delId]);

                app_log("Deleted user ID {$delId} ('{$targetUser['email']}') by Admin ID " . current_user_id(), 'WARNING');
                $_SESSION['flash_success'] = "Staff account '{$targetUser['firstname']} {$targetUser['lastname']}' deleted successfully.";
            }
        } catch (PDOException $e) {
            app_log("Staff deletion error: " . $e->getMessage(), 'ERROR');
            $_SESSION['flash_error'] = 'Database error while deleting staff account.';
        }
    }
    header("Location: " . APP_URL . "/admin/index.php?page=staff");
    exit;
}

require_once __DIR__ . '/../header.php';

// Fetch staff accounts with assigned branch details
$searchQuery = trim($_GET['q'] ?? '');
$params = [];
$whereSql = '';

if (!empty($searchQuery)) {
    $whereSql = " WHERE u.firstname LIKE :search OR u.lastname LIKE :search OR u.email LIKE :search OR b.city LIKE :search";
    $params[':search'] = '%' . $searchQuery . '%';
}

$staffList = [];
try {
    $staffSql = "
        SELECT u.*, b.city as branch_city, b.branch_code
        FROM users u
        LEFT JOIN branches b ON u.branch_id = b.id
        {$whereSql}
        ORDER BY u.type ASC, u.firstname ASC
    ";
    $sStmt = $pdo->prepare($staffSql);
    $sStmt->execute($params);
    $staffList = $sStmt->fetchAll();
} catch (PDOException $e) {
    app_log("Error listing staff: " . $e->getMessage(), 'ERROR');
    $flashError = "Failed to load staff accounts.";
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
  <div>
    <h1 style="font-size: 1.6rem; font-weight: 800; color: var(--gt-navy-950); margin: 0 0 0.25rem 0;">
      Staff & Operator Directory
    </h1>
    <p style="color: var(--gt-navy-600); margin: 0; font-size: 0.9375rem;">
      Manage administrator credentials, branch staff assignments, and access privileges.
    </p>
  </div>

  <div>
    <a href="<?php echo APP_URL; ?>/admin/index.php?page=new_staff" class="gt-btn gt-btn-primary">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 4px;">
        <line x1="12" y1="5" x2="12" y2="19"></line>
        <line x1="5" y1="12" x2="19" y2="12"></line>
      </svg>
      Add Staff Account
    </a>
  </div>
</div>

<!-- Search Bar -->
<div class="gt-card" style="margin-bottom: 1.5rem; padding: 1.25rem;">
  <form method="GET" action="<?php echo APP_URL; ?>/admin/index.php" style="display: flex; gap: 10px; max-width: 500px;">
    <input type="hidden" name="page" value="staff">
    <input 
      type="search" 
      name="q" 
      class="gt-input" 
      placeholder="Search by name, email, or hub city..." 
      value="<?php echo e($searchQuery); ?>"
    >
    <button type="submit" class="gt-btn gt-btn-primary">Search</button>
    <?php if (!empty($searchQuery)): ?>
      <a href="<?php echo APP_URL; ?>/admin/index.php?page=staff" class="gt-btn gt-btn-ghost">Reset</a>
    <?php endif; ?>
  </form>
</div>

<!-- Staff Table -->
<div class="gt-card">
  <div class="gt-table-container">
    <table class="gt-table">
      <thead>
        <tr>
          <th>Staff Name</th>
          <th>Email Address</th>
          <th>Role / Privilege</th>
          <th>Assigned Hub</th>
          <th>Registered Date</th>
          <th style="text-align: right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($staffList)): ?>
          <tr>
            <td colspan="6" style="text-align: center; padding: 2.5rem; color: var(--gt-navy-500);">
              No staff accounts found.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($staffList as $u): ?>
            <tr>
              <td>
                <div style="font-weight: 700; color: var(--gt-navy-950); font-size: 0.9375rem;">
                  <?php echo e($u['firstname'] . ' ' . $u['lastname']); ?>
                  <?php if ((int)$u['id'] === (int)current_user_id()): ?>
                    <span class="gt-badge gt-badge-primary" style="font-size: 0.65rem; padding: 2px 6px; margin-left: 4px;">You</span>
                  <?php endif; ?>
                </div>
              </td>
              <td>
                <span style="font-size: 0.875rem; color: var(--gt-navy-700);">
                  <?php echo e($u['email']); ?>
                </span>
              </td>
              <td>
                <span class="gt-badge <?php echo (int)$u['type'] === 1 ? 'gt-badge-primary' : 'gt-badge-0'; ?>">
                  <?php echo (int)$u['type'] === 1 ? 'Administrator' : 'Branch Staff'; ?>
                </span>
              </td>
              <td>
                <?php if ((int)$u['type'] === 1): ?>
                  <span style="font-size: 0.8125rem; color: var(--gt-navy-500); font-style: italic;">
                    Global (All Hubs)
                  </span>
                <?php elseif (!empty($u['branch_city'])): ?>
                  <span style="font-size: 0.875rem; font-weight: 600; color: var(--gt-navy-800);">
                    <?php echo e($u['branch_city']); ?>
                  </span>
                  <span style="font-size: 0.75rem; color: var(--gt-navy-400); font-family: monospace;">
                    (<?php echo e($u['branch_code']); ?>)
                  </span>
                <?php else: ?>
                  <span style="font-size: 0.8125rem; color: #dc2626;">Unassigned Hub</span>
                <?php endif; ?>
              </td>
              <td style="font-size: 0.8125rem; color: var(--gt-navy-600); white-space: nowrap;">
                <?php echo date('M d, Y', strtotime($u['date_created'])); ?>
              </td>
              <td style="text-align: right; white-space: nowrap;">
                <a href="<?php echo APP_URL; ?>/admin/index.php?page=edit_staff&id=<?php echo $u['id']; ?>" class="gt-btn gt-btn-outline gt-btn-sm" style="padding: 3px 8px; font-size: 0.8125rem;">
                  Edit
                </a>

                <?php if ((int)$u['id'] !== (int)current_user_id()): ?>
                  <form 
                    method="POST" 
                    action="<?php echo APP_URL; ?>/admin/index.php?page=staff" 
                    style="display: inline-block; margin-left: 4px;"
                    onsubmit="return confirm('Are you sure you want to delete staff account \'<?php echo addslashes($u['firstname'] . ' ' . $u['lastname']); ?>\'?');"
                  >
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="action" value="delete_staff">
                    <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                    <button type="submit" class="gt-btn gt-btn-ghost gt-btn-sm" style="color: #ef4444; padding: 3px 8px; font-size: 0.8125rem;">
                      Delete
                    </button>
                  </form>
                <?php endif; ?>
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
