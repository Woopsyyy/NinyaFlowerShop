<?php
/**
 * Ninya Flower Shop - Premium Romantic Floral Boutique
 * Checkout & Order Processing Page
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';

// Guest or user checking: force login or guest checkout.
// The user asked for a complete production experience. Let's redirect to login first or handle it gracefully.
// Let's allow guest checkouts or auto-register them, but forcing login is very clean and standard. Let's force login for database integrity of users.
require_login();

log_analytics('page_view', ['page' => 'checkout']);

// Load Cart items for verification
$cart_items = [];
$subtotal = 0;

try {
    $stmt = $pdo->prepare("SELECT ci.*, b.id as bq_id, b.name, b.price, b.sale_price, b.stock 
                           FROM cart_items ci 
                           JOIN bouquets b ON ci.bouquet_id = b.id 
                           WHERE ci.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_items = $stmt->fetchAll();
    
    if (empty($cart_items)) {
        set_flash('error', 'Your shopping bag is empty. Please add bouquets before checking out.');
        header("Location: shop.php");
        exit;
    }
    
    foreach ($cart_items as $item) {
        $price = $item['sale_price'] !== null ? $item['sale_price'] : $item['price'];
        $subtotal += $price * $item['quantity'];
    }
} catch (PDOException $e) {
    die("Verification error: " . $e->getMessage());
}

// Handle Checkout Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Security token expired. Please try placing your order again.');
        header("Location: checkout.php");
        exit;
    }
    
    $recipient_name = trim($_POST['recipient_name']);
    $recipient_phone = trim($_POST['recipient_phone']);
    $delivery_address = trim($_POST['delivery_address']);
    $delivery_type = $_POST['delivery_type']; // Same Day, Scheduled, Pickup
    $delivery_date = $_POST['delivery_date'];
    $delivery_time_slot = $_POST['delivery_time_slot'];
    $card_message = trim($_POST['card_message']);
    $note = trim($_POST['note']);
    
    // Calculate Delivery fee based on choice
    $delivery_fee = STANDARD_DELIVERY_FEE;
    if ($delivery_type === 'Same Day') {
        $delivery_fee = SAME_DAY_SURCHARGE;
    } elseif ($delivery_type === 'Pickup') {
        $delivery_fee = PICKUP_FEE;
    }
    
    $total_amount = $subtotal + $delivery_fee;
    $tracking_number = generate_tracking_number();
    
    // Validate inputs
    if (empty($recipient_name) || empty($recipient_phone) || ($delivery_type !== 'Pickup' && empty($delivery_address))) {
        set_flash('error', 'Please fill in all required delivery information.');
        header("Location: checkout.php");
        exit;
    }
    
    if (empty($delivery_date)) {
        $delivery_date = date('Y-m-d');
    }
    
    try {
        // Start Transaction
        $pdo->beginTransaction();
        
        // 1. Insert order record
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, tracking_number, total_amount, payment_method, status, delivery_type, delivery_date, delivery_time_slot, recipient_name, recipient_phone, delivery_address, card_message, note) VALUES (?, ?, ?, 'Cash on Delivery', 'Pending', ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $_SESSION['user_id'],
            $tracking_number,
            $total_amount,
            $delivery_type,
            $delivery_date,
            $delivery_time_slot,
            $recipient_name,
            $recipient_phone,
            $delivery_address,
            $card_message,
            $note
        ]);
        
        $order_id = $pdo->lastInsertId();
        
        // 2. Insert items and deduct stock
        $ins_item = $pdo->prepare("INSERT INTO order_items (order_id, bouquet_id, price, quantity) VALUES (?, ?, ?, ?)");
        $upd_stock = $pdo->prepare("UPDATE bouquets SET stock = stock - ? WHERE id = ?");
        
        foreach ($cart_items as $item) {
            $price = $item['sale_price'] !== null ? $item['sale_price'] : $item['price'];
            
            // Insert item
            $ins_item->execute([
                $order_id,
                $item['bouquet_id'],
                $price,
                $item['quantity']
            ]);
            
            // Deduct stock
            $upd_stock->execute([
                $item['quantity'],
                $item['bouquet_id']
            ]);
        }
        
        // 3. Create initial tracking log
        $log = $pdo->prepare("INSERT INTO delivery_tracking (order_id, status, description) VALUES (?, 'Pending', 'Order created successfully. Restricting to Cash on Delivery. Preparing beautiful fresh blooms.')");
        $log->execute([$order_id]);
        
        // 4. Empty Cart
        $del = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ?");
        $del->execute([$_SESSION['user_id']]);
        
        // Log transaction analytics
        log_analytics('order_placed', ['order_id' => $order_id, 'tracking_number' => $tracking_number, 'amount' => $total_amount]);
        
        // Commit Transaction
        $pdo->commit();
        
        set_flash('success', 'Thank you! Your boutique order has been confirmed.');
        header("Location: tracking.php?tracking=" . $tracking_number);
        exit;
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        set_flash('error', 'Order placement failed: ' . $e->getMessage());
        header("Location: checkout.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Boutique Checkout - Ninya Flower Shop</title>
    
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body style="background-color: var(--bg-warm);">

    <!-- Header Navigation -->
    <?php include __DIR__ . '/components/navbar.php'; ?>

    <div class="container" style="padding-top: 4rem; padding-bottom: 7rem;">
        
        <span class="section-subtitle">Secure Dispatch</span>
        <h1 style="font-family: var(--font-serif); font-size: 3.5rem; text-align: center; color: var(--text-charcoal); margin-bottom: 4rem;">
            Boutique Checkout
        </h1>

        <!-- Error Notification -->
        <?php if ($error = get_flash('error')): ?>
            <div class="alert alert-error" style="max-width: 900px; margin: 0 auto 3rem auto;"><?php echo e($error); ?></div>
        <?php endif; ?>

        <!-- Split Checkout Form -->
        <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 4rem; align-items: start;">
            
            <!-- Left Form Details -->
            <form action="checkout.php" method="POST" id="checkout-form">
                <?php csrf_field(); ?>
                
                <div style="background-color: var(--bg-white); border: var(--border-soft); padding: 3rem; box-shadow: var(--shadow-soft); display: flex; flex-direction: column; gap: 2rem;">
                    
                    <!-- 1. Recipient info -->
                    <div>
                        <h3 style="font-family: var(--font-serif); font-size: 1.6rem; color: var(--text-charcoal); border-bottom: 1px solid var(--champagne); padding-bottom: 0.8rem; margin-bottom: 1.5rem;">
                            1. Recipient Details
                        </h3>
                        
                        <div class="grid-2" style="gap: 1.5rem;">
                            <div class="form-group">
                                <label class="form-label" for="recipient_name">Recipient Full Name *</label>
                                <input type="text" id="recipient_name" name="recipient_name" required placeholder="e.g. Juliet Capulet" class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="recipient_phone">Recipient Phone Number *</label>
                                <input type="text" id="recipient_phone" name="recipient_phone" required placeholder="e.g. +1 555-0199" class="form-control">
                            </div>
                        </div>
                    </div>
                    
                    <!-- 2. Delivery schedules -->
                    <div>
                        <h3 style="font-family: var(--font-serif); font-size: 1.6rem; color: var(--text-charcoal); border-bottom: 1px solid var(--champagne); padding-bottom: 0.8rem; margin-bottom: 1.5rem;">
                            2. Delivery Schedule
                        </h3>
                        
                        <div class="form-group">
                            <label class="form-label">Delivery Arrangement Method *</label>
                            <div class="request-chips-grid">
                                <input type="radio" id="del_same_day" name="delivery_type" value="Same Day" checked class="chip-input" onclick="updateFees('Same Day');">
                                <label for="del_same_day" class="chip-label">Same Day Delivery (+$25.00)</label>
                                
                                <input type="radio" id="del_scheduled" name="delivery_type" value="Scheduled" class="chip-input" onclick="updateFees('Scheduled');">
                                <label for="del_scheduled" class="chip-label">Scheduled Date (+$15.00)</label>
                                
                                <input type="radio" id="del_pickup" name="delivery_type" value="Pickup" class="chip-input" onclick="updateFees('Pickup');">
                                <label for="del_pickup" class="chip-label">Boutique Pickup ($0.00)</label>
                            </div>
                        </div>
                        
                        <div class="grid-2" style="gap: 1.5rem;" id="schedule-selectors">
                            <div class="form-group">
                                <label class="form-label" for="delivery_date">Delivery Date *</label>
                                <input type="date" id="delivery_date" name="delivery_date" class="form-control" min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="delivery_time_slot">Preferred Time Slot *</label>
                                <select id="delivery_time_slot" name="delivery_time_slot" class="form-control">
                                    <option value="Morning Dispatch (8:00 AM - 12:00 PM)">Morning Dispatch (8:00 AM - 12:00 PM)</option>
                                    <option value="Afternoon Sun (12:00 PM - 5:00 PM)">Afternoon Sun (12:00 PM - 5:00 PM)</option>
                                    <option value="Twilight Romantic (5:00 PM - 9:00 PM)">Twilight Romantic (5:00 PM - 9:00 PM)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 3. Shipping details address -->
                    <div id="address-section">
                        <h3 style="font-family: var(--font-serif); font-size: 1.6rem; color: var(--text-charcoal); border-bottom: 1px solid var(--champagne); padding-bottom: 0.8rem; margin-bottom: 1.5rem;">
                            3. Physical Address
                        </h3>
                        
                        <div class="form-group">
                            <label class="form-label" for="delivery_address">Delivery Address *</label>
                            <textarea id="delivery_address" name="delivery_address" required placeholder="Specify street name, building number, apartment and complex entry rules..." class="form-control"></textarea>
                        </div>
                    </div>
                    
                    <!-- 4. Handwritten note card and instructions -->
                    <div>
                        <h3 style="font-family: var(--font-serif); font-size: 1.6rem; color: var(--text-charcoal); border-bottom: 1px solid var(--champagne); padding-bottom: 0.8rem; margin-bottom: 1.5rem;">
                            4. Botanical Card Note & Instructions
                        </h3>
                        
                        <div class="form-group">
                            <label class="form-label" for="card_message">Handwritten Calligraphic Note Message (Optional)</label>
                            <textarea id="card_message" name="card_message" placeholder="Type the warm wishes, romantic greetings, or anniversary congratulations to be handwritten by our calligrapher..." class="form-control" style="min-height: 80px;"></textarea>
                            <span style="font-size: 0.75rem; color: var(--text-muted); font-style: italic;">*Delivered on structured luxury cardstock inside a matching envelope.</span>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="note">Order Courier Instructions (Optional)</label>
                            <input type="text" id="note" name="note" placeholder="e.g. Leave at concierge, call before knocking, etc." class="form-control">
                        </div>
                    </div>
                    
                    <!-- 5. Strict Cash on Delivery restrictor -->
                    <div>
                        <h3 style="font-family: var(--font-serif); font-size: 1.6rem; color: var(--text-charcoal); border-bottom: 1px solid var(--champagne); padding-bottom: 0.8rem; margin-bottom: 1.5rem;">
                            5. Payment Parameters
                        </h3>
                        
                        <div style="background-color: var(--primary-blush); border: 1px solid rgba(197, 160, 89, 0.2); padding: 1.5rem; display: flex; align-items: start; gap: 1rem;">
                            <i class="fas fa-hand-holding-usd" style="font-size: 1.5rem; color: var(--gold); margin-top: 0.2rem;"></i>
                            <div>
                                <h4 style="font-family: var(--font-sans); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; color: var(--text-charcoal); margin-bottom: 0.3rem;">
                                    Cash on Delivery Only (COD)
                                </h4>
                                <p style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.5; font-weight: 300;">
                                    To ensure maximum security and perfect fresh bloom confirmation, we strictly operate on **Cash on Delivery**. Please pay our delivery artisan directly upon inspection of your hand-tied bouquet.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </form>
            
            <!-- Right Order Summaries -->
            <div style="background-color: var(--bg-white); border: var(--border-soft); padding: 2.5rem; box-shadow: var(--shadow-soft);">
                <h3 style="font-family: var(--font-serif); font-size: 1.6rem; color: var(--text-charcoal); margin-bottom: 2rem;">
                    Bespoke Order Summary
                </h3>
                
                <!-- Items list -->
                <div style="display: flex; flex-direction: column; gap: 1.5rem; border-bottom: 1px solid var(--champagne); padding-bottom: 2rem; margin-bottom: 2rem;">
                    <?php foreach ($cart_items as $item): ?>
                        <?php 
                        $price = $item['sale_price'] !== null ? $item['sale_price'] : $item['price'];
                        ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem;">
                            <div style="display: flex; gap: 1rem; align-items: center;">
                                <div style="font-weight: 500; color: var(--text-charcoal);"><?php echo e($item['name']); ?></div>
                                <div style="color: var(--text-muted);">x<?php echo $item['quantity']; ?></div>
                            </div>
                            <div style="font-weight: 500; color: var(--text-charcoal);"><?php echo format_price($price * $item['quantity']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Fees -->
                <div style="display: flex; flex-direction: column; gap: 1.2rem; font-size: 0.9rem; border-bottom: 1px solid var(--champagne); padding-bottom: 2rem; margin-bottom: 2rem;">
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">Bouquet Subtotal:</span>
                        <span style="font-weight: 500; color: var(--text-charcoal);"><?php echo format_price($subtotal); ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">Delivery dispatch fee:</span>
                        <span style="font-weight: 500; color: var(--text-charcoal);" id="summary-delivery-fee"><?php echo format_price(SAME_DAY_SURCHARGE); ?></span>
                    </div>
                </div>
                
                <!-- Grand Total -->
                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 1.2rem; color: var(--text-charcoal); font-weight: 600; margin-bottom: 2.5rem;">
                    <span>Grand Total:</span>
                    <span style="color: var(--gold); font-size: 1.5rem; font-family: var(--font-sans);" id="summary-grand-total"><?php echo format_price($subtotal + SAME_DAY_SURCHARGE); ?></span>
                </div>
                
                <button type="submit" form="checkout-form" name="place_order" class="btn btn-primary" style="display: block; width: 100%; padding: 1.2rem; font-size: 0.8rem; letter-spacing: 0.15em;">
                    Place Order (COD)
                </button>
            </div>
            
        </div>
        
    </div>

    <!-- Footer Component -->
    <?php include __DIR__ . '/components/footer.php'; ?>

    <script>
        // Delivery calculations based on radio parameters
        const standardFee = <?php echo STANDARD_DELIVERY_FEE; ?>;
        const sameDayFee = <?php echo SAME_DAY_SURCHARGE; ?>;
        const pickupFee = <?php echo PICKUP_FEE; ?>;
        const subtotal = <?php echo $subtotal; ?>;
        
        function updateFees(type) {
            let activeFee = standardFee;
            const addressSec = document.getElementById('address-section');
            const addressInput = document.getElementById('delivery_address');
            const schedSelectors = document.getElementById('schedule-selectors');
            
            if (type === 'Same Day') {
                activeFee = sameDayFee;
                addressSec.style.display = 'block';
                addressInput.setAttribute('required', 'required');
                schedSelectors.style.display = 'grid';
            } else if (type === 'Scheduled') {
                activeFee = standardFee;
                addressSec.style.display = 'block';
                addressInput.setAttribute('required', 'required');
                schedSelectors.style.display = 'grid';
            } else if (type === 'Pickup') {
                activeFee = pickupFee;
                addressSec.style.display = 'none';
                addressInput.removeAttribute('required');
                schedSelectors.style.display = 'none';
            }
            
            // Format to standard currency
            document.getElementById('summary-delivery-fee').textContent = '$' + activeFee.toFixed(2);
            document.getElementById('summary-grand-total').textContent = '$' + (subtotal + activeFee).toFixed(2);
        }
        
        // Initial load calculation
        updateFees('Same Day');
    </script>
</body>
</html>
