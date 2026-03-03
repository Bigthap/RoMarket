-- สร้างฐานข้อมูล
CREATE DATABASE IF NOT EXISTS roblox_market DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE roblox_market;

-- ตาราง users (ผู้ใช้งาน)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT 'default.png',
    bio TEXT,
    roblox_username VARCHAR(50),
    role ENUM('user','admin') DEFAULT 'user',
    is_banned TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ตาราง tags (แท็ก = ชื่อ Map/เกมใน Roblox)
CREATE TABLE IF NOT EXISTS tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL
) ENGINE=InnoDB;

-- ตาราง posts (โพสต์ซื้อ/ขาย/แลกเปลี่ยน)
CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    post_type ENUM('sell','buy','trade') DEFAULT 'sell',
    item_name VARCHAR(200),
    price VARCHAR(100),
    currency ENUM('THB','USD') DEFAULT 'THB',
    image VARCHAR(255),
    tag_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ตาราง comments (คอมเมนต์)
CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ตาราง likes (ไลค์)
CREATE TABLE IF NOT EXISTS likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (post_id, user_id),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ตาราง messages (ข้อความแชทส่วนตัว)
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ตาราง hall_of_fame (เกียรติยศ — Admin โพสต์เท่านั้น)
CREATE TABLE IF NOT EXISTS hall_of_fame (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    badge_label VARCHAR(50) DEFAULT 'Legend',
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ข้อมูลแท็ก Map เริ่มต้น
INSERT INTO tags (name, slug) VALUES
('Adopt Me!', 'adopt-me'),
('Blox Fruits', 'blox-fruits'),
('Brookhaven', 'brookhaven'),
('Pet Simulator X', 'pet-simulator-x'),
('Murder Mystery 2', 'murder-mystery-2'),
('Tower of Hell', 'tower-of-hell'),
('Royale High', 'royale-high'),
('MeepCity', 'meepcity'),
('Jailbreak', 'jailbreak'),
('Arsenal', 'arsenal');

-- สร้างบัญชี Admin เริ่มต้น (password: admin123)
INSERT INTO users (username, email, password, role, roblox_username) VALUES
('admin', 'admin@robloxmarket.com', 'admin123', 'admin', 'AdminRoblox');
