<?php
/**
 * Ninya Flower Shop - Boutique Control Room
 * Testimonials & Homepage Quotes Manager
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';

// Force Admin Access Gate
require_admin();

log_analytics('admin_view', ['section' => 'testimonials']);

$error_msg = '';
$success_msg = '';

// Handle testimonial CRUD & status toggles
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Toggle Active State
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_active') {
        $id = (int)$_POST['id'];
        $current_state = (int)$_POST['is_active'];
        $new_state = $current_state === 1 ? 0 : 1;
        
        try {
            $stmt = $pdo->prepare("UPDATE testimonials SET is_active = ? WHERE id = ?");
            $stmt->execute([$new_state, $id]);
            $success_msg = "Testimonial active state updated successfully!";
            log_analytics('testimonial_toggle', ['id' => $id, 'new_state' => $new_state]);
        } catch (PDOException $e) {
            $error_msg = "Failed to update state: " . $e->getMessage();
        }
    }
    
    // Delete Testimonial
    elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        
        try {
            $stmt = $pdo->prepare("DELETE FROM testimonials WHERE id = ?");
            $stmt->execute([$id]);
            $success_msg = "Testimonial successfully removed from registry.";
            log_analytics('testimonial_deleted', ['id' => $id]);
        } catch (PDOException $e) {
            $error_msg = "Failed to delete: " . $e->getMessage();
        }
    }
    
    // Create new Testimonial
    elseif (isset($_POST['action']) && $_POST['action'] === 'create') {
        $name = trim($_POST['name']);
        $role = trim($_POST['role']);
        $quote = trim($_POST['quote']);
        
        // Handle avatar upload if available, else use a placeholder avatar
        $avatar_url = 'assets/images/testimonial_1.jpg'; // default
        
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['avatar'];
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($file_ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $error_msg = 'Invalid avatar type. Only JPG, PNG, and WEBP are accepted.';
            } elseif ($file['size'] > 2 * 1024 * 1024) { // 2MB max
                $error_msg = 'Avatar size exceeds 2MB limit.';
            } else {
                $new_filename = 'avatar_' . bin2hex(random_bytes(6)) . '.' . $file_ext;
                $destination = __DIR__ . '/../assets/images/' . $new_filename;
                
                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $avatar_url = 'assets/images/' . $new_filename;
                } else {
                    $error_msg = 'Failed to save avatar image.';
                }
            }
        }
        
        if (empty($name) || empty($quote)) {
            $error_msg = "Name and Testimonial Quote fields are required.";
        }
        
        if (empty($error_msg)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO testimonials (name, role, quote, avatar_url, is_active) VALUES (?, ?, ?, ?, 1)");
                $stmt->execute([$name, $role, $quote, $avatar_url]);
                $success_msg = "New testimonial logged successfully!";
                log_analytics('testimonial_created', ['name' => $name]);
            } catch (PDOException $e) {
                $error_msg = "Database insert failed: " . $e->getMessage();
            }
        }
    }
}

// Fetch all testimonials
$testimonials = [];
try {
    $stmt = $pdo->query("SELECT * FROM testimonials ORDER BY created_at DESC");
    $testimonials = $stmt->fetchAll();
} catch (PDOException $e) {
    $testimonials = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Curate Testimonials - Ninya Flower Shop</title>
    
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
                <li><a href="customers.php" class="admin-menu-link"><i class="fas fa-users"></i> <span>Customers</span></a></li>
                <li><a href="testimonials.php" class="admin-menu-link active"><i class="fas fa-quote-left"></i> <span>Quotes</span></a></li>
                <li><a href="../index.php" class="admin-menu-link"><i class="fas fa-store"></i> <span>Boutique Front</span></a></li>
                <li><a href="../logout.php" class="admin-menu-link" style="margin-top: 3rem; color: #f56565;"><i class="fas fa-sign-out-alt"></i> <span>Log Out</span></a></li>
            </ul>
        </aside>
        
        <!-- Right Main Workspace -->
        <main class="admin-content">
            <header class="admin-header">
                <div>
                    <span style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-muted);">Brand Storytelling</span>
                    <h1 class="admin-title">Testimonials & Quotes</h1>
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
                
                <!-- Left panel: Testimonial Registry -->
                <div class="admin-card">
                    <h3 style="font-family: var(--font-serif); font-size: 1.4rem; color: var(--text-charcoal); border-bottom: 1px solid var(--admin-border); padding-bottom: 0.8rem; margin-bottom: 1.5rem;">
                        Homepage Testimonials
                    </h3>
                    
                    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                        <?php if (!empty($testimonials)): ?>
                            <?php foreach ($testimonials as $test): ?>
                                <div style="border: var(--border-soft); background-color: var(--bg-white); padding: 1.5rem; display: flex; gap: 1.5rem; justify-content: space-between; align-items: start;">
                                    <div style="display: flex; gap: 1.2rem; align-items: start;">
                                        <!-- Avatar -->
                                        <div style="width: 50px; height: 50px; border-radius: 50%; overflow: hidden; background-color: var(--champagne); flex-shrink: 0; border: var(--border-soft);">
                                            <img src="../<?php echo e($test['avatar_url'] ?: 'assets/images/testimonial_1.jpg'); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                        </div>
                                        <div>
                                            <div style="font-family: var(--font-serif); font-size: 1.1rem; color: var(--text-charcoal); font-weight: 500; display: flex; align-items: center; gap: 0.6rem;">
                                                <?php echo e($test['name']); ?>
                                                <?php if ($test['role']): ?>
                                                    <span style="font-family: var(--font-sans); font-size: 0.75rem; font-weight: normal; color: var(--text-muted); background-color: var(--primary-blush); padding: 0.1rem 0.4rem; border-radius: 3px;"><?php echo e($test['role']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <p style="font-size: 0.85rem; font-style: italic; color: #4A5568; line-height: 1.6; margin-top: 0.5rem; position: relative;">
                                                "<?php echo e($test['quote']); ?>"
                                            </p>
                                            <span style="font-size: 0.7rem; color: var(--text-muted); display: block; margin-top: 0.8rem;">Logged on: <?php echo date('M d, Y', strtotime($test['created_at'])); ?></span>
                                        </div>
                                    </div>
                                    
                                    <!-- Actions Panel -->
                                    <div style="display: flex; flex-direction: column; gap: 0.5rem; flex-shrink: 0; align-items: flex-end;">
                                        <!-- Active Status Toggle Form -->
                                        <form action="testimonials.php" method="POST">
                                            <input type="hidden" name="action" value="toggle_active">
                                            <input type="hidden" name="id" value="<?php echo $test['id']; ?>">
                                            <input type="hidden" name="is_active" value="<?php echo $test['is_active']; ?>">
                                            <?php if ($test['is_active'] === 1): ?>
                                                <button type="submit" class="btn" style="background-color: var(--success); color: var(--bg-white); font-size: 0.7rem; padding: 0.35rem 0.8rem; border-radius: 4px; display: flex; align-items: center; gap: 0.3rem;">
                                                    <i class="fas fa-eye"></i> Active on Home
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" class="btn" style="background-color: var(--text-muted); color: var(--bg-white); font-size: 0.7rem; padding: 0.35rem 0.8rem; border-radius: 4px; display: flex; align-items: center; gap: 0.3rem;">
                                                    <i class="fas fa-eye-slash"></i> Hidden
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                        
                                        <!-- Delete Form -->
                                        <form action="testimonials.php" method="POST" onsubmit="return confirm('Are you sure you want to permanently remove this quote from your site?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $test['id']; ?>">
                                            <button type="submit" class="btn" style="background-color: transparent; border: 1px solid var(--danger); color: var(--danger); font-size: 0.7rem; padding: 0.35rem 0.8rem; border-radius: 4px;">
                                                <i class="far fa-trash-alt"></i> Remove
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="text-align: center; color: var(--text-muted); font-style: italic; padding: 3rem;">
                                No testimonials have been registered.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Right panel: Create New Testimonial Form -->
                <div class="admin-card">
                    <h3 style="font-family: var(--font-serif); font-size: 1.4rem; color: var(--text-charcoal); border-bottom: 1px solid var(--admin-border); padding-bottom: 0.8rem; margin-bottom: 1.5rem;">
                        Log Quote
                    </h3>
                    
                    <form action="testimonials.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="form-group" style="margin-bottom: 1.2rem;">
                            <label class="form-label" for="name">Patron Name *</label>
                            <input type="text" id="name" name="name" required placeholder="e.g. Isabella Thorne" class="form-control" style="background-color: var(--bg-warm);">
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 1.2rem;">
                            <label class="form-label" for="role">Patron Subtext / Role</label>
                            <input type="text" id="role" name="role" placeholder="e.g. Happy Bride / CEO of Sterling Corp" class="form-control" style="background-color: var(--bg-warm);">
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 1.2rem;">
                            <label class="form-label" for="avatar">Patron Avatar (JPG, PNG, WEBP)</label>
                            <input type="file" id="avatar" name="avatar" class="form-control" style="border: 1px dashed rgba(143,168,155,0.4); padding: 0.5rem; background-color: var(--bg-warm);">
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 1.8rem;">
                            <label class="form-label" for="quote">Botanical Experience / Quote *</label>
                            <textarea id="quote" name="quote" required placeholder="Describe their luxury florist campaign experience..." class="form-control" style="background-color: var(--bg-warm); min-height: 100px;"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="display: block; width: 100%; font-size: 0.75rem; padding: 0.8rem;">
                            Publish Testimonial Log
                        </button>
                    </form>
                </div>
                
            </div>
        </main>
        
    </div>

    <!-- FontAwesome JS script -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</body>
</html>
