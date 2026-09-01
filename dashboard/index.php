<?php
require_once '../config/database.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$user_data = getUserData($pdo, $user_id);

$today = date('Y-m-d');
$today_day = date('w'); // 0=domenica, 1=lunedì, ..., 6=sabato
$week_start = date('Y-m-d', strtotime('monday this week'));
$week_end = date('Y-m-d', strtotime('sunday this week'));

// Mappatura giorni PHP -> DB
$php_to_db_days = [
    1 => 'monday',
    2 => 'tuesday',
    3 => 'wednesday',
    4 => 'thursday',
    5 => 'friday',
    6 => 'saturday',
    0 => 'sunday'
];

$days_italian = [
    'monday' => 'Lunedì',
    'tuesday' => 'Martedì',
    'wednesday' => 'Mercoledì',
    'thursday' => 'Giovedì',
    'friday' => 'Venerdì',
    'saturday' => 'Sabato',
    'sunday' => 'Domenica'
];

$today_db_day = $php_to_db_days[$today_day] ?? null;

// Statistiche settimana
$stmt = $pdo->prepare("SELECT COUNT(*) as total_sessions, COALESCE(SUM(duration_minutes), 0) as total_minutes FROM workout_sessions WHERE user_id = ? AND session_date >= ? AND session_date <= ?");
$stmt->execute([$user_id, $week_start, $week_end]);
$week_stats = $stmt->fetch();

// Ultimo peso
$stmt = $pdo->prepare("SELECT weight FROM progress_tracking WHERE user_id = ? ORDER BY track_date DESC LIMIT 1");
$stmt->execute([$user_id]);
$last_weight = $stmt->fetchColumn();

// ALLENAMENTO DEL GIORNO
$stmt = $pdo->prepare("
    SELECT w.*, 
           (SELECT COUNT(*) FROM workout_exercises WHERE workout_id = w.id) as exercise_count
    FROM workouts w 
    WHERE w.user_id = ? AND w.is_active = TRUE AND w.day_of_week = ?
    LIMIT 1
");
$stmt->execute([$user_id, $today_db_day]);
$today_workout = $stmt->fetch();

// Se c'è un allenamento oggi, recupera il primo esercizio
$today_first_exercise = null;
if ($today_workout) {
    $stmt = $pdo->prepare("
        SELECT we.*, e.name as exercise_name, e.category, e.image_url, e.instructions
        FROM workout_exercises we
        JOIN exercises e ON we.exercise_id = e.id
        WHERE we.workout_id = ?
        ORDER BY we.order_num
        LIMIT 1
    ");
    $stmt->execute([$today_workout['id']]);
    $today_first_exercise = $stmt->fetch();
}

// Macro oggi
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_calories), 0) as total_calories, COALESCE(SUM(total_protein), 0) as total_protein, COALESCE(SUM(total_carbs), 0) as total_carbs, COALESCE(SUM(total_fats), 0) as total_fats FROM meals WHERE user_id = ? AND meal_date = ?");
$stmt->execute([$user_id, $today]);
$today_nutrition = $stmt->fetch();

$target_calories = $user_data['target_calories'] ?? 2900;
$target_protein = $user_data['target_protein'] ?? 160;
$target_carbs = $user_data['target_carbs'] ?? 400;
$target_fats = $user_data['target_fats'] ?? 85;

