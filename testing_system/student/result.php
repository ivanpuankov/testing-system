<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireRole($pdo, 1);

$attempt_id = $_GET['attempt_id'] ?? 0;
$xp_earned = $_GET['xp'] ?? 0;
$level_up = $_GET['level_up'] ?? 0;

// Получаем результат
$stmt = $pdo->prepare("
    SELECT ta.*, t.title 
    FROM test_attempts ta 
    JOIN tests t ON ta.test_id = t.id 
    WHERE ta.id = ? AND ta.student_id = ?
");
$stmt->execute([$attempt_id, $_SESSION['user_id']]);
$attempt = $stmt->fetch();

if (!$attempt) {
    die("Результат не найден");
}

// Получаем общее количество вопросов
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM questions WHERE test_id = ?");
$stmt->execute([$attempt['test_id']]);
$total_questions = $stmt->fetch()['total'];

$percentage = ($attempt['score'] / $total_questions) * 100;
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Результат теста</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .result-card {
            background: white;
            padding: 40px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            max-width: 500px;
        }
        h1 { margin-bottom: 20px; color: #333; }
        .score {
            font-size: 48px;
            font-weight: bold;
            margin: 20px 0;
        }
        .perfect { color: #27ae60; }
        .good { color: #3498db; }
        .bad { color: #e74c3c; }
        .xp-earned {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .level-up {
            background: #f1c40f;
            color: #333;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            font-weight: bold;
        }
        .btn {
            display: inline-block;
            padding: 12px 25px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .btn:hover { background: #5a67d8; }
    </style>
</head>
<body>
    <div class="result-card">
        <h1>📊 Результат теста</h1>
        <p><strong><?= htmlspecialchars($attempt['title']) ?></strong></p>
        
        <div class="score <?= $percentage >= 80 ? 'perfect' : ($percentage >= 50 ? 'good' : 'bad') ?>">
            <?= $attempt['score'] ?> / <?= $total_questions ?>
        </div>
        
        <p>Правильных ответов: <?= round($percentage, 1) ?>%</p>
        
        <div class="xp-earned">
            ✨ Получено опыта: <strong><?= $xp_earned ?> XP</strong>
        </div>
        
        <?php if ($level_up): ?>
            <div class="level-up">
                🎉 ПОЗДРАВЛЯЕМ! Вы повысили уровень! 🎉
            </div>
        <?php endif; ?>
        
        <a href="index.php" class="btn">🏠 На главную</a>
    </div>
</body>
</html>