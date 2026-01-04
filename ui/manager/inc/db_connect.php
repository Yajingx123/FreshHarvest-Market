<?php
// db.php
$servername = "localhost";
$username = "ceo_user";
$password = "YourPassword123!";
$database = "mydb";

// 创建连接
$conn = new mysqli($servername, $username, $password, $database);

// 检查连接
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// 设置字符集
$conn->set_charset("utf8mb4");
?>
