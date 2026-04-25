<?php
/**
 * Database Configuration
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'phongtro');

// Tạo kết nối
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Kiểm tra kết nối
if (!$conn) {
    die("Kết nối database thất bại: " . mysqli_connect_error());
}

// Set charset
mysqli_set_charset($conn, "utf8mb4");

?>
