<?php
/**
 * Ninya Flower Shop - Premium Romantic Floral Boutique
 * Wishlist System (Supports GET Page & POST AJAX)
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';

// Check if Request is AJAX POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (!is_logged_in()) {
        echo json_encode(['status' => 'unauthorized', 'message' => 'Please log in to save bouquets.']);
        exit;
    }
    
    $action = isset($_POST['action']) ? trim($_POST['action']) : '';
    $bouquet_id = isset($_POST['bouquet_id']) ? (int)$_POST['bouquet_id'] : 0;
    
    if ($action === 'toggle' && $bouquet_id > 0) {
        $user_id = $_SESSION['user_id'];
        
        try {
            // Check if already in wishlist
            $check = $pdo->prepare("SELECT id FROM wishlist_items WHERE user_id = ? AND bouquet_id = ?");
            $check->execute([$user_id, $bouquet_id]);
            $existing = $check->fetch();
            
            $added = false;
            if ($existing) {
                // Remove
                $del = $pdo->prepare("DELETE FROM wishlist_items WHERE id = ?");
                $del->execute([$existing['id']]);
                log_analytics('wishlist_remove', ['bouquet_id' => $bouquet_id]);
            } else {
                // Add
                $ins = $pdo->prepare("INSERT INTO wishlist_items (user_id, bouquet_id) VALUES (?, ?)");
                $ins->execute([$user_id, $bouquet_id]);
                $added = true;
                log_analytics('wishlist_add', ['bouquet_id' => $bouquet_id]);
            }
            
            // Get new wishlist count
            $wish_count = get_wishlist_count();
            
            echo json_encode([
                'status' => 'success',
                'added' => $added,
                'count' => $wish_count
            ]);
            exit;
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
            exit;
        }
    }
    
    echo json_encode(['status' => 'error', 'message' => 'Invalid actions.']);
    exit;
}

// --------------------------------------------------
// GET Page Rendering
// --------------------------------------------------
require_login(); // Force login on page rendering

log_analytics('page_view', ['page' => 'wishlist']);

// Fetch user's wishlist bouquets
$wishlist_bouquets = [];
try {
    $stmt = $pdo->prepare("SELECT b.*, c.name as category_name 
                           FROM wishlist_items wi 
                           JOIN bouquets b ON wi.bouquet_id = b.id 
                           LEFT JOIN categories c ON b.category_id = c.id 
                           WHERE wi.user_id = ? AND b.stock > 0");
    $stmt->execute([$_SESSION['user_id']]);
    $wishlist_bouquets = $stmt->fetchAll();
} catch (PDOException $e) {
    $wishlist_bouquets = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saved Blooms - Ninya Flower Shop</title>
    
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body style="background-color: var(--bg-warm);">

    <!-- Header Navigation -->
    <?php include __DIR__ . '/components/navbar.php'; ?>

    <div class="container" style="padding-top: 4rem; padding-bottom: 7rem;">
        
        <span class="section-subtitle">Your Sanctuary</span>
        <h1 style="font-family: var(--font-serif); font-size: 3.5rem; text-align: center; color: var(--text-charcoal); margin-bottom: 4rem;">
            Saved Blooms
        </h1>
        
        <?php if (!empty($wishlist_bouquets)): ?>
            <!-- Standard dynamic grid layout -->
            <div class="grid-3">
                <?php 
                foreach ($wishlist_bouquets as $bouquet) {
                    include __DIR__ . '/components/bouquet-card.php';
                }
                ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 6rem 0; max-width: 500px; margin: 0 auto;">
                <i class="far fa-heart" style="font-size: 4rem; color: var(--accent-blush); margin-bottom: 2rem;"></i>
                <h3 style="font-family: var(--font-serif); font-size: 2rem; color: var(--text-charcoal); margin-bottom: 0.8rem;">Your Wishlist is Empty</h3>
                <p style="color: var(--text-muted); line-height: 1.7; font-weight: 300; margin-bottom: 2.5rem;">
                    You have not saved any beautiful, romantic flower designs yet. Press the heart icon on any bouquet to keep them in this private garden.
                </p>
                <a href="shop.php" class="btn btn-primary">Discover Bouquets</a>
            </div>
        <?php endif; ?>
        
    </div>

    <!-- Footer Component -->
    <?php include __DIR__ . '/components/footer.php'; ?>

</body>
</html>
