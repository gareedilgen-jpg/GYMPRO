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

// Elimina utente
if (isset($_GET['delete'])) {
    $uid = intval($_GET['delete']);
    if ($uid != $_SESSION['user_id']) { // Non permettere di eliminare se stessi
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$uid]);
        header('Location: users.php?deleted=1');
        exit;
    }
}

// Recupera tutti gli utenti
$stmt = $pdo->query("
    SELECT u.*, up.training_level, up.goal, up.weight,
           (SELECT COUNT(*) FROM workout_sessions WHERE user_id = u.id) as session_count
    FROM users u
    LEFT JOIN user_profiles up ON u.id = up.user_id
    ORDER BY u.created_at DESC
");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Utenti | GymPro Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="flex-between mb-2" style="margin-top: 16px;">
            <a href="index.php" class="btn btn-sm" style="background: var(--surface); color: var(--foreground);">← Admin Dashboard</a>
            <h2 style="margin: 0; font-size: 20px;">👥 Gestione Utenti</h2>
        </div>
        
        <?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Utente eliminato!</div><?php endif; ?>
        
        <div class="card">
            <div class="card-body" style="padding: 0;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--border);">
                            <th style="text-align: left; padding: 12px 16px; font-size: 11px; text-transform: uppercase; color: var(--muted-foreground);">Utente</th>
                            <th style="text-align: left; padding: 12px 16px; font-size: 11px; text-transform: uppercase; color: var(--muted-foreground);">Livello</th>
                            <th style="text-align: left; padding: 12px 16px; font-size: 11px; text-transform: uppercase; color: var(--muted-foreground);">Sessioni</th>
                            <th style="text-align: right; padding: 12px 16px; font-size: 11px; text-transform: uppercase; color: var(--muted-foreground);">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 12px 16px;">
                                    <div style="font-weight: 600; font-size: 14px;"><?= htmlspecialchars($u['full_name']) ?></div>
                                    <div style="font-size: 12px; color: var(--muted-foreground);"><?= htmlspecialchars($u['email']) ?></div>
                                </td>
                                <td style="padding: 12px 16px;">
                                    <span style="font-size: 12px; background: var(--surface-strong); padding: 4px 8px; border-radius: 4px;">
                                        <?= ucfirst($u['training_level'] ?? 'N/A') ?>
                                    </span>
                                </td>
                                <td style="padding: 12px 16px; font-size: 14px;"><?= $u['session_count'] ?></td>
                                <td style="padding: 12px 16px; text-align: right;">
                                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                        <a href="?delete=<?= $u['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Eliminare definitivamente questo utente?')">🗑️</a>
                                    <?php else: ?>
                                        <span style="font-size: 12px; color: var(--muted-foreground);">(Tu)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <?php include '../includes/bottom-nav.php'; ?>
</body>
</html>