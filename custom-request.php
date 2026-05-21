<?php
/**
 * Ninya Flower Shop - Premium Romantic Floral Boutique
 * Custom Bouquet Request Page (Inspiration Photo Upload)
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';

// Force login to associate request with user
require_login();

log_analytics('page_view', ['page' => 'custom_request']);

$error_msg = '';
$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        $error_msg = 'Security token expired. Please try again.';
    } else {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $flowers = isset($_POST['flowers']) ? implode(', ', $_POST['flowers']) : 'Bespoke Mix';
        $color_theme = isset($_POST['color_theme']) ? $_POST['color_theme'] : 'Soft Neutral';
        $budget_range = isset($_POST['budget']) ? $_POST['budget'] : '$100 - $150';
        $delivery_date = $_POST['delivery_date'];
        $message = trim($_POST['message']);
        
        $image_path = null;
        
        // Handle file upload securely
        if (isset($_FILES['inspiration_image']) && $_FILES['inspiration_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['inspiration_image'];
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_all_extensions($file_ext)) {
                $error_msg = 'Invalid file type. Only JPG, PNG, and WEBP images are accepted.';
            } elseif ($file['size'] > MAX_FILE_SIZE) {
                $error_msg = 'File is too large. Maximum upload size is 5MB.';
            } else {
                // Generate randomized safe name
                $new_filename = bin2hex(random_bytes(10)) . '.' . $file_ext;
                $destination = UPLOAD_DIR . $new_filename;
                
                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $image_path = UPLOAD_URL . $new_filename;
                } else {
                    $error_msg = 'Failed to save uploaded inspiration image.';
                }
            }
        }
        
        if (empty($recipient_name) && empty($error_msg)) {
            // Write to database custom_requests
            try {
                $stmt = $pdo->prepare("INSERT INTO custom_requests (user_id, name, email, phone, flower_preferences, color_theme, budget_range, delivery_date, message, image_path, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
                $stmt->execute([
                    $_SESSION['user_id'],
                    $name,
                    $email,
                    $phone,
                    $flowers,
                    $color_theme,
                    $budget_range,
                    $delivery_date ? $delivery_date : null,
                    $message,
                    $image_path
                ]);
                
                log_analytics('custom_request_placed', ['user_id' => $_SESSION['user_id']]);
                
                $success_msg = 'Your bespoke request has been sent! Our lead design florist will review it shortly.';
            } catch (PDOException $e) {
                $error_msg = 'Failed to submit request: ' . $e->getMessage();
            }
        }
    }
}

// Check helper extension
function in_all_extensions($ext) {
    return in_array($ext, ALLOWED_EXTENSIONS);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bespoke Custom Bouquet Designer - Ninya Flower Shop</title>
    
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body style="background-color: var(--bg-warm);">

    <!-- Header Navigation -->
    <?php include __DIR__ . '/components/navbar.php'; ?>

    <div class="container" style="padding-top: 4rem; padding-bottom: 7rem;">
        
        <span class="section-subtitle">Your Imagination</span>
        <h1 style="font-family: var(--font-serif); font-size: 3.5rem; text-align: center; color: var(--text-charcoal); margin-bottom: 2rem;">
            Custom Bouquet request
        </h1>
        <p style="text-align: center; max-width: 600px; margin: 0 auto 4rem auto; color: var(--text-muted); font-size: 1rem; line-height: 1.7; font-weight: 300;">
            Can't find your ideal layout in our standard catalogue? Collaborate directly with our luxury designers to create a customized dream floral story.
        </p>

        <!-- Notifications -->
        <?php if ($error_msg): ?>
            <div class="alert alert-error" style="max-width: 800px; margin: 0 auto 3rem auto;"><?php echo e($error_msg); ?></div>
        <?php endif; ?>
        <?php if ($success_msg): ?>
            <div class="alert alert-success" style="max-width: 800px; margin: 0 auto 3rem auto;"><?php echo e($success_msg); ?></div>
        <?php endif; ?>

        <!-- Form layout -->
        <div style="background-color: var(--bg-white); border: var(--border-soft); max-width: 800px; margin: 0 auto; padding: 4rem; box-shadow: var(--shadow-soft);">
            
            <form action="custom-request.php" method="POST" enctype="multipart/form-data">
                <?php csrf_field(); ?>
                
                <input type="hidden" name="submit_request" value="1">
                
                <!-- 1. Customer metadata -->
                <div style="margin-bottom: 3rem;">
                    <h3 style="font-family: var(--font-serif); font-size: 1.6rem; color: var(--text-charcoal); border-bottom: 1px solid var(--champagne); padding-bottom: 0.8rem; margin-bottom: 1.5rem;">
                        1. Your Contact Details
                    </h3>
                    <div class="grid-3" style="gap: 1.5rem;">
                        <div class="form-group">
                            <label class="form-label" for="name">Your Name *</label>
                            <input type="text" id="name" name="name" required value="<?php echo e($_SESSION['user_name'] ?? ''); ?>" class="form-control" style="background-color: var(--bg-warm); border-color: rgba(143,168,155,0.15);">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="email">Email Address *</label>
                            <input type="email" id="email" name="email" required value="<?php echo e($_SESSION['user_email'] ?? ''); ?>" class="form-control" style="background-color: var(--bg-warm); border-color: rgba(143,168,155,0.15);">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="phone">Phone Number *</label>
                            <input type="text" id="phone" name="phone" required placeholder="e.g. +1 555-0199" class="form-control" style="background-color: var(--bg-warm); border-color: rgba(143,168,155,0.15);">
                        </div>
                    </div>
                </div>
                
                <!-- 2. Flower Preferences chips -->
                <div style="margin-bottom: 3rem;">
                    <h3 style="font-family: var(--font-serif); font-size: 1.6rem; color: var(--text-charcoal); border-bottom: 1px solid var(--champagne); padding-bottom: 0.8rem; margin-bottom: 1.5rem;">
                        2. Select Preferred Blooms
                    </h3>
                    <div class="request-chips-grid">
                        <input type="checkbox" id="fl_roses" name="flowers[]" value="Premium Roses" class="chip-input">
                        <label for="fl_roses" class="chip-label">Roses</label>
                        
                        <input type="checkbox" id="fl_peonies" name="flowers[]" value="White Peonies" class="chip-input">
                        <label for="fl_peonies" class="chip-label">Peonies</label>
                        
                        <input type="checkbox" id="fl_tulips" name="flowers[]" value="Pastel Tulips" class="chip-input">
                        <label for="fl_tulips" class="chip-label">Tulips</label>
                        
                        <input type="checkbox" id="fl_sunflowers" name="flowers[]" value="Gold Sunflowers" class="chip-input">
                        <label for="fl_sunflowers" class="chip-label">Sunflowers</label>
                        
                        <input type="checkbox" id="fl_baby" name="flowers[]" value="Baby's Breath" class="chip-input">
                        <label for="fl_baby" class="chip-label">Baby's Breath</label>
                        
                        <input type="checkbox" id="fl_euc" name="flowers[]" value="Eucalyptus foliage" class="chip-input">
                        <label for="fl_euc" class="chip-label">Eucalyptus</label>
                        
                        <input type="checkbox" id="fl_sage" name="flowers[]" value="Wild Sage" class="chip-input">
                        <label for="fl_sage" class="chip-label">Wild Sage</label>
                        
                        <input type="checkbox" id="fl_hydrangeas" name="flowers[]" value="Hydrangeas" class="chip-input">
                        <label for="fl_hydrangeas" class="chip-label">Hydrangeas</label>
                    </div>
                </div>
                
                <!-- 3. Color theme palette swatches -->
                <div style="margin-bottom: 3rem;">
                    <h3 style="font-family: var(--font-serif); font-size: 1.6rem; color: var(--text-charcoal); border-bottom: 1px solid var(--champagne); padding-bottom: 0.8rem; margin-bottom: 1.5rem;">
                        3. Choose Color Palette Theme
                    </h3>
                    
                    <div class="theme-swatches">
                        <!-- Blush Pink -->
                        <div>
                            <input type="radio" id="col_blush" name="color_theme" value="Blush Pink" checked class="chip-input">
                            <label for="col_blush" class="swatch-label">
                                <div class="swatch-color" style="background-color: #FDF6F6;"></div>
                                <span>Blush Pink</span>
                            </label>
                        </div>
                        
                        <!-- Champagne Beige -->
                        <div>
                            <input type="radio" id="col_champ" name="color_theme" value="Champagne Beige" class="chip-input">
                            <label for="col_champ" class="swatch-label">
                                <div class="swatch-color" style="background-color: #F3EFE9;"></div>
                                <span>Champagne Beige</span>
                            </label>
                        </div>
                        
                        <!-- Soft Sage -->
                        <div>
                            <input type="radio" id="col_sage" name="color_theme" value="Soft Sage" class="chip-input">
                            <label for="col_sage" class="swatch-label">
                                <div class="swatch-color" style="background-color: #D5E0D5;"></div>
                                <span>Soft Sage</span>
                            </label>
                        </div>
                        
                        <!-- Classic Romance -->
                        <div>
                            <input type="radio" id="col_rom" name="color_theme" value="Classic Romance" class="chip-input">
                            <label for="col_rom" class="swatch-label">
                                <div class="swatch-color" style="background-color: #A63F3F;"></div>
                                <span>Classic Romance</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- 4. Budget Range Slider & Delivery date -->
                <div style="margin-bottom: 3rem;">
                    <h3 style="font-family: var(--font-serif); font-size: 1.6rem; color: var(--text-charcoal); border-bottom: 1px solid var(--champagne); padding-bottom: 0.8rem; margin-bottom: 1.5rem;">
                        4. Budget & Schedule
                    </h3>
                    
                    <div class="grid-2" style="gap: 2.5rem;">
                        <!-- Budget -->
                        <div class="form-group">
                            <label class="form-label" for="budget">Bespoke Budget Range *</label>
                            <select id="budget" name="budget" required class="form-control" style="background-color: var(--bg-warm); border-color: rgba(143,168,155,0.15);">
                                <option value="Under $75">Under $75 (Casual Gift)</option>
                                <option value="$75 - $125" selected>$75 - $125 (Instagram Florist Choice)</option>
                                <option value="$125 - $200">$125 - $200 (Luxury arrangement)</option>
                                <option value="Over $200">Over $200 (Cinematic Grandeur)</option>
                            </select>
                        </div>
                        
                        <!-- Date -->
                        <div class="form-group">
                            <label class="form-label" for="delivery_date">Requested Delivery Date</label>
                            <input type="date" id="delivery_date" name="delivery_date" class="form-control" min="<?php echo date('Y-m-d'); ?>" style="background-color: var(--bg-warm); border-color: rgba(143,168,155,0.15);">
                        </div>
                    </div>
                </div>
                
                <!-- 5. Upload inspiration photo and details -->
                <div style="margin-bottom: 3rem;">
                    <h3 style="font-family: var(--font-serif); font-size: 1.6rem; color: var(--text-charcoal); border-bottom: 1px solid var(--champagne); padding-bottom: 0.8rem; margin-bottom: 1.5rem;">
                        5. Upload Inspiration Photo & Design Notes
                    </h3>
                    
                    <div class="form-group">
                        <label class="form-label" for="inspiration_image">Upload Inspiration Photo (MIME: JPG, PNG, WEBP max 5MB)</label>
                        <input type="file" id="inspiration_image" name="inspiration_image" class="form-control" style="border: 1px dashed rgba(143,168,155,0.4); padding: 1.5rem; background-color: var(--bg-warm);">
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 2rem;">
                        <label class="form-label" for="message">Design specifications or custom message card notes:</label>
                        <textarea id="message" name="message" placeholder="State specific ribbons, special height requirements, box sizes, or warm card greetings to be included in our luxury dispatch envelope..." class="form-control" style="background-color: var(--bg-warm); border-color: rgba(143,168,155,0.15); min-height: 120px;"></textarea>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary" style="display: block; width: 100%; padding: 1.2rem; font-size: 0.8rem; letter-spacing: 0.15em;">
                    Submit Bespoke Request
                </button>
            </form>
            
        </div>
        
    </div>

    <!-- Footer Component -->
    <?php include __DIR__ . '/components/footer.php'; ?>

</body>
</html>
