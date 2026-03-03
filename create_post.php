<?php
$pageTitle = 'สร้างโพสต์ใหม่';
require_once 'config.php';
require_once 'functions.php';
requireLogin();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $postType = $_POST['post_type'] ?? 'sell';
    $itemName = trim($_POST['item_name'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $currency = $_POST['currency'] ?? 'THB';
    $tagId = intval($_POST['tag_id'] ?? 0);

    // Validate
    if (empty($title))
        $errors[] = 'กรุณากรอกหัวข้อโพสต์';
    if (empty($content))
        $errors[] = 'กรุณากรอกเนื้อหา';
    if (!in_array($postType, ['sell', 'buy', 'trade']))
        $errors[] = 'ประเภทโพสต์ไม่ถูกต้อง';

    // ซื้อ/ขาย ราคาต้องเป็นจำนวนเต็มบวก
    if (in_array($postType, ['sell', 'buy']) && $price !== '') {
        if (!ctype_digit($price) || intval($price) <= 0) {
            $errors[] = 'ราคาต้องเป็นตัวเลขจำนวนเต็มบวกเท่านั้น (สำหรับซื้อ/ขาย)';
        }
    }

    // Upload image
    $imageName = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imageName = uploadImage($_FILES['image'], 'posts');
        if (!$imageName) {
            $errors[] = 'อัพโหลดรูปไม่สำเร็จ (รองรับ JPG, PNG, GIF, WEBP ขนาดไม่เกิน 5MB)';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, title, content, post_type, item_name, price, currency, image, tag_id)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['user_id'],
            $title,
            $content,
            $postType,
            $itemName ?: null,
            $price ?: null,
            in_array($postType, ['sell','buy']) ? $currency : null,
            $imageName,
            $tagId ?: null
        ]);

        redirect('index.php');
    }
}

require_once 'header.php';
?>

<div class="form-container wide">
    <div class="form-card">
        <h1 class="form-title"><i class="fas fa-plus-circle"></i> สร้างโพสต์ใหม่</h1>
        <p class="form-subtitle">โพสต์ซื้อ ขาย หรือแลกเปลี่ยนไอเท็ม Roblox</p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span>
                    <?= implode('<br>', $errors) ?>
                </span>
            </div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label class="form-label">ประเภท</label>
                <div class="post-type-group">
                    <input type="radio" name="post_type" value="sell" id="type-sell" class="post-type-radio"
                        <?= ($postType ?? 'sell') === 'sell' ? 'checked' : '' ?>>
                    <label for="type-sell" class="post-type-label">🟢 ขาย</label>
                    <input type="radio" name="post_type" value="buy" id="type-buy" class="post-type-radio" <?= ($postType ?? '') === 'buy' ? 'checked' : '' ?>>
                    <label for="type-buy" class="post-type-label">🔵 ซื้อ</label>
                    <input type="radio" name="post_type" value="trade" id="type-trade" class="post-type-radio"
                        <?= ($postType ?? '') === 'trade' ? 'checked' : '' ?>>
                    <label for="type-trade" class="post-type-label">🟣 แลก</label>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">หัวข้อโพสต์</label>
                <input type="text" name="title" class="form-input" placeholder="เช่น ขาย Godly ใน MM2 ราคาถูก!"
                    value="<?= sanitize($title ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">เนื้อหา</label>
                <textarea name="content" class="form-textarea"
                    placeholder="รายละเอียดของไอเท็มที่ต้องการซื้อ/ขาย/แลก..."
                    required><?= sanitize($content ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">ชื่อไอเท็ม (ไม่จำเป็น)</label>
                <input type="text" name="item_name" class="form-input" placeholder="เช่น Godly Knife, Permanent Buddha"
                    value="<?= sanitize($itemName ?? '') ?>">
            </div>

            <div class="form-group" id="priceGroup">
                <label class="form-label" id="priceLabel">ราคา</label>
                <div style="display:flex;gap:0.5rem;align-items:start;">
                    <input type="number" name="price" id="priceInput" class="form-input" placeholder="เช่น 500"
                        value="<?= sanitize($price ?? '') ?>" min="1" step="1" style="flex:1;">
                    <select name="currency" id="currencySelect" class="form-select" style="width:100px;flex-shrink:0;">
                        <option value="THB" <?= ($currency ?? 'THB') === 'THB' ? 'selected' : '' ?>>฿ THB</option>
                        <option value="USD" <?= ($currency ?? '') === 'USD' ? 'selected' : '' ?>>$ USD</option>
                    </select>
                </div>
                <small id="priceHint"
                    style="color:var(--text-muted);font-size:0.78rem;margin-top:0.3rem;">กรอกจำนวนเต็มบวกเท่านั้น</small>
            </div>

            <div class="form-group">
                <label class="form-label">Map / เกม</label>
                <select name="tag_id" class="form-select">
                    <option value="">-- เลือก Map --</option>
                    <?php foreach ($allTags as $tag): ?>
                        <option value="<?= $tag['id'] ?>" <?= ($tagId ?? 0) == $tag['id'] ? 'selected' : '' ?>>
                            <?= sanitize($tag['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">รูปภาพ (ไม่จำเป็น)</label>
                <input type="file" name="image" class="form-file" accept="image/*">
            </div>

            <button type="submit" class="btn btn-primary form-submit"><i class="fas fa-paper-plane"></i> โพสต์</button>
        </form>
    </div>
</div>

<script>
    // สลับ input ราคาตามประเภทโพสต์
    document.querySelectorAll('input[name="post_type"]').forEach(radio => {
        radio.addEventListener('change', updatePriceField);
    });

    function updatePriceField() {
        const type = document.querySelector('input[name="post_type"]:checked')?.value;
        const priceInput = document.getElementById('priceInput');
        const priceLabel = document.getElementById('priceLabel');
        const priceHint = document.getElementById('priceHint');
        const currencySelect = document.getElementById('currencySelect');

        if (type === 'trade') {
            priceInput.type = 'text';
            priceInput.min = '';
            priceInput.step = '';
            priceInput.placeholder = 'เช่น แลกกับ Dark Blade';
            priceLabel.textContent = 'ข้อเสนอแลก';
            priceHint.textContent = 'พิมพ์ข้อเสนอแลกเปลี่ยนได้อิสระ';
            currencySelect.style.display = 'none';
        } else {
            priceInput.type = 'number';
            priceInput.min = '1';
            priceInput.step = '1';
            priceInput.placeholder = 'เช่น 500';
            priceLabel.textContent = 'ราคา';
            priceHint.textContent = 'กรอกจำนวนเต็มบวกเท่านั้น';
            currencySelect.style.display = '';
        }
    }

    // เรียกทันทีตอนโหลดหน้า
    document.addEventListener('DOMContentLoaded', updatePriceField);
</script>

<?php require_once 'footer.php'; ?>