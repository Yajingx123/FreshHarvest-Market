<?php
// config/db_connect.php

// 数据库配置
define('DB_HOST', 'localhost');
define('DB_USER', 'root');      // 改成你的MySQL用户名
define('DB_PASS', '123456');          // 改成你的MySQL密码
define('DB_NAME', 'mydb');

// 创建数据库连接
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // 检查连接
    if ($conn->connect_error) {
        die("数据库连接失败: " . $conn->connect_error);
    }
    
    // 设置字符集
    $conn->set_charset("utf8mb4");
    
    return $conn;
}

// PDO 连接（用于需要 PDO 的页面）
function getPDOConnection() {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}

// 安全函数：防止SQL注入
function sanitize($input, $conn) {
    return $conn->real_escape_string(htmlspecialchars(trim($input)));
}
?>
