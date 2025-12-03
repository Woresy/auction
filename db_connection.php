<?php
// db_connect.php
// 负责建立到 auction_database 的连接

# WRY
// $host     = 'localhost';
// $user     = 'auction_user';       // 你在 phpMyAdmin 里创建的用户名
// $password = 'StrongPass123!';
// $dbname   = 'auction_database';

date_default_timezone_set('Europe/London');

# GMZ
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'auction';



$connection = mysqli_connect($host, $user, $password, $dbname);

if (!$connection) {
    die('Failed to connect to MySQL: ' . mysqli_connect_error());
}

// 建议设置一下字符集，保证中文正常
mysqli_set_charset($connection, 'utf8mb4');

echo "DB connection OK<br>";
?>
