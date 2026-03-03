<?php
$pageTitle = 'ข้อความ (DM)';
require_once 'config.php';
require_once 'functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$currentUserId = $_SESSION['user_id'];
$chatWithId = isset($_GET['user']) ? intval($_GET['user']) : 0;

// ดึงรายชื่อผู้ติดต่อที่เคยคุยด้วย (จัดกลุ่มและเรียงตามข้อความล่าสุด)
$sqlContacts = "
    SELECT 
        u.id, 
        u.username, 
        u.avatar,
        MAX(m.created_at) as last_msg_time,
        (SELECT message FROM messages WHERE (sender_id = u.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = u.id) ORDER BY created_at DESC LIMIT 1) as last_message,
        (SELECT COUNT(*) FROM messages WHERE sender_id = u.id AND receiver_id = ? AND is_read = 0) as unread_count
    FROM users u
    JOIN messages m ON (m.sender_id = u.id AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = u.id)
    WHERE u.id != ?
    GROUP BY u.id
    ORDER BY last_msg_time DESC
";
$stmt = $pdo->prepare($sqlContacts);
$stmt->execute([$currentUserId, $currentUserId, $currentUserId, $currentUserId, $currentUserId, $currentUserId]);
$contacts = $stmt->fetchAll();

// ถ้าเข้า URL มาโดนระบุ user id ใหม่ที่ยังไม่เคยคุยด้วย ให้ดึงข้อมูลมาแสดงด้วย
$chatUser = null;
if ($chatWithId && $chatWithId != $currentUserId) {
    $chatUser = getUserById($pdo, $chatWithId);
    if ($chatUser) {
        // อัปเดตสถานะการอ่าน
        $stmtUpdate = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?");
        $stmtUpdate->execute([$chatWithId, $currentUserId]);

        // เช็คว่าอยู่ใน contacts ลิสต์ไหม ถ้าไม่ ให้เพิ่มแปะไว้บนสุด (แบบจำลองในอาร์เรย์)
        $found = false;
        foreach ($contacts as $c) {
            if ($c['id'] == $chatWithId) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            array_unshift($contacts, [
                'id' => $chatUser['id'],
                'username' => $chatUser['username'],
                'avatar' => $chatUser['avatar'],
                'last_msg_time' => '',
                'last_message' => 'ยังไม่มีข้อความ เริ่มคุยเลย!',
                'unread_count' => 0
            ]);
        }
    } else {
        $chatWithId = 0; // ไม่พบ user
    }
}

// ถ้าไม่ได้ระบุใครเลย และมีผู้ติดต่อ ให้เลือกคนแรกสุด
if (!$chatWithId && count($contacts) > 0) {
    $chatWithId = $contacts[0]['id'];
    $chatUser = getUserById($pdo, $chatWithId);

    // อัปเดตการอ่านของคนแรกด้วย
    $stmtUpdate = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?");
    $stmtUpdate->execute([$chatWithId, $currentUserId]);
}

require_once 'header.php';
?>

<!-- ลิงก์ไฟล์ CSS เพิ่มเติมเฉพาะหน้านี้ (แม้จะ link ใน header แล้ว แต่เพื่อความชัวร์ถ้าลืม) -->
<link rel="stylesheet" href="<?= SITE_URL ?>/css/messages.css">

