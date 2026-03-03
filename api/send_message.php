<?php
require_once '../config.php';
require_once '../functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Please login']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$senderId = $_SESSION['user_id'];
$receiverId = intval($_POST['receiver_id'] ?? 0);
$message = trim($_POST['message'] ?? '');

if (!$receiverId || empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit;
}

// เช็คว่า receiver มีอยู่จริง
$stmtCheck = $pdo->prepare("SELECT id FROM users WHERE id = ?");
$stmtCheck->execute([$receiverId]);
if (!$stmtCheck->fetchColumn()) {
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$senderId, $receiverId, $message]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
