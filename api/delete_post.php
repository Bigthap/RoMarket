<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'กรุณาเข้าสู่ระบบ']);
    exit;
}

$postId = intval($_POST['post_id'] ?? 0);
if (!$postId) {
    echo json_encode(['error' => 'ไม่พบโพสต์']);
    exit;
}

// ดึงข้อมูลโพสต์
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$postId]);
$post = $stmt->fetch();

if (!$post) {
    echo json_encode(['error' => 'ไม่พบโพสต์']);
    exit;
}

// ตรวจสิทธิ์: เจ้าของ หรือ Admin
if ($post['user_id'] != $_SESSION['user_id'] && !isAdmin()) {
    echo json_encode(['error' => 'คุณไม่มีสิทธิ์ลบโพสต์นี้']);
    exit;
}

// ลบรูปภาพ (ถ้ามี)
if ($post['image']) {
    $imagePath = UPLOAD_PATH . '/posts/' . $post['image'];
    if (file_exists($imagePath)) {
        unlink($imagePath);
    }
}

// ลบโพสต์ (คอมเมนต์+ไลค์จะถูกลบตาม CASCADE)
$stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
$stmt->execute([$postId]);

echo json_encode(['success' => true]);
