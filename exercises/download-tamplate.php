<?php
// Genera template CSV vuoto per importazione esercizi

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="template_esercizi_gympro.csv"');

$output = fopen('php://output', 'w');

// BOM per Excel UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Header colonne
fputcsv($output, [
    'Nome Esercizio',
    'Categoria (chest/back/legs/shoulders/arms/core/cardio)',
    'Attrezzatura',
    'Descrizione',
    'Istruzioni/Tecnica',
    'URL Immagine (opzionale)'
], ';');

// Esempi di righe
fputcsv($output, [
    'Panca Piana Bilanciere',
    'chest',
    'Bilanciere',
    'Esercizio fondamentale per il petto',
    'Scapole retratte, controllo eccentrico 2 secondi',
    'https://esempio.com/panca.jpg'
], ';');

fputcsv($output, [
    'Stacchi da Terra',
    'back',
    'Bilanciere',
    'Esercizio per catena posteriore',
    'Schiena neutra, carico sui talloni',
    ''
], ';');

fputcsv($output, [
    'Squat con Bilanciere',
    'legs',
    'Bilanciere',
    'Esercizio fondamentale per le gambe',
    'Profondità parallelo, ginocchia in linea con i piedi',
    ''
], ';');

fclose($output);
exit;
?>