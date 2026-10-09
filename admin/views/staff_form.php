<?php
/**
 * GaaTiTrack Admin - Staff Account Intake & Edit Form
 */

require_admin();

$staffId = (int)($_GET['id'] ?? 0);
$isEdit = $staffId > 0;
$pageTitle = $isEdit ? 'Edit Staff Account' : 'Add New Staff Account';

$user = [
    'id' => 0,
    'firstname' => '',
    'lastname' => '',
    'email' => '',
    'type' => 2,
    'branch_id' => 0,
];

// Fetch available branches
$branches = [];
try {
    $bStmt = $pdo->query("SELECT id, city, branch_code FROM branches ORDER BY city ASC");
    $branches = $bStmt->fetchAll();
} catch (PDOException $e) {}

// If Edit, load existing staff account
if ($isEdit) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $staffId]);
        $existing = $stmt->fetch();
        if (!$existing) {
            $_SESSION['flash_error'] = 'Staff account record not found.';
            header("Location: " . APP_URL . "/admin/index.php?page=staff");
            exit;
        }
        $user = $existing;
    } catch (PDOException $e) {
        app_log("Error loading user {$staffId}: " . $e->getMessage(), 'ERROR');
        $_SESSION['flash_error'] = 'Database error loading user account.';
        header("Location: " . APP_URL . "/admin/index.php?page=staff");
        exit;
    }
}

$errors = [];

