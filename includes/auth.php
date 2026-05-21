<?php
/**
 * Authentication & Access Level Gatekeeper
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session.php';

/**
 * Register a new customer account safely
 * @param string $name
 * @param string $email
 * @param string $password
 * @param string $phone
 * @return bool|string True on success, error string on failure
 */
function register_user($name, $email, $password, $phone = '') {
    global $pdo;
    
    // Check if user already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return "An account with this email address already exists.";
    }
    
    // Hash password securely
    $hash = password_hash($password, PASSWORD_BCRYPT);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, 'customer')");
        $stmt->execute([$name, $email, $hash, $phone]);
        
        // Sync guest cart to user cart if exists
        $user_id = $pdo->lastInsertId();
        sync_guest_cart($user_id);
        
        return true;
    } catch (PDOException $e) {
        return "Registration failed. Please try again. Code: " . $e->getCode();
    }
}

/**
 * Log in a customer
 * @param string $email
 * @param string $password
 * @return bool|string True on success, error string on failure
 */
function login_user($email, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($password, $user['password'])) {
        return "Invalid email address or password.";
    }
    
    // Setup session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    
    // Sync session guest cart to database
    sync_guest_cart($user['id']);
    
    return true;
}

/**
 * Log in an administrator
 * @param string $username
 * @param string $password
 * @return bool|string True on success, error string on failure
 */
function login_admin($username, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $admin = $stmt->fetch();
    
    if (!$admin || !password_verify($password, $admin['password'])) {
        return "Invalid administrator credentials.";
    }
    
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['admin_role'] = $admin['role'];
    
    return true;
}

/**
 * Sync temporary guest cart items into Database
 * @param int $user_id
 */
function sync_guest_cart($user_id) {
    global $pdo;
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $bouquet_id => $quantity) {
            // Check if item exists in user's cart
            $stmt = $pdo->prepare("SELECT id, quantity FROM cart_items WHERE user_id = ? AND bouquet_id = ?");
            $stmt->execute([$user_id, $bouquet_id]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Update quantity
                $new_qty = $existing['quantity'] + $quantity;
                $upd = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
                $upd->execute([$new_qty, $existing['id']]);
            } else {
                // Insert new item
                $ins = $pdo->prepare("INSERT INTO cart_items (user_id, bouquet_id, quantity) VALUES (?, ?, ?)");
                $ins->execute([$user_id, $bouquet_id, $quantity]);
            }
        }
        // Empty guest session cart
        unset($_SESSION['cart']);
    }
}

/**
 * Redirect non-logged-in users
 */
function require_login() {
    if (!is_logged_in()) {
        set_flash('error', 'Please log in to continue.');
        header("Location: login.php");
        exit;
    }
}

/**
 * Redirect non-logged-in administrators
 */
function require_admin() {
    if (!is_admin_logged_in()) {
        set_flash('error', 'Administrator access restricted. Please log in.');
        // Works from both root-level and admin/ subdirectory pages
        $is_in_admin = (str_contains($_SERVER['PHP_SELF'] ?? '', '/admin/'));
        $redirect = $is_in_admin ? '../login.php' : 'login.php';
        header("Location: $redirect");
        exit;
    }
}
