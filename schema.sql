-- =============================================
-- Schema Database Chatbot Keuangan WhatsApp
-- =============================================

-- Buat database (jalankan jika belum ada)
-- CREATE DATABASE IF NOT EXISTS keuangan_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE keuangan_db;

-- Tabel untuk kategori pengeluaran
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    is_custom TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel untuk pengeluaran
CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Index untuk mempercepat query berdasarkan tanggal
CREATE INDEX idx_expenses_created_at ON expenses(created_at);

-- Tabel untuk pending expenses (konfirmasi sebelum save)
CREATE TABLE IF NOT EXISTS pending_expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(20) NOT NULL,
    expenses_data JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    INDEX idx_pending_phone (phone),
    INDEX idx_pending_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert kategori default
INSERT INTO categories (name, is_custom) VALUES 
('makan', 0),
('transport', 0),
('belanja', 0),
('hiburan', 0),
('tagihan', 0),
('lainnya', 0)
ON DUPLICATE KEY UPDATE name = name;

CREATE TABLE IF NOT EXISTS pending_expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(20) NOT NULL,
    expenses_data JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    INDEX idx_pending_phone (phone),
    INDEX idx_pending_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;