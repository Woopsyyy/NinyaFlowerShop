<?php
/**
 * Ninya Flower Shop - Premium Romantic Floral Boutique
 * Customer Registration Page
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';

log_analytics('page_view', ['page' => 'register']);

// If already logged in, redirect
if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        $error_msg = 'Security token expired. Please try again.';
    } else {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $confirm_password = trim($_POST['confirm_password']);
        $phone = trim($_POST['phone']);
        
        if (empty($name) || empty($email) || empty($password)) {
            $error_msg = 'Please fill in all required fields.';
        } elseif ($password !== $confirm_password) {
            $error_msg = 'Passwords do not match.';
        } elseif (strlen($password) < 6) {
            $error_msg = 'Password must be at least 6 characters.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_msg = 'Please enter a valid email address.';
        } else {
            // Safe registration attempt
            $reg_check = register_user($name, $email, $password, $phone);
            
            if ($reg_check === true) {
                // Instantly log them in
                login_user($email, $password);
                log_analytics('user_registered', ['email' => $email]);
                set_flash('success', "Welcome to the Ninya family, {$name}!");
                header("Location: index.php");
                exit;
            } else {
                $error_msg = $reg_check;
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
    <title>Boutique Registry - Ninya Flower Shop</title>
    
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body style="background-color: var(--bg-warm);">

    <!-- Header Navigation -->
    <?php include __DIR__ . '/components/navbar.php'; ?>

    <div class="container" style="padding-top: 5rem; padding-bottom: 7rem; display: flex; justify-content: center; align-items: center; min-height: 80vh;">
        
        <div style="background-color: var(--bg-white); border: var(--border-soft); width: 100%; max-width: 500px; padding: 3rem; box-shadow: var(--shadow-soft);">
            
            <!-- Logo details -->
            <div style="text-align: center; margin-bottom: 2.5rem;">
                <span class="section-subtitle" style="margin-bottom: 0.5rem;">The Sanctuary</span>
                <h2 style="font-family: var(--font-serif); font-size: 2.4rem; color: var(--text-charcoal);">Boutique Registry</h2>
            </div>
            
            <!-- Error feedback -->
            <?php if ($error_msg): ?>
                <div class="alert alert-error" style="font-size: 0.8rem; padding: 0.8rem; margin-bottom: 1.5rem;"><?php echo e($error_msg); ?></div>
            <?php endif; ?>
            
            <form action="register.php" method="POST">
                <?php csrf_field(); ?>
                
                <div class="form-group">
                    <label class="form-label" for="name">Full Name *</label>
                    <input type="text" id="name" name="name" required placeholder="e.g. Juliet Capulet" class="form-control" style="background-color: var(--bg-warm); border-color: rgba(143,168,155,0.15);">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="email">Email Address *</label>
                    <input type="email" id="email" name="email" required placeholder="e.g. juliet@ninya.com" class="form-control" style="background-color: var(--bg-warm); border-color: rgba(143,168,155,0.15);">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="phone">Phone Number (Optional)</label>
                    <input type="text" id="phone" name="phone" placeholder="e.g. +1 555-0199" class="form-control" style="background-color: var(--bg-warm); border-color: rgba(143,168,155,0.15);">
                </div>
                
                <div class="grid-2" style="gap: 1.5rem;">
                    <div class="form-group">
                        <label class="form-label" for="password">Password *</label>
                        <input type="password" id="password" name="password" required placeholder="Min 6 characters..." class="form-control" style="background-color: var(--bg-warm); border-color: rgba(143,168,155,0.15);">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required placeholder="Verify password..." class="form-control" style="background-color: var(--bg-warm); border-color: rgba(143,168,155,0.15);">
                    </div>
                </div>
                
                <button type="submit" name="register" class="btn btn-primary" style="display: block; width: 100%; padding: 1rem; font-size: 0.8rem; letter-spacing: 0.15em; margin-top: 1rem;">
                    Register Account
                </button>
                
                <div style="text-align: center; margin-top: 2rem; font-size: 0.85rem; color: var(--text-muted);">
                    <span>Already have a boutique profile?</span>
                    <a href="login.php" style="color: var(--gold); text-decoration: underline; margin-left: 0.4rem;">Log In</a>
                </div>
            </form>
            
        </div>
        
    </div>

    <!-- Footer Component -->
    <?php include __DIR__ . '/components/footer.php'; ?>

</body>
</html>
