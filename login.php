<?php
$pageTitle = 'เข้าสู่ระบบ';
require_once 'config.php';
require_once 'functions.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $errors[] = 'กรุณากรอกอีเมลและรหัสผ่าน';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && $password === $user['password']) {
            // ตรวจ ban
            if ($user['is_banned']) {
                $errors[] = 'บัญชีของคุณถูกระงับ กรุณาติดต่อผู้ดูแลระบบ';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['avatar'] = $user['avatar'];
                redirect('index.php');
            }
        } else {
            $errors[] = 'อีเมลหรือรหัสผ่านไม่ถูกต้อง';
        }
    }
}

require_once 'header.php';
?>

<div class="form-container">
    <div class="form-card">
        <h1 class="form-title"><i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ</h1>
        <p class="form-subtitle">ยินดีต้อนรับกลับสู่ RoMarket</p>

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
                <label class="form-label">อีเมล</label>
                <input type="email" name="email" class="form-input" placeholder="example@email.com"
                    value="<?= sanitize($email ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">รหัสผ่าน</label>
                <input type="password" name="password" class="form-input" placeholder="รหัสผ่านของคุณ" required>
            </div>

            <button type="submit" class="btn btn-primary form-submit"><i class="fas fa-sign-in-alt"></i>
                เข้าสู่ระบบ</button>
        </form>

        <p class="form-footer">ยังไม่มีบัญชี? <a href="<?= SITE_URL ?>/register.php">สมัครสมาชิก</a></p>
    </div>
</div>

<?php require_once 'footer.php'; ?>