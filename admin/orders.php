<?php
/**
 * Ninya Flower Shop - Boutique Control Room
 * Order & Status Tracking Manager
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

log_analytics('admin_view', ['section' => 'orders']);

$error_msg = '';
$success_msg = '';

// Handle Status Toggling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = trim($_POST['status']);
    $description = trim($_POST['tracking_description']);
    
    // Status descriptions map to pre-fill descriptions
    $status_desc_map = [
        'Pending' => 'Order created. Payment parameters restricted to Cash on Delivery.',
        'Confirmed' => 'Order confirmed. Sourcing fresh botanical stems from local organic fields.',
        'Preparing Bouquet' => 'Artisan florist wrapping is in progress. Fresh hydration gels added.',
        'Out for Delivery' => 'Artisan courier has departed the boutique Chelsea greenhouse. On route.',
        'Delivered' => 'Order successfully hand-delivered to the recipient. Cash on Delivery cleared.',
        'Cancelled' => 'This order was cancelled by the boutique administration.'
    ];
    
    if (empty($description)) {
        $description = $status_desc_map[$new_status] ?? 'Order status updated to ' . $new_status;
    }
    
    try {
        $pdo->beginTransaction();
        
        // 1. Update order status
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $order_id]);
        
        // 2. Automatically log status timeline update in delivery_tracking
        $stmt = $pdo->prepare("INSERT INTO delivery_tracking (order_id, status, description) VALUES (?, ?, ?)");
        $stmt->execute([$order_id, $new_status, $description]);
        
        $pdo->commit();
        $success_msg = "Order status toggled to '{$new_status}' successfully!";
        log_analytics('order_status_updated', ['order_id' => $order_id, 'new_status' => $new_status]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error_msg = 'Transaction failed: ' . $e->getMessage();
    }
}

// Fetch all orders
$orders = [];
try {
    $stmt = $pdo->query("SELECT o.*, u.name as customer_name 
                         FROM orders o 
                         LEFT JOIN users u ON o.user_id = u.id 
                         ORDER BY o.created_at DESC");
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $orders = [];
}

// Determine active order focus for details inspection
$active_order = null;
$order_items = [];
$tracking_logs = [];

$focus_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($focus_id > 0) {
    foreach ($orders as $ord) {
        if ($ord['id'] === $focus_id) {
            $active_order = $ord;
            break;
        }
    }
    
    if ($active_order) {
        try {
            // Load Items
            $stmt = $pdo->prepare("SELECT oi.*, b.name, b.image_url 
                                   FROM order_items oi 
                                   LEFT JOIN bouquets b ON oi.bouquet_id = b.id 
                                   WHERE oi.order_id = ?");
            $stmt->execute([$active_order['id']]);
            $order_items = $stmt->fetchAll();
            
            // Load Logs
            $stmt = $pdo->prepare("SELECT * FROM delivery_tracking WHERE order_id = ? ORDER BY updated_at DESC");
            $stmt->execute([$active_order['id']]);
            $tracking_logs = $stmt->fetchAll();
        } catch (PDOException $e) {
            $order_items = [];
            $tracking_logs = [];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order State Manager - Ninya Flower Shop</title>
    
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-body">

    <div class="admin-layout">
        
        <!-- Left Sidebar Navigation -->
        <aside class="admin-sidebar">
            <div class="admin-sidebar-header">
                Ninya<span>.</span>Control
            </div>
            <ul class="admin-menu">
                <li><a href="dashboard.php" class="admin-menu-link"><i class="fas fa-th-large"></i> <span>Dashboard</span></a></li>
                <li><a href="analytics.php" class="admin-menu-link"><i class="fas fa-chart-line"></i> <span>Analytics</span></a></li>
                <li><a href="bouquets.php" class="admin-menu-link"><i class="fas fa-seedling"></i> <span>Manage Stems</span></a></li>
                <li><a href="orders.php" class="admin-menu-link active"><i class="fas fa-shopping-bag"></i> <span>Orders</span></a></li>
                <li><a href="custom-requests.php" class="admin-menu-link"><i class="fas fa-magic"></i> <span>Custom Requests</span></a></li>
                <li><a href="customers.php" class="admin-menu-link"><i class="fas fa-users"></i> <span>Customers</span></a></li>
                <li><a href="testimonials.php" class="admin-menu-link"><i class="fas fa-quote-left"></i> <span>Quotes</span></a></li>
                <li><a href="../index.php" class="admin-menu-link"><i class="fas fa-store"></i> <span>Boutique Front</span></a></li>
                <li><a href="../logout.php" class="admin-menu-link" style="margin-top: 3rem; color: #f56565;"><i class="fas fa-sign-out-alt"></i> <span>Log Out</span></a></li>
            </ul>
        </aside>
        
        <!-- Right Main Workspace -->
        <main class="admin-content">
            <header class="admin-header">
                <div>
                    <span style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-muted);">Boutique bookings</span>
                    <h1 class="admin-title">Orders Management</h1>
                </div>
            </header>

            <!-- Alerts -->
            <?php if ($error_msg): ?>
                <div class="alert alert-error"><?php echo e($error_msg); ?></div>
            <?php endif; ?>
            <?php if ($success_msg): ?>
                <div class="alert alert-success"><?php echo e($success_msg); ?></div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 3rem; align-items: start;">
                
                <!-- Left panel: List of all Orders -->
                <div class="admin-card">
                    <h3 style="font-family: var(--font-serif); font-size: 1.4rem; color: var(--text-charcoal); border-bottom: 1px solid var(--admin-border); padding-bottom: 0.8rem; margin-bottom: 1.5rem;">
                        Bespoke Bookings
                    </h3>
                    
                    <div class="table-wrapper">
                        <table class="table-premium">
                            <thead>
                                <tr>
                                    <th>Order Code</th>
                                    <th>Client</th>
                                    <th>Method</th>
                                    <th>Delivery Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($orders)): ?>
                                    <?php foreach ($orders as $ord): ?>
                                        <tr style="<?php echo ($active_order && $active_order['id'] === $ord['id']) ? 'background-color: var(--sage-light);' : ''; ?>">
                                            <td><strong><?php echo e($ord['tracking_number']); ?></strong></td>
                                            <td><?php echo e($ord['recipient_name']); ?></td>
                                            <td><?php echo e($ord['delivery_type']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($ord['delivery_date'])); ?></td>
                                            <td style="font-weight: 500;"><?php echo format_price($ord['total_amount']); ?></td>
                                            <td>
                                                <?php
                                                $st_class = strtolower(str_replace(' ', '', $ord['status']));
                                                ?>
                                                <span class="badge-status badge-<?php echo $st_class; ?>">
                                                    <?php echo e($ord['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="orders.php?order_id=<?php echo $ord['id']; ?>" class="btn" style="padding: 0.35rem 0.6rem; font-size: 0.7rem; background-color: var(--sage-green); color: var(--bg-white);">
                                                    Inspect
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; color: var(--text-muted); font-style: italic;">
                                            No bookings have been logged.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Right panel: Selected Order Details & Status Toggler -->
                <div>
                    <?php if ($active_order): ?>
                        <div class="admin-card" style="display: flex; flex-direction: column; gap: 2rem;">
                            
                            <!-- Header Code -->
                            <div>
                                <span style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Inspection Details</span>
                                <h3 style="font-family: var(--font-serif); font-size: 1.6rem; color: var(--text-charcoal); border-bottom: 1px solid var(--admin-border); padding-bottom: 0.5rem;">
                                    <?php echo e($active_order['tracking_number']); ?>
                                </h3>
                            </div>
                            
                            <!-- Status Selector Form -->
                            <form action="orders.php?order_id=<?php echo $active_order['id']; ?>" method="POST" style="background-color: var(--bg-warm); padding: 1.5rem; border: var(--border-soft);">
                                <input type="hidden" name="update_status" value="1">
                                <input type="hidden" name="order_id" value="<?php echo $active_order['id']; ?>">
                                
                                <div class="form-group">
                                    <label class="form-label" for="status">Modify Order Status</label>
                                    <select id="status" name="status" class="form-control" onchange="prefillDescription(this.value);" style="background-color: var(--bg-white);">
                                        <option value="Pending" <?php echo $active_order['status'] === 'Pending' ? 'selected' : ''; ?>>Pending (COD verification)</option>
                                        <option value="Confirmed" <?php echo $active_order['status'] === 'Confirmed' ? 'selected' : ''; ?>>Confirmed (Sourcing stems)</option>
                                        <option value="Preparing Bouquet" <?php echo $active_order['status'] === 'Preparing Bouquet' ? 'selected' : ''; ?>>Preparing Bouquet (Wrapping)</option>
                                        <option value="Out for Delivery" <?php echo $active_order['status'] === 'Out for Delivery' ? 'selected' : ''; ?>>Out for Delivery (On route)</option>
                                        <option value="Delivered" <?php echo $active_order['status'] === 'Delivered' ? 'selected' : ''; ?>>Delivered (Payment cleared)</option>
                                        <option value="Cancelled" <?php echo $active_order['status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label" for="tracking_description">Timeline Log Description</label>
                                    <textarea id="tracking_description" name="tracking_description" placeholder="Specify custom courier logs..." class="form-control" style="background-color: var(--bg-white); min-height: 80px;"></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-primary" style="display: block; width: 100%; font-size: 0.75rem; padding: 0.7rem 1rem;">
                                    Toggle Order State & Log Update
                                </button>
                            </form>
                            
                            <!-- Delivery Metadata -->
                            <div>
                                <h4 style="font-family: var(--font-serif); font-size: 1.25rem; color: var(--text-charcoal); border-bottom: 1px solid var(--admin-border); padding-bottom: 0.3rem; margin-bottom: 0.8rem;">
                                    Client Delivery details
                                </h4>
                                <div style="font-size: 0.85rem; color: var(--text-muted); display: flex; flex-direction: column; gap: 0.6rem;">
                                    <div><strong>Name:</strong> <?php echo e($active_order['recipient_name']); ?></div>
                                    <div><strong>Phone:</strong> <?php echo e($active_order['recipient_phone']); ?></div>
                                    <?php if ($active_order['delivery_type'] !== 'Pickup'): ?>
                                        <div><strong>Address:</strong> <?php echo e($active_order['delivery_address']); ?></div>
                                    <?php endif; ?>
                                    <div><strong>Datetime:</strong> <?php echo date('M d, Y', strtotime($active_order['delivery_date'])); ?> (<?php echo e($active_order['delivery_time_slot']); ?>)</div>
                                </div>
                            </div>
                            
                            <!-- Card Note -->
                            <?php if ($active_order['card_message']): ?>
                                <div>
                                    <h4 style="font-family: var(--font-serif); font-size: 1.25rem; color: var(--gold); border-bottom: 1px solid var(--admin-border); padding-bottom: 0.3rem; margin-bottom: 0.8rem;">
                                        Calligraphic Card Note
                                    </h4>
                                    <p style="font-size: 0.85rem; font-style: italic; background-color: var(--primary-blush); padding: 0.8rem 1rem; border-left: 2px solid var(--gold); line-height: 1.5;">
                                        "<?php echo e($active_order['card_message']); ?>"
                                    </p>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Stems Ordered -->
                            <div>
                                <h4 style="font-family: var(--font-serif); font-size: 1.25rem; color: var(--text-charcoal); border-bottom: 1px solid var(--admin-border); padding-bottom: 0.3rem; margin-bottom: 0.8rem;">
                                    Stems Ordered
                                </h4>
                                <div style="display: flex; flex-direction: column; gap: 0.8rem;">
                                    <?php foreach ($order_items as $item): ?>
                                        <div style="display: flex; align-items: center; justify-content: space-between; font-size: 0.85rem;">
                                            <div style="display: flex; gap: 0.6rem; align-items: center;">
                                                <div style="width: 30px; height: 40px; overflow: hidden; background-color: var(--champagne);">
                                                    <img src="../<?php echo e($item['image_url'] ?? 'assets/images/amour_rose.png'); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                                </div>
                                                <div>
                                                    <div style="font-weight: 500; color: var(--text-charcoal);"><?php echo e($item['name'] ?? 'Bespoke Bouquet'); ?></div>
                                                    <div style="font-size: 0.75rem; color: var(--text-muted);">x<?php echo $item['quantity']; ?></div>
                                                </div>
                                            </div>
                                            <div style="font-weight: 600;"><?php echo format_price($item['price'] * $item['quantity']); ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Total Row -->
                            <div style="border-top: 1px solid var(--admin-border); padding-top: 1rem; display: flex; justify-content: space-between; align-items: center; font-size: 1.1rem; font-weight: 600; color: var(--text-charcoal);">
                                <span>Grand Total (COD):</span>
                                <span style="color: var(--gold);"><?php echo format_price($active_order['total_amount']); ?></span>
                            </div>
                            
                        </div>
                    <?php else: ?>
                        <div class="admin-card" style="text-align: center; color: var(--text-muted); padding: 4rem 2rem;">
                            <i class="far fa-eye" style="font-size: 2.5rem; color: var(--admin-text-light); margin-bottom: 1.5rem; display: block;"></i>
                            <p style="font-size: 0.9rem;">Select an order booking from the inventory registry list to inspect details and modify status dispatches.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
            </div>
        </main>
        
    </div>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        // Automatic prefill values based on dropdown selection
        const prefillMap = {
            'Pending': 'Order created. Payment parameters restricted to Cash on Delivery.',
            'Confirmed': 'Order confirmed. Sourcing fresh botanical stems from local organic fields.',
            'Preparing Bouquet': 'Artisan florist wrapping is in progress. Fresh hydration gels added.',
            'Out for Delivery': 'Artisan courier has departed the boutique Chelsea greenhouse. On route.',
            'Delivered': 'Order successfully hand-delivered to the recipient. Cash on Delivery cleared.',
            'Cancelled': 'This order was cancelled by the boutique administration.'
        };
        
        function prefillDescription(status) {
            const txt = document.getElementById('tracking_description');
            if (txt) {
                txt.value = prefillMap[status] || '';
            }
        }
        
        // Initial prefill
        <?php if ($active_order): ?>
            prefillDescription(document.getElementById('status').value);
        <?php endif; ?>
    </script>
</body>
</html>
