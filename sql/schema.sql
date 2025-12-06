CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(120) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    plan VARCHAR(20) DEFAULT 'basic',
    created_at DATETIME NOT NULL
);

CREATE TABLE google_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    access_token TEXT NOT NULL,
    refresh_token TEXT,
    expires_in INT DEFAULT 3600,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    account_id VARCHAR(120) NOT NULL,
    location_id VARCHAR(120) NOT NULL,
    business_name VARCHAR(255) NOT NULL,
    address VARCHAR(255) NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    location_id INT NOT NULL,
    review_id VARCHAR(120) UNIQUE NOT NULL,
    reviewer_name VARCHAR(255) NOT NULL,
    star_rating INT DEFAULT 0,
    comment TEXT,
    review_time DATETIME,
    ai_reply_status VARCHAR(20) DEFAULT 'pending',
    ai_reply_text TEXT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE
);

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    provider VARCHAR(20) NOT NULL,
    subscription_status VARCHAR(30) DEFAULT 'inactive',
    subscription_id VARCHAR(120),
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME NOT NULL
);
