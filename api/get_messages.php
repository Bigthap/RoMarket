<?php
require_once '../config.php';
require_once '../functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Please login']);
    exit;
}

$currentUserId = $_SESSION['user_id'];
$chatWithId = intval($_GET['user_id'] ?? 0);
$lastId = intval($_GET['last_id'] ?? 0);

if (!$chatWithId) {
    echo json_encode(['success' => false, 'error' => 'Missing user ID']);
    exit;
}

try {
    // ดึงเฉพาะข้อความที่ id มากกว่า lastId เรียงตามเวลาเก่าไปใหม่
    $stmt = $pdo->prepare("
        SELECT id, sender_id, receiver_id, message, created_at
        FROM messages 
        WHERE id > ? 
          AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
        ORDER BY created_at ASC
    ");
    $stmt->execute([$lastId, $currentUserId, $chatWithId, $chatWithId, $currentUserId]);
    $messages = $stmt->fetchAll();

    // อัปเดตสถานะการอ่านสำหรับข้อความใหม่ที่ผู้อื่นส่งมาให้เรา
    $unreadIds = [];
    foreach ($messages as $msg) {
        if ($msg['receiver_id'] == $currentUserId) {
            $unreadIds[] = $msg['id'];
        }
    }

    if (!empty($unreadIds)) {
        // ใช้ in เพราะเร็วและปลอดภัยกว่า
        $placeholders = str_repeat('?,', count($unreadIds) - 1) . '?';
        $updateStmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id IN ($placeholders)");
        $updateStmt->execute($unreadIds);
    }

    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
