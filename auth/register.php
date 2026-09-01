<?php
require_once '../config/database.php';

if (isLoggedIn()) {
    header('Location: ../dashboard/index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    if (empty($username) || empty($email) || empty($full_name) || empty($password)) {
        $error = 'Tutti i campi sono obbligatori';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email non valida';
    } elseif (strlen($password) < 6) {
        $error = 'La password deve essere di almeno 6 caratteri';
    } elseif ($password !== $password_confirm) {
        $error = 'Le password non coincidono';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
            $stmt->execute([$email, $username]);
            
            if ($stmt->fetch()) {
                $error = 'Email o username già registrati';
            } else {
                $hashed_password = hashPassword($password);
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, email, password, full_name) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$username, $email, $hashed_password, $full_name]);
                $user_id = $pdo->lastInsertId();
                
                $stmt = $pdo->prepare("
                    INSERT INTO user_profiles (user_id, training_level, goal) 
                    VALUES (?, 'intermediate', 'muscle_gain')
                ");
                $stmt->execute([$user_id]);
                
                $success = 'Registrazione completata! Ora puoi accedere.';
            }
        } catch (PDOException $e) {
            $error = 'Errore durante la registrazione. Riprova.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrati - My Workout</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-header">
            <h1>🏋️ My Workout</h1>
            <p>Crea il tuo account</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form method="POST" class="auth-form">
            <div class="form-group">
                <label for="full_name">Nome Completo</label>
                <input type="text" id="full_name" name="full_name" required 
                       value="<?= htmlspecialchars($full_name ?? '') ?>"
                       placeholder="Mario Rossi">
            </div>
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required 
                       value="<?= htmlspecialchars($username ?? '') ?>"
                       placeholder="mariorossi">
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required 
                       value="<?= htmlspecialchars($email ?? '') ?>"
                       placeholder="la-tua@email.com">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required 
                       placeholder="Minimo 6 caratteri">
            </div>
            
            <div class="form-group">
                <label for="password_confirm">Conferma Password</label>
                <input type="password" id="password_confirm" name="password_confirm" required 
                       placeholder="Ripeti password">
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Registrati</button>
        </form>
        
        <div class="auth-footer">
            <p>Hai già un account? <a href="login.php">Accedi</a></p>
        </div>
    </div>
</body>
</html>