<div class="messages-layout <?= $chatWithId ? 'chat-active' : '' ?>">

    <!-- ฝั่งซ้าย: รายชื่อคนคุย -->
    <div class="contacts-sidebar">
        <div class="contacts-header">
            <h2><i class="fas fa-comments"></i> ข้อความ</h2>
        </div>
        <div class="contacts-list">
            <?php if (empty($contacts)): ?>
                <div style="padding: 2rem; text-align: center; color: var(--text-muted); font-size: 0.9rem;">
                    <i class="fas fa-user-friends" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <p>ยังไม่มีประวัติการแชท</p>
                    <p style="margin-top: 0.5rem; font-size: 0.8rem;">ไปที่โปรไฟล์ของผู้ใช้เพื่อส่งข้อความแรก</p>
                </div>
            <?php else: ?>
                <?php foreach ($contacts as $c): ?>
                    <a href="<?= SITE_URL ?>/messages.php?user=<?= $c['id'] ?>"
                        class="contact-item <?= $chatWithId == $c['id'] ? 'active' : '' ?>">
                        <img src="<?= SITE_URL ?>/uploads/avatars/<?= sanitize($c['avatar']) ?>" class="contact-avatar"
                            onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($c['username']) ?>&background=8b5cf6&color=fff'">
                        <div class="contact-info">
                            <div class="contact-name">
                                <?= sanitize($c['username']) ?>
                                <?php if ($c['unread_count'] > 0): ?>
                                    <span class="unread-badge">
                                        <?= $c['unread_count'] ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="contact-last-msg">
                                <?= sanitize(mb_substr($c['last_message'], 0, 30)) ?>
                                <?= mb_strlen($c['last_message']) > 30 ? '...' : '' ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- ฝั่งขวา: กล่องแชท -->
    <div class="chat-area">
        <?php if ($chatWithId && $chatUser): ?>
            <!-- หัวแชท -->
            <div class="chat-header">
                <button class="mobile-back-btn" onclick="window.location.href='<?= SITE_URL ?>/messages.php'"><i
                        class="fas fa-arrow-left"></i></button>
                <img src="<?= SITE_URL ?>/uploads/avatars/<?= sanitize($chatUser['avatar']) ?>" class="chat-header-avatar"
                    onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($chatUser['username']) ?>&background=8b5cf6&color=fff'">
                <div class="chat-header-info">
                    <a href="<?= SITE_URL ?>/profile.php?id=<?= $chatUser['id'] ?>" class="chat-header-name">
                        <?= sanitize($chatUser['username']) ?>
                    </a>
                    <div style="font-size: 0.8rem; color: var(--text-muted);">
                        <?php if ($chatUser['roblox_username']): ?>
                            <i class="fas fa-gamepad"></i>
                            <?= sanitize($chatUser['roblox_username']) ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- กล่องข้อความ ดึงประวัติมาโชว์ตอนโหลด -->
            <div class="chat-messages" id="chatMessages">
                <?php
                // ดึงข้อความ 50 ข้อความล่าสุด (เรียงจากเก่าไปใหม่)
                $stmtMsg = $pdo->prepare("
                    SELECT * FROM (
                        SELECT * FROM messages 
                        WHERE (sender_id = ? AND receiver_id = ?) 
                           OR (sender_id = ? AND receiver_id = ?)
                        ORDER BY created_at DESC 
                        LIMIT 50
                    ) sub
                    ORDER BY created_at ASC
                ");
                $stmtMsg->execute([$currentUserId, $chatWithId, $chatWithId, $currentUserId]);
                $messages = $stmtMsg->fetchAll();

                $lastDate = '';
                foreach ($messages as $msg) {
                    $isMe = $msg['sender_id'] == $currentUserId;
                    $msgDate = date('Y-m-d', strtotime($msg['created_at']));
                    $msgTime = date('H:i', strtotime($msg['created_at']));

                    // แทรกวันที่ถ้าระยะเวลาห่างกันข้ามวัน
                    if ($msgDate != $lastDate) {
                        echo '<div style="text-align: center; margin: 1rem 0; font-size: 0.75rem; color: var(--text-muted); background: rgba(0,0,0,0.3); padding: 0.2rem 1rem; border-radius: 50px; align-self: center;">' . date('d/m/Y', strtotime($msgDate)) . '</div>';
                        $lastDate = $msgDate;
                    }
                    ?>
                    <div class="message-item <?= $isMe ? 'msg-out' : 'msg-in' ?>" data-id="<?= $msg['id'] ?>">
                        <div class="message-bubble">
                            <?= nl2br(sanitize($msg['message'])) ?>
                        </div>
                        <div class="message-time">
                            <?= $msgTime ?>
                        </div>
                    </div>
                <?php } ?>
            </div>

            <!-- ฟอร์มส่งข้อความ -->
            <div class="chat-input-area">
                <input type="text" id="msgInput" class="chat-input" placeholder="พิมพ์ข้อความที่นี่..." autocomplete="off"
                    onkeypress="handleKeyPress(event)">
                <button type="button" id="msgSendBtn" class="chat-send-btn" onclick="sendMessage()"><i
                        class="fas fa-paper-plane"></i></button>
            </div>

        <?php else: ?>
            <div class="chat-empty-state">
                <i class="far fa-comments"></i>
                <h2>ยินดีต้อนรับสู่กล่องข้อความ</h2>
                <p>เลือกประวัติแชทด้านซ้าย หรือไปที่หน้าโปรไฟล์ของคนอื่นเพื่อเริ่มคุย</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Scroll ไปล่างสุดของช่องแชท
    const chatMessages = document.getElementById('chatMessages');
    if (chatMessages) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    const currentUserId = <?= $_SESSION['user_id'] ?? 0 ?>;
    const chatWithId = <?= $chatWithId ?>;
    let lastMessageId = 0;

    // หา ID ของข้อความล่าสุดที่มีในหน้าจอ
    function updateLastMessageId() {
        if (chatMessages) {
            const msgs = chatMessages.querySelectorAll('.message-item');
            if (msgs.length > 0) {
                lastMessageId = msgs[msgs.length - 1].getAttribute('data-id') || 0;
            }
        }
    }
    updateLastMessageId();

    // ส่งข้อความ
    function sendMessage() {
        const input = document.getElementById('msgInput');
        const message = input.value.trim();
        if (!message || !chatWithId) return;

        const btn = document.getElementById('msgSendBtn');
        input.disabled = true;
        btn.disabled = true;

        fetch('<?= SITE_URL ?>/api/send_message.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `receiver_id=${chatWithId}&message=${encodeURIComponent(message)}`
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    input.value = '';
                    // โหลดข้อความใหม่ทันที
                    fetchNewMessages();
                } else {
                    alert(data.error || 'เกิดข้อผิดพลาดในการส่งข้อความ');
                }
            })
            .catch(err => console.error(err))
            .finally(() => {
                input.disabled = false;
                btn.disabled = false;
                input.focus();
            });
    }

    function handleKeyPress(e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    }

    // โหลดข้อความใหม่แบบ Long Polling หรือ Interval (ทุก 3 วิ)
    function fetchNewMessages() {
        if (!chatWithId || !chatMessages) return;

        fetch(`<?= SITE_URL ?>/api/get_messages.php?user_id=${chatWithId}&last_id=${lastMessageId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.messages.length > 0) {
                    let shouldScroll = chatMessages.scrollTop + chatMessages.clientHeight >= chatMessages.scrollHeight - 50;

                    data.messages.forEach(msg => {
                        const isMe = msg.sender_id == currentUserId;
                        const time = new Date(msg.created_at).toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' });

                        const div = document.createElement('div');
                        div.className = `message-item ${isMe ? 'msg-out' : 'msg-in'}`;
                        div.setAttribute('data-id', msg.id);

                        // แปลง \n เป็น <br> และป้องกัน XSS เล็กน้อย (ทาง API ควรจัดการมาแล้วส่วนหนึ่ง)
                        const safeMsg = msg.message.replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/\n/g, "<br>");

                        div.innerHTML = `
                    <div class="message-bubble">${safeMsg}</div>
                    <div class="message-time">${time}</div>
                `;
                        chatMessages.appendChild(div);
                        lastMessageId = msg.id; // อัปเดตอันล่าสุด
                    });

                    if (shouldScroll) {
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                    }
                }
            })
            .catch(err => console.error(err));
    }

    // ตรวจสอบข้อความใหม่ทุกๆ 3 วินาที
    if (chatWithId) {
        setInterval(fetchNewMessages, 3000);
    }
</script>

<?php require_once 'footer.php'; ?>