<?php
$pageTitle = 'Hall of Fame';
require_once 'config.php';
require_once 'functions.php';

// ดึงโพสต์ Hall of Fame ทั้งหมด
$stmt = $pdo->query("SELECT hf.*, u.username, u.avatar
                      FROM hall_of_fame hf
                      JOIN users u ON hf.user_id = u.id
                      ORDER BY hf.created_at DESC");
$fame_posts = $stmt->fetchAll();

require_once 'header.php';
?>

<div class="fame-page">
    <div class="fame-header-section">
        <div class="fame-icon"><i class="fas fa-trophy"></i></div>
        <h1 class="fame-main-title">Hall of Fame</h1>
        <p class="fame-subtitle">รวมผู้สร้างเว็ประดับตำนาน ผู้สร้างเว็ปRoMarket</p>
        <?php if (isAdmin()): ?>
            <a href="<?= SITE_URL ?>/create_fame.php" class="btn btn-primary" style="margin-top:1rem;">
                <i class="fas fa-plus"></i> เพิ่ม Hall of Fame
            </a>
        <?php endif; ?>
    </div>

    <div class="fame-grid">
        <?php if (empty($fame_posts)): ?>
            <div class="empty-state" style="grid-column:1/-1;">
                <i class="fas fa-trophy"></i>
                <h3>ยังไม่มีรายชื่อใน Hall of Fame</h3>
                <p>เร็วๆ นี้ Admin จะประกาศตำนานคนแรก!</p>
            </div>
        <?php else: ?>
            <?php foreach ($fame_posts as $fp): ?>
                <div class="fame-card">
                    <?php if ($fp['image']): ?>
                        <div class="fame-card-image">
                            <img src="<?= SITE_URL ?>/uploads/posts/<?= sanitize($fp['image']) ?>" alt="">
                        </div>
                    <?php endif; ?>
                    <div class="fame-card-body">
                        <div class="fame-badge-row">
                            <span class="fame-badge"><i class="fas fa-star"></i>
                                <?= sanitize($fp['badge_label'] ?: 'Legend') ?>
                            </span>
                        </div>
                        <h3 class="fame-card-title">
                            <?= sanitize($fp['title']) ?>
                        </h3>
                        <p class="fame-card-desc">
                            <?= nl2br(sanitize($fp['description'])) ?>
                        </p>
                    </div>
                    <div class="fame-card-footer">
                        <img src="<?= SITE_URL ?>/uploads/avatars/<?= sanitize($fp['avatar']) ?>" alt=""
                            class="fame-footer-avatar"
                            onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($fp['username']) ?>&background=8b5cf6&color=fff'">
                        <div>
                            <span class="fame-footer-by">โพสต์โดย
                                <?= sanitize($fp['username']) ?>
                            </span>
                            <span class="fame-footer-time">
                                <?= timeAgo($fp['created_at']) ?>
                            </span>
                        </div>
                        <?php if (isAdmin()): ?>
                            <button class="post-delete-btn" onclick="deleteFame(<?= $fp['id'] ?>)" title="ลบ"
                                style="margin-left:auto;">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    async function deleteFame(id) {
        if (!confirm('ต้องการลบรายการนี้ออกจาก Hall of Fame?')) return;
        const fd = new FormData();
        fd.append('fame_id', id);
        const res = await fetch(`${SITE_URL}/api/delete_fame.php`, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) location.reload();
        else alert(data.error || 'เกิดข้อผิดพลาด');
    }
</script>

<?php require_once 'footer.php'; ?>