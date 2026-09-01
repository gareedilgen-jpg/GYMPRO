<?php
require_once '../config/database.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$date = $_GET['date'] ?? date('Y-m-d');
$type = $_GET['type'] ?? 'breakfast';
$meal_id = intval($_GET['id'] ?? 0);

// Recupera pasto esistente
$meal = null;
$meal_foods = [];
if ($meal_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM meals WHERE id = ? AND user_id = ?");
    $stmt->execute([$meal_id, $user_id]);
    $meal = $stmt->fetch();
    if ($meal) {
        $stmt = $pdo->prepare("SELECT mf.*, f.name FROM meal_foods mf JOIN foods f ON mf.food_id = f.id WHERE mf.meal_id = ?");
        $stmt->execute([$meal_id]);
        $meal_foods = $stmt->fetchAll();
    }
}

// Gestione salvataggio
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';
    
    if ($action === 'delete' && $meal_id) {
        $pdo->prepare("DELETE FROM meals WHERE id = ? AND user_id = ?")->execute([$meal_id, $user_id]);
        header("Location: index.php?date=$date"); exit;
    }

    $total_cal = 0; $total_p = 0; $total_c = 0; $total_f = 0;
    $foods_to_insert = [];

    if (isset($_POST['food_ids'])) {
        foreach ($_POST['food_ids'] as $idx => $food_id) {
            $qty = floatval($_POST['quantities'][$idx] ?? 0);
            if ($food_id && $qty > 0) {
                $stmt = $pdo->prepare("SELECT * FROM foods WHERE id = ?");
                $stmt->execute([$food_id]);
                $f = $stmt->fetch();
                if ($f) {
                    $factor = $qty / 100;
                    $cal = $f['calories_per_100g'] * $factor;
                    $p = $f['protein_per_100g'] * $factor;
                    $c = $f['carbs_per_100g'] * $factor;
                    $fat = $f['fats_per_100g'] * $factor;
                    
                    $total_cal += $cal; $total_p += $p; $total_c += $c; $total_f += $fat;
                    $foods_to_insert[] = ['fid' => $food_id, 'qty' => $qty, 'cal' => $cal, 'p' => $p, 'c' => $c, 'f' => $fat];
                }
            }
        }
    }

    try {
        $pdo->beginTransaction();
        if ($meal_id) {
            $pdo->prepare("UPDATE meals SET total_calories=?, total_protein=?, total_carbs=?, total_fats=? WHERE id=?")->execute([$total_cal, $total_p, $total_c, $total_f, $meal_id]);
            $pdo->prepare("DELETE FROM meal_foods WHERE meal_id=?")->execute([$meal_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO meals (user_id, meal_type, meal_date, total_calories, total_protein, total_carbs, total_fats) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $type, $date, $total_cal, $total_p, $total_c, $total_f]);
            $meal_id = $pdo->lastInsertId();
        }
        foreach ($foods_to_insert as $fd) {
            $pdo->prepare("INSERT INTO meal_foods (meal_id, food_id, quantity_grams, calories, protein, carbs, fats) VALUES (?, ?, ?, ?, ?, ?, ?)")
                ->execute([$meal_id, $fd['fid'], $fd['qty'], $fd['cal'], $fd['p'], $fd['c'], $fd['f']]);
        }
        $pdo->commit();
        header("Location: index.php?date=$date"); exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Errore: " . $e->getMessage();
    }
}

// Recupera tutti i cibi per il dropdown
$all_foods = $pdo->query("SELECT * FROM foods ORDER BY category, name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Pasto - My Workout</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <div class="container">
        <div class="flex-between mb-2">
            <a href="index.php?date=<?= $date ?>" class="btn">← Indietro</a>
            <h2 style="margin:0;">Pasto</h2>
        </div>

        <form method="POST" id="meal-form">
            <input type="hidden" name="action" value="save">
            
            <div class="card">
                <div class="card-header"><h3>Alimenti</h3></div>
                <div class="card-body" id="food-list">
                    <?php if (empty($meal_foods)): ?>
                        <div class="food-entry" style="border-bottom: 1px solid var(--light-gray); padding-bottom: 10px; margin-bottom: 10px;">
                            <select name="food_ids[]" class="form-control" style="margin-bottom: 8px;">
                                <option value="">-- Seleziona Alimento --</option>
                                <?php foreach ($all_foods as $f): ?>
                                    <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['name']) ?> (<?= $f['calories_per_100g'] ?> kcal/100g)</option>
                                <?php endforeach; ?>
                            </select>
                            <input type="number" name="quantities[]" class="form-control" placeholder="Quantità (g)" value="100" min="1">
                        </div>
                    <?php else: ?>
                        <?php foreach ($meal_foods as $mf): ?>
                            <div class="food-entry" style="border-bottom: 1px solid var(--light-gray); padding-bottom: 10px; margin-bottom: 10px;">
                                <select name="food_ids[]" class="form-control" style="margin-bottom: 8px;">
                                    <?php foreach ($all_foods as $f): ?>
                                        <option value="<?= $f['id'] ?>" <?= $f['id'] == $mf['food_id'] ? 'selected' : '' ?>><?= htmlspecialchars($f['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="number" name="quantities[]" class="form-control" value="<?= $mf['quantity_grams'] ?>" min="1">
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <button type="button" class="btn btn-secondary btn-block" onclick="addFoodRow()">+ Aggiungi Altro Alimento</button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="margin-bottom: 10px;">💾 Salva Pasto</button>
            <?php if ($meal_id): ?>
                <button type="button" class="btn btn-danger btn-block" onclick="if(confirm('Eliminare?')){document.querySelector('[name=action]').value='delete';document.getElementById('meal-form').submit();}">🗑️ Elimina</button>
            <?php endif; ?>
        </form>
    </div>

    <script>
    function addFoodRow() {
        const div = document.createElement('div');
        div.className = 'food-entry';
        div.style.cssText = 'border-bottom: 1px solid var(--light-gray); padding-bottom: 10px; margin-bottom: 10px;';
        div.innerHTML = `
            <select name="food_ids[]" class="form-control" style="margin-bottom: 8px;">
                <option value="">-- Seleziona Alimento --</option>
                <?php foreach ($all_foods as $f): ?>
                    <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="quantities[]" class="form-control" placeholder="Quantità (g)" value="100" min="1">
        `;
        document.getElementById('food-list').appendChild(div);
    }
    </script>
</body>
</html>