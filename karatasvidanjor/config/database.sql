-- Karataş Vidanjör Web Sitesi Veritabanı
-- Veritabanı: karatasvidanjor

CREATE DATABASE IF NOT EXISTS karatasvidanjor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE karatasvidanjor;

-- İletişim mesajları tablosu
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('new', 'read', 'replied') DEFAULT 'new',
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Site ayarları tablosu (opsiyonel)
CREATE TABLE IF NOT EXISTS site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Varsayılan ayarları ekle
INSERT INTO site_settings (setting_key, setting_value) VALUES
('site_title', 'Karatas Vidanjör - Profesyonel Vidanjör Hizmetleri'),
('site_description', 'Bursa ve çevresinde vidanjör, tıkalı gider açma, kanalizasyon ve sanitasyon hizmetleri. 7/24 profesyonel hizmet.'),
('contact_email', 'karatasvidanjor@gmail.com'),
('contact_phone', '+90 538 366 03 25'),
('company_address', 'Bağlarbaşı Mahallesi 1. Ömer Oğlu Sokak No:2 Osmangazi/Bursa')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);