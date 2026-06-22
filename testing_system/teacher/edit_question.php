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

$question_id = $_GET['question_id'] ?? 0;
$test_id = $_GET['test_id'] ?? 0;

// Получаем вопрос
$stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ? AND test_id = ?");
$stmt->execute([$question_id, $test_id]);
$question = $stmt->fetch();

if (!$question) {
    die("Вопрос не найден. ID: $question_id, Test ID: $test_id");
}

// Получаем варианты ответов
$stmt = $pdo->prepare("SELECT * FROM answer_options WHERE question_id = ? ORDER BY id");
$stmt->execute([$question_id]);
$options = $stmt->fetchAll();

$is_multiple = ($question['question_type'] == 'multiple');
$correct_indices = [];

if ($is_multiple && !empty($options)) {
    $correct_indices = decryptMultipleAnswers($options[0]['is_correct'], ENCRYPTION_KEY);
    if (!is_array($correct_indices)) $correct_indices = [];
}

// Обработка сохранения
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $question_text = $_POST['question_text'];
    $question_type = $_POST['question_type'];
    $options_text = $_POST['options'] ?? [];
    $correct = $_POST['correct'] ?? [];
    
    // Обновляем вопрос
    $stmt = $pdo->prepare("UPDATE questions SET question_text = ?, question_type = ? WHERE id = ?");
    $stmt->execute([$question_text, $question_type, $question_id]);
    
    // Удаляем старые варианты ответов
    $stmt = $pdo->prepare("DELETE FROM answer_options WHERE question_id = ?");
    $stmt->execute([$question_id]);
    
    // Добавляем новые
    if ($question_type == 'multiple') {
        $correct_indices_new = [];
        foreach ($correct as $index) {
            $correct_indices_new[] = intval($index);
        }
        $encrypted_correct = encryptMultipleAnswers($correct_indices_new, ENCRYPTION_KEY);
        
        foreach ($options_text as $index => $option_text) {
            if (trim($option_text)) {
                $stmt = $pdo->prepare("INSERT INTO answer_options (question_id, option_text, is_correct) VALUES (?, ?, ?)");
                $stmt->execute([$question_id, $option_text, $encrypted_correct]);
            }
        }
    } else {
        foreach ($options_text as $index => $option_text) {
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
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование вопроса</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #f0f2f5;
            padding: 20px;
        }
        .container { max-width: 800px; margin: 0 auto; }
        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h1 { margin-bottom: 20px; color: #333; }
        .form-group { margin-bottom: 15px; }
        label { font-weight: bold; display: block; margin-bottom: 5px; }
        input[type="text"], select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .option-row { margin: 10px 0; }
        button {
            background: #27ae60;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .add-btn { background: #3498db; }
        .back { display: inline-block; margin-top: 15px; color: #667eea; text-decoration: none; }
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
        <div class="card">
            <h1>✏️ Редактировать вопрос</h1>
            <form method="POST">
                <div class="form-group">
                    <label>Текст вопроса</label>
                    <input type="text" name="question_text" value="<?= htmlspecialchars($question['question_text']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Тип вопроса</label>
                    <select name="question_type" id="question_type" onchange="toggleCorrectType()">
                        <option value="single" <?= $question['question_type'] == 'single' ? 'selected' : '' ?>>Одиночный выбор</option>
                        <option value="multiple" <?= $question['question_type'] == 'multiple' ? 'selected' : '' ?>>Множественный выбор</option>
                    </select>
                </div>
                <label>Варианты ответов</label>
                <div id="options">
                    <?php if (empty($options)): ?>
                        <div class="option-row">
                            <input type="text" name="options[]" placeholder="Вариант 1" required style="width: 60%;">
                            <label>
                                <input type="checkbox" name="correct[]" value="0" class="correct-checkbox"> Правильный
                            </label>
                            <button type="button" class="remove-option-btn" onclick="this.parentElement.remove()">🗑️ Удалить</button>
                        </div>
                        <div class="option-row">
                            <input type="text" name="options[]" placeholder="Вариант 2" required style="width: 60%;">
                            <label>
                                <input type="checkbox" name="correct[]" value="1" class="correct-checkbox"> Правильный
                            </label>
                            <button type="button" class="remove-option-btn" onclick="this.parentElement.remove()">🗑️ Удалить</button>
                        </div>
                    <?php else: ?>
                        <?php foreach ($options as $idx => $opt): 
                            $is_correct = false;
                            if ($is_multiple) {
                                $is_correct = in_array($idx, $correct_indices);
                            } else {
                                $data = base64_decode($opt['is_correct']);
                                $iv = substr($data, 0, 16);
                                $encrypted_data = substr($data, 16);
                                $decrypted = openssl_decrypt($encrypted_data, 'AES-256-CBC', ENCRYPTION_KEY, 0, $iv);
                                $is_correct = ($decrypted == '1');
                            }
                        ?>
                            <div class="option-row">
                                <input type="text" name="options[]" value="<?= htmlspecialchars($opt['option_text']) ?>" required style="width: 60%;">
                                <label>
                                    <input type="checkbox" name="correct[]" value="<?= $idx ?>" <?= $is_correct ? 'checked' : '' ?> class="correct-checkbox"> Правильный
                                </label>
                                <button type="button" class="remove-option-btn" onclick="this.parentElement.remove()">🗑️ Удалить</button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <button type="button" class="add-btn" onclick="addOption()">+ Добавить вариант</button>
                <button type="submit" style="margin-top: 15px;">💾 Сохранить</button>
            </form>
            <a href="edit_test.php?id=<?= $test_id ?>" class="back">← Назад к тесту</a>
        </div>
    </div>

    <script>
        function addOption() {
            const container = document.getElementById('options');
            const currentOptions = container.querySelectorAll('.option-row');
            const newIndex = currentOptions.length;
            
            const div = document.createElement('div');
            div.className = 'option-row';
            div.innerHTML = `
                <input type="text" name="options[]" placeholder="Вариант ${newIndex + 1}" required style="width: 60%;">
                <label>
                    <input type="checkbox" name="correct[]" value="${newIndex}" class="correct-checkbox"> Правильный
                </label>
                <button type="button" class="remove-option-btn" onclick="this.parentElement.remove()">🗑️ Удалить</button>
            `;
            container.appendChild(div);
        }
        
        function toggleCorrectType() {
            const type = document.getElementById('question_type').value;
            const checkboxes = document.querySelectorAll('.correct-checkbox');
            
            if (type === 'single') {
                checkboxes.forEach((checkbox) => {
                    const newInput = document.createElement('input');
                    newInput.type = 'radio';
                    newInput.name = 'correct[]';
                    newInput.value = checkbox.value;
                    newInput.className = 'correct-radio';
                    if (checkbox.checked) newInput.checked = true;
                    checkbox.parentElement.innerHTML = '';
                    checkbox.parentElement.appendChild(newInput);
                    checkbox.parentElement.appendChild(document.createTextNode(' Правильный'));
                });
            } else {
                checkboxes.forEach((radio) => {
                    const newInput = document.createElement('input');
                    newInput.type = 'checkbox';
                    newInput.name = 'correct[]';
                    newInput.value = radio.value;
                    newInput.className = 'correct-checkbox';
                    if (radio.checked) newInput.checked = true;
                    radio.parentElement.innerHTML = '';
                    radio.parentElement.appendChild(newInput);
                    radio.parentElement.appendChild(document.createTextNode(' Правильный'));
                });
            }
        }
    </script>
</body>
</html>