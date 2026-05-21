/**
 * Ninya Flower Shop - Core Interactive Interactions & AJAX controls
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Scroll-sensitive Navigation Panel
    const navbar = document.querySelector('.navbar-wrapper');
    if (navbar) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }

    // 2. Interactive Product Image Detail Slider
    const mainImg = document.getElementById('main-gallery-img');
    const thumbs = document.querySelectorAll('.gallery-thumb-item');
    if (mainImg && thumbs.length > 0) {
        thumbs.forEach(thumb => {
            thumb.addEventListener('click', function() {
                // Deactivate others
                thumbs.forEach(t => t.classList.remove('active'));
                
                // Activate clicked
                this.classList.add('active');
                
                // Swap image with smooth opacity fade
                const newSrc = this.getAttribute('data-src');
                mainImg.style.opacity = '0';
                
                setTimeout(() => {
                    mainImg.src = newSrc;
                    mainImg.style.opacity = '1';
                }, 200);
            });
        });
    }

    // 3. Global AJAX Wishlist System
    const wishlistBtns = document.querySelectorAll('.wishlist-trigger-btn');
    wishlistBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const bouquetId = this.getAttribute('data-bouquet-id');
            const icon = this.querySelector('i');
            
            if (!bouquetId) return;

            // Perform AJAX Request
            fetch('wishlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=toggle&bouquet_id=${bouquetId}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // Toggle active visual states
                    if (data.added) {
                        this.classList.add('active');
                        if (icon) {
                            icon.classList.remove('far');
                            icon.classList.add('fas');
                        }
                        showToast('Saved to your wishlist', 'success');
                    } else {
                        this.classList.remove('active');
                        if (icon) {
                            icon.classList.remove('fas');
                            icon.classList.add('far');
                        }
                        showToast('Removed from wishlist', 'info');
                    }
                    
                    // Update header wishlist badge count
                    const badge = document.getElementById('wishlist-badge');
                    if (badge) {
                        if (data.count > 0) {
                            badge.textContent = data.count;
                            badge.style.display = 'block';
                        } else {
                            badge.style.display = 'none';
                        }
                    }
                } else if (data.status === 'unauthorized') {
                    // Redirect or show modal
                    showToast('Please login to save items', 'error');
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 1500);
                } else {
                    showToast(data.message || 'An error occurred', 'error');
                }
            })
            .catch(err => {
                showToast('Failed to connect to server', 'error');
            });
        });
    });

    // 4. Custom Floating Notifications (Toast Alert system)
    function showToast(message, type = 'success') {
        // Remove existing toast container if not exists
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.style.position = 'fixed';
            container.style.bottom = '2rem';
            container.style.right = '2rem';
            container.style.zIndex = '9999';
            container.style.display = 'flex';
            container.style.flexDirection = 'column';
            container.style.gap = '0.5rem';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `toast-alert toast-${type}`;
        toast.style.background = '#FFFFFF';
        toast.style.color = '#3A322F';
        toast.style.border = '1px solid rgba(143,168,155,0.2)';
        toast.style.padding = '0.8rem 1.5rem';
        toast.style.boxShadow = '0 10px 25px rgba(58,50,47,0.1)';
        toast.style.fontSize = '0.8rem';
        toast.style.letterSpacing = '0.05em';
        toast.style.textTransform = 'uppercase';
        toast.style.borderLeft = '3px solid ' + (type === 'success' ? '#8FA89B' : type === 'error' ? '#e74c3c' : '#C5A059');
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(20px)';
        toast.style.transition = 'all 0.4s cubic-bezier(0.25, 1, 0.5, 1)';
        
        toast.textContent = message;
        container.appendChild(toast);
        
        // Trigger reflow
        toast.offsetHeight;
        
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-20px)';
            setTimeout(() => {
                toast.remove();
            }, 400);
        }, 3000);
    }
    
    // Expose toast so other pages can use it
    window.showToast = showToast;
});
