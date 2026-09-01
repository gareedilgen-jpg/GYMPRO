<?php
/**
 * FITNESS TRACKER - my_workout
 * Punto di ingresso principale
 * 
 * Reindirizza alla dashboard se loggato, altrimenti al login
 */

session_start();

// Verifica se l'utente è già loggato
if (isset($_SESSION['user_id'])) {
    // Utente loggato → vai alla dashboard
    header('Location: dashboard/index.php');
} else {
    // Utente non loggato → vai al login
    header('Location: auth/login.php');
}
exit;
?>