<?php
/**
 * Reusable Premium Bouquet Card Component
 * Requires variable $bouquet (array) to be loaded before inclusion.
 */

if (!isset($bouquet)) {
    return;
}

// Check if this specific item is in user's wishlist
$is_wished = false;
if (is_logged_in()) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM wishlist_items WHERE user_id = ? AND bouquet_id = ?");
    $stmt->execute([$_SESSION['user_id'], $bouquet['id']]);
    if ($stmt->fetch()) {
        $is_wished = true;
    }
}

// Compute badge
$badge = '';
if ($bouquet['stock'] <= 0) {
    $badge = 'Out of Stock';
} elseif ($bouquet['sale_price'] !== null && $bouquet['sale_price'] < $bouquet['price']) {
    $badge = 'Offer';
} elseif ($bouquet['is_featured']) {
    $badge = 'Signature';
} elseif ($bouquet['is_best_seller']) {
    $badge = 'Popular';
}
?>

<div class="bouquet-card">
    <div class="card-img-wrapper">
        <!-- Visual badge tag -->
        <?php if ($badge): ?>
            <span class="card-badge"><?php echo e($badge); ?></span>
        <?php endif; ?>
        
        <!-- Wishlist Button -->
        <button class="card-wishlist-btn wishlist-trigger-btn <?php echo $is_wished ? 'active' : ''; ?>" 
                data-bouquet-id="<?php echo $bouquet['id']; ?>" 
                title="<?php echo $is_wished ? 'Remove from wishlist' : 'Save to wishlist'; ?>">
            <i class="<?php echo $is_wished ? 'fas' : 'far'; ?> fa-heart"></i>
        </button>
        
        <!-- Main Link Bouquet Details -->
        <a href="bouquet.php?slug=<?php echo $bouquet['slug']; ?>">
            <img src="<?php echo e($bouquet['image_url']); ?>" alt="<?php echo e($bouquet['name']); ?>" class="bouquet-card-img" loading="lazy">
        </a>
    </div>
    
    <!-- Info -->
    <div class="card-info">
        <span class="card-category">
            <?php echo isset($bouquet['category_name']) ? e($bouquet['category_name']) : 'Bespoke Floral'; ?>
        </span>
        <h3 class="card-title">
            <a href="bouquet.php?slug=<?php echo $bouquet['slug']; ?>"><?php echo e($bouquet['name']); ?></a>
        </h3>
        
        <div class="card-price">
            <?php if ($bouquet['sale_price'] !== null && $bouquet['sale_price'] < $bouquet['price']): ?>
                <span class="original-price"><?php echo format_price($bouquet['price']); ?></span>
                <span class="sale-price"><?php echo format_price($bouquet['sale_price']); ?></span>
            <?php else: ?>
                <span><?php echo format_price($bouquet['price']); ?></span>
            <?php endif; ?>
        </div>
    </div>
</div>
