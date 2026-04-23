-- Mobile Shop E-commerce Database Schema
-- Run this file in phpMyAdmin or MySQL CLI to set up the database

CREATE DATABASE IF NOT EXISTS mobile_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mobile_shop;

-- =============================================
-- USERS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================
-- CATEGORIES TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================
-- PRODUCTS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT DEFAULT NULL,
    name VARCHAR(200) NOT NULL,
    brand VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    discount_price DECIMAL(10,2) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    image VARCHAR(255) DEFAULT 'default.png',
    stock INT DEFAULT 0,
    featured TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =============================================
-- ORDERS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    shipping_name VARCHAR(100) NOT NULL,
    shipping_address TEXT NOT NULL,
    shipping_phone VARCHAR(20) NOT NULL,
    payment_method ENUM('cod', 'online') DEFAULT 'cod',
    status ENUM('pending', 'processing', 'delivered', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- ORDER ITEMS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- CART TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (user_id, product_id)
) ENGINE=InnoDB;

-- =============================================
-- SEED DATA
-- =============================================

-- Admin user (password: admin123) — regenerate hash with: password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO users (name, email, password, role) VALUES
('Admin', 'admin@mobileshop.com', '$2y$10$DyuVro8WsIyrbY8bFcpsbeSZggmagzsvrXHzkpGdd6PV.GNRfZspu', 'admin');

-- Sample user (password: user123)
INSERT INTO users (name, email, password, phone, address, role) VALUES
('John Doe', 'john@example.com', '$2y$10$wxiBmrkn7LXMA9IK97XqXOrDcTiHlkEEpUgXeOENp9P./Rglrp0H.', '01712345678', '123 Main St, Dhaka', 'user');

-- Categories
INSERT INTO categories (name, description) VALUES
('Smartphones', 'Latest smartphones from top brands'),
('Tablets', 'Tablets and iPads for work and play'),
('Accessories', 'Mobile accessories and gadgets'),
('Chargers & Cables', 'Fast chargers and premium cables'),
('Cases & Covers', 'Protective cases and stylish covers');

-- Products
INSERT INTO products (category_id, name, brand, price, description, image, stock, featured) VALUES
(1, 'Samsung Galaxy S24 Ultra', 'Samsung', 1299.99, 'The ultimate Galaxy experience with S Pen, 200MP camera, and titanium frame. Features a stunning 6.8" Dynamic AMOLED display with 120Hz refresh rate.', 'samsung-s24-ultra.jpg', 25, 1),
(1, 'iPhone 15 Pro Max', 'Apple', 1199.99, 'Forged in titanium. Featuring the A17 Pro chip, a customizable Action button, and the most powerful iPhone camera system ever.', 'iphone-15-pro-max.jpg', 30, 1),
(1, 'Google Pixel 8 Pro', 'Google', 999.99, 'The best of Google with the Tensor G3 chip, advanced AI photo editing, and 7 years of OS updates.', 'pixel-8-pro.jpg', 20, 1),
(1, 'OnePlus 12', 'OnePlus', 799.99, 'Flagship killer with Snapdragon 8 Gen 3, 100W SUPERVOOC charging, and Hasselblad camera system.', 'oneplus-12.jpg', 15, 1),
(1, 'Xiaomi 14 Pro', 'Xiaomi', 699.99, 'Premium flagship with Leica optics, Snapdragon 8 Gen 3, and 120W HyperCharge technology.', 'xiaomi-14-pro.jpg', 18, 0),
(1, 'Samsung Galaxy A55', 'Samsung', 449.99, 'Mid-range champion with Super AMOLED display, 50MP camera, and 5000mAh battery. Great value for money.', 'samsung-a55.jpg', 40, 0),
(2, 'iPad Pro M4', 'Apple', 1099.99, 'The thinnest Apple product ever with M4 chip, Ultra Retina XDR display, and Apple Pencil Pro support.', 'ipad-pro-m4.jpg', 12, 1),
(2, 'Samsung Galaxy Tab S9', 'Samsung', 849.99, 'Premium Android tablet with Dynamic AMOLED 2X display, Snapdragon 8 Gen 2, and S Pen included.', 'galaxy-tab-s9.jpg', 10, 0),
(3, 'AirPods Pro 2', 'Apple', 249.99, 'Rebuilt from the sound up with H2 chip, Adaptive Audio, and USB-C charging case.', 'airpods-pro-2.jpg', 50, 1),
(3, 'Samsung Galaxy Watch 6', 'Samsung', 329.99, 'Advanced health monitoring with BioActive Sensor, sapphire crystal glass, and Wear OS.', 'galaxy-watch-6.jpg', 22, 0),
(4, 'Anker 65W GaN Charger', 'Anker', 45.99, 'Ultra-compact GaN charger with 3 ports, PowerIQ 3.0, and foldable plug design.', 'anker-65w.jpg', 100, 0),
(4, 'Apple 20W USB-C Adapter', 'Apple', 19.99, 'Official Apple fast charger compatible with iPhone, iPad, and AirPods.', 'apple-20w.jpg', 80, 0),
(5, 'Spigen Ultra Hybrid Case', 'Spigen', 15.99, 'Crystal clear protection with Air Cushion Technology for iPhone 15 Pro Max.', 'spigen-case.jpg', 200, 0),
(5, 'OtterBox Defender Series', 'OtterBox', 49.99, 'Multi-layer defense with port covers and holster for ultimate phone protection.', 'otterbox-defender.jpg', 60, 0);
