<?php
require_once '../config/database.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$workout_id = intval($_GET['id'] ?? 0);
$error = '';
$success = '';

// Recupera scheda
$stmt = $pdo->prepare("SELECT * FROM workouts WHERE id = ? AND user_id = ?");
$stmt->execute([$workout_id, $user_id]);
$workout = $stmt->fetch();

if (!$workout) {
    header('Location: index.php');
    exit;
}

// Recupera esercizi della scheda
$stmt = $pdo->prepare("SELECT * FROM workout_exercises WHERE workout_id = ? ORDER BY order_num");
$stmt->execute([$workout_id]);
$current_exercises = $stmt->fetchAll();

// Recupera tutti gli esercizi dall'archivio (con immagini)
$stmt = $pdo->query("SELECT * FROM exercises ORDER BY category, name");
$all_exercises = $stmt->fetchAll();

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
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($name)) {
        $error = 'Il nome è obbligatorio';
    } else {
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("UPDATE workouts SET name=?, day_of_week=?, focus_area=?, duration_minutes=?, is_active=? WHERE id=?");
            $stmt->execute([$name, $day_of_week, $focus_area, $duration, $is_active, $workout_id]);
            
            $stmt = $pdo->prepare("DELETE FROM workout_exercises WHERE workout_id = ?");
            $stmt->execute([$workout_id]);
            
            if (isset($_POST['exercises']) && is_array($_POST['exercises'])) {
                foreach ($_POST['exercises'] as $index => $exercise) {
                    if (!empty($exercise['exercise_id'])) {
                        $stmt = $pdo->prepare("INSERT INTO workout_exercises (workout_id, exercise_id, order_num, sets, reps, rest_time, rpe, weight, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $workout_id, $exercise['exercise_id'], $index + 1,
                            $exercise['sets'] ?? 3, $exercise['reps'] ?? '10', 
                            $exercise['rest_time'] ?? '1:30', $exercise['rpe'] ?? 7,
                            $exercise['weight'] ?? null, $exercise['notes'] ?? ''
                        ]);
                    }
                }
            }
            $pdo->commit();
            header('Location: index.php?updated=1');
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifica Scheda - My Workout</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Modal ricerca archivio */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .modal-overlay.active { display: flex; }
        .modal-content {
            background: #0c1015;
            border-radius: 12px;
            max-width: 500px;
            width: 100%;
            max-height: 80vh;
            overflow-y: auto;
            padding: 20px;
        }
        .exercise-search-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px;
            border-bottom: 1px solid var(--light-gray);
            cursor: pointer;
        }
        .exercise-search-item:hover { background: var(--light-gray); }
        .exercise-search-item img {
            width: 50px; height: 50px;
            border-radius: 8px;
            object-fit: cover;
        }
        .exercise-search-item .placeholder-img {
            width: 50px; height: 50px;
            border-radius: 8px;
            background: var(--light-gray);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .image-preview {
            width: 100px;
            height: 100px;
            border-radius: 8px;
            object-fit: cover;
            margin-top: 8px;
            border: 2px solid var(--light-gray);
        }
        .rpe-info {
            background: #e0f2fe;
            border-left: 4px solid var(--primary);
            padding: 10px;
            border-radius: 8px;
            font-size: 12px;
            margin-top: 8px;
        }
        .rpe-info strong { color: var(--primary); }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="flex-between mb-2">
            <a href="index.php" class="btn">← Indietro</a>
            <h2>Modifica Scheda</h2>
        </div>
        
        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        
        <form method="POST" id="workout-form">
            <div class="card">
                <div class="card-body">
                    <div class="form-group">
                        <label>Nome Scheda *</label>
                        <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($workout['name']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Giorno</label>
                        <select name="day_of_week" class="form-control">
                            <option value="">Seleziona giorno</option>
                            <?php foreach ($days as $key => $label): ?>
                                <option value="<?= $key ?>" <?= $workout['day_of_week'] == $key ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Focus</label>
                        <input type="text" name="focus_area" class="form-control" value="<?= htmlspecialchars($workout['focus_area']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Durata (minuti)</label>
                        <input type="number" name="duration_minutes" class="form-control" value="<?= $workout['duration_minutes'] ?>" min="15" max="180">
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_active" <?= $workout['is_active'] ? 'checked' : '' ?>> Scheda Attiva
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header flex-between">
                    <h3>Esercizi (<?= count($current_exercises) ?>)</h3>
                    <div>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="openArchiveModal()">📚 Da Archivio</button>
                        <button type="button" class="btn btn-sm btn-primary" onclick="addExercise()">+ Nuovo</button>
                    </div>
                </div>
                <div class="card-body" id="exercises-container">
                    <?php foreach ($current_exercises as $idx => $ex): 
                        // Recupera dati esercizio completo
                        $stmt = $pdo->prepare("SELECT * FROM exercises WHERE id = ?");
                        $stmt->execute([$ex['exercise_id']]);
                        $ex_data = $stmt->fetch();
                    ?>
                        <div class="exercise-entry card" style="margin-bottom: 12px; padding: 12px;">
                            <div class="flex-between mb-2">
                                <strong>Esercizio #<?= $idx + 1 ?></strong>
                                <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.exercise-entry').remove()">×</button>
                            </div>
                            <input type="hidden" name="exercises[<?= $idx ?>][exercise_id]" value="<?= $ex['exercise_id'] ?>">
                            
                            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px; padding: 10px; background: var(--light-gray); border-radius: 8px;">
                                <?php if ($ex_data['image_url']): ?>
                                    <img src="<?= htmlspecialchars($ex_data['image_url']) ?>" alt="<?= htmlspecialchars($ex_data['name']) ?>" style="width: 60px; height: 60px; border-radius: 8px; object-fit: cover;">
                                <?php else: ?>
                                    <div style="width: 60px; height: 60px; border-radius: 8px; background: var(--gray); color: white; display: flex; align-items: center; justify-content: center; font-size: 24px;">💪</div>
                                <?php endif; ?>
                                <div>
                                    <div style="font-weight: 600;"><?= htmlspecialchars($ex_data['name']) ?></div>
                                    <div style="font-size: 12px; color: var(--gray);"><?= $categories[$ex_data['category']] ?? '' ?></div>
                                </div>
                            </div>
                            
                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px;">
                                <div class="form-group"><label>Serie</label><input type="number" name="exercises[<?= $idx ?>][sets]" class="form-control" value="<?= $ex['sets'] ?>" min="1"></div>
                                <div class="form-group"><label>Ripetizioni</label><input type="text" name="exercises[<?= $idx ?>][reps]" class="form-control" value="<?= htmlspecialchars($ex['reps']) ?>"></div>
                                <div class="form-group"><label>Recupero</label><input type="text" name="exercises[<?= $idx ?>][rest_time]" class="form-control" value="<?= htmlspecialchars($ex['rest_time']) ?>"></div>
                                <div class="form-group">
                                    <label>RPE (1-10) <span style="cursor: help;" onclick="toggleRpeInfo(this)">ℹ️</span></label>
                                    <input type="number" name="exercises[<?= $idx ?>][rpe]" class="form-control" value="<?= $ex['rpe'] ?>" min="1" max="10">
                                    <div class="rpe-info" style="display: none;">
                                        <strong>Scala RPE:</strong><br>
                                        • RPE 10: Cedimento (0 RIR)<br>
                                        • RPE 9: 1 rip in riserva<br>
                                        • RPE 8: 2 rip in riserva<br>
                                        • RPE 7: 3 rip in riserva<br>
                                        • RPE 5-6: Riscaldamento
                                    </div>
                                </div>
                                <div class="form-group"><label>Peso Usato (kg)</label><input type="number" step="0.5" name="exercises[<?= $idx ?>][weight]" class="form-control" value="<?= $ex['weight'] ?? '' ?>" placeholder="Carico"></div>
                            </div>
                            <div class="form-group">
                                <label>Note Tecniche</label>
                                <textarea name="exercises[<?= $idx ?>][notes]" class="form-control" rows="2"><?= htmlspecialchars($ex['notes']) ?></textarea>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">💾 Salva Modifiche</button>
        </form>
    </div>
    
    <!-- Modal Ricerca Archivio -->
    <div class="modal-overlay" id="archiveModal">
        <div class="modal-content">
            <div class="flex-between mb-2">
                <h3>📚 Seleziona dall'Archivio</h3>
                <button type="button" class="btn btn-sm" onclick="closeArchiveModal()">✕</button>
            </div>
            <input type="text" id="archiveSearch" class="form-control mb-2" placeholder=" Cerca esercizio..." oninput="filterArchive()">
            <select id="archiveCategory" class="form-control mb-2" onchange="filterArchive()">
                <option value="">Tutte le categorie</option>
                <?php foreach ($categories as $key => $label): ?>
                    <option value="<?= $key ?>"><?= $label ?></option>
                <?php endforeach; ?>
            </select>
            <div id="archiveList">
                <?php foreach ($all_exercises as $ex): ?>
                    <div class="exercise-search-item" data-name="<?= strtolower($ex['name']) ?>" data-category="<?= $ex['category'] ?>" onclick="selectFromArchive(<?= $ex['id'] ?>, '<?= htmlspecialchars($ex['name'], ENT_QUOTES) ?>', '<?= $categories[$ex['category']] ?>', '<?= htmlspecialchars($ex['image_url'] ?? '', ENT_QUOTES) ?>')">
                        <?php if ($ex['image_url']): ?>
                            <img src="<?= htmlspecialchars($ex['image_url']) ?>" alt="<?= htmlspecialchars($ex['name']) ?>">
                        <?php else: ?>
                            <div class="placeholder-img">💪</div>
                        <?php endif; ?>
                        <div style="flex: 1;">
                            <div style="font-weight: 600;"><?= htmlspecialchars($ex['name']) ?></div>
                            <div style="font-size: 12px; color: var(--gray);"><?= $categories[$ex['category']] ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <script>
    let exerciseCount = <?= count($current_exercises) ?>;
    
    function openArchiveModal() {
        document.getElementById('archiveModal').classList.add('active');
    }
    
    function closeArchiveModal() {
        document.getElementById('archiveModal').classList.remove('active');
    }
    
    function filterArchive() {
        const search = document.getElementById('archiveSearch').value.toLowerCase();
        const category = document.getElementById('archiveCategory').value;
        document.querySelectorAll('.exercise-search-item').forEach(item => {
            const name = item.dataset.name;
            const cat = item.dataset.category;
            const matchSearch = name.includes(search);
            const matchCat = !category || cat === category;
            item.style.display = (matchSearch && matchCat) ? 'flex' : 'none';
        });
    }
    
    function selectFromArchive(id, name, category, imageUrl) {
        const container = document.getElementById('exercises-container');
        const index = exerciseCount++;
        
        const div = document.createElement('div');
        div.className = 'exercise-entry card';
        div.style.marginBottom = '12px';
        div.style.padding = '12px';
        div.innerHTML = `
            <div class="flex-between mb-2">
                <strong>Esercizio #${index + 1}</strong>
                <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.exercise-entry').remove()">×</button>
            </div>
            <input type="hidden" name="exercises[${index}][exercise_id]" value="${id}">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px; padding: 10px; background: var(--light-gray); border-radius: 8px;">
                ${imageUrl ? `<img src="${imageUrl}" style="width: 60px; height: 60px; border-radius: 8px; object-fit: cover;">` : '<div style="width: 60px; height: 60px; border-radius: 8px; background: var(--gray); color: white; display: flex; align-items: center; justify-content: center; font-size: 24px;">💪</div>'}
                <div>
                    <div style="font-weight: 600;">${name}</div>
                    <div style="font-size: 12px; color: var(--gray);">${category}</div>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px;">
                <div class="form-group"><label>Serie</label><input type="number" name="exercises[${index}][sets]" class="form-control" value="3" min="1"></div>
                <div class="form-group"><label>Ripetizioni</label><input type="text" name="exercises[${index}][reps]" class="form-control" value="10"></div>
                <div class="form-group"><label>Recupero</label><input type="text" name="exercises[${index}][rest_time]" class="form-control" value="1:30"></div>
                <div class="form-group">
                    <label>RPE (1-10) <span style="cursor: help;" onclick="toggleRpeInfo(this)">ℹ️</span></label>
                    <input type="number" name="exercises[${index}][rpe]" class="form-control" value="7" min="1" max="10">
                    <div class="rpe-info" style="display: none;">
                        <strong>Scala RPE:</strong><br>
                        • RPE 10: Cedimento (0 RIR)<br>
                        • RPE 9: 1 rip in riserva<br>
                        • RPE 8: 2 rip in riserva<br>
                        • RPE 7: 3 rip in riserva<br>
                        • RPE 5-6: Riscaldamento
                    </div>
                </div>
                <div class="form-group"><label>Peso Usato (kg)</label><input type="number" step="0.5" name="exercises[${index}][weight]" class="form-control" placeholder="Carico"></div>
            </div>
            <div class="form-group">
                <label>Note Tecniche</label>
                <textarea name="exercises[${index}][notes]" class="form-control" rows="2"></textarea>
            </div>
        `;
        container.appendChild(div);
        closeArchiveModal();
    }
    
    function toggleRpeInfo(el) {
        const info = el.closest('.form-group').querySelector('.rpe-info');
        info.style.display = info.style.display === 'none' ? 'block' : 'none';
    }
    
    // Chiudi modal cliccando fuori
    document.getElementById('archiveModal').addEventListener('click', function(e) {
        if (e.target === this) closeArchiveModal();
    });
    </script>
</body>
</html>