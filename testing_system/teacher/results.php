<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireRole($pdo, 2);

$test_id = $_GET['id'] ?? 0;

// Получаем сообщения об успехе/ошибке
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success']);
unset($_SESSION['error']);

// Проверяем, что тест принадлежит этому преподавателю
$stmt = $pdo->prepare("SELECT * FROM tests WHERE id = ? AND created_by = ?");
$stmt->execute([$test_id, $_SESSION['user_id']]);
$test = $stmt->fetch();

if (!$test) {
    die("<!DOCTYPE html>
    <html>
    <head><title>Ошибка</title></head>
    <body style='font-family: Arial; text-align: center; padding: 50px;'>
        <h1>❌ Ошибка</h1>
        <p>Тест не найден или доступ запрещён</p>
        <a href='index.php'>← Вернуться к списку тестов</a>
    </body>
    </html>");
}

// Получаем все попытки по этому тесту
$stmt = $pdo->prepare("
    SELECT ta.*, u.full_name, u.login,
           (SELECT COUNT(*) FROM questions WHERE test_id = ta.test_id) as total_questions
    FROM test_attempts ta
    JOIN users u ON ta.student_id = u.id
    WHERE ta.test_id = ?
    ORDER BY ta.finished_at DESC
");
$stmt->execute([$test_id]);
$attempts = $stmt->fetchAll();

// Статистика по тесту
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_attempts,
        AVG(score) as avg_score,
        MAX(score) as max_score,
        MIN(score) as min_score,
        COUNT(DISTINCT student_id) as unique_students
    FROM test_attempts
    WHERE test_id = ?
");
$stmt->execute([$test_id]);
$stats = $stmt->fetch();

// Если нет попыток, обнуляем статистику
if (!$stats['total_attempts']) {
    $stats = [
        'total_attempts' => 0,
        'avg_score' => 0,
        'max_score' => 0,
        'min_score' => 0,
        'unique_students' => 0
    ];
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Результаты теста: <?= htmlspecialchars($test['title']) ?></title>
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
        h1 { color: #333; font-size: 24px; }
        .back-btn {
            background: #667eea;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 5px;
        }
        .back-btn:hover { background: #5a67d8; }
        
        /* Сообщения */
        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #27ae60;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #e74c3c;
        }
        
        /* Статистика */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
        
        /* Кнопки */
        .btn-export {
            background: #27ae60;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin-bottom: 20px;
            margin-right: 10px;
        }
        .btn-export:hover { background: #219a52; }
        
        .btn-clear {
            background: #e74c3c;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin-bottom: 20px;
        }
        .btn-clear:hover { background: #c0392b; }
        
        /* Таблица */
        .results-table {
            background: white;
            border-radius: 10px;
            overflow-x: auto;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #667eea;
            color: white;
        }
        tr:hover {
            background: #f5f5f5;
        }
        .score-high {
            color: #27ae60;
            font-weight: bold;
        }
        .score-medium {
            color: #f39c12;
            font-weight: bold;
        }
        .score-low {
            color: #e74c3c;
            font-weight: bold;
        }
        .empty-results {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        /* Адаптивность */
        @media (max-width: 768px) {
            .header { flex-direction: column; gap: 10px; text-align: center; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Шапка -->
        <div class="header">
            <h1>📊 Результаты теста: <?= htmlspecialchars($test['title']) ?></h1>
            <a href="index.php" class="back-btn">← Назад к тестам</a>
        </div>

        <!-- Сообщения -->
        <?php if ($success): ?>
            <div class="alert-success">✅ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert-error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Статистика -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= $stats['total_attempts'] ?></div>
                <div class="stat-label">Всего попыток</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= round($stats['avg_score'], 1) ?></div>
                <div class="stat-label">Средний балл</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['max_score'] ?></div>
                <div class="stat-label">Лучший результат</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['min_score'] ?></div>
                <div class="stat-label">Худший результат</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['unique_students'] ?></div>
                <div class="stat-label">Уникальных студентов</div>
            </div>
        </div>

        <!-- Кнопки действий -->
        <div style="margin-bottom: 20px;">
            <a href="export_results.php?id=<?= $test_id ?>" class="btn-export">📥 Экспорт в CSV</a>
            
            <?php if ($stats['total_attempts'] > 0): ?>
                <a href="clear_results.php?id=<?= $test_id ?>" 
                   class="btn-clear" 
                   onclick="return confirm('Вы уверены, что хотите удалить ВСЕ результаты по тесту «<?= htmlspecialchars($test['title']) ?>»? Это действие нельзя отменить.\n\nБудет удалено <?= $stats['total_attempts'] ?> записей.')">
                   🗑️ Очистить все результаты
                </a>
            <?php endif; ?>
        </div>

        <!-- Таблица результатов -->
        <div class="results-table">
            <table>
                <thead>
                    <tr>
                        <th>Студент</th>
                        <th>Логин</th>
                        <th>Начало</th>
                        <th>Завершён</th>
                        <th>Баллы</th>
                        <th>Процент</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($attempts)): ?>
                        <tr>
                            <td colspan="7" class="empty-results">
                                📭 Пока нет результатов по этому тесту
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($attempts as $attempt): 
                            $percentage = ($attempt['score'] / $attempt['total_questions']) * 100;
                            $score_class = $percentage >= 80 ? 'score-high' : ($percentage >= 50 ? 'score-medium' : 'score-low');
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($attempt['full_name']) ?></td>
                                <td><?= htmlspecialchars($attempt['login']) ?></td>
                                <td><?= date('d.m.Y H:i', strtotime($attempt['started_at'])) ?></td>
                                <td><?= $attempt['finished_at'] ? date('d.m.Y H:i', strtotime($attempt['finished_at'])) : '-' ?></td>
                                <td class="<?= $score_class ?>"><?= $attempt['score'] ?> / <?= $attempt['total_questions'] ?></td>
                                <td class="<?= $score_class ?>"><?= round($percentage, 1) ?>%</td>
                                <td><?= $attempt['status'] == 'completed' ? '✅ Завершён' : '⏳ В процессе' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>