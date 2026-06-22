<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireRole($pdo, 2);

$selected_group = $_GET['group_id'] ?? 0;

// Получаем данные для экспорта
if ($selected_group && $selected_group != 'all' && $selected_group != 'ungrouped') {
    $stmt = $pdo->prepare("
        SELECT u.full_name, u.login, u.current_xp, u.total_tests_passed, u.total_correct_answers, g.name as group_name 
        FROM users u 
        LEFT JOIN groups g ON u.group_id = g.id 
        WHERE u.role_id = 1 AND u.group_id = ?
        ORDER BY u.full_name
    ");
    $stmt->execute([$selected_group]);
    $group_info = $pdo->prepare("SELECT name FROM groups WHERE id = ?");
    $group_info->execute([$selected_group]);
    $group = $group_info->fetch();
    $filename = "students_group_" . $group['name'] . "_" . date('Y-m-d');
    
} elseif ($selected_group == 'ungrouped') {
    $stmt = $pdo->prepare("
        SELECT u.full_name, u.login, u.current_xp, u.total_tests_passed, u.total_correct_answers, 'Без группы' as group_name 
        FROM users u 
        WHERE u.role_id = 1 AND (u.group_id IS NULL OR u.group_id = 0)
        ORDER BY u.full_name
    ");
    $stmt->execute();
    $filename = "students_ungrouped_" . date('Y-m-d');
    
} else {
    $stmt = $pdo->prepare("
        SELECT u.full_name, u.login, u.current_xp, u.total_tests_passed, u.total_correct_answers, COALESCE(g.name, 'Без группы') as group_name 
        FROM users u 
        LEFT JOIN groups g ON u.group_id = g.id 
        WHERE u.role_id = 1 
        ORDER BY g.name, u.full_name
    ");
    $stmt->execute();
    $filename = "students_all_" . date('Y-m-d');
}

$students = $stmt->fetchAll();

// Устанавливаем заголовки для скачивания CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '.csv"');

// BOM для UTF-8
$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Заголовки
fputcsv($output, ['ФИО', 'Логин', 'Группа', 'Опыт (XP)', 'Тестов пройдено', 'Правильных ответов']);

// Данные
foreach ($students as $student) {
    fputcsv($output, [
        $student['full_name'],
        $student['login'],
        $student['group_name'],
        $student['current_xp'],
        $student['total_tests_passed'],
        $student['total_correct_answers']
    ]);
}

fclose($output);
exit();
?>