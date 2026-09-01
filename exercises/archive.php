<?php
require_once '../config/database.php';
requireLogin();

$cat = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';

$query = "SELECT * FROM exercises WHERE 1=1";
$params = [];

if ($cat) { $query .= " AND category = ?"; $params[] = $cat; }
if ($search) { $query .= " AND (name LIKE ? OR description LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
$query .= " ORDER BY category, name";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$exercises = $stmt->fetchAll();

$cats = ['chest'=>'Petto', 'back'=>'Schiena', 'legs'=>'Gambe', 'shoulders'=>'Spalle', 'arms'=>'Braccia', 'core'=>'Core', 'cardio'=>'Cardio'];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archivio Esercizi - My Workout</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <div class="container">
			<div class="flex-between mb-2" style="margin-top: 16px;">
				<a href="../dashboard/index.php" class="btn btn-sm" style="background: var(--surface); color: var(--foreground);">← Dashboard</a>
				<h2 style="margin: 0; font-size: 20px;"> Archivio Esercizi</h2>
				<div>
					<a href="import-export.php" class="btn btn-sm btn-secondary">📥 Import/Export</a>
					<a href="create.php" class="btn btn-sm btn-primary">+ Nuovo</a>
				</div>
			</div>
        
        <?php if (isset($_GET['created'])): ?><div class="alert alert-success">Esercizio aggiunto!</div><?php endif; ?>
        <?php if (isset($_GET['updated'])): ?><div class="alert alert-success">Esercizio aggiornato!</div><?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Esercizio eliminato!</div><?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <form method="GET" class="flex" style="gap: 8px;">
                    <input type="text" name="search" class="form-control" placeholder="🔍 Cerca esercizio..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn-primary">Cerca</button>
                </form>
                
                <div style="display: flex; gap: 8px; overflow-x: auto; padding: 12px 0;">
                    <a href="archive.php" class="btn btn-sm <?= !$cat?'btn-primary':'' ?>">Tutti</a>
                    <?php foreach($cats as $k=>$v): ?>
                        <a href="archive.php?category=<?= $k ?>" class="btn btn-sm <?= $cat==$k?'btn-primary':'' ?>"><?= $v ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <?php foreach ($exercises as $ex): ?>
            <div class="exercise-item" style="background: #15181f; border-radius: 12px; padding: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 12px; border: solid 1px oklch(0.31 0.01 256.83);">
                <div style="display: flex; gap: 12px; align-items: start;">
                    <?php if ($ex['image_url']): ?>
                        <img src="<?= htmlspecialchars($ex['image_url']) ?>" alt="<?= htmlspecialchars($ex['name']) ?>" style="width: 70px; height: 70px; border-radius: 8px; object-fit: cover;border: 3px solid #bce426;">
                    <?php else: ?>
                        <div style="width: 70px; height: 70px; border-radius: 8px; background: var(--light-gray); display: flex; align-items: center; justify-content: center; font-size: 32px;">💪</div>
                    <?php endif; ?>
                    <div style="flex: 1;">
                        <div style="font-weight: 600; font-size: 16px; margin-bottom: 4px;"><?= htmlspecialchars($ex['name']) ?></div>
                        <div style="font-size: 12px; color: var(--primary); font-weight: 600; text-transform: uppercase;"><?= $cats[$ex['category']] ?? $ex['category'] ?> • <?= htmlspecialchars($ex['equipment']) ?></div>
                        <?php if ($ex['description']): ?>
                            <div style="font-size: 13px; color: var(--gray); margin-top: 4px;"><?= htmlspecialchars($ex['description']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($ex['instructions']): ?>
                    <div style="margin-top: 12px; font-size: 13px; color: var(--gray); background: var(--light-gray); padding: 10px; border-radius: 8px;">
                        <strong>📝 Tecnica:</strong> <?= htmlspecialchars($ex['instructions']) ?>
                    </div>
                <?php endif; ?>
                
                <div style="display: flex; gap: 8px; margin-top: 12px;">
                    <a href="edit.php?id=<?= $ex['id'] ?>" class="btn btn-sm btn-secondary" style="flex: 1;">️ Modifica</a>
                    <a href="delete.php?id=<?= $ex['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Eliminare questo esercizio?')">🗑️</a>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if (empty($exercises)): ?>
            <div class="card">
                <div class="card-body text-center">
                    <p style="font-size: 48px;">🔍</p>
                    <h3>Nessun esercizio trovato</h3>
                    <a href="create.php" class="btn btn-primary mt-2">Aggiungi il primo</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php include '../includes/bottom-nav.php'; ?>
</body>
</html>