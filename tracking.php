<?php
/**
 * Ninya Flower Shop - Premium Romantic Floral Boutique
 * Live Order Tracking & History Stepper
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/session.php';

log_analytics('page_view', ['page' => 'tracking']);

$tracking_number = isset($_GET['tracking']) ? strtoupper(trim($_GET['tracking'])) : '';
$order = null;
$tracking_logs = [];
$order_items = [];

if ($tracking_number) {
    try {
        // Fetch order details
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE tracking_number = ? LIMIT 1");
        $stmt->execute([$tracking_number]);
        $order = $stmt->fetch();
        
        if ($order) {
            // Fetch tracking logs
            $stmt = $pdo->prepare("SELECT * FROM delivery_tracking WHERE order_id = ? ORDER BY updated_at DESC");
            $stmt->execute([$order['id']]);
            $tracking_logs = $stmt->fetchAll();
            
            // Fetch items
            $stmt = $pdo->prepare("SELECT oi.*, b.name, b.image_url 
                                   FROM order_items oi 
                                   LEFT JOIN bouquets b ON oi.bouquet_id = b.id 
                                   WHERE oi.order_id = ?");
            $stmt->execute([$order['id']]);
            $order_items = $stmt->fetchAll();
        } else {
            set_flash('error', 'Tracking number not found. Please verify your order receipt code.');
        }
    } catch (PDOException $e) {
        set_flash('error', 'Database lookup failed: ' . $e->getMessage());
    }
}

// Helper to determine status stage indexes
$status_index = [
    'Pending' => 1,
    'Confirmed' => 2,
    'Preparing Bouquet' => 3,
    'Out for Delivery' => 4,
    'Delivered' => 5,
    'Cancelled' => -1
];

$current_stage = $order ? ($status_index[$order['status']] ?? 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Delivery Tracking - Ninya Flower Shop</title>
    
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body style="background-color: var(--bg-warm);">

    <!-- Header Navigation -->
    <?php include __DIR__ . '/components/navbar.php'; ?>

    <div class="container" style="padding-top: 4rem; padding-bottom: 7rem;">
        
        <span class="section-subtitle">Live Dispatches</span>
        <h1 style="font-family: var(--font-serif); font-size: 3.5rem; text-align: center; color: var(--text-charcoal); margin-bottom: 4rem;">
            Order Tracking
        </h1>

        <!-- Flash alerts -->
        <?php if ($error = get_flash('error')): ?>
            <div class="alert alert-error" style="max-width: 700px; margin: 0 auto 3rem auto;"><?php echo e($error); ?></div>
        <?php endif; ?>
        <?php if ($success = get_flash('success')): ?>
            <div class="alert alert-success" style="max-width: 700px; margin: 0 auto 3rem auto;"><?php echo e($success); ?></div>
        <?php endif; ?>

        <!-- Search Bar -->
        <div style="max-width: 600px; margin: 0 auto 4rem auto; background-color: var(--bg-white); border: var(--border-soft); padding: 2rem; box-shadow: var(--shadow-soft);">
            <form action="tracking.php" method="GET" style="display: flex; flex-direction: column; gap: 1rem;">
                <label class="form-label" for="tracking">Enter Boutique Tracking Code *</label>
                <div style="display: flex; gap: 1rem;">
                    <input type="text" id="tracking" name="tracking" required placeholder="e.g. NY-20260521-F5E3" value="<?php echo e($tracking_number); ?>" class="form-control" style="margin-bottom: 0; text-transform: uppercase;">
                    <button type="submit" class="btn btn-primary" style="padding: 0.8rem 2rem;">
                        Search
                    </button>
                </div>
            </form>
        </div>

        <?php if ($order): ?>
            <!-- 1. Visual Status Stepper -->
            <div class="tracking-wrapper">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--champagne); padding-bottom: 1rem; margin-bottom: 2rem;">
                    <div>
                        <span style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Tracking Number</span>
                        <h3 style="font-family: var(--font-serif); font-size: 1.4rem; color: var(--text-charcoal);"><?php echo e($order['tracking_number']); ?></h3>
                    </div>
                    <div style="text-align: right;">
                        <span style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Estimated Dispatch</span>
                        <h4 style="font-family: var(--font-sans); font-size: 0.85rem; font-weight: 600; color: var(--gold);"><?php echo date('M d, Y', strtotime($order['delivery_date'])); ?> (<?php echo e($order['delivery_time_slot']); ?>)</h4>
                    </div>
                </div>
                
                <?php if ($order['status'] === 'Cancelled'): ?>
                    <div style="background-color: #fcebeb; color: #8c3b3b; padding: 1rem 1.5rem; text-align: center; font-weight: 500; font-size: 0.9rem; margin-bottom: 2rem;">
                        <i class="fas fa-exclamation-triangle" style="margin-right: 0.5rem;"></i> This order has been cancelled.
                    </div>
                <?php else: ?>
                    <!-- Active timeline progress bar calculation -->
                    <?php
                    $progress_width = 0;
                    if ($current_stage >= 1) $progress_width = (($current_stage - 1) / 4) * 100;
                    ?>
                    
                    <div class="tracking-timeline">
                        <div class="tracking-timeline-progress" style="width: <?php echo $progress_width; ?>%;"></div>
                        
                        <!-- Step 1: Pending -->
                        <div class="tracking-step <?php echo $current_stage == 1 ? 'active' : ($current_stage > 1 ? 'completed' : ''); ?>">
                            <div class="tracking-node"><i class="fas fa-clock"></i></div>
                            <span class="tracking-step-label">Pending</span>
                        </div>
                        
                        <!-- Step 2: Confirmed -->
                        <div class="tracking-step <?php echo $current_stage == 2 ? 'active' : ($current_stage > 2 ? 'completed' : ''); ?>">
                            <div class="tracking-node"><i class="fas fa-check"></i></div>
                            <span class="tracking-step-label">Confirmed</span>
                        </div>
                        
                        <!-- Step 3: Preparing Bouquet -->
                        <div class="tracking-step <?php echo $current_stage == 3 ? 'active' : ($current_stage > 3 ? 'completed' : ''); ?>">
                            <div class="tracking-node"><i class="fas fa-seedling"></i></div>
                            <span class="tracking-step-label">Wrapping</span>
                        </div>
                        
                        <!-- Step 4: Out for Delivery -->
                        <div class="tracking-step <?php echo $current_stage == 4 ? 'active' : ($current_stage > 4 ? 'completed' : ''); ?>">
                            <div class="tracking-node"><i class="fas fa-shipping-fast"></i></div>
                            <span class="tracking-step-label">On Route</span>
                        </div>
                        
                        <!-- Step 5: Delivered -->
                        <div class="tracking-step <?php echo $current_stage == 5 ? 'active' : ($current_stage > 5 ? 'completed' : ''); ?>">
                            <div class="tracking-node"><i class="fas fa-hand-holding-heart"></i></div>
                            <span class="tracking-step-label">Delivered</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 2. Split details (Logs left, recipient metadata right) -->
            <div class="grid-2" style="margin-top: 4rem; align-items: start;">
                
                <!-- Left: Status logs -->
                <div style="background-color: var(--bg-white); border: var(--border-soft); padding: 2.5rem; box-shadow: var(--shadow-soft);">
                    <h3 style="font-family: var(--font-serif); font-size: 1.5rem; color: var(--text-charcoal); border-bottom: 1px solid var(--champagne); padding-bottom: 0.8rem; margin-bottom: 2rem;">
                        Timeline Updates
                    </h3>
                    
                    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                        <?php if (!empty($tracking_logs)): ?>
                            <?php foreach ($tracking_logs as $log): ?>
                                <div style="display: flex; gap: 1.5rem; border-left: 2px solid var(--sage-green); padding-left: 1.5rem; position: relative;">
                                    <!-- Log node point -->
                                    <div style="position: absolute; left: -6px; top: 0; width: 10px; height: 10px; border-radius: 50%; background-color: var(--sage-green);"></div>
                                    
                                    <div>
                                        <h4 style="font-family: var(--font-sans); font-size: 0.85rem; font-weight: 600; color: var(--text-charcoal); margin-bottom: 0.2rem;">
                                            <?php echo e($log['status']); ?>
                                        </h4>
                                        <p style="font-size: 0.8rem; color: var(--text-muted); font-weight: 300; line-height: 1.5; margin-bottom: 0.4rem;">
                                            <?php echo e($log['description']); ?>
                                        </p>
                                        <span style="font-size: 0.7rem; color: var(--text-muted);"><?php echo date('M d, Y - h:i A', strtotime($log['updated_at'])); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="font-style: italic; color: var(--text-muted); font-size: 0.85rem;">No logs reported for this order yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Right: Metadata Summary -->
                <div style="background-color: var(--bg-white); border: var(--border-soft); padding: 2.5rem; box-shadow: var(--shadow-soft); display: flex; flex-direction: column; gap: 2rem;">
                    <div>
                        <h3 style="font-family: var(--font-serif); font-size: 1.5rem; color: var(--text-charcoal); border-bottom: 1px solid var(--champagne); padding-bottom: 0.8rem; margin-bottom: 1.2rem;">
                            Delivery Information
                        </h3>
                        
                        <div style="display: flex; flex-direction: column; gap: 0.8rem; font-size: 0.85rem; color: var(--text-muted);">
                            <div><strong style="color: var(--text-charcoal);">Recipient Name:</strong> <?php echo e($order['recipient_name']); ?></div>
                            <div><strong style="color: var(--text-charcoal);">Recipient Phone:</strong> <?php echo e($order['recipient_phone']); ?></div>
                            <?php if ($order['delivery_type'] !== 'Pickup'): ?>
                                <div><strong style="color: var(--text-charcoal);">Delivery Address:</strong> <?php echo e($order['delivery_address']); ?></div>
                            <?php endif; ?>
                            <div><strong style="color: var(--text-charcoal);">Delivery Type:</strong> <?php echo e($order['delivery_type']); ?></div>
                        </div>
                    </div>
                    
                    <?php if ($order['card_message']): ?>
                        <div>
                            <h4 style="font-family: var(--font-serif); font-size: 1.2rem; color: var(--gold); border-bottom: 1px solid var(--champagne); padding-bottom: 0.5rem; margin-bottom: 0.8rem;">
                                handwritten Note
                            </h4>
                            <p style="font-size: 0.85rem; font-style: italic; color: var(--text-charcoal); line-height: 1.6; background-color: var(--primary-blush); padding: 1rem 1.2rem; border-left: 2px solid var(--gold);">
                                "<?php echo e($order['card_message']); ?>"
                            </p>
                        </div>
                    <?php endif; ?>
                    
                    <div>
                        <h3 style="font-family: var(--font-serif); font-size: 1.5rem; color: var(--text-charcoal); border-bottom: 1px solid var(--champagne); padding-bottom: 0.8rem; margin-bottom: 1.2rem;">
                            Items Ordered
                        </h3>
                        
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <?php foreach ($order_items as $item): ?>
                                <div style="display: flex; align-items: center; justify-content: space-between; font-size: 0.85rem;">
                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                        <div style="width: 40px; height: 50px; overflow: hidden; background-color: var(--champagne);">
                                            <img src="<?php echo e($item['image_url'] ?? 'assets/images/amour_rose.png'); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                        </div>
                                        <div>
                                            <div style="font-weight: 500; color: var(--text-charcoal);"><?php echo e($item['name'] ?? 'Bespoke Bouquet'); ?></div>
                                            <div style="font-size: 0.75rem; color: var(--text-muted);">x<?php echo $item['quantity']; ?></div>
                                        </div>
                                    </div>
                                    <div style="font-weight: 600; color: var(--text-charcoal);"><?php echo format_price($item['price'] * $item['quantity']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div style="border-top: 1px solid var(--champagne); padding-top: 1.5rem; display: flex; justify-content: space-between; align-items: center; font-size: 1.1rem; color: var(--text-charcoal); font-weight: 600;">
                        <span>Total Paid (COD):</span>
                        <span style="color: var(--gold); font-size: 1.25rem;"><?php echo format_price($order['total_amount']); ?></span>
                    </div>
                </div>
                
            </div>
        <?php endif; ?>
        
    </div>

    <!-- Footer Component -->
    <?php include __DIR__ . '/components/footer.php'; ?>

</body>
</html>
