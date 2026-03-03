<?php
$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';
requireAdmin();

// สถิติ
$userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$postCount = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$commentCount = $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();
$bannedCount = $pdo->query("SELECT COUNT(*) FROM users WHERE is_banned = 1")->fetchColumn();

require_once __DIR__ . '/../header.php';
?>

<div class="admin-header">
    <h1 class="admin-title"><i class="fas fa-shield-alt"></i> Admin Dashboard</h1>
    <a href="<?= SITE_URL ?>/" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> กลับหน้าฟีด</a>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <h3>
                <?= $userCount ?>
            </h3>
            <p>ผู้ใช้ทั้งหมด</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon cyan"><i class="fas fa-clipboard-list"></i></div>
        <div class="stat-info">
            <h3>
                <?= $postCount ?>
            </h3>
            <p>โพสต์ทั้งหมด</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-comments"></i></div>
        <div class="stat-info">
            <h3>
                <?= $commentCount ?>
            </h3>
            <p>คอมเมนต์ทั้งหมด</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon pink"><i class="fas fa-ban"></i></div>
        <div class="stat-info">
            <h3>
                <?= $bannedCount ?>
            </h3>
            <p>ถูกแบน</p>
        </div>
    </div>
</div>

<div class="admin-links">
    <a href="<?= SITE_URL ?>/admin/manage_users.php" class="admin-link-card">
        <i class="fas fa-users-cog"></i>
        <span>จัดการผู้ใช้</span>
    </a>
    <a href="<?= SITE_URL ?>/admin/manage_posts.php" class="admin-link-card">
        <i class="fas fa-clipboard-list"></i>
        <span>จัดการโพสต์</span>
    </a>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>