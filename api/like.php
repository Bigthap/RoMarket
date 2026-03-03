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

// เช็คว่าไลค์อยู่แล้วหรือยัง
$stmt = $pdo->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
$stmt->execute([$postId, $_SESSION['user_id']]);
$existing = $stmt->fetch();

if ($existing) {
    // ยกเลิกไลค์
    $stmt = $pdo->prepare("DELETE FROM likes WHERE id = ?");
    $stmt->execute([$existing['id']]);
    $liked = false;
} else {
    // กดไลค์
    $stmt = $pdo->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)");
    $stmt->execute([$postId, $_SESSION['user_id']]);
    $liked = true;
}

$count = getLikeCount($pdo, $postId);

echo json_encode(['liked' => $liked, 'count' => $count]);
