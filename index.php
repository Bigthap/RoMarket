<?php
$pageTitle = 'ฟีด';
require_once 'config.php';
require_once 'functions.php';

$isGuest = !isLoggedIn();

// ดึงพารามิเตอร์ Filter ทั้งหมด
$tagSlug = $_GET['tag'] ?? '';
$postType = $_GET['type'] ?? '';
$itemSearch = trim($_GET['item'] ?? '');
$userSearch = trim($_GET['user'] ?? '');
$priceMin = $_GET['price_min'] ?? '';
$priceMax = $_GET['price_max'] ?? '';
$minLikes = $_GET['min_likes'] ?? '';
$minComments = $_GET['min_comments'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// สร้าง query
$where = [];
$params = [];
$having = [];
$havingParams = [];

if ($tagSlug) {
    $where[] = "t.slug = ?";
    $params[] = $tagSlug;
}

if ($postType && in_array($postType, ['sell', 'buy', 'trade'])) {
    $where[] = "p.post_type = ?";
    $params[] = $postType;
}

if ($itemSearch) {
    $where[] = "p.item_name LIKE ?";
    $params[] = "%$itemSearch%";
}

if ($userSearch) {
    $where[] = "u.username LIKE ?";
    $params[] = "%$userSearch%";
}

if ($priceMin !== '') {
    $where[] = "CAST(REGEXP_REPLACE(p.price, '[^0-9.]', '') AS DECIMAL(10,2)) >= ?";
    $params[] = floatval($priceMin);
}

if ($priceMax !== '') {
    $where[] = "CAST(REGEXP_REPLACE(p.price, '[^0-9.]', '') AS DECIMAL(10,2)) <= ?";
    $params[] = floatval($priceMax);
}

if ($minLikes !== '' && intval($minLikes) > 0) {
    $having[] = "like_count >= ?";
    $havingParams[] = intval($minLikes);
}

if ($minComments !== '' && intval($minComments) > 0) {
    $having[] = "comment_count >= ?";
    $havingParams[] = intval($minComments);
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$havingSQL = $having ? 'HAVING ' . implode(' AND ', $having) : '';
$allParams = array_merge($params, $havingParams);

// นับจำนวนโพสต์ทั้งหมด (with subquery for HAVING)
$countSQL = "SELECT COUNT(*) FROM (
    SELECT p.id,
           (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS like_count,
           (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comment_count
    FROM posts p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN tags t ON p.tag_id = t.id
    $whereSQL
    GROUP BY p.id
    $havingSQL
) AS filtered";
$stmt = $pdo->prepare($countSQL);
$stmt->execute($allParams);
$totalPosts = $stmt->fetchColumn();
$totalPages = ceil($totalPosts / $perPage);

// ดึงโพสต์
$sql = "SELECT p.*, u.username, u.avatar, t.name AS tag_name, t.slug AS tag_slug,
               (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS like_count,
               (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comment_count
        FROM posts p
        JOIN users u ON p.user_id = u.id
        LEFT JOIN tags t ON p.tag_id = t.id
        $whereSQL
        GROUP BY p.id
        $havingSQL
        ORDER BY p.created_at DESC
        LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($allParams);
$posts = $stmt->fetchAll();

// ตรวจว่ามี filter อยู่ไหม
$hasFilter = $tagSlug || $postType || $itemSearch || $userSearch || $priceMin !== '' || $priceMax !== '' || $minLikes !== '' || $minComments !== '';

require_once 'header.php';
?>

<div class="feed-layout">
    <div class="feed-posts">

        <!-- Advanced Filter Panel -->
        <div class="filter-panel">
            <div class="filter-panel-header" onclick="toggleFilterPanel()">
                <span><i class="fas fa-sliders-h"></i> ค้นหาขั้นสูง</span>
                <?php if ($hasFilter): ?>
                    <span class="filter-active-badge"><i class="fas fa-filter"></i> กำลังกรอง</span>
                <?php endif; ?>
                <i class="fas fa-chevron-down filter-toggle-icon" id="filterToggleIcon"></i>
            </div>
            <form class="filter-panel-body" id="filterPanelBody" method="GET" action="<?= SITE_URL ?>/">
                <div class="filter-grid">
                    <!-- ชื่อไอเท็ม -->
                    <div class="filter-field">
                        <label class="filter-label"><i class="fas fa-cube"></i> ชื่อไอเท็ม</label>
                        <input type="text" name="item" class="filter-input" placeholder="เช่น Godly, Buddha..."
                            value="<?= sanitize($itemSearch) ?>">
                    </div>

                    <!-- Username -->
                    <div class="filter-field">
                        <label class="filter-label"><i class="fas fa-user"></i> Username ผู้โพสต์</label>
                        <input type="text" name="user" class="filter-input" placeholder="ค้นหาชื่อผู้โพสต์..."
                            value="<?= sanitize($userSearch) ?>">
                    </div>

                    <!-- ราคาต่ำสุด -->
                    <div class="filter-field">
                        <label class="filter-label"><i class="fas fa-coins"></i> ราคาต่ำสุด</label>
                        <input type="number" name="price_min" class="filter-input" placeholder="0" min="0"
                            value="<?= sanitize($priceMin) ?>">
                    </div>

                    <!-- ราคาสูงสุด -->
                    <div class="filter-field">
                        <label class="filter-label"><i class="fas fa-coins"></i> ราคาสูงสุด</label>
                        <input type="number" name="price_max" class="filter-input" placeholder="99999" min="0"
                            value="<?= sanitize($priceMax) ?>">
                    </div>

                    <!-- Map / แท็ก -->
                    <div class="filter-field">
                        <label class="filter-label"><i class="fas fa-gamepad"></i> Map / เกม</label>
                        <select name="tag" class="filter-input">
                            <option value="">ทุก Map</option>
                            <?php foreach ($allTags as $tag): ?>
                                <option value="<?= $tag['slug'] ?>" <?= $tagSlug === $tag['slug'] ? 'selected' : '' ?>>
                                    <?= sanitize($tag['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- ประเภท -->
                    <div class="filter-field">
                        <label class="filter-label"><i class="fas fa-tag"></i> ประเภท</label>
                        <select name="type" class="filter-input">
                            <option value="">ทั้งหมด</option>
                            <option value="sell" <?= $postType === 'sell' ? 'selected' : '' ?>>🟢 ขาย</option>
                            <option value="buy" <?= $postType === 'buy' ? 'selected' : '' ?>>🔵 ซื้อ</option>
                            <option value="trade" <?= $postType === 'trade' ? 'selected' : '' ?>>🟣 แลก</option>
                        </select>
                    </div>

                    <!-- ยอดไลค์ขั้นต่ำ -->
                    <div class="filter-field">
                        <label class="filter-label"><i class="fas fa-heart"></i> ไลค์ขั้นต่ำ</label>
                        <input type="number" name="min_likes" class="filter-input" placeholder="0" min="0"
                            value="<?= sanitize($minLikes) ?>">
                    </div>

                    <!-- จำนวนคอมเมนต์ขั้นต่ำ -->
                    <div class="filter-field">
                        <label class="filter-label"><i class="fas fa-comment"></i> คอมเมนต์ขั้นต่ำ</label>
                        <input type="number" name="min_comments" class="filter-input" placeholder="0" min="0"
                            value="<?= sanitize($minComments) ?>">
                    </div>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> ค้นหา</button>
                    <a href="<?= SITE_URL ?>/" class="btn btn-outline btn-sm"><i class="fas fa-times"></i> ล้าง
                        Filter</a>
                </div>
            </form>
        </div>

        <!-- Quick Filter Bar -->
        <div class="filter-bar">
            <?php
            $qp = $_GET;
            unset($qp['type'], $qp['page']);
            $baseQ = http_build_query($qp);
            ?>
            <a href="<?= SITE_URL ?>/?<?= $baseQ ?>" class="filter-btn <?= !$postType ? 'active' : '' ?>">ทั้งหมด</a>
            <a href="<?= SITE_URL ?>/?<?= $baseQ ?><?= $baseQ ? '&' : '' ?>type=sell"
                class="filter-btn <?= $postType === 'sell' ? 'active' : '' ?>">🟢 ขาย</a>
            <a href="<?= SITE_URL ?>/?<?= $baseQ ?><?= $baseQ ? '&' : '' ?>type=buy"
                class="filter-btn <?= $postType === 'buy' ? 'active' : '' ?>">🔵 ซื้อ</a>
            <a href="<?= SITE_URL ?>/?<?= $baseQ ?><?= $baseQ ? '&' : '' ?>type=trade"
                class="filter-btn <?= $postType === 'trade' ? 'active' : '' ?>">🟣 แลก</a>
            <?php if ($hasFilter): ?>
                <span class="filter-result-count"><i class="fas fa-check-circle"></i> พบ <?= $totalPosts ?> โพสต์</span>
            <?php endif; ?>
        </div>

        <?php if (empty($posts)): ?>
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <h3>ไม่พบโพสต์</h3>
                <?php if ($hasFilter): ?>
                    <p>ลองเปลี่ยนเงื่อนไข Filter ดู</p>
                    <a href="<?= SITE_URL ?>/" class="btn btn-outline" style="margin-top:1rem;"><i class="fas fa-times"></i>
                        ล้าง Filter</a>
                <?php else: ?>
                    <p>เป็นคนแรกที่โพสต์ขายไอเท็ม!</p>
                    <a href="<?= SITE_URL ?>/create_post.php" class="btn btn-primary" style="margin-top:1rem;"><i
                            class="fas fa-plus"></i> สร้างโพสต์</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <div class="post-card" id="post-<?= $post['id'] ?>">
                    <div class="post-card-header">
                        <div class="post-user-info">
                            <a href="<?= SITE_URL ?>/profile.php?id=<?= $post['user_id'] ?>">
                                <img src="<?= SITE_URL ?>/uploads/avatars/<?= sanitize($post['avatar']) ?>" alt=""
                                    class="post-avatar"
                                    onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($post['username']) ?>&background=8b5cf6&color=fff'">
                            </a>
                            <div>
                                <a href="<?= SITE_URL ?>/profile.php?id=<?= $post['user_id'] ?>"
                                    class="post-username"><?= sanitize($post['username']) ?></a>
                                <div class="post-meta">
                                    <span><?= timeAgo($post['created_at']) ?></span>
                                    <span
                                        class="badge <?= getPostTypeClass($post['post_type']) ?>"><?= getPostTypeLabel($post['post_type']) ?></span>
                                </div>
                            </div>
                        </div>
                        <?php if (!$isGuest && ($post['user_id'] == $_SESSION['user_id'] || isAdmin())): ?>
                            <button class="post-delete-btn" onclick="deletePost(<?= $post['id'] ?>)" title="ลบโพสต์">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        <?php endif; ?>
                    </div>

                    <div class="post-card-body">
                        <h3 class="post-title"><a
                                href="<?= SITE_URL ?>/post_detail.php?id=<?= $post['id'] ?>"><?= sanitize($post['title']) ?></a>
                        </h3>
                        <p class="post-content">
                            <?= nl2br(sanitize(mb_substr($post['content'], 0, 200))) ?>
                            <?= mb_strlen($post['content']) > 200 ? '...' : '' ?>
                        </p>

                        <div class="post-item-info">
                            <?php if ($post['item_name']): ?>
                                <span class="post-item-tag"><i class="fas fa-cube"></i> <?= sanitize($post['item_name']) ?></span>
                            <?php endif; ?>
                            <?php if ($post['price']): ?>
                                <span class="post-price"><i class="fas fa-coins"></i>
                                    <?= formatPrice($post['price'], $post['currency']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($post['image']): ?>
                        <a href="<?= SITE_URL ?>/post_detail.php?id=<?= $post['id'] ?>" class="post-image-link">
                            <div class="post-image-blur"
                                style="background-image: url('<?= SITE_URL ?>/uploads/posts/<?= sanitize($post['image']) ?>');">
                            </div>
                            <img src="<?= SITE_URL ?>/uploads/posts/<?= sanitize($post['image']) ?>" alt="" class="post-image">
                        </a>
                    <?php endif; ?>

                    <div class="post-card-footer">
                        <div class="post-actions">
                            <?php
                            $likeCount = $post['like_count'];
                            $liked = $isGuest ? false : isLiked($pdo, $post['id'], $_SESSION['user_id']);
                            $commentCount = $post['comment_count'];
                            ?>
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
                            <a href="<?= SITE_URL ?>/post_detail.php?id=<?= $post['id'] ?>#comments" class="post-action-btn">
                                <i class="far fa-comment"></i>
                                <span><?= $commentCount ?></span>
                            </a>
                        </div>
                        <?php if ($post['tag_name']): ?>
                            <a href="<?= SITE_URL ?>/?tag=<?= urlencode($post['tag_slug']) ?>" class="post-tag-link">
                                <i class="fas fa-gamepad"></i> <?= sanitize($post['tag_name']) ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php
                    $queryParams = $_GET;
                    ?>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php $queryParams['page'] = $i; ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?= $i ?></span>
                        <?php else: ?>
                            <a href="<?= SITE_URL ?>/?<?= http_build_query($queryParams) ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Sidebar Tags -->
    <aside class="sidebar">
        <div class="sidebar-card">
            <h3 class="sidebar-title"><i class="fas fa-gamepad"></i> Map / เกม</h3>
            <ul class="tag-list">
                <li class="tag-item">
                    <a href="<?= SITE_URL ?>/" class="<?= !$tagSlug ? 'active' : '' ?>"><i class="fas fa-th-large"></i>
                        ทั้งหมด</a>
                </li>
                <?php foreach ($allTags as $tag): ?>
                    <li class="tag-item">
                        <a href="<?= SITE_URL ?>/?tag=<?= urlencode($tag['slug']) ?>"
                            class="<?= $tagSlug === $tag['slug'] ? 'active' : '' ?>">
                            <i class="fas fa-hashtag"></i> <?= sanitize($tag['name']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </aside>
</div>

<script>
    function toggleFilterPanel() {
        const body = document.getElementById('filterPanelBody');
        const icon = document.getElementById('filterToggleIcon');
        body.classList.toggle('open');
        icon.classList.toggle('rotated');
    }
    <?php if ($hasFilter): ?>
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('filterPanelBody').classList.add('open');
            document.getElementById('filterToggleIcon').classList.add('rotated');
        });
    <?php endif; ?>
</script>

<?php require_once 'footer.php'; ?>