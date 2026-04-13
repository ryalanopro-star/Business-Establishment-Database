CREATE DATABASE IF NOT EXISTS pc_peripherals_db;
USE pc_peripherals_db;

-- CUSTOMERS
CREATE TABLE customers (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    contact_number VARCHAR(20),
    email VARCHAR(100)
);

INSERT INTO customers (full_name, contact_number, email) VALUES
('Juan Dela Cruz', '09171234567', 'juan@email.com'),
('Maria Santos', '09281234567', 'maria@email.com'),
('Pedro Reyes', '09391234567', 'pedro@email.com'),
('Ana Garcia', '09451234567', 'ana@email.com'),
('Carlos Mendoza', '09561234567', 'carlos@email.com');

-- PRODUCTS
CREATE TABLE products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(100) NOT NULL,
    category VARCHAR(50),
    price DECIMAL(10,2),
    stock_quantity INT
);

INSERT INTO products (product_name, category, price, stock_quantity) VALUES
('VXE Dragonfly R1 Wireless', 'Mouse', 990.00, 4),
('Logitech G304', 'Mouse', 1200.00, 6),
('Aula F75 Mechanical Keyboard', 'Keyboard', 1800.00, 3),
('Razer BlackShark V2', 'Headset', 2500.00, 5),
('HyperX XL', 'Mousepad', 300.00, 10);

-- ORDERS
CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    order_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(50),
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
);

INSERT INTO orders (customer_id, status) VALUES
(1, 'Pending'),
(2, 'Completed'),
(3, 'Cancelled'),
(4, 'Pending'),
(5, 'Completed');

-- ORDER ITEMS
CREATE TABLE order_items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    product_id INT,
    quantity INT,
    subtotal DECIMAL(10,2),
    FOREIGN KEY (order_id) REFERENCES orders(order_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

INSERT INTO order_items (order_id, product_id, quantity, subtotal) VALUES
(1, 1, 2, 1980.00),
(2, 2, 1, 1200.00),
(3, 3, 1, 1800.00),
(4, 4, 1, 2500.00),
(5, 5, 3, 900.00);

-- PAYMENTS
CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNIQUE,
    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    payment_method VARCHAR(50),
    total_amount DECIMAL(10,2),
    FOREIGN KEY (order_id) REFERENCES orders(order_id)
);

INSERT INTO payments (order_id, payment_method, total_amount) VALUES
(2, 'Cash', 1200.00),
(5, 'GCash', 900.00);