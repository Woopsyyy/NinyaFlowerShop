<?php
/**
 * Ninya Flower Shop - Boutique Control Room
 * Shopify-style Admin Dashboard
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';

// Force Admin Access Gate
require_admin();

log_analytics('admin_view', ['section' => 'dashboard']);

// Fetch Dashboard aggregations
try {
    // 1. Total Orders count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders");
    $total_orders = $stmt->fetch()['count'] ?? 0;
    
    // 2. Total Revenue (sum of completed/delivered/confirmed orders, or all)
    $stmt = $pdo->query("SELECT SUM(total_amount) as revenue FROM orders WHERE status != 'Cancelled'");
    $total_revenue = $stmt->fetch()['revenue'] ?? 0.00;
    
    // 3. Registered Customers count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'");
    $total_customers = $stmt->fetch()['count'] ?? 0;
    
    // 4. Custom Requests count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM custom_requests");
    $total_custom_requests = $stmt->fetch()['count'] ?? 0;
    
    // 5. Recent orders list
    $stmt = $pdo->query("SELECT o.*, u.name as customer_name 
                         FROM orders o 
                         LEFT JOIN users u ON o.user_id = u.id 
                         ORDER BY o.created_at DESC LIMIT 5");
    $recent_orders = $stmt->fetchAll();
    
    // 6. Revenue trend data over last 7 days for our SVG Chart
    $revenue_data = [];
    $revenue_labels = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $stmt = $pdo->prepare("SELECT SUM(total_amount) as daily_rev FROM orders WHERE DATE(created_at) = ? AND status != 'Cancelled'");
        $stmt->execute([$date]);
        $rev = $stmt->fetch()['daily_rev'] ?? 0;
        
        $revenue_data[] = (float)$rev;
        $revenue_labels[] = date('M d', strtotime($date));
    }
} catch (PDOException $e) {
    die("Aggregation error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control Room - Ninya Flower Shop</title>
    
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
                <li>
                    <a href="dashboard.php" class="admin-menu-link active">
                        <i class="fas fa-th-large"></i> <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="analytics.php" class="admin-menu-link">
                        <i class="fas fa-chart-line"></i> <span>Analytics</span>
                    </a>
                </li>
                <li>
                    <a href="bouquets.php" class="admin-menu-link">
                        <i class="fas fa-seedling"></i> <span>Manage Stems</span>
                    </a>
                </li>
                <li>
                    <a href="orders.php" class="admin-menu-link">
                        <i class="fas fa-shopping-bag"></i> <span>Orders</span>
                    </a>
                </li>
                <li>
                    <a href="custom-requests.php" class="admin-menu-link">
                        <i class="fas fa-magic"></i> <span>Custom Requests</span>
                    </a>
                </li>
                <li>
                    <a href="customers.php" class="admin-menu-link">
                        <i class="fas fa-users"></i> <span>Customers</span>
                    </a>
                </li>
                <li>
                    <a href="testimonials.php" class="admin-menu-link">
                        <i class="fas fa-quote-left"></i> <span>Quotes</span>
                    </a>
                </li>
                <li>
                    <a href="../index.php" class="admin-menu-link">
                        <i class="fas fa-store"></i> <span>Boutique Front</span>
                    </a>
                </li>
                <li>
                    <a href="../logout.php" class="admin-menu-link" style="margin-top: 3rem; color: #f56565;">
                        <i class="fas fa-sign-out-alt"></i> <span>Log Out</span>
                    </a>
                </li>
            </ul>
        </aside>
        
        <!-- Right Main Workspace -->
        <main class="admin-content">
            
            <header class="admin-header">
                <div>
                    <span style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-muted);">Boutique Overview</span>
                    <h1 class="admin-title">Dashboard</h1>
                </div>
                <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 500;">
                    <i class="far fa-calendar-alt" style="margin-right: 0.4rem;"></i> <?php echo date('l, M d, Y'); ?>
                </div>
            </header>
            
            <!-- Metric summary cards -->
            <section class="metric-grid">
                <!-- Total Orders -->
                <div class="metric-card">
                    <div>
                        <span class="metric-title">Total Orders</span>
                        <div class="metric-value"><?php echo $total_orders; ?></div>
                    </div>
                    <span class="metric-trend trend-up"><i class="fas fa-arrow-up"></i> Lifetime sales</span>
                </div>
                
                <!-- Revenue -->
                <div class="metric-card">
                    <div>
                        <span class="metric-title">Revenue</span>
                        <div class="metric-value"><?php echo format_price($total_revenue); ?></div>
                    </div>
                    <span class="metric-trend trend-up"><i class="fas fa-arrow-up"></i> Excludes cancelled</span>
                </div>
                
                <!-- Customers -->
                <div class="metric-card">
                    <div>
                        <span class="metric-title">Customers</span>
                        <div class="metric-value"><?php echo $total_customers; ?></div>
                    </div>
                    <span class="metric-trend trend-up"><i class="fas fa-arrow-up"></i> Registered profiles</span>
                </div>
                
                <!-- Custom Requests -->
                <div class="metric-card">
                    <div>
                        <span class="metric-title">Bespoke Inquiries</span>
                        <div class="metric-value"><?php echo $total_custom_requests; ?></div>
                    </div>
                    <span class="metric-trend trend-up"><i class="fas fa-arrow-up"></i> Inspiration ideas</span>
                </div>
            </section>
            
            <!-- Curve SVG Chart panel -->
            <section class="admin-card">
                <div class="admin-card-header">
                    <h3 style="font-family: var(--font-serif); font-size: 1.4rem; color: var(--text-charcoal);">
                        Boutique Weekly Revenue Campaign
                    </h3>
                    <span style="font-size: 0.8rem; color: var(--text-muted);">Past 7 Days (USD)</span>
                </div>
                
                <!-- Embedded data parameters parsed by admin.js -->
                <div class="chart-container">
                    <div id="analytics-chart" style="width:100%; height:100%;" 
                         data-points="<?php echo e(json_encode($revenue_data)); ?>"
                         data-labels="<?php echo e(json_encode($revenue_labels)); ?>">
                    </div>
                </div>
            </section>
            
            <!-- Recent bookings table -->
            <section class="admin-card">
                <div class="admin-card-header">
                    <h3 style="font-family: var(--font-serif); font-size: 1.4rem; color: var(--text-charcoal);">
                        Recent Boutique Bookings
                    </h3>
                    <a href="orders.php" class="btn btn-secondary" style="font-size: 0.75rem; padding: 0.5rem 1rem;">
                        View All Orders
                    </a>
                </div>
                
                <div class="table-wrapper">
                    <table class="table-premium">
                        <thead>
                            <tr>
                                <th>Order Code</th>
                                <th>Client</th>
                                <th>Delivery Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Booked On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recent_orders)): ?>
                                <?php foreach ($recent_orders as $ord): ?>
                                    <tr>
                                        <td><strong><?php echo e($ord['tracking_number']); ?></strong></td>
                                        <td><?php echo e($ord['recipient_name']); ?></td>
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
                                        <td><?php echo date('M d, Y - h:i A', strtotime($ord['created_at'])); ?></td>
                                        <td>
                                            <a href="orders.php?order_id=<?php echo $ord['id']; ?>" class="btn" style="padding: 0.35rem 0.8rem; font-size: 0.7rem; background-color: var(--sage-green); color: var(--bg-white);">
                                                Manage
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; color: var(--text-muted); font-style: italic;">
                                        No bookings have been logged yet.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            
        </main>
        
    </div>

    <!-- FontAwesome & Custom Dashboard engine -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../assets/js/admin.js"></script>

</body>
</html>
