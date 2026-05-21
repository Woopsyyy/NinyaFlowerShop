<?php
/**
 * Ninya Flower Shop - Our Story Page
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/session.php';

log_analytics('page_view', ['page' => 'about']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Ethereal Story - Ninya Flower Shop</title>
    
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body style="background-color: var(--bg-warm);">

    <!-- Header Navigation -->
    <?php include __DIR__ . '/components/navbar.php'; ?>

    <!-- Editorial Intro -->
    <section style="padding: 6rem 0 3rem 0; text-align: center;">
        <div class="container" style="max-width: 700px;">
            <span class="section-subtitle">Our Heritage</span>
            <h1 style="font-family: var(--font-serif); font-size: 3.8rem; color: var(--text-charcoal); line-height: 1.1; margin-bottom: 1.5rem;">
                Crafted for Ethereal Romance
            </h1>
            <p style="font-size: 1.15rem; color: var(--text-muted); font-weight: 300; line-height: 1.8;">
                Born from a passion for organic Instagram aesthetics and high-fashion editorial floristry. Ninya Flower Shop transcends traditional retail to offer custom cinematic floral storytelling.
            </p>
        </div>
    </section>

    <!-- Visual Story Layout (Text on left, Campaign image on right) -->
    <section style="padding: 5rem 0;">
        <div class="container">
            <div class="grid-2" style="align-items: center; gap: 5rem;">
                
                <!-- Text Story -->
                <div>
                    <h2 style="font-family: var(--font-serif); font-size: 2.8rem; color: var(--text-charcoal); margin-bottom: 1.8rem; line-height: 1.2;">
                        Stems selected by expert hands, designed for romantic souls
                    </h2>
                    
                    <p style="font-size: 1rem; color: var(--text-muted); line-height: 1.8; font-weight: 300; margin-bottom: 1.5rem;">
                        Our journey began in a boutique greenhouse, seeking to restore emotional depth to floral gifts. We believe that flowers are not mere commodities; they are silent messengers of devotion, apologies, celebration, and comforting presence.
                    </p>
                    
                    <p style="font-size: 1rem; color: var(--text-muted); line-height: 1.8; font-weight: 300; margin-bottom: 2rem;">
                        Every bloom in our workshop is inspected for petal density, natural structural strength, and pristine colour clarity. We source from sustainable organic local fields and wrap them inside our premium cotton linen papers, accompanied by hand-inked calligraphy notes.
                    </p>
                    
                    <a href="shop.php" class="btn btn-primary">Browse Signature Stems</a>
                </div>
                
                <!-- Visual asset -->
                <div style="position: relative; height: 500px; overflow: hidden; box-shadow: var(--shadow-hover); border: var(--border-soft);">
                    <img src="assets/images/amour_rose.png" alt="Floral styling campaign" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                
            </div>
        </div>
    </section>

    <!-- Design Core Values -->
    <section style="background-color: var(--bg-white); border-top: var(--border-soft); border-bottom: var(--border-soft); padding: 6rem 0;">
        <div class="container" style="text-align: center;">
            <span class="section-subtitle">Aesthetic Values</span>
            <h2 class="section-title" style="margin-bottom: 4rem;">The Pillars of Ninya</h2>
            
            <div class="grid-3">
                <div style="padding: 2rem; background-color: var(--bg-warm); border: var(--border-soft);">
                    <i class="fas fa-feather" style="font-size: 2.5rem; color: var(--gold); margin-bottom: 1.5rem;"></i>
                    <h3 style="font-family: var(--font-serif); font-size: 1.5rem; color: var(--text-charcoal); margin-bottom: 1rem;">
                        Artisanal Hands
                    </h3>
                    <p style="font-size: 0.9rem; color: var(--text-muted); line-height: 1.7; font-weight: 300;">
                        No templates or automated rows. Each branch, rosebud, and sage leaf is composed organically by botanical professionals.
                    </p>
                </div>
                
                <div style="padding: 2rem; background-color: var(--bg-warm); border: var(--border-soft);">
                    <i class="fas fa-hand-holding-heart" style="font-size: 2.5rem; color: var(--gold); margin-bottom: 1.5rem;"></i>
                    <h3 style="font-family: var(--font-serif); font-size: 1.5rem; color: var(--text-charcoal); margin-bottom: 1rem;">
                        Emotional Meanings
                    </h3>
                    <p style="font-size: 0.9rem; color: var(--text-muted); line-height: 1.7; font-weight: 300;">
                        We pair every signature bouquet with custom literary meanings to enrich the visual gift with literary romantic depth.
                    </p>
                </div>
                
                <div style="padding: 2rem; background-color: var(--bg-warm); border: var(--border-soft);">
                    <i class="fas fa-history" style="font-size: 2.5rem; color: var(--gold); margin-bottom: 1.5rem;"></i>
                    <h3 style="font-family: var(--font-serif); font-size: 1.5rem; color: var(--text-charcoal); margin-bottom: 1rem;">
                        Everlasting Memories
                    </h3>
                    <p style="font-size: 0.9rem; color: var(--text-muted); line-height: 1.7; font-weight: 300;">
                        From protective water-hydration gels to custom vase boxes, our designs are optimized to live longer in your romantic chambers.
                    </p>
                </div>
            </div>
            
        </div>
    </section>

    <!-- Footer Component -->
    <?php include __DIR__ . '/components/footer.php'; ?>

</body>
</html>
