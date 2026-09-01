<?php
require_once '../config/database.php';
requireLogin();
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['track_date'];
    $weight = $_POST['weight'] ?? null;
    $chest = $_POST['chest'] ?? null;
    $arm = $_POST['arm'] ?? null;
    $waist = $_POST['waist'] ?? null;
    $energy = $_POST['energy'] ?? null;
    $sleep = $_POST['sleep'] ?? null;
    $notes = $_POST['notes'] ?? '';

    // Controlla se esiste già per quella data
    $stmt = $pdo->prepare("SELECT id FROM progress_tracking WHERE user_id = ? AND track_date = ?");
    $stmt->execute([$user_id, $date]);
    $existing = $stmt->fetch();

    if ($existing) {
        $stmt = $pdo->prepare("UPDATE progress_tracking SET weight=?, chest_circumference=?, arm_circumference=?, waist_circumference=?, energy_level=?, sleep_quality=?, notes=? WHERE id=?");
        $stmt->execute([$weight, $chest, $arm, $waist, $energy, $sleep, $notes, $existing['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO progress_tracking (user_id, track_date, weight, chest_circumference, arm_circumference, waist_circumference, energy_level, sleep_quality, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $date, $weight, $chest, $arm, $waist, $energy, $sleep, $notes]);
    }
    header('Location: index.php?saved=1'); exit;
}

$stmt = $pdo->prepare("SELECT * FROM progress_tracking WHERE user_id = ? ORDER BY track_date DESC LIMIT 10");
$stmt->execute([$user_id]);
$history = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progressi - My Workout</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <div class="container">
        <h2 class="mb-2">📊 Monitoraggio Progressi</h2>
        <?php if (isset($_GET['saved'])): ?><div class="alert alert-success">Dati salvati!</div><?php endif; ?>

        <div class="card">
            <div class="card-header"><h3>Registra Dati</h3></div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label>Data</label>
                        <input type="date" name="track_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="progress-grid" style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                        <div class="form-group"><label>Peso (kg)</label><input type="number" step="0.1" name="weight" class="form-control" placeholder="72.0"></div>
                        <div class="form-group"><label>Petto (cm)</label><input type="number" step="0.1" name="chest" class="form-control" placeholder="100"></div>
                        <div class="form-group"><label>Braccio (cm)</label><input type="number" step="0.1" name="arm" class="form-control" placeholder="35"></div>
                        <div class="form-group"><label>Vita (cm)</label><input type="number" step="0.1" name="waist" class="form-control" placeholder="80"></div>
                        <div class="form-group"><label>Energia (1-10)</label><input type="number" min="1" max="10" name="energy" class="form-control" value="7"></div>
                        <div class="form-group"><label>Sonno (1-10)</label><input type="number" min="1" max="10" name="sleep" class="form-control" value="7"></div>
                    </div>
                    <div class="form-group"><label>Note</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                    <button type="submit" class="btn btn-primary btn-block">💾 Salva Progresso</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3>Storico Recente</h3></div>
            <div class="card-body">
                <?php if (empty($history)): ?>
                    <p class="text-muted text-center">Nessun dato registrato.</p>
                <?php else: ?>
                    <table style="width:100%; font-size:13px; text-align:left;">
                        <thead><tr style="border-bottom:2px solid var(--light-gray);"><th style="padding:8px;">Data</th><th style="padding:8px;">Peso</th><th style="padding:8px;">Braccio</th><th style="padding:8px;">Energia</th></tr></thead>
                        <tbody>
                        <?php foreach ($history as $h): ?>
                            <tr style="border-bottom:1px solid var(--light-gray);">
                                <td style="padding:8px;"><?= date('d/m', strtotime($h['track_date'])) ?></td>
                                <td style="padding:8px;"><?= $h['weight'] ? $h['weight'].' kg' : '-' ?></td>
                                <td style="padding:8px;"><?= $h['arm_circumference'] ? $h['arm_circumference'].' cm' : '-' ?></td>
                                <td style="padding:8px;"><?= $h['energy_level'] ?? '-' ?>/10</td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php include '../includes/bottom-nav.php'; ?>
</body>
</html>