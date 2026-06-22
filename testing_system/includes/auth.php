<?php
// includes/auth.php - функции для авторизации и проверки ролей

session_start();

// Проверка, авторизован ли пользователь
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Проверка роли (1 - студент, 2 - преподаватель, 3 - администратор)
function hasRole($role_id) {
    return isset($_SESSION['role_id']) && $_SESSION['role_id'] == $role_id;
}

// Получение данных текущего пользователя
function getCurrentUser($pdo) {
    if (!isLoggedIn()) return null;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Перенаправление, если не авторизован
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /testing_system/index.php');
        exit();
    }
}

// Перенаправление, если нет нужной роли
function requireRole($pdo, $required_role_id) {
    requireLogin();
    if ($_SESSION['role_id'] != $required_role_id) {
        header('Location: /testing_system/index.php?error=access_denied');
        exit();
    }
}
?>