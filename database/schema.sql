-- Chocolife database schema
DROP DATABASE IF EXISTS chocolife;
CREATE DATABASE chocolife CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE chocolife;

-- 1. Users
CREATE TABLE users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    first_name    VARCHAR(50)  NOT NULL,
    last_name     VARCHAR(50)  NOT NULL,
    email         VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone         VARCHAR(20)  NULL,
    role          ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Categories
CREATE TABLE categories (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- 3. Partners (companies that provide offers)
CREATE TABLE partners (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    name    VARCHAR(100) NOT NULL,
    city    VARCHAR(50)  NOT NULL DEFAULT 'Almaty',
    address VARCHAR(200) NULL,
    phone   VARCHAR(20)  NULL
) ENGINE=InnoDB;

-- 4. Products (offers)
CREATE TABLE products (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    product_number VARCHAR(20)  NOT NULL UNIQUE,
    name           VARCHAR(150) NOT NULL,
    description    TEXT NULL,
    price          DECIMAL(10,2) NOT NULL,
    old_price      DECIMAL(10,2) NULL,
    stock          INT NOT NULL DEFAULT 100,
    status         ENUM('Available', 'Unavailable') NOT NULL DEFAULT 'Available',
    image          VARCHAR(255) NULL,
    valid_until    DATE NULL,
    category_id    INT NOT NULL,
    partner_id     INT NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT chk_products_price CHECK (price >= 0),
    CONSTRAINT chk_products_old_price CHECK (old_price IS NULL OR old_price >= price),
    CONSTRAINT chk_products_stock CHECK (stock >= 0),

    CONSTRAINT fk_products_category FOREIGN KEY (category_id)
        REFERENCES categories(id) ON DELETE RESTRICT,
    CONSTRAINT fk_products_partner FOREIGN KEY (partner_id)
        REFERENCES partners(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 5. Cart items
CREATE TABLE cart_items (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    product_id INT NOT NULL,
    quantity   INT NOT NULL DEFAULT 1,
    added_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT chk_cart_quantity CHECK (quantity BETWEEN 1 AND 99),
    CONSTRAINT uq_cart_user_product UNIQUE (user_id, product_id),

    CONSTRAINT fk_cart_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_cart_product FOREIGN KEY (product_id)
        REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. Orders
CREATE TABLE orders (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    order_number  VARCHAR(30)  NOT NULL UNIQUE,
    user_id       INT NULL,
    customer_name VARCHAR(100) NOT NULL,
    email         VARCHAR(100) NOT NULL,
    phone         VARCHAR(20)  NOT NULL,
    address       VARCHAR(200) NOT NULL,
    total         DECIMAL(10,2) NOT NULL,
    status        ENUM('Pending', 'Completed', 'Declined') NOT NULL DEFAULT 'Pending',
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT chk_orders_total CHECK (total >= 0),

    CONSTRAINT fk_orders_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 7. Order items (name and price are copied at the moment of purchase)
CREATE TABLE order_items (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    order_id     INT NOT NULL,
    product_id   INT NULL,
    product_name VARCHAR(150) NOT NULL,
    price        DECIMAL(10,2) NOT NULL,
    quantity     INT NOT NULL,

    CONSTRAINT chk_order_items_quantity CHECK (quantity BETWEEN 1 AND 99),
    CONSTRAINT chk_order_items_price CHECK (price >= 0),

    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id)
        REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product FOREIGN KEY (product_id)
        REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 8. Reviews
CREATE TABLE reviews (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    product_id INT NOT NULL,
    rating     TINYINT NOT NULL,
    comment    TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5),
    CONSTRAINT uq_reviews_user_product UNIQUE (user_id, product_id),

    CONSTRAINT fk_reviews_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_product FOREIGN KEY (product_id)
        REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 9. Favorites
CREATE TABLE favorites (
    user_id    INT NOT NULL,
    product_id INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (user_id, product_id),

    CONSTRAINT fk_favorites_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_favorites_product FOREIGN KEY (product_id)
        REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Indexes for frequent filters and reports
CREATE INDEX idx_products_status   ON products(status);
CREATE INDEX idx_orders_created_at ON orders(created_at);
CREATE INDEX idx_orders_status     ON orders(status);
