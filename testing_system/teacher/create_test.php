<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireRole($pdo, 2);

// Получаем группы преподавателя для выбора
$stmt = $pdo->prepare("SELECT * FROM groups WHERE created_by = ? ORDER BY name");
$stmt->execute([$_SESSION['user_id']]);
$groups = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $time_limit = $_POST['time_limit'] ?? 0;
    $max_attempts = $_POST['max_attempts'] ?? 0;
    $group_id = $_POST['group_id'] ?: NULL;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $stmt = $pdo->prepare("INSERT INTO tests (title, description, created_by, time_limit, max_attempts, group_id, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$title, $description, $_SESSION['user_id'], $time_limit, $max_attempts, $group_id, $is_active]);
    
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Создание теста</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #f0f2f5;
            padding: 20px;
        }
        .container { max-width: 600px; margin: 0 auto; }
        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h1 { margin-bottom: 20px; color: #333; }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        input, textarea, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        textarea { resize: vertical; }
        button {
            background: #27ae60;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        button:hover { background: #219a52; }
        .back {
            display: inline-block;
            margin-top: 15px;
            color: #667eea;
            text-decoration: none;
        }
        .back:hover { text-decoration: underline; }
        .hint {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }
        .checkbox-label input {
            width: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>➕ Создание теста</h1>
            <form method="POST">
                <div class="form-group">
                    <label>Название теста</label>
                    <input type="text" name="title" required placeholder="Например: Основы PHP">
                </div>
                
                <div class="form-group">
                    <label>Описание</label>
                    <textarea name="description" rows="3" placeholder="Краткое описание теста..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>⏱️ Время на тест (минут)</label>
                    <input type="number" name="time_limit" value="0" min="0">
                    <div class="hint">0 = без ограничения времени</div>
                </div>
                
                <div class="form-group">
                    <label>📊 Максимум попыток</label>
                    <input type="number" name="max_attempts" value="0" min="0">
                    <div class="hint">0 = без ограничения попыток</div>
                </div>
                
                <div class="form-group">
                    <label>👥 Доступно для группы</label>
                    <select name="group_id">
                        <option value="">-- Все студенты (без ограничений) --</option>
                        <?php foreach ($groups as $group): ?>
                            <option value="<?= $group['id'] ?>"><?= htmlspecialchars($group['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="hint">Если выберите группу, тест увидят только студенты этой группы</div>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" checked> Активен (доступен студентам)
                    </label>
                </div>
                
                <button type="submit">✅ Создать тест</button>
            </form>
            <a href="index.php" class="back">← Назад к списку тестов</a>
        </div>
    </div>
</body>
</html>