$pct_protein = $target_protein > 0 ? min(100, ($today_nutrition['total_protein'] / $target_protein) * 100) : 0;
$pct_carbs = $target_carbs > 0 ? min(100, ($today_nutrition['total_carbs'] / $target_carbs) * 100) : 0;
$pct_fats = $target_fats > 0 ? min(100, ($today_nutrition['total_fats'] / $target_fats) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dashboard - GymPro</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <?php if (!empty($user_data['profile_photo'])): ?>
                <img src="<?= htmlspecialchars($user_data['profile_photo']) ?>" alt="Foto profilo" class="profile-photo-dashboard">
            <?php else: ?>
                <div class="profile-photo-dashboard" style="background: var(--surface-strong); display: flex; align-items: center; justify-content: center; font-size: 36px; color: var(--muted-foreground); margin-right: 16px;">
                    👤
                </div>
            <?php endif; ?>
            <div class="welcome-text">
                <h2>Ciao, <?= htmlspecialchars($user_data['full_name']) ?> 👋</h2>
                <p class="text-muted">
                     <?= $days_italian[$today_db_day] ?? 'Oggi' ?>, <?= date('d/m/Y') ?>
                </p>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <img src="/assets/images/weight.png" alt="Peso" style="width: 28px; height: 28px; object-fit: contain;">
                </div>
                <div class="stat-info">
                    <div class="stat-value num"><?= $last_weight ? number_format($last_weight, 1) . ' kg' : '--' ?></div>
                    <div class="stat-label">Peso attuale</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <img src="/assets/images/fire.png" alt="Kcal" style="width: 28px; height: 28px; object-fit: contain;">
                </div>
                <div class="stat-info">
                    <div class="stat-value num text-kcal"><?= number_format($today_nutrition['total_calories']) ?></div>
                    <div class="stat-label">Kcal oggi</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <img src="/assets/images/crossfit.png" alt="Allenamenti" style="width: 28px; height: 28px; object-fit: contain;">
                </div>
                <div class="stat-info">
                    <div class="stat-value num"><?= $week_stats['total_sessions'] ?></div>
                    <div class="stat-label">Allenamenti/sett</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <img src="/assets/images/time-left.png" alt="Minuti" style="width: 28px; height: 28px; object-fit: contain;">
                </div>
                <div class="stat-info">
                    <div class="stat-value num"><?= $week_stats['total_minutes'] ?>'</div>
                    <div class="stat-label">Minuti totali</div>
                </div>
            </div>
        </div>
        
        <!-- ALLENAMENTO DEL GIORNO -->
        <?php if ($today_workout): ?>
        <div class="card highlight-card">
            <div class="card-header">
                <h3>🎯 Allenamento di <?= $days_italian[$today_db_day] ?? 'Oggi' ?></h3>
            </div>
            <div class="card-body">
                <div style="margin-bottom: 16px;">
                    <h4 style="font-size: 22px; margin: 0 0 8px 0; color: var(--foreground);">
                        <?= htmlspecialchars($today_workout['name']) ?>
                    </h4>
                    <p class="text-muted" style="margin: 0;">
                        <?= $today_workout['exercise_count'] ?> esercizi • 
                        <?= $today_workout['duration_minutes'] ?> min • 
                        <?= htmlspecialchars($today_workout['focus_area']) ?>
                    </p>
                </div>
                
                <!-- Primo esercizio del giorno -->
                <?php if ($today_first_exercise): ?>
                <div style="background: var(--surface-strong); border-radius: var(--radius-md); padding: 12px; margin-bottom: 16px; border-left: 3px solid var(--primary);">
                    <div style="font-size: 11px; color: var(--primary); text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em; margin-bottom: 4px;">
                        Primo esercizio
                    </div>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <?php if (!empty($today_first_exercise['image_url'])): ?>
                            <img src="<?= htmlspecialchars($today_first_exercise['image_url']) ?>" 
                                 alt="<?= htmlspecialchars($today_first_exercise['exercise_name']) ?>" 
                                 style="width: 60px; height: 60px; border-radius: var(--radius-md); object-fit: cover;">
                        <?php else: ?>
                            <div style="width: 60px; height: 60px; border-radius: var(--radius-md); background: var(--surface); display: flex; align-items: center; justify-content: center; font-size: 28px;">💪</div>
                        <?php endif; ?>
                        <div style="flex: 1;">
                            <div style="font-weight: 700; font-size: 16px; color: var(--foreground);">
                                <?= htmlspecialchars($today_first_exercise['exercise_name']) ?>
                            </div>
                            <div style="font-size: 13px; color: var(--muted-foreground);">
                                <?= $today_first_exercise['sets'] ?> serie × <?= htmlspecialchars($today_first_exercise['reps']) ?> rip
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <a href="../workouts/view.php?id=<?= $today_workout['id'] ?>" class="btn btn-primary btn-block">
                    ▶️ Inizia Allenamento
                </a>
            </div>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="card-body text-center" style="padding: 32px 20px;">
                <p style="font-size: 48px; margin-bottom: 12px;">😴</p>
                <h3 style="margin-bottom: 8px;">Giorno di Riposo</h3>
                <p class="text-muted">Oggi è <?= $days_italian[$today_db_day] ?? 'oggi' ?>. Recupera e preparati per il prossimo allenamento!</p>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Macro Giornalieri -->
        <div class="card">
            <div class="card-header">
                <h3>🍽️ Macro Giornalieri</h3>
            </div>
            <div class="card-body">
                <div class="macro-bars">
                    <div class="macro-item">
                        <div class="flex-between">
                            <span class="macro-label">Proteine</span>
                            <span class="macro-value num"><span class="text-protein"><?= number_format($today_nutrition['total_protein']) ?>g</span> / <?= $target_protein ?>g</span>
                        </div>
                        <div class="macro-bar">
                            <div class="macro-fill protein" style="width: <?= $pct_protein ?>%"></div>
                        </div>
                    </div>
                    <div class="macro-item">
                        <div class="flex-between">
                            <span class="macro-label">Carboidrati</span>
                            <span class="macro-value num"><span class="text-carbs"><?= number_format($today_nutrition['total_carbs']) ?>g</span> / <?= $target_carbs ?>g</span>
                        </div>
                        <div class="macro-bar">
                            <div class="macro-fill carbs" style="width: <?= $pct_carbs ?>%"></div>
                        </div>
                    </div>
                    <div class="macro-item">
                        <div class="flex-between">
                            <span class="macro-label">Grassi</span>
                            <span class="macro-value num"><span class="text-fat"><?= number_format($today_nutrition['total_fats']) ?>g</span> / <?= $target_fats ?>g</span>
                        </div>
                        <div class="macro-bar">
                            <div class="macro-fill fats" style="width: <?= $pct_fats ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h3>⚡ Azioni Rapide</h3>
            </div>
            <div class="card-body">
                <div class="quick-actions">
                    <a href="../workouts/index.php" class="action-btn">
                        <span class="action-icon">💪</span>
                        <span>Allenamenti</span>
                    </a>
                    <a href="../nutrition/index.php" class="action-btn">
                        <span class="action-icon">🍽️</span>
                        <span>Nutrizione</span>
                    </a>
                    <a href="../progress/index.php" class="action-btn">
                        <span class="action-icon"></span>
                        <span>Progressi</span>
                    </a>
                    <a href="../exercises/archive.php" class="action-btn">
                        <span class="action-icon">📚</span>
                        <span>Esercizi</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/bottom-nav.php'; ?>
    <script src="../assets/js/app.js"></script>
</body>
</html>