// Handle POST Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf)) {
        $errors[] = 'Security validation failed (CSRF token expired or mismatch).';
    } else {
        $firstname = trim($_POST['firstname'] ?? '');
        $lastname = trim($_POST['lastname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $type = (int)($_POST['type'] ?? 2);
        $branchId = (int)($_POST['branch_id'] ?? 0);
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($firstname)) {
            $errors[] = 'First name is required.';
        }
        if (empty($lastname)) {
            $errors[] = 'Last name is required.';
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid corporate or personnel email address is required.';
        }
        if ($type !== 1 && $type !== 2) {
            $errors[] = 'Invalid account role selected.';
        }
        if ($type === 2 && $branchId <= 0) {
            $errors[] = 'Branch staff must be assigned to an active branch hub.';
        }

        // Email uniqueness check
        if (empty($errors)) {
            try {
                $emailCheck = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
                $emailCheck->execute([':email' => $email, ':id' => $staffId]);
                if ($emailCheck->fetch()) {
                    $errors[] = "Email address '{$email}' is already registered to another staff account.";
                }
            } catch (PDOException $e) {
                $errors[] = 'Database error verifying email uniqueness.';
            }
        }

        // Password validation
        if (!$isEdit) {
            // New user requires password
            if (empty($password)) {
                $errors[] = 'A secure password is required for new accounts.';
            } elseif (strlen($password) < 6) {
                $errors[] = 'Password must be at least 6 characters long.';
            } elseif ($password !== $confirmPassword) {
                $errors[] = 'Password confirmation does not match.';
            }
        } else {
            // Edit user: optional password change
            if (!empty($password)) {
                if (strlen($password) < 6) {
                    $errors[] = 'New password must be at least 6 characters long.';
                } elseif ($password !== $confirmPassword) {
                    $errors[] = 'Password confirmation does not match.';
                }
            }
        }

        // Save to Database
        if (empty($errors)) {
            try {
                // If admin, branch_id is 0
                $effectiveBranchId = ($type === 1) ? 0 : $branchId;

                if ($isEdit) {
                    if (!empty($password)) {
                        $hash = password_hash($password, PASSWORD_BCRYPT);
                        $upd = $pdo->prepare("
                            UPDATE users SET 
                                firstname = :fn,
                                lastname = :ln,
                                email = :email,
                                password = :pwd,
                                type = :type,
                                branch_id = :bid
                            WHERE id = :id
                        ");
                        $upd->execute([
                            ':fn' => $firstname,
                            ':ln' => $lastname,
                            ':email' => $email,
                            ':pwd' => $hash,
                            ':type' => $type,
                            ':bid' => $effectiveBranchId,
                            ':id' => $staffId
                        ]);
                    } else {
                        $upd = $pdo->prepare("
                            UPDATE users SET 
                                firstname = :fn,
                                lastname = :ln,
                                email = :email,
                                type = :type,
                                branch_id = :bid
                            WHERE id = :id
                        ");
                        $upd->execute([
                            ':fn' => $firstname,
                            ':ln' => $lastname,
                            ':email' => $email,
                            ':type' => $type,
                            ':bid' => $effectiveBranchId,
                            ':id' => $staffId
                        ]);
                    }

                    app_log("Updated user account ID {$staffId} ('{$email}') by Admin " . current_user_id());
                    $_SESSION['flash_success'] = "Staff account '{$firstname} {$lastname}' updated successfully.";

                } else {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $ins = $pdo->prepare("
                        INSERT INTO users (firstname, lastname, email, password, type, branch_id, date_created)
                        VALUES (:fn, :ln, :email, :pwd, :type, :bid, NOW())
                    ");
                    $ins->execute([
                        ':fn' => $firstname,
                        ':ln' => $lastname,
                        ':email' => $email,
                        ':pwd' => $hash,
                        ':type' => $type,
                        ':bid' => $effectiveBranchId
                    ]);

                    $newId = $pdo->lastInsertId();
                    app_log("Created user account ID {$newId} ('{$email}') by Admin " . current_user_id());
                    $_SESSION['flash_success'] = "Staff account '{$firstname} {$lastname}' created successfully with modern bcrypt encryption.";
                }

                header("Location: " . APP_URL . "/admin/index.php?page=staff");
                exit;

            } catch (PDOException $e) {
                app_log("User account save error: " . $e->getMessage(), 'ERROR');
                $errors[] = 'Database error while saving staff account.';
            }
        }
    }
}

require_once __DIR__ . '/../header.php';
?>

<div style="max-width: 800px; margin: 0 auto;">
  
  <div style="margin-bottom: 2rem;">
    <a href="<?php echo APP_URL; ?>/admin/index.php?page=staff" class="gt-btn gt-btn-ghost gt-btn-sm" style="margin-bottom: 0.5rem;">
      &larr; Back to Staff Directory
    </a>
    <h1 style="font-size: 1.6rem; font-weight: 800; color: var(--gt-navy-950); margin: 0 0 0.25rem 0;">
      <?php echo e($pageTitle); ?>
    </h1>
    <p style="color: var(--gt-navy-600); margin: 0; font-size: 0.9375rem;">
      Configure account credentials, permissions, and hub assignments.
    </p>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="gt-alert gt-alert-error" style="margin-bottom: 1.5rem;">
      <strong>Please correct the following errors:</strong>
      <ul style="margin: 0.5rem 0 0 1.25rem; padding: 0;">
        <?php foreach ($errors as $err): ?>
          <li><?php echo e($err); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div class="gt-card">
    <form method="POST" action="<?php echo APP_URL; ?>/admin/index.php?page=<?php echo $isEdit ? 'edit_staff&id=' . $staffId : 'new_staff'; ?>">
      <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 1.25rem;">
        <div>
          <label for="firstname" class="gt-label">First Name *</label>
          <input 
            type="text" 
            id="firstname" 
            name="firstname" 
            class="gt-input" 
            value="<?php echo e($_POST['firstname'] ?? $user['firstname']); ?>" 
            required
            placeholder="e.g. Rahul"
          >
        </div>

        <div>
          <label for="lastname" class="gt-label">Last Name *</label>
          <input 
            type="text" 
            id="lastname" 
            name="lastname" 
            class="gt-input" 
            value="<?php echo e($_POST['lastname'] ?? $user['lastname']); ?>" 
            required
            placeholder="e.g. Sharma"
          >
        </div>
      </div>

      <div class="gt-form-group">
        <label for="email" class="gt-label">Corporate Email Address *</label>
        <input 
          type="email" 
          id="email" 
          name="email" 
          class="gt-input" 
          value="<?php echo e($_POST['email'] ?? $user['email']); ?>" 
          required
          placeholder="staff.member@gaatitrack.com"
        >
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 1.25rem;">
        <div>
          <label for="type" class="gt-label">Account Role *</label>
          <select id="type" name="type" class="gt-input" required onchange="toggleBranchField(this.value)">
            <option value="2" <?php echo (int)($_POST['type'] ?? $user['type']) === 2 ? 'selected' : ''; ?>>
              Branch Staff (Scoped to Assigned Hub)
            </option>
            <option value="1" <?php echo (int)($_POST['type'] ?? $user['type']) === 1 ? 'selected' : ''; ?>>
              Administrator (Full Global Access)
            </option>
          </select>
        </div>

        <div id="branch-select-group">
          <label for="branch_id" class="gt-label">Assigned Hub *</label>
          <select id="branch_id" name="branch_id" class="gt-input">
            <option value="0">-- Select Branch Hub --</option>
            <?php foreach ($branches as $b): ?>
              <option value="<?php echo $b['id']; ?>" <?php echo (int)($_POST['branch_id'] ?? $user['branch_id']) === (int)$b['id'] ? 'selected' : ''; ?>>
                <?php echo e($b['city']); ?> (<?php echo e($b['branch_code']); ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- Password Section -->
      <div style="border-top: 1px solid var(--gt-border); padding-top: 1.5rem; margin-top: 1.5rem;">
        <div style="font-weight: 700; color: var(--gt-navy-900); margin-bottom: 0.5rem;">
          Security & Password
        </div>
        <?php if ($isEdit): ?>
          <p style="font-size: 0.8125rem; color: var(--gt-navy-500); margin-top: 0; margin-bottom: 1rem;">
            Leave password fields empty to keep current password unchanged.
          </p>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 1.25rem;">
          <div>
            <label for="password" class="gt-label"><?php echo $isEdit ? 'New Password (Optional)' : 'Password *'; ?></label>
            <input 
              type="password" 
              id="password" 
              name="password" 
              class="gt-input" 
              <?php echo !$isEdit ? 'required' : ''; ?>
              minlength="6"
              placeholder="Minimum 6 characters"
            >
          </div>

          <div>
            <label for="confirm_password" class="gt-label">Confirm Password</label>
            <input 
              type="password" 
              id="confirm_password" 
              name="confirm_password" 
              class="gt-input" 
              minlength="6"
              placeholder="Re-enter password"
            >
          </div>
        </div>
      </div>

      <div style="display: flex; gap: 10px; margin-top: 2rem; border-top: 1px solid var(--gt-border); padding-top: 1.25rem;">
        <button type="submit" class="gt-btn gt-btn-primary" style="padding: 0.65rem 1.75rem;">
          <?php echo $isEdit ? 'Save Changes' : 'Create Staff Account'; ?>
        </button>
        <a href="<?php echo APP_URL; ?>/admin/index.php?page=staff" class="gt-btn gt-btn-ghost">
          Cancel
        </a>
      </div>

    </form>
  </div>

</div>

<script>
function toggleBranchField(roleVal) {
  const branchGroup = document.getElementById('branch-select-group');
  if (parseInt(roleVal) === 1) {
    branchGroup.style.opacity = '0.5';
    branchGroup.style.pointerEvents = 'none';
  } else {
    branchGroup.style.opacity = '1';
    branchGroup.style.pointerEvents = 'auto';
  }
}
// Init on load
document.addEventListener('DOMContentLoaded', function() {
  toggleBranchField(document.getElementById('type').value);
});
</script>

<?php
require_once __DIR__ . '/../footer.php';
