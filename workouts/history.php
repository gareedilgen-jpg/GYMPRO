<?php
require_once '../config/database.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Recupera storico sessioni (ultime 50 per avere più dati)
$stmt = $pdo->prepare("
    SELECT ws.*, w.name as workout_name, w.day_of_week 
    FROM workout_sessions ws
    JOIN workouts w ON ws.workout_id = w.id
    WHERE ws.user_id = ?
    ORDER BY ws.session_date DESC
    LIMIT 50
");
$stmt->execute([$user_id]);
$sessions = $stmt->fetchAll();

// Pre-carica tutte le serie per calcolare volumi e dettagli senza query N+1
$session_ids = array_column($sessions, 'id');
$all_sets = [];
$total_volumes = [];

if (!empty($session_ids)) {
    $placeholders = str_repeat('?,', count($session_ids) - 1) . '?';
    $stmt = $pdo->prepare("
        SELECT ws.*, e.name as exercise_name 
        FROM workout_sets ws
        JOIN exercises e ON ws.exercise_id = e.id
        WHERE ws.session_id IN ($placeholders)
        ORDER BY ws.session_id, e.name, ws.set_number
    ");
    $stmt->execute($session_ids);
    
    while ($row = $stmt->fetch()) {
        $sid = $row['session_id'];
        $all_sets[$sid][] = $row;
        
        // Volume = Peso * Ripetizioni (solo se completata)
        if ($row['completed']) {
            if (!isset($total_volumes[$sid])) $total_volumes[$sid] = 0;
            $total_volumes[$sid] += ($row['weight'] * $row['reps']);
        }
    }
}

$days_italian = [
    'monday' => 'Lun', 'tuesday' => 'Mar', 'wednesday' => 'Mer',
    'thursday' => 'Gio', 'friday' => 'Ven', 'saturday' => 'Sab', 'sunday' => 'Dom'
];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Storico Allenamenti - GymPro</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Badge di stato */
        .status-badge { 
            padding: 4px 10px; border-radius: 12px; font-size: 10px; 
            font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; 
        }
        .status-completed { background: rgba(34, 197, 94, 0.15); color: var(--success); border: 1px solid rgba(34, 197, 94, 0.3); }
        .status-partial { background: rgba(245, 158, 11, 0.15); color: var(--warning); border: 1px solid rgba(245, 158, 11, 0.3); }
        .status-progress { background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); }
        
        /* Volume Display */
        .volume-display { 
            font-family: 'Barlow Condensed', sans-serif; font-size: 26px; 
            font-weight: 700; color: var(--primary); line-height: 1; 
        }
        .volume-label { font-size: 10px; color: var(--muted-foreground); text-transform: uppercase; margin-top: 4px;}
        
        /* Modal Bottom Sheet per Dettagli */
        .modal-overlay {
            display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.85); z-index: 1000;
            align-items: flex-end; justify-content: center; backdrop-filter: blur(4px);
        }
        .modal-overlay.active { display: flex; }
        .modal-sheet {
            background: var(--card); width: 100%; max-width: 600px; 
            max-height: 85vh; border-radius: 20px 20px 0 0; padding: 24px;
            overflow-y: auto; border-top: 4px solid var(--primary);
            animation: slideUp 0.3s ease-out;
        }
        @keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
        
        .detail-exercise { margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid var(--border); }
        .detail-exercise:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .detail-set-row { 
            display: flex; justify-content: space-between; padding: 8px 0; 
            font-size: 14px; border-bottom: 1px solid rgba(255,255,255,0.05); 
        }
        .detail-set-row:last-child { border-bottom: none; }
        .detail-set-row.done { color: var(--foreground); }
        .detail-set-row.missed { color: var(--muted-foreground); opacity: 0.6; }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="flex-between mb-2" style="margin-top: 16px;">
            <a href="index.php" class="btn btn-sm" style="background: var(--surface); color: var(--foreground);">← Indietro</a>
            <h2 style="margin: 0; font-size: 20px;">Storico Allenamenti</h2>
        </div>
        
        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success">Sessione eliminata dallo storico.</div>
        <?php endif; ?>
        
        <?php if (empty($sessions)): ?>
            <div class="card">
                <div class="card-body text-center" style="padding: 60px 20px;">
                    <p style="font-size: 64px; margin-bottom: 16px; opacity: 0.5;">📭</p>
                    <h3>Nessuno storico disponibile</h3>
                    <p class="text-muted" style="margin-top: 8px;">Completa il tuo primo allenamento per vederlo apparire qui.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($sessions as $session): 
                $volume = $total_volumes[$session['id']] ?? 0;
                
                // Determina classe e testo stato
                $status_class = 'status-progress'; $status_text = 'In Corso';
                if ($session['status'] === 'completed') {
                    $status_class = 'status-completed'; $status_text = 'Completato';
                } elseif ($session['status'] === 'partial') {
                    $status_class = 'status-partial'; $status_text = 'Parziale';
                }
            ?>
                <div class="card" style="margin-bottom: 12px; transition: transform 0.2s;">
                    <div class="card-body" style="padding: 16px;">
                        <!-- Header Card -->
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 16px;">
                            <div>
                                <div style="font-size: 11px; color: var(--muted-foreground); text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">
                                    <?= $days_italian[$session['day_of_week']] ?? '' ?> • <?= date('d/m/Y', strtotime($session['session_date'])) ?>
                                </div>
                                <h3 style="margin: 6px 0 0 0; font-size: 18px; color: var(--foreground); line-height: 1.2;">
                                    <?= htmlspecialchars($session['workout_name']) ?>
                                </h3>
                            </div>
                            <span class="status-badge <?= $status_class ?>"><?= $status_text ?></span>
                        </div>
                        
                        <!-- Footer Card con Volume e Azioni -->
                        <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 16px; border-top: 1px solid var(--border);">
                            <div>
                                <div class="volume-display"><?= number_format($volume, 0, ',', '.') ?> <span style="font-size: 14px; color: var(--muted-foreground); font-weight: 400;">kg</span></div>
                                <div class="volume-label">Volume Totale</div>
                            </div>
                            
                            <div style="display: flex; gap: 8px;">
                                <?php if ($session['status'] !== 'completed'): ?>
                                    <a href="view.php?id=<?= $session['workout_id'] ?>&session=<?= $session['id'] ?>" class="btn btn-sm btn-primary" style="padding: 8px 12px;">
                                        ▶️ Riprendi
                                    </a>
                                <?php endif; ?>
                                
                                <button onclick="openDetails(<?= $session['id'] ?>)" class="btn btn-sm btn-secondary" style="padding: 8px 12px;">
                                     Dettagli
                                </button>
                                
                                <a href="delete-session.php?id=<?= $session['id'] ?>" class="btn btn-sm" style="padding: 8px 12px; background: transparent; border: 1px solid var(--destructive); color: var(--destructive);" onclick="return confirm('Eliminare definitivamente questa sessione?')">
                                    🗑️
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- MODAL DETTAGLI SESSIONE -->
                <div class="modal-overlay" id="modal-<?= $session['id'] ?>">
                    <div class="modal-sheet">
                        <div class="flex-between mb-2">
                            <div>
                                <h3 style="margin: 0; font-size: 20px;">Recap Sessione</h3>
                                <p class="text-muted" style="margin: 4px 0 0 0; font-size: 13px;">
                                    <?= date('d/m/Y H:i', strtotime($session['session_date'])) ?> • <?= $session['duration_minutes'] ?> min
                                </p>
                            </div>
                            <button onclick="closeDetails(<?= $session['id'] ?>)" class="btn btn-sm" style="background: var(--surface-strong); width: 36px; height: 36px; padding: 0; display: flex; align-items: center; justify-content: center; border-radius: 50%;">✕</button>
                        </div>
                        
                        <?php 
                        $sets_data = $all_sets[$session['id']] ?? [];
                        if (empty($sets_data)): 
                        ?>
                            <div style="text-align: center; padding: 40px 0; color: var(--muted-foreground);">
                                <p style="font-size: 32px; margin-bottom: 8px;"></p>
                                <p>Nessuna serie registrata per questa sessione.</p>
                            </div>
                        <?php else: 
                            // Raggruppa serie per esercizio
                            $grouped_sets = [];
                            foreach ($sets_data as $set) {
                                $grouped_sets[$set['exercise_name']][] = $set;
                            }
                            
                            foreach ($grouped_sets as $ex_name => $ex_sets): 
                        ?>
                            <div class="detail-exercise">
                                <div style="font-weight: 700; font-size: 16px; margin-bottom: 12px; color: var(--primary); display: flex; align-items: center; gap: 8px;">
                                    <span style="width: 6px; height: 6px; background: var(--primary); border-radius: 50%;"></span>
                                    <?= htmlspecialchars($ex_name) ?>
                                </div>
                                <?php foreach ($ex_sets as $set): ?>
                                    <div class="detail-set-row <?= $set['completed'] ? 'done' : 'missed' ?>">
                                        <span style="color: var(--muted-foreground); font-size: 12px; width: 50px;">Serie <?= $set['set_number'] ?></span>
                                        <span style="flex: 1; text-align: center;">
                                            <strong><?= $set['weight'] ?></strong> kg × <strong><?= $set['reps'] ?></strong> rip
                                        </span>
                                        <span style="width: 24px; text-align: right;">
                                            <?= $set['completed'] ? '✅' : '' ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; endif; ?>
                        
                        <button onclick="closeDetails(<?= $session['id'] ?>)" class="btn btn-block btn-secondary" style="margin-top: 24px; padding: 14px;">Chiudi Recap</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <?php include '../includes/bottom-nav.php'; ?>
    
    <script>
    function openDetails(id) {
        document.getElementById('modal-' + id).classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closeDetails(id) {
        document.getElementById('modal-' + id).classList.remove('active');
        document.body.style.overflow = '';
    }
    
    // Chiudi modal cliccando sullo sfondo scuro
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });
    </script>
</body>
</html>