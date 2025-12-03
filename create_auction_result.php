<?php
// create_auction_result.php
session_start();
include_once('db_connection.php');

if (!isset($_SESSION['logged_in']) || $_SESSION['account_type'] !== 'seller') {
    echo "<script>alert('You must be logged in as a seller to create an auction.'); 
    window.location.href='index.php';</script>";
    exit;
}

$title       = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$category    = trim($_POST['category'] ?? '');
$startPrice  = $_POST['startprice'] ?? '';
$endDate     = $_POST['enddate'] ?? '';

$reservePriceRaw = $_POST['reservePrice'] ?? '';

$sellerId  = $_SESSION['user_id'];
$startDate = date('Y-m-d H:i:s');  

$imagePath = NULL;

if (isset($_FILES['itemImage']) && $_FILES['itemImage']['error'] === UPLOAD_ERR_OK) {

    $target_dir = "uploads/";

    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $tmp  = $_FILES['itemImage']['tmp_name'];
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

if (!$title || !$description || !$category || !$startPrice || !$endDate) {
    echo "<script>alert('Please fill in all required fields.'); 
    window.location.href='create_auction.php';</script>";
    exit;
}

if (!is_numeric($startPrice) || $startPrice <= 0) {
    echo "<script>alert('Start price must be a positive number.'); 
    window.location.href='create_auction.php';</script>";
    exit;
}

$startPrice = (int)$startPrice;

if ($reservePriceRaw === '' || $reservePriceRaw === null) {
    $reservePrice = 0;
} else {
    if (!ctype_digit((string)$reservePriceRaw)) {
        echo "<script>alert('Reserve price must be a non-negative integer.'); 
        window.location.href='create_auction.php';</script>";
        exit;
    }
    $reservePrice = (int)$reservePriceRaw;
    if ($reservePrice < 0) {
        echo "<script>alert('Reserve price cannot be negative.'); 
        window.location.href='create_auction.php';</script>";
        exit;
    }

    if ($reservePrice > 0 && $reservePrice < $startPrice) {
        echo "<script>alert('Reserve price cannot be lower than starting price.'); 
        window.location.href='create_auction.php';</script>";
        exit;
    }
}

$finalPrice = $startPrice;
$winnerId   = $sellerId;    

$status = 'active';       


$sql = "INSERT INTO items 
        (sellerId, title, description, category, startPrice, reservePrice, finalPrice, startDate, endDate, status, winnerId, imagePath)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

if ($stmt = mysqli_prepare($connection, $sql)) {

    mysqli_stmt_bind_param(
        $stmt, 
        "isssiiisssis",
        $sellerId,      // i
        $title,         // s
        $description,   // s
        $category,      // s
        $startPrice,    // i
        $reservePrice,  // i 
        $finalPrice,    // i
        $startDate,     // s
        $endDate,       // s
        $status,        // s
        $winnerId,      // i
        $imagePath      // s
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
