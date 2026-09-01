<?php
require_once '../config/database.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Gestione EXPORT
if (isset($_GET['action']) && $_GET['action'] === 'export') {
    try {
        // Recupera tutti gli esercizi dell'utente o globali
        $stmt = $pdo->query("
            SELECT 
                name,
                category,
                equipment,
                description,
                instructions,
                image_url
            FROM exercises 
            ORDER BY category, name
        ");
        $exercises = $stmt->fetchAll();
        
        // Crea CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="archivio_esercizi_' . date('Y-m-d') . '.csv"');
        
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
        ], ';'); // Usiamo ; come separatore per Excel italiano
        
        // Dati
        foreach ($exercises as $ex) {
            fputcsv($output, [
                $ex['name'],
                $ex['category'],
                $ex['equipment'],
                $ex['description'],
                $ex['instructions'],
                $ex['image_url']
            ], ';');
        }
        
        fclose($output);
        exit;
        
    } catch (Exception $e) {
        $error = 'Errore durante l\'export: ' . $e->getMessage();
    }
}

// Gestione IMPORT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file'])) {
    $file = $_FILES['import_file'];
    $import_mode = $_POST['import_mode'] ?? 'skip'; // skip, update, replace
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['text/csv', 'application/vnd.ms-excel', 'text/plain'];
        $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        
        if ($file_ext === 'csv' || in_array($file['type'], $allowed_types)) {
            try {
                $handle = fopen($file['tmp_name'], 'r');
                
                // Salta BOM se presente
                $bom = fread($handle, 3);
                if ($bom !== pack('CCC', 0xEF, 0xBB, 0xBF)) {
                    rewind($handle);
                }
                
                $row = 0;
                $imported = 0;
                $updated = 0;
                $skipped = 0;
                $errors = [];
                
                $pdo->beginTransaction();
                
                while (($data = fgetcsv($handle, 1000, ';')) !== FALSE) {
                    $row++;
                    
                    // Salta header
                    if ($row === 1 && stripos($data[0], 'nome') !== false) {
                        continue;
                    }
                    
                    // Salta righe vuote
                    if (empty($data[0]) || count($data) < 2) {
                        continue;
                    }
                    
                    $name = trim($data[0] ?? '');
                    $category = trim(strtolower($data[1] ?? 'chest'));
                    $equipment = trim($data[2] ?? '');
                    $description = trim($data[3] ?? '');
                    $instructions = trim($data[4] ?? '');
                    $image_url = trim($data[5] ?? '');
                    
                    // Validazione
                    if (empty($name)) {
                        $errors[] = "Riga $row: Nome esercizio vuoto";
                        continue;
                    }
                    
                    // Verifica categoria valida
                    $valid_categories = ['chest', 'back', 'legs', 'shoulders', 'arms', 'core', 'cardio'];
                    if (!in_array($category, $valid_categories)) {
                        $category = 'chest'; // Default
                    }
                    
                    // Verifica se esiste già
                    $stmt = $pdo->prepare("SELECT id FROM exercises WHERE name = ?");
                    $stmt->execute([$name]);
                    $existing = $stmt->fetch();
                    
                    if ($existing) {
                        if ($import_mode === 'update') {
                            // Aggiorna esistente
                            $stmt = $pdo->prepare("
                                UPDATE exercises 
                                SET category = ?, equipment = ?, description = ?, 
                                    instructions = ?, image_url = ?
                                WHERE id = ?
                            ");
                            $stmt->execute([
                                $category, $equipment, $description, 
                                $instructions, $image_url, $existing['id']
                            ]);
                            $updated++;
                        } elseif ($import_mode === 'replace') {
                            // Elimina e ricrea
                            $stmt = $pdo->prepare("DELETE FROM exercises WHERE id = ?");
                            $stmt->execute([$existing['id']]);
                            
                            $stmt = $pdo->prepare("
                                INSERT INTO exercises 
                                (name, category, equipment, description, instructions, image_url) 
                                VALUES (?, ?, ?, ?, ?, ?)
                            ");
                            $stmt->execute([
                                $name, $category, $equipment, 
                                $description, $instructions, $image_url
                            ]);
                            $imported++;
                        } else {
                            // Skip
                            $skipped++;
                        }
                    } else {
                        // Inserisci nuovo
                        $stmt = $pdo->prepare("
                            INSERT INTO exercises 
                            (name, category, equipment, description, instructions, image_url) 
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $name, $category, $equipment, 
                            $description, $instructions, $image_url
                        ]);
                        $imported++;
                    }
                }
                
                fclose($handle);
                $pdo->commit();
                
                $success = "Importazione completata!<br>
                           ✅ Importati: $imported<br>
                           🔄 Aggiornati: $updated<br>
                           ⏭️ Saltati: $skipped";
                
                if (!empty($errors)) {
                    $success .= "<br><br>⚠️ Errori:<br>" . implode("<br>", array_slice($errors, 0, 10));
                }
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Errore durante l\'import: ' . $e->getMessage();
            }
        } else {
            $error = 'Formato file non valido. Usa CSV.';
        }
    } else {
        $error = 'Errore nel caricamento del file.';
    }
}

