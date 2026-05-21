<?php
/**
 * Ninya Flower Shop - Boutique Contact Page
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/session.php';

log_analytics('page_view', ['page' => 'contact']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Our Designers - Ninya Flower Shop</title>
    
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body style="background-color: var(--bg-warm);">

    <!-- Header Navigation -->
    <?php include __DIR__ . '/components/navbar.php'; ?>

    <div class="container" style="padding-top: 4rem; padding-bottom: 7rem;">
        
        <span class="section-subtitle">Botanical Inquiries</span>
        <h1 style="font-family: var(--font-serif); font-size: 3.5rem; text-align: center; color: var(--text-charcoal); margin-bottom: 2rem;">
            Contact Ninya
        </h1>
        <p style="text-align: center; max-width: 600px; margin: 0 auto 5rem auto; color: var(--text-muted); font-size: 1rem; line-height: 1.7; font-weight: 300;">
            Whether coordinating wedding consultancies, ordering bulk corporate dispatches, or looking for specific seasonal blooms. We'd love to chat.
        </p>

        <!-- Split Layout (Contact info on left, form on right) -->
        <div class="grid-2" style="gap: 5rem; align-items: start;">
            
            <!-- Left Info Panel -->
            <div style="background-color: var(--bg-white); border: var(--border-soft); padding: 3rem; box-shadow: var(--shadow-soft); display: flex; flex-direction: column; gap: 2.5rem;">
                
                <div>
                    <h3 style="font-family: var(--font-serif); font-size: 1.6rem; color: var(--text-charcoal); margin-bottom: 1rem;">
                        The Design Greenhouse
                    </h3>
                    <p style="font-size: 0.9rem; color: var(--text-muted); line-height: 1.8; font-weight: 300;">
                        148 Rosebud Boulevard, Suite 500<br>
                        Chelsea Floral District, NY 10011
                    </p>
                </div>
                
                <div>
                    <h3 style="font-family: var(--font-serif); font-size: 1.6rem; color: var(--text-charcoal); margin-bottom: 1rem;">
                        Direct Lines
                    </h3>
                    <p style="font-size: 0.9rem; color: var(--text-muted); line-height: 1.8; font-weight: 300;">
                        <strong>General Stems:</strong> hello@ninyaflowershop.com<br>
                        <strong>Wedding & Events:</strong> concierge@ninyaflowershop.com<br>
                        <strong>Phone Inquiries:</strong> +1 (555) 873-3567
                    </p>
                </div>
                
                <div>
                    <h3 style="font-family: var(--font-serif); font-size: 1.6rem; color: var(--text-charcoal); margin-bottom: 1rem;">
                        Artisan Hours
                    </h3>
                    <p style="font-size: 0.9rem; color: var(--text-muted); line-height: 1.8; font-weight: 300;">
                        <strong>Monday &mdash; Saturday:</strong> 8:00 AM &mdash; 7:00 PM<br>
                        <strong>Sunday:</strong> Closed (Bloom picking in local fields)
                    </p>
                </div>
                
            </div>
            
            <!-- Right Form Panel -->
            <div style="background-color: var(--bg-white); border: var(--border-soft); padding: 3rem; box-shadow: var(--shadow-soft);">
                
                <form onsubmit="event.preventDefault(); window.showToast('Thank you! Your inquiry has been sent.', 'success'); this.reset();" style="display: flex; flex-direction: column; gap: 1.5rem;">
                    
                    <div class="form-group">
                        <label class="form-label" for="contact_name">Full Name *</label>
                        <input type="text" id="contact_name" required placeholder="e.g. Juliet Capulet" class="form-control" style="background-color: var(--bg-warm); border-color: rgba(143,168,155,0.15);">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="contact_email">Email Address *</label>
                        <input type="email" id="contact_email" required placeholder="e.g. juliet@ninya.com" class="form-control" style="background-color: var(--bg-warm); border-color: rgba(143,168,155,0.15);">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="contact_subject">Inquiry Subject *</label>
                        <select id="contact_subject" required class="form-control" style="background-color: var(--bg-warm); border-color: rgba(143,168,155,0.15);">
                            <option value="General inquiry">General Stems Question</option>
                            <option value="Wedding event consultation">Wedding & Event consultancy</option>
                            <option value="Bulk order delivery">Bulk corporate order dispatches</option>
                            <option value="Partnership / Press">Instagram brand partnership</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 2rem;">
                        <label class="form-label" for="contact_message">Your Message *</label>
                        <textarea id="contact_message" required placeholder="Type your detailed question, date requests, vase heights, or flower preferences..." class="form-control" style="background-color: var(--bg-warm); border-color: rgba(143,168,155,0.15); min-height: 120px;"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="display: block; width: 100%; padding: 1.1rem; font-size: 0.8rem; letter-spacing: 0.15em;">
                        Send Inquiry
                    </button>
                    
                </form>
                
            </div>
            
        </div>
        
    </div>

    <!-- Footer Component -->
    <?php include __DIR__ . '/components/footer.php'; ?>

</body>
</html>
