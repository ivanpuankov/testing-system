<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Проверяем, что пользователь - студент (role_id = 1)
requireRole($pdo, 1);

// Получаем данные текущего пользователя
$user = getCurrentUser($pdo);

// Получаем уровень пользователя
$stmt = $pdo->prepare("SELECT * FROM levels WHERE min_xp <= ? ORDER BY min_xp DESC LIMIT 1");
$stmt->execute([$user['current_xp']]);
$level = $stmt->fetch();

// Получаем следующий уровень для прогресс-бара
$stmt = $pdo->prepare("SELECT * FROM levels WHERE min_xp > ? ORDER BY min_xp LIMIT 1");
$stmt->execute([$user['current_xp']]);
$next_level = $stmt->fetch();

// Получаем группу студента
$student_group_id = $user['group_id'];

// Получаем список доступных тестов (активных и доступных группе студента)
if ($student_group_id) {
    $stmt = $pdo->prepare("
        SELECT * FROM tests 
        WHERE is_active = 1 
        AND (group_id IS NULL OR group_id = ?)
        ORDER BY created_at DESC
    ");
    $stmt->execute([$student_group_id]);
} else {
    $stmt = $pdo->prepare("
        SELECT * FROM tests 
        WHERE is_active = 1 
        AND group_id IS NULL
        ORDER BY created_at DESC
    ");
    $stmt->execute();
}
$tests = $stmt->fetchAll();

// Функция для получения количества попыток студента по тесту
function getAttemptsCount($pdo, $test_id, $student_id) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as attempts_count 
        FROM test_attempts 
        WHERE test_id = ? AND student_id = ? AND status = 'completed'
    ");
    $stmt->execute([$test_id, $student_id]);
    return $stmt->fetch()['attempts_count'];
}

// Функция для проверки, можно ли проходить тест
function canTakeTest($pdo, $test, $student_id) {
    if ($test['max_attempts'] == 0) {
        return true;
    }
    $attempts_done = getAttemptsCount($pdo, $test['id'], $student_id);
    return $attempts_done < $test['max_attempts'];
}

// Функция для получения оставшихся попыток
function getRemainingAttempts($pdo, $test, $student_id) {
    if ($test['max_attempts'] == 0) {
        return '∞';
    }
    $attempts_done = getAttemptsCount($pdo, $test['id'], $student_id);
    $remaining = $test['max_attempts'] - $attempts_done;
    return max(0, $remaining);
}

// Получаем количество полученных ачивок
$stmt = $pdo->prepare("SELECT COUNT(*) as earned_count FROM user_achievements WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$earned_achievements = $stmt->fetch()['earned_count'];

// Получаем общее количество ачивок
$stmt = $pdo->query("SELECT COUNT(*) as total_count FROM achievements");
$total_achievements = $stmt->fetch()['total_count'];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Студент - Система тестирования</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #f0f2f5;
            padding: 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        
        /* Шапка */
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
        .header h1 { color: #333; font-size: 24px; }
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .user-name {
            font-weight: bold;
            color: #667eea;
        }
        .group-badge {
            background: #3498db;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
        }
        .logout {
            background: #dc3545;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 5px;
        }
        .logout:hover { background: #c82333; }
        
        /* Карточка уровня */
        .level-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .level-card h2 { margin-bottom: 10px; font-size: 28px; }
        .level-icon { font-size: 40px; margin-bottom: 10px; }
        .stats {
            display: flex;
            gap: 30px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        .stat {
            background: rgba(255,255,255,0.2);
            padding: 10px 15px;
            border-radius: 8px;
        }
        .progress-bar {
            background: rgba(255,255,255,0.3);
            border-radius: 10px;
            height: 25px;
            margin-top: 15px;
            overflow: hidden;
        }
        .progress-fill {
            background: #ffd700;
            border-radius: 10px;
            height: 25px;
            width: 0%;
            transition: width 0.5s ease;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 10px;
            color: #333;
            font-weight: bold;
            font-size: 12px;
        }
        
        /* Секция тестов */
        .section-title {
            font-size: 22px;
            margin: 20px 0 15px 0;
            color: #333;
        }
        .test-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .test-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .test-card:hover { transform: translateY(-3px); }
        .test-card h3 { margin-bottom: 10px; color: #333; }
        .test-card p { color: #666; margin-bottom: 8px; }
        .attempts-info {
            background: #f8f9fa;
            padding: 5px 10px;
            border-radius: 5px;
            display: inline-block;
            margin: 10px 0;
            font-size: 14px;
        }
        .attempts-left { color: #27ae60; font-weight: bold; }
        .attempts-zero { color: #e74c3c; font-weight: bold; }
        .btn-start {
            display: inline-block;
            background: #27ae60;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 10px;
        }
        .btn-start:hover { background: #219a52; }
        .btn-disabled {
            display: inline-block;
            background: #95a5a6;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            margin-top: 10px;
            cursor: not-allowed;
        }
        
        /* Навигация */
        .nav-links {
            background: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            margin-top: 20px;
        }
        .nav-links a {
            color: #667eea;
            text-decoration: none;
            margin: 0 15px;
            font-weight: bold;
        }
        .nav-links a:hover { text-decoration: underline; }
        
        @media (max-width: 768px) {
            .stats { flex-direction: column; gap: 10px; }
            .test-grid { grid-template-columns: 1fr; }
            .header { flex-direction: column; gap: 10px; text-align: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Шапка -->
        <div class="header">
            <h1>📚 Система тестирования</h1>
            <div class="user-info">
                <span class="user-name">👋 <?= htmlspecialchars($user['full_name']) ?></span>
                <?php if ($user['group_id']): 
                    $stmt = $pdo->prepare("SELECT name FROM groups WHERE id = ?");
                    $stmt->execute([$user['group_id']]);
                    $group = $stmt->fetch();
                ?>
                    <span class="group-badge">👥 <?= htmlspecialchars($group['name']) ?></span>
                <?php endif; ?>
                <a href="/testing_system/logout.php" class="logout">Выход</a>
            </div>
        </div>

        <!-- Карточка уровня и прогресса -->
        <div class="level-card">
            <div class="level-icon">🏆</div>
            <h2><?= htmlspecialchars($level['level_name']) ?></h2>
            
            <div class="stats">
                <div class="stat">✨ Опыт: <strong><?= $user['current_xp'] ?> XP</strong></div>
                <div class="stat">📊 Тестов пройдено: <strong><?= $user['total_tests_passed'] ?></strong></div>
                <div class="stat">✅ Правильных ответов: <strong><?= $user['total_correct_answers'] ?></strong></div>
                <div class="stat">🎖️ Достижений: <strong><?= $earned_achievements ?> / <?= $total_achievements ?></strong></div>
            </div>
            
            <?php if ($next_level): 
                $xp_needed = $next_level['min_xp'] - $user['current_xp'];
                $xp_current = $user['current_xp'] - $level['min_xp'];
                $xp_for_next = $next_level['min_xp'] - $level['min_xp'];
                $percent = ($xp_current / $xp_for_next) * 100;
            ?>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= $percent ?>%;">
                        <?= round($percent) ?>%
                    </div>
                </div>
                <p style="margin-top: 10px; font-size: 14px;">
                    До уровня <strong><?= $next_level['level_name'] ?></strong> осталось <strong><?= $xp_needed ?> XP</strong>
                </p>
            <?php else: ?>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 100%;">MAX</div>
                </div>
                <p style="margin-top: 10px; font-size: 14px;">🏆 Вы достигли максимального уровня!</p>
            <?php endif; ?>
        </div>

        <!-- Доступные тесты -->
        <h2 class="section-title">📖 Доступные тесты</h2>
        
        <?php if (empty($tests)): ?>
            <div style="text-align: center; padding: 40px; background: white; border-radius: 10px;">
                <p>📭 Нет доступных тестов</p>
                <p style="color: #666; margin-top: 10px;">Преподаватели ещё не добавили тесты для вашей группы</p>
            </div>
        <?php else: ?>
            <div class="test-grid">
                <?php foreach ($tests as $test): 
                    $can_take = canTakeTest($pdo, $test, $_SESSION['user_id']);
                    $remaining = getRemainingAttempts($pdo, $test, $_SESSION['user_id']);
                    $attempts_done = getAttemptsCount($pdo, $test['id'], $_SESSION['user_id']);
                ?>
                    <div class="test-card">
                        <h3><?= htmlspecialchars($test['title']) ?></h3>
                        <p><?= htmlspecialchars($test['description']) ?></p>
                        
                        <?php if ($test['time_limit'] > 0): ?>
                            <p>⏱️ Время: <?= $test['time_limit'] ?> минут</p>
                        <?php else: ?>
                            <p>⏱️ Время: без ограничений</p>
                        <?php endif; ?>
                        
                        <?php if ($test['max_attempts'] > 0): ?>
                            <div class="attempts-info">
                                📊 Попыток осталось: 
                                <span class="<?= $remaining > 0 ? 'attempts-left' : 'attempts-zero' ?>">
                                    <?= $remaining ?>
                                </span> 
                                из <?= $test['max_attempts'] ?>
                            </div>
                        <?php else: ?>
                            <div class="attempts-info">📊 Попыток: ∞ (без ограничений)</div>
                        <?php endif; ?>
                        
                        <?php if ($can_take): ?>
                            <a href="take_test.php?id=<?= $test['id'] ?>" class="btn-start">▶️ Начать тест</a>
                        <?php else: ?>
                            <span class="btn-disabled">🔒 Попытки закончились</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Ссылки на рейтинг и достижения -->
        <div class="nav-links">
            <a href="leaderboard.php">🏆 Рейтинг студентов</a>
            <a href="achievements.php">🎖️ Мои достижения</a>
        </div>
    </div>
</body>
</html>