<?php
$pageTitle = 'เพิ่ม Hall of Fame';
require_once 'config.php';
require_once 'functions.php';
requireAdmin();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $badgeLabel = trim($_POST['badge_label'] ?? 'Legend');

    if (empty($title))
        $errors[] = 'กรุณากรอกหัวข้อ';
    if (empty($description))
        $errors[] = 'กรุณากรอกรายละเอียด';

    // Upload image
    $imageName = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imageName = uploadImage($_FILES['image'], 'posts');
        if (!$imageName) {
            $errors[] = 'อัพโหลดรูปไม่สำเร็จ (รองรับ JPG, PNG, GIF, WEBP ขนาดไม่เกิน 5MB)';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO hall_of_fame (user_id, title, description, badge_label, image) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $title, $description, $badgeLabel, $imageName]);
        redirect('hall_of_fame.php');
    }
}

require_once 'header.php';
?>

<div class="form-container wide">
    <div class="form-card">
        <h1 class="form-title"><i class="fas fa-trophy"></i> เพิ่ม Hall of Fame</h1>
        <p class="form-subtitle">ประกาศเกียรติยศหรือสร้างตำนานใหม่</p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span>
                    <?= implode('<br>', $errors) ?>
                </span>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label class="form-label">หัวข้อ</label>
                <input type="text" name="title" class="form-input" placeholder="เช่น Top Trader อันดับ 1 ประจำเดือน"
                    value="<?= sanitize($title ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">รายละเอียด</label>
                <textarea name="description" class="form-textarea"
                    placeholder="เล่ารายละเอียดเกี่ยวกับ Hall of Fame นี้..."
                    required><?= sanitize($description ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">ป้ายเกียรติยศ</label>
                <select name="badge_label" class="form-select">
                    <option value="Legend">🏆 Legend</option>
                    <option value="Top Trader">⭐ Top Trader</option>
                    <option value="Rare Collector">💎 Rare Collector</option>
                    <option value="MVP">🔥 MVP</option>
                    <option value="Hall of Fame">🎖️ Hall of Fame</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">รูปภาพ (ไม่จำเป็น)</label>
                <input type="file" name="image" class="form-file" accept="image/*">
            </div>

            <button type="submit" class="btn btn-primary form-submit"><i class="fas fa-trophy"></i> ประกาศ Hall of
                Fame</button>
        </form>

        <p class="form-footer"><a href="<?= SITE_URL ?>/hall_of_fame.php"><i class="fas fa-arrow-left"></i> กลับ Hall of
                Fame</a></p>
    </div>
</div>

<?php require_once 'footer.php'; ?>