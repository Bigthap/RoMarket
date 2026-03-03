<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
$allTags = getAllTags($pdo);
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="RoMarket - ตลาดซื้อขายแลกเปลี่ยนไอเท็มในเกม Roblox">
    <title>
        <?= isset($pageTitle) ? sanitize($pageTitle) . ' | ' : '' ?>RoMarket
    </title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/css/base.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/css/navbar.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/css/feed.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/css/post.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/css/profile.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/css/forms.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/css/admin.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/css/fame.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/css/messages.css">
</head>

<body>
    <nav class="navbar" id="navbar">
        <div class="nav-container">
            <a href="<?= SITE_URL ?>/" class="nav-logo">
                <i class="fas fa-gamepad"></i>
                <span>RoMarket</span>
            </a>

            <div class="nav-menu" id="navMenu">
                <a href="<?= SITE_URL ?>/" class="nav-link"><i class="fas fa-home"></i> ฟีด</a>
                <a href="<?= SITE_URL ?>/hall_of_fame.php" class="nav-link nav-fame"><i class="fas fa-trophy"></i> Hall
                    of Fame</a>
                <?php if (isLoggedIn()): ?>
                    <a href="<?= SITE_URL ?>/messages.php" class="nav-link"><i class="fas fa-envelope"></i>
                        ข้อความ</a>
                    <a href="<?= SITE_URL ?>/create_post.php" class="nav-link"><i class="fas fa-plus-circle"></i>
                        โพสต์ใหม่</a>
                    <a href="<?= SITE_URL ?>/profile.php?id=<?= $_SESSION['user_id'] ?>" class="nav-link"><i
                            class="fas fa-user"></i> โปรไฟล์</a>
                    <?php if (isAdmin()): ?>
                        <a href="<?= SITE_URL ?>/admin/dashboard.php" class="nav-link nav-admin"><i
                                class="fas fa-shield-alt"></i> Admin Panel</a>
                    <?php endif; ?>
                    <a href="<?= SITE_URL ?>/logout.php" class="nav-link nav-logout"><i class="fas fa-sign-out-alt"></i>
                        ออก</a>
                <?php else: ?>
                    <a href="<?= SITE_URL ?>/login.php" class="nav-link"><i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ</a>
                    <a href="<?= SITE_URL ?>/register.php" class="nav-link btn-register"><i class="fas fa-user-plus"></i>
                        สมัครสมาชิก</a>
                <?php endif; ?>
            </div>

            <button class="nav-toggle" id="navToggle" aria-label="Toggle menu">
                <span></span><span></span><span></span>
            </button>
        </div>
    </nav>

    <main class="main-content">