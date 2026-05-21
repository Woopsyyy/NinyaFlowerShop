<?php
/**
 * Ninya Flower Shop - Premium Romantic Floral Boutique
 * Customer & Admin Login Page
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';

log_analytics('page_view', ['page' => 'login']);

// If already logged in, redirect
if (is_logged_in()) {
    header("Location: index.php");
    exit;
}
if (is_admin_logged_in()) {
    header("Location: admin/dashboard.php");
    exit;
}

// Automatic database self-healing for the administrator account
try {
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = 'admin'");
    $stmt->execute();
    $admin_db = $stmt->fetch();
    if ($admin_db) {
        // If password is not 'admin123', correct it automatically
        if (!password_verify('admin123', $admin_db['password'])) {
            $new_hash = password_hash('admin123', PASSWORD_BCRYPT);
            $upd = $pdo->prepare("UPDATE admins SET password = ? WHERE username = 'admin'");
            $upd->execute([$new_hash]);
        }
    } else {
        // If admin record is missing completely, create it
        $new_hash = password_hash('admin123', PASSWORD_BCRYPT);
        $ins = $pdo->prepare("INSERT INTO admins (username, password, email, role) VALUES ('admin', ?, 'admin@ninyaflowers.com', 'Super Admin')");
        $ins->execute([$new_hash]);
    }
} catch (PDOException $e) {
    // Fail silently if database connection/tables aren't ready yet
}

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        $error_msg = 'Security token expired. Please try again.';
    } else {
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        
        if (empty($email) || empty($password)) {
            $error_msg = 'Please fill in all credentials.';
        } else {
            // First: Check if this is an Admin Login
            // (Admins can login using email or username)
            $admin_check = login_admin($email, $password);
            
            if ($admin_check === true) {
                log_analytics('admin_login', ['username' => $_SESSION['admin_username']]);
                set_flash('success', 'Welcome to the Ninya Flower Shop Control Room.');
                header("Location: admin/dashboard.php");
                exit;
            }
            
            // Second: Check if this is a Customer Login
            $customer_check = login_user($email, $password);
            
            if ($customer_check === true) {
                log_analytics('user_login', ['email' => $_SESSION['user_email']]);
                set_flash('success', "Welcome back, {$_SESSION['user_name']}!");
                header("Location: index.php");
                exit;
            } else {
                // If both fail, report customer error
                $error_msg = is_string($customer_check) ? $customer_check : 'Invalid login credentials.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boutique Entry - Ninya Flower Shop</title>
    
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body style="background-color: var(--bg-warm);">

    <!-- Header Navigation -->
    <?php include __DIR__ . '/components/navbar.php'; ?>

    <div class="container" style="padding-top: 5rem; padding-bottom: 7rem; display: flex; justify-content: center; align-items: center; min-height: 70vh;">
        
        <div style="background-color: var(--bg-white); border: var(--border-soft); width: 100%; max-width: 450px; padding: 3rem; box-shadow: var(--shadow-soft);">
            
            <!-- Logo details -->
            <div style="text-align: center; margin-bottom: 2.5rem;">
                <span class="section-subtitle" style="margin-bottom: 0.5rem;">The Sanctuary</span>
                <h2 style="font-family: var(--font-serif); font-size: 2.4rem; color: var(--text-charcoal);">Boutique Entry</h2>
            </div>
            
            <!-- Error feedback -->
            <?php if ($error_msg): ?>
                <div class="alert alert-error" style="font-size: 0.8rem; padding: 0.8rem; margin-bottom: 1.5rem;"><?php echo e($error_msg); ?></div>
            <?php endif; ?>
            <?php if ($success = get_flash('success')): ?>
                <div class="alert alert-success" style="font-size: 0.8rem; padding: 0.8rem; margin-bottom: 1.5rem;"><?php echo e($success); ?></div>
            <?php endif; ?>
            <?php if ($error = get_flash('error')): ?>
                <div class="alert alert-error" style="font-size: 0.8rem; padding: 0.8rem; margin-bottom: 1.5rem;"><?php echo e($error); ?></div>
            <?php endif; ?>
            
            <form action="login.php" method="POST">
                <?php csrf_field(); ?>
                
                <div class="form-group">
                    <label class="form-label" for="email">Email or Username *</label>
                    <input type="text" id="email" name="email" required placeholder="e.g. Juliet@ninya.com" class="form-control" style="background-color: var(--bg-warm); border-color: rgba(143,168,155,0.15);">
                </div>
                
                <div class="form-group" style="margin-bottom: 2rem;">
                    <label class="form-label" for="password">Password *</label>
                    <input type="password" id="password" name="password" required placeholder="Type your secure password..." class="form-control" style="background-color: var(--bg-warm); border-color: rgba(143,168,155,0.15);">
                </div>
                
                <button type="submit" name="login" class="btn btn-primary" style="display: block; width: 100%; padding: 1rem; font-size: 0.8rem; letter-spacing: 0.15em;">
                    Log In
                </button>
                
                <div style="text-align: center; margin-top: 2rem; font-size: 0.85rem; color: var(--text-muted);">
                    <span>First time visiting our boutique?</span>
                    <a href="register.php" style="color: var(--gold); text-decoration: underline; margin-left: 0.4rem;">Register Account</a>
                </div>
                
                <div style="text-align: center; margin-top: 1.5rem; font-size: 0.75rem; border-top: 1px dashed var(--champagne); padding-top: 1.5rem; color: var(--text-muted);">
                    <span>Admin Credentials: <strong>admin</strong> / <strong>admin123</strong></span>
                </div>
            </form>
            
        </div>
        
    </div>

    <!-- Footer Component -->
    <?php include __DIR__ . '/components/footer.php'; ?>

</body>
</html>
