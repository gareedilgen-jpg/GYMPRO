<?php
// Configurazione database per Altervista
define('DB_HOST', 'localhost');
define('DB_NAME', 'my_workout');
define('DB_USER', 'root');
define('DB_PASS', ''); // Lascia vuoto come indicato da Altervista
define('DB_CHARSET', 'utf8mb4');

// Opzioni PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
];

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    die("Errore di connessione al database: " . $e->getMessage());
}

// Avvio sessione
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Funzione per verificare se l'utente è loggato
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Funzione per richiedere login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /auth/login.php');
        exit;
    }
}

// Funzione per ottenere dati utente
function getUserData($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT u.*, up.* 
        FROM users u 
        LEFT JOIN user_profiles up ON u.id = up.user_id 
        WHERE u.id = ?
    ");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

// Funzione per hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Funzione per verificare password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}
?>