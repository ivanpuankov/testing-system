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

// Проверяем, есть ли у теста пройденные попытки
$stmt = $pdo->prepare("SELECT COUNT(*) as attempts_count FROM test_attempts WHERE test_id = ?");
$stmt->execute([$test_id]);
$attempts_count = $stmt->fetch()['attempts_count'];

if ($attempts_count > 0) {
    $_SESSION['error'] = "Невозможно удалить тест, так как есть студенты, которые его уже прошли. Удалите сначала результаты или отключите тест.";
    header('Location: index.php');
    exit();
}

// Удаляем тест
$stmt = $pdo->prepare("DELETE FROM tests WHERE id = ?");
$stmt->execute([$test_id]);

$_SESSION['success'] = "Тест «" . htmlspecialchars($test['title']) . "» успешно удалён";
header('Location: index.php');
exit();
?>