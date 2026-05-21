<?php
/**
 * Ninya Flower Shop - Boutique Control Room
 * Bouquet Catalog Management (CRUD)
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

log_analytics('admin_view', ['section' => 'bouquets']);

$error_msg = '';
$success_msg = '';

// Handle CRUD Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. CREATE or UPDATE Bouquet
    if (isset($_POST['save_bouquet'])) {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $name = trim($_POST['name']);
        $slug = trim($_POST['slug']);
        $description = trim($_POST['description']);
        $meaning = trim($_POST['meaning']);
        $price = (float)$_POST['price'];
        $sale_price = $_POST['sale_price'] !== '' ? (float)$_POST['sale_price'] : null;
        $stock = (int)$_POST['stock'];
        $category_id = (int)$_POST['category_id'];
        
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $is_best_seller = isset($_POST['is_best_seller']) ? 1 : 0;
        $is_wedding = isset($_POST['is_wedding']) ? 1 : 0;
        
        // Auto slug if empty
        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        }
        
        $image_url = isset($_POST['existing_image']) ? $_POST['existing_image'] : 'assets/images/amour_rose.png';
        
        // Handle File upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image'];
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (in_array($file_ext, ALLOWED_EXTENSIONS)) {
                $new_filename = bin2hex(random_bytes(10)) . '.' . $file_ext;
                $destination = UPLOAD_DIR . $new_filename;
                
                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $image_url = UPLOAD_URL . $new_filename;
                }
            }
        }
        
        if (empty($name) || $price <= 0 || $category_id <= 0) {
            $error_msg = 'Please fill in all required bouquet parameters.';
        } else {
            try {
                if ($id > 0) {
                    // Update
                    $stmt = $pdo->prepare("UPDATE bouquets SET category_id = ?, name = ?, slug = ?, description = ?, meaning = ?, price = ?, sale_price = ?, stock = ?, image_url = ?, is_featured = ?, is_best_seller = ?, is_wedding = ? WHERE id = ?");
                    $stmt->execute([
                        $category_id, $name, $slug, $description, $meaning, $price, $sale_price, $stock, $image_url,
                        $is_featured, $is_best_seller, $is_wedding, $id
                    ]);
                    $success_msg = "{$name} updated successfully!";
                } else {
                    // Create
                    $stmt = $pdo->prepare("INSERT INTO bouquets (category_id, name, slug, description, meaning, price, sale_price, stock, image_url, is_featured, is_best_seller, is_wedding) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $category_id, $name, $slug, $description, $meaning, $price, $sale_price, $stock, $image_url,
                        $is_featured, $is_best_seller, $is_wedding
                    ]);
                    $success_msg = "{$name} added to catalog!";
                }
            } catch (PDOException $e) {
                $error_msg = 'Database transaction failed: ' . $e->getMessage();
            }
        }
    }
    
    // 2. DELETE Bouquet
    if (isset($_POST['delete_bouquet'])) {
        $id = (int)$_POST['id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM bouquets WHERE id = ?");
            $stmt->execute([$id]);
            $success_msg = 'Bouquet deleted from catalog.';
        } catch (PDOException $e) {
            $error_msg = 'Failed to delete bouquet: ' . $e->getMessage();
        }
    }
}

// Fetch all bouquets for list
$bouquets = [];
$categories = [];
try {
    $stmt = $pdo->query("SELECT b.*, c.name as category_name 
                         FROM bouquets b 
                         LEFT JOIN categories c ON b.category_id = c.id 
                         ORDER BY b.created_at DESC");
    $bouquets = $stmt->fetchAll();
    
    $categories = get_categories();
} catch (PDOException $e) {
    $bouquets = [];
}

// Check Edit action
$edit_bouquet = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    foreach ($bouquets as $bq) {
        if ($bq['id'] === $edit_id) {
            $edit_bouquet = $bq;
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
    <title>Bespoke Catalog Manager - Ninya Flower Shop</title>
    
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
                <li><a href="bouquets.php" class="admin-menu-link active"><i class="fas fa-seedling"></i> <span>Manage Stems</span></a></li>
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
                    <span style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-muted);">Greenhouse inventory</span>
                    <h1 class="admin-title">Manage Stems</h1>
                </div>
            </header>

            <!-- Alerts -->
            <?php if ($error_msg): ?>
                <div class="alert alert-error"><?php echo e($error_msg); ?></div>
            <?php endif; ?>
            <?php if ($success_msg): ?>
                <div class="alert alert-success"><?php echo e($success_msg); ?></div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 1fr 1.8fr; gap: 3rem; align-items: start;">
                
                <!-- Left panel: Form editor -->
                <div class="admin-card">
                    <h3 style="font-family: var(--font-serif); font-size: 1.4rem; color: var(--text-charcoal); border-bottom: 1px solid var(--admin-border); padding-bottom: 0.8rem; margin-bottom: 1.5rem;">
                        <?php echo $edit_bouquet ? 'Edit Arrangement' : 'Add New Bouquet'; ?>
                    </h3>
                    
                    <form action="bouquets.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="save_bouquet" value="1">
                        <?php if ($edit_bouquet): ?>
                            <input type="hidden" name="id" value="<?php echo $edit_bouquet['id']; ?>">
                            <input type="hidden" name="existing_image" value="<?php echo $edit_bouquet['image_url']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label class="form-label" for="name">Bouquet Name *</label>
                            <input type="text" id="name" name="name" required value="<?php echo $edit_bouquet ? e($edit_bouquet['name']) : ''; ?>" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="slug">Slug (Optional URL key)</label>
                            <input type="text" id="slug" name="slug" placeholder="e.g. amour-de-rose" value="<?php echo $edit_bouquet ? e($edit_bouquet['slug']) : ''; ?>" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="category_id">Category *</label>
                            <select id="category_id" name="category_id" required class="form-control">
                                <option value="">-- Choose Category --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo ($edit_bouquet && $edit_bouquet['category_id'] == $cat['id']) ? 'selected' : ''; ?>><?php echo e($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="grid-3" style="gap: 1rem;">
                            <div class="form-group">
                                <label class="form-label" for="price">Standard Price *</label>
                                <input type="number" step="0.01" id="price" name="price" required value="<?php echo $edit_bouquet ? $edit_bouquet['price'] : ''; ?>" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="sale_price">Sale Price</label>
                                <input type="number" step="0.01" id="sale_price" name="sale_price" value="<?php echo ($edit_bouquet && $edit_bouquet['sale_price'] !== null) ? $edit_bouquet['sale_price'] : ''; ?>" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="stock">In Stock *</label>
                                <input type="number" id="stock" name="stock" required value="<?php echo $edit_bouquet ? $edit_bouquet['stock'] : '10'; ?>" class="form-control">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="image">Arrangement Image File *</label>
                            <input type="file" id="image" name="image" class="form-control">
                            <?php if ($edit_bouquet): ?>
                                <span style="font-size: 0.75rem; color: var(--text-muted);">Has active image: <strong><?php echo basename($edit_bouquet['image_url']); ?></strong></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="description">Floral Description *</label>
                            <textarea id="description" name="description" required placeholder="Describe wrapper layers, flower counts..." class="form-control" style="min-height: 80px;"><?php echo $edit_bouquet ? e($edit_bouquet['description']) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="meaning">Emotional Bloom Meaning *</label>
                            <textarea id="meaning" name="meaning" required placeholder="e.g. Roses carry timeless symbols of devotion..." class="form-control" style="min-height: 60px;"><?php echo $edit_bouquet ? e($edit_bouquet['meaning']) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-group" style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 1rem; border-top: 1px dashed var(--admin-border); padding-top: 1rem;">
                            <label style="display: flex; align-items: center; gap: 0.8rem; font-size: 0.85rem; cursor: pointer;">
                                <input type="checkbox" name="is_featured" value="1" <?php echo ($edit_bouquet && $edit_bouquet['is_featured']) ? 'checked' : ''; ?>>
                                <span>Signature Featured Listing</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.8rem; font-size: 0.85rem; cursor: pointer;">
                                <input type="checkbox" name="is_best_seller" value="1" <?php echo ($edit_bouquet && $edit_bouquet['is_best_seller']) ? 'checked' : ''; ?>>
                                <span>Mark as Popular Best Seller</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.8rem; font-size: 0.85rem; cursor: pointer;">
                                <input type="checkbox" name="is_wedding" value="1" <?php echo ($edit_bouquet && $edit_bouquet['is_wedding']) ? 'checked' : ''; ?>>
                                <span>Include in Wedding Collections</span>
                            </label>
                        </div>
                        
                        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                            <button type="submit" class="btn btn-primary" style="flex: 1; font-size: 0.75rem; padding: 0.6rem 1rem;">
                                <?php echo $edit_bouquet ? 'Save Changes' : 'Create Bouquet'; ?>
                            </button>
                            <?php if ($edit_bouquet): ?>
                                <a href="bouquets.php" class="btn btn-secondary" style="font-size: 0.75rem; padding: 0.6rem 1.2rem;">
                                    Cancel
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                
                <!-- Right panel: Interactive inventory table -->
                <div class="admin-card">
                    <h3 style="font-family: var(--font-serif); font-size: 1.4rem; color: var(--text-charcoal); border-bottom: 1px solid var(--admin-border); padding-bottom: 0.8rem; margin-bottom: 1.5rem;">
                        Inventory Registry
                    </h3>
                    
                    <div class="table-wrapper">
                        <table class="table-premium">
                            <thead>
                                <tr>
                                    <th>Stems</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($bouquets)): ?>
                                    <?php foreach ($bouquets as $bq): ?>
                                        <tr>
                                            <td style="width: 50px;">
                                                <div style="width: 40px; height: 50px; overflow: hidden; background-color: var(--champagne);">
                                                    <img src="../<?php echo e($bq['image_url']); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                                </div>
                                            </td>
                                            <td>
                                                <strong><?php echo e($bq['name']); ?></strong>
                                                <div style="display: flex; gap: 0.4rem; margin-top: 0.2rem;">
                                                    <?php if ($bq['is_featured']): ?><span style="font-size: 0.6rem; color: var(--gold); border: 1px solid var(--gold); padding: 0 0.25rem;">Sig</span><?php endif; ?>
                                                    <?php if ($bq['is_best_seller']): ?><span style="font-size: 0.6rem; color: var(--sage-green); border: 1px solid var(--sage-green); padding: 0 0.25rem;">Best</span><?php endif; ?>
                                                    <?php if ($bq['is_wedding']): ?><span style="font-size: 0.6rem; color: #4299e1; border: 1px solid #4299e1; padding: 0 0.25rem;">Wed</span><?php endif; ?>
                                                </div>
                                            </td>
                                            <td><?php echo e($bq['category_name'] ?? 'Bespoke'); ?></td>
                                            <td>
                                                <?php if ($bq['sale_price'] !== null): ?>
                                                    <span style="color: var(--gold);"><?php echo format_price($bq['sale_price']); ?></span>
                                                <?php else: ?>
                                                    <span><?php echo format_price($bq['price']); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span style="font-weight: 500; color: <?php echo $bq['stock'] < 5 ? '#f56565' : 'inherit'; ?>;">
                                                    <?php echo $bq['stock']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div style="display: flex; gap: 0.5rem;">
                                                    <a href="bouquets.php?edit=<?php echo $bq['id']; ?>" class="btn" style="padding: 0.35rem 0.6rem; font-size: 0.7rem; background-color: var(--sage-green); color: var(--bg-white);">
                                                        Edit
                                                    </a>
                                                    
                                                    <form action="bouquets.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this bouquet from the greenhouse catalogue?');">
                                                        <input type="hidden" name="delete_bouquet" value="1">
                                                        <input type="hidden" name="id" value="<?php echo $bq['id']; ?>">
                                                        <button type="submit" class="btn" style="padding: 0.35rem 0.6rem; font-size: 0.7rem; background-color: #f56565; color: var(--bg-white);">
                                                            Del
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; color: var(--text-muted); font-style: italic;">
                                            Inventory is empty.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
            </div>
        </main>
    </div>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</body>
</html>
