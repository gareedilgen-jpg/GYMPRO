<?php
require_once '../config/database.php';
requireLogin();

$error = '';
$categories = [
    'chest' => 'Petto', 'back' => 'Schiena', 'legs' => 'Gambe', 
    'shoulders' => 'Spalle', 'arms' => 'Braccia', 'core' => 'Core', 'cardio' => 'Cardio'
];

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
    
    if (isset($_FILES['image_upload']) && $_FILES['image_upload']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image_upload'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (in_array($file['type'], $allowed_types) && $file['size'] < 2000000) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_name = 'ex_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $dest = $upload_dir . $new_name;
            
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $image_url = '/uploads/exercises/' . $new_name;
            }
        } else {
            $error = 'Immagine non valida (max 2MB, JPG/PNG/GIF/WEBP)';
        }
    }
    
    if (empty($name)) {
        $error = 'Il nome è obbligatorio';
    } elseif (!$error) {
        try {
            $stmt = $pdo->prepare("INSERT INTO exercises (name, description, category, equipment, image_url, instructions) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $category, $equipment, $image_url, $instructions]);
            header('Location: archive.php?created=1');
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
    <title>Nuovo Esercizio - My Workout</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="flex-between mb-2">
            <a href="archive.php" class="btn">← Archivio</a>
            <h2>Nuovo Esercizio</h2>
        </div>
        
        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Nome Esercizio *</label>
                        <input type="text" name="name" class="form-control" required placeholder="es. Squat con bilanciere">
                    </div>
                    
                    <div class="form-group">
                        <label>Categoria</label>
                        <select name="category" class="form-control">
                            <?php foreach ($categories as $key => $label): ?>
                                <option value="<?= $key ?>"><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Attrezzatura</label>
                        <input type="text" name="equipment" class="form-control" placeholder="es. Bilanciere, Manubri...">
                    </div>
                    
                    <div class="form-group">
                        <label>Descrizione breve</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Descrizione sintetica..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Istruzioni / Tecnica</label>
                        <textarea name="instructions" class="form-control" rows="4" placeholder="Descrivi la corretta esecuzione..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Immagine Esercizio</label>
                        <label style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px;">Opzione 1: Carica immagine</label>
                        <input type="file" name="image_upload" class="form-control" accept="image/*" onchange="previewUpload(this)">
                        <img id="uploadPreview" style="display: none; max-width: 200px; border-radius: 8px; margin-top: 8px;">
                        
                        <label style="font-size: 13px; font-weight: 600; margin-top: 16px; display: block; margin-bottom: 4px;">Opzione 2: URL immagine</label>
                        <input type="url" name="image_url" class="form-control" placeholder="https://..." oninput="previewUrl(this.value)">
                        <img id="urlPreview" style="display: none; max-width: 200px; border-radius: 8px; margin-top: 8px;">
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">💾 Salva Esercizio</button>
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