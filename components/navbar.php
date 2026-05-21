<?php
/**
 * Glassmorphic Header Navigation Component
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';

// Get current page name to set active classes
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- FontAwesome Icons for E-commerce actions -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<header class="navbar-wrapper">
    <div class="navbar-container">
        <!-- Logo Brand -->
        <a href="index.php" class="logo">
            Ninya<span>.</span>
        </a>
        
        <!-- Navigation Directories -->
        <nav>
            <ul class="nav-links">
                <li><a href="index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">Home</a></li>
                <li><a href="shop.php" class="<?php echo $current_page == 'shop.php' ? 'active' : ''; ?>">Shop</a></li>
                <li><a href="custom-request.php" class="<?php echo $current_page == 'custom-request.php' ? 'active' : ''; ?>">Custom Bouquet</a></li>
                <li><a href="about.php" class="<?php echo $current_page == 'about.php' ? 'active' : ''; ?>">Our Story</a></li>
                <li><a href="contact.php" class="<?php echo $current_page == 'contact.php' ? 'active' : ''; ?>">Contact</a></li>
                <li><a href="tracking.php" class="<?php echo $current_page == 'tracking.php' ? 'active' : ''; ?>">Track Order</a></li>
            </ul>
        </nav>
        
        <!-- User Actions (Cart, Saved items, Profile) -->
        <div class="nav-actions">
            <?php if (is_admin_logged_in()): ?>
                <a href="admin/dashboard.php" class="nav-icon" title="Admin Control Room" style="color: var(--gold);">
                    <i class="fas fa-crown"></i>
                </a>
            <?php endif; ?>
            
            <!-- Wishlist Heart Trigger -->
            <a href="wishlist.php" class="nav-icon" title="My Wishlist">
                <i class="far fa-heart"></i>
                <?php
                $wish_count = get_wishlist_count();
                ?>
                <span id="wishlist-badge" class="badge" style="display: <?php echo $wish_count > 0 ? 'block' : 'none'; ?>;">
                    <?php echo $wish_count; ?>
                </span>
            </a>
            
            <!-- Shopping Cart Icon -->
            <a href="cart.php" class="nav-icon" title="My Shopping Cart">
                <i class="fas fa-shopping-bag"></i>
                <?php
                $cart_count = get_cart_count();
                ?>
                <span id="cart-badge" class="badge" style="display: <?php echo $cart_count > 0 ? 'block' : 'none'; ?>;">
                    <?php echo $cart_count; ?>
                </span>
            </a>
            
            <!-- Profile authentication toggles -->
            <?php if (is_logged_in()): ?>
                <a href="logout.php" class="nav-icon" title="Log Out" style="font-size: 0.8rem; font-family: var(--font-sans); letter-spacing: 0.15em; text-transform: uppercase;">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            <?php else: ?>
                <a href="login.php" class="nav-icon" title="Login / Register">
                    <i class="far fa-user"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- Global spacing shim to prevent content starting behind the fixed navbar -->
<div style="height: 80px;"></div>
