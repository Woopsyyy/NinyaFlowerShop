<?php
/**
 * Editorial Footer Component
 */
?>
<footer style="background-color: var(--bg-white); border-top: var(--border-soft); padding: 5rem 0 3rem 0; margin-top: 6rem; font-size: 0.85rem; color: var(--text-muted);">
    <div class="container" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1.5fr; gap: 4rem;">
        
        <!-- Brand Description -->
        <div>
            <h3 style="font-family: var(--font-serif); font-size: 1.8rem; font-weight: 400; color: var(--text-charcoal); margin-bottom: 1.2rem;">
                Ninya<span style="color: var(--gold);">.</span>
            </h3>
            <p style="line-height: 1.8; margin-bottom: 1.5rem; font-weight: 300;">
                A romantic premium Instagram florist and handcrafted boutique. We design cinematic floral storytelling that transforms simple moments into lifelong emotional memories.
            </p>
            <div style="display: flex; gap: 1.2rem; font-size: 1rem; color: var(--text-charcoal);">
                <a href="#" style="opacity: 0.7;"><i class="fab fa-instagram"></i></a>
                <a href="#" style="opacity: 0.7;"><i class="fab fa-pinterest"></i></a>
                <a href="#" style="opacity: 0.7;"><i class="fab fa-facebook-f"></i></a>
                <a href="#" style="opacity: 0.7;"><i class="fab fa-twitter"></i></a>
            </div>
        </div>
        
        <!-- Quick links -->
        <div>
            <h4 style="font-family: var(--font-sans); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.15em; color: var(--text-charcoal); margin-bottom: 1.5rem; font-weight: 600;">
                Explore
            </h4>
            <ul style="list-style: none; display: flex; flex-direction: column; gap: 0.8rem; padding: 0;">
                <li><a href="shop.php" style="opacity: 0.8;">Shop All</a></li>
                <li><a href="custom-request.php" style="opacity: 0.8;">Custom Arrangements</a></li>
                <li><a href="about.php" style="opacity: 0.8;">Our Story</a></li>
                <li><a href="contact.php" style="opacity: 0.8;">Inquiries</a></li>
            </ul>
        </div>
        
        <!-- Policies -->
        <div>
            <h4 style="font-family: var(--font-sans); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.15em; color: var(--text-charcoal); margin-bottom: 1.5rem; font-weight: 600;">
                Services
            </h4>
            <ul style="list-style: none; display: flex; flex-direction: column; gap: 0.8rem; padding: 0;">
                <li><a href="tracking.php" style="opacity: 0.8;">Track Delivery</a></li>
                <li><a href="#" style="opacity: 0.8;">Same-Day Terms</a></li>
                <li><a href="#" style="opacity: 0.8;">Pickup Location</a></li>
                <li><a href="#" style="opacity: 0.8;">Refund Policy</a></li>
            </ul>
        </div>
        
        <!-- Newsletter signup -->
        <div>
            <h4 style="font-family: var(--font-sans); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.15em; color: var(--text-charcoal); margin-bottom: 1.5rem; font-weight: 600;">
                The Floral Journal
            </h4>
            <p style="line-height: 1.8; margin-bottom: 1.2rem; font-weight: 300;">
                Subscribe to receive seasonal styling campaigns, bouquet meanings, and romantic florist events.
            </p>
            <form onsubmit="event.preventDefault(); window.showToast('Thank you for subscribing!', 'success'); this.reset();" style="display: flex; border: 1px solid rgba(143,168,155,0.2);">
                <input type="email" placeholder="Your Email Address" required style="flex: 1; border: none; padding: 0.8rem 1rem; font-size: 0.8rem; background-color: var(--bg-warm);">
                <button type="submit" style="background-color: var(--text-charcoal); color: var(--bg-warm); border: none; padding: 0 1.2rem; cursor: pointer; transition: var(--transition-fast);">
                    <i class="fas fa-paper-plane" style="font-size: 0.8rem;"></i>
                </button>
            </form>
        </div>
    </div>
    
    <!-- Copy section -->
    <div class="container" style="border-top: var(--border-soft); padding-top: 2rem; margin-top: 4rem; display: flex; justify-content: space-between; font-size: 0.75rem;">
        <p>&copy; <?php echo date('Y'); ?> Ninya Flower Shop. Crafted for premium romantic gifting.</p>
        <p style="letter-spacing: 0.05em; text-transform: uppercase;">COD Only &bull; Same Day Delivery &bull; Instagram Florist</p>
    </div>
</footer>

<!-- Shared JavaScript dependencies -->
<script src="assets/js/main.js"></script>
