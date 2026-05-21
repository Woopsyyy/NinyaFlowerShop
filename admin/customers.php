<?php
/**
 * Ninya Flower Shop - Boutique Control Room
 * Customers Registry & Luxury CRM
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';

// Force Admin Access Gate
require_admin();

log_analytics('admin_view', ['section' => 'customers']);

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Query Customers with total orders and lifetime value (LTV) spent
try {
    $sql = "SELECT u.id, u.name, u.email, u.phone, u.created_at,
                   COUNT(o.id) as total_bookings,
                   COALESCE(SUM(CASE WHEN o.status != 'Cancelled' THEN o.total_amount ELSE 0 END), 0.00) as total_spent
            FROM users u
            LEFT JOIN orders o ON u.id = o.user_id
            WHERE u.role = 'customer'";
            
    if ($search_query !== '') {
        $sql .= " AND (u.name LIKE :search OR u.email LIKE :search OR u.phone LIKE :search)";
    }
    
    $sql .= " GROUP BY u.id ORDER BY total_spent DESC, u.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    if ($search_query !== '') {
        $stmt->bindValue(':search', '%' . $search_query . '%');
    }
    $stmt->execute();
    $customers = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boutique Client Registry - Ninya Flower Shop</title>
    
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
                <li><a href="orders.php" class="admin-menu-link"><i class="fas fa-shopping-bag"></i> <span>Orders</span></a></li>
                <li><a href="custom-requests.php" class="admin-menu-link"><i class="fas fa-magic"></i> <span>Custom Requests</span></a></li>
                <li><a href="customers.php" class="admin-menu-link active"><i class="fas fa-users"></i> <span>Customers</span></a></li>
                <li><a href="testimonials.php" class="admin-menu-link"><i class="fas fa-quote-left"></i> <span>Quotes</span></a></li>
                <li><a href="../index.php" class="admin-menu-link"><i class="fas fa-store"></i> <span>Boutique Front</span></a></li>
                <li><a href="../logout.php" class="admin-menu-link" style="margin-top: 3rem; color: #f56565;"><i class="fas fa-sign-out-alt"></i> <span>Log Out</span></a></li>
            </ul>
        </aside>
        
        <!-- Right Main Workspace -->
        <main class="admin-content">
            <header class="admin-header">
                <div>
                    <span style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-muted);">Luxury CRM profiles</span>
                    <h1 class="admin-title">Customer Registry</h1>
                </div>
            </header>

            <!-- Search Filter Bar -->
            <div class="admin-card" style="padding: 1.5rem; margin-bottom: 2rem;">
                <form action="customers.php" method="GET" style="display: flex; gap: 1rem; align-items: center;">
                    <div style="flex: 1; position: relative;">
                        <input type="text" name="search" placeholder="Search by name, email, or phone number..." value="<?php echo e($search_query); ?>" class="form-control" style="padding-left: 2.5rem; background-color: var(--bg-warm); width: 100%;">
                        <i class="fas fa-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                    </div>
                    <button type="submit" class="btn btn-primary" style="padding: 0.8rem 2rem; font-size: 0.8rem;">
                        Search Profiles
                    </button>
                    <?php if ($search_query !== ''): ?>
                        <a href="customers.php" class="btn btn-secondary" style="padding: 0.8rem 1.5rem; font-size: 0.8rem;">
                            Clear Filter
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Customer List Table -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3 style="font-family: var(--font-serif); font-size: 1.4rem; color: var(--text-charcoal);">
                        Botanical Patron list
                    </h3>
                    <span style="font-size: 0.8rem; color: var(--text-muted);"><?php echo count($customers); ?> registered customer profile(s)</span>
                </div>
                
                <div class="table-wrapper">
                    <table class="table-premium">
                        <thead>
                            <tr>
                                <th>Client Profile</th>
                                <th>Contact Information</th>
                                <th>Joined Date</th>
                                <th style="text-align: center;">Total Bookings</th>
                                <th style="text-align: right;">Lifetime Value (LTV)</th>
                                <th style="text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($customers)): ?>
                                <?php foreach ($customers as $cust): ?>
                                    <tr>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 0.8rem;">
                                                <div style="width: 40px; height: 40px; border-radius: 50%; background-color: var(--primary-blush); color: var(--gold); display: flex; align-items: center; justify-content: center; font-weight: 600; font-family: var(--font-serif); border: var(--border-soft);">
                                                    <?php 
                                                    $parts = explode(' ', $cust['name']);
                                                    $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
                                                    echo $initials;
                                                    ?>
                                                </div>
                                                <div>
                                                    <div style="font-weight: 600; color: var(--text-charcoal); font-size: 0.95rem;"><?php echo e($cust['name']); ?></div>
                                                    <div style="font-size: 0.75rem; color: var(--text-muted);">ID: #<?php echo $cust['id']; ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="display: flex; flex-direction: column; gap: 0.2rem;">
                                                <div><i class="far fa-envelope" style="width: 16px; color: var(--text-muted);"></i> <?php echo e($cust['email']); ?></div>
                                                <?php if ($cust['phone']): ?>
                                                    <div><i class="fas fa-phone" style="width: 16px; color: var(--text-muted);"></i> <?php echo e($cust['phone']); ?></div>
                                                <?php else: ?>
                                                    <div style="font-style: italic; color: var(--text-muted);"><i class="fas fa-phone" style="width: 16px;"></i> No phone logged</div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo date('M d, Y', strtotime($cust['created_at'])); ?>
                                        </td>
                                        <td style="text-align: center; font-weight: 500;">
                                            <span class="badge-status badge-preparing" style="font-size: 0.75rem; padding: 0.2rem 0.5rem;">
                                                <?php echo $cust['total_bookings']; ?> order(s)
                                            </span>
                                        </td>
                                        <td style="text-align: right; font-weight: 600; color: var(--gold); font-size: 0.95rem;">
                                            <?php echo format_price($cust['total_spent']); ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <a href="orders.php?search=<?php echo urlencode($cust['name']); ?>" class="btn" style="padding: 0.35rem 0.8rem; font-size: 0.7rem; background-color: var(--sage-green); color: var(--bg-white);">
                                                View Bookings
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: var(--text-muted); font-style: italic;">
                                        No customer profiles found matching filters.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
        
    </div>

    <!-- FontAwesome JS script -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</body>
</html>
