<?php
require_once 'config.php';
require_once 'functions.php';

$userId = intval($_GET['id'] ?? 0);
if (!$userId)
    redirect('index.php');

$user = getUserById($pdo, $userId);
if (!$user)
    redirect('index.php');

$pageTitle = $user['username'];

// นับโพสต์ของ user
$stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
$stmt->execute([$userId]);
$postCount = $stmt->fetchColumn();

// นับไลค์ที่ได้รับทั้งหมด
$stmt = $pdo->prepare("SELECT COUNT(*) FROM likes l JOIN posts p ON l.post_id = p.id WHERE p.user_id = ?");
$stmt->execute([$userId]);
$totalLikes = $stmt->fetchColumn();

// ดึงโพสต์ของ user
$stmt = $pdo->prepare("SELECT p.*, u.username, u.avatar, t.name AS tag_name, t.slug AS tag_slug
                        FROM posts p
                        JOIN users u ON p.user_id = u.id
                        LEFT JOIN tags t ON p.tag_id = t.id
                        WHERE p.user_id = ?
                        ORDER BY p.created_at DESC");
$stmt->execute([$userId]);
$posts = $stmt->fetchAll();

require_once 'header.php';
?>

<div class="profile-header">
    <div class="profile-avatar-wrap">
        <img src="<?= SITE_URL ?>/uploads/avatars/<?= sanitize($user['avatar']) ?>" alt="" class="profile-avatar"
            onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user['username']) ?>&background=8b5cf6&color=fff&size=110'">
    </div>
    <h1 class="profile-username">
        <?= sanitize($user['username']) ?>
        <?php if ($user['role'] === 'admin'): ?>
            <span class="badge badge-admin" style="font-size:0.6rem;vertical-align:middle;">ADMIN</span>
        <?php endif; ?>
    </h1>
    <?php if ($user['roblox_username']): ?>
        <div class="profile-roblox"><i class="fas fa-gamepad"></i>
            <?= sanitize($user['roblox_username']) ?>
        </div>
    <?php endif; ?>
    <?php if ($user['bio']): ?>
        <p class="profile-bio">
            <?= nl2br(sanitize($user['bio'])) ?>
        </p>
    <?php endif; ?>

    <div class="profile-stats">
        <div class="profile-stat">
            <div class="profile-stat-value">
                <?= $postCount ?>
            </div>
            <div class="profile-stat-label">โพสต์</div>
        </div>
        <div class="profile-stat">
            <div class="profile-stat-value">
                <?= $totalLikes ?>
            </div>
            <div class="profile-stat-label">ไลค์ที่ได้</div>
        </div>
    </div>

    <div class="profile-joined"><i class="far fa-calendar-alt"></i> สมาชิกตั้งแต่
        <?= date('d/m/Y', strtotime($user['created_at'])) ?>
    </div>

    <?php if (isLoggedIn() && $_SESSION['user_id'] == $userId): ?>
        <div class="profile-actions">
            <a href="<?= SITE_URL ?>/edit_profile.php" class="btn btn-outline btn-sm"><i class="fas fa-edit"></i>
                แก้ไขโปรไฟล์</a>
        </div>
    <?php elseif (isLoggedIn()): ?>
        <div class="profile-actions">
            <a href="<?= SITE_URL ?>/messages.php?user=<?= $userId ?>" class="btn btn-primary btn-sm"><i
                    class="fas fa-envelope"></i>
                ส่งข้อความ</a>
        </div>
    <?php endif; ?>
</div>

<h3 class="profile-posts-title"><i class="fas fa-th-large"></i> โพสต์ของ
    <?= sanitize($user['username']) ?>
</h3>

<div class="feed-posts">
    <?php if (empty($posts)): ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <h3>ยังไม่มีโพสต์</h3>
        </div>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
            <div class="post-card" id="post-<?= $post['id'] ?>">
                <div class="post-card-header">
                    <div class="post-user-info">
                        <img src="<?= SITE_URL ?>/uploads/avatars/<?= sanitize($post['avatar']) ?>" alt="" class="post-avatar"
                            onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($post['username']) ?>&background=8b5cf6&color=fff'">
                        <div>
                            <span class="post-username">
                                <?= sanitize($post['username']) ?>
                            </span>
                            <div class="post-meta">
                                <span>
                                    <?= timeAgo($post['created_at']) ?>
                                </span>
                                <span class="badge <?= getPostTypeClass($post['post_type']) ?>">
                                    <?= getPostTypeLabel($post['post_type']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php if (isLoggedIn() && ($post['user_id'] == $_SESSION['user_id'] || isAdmin())): ?>
                        <button class="post-delete-btn" onclick="deletePost(<?= $post['id'] ?>)" title="ลบโพสต์"><i
                                class="fas fa-trash-alt"></i></button>
                    <?php endif; ?>
                </div>
                <div class="post-card-body">
                    <h3 class="post-title"><a href="<?= SITE_URL ?>/post_detail.php?id=<?= $post['id'] ?>">
                            <?= sanitize($post['title']) ?>
                        </a></h3>
                    <p class="post-content">
                        <?= nl2br(sanitize(mb_substr($post['content'], 0, 200))) ?>
                    </p>
                    <div class="post-item-info">
                        <?php if ($post['item_name']): ?>
                            <span class="post-item-tag"><i class="fas fa-cube"></i>
                                <?= sanitize($post['item_name']) ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($post['price']): ?>
                            <span class="post-price"><i class="fas fa-coins"></i>
                                <?= formatPrice($post['price'], $post['currency']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($post['image']): ?>
                    <a href="<?= SITE_URL ?>/post_detail.php?id=<?= $post['id'] ?>" class="post-image-link">
                        <div class="post-image-blur"
                            style="background-image: url('<?= SITE_URL ?>/uploads/posts/<?= sanitize($post['image']) ?>');"></div>
                        <img src="<?= SITE_URL ?>/uploads/posts/<?= sanitize($post['image']) ?>" alt="" class="post-image">
                    </a>
                <?php endif; ?>
                <div class="post-card-footer">
                    <div class="post-actions">
                        <?php
                        $likeCount = getLikeCount($pdo, $post['id']);
                        $commentCount = getCommentCount($pdo, $post['id']);
                        $liked = isLoggedIn() ? isLiked($pdo, $post['id'], $_SESSION['user_id']) : false;
                        ?>
                        <button class="post-action-btn <?= $liked ? 'liked' : '' ?>"
                            onclick="toggleLike(<?= $post['id'] ?>, this)">
                            <i class="<?= $liked ? 'fas' : 'far' ?> fa-heart"></i>
                            <span class="like-count">
                                <?= $likeCount ?>
                            </span>
                        </button>
                        <a href="<?= SITE_URL ?>/post_detail.php?id=<?= $post['id'] ?>#comments" class="post-action-btn">
                            <i class="far fa-comment"></i> <span>
                                <?= $commentCount ?>
                            </span>
                        </a>
                    </div>
                    <?php if ($post['tag_name']): ?>
                        <a href="<?= SITE_URL ?>/?tag=<?= urlencode($post['tag_slug']) ?>" class="post-tag-link"><i
                                class="fas fa-gamepad"></i>
                            <?= sanitize($post['tag_name']) ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>