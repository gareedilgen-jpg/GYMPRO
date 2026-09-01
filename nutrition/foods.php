<?php
require_once '../config/database.php';
requireLogin();

$cat = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
$action = $_GET['action'] ?? '';
$food_id = intval($_GET['id'] ?? 0);

$upload_dir = '../uploads/foods/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

// Gestione eliminazione
if ($action === 'delete' && $food_id) {
    $stmt = $pdo->prepare("SELECT image_url FROM foods WHERE id = ?");
    $stmt->execute([$food_id]);
    $f = $stmt->fetch();
    if ($f['image_url'] && strpos($f['image_url'], 'http') !== 0 && file_exists('..' . $f['image_url'])) {
        @unlink('..' . $f['image_url']);
    }
    $pdo->prepare("DELETE FROM foods WHERE id = ?")->execute([$food_id]);
    header('Location: foods.php?deleted=1');
    exit;
}

// Gestione salvataggio (nuovo o modifica)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $category = $_POST['category'];
    $cal = floatval($_POST['calories']);
    $p = floatval($_POST['protein']);
    $c = floatval($_POST['carbs']);
    $f_val = floatval($_POST['fats']);
    $fiber = floatval($_POST['fiber'] ?? 0);
    $image_url = trim($_POST['image_url'] ?? '');
    $food_id_post = intval($_POST['food_id'] ?? 0);
    
    if (isset($_FILES['image_upload']) && $_FILES['image_upload']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image_upload'];
        if (in_array($file['type'], ['image/jpeg', 'image/png', 'image/gif', 'image/webp']) && $file['size'] < 2000000) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_name = 'food_' . time() . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_name)) {
                $image_url = '/uploads/foods/' . $new_name;
            }
        }
    }
    
    if ($food_id_post) {
        $pdo->prepare("UPDATE foods SET name=?, category=?, calories_per_100g=?, protein_per_100g=?, carbs_per_100g=?, fats_per_100g=?, fiber_per_100g=?, image_url=? WHERE id=?")
            ->execute([$name, $category, $cal, $p, $c, $f_val, $fiber, $image_url, $food_id_post]);
        header('Location: foods.php?updated=1');
    } else {
        $pdo->prepare("INSERT INTO foods (name, category, calories_per_100g, protein_per_100g, carbs_per_100g, fats_per_100g, fiber_per_100g, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$name, $category, $cal, $p, $c, $f_val, $fiber, $image_url]);
        header('Location: foods.php?created=1');
    }
    exit;
}

// Recupera alimento per modifica
$edit_food = null;
if ($action === 'edit' && $food_id) {
    $stmt = $pdo->prepare("SELECT * FROM foods WHERE id = ?");
    $stmt->execute([$food_id]);
    $edit_food = $stmt->fetch();
}

// Lista alimenti
$query = "SELECT * FROM foods WHERE 1=1";
$params = [];
if ($cat) { $query .= " AND category = ?"; $params[] = $cat; }
if ($search) { $query .= " AND name LIKE ?"; $params[] = "%$search%"; }
$query .= " ORDER BY category, name";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$foods = $stmt->fetchAll();

