<?php
require_once '../config/database.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$date = $_GET['date'] ?? $today;

// Recupera target dal profilo
$stmt = $pdo->prepare("SELECT target_calories, target_protein, target_carbs, target_fats FROM user_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$targets = $stmt->fetch();

// Recupera pasti del giorno
$stmt = $pdo->prepare("SELECT * FROM meals WHERE user_id = ? AND meal_date = ? ORDER BY FIELD(meal_type, 'breakfast', 'snack_am', 'lunch', 'snack_pm', 'dinner')");
$stmt->execute([$user_id, $date]);
$meals = $stmt->fetchAll();

// Calcola totali
$totals = ['cal' => 0, 'p' => 0, 'c' => 0, 'f' => 0];
foreach ($meals as $meal) {
    $totals['cal'] += $meal['total_calories'];
    $totals['p'] += $meal['total_protein'];
    $totals['c'] += $meal['total_carbs'];
    $totals['f'] += $meal['total_fats'];
}

$meal_labels = ['breakfast' => '🌅 Colazione', 'snack_am' => '🍎 Spuntino AM', 'lunch' => '️ Pranzo', 'snack_pm' => '🥤 Spuntino PM', 'dinner' => '🌙 Cena'];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nutrizione - My Workout</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <div class="container">
        <div class="flex-between mb-2">
            <a href="?date=<?= date('Y-m-d', strtotime($date . ' -1 day')) ?>" class="btn btn-sm">←</a>
            <h2 style="margin:0;"><?= date('d/m/Y', strtotime($date)) ?></h2>
            <a href="?date=<?= date('Y-m-d', strtotime($date . ' +1 day')) ?>" class="btn btn-sm">→</a>
        </div>

        <!-- Riepilogo Macro -->
        <div class="card highlight-card">
            <div class="card-body text-center">
                <h3 style="color: var(--primary); font-size: 32px; margin: 0;"><?= number_format($totals['cal']) ?> / <?= $targets['target_calories'] ?> kcal</h3>
                <p class="text-muted">Calorie Giornaliere</p>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 15px;">
                    <div><div style="font-weight:700; font-size:18px;"><?= number_format($totals['p']) ?>g</div><div style="font-size:12px; color:var(--gray);">Proteine<br>(Target: <?= $targets['target_protein'] ?>g)</div></div>
                    <div><div style="font-weight:700; font-size:18px;"><?= number_format($totals['c']) ?>g</div><div style="font-size:12px; color:var(--gray);">Carboidrati<br>(Target: <?= $targets['target_carbs'] ?>g)</div></div>
                    <div><div style="font-weight:700; font-size:18px;"><?= number_format($totals['f']) ?>g</div><div style="font-size:12px; color:var(--gray);">Grassi<br>(Target: <?= $targets['target_fats'] ?>g)</div></div>
                </div>
            </div>
        </div>

        <!-- Lista Pasti -->
        <?php foreach ($meal_labels as $key => $label): 
            $meal = array_filter($meals, fn($m) => $m['meal_type'] === $key);
            $meal = !empty($meal) ? reset($meal) : null;
        ?>
            <div class="card">
                <div class="card-header flex-between">
                    <h3 style="margin:0; font-size:16px;"><?= $label ?></h3>
                    <a href="meals.php?date=<?= $date ?>&type=<?= $key ?>&id=<?= $meal['id'] ?? 0 ?>" class="btn btn-sm btn-primary">
                        <?= $meal ? 'Modifica' : '+ Aggiungi' ?>
                    </a>
                </div>
                <?php if ($meal): ?>
                <div class="card-body">
                    <div style="font-size: 14px; color: var(--gray);">
                        <strong><?= number_format($meal['total_calories']) ?> kcal</strong> • 
                        P: <?= number_format($meal['total_protein']) ?>g • 
                        C: <?= number_format($meal['total_carbs']) ?>g • 
                        G: <?= number_format($meal['total_fats']) ?>g
                    </div>
                </div>
                <?php else: ?>
                <div class="card-body text-center text-muted">Nessun alimento registrato</div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php include '../includes/bottom-nav.php'; ?>
</body>
</html>