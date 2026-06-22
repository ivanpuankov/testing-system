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
    $_SESSION['error'] = "Тест не найден или доступ запрещён";
    header('Location: index.php');
    exit();
}

// Получаем количество попыток перед удалением
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM test_attempts WHERE test_id = ?");
$stmt->execute([$test_id]);
$count = $stmt->fetch()['count'];

if ($count == 0) {
    $_SESSION['error'] = "У этого теста нет результатов для удаления";
    header('Location: results.php?id=' . $test_id);
    exit();
}

// Удаляем все попытки по этому тесту
$stmt = $pdo->prepare("DELETE FROM test_attempts WHERE test_id = ?");
$stmt->execute([$test_id]);

$_SESSION['success'] = "Удалено {$count} результатов по тесту «" . htmlspecialchars($test['title']) . "»";
header('Location: results.php?id=' . $test_id);
exit();
?>