<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireRole($pdo, 1);

// Секретный ключ для шифрования (должен быть одинаковым с edit_test.php)
define('ENCRYPTION_KEY', 'your_secret_key_32_bytes_long_!!!');

// Функция расшифровки для одиночного выбора
function decryptCorrect($encrypted_data, $key) {
    $data = base64_decode($encrypted_data);
    $iv = substr($data, 0, 16);
    $encrypted = substr($data, 16);
    return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
}

// Функция расшифровки для множественного выбора
function decryptMultipleAnswers($encrypted_data, $key) {
    $data = base64_decode($encrypted_data);
    $iv = substr($data, 0, 16);
    $encrypted = substr($data, 16);
    $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    return json_decode($decrypted, true);
}

$test_id = $_GET['id'] ?? 0;

// Получаем информацию о тесте
$stmt = $pdo->prepare("SELECT * FROM tests WHERE id = ? AND is_active = 1");
$stmt->execute([$test_id]);
$test = $stmt->fetch();

if (!$test) {
    die("<!DOCTYPE html>
    <html>
    <head><title>Ошибка</title></head>
    <body style='font-family: Arial; text-align: center; padding: 50px;'>
        <h1>❌ Тест не найден</h1>
        <p>Такой тест не существует или недоступен.</p>
        <a href='index.php'>← Вернуться к списку тестов</a>
    </body>
    </html>");
}

// Удаляем старые незавершённые попытки
$stmt = $pdo->prepare("DELETE FROM test_attempts WHERE test_id = ? AND student_id = ? AND status = 'in_progress'");
$stmt->execute([$test_id, $_SESSION['user_id']]);

// Проверка лимита попыток
if ($test['max_attempts'] > 0) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as attempts_count FROM test_attempts WHERE test_id = ? AND student_id = ? AND status = 'completed'");
    $stmt->execute([$test_id, $_SESSION['user_id']]);
    $attempts_done = $stmt->fetch()['attempts_count'];
    
    if ($attempts_done >= $test['max_attempts']) {
        die("
            <!DOCTYPE html>
            <html>
            <head>
                <title>Доступ запрещён</title>
                <style>
                    body { font-family: Arial; text-align: center; padding: 50px; }
                    h1 { color: #e74c3c; }
                </style>
            </head>
            <body>
                <h1>⛔ Доступ запрещён</h1>
                <p>Вы исчерпали лимит попыток для этого теста.</p>
                <p>📊 Максимум попыток: <strong>{$test['max_attempts']}</strong></p>
                <p>✅ Вы использовали: <strong>{$attempts_done}</strong> попыток(и)</p>
                <a href='index.php'>← Вернуться к списку тестов</a>
            </body>
            </html>
        ");
    }
}

// Создаём новую попытку прохождения
$stmt = $pdo->prepare("INSERT INTO test_attempts (student_id, test_id, status, started_at) VALUES (?, ?, 'in_progress', NOW())");
$stmt->execute([$_SESSION['user_id'], $test_id]);
$attempt_id = $pdo->lastInsertId();

// Получаем вопросы теста
$stmt = $pdo->prepare("SELECT * FROM questions WHERE test_id = ? ORDER BY id");
$stmt->execute([$test_id]);
$questions = $stmt->fetchAll();
$total_questions = count($questions);

// ==============================================
// ОБРАБОТКА ОТВЕТОВ (с поддержкой множественного выбора)
// ==============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $score = 0;
    
    foreach ($questions as $question) {
        $user_answer = $_POST['question_' . $question['id']] ?? null;
        
        if (!$user_answer) {
            continue;
        }
        
        // Приводим ответ к массиву (для одиночного выбора будет массив из 1 элемента)
        $answers = is_array($user_answer) ? $user_answer : [$user_answer];
        
        // Получаем варианты ответов для этого вопроса
        $stmt = $pdo->prepare("SELECT * FROM answer_options WHERE question_id = ? ORDER BY id");
        $stmt->execute([$question['id']]);
        $options = $stmt->fetchAll();
        
        $is_multiple = ($question['question_type'] == 'multiple');
        
        if ($is_multiple) {
            // ========== МНОЖЕСТВЕННЫЙ ВЫБОР ==========
            // В поле is_correct хранится зашифрованный JSON с индексами правильных ответов
            $encrypted = $options[0]['is_correct'];
            $correct_indices = decryptMultipleAnswers($encrypted, ENCRYPTION_KEY);
            
            // Собираем индексы выбранных ответов
            $selected_indices = [];
            foreach ($answers as $answer_id) {
                // Находим индекс варианта
                foreach ($options as $idx => $opt) {
                    if ($opt['id'] == $answer_id) {
                        $selected_indices[] = $idx;
                        break;
                    }
                }
            }
            
            // Проверяем, совпадают ли выбранные индексы с правильными
            sort($selected_indices);
            sort($correct_indices);
            
            if ($selected_indices == $correct_indices && !empty($correct_indices)) {
                $score++;
                // Сохраняем все выбранные ответы
                foreach ($answers as $answer_id) {
                    $stmt = $pdo->prepare("INSERT INTO student_answers (attempt_id, question_id, selected_option_id) VALUES (?, ?, ?)");
                    $stmt->execute([$attempt_id, $question['id'], $answer_id]);
                }
            }
        } else {
            // ========== ОДИНОЧНЫЙ ВЫБОР ==========
            $user_answer_id = $answers[0];
            
            // Находим выбранный вариант
            $selected_option = null;
            foreach ($options as $opt) {
                if ($opt['id'] == $user_answer_id) {
                    $selected_option = $opt;
                    break;
                }
            }
            
            if ($selected_option) {
                $is_correct_value = decryptCorrect($selected_option['is_correct'], ENCRYPTION_KEY);
                
                if ($is_correct_value == '1') {
                    $score++;
                    $stmt = $pdo->prepare("INSERT INTO student_answers (attempt_id, question_id, selected_option_id) VALUES (?, ?, ?)");
                    $stmt->execute([$attempt_id, $question['id'], $user_answer_id]);
                }
            }
        }
    }
    
    // Обновляем попытку с результатом
    $stmt = $pdo->prepare("UPDATE test_attempts SET score = ?, status = 'completed', finished_at = NOW() WHERE id = ?");
    $stmt->execute([$score, $attempt_id]);
    
    // Обновляем статистику пользователя
    $stmt = $pdo->prepare("UPDATE users SET total_tests_passed = total_tests_passed + 1, total_correct_answers = total_correct_answers + ? WHERE id = ?");
    $stmt->execute([$score, $_SESSION['user_id']]);
    
    // Начисляем опыт (10 базовых + 2 за каждый правильный ответ)
    $xp_earned = 10 + ($score * 2);
    $perfect_bonus = 0;
    
    if ($score == $total_questions) {
        $perfect_bonus = 30;
        $xp_earned += 30;
        
        // Проверка ачивки "Идеал"
        $stmt = $pdo->prepare("SELECT id FROM achievements WHERE name LIKE '%Идеал%'");
        $stmt->execute();
        $achievement = $stmt->fetch();
        if ($achievement) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO user_achievements (user_id, achievement_id) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'], $achievement['id']]);
        }
    }
    
    // Проверка ачивки "Первый шаг"
    $stmt = $pdo->prepare("SELECT COUNT(*) as tests_count FROM test_attempts WHERE student_id = ? AND status = 'completed'");
    $stmt->execute([$_SESSION['user_id']]);
    $total_tests = $stmt->fetch()['tests_count'];
    
    if ($total_tests == 1) {
        $stmt = $pdo->prepare("SELECT id FROM achievements WHERE name LIKE '%Первый шаг%'");
        $stmt->execute();
        $achievement = $stmt->fetch();
        if ($achievement) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO user_achievements (user_id, achievement_id) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'], $achievement['id']]);
        }
    }
    
    // Обновляем опыт пользователя
    $stmt = $pdo->prepare("UPDATE users SET current_xp = current_xp + ? WHERE id = ?");
    $stmt->execute([$xp_earned, $_SESSION['user_id']]);
    
    // Логируем начисление опыта
    $stmt = $pdo->prepare("INSERT INTO xp_log (user_id, amount, source, source_id) VALUES (?, ?, 'test_completed', ?)");
    $stmt->execute([$_SESSION['user_id'], $xp_earned, $test_id]);
    
    // Проверка повышения уровня
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    $stmt = $pdo->prepare("SELECT * FROM levels WHERE min_xp <= ? ORDER BY min_xp DESC LIMIT 1");
    $stmt->execute([$user['current_xp']]);
    $new_level = $stmt->fetch();
    
    $level_up = false;
    if ($new_level && $new_level['id'] != $user['current_level_id']) {
        $stmt = $pdo->prepare("UPDATE users SET current_level_id = ? WHERE id = ?");
        $stmt->execute([$new_level['id'], $_SESSION['user_id']]);
        $level_up = true;
    }
    
    // Перенаправляем на страницу результата
    header("Location: result.php?attempt_id=$attempt_id&xp=$xp_earned&perfect=$perfect_bonus&level_up=" . ($level_up ? '1' : '0'));
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($test['title']) ?> - Прохождение теста</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #f0f2f5;
            padding: 20px;
        }
        .container { max-width: 800px; margin: 0 auto; }
        .header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .timer {
            font-size: 28px;
            font-weight: bold;
            color: #e74c3c;
            margin-top: 10px;
            font-family: monospace;
        }
        .question-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .question-text {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #333;
        }
        .question-number {
            color: #666;
            margin-bottom: 10px;
            font-size: 14px;
        }
        .question-type {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 11px;
            margin-left: 10px;
        }
        .type-single { background: #3498db; color: white; }
        .type-multiple { background: #9b59b6; color: white; }
        .option {
            margin: 10px 0;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.2s;
            display: block;
        }
        .option:hover { background: #f0f2f5; }
        .option input {
            margin-right: 10px;
            transform: scale(1.1);
        }
        .btn-submit {
            display: block;
            width: 100%;
            padding: 15px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 18px;
            cursor: pointer;
            margin-top: 20px;
            font-weight: bold;
        }
        .btn-submit:hover { background: #219a52; }
        .progress-info {
            background: #e8f0fe;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .hint {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📝 <?= htmlspecialchars($test['title']) ?></h1>
            <?php if ($test['time_limit'] > 0): ?>
                <div class="timer" id="timer">⏱️ <?= $test['time_limit'] ?>:00</div>
                <p class="hint">Тест будет автоматически завершён по истечении времени</p>
            <?php endif; ?>
        </div>

        <div class="progress-info">📋 Всего вопросов: <?= $total_questions ?></div>

        <form method="POST" id="testForm">
            <?php foreach ($questions as $index => $question): 
                $is_multiple = ($question['question_type'] == 'multiple');
                $type_label = $is_multiple ? 'Множественный выбор (можно выбрать несколько ответов)' : 'Одиночный выбор (только один ответ)';
                $type_class = $is_multiple ? 'type-multiple' : 'type-single';
            ?>
                <div class="question-card">
                    <div class="question-number">
                        ❓ Вопрос <?= $index + 1 ?> из <?= $total_questions ?>
                        <span class="question-type <?= $type_class ?>"><?= $type_label ?></span>
                    </div>
                    <div class="question-text"><?= htmlspecialchars($question['question_text']) ?></div>
                    
                    <?php
                    $stmt = $pdo->prepare("SELECT * FROM answer_options WHERE question_id = ? ORDER BY id");
                    $stmt->execute([$question['id']]);
                    $options = $stmt->fetchAll();
                    ?>
                    
                    <?php if ($is_multiple): ?>
                        <?php foreach ($options as $option): ?>
                            <label class="option">
                                <input type="checkbox" 
                                       name="question_<?= $question['id'] ?>[]" 
                                       value="<?= $option['id'] ?>">
                                <?= htmlspecialchars($option['option_text']) ?>
                            </label>
                        <?php endforeach; ?>
                        <?php if (count($options) == 0): ?>
                            <p style="color: #e74c3c;">⚠️ Нет вариантов ответов. Добавьте их в настройках теста.</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php foreach ($options as $option): ?>
                            <label class="option">
                                <input type="radio" 
                                       name="question_<?= $question['id'] ?>" 
                                       value="<?= $option['id'] ?>" 
                                       required>
                                <?= htmlspecialchars($option['option_text']) ?>
                            </label>
                        <?php endforeach; ?>
                        <?php if (count($options) == 0): ?>
                            <p style="color: #e74c3c;">⚠️ Нет вариантов ответов. Добавьте их в настройках теста.</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
            <button type="submit" class="btn-submit">✅ Завершить тест</button>
        </form>
    </div>

    <?php if ($test['time_limit'] > 0): ?>
    <script>
        let timeLeft = <?= $test['time_limit'] * 60 ?>;
        let timerElement = document.getElementById('timer');
        
        function updateTimer() {
            let minutes = Math.floor(timeLeft / 60);
            let seconds = timeLeft % 60;
            timerElement.textContent = `⏱️ ${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            if (timeLeft <= 0) {
                alert('Время вышло! Тест будет автоматически отправлен.');
                document.getElementById('testForm').submit();
            }
            
            if (timeLeft <= 60) {
                timerElement.style.color = '#e74c3c';
                timerElement.style.fontSize = '32px';
            }
            timeLeft--;
        }
        
        setInterval(updateTimer, 1000);
    </script>
    <?php endif; ?>
</body>
</html>