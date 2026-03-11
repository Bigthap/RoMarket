<?php
require_once 'config.php';
require_once 'functions.php';

$isGuest = !isLoggedIn();

$postId = intval($_GET['id'] ?? 0);
if (!$postId)
    redirect('index.php');

// ดึงข้อมูลโพสต์
$stmt = $pdo->prepare("SELECT p.*, u.username, u.avatar, t.name AS tag_name, t.slug AS tag_slug
                        FROM posts p
                        JOIN users u ON p.user_id = u.id
                        LEFT JOIN tags t ON p.tag_id = t.id
                        WHERE p.id = ?");
$stmt->execute([$postId]);
$post = $stmt->fetch();

if (!$post)
    redirect('index.php');

$pageTitle = $post['title'];

// ดึงคอมเมนต์
$stmt = $pdo->prepare("SELECT c.*, u.username, u.avatar
                        FROM comments c
                        JOIN users u ON c.user_id = u.id
                        WHERE c.post_id = ?
                        ORDER BY c.created_at ASC");
$stmt->execute([$postId]);
$comments = $stmt->fetchAll();

$likeCount = getLikeCount($pdo, $postId);
$liked = $isGuest ? false : isLiked($pdo, $postId, $_SESSION['user_id']);
$currentUser = $isGuest ? null : getUserById($pdo, $_SESSION['user_id']);

require_once 'header.php';
?>

<div class="post-detail">
    <div class="post-detail-card">
        <div class="post-detail-header">
            <div class="post-user-info">
                <a href="<?= SITE_URL ?>/profile.php?id=<?= $post['user_id'] ?>">
                    <img src="<?= SITE_URL ?>/uploads/avatars/<?= sanitize($post['avatar']) ?>" alt=""
                        class="post-avatar"
                        onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($post['username']) ?>&background=8b5cf6&color=fff'">
                </a>
                <div>
                    <a href="<?= SITE_URL ?>/profile.php?id=<?= $post['user_id'] ?>" class="post-username">
                        <?= sanitize($post['username']) ?>
                    </a>
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
            <?php if (!$isGuest && ($post['user_id'] == $_SESSION['user_id'] || isAdmin())): ?>
                <button class="post-delete-btn" onclick="deletePost(<?= $post['id'] ?>, true)" title="ลบโพสต์">
                    <i class="fas fa-trash-alt"></i>
                </button>
            <?php endif; ?>
        </div>

        <div class="post-detail-body">
            <h1 class="post-detail-title">
                <?= sanitize($post['title']) ?>
            </h1>

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
                <?php if ($post['tag_name']): ?>
                    <a href="<?= SITE_URL ?>/?tag=<?= urlencode($post['tag_slug']) ?>" class="post-tag-link">
                        <i class="fas fa-gamepad"></i>
                        <?= sanitize($post['tag_name']) ?>
                    </a>
                <?php endif; ?>
            </div>

            <div class="post-detail-content">
                <?= nl2br(sanitize($post['content'])) ?>
            </div>
        </div>

        <?php if ($post['image']): ?>
            <div class="post-detail-image-container">
                <div class="post-image-blur"
                    style="background-image: url('<?= SITE_URL ?>/uploads/posts/<?= sanitize($post['image']) ?>');"></div>
                <img src="<?= SITE_URL ?>/uploads/posts/<?= sanitize($post['image']) ?>" alt="" class="post-detail-image">
            </div>
        <?php endif; ?>

        <div class="post-detail-footer">
            <div class="post-actions">
                <?php if ($isGuest): ?>
                    <a href="<?= SITE_URL ?>/login.php" class="post-action-btn" title="เข้าสู่ระบบเพื่อกดถูกใจ">
                        <i class="far fa-heart"></i>
                        <span class="like-count"><?= $likeCount ?></span>
                    </a>
                <?php else: ?>
                    <button class="post-action-btn <?= $liked ? 'liked' : '' ?>"
                        onclick="toggleLike(<?= $post['id'] ?>, this)" id="like-btn-<?= $post['id'] ?>">
                        <i class="<?= $liked ? 'fas' : 'far' ?> fa-heart"></i>
                        <span class="like-count"><?= $likeCount ?></span>
                    </button>
                <?php endif; ?>
                <span class="post-action-btn">
                    <i class="far fa-comment"></i>
                    <span id="comment-count">
                        <?= count($comments) ?>
                    </span>
                </span>
            </div>
        </div>
    </div>

    <!-- Comments Section -->
    <div class="comments-section" id="comments">
        <h3 class="comments-title"><i class="fas fa-comments"></i> คอมเมนต์ (
            <?= count($comments) ?>)
        </h3>

        <?php if ($isGuest): ?>
            <div class="comment-form guest-login-prompt">
                <p><i class="fas fa-sign-in-alt"></i> <a href="<?= SITE_URL ?>/login.php">เข้าสู่ระบบ</a> เพื่อแสดงความคิดเห็น</p>
            </div>
        <?php else: ?>
            <div class="comment-form">
                <img src="<?= SITE_URL ?>/uploads/avatars/<?= sanitize($currentUser['avatar']) ?>" alt=""
                    class="comment-avatar"
                    onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($currentUser['username']) ?>&background=8b5cf6&color=fff'">
                <div class="comment-input-wrap">
                    <textarea class="comment-input" id="commentInput" placeholder="เขียนคอมเมนต์..."></textarea>
                    <div class="comment-submit-row">
                        <button class="btn btn-primary btn-sm" onclick="submitComment(<?= $post['id'] ?>)"><i
                                class="fas fa-paper-plane"></i> ส่ง</button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="comment-list" id="commentList">
            <?php foreach ($comments as $comment): ?>
                <div class="comment-item">
                    <a href="<?= SITE_URL ?>/profile.php?id=<?= $comment['user_id'] ?>">
                        <img src="<?= SITE_URL ?>/uploads/avatars/<?= sanitize($comment['avatar']) ?>" alt=""
                            class="comment-avatar"
                            onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($comment['username']) ?>&background=8b5cf6&color=fff'">
                    </a>
                    <div class="comment-body">
                        <a href="<?= SITE_URL ?>/profile.php?id=<?= $comment['user_id'] ?>" class="comment-user">
                            <?= sanitize($comment['username']) ?>
                        </a>
                        <span class="comment-time">
                            <?= timeAgo($comment['created_at']) ?>
                        </span>
                        <p class="comment-text">
                            <?= nl2br(sanitize($comment['content'])) ?>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>