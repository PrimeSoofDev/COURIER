<?php
/**
 * GaaTiTrack - Modernized Secure Staff & Administration Authentication
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/includes/components.php';

// Enforce secure session cookie settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// Redirect already authenticated users
if (isset($_SESSION['login_id'])) {
    header("Location: " . APP_URL . "/admin/index.php");
    exit;
}

$errorMsg = '';
$infoMsg = '';

if (isset($_GET['logged_out'])) {
    $infoMsg = 'You have been safely logged out.';
}

// Rate Limiting Parameters (5 attempts / 5 minutes)
$maxAttempts = 5;
$lockoutDuration = 300; // seconds
$now = time();

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = ['count' => 0, 'first_attempt' => $now];
}

$isLockedOut = false;
if ($_SESSION['login_attempts']['count'] >= $maxAttempts) {
    $timeSinceFirst = $now - $_SESSION['login_attempts']['first_attempt'];
    if ($timeSinceFirst < $lockoutDuration) {
        $isLockedOut = true;
        $remainingSeconds = $lockoutDuration - $timeSinceFirst;
        $remainingMinutes = ceil($remainingSeconds / 60);
        $errorMsg = "Too many failed login attempts. Please wait {$remainingMinutes} minute(s) before trying again.";
    } else {
        // Reset lockout after period expires
        $_SESSION['login_attempts'] = ['count' => 0, 'first_attempt' => $now];
    }
}

// Process Login Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isLockedOut) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    // 1. Verify CSRF Token
    if (!verify_csrf_token($csrfToken)) {
        $errorMsg = 'Security validation failed (CSRF mismatch). Please refresh the page and try again.';
    } elseif (empty($email) || empty($password)) {
        $errorMsg = 'Please enter both your email address and password.';
    } else {
        try {
            // 2. Fetch User via Secure Prepared Statement
            $stmt = $pdo->prepare("SELECT id, firstname, lastname, email, password, type, branch_id FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            $authenticated = false;

            if ($user) {
                // 3. Verify Password (supports modern bcrypt and transparent legacy MD5 migration)
                if (password_verify($password, $user['password'])) {
                    $authenticated = true;
                } elseif (md5($password) === $user['password']) {
                    $authenticated = true;
                    // Transparently upgrade legacy MD5 hash to modern secure bcrypt
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $upStmt = $pdo->prepare("UPDATE users SET password = :hash WHERE id = :id");
                    $upStmt->execute([':hash' => $newHash, ':id' => $user['id']]);
                    app_log("Upgraded password hashing to bcrypt for user ID: " . $user['id'], 'INFO');
                }
            }

            if ($authenticated) {
                // 4. Session ID Regeneration to Prevent Session Fixation
                session_regenerate_id(true);

                // Set secure authenticated session variables
                $_SESSION['login_id'] = (int)$user['id'];
                $_SESSION['login_firstname'] = $user['firstname'];
                $_SESSION['login_lastname'] = $user['lastname'];
                $_SESSION['login_name'] = trim($user['firstname'] . ' ' . $user['lastname']);
                $_SESSION['login_email'] = $user['email'];
                $_SESSION['login_type'] = (int)$user['type']; // 1 = Admin, 2 = Staff
                $_SESSION['login_branch_id'] = (int)$user['branch_id'];
                $_SESSION['login_time'] = time();

                // Clear login failure counters
                unset($_SESSION['login_attempts']);

                // Ensure system settings are loaded into session
                if (!isset($_SESSION['system'])) {
                    $sys = $pdo->query("SELECT * FROM system_settings LIMIT 1")->fetch();
                    if ($sys) {
                        $_SESSION['system'] = $sys;
                    }
                }

                app_log("Successful login: " . $user['email'] . " (Role: " . ($user['type'] == 1 ? 'Admin' : 'Staff') . ")", 'INFO');

                // Return JSON if AJAX request
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['status' => 'success', 'redirect' => APP_URL . '/admin/index.php']);
                    exit;
                }

                header("Location: " . APP_URL . "/admin/index.php");
                exit;
            } else {
                // Authentication Failure: Enforce generic error without disclosing account existence
                $_SESSION['login_attempts']['count']++;
                app_log("Failed login attempt for: " . $email . " from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown'), 'WARNING');
                $errorMsg = 'Invalid email or password.';
            }

        } catch (PDOException $e) {
            app_log("Authentication database error: " . $e->getMessage(), 'ERROR');
            $errorMsg = 'A temporary database error occurred. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Staff & Admin Portal Login | GaaTiTrack</title>
  <link rel="icon" type="image/x-icon" href="<?php echo APP_URL; ?>/assets/uploads/logo1.jpg">
  <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/design-system.css">
</head>
<body style="background: radial-gradient(circle at top, #f0f9ff 0%, #f8fafc 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem 1rem;">

  <!-- Accessible Skip Link -->
  <a href="#login-main-content" class="gt-skip-link">Skip to login form</a>

  <main id="login-main-content" style="width: 100%; max-width: 440px;">
    
    <!-- Header Brand & Return Link -->
    <div style="text-align: center; margin-bottom: 2rem;">
      <a href="<?php echo APP_URL; ?>/index.php" class="gt-brand" style="justify-content: center; margin-bottom: 0.75rem;">
        <div class="gt-brand-logo" style="width: 44px; height: 44px;">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2">
            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
            <line x1="12" y1="22.08" x2="12" y2="12"></line>
          </svg>
        </div>
        <div class="gt-brand-title" style="font-size: 1.45rem;">GaaTi<span>Track</span></div>
      </a>
      <p style="color: var(--gt-navy-500); font-size: 0.9375rem; margin: 0;">Authorized Personnel & Staff Portal</p>
    </div>

    <!-- Login Card -->
    <div class="gt-card" style="padding: 2.25rem; box-shadow: var(--gt-shadow-lg); border-color: var(--gt-border);">
      
      <h1 style="font-size: 1.4rem; margin-bottom: 0.35rem; color: var(--gt-navy-950); text-align: center;">Welcome Back</h1>
      <p style="font-size: 0.875rem; color: var(--gt-navy-500); text-align: center; margin-bottom: 1.75rem;">Sign in with your staff or administrator credentials.</p>

      <?php if (!empty($infoMsg)): ?>
        <div class="gt-alert gt-alert-info" style="margin-bottom: 1.25rem; font-size: 0.875rem;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="16" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12.01" y2="8"></line>
          </svg>
          <div><?php echo e($infoMsg); ?></div>
        </div>
      <?php endif; ?>

      <?php if (!empty($errorMsg)): ?>
        <div class="gt-alert gt-alert-error" style="margin-bottom: 1.25rem; font-size: 0.875rem;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
          </svg>
          <div><?php echo e($errorMsg); ?></div>
        </div>
      <?php endif; ?>

      <form action="<?php echo APP_URL; ?>/login.php" method="POST" id="auth-form">
        <!-- CSRF Token -->
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

        <div class="gt-form-group">
          <label for="email" class="gt-label">Email Address</label>
          <input 
            type="email" 
            name="email" 
            id="email" 
            class="gt-input" 
            placeholder="name@domain.com" 
            value="<?php echo e($_POST['email'] ?? ''); ?>" 
            required 
            autocomplete="email"
            autofocus
          >
        </div>

        <div class="gt-form-group">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.375rem;">
            <label for="password" class="gt-label" style="margin-bottom: 0;">Password</label>
          </div>
          <div style="position: relative;">
            <input 
              type="password" 
              name="password" 
              id="password" 
              class="gt-input" 
              placeholder="••••••••" 
              required 
              autocomplete="current-password"
              style="padding-right: 42px;"
            >
            <button 
              type="button" 
              id="toggle-password" 
              aria-label="Toggle password visibility" 
              style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--gt-navy-400); padding: 4px; display: flex; align-items: center; justify-content: center;"
            >
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" id="eye-icon">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
            </button>
          </div>
        </div>

        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; font-size: 0.875rem;">
          <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; color: var(--gt-navy-600);">
            <input type="checkbox" name="remember" id="remember" style="accent-color: var(--gt-primary);">
            <span>Remember me</span>
          </label>
        </div>

        <button type="submit" class="gt-btn gt-btn-primary gt-btn-block gt-btn-lg" id="submit-btn">
          Sign In to Dashboard
        </button>
      </form>

    </div>

    <!-- Return Link -->
    <div style="text-align: center; margin-top: 1.5rem;">
      <a href="<?php echo APP_URL; ?>/index.php" style="color: var(--gt-navy-500); font-size: 0.875rem; display: inline-flex; align-items: center; gap: 6px;">
        &larr; Back to Public Portal
      </a>
    </div>

  </main>

  <script>
    // Password visibility toggle
    const toggleBtn = document.getElementById('toggle-password');
    const pwdInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eye-icon');

    if (toggleBtn && pwdInput) {
      toggleBtn.addEventListener('click', function() {
        if (pwdInput.type === 'password') {
          pwdInput.type = 'text';
          eyeIcon.innerHTML = `
            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
            <line x1="1" y1="1" x2="23" y2="23"></line>
          `;
          toggleBtn.setAttribute('aria-label', 'Hide password');
        } else {
          pwdInput.type = 'password';
          eyeIcon.innerHTML = `
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
            <circle cx="12" cy="12" r="3"></circle>
          `;
          toggleBtn.setAttribute('aria-label', 'Show password');
        }
      });
    }
  </script>

</body>
</html>
