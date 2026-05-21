-- Master Database Schema for Ninya Flower Shop
-- Highly XAMPP & phpMyAdmin compatible. Uses InnoDB with full foreign key constraints.

CREATE DATABASE IF NOT EXISTS ninya_flower_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ninya_flower_shop;

-- Disable foreign key checks momentarily to rebuild safely if needed
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Users table (Customers and Admins combined role option, but keeping admins separate as requested)
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NULL,
    role ENUM('customer', 'admin') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Admins table
DROP TABLE IF EXISTS admins;
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    role VARCHAR(50) DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Categories table
DROP TABLE IF EXISTS categories;
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Bouquets table
DROP TABLE IF EXISTS bouquets;
CREATE TABLE bouquets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(150) NOT NULL UNIQUE,
    description TEXT NOT NULL,
    meaning TEXT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    sale_price DECIMAL(10, 2) DEFAULT NULL,
    stock INT DEFAULT 0,
    image_url VARCHAR(255) NOT NULL,
    is_featured TINYINT(1) DEFAULT 0,
    is_best_seller TINYINT(1) DEFAULT 0,
    is_wedding TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Bouquet Images table (For multi-image galleries)
DROP TABLE IF EXISTS bouquet_images;
CREATE TABLE bouquet_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bouquet_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (bouquet_id) REFERENCES bouquets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Cart Items table
DROP TABLE IF EXISTS cart_items;
CREATE TABLE cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    bouquet_id INT NOT NULL,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (bouquet_id) REFERENCES bouquets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Wishlist Items table
DROP TABLE IF EXISTS wishlist_items;
CREATE TABLE wishlist_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    bouquet_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (bouquet_id) REFERENCES bouquets(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wish (user_id, bouquet_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Orders table
DROP TABLE IF EXISTS orders;
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    tracking_number VARCHAR(50) NOT NULL UNIQUE,
    total_amount DECIMAL(10, 2) NOT NULL,
    payment_method VARCHAR(50) DEFAULT 'Cash on Delivery',
    status ENUM('Pending', 'Confirmed', 'Preparing Bouquet', 'Out for Delivery', 'Delivered', 'Cancelled') DEFAULT 'Pending',
    delivery_type ENUM('Same Day', 'Scheduled', 'Pickup') DEFAULT 'Same Day',
    delivery_date DATE NOT NULL,
    delivery_time_slot VARCHAR(100) NOT NULL,
    recipient_name VARCHAR(100) NOT NULL,
    recipient_phone VARCHAR(20) NOT NULL,
    delivery_address TEXT NOT NULL,
    card_message TEXT NULL,
    note TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Order Items table
DROP TABLE IF EXISTS order_items;
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    bouquet_id INT NULL,
    price DECIMAL(10, 2) NOT NULL,
    quantity INT DEFAULT 1,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (bouquet_id) REFERENCES bouquets(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Delivery Tracking Logs table
DROP TABLE IF EXISTS delivery_tracking;
CREATE TABLE delivery_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    status VARCHAR(100) NOT NULL,
    description TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Reviews table
DROP TABLE IF EXISTS reviews;
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    bouquet_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT NULL,
    is_approved TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (bouquet_id) REFERENCES bouquets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Testimonials table
DROP TABLE IF EXISTS testimonials;
CREATE TABLE testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    role VARCHAR(100) NULL,
    quote TEXT NOT NULL,
    avatar_url VARCHAR(255) NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Custom Requests table
DROP TABLE IF EXISTS custom_requests;
CREATE TABLE custom_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    flower_preferences TEXT NULL,
    color_theme VARCHAR(100) NULL,
    budget_range VARCHAR(100) NULL,
    delivery_date DATE NULL,
    message TEXT NULL,
    image_path VARCHAR(255) NULL,
    status ENUM('Pending', 'Reviewed', 'Approved', 'Declined') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Saved Addresses table
DROP TABLE IF EXISTS addresses;
CREATE TABLE addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255) NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NULL,
    postal_code VARCHAR(20) NOT NULL,
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Analytics Event Log table
DROP TABLE IF EXISTS analytics;
CREATE TABLE analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(100) NOT NULL,
    event_data TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Enable foreign key checks back
SET FOREIGN_KEY_CHECKS = 1;

-- ==================================================
-- INITIAL SEED DATA
-- ==================================================

-- Seed Default Admins
-- Password for admin is 'admin123' (hashed using BCRYPT)
INSERT INTO admins (username, password, email, role) VALUES
('admin', '$2y$10$w3e8q56yE.e8r.YmK/U0be7rYdI2iZlKqW8v.Yc5fKpx1hPZJ6d9q', 'admin@ninyaflowers.com', 'Super Admin');

-- Seed Default Categories
INSERT INTO categories (name, slug, description) VALUES
('Rose Bouquets', 'rose-bouquets', 'Timeless expressions of deep love, handcrafted with premium long-stemmed roses.'),
('Tulips', 'tulips', 'Elegant pastel blooms signaling fresh beginnings and delicate appreciation.'),
('Sunflower Bouquets', 'sunflowers', 'Bright radiant sunflower creations crafted to bring golden joy and warmth.'),
('Wedding Flowers', 'wedding-flowers', 'Whimsical bespoke arrangements and hand-tied bouquets for your dream romantic union.'),
('Dried Flowers', 'dried-flowers', 'Everlasting rustic dried botanicals in neutral and calming configurations.'),
('Flower Boxes', 'flower-boxes', 'Sleek luxury suede boxes overflowing with perfectly arranged seasonal blooms.'),
('Gift Bundles', 'gift-bundles', 'Exquisite flower couplings featuring curated botanical cards, candles, and cards.'),
('Chocolates + Flowers', 'chocolates-flowers', 'Decadent artisanal French chocolates paired beautifully with premium romantic floristry.');

-- Seed Default Bouquets
INSERT INTO bouquets (category_id, name, slug, description, meaning, price, sale_price, stock, image_url, is_featured, is_best_seller, is_wedding) VALUES
(1, 'Amour de Rose', 'amour-de-rose', 'A luxurious romantic bundle of premium blush pink and deep red roses, hand-wrapped in champagne linen paper and satin ribbons. Perfect for profound romantic expressions.', 'Roses are the eternal signature of passionate romance, representing deep affection, timeless loyalty, and pure visual luxury.', 125.00, 110.00, 15, 'assets/images/amour_rose.png', 1, 1, 0),
(2, 'Whispering Tulips', 'whispering-tulips', 'Delicate pastel pink and white tulips nestled in clean, rustic cotton linen paper. Signifies soft new beginnings and gentle affection.', 'Tulips signify perfect love and fresh beginnings, conveying a message of calm appreciation and pure romantic trust.', 85.00, NULL, 20, 'assets/images/whispering_tulips.png', 1, 0, 0),
(3, 'Golden Solstice', 'golden-solstice', 'Vibrant, hand-picked sunflowers paired beautifully with white baby\'s breath and refreshing sage green eucalyptus leaves. Wrapped in warm earth craft paper.', 'Sunflowers represent loyalty, adoration, and standard warmth, bringing radiant sunlight and joy to your recipient\'s heart.', 70.00, 65.00, 10, 'assets/images/golden_solstice.png', 0, 1, 0),
(4, 'Elysian Meadow', 'elysian-meadow', 'An exquisite, whimsical wedding arrangement featuring gorgeous white peonies, soft ranunculus, and wild sage leaves. Bound with delicate sage silk.', 'White peonies carry deep symbols of happy marriage, honor, prosperity, and the elegant meeting of two romantic paths.', 180.00, NULL, 8, 'assets/images/elysian_meadow.png', 0, 0, 1);

-- Seed Testimonials
INSERT INTO testimonials (name, role, quote, avatar_url, is_active) VALUES
('Isabella Thorne', 'Bride', 'Ninya designed our wedding arches and my hand bouquet. The Peonies and Sage Greens felt like an ethereal fairy tale. Instantly visual and romantic!', 'assets/images/testimonial_1.jpg', 1),
('Julian Sterling', 'Anniversary Gift', 'The presentation was breathtaking. My wife gasped when the La Parisienne Box was delivered. Premium craftsmanship at its absolute finest.', 'assets/images/testimonial_2.jpg', 1),
('Sophia Moretti', 'Lifestyle Blogger', 'I recommend Ninya Flower Shop to all my followers. Their Pinterest-style arrangements are purely cinematic and their delivery is flawlessly timed.', 'assets/images/testimonial_3.svg', 1);

