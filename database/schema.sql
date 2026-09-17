CREATE DATABASE IF NOT EXISTS computemart_php CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE computemart_php;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NULL,           -- NULL for Google-only accounts
    google_id VARCHAR(190) NULL UNIQUE,
    role ENUM('buyer','seller','admin') NOT NULL DEFAULT 'buyer',
    store_name VARCHAR(150) NULL,              -- only meaningful for sellers
    is_locked TINYINT(1) NOT NULL DEFAULT 0,   -- admin-disabled account
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL,
    category_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    image_url VARCHAR(500) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,   -- soft-delete flag
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id),
    FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB;

CREATE TABLE cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    buyer_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_buyer_product (buyer_id, product_id),
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    buyer_id INT NOT NULL,
    order_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending','paid','cancelled','completed') NOT NULL DEFAULT 'pending',
    total_amount DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (buyer_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    seller_id INT NOT NULL,        -- denormalized for fast seller-side lookups
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,  -- price snapshot at purchase time
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (seller_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL UNIQUE,
    amount DECIMAL(10,2) NOT NULL,
    method VARCHAR(50) NOT NULL DEFAULT 'eSewa',
    transaction_uuid VARCHAR(100) NOT NULL UNIQUE,
    esewa_ref_id VARCHAR(100) NULL,
    status ENUM('pending','success','failed') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    verified_at DATETIME NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Seed data --------------------------------------------------------------

INSERT INTO categories (name, description) VALUES
    ('CPU', 'Processors'),
    ('RAM', 'Memory modules'),
    ('GPU', 'Graphics cards'),
    ('Motherboard', NULL),
    ('Storage', 'SSDs and HDDs'),
    ('PSU', 'Power supplies'),
    ('Cooling', NULL),
    ('Peripherals', NULL);

-- Password is Admin@12345 (bcrypt hash, generated with PHP's password_hash)
INSERT INTO users (full_name, email, password_hash, role) VALUES
    ('Site Admin', 'admin@computemart.local', '$2y$10$FhX7ssM8fhV40vsXQxfsLeJ.5vf2fz8oqGvdUe7QHm8jrLwEYhjs6', 'admin');

-- Password is Seller@12345
INSERT INTO users (full_name, email, password_hash, role, store_name) VALUES
    ('Demo Seller', 'seller@computemart.local', '$2y$10$n9aLFDHhTajx6pxgaS.IBOegFmfR3Kklhr2sFc8VNOGjKQ7QjxZxi', 'seller', 'Kathmandu PC Parts');

INSERT INTO products (seller_id, category_id, name, description, price, stock) VALUES
    ((SELECT id FROM users WHERE email = 'seller@computemart.local'), (SELECT id FROM categories WHERE name = 'CPU'), 'AMD Ryzen 5 7600', '6-core, 12-thread desktop CPU', 28000, 10),
    ((SELECT id FROM users WHERE email = 'seller@computemart.local'), (SELECT id FROM categories WHERE name = 'RAM'), 'Corsair Vengeance 16GB DDR5', '16GB (2x8GB) 5600MHz', 6500, 25),
    ((SELECT id FROM users WHERE email = 'seller@computemart.local'), (SELECT id FROM categories WHERE name = 'GPU'), 'NVIDIA RTX 4060', '8GB GDDR6 graphics card', 52000, 5);
