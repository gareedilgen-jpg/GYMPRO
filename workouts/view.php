<?php
require_once '../config/database.php';
requireLogin();

error_reporting(E_ALL);
ini_set('display_errors', 1);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$user_id = $_SESSION['user_id'];
$workout_id = intval($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM workouts WHERE id = ? AND user_id = ?");
$stmt->execute([$workout_id, $user_id]);
$workout = $stmt->fetch();

if (!$workout) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT we.*, e.name as exercise_name, e.category, e.instructions, e.image_url
    FROM workout_exercises we
    JOIN exercises e ON we.exercise_id = e.id
    WHERE we.workout_id = ?
    ORDER BY we.order_num
");
$stmt->execute([$workout_id]);
$workout_exercises = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM workout_warmups WHERE workout_id = ? ORDER BY order_num");
$stmt->execute([$workout_id]);
$warmups = $stmt->fetchAll();

$days_italian = [
    'monday' => 'Lunedì', 'tuesday' => 'Martedì', 'wednesday' => 'Mercoledì',
    'thursday' => 'Giovedì', 'friday' => 'Venerdì', 'saturday' => 'Sabato', 'sunday' => 'Domenica'
];

$categories_map = [
    'chest' => 'Petto', 'back' => 'Schiena', 'legs' => 'Gambe',
    'shoulders' => 'Spalle', 'arms' => 'Braccia', 'core' => 'Core', 'cardio' => 'Cardio'
];

$error = '';

// ============================================
// RECUPERA SESSIONE ATTIVA E DATI SERIE (PRIMA DEL POST!)
// ============================================
$session_id = intval($_GET['session'] ?? 0);
$active_session = null;
$saved_sets = [];
$exercise_completion = [];

if ($session_id === 0) {
    $stmt = $pdo->prepare("
        SELECT * FROM workout_sessions 
        WHERE user_id = ? AND workout_id = ? AND status = 'in_progress'
        ORDER BY session_date DESC LIMIT 1
    ");
    $stmt->execute([$user_id, $workout_id]);
    $active_session = $stmt->fetch();
    if ($active_session) $session_id = $active_session['id'];
} elseif ($session_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM workout_sessions WHERE id = ? AND user_id = ?");
    $stmt->execute([$session_id, $user_id]);
    $active_session = $stmt->fetch();
}

// Carica serie salvate ORA così sono disponibili per la validazione nel POST
if ($active_session) {
    $stmt = $pdo->prepare("SELECT * FROM workout_sets WHERE session_id = ? ORDER BY exercise_id, set_number");
    $stmt->execute([$active_session['id']]);
    while ($row = $stmt->fetch()) {
        $saved_sets[$row['exercise_id']][$row['set_number']] = $row;
    }
    
    foreach ($workout_exercises as $ex) {
        $ex_sets = $saved_sets[$ex['exercise_id']] ?? [];
        $total_sets = $ex['sets'];
        $completed_sets = 0;
        foreach ($ex_sets as $s) if ($s['completed']) $completed_sets++;
        
        $exercise_completion[$ex['exercise_id']] = [
            'completed_sets' => $completed_sets,
            'total_sets' => $total_sets,
            'is_fully_done' => $completed_sets >= $total_sets
        ];
    }
}

$weight_history = [];
foreach ($workout_exercises as $ex) {
    $stmt = $pdo->prepare("
        SELECT ws.weight, ws.reps, ws.set_number, wsess.session_date
        FROM workout_sets ws
        JOIN workout_sessions wsess ON ws.session_id = wsess.id
        WHERE wsess.user_id = ? AND ws.exercise_id = ? AND ws.completed = 1
        ORDER BY wsess.session_date DESC, ws.set_number ASC LIMIT 15
    ");
    $stmt->execute([$user_id, $ex['exercise_id']]);
    $weight_history[$ex['exercise_id']] = $stmt->fetchAll();
}

$completed_exercises_count = 0;
foreach ($exercise_completion as $ec) if ($ec['is_fully_done']) $completed_exercises_count++;
$total_exercises = count($workout_exercises);
$global_progress_pct = $total_exercises > 0 ? ($completed_exercises_count / $total_exercises) * 100 : 0;

// ============================================
// GESTIONE POST
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['start_session'])) {
        $duration = intval($_POST['duration'] ?? $workout['duration_minutes']);
        
        $stmt = $pdo->prepare("
            INSERT INTO workout_sessions (user_id, workout_id, session_date, duration_minutes, status, total_exercises_count) 
            VALUES (?, ?, NOW(), ?, 'in_progress', ?)
        ");
        $stmt->execute([$user_id, $workout_id, $duration, count($workout_exercises)]);
        $new_session_id = $pdo->lastInsertId();
        
        header('Location: view.php?id=' . $workout_id . '&session=' . $new_session_id);
        exit;
    }
    
    if (isset($_POST['save_single_exercise'])) {
        $session_id = intval($_POST['session_id'] ?? 0);
        $exercise_id = intval($_POST['exercise_id'] ?? 0);
        
        if ($session_id > 0 && $exercise_id > 0) {
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("DELETE FROM workout_sets WHERE session_id = ? AND exercise_id = ?");
                $stmt->execute([$session_id, $exercise_id]);
                
                if (isset($_POST['sets']) && is_array($_POST['sets'])) {
                    foreach ($_POST['sets'] as $set_num => $set_data) {
                        $weight = floatval($set_data['weight'] ?? 0);
                        $reps = intval($set_data['reps'] ?? 0);
                        $completed = isset($set_data['completed']) ? 1 : 0;
                        
                        $stmt = $pdo->prepare("
                            INSERT INTO workout_sets (session_id, exercise_id, set_number, weight, reps, completed)
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([$session_id, $exercise_id, $set_num, $weight, $reps, $completed]);
                    }
                }
                
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as cnt FROM (
                        SELECT ws.exercise_id, 
                               SUM(CASE WHEN ws.completed = 1 THEN 1 ELSE 0 END) as done_sets,
                               (SELECT we.sets FROM workout_exercises we WHERE we.workout_id = ? AND we.exercise_id = ws.exercise_id) as total_sets
                        FROM workout_sets ws
                        WHERE ws.session_id = ?
                        GROUP BY ws.exercise_id
                        HAVING done_sets >= total_sets
                    ) t
                ");
                $stmt->execute([$workout_id, $session_id]);
                $completed_count = $stmt->fetchColumn() ?: 0;
                
                $stmt = $pdo->prepare("UPDATE workout_sessions SET completed_exercises_count = ?, total_exercises_count = ? WHERE id = ?");
                $stmt->execute([$completed_count, count($workout_exercises), $session_id]);
                
                $pdo->commit();
                header('Location: view.php?id=' . $workout_id . '&session=' . $session_id . '&saved=' . $exercise_id);
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Errore salvataggio: ' . $e->getMessage();
            }
        }
    }
    
    // Completa sessione - LOGICA LOCALE ROBUSTA
    if (isset($_POST['complete_session'])) {
        $session_id = intval($_POST['session_id'] ?? 0);
        $duration = intval($_POST['duration'] ?? 0);
        $notes = trim($_POST['session_notes'] ?? '');
        $is_partial = isset($_POST['is_partial']) ? 1 : 0;
        
        if ($session_id > 0) {
            try {
                if (!$is_partial) {
                    // Validazione locale usando $saved_sets (già caricato)
                    $missing_exercises = [];
                    
                    foreach ($workout_exercises as $ex) {
                        $ex_sets_in_db = $saved_sets[$ex['exercise_id']] ?? [];
                        $completed_in_db = 0;
                        
                        foreach ($ex_sets_in_db as $set) {
                            if ($set['completed']) $completed_in_db++;
                        }
                        
                        if ($completed_in_db < $ex['sets']) {
                            $missing_exercises[] = htmlspecialchars($ex['exercise_name']) . 
                                " (" . $completed_in_db . "/" . $ex['sets'] . " serie)";
                        }
                    }
                    
                    if (!empty($missing_exercises)) {
                        $error = "<strong>Impossibile completare totalmente.</strong><br>Esercizi incompleti:<br>• " . 
                                 implode("<br>• ", array_slice($missing_exercises, 0, 5));
                        if (count($missing_exercises) > 5) {
                            $error .= "<br>...e altri " . (count($missing_exercises) - 5) . " esercizi.";
                        }
                        $error .= "<br><br>Usa 'Completa Parziale' oppure torna indietro e finisci le serie.";
                    } else {
                        $stmt = $pdo->prepare("
                            UPDATE workout_sessions 
                            SET duration_minutes = ?, notes = ?, status = 'completed', is_partial = 0
                            WHERE id = ? AND user_id = ?
                        ");
                        $stmt->execute([$duration, $notes, $session_id, $user_id]);
                        
                        if ($stmt->rowCount() > 0) {
                            header('Location: index.php?completed=1');
                            exit;
                        } else {
                            $error = "Errore DB: La sessione non è stata aggiornata.";
                        }
                    }
                } else {
                    $final_notes = $notes;
                    if (!empty($_POST['partial_reason'])) {
                        $final_notes .= "\n\n[PARZIALE: " . trim($_POST['partial_reason']) . "]";
                    }
                    
                    $stmt = $pdo->prepare("
                        UPDATE workout_sessions 
                        SET duration_minutes = ?, notes = ?, status = 'partial', is_partial = 1
                        WHERE id = ? AND user_id = ?
                    ");
                    $stmt->execute([$duration, $final_notes, $session_id, $user_id]);
                    
                    if ($stmt->rowCount() > 0) {
                        header('Location: index.php?completed=1');
                        exit;
                    } else {
                        $error = "Errore DB: Impossibile salvare la sessione parziale.";
                    }
                }
            } catch (PDOException $e) {
                $error = "ERRORE SQL: " . $e->getMessage();
            }
        } else {
            $error = "Errore: ID sessione non valido.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($workout['name']) ?> - GymPro</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .set-row { display: grid; grid-template-columns: 40px 1fr 1fr 60px; gap: 8px; align-items: center; padding: 8px; background: var(--surface-strong); border-radius: var(--radius-md); margin-bottom: 6px; }
        .set-row.completed { background: rgba(163, 230, 53, 0.15); border-left: 3px solid var(--primary); }
        .set-number { font-family: 'Barlow Condensed', sans-serif; font-size: 18px; font-weight: 700; color: var(--primary); text-align: center; }
        .set-row.completed .set-number { color: var(--success); }
        .set-input { padding: 8px; background: var(--background); border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--foreground); font-size: 14px; text-align: center; width: 100%; }
        .set-input:focus { outline: none; border-color: var(--primary); }
        .set-checkbox { width: 24px; height: 24px; cursor: pointer; accent-color: var(--primary); }
        .progress-indicator { display: flex; align-items: center; gap: 8px; margin-top: 12px; padding: 12px; background: var(--surface-strong); border-radius: var(--radius-md); }
        .progress-bar { flex: 1; height: 8px; background: var(--background); border-radius: 4px; overflow: hidden; }
        .progress-fill { height: 100%; background: var(--primary); transition: width 0.3s ease; }
        .progress-text { font-size: 12px; font-weight: 600; color: var(--foreground); min-width: 60px; text-align: right; }
        .history-item { padding: 8px 12px; background: var(--surface-strong); border-radius: var(--radius-sm); margin-bottom: 6px; font-size: 13px; display: flex; justify-content: space-between; align-items: center; }
        .history-date { color: var(--muted-foreground); font-size: 11px; }
        .history-weight { font-family: 'Barlow Condensed', sans-serif; font-size: 16px; font-weight: 700; color: var(--primary); }
        .timer-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.9); z-index: 9999; align-items: center; justify-content: center; flex-direction: column; }
        .timer-overlay.active { display: flex; }
        .timer-display-overlay { font-family: 'Barlow Condensed', sans-serif; font-size: 100px; font-weight: 700; color: var(--primary); text-shadow: 0 0 20px rgba(163, 230, 53, 0.3); }
        .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 10000; align-items: center; justify-content: center; padding: 16px; }
        .modal.active { display: flex; }
        .modal-content { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius-lg); max-width: 500px; width: 100%; padding: 24px; }
        .warmup-item { background: var(--surface-strong); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 12px; margin-bottom: 8px; }
        .exercise-content-top { margin-bottom: 16px; }
        .exercise-actions-bottom { margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--border); display: flex; flex-direction: column; gap: 12px; }
        .alert-error { background: rgba(239, 68, 68, 0.15) !important; color: #fca5a5 !important; border: 1px solid rgba(239, 68, 68, 0.3) !important; border-left: 4px solid #ef4444 !important; padding: 16px !important; border-radius: var(--radius-md) !important; margin-bottom: 16px !important; }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="flex-between mb-2" style="margin-top: 16px;">
            <a href="index.php" class="btn btn-sm" style="background: var(--surface); color: var(--foreground);">← Indietro</a>
            <h2 style="margin: 0; font-size: 20px;"><?= htmlspecialchars($workout['name']) ?></h2>
            <a href="edit.php?id=<?= $workout['id'] ?>" class="btn btn-sm btn-secondary">✏️</a>
        </div>
        
        <?php if (isset($_GET['saved'])): ?>
            <div class="alert alert-success">Serie salvate! Esercizio #<?= intval($_GET['saved']) ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <div class="card highlight-card">
            <div class="card-body">
                <h3 style="margin: 0 0 8px 0;"><?= htmlspecialchars($workout['name']) ?></h3>
                <p class="text-muted" style="margin: 0;">
                    <?= $days_italian[$workout['day_of_week']] ?? 'Giorno libero' ?> • 
                    <?= $workout['duration_minutes'] ?> min • 
                    <?= htmlspecialchars($workout['focus_area']) ?>
                </p>
            </div>
        </div>
        
        <?php if (!$active_session): ?>
            <?php if (!empty($warmups)): ?>
            <div class="card">
                <div class="card-header"><h3> Riscaldamento Pre-Workout</h3></div>
                <div class="card-body">
                    <?php foreach ($warmups as $warmup): ?>
                        <div class="warmup-item">
                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                <div style="flex: 1;">
                                    <div style="font-weight: 600; margin-bottom: 4px;"><?= htmlspecialchars($warmup['name']) ?></div>
                                    <?php if ($warmup['description']): ?>
                                        <div class="text-muted" style="font-size: 13px;"><?= htmlspecialchars($warmup['description']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div style="background: var(--primary); color: var(--primary-foreground); padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                    <?= $warmup['duration_minutes'] ?> min
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <h3 class="mb-2">Esercizi (<?= count($workout_exercises) ?>)</h3>
            <?php foreach ($workout_exercises as $index => $exercise): ?>
                <div class="card" style="margin-bottom: 16px;">
                    <div class="exercise-image-container">
                        <?php if (!empty($exercise['image_url'])): ?>
                            <img src="<?= htmlspecialchars($exercise['image_url']) ?>" alt="<?= htmlspecialchars($exercise['exercise_name']) ?>" class="exercise-image">
                        <?php else: ?>
                            <div class="exercise-placeholder">💪</div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                            <span style="background: var(--primary); color: var(--primary-foreground); width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 700;"><?= $index + 1 ?></span>
                            <div style="flex: 1;">
                                <div style="font-weight: 700; font-size: 20px;"><?= htmlspecialchars($exercise['exercise_name']) ?></div>
                                <div style="font-size: 14px; color: var(--muted-foreground);"><?= $categories_map[$exercise['category']] ?? '' ?></div>
                            </div>
                        </div>
                        <div class="exercise-details-grid">
                            <div class="exercise-detail-box"><div class="exercise-detail-label">Serie</div><div class="exercise-detail-value"><?= $exercise['sets'] ?></div></div>
                            <div class="exercise-detail-box"><div class="exercise-detail-label">Rip</div><div class="exercise-detail-value"><?= htmlspecialchars($exercise['reps']) ?></div></div>
                            <div class="exercise-detail-box"><div class="exercise-detail-label">RPE</div><div class="exercise-detail-value"><?= $exercise['rpe'] ?></div></div>
                            <div class="exercise-detail-box"><div class="exercise-detail-label">Recupero</div><div class="exercise-detail-value" style="font-size: 16px;"><?= htmlspecialchars($exercise['rest_time']) ?></div></div>
                        </div>
                        <?php if (!empty($exercise['instructions'])): ?>
                            <div style="font-size: 14px; color: var(--foreground); margin-bottom: 16px; background: rgba(163, 230, 53, 0.1); padding: 12px; border-radius: var(--radius-md); border-left: 3px solid var(--primary);">
                                <strong style="color: var(--primary); display: block; margin-bottom: 4px; text-transform: uppercase; font-size: 11px;">📝 Tecnica</strong>
                                <?= htmlspecialchars($exercise['instructions']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="card mt-2">
                <div class="card-body text-center">
                    <h3 style="margin-bottom: 16px;">Pronto ad allenarti?</h3>
                    <form method="POST">
                        <div class="form-group"><label>Durata prevista (min)</label><input type="number" name="duration" class="form-control" value="<?= $workout['duration_minutes'] ?>" min="1"></div>
                        <button type="submit" name="start_session" class="btn btn-primary btn-block" style="font-size: 16px; padding: 14px;">▶️ Inizia Allenamento</button>
                    </form>
                </div>
            </div>
            
        <?php else: ?>
            <div class="alert alert-success" style="margin-bottom: 16px;">✅ Sessione in corso - Iniziata alle <?= date('H:i', strtotime($active_session['session_date'])) ?></div>
            
            <div class="card">
                <div class="card-body">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                        <span style="font-size: 14px; font-weight: 600; text-transform: uppercase; color: var(--muted-foreground);">Progresso Allenamento</span>
                        <span style="font-size: 16px; font-weight: 700; color: var(--primary);"><?= $completed_exercises_count ?>/<?= $total_exercises ?></span>
                    </div>
                    <div class="progress-bar" style="height: 12px;"><div class="progress-fill" style="width: <?= $global_progress_pct ?>%"></div></div>
                    <div style="text-align: center; margin-top: 8px; font-size: 12px; color: var(--muted-foreground);"><?= number_format($global_progress_pct, 1) ?>% completato</div>
                </div>
            </div>
            
            <?php foreach ($workout_exercises as $index => $exercise): 
                $rest_seconds = 90;
                if (!empty($exercise['rest_time'])) {
                    if (strpos($exercise['rest_time'], ':') !== false) {
                        $parts = explode(':', $exercise['rest_time']);
                        $rest_seconds = (intval($parts[0]) * 60) + intval($parts[1]);
                    } elseif (is_numeric($exercise['rest_time'])) { $rest_seconds = intval($exercise['rest_time']); }
                }
                
                $exercise_sets = $saved_sets[$exercise['exercise_id']] ?? [];
                $completed_sets = 0;
                foreach ($exercise_sets as $s) if ($s['completed']) $completed_sets++;
                $total_sets = $exercise['sets'];
                $exercise_progress_pct = $total_sets > 0 ? ($completed_sets / $total_sets) * 100 : 0;
                $is_exercise_complete = $completed_sets >= $total_sets;
            ?>
                <div class="card" style="margin-bottom: 16px; <?= $is_exercise_complete ? 'border: 2px solid var(--success);' : '' ?>">
                    <div class="exercise-image-container">
                        <?php if (!empty($exercise['image_url'])): ?>
                            <img src="<?= htmlspecialchars($exercise['image_url']) ?>" alt="<?= htmlspecialchars($exercise['exercise_name']) ?>" class="exercise-image">
                        <?php else: ?>
                            <div class="exercise-placeholder">💪</div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="exercise-content-top">
                            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                                <span style="background: <?= $is_exercise_complete ? 'var(--success)' : 'var(--primary)' ?>; color: white; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 700;"><?= $index + 1 ?></span>
                                <div style="flex: 1;">
                                    <div style="font-weight: 700; font-size: 20px;"><?= htmlspecialchars($exercise['exercise_name']) ?></div>
                                    <div style="font-size: 14px; color: var(--muted-foreground);"><?= $categories_map[$exercise['category']] ?? '' ?></div>
                                </div>
                                <?php if ($is_exercise_complete): ?>
                                    <span style="background: var(--success); color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">✅ COMPLETO</span>
                                <?php endif; ?>
                            </div>
                            <div class="progress-indicator">
                                <span style="font-size: 12px; color: var(--muted-foreground); text-transform: uppercase; font-weight: 600;">Progresso Esercizio</span>
                                <div class="progress-bar"><div class="progress-fill" style="width: <?= $exercise_progress_pct ?>%"></div></div>
                                <span class="progress-text"><?= $completed_sets ?>/<?= $total_sets ?></span>
                            </div>
                            <form method="POST" class="exercise-form" style="margin-top: 16px;">
                                <input type="hidden" name="session_id" value="<?= $active_session['id'] ?>">
                                <input type="hidden" name="exercise_id" value="<?= $exercise['exercise_id'] ?>">
                                <div style="margin-bottom: 12px;">
                                    <div style="font-size: 12px; color: var(--muted-foreground); text-transform: uppercase; font-weight: 600; margin-bottom: 8px;">Serie (target: <?= $exercise['sets'] ?> × <?= htmlspecialchars($exercise['reps']) ?>)</div>
                                    <?php for ($s = 1; $s <= $total_sets; $s++): 
                                        $saved_set = $exercise_sets[$s] ?? null;
                                        $is_completed = $saved_set && $saved_set['completed'];
                                    ?>
                                        <div class="set-row <?= $is_completed ? 'completed' : '' ?>">
                                            <div class="set-number"><?= $s ?></div>
                                            <input type="number" step="0.5" name="sets[<?= $s ?>][weight]" class="set-input" placeholder="kg" value="<?= $saved_set ? $saved_set['weight'] : ($exercise['weight'] ?? '') ?>">
                                            <input type="number" name="sets[<?= $s ?>][reps]" class="set-input" placeholder="rip" value="<?= $saved_set ? $saved_set['reps'] : preg_replace('/[^0-9]/', '', explode('-', $exercise['reps'])[0]) ?>">
                                            <input type="checkbox" name="sets[<?= $s ?>][completed]" class="set-checkbox" value="1" <?= $is_completed ? 'checked' : '' ?> onchange="this.closest('.set-row').classList.toggle('completed', this.checked)">
                                        </div>
                                    <?php endfor; ?>
                                </div>
                                <div class="exercise-actions-bottom">
                                    <button type="submit" name="save_single_exercise" class="btn btn-secondary btn-block">💾 Salva Serie - Esercizio <?= $index + 1 ?></button>
                                    <button type="button" class="btn btn-secondary btn-block" onclick="startRestTimer(<?= $rest_seconds ?>, <?= $index ?>)" style="font-size: 16px; padding: 14px;">⏱️ Timer Recupero (<?= htmlspecialchars($exercise['rest_time']) ?>)</button>
                                    <div id="timer-display-<?= $index ?>" style="display: none; margin-top: 12px; font-size: 32px; font-weight: 700; color: var(--primary);"></div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="card mt-2">
                <div class="card-body">
                    <h3 style="margin-bottom: 16px;">Completa Allenamento</h3>
                    <form method="POST" id="complete-form">
                        <input type="hidden" name="session_id" value="<?= $active_session['id'] ?>">
                        <input type="hidden" name="is_partial" id="is-partial" value="0">
                        <input type="hidden" name="partial_reason" id="partial-reason-hidden" value="">
                        <div class="form-group"><label>Durata effettiva (min)</label><input type="number" name="duration" class="form-control" value="<?= $workout['duration_minutes'] ?>" min="1" id="actual-duration"></div>
                        <div class="form-group"><label>Note sessione</label><textarea name="session_notes" class="form-control" rows="2" placeholder="Sensazioni, carichi usati..." id="session-notes"></textarea></div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <button type="button" class="btn btn-warning btn-block" onclick="showPartialModal()" style="padding: 14px;">⏸️ Completa Parziale</button>
                            <button type="button" class="btn btn-success btn-block" onclick="completeSession(false)" style="padding: 14px;">✅ Completa Totale</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="timer-overlay" id="timerOverlay">
        <div class="timer-display-overlay" id="timerOverlayDisplay">00:00</div>
        <button type="button" class="btn btn-secondary" onclick="stopRestTimer()" style="margin-top: 24px; padding: 12px 32px; font-size: 16px;">⏹️ Interrompi Timer</button>
    </div>
    
    <div class="modal" id="partialModal">
        <div class="modal-content">
            <h3 style="margin-bottom: 16px; color: var(--warning);">⚠️ Allenamento Parziale</h3>
            <p style="margin-bottom: 16px; color: var(--muted-foreground);">Hai completato <?= $completed_exercises_count ?> esercizi su <?= $total_exercises ?>. Vuoi davvero completare in modo parziale?</p>
            <div class="form-group"><label>Motivo (opzionale)</label><textarea id="partial-reason" class="form-control" rows="2" placeholder="Tempo limitato, infortunio, ecc..."></textarea></div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <button type="button" class="btn btn-secondary" onclick="hidePartialModal()">Annulla</button>
                <button type="button" class="btn btn-warning" onclick="completeSession(true)">Conferma Parziale</button>
            </div>
        </div>
    </div>
    
    <?php include '../includes/bottom-nav.php'; ?>
    <script>
    let currentTimerInterval = null;
    let currentTimerSeconds = 0;
    
    function startRestTimer(seconds, index) {
        const display = document.getElementById('timer-display-' + index);
        const overlay = document.getElementById('timerOverlay');
        const overlayDisplay = document.getElementById('timerOverlayDisplay');
        currentTimerSeconds = seconds;
        overlayDisplay.textContent = formatTime(seconds);
        overlay.classList.add('active');
        currentTimerInterval = setInterval(function() {
            currentTimerSeconds--;
            const formatted = formatTime(currentTimerSeconds);
            overlayDisplay.textContent = formatted;
            if (display) display.textContent = formatted;
            if (currentTimerSeconds <= 0) {
                stopRestTimer();
                overlayDisplay.textContent = 'FINE!';
                overlayDisplay.style.color = 'var(--success)';
                if (navigator.vibrate) navigator.vibrate([200, 100, 200]);
                setTimeout(function() {
                    overlay.classList.remove('active');
                    overlayDisplay.style.color = 'var(--primary)';
                    if (display) { display.style.display = 'none'; display.textContent = ''; }
                }, 2000);
            }
        }, 1000);
    }
    
    function stopRestTimer() {
        if (currentTimerInterval) { clearInterval(currentTimerInterval); currentTimerInterval = null; }
        document.getElementById('timerOverlay').classList.remove('active');
    }
    
    function formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return (mins < 10 ? '0' : '') + mins + ':' + (secs < 10 ? '0' : '') + secs;
    }
    
    function showPartialModal() { document.getElementById('partialModal').classList.add('active'); }
    function hidePartialModal() { document.getElementById('partialModal').classList.remove('active'); }
    
    function completeSession(isPartial) {
        const duration = document.getElementById('actual-duration').value;
        const notes = document.getElementById('session-notes').value;
        const partialReason = document.getElementById('partial-reason').value;
        document.getElementById('is-partial').value = isPartial ? '1' : '0';
        document.getElementById('partial-reason-hidden').value = partialReason;
        document.getElementById('session-notes').value = notes + (isPartial ? '\n\n[PARZIALE: ' + partialReason + ']' : '');
        document.getElementById('complete-form').submit();
    }
    
    document.getElementById('partialModal')?.addEventListener('click', function(e) {
        if (e.target === this) hidePartialModal();
    });
    </script>
</body>
</html>