<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireRole($pdo, 2);

$test_id = $_GET['id'] ?? 0;

// Проверяем, что тест принадлежит этому преподавателю
$stmt = $pdo->prepare("SELECT * FROM tests WHERE id = ? AND created_by = ?");
$stmt->execute([$test_id, $_SESSION['user_id']]);
$test = $stmt->fetch();

if (!$test) {
    die("Тест не найден или доступ запрещён");
}

// Получаем данные
$stmt = $pdo->prepare("
    SELECT u.full_name, u.login, ta.score, 
           (SELECT COUNT(*) FROM questions WHERE test_id = ta.test_id) as total_questions,
           ta.started_at, ta.finished_at
    FROM test_attempts ta
    JOIN users u ON ta.student_id = u.id
    WHERE ta.test_id = ?
    ORDER BY ta.finished_at DESC
");
$stmt->execute([$test_id]);
$attempts = $stmt->fetchAll();

// Устанавливаем заголовки для скачивания CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="results_' . $test['title'] . '_' . date('Y-m-d') . '.csv"');

// Создаём вывод
$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM для UTF-8

// Заголовки
fputcsv($output, ['Студент', 'Логин', 'Баллы', 'Всего вопросов', 'Процент', 'Дата начала', 'Дата завершения']);

// Данные
foreach ($attempts as $attempt) {
    $percentage = round(($attempt['score'] / $attempt['total_questions']) * 100, 1);
    fputcsv($output, [
        $attempt['full_name'],
        $attempt['login'],
        $attempt['score'],
        $attempt['total_questions'],
        $percentage . '%',
        date('d.m.Y H:i', strtotime($attempt['started_at'])),
        $attempt['finished_at'] ? date('d.m.Y H:i', strtotime($attempt['finished_at'])) : '-'
    ]);
}

fclose($output);
exit();
?>