$cats = ['chest'=>'Petto', 'back'=>'Schiena', 'legs'=>'Gambe', 'shoulders'=>'Spalle', 'arms'=>'Braccia', 'core'=>'Core', 'cardio'=>'Cardio'];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import/Export Esercizi - GymPro</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="flex-between mb-2" style="margin-top: 16px;">
            <a href="archive.php" class="btn btn-sm" style="background: var(--surface); color: var(--foreground);">← Archivio</a>
            <h2 style="margin: 0; font-size: 20px;">📥 Import/Export</h2>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <!-- Sezione EXPORT -->
        <div class="card">
            <div class="card-header">
                <h3>📤 Esporta Archivio</h3>
            </div>
            <div class="card-body">
                <p class="text-muted" style="margin-bottom: 16px;">
                    Scarica l'archivio esercizi esistente in formato CSV (compatibile con Excel). 
                    Puoi usarlo come template per modifiche o backup.
                </p>
                <a href="?action=export" class="btn btn-primary btn-block">
                    ️ Scarica Archivio Completo (CSV)
                </a>
            </div>
        </div>
        
        <!-- Sezione IMPORT -->
        <div class="card">
            <div class="card-header">
                <h3> Importa Esercizi</h3>
            </div>
            <div class="card-body">
                <p class="text-muted" style="margin-bottom: 16px;">
                    Carica un file CSV con i nuovi esercizi. Il file deve seguire la struttura del template scaricabile.
                </p>
                
                <div class="alert alert-warning" style="margin-bottom: 16px;">
                    <strong>Formato richiesto:</strong> CSV con separatore punto e virgola (;)<br>
                    <strong>Encoding:</strong> UTF-8<br>
                    <strong>Colonne:</strong> Nome, Categoria, Attrezzatura, Descrizione, Istruzioni, URL Immagine
                </div>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>File CSV *</label>
                        <input type="file" name="import_file" class="form-control" accept=".csv" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Gestione Duplicati</label>
                        <select name="import_mode" class="form-control">
                            <option value="skip">Salta esercizi già esistenti</option>
                            <option value="update">Aggiorna esercizi esistenti</option>
                            <option value="replace">Elimina e ricrea esercizi esistenti</option>
                        </select>
                        <small class="text-muted">
                            Scegli come gestire gli esercizi con lo stesso nome già presenti nell'archivio.
                        </small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">
                        📤 Importa Esercizi
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Template Download -->
        <div class="card">
            <div class="card-header">
                <h3>📋 Template di Esempio</h3>
            </div>
            <div class="card-body">
                <p class="text-muted" style="margin-bottom: 16px;">
                    Scarica un template vuoto per capire la struttura corretta del file.
                </p>
                <a href="download-template.php" class="btn btn-secondary btn-block">
                    📄 Scarica Template Vuoto
                </a>
            </div>
        </div>
    </div>
    
    <?php include '../includes/bottom-nav.php'; ?>
</body>
</html>