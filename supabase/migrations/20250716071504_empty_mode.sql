/*
# SoftZone Database Schema

Complete database structure for the SoftZone platform including:

1. New Tables
   - `users` - User accounts with roles and balance
   - `products` - Software products catalog
   - `product_keys` - License keys management
   - `sales` - Purchase transactions
   - `transactions` - Financial transaction history
   - `reseller_applications` - Reseller application system
   - `system_settings` - Platform configuration
   - `activity_logs` - User activity tracking

2. Security
   - All tables properly indexed for performance
   - Foreign key constraints for data integrity
   - Default values and proper data types

3. Sample Data
   - Admin user account
   - Product catalog with gaming software
   - System configuration settings
*/

-- Create database
CREATE DATABASE IF NOT EXISTS softzone_db;
USE softzone_db;

-- Tabela de usuários
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    phone VARCHAR(20),
    avatar VARCHAR(255) DEFAULT 'default-avatar.png',
    role ENUM('user', 'admin') DEFAULT 'user',
    balance DECIMAL(10,2) DEFAULT 0.00,
    total_spent DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('active', 'suspended', 'banned') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status)
);

-- Tabela de produtos/softwares
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    category VARCHAR(50),
    status ENUM('active', 'inactive', 'development') DEFAULT 'active',
    features TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_category (category),
    INDEX idx_price (price)
);

-- Tabela de keys/licenças
CREATE TABLE IF NOT EXISTS product_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    key_value VARCHAR(255) UNIQUE NOT NULL,
    duration_days INT DEFAULT 30,
    status ENUM('available', 'sold', 'expired', 'revoked') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id),
    INDEX idx_status (status),
    INDEX idx_key_value (key_value)
);

-- Tabela de vendas
CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    product_id INT,
    key_id INT,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50),
    payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    transaction_id VARCHAR(100),
    expires_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (key_id) REFERENCES product_keys(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_product_id (product_id),
    INDEX idx_payment_status (payment_status),
    INDEX idx_created_at (created_at)
);

-- Tabela de transações financeiras
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    type ENUM('deposit', 'purchase', 'refund', 'bonus') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT,
    reference_id INT,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_type (type),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- Tabela de aplicações de reseller
CREATE TABLE IF NOT EXISTS reseller_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source VARCHAR(255),
    experience TEXT,
    software VARCHAR(255),
    plan VARCHAR(255),
    phone VARCHAR(50),
    email VARCHAR(100),
    discord_id VARCHAR(100),
    discord_invite VARCHAR(255),
    locations TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_email (email),
    INDEX idx_created_at (created_at)
);

-- Tabela de configurações do sistema
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
);

-- Tabela de logs de atividades
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
);

-- Inserir usuário admin padrão (senha: password)
INSERT INTO users (username, email, password, full_name, role, balance) VALUES 
('admin', 'admin@softzone.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin', 1000.00);

-- Inserir produtos padrão
INSERT INTO products (name, description, price, image, category, status) VALUES 
('Free Fire', 'Hack completo para Free Fire com aimbot, wallhack e muito mais', 15.00, 'FREE FIRE.PNG', 'Mobile', 'active'),
('Valorant', 'Cheat premium para Valorant com recursos avançados', 25.00, 'VALORANT.PNG', 'PC', 'active'),
('FiveM', 'Menu completo para servidores FiveM', 20.00, 'FIVEM.PNG', 'PC', 'active'),
('Fortnite', 'Hack seguro para Fortnite com anti-ban', 30.00, 'FORTI.PNG', 'PC', 'development'),
('Warzone', 'Cheat premium para Call of Duty Warzone', 35.00, 'cod.png', 'PC', 'development'),
('CS2', 'Hack completo para Counter-Strike 2', 28.00, 'CS2.PNG', 'PC', 'development');

-- Inserir algumas keys de exemplo
INSERT INTO product_keys (product_id, key_value, status) VALUES 
(1, 'SK-FF-' + UPPER(SUBSTRING(MD5(RAND()), 1, 12)), 'available'),
(1, 'SK-FF-' + UPPER(SUBSTRING(MD5(RAND()), 1, 12)), 'available'),
(2, 'SK-VAL-' + UPPER(SUBSTRING(MD5(RAND()), 1, 12)), 'available'),
(2, 'SK-VAL-' + UPPER(SUBSTRING(MD5(RAND()), 1, 12)), 'available'),
(3, 'SK-FM-' + UPPER(SUBSTRING(MD5(RAND()), 1, 12)), 'available');

-- Inserir configurações padrão
INSERT INTO system_settings (setting_key, setting_value, description) VALUES 
('site_name', 'SoftZone', 'Nome do site'),
('maintenance_mode', '0', 'Modo de manutenção (0=off, 1=on)'),
('registration_enabled', '1', 'Registro habilitado (0=off, 1=on)'),
('min_deposit', '10.00', 'Valor mínimo de depósito'),
('reseller_bonus', '26.667', 'Porcentagem de bônus para revendedores'),
('reseller_discount', '70', 'Porcentagem de desconto para revendedores'),
('pix_key', 'admin@softzone.com', 'Chave PIX para pagamentos'),
('support_email', 'support@softzone.com', 'Email de suporte'),
('discord_invite', 'https://discord.gg/vBDbaF9UA5', 'Link do Discord');