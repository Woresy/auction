<?php
session_start();
require_once 'db_connection.php';
require_once 'utilities.php';   


if (empty($_SESSION['user_id'])) {
    header("Location: index.php?login_error=bid_login");
    exit;
}

$buyerId  = (int)$_SESSION['user_id'];
$itemId   = isset($_POST['itemId'])    ? (int)$_POST['itemId']    : 0;
$bidAmount = isset($_POST['bidAmount']) ? (float)$_POST['bidAmount'] : 0.0;

if ($itemId <= 0 || $bidAmount <= 0) {
    die("Invalid bid.");
}

$sql_item = "SELECT 
                i.itemId,
                i.sellerId,
                i.title,
                i.startPrice,
                i.finalPrice,
                i.endDate,
                i.status,
                i.winnerId,
                COALESCE(MAX(b.bidAmount), i.startPrice) AS current_price
             FROM items i
             LEFT JOIN bid b ON i.itemId = b.itemId
             WHERE i.itemId = ?
             GROUP BY i.itemId";

$stmt = mysqli_prepare($connection, $sql_item);
mysqli_stmt_bind_param($stmt, 'i', $itemId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$item   = mysqli_fetch_assoc($result);

if (!$item) {
    die("Item not found.");
}

$now      = new DateTime();
$end_time = new DateTime($item['endDate']);

if ($now > $end_time) {

    die("This auction has already ended.");
}

if ($item['status'] !== 'active' && $item['status'] !== 'open') {

    die("This auction is not active.");
}

$current_price = (float)$item['current_price'];
$min_increment = 1;  

if ($bidAmount < $current_price + $min_increment) {
    die("Your bid must be higher than the current price.");
}

$sql_last = "SELECT buyerId 
             FROM bid 
             WHERE itemId = ?
             ORDER BY bidTime DESC, bidId DESC
             LIMIT 1";

$stmt_last = mysqli_prepare($connection, $sql_last);
mysqli_stmt_bind_param($stmt_last, 'i', $itemId);
mysqli_stmt_execute($stmt_last);
$res_last = mysqli_stmt_get_result($stmt_last);
$last_bid = mysqli_fetch_assoc($res_last);
mysqli_stmt_close($stmt_last);

if ($last_bid && (int)$last_bid['buyerId'] === $buyerId) {
    header("Location: listing.php?item_id=" . $itemId . "&bid_error=same_user");
    exit;
}

$sql_insert = "INSERT INTO bid (itemId, buyerId, bidAmount, bidTime)
               VALUES (?, ?, ?, NOW())";
$stmt_ins = mysqli_prepare($connection, $sql_insert);
mysqli_stmt_bind_param($stmt_ins, 'iid', $itemId, $buyerId, $bidAmount);
mysqli_stmt_execute($stmt_ins);


$sql_update = "UPDATE items
               SET finalPrice = ?, winnerId = ?
               WHERE itemId = ?";
$stmt_upd = mysqli_prepare($connection, $sql_update);
mysqli_stmt_bind_param($stmt_upd, 'dii', $bidAmount, $buyerId, $itemId);
mysqli_stmt_execute($stmt_upd);


header("Location: listing.php?item_id=" . $itemId . "&bid=success");
exit;
