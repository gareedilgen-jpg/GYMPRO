<?php
if (!isLoggedIn()) {
    header('Location: /auth/login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT profile_photo, role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user_data = $stmt->fetch();
$profile_photo = $user_data['profile_photo'] ?? null;
$is_admin = ($user_data['role'] === 'admin');

$stmt = $pdo->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$_SESSION['user_id']]);
$unread_notifications = $stmt->fetch()['unread_count'];
?>
<header class="main-header">
    <div class="header-content">
        <div class="logo">
            <a href="/dashboard/index.php" style="display: flex; align-items: center; gap: 8px; text-decoration: none;">
                <img src="/assets/images/crossfit.png" alt="Logo" class="logo-icon" style="width: 24px; height: 24px;">
                <span style="font-weight: bold; font-size: 20px;">
                    <span style="color: #ffffff;">GYM</span><span style="color: #a3e635;">PRO</span>
                </span>
            </a>
        </div>
        
        <div style="display: flex; align-items: center; gap: 16px;">
            <?php if ($is_admin): ?>
                <a href="/admin/notifications.php" class="btn btn-sm btn-secondary" style="padding: 6px 12px; font-size: 12px; border-color: var(--primary); color: var(--primary);">
                    🛡️ Admin
                </a>
            <?php endif; ?>
            
            <div style="position: relative;">
                <button onclick="toggleNotifications()" style="background: transparent; border: none; cursor: pointer; padding: 8px; display: flex; align-items: center; justify-content: center;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--foreground);">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <?php if ($unread_notifications > 0): ?>
                        <span id="notif-badge" style="position: absolute; top: 0; right: 0; background: var(--destructive); color: white; font-size: 10px; font-weight: 700; width: 18px; height: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid var(--surface);"><?= $unread_notifications ?></span>
                    <?php endif; ?>
                </button>
                
                <div id="notifications-dropdown" style="display: none; position: absolute; top: 100%; right: 0; margin-top: 8px; background: var(--card); border: 1px solid var(--border); border-radius: var(--radius-lg); width: 320px; max-height: 400px; overflow-y: auto; box-shadow: var(--shadow-xl); z-index: 1000;">
                    <div style="padding: 16px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                        <h4 style="margin: 0; font-size: 16px; font-weight: 600;">NOTIFICHE</h4>
                        <?php if ($unread_notifications > 0): ?>
                            <button onclick="markAllAsRead()" style="background: transparent; border: none; color: var(--primary); font-size: 12px; cursor: pointer; font-weight: 600;">Segna tutte lette</button>
                        <?php endif; ?>
                    </div>
                    <div id="notifications-list">
                        <div style="padding: 20px; text-align: center; color: var(--muted-foreground); font-size: 13px;">Caricamento...</div>
                    </div>
                    <div style="padding: 12px; border-top: 1px solid var(--border); text-align: center;">
                        <a href="/notifications/index.php" style="color: var(--primary); font-size: 13px; font-weight: 600; text-decoration: none;">Vedi tutte →</a>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($profile_photo)): ?>
                <img src="<?= htmlspecialchars($profile_photo) ?>" alt="Profilo" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary); cursor: pointer;" onclick="window.location.href='/profile/index.php'">
            <?php else: ?>
                <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--surface-strong); display: flex; align-items: center; justify-content: center; font-size: 20px; cursor: pointer; border: 2px solid var(--primary);" onclick="window.location.href='/profile/index.php'">👤</div>
            <?php endif; ?>
        </div>
    </div>
</header>

<div id="notification-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 999;" onclick="closeNotifications()"></div>

<script>
function toggleNotifications() {
    const dropdown = document.getElementById('notifications-dropdown');
    const overlay = document.getElementById('notification-overlay');
    if (dropdown.style.display === 'none') {
        dropdown.style.display = 'block';
        overlay.style.display = 'block';
        loadNotifications();
    } else {
        closeNotifications();
    }
}

function closeNotifications() {
    document.getElementById('notifications-dropdown').style.display = 'none';
    document.getElementById('notification-overlay').style.display = 'none';
}

function loadNotifications() {
    fetch('/api/notifications.php?action=list&limit=5')
        .then(res => res.json())
        .then(data => {
            const list = document.getElementById('notifications-list');
            if (data.length === 0) {
                list.innerHTML = '<div style="padding: 20px; text-align: center; color: var(--muted-foreground); font-size: 13px;">Nessuna notifica</div>';
            } else {
                list.innerHTML = data.map(n => `
                    <div style="padding: 12px 16px; border-bottom: 1px solid var(--border); ${n.is_read ? '' : 'background: rgba(163, 230, 53, 0.05);'}">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                            <div style="flex: 1; font-size: 13px; color: var(--foreground); line-height: 1.4; ${n.is_read ? '' : 'font-weight: 600;'}">${n.message}</div>
                            <button onclick="deleteNotification(${n.id}, this)" style="background: transparent; border: none; color: var(--muted-foreground); cursor: pointer; padding: 4px; font-size: 16px;">×</button>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 11px; color: var(--muted-foreground);">${timeAgo(n.created_at)}</span>
                            ${!n.is_read ? `<button onclick="markAsRead(${n.id}, this)" style="background: transparent; border: none; color: var(--primary); font-size: 11px; cursor: pointer; font-weight: 600;">Segna letta</button>` : ''}
                        </div>
                    </div>
                `).join('');
            }
        })
        .catch(err => console.error('Errore:', err));
}

function markAsRead(id, btn) {
    fetch(`/api/notifications.php?action=mark_read&id=${id}`)
        .then(() => {
            btn.remove();
            updateBadge();
            const item = btn.closest('div[style*="padding"]');
            if (item) {
                item.style.background = 'transparent';
                const msgDiv = item.querySelector('div[style*="font-weight"]');
                if (msgDiv) msgDiv.style.fontWeight = 'normal';
            }
        });
}

function markAllAsRead() {
    fetch('/api/notifications.php?action=mark_all_read', { method: 'POST' })
        .then(() => location.reload());
}

function deleteNotification(id, btn) {
    if (confirm('Eliminare questa notifica?')) {
        fetch(`/api/notifications.php?action=delete&id=${id}`)
            .then(() => {
                btn.closest('div[style*="padding"]').remove();
                updateBadge();
            });
    }
}

function updateBadge() {
    fetch('/api/notifications.php?action=count_unread')
        .then(res => res.json())
        .then(data => {
            const badge = document.getElementById('notif-badge');
            if (data.count > 0) {
                if (!badge) {
                    const newBadge = document.createElement('span');
                    newBadge.id = 'notif-badge';
                    newBadge.style.cssText = 'position: absolute; top: 0; right: 0; background: var(--destructive); color: white; font-size: 10px; font-weight: 700; width: 18px; height: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid var(--surface);';
                    newBadge.textContent = data.count;
                    document.querySelector('button[onclick="toggleNotifications()"]').appendChild(newBadge);
                } else {
                    badge.textContent = data.count;
                }
            } else if (badge) {
                badge.remove();
            }
        });
}

function timeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    if (seconds < 60) return 'Adesso';
    if (seconds < 3600) return Math.floor(seconds / 60) + ' min fa';
    if (seconds < 86400) return Math.floor(seconds / 3600) + ' ore fa';
    return Math.floor(seconds / 86400) + ' giorni fa';
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeNotifications();
});
</script>