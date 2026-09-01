<?php
require_once '../config/database.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$notif_id = intval($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE id = ? AND user_id = ?");
$stmt->execute([$notif_id, $user_id]);
$notification = $stmt->fetch();

if (!$notification) {
    header('Location: /notifications/index.php');
    exit;
}

// Segna come letta automaticamente quando viene visualizzata
if (!$notification['is_read']) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
    $stmt->execute([$notif_id]);
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifica - GymPro</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="flex-between mb-2" style="margin-top: 16px;">
            <a href="/notifications/index.php" class="btn btn-sm" style="background: var(--surface); color: var(--foreground);">← Tutte le notifiche</a>
            <h2 style="margin: 0; font-size: 20px;">Dettaglio Notifica</h2>
        </div>
        
        <div class="card">
            <div class="card-body" style="padding: 24px;">
                <p style="font-size: 16px; line-height: 1.6; color: var(--foreground); margin-bottom: 24px;">
                    <?= nl2br(htmlspecialchars($notification['message'])) ?>
                </p>
                
                <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 16px; border-top: 1px solid var(--border);">
                    <span style="font-size: 13px; color: var(--muted-foreground);">Ricevuta il <?= date('d/m/Y alle H:i', strtotime($notification['created_at'])) ?></span>
                    <div style="display: flex; gap: 8px;">
                        <?php if ($notification['link']): ?>
                            <a href="<?= htmlspecialchars($notification['link']) ?>" class="btn btn-primary">Vai al contenuto →</a>
                        <?php endif; ?>
                        <a href="?delete=<?= $notification['id'] ?>" class="btn btn-danger" onclick="return confirm('Eliminare questa notifica?')">🗑️ Elimina</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/bottom-nav.php'; ?>
</body>
</html>