<?php
require_once '../config/database.php';
requireLogin();

$user_id = $_SESSION['user_id'];

if (isset($_GET['mark_read'])) {
    $nid = intval($_GET['mark_read']);
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$nid, $user_id]);
    header('Location: index.php');
    exit;
}

if (isset($_GET['delete'])) {
    $nid = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->execute([$nid, $user_id]);
    header('Location: index.php?deleted=1');
    exit;
}

if (isset($_GET['mark_all_read'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    header('Location: index.php');
    exit;
}

$page = intval($_GET['page'] ?? 1);
$per_page = 20;
$offset = ($page - 1) * $per_page;

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = ?");
$stmt->execute([$user_id]);
$total = $stmt->fetch()['total'];
$total_pages = ceil($total / $per_page);

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->execute([$user_id, $per_page, $offset]);
$notifications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifiche - GymPro</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="flex-between mb-2" style="margin-top: 16px;">
            <a href="/dashboard/index.php" class="btn btn-sm" style="background: var(--surface); color: var(--foreground);">← Dashboard</a>
            <h2 style="margin: 0; font-size: 20px;">🔔 Le tue Notifiche</h2>
            <a href="?mark_all_read=1" class="btn btn-sm btn-secondary" onclick="return confirm('Segnare tutte come lette?')">✅ Segna tutte lette</a>
        </div>
        
        <?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Notifica eliminata!</div><?php endif; ?>
        
        <?php if (empty($notifications)): ?>
            <div class="card">
                <div class="card-body text-center" style="padding: 60px 20px;">
                    <p style="font-size: 64px; margin-bottom: 16px;">🔕</p>
                    <h3>Nessuna notifica</h3>
                    <p class="text-muted">Quando l'amministratore pubblicherà aggiornamenti, li vedrai qui.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $n): ?>
                <div class="card" style="margin-bottom: 12px; <?= !$n['is_read'] ? 'border-left: 4px solid var(--primary);' : '' ?>">
                    <div class="card-body">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                            <p style="margin: 0; font-size: 14px; line-height: 1.5; color: var(--foreground); <?= !$n['is_read'] ? 'font-weight: 600;' : '' ?>">
                                <?= htmlspecialchars($n['message']) ?>
                            </p>
                            <?php if (!$n['is_read']): ?>
                                <span style="background: var(--primary); color: var(--primary-foreground); font-size: 10px; padding: 2px 8px; border-radius: 10px; font-weight: 700; white-space: nowrap;">NUOVA</span>
                            <?php endif; ?>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border);">
                            <span style="font-size: 12px; color: var(--muted-foreground);"><?= date('d/m/Y H:i', strtotime($n['created_at'])) ?></span>
                            <div style="display: flex; gap: 8px;">
                                <?php if ($n['link']): ?>
                                    <a href="<?= htmlspecialchars($n['link']) ?>" class="btn btn-sm btn-primary">Vai →</a>
                                <?php endif; ?>
                                <?php if (!$n['is_read']): ?>
                                    <a href="?mark_read=<?= $n['id'] ?>" class="btn btn-sm btn-secondary">Segna letta</a>
                                <?php endif; ?>
                                <a href="?delete=<?= $n['id'] ?>" class="btn btn-sm" style="background: transparent; color: var(--destructive); border: 1px solid var(--destructive);" onclick="return confirm('Eliminare questa notifica?')">🗑️</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if ($total_pages > 1): ?>
                <div style="display: flex; justify-content: center; gap: 8px; margin-top: 24px;">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?= $i ?>" class="btn btn-sm <?= $i == $page ? 'btn-primary' : 'btn-secondary' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <?php include '../includes/bottom-nav.php'; ?>
</body>
</html>