<?php
/**
 * Ninya Flower Shop - Premium Romantic Floral Boutique
 * Bouquet Product Detail Page
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/session.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (!$slug) {
    header("Location: shop.php");
    exit;
}

// Fetch main bouquet
try {
    $stmt = $pdo->prepare("SELECT b.*, c.name as category_name, c.slug as category_slug 
                           FROM bouquets b 
                           LEFT JOIN categories c ON b.category_id = c.id 
                           WHERE b.slug = ? LIMIT 1");
    $stmt->execute([$slug]);
    $bouquet = $stmt->fetch();
} catch (PDOException $e) {
    $bouquet = null;
}

if (!$bouquet) {
    set_flash('error', 'The requested bouquet was not found in our catalog.');
    header("Location: shop.php");
    exit;
}

// Log view analytics
log_analytics('product_view', ['bouquet_id' => $bouquet['id'], 'name' => $bouquet['name']]);

// Fetch secondary gallery images
try {
    $stmt = $pdo->prepare("SELECT * FROM bouquet_images WHERE bouquet_id = ? ORDER BY sort_order ASC");
    $stmt->execute([$bouquet['id']]);
    $gallery_images = $stmt->fetchAll();
} catch (PDOException $e) {
    $gallery_images = [];
}

// Fetch approved reviews
try {
    $stmt = $pdo->prepare("SELECT r.*, u.name as user_name 
                           FROM reviews r 
                           JOIN users u ON r.user_id = u.id 
                           WHERE r.bouquet_id = ? AND r.is_approved = 1 
                           ORDER BY r.created_at DESC");
    $stmt->execute([$bouquet['id']]);
    $reviews = $stmt->fetchAll();
    
    // Average rating
    $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM reviews WHERE bouquet_id = ? AND is_approved = 1");
    $stmt->execute([$bouquet['id']]);
    $rating_stats = $stmt->fetch();
    $avg_rating = round($rating_stats['avg_rating'] ?? 5.0, 1);
    $rating_count = (int)$rating_stats['count'];
} catch (PDOException $e) {
    $reviews = [];
    $avg_rating = 5.0;
    $rating_count = 0;
}

// Handle Add to Cart submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    if ($quantity < 1) $quantity = 1;
    
    if (is_logged_in()) {
        $user_id = $_SESSION['user_id'];
        // Check if item already exists in database cart
        $check = $pdo->prepare("SELECT id, quantity FROM cart_items WHERE user_id = ? AND bouquet_id = ?");
        $check->execute([$user_id, $bouquet['id']]);
        $existing = $check->fetch();
        
        if ($existing) {
            $new_qty = $existing['quantity'] + $quantity;
            $upd = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
            $upd->execute([$new_qty, $existing['id']]);
        } else {
            $ins = $pdo->prepare("INSERT INTO cart_items (user_id, bouquet_id, quantity) VALUES (?, ?, ?)");
            $ins->execute([$user_id, $bouquet['id'], $quantity]);
        }
    } else {
        // Safe Session cart for guests
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        if (isset($_SESSION['cart'][$bouquet['id']])) {
            $_SESSION['cart'][$bouquet['id']] += $quantity;
        } else {
            $_SESSION['cart'][$bouquet['id']] = $quantity;
        }
    }
    
    set_flash('success', "{$bouquet['name']} has been added to your shopping bag!");
    header("Location: bouquet.php?slug=" . $bouquet['slug']);
    exit;
}

// Handle Review Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    require_login();
    
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 5;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    
    if ($rating < 1 || $rating > 5) $rating = 5;
    
    try {
        $ins = $pdo->prepare("INSERT INTO reviews (user_id, bouquet_id, rating, comment, is_approved) VALUES (?, ?, ?, ?, 1)"); // Auto approve reviews in local setup for testing ease
        $ins->execute([$_SESSION['user_id'], $bouquet['id'], $rating, $comment]);
        set_flash('success', 'Thank you! Your romantic floral review has been successfully posted.');
    } catch (PDOException $e) {
        set_flash('error', 'Failed to publish review. Please try again.');
    }
    
    header("Location: bouquet.php?slug=" . $bouquet['slug']);
    exit;
}

// Fetch Related Bouquets (same category, excluding current product)
try {
    $stmt = $pdo->prepare("SELECT b.*, c.name as category_name 
                           FROM bouquets b 
                           LEFT JOIN categories c ON b.category_id = c.id 
                           WHERE b.category_id = ? AND b.id != ? AND b.stock > 0 
                           LIMIT 3");
    $stmt->execute([$bouquet['category_id'], $bouquet['id']]);
    $related_bouquets = $stmt->fetchAll();
} catch (PDOException $e) {
    $related_bouquets = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="View the meaning, description, and purchase options for the premium hand-tied <?php echo e($bouquet['name']); ?> bouquet.">
    <title><?php echo e($bouquet['name']); ?> - Ninya Flower Shop</title>
    
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body style="background-color: var(--bg-warm);">

    <!-- Header Navigation -->
    <?php include __DIR__ . '/components/navbar.php'; ?>

    <div class="container" style="padding-top: 3rem; padding-bottom: 6rem;">
        
        <!-- Flash notifications inside details -->
        <?php if ($success = get_flash('success')): ?>
            <div class="alert alert-success"><?php echo e($success); ?></div>
        <?php endif; ?>
        <?php if ($error = get_flash('error')): ?>
            <div class="alert alert-error"><?php echo e($error); ?></div>
        <?php endif; ?>

        <!-- Split Grid (Visual assets on left, Details on right) -->
        <div class="grid-2">
            
            <!-- 1. Interactive Image Showcase Gallery -->
            <div>
                <div class="gallery-main">
                    <img id="main-gallery-img" src="<?php echo e($bouquet['image_url']); ?>" alt="<?php echo e($bouquet['name']); ?>">
                </div>
                
                <!-- Thumbnails -->
                <div class="gallery-thumbs">
                    <!-- Default main image as first thumbnail -->
                    <div class="gallery-thumb-item active" data-src="<?php echo e($bouquet['image_url']); ?>">
                        <img src="<?php echo e($bouquet['image_url']); ?>" alt="<?php echo e($bouquet['name']); ?> thumb">
                    </div>
                    <?php foreach ($gallery_images as $g_img): ?>
                        <div class="gallery-thumb-item" data-src="<?php echo e($g_img['image_url']); ?>">
                            <img src="<?php echo e($g_img['image_url']); ?>" alt="Detail Gallery Thumb">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- 2. Purchase Actions & Stems Metadata -->
            <div>
                <!-- Category and Title -->
                <span style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.2em; color: var(--sage-green); font-weight: 500; display: block; margin-bottom: 0.8rem;">
                    <?php echo e($bouquet['category_name']); ?>
                </span>
                <h1 style="font-family: var(--font-serif); font-size: 3.4rem; color: var(--text-charcoal); line-height: 1.1; margin-bottom: 1rem;">
                    <?php echo e($bouquet['name']); ?>
                </h1>
                
                <!-- Rating review indicator -->
                <div style="display: flex; align-items: center; gap: 0.8rem; margin-bottom: 2rem; font-size: 0.9rem; color: var(--text-muted);">
                    <div style="color: var(--gold);">
                        <?php
                        $full_stars = floor($avg_rating);
                        for ($i = 0; $i < 5; $i++) {
                            if ($i < $full_stars) {
                                echo '<i class="fas fa-star"></i>';
                            } else {
                                echo '<i class="far fa-star"></i>';
                            }
                        }
                        ?>
                    </div>
                    <span><?php echo $avg_rating; ?>/5.0 (<?php echo $rating_count; ?> customer reviews)</span>
                </div>
                
                <!-- Pricing tags -->
                <div style="font-size: 1.8rem; margin-bottom: 2rem; color: var(--text-charcoal); font-weight: 300;">
                    <?php if ($bouquet['sale_price'] !== null && $bouquet['sale_price'] < $bouquet['price']): ?>
                        <span style="text-decoration: line-through; color: var(--text-muted); font-size: 1.3rem; margin-right: 0.8rem;">
                            <?php echo format_price($bouquet['price']); ?>
                        </span>
                        <span style="color: var(--gold); font-weight: 500;">
                            <?php echo format_price($bouquet['sale_price']); ?>
                        </span>
                    <?php else: ?>
                        <span><?php echo format_price($bouquet['price']); ?></span>
                    <?php endif; ?>
                </div>
                
                <!-- Editorial Description -->
                <p style="font-size: 1.05rem; line-height: 1.8; color: var(--text-muted); margin-bottom: 2rem; font-weight: 300;">
                    <?php echo e($bouquet['description']); ?>
                </p>
                
                <!-- Signature Flower Meaning (The requested emotional storytelling block) -->
                <div class="meaning-card">
                    <h3 class="meaning-title">
                        <i class="fas fa-feather-alt" style="margin-right: 0.5rem; font-size: 0.95rem;"></i> Signature Bloom Meaning
                    </h3>
                    <p class="meaning-text">
                        "<?php echo e($bouquet['meaning']); ?>"
                    </p>
                </div>
                
                <!-- Purchase form & Quantity controls -->
                <form action="bouquet.php?slug=<?php echo e($bouquet['slug']); ?>" method="POST" style="margin-top: 2.5rem; display: flex; flex-direction: column; gap: 1.5rem;">
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <span class="form-label" style="margin-bottom: 0;">Quantity:</span>
                        <div style="display: flex; border: 1px solid rgba(143,168,155,0.2); background-color: var(--bg-white);">
                            <button type="button" onclick="const q = document.getElementById('qty'); if (q.value > 1) q.value--;" style="background:transparent; border:none; padding: 0.8rem 1.2rem; cursor:pointer;"><i class="fas fa-minus" style="font-size:0.75rem;"></i></button>
                            <input type="number" id="qty" name="quantity" value="1" min="1" max="<?php echo $bouquet['stock']; ?>" readonly style="border:none; text-align:center; width:50px; font-weight:600; font-size:0.9rem; background:transparent;">
                            <button type="button" onclick="const q = document.getElementById('qty'); if (q.value < <?php echo $bouquet['stock']; ?>) q.value++;" style="background:transparent; border:none; padding: 0.8rem 1.2rem; cursor:pointer;"><i class="fas fa-plus" style="font-size:0.75rem;"></i></button>
                        </div>
                        <span style="font-size: 0.8rem; color: var(--sage-green); font-weight: 500;">(<?php echo $bouquet['stock']; ?> bundles left in boutique)</span>
                    </div>
                    
                    <div style="display: flex; gap: 1rem; width: 100%;">
                        <button type="submit" name="add_to_cart" class="btn btn-primary" style="flex: 1; padding: 1.1rem 2rem;">
                            Add To Shopping Bag
                        </button>
                        
                        <?php
                        $is_wished = false;
                        if (is_logged_in()) {
                            $stmt = $pdo->prepare("SELECT id FROM wishlist_items WHERE user_id = ? AND bouquet_id = ?");
                            $stmt->execute([$_SESSION['user_id'], $bouquet['id']]);
                            if ($stmt->fetch()) $is_wished = true;
                        }
                        ?>
                        <button type="button" class="btn btn-secondary wishlist-trigger-btn <?php echo $is_wished ? 'active' : ''; ?>" data-bouquet-id="<?php echo $bouquet['id']; ?>" style="display: flex; align-items: center; justify-content: center; width: 60px; padding: 0;">
                            <i class="<?php echo $is_wished ? 'fas' : 'far'; ?> fa-heart" style="font-size: 1.1rem; color: <?php echo $is_wished ? '#e74c3c' : 'inherit'; ?>;"></i>
                        </button>
                    </div>
                </form>
                
                <!-- Brand Delivery slot details -->
                <div style="margin-top: 3rem; border-top: var(--border-soft); padding-top: 2rem; display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; font-size: 0.85rem; color: var(--text-muted);">
                    <div>
                        <h4 style="color: var(--text-charcoal); font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.4rem;">
                            Same Day Dispatch
                        </h4>
                        <p style="font-weight: 300;">Dispatched on same-day parameters if order is booked before 2:00 PM today.</p>
                    </div>
                    <div>
                        <h4 style="color: var(--text-charcoal); font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.4rem;">
                            Handcrafted wrapping
                        </h4>
                        <p style="font-weight: 300;">Includes signature linen paper, calligraphic handwritten card, and protection hydration gel.</p>
                    </div>
                </div>
            </div>
            
        </div>

        <!-- 3. Reviews Logger Tabs & Comments Section -->
        <section style="margin-top: 7rem; border-top: var(--border-soft); padding-top: 5rem;">
            <div style="max-width: 800px; margin: 0 auto;">
                <span class="section-subtitle">Stems Feedback</span>
                <h2 style="font-family: var(--font-serif); font-size: 2.2rem; text-align: center; margin-bottom: 3.5rem;">
                    Boutique Reviews (<?php echo $rating_count; ?>)
                </h2>
                
                <!-- Review Form -->
                <?php if (is_logged_in()): ?>
                    <div style="background-color: var(--bg-white); border: var(--border-soft); padding: 2.5rem; margin-bottom: 4rem;">
                        <h3 style="font-family: var(--font-serif); font-size: 1.6rem; color: var(--text-charcoal); margin-bottom: 1.5rem;">
                            Add Your Experience
                        </h3>
                        
                        <form action="bouquet.php?slug=<?php echo e($bouquet['slug']); ?>" method="POST">
                            <input type="hidden" name="submit_review" value="1">
                            
                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <label class="form-label">Review Rating:</label>
                                <select name="rating" required class="form-control" style="background-color: var(--bg-warm); border-color: rgba(143,168,155,0.15); width: 150px;">
                                    <option value="5">5 Stars (Excellent)</option>
                                    <option value="4">4 Stars (Beautiful)</option>
                                    <option value="3">3 Stars (Good)</option>
                                    <option value="2">2 Stars (Average)</option>
                                    <option value="1">1 Star (Dissatisfied)</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Review comments:</label>
                                <textarea name="comment" required placeholder="Describe your emotional reactions, wrapper aesthetic, and recipient response..." class="form-control" style="background-color: var(--bg-warm); border-color: rgba(143,168,155,0.15); min-height: 100px;"></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary" style="padding: 0.75rem 1.8rem; font-size: 0.8rem;">
                                Post Review
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; background-color: var(--bg-white); border: var(--border-soft); padding: 2rem; margin-bottom: 4rem;">
                        <p style="color: var(--text-muted); font-size: 0.9rem;">
                            Only registered customer accounts can log reviews. Please <a href="login.php" style="color: var(--gold); text-decoration: underline;">log in</a> to share your experience.
                        </p>
                    </div>
                <?php endif; ?>
                
                <!-- Reviews Logs List -->
                <div style="display: flex; flex-direction: column; gap: 2rem;">
                    <?php if (!empty($reviews)): ?>
                        <?php foreach ($reviews as $rev): ?>
                            <div style="border-bottom: 1px solid rgba(243, 239, 233, 0.9); padding-bottom: 2rem;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.8rem;">
                                    <div>
                                        <h4 style="font-size: 1rem; color: var(--text-charcoal); font-weight: 500; font-family: var(--font-sans);"><?php echo e($rev['user_name']); ?></h4>
                                        <div style="color: var(--gold); font-size: 0.75rem; margin-top: 0.2rem;">
                                            <?php
                                            for ($i = 0; $i < 5; $i++) {
                                                if ($i < $rev['rating']) {
                                                    echo '<i class="fas fa-star"></i>';
                                                } else {
                                                    echo '<i class="far fa-star"></i>';
                                                }
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <span style="font-size: 0.75rem; color: var(--text-muted);"><?php echo date('F d, Y', strtotime($rev['created_at'])); ?></span>
                                </div>
                                <p style="font-size: 0.9rem; line-height: 1.7; color: var(--text-muted); font-weight: 300;">
                                    "<?php echo e($rev['comment']); ?>"
                                </p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align: center; color: var(--text-muted); padding: 2rem 0; font-style: italic;">
                            No reviews have been written for this bouquet yet. Let us know what you think!
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- 4. Related Bouquets Slider Showcase -->
        <?php if (!empty($related_bouquets)): ?>
            <section style="margin-top: 7rem; border-top: var(--border-soft); padding-top: 5rem;">
                <span class="section-subtitle">Complementary Stems</span>
                <h2 style="font-family: var(--font-serif); font-size: 2.2rem; text-align: center; margin-bottom: 3.5rem;">
                    You May Also Adore
                </h2>
                
                <div class="grid-3">
                    <?php 
                    foreach ($related_bouquets as $bouquet) {
                        include __DIR__ . '/components/bouquet-card.php';
                    }
                    ?>
                </div>
            </section>
        <?php endif; ?>

    </div>

    <!-- Footer Component -->
    <?php include __DIR__ . '/components/footer.php'; ?>

</body>
</html>
