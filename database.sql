CREATE DATABASE IF NOT EXISTS lienzo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lienzo;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    email VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    credits INT DEFAULT 20,
    daily_count INT DEFAULT 0,
    daily_count_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(64) PRIMARY KEY,
    user_id INT NOT NULL,
    login_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    result_url TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Admin por defecto (password: Admin123)
-- Cambia el email y password antes de usar en producción
INSERT INTO users (name, email, password, role, credits)
VALUES ('Administrador', 'admin@lienzo.com', '$2y$10$ZJy.gZk4CxcYpg4gETuEVORtLcafIxXIEXXI37xCC4TWfsbDbgQRO', 'admin', 9999)
ON DUPLICATE KEY UPDATE credits = credits;
