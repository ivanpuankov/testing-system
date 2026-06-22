<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireRole($pdo, 2);

define('ENCRYPTION_KEY', 'your_secret_key_32_bytes_long_!!!');

function encryptCorrect($value, $key) {
    $iv = openssl_random_pseudo_bytes(16);
    $encrypted = openssl_encrypt($value ? '1' : '0', 'AES-256-CBC', $key, 0, $iv);
    return base64_encode($iv . $encrypted);
}

function encryptMultipleAnswers($answers, $key) {
    $answers_json = json_encode($answers);
    $iv = openssl_random_pseudo_bytes(16);
    $encrypted = openssl_encrypt($answers_json, 'AES-256-CBC', $key, 0, $iv);
    return base64_encode($iv . $encrypted);
}

function decryptMultipleAnswers($encrypted_data, $key) {
    $data = base64_decode($encrypted_data);
    $iv = substr($data, 0, 16);
    $encrypted = substr($data, 16);
    $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    return json_decode($decrypted, true);
}

$test_id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM tests WHERE id = ? AND created_by = ?");
$stmt->execute([$test_id, $_SESSION['user_id']]);
$test = $stmt->fetch();

if (!$test) {
    die("Тест не найден");
}

// Обновление теста
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_test'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $time_limit = $_POST['time_limit'];
    $max_attempts = $_POST['max_attempts'];
    $group_id = $_POST['group_id'] ?: NULL;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $stmt = $pdo->prepare("UPDATE tests SET title = ?, description = ?, time_limit = ?, max_attempts = ?, group_id = ?, is_active = ? WHERE id = ?");
    $stmt->execute([$title, $description, $time_limit, $max_attempts, $group_id, $is_active, $test_id]);
    header('Location: edit_test.php?id=' . $test_id);
    exit();
}

// Добавление вопроса
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_question'])) {
    $question_text = $_POST['question_text'];
    $question_type = $_POST['question_type'];
    
    $stmt = $pdo->prepare("INSERT INTO questions (test_id, question_text, question_type) VALUES (?, ?, ?)");
    $stmt->execute([$test_id, $question_text, $question_type]);
    $question_id = $pdo->lastInsertId();
    
    $options = $_POST['options'] ?? [];
    $correct = $_POST['correct'] ?? [];
    
    if ($question_type == 'multiple') {
        $correct_indices = [];
        foreach ($correct as $index) {
            $correct_indices[] = intval($index);
        }
        $encrypted_correct = encryptMultipleAnswers($correct_indices, ENCRYPTION_KEY);
        
        foreach ($options as $index => $option_text) {
            if (trim($option_text)) {
                $stmt = $pdo->prepare("INSERT INTO answer_options (question_id, option_text, is_correct) VALUES (?, ?, ?)");
                $stmt->execute([$question_id, $option_text, $encrypted_correct]);
            }
        }
    } else {
        foreach ($options as $index => $option_text) {
            if (trim($option_text)) {
                $is_correct = in_array($index, $correct);
                $encrypted_correct = encryptCorrect($is_correct, ENCRYPTION_KEY);
                $stmt = $pdo->prepare("INSERT INTO answer_options (question_id, option_text, is_correct) VALUES (?, ?, ?)");
                $stmt->execute([$question_id, $option_text, $encrypted_correct]);
            }
        }
    }
    
    header("Location: edit_test.php?id=$test_id");
    exit();
}

