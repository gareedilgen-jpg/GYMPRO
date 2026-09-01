<?php
require_once '../config/database.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$session_id = intval($_GET['id'] ?? 0);

if ($session_id > 0) {
    try {
        // Verifica proprietà e elimina (CASCADE eliminerà anche workout_sets)
        $stmt = $pdo->prepare("DELETE FROM workout_sessions WHERE id = ? AND user_id = ?");
        $stmt->execute([$session_id, $user_id]);
    } catch (Exception $e) {
        error_log("Errore eliminazione sessione: " . $e->getMessage());
    }
}

header('Location: history.php?deleted=1');
exit;
?>