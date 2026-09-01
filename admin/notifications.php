<?php
require_once '../config/database.php';
requireLogin();

// VERIFICA ADMIN
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user_role = $stmt->fetch()['role'];

if ($user_role !== 'admin') { 
    header('Location: /dashboard/index.php'); 
    exit; 
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Invia notifica
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send') {
    $message = trim($_POST['message'] ?? '');
    $target = $_POST['target'] ?? 'all'; // 'all' o 'single'
    $target_user_id = intval($_POST['target_user_id'] ?? 0);
    $link = trim($_POST['link'] ?? '');
    
    if (empty($message)) {
        $error = 'Il messaggio è obbligatorio';
    } else {
        try {
            if ($target === 'all') {
                // Invia a tutti gli utenti
                $stmt = $pdo->query("SELECT id FROM users");
                $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, link, created_by) VALUES (?, ?, ?, ?)");
                
                foreach ($users as $uid) {
                    $stmt->execute([$uid, $message, $link, $user_id]);
                }
                
                $success = "Notifica inviata a " . count($users) . " utenti!";
            } else {
                // Invia a singolo utente
                if ($target_user_id <= 0) {
                    $error = 'Seleziona un utente valido';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, link, created_by) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$target_user_id, $message, $link, $user_id]);
                    $success = "Notifica inviata con successo!";
                }
            }
        } catch (Exception $e) {
            $error = 'Errore: ' . $e->getMessage();
        }
    }
}

// Elimina singola notifica
if (isset($_GET['delete_notification'])) {
    $notif_id = intval($_GET['delete_notification']);
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ?");
    $stmt->execute([$notif_id]);
    $success = "Notifica eliminata!";
}

// Elimina tutte le notifiche lette
if (isset($_POST['action']) && $_POST['action'] === 'delete_read') {
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE is_read = 1");
    $stmt->execute();
    $success = "Notifiche lette eliminate!";
}

// Elimina tutte le notifiche di un utente specifico
if (isset($_GET['clear_user_notifications'])) {
    $uid = intval($_GET['clear_user_notifications']);
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ?");
    $stmt->execute([$uid]);
    $success = "Tutte le notifiche dell'utente eliminate!";
}

// Recupera lista utenti per dropdown
$stmt = $pdo->query("SELECT id, full_name, email, role FROM users ORDER BY full_name");
$users = $stmt->fetchAll();

// Statistiche utenti
$total_users = count($users);
$admin_count = 0;
foreach ($users as $u) {
    if ($u['role'] === 'admin') $admin_count++;
}

