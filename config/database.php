<?php
$host = 'localhost';
$db   = 'inseptum';
$user = 'root';
$pass = 'root'; // Пароль по умолчанию в MAMP

// Подключаемся к базе данных
$connect = mysqli_connect($host, $user, $pass, $db);

// Проверяем соединение
if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

// Устанавливаем кодировку UTF-8
mysqli_set_charset($connect, "utf8mb4");

// echo "Database connected successfully!";
?>