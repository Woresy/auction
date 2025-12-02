<?php
session_start();
require_once 'db_connection.php';
require_once 'utilities.php';   


if (empty($_SESSION['user_id'])) {
    header("Location: index.php?login_error=bid_login");
    exit;
}

if(empty($_SESSION['account_type']) || $_SESSION['account_type'] !== 'buyer') {
    echo "<script>alert('You must be logged in as a buyer to place a bid.');window.history.back();</script>";
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

if ((int)$item['sellerId'] === $buyerId) {
    echo "<script>alert('You cannot bid on your own item.');window.history.back();</script>";
    exit;
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
$bidAmountInt = (int)$bidAmount;  

if ((float)$bidAmountInt != $bidAmount) {
    die("Your bid must be a whole number higher than the current price.");
}

$min_integer_bid = (int)floor($current_price) + 1;

if ($bidAmountInt < $min_integer_bid) {
    die("Your bid must be at least £" . $min_integer_bid . ".");
}

$bidAmount = (float)$bidAmountInt;

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

// Notify watchers about this new bid (file-based watchlists)
require_once 'send_mail.php';

// Get previous highest bidder (before this insert). We already fetched last_bid earlier.
$prev_highest_id = null;
if ($last_bid && isset($last_bid['buyerId'])) {
    $prev_highest_id = intval($last_bid['buyerId']);
}

$watchDir = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'watchlists' . DIRECTORY_SEPARATOR;
$watchers = [];
if (is_dir($watchDir)) {
    foreach (glob($watchDir . '*.json') as $f) {
        $uid = intval(basename($f, '.json'));
        $json = file_get_contents($f);
        $arr = json_decode($json, true);
        if (!is_array($arr)) continue;
        $vals = array_map('intval', $arr);
        if (in_array($itemId, $vals)) $watchers[] = $uid;
    }
}

if (!empty($watchers)) {
    // fetch watcher emails/usernames in one query
    $ids_list = implode(',', array_map('intval', $watchers));
    $sql_u = "SELECT userId, email, userName FROM users WHERE userId IN ($ids_list)";
    $res_u = mysqli_query($connection, $sql_u);
    while ($watch = mysqli_fetch_assoc($res_u)) {
        $to = $watch['email'];
        $uname = $watch['userName'];
        $uid = intval($watch['userId']);
        // Skip sending to bidder themselves
        if ($uid === $buyerId) continue;

        if ($prev_highest_id && $uid === $prev_highest_id) {
            // They were the previous highest - they've been outbid
            $subject = "You've been outbid on an auction";
            $body = "<p>Hi " . htmlspecialchars($uname) . ",</p>" .
                            "<p>You have been outbid on the auction <strong>" . htmlspecialchars($item['title']) . "</strong> (Item #" . $itemId . ").<br>" .
                            "New highest bid: £" . number_format($bidAmount,2) . "</p>" .
                            "<p><a href='" . (isset($_SERVER['HTTP_HOST']) ? 'http://' . $_SERVER['HTTP_HOST'] : '') . "/listing.php?item_id=" . $itemId . "'>View listing</a></p>";
            send_email($to, $subject, $body);
        } else {
            // Generic update to watchers
            $subject = "New bid on a watched auction";
            $body = "<p>Hi " . htmlspecialchars($uname) . ",</p>" .
                            "<p>A new bid of £" . number_format($bidAmount,2) . " was placed on the auction <strong>" . htmlspecialchars($item['title']) . "</strong> (Item #" . $itemId . ").</p>" .
                            "<p><a href='" . (isset($_SERVER['HTTP_HOST']) ? 'http://' . $_SERVER['HTTP_HOST'] : '') . "/listing.php?item_id=" . $itemId . "'>View listing</a></p>";
            send_email($to, $subject, $body);
        }
    }
}

header("Location: listing.php?item_id=" . $itemId . "&bid=success");
exit;
