<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireRole($pdo, 3);

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Добавление пользователя
    if (isset($_POST['add_user'])) {
        $login = trim($_POST['login']);
        $full_name = trim($_POST['full_name']);
        $role_id = intval($_POST['role_id']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        // Проверка, не существует ли уже такой логин
        $stmt = $pdo->prepare("SELECT id FROM users WHERE login = ?");
        $stmt->execute([$login]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = "Пользователь с логином '{$login}' уже существует";
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (login, password_hash, full_name, role_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$login, $password, $full_name, $role_id]);
            $_SESSION['success'] = "Пользователь '{$full_name}' добавлен";
        }
        header('Location: index.php');
        exit();
    }
    
    // Удаление пользователя
    if (isset($_POST['delete_user'])) {
        $user_id = intval($_POST['user_id']);
        
        // Нельзя удалить самого себя
        if ($user_id == $_SESSION['user_id']) {
            $_SESSION['error'] = "Нельзя удалить самого себя";
        } else {
            // Проверяем, не админ ли это
            $stmt = $pdo->prepare("SELECT role_id FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if ($user && $user['role_id'] == 3) {
                $_SESSION['error'] = "Нельзя удалить администратора";
            } else {
                try {
                    // Начинаем транзакцию
                    $pdo->beginTransaction();
                    
                    // Удаляем связанные записи в правильном порядке
                    $pdo->prepare("DELETE FROM xp_log WHERE user_id = ?")->execute([$user_id]);
                    $pdo->prepare("DELETE FROM user_achievements WHERE user_id = ?")->execute([$user_id]);
                    $pdo->prepare("DELETE FROM test_attempts WHERE student_id = ?")->execute([$user_id]);
                    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
                    
                    // Подтверждаем транзакцию
                    $pdo->commit();
                    
                    $_SESSION['success'] = "Пользователь удалён";
                } catch (PDOException $e) {
                    // Откатываем при ошибке
                    $pdo->rollBack();
                    $_SESSION['error'] = "Ошибка при удалении: " . $e->getMessage();
                }
            }
        }
        header('Location: index.php');
        exit();
    }
    
    // Смена роли
    if (isset($_POST['change_role'])) {
        $user_id = intval($_POST['user_id']);
        $new_role = intval($_POST['new_role']);
        
        // Нельзя менять роль администратора
        $stmt = $pdo->prepare("SELECT role_id FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if ($user && $user['role_id'] == 3) {
            $_SESSION['error'] = "Нельзя изменить роль администратора";
        } else {
            $stmt = $pdo->prepare("UPDATE users SET role_id = ? WHERE id = ?");
            $stmt->execute([$new_role, $user_id]);
            $_SESSION['success'] = "Роль пользователя изменена";
        }
        header('Location: index.php');
        exit();
    }
}

// Получаем сообщения
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success']);
unset($_SESSION['error']);

// Получаем всех пользователей
$stmt = $pdo->query("
    SELECT u.*, r.name as role_name 
    FROM users u 
    JOIN roles r ON u.role_id = r.id 
    ORDER BY u.id
");
$users = $stmt->fetchAll();

// Получаем список ролей для выпадающего списка
$stmt = $pdo->query("SELECT * FROM roles ORDER BY id");
$roles = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель администратора</title>
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
        
        .section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h2 { margin-bottom: 15px; color: #333; }
        
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            width: 250px;
        }
        button {
            background: #27ae60;
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover { background: #219a52; }
        
        .delete-btn {
            background: #e74c3c;
            padding: 4px 10px;
            font-size: 12px;
            border: none;
            cursor: pointer;
            color: white;
            border-radius: 5px;
        }
        .delete-btn:hover { background: #c0392b; }
        
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
        .role-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 12px;
        }
        .role-1 { background: #3498db; color: white; }
        .role-2 { background: #e67e22; color: white; }
        .role-3 { background: #e74c3c; color: white; }
        
        .inline-form {
            display: inline-block;
            margin-right: 5px;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        @media (max-width: 768px) {
            .header { flex-direction: column; gap: 10px; text-align: center; }
            table { font-size: 12px; }
            th, td { padding: 5px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>👑 Панель администратора</h1>
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

        <!-- Добавление пользователя -->
        <div class="section">
            <h2>➕ Добавить пользователя</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Логин</label>
                    <input type="text" name="login" required>
                </div>
                <div class="form-group">
                    <label>ФИО</label>
                    <input type="text" name="full_name" required>
                </div>
                <div class="form-group">
                    <label>Пароль (по умолчанию 123)</label>
                    <input type="text" name="password" value="123" required>
                </div>
                <div class="form-group">
                    <label>Роль</label>
                    <select name="role_id">
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= $role['id'] ?>"><?= htmlspecialchars($role['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="add_user">Создать пользователя</button>
            </form>
        </div>

        <!-- Список пользователей -->
        <div class="section">
            <h2>📋 Список пользователей</h2>
            <div style="overflow-x: auto;">
                 <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Логин</th>
                            <th>ФИО</th>
                            <th>Роль</th>
                            <th>Опыт (XP)</th>
                            <th>Тестов пройдено</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= $user['id'] ?></td>
                                <td><?= htmlspecialchars($user['login']) ?></td>
                                <td><?= htmlspecialchars($user['full_name']) ?></td>
                                <td>
                                    <span class="role-badge role-<?= $user['role_id'] ?>">
                                        <?= htmlspecialchars($user['role_name']) ?>
                                    </span>
                                 </td>
                                 <td><?= $user['current_xp'] ?> XP</td>
                                 <td><?= $user['total_tests_passed'] ?></td>
                                 <td>
                                    <?php if ($user['role_id'] != 3): ?>
                                        <div class="action-buttons">
                                            <!-- Форма смены роли -->
                                            <form method="POST" class="inline-form" onsubmit="return confirm('Изменить роль?')">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <select name="new_role" onchange="this.form.submit()">
                                                    <option value="1" <?= $user['role_id'] == 1 ? 'selected' : '' ?>>Студент</option>
                                                    <option value="2" <?= $user['role_id'] == 2 ? 'selected' : '' ?>>Преподаватель</option>
                                                </select>
                                                <input type="hidden" name="change_role" value="1">
                                            </form>
                                            
                                            <!-- Форма удаления -->
                                            <form method="POST" class="inline-form" onsubmit="return confirm('Удалить пользователя «<?= htmlspecialchars($user['full_name']) ?>»?')">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <button type="submit" name="delete_user" class="delete-btn">🗑️ Удалить</button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: #666;">Администратор</span>
                                    <?php endif; ?>
                                 </td>
                             </tr>
                        <?php endforeach; ?>
                    </tbody>
                 </table>
            </div>
        </div>
    </div>
</body>
</html>