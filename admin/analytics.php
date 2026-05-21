<?php
/**
 * Ninya Flower Shop - Boutique Control Room
 * SaaS Analytics & Revenue Performance Reports
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';

// Force Admin Access Gate
require_admin();

log_analytics('admin_view', ['section' => 'analytics']);

try {
    // 1. High level statistics
    // 1.1 Lifetime revenue
    $stmt = $pdo->query("SELECT SUM(total_amount) as rev FROM orders WHERE status != 'Cancelled'");
    $lifetime_revenue = $stmt->fetch()['rev'] ?? 0.00;
    
    // 1.2 Lifetime orders count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders");
    $total_orders = $stmt->fetch()['count'] ?? 0;
    
    // 1.3 Average Order Value (AOV)
    $avg_order_value = $total_orders > 0 ? $lifetime_revenue / $total_orders : 0.00;
    
    // 1.4 Custom Bouquet inquiries
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM custom_requests");
    $total_custom_inquiries = $stmt->fetch()['cnt'] ?? 0;

    // 2. Revenue over the last 12 Days (for curve SVG rendering)
    $revenue_data = [];
    $revenue_labels = [];
    for ($i = 11; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $stmt = $pdo->prepare("SELECT SUM(total_amount) as daily_rev FROM orders WHERE DATE(created_at) = ? AND status != 'Cancelled'");
        $stmt->execute([$date]);
        $rev = $stmt->fetch()['daily_rev'] ?? 0;
        
        $revenue_data[] = (float)$rev;
        $revenue_labels[] = date('M d', strtotime($date));
    }

    // 3. Top-selling bouquets by purchase volume
    $stmt = $pdo->query("SELECT b.id, b.name, b.image_url, b.price, c.name as category_name,
                                SUM(oi.quantity) as total_qty,
                                SUM(oi.price * oi.quantity) as revenue_generated
                         FROM order_items oi
                         JOIN bouquets b ON oi.bouquet_id = b.id
                         JOIN categories c ON b.category_id = c.id
                         JOIN orders o ON oi.order_id = o.id
                         WHERE o.status != 'Cancelled'
                         GROUP BY b.id
                         ORDER BY total_qty DESC, revenue_generated DESC
                         LIMIT 5");
    $top_sellers = $stmt->fetchAll();

    // 4. Delivery method split
    $stmt = $pdo->query("SELECT delivery_type, COUNT(*) as count, SUM(total_amount) as amount 
                         FROM orders 
                         WHERE status != 'Cancelled'
                         GROUP BY delivery_type");
    $delivery_split = $stmt->fetchAll();
    
    $delivery_totals = ['Same Day' => 0, 'Scheduled' => 0, 'Pickup' => 0];
    $delivery_revs = ['Same Day' => 0.00, 'Scheduled' => 0.00, 'Pickup' => 0.00];
    $delivery_count_sum = 0;
    foreach ($delivery_split as $split) {
        $type = $split['delivery_type'];
        if (isset($delivery_totals[$type])) {
            $delivery_totals[$type] = (int)$split['count'];
            $delivery_revs[$type] = (float)$split['amount'];
            $delivery_count_sum += (int)$split['count'];
        }
    }

    // 5. Behavioral Analytics (from analytics event table)
    $stmt = $pdo->query("SELECT event_type, COUNT(*) as count 
                         FROM analytics 
                         GROUP BY event_type 
                         ORDER BY count DESC");
    $event_analytics = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Database aggregation failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SaaS Report Center - Ninya Flower Shop</title>
    
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
                <li><a href="analytics.php" class="admin-menu-link active"><i class="fas fa-chart-line"></i> <span>Analytics</span></a></li>
                <li><a href="bouquets.php" class="admin-menu-link"><i class="fas fa-seedling"></i> <span>Manage Stems</span></a></li>
                <li><a href="orders.php" class="admin-menu-link"><i class="fas fa-shopping-bag"></i> <span>Orders</span></a></li>
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
                    <span style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-muted);">SaaS Report Center</span>
                    <h1 class="admin-title">Boutique Analytics</h1>
                </div>
            </header>

            <!-- Metrics Card Deck -->
            <section class="metric-grid" style="margin-bottom: 2rem;">
                <div class="metric-card">
                    <div>
                        <span class="metric-title">Lifetime Revenue</span>
                        <div class="metric-value"><?php echo format_price($lifetime_revenue); ?></div>
                    </div>
                    <span class="metric-trend trend-up"><i class="fas fa-chart-line"></i> Total turnover</span>
                </div>
                <div class="metric-card">
                    <div>
                        <span class="metric-title">Bespoke Orders</span>
                        <div class="metric-value"><?php echo $total_orders; ?></div>
                    </div>
                    <span class="metric-trend trend-up"><i class="fas fa-shopping-cart"></i> Placed checkouts</span>
                </div>
                <div class="metric-card">
                    <div>
                        <span class="metric-title">Average Order (AOV)</span>
                        <div class="metric-value"><?php echo format_price($avg_order_value); ?></div>
                    </div>
                    <span class="metric-trend trend-up"><i class="fas fa-percentage"></i> Cart size value</span>
                </div>
                <div class="metric-card">
                    <div>
                        <span class="metric-title">Custom Inquiries</span>
                        <div class="metric-value"><?php echo $total_custom_inquiries; ?></div>
                    </div>
                    <span class="metric-trend trend-up"><i class="fas fa-magic"></i> Creative briefs</span>
                </div>
            </section>

            <!-- Curve Graph Campaign -->
            <section class="admin-card" style="margin-bottom: 2rem;">
                <div class="admin-card-header">
                    <h3 style="font-family: var(--font-serif); font-size: 1.4rem; color: var(--text-charcoal);">
                        Seasonal Boutique Revenue Trend
                    </h3>
                    <span style="font-size: 0.8rem; color: var(--text-muted);">Daily aggregates for past 12 days (excluding cancellations)</span>
                </div>
                <div class="chart-container">
                    <div id="analytics-chart" style="width:100%; height:100%;" 
                         data-points="<?php echo e(json_encode($revenue_data)); ?>"
                         data-labels="<?php echo e(json_encode($revenue_labels)); ?>">
                    </div>
                </div>
            </section>

            <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 2rem; align-items: start; margin-bottom: 2rem;">
                
                <!-- Left panel: Top-selling products -->
                <div class="admin-card" style="margin-bottom: 0;">
                    <h3 style="font-family: var(--font-serif); font-size: 1.3rem; color: var(--text-charcoal); border-bottom: 1px solid var(--admin-border); padding-bottom: 0.8rem; margin-bottom: 1.2rem;">
                        Top-Selling Arrangements
                    </h3>
                    
                    <div class="table-wrapper">
                        <table class="table-premium">
                            <thead>
                                <tr>
                                    <th>Flower Bouquet</th>
                                    <th style="text-align: center;">Qty Sold</th>
                                    <th style="text-align: right;">Revenue Generated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($top_sellers)): ?>
                                    <?php foreach ($top_sellers as $seller): ?>
                                        <tr>
                                            <td>
                                                <div style="display: flex; gap: 0.8rem; align-items: center;">
                                                    <div style="width: 35px; height: 45px; overflow: hidden; background-color: var(--champagne); border-radius: 2px;">
                                                        <img src="../<?php echo e($seller['image_url']); ?>" alt="" style="width:100%; height:100%; object-fit:cover;">
                                                    </div>
                                                    <div>
                                                        <div style="font-weight:600; color:var(--text-charcoal);"><?php echo e($seller['name']); ?></div>
                                                        <div style="font-size:0.75rem; color:var(--text-muted);"><?php echo e($seller['category_name']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td style="text-align: center; font-weight: 500;">
                                                <?php echo $seller['total_qty']; ?> bouquet(s)
                                            </td>
                                            <td style="text-align: right; font-weight: 600; color: var(--gold);">
                                                <?php echo format_price($seller['revenue_generated']); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" style="text-align: center; color: var(--text-muted); font-style: italic;">
                                            No products have been purchased yet.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Right panel: Delivery Preference Split -->
                <div class="admin-card" style="margin-bottom: 0;">
                    <h3 style="font-family: var(--font-serif); font-size: 1.3rem; color: var(--text-charcoal); border-bottom: 1px solid var(--admin-border); padding-bottom: 0.8rem; margin-bottom: 1.5rem;">
                        Delivery Preferences
                    </h3>
                    
                    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                        <?php foreach ($delivery_totals as $method => $count): ?>
                            <?php 
                            $pct = $delivery_count_sum > 0 ? round(($count / $delivery_count_sum) * 100) : 0;
                            $color = 'var(--sage-green)';
                            if ($method === 'Same Day') $color = 'var(--gold)';
                            elseif ($method === 'Pickup') $color = 'var(--text-muted)';
                            ?>
                            <div>
                                <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 0.4rem;">
                                    <strong style="color: var(--text-charcoal);"><?php echo $method; ?> Delivery</strong>
                                    <span style="color: var(--text-muted);"><?php echo $count; ?> order(s) (<?php echo $pct; ?>%)</span>
                                </div>
                                <div style="width: 100%; height: 8px; background-color: var(--admin-bg); border-radius: 4px; overflow: hidden;">
                                    <div style="width: <?php echo $pct; ?>%; height: 100%; background-color: <?php echo $color; ?>; border-radius: 4px;"></div>
                                </div>
                                <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--text-muted); margin-top: 0.3rem;">
                                    <span>Calculated revenue:</span>
                                    <strong><?php echo format_price($delivery_revs[$method]); ?></strong>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Behavioral Analytics Event Logging -->
            <div class="admin-card">
                <h3 style="font-family: var(--font-serif); font-size: 1.3rem; color: var(--text-charcoal); border-bottom: 1px solid var(--admin-border); padding-bottom: 0.8rem; margin-bottom: 1.2rem;">
                    Client Interaction Activity Logs
                </h3>
                <div class="table-wrapper">
                    <table class="table-premium">
                        <thead>
                            <tr>
                                <th>Interactive Activity</th>
                                <th>Event Description</th>
                                <th style="text-align: right;">Total Trigger Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($event_analytics)): ?>
                                <?php foreach ($event_analytics as $evt): ?>
                                    <?php 
                                    $pretty_name = ucwords(str_replace('_', ' ', $evt['event_type']));
                                    $desc = 'User triggered interaction inside Ninya boutique platform.';
                                    if ($evt['event_type'] === 'page_view') $desc = 'Visitor loaded customer-facing pages (index, shop, about, bouquet).';
                                    elseif ($evt['event_type'] === 'admin_view') $desc = 'Control Room panels loaded by administrative account.';
                                    elseif ($evt['event_type'] === 'add_to_cart') $desc = 'Arrangement successfully added to a guest or user shopping bag.';
                                    elseif ($evt['event_type'] === 'wishlist_add') $desc = 'Bouquet saved to client wishlist profile.';
                                    elseif ($evt['event_type'] === 'custom_request_placed') $desc = 'Bespoke custom bouquet request designer uploaded successfully.';
                                    elseif ($evt['event_type'] === 'order_placed') $desc = 'Cash on Delivery checkout sheet submitted.';
                                    elseif ($evt['event_type'] === 'order_status_updated') $desc = 'Order timeline log status updated in database.';
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="badge-status badge-preparing" style="font-size: 0.75rem;">
                                                <?php echo e($evt['event_type']); ?>
                                            </span>
                                        </td>
                                        <td style="color: var(--text-muted); font-size: 0.8rem;"><?php echo $desc; ?></td>
                                        <td style="text-align: right; font-weight: 600; font-size: 0.9rem; color: var(--text-charcoal);">
                                            <?php echo number_format($evt['count']); ?> action(s)
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" style="text-align: center; color: var(--text-muted); font-style: italic;">
                                        No interactions have been logged yet.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
        
    </div>

    <!-- FontAwesome & Dashboard coordinates engine -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../assets/js/admin.js"></script>

</body>
</html>
