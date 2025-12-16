<?php
// UI/config/db_connect.php

$DB_HOST = "127.0.0.1";
$DB_PORT = "3306";
$DB_NAME = "mydb";
$DB_USER = "root";      
$DB_PASS = "NewRootPwd123!"; 

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, (int)$DB_PORT);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    die("Database connection failed: " . $e->getMessage());
}