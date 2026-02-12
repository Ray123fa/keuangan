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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel untuk admin interface (Google OAuth)
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    google_sub VARCHAR(64) NOT NULL UNIQUE,
    email VARCHAR(191) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    avatar_url VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel untuk pengeluaran
CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    amount BIGINT UNSIGNED NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Jika tabel expenses sudah ada dengan DECIMAL, jalankan migrasi ini sekali:
-- ALTER TABLE expenses MODIFY amount BIGINT UNSIGNED NOT NULL;

-- Index untuk mempercepat query berdasarkan tanggal
CREATE INDEX idx_expenses_created_at ON expenses(created_at);
CREATE INDEX idx_expenses_category_created_at ON expenses(category_id, created_at);

-- Insert kategori default
INSERT INTO categories (name) VALUES 
('makan'),
('transport'),
('belanja'),
('hiburan'),
('tagihan'),
('lainnya')
ON DUPLICATE KEY UPDATE name = name;
