<?php
require_once '../config/database.php';
requireLogin();

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT w.*, COUNT(we.id) as exercise_count
    FROM workouts w
    LEFT JOIN workout_exercises we ON w.id = we.workout_id
    WHERE w.user_id = ?
    GROUP BY w.id
    ORDER BY FIELD(w.day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')
");
$stmt->execute([$user_id]);
$workouts = $stmt->fetchAll();

$days_italian = [
    'monday' => 'Lunedì', 'tuesday' => 'Martedì', 'wednesday' => 'Mercoledì',
    'thursday' => 'Giovedì', 'friday' => 'Venerdì', 'saturday' => 'Sabato', 'sunday' => 'Domenica'
];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Le tue Schede - GymPro</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .page-header {
            margin-top: 16px;
            margin-bottom: 20px;
        }
        .page-title {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 32px;
            font-weight: 700;
            color: var(--foreground);
            text-transform: uppercase;
            margin: 0 0 16px 0;
            letter-spacing: 0.02em;
        }
        .action-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
        .workout-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            margin-bottom: 16px;
            overflow: hidden;
        }
        .workout-header {
            padding: 16px 20px;
        }
        .workout-name {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 20px;
            font-weight: 700;
            color: var(--foreground);
            margin: 0 0 6px 0;
            text-transform: uppercase;
        }
        .workout-meta {
            font-size: 13px;
            color: var(--muted-foreground);
        }
        .workout-body {
            padding: 0 20px 20px 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-active { background: var(--primary); color: var(--primary-foreground); }
        .status-inactive { background: var(--surface-strong); color: var(--muted-foreground); }
        .workout-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-top: 8px;
            padding-top: 16px;
            border-top: 1px solid var(--border);
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <!-- HEADER: Titolo SOPRA, pulsanti SOTTO -->
        <div class="page-header">
            <h1 class="page-title">Le tue Schede</h1>
            
            <div class="action-bar">
                <a href="../dashboard/index.php" class="btn btn-sm" style="background: var(--surface); color: var(--foreground);">
                    ← Dashboard
                </a>
                <a href="history.php" class="btn btn-sm btn-secondary">
                    📜 Storico
                </a>
                <a href="../exercises/archive.php" class="btn btn-sm btn-secondary">
                    📚 Archivio
                </a>
                <a href="create.php" class="btn btn-sm btn-primary">
                    + Nuova
                </a>
            </div>
        </div>
        
        <?php if (isset($_GET['created'])): ?>
            <div class="alert alert-success">Scheda creata con successo!</div>
        <?php endif; ?>
        
        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success">Scheda aggiornata!</div>
        <?php endif; ?>
        
        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success">Scheda eliminata!</div>
        <?php endif; ?>
        
        <?php if (empty($workouts)): ?>
            <div class="card">
                <div class="card-body text-center" style="padding: 60px 20px;">
                    <p style="font-size: 64px; margin-bottom: 16px;">💪</p>
                    <h3 style="margin-bottom: 8px;">Nessun allenamento</h3>
                    <p class="text-muted" style="margin-bottom: 24px;">Crea la tua prima scheda o importa dall'archivio esercizi</p>
                    <a href="create.php" class="btn btn-primary">Crea Allenamento</a>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($workouts as $workout): ?>
                <div class="workout-card">
                    <div class="workout-header">
                        <h2 class="workout-name"><?= htmlspecialchars($workout['name']) ?></h2>
                        <div class="workout-meta">
                            <?= $days_italian[$workout['day_of_week']] ?? 'Giorno libero' ?> • 
                            <?= $workout['exercise_count'] ?> esercizi • 
                            <?= $workout['duration_minutes'] ?> min
                        </div>
                    </div>
                    
                    <div class="workout-body">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span class="status-badge <?= $workout['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                <?= $workout['is_active'] ? 'Attivo' : 'Inattivo' ?>
                            </span>
                            <a href="view.php?id=<?= $workout['id'] ?>" class="btn btn-sm btn-primary">
                                ▶️ Vedi
                            </a>
                        </div>
                        
                        <div class="workout-actions">
                            <a href="edit.php?id=<?= $workout['id'] ?>" class="btn btn-sm btn-secondary">
                                ✏️ Modifica
                            </a>
                            <a href="delete.php?id=<?= $workout['id'] ?>" 
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('Sei sicuro di voler eliminare questa scheda?')">
                                ️ Elimina
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <?php include '../includes/bottom-nav.php'; ?>
</body>
</html>