<?php
require_once '../config/database.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$user_data = getUserData($pdo, $user_id);

// Crea cartella upload se non esiste
$upload_dir = __DIR__ . '/../uploads/profiles/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $age = intval($_POST['age'] ?? 30);
    $height = floatval($_POST['height'] ?? 187);
    $weight = floatval($_POST['weight'] ?? 72);
    $level = $_POST['training_level'] ?? 'intermediate';
    $goal = $_POST['goal'] ?? 'muscle_gain';
    $profile_photo = $user_data['profile_photo'] ?? null;
    
    // 1. Gestione Rimozione Foto
    if (isset($_POST['remove_photo']) && $_POST['remove_photo'] === '1') {
        if ($profile_photo && file_exists(__DIR__ . '/..' . $profile_photo)) {
            @unlink(__DIR__ . '/..' . $profile_photo);
        }
        $profile_photo = null;
    }
    
    // 2. Gestione Upload Nuova Foto
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_photo'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (in_array($file['type'], $allowed_types) && $file['size'] < 2000000) {
            // Elimina vecchia foto se esiste
            if ($profile_photo && file_exists(__DIR__ . '/..' . $profile_photo)) {
                @unlink(__DIR__ . '/..' . $profile_photo);
            }
            
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_name = 'user_' . $user_id . '_' . time() . '.' . $ext;
            $dest = $upload_dir . $new_name;
            
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $profile_photo = '/uploads/profiles/' . $new_name;
            } else {
                $error = 'Errore durante il salvataggio della foto.';
            }
        } else {
            $error = 'Formato non valido o file troppo grande (max 2MB, usa JPG/PNG/WEBP).';
        }
    }

    // 3. Salvataggio Dati (solo se non c'è errore o se è solo upload foto)
    if (empty($error)) {
        // Calcolo BMR e TDEE corretti
        // Formula di Mifflin-St Jeor per uomini: (10 × peso in kg) + (6.25 × altezza in cm) - (5 × età in anni) + 5
        // Per donne: ... - 161
        $gender_multiplier = 5; // Default uomo
        $bmr = (10 * $weight) + (6.25 * $height) - (5 * $age) + $gender_multiplier;
        
        // Moltiplicatori attività fisica
        $multipliers = [
            'beginner' => 1.375,      // Leggermente attivo
            'intermediate' => 1.55,   // Moderatamente attivo
            'advanced' => 1.725       // Molto attivo
        ];
        $tdee = $bmr * ($multipliers[$level] ?? 1.55);
        
        // Surplus/Deficit calorico in base all'obiettivo
        $surplus = [
            'muscle_gain' => 400,     // Bulk: +400 kcal
            'maintenance' => 0,       // Mantenimento
            'fat_loss' => -500        // Cutting: -500 kcal
        ];
        $target_cal = $tdee + ($surplus[$goal] ?? 0);
        
        // Calcolo macro target (grammi per kg di peso corporeo)
        $target_protein = $weight * 2.0;        // 2g per kg
        $target_fats = $weight * 1.0;           // 1g per kg
        $remaining_cal = $target_cal - (($target_protein * 4) + ($target_fats * 9));
        $target_carbs = $remaining_cal / 4;     // 4 kcal per grammo
        
        // Arrotondamenti
        $target_protein = round($target_protein, 1);
        $target_fats = round($target_fats, 1);
        $target_carbs = round($target_carbs, 1);
        $target_cal = round($target_cal, 0);
        $bmi = $height > 0 ? $weight / (($height/100)**2) : 0;

        $pdo->prepare("UPDATE users SET full_name=?, profile_photo=? WHERE id=?")
            ->execute([$full_name, $profile_photo, $user_id]);
            
        $pdo->prepare("UPDATE user_profiles SET age=?, height=?, weight=?, bmi=?, training_level=?, goal=?, bmr=?, tdee=?, target_calories=?, target_protein=?, target_carbs=?, target_fats=? WHERE user_id=?")
            ->execute([$age, $height, $weight, $bmi, $level, $goal, $bmr, $tdee, $target_cal, $target_protein, $target_carbs, $target_fats, $user_id]);
        
        $_SESSION['full_name'] = $full_name;
        $success = 'Profilo aggiornato con successo! BMR, TDEE e macro ricalcolati.';
        
        // Ricarica i dati aggiornati
        $user_data = getUserData($pdo, $user_id);
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilo - My Workout</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .profile-header {
            text-align: center;
            padding: 30px 20px;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            border-radius: 12px;
            margin-bottom: 20px;
            color: white;
        }
        .profile-photo-large {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid white;
            box-shadow: 0 8px 24px rgba(0,0,0,0.3);
            margin: 0 auto 15px auto;
            display: block;
            background: #f3f4f6;
        }
        .photo-placeholder {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 80px;
            color: #9ca3af;
            margin: 0 auto 15px auto;
            border: 5px solid white;
            box-shadow: 0 8px 24px rgba(0,0,0,0.3);
        }
        .photo-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 10px;
        }
        .photo-preview {
            max-width: 180px;
            border-radius: 50%;
            margin-top: 15px;
            display: none;
            border: 5px solid white;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h2 class="mb-2">👤 Il tuo Profilo</h2>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success">Profilo aggiornato con successo!</div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <!-- Sezione Foto Profilo GRANDE -->
                <div class="profile-header">
                    <?php if (!empty($user_data['profile_photo'])): ?>
                        <img src="<?= htmlspecialchars($user_data['profile_photo']) ?>" alt="Foto profilo" class="profile-photo-large" id="currentPhoto">
                    <?php else: ?>
                        <div class="photo-placeholder" id="currentPhoto">👤</div>
                    <?php endif; ?>
                    
                    <h3 style="margin: 10px 0 5px 0; font-size: 22px;"><?= htmlspecialchars($user_data['full_name']) ?></h3>
                    <p style="opacity: 0.9; margin: 0;">Livello: <?= ucfirst($user_data['training_level'] ?? 'Intermedio') ?></p>
                    
                    <div class="photo-actions">
                        <label for="profile_photo_input" class="btn btn-sm" style="background: white; color: var(--primary); font-weight: 600; cursor: pointer;">
                            📷 Cambia Foto
                        </label>
                        <?php if (!empty($user_data['profile_photo'])): ?>
                            <button type="button" class="btn btn-sm btn-danger" 
                                    onclick="if(confirm('Rimuovere la foto profilo?')){document.getElementById('remove_photo').value='1';document.getElementById('profile-form').submit();}">
                                🗑️ Rimuovi
                            </button>
                        <?php endif; ?>
                        <button type="button" id="confirmPhotoBtn" class="btn btn-sm btn-primary" style="display: none;" onclick="submitFormForPhoto()">
                            ✅ Conferma Foto
                        </button>
                    </div>
                    <input type="file" id="profile_photo_input" name="profile_photo" style="display: none;" accept="image/*" onchange="previewPhoto(this)">
                    <img id="photoPreview" class="photo-preview">
                </div>

                <form method="POST" enctype="multipart/form-data" id="profile-form">
                    <input type="hidden" id="remove_photo" name="remove_photo" value="0">
                    
                    <div class="form-group">
                        <label>Nome Completo</label>
                        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user_data['full_name']) ?>" required>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label>Età</label>
                            <input type="number" name="age" class="form-control" value="<?= $user_data['age'] ?? 30 ?>">
                        </div>
                        <div class="form-group">
                            <label>Altezza (cm)</label>
                            <input type="number" step="0.1" name="height" class="form-control" value="<?= $user_data['height'] ?? 187 ?>">
                        </div>
                        <div class="form-group">
                            <label>Peso (kg)</label>
                            <input type="number" step="0.1" name="weight" class="form-control" value="<?= $user_data['weight'] ?? 72 ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Livello di Allenamento</label>
                        <select name="training_level" class="form-control">
                            <option value="beginner" <?= ($user_data['training_level']??'')=='beginner'?'selected':'' ?>>Principiante</option>
                            <option value="intermediate" <?= ($user_data['training_level']??'')=='intermediate'?'selected':'' ?>>Intermedio</option>
                            <option value="advanced" <?= ($user_data['training_level']??'')=='advanced'?'selected':'' ?>>Avanzato</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Obiettivo</label>
                        <select name="goal" class="form-control">
                            <option value="muscle_gain" <?= ($user_data['goal']??'')=='muscle_gain'?'selected':'' ?>>Aumento Massa (Clean Bulk)</option>
                            <option value="maintenance" <?= ($user_data['goal']??'')=='maintenance'?'selected':'' ?>>Mantenimento</option>
                            <option value="fat_loss" <?= ($user_data['goal']??'')=='fat_loss'?'selected':'' ?>>Definizione</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">💾 Calcola e Salva Modifiche</button>
                    <p class="text-muted" style="font-size: 13px; margin-top: 12px; text-align: center;">
                        ℹ️ Il sistema calcolerà automaticamente BMR, TDEE e i macro target in base ai dati inseriti.
                    </p>
                </form>
            </div>
        </div>

        <div class="card mt-2">
            <div class="card-header"><h3>📊 I tuoi Parametri Calcolati</h3></div>
            <div class="card-body">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-info">
                            <div class="stat-value"><?= number_format($user_data['bmr'] ?? 0) ?></div>
                            <div class="stat-label">BMR (kcal)</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-info">
                            <div class="stat-value"><?= number_format($user_data['tdee'] ?? 0) ?></div>
                            <div class="stat-label">TDEE (kcal)</div>
                        </div>
                    </div>
                    <div class="stat-card" style="grid-column: span 2;">
                        <div class="stat-info">
                            <div class="stat-value"><?= number_format($user_data['target_calories'] ?? 0) ?></div>
                            <div class="stat-label">Target Calorico Giornaliero</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <a href="../auth/logout.php" class="btn btn-danger btn-block mt-2">🚪 Esci dall'Account</a>
    </div>
    
    <?php include '../includes/bottom-nav.php'; ?>
    
    <script>
    let photoSelected = false;
    
    function previewPhoto(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('photoPreview');
                const current = document.getElementById('currentPhoto');
                const confirmBtn = document.getElementById('confirmPhotoBtn');
                
                preview.src = e.target.result;
                preview.style.display = 'block';
                
                // Nascondi temporaneamente la foto corrente per mostrare l'anteprima
                if(current.tagName === 'IMG') {
                    current.style.display = 'none';
                } else {
                    current.style.display = 'none';
                }
                
                // Mostra il bottone di conferma
                confirmBtn.style.display = 'inline-block';
                photoSelected = true;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    function submitFormForPhoto() {
        if (photoSelected) {
            document.getElementById('profile-form').submit();
        }
    }
    </script>
</body>
</html>