-- KI6CR Ham Radio Kit Inventory Management System
-- Database Schema
-- 
-- IMPORTANT: Select your database in phpMyAdmin BEFORE importing this file
-- This will create tables in whatever database you have selected

-- Projects/Kits table
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_name VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('active', 'archived', 'planning') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Parts master list
CREATE TABLE IF NOT EXISTS parts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    part_number VARCHAR(255) NOT NULL,
    part_name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    current_stock INT DEFAULT 0,
    min_stock_level INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_part_number (part_number),
    INDEX idx_category (category),
    INDEX idx_stock (current_stock)
) ENGINE=InnoDB;

-- Parts sources and pricing
CREATE TABLE IF NOT EXISTS part_sources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    part_id INT NOT NULL,
    supplier_name VARCHAR(255) NOT NULL,
    supplier_part_number VARCHAR(255),
    cost DECIMAL(10, 2) NOT NULL,
    url TEXT,
    is_preferred BOOLEAN DEFAULT FALSE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (part_id) REFERENCES parts(id) ON DELETE CASCADE,
    INDEX idx_part_id (part_id)
) ENGINE=InnoDB;

-- Bill of Materials - parts required for each project
-- variation_attribute/variation_value: empty string = fixed/shared part;
--   non-empty = variable part that only applies to one attribute option
--   (e.g. variation_attribute='Connector', variation_value='Male pigtail adapter')
CREATE TABLE IF NOT EXISTS project_parts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    part_id INT NOT NULL,
    quantity_required INT NOT NULL DEFAULT 1,
    notes TEXT,
    variation_attribute VARCHAR(100) NOT NULL DEFAULT '',
    variation_value VARCHAR(255) NOT NULL DEFAULT '',
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (part_id) REFERENCES parts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_project_part (project_id, part_id, variation_attribute, variation_value),
    INDEX idx_project_id (project_id)
) ENGINE=InnoDB;

-- Maps each combination of attribute values to a WooCommerce variation product ID.
-- combo_key format: "AttributeA:ValueA|AttributeB:ValueB" (attributes sorted alphabetically)
CREATE TABLE IF NOT EXISTS project_variation_mappings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    combo_key VARCHAR(500) NOT NULL,
    wc_variation_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_project_combo (project_id, combo_key)
) ENGINE=InnoDB;

-- Inventory purchases/check-ins
CREATE TABLE IF NOT EXISTS inventory_checkins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    part_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_cost DECIMAL(10, 2) NOT NULL,
    total_cost DECIMAL(10, 2) NOT NULL,
    supplier_name VARCHAR(255),
    purchase_date DATE NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (part_id) REFERENCES parts(id) ON DELETE CASCADE,
    INDEX idx_part_id (part_id),
    INDEX idx_purchase_date (purchase_date)
) ENGINE=InnoDB;

-- Customer orders
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(100) NOT NULL UNIQUE,
    project_id INT NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    customer_email VARCHAR(255),
    customer_phone VARCHAR(50),
    customer_callsign VARCHAR(50),
    quantity INT NOT NULL DEFAULT 1,
    price_paid DECIMAL(10, 2) NOT NULL,
    order_date DATE NOT NULL,
    status ENUM('pending', 'paid', 'shipped', 'completed', 'cancelled') DEFAULT 'pending',
    shipping_address TEXT,
    notes TEXT,
    variation_combo_key VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id),
    INDEX idx_order_number (order_number),
    INDEX idx_customer_name (customer_name),
    INDEX idx_order_date (order_date),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- System user (simple authentication for you)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
) ENGINE=InnoDB;

-- Insert default admin user (password: admin123 - CHANGE THIS IMMEDIATELY AFTER FIRST LOGIN!)
INSERT INTO users (username, password_hash, email) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ki6cr@example.com');

-- Create some sample data for testing
INSERT INTO projects (project_name, description, status) VALUES
('40m CW QRP Transceiver', 'Classic 40 meter band CW transceiver kit with 5W output', 'active'),
('Antenna Tuner Kit', 'Manual L-network antenna tuner for HF bands', 'active'),
('Morse Code Keyer', 'Iambic keyer with memory and adjustable speed', 'planning');

INSERT INTO parts (part_number, part_name, description, category, current_stock, min_stock_level) VALUES
('R-1K-1/4W', '1K Ohm Resistor 1/4W', 'Carbon film resistor 1/4 watt 5%', 'Resistors', 100, 50),
('C-100uF-16V', '100uF Electrolytic Capacitor', '16V radial electrolytic capacitor', 'Capacitors', 50, 25),
('IC-NE602', 'NE602 Mixer IC', 'Double-balanced mixer and oscillator', 'ICs', 10, 5),
('XTAL-7.040MHz', '7.040MHz Crystal', 'HC-49 crystal for 40m band', 'Crystals', 20, 10),
('PCB-QRP-TX-v1', 'QRP Transceiver PCB v1', 'Custom PCB for 40m transceiver', 'PCBs', 15, 5);

INSERT INTO part_sources (part_id, supplier_name, supplier_part_number, cost, url, is_preferred) VALUES
(1, 'Mouser Electronics', '71-CCF071K00FKE36', 0.10, 'https://www.mouser.com/...', TRUE),
(1, 'Digi-Key', 'CF14JT1K00CT-ND', 0.12, 'https://www.digikey.com/...', FALSE),
(2, 'Mouser Electronics', '647-UVR1C101MED', 0.35, 'https://www.mouser.com/...', TRUE),
(3, 'eBay Various', 'NE602AN', 2.50, 'https://www.ebay.com/...', TRUE),
(4, 'Mouser Electronics', 'ECS-70.4-S', 1.25, 'https://www.mouser.com/...', TRUE);

INSERT INTO project_parts (project_id, part_id, quantity_required) VALUES
(1, 1, 20),
(1, 2, 5),
(1, 3, 2),
(1, 4, 1),
(1, 5, 1);