// Recupera tutte le notifiche per la gestione
$stmt = $pdo->query("
    SELECT n.*, u.full_name as user_name, u.email as user_email,
           CASE WHEN n.created_by IS NOT NULL THEN (SELECT full_name FROM users WHERE id = n.created_by) ELSE 'Sistema' END as creator_name
    FROM notifications n
    LEFT JOIN users u ON n.user_id = u.id
    ORDER BY n.created_at DESC
    LIMIT 100
");
$notifications = $stmt->fetchAll();

// Conta notifiche non lette
$stmt = $pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0");
$unread_count = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Gestione Notifiche e Utenti | GymPro</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }
        .stat-box {
            background: var(--surface);
            padding: 16px;
            border-radius: var(--radius-md);
            text-align: center;
        }
        .stat-box .value {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary);
        }
        .stat-box .label {
            font-size: 12px;
            color: var(--muted-foreground);
            margin-top: 4px;
        }
        .notification-item {
            padding: 12px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .notification-item:last-child {
            border-bottom: none;
        }
        .notification-content {
            flex: 1;
        }
        .notification-message {
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 4px;
        }
        .notification-meta {
            font-size: 11px;
            color: var(--muted-foreground);
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-unread {
            background: var(--primary);
            color: white;
        }
        .badge-read {
            background: var(--surface-strong);
            color: var(--muted-foreground);
        }
        .user-row {
            padding: 12px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .user-info {
            flex: 1;
        }
        .user-name {
            font-weight: 600;
            font-size: 14px;
        }
        .user-email {
            font-size: 12px;
            color: var(--muted-foreground);
        }
        .action-buttons {
            display: flex;
            gap: 8px;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="flex-between mb-2" style="margin-top: 16px;">
            <a href="/dashboard/index.php" class="btn btn-sm" style="background: var(--surface); color: var(--foreground);">← Dashboard</a>
            <h2 style="margin: 0; font-size: 20px;">🛡️ Pannello Admin</h2>
        </div>
        
        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error\"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        
        <!-- Statistiche -->
        <div class="admin-stats">
            <div class="stat-box">
                <div class="value"><?= $total_users ?></div>
                <div class="label">Utenti Totali</div>
            </div>
            <div class="stat-box">
                <div class="value"><?= $admin_count ?></div>
                <div class="label">Amministratori</div>
            </div>
            <div class="stat-box">
                <div class="value"><?= $unread_count ?></div>
                <div class="label">Notifiche Non Letta</div>
            </div>
            <div class="stat-box">
                <div class="value"><?= count($notifications) ?></div>
                <div class="label">Notifiche Totali</div>
            </div>
        </div>
        
        <!-- Invio Notifiche -->
        <div class="card">
            <div class="card-header">
                <h3>📢 Invia Nuova Notifica</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label>Messaggio *</label>
                        <textarea name="message" class="form-control" rows="4" required placeholder="Scrivi qui l'aggiornamento per gli utenti..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Link opzionale</label>
                        <input type="text" name="link" class="form-control" placeholder="/workouts/view.php?id=1">
                        <small class="text-muted">URL interno a cui reindirizzare l'utente quando clicca la notifica</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Destinatari</label>
                        <select name="target" id="target-select" class="form-control" onchange="toggleTargetUser()">
                            <option value="all">Tutti gli utenti</option>
                            <option value="single">Singolo utente</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="target-user-group" style="display: none;">
                        <label>Seleziona Utente</label>
                        <select name="target_user_id" class="form-control">
                            <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?> (<?= htmlspecialchars($u['email']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" name="action" value="send" class="btn btn-primary btn-block" style="padding: 14px;">📤 Invia Notifica</button>
                </form>
            </div>
        </div>
        
        <!-- Gestione Notifiche Esistenti -->
        <div class="card mt-2">
            <div class="card-header flex-between">
                <h3>📋 Gestione Notifiche</h3>
                <form method="POST" style="display: inline;" onsubmit="return confirm('Eliminare tutte le notifiche lette?')">
                    <button type="submit" name="action" value="delete_read" class="btn btn-sm btn-danger">🗑️ Elimina Letta</button>
                </form>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php if (count($notifications) > 0): ?>
                    <div style="max-height: 400px; overflow-y: auto;">
                        <?php foreach ($notifications as $n): ?>
                            <div class="notification-item">
                                <div class="notification-content">
                                    <div class="notification-message">
                                        <?= htmlspecialchars($n['message']) ?>
                                        <?php if ($n['is_read']): ?>
                                            <span class="badge badge-read">Letta</span>
                                        <?php else: ?>
                                            <span class="badge badge-unread">Nuova</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="notification-meta">
                                        Utente: <?= htmlspecialchars($n['user_name']) ?> (<?= htmlspecialchars($n['user_email']) ?>) • 
                                        Inviata da: <?= htmlspecialchars($n['creator_name']) ?> • 
                                        <?= date('d/m/Y H:i', strtotime($n['created_at'])) ?>
                                    </div>
                                </div>
                                <div class="action-buttons">
                                    <?php if (!empty($n['link'])): ?>
                                        <a href="<?= htmlspecialchars($n['link']) ?>" class="btn btn-sm" target="_blank">🔗</a>
                                    <?php endif; ?>
                                    <a href="?delete_notification=<?= $n['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Eliminare questa notifica?')">🗑️</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="padding: 32px; text-align: center; color: var(--muted-foreground);">
                        Nessuna notifica presente
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Gestione Utenti Rapida -->
        <div class="card mt-2">
            <div class="card-header">
                <h3>👥 Gestione Utenti Rapida</h3>
            </div>
            <div class="card-body" style="padding: 0;">
                <div style="max-height: 300px; overflow-y: auto;">
                    <?php foreach ($users as $u): ?>
                        <div class="user-row">
                            <div class="user-info">
                                <div class="user-name">
                                    <?= htmlspecialchars($u['full_name']) ?>
                                    <?php if ($u['role'] === 'admin'): ?>
                                        <span class="badge badge-unread">Admin</span>
                                    <?php endif; ?>
                                </div>
                                <div class="user-email"><?= htmlspecialchars($u['email']) ?></div>
                            </div>
                            <div class="action-buttons">
                                <a href="?clear_user_notifications=<?= $u['id'] ?>" class="btn btn-sm" onclick="return confirm('Eliminare tutte le notifiche di questo utente?')">🔔 Clear</a>
                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                    <a href="users.php?delete=<?= $u['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Eliminare definitivamente questo utente?')">🗑️</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div style="padding: 12px; text-align: center;">
                    <a href="users.php" class="btn btn-primary btn-sm">Gestione Completa Utenti →</a>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/bottom-nav.php'; ?>
    <script>
    function toggleTargetUser() {
        const select = document.getElementById('target-select');
        const group = document.getElementById('target-user-group');
        group.style.display = select.value === 'single' ? 'block' : 'none';
    }
    </script>
</body>
</html>