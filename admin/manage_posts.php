<?php
$pageTitle = 'จัดการโพสต์';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';
requireAdmin();

// ลบโพสต์
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $postId = intval($_POST['post_id'] ?? 0);
    if ($postId) {
        $stmt = $pdo->prepare("SELECT image FROM posts WHERE id = ?");
        $stmt->execute([$postId]);
        $post = $stmt->fetch();
        if ($post && $post['image']) {
            $imagePath = UPLOAD_PATH . '/posts/' . $post['image'];
            if (file_exists($imagePath))
                unlink($imagePath);
        }
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->execute([$postId]);
    }
    header("Location: " . SITE_URL . "/admin/manage_posts.php");
    exit;
}

// กรอง
$tagFilter = $_GET['tag'] ?? '';
$typeFilter = $_GET['type'] ?? '';

$where = [];
$params = [];

if ($tagFilter) {
    $where[] = "t.slug = ?";
    $params[] = $tagFilter;
}
if ($typeFilter && in_array($typeFilter, ['sell', 'buy', 'trade'])) {
    $where[] = "p.post_type = ?";
    $params[] = $typeFilter;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT p.*, u.username, t.name AS tag_name, t.slug AS tag_slug
        FROM posts p
        JOIN users u ON p.user_id = u.id
        LEFT JOIN tags t ON p.tag_id = t.id
        $whereSQL
        ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();

$allTags = getAllTags($pdo);

require_once __DIR__ . '/../header.php';
?>

<div class="admin-header">
    <h1 class="admin-title"><i class="fas fa-clipboard-list"></i> จัดการโพสต์</h1>
    <a href="<?= SITE_URL ?>/admin/dashboard.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> กลับ
        Dashboard</a>
</div>

<!-- Filter -->
<div class="filter-bar">
    <a href="<?= SITE_URL ?>/admin/manage_posts.php" class="filter-btn <?= !$typeFilter ? 'active' : '' ?>">ทั้งหมด (
        <?= count($posts) ?>)
    </a>
    <a href="<?= SITE_URL ?>/admin/manage_posts.php?type=sell"
        class="filter-btn <?= $typeFilter === 'sell' ? 'active' : '' ?>">🟢 ขาย</a>
    <a href="<?= SITE_URL ?>/admin/manage_posts.php?type=buy"
        class="filter-btn <?= $typeFilter === 'buy' ? 'active' : '' ?>">🔵 ซื้อ</a>
    <a href="<?= SITE_URL ?>/admin/manage_posts.php?type=trade"
        class="filter-btn <?= $typeFilter === 'trade' ? 'active' : '' ?>">🟣 แลก</a>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h3 class="admin-card-title"><i class="fas fa-list"></i> โพสต์ทั้งหมด</h3>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>หัวข้อ</th>
                    <th>ผู้โพสต์</th>
                    <th>ประเภท</th>
                    <th>Map</th>
                    <th>วันที่</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($posts)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center;color:var(--text-muted);padding:2rem;">ไม่มีโพสต์</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($posts as $p): ?>
                        <tr>
                            <td>
                                <?= $p['id'] ?>
                            </td>
                            <td>
                                <a href="<?= SITE_URL ?>/post_detail.php?id=<?= $p['id'] ?>"
                                    style="color:var(--text-primary);font-weight:600;">
                                    <?= sanitize(mb_substr($p['title'], 0, 40)) ?>
                                    <?= mb_strlen($p['title']) > 40 ? '...' : '' ?>
                                </a>
                            </td>
                            <td>
                                <a href="<?= SITE_URL ?>/profile.php?id=<?= $p['user_id'] ?>" style="color:var(--accent-cyan);">
                                    <?= sanitize($p['username']) ?>
                                </a>
                            </td>
                            <td><span class="badge <?= getPostTypeClass($p['post_type']) ?>">
                                    <?= getPostTypeLabel($p['post_type']) ?>
                                </span></td>
                            <td style="color:var(--text-secondary);">
                                <?= sanitize($p['tag_name'] ?: '-') ?>
                            </td>
                            <td style="color:var(--text-muted);font-size:0.8rem;">
                                <?= date('d/m/Y H:i', strtotime($p['created_at'])) ?>
                            </td>
                            <td>
                                <div class="admin-actions">
                                    <a href="<?= SITE_URL ?>/post_detail.php?id=<?= $p['id'] ?>"
                                        class="admin-btn admin-btn-view"><i class="fas fa-eye"></i> ดู</a>
                                    <form method="POST" style="display:inline;"
                                        onsubmit="return confirm('ต้องการลบโพสต์นี้จริงหรือไม่?')">
                                        <input type="hidden" name="post_id" value="<?= $p['id'] ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="admin-btn admin-btn-delete"><i class="fas fa-trash"></i>
                                            ลบ</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>