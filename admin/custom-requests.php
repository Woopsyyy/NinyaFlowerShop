<?php
/**
 * Ninya Flower Shop - Boutique Control Room
 * Bespoke Custom Bouquet Requests Manager
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';

// Force Admin Access Gate
require_admin();

log_analytics('admin_view', ['section' => 'custom_requests']);

$error_msg = '';
$success_msg = '';

// Handle Status Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $request_id = (int)$_POST['request_id'];
    $new_status = trim($_POST['status']);
    
    $allowed_statuses = ['Pending', 'Reviewed', 'Approved', 'Declined'];
    
    if (in_array($new_status, $allowed_statuses)) {
        try {
            $stmt = $pdo->prepare("UPDATE custom_requests SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $request_id]);
            
            $success_msg = "Custom request status updated to '{$new_status}' successfully!";
            log_analytics('custom_request_status_updated', ['request_id' => $request_id, 'new_status' => $new_status]);
        } catch (PDOException $e) {
            $error_msg = 'Database error: ' . $e->getMessage();
        }
    } else {
        $error_msg = 'Invalid status selected.';
    }
}

// Fetch all custom requests
$requests = [];
try {
    $stmt = $pdo->query("SELECT cr.*, u.name as customer_profile_name 
                         FROM custom_requests cr 
                         LEFT JOIN users u ON cr.user_id = u.id 
                         ORDER BY cr.created_at DESC");
    $requests = $stmt->fetchAll();
} catch (PDOException $e) {
    $requests = [];
}

// Determine active request for details inspection
$active_req = null;
$focus_id = isset($_GET['request_id']) ? (int)$_GET['request_id'] : 0;
if ($focus_id > 0) {
    foreach ($requests as $req) {
        if ($req['id'] === $focus_id) {
            $active_req = $req;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bespoke Custom Inquiries - Ninya Flower Shop</title>
    
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
                <li><a href="custom-requests.php" class="admin-menu-link active"><i class="fas fa-magic"></i> <span>Custom Requests</span></a></li>
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
                    <span style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-muted);">Bespoke client ideas</span>
                    <h1 class="admin-title">Custom Requests</h1>
                </div>
            </header>

            <!-- Alerts -->
            <?php if ($error_msg): ?>
                <div class="alert alert-error"><?php echo e($error_msg); ?></div>
            <?php endif; ?>
            <?php if ($success_msg): ?>
                <div class="alert alert-success"><?php echo e($success_msg); ?></div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 1.4fr 1fr; gap: 3rem; align-items: start;">
                
                <!-- Left panel: List of all Custom Requests -->
                <div class="admin-card">
                    <h3 style="font-family: var(--font-serif); font-size: 1.4rem; color: var(--text-charcoal); border-bottom: 1px solid var(--admin-border); padding-bottom: 0.8rem; margin-bottom: 1.5rem;">
                        Inspiration Registries
                    </h3>
                    
                    <div class="table-wrapper">
                        <table class="table-premium">
                            <thead>
                                <tr>
                                    <th>Client</th>
                                    <th>Budget</th>
                                    <th>Color Theme</th>
                                    <th>Delivery Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($requests)): ?>
                                    <?php foreach ($requests as $req): ?>
                                        <tr style="<?php echo ($active_req && $active_req['id'] === $req['id']) ? 'background-color: var(--sage-light);' : ''; ?>">
                                            <td>
                                                <div style="font-weight: 600; color: var(--text-charcoal);"><?php echo e($req['name']); ?></div>
                                                <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo e($req['email']); ?></div>
                                            </td>
                                            <td style="font-weight: 500;"><?php echo e($req['budget_range']); ?></td>
                                            <td>
                                                <span style="font-size: 0.8rem;"><?php echo e($req['color_theme']); ?></span>
                                            </td>
                                            <td>
                                                <?php echo $req['delivery_date'] ? date('M d, Y', strtotime($req['delivery_date'])) : '<span style="font-style:italic;color:var(--text-muted);">Unscheduled</span>'; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $st = strtolower($req['status']);
                                                $badge_class = 'badge-preparing'; // fallback
                                                if ($st === 'pending') $badge_class = 'badge-pending';
                                                elseif ($st === 'reviewed') $badge_class = 'badge-outfordelivery';
                                                elseif ($st === 'approved') $badge_class = 'badge-confirmed';
                                                elseif ($st === 'declined') $badge_class = 'badge-cancelled';
                                                ?>
                                                <span class="badge-status <?php echo $badge_class; ?>">
                                                    <?php echo e($req['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="custom-requests.php?request_id=<?php echo $req['id']; ?>" class="btn" style="padding: 0.35rem 0.6rem; font-size: 0.7rem; background-color: var(--sage-green); color: var(--bg-white);">
                                                    Inspect
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; color: var(--text-muted); font-style: italic;">
                                            No bespoke design requests have been filed yet.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Right panel: Selected Custom Request Inspection & Status Toggler -->
                <div>
                    <?php if ($active_req): ?>
                        <div class="admin-card" style="display: flex; flex-direction: column; gap: 2rem;">
                            
                            <!-- Header Title -->
                            <div>
                                <span style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Bespoke Request ##<?php echo $active_req['id']; ?></span>
                                <h3 style="font-family: var(--font-serif); font-size: 1.6rem; color: var(--text-charcoal); border-bottom: 1px solid var(--admin-border); padding-bottom: 0.5rem;">
                                    <?php echo e($active_req['name']); ?>'s Inquiry
                                </h3>
                            </div>
                            
                            <!-- Status Selector Form -->
                            <form action="custom-requests.php?request_id=<?php echo $active_req['id']; ?>" method="POST" style="background-color: var(--bg-warm); padding: 1.5rem; border: var(--border-soft);">
                                <input type="hidden" name="update_status" value="1">
                                <input type="hidden" name="request_id" value="<?php echo $active_req['id']; ?>">
                                
                                <div class="form-group" style="margin-bottom: 1rem;">
                                    <label class="form-label" for="status">Modify Inquiry Status</label>
                                    <select id="status" name="status" class="form-control" style="background-color: var(--bg-white);">
                                        <option value="Pending" <?php echo $active_req['status'] === 'Pending' ? 'selected' : ''; ?>>Pending (Reviewing)</option>
                                        <option value="Reviewed" <?php echo $active_req['status'] === 'Reviewed' ? 'selected' : ''; ?>>Reviewed (Contacting customer)</option>
                                        <option value="Approved" <?php echo $active_req['status'] === 'Approved' ? 'selected' : ''; ?>>Approved (Quotation sent)</option>
                                        <option value="Declined" <?php echo $active_req['status'] === 'Declined' ? 'selected' : ''; ?>>Declined / Closed</option>
                                    </select>
                                </div>
                                
                                <button type="submit" class="btn btn-primary" style="display: block; width: 100%; font-size: 0.75rem; padding: 0.7rem 1rem;">
                                    Update Inquiry State
                                </button>
                            </form>
                            
                            <!-- Design Parameters -->
                            <div style="display: flex; flex-direction: column; gap: 1rem;">
                                <h4 style="font-family: var(--font-serif); font-size: 1.25rem; color: var(--text-charcoal); border-bottom: 1px solid var(--admin-border); padding-bottom: 0.3rem; margin-bottom: 0.5rem;">
                                    Arrangement Preferences
                                </h4>
                                
                                <div style="font-size: 0.85rem; display: flex; flex-direction: column; gap: 0.8rem; color: var(--text-charcoal);">
                                    <div>
                                        <strong style="color: var(--text-muted); display: block; margin-bottom: 0.2rem;">Chosen Stems & Blooms:</strong>
                                        <span style="font-weight: 500; font-family: var(--font-serif); font-size: 0.95rem; color: var(--sage-green);"><?php echo e($active_req['flower_preferences'] ?: 'Bespoke mix'); ?></span>
                                    </div>
                                    
                                    <div>
                                        <strong style="color: var(--text-muted); display: block; margin-bottom: 0.2rem;">Color Palette:</strong>
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <?php
                                            $col_val = trim($active_req['color_theme']);
                                            $swatch_color = '#FDF6F6'; // pink
                                            if (stripos($col_val, 'beige') !== false) $swatch_color = '#F3EFE9';
                                            elseif (stripos($col_val, 'sage') !== false) $swatch_color = '#D5E0D5';
                                            elseif (stripos($col_val, 'romance') !== false) $swatch_color = '#A63F3F';
                                            ?>
                                            <div style="width: 14px; height: 14px; border-radius: 50%; border: 1px solid var(--admin-border); background-color: <?php echo $swatch_color; ?>;"></div>
                                            <span style="font-weight: 500;"><?php echo e($col_val); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <strong style="color: var(--text-muted); display: block; margin-bottom: 0.2rem;">Budget Allocated:</strong>
                                        <span style="font-weight: 600; color: var(--gold);"><?php echo e($active_req['budget_range']); ?></span>
                                    </div>
                                    
                                    <div>
                                        <strong style="color: var(--text-muted); display: block; margin-bottom: 0.2rem;">Requested Timeline:</strong>
                                        <span><?php echo $active_req['delivery_date'] ? date('M d, Y', strtotime($active_req['delivery_date'])) : 'Anytime (Flexible schedule)'; ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Custom Specifications / Notes -->
                            <div>
                                <h4 style="font-family: var(--font-serif); font-size: 1.25rem; color: var(--text-charcoal); border-bottom: 1px solid var(--admin-border); padding-bottom: 0.3rem; margin-bottom: 0.8rem;">
                                    Client specifications
                                </h4>
                                <p style="font-size: 0.85rem; line-height: 1.6; background-color: var(--bg-warm); padding: 1rem; border: var(--border-soft); border-left: 2px solid var(--sage-green); font-style: italic; color: #4A5568;">
                                    <?php echo $active_req['message'] ? nl2br(e($active_req['message'])) : 'No additional design logs provided.'; ?>
                                </p>
                            </div>

                            <!-- Customer Profile -->
                            <div>
                                <h4 style="font-family: var(--font-serif); font-size: 1.25rem; color: var(--text-charcoal); border-bottom: 1px solid var(--admin-border); padding-bottom: 0.3rem; margin-bottom: 0.8rem;">
                                    Contact Profile
                                </h4>
                                <div style="font-size: 0.85rem; color: var(--text-muted); display: flex; flex-direction: column; gap: 0.5rem;">
                                    <div><strong>Name:</strong> <?php echo e($active_req['name']); ?></div>
                                    <div><strong>Email:</strong> <a href="mailto:<?php echo e($active_req['email']); ?>" style="color: var(--sage-green); text-decoration: underline;"><?php echo e($active_req['email']); ?></a></div>
                                    <div><strong>Phone:</strong> <?php echo e($active_req['phone']); ?></div>
                                    <div><strong>Logged Client:</strong> <?php echo $active_req['customer_profile_name'] ? '<span class="badge-status badge-confirmed" style="padding: 0.1rem 0.4rem;">Registered</span>' : '<span class="badge-status badge-preparing" style="padding: 0.1rem 0.4rem;">Guest Checkout</span>'; ?></div>
                                </div>
                            </div>
                            
                            <!-- Inspiration Image -->
                            <?php if ($active_req['image_path']): ?>
                                <div>
                                    <h4 style="font-family: var(--font-serif); font-size: 1.25rem; color: var(--text-charcoal); border-bottom: 1px solid var(--admin-border); padding-bottom: 0.3rem; margin-bottom: 0.8rem;">
                                        Uploaded Inspiration Idea
                                    </h4>
                                    <a href="../<?php echo e($active_req['image_path']); ?>" target="_blank" style="display: block; border: 1px solid var(--admin-border); padding: 0.5rem; background-color: var(--bg-white); transition: var(--transition-fast);" onmouseover="this.style.borderColor='var(--gold)';" onmouseout="this.style.borderColor='var(--admin-border)';">
                                        <img src="../<?php echo e($active_req['image_path']); ?>" alt="Inspiration" style="width: 100%; max-height: 250px; object-fit: contain; display: block; margin: 0 auto;">
                                        <span style="display: block; text-align: center; font-size: 0.7rem; color: var(--text-muted); margin-top: 0.5rem;"><i class="fas fa-search-plus"></i> Click to inspect image full screen</span>
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                        </div>
                    <?php else: ?>
                        <div class="admin-card" style="text-align: center; color: var(--text-muted); padding: 4rem 2rem;">
                            <i class="fas fa-magic" style="font-size: 2.5rem; color: var(--admin-text-light); margin-bottom: 1.5rem; display: block;"></i>
                            <p style="font-size: 0.9rem;">Select a bespoke custom inquiry from the inspiration registry list to review color boards, upload details, and manage client dispatches.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
            </div>
        </main>
        
    </div>

    <!-- FontAwesome JS script -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</body>
</html>