$cats = ['protein'=>'Proteine', 'carbs'=>'Carboidrati', 'fats'=>'Grassi', 'vegetables'=>'Verdure', 'fruits'=>'Frutta', 'other'=>'Altro'];
$cat_icons = ['protein'=>'🥩', 'carbs'=>'🍝', 'fats'=>'🥑', 'vegetables'=>'🥦', 'fruits'=>'', 'other'=>'🍴'];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archivio Alimenti - My Workout</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <div class="container">
        <div class="flex-between mb-2">
            <a href="../nutrition/index.php" class="btn">← Nutrizione</a>
            <h2>️ Alimenti</h2>
            <button class="btn btn-sm btn-primary" onclick="toggleForm()">+ Nuovo</button>
        </div>
        
        <?php if (isset($_GET['created'])): ?><div class="alert alert-success">Alimento aggiunto!</div><?php endif; ?>
        <?php if (isset($_GET['updated'])): ?><div class="alert alert-success">Alimento aggiornato!</div><?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Alimento eliminato!</div><?php endif; ?>
        
        <!-- Form Nuovo/Modifica -->
        <div class="card" id="foodForm" style="<?= $edit_food ? '' : 'display:none;' ?>">
            <div class="card-header">
                <h3><?= $edit_food ? '✏️ Modifica' : '➕ Nuovo' ?> Alimento</h3>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <?php if ($edit_food): ?><input type="hidden" name="food_id" value="<?= $edit_food['id'] ?>"><?php endif; ?>
                    
                    <div class="form-group">
                        <label>Nome *</label>
                        <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($edit_food['name'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Categoria</label>
                        <select name="category" class="form-control">
                            <?php foreach ($cats as $k => $v): ?>
                                <option value="<?= $k ?>" <?= ($edit_food['category'] ?? '') == $k ? 'selected' : '' ?>><?= $cat_icons[$k] ?> <?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px;">
                        <div class="form-group"><label>Calorie/100g</label><input type="number" step="0.1" name="calories" class="form-control" required value="<?= $edit_food['calories_per_100g'] ?? 0 ?>"></div>
                        <div class="form-group"><label>Proteine/100g</label><input type="number" step="0.1" name="protein" class="form-control" required value="<?= $edit_food['protein_per_100g'] ?? 0 ?>"></div>
                        <div class="form-group"><label>Carboidrati/100g</label><input type="number" step="0.1" name="carbs" class="form-control" required value="<?= $edit_food['carbs_per_100g'] ?? 0 ?>"></div>
                        <div class="form-group"><label>Grassi/100g</label><input type="number" step="0.1" name="fats" class="form-control" required value="<?= $edit_food['fats_per_100g'] ?? 0 ?>"></div>
                        <div class="form-group"><label>Fibre/100g</label><input type="number" step="0.1" name="fiber" class="form-control" value="<?= $edit_food['fiber_per_100g'] ?? 0 ?>"></div>
                    </div>
                    
                    <div class="form-group">
                        <label>Immagine</label>
                        <?php if ($edit_food['image_url'] ?? ''): ?>
                            <img src="<?= htmlspecialchars($edit_food['image_url']) ?>" style="max-width: 150px; border-radius: 8px; margin-bottom: 8px;">
                        <?php endif; ?>
                        <input type="file" name="image_upload" class="form-control" accept="image/*">
                        <input type="url" name="image_url" class="form-control" style="margin-top: 8px;" placeholder="Oppure URL immagine" value="<?= htmlspecialchars($edit_food['image_url'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Note</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Note aggiuntive..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">💾 Salva</button>
                    <?php if ($edit_food): ?>
                        <a href="foods.php" class="btn btn-block" style="margin-top: 8px;">Annulla</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        
        <!-- Ricerca e Filtri -->
        <div class="card">
            <div class="card-body">
                <form method="GET" class="flex" style="gap: 8px;">
                    <input type="text" name="search" class="form-control" placeholder="🔍 Cerca..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn-primary">Cerca</button>
                </form>
                <div style="display: flex; gap: 8px; overflow-x: auto; padding: 12px 0;">
                    <a href="foods.php" class="btn btn-sm <?= !$cat?'btn-primary':'' ?>">Tutti</a>
                    <?php foreach($cats as $k=>$v): ?>
                        <a href="foods.php?category=<?= $k ?>" class="btn btn-sm <?= $cat==$k?'btn-primary':'' ?>"><?= $cat_icons[$k] ?> <?= $v ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Lista -->
        <?php foreach ($foods as $food): ?>
            <div class="food-item" style="background: white; border-radius: 12px; padding: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 12px; display: flex; align-items: center; gap: 12px;">
                <?php if ($food['image_url']): ?>
                    <img src="<?= htmlspecialchars($food['image_url']) ?>" style="width: 60px; height: 60px; border-radius: 8px; object-fit: cover;">
                <?php else: ?>
                    <div style="width: 60px; height: 60px; border-radius: 8px; background: var(--light-gray); display: flex; align-items: center; justify-content: center; font-size: 28px;"><?= $cat_icons[$food['category']] ?? '' ?></div>
                <?php endif; ?>
                <div style="flex: 1;">
                    <div style="font-weight: 600;"><?= htmlspecialchars($food['name']) ?></div>
                    <div style="font-size: 12px; color: var(--gray);"><?= $cats[$food['category']] ?> • per 100g</div>
                    <div style="font-size: 12px; margin-top: 4px;">
                        <strong><?= number_format($food['calories_per_100g']) ?></strong> kcal • 
                        P:<strong><?= number_format($food['protein_per_100g'], 1) ?></strong>g • 
                        C:<strong><?= number_format($food['carbs_per_100g'], 1) ?></strong>g • 
                        G:<strong><?= number_format($food['fats_per_100g'], 1) ?></strong>g
                    </div>
                </div>
                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <a href="foods.php?action=edit&id=<?= $food['id'] ?>" class="btn btn-sm btn-secondary">✏️</a>
                    <a href="foods.php?action=delete&id=<?= $food['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Eliminare?')">🗑️</a>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if (empty($foods)): ?>
            <div class="card"><div class="card-body text-center"><p style="font-size: 48px;">🔍</p><p>Nessun alimento trovato</p></div></div>
        <?php endif; ?>
    </div>
    <?php include '../includes/bottom-nav.php'; ?>
    <script>
    function toggleForm() {
        const form = document.getElementById('foodForm');
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
        form.scrollIntoView({ behavior: 'smooth' });
    }
    </script>
</body>
</html>