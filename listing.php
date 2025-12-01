<?php include_once("header.php")?>
<?php require("utilities.php")?>
<?php require("db_connection.php")?>

<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$item_id = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;
if ($item_id <= 0) {
  echo '<div class="container"><div class="alert alert-danger my-3">Invalid item id.</div></div>';
  include_once("footer.php");
  exit;
}

$sql = "SELECT 
          i.title,
          i.description,
          i.startPrice,
          i.finalPrice,
          i.endDate,
          i.status,
          i.winnerId,
          i.sellerId,
          COALESCE(MAX(b.bidAmount), i.startPrice) AS current_price,
          COUNT(b.bidId) AS num_bids
        FROM items i
        LEFT JOIN bid b ON i.itemId = b.itemId
        WHERE i.itemId = ?
        GROUP BY i.itemId";

$stmt = mysqli_prepare($connection, $sql);
mysqli_stmt_bind_param($stmt, 'i', $item_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

if (!$row) {
  echo '<div class="container"><div class="alert alert-danger my-3">Item not found.</div></div>';
  include_once("footer.php");
  exit;
}

$bid_error_msg = '';
if (isset($_GET['bid_error'])) {
  switch ($_GET['bid_error']) {
    case 'same_user':
      $bid_error_msg = 'You cannot place two consecutive bids on this item.';
      break;
    default:
      $bid_error_msg = 'Bid failed. Please try again.';
      break;
  }
}


$title         = $row['title'];
$description   = $row['description'];
$current_price = (float)$row['current_price'];
$num_bids      = (int)$row['num_bids'];
$end_time      = new DateTime($row['endDate']);
$status        = $row['status'];      
$winner_id     = (int)$row['winnerId'];

$currentUserId   = $_SESSION['user_id']      ?? null;
$currentUserType = $_SESSION['account_type'] ?? null;
$isBuyer         = ($currentUserId && $currentUserType === 'buyer');

$seller_id = null;
if (isset($row['sellerId'])) {
  $seller_id = (int)$row['sellerId'];
}
$isOwnerOfItem = ($currentUserId && $seller_id !== null && $currentUserId === $seller_id);

$now = new DateTime();
$time_remaining = '';

if ($now < $end_time) {
  $time_to_end    = date_diff($now, $end_time);
  $time_remaining = ' (in ' . display_time_remaining($time_to_end) . ')';
}


$has_session = isset($_SESSION['user_id']);  
$watching = false;                          

?>
<div class="container">

<div class="row"> <!-- Row #1 with auction title + watch button -->
  <div class="col-sm-8"> <!-- Left col -->
    <h2 class="my-3"><?php echo htmlspecialchars($title); ?></h2>
  </div>
  <div class="col-sm-4 align-self-center"> <!-- Right col -->
<?php

  if ($now < $end_time):
?>
    <div id="watch_nowatch" <?php if ($has_session && $watching) echo('style="display: none"');?> >
      <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addToWatchlist()">+ Add to watchlist</button>
    </div>
    <div id="watch_watching" <?php if (!$has_session || !$watching) echo('style="display: none"');?> >
      <button type="button" class="btn btn-success btn-sm" disabled>Watching</button>
      <button type="button" class="btn btn-danger btn-sm" onclick="removeFromWatchlist()">Remove watch</button>
    </div>
<?php endif /* Print nothing otherwise */ ?>
  </div>
</div>

<div class="row"> <!-- Row #2 with auction description + bidding info -->
  <div class="col-sm-8"> <!-- Left col with item info -->

    <div class="itemDescription">
      <?php echo nl2br(htmlspecialchars($description)); ?>
    </div>

    <hr>
    <p><strong>Number of bids:</strong> <?php echo $num_bids; ?></p>

    <?php
    $sql_hist = "SELECT b.bidAmount, b.bidTime, u.userName
                 FROM bid b
                 JOIN users u ON b.buyerId = u.userId
                 WHERE b.itemId = ?
                 ORDER BY b.bidTime DESC";
    $stmt_h = mysqli_prepare($connection, $sql_hist);
    mysqli_stmt_bind_param($stmt_h, 'i', $item_id);
    mysqli_stmt_execute($stmt_h);
    $hist_result = mysqli_stmt_get_result($stmt_h);
    ?>

    <h4 class="mt-4">Bid history</h4>
    <?php if (mysqli_num_rows($hist_result) === 0): ?>
      <p class="text-muted">No bids yet.</p>
    <?php else: ?>
      <table class="table table-sm">
        <thead>
          <tr>
            <th>Bidder</th>
            <th>Amount (£)</th>
            <th>Time</th>
          </tr>
        </thead>
        <tbody>
        <?php while ($b = mysqli_fetch_assoc($hist_result)): ?>
          <tr>
            <td><?php echo htmlspecialchars($b['userName']); ?></td>
            <td><?php echo number_format($b['bidAmount'], 2); ?></td>
            <td><?php echo htmlspecialchars($b['bidTime']); ?></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    <?php endif; ?>

  </div>

  <div class="col-sm-4"> <!-- Right col with bidding info -->

    <p>
<?php if ($now > $end_time): ?>
     This auction ended <?php echo(date_format($end_time, 'j M H:i')) ?>.
     <?php if ($winner_id > 0 && $num_bids > 0): ?>
       <br>Final price: £<?php echo number_format($row['finalPrice'] ?: $current_price, 2); ?>
       
     <?php else: ?>
       <br>No valid bids were placed.
     <?php endif; ?>
<?php else: ?>
     Auction ends <?php echo(date_format($end_time, 'j M H:i') . $time_remaining) ?></p>  
    <p class="lead">Current bid: £<?php echo(number_format($current_price, 2)) ?></p>

    <?php if (!empty($bid_error_msg)): ?>
      <div class="alert alert-danger" role="alert">
        <?php echo htmlspecialchars($bid_error_msg); ?>
      </div>
    <?php endif; ?>

    <?php if (!$has_session): ?>
      <p class="text-muted">Please log in as a buyer to place a bid.</p>
    <?php elseif (!$isBuyer): ?>
      <p class="text-muted">Only buyer accounts can place bids.</p>
    <?php elseif ($isOwnerOfItem): ?>
      <p class="text-muted">You cannot place bids on your own auction.</p>
    <?php else: ?>
      <!-- Bidding form -->
      <form method="POST" action="place_bid.php">
        <div class="input-group">
          <div class="input-group-prepend">
            <span class="input-group-text">£</span>
          </div>
        
          <input 
            type="number" 
            class="form-control" 
            id="bid"
            name="bidAmount"
            min="<?php echo htmlspecialchars(number_format($current_price + 0.01, 2, '.', '')); ?>"
            step="0.01"
            required
          >
        </div>
      
        <input type="hidden" name="itemId" value="<?php echo $item_id; ?>">
        <button type="submit" class="btn btn-primary form-control mt-2">Place bid</button>
      </form>
    <?php endif; ?>
<?php endif ?>


  
  </div> <!-- End of right col with bidding info -->

</div> <!-- End of row #2 -->

<?php include_once("footer.php")?>


<script> 
// JavaScript functions: addToWatchlist and removeFromWatchlist.

function addToWatchlist(button) {
  console.log("These print statements are helpful for debugging btw");


  $.ajax('watchlist_funcs.php', {
    type: "POST",
    data: {functionname: 'add_to_watchlist', arguments: [<?php echo($item_id);?>]},

    success: 
      function (obj, textstatus) {
        console.log("Success");
        var objT = obj.trim();
 
        if (objT == "success") {
          $("#watch_nowatch").hide();
          $("#watch_watching").show();
        }
        else {
          var mydiv = document.getElementById("watch_nowatch");
          mydiv.appendChild(document.createElement("br"));
          mydiv.appendChild(document.createTextNode("Add to watch failed. Try again later."));
        }
      },

    error:
      function (obj, textstatus) {
        console.log("Error");
      }
  }); // End of AJAX call
}

function removeFromWatchlist(button) {
  $.ajax('watchlist_funcs.php', {
    type: "POST",
    data: {functionname: 'remove_from_watchlist', arguments: [<?php echo($item_id);?>]},

    success: 
      function (obj, textstatus) {
        console.log("Success");
        var objT = obj.trim();
 
        if (objT == "success") {
          $("#watch_watching").hide();
          $("#watch_nowatch").show();
        }
        else {
          var mydiv = document.getElementById("watch_watching");
          mydiv.appendChild(document.createElement("br"));
          mydiv.appendChild(document.createTextNode("Watch removal failed. Try again later."));
        }
      },

    error:
      function (obj, textstatus) {
        console.log("Error");
      }
  }); // End of AJAX call
}
</script>