// Удаление вопроса
if (isset($_GET['delete_question'])) {
    $question_id = $_GET['delete_question'];
    $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ? AND test_id = ?");
    $stmt->execute([$question_id, $test_id]);
    header("Location: edit_test.php?id=$test_id");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM groups WHERE created_by = ? ORDER BY name");
$stmt->execute([$_SESSION['user_id']]);
$groups = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM questions WHERE test_id = ? ORDER BY id");
$stmt->execute([$test_id]);
$questions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование теста</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #f0f2f5;
            padding: 20px;
        }
        .container { max-width: 900px; margin: 0 auto; }
        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h1, h2 { margin-bottom: 20px; color: #333; }
        .form-group { margin-bottom: 15px; }
        label { font-weight: bold; display: block; margin-bottom: 5px; }
        input, textarea, select {
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
        .delete-btn {
            background: #e74c3c;
            color: white;
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 12px;
            margin-left: 10px;
        }
        .edit-btn {
            background: #3498db;
            color: white;
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 12px;
            margin-left: 10px;
        }
        .question-block {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
            background: #fafafa;
        }
        .option-row { margin: 10px 0; }
        .back { display: inline-block; margin-top: 15px; color: #667eea; text-decoration: none; }
        .type-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 11px;
        }
        .type-single { background: #3498db; color: white; }
        .type-multiple { background: #9b59b6; color: white; }
        .correct-label { margin-left: 10px; }
        .remove-option-btn {
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 3px;
            padding: 5px 10px;
            margin-left: 10px;
            cursor: pointer;
        }
        .remove-option-btn:hover { background: #c0392b; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Редактирование теста -->
        <div class="card">
            <h1>✏️ Редактирование теста</h1>
            <form method="POST">
                <div class="form-group">
                    <label>Название</label>
                    <input type="text" name="title" value="<?= htmlspecialchars($test['title']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Описание</label>
                    <textarea name="description" rows="3"><?= htmlspecialchars($test['description']) ?></textarea>
                </div>
                <div class="form-group">
                    <label>⏱️ Время (минут)</label>
                    <input type="number" name="time_limit" value="<?= $test['time_limit'] ?>">
                </div>
                <div class="form-group">
                    <label>📊 Максимум попыток</label>
                    <input type="number" name="max_attempts" value="<?= $test['max_attempts'] ?>">
                </div>
                <div class="form-group">
                    <label>👥 Группа</label>
                    <select name="group_id">
                        <option value="">-- Все студенты --</option>
                        <?php foreach ($groups as $group): ?>
                            <option value="<?= $group['id'] ?>" <?= $test['group_id'] == $group['id'] ? 'selected' : '' ?>><?= htmlspecialchars($group['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><input type="checkbox" name="is_active" <?= $test['is_active'] ? 'checked' : '' ?>> Активен</label>
                </div>
                <button type="submit" name="update_test">💾 Сохранить</button>
            </form>
        </div>

        <!-- Список вопросов -->
        <div class="card">
            <h2>📝 Вопросы теста</h2>
            <?php if (empty($questions)): ?>
                <p style="color: #666;">Вопросов пока нет. Добавьте первый вопрос ниже.</p>
            <?php else: ?>
                <?php foreach ($questions as $q): 
                    $stmt_opts = $pdo->prepare("SELECT * FROM answer_options WHERE question_id = ? ORDER BY id");
                    $stmt_opts->execute([$q['id']]);
                    $options = $stmt_opts->fetchAll();
                    
                    $is_multiple = ($q['question_type'] == 'multiple');
                    $type_label = $is_multiple ? 'Множественный выбор' : 'Одиночный выбор';
                    $type_class = $is_multiple ? 'type-multiple' : 'type-single';
                    
                    $correct_indices = [];
                    if ($is_multiple && !empty($options)) {
                        $correct_indices = decryptMultipleAnswers($options[0]['is_correct'], ENCRYPTION_KEY);
                        if (!is_array($correct_indices)) $correct_indices = [];
                    }
                ?>
                    <div class="question-block">
                        <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 10px;">
                            <strong><?= htmlspecialchars($q['question_text']) ?></strong>
                            <span class="type-badge <?= $type_class ?>"><?= $type_label ?></span>
                            <div style="margin-left: auto;">
                                <a href="edit_question.php?question_id=<?= $q['id'] ?>&test_id=<?= $test_id ?>" class="edit-btn">✏️ Редактировать</a>
                                <a href="?id=<?= $test_id ?>&delete_question=<?= $q['id'] ?>" class="delete-btn" onclick="return confirm('Удалить вопрос?')">🗑️ Удалить</a>
                            </div>
                        </div>
                        <ul style="margin-top: 10px; margin-left: 20px;">
                            <?php foreach ($options as $idx => $opt): 
                                $is_correct = false;
                                if ($is_multiple) {
                                    $is_correct = in_array($idx, $correct_indices);
                                } else {
                                    $encrypted = $opt['is_correct'];
                                    $data = base64_decode($encrypted);
                                    $iv = substr($data, 0, 16);
                                    $encrypted_data = substr($data, 16);
                                    $decrypted = openssl_decrypt($encrypted_data, 'AES-256-CBC', ENCRYPTION_KEY, 0, $iv);
                                    $is_correct = ($decrypted == '1');
                                }
                            ?>
                                <li>
                                    <?= htmlspecialchars($opt['option_text']) ?> 
                                    <?php if ($is_correct): ?>
                                        <span style="color: #27ae60; font-weight: bold;">✅ Правильный</span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if ($is_multiple && count($correct_indices) > 0): ?>
                            <div style="font-size: 12px; color: #9b59b6; margin-top: 5px;">
                                💡 Правильные ответы: <?= implode(', ', array_map(function($i) { return $i + 1; }, $correct_indices)) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Добавление вопроса -->
        <div class="card">
            <h2>➕ Добавить вопрос</h2>
            <form method="POST" id="addQuestionForm">
                <div class="form-group">
                    <label>Текст вопроса</label>
                    <input type="text" name="question_text" required>
                </div>
                <div class="form-group">
                    <label>Тип вопроса</label>
                    <select name="question_type" id="question_type" onchange="toggleCorrectType()">
                        <option value="single">Одиночный выбор (один правильный ответ)</option>
                        <option value="multiple">Множественный выбор (несколько правильных ответов)</option>
                    </select>
                </div>
                <label>Варианты ответов</label>
                <div id="options">
                    <div class="option-row">
                        <input type="text" name="options[]" placeholder="Вариант 1" required style="width: 60%;">
                        <label class="correct-label">
                            <input type="checkbox" name="correct[]" value="0" class="correct-checkbox"> Правильный
                        </label>
                    </div>
                    <div class="option-row">
                        <input type="text" name="options[]" placeholder="Вариант 2" required style="width: 60%;">
                        <label class="correct-label">
                            <input type="checkbox" name="correct[]" value="1" class="correct-checkbox"> Правильный
                        </label>
                    </div>
                </div>
                <button type="button" onclick="addOption()" style="background: #3498db;">+ Добавить вариант</button>
                <button type="submit" name="add_question" style="margin-top: 15px;">✅ Сохранить вопрос</button>
            </form>
        </div>
        
        <a href="index.php" class="back">← Назад к списку тестов</a>
    </div>

    <script>
        function addOption() {
            const optionsContainer = document.getElementById('options');
            const currentOptions = optionsContainer.querySelectorAll('.option-row');
            const newIndex = currentOptions.length;
            
            const div = document.createElement('div');
            div.className = 'option-row';
            div.innerHTML = `
                <input type="text" name="options[]" placeholder="Вариант ${newIndex + 1}" required style="width: 60%;">
                <label class="correct-label">
                    <input type="checkbox" name="correct[]" value="${newIndex}" class="correct-checkbox"> Правильный
                </label>
                <button type="button" class="remove-option-btn" onclick="this.parentElement.remove()">🗑️ Удалить</button>
            `;
            optionsContainer.appendChild(div);
        }
        
        function toggleCorrectType() {
            const type = document.getElementById('question_type').value;
            const labels = document.querySelectorAll('.correct-label');
            
            if (type === 'single') {
                labels.forEach((label) => {
                    const input = label.querySelector('input');
                    const newInput = document.createElement('input');
                    newInput.type = 'radio';
                    newInput.name = 'correct[]';
                    newInput.value = input.value;
                    newInput.className = 'correct-radio';
                    label.innerHTML = '';
                    label.appendChild(newInput);
                    label.appendChild(document.createTextNode(' Правильный'));
                });
            } else {
                labels.forEach((label) => {
                    const input = label.querySelector('input');
                    const newInput = document.createElement('input');
                    newInput.type = 'checkbox';
                    newInput.name = 'correct[]';
                    newInput.value = input.value;
                    newInput.className = 'correct-checkbox';
                    label.innerHTML = '';
                    label.appendChild(newInput);
                    label.appendChild(document.createTextNode(' Правильный'));
                });
            }
        }
    </script>
</body>
</html>