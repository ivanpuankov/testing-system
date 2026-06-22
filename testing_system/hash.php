<?php
// hash.php - генерация правильного хеша пароля

$password = '123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Пароль: 123<br>";
echo "Хеш: " . $hash . "<br>";
echo "<hr>";

// Проверка
if (password_verify('123', $hash)) {
    echo "✅ Проверка пройдена: пароль 123 соответствует хешу";
} else {
    echo "❌ Ошибка проверки";
}
?>