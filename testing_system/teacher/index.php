<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireRole($pdo, 2);

// Получаем сообщения об успехе/ошибке
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success']);
unset($_SESSION['error']);

// Получаем тесты, созданные этим преподавателем
$stmt = $pdo->prepare("
    SELECT t.*, g.name as group_name 
    FROM tests t 
    LEFT JOIN groups g ON t.group_id = g.id 
    WHERE t.created_by = ? 
    ORDER BY t.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$tests = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель преподавателя</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #f0f2f5;
            padding: 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        
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
        .logout {
            background: #dc3545;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 5px;
        }
        .logout:hover { background: #c82333; }
        
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
        
        .btn-create {
            background: #27ae60;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin-bottom: 20px;
            margin-right: 10px;
        }
        .btn-create:hover { background: #219a52; }
        
        .btn-groups {
            background: #3498db;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin-bottom: 20px;
            margin-right: 10px;
        }
        .btn-groups:hover { background: #2980b9; }
        
        .btn-view-groups {
            background: #9b59b6;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin-bottom: 20px;
        }
        .btn-view-groups:hover { background: #8e44ad; }
        
        .test-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .test-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .test-card:hover { transform: translateY(-3px); }
        .test-card h3 { 
            margin-bottom: 10px; 
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 8px;
        }
        .test-card p { color: #666; margin-bottom: 8px; }
        
        .btn-edit {
            background: #3498db;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin-top: 10px;
            margin-right: 5px;
        }
        .btn-edit:hover { background: #2980b9; }
        
        .btn-results {
            background: #9b59b6;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin-top: 10px;
            margin-right: 5px;
        }
        .btn-results:hover { background: #8e44ad; }
        
        .btn-delete {
            background: #e74c3c;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin-top: 10px;
        }
        .btn-delete:hover { background: #c0392b; }
        
        .status-active {
            color: #27ae60;
            font-weight: bold;
        }
        .status-inactive {
            color: #e74c3c;
            font-weight: bold;
        }
        
        .group-badge {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 11px;
            margin-top: 5px;
        }
        
        .empty-message {
            grid-column: 1/-1;
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 10px;
        }
        
        .section-title {
            font-size: 22px;
            margin: 20px 0 10px 0;
            color: #333;
        }
        
        @media (max-width: 768px) {
            .header { flex-direction: column; gap: 10px; text-align: center; }
            .test-grid { grid-template-columns: 1fr; }
            .btn-create, .btn-groups, .btn-view-groups { 
                display: block; 
                text-align: center; 
                margin-right: 0;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📚 Панель преподавателя</h1>
            <div>
                <span style="margin-right: 15px;">👋 <?= htmlspecialchars($_SESSION['user_name']) ?></span>
                <a href="/testing_system/logout.php" class="logout">Выход</a>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert-success">✅ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert-error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div>
            <a href="create_test.php" class="btn-create">➕ Создать новый тест</a>
            <a href="groups.php" class="btn-groups">👥 Управление группами</a>
            <a href="view_groups.php" class="btn-view-groups">📋 Просмотр групп</a>
        </div>

        <h2 class="section-title">📖 Мои тесты</h2>
        <div class="test-grid">
            <?php if (empty($tests)): ?>
                <div class="empty-message">
                    <p>📭 У вас пока нет созданных тестов</p>
                    <a href="create_test.php" style="color: #27ae60;">Создать первый тест →</a>
                </div>
            <?php else: ?>
                <?php foreach ($tests as $test): ?>
                    <div class="test-card">
                        <h3><?= htmlspecialchars($test['title']) ?></h3>
                        <p><?= htmlspecialchars($test['description']) ?></p>
                        <p>⏱️ Время: <?= $test['time_limit'] ?> минут</p>
                        <p>📊 Попыток: <?= $test['max_attempts'] == 0 ? '∞ (без лимита)' : $test['max_attempts'] ?></p>
                        
                        <?php if ($test['group_id']): ?>
                            <p>👥 Группа: <span class="group-badge"><?= htmlspecialchars($test['group_name']) ?></span></p>
                        <?php else: ?>
                            <p>👥 Группа: <span class="group-badge">Все студенты</span></p>
                        <?php endif; ?>
                        
                        <p>📅 Создан: <?= date('d.m.Y H:i', strtotime($test['created_at'])) ?></p>
                        <p>Статус: 
                            <span class="<?= $test['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                <?= $test['is_active'] ? '✅ Активен' : '❌ Неактивен' ?>
                            </span>
                        </p>
                        <div>
                            <a href="edit_test.php?id=<?= $test['id'] ?>" class="btn-edit">✏️ Редактировать</a>
                            <a href="results.php?id=<?= $test['id'] ?>" class="btn-results">📊 Результаты</a>
                            <a href="delete_test.php?id=<?= $test['id'] ?>" 
                               class="btn-delete" 
                               onclick="return confirm('Вы уверены, что хотите удалить тест «<?= htmlspecialchars($test['title']) ?>»? Все вопросы и ответы также будут удалены.')">
                               🗑️ Удалить
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>