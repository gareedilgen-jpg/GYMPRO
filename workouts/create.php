<?php
require_once '../config/database.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$error = '';

// Recupera esercizi disponibili
$stmt = $pdo->query("SELECT * FROM exercises ORDER BY category, name");
$exercises = $stmt->fetchAll();

$categories = [
    'chest' => 'Petto', 'back' => 'Schiena', 'legs' => 'Gambe', 
    'shoulders' => 'Spalle', 'arms' => 'Braccia', 'core' => 'Core', 'cardio' => 'Cardio'
];

$days = [
    'monday' => 'Lunedì', 'tuesday' => 'Martedì', 'wednesday' => 'Mercoledì',
    'thursday' => 'Giovedì', 'friday' => 'Venerdì', 'saturday' => 'Sabato', 'sunday' => 'Domenica'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $day_of_week = $_POST['day_of_week'] ?? null;
    $focus_area = trim($_POST['focus_area'] ?? '');
    $duration = intval($_POST['duration_minutes'] ?? 60);
    
    if (empty($name)) {
        $error = 'Il nome è obbligatorio';
    } else {
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("INSERT INTO workouts (user_id, name, day_of_week, focus_area, duration_minutes) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $name, $day_of_week, $focus_area, $duration]);
            $workout_id = $pdo->lastInsertId();
            
            if (isset($_POST['exercises']) && is_array($_POST['exercises'])) {
                foreach ($_POST['exercises'] as $index => $exercise) {
                    if (!empty($exercise['exercise_id'])) {
                        $stmt = $pdo->prepare("INSERT INTO workout_exercises (workout_id, exercise_id, order_num, sets, reps, rest_time, rpe, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $workout_id, $exercise['exercise_id'], $index + 1,
                            $exercise['sets'] ?? 3, $exercise['reps'] ?? '10', 
                            $exercise['rest_time'] ?? '1:30', $exercise['rpe'] ?? 7, 
                            $exercise['notes'] ?? ''
                        ]);
                    }
                }
            }
            $pdo->commit();
            header('Location: index.php?created=1');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Errore: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Nuovo Allenamento - My Workout</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="flex-between mb-2">
            <a href="index.php" class="btn">← Indietro</a>
            <h2>Nuova Scheda</h2>
        </div>
        
        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        
        <form method="POST" id="workout-form">
            <div class="card">
                <div class="card-body">
                    <div class="form-group">
                        <label>Nome Scheda *</label>
                        <input type="text" name="name" class="form-control" required placeholder="es. Full Body A">
                    </div>
                    <div class="form-group">
                        <label>Giorno</label>
                        <select name="day_of_week" class="form-control">
                            <option value="">Seleziona giorno</option>
                            <?php foreach ($days as $key => $label): ?><option value="<?= $key ?>"><?= $label ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Focus</label>
                        <input type="text" name="focus_area" class="form-control" placeholder="es. Spinta + Gambe">
                    </div>
                    <div class="form-group">
                        <label>Durata (minuti)</label>
                        <input type="number" name="duration_minutes" class="form-control" value="60" min="15" max="180">
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header flex-between">
                    <h3>Esercizi</h3>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="addExercise()">+ Aggiungi</button>
                </div>
                <div class="card-body" id="exercises-container">
                    <p class="text-muted text-center" id="no-exercises">Nessun esercizio. Clicca "+ Aggiungi".</p>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">💾 Salva Scheda</button>
        </form>
    </div>
    
    <script>
    const exercisesData = <?= json_encode($exercises) ?>;
    const categories = <?= json_encode($categories) ?>;
    let exerciseCount = 0;
    
    function addExercise() {
        document.getElementById('no-exercises')?.remove();
        const container = document.getElementById('exercises-container');
        const index = exerciseCount++;
        
        const div = document.createElement('div');
        div.className = 'card';
        div.style.marginBottom = '12px';
        div.innerHTML = `
            <div class="card-body">
                <div class="flex-between mb-2">
                    <strong>Esercizio #${index + 1}</strong>
                    <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.card').remove()">×</button>
                </div>
                <div class="form-group">
                    <label>Esercizio</label>
                    <select name="exercises[${index}][exercise_id]" class="form-control" required>
                        <option value="">Seleziona</option>
                        ${Object.entries(categories).map(([key, label]) => `
                            <optgroup label="${label}">
                                ${exercisesData.filter(e => e.category === key).map(e => `<option value="${e.id}">${e.name}</option>`).join('')}
                            </optgroup>
                        `).join('')}
                    </select>
                </div>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px;">
                    <div class="form-group"><label>Serie</label><input type="number" name="exercises[${index}][sets]" class="form-control" value="3" min="1"></div>
                    <div class="form-group"><label>Ripetizioni</label><input type="text" name="exercises[${index}][reps]" class="form-control" value="10"></div>
                    <div class="form-group"><label>Recupero</label><input type="text" name="exercises[${index}][rest_time]" class="form-control" value="1:30"></div>
                    <div class="form-group"><label>RPE (1-10)</label><input type="number" name="exercises[${index}][rpe]" class="form-control" value="7" min="1" max="10"></div>
                </div>
                <div class="form-group">
                    <label>Note Tecniche</label>
                    <textarea name="exercises[${index}][notes]" class="form-control" rows="2"></textarea>
                </div>
            </div>
        `;
        container.appendChild(div);
    }
    </script>
</body>
</html>