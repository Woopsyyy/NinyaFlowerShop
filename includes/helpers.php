<?php
/**
 * Helper Utilities & Standard Formatting Rules
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session.php';

/**
 * Clean XSS protection output
 * @param string $val
 * @return string
 */
function e($val) {
    return htmlspecialchars($val ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Format price to currency
 * @param float $price
 * @return string
 */
function format_price($price) {
    return CURRENCY . number_format((float)$price, 2);
}

/**
 * Generate a unique tracking number
 * Format: NY-YYYY-RAND
 * @return string
 */
function generate_tracking_number() {
    return 'NY-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

/**
 * Get active user's cart count
 * @return int
 */
function get_cart_count() {
    global $pdo;
    if (is_logged_in()) {
        $stmt = $pdo->prepare("SELECT SUM(quantity) as cnt FROM cart_items WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $res = $stmt->fetch();
        return (int)($res['cnt'] ?? 0);
    } else {
        // Fallback to session cart for guests
        $count = 0;
        if (isset($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $qty) {
                $count += $qty;
            }
        }
        return $count;
    }
}

/**
 * Get active user's wishlist count
 * @return int
 */
function get_wishlist_count() {
    global $pdo;
    if (is_logged_in()) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM wishlist_items WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $res = $stmt->fetch();
        return (int)($res['cnt'] ?? 0);
    }
    return 0;
}

/**
 * Truncate long descriptions
 * @param string $text
 * @param int $chars
 * @return string
 */
function truncate($text, $chars = 100) {
    if (strlen($text) <= $chars) return $text;
    $text = substr($text, 0, $chars);
    $text = substr($text, 0, strrpos($text, ' '));
    return $text . '...';
}

/**
 * Get current system-wide categories
 * @return array
 */
function get_categories() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
    return $stmt->fetchAll();
}

/**
 * Insert Analytics tracking log
 * @param string $type
 * @param array $data
 */
function log_analytics($type, $data = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO analytics (event_type, event_data) VALUES (?, ?)");
        $stmt->execute([$type, json_encode($data)]);
    } catch (Exception $e) {
        // Suppress analytics writing errors to avoid breaking core flows
    }
}
