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

// Statistiche Globali
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_workouts = $pdo->query("SELECT COUNT(*) FROM workouts")->fetchColumn();
$total_sessions = $pdo->query("SELECT COUNT(*) FROM workout_sessions")->fetchColumn();
$total_volume = $pdo->query("SELECT COALESCE(SUM(total_volume), 0) FROM workout_sessions")->fetchColumn();

// Ultimi utenti registrati
$stmt = $pdo->query("SELECT id, full_name, email, created_at FROM users ORDER BY created_at DESC LIMIT 5");
$recent_users = $stmt->fetchAll();

// Sessioni recenti
$stmt = $pdo->query("
    SELECT ws.*, u.full_name, w.name as workout_name 
    FROM workout_sessions ws
    JOIN users u ON ws.user_id = u.id
    JOIN workouts w ON ws.workout_id = w.id
    ORDER BY ws.session_date DESC LIMIT 5
");
$recent_sessions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | GymPro</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="flex-between mb-2" style="margin-top: 16px;">
            <a href="/dashboard/index.php" class="btn btn-sm" style="background: var(--surface); color: var(--foreground);">← Dashboard Utente</a>
            <h2 style="margin: 0; font-size: 20px;">🛡️ Pannello Admin</h2>
        </div>
        
        <!-- Stats Grid -->
        <div class="stats-grid" style="grid-template-columns: repeat(2, 1fr); margin-bottom: 24px;">
            <div class="stat-card">
                <div class="stat-value"><?= $total_users ?></div>
                <div class="stat-label">Utenti Totali</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $total_workouts ?></div>
                <div class="stat-label">Schede Create</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $total_sessions ?></div>
                <div class="stat-label">Sessioni Completate</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= number_format($total_volume) ?></div>
                <div class="stat-label">Volume Totale (kg)</div>
            </div>
        </div>
        
        <!-- Ultime Attività -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <!-- Ultimi Utenti -->
            <div class="card">
                <div class="card-header"><h3>👥 Ultimi Utenti</h3></div>
                <div class="card-body" style="padding: 0;">
                    <?php foreach ($recent_users as $u): ?>
                        <div style="padding: 12px 16px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-weight: 600; font-size: 14px;"><?= htmlspecialchars($u['full_name']) ?></div>
                                <div style="font-size: 12px; color: var(--muted-foreground);"><?= htmlspecialchars($u['email']) ?></div>
                            </div>
                            <span style="font-size: 11px; color: var(--muted-foreground);"><?= date('d/m/Y', strtotime($u['created_at'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Sessioni Recenti -->
            <div class="card">
                <div class="card-header"><h3>🏋️ Sessioni Recenti</h3></div>
                <div class="card-body" style="padding: 0;">
                    <?php foreach ($recent_sessions as $s): ?>
                        <div style="padding: 12px 16px; border-bottom: 1px solid var(--border);">
                            <div style="font-weight: 600; font-size: 14px;"><?= htmlspecialchars($s['full_name']) ?></div>
                            <div style="font-size: 12px; color: var(--muted-foreground);"><?= htmlspecialchars($s['workout_name']) ?> • <?= date('d/m H:i', strtotime($s['session_date'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Link Rapidi Admin -->
        <div class="card mt-2">
            <div class="card-header"><h3>⚡ Azioni Rapide Admin</h3></div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
                    <a href="notifications.php" class="btn btn-secondary btn-block">📢 Invia Notifiche</a>
                    <a href="users.php" class="btn btn-secondary btn-block">👥 Gestisci Utenti</a>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/bottom-nav.php'; ?>
</body>
</html>