<?php
/**
 * Ninya Flower Shop - Premium Romantic Floral Boutique
 * Shop Catalog (Pinterest Layout & Filters)
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/session.php';

// Log analytics page view
log_analytics('page_view', ['page' => 'shop']);

// Retrieve filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_slug = isset($_GET['category']) ? trim($_GET['category']) : '';
$filter_tag = isset($_GET['tag']) ? trim($_GET['tag']) : '';

// Build Query
$query_str = "SELECT b.*, c.name as category_name, c.slug as category_slug 
              FROM bouquets b 
              LEFT JOIN categories c ON b.category_id = c.id 
              WHERE b.stock > 0";
$params = [];

if ($search !== '') {
    $query_str .= " AND (b.name LIKE ? OR b.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category_slug !== '') {
    $query_str .= " AND c.slug = ?";
    $params[] = $category_slug;
}

if ($filter_tag === 'featured') {
    $query_str .= " AND b.is_featured = 1";
} elseif ($filter_tag === 'bestseller') {
    $query_str .= " AND b.is_best_seller = 1";
} elseif ($filter_tag === 'wedding') {
    $query_str .= " AND b.is_wedding = 1";
}

// Order by date added
$query_str .= " ORDER BY b.created_at DESC";

try {
    $stmt = $pdo->prepare($query_str);
    $stmt->execute($params);
    $bouquets = $stmt->fetchAll();
    
    // Fetch categories for sidebar filter
    $categories = get_categories();
} catch (PDOException $e) {
    $bouquets = [];
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Browse our luxury hand-tied bouquets, roses, tulips, and dried flowers in our signature Pinterest florist grid catalog.">
    <title>The Floral Catalog - Ninya Flower Shop</title>
    
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body style="background-color: var(--bg-warm);">

    <!-- Header Navigation -->
    <?php include __DIR__ . '/components/navbar.php'; ?>

    <!-- Editorial Intro Header -->
    <section style="padding: 5rem 0 3rem 0; text-align: center;">
        <div class="container" style="max-width: 600px;">
            <span class="section-subtitle">Delicate Selections</span>
            <h1 style="font-family: var(--font-serif); font-size: 3.5rem; color: var(--text-charcoal); margin-bottom: 1.5rem;">
                The Bouquet Journal
            </h1>
            <p style="font-size: 1rem; color: var(--text-muted); font-weight: 300;">
                Select from our signature hand-tied arrangements, carefully sourced and detailed with our signature meanings to deliver profound romance.
            </p>
        </div>
    </section>

    <!-- Main Filter Workspace & Grid -->
    <section style="padding-bottom: 7rem;">
        <div class="container">
            
            <!-- Filters bar -->
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem 0; margin-bottom: 3.5rem; border-top: var(--border-soft); border-bottom: var(--border-soft); gap: 2rem; flex-wrap: wrap;">
                <!-- Category swatches -->
                <div style="display: flex; gap: 1rem; overflow-x: auto; padding-bottom: 0.5rem; flex: 1;">
                    <a href="shop.php" class="btn" style="padding: 0.5rem 1.2rem; font-size: 0.75rem; border: 1px solid <?php echo $category_slug === '' && $filter_tag === '' ? 'var(--gold)' : 'rgba(143,168,155,0.2)'; ?>; background: <?php echo $category_slug === '' && $filter_tag === '' ? 'var(--sage-light)' : 'transparent'; ?>; color: var(--text-charcoal); flex-shrink: 0; white-space: nowrap;">All Stems</a>
                    
                    <a href="shop.php?tag=featured" class="btn" style="padding: 0.5rem 1.2rem; font-size: 0.75rem; border: 1px solid <?php echo $filter_tag === 'featured' ? 'var(--gold)' : 'rgba(143,168,155,0.2)'; ?>; background: <?php echo $filter_tag === 'featured' ? 'var(--sage-light)' : 'transparent'; ?>; color: var(--text-charcoal); flex-shrink: 0; white-space: nowrap;">Featured</a>
                    
                    <a href="shop.php?tag=bestseller" class="btn" style="padding: 0.5rem 1.2rem; font-size: 0.75rem; border: 1px solid <?php echo $filter_tag === 'bestseller' ? 'var(--gold)' : 'rgba(143,168,155,0.2)'; ?>; background: <?php echo $filter_tag === 'bestseller' ? 'var(--sage-light)' : 'transparent'; ?>; color: var(--text-charcoal); flex-shrink: 0; white-space: nowrap;">Best Sellers</a>
                    
                    <a href="shop.php?tag=wedding" class="btn" style="padding: 0.5rem 1.2rem; font-size: 0.75rem; border: 1px solid <?php echo $filter_tag === 'wedding' ? 'var(--gold)' : 'rgba(143,168,155,0.2)'; ?>; background: <?php echo $filter_tag === 'wedding' ? 'var(--sage-light)' : 'transparent'; ?>; color: var(--text-charcoal); flex-shrink: 0; white-space: nowrap;">Weddings</a>
                    
                    <?php foreach ($categories as $cat): ?>
                        <a href="shop.php?category=<?php echo $cat['slug']; ?>" class="btn" style="padding: 0.5rem 1.2rem; font-size: 0.75rem; border: 1px solid <?php echo $category_slug === $cat['slug'] ? 'var(--gold)' : 'rgba(143,168,155,0.2)'; ?>; background: <?php echo $category_slug === $cat['slug'] ? 'var(--sage-light)' : 'transparent'; ?>; color: var(--text-charcoal); white-space: nowrap; flex-shrink: 0;"><?php echo e($cat['name']); ?></a>
                    <?php endforeach; ?>
                </div>
                
                <!-- Text Search inputs -->
                <form action="shop.php" method="GET" style="display: flex; border: 1px solid rgba(143,168,155,0.2); background-color: var(--bg-white);">
                    <?php if ($category_slug): ?>
                        <input type="hidden" name="category" value="<?php echo e($category_slug); ?>">
                    <?php endif; ?>
                    <?php if ($filter_tag): ?>
                        <input type="hidden" name="tag" value="<?php echo e($filter_tag); ?>">
                    <?php endif; ?>
                    <input type="text" name="search" placeholder="Search bouquet..." value="<?php echo e($search); ?>" style="border: none; padding: 0.6rem 1rem; font-size: 0.8rem; width: 220px; background: transparent;">
                    <button type="submit" style="background: transparent; border: none; padding: 0 1rem; cursor: pointer; color: var(--text-muted);">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
            
            <!-- Dynamic Grid rendering (Pinterest Layout) -->
            <?php if (!empty($bouquets)): ?>
                <div class="pinterest-grid">
                    <?php foreach ($bouquets as $bouquet): ?>
                        <div class="pinterest-item">
                            <?php include __DIR__ . '/components/bouquet-card.php'; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 6rem 0;">
                    <i class="fas fa-seedling" style="font-size: 3rem; color: var(--accent-blush); margin-bottom: 1.5rem;"></i>
                    <h3 style="font-family: var(--font-serif); font-size: 1.8rem; color: var(--text-charcoal); margin-bottom: 0.5rem;">No Blooms Found</h3>
                    <p style="color: var(--text-muted); font-weight: 300;">We couldn't find any flower arrangements matching your active filters.</p>
                    <a href="shop.php" class="btn btn-primary" style="margin-top: 2rem;">Clear Filters</a>
                </div>
            <?php endif; ?>
            
        </div>
    </section>

    <!-- Footer Component -->
    <?php include __DIR__ . '/components/footer.php'; ?>

</body>
</html>
