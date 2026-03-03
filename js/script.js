// ===== SITE URL =====
const SITE_URL = '/Final';

// ===== Navbar Toggle (Mobile) =====
document.addEventListener('DOMContentLoaded', () => {
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');

    if (navToggle && navMenu) {
        navToggle.addEventListener('click', () => {
            navToggle.classList.toggle('active');
            navMenu.classList.toggle('active');
        });

        // ปิดเมนูเมื่อคลิกลิงก์
        navMenu.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                navToggle.classList.remove('active');
                navMenu.classList.remove('active');
            });
        });
    }
});

// ===== Toggle Like (AJAX) =====
async function toggleLike(postId, btn) {
    try {
        const formData = new FormData();
        formData.append('post_id', postId);

        const response = await fetch(`${SITE_URL}/api/like.php`, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.error) {
            alert(data.error);
            return;
        }

        // อัปเดต UI
        const icon = btn.querySelector('i');
        const countSpan = btn.querySelector('.like-count');

        if (data.liked) {
            btn.classList.add('liked');
            icon.classList.remove('far');
            icon.classList.add('fas');
            icon.style.animation = 'pulse 0.4s ease';
            setTimeout(() => icon.style.animation = '', 400);
        } else {
            btn.classList.remove('liked');
            icon.classList.remove('fas');
            icon.classList.add('far');
        }

        countSpan.textContent = data.count;

    } catch (error) {
        console.error('Like error:', error);
    }
}

// ===== Submit Comment (AJAX) =====
async function submitComment(postId) {
    const input = document.getElementById('commentInput');
    const content = input.value.trim();

    if (!content) {
        input.focus();
        return;
    }

    try {
        const formData = new FormData();
        formData.append('post_id', postId);
        formData.append('content', content);

        const response = await fetch(`${SITE_URL}/api/comment.php`, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.error) {
            alert(data.error);
            return;
        }

        if (data.success) {
            const c = data.comment;
            const avatarUrl = `${SITE_URL}/uploads/avatars/${c.avatar}`;
            const fallbackAvatar = `https://ui-avatars.com/api/?name=${encodeURIComponent(c.username)}&background=8b5cf6&color=fff`;

            const commentHTML = `
                <div class="comment-item" style="animation: fadeIn 0.3s ease;">
                    <a href="${SITE_URL}/profile.php?id=${c.user_id}">
                        <img src="${avatarUrl}" alt="" class="comment-avatar"
                             onerror="this.src='${fallbackAvatar}'">
                    </a>
                    <div class="comment-body">
                        <a href="${SITE_URL}/profile.php?id=${c.user_id}" class="comment-user">${c.username}</a>
                        <span class="comment-time">${c.time}</span>
                        <p class="comment-text">${c.content.replace(/\n/g, '<br>')}</p>
                    </div>
                </div>
            `;

            const commentList = document.getElementById('commentList');
            commentList.insertAdjacentHTML('beforeend', commentHTML);

            // อัปเดตจำนวนคอมเมนต์
            const countEl = document.getElementById('comment-count');
            if (countEl) {
                countEl.textContent = parseInt(countEl.textContent) + 1;
            }

            input.value = '';

            // เลื่อนไปยังคอมเมนต์ใหม่
            commentList.lastElementChild.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

    } catch (error) {
        console.error('Comment error:', error);
    }
}

// ===== Delete Post (AJAX) =====
async function deletePost(postId, redirectAfter = false) {
    if (!confirm('ต้องการลบโพสต์นี้จริงหรือไม่?')) return;

    try {
        const formData = new FormData();
        formData.append('post_id', postId);

        const response = await fetch(`${SITE_URL}/api/delete_post.php`, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.error) {
            alert(data.error);
            return;
        }

        if (data.success) {
            if (redirectAfter) {
                window.location.href = `${SITE_URL}/`;
            } else {
                // ลบการ์ดจากหน้า
                const postCard = document.getElementById(`post-${postId}`);
                if (postCard) {
                    postCard.style.transition = 'all 0.3s ease';
                    postCard.style.opacity = '0';
                    postCard.style.transform = 'scale(0.95)';
                    setTimeout(() => postCard.remove(), 300);
                }
            }
        }

    } catch (error) {
        console.error('Delete error:', error);
    }
}

// ===== Enter key สำหรับส่งคอมเมนต์ =====
document.addEventListener('DOMContentLoaded', () => {
    const commentInput = document.getElementById('commentInput');
    if (commentInput) {
        commentInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                const postId = commentInput.closest('.comments-section')
                    ?.querySelector('[onclick*="submitComment"]')
                    ?.getAttribute('onclick')
                    ?.match(/\d+/)?.[0];
                if (postId) submitComment(parseInt(postId));
            }
        });
    }
});
