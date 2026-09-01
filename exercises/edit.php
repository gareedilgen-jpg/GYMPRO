<?php
require_once '../config/database.php';
requireLogin();

$exercise_id = intval($_GET['id'] ?? 0);
$error = '';
$success = '';

// Recupera esercizio
$stmt = $pdo->prepare("SELECT * FROM exercises WHERE id = ?");
$stmt->execute([$exercise_id]);
$exercise = $stmt->fetch();

if (!$exercise) {
    header('Location: archive.php');
    exit;
}

$categories = [
    'chest' => 'Petto', 'back' => 'Schiena', 'legs' => 'Gambe', 
    'shoulders' => 'Spalle', 'arms' => 'Braccia', 'core' => 'Core', 'cardio' => 'Cardio'
];

// Crea cartella upload se non esiste
$upload_dir = '../uploads/exercises/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = $_POST['category'] ?? 'chest';
    $equipment = trim($_POST['equipment'] ?? '');
    $instructions = trim($_POST['instructions'] ?? '');
    $image_url = trim($_POST['image_url'] ?? '');
    
    // Gestione upload immagine
    if (isset($_FILES['image_upload']) && $_FILES['image_upload']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image_upload'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (in_array($file['type'], $allowed_types)) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_name = 'ex_' . $exercise_id . '_' . time() . '.' . $ext;
            $dest = $upload_dir . $new_name;
            
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                // Elimina vecchia immagine se esistente e non è URL esterno
                if ($exercise['image_url'] && strpos($exercise['image_url'], 'http') !== 0 && file_exists('..' . $exercise['image_url'])) {
                    @unlink('..' . $exercise['image_url']);
                }
                $image_url = '/uploads/exercises/' . $new_name;
            }
        } else {
            $error = 'Tipo di immagine non valido (usa JPG, PNG, GIF o WEBP)';
        }
    }
    
    if (empty($name)) {
        $error = 'Il nome è obbligatorio';
    } elseif (!$error) {
        try {
            $stmt = $pdo->prepare("UPDATE exercises SET name=?, description=?, category=?, equipment=?, image_url=?, instructions=? WHERE id=?");
            $stmt->execute([$name, $description, $category, $equipment, $image_url, $instructions, $exercise_id]);
            header('Location: archive.php?updated=1');
            exit;
        } catch (Exception $e) {
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
    <title>Modifica Esercizio - My Workout</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="flex-between mb-2">
            <a href="archive.php" class="btn">← Archivio</a>
            <h2>Modifica Esercizio</h2>
        </div>
        
        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Nome Esercizio *</label>
                        <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($exercise['name']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Categoria</label>
                        <select name="category" class="form-control">
                            <?php foreach ($categories as $key => $label): ?>
                                <option value="<?= $key ?>" <?= $exercise['category'] == $key ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Attrezzatura</label>
                        <input type="text" name="equipment" class="form-control" value="<?= htmlspecialchars($exercise['equipment']) ?>" placeholder="es. Bilanciere, Manubri, Macchina...">
                    </div>
                    
                    <div class="form-group">
                        <label>Descrizione</label>
                        <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($exercise['description']) ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Istruzioni / Tecnica di Esecuzione</label>
                        <textarea name="instructions" class="form-control" rows="4" placeholder="Descrivi la corretta esecuzione..."><?= htmlspecialchars($exercise['instructions']) ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Immagine Esercizio</label>
                        
                        <?php if ($exercise['image_url']): ?>
                            <div style="margin-bottom: 12px;">
                                <img src="<?= htmlspecialchars($exercise['image_url']) ?>" alt="Preview" style="max-width: 200px; border-radius: 8px; border: 2px solid var(--light-gray);">
                                <p style="font-size: 12px; color: var(--gray); margin-top: 4px;">Immagine attuale</p>
                            </div>
                        <?php endif; ?>
                        
                        <label style="font-size: 13px; font-weight: 600; margin-top: 8px; display: block;">Opzione 1: Carica nuova immagine</label>
                        <input type="file" name="image_upload" class="form-control" accept="image/*" onchange="previewUpload(this)">
                        <img id="uploadPreview" style="display: none; max-width: 200px; border-radius: 8px; margin-top: 8px;">
                        
                        <label style="font-size: 13px; font-weight: 600; margin-top: 16px; display: block;">Opzione 2: URL immagine esterna</label>
                        <input type="url" name="image_url" class="form-control" value="<?= htmlspecialchars($exercise['image_url']) ?>" placeholder="https://..." oninput="previewUrl(this.value)">
                        <img id="urlPreview" style="display: none; max-width: 200px; border-radius: 8px; margin-top: 8px;">
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">💾 Salva Modifiche</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
    function previewUpload(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.getElementById('uploadPreview');
                img.src = e.target.result;
                img.style.display = 'block';
                document.getElementById('urlPreview').style.display = 'none';
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    function previewUrl(url) {
        const img = document.getElementById('urlPreview');
        if (url) {
            img.src = url;
            img.style.display = 'block';
            img.onerror = function() { this.style.display = 'none'; };
            document.getElementById('uploadPreview').style.display = 'none';
        } else {
            img.style.display = 'none';
        }
    }
    </script>
</body>
</html>