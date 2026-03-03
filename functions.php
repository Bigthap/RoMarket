<?php

// ตรวจว่าล็อกอินอยู่ไหม
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

// ตรวจว่าเป็น Admin ไหม
function isAdmin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// บังคับให้ต้องเป็น Admin
function requireAdmin()
{
    if (!isLoggedIn() || !isAdmin()) {
        redirect('index.php');
        exit;
    }
}

// บังคับให้ต้องล็อกอิน
function requireLogin()
{
    if (!isLoggedIn()) {
        redirect('login.php');
        exit;
    }
}

// เปลี่ยนหน้า
function redirect($url)
{
    header("Location: " . SITE_URL . "/$url");
    exit;
}

// ทำความสะอาด input
function sanitize($data)
{
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// แปลงเวลาเป็น "X นาทีที่แล้ว"
function timeAgo($datetime)
{
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0)
        return $diff->y . ' ปีที่แล้ว';
    if ($diff->m > 0)
        return $diff->m . ' เดือนที่แล้ว';
    if ($diff->d > 0)
        return $diff->d . ' วันที่แล้ว';
    if ($diff->h > 0)
        return $diff->h . ' ชั่วโมงที่แล้ว';
    if ($diff->i > 0)
        return $diff->i . ' นาทีที่แล้ว';
    return 'เมื่อสักครู่';
}

// นับจำนวนไลค์
function getLikeCount($pdo, $postId)
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
    $stmt->execute([$postId]);
    return $stmt->fetchColumn();
}

// ตรวจว่าไลค์แล้วหรือยัง
function isLiked($pdo, $postId, $userId)
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$postId, $userId]);
    return $stmt->fetchColumn() > 0;
}

// นับจำนวนคอมเมนต์
function getCommentCount($pdo, $postId)
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ?");
    $stmt->execute([$postId]);
    return $stmt->fetchColumn();
}

// ดึงข้อมูล user จาก id
function getUserById($pdo, $userId)
{
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

// ดึงแท็กทั้งหมด
function getAllTags($pdo)
{
    $stmt = $pdo->query("SELECT * FROM tags ORDER BY name ASC");
    return $stmt->fetchAll();
}

// แปลง post_type เป็นภาษาไทย
function getPostTypeLabel($type)
{
    $labels = [
        'sell' => 'ขาย',
        'buy' => 'ซื้อ',
        'trade' => 'แลก'
    ];
    return $labels[$type] ?? $type;
}

// แปลง post_type เป็น CSS class
function getPostTypeClass($type)
{
    $classes = [
        'sell' => 'badge-sell',
        'buy' => 'badge-buy',
        'trade' => 'badge-trade'
    ];
    return $classes[$type] ?? '';
}

// แสดงราคาพร้อมสกุลเงิน
function formatPrice($price, $currency = null)
{
    if (!$price)
        return '';
    if (!$currency)
        return sanitize($price);
    $symbols = ['THB' => '฿', 'USD' => '$'];
    $symbol = $symbols[$currency] ?? '';
    return $symbol . number_format(intval($price)) . ' ' . $currency;
}

// จัดการอัพโหลดรูป
function uploadImage($file, $folder = 'posts')
{
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    if (!in_array($file['type'], $allowedTypes)) {
        return null;
    }

    if ($file['size'] > $maxSize) {
        return null;
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $uploadDir = UPLOAD_PATH . '/' . $folder . '/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $destination = $uploadDir . $filename;
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return $filename;
    }

    return null;
}
