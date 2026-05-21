<?php
/**
 * Global Configuration File
 * Ninya Flower Shop - Portable XAMPP Configuration
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ninya_flower_shop');

// App Settings
define('SITE_NAME', 'Ninya Flower Shop');
define('SITE_SLOGAN', 'Premium Romantic Floral Boutique');
define('CURRENCY', '$');

// File Uploads
define('UPLOAD_DIR', __DIR__ . '/../assets/images/uploads/');
define('UPLOAD_URL', 'assets/images/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);

// Delivery Fees
define('STANDARD_DELIVERY_FEE', 15.00);
define('SAME_DAY_SURCHARGE', 25.00);
define('PICKUP_FEE', 0.00);

// Initialize directories if they do not exist
if (!is_dir(__DIR__ . '/../assets/images/uploads')) {
    mkdir(__DIR__ . '/../assets/images/uploads', 0755, true);
}
