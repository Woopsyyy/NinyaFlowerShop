<?php
/**
 * Ninya Flower Shop - Premium Romantic Floral Boutique
 * Homepage Landing Experience
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/session.php';

// Log page view analytics
log_analytics('page_view', ['page' => 'home']);

// Fetch Featured Bouquets
try {
    $stmt = $pdo->prepare("SELECT b.*, c.name as category_name 
                           FROM bouquets b 
                           LEFT JOIN categories c ON b.category_id = c.id 
                           WHERE b.is_featured = 1 AND b.stock > 0 
                           LIMIT 3");
    $stmt->execute();
    $featured_bouquets = $stmt->fetchAll();
    
    // Fetch Best Sellers
    $stmt = $pdo->prepare("SELECT b.*, c.name as category_name 
                           FROM bouquets b 
                           LEFT JOIN categories c ON b.category_id = c.id 
                           WHERE b.is_best_seller = 1 AND b.stock > 0 
                           LIMIT 4");
    $stmt->execute();
    $best_sellers = $stmt->fetchAll();

    // Fetch Pinterest Showcase items (all active bouquets)
    $stmt = $pdo->prepare("SELECT b.*, c.name as category_name 
                           FROM bouquets b 
                           LEFT JOIN categories c ON b.category_id = c.id 
                           ORDER BY RAND() LIMIT 6");
    $stmt->execute();
    $showcase_bouquets = $stmt->fetchAll();
    
    // Fetch testimonials
    $stmt = $pdo->query("SELECT * FROM testimonials WHERE is_active = 1 LIMIT 3");
    $testimonials = $stmt->fetchAll();
} catch (PDOException $e) {
    $featured_bouquets = [];
    $best_sellers = [];
    $showcase_bouquets = [];
    $testimonials = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Ninya Flower Shop is a premium, romantic Instagram florist and handcrafted boutique designing cinematic floral storytelling campaigns.">
    <title>Ninya Flower Shop - Premium Romantic Floral Boutique</title>
    
    <!-- Design styling -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body>

    <!-- Header Navigation -->
    <?php include __DIR__ . '/components/navbar.php'; ?>

    <!-- 1. Cinematic Hero Section -->
    <section class="hero-viewport">
        <!-- Soft background atmospheric video (represented via curated CSS colors & overlays, with petal particle canvas) -->
        <div class="hero-gradient"></div>
        <canvas id="petals-canvas"></canvas>
        
        <div class="hero-content">
            <span class="hero-tagline">Instagram Florist &bull; Luxury Handcrafted</span>
            <h1 class="hero-title">Cinematic <span>floral</span> creations for romantic souls</h1>
            <p class="hero-desc">
                Exquisite, thoughtfully curated bloom arrangements that tell an emotional story. Hand-tied by luxury artisans to elevate your celebratory spaces.
            </p>
            <div class="hero-actions">
                <a href="shop.php" class="btn btn-primary">Shop Bouquets</a>
                <a href="custom-request.php" class="btn btn-secondary">Bespoke Requests</a>
            </div>
        </div>
    </section>

    <!-- 2. Brand Introduction Philosophy -->
    <section style="padding: 7rem 0; background-color: var(--bg-warm); text-align: center;">
        <div class="container" style="max-width: 800px;">
            <span class="section-subtitle">Our Philosophy</span>
            <h2 style="font-family: var(--font-serif); font-size: 2.8rem; line-height: 1.3; color: var(--text-charcoal); margin-bottom: 2rem;">
                Crafting visual poems with delicate organic stems
            </h2>
            <p style="font-size: 1.1rem; line-height: 1.8; color: var(--text-muted); font-weight: 300;">
                Every petal placed in our signature designs carries a profound emotional rhythm. We eschew factory line assembly in favor of high-art floristry, designing customized, premium experiences that celebrate milestones, unions, and the pure, calming language of nature.
            </p>
        </div>
    </section>

    <!-- 3. Romantic Collections Grid -->
    <section style="padding: 5rem 0; background-color: var(--bg-white);">
        <div class="container">
            <span class="section-subtitle">The Collections</span>
            <h2 class="section-title">Browse by Aesthetic</h2>
            
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-top: 3rem;">
                <!-- Roses -->
                <a href="shop.php?category=rose-bouquets" style="position: relative; height: 350px; overflow: hidden; display: block;" class="collection-panel">
                    <img src="assets/images/amour_rose.png" alt="Rose Bouquets" style="width:100%; height:100%; object-fit:cover; transition: var(--transition-smooth);" class="panel-img">
                    <div style="position: absolute; bottom: 0; left: 0; width: 100%; padding: 2rem 1.5rem; background: linear-gradient(to top, rgba(58,50,47,0.7) 0%, rgba(58,50,47,0) 100%); color: var(--bg-warm); text-align: center;">
                        <h4 style="font-size: 1.4rem;">Roses</h4>
                    </div>
                </a>
                
                <!-- Tulips -->
                <a href="shop.php?category=tulips" style="position: relative; height: 350px; overflow: hidden; display: block;" class="collection-panel">
                    <img src="assets/images/whispering_tulips.png" alt="Tulips" style="width:100%; height:100%; object-fit:cover; transition: var(--transition-smooth);" class="panel-img">
                    <div style="position: absolute; bottom: 0; left: 0; width: 100%; padding: 2rem 1.5rem; background: linear-gradient(to top, rgba(58,50,47,0.7) 0%, rgba(58,50,47,0) 100%); color: var(--bg-warm); text-align: center;">
                        <h4 style="font-size: 1.4rem;">Tulips</h4>
                    </div>
                </a>
                
                <!-- Sunflowers -->
                <a href="shop.php?category=sunflowers" style="position: relative; height: 350px; overflow: hidden; display: block;" class="collection-panel">
                    <img src="assets/images/golden_solstice.png" alt="Sunflowers" style="width:100%; height:100%; object-fit:cover; transition: var(--transition-smooth);" class="panel-img">
                    <div style="position: absolute; bottom: 0; left: 0; width: 100%; padding: 2rem 1.5rem; background: linear-gradient(to top, rgba(58,50,47,0.7) 0%, rgba(58,50,47,0) 100%); color: var(--bg-warm); text-align: center;">
                        <h4 style="font-size: 1.4rem;">Sunflowers</h4>
                    </div>
                </a>
                
                <!-- Wedding -->
                <a href="shop.php?category=wedding-flowers" style="position: relative; height: 350px; overflow: hidden; display: block;" class="collection-panel">
                    <img src="assets/images/elysian_meadow.png" alt="Wedding Flowers" style="width:100%; height:100%; object-fit:cover; transition: var(--transition-smooth);" class="panel-img">
                    <div style="position: absolute; bottom: 0; left: 0; width: 100%; padding: 2rem 1.5rem; background: linear-gradient(to top, rgba(58,50,47,0.7) 0%, rgba(58,50,47,0) 100%); color: var(--bg-warm); text-align: center;">
                        <h4 style="font-size: 1.4rem;">Weddings</h4>
                    </div>
                </a>
            </div>
        </div>
    </section>

    <!-- 4. Featured Stems section -->
    <section style="padding: 6rem 0; background-color: var(--bg-warm);">
        <div class="container">
            <span class="section-subtitle">Signature Selection</span>
            <h2 class="section-title">The Featured Creations</h2>
            
            <div class="grid-3" style="margin-top: 3.5rem;">
                <?php 
                if (!empty($featured_bouquets)) {
                    foreach ($featured_bouquets as $bouquet) {
                        include __DIR__ . '/components/bouquet-card.php';
                    }
                } else {
                    echo '<p style="text-align:center;grid-column: 1/-1;color:var(--text-muted);">No featured bouquets available.</p>';
                }
                ?>
            </div>
        </div>
    </section>

    <!-- 5. Interactive Wedding Section (Visual campaign layout) -->
    <section style="position: relative; padding: 12rem 0; background: url('assets/images/elysian_meadow.png') no-repeat center center/cover;">
        <div style="position: absolute; top:0; left:0; width:100%; height:100%; background-color: rgba(58, 50, 47, 0.45); z-index: 1;"></div>
        <div class="container" style="position: relative; z-index: 5; max-width: 600px; margin-left: 2rem; color: var(--bg-warm);">
            <span style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.2em; color: var(--accent-blush); font-weight: 500;">Bespoke Unions</span>
            <h2 style="font-family: var(--font-serif); font-size: 3.5rem; line-height: 1.2; margin: 1.5rem 0 2rem 0; font-weight: 300;">Wedding Floral Artistry</h2>
            <p style="font-size: 1.05rem; line-height: 1.8; margin-bottom: 2.5rem; opacity: 0.95; font-weight: 200;">
                From delicate hand-tied bridal bouquets to grand cinematic floral arches. Let Ninya craft the floral narrative of your dream wedding with white peonies, sage green foliage, and sweet garden ranunculus.
            </p>
            <a href="custom-request.php?type=wedding" class="btn" style="background-color: var(--bg-warm); color: var(--text-charcoal); border: none;">Book Consultation</a>
        </div>
    </section>

    <!-- 6. Best Sellers Catalog section -->
    <section style="padding: 6rem 0; background-color: var(--bg-white);">
        <div class="container">
            <span class="section-subtitle">Highly Adored</span>
            <h2 class="section-title">Romantic Best Sellers</h2>
            
            <div class="grid-4" style="margin-top: 3.5rem;">
                <?php 
                if (!empty($best_sellers)) {
                    foreach ($best_sellers as $bouquet) {
                        include __DIR__ . '/components/bouquet-card.php';
                    }
                } else {
                    echo '<p style="text-align:center;grid-column:1/-1;color:var(--text-muted);">No best sellers available.</p>';
                }
                ?>
            </div>
        </div>
    </section>

    <!-- 7. Pinterest-style Showcase Grid -->
    <section style="padding: 6rem 0; background-color: var(--bg-warm);">
        <div class="container">
            <span class="section-subtitle">Floral Editorial</span>
            <h2 class="section-title">Pinterest Showcase</h2>
            
            <div class="pinterest-grid" style="margin-top: 3.5rem;">
                <?php 
                if (!empty($showcase_bouquets)) {
                    foreach ($showcase_bouquets as $bouquet) {
                        echo '<div class="pinterest-item">';
                        include __DIR__ . '/components/bouquet-card.php';
                        echo '</div>';
                    }
                } else {
                    echo '<p style="text-align:center;grid-column:1/-1;color:var(--text-muted);">No showcase items available.</p>';
                }
                ?>
            </div>
        </div>
    </section>

    <!-- 8. Testimonials carousel section -->
    <section style="padding: 6rem 0; background-color: var(--bg-white); border-top: var(--border-soft);">
        <div class="container" style="max-width: 800px; text-align: center;">
            <span class="section-subtitle">Gratitude</span>
            <h2 class="section-title" style="margin-bottom: 3.5rem;">Boutique Testimonials</h2>
            
            <div style="position: relative;">
                <?php if (!empty($testimonials)): ?>
                    <div style="display: flex; flex-direction: column; align-items: center; gap: 1.5rem;">
                        <i class="fas fa-quote-left" style="font-size: 2rem; color: var(--accent-blush);"></i>
                        <p id="testimonial-quote" style="font-family: var(--font-serif); font-size: 1.6rem; font-style: italic; line-height: 1.6; color: var(--text-charcoal);">
                            "<?php echo e($testimonials[0]['quote']); ?>"
                        </p>
                        <h4 id="testimonial-author" style="font-family: var(--font-sans); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.15em; font-weight: 600; color: var(--gold); margin-top: 1rem;">
                            &mdash; <?php echo e($testimonials[0]['name']); ?>, <?php echo e($testimonials[0]['role']); ?>
                        </h4>
                    </div>
                <?php else: ?>
                    <p style="color:var(--text-muted);">Thank you to all our customers worldwide.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer component -->
    <?php include __DIR__ . '/components/footer.php'; ?>
    
    <!-- Script overrides -->
    <script src="assets/js/petals.js"></script>
    <script>
        // Smooth hover transition scaling for Collections Grid
        document.querySelectorAll('.collection-panel').forEach(panel => {
            panel.addEventListener('mouseenter', () => {
                panel.querySelector('.panel-img').style.transform = 'scale(1.05)';
            });
            panel.addEventListener('mouseleave', () => {
                panel.querySelector('.panel-img').style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>
