<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireRole($pdo, 1);

// Получаем топ-10 студентов по опыту
$stmt = $pdo->query("
    SELECT u.id, u.full_name, u.current_xp, l.level_name, u.total_tests_passed
    FROM users u
    JOIN levels l ON u.current_level_id = l.id
    WHERE u.role_id = 1
    ORDER BY u.current_xp DESC
    LIMIT 10
");
$leaders = $stmt->fetchAll();

// Место текущего пользователя
$stmt = $pdo->prepare("
    SELECT COUNT(*) + 1 as rank
    FROM users
    WHERE role_id = 1 AND current_xp > (SELECT current_xp FROM users WHERE id = ?)
");
$stmt->execute([$_SESSION['user_id']]);
$user_rank = $stmt->fetch()['rank'];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Рейтинг студентов</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #f0f2f5;
            padding: 20px;
        }
        .container { max-width: 800px; margin: 0 auto; }
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
        .leaderboard {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .leader-item {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
        }
        .leader-item:last-child { border-bottom: none; }
        .rank {
            font-size: 24px;
            font-weight: bold;
            width: 60px;
            color: #667eea;
        }
        .rank-1 { color: #ffd700; }
        .rank-2 { color: #c0c0c0; }
        .rank-3 { color: #cd7f32; }
        .info {
            flex: 1;
        }
        .name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .level {
            font-size: 14px;
            color: #666;
        }
        .stats {
            text-align: right;
        }
        .xp {
            font-size: 20px;
            font-weight: bold;
            color: #27ae60;
        }
        .tests {
            font-size: 14px;
            color: #666;
        }
        .current-user {
            background: #e8f0fe;
            border-left: 4px solid #667eea;
        }
        .my-rank {
            background: white;
            margin-top: 20px;
            border-radius: 10px;
            padding: 15px 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏆 Рейтинг студентов</h1>
            <a href="index.php" class="back-btn">← Назад</a>
        </div>

        <div class="leaderboard">
            <?php foreach ($leaders as $index => $leader): ?>
                <?php
                $rank_class = '';
                if ($index == 0) $rank_class = 'rank-1';
                elseif ($index == 1) $rank_class = 'rank-2';
                elseif ($index == 2) $rank_class = 'rank-3';
                ?>
                <div class="leader-item <?= ($leader['id'] == $_SESSION['user_id']) ? 'current-user' : '' ?>">
                    <div class="rank <?= $rank_class ?>">#<?= $index + 1 ?></div>
                    <div class="info">
                        <div class="name"><?= htmlspecialchars($leader['full_name']) ?></div>
                        <div class="level"><?= htmlspecialchars($leader['level_name']) ?></div>
                    </div>
                    <div class="stats">
                        <div class="xp"><?= $leader['current_xp'] ?> XP</div>
                        <div class="tests">📊 <?= $leader['total_tests_passed'] ?> тестов</div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="my-rank">
            <strong>🎯 Ваше место в рейтинге: #<?= $user_rank ?></strong>
        </div>
    </div>
</body>
</html>