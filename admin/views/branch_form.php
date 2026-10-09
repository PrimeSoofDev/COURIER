<?php
/**
 * GaaTiTrack Admin - Branch Hub Intake & Edit Form
 */

require_admin();

$branchId = (int)($_GET['id'] ?? 0);
$isEdit = $branchId > 0;
$pageTitle = $isEdit ? 'Edit Branch Hub' : 'Add New Branch Hub';

$branch = [
    'id' => 0,
    'branch_code' => '',
    'street' => '',
    'city' => '',
    'state' => '',
    'zip_code' => '',
    'country' => 'United States',
    'contact' => '',
];

// If Edit, load existing record
if ($isEdit) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM branches WHERE id = :id");
        $stmt->execute([':id' => $branchId]);
        $existing = $stmt->fetch();
        if (!$existing) {
            $_SESSION['flash_error'] = 'Branch hub record not found.';
            header("Location: " . APP_URL . "/admin/index.php?page=branches");
            exit;
        }
        $branch = $existing;
    } catch (PDOException $e) {
        app_log("Error loading branch {$branchId}: " . $e->getMessage(), 'ERROR');
        $_SESSION['flash_error'] = 'Database error loading branch hub.';
        header("Location: " . APP_URL . "/admin/index.php?page=branches");
        exit;
    }
} else {
    // Generate fresh random 15-character branch code
    $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $branch['branch_code'] = substr(str_shuffle($chars), 0, 15);
}

$errors = [];

// Handle POST Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    
    if (!verify_csrf_token($csrf)) {
        $errors[] = 'Security validation failed (CSRF token expired or mismatch).';
    } else {
        $bCode = trim($_POST['branch_code'] ?? '');
        $street = trim($_POST['street'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $zipCode = trim($_POST['zip_code'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $contact = trim($_POST['contact'] ?? '');

        if (empty($bCode)) {
            $errors[] = 'Branch code is required.';
        }
        if (empty($city)) {
            $errors[] = 'City location is required.';
        }
        if (empty($street)) {
            $errors[] = 'Physical street address is required.';
        }
        if (empty($contact)) {
            $errors[] = 'Hub contact phone or email is required.';
        }

        // Check unique branch code
        if (empty($errors)) {
            try {
                $codeCheck = $pdo->prepare("SELECT id FROM branches WHERE branch_code = :code AND id != :id");
                $codeCheck->execute([':code' => $bCode, ':id' => $branchId]);
                if ($codeCheck->fetch()) {
                    $errors[] = "Branch code '{$bCode}' is already allocated to another hub.";
                }
            } catch (PDOException $e) {
                $errors[] = 'Database error checking branch code.';
            }
        }

        if (empty($errors)) {
            try {
                if ($isEdit) {
                    $upd = $pdo->prepare("
                        UPDATE branches SET 
                            branch_code = :code,
                            street = :street,
                            city = :city,
                            state = :state,
                            zip_code = :zip,
                            country = :country,
                            contact = :contact
                        WHERE id = :id
                    ");
                    $upd->execute([
                        ':code' => $bCode,
                        ':street' => $street,
                        ':city' => $city,
                        ':state' => $state,
                        ':zip' => $zipCode,
                        ':country' => $country,
                        ':contact' => $contact,
                        ':id' => $branchId
                    ]);

                    app_log("Updated branch hub ID {$branchId} ('{$city}') by Admin " . current_user_id());
                    $_SESSION['flash_success'] = "Branch hub '{$city}' updated successfully.";
                } else {
                    $ins = $pdo->prepare("
                        INSERT INTO branches (branch_code, street, city, state, zip_code, country, contact, date_created)
                        VALUES (:code, :street, :city, :state, :zip, :country, :contact, NOW())
                    ");
                    $ins->execute([
                        ':code' => $bCode,
                        ':street' => $street,
                        ':city' => $city,
                        ':state' => $state,
                        ':zip' => $zipCode,
                        ':country' => $country,
                        ':contact' => $contact
                    ]);

                    $newId = $pdo->lastInsertId();
                    app_log("Created new branch hub ID {$newId} ('{$city}') by Admin " . current_user_id());
                    $_SESSION['flash_success'] = "New branch hub '{$city}' added successfully.";
                }

                header("Location: " . APP_URL . "/admin/index.php?page=branches");
                exit;

            } catch (PDOException $e) {
                app_log("Branch save error: " . $e->getMessage(), 'ERROR');
                $errors[] = 'Database error while saving branch hub.';
            }
        }
    }
}

require_once __DIR__ . '/../header.php';
?>

<div style="max-width: 800px; margin: 0 auto;">
  
  <div style="margin-bottom: 2rem;">
    <a href="<?php echo APP_URL; ?>/admin/index.php?page=branches" class="gt-btn gt-btn-ghost gt-btn-sm" style="margin-bottom: 0.5rem;">
      &larr; Back to Branch Hubs
    </a>
    <h1 style="font-size: 1.6rem; font-weight: 800; color: var(--gt-navy-950); margin: 0 0 0.25rem 0;">
      <?php echo e($pageTitle); ?>
    </h1>
    <p style="color: var(--gt-navy-600); margin: 0; font-size: 0.9375rem;">
      Configure distribution center details, geographical coordinates, and direct contact numbers.
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
    <form method="POST" action="<?php echo APP_URL; ?>/admin/index.php?page=<?php echo $isEdit ? 'edit_branch&id=' . $branchId : 'new_branch'; ?>">
      <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 1.25rem;">
        <div>
          <label for="branch_code" class="gt-label">Branch Hub Code *</label>
          <input 
            type="text" 
            id="branch_code" 
            name="branch_code" 
            class="gt-input" 
            value="<?php echo e($_POST['branch_code'] ?? $branch['branch_code']); ?>" 
            required
            maxlength="50"
            style="font-family: monospace; font-weight: 700;"
          >
          <small class="gt-form-text">Unique system identifier for routing manifests.</small>
        </div>

        <div>
          <label for="city" class="gt-label">Hub City / Region *</label>
          <input 
            type="text" 
            id="city" 
            name="city" 
            class="gt-input" 
            value="<?php echo e($_POST['city'] ?? $branch['city']); ?>" 
            required
            placeholder="e.g. New York, Chicago, Los Angeles"
          >
        </div>
      </div>

      <div class="gt-form-group">
        <label for="street" class="gt-label">Street / Facility Address *</label>
        <textarea 
          id="street" 
          name="street" 
          class="gt-input" 
          rows="3" 
          required 
          placeholder="Building, street, suite, and facility code..."
        ><?php echo e($_POST['street'] ?? $branch['street']); ?></textarea>
      </div>

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.25rem; margin-bottom: 1.25rem;">
        <div>
          <label for="state" class="gt-label">State / Province</label>
          <input 
            type="text" 
            id="state" 
            name="state" 
            class="gt-input" 
            value="<?php echo e($_POST['state'] ?? $branch['state']); ?>" 
            placeholder="e.g. NY, IL, CA, TX"
          >
        </div>

        <div>
          <label for="zip_code" class="gt-label">Postal / ZIP Code</label>
          <input 
            type="text" 
            id="zip_code" 
            name="zip_code" 
            class="gt-input" 
            value="<?php echo e($_POST['zip_code'] ?? $branch['zip_code']); ?>" 
            placeholder="e.g. 10001, 60606, 90017"
          >
        </div>

        <div>
          <label for="country" class="gt-label">Country</label>
          <input 
            type="text" 
            id="country" 
            name="country" 
            class="gt-input" 
            value="<?php echo e($_POST['country'] ?? $branch['country']); ?>" 
            placeholder="e.g. United States"
          >
        </div>
      </div>

      <div class="gt-form-group">
        <label for="contact" class="gt-label">Hub Contact Phone / Hotline *</label>
        <input 
          type="text" 
          id="contact" 
          name="contact" 
          class="gt-input" 
          value="<?php echo e($_POST['contact'] ?? $branch['contact']); ?>" 
          required 
          placeholder="e.g. +1 (555) 019-2834"
        >
        <small class="gt-form-text">Used on printed waybills and customer support queries.</small>
      </div>

      <div style="display: flex; gap: 10px; margin-top: 2rem; border-top: 1px solid var(--gt-border); padding-top: 1.25rem;">
        <button type="submit" class="gt-btn gt-btn-primary" style="padding: 0.65rem 1.75rem;">
          <?php echo $isEdit ? 'Save Changes' : 'Create Branch Hub'; ?>
        </button>
        <a href="<?php echo APP_URL; ?>/admin/index.php?page=branches" class="gt-btn gt-btn-ghost">
          Cancel
        </a>
      </div>

    </form>
  </div>

</div>

<?php
require_once __DIR__ . '/../footer.php';
