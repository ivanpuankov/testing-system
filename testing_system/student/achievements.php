<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireRole($pdo, 1);

// Получаем все достижения и отмечаем полученные
$stmt = $pdo->prepare("
    SELECT a.*, 
           (SELECT COUNT(*) FROM user_achievements ua WHERE ua.achievement_id = a.id AND ua.user_id = ?) as earned
    FROM achievements a
    ORDER BY a.id
");
$stmt->execute([$_SESSION['user_id']]);
$achievements = $stmt->fetchAll();

// Получаем количество полученных достижений
$stmt = $pdo->prepare("SELECT COUNT(*) as earned_count FROM user_achievements WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$earned_count = $stmt->fetch()['earned_count'];

$total_count = count($achievements);
$percentage = ($earned_count / $total_count) * 100;
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Мои достижения</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #f0f2f5;
            padding: 20px;
        }
        .container { max-width: 1000px; margin: 0 auto; }
        .header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h1 { color: #333; }
        .back-btn {
            background: #667eea;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 5px;
        }
        .progress-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        .progress-bar {
            background: #e0e0e0;
            border-radius: 10px;
            height: 20px;
            margin: 10px 0;
            overflow: hidden;
        }
        .progress-fill {
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 10px;
            height: 20px;
        }
        .stats {
            font-size: 18px;
            margin-top: 10px;
        }
        .achievements-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        .achievement-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .achievement-card:hover { transform: translateY(-5px); }
        .earned { border: 2px solid #27ae60; }
        .locked { opacity: 0.6; filter: grayscale(0.3); }
        .icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .achievement-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .achievement-desc {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        .xp-reward {
            font-size: 14px;
            color: #f39c12;
            font-weight: bold;
        }
        .earned-badge {
            display: inline-block;
            background: #27ae60;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            margin-top: 10px;
        }
        .locked-badge {
            display: inline-block;
            background: #95a5a6;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎖️ Мои достижения</h1>
            <a href="index.php" class="back-btn">← Назад</a>
        </div>

        <div class="progress-section">
            <h3>Прогресс коллекционирования</h3>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?= $percentage ?>%;"></div>
            </div>
            <div class="stats">
                Получено: <?= $earned_count ?> из <?= $total_count ?> достижений (<?= round($percentage) ?>%)
            </div>
        </div>

        <div class="achievements-grid">
            <?php foreach ($achievements as $achievement): ?>
                <div class="achievement-card <?= $achievement['earned'] ? 'earned' : 'locked' ?>">
                    <div class="icon"><?= htmlspecialchars($achievement['icon']) ?></div>
                    <div class="achievement-name"><?= htmlspecialchars($achievement['name']) ?></div>
                    <div class="achievement-desc"><?= htmlspecialchars($achievement['description']) ?></div>
                    <div class="xp-reward">+<?= $achievement['xp_reward'] ?> XP</div>
                    <?php if ($achievement['earned']): ?>
                        <div class="earned-badge">✅ Получено</div>
                    <?php else: ?>
                        <div class="locked-badge">🔒 Не получено</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>