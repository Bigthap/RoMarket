<?php
$pageTitle = 'สมัครสมาชิก';
require_once 'config.php';
require_once 'functions.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $robloxUsername = trim($_POST['roblox_username'] ?? '');

    // Validate
    if (empty($username))
        $errors[] = 'กรุณากรอกชื่อผู้ใช้';
    if (strlen($username) < 3)
        $errors[] = 'ชื่อผู้ใช้ต้องมีอย่างน้อย 3 ตัวอักษร';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = 'อีเมลไม่ถูกต้อง';
    if (strlen($password) < 6)
        $errors[] = 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร';
    if ($password !== $confirmPassword)
        $errors[] = 'รหัสผ่านไม่ตรงกัน';

    // Check duplicates
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'ชื่อผู้ใช้หรืออีเมลนี้มีอยู่ในระบบแล้ว';
        }
    }

    // Create user
    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, roblox_username) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $email, $password, $robloxUsername]);

        $userId = $pdo->lastInsertId();
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = 'user';
        $_SESSION['avatar'] = 'default.png';

        redirect('index.php');
    }
}

require_once 'header.php';
?>

<div class="form-container">
    <div class="form-card">
        <h1 class="form-title"><i class="fas fa-user-plus"></i> สมัครสมาชิก</h1>
        <p class="form-subtitle">เข้าร่วม RoMarket เพื่อซื้อขายไอเท็ม Roblox</p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span>
                    <?= implode('<br>', $errors) ?>
                </span>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">ชื่อผู้ใช้</label>
                <input type="text" name="username" class="form-input" placeholder="เช่น RobloxPro"
                    value="<?= sanitize($username ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">อีเมล</label>
                <input type="email" name="email" class="form-input" placeholder="example@email.com"
                    value="<?= sanitize($email ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">ชื่อ Roblox (ไม่จำเป็น)</label>
                <input type="text" name="roblox_username" class="form-input" placeholder="ชื่อบัญชี Roblox ของคุณ"
                    value="<?= sanitize($robloxUsername ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label">รหัสผ่าน</label>
                <input type="password" name="password" class="form-input" placeholder="อย่างน้อย 6 ตัวอักษร" required>
            </div>

            <div class="form-group">
                <label class="form-label">ยืนยันรหัสผ่าน</label>
                <input type="password" name="confirm_password" class="form-input" placeholder="พิมพ์รหัสผ่านอีกครั้ง"
                    required>
            </div>

            <button type="submit" class="btn btn-primary form-submit"><i class="fas fa-user-plus"></i>
                สมัครสมาชิก</button>
        </form>

        <p class="form-footer">มีบัญชีแล้ว? <a href="<?= SITE_URL ?>/login.php">เข้าสู่ระบบ</a></p>
    </div>
</div>

<?php require_once 'footer.php'; ?>