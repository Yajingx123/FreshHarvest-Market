<?php
// db.php
$servername = "localhost";
$username = "root";
$password = "NewRootPwd123!";
$database = "mydb";

// 创建连接
$conn = new mysqli($servername, $username, $password, $database);

// 检查连接
if ($conn->connect_error) {
    die("数据库连接失败: " . $conn->connect_error);
}

// 设置字符集
$conn->set_charset("utf8mb4");
?>