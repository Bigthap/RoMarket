<?php
session_start();
date_default_timezone_set('Asia/Bangkok');

// ตั้งค่าฐานข้อมูล
define('DB_HOST', 'localhost');
define('DB_NAME', 'roblox_market');
define('DB_USER', 'root');
define('DB_PASS', '');

// เชื่อมต่อ MySQL ด้วย PDO
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("ไม่สามารถเชื่อมต่อฐานข้อมูลได้: " . $e->getMessage());
}

// ค่าคงที่
define('SITE_NAME', 'RoMarket');
define('SITE_URL', '/Final');
define('UPLOAD_PATH', __DIR__ . '/uploads');
