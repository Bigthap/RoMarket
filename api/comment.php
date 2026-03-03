<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'กรุณาเข้าสู่ระบบ']);
    exit;
}

$postId = intval($_POST['post_id'] ?? 0);
$content = trim($_POST['content'] ?? '');

if (!$postId || empty($content)) {
    echo json_encode(['error' => 'กรุณากรอกข้อมูลให้ครบ']);
    exit;
}

// บันทึกคอมเมนต์
$stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
$stmt->execute([$postId, $_SESSION['user_id'], $content]);

$user = getUserById($pdo, $_SESSION['user_id']);

echo json_encode([
    'success' => true,
    'comment' => [
        'username' => $user['username'],
        'avatar' => $user['avatar'],
        'content' => sanitize($content),
        'user_id' => $user['id'],
        'time' => 'เมื่อสักครู่'
    ]
]);
