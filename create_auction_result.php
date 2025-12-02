<?php
// create_auction_result.php
session_start();
include_once('db_connection.php');

// 必须先检查是否已登录且是 seller
if (!isset($_SESSION['logged_in']) || $_SESSION['account_type'] !== 'seller') {
    echo "<script>alert('You must be logged in as a seller to create an auction.'); 
    window.location.href='index.php';</script>";
    exit;
}

// 获取表单字段
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$category = trim($_POST['category'] ?? '');
$startPrice = $_POST['startprice'] ?? '';
$endDate = $_POST['enddate'] ?? '';

$sellerId = $_SESSION['user_id'];
$startDate = date('Y-m-d H:i:s');  // 当前时间

// Handle image upload
$imagePath = NULL;

if (isset($_FILES['itemImage']) && $_FILES['itemImage']['error'] === UPLOAD_ERR_OK) {

    $target_dir = "uploads/";

    // 若 uploads 文件夹不存在则创建
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $tmp = $_FILES['itemImage']['tmp_name'];
    $name = time() . "_" . basename($_FILES['itemImage']['name']);
    $target_file = $target_dir . $name;

    $allowed_ext = ['jpg','jpeg','png','gif'];
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    if (in_array($ext, $allowed_ext)) {
        if (move_uploaded_file($tmp, $target_file)) {
            $imagePath = $target_file;
        }
    }
}




// 基本验证
if (!$title || !$description || !$category || !$startPrice || !$endDate) {
    echo "<script>alert('Please fill in all required fields.'); 
    window.location.href='create_auction.php';</script>";
    exit;
}

// 安全检查：startPrice 必须是数字
if (!is_numeric($startPrice) || $startPrice <= 0) {
    echo "<script>alert('Start price must be a positive number.'); 
    window.location.href='create_auction.php';</script>";
    exit;
}

// 因为 items 表结构需要 NOT NULL 的 finalPrice 和 winnerId
$finalPrice = $startPrice;
$winnerId = $sellerId;    // 没有出价前临时写为卖家

$status = 'active';       // 拍卖开始时设置为 active

// 插入 SQL
$sql = "INSERT INTO items 
        (sellerId, title, description, category, startPrice, finalPrice, startDate, endDate, status, winnerId, imagePath)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

if ($stmt = mysqli_prepare($connection, $sql)) {

    mysqli_stmt_bind_param(
        $stmt, 
        "isssiisssis",
        $sellerId,
        $title,
        $description,
        $category,
        $startPrice,
        $finalPrice,
        $startDate,
        $endDate,
        $status,
        $winnerId,
        $imagePath
    );

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        echo "<script>alert('Auction created successfully!'); 
        window.location.href='mylistings.php';</script>";
        exit;
    } else {
        $err = mysqli_error($connection);
        echo "<script>alert('Failed to create auction. DB error: \\n$err'); 
        window.location.href='create_auction.php';</script>";
        exit;
    }

} else {
    $err = mysqli_error($connection);
    echo "<script>alert('Failed to prepare statement: \\n$err'); 
    window.location.href='create_auction.php';</script>";
    exit;
}

?>
