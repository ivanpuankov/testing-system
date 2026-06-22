<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireRole($pdo, 2);

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_group'])) {
        $name = $_POST['name'];
        $description = $_POST['description'];
        
        $stmt = $pdo->prepare("INSERT INTO groups (name, description, created_by) VALUES (?, ?, ?)");
        $stmt->execute([$name, $description, $_SESSION['user_id']]);
        $_SESSION['success'] = "Группа «{$name}» создана";
        
    } elseif (isset($_POST['delete_group'])) {
        $group_id = $_POST['group_id'];
        
        // Проверяем, есть ли тесты, привязанные к этой группе
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tests WHERE group_id = ?");
        $stmt->execute([$group_id]);
        $tests_count = $stmt->fetch()['count'];
        
        if ($tests_count > 0) {
            $_SESSION['error'] = "Невозможно удалить группу, так как есть тесты, привязанные к ней";
        } else {
            $stmt = $pdo->prepare("DELETE FROM groups WHERE id = ? AND created_by = ?");
            $stmt->execute([$group_id, $_SESSION['user_id']]);
            $_SESSION['success'] = "Группа удалена";
        }
    }
}

// Получаем все группы преподавателя
$stmt = $pdo->prepare("SELECT * FROM groups WHERE created_by = ? ORDER BY id");
$stmt->execute([$_SESSION['user_id']]);
$groups = $stmt->fetchAll();

// Получаем всех студентов
$stmt = $pdo->prepare("SELECT u.*, g.name as group_name FROM users u LEFT JOIN groups g ON u.group_id = g.id WHERE u.role_id = 1 ORDER BY u.full_name");
$stmt->execute();
$students = $stmt->fetchAll();

// Обработка назначения студента в группу
if (isset($_POST['assign_student'])) {
    $student_id = $_POST['student_id'];
    $group_id = $_POST['group_id'] ?: NULL;
    
    $stmt = $pdo->prepare("UPDATE users SET group_id = ? WHERE id = ? AND role_id = 1");
    $stmt->execute([$group_id, $student_id]);
    $_SESSION['success'] = "Студент назначен в группу";
    header('Location: groups.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление группами</title>
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
        .back-btn {
            background: #667eea;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 5px;
        }
        .card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h2 { margin-bottom: 15px; color: #333; }
        .form-group { margin-bottom: 15px; }
        label { font-weight: bold; display: block; margin-bottom: 5px; }
        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        button {
            background: #27ae60;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn-delete {
            background: #e74c3c;
            padding: 5px 10px;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #667eea;
            color: white;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .group-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        .group-badge {
            background: #667eea;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>👥 Управление группами</h1>
            <a href="index.php" class="back-btn">← Назад</a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-success">✅ <?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-error">❌ <?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Создание группы -->
        <div class="card">
            <h2>➕ Создать группу</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Название группы</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Описание</label>
                    <textarea name="description" rows="2"></textarea>
                </div>
                <button type="submit" name="add_group">Создать группу</button>
            </form>
        </div>

        <!-- Список групп -->
        <div class="card">
            <h2>📋 Мои группы</h2>
            <?php if (empty($groups)): ?>
                <p>У вас пока нет созданных групп</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr><th>ID</th><th>Название</th><th>Описание</th><th>Действия</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($groups as $group): ?>
                        <tr>
                            <td><?= $group['id'] ?></td>
                            <td><?= htmlspecialchars($group['name']) ?></td>
                            <td><?= htmlspecialchars($group['description']) ?></td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Удалить группу?')">
                                    <input type="hidden" name="group_id" value="<?= $group['id'] ?>">
                                    <button type="submit" name="delete_group" class="btn-delete">🗑️ Удалить</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Назначение студентов в группы -->
        <div class="card">
            <h2>📚 Назначение студентов в группы</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Студент</label>
                    <select name="student_id" required>
                        <option value="">-- Выберите студента --</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?= $student['id'] ?>">
                                <?= htmlspecialchars($student['full_name']) ?> (<?= $student['login'] ?>)
                                <?= $student['group_name'] ? " - {$student['group_name']}" : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Группа</label>
                    <select name="group_id">
                        <option value="">-- Без группы --</option>
                        <?php foreach ($groups as $group): ?>
                            <option value="<?= $group['id'] ?>"><?= htmlspecialchars($group['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="assign_student">Назначить</button>
            </form>
        </div>
    </div>
</body>
</html>