CREATE TABLE IF NOT EXISTS businesses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    google_location VARCHAR(255) NOT NULL,
    google_access_token TEXT NOT NULL,
    gemini_api_key VARCHAR(255) NOT NULL,
    gemini_model VARCHAR(255) DEFAULT 'models/gemini-1.0-pro',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS review_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id INT UNSIGNED NOT NULL,
    google_review_name VARCHAR(255) NOT NULL,
    reviewer_name VARCHAR(255) DEFAULT NULL,
    rating TINYINT DEFAULT NULL,
    comment TEXT,
    review_update_time DATETIME DEFAULT NULL,
    reply_text TEXT DEFAULT NULL,
    reply_source VARCHAR(32) DEFAULT NULL,
    replied_at DATETIME DEFAULT NULL,
    raw_review LONGTEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_review_name (google_review_name),
    KEY idx_review_business (business_id),
    CONSTRAINT fk_review_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
