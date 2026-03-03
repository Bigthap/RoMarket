<?php
$pageTitle = 'จัดการผู้ใช้';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';
requireAdmin();

// จัดการ actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $targetId = intval($_POST['user_id'] ?? 0);

    if ($targetId && $targetId != $_SESSION['user_id']) {
        switch ($action) {
            case 'ban':
                $stmt = $pdo->prepare("UPDATE users SET is_banned = 1 WHERE id = ?");
                $stmt->execute([$targetId]);
                break;
            case 'unban':
                $stmt = $pdo->prepare("UPDATE users SET is_banned = 0 WHERE id = ?");
                $stmt->execute([$targetId]);
                break;
            case 'delete':
                // ลบ avatar (ถ้าไม่ใช่ default)
                $user = getUserById($pdo, $targetId);
                if ($user && $user['avatar'] !== 'default.png') {
                    $avatarPath = UPLOAD_PATH . '/avatars/' . $user['avatar'];
                    if (file_exists($avatarPath))
                        unlink($avatarPath);
                }
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$targetId]);
                break;
        }
    }
    header("Location: " . SITE_URL . "/admin/manage_users.php");
    exit;
}

// ค้นหา
$search = trim($_GET['search'] ?? '');
$where = '';
$params = [];

if ($search) {
    $where = "WHERE username LIKE ? OR email LIKE ? OR roblox_username LIKE ?";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm];
}

$stmt = $pdo->prepare("SELECT * FROM users $where ORDER BY created_at DESC");
$stmt->execute($params);
$users = $stmt->fetchAll();

require_once __DIR__ . '/../header.php';
?>

<div class="admin-header">
    <h1 class="admin-title"><i class="fas fa-users-cog"></i> จัดการผู้ใช้</h1>
    <a href="<?= SITE_URL ?>/admin/dashboard.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> กลับ
        Dashboard</a>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h3 class="admin-card-title"><i class="fas fa-list"></i> ผู้ใช้ทั้งหมด (
            <?= count($users) ?>)
        </h3>
        <form class="admin-search" method="GET">
            <input type="text" name="search" placeholder="ค้นหา Username / Email..." value="<?= sanitize($search) ?>">
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
        </form>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Roblox</th>
                    <th>Role</th>
                    <th>สถานะ</th>
                    <th>วันสมัคร</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <?= $u['id'] ?>
                        </td>
                        <td>
                            <a href="<?= SITE_URL ?>/profile.php?id=<?= $u['id'] ?>"
                                style="color:var(--text-primary);font-weight:600;">
                                <?= sanitize($u['username']) ?>
                            </a>
                        </td>
                        <td style="color:var(--text-secondary);">
                            <?= sanitize($u['email']) ?>
                        </td>
                        <td style="color:var(--accent-cyan);">
                            <?= sanitize($u['roblox_username'] ?: '-') ?>
                        </td>
                        <td>
                            <?php if ($u['role'] === 'admin'): ?>
                                <span class="badge badge-admin">Admin</span>
                            <?php else: ?>
                                <span class="badge"
                                    style="background:rgba(139,92,246,0.1);color:var(--accent-purple);border:1px solid rgba(139,92,246,0.2);">User</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($u['is_banned']): ?>
                                <span class="badge badge-banned"><i class="fas fa-ban"></i> แบน</span>
                            <?php else: ?>
                                <span class="badge"
                                    style="background:rgba(16,185,129,0.1);color:var(--accent-green);border:1px solid rgba(16,185,129,0.2);">ปกติ</span>
                            <?php endif; ?>
                        </td>
                        <td style="color:var(--text-muted);font-size:0.8rem;">
                            <?= date('d/m/Y', strtotime($u['created_at'])) ?>
                        </td>
                        <td>
                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                <div class="admin-actions">
                                    <?php if ($u['is_banned']): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <input type="hidden" name="action" value="unban">
                                            <button type="submit" class="admin-btn admin-btn-unban"><i class="fas fa-check"></i>
                                                ปลดแบน</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <input type="hidden" name="action" value="ban">
                                            <button type="submit" class="admin-btn admin-btn-ban"><i class="fas fa-ban"></i>
                                                แบน</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" style="display:inline;"
                                        onsubmit="return confirm('ต้องการลบผู้ใช้ <?= sanitize($u['username']) ?> จริงหรือไม่? โพสต์และคอมเมนต์ทั้งหมดจะถูกลบด้วย')">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="admin-btn admin-btn-delete"><i class="fas fa-trash"></i>
                                            ลบ</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <span style="color:var(--text-muted);font-size:0.8rem;">คุณ</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>