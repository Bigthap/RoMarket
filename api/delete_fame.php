<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['error' => 'ไม่มีสิทธิ์']);
    exit;
}

$fameId = intval($_POST['fame_id'] ?? 0);
if (!$fameId) {
    echo json_encode(['error' => 'ไม่พบรายการ']);
    exit;
}

// ลบรูป
$stmt = $pdo->prepare("SELECT image FROM hall_of_fame WHERE id = ?");
$stmt->execute([$fameId]);
$fame = $stmt->fetch();
if ($fame && $fame['image']) {
    $path = UPLOAD_PATH . '/posts/' . $fame['image'];
    if (file_exists($path))
        unlink($path);
}

$stmt = $pdo->prepare("DELETE FROM hall_of_fame WHERE id = ?");
$stmt->execute([$fameId]);

echo json_encode(['success' => true]);
