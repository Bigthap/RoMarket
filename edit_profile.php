<?php
$pageTitle = 'แก้ไขโปรไฟล์';
require_once 'config.php';
require_once 'functions.php';
requireLogin();

$user = getUserById($pdo, $_SESSION['user_id']);
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bio = trim($_POST['bio'] ?? '');
    $robloxUsername = trim($_POST['roblox_username'] ?? '');

    // Upload avatar
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $avatarName = uploadImage($_FILES['avatar'], 'avatars');
        if ($avatarName) {
            $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
            $stmt->execute([$avatarName, $_SESSION['user_id']]);
            $_SESSION['avatar'] = $avatarName;
        } else {
            $errors[] = 'อัพโหลดรูปไม่สำเร็จ';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE users SET bio = ?, roblox_username = ? WHERE id = ?");
        $stmt->execute([$bio, $robloxUsername, $_SESSION['user_id']]);
        $success = 'อัปเดตโปรไฟล์เรียบร้อยแล้ว!';
        $user = getUserById($pdo, $_SESSION['user_id']); // refresh
    }
}

require_once 'header.php';
?>

<div class="form-container edit-profile-wrap">
    <div class="form-card">
        <h1 class="form-title"><i class="fas fa-user-edit"></i> แก้ไขโปรไฟล์</h1>

        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i>
                <?= $success ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i>
                <?= implode('<br>', $errors) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <div class="edit-avatar-preview">
                <img src="<?= SITE_URL ?>/uploads/avatars/<?= sanitize($user['avatar']) ?>" alt=""
                    onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user['username']) ?>&background=8b5cf6&color=fff&size=80'">
                <div class="form-group" style="margin-bottom:0;flex:1;">
                    <label class="form-label">เปลี่ยนรูปโปรไฟล์</label>
                    <input type="file" name="avatar" class="form-file" accept="image/*">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">ชื่อ Roblox</label>
                <input type="text" name="roblox_username" class="form-input" placeholder="ชื่อบัญชี Roblox"
                    value="<?= sanitize($user['roblox_username'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label">เกี่ยวกับตัวเอง</label>
                <textarea name="bio" class="form-textarea"
                    placeholder="เล่าเกี่ยวกับตัวคุณ..."><?= sanitize($user['bio'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary form-submit"><i class="fas fa-save"></i> บันทึก</button>
        </form>

        <p class="form-footer"><a href="<?= SITE_URL ?>/profile.php?id=<?= $_SESSION['user_id'] ?>"><i
                    class="fas fa-arrow-left"></i> กลับไปโปรไฟล์</a></p>
    </div>
</div>

<?php require_once 'footer.php'; ?>