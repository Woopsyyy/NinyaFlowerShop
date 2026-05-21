<?php
/**
 * Ninya Flower Shop - Premium Romantic Floral Boutique
 * Shopping Cart Page
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/session.php';

log_analytics('page_view', ['page' => 'cart']);

// Handle Cart Actions (POST Updates)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Update quantities
    if (isset($_POST['update_cart'])) {
        $quantities = isset($_POST['qty']) ? $_POST['qty'] : [];
        
        foreach ($quantities as $id => $qty) {
            $qty = (int)$qty;
            if ($qty < 1) $qty = 1;
            
            if (is_logged_in()) {
                $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$qty, $id, $_SESSION['user_id']]);
            } else {
                if (isset($_SESSION['cart'][$id])) {
                    $_SESSION['cart'][$id] = $qty;
                }
            }
        }
        set_flash('success', 'Your shopping bag was successfully updated.');
        header("Location: cart.php");
        exit;
    }
    
    // 2. Remove single item
    if (isset($_POST['remove_item'])) {
        $item_id = (int)$_POST['item_id'];
        
        if (is_logged_in()) {
            $stmt = $pdo->prepare("DELETE FROM cart_items WHERE id = ? AND user_id = ?");
            $stmt->execute([$item_id, $_SESSION['user_id']]);
        } else {
            if (isset($_SESSION['cart'][$item_id])) {
                unset($_SESSION['cart'][$item_id]);
            }
        }
        set_flash('success', 'Item removed from your shopping bag.');
        header("Location: cart.php");
        exit;
    }
}

// Load Cart items
$cart_items = [];
$subtotal = 0;

try {
    if (is_logged_in()) {
        $stmt = $pdo->prepare("SELECT ci.*, b.name, b.price, b.sale_price, b.image_url, b.slug, b.stock 
                               FROM cart_items ci 
                               JOIN bouquets b ON ci.bouquet_id = b.id 
                               WHERE ci.user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $db_items = $stmt->fetchAll();
        
        foreach ($db_items as $item) {
            $effective_price = $item['sale_price'] !== null ? $item['sale_price'] : $item['price'];
            $total_item_price = $effective_price * $item['quantity'];
            $subtotal += $total_item_price;
            
            $cart_items[] = [
                'id' => $item['id'],
                'bouquet_id' => $item['bouquet_id'],
                'name' => $item['name'],
                'price' => $effective_price,
                'image_url' => $item['image_url'],
                'slug' => $item['slug'],
                'quantity' => $item['quantity'],
                'stock' => $item['stock'],
                'total_price' => $total_item_price
            ];
        }
    } else {
        // Guest session lookup
        if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $bq_id => $quantity) {
                $stmt = $pdo->prepare("SELECT * FROM bouquets WHERE id = ? LIMIT 1");
                $stmt->execute([$bq_id]);
                $bq = $stmt->fetch();
                
                if ($bq) {
                    $effective_price = $bq['sale_price'] !== null ? $bq['sale_price'] : $bq['price'];
                    $total_item_price = $effective_price * $quantity;
                    $subtotal += $total_item_price;
                    
                    $cart_items[] = [
                        'id' => $bq['id'], // use bouquet id directly for reference in guest mode
                        'bouquet_id' => $bq['id'],
                        'name' => $bq['name'],
                        'price' => $effective_price,
                        'image_url' => $bq['image_url'],
                        'slug' => $bq['slug'],
                        'quantity' => $quantity,
                        'stock' => $bq['stock'],
                        'total_price' => $total_item_price
                    ];
                }
            }
        }
    }
} catch (PDOException $e) {
    $cart_items = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Bag - Ninya Flower Shop</title>
    
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body style="background-color: var(--bg-warm);">

    <!-- Header Navigation -->
    <?php include __DIR__ . '/components/navbar.php'; ?>

    <div class="container" style="padding-top: 4rem; padding-bottom: 7rem;">
        
        <span class="section-subtitle">Your Selections</span>
        <h1 style="font-family: var(--font-serif); font-size: 3.5rem; text-align: center; color: var(--text-charcoal); margin-bottom: 4rem;">
            Shopping Bag
        </h1>
        
        <!-- Alerts -->
        <?php if ($success = get_flash('success')): ?>
            <div class="alert alert-success" style="max-width: 900px; margin: 0 auto 3rem auto;"><?php echo e($success); ?></div>
        <?php endif; ?>

        <?php if (!empty($cart_items)): ?>
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 4rem; align-items: start;">
                
                <!-- Left: Items list table -->
                <form action="cart.php" method="POST">
                    <div style="background-color: var(--bg-white); border: var(--border-soft); padding: 2rem; box-shadow: var(--shadow-soft);">
                        
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem; text-align: left;">
                            <thead>
                                <tr style="border-bottom: 2px solid var(--champagne); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.1em; color: var(--text-muted);">
                                    <th style="padding-bottom: 1.2rem;">Bouquet Details</th>
                                    <th style="padding-bottom: 1.2rem; text-align: center;">Price</th>
                                    <th style="padding-bottom: 1.2rem; text-align: center;">Qty</th>
                                    <th style="padding-bottom: 1.2rem; text-align: right;">Total</th>
                                    <th style="padding-bottom: 1.2rem; text-align: center;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart_items as $item): ?>
                                    <tr style="border-bottom: 1px solid var(--champagne);">
                                        <!-- Details -->
                                        <td style="padding: 1.5rem 0; display: flex; align-items: center; gap: 1.5rem;">
                                            <a href="bouquet.php?slug=<?php echo $item['slug']; ?>" style="width: 70px; height: 90px; overflow: hidden; background-color: var(--champagne); display: block;">
                                                <img src="<?php echo e($item['image_url']); ?>" alt="<?php echo e($item['name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                            </a>
                                            <div>
                                                <h4 style="font-family: var(--font-serif); font-size: 1.25rem; color: var(--text-charcoal); margin-bottom: 0.3rem;">
                                                    <a href="bouquet.php?slug=<?php echo $item['slug']; ?>"><?php echo e($item['name']); ?></a>
                                                </h4>
                                                <span style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--sage-green);">Hand-tied Bouquet</span>
                                            </div>
                                        </td>
                                        
                                        <!-- Price -->
                                        <td style="padding: 1.5rem 0; text-align: center; color: var(--text-charcoal);">
                                            <?php echo format_price($item['price']); ?>
                                        </td>
                                        
                                        <!-- Qty modifier -->
                                        <td style="padding: 1.5rem 0; text-align: center;">
                                            <div style="display: inline-flex; border: 1px solid rgba(143,168,155,0.15); background-color: var(--bg-warm); align-items: center;">
                                                <button type="button" onclick="const q = document.getElementById('qty_<?php echo $item['id']; ?>'); if (q.value > 1) q.value--;" style="background:transparent; border:none; padding: 0.4rem 0.8rem; cursor:pointer;"><i class="fas fa-minus" style="font-size:0.65rem;"></i></button>
                                                
                                                <input type="number" id="qty_<?php echo $item['id']; ?>" name="qty[<?php echo $item['id']; ?>]" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock']; ?>" readonly style="border:none; text-align:center; width:36px; font-weight:600; font-size:0.85rem; background:transparent;">
                                                
                                                <button type="button" onclick="const q = document.getElementById('qty_<?php echo $item['id']; ?>'); if (q.value < <?php echo $item['stock']; ?>) q.value++;" style="background:transparent; border:none; padding: 0.4rem 0.8rem; cursor:pointer;"><i class="fas fa-plus" style="font-size:0.65rem;"></i></button>
                                            </div>
                                        </td>
                                        
                                        <!-- Line Total -->
                                        <td style="padding: 1.5rem 0; text-align: right; font-weight: 500; color: var(--text-charcoal);">
                                            <?php echo format_price($item['total_price']); ?>
                                        </td>
                                        
                                        <!-- Remove button -->
                                        <td style="padding: 1.5rem 0; text-align: center;">
                                            <button type="submit" name="remove_item" onclick="document.getElementById('remove_item_id').value = '<?php echo $item['id']; ?>';" style="background: transparent; border: none; cursor: pointer; color: var(--text-muted); opacity: 0.6; transition: var(--transition-fast);" onmouseover="this.style.opacity='1'; this.style.color='#e74c3c';" onmouseout="this.style.opacity='0.6'; this.style.color='var(--text-muted)';">
                                                <i class="far fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <!-- Hidden inputs for single remove -->
                        <input type="hidden" name="item_id" id="remove_item_id" value="">
                        
                        <!-- Update Cart actions row -->
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 2rem;">
                            <a href="shop.php" class="btn btn-secondary" style="font-size: 0.75rem; padding: 0.7rem 1.8rem;">
                                Continue Browsing
                            </a>
                            <button type="submit" name="update_cart" class="btn btn-primary" style="font-size: 0.75rem; padding: 0.7rem 1.8rem; background-color: var(--sage-green);">
                                Update Cart Quantities
                            </button>
                        </div>
                        
                    </div>
                </form>
                
                <!-- Right: Summary Sidebar panel -->
                <div style="background-color: var(--bg-white); border: var(--border-soft); padding: 2.5rem; box-shadow: var(--shadow-soft);">
                    <h3 style="font-family: var(--font-serif); font-size: 1.6rem; color: var(--text-charcoal); margin-bottom: 2rem;">
                        Summary
                    </h3>
                    
                    <div style="display: flex; flex-direction: column; gap: 1.2rem; font-size: 0.9rem; border-bottom: 1px solid var(--champagne); padding-bottom: 2rem;">
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-muted);">Bouquet Subtotal:</span>
                            <span style="font-weight: 500; color: var(--text-charcoal);"><?php echo format_price($subtotal); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-muted);">Delivery dispatch fee:</span>
                            <span style="font-weight: 500; color: var(--text-charcoal);"><?php echo format_price(STANDARD_DELIVERY_FEE); ?></span>
                        </div>
                        <p style="font-size: 0.75rem; color: var(--sage-green); font-style: italic; line-height: 1.4;">
                            *Final delivery surcharges based on same-day parameters or pick-up methods will adjust during checkout.
                        </p>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 2rem 0; font-size: 1.2rem; color: var(--text-charcoal); font-weight: 600;">
                        <span>Estimated Total:</span>
                        <span style="color: var(--gold); font-size: 1.4rem; font-family: var(--font-sans);"><?php echo format_price($subtotal + STANDARD_DELIVERY_FEE); ?></span>
                    </div>
                    
                    <a href="checkout.php" class="btn btn-primary" style="display: block; width: 100%; padding: 1.1rem; letter-spacing: 0.15em; font-size: 0.8rem; margin-top: 1rem;">
                        Proceed To Checkout
                    </a>
                </div>
                
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 6rem 0; max-width: 500px; margin: 0 auto;">
                <i class="fas fa-shopping-bag" style="font-size: 4rem; color: var(--accent-blush); margin-bottom: 2rem;"></i>
                <h3 style="font-family: var(--font-serif); font-size: 2rem; color: var(--text-charcoal); margin-bottom: 0.8rem;">Your Bag is Empty</h3>
                <p style="color: var(--text-muted); line-height: 1.7; font-weight: 300; margin-bottom: 2.5rem;">
                    You have not selected any gorgeous hand-tied flower arrangements yet. Explore our Pinterest catalog to gather beautiful bouquets.
                </p>
                <a href="shop.php" class="btn btn-primary">Explore Stems</a>
            </div>
        <?php endif; ?>
        
    </div>

    <!-- Footer Component -->
    <?php include __DIR__ . '/components/footer.php'; ?>

</body>
</html>
