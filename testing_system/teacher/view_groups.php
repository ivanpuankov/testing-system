<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireRole($pdo, 2);

// Получаем все группы преподавателя
$stmt = $pdo->prepare("SELECT * FROM groups WHERE created_by = ? ORDER BY name");
$stmt->execute([$_SESSION['user_id']]);
$groups = $stmt->fetchAll();

// Получаем выбранную группу для фильтрации
$selected_group = $_GET['group_id'] ?? 0;

// Получаем студентов с фильтрацией по группе
if ($selected_group && $selected_group != 'all') {
    $stmt = $pdo->prepare("
        SELECT u.*, g.name as group_name 
        FROM users u 
        LEFT JOIN groups g ON u.group_id = g.id 
        WHERE u.role_id = 1 AND u.group_id = ?
        ORDER BY u.full_name
    ");
    $stmt->execute([$selected_group]);
} elseif ($selected_group == 'all') {
    $stmt = $pdo->prepare("
        SELECT u.*, g.name as group_name 
        FROM users u 
        LEFT JOIN groups g ON u.group_id = g.id 
        WHERE u.role_id = 1 
        ORDER BY g.name, u.full_name
    ");
    $stmt->execute();
} else {
    // По умолчанию показываем всех студентов
    $stmt = $pdo->prepare("
        SELECT u.*, g.name as group_name 
        FROM users u 
        LEFT JOIN groups g ON u.group_id = g.id 
        WHERE u.role_id = 1 
        ORDER BY u.full_name
    ");
    $stmt->execute();
}
$students = $stmt->fetchAll();

// Статистика по группам
$group_stats = [];
foreach ($groups as $group) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role_id = 1 AND group_id = ?");
    $stmt->execute([$group['id']]);
    $group_stats[$group['id']] = $stmt->fetch()['count'];
}

// Количество студентов без группы
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role_id = 1 AND (group_id IS NULL OR group_id = 0)");
$stmt->execute();
$ungrouped_count = $stmt->fetch()['count'];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Просмотр групп</title>
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
        .back-btn {
            background: #667eea;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 5px;
        }
        .back-btn:hover { background: #5a67d8; }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .stat-card.active {
            border: 2px solid #667eea;
            background: #e8f0fe;
        }
        .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            color: #666;
            margin-top: 5px;
            font-size: 14px;
        }
        
        .students-table {
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
        .group-badge {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
        }
        .no-group {
            display: inline-block;
            background: #95a5a6;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
        }
        .stats-info {
            background: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .total-count {
            font-size: 16px;
            color: #333;
        }
        .export-btn {
            background: #27ae60;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 5px;
        }
        .export-btn:hover { background: #219a52; }
        
        @media (max-width: 768px) {
            .header { flex-direction: column; gap: 10px; text-align: center; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .stats-info { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>👥 Просмотр групп и студентов</h1>
            <a href="index.php" class="back-btn">← Назад</a>
        </div>

        <!-- Статистика по группам -->
        <div class="stats-grid">
            <div class="stat-card <?= (!$selected_group || $selected_group == 'all') ? 'active' : '' ?>" onclick="window.location.href='?group_id=all'">
                <div class="stat-value"><?= count($students) ?></div>
                <div class="stat-label">👨‍🎓 Всего студентов</div>
            </div>
            
            <?php foreach ($groups as $group): ?>
                <div class="stat-card <?= ($selected_group == $group['id']) ? 'active' : '' ?>" onclick="window.location.href='?group_id=<?= $group['id'] ?>'">
                    <div class="stat-value"><?= $group_stats[$group['id']] ?? 0 ?></div>
                    <div class="stat-label">👥 <?= htmlspecialchars($group['name']) ?></div>
                </div>
            <?php endforeach; ?>
            
            <?php if ($ungrouped_count > 0): ?>
                <div class="stat-card <?= ($selected_group == 'ungrouped') ? 'active' : '' ?>" onclick="window.location.href='?group_id=ungrouped'">
                    <div class="stat-value"><?= $ungrouped_count ?></div>
                    <div class="stat-label">📭 Без группы</div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Информация и экспорт -->
        <div class="stats-info">
            <div class="total-count">
                📊 Показано: <strong><?= count($students) ?></strong> студентов
            </div>
            <a href="export_group.php<?= $selected_group ? '?group_id=' . $selected_group : '' ?>" class="export-btn">📥 Экспорт списка в CSV</a>
        </div>

        <!-- Таблица студентов -->
        <div class="students-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ФИО</th>
                        <th>Логин</th>
                        <th>Группа</th>
                        <th>Опыт (XP)</th>
                        <th>Тестов пройдено</th>
                        <th>Правильных ответов</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px;">
                                📭 Нет студентов в этой категории
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?= $student['id'] ?></td>
                                <td><strong><?= htmlspecialchars($student['full_name']) ?></strong></td>
                                <td><?= htmlspecialchars($student['login']) ?></td>
                                <td>
                                    <?php if ($student['group_name']): ?>
                                        <span class="group-badge"><?= htmlspecialchars($student['group_name']) ?></span>
                                    <?php else: ?>
                                        <span class="no-group">Без группы</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $student['current_xp'] ?> XP</td>
                                <td><?= $student['total_tests_passed'] ?></td>
                                <td><?= $student['total_correct_answers'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Добавляем обработку для отображения студентов без группы
        <?php if ($selected_group == 'ungrouped'): ?>
            // Фильтр для студентов без группы уже применён в SQL
        <?php endif; ?>
    </script>
</body>
</html>