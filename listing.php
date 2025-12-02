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
          i.imagePath,
          u.userName  AS winnerName,
          s.userName  AS sellerName,  
          COALESCE(MAX(b.bidAmount), i.startPrice) AS current_price,
          COUNT(b.bidId) AS num_bids
        FROM items i
        LEFT JOIN bid   b ON i.itemId   = b.itemId
        LEFT JOIN users u ON i.winnerId = u.userId
        LEFT JOIN users s ON i.sellerId = s.userId   
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

$title           = $row['title'];
$description     = $row['description'];
$image_path      = $row['imagePath'] ?? null;
$current_price   = (float)$row['current_price'];
$min_integer_bid = floor($current_price) + 1;
$num_bids        = (int)$row['num_bids'];
$end_time        = new DateTime($row['endDate']);
$status          = $row['status'];
$winner_id       = (int)$row['winnerId'];
$winner_name     = $row['winnerName'] ?? null;

$seller_id   = isset($row['sellerId']) ? (int)$row['sellerId'] : null;
$seller_name = $row['sellerName'] ?? null;

$currentUserId   = $_SESSION['user_id']      ?? null;
$currentUserType = $_SESSION['account_type'] ?? null;
$isBuyer         = ($currentUserId && $currentUserType === 'buyer');

$isOwnerOfItem = ($currentUserId && $seller_id !== null && $currentUserId === $seller_id);

$seller_avg_rating   = null;
$seller_feedback_cnt = 0;

if ($seller_id) {
  $sql_rating = "
      SELECT 
          AVG(rating) AS avg_rating,
          COUNT(*)    AS cnt
      FROM feedback
      WHERE sellerId = ?
  ";
  $stmt_rating = mysqli_prepare($connection, $sql_rating);
  mysqli_stmt_bind_param($stmt_rating, 'i', $seller_id);
  mysqli_stmt_execute($stmt_rating);
  $res_rating = mysqli_stmt_get_result($stmt_rating);
  if ($row_rating = mysqli_fetch_assoc($res_rating)) {
      $seller_avg_rating   = $row_rating['avg_rating']; 
      $seller_feedback_cnt = (int)$row_rating['cnt'];
  }
}

$now = new DateTime();
$time_remaining = '';

if ($now < $end_time) {
  $time_to_end    = date_diff($now, $end_time);
  $time_remaining = ' (in ' . display_time_remaining($time_to_end) . ')';
}

$hasEndedByTime = ($now >= $end_time);

if ($hasEndedByTime && $status !== 'closed') {
  $update_sql  = "UPDATE items SET status = 'closed' WHERE itemId = ? AND status <> 'closed'";
  $update_stmt = mysqli_prepare($connection, $update_sql);
  if ($update_stmt) {
    mysqli_stmt_bind_param($update_stmt, 'i', $item_id);
    mysqli_stmt_execute($update_stmt);
    $status = 'closed';
    // Send notifications to watchers about auction result
    require_once 'send_mail.php';

    // Get latest item info
    $info_sql = "SELECT i.title, i.finalPrice, i.winnerId, i.sellerId, u.userName AS winnerName
                 FROM items i
                 LEFT JOIN users u ON i.winnerId = u.userId
                 WHERE i.itemId = ? LIMIT 1";
    $info_stmt = mysqli_prepare($connection, $info_sql);
    if ($info_stmt) {
      mysqli_stmt_bind_param($info_stmt, 'i', $item_id);
      mysqli_stmt_execute($info_stmt);
      $info_res = mysqli_stmt_get_result($info_stmt);
      $info = mysqli_fetch_assoc($info_res);

      // File-based watchlists: scan files to find watchers
      $watchDir = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'watchlists' . DIRECTORY_SEPARATOR;
      if (is_dir($watchDir)) {
        foreach (glob($watchDir . '*.json') as $f) {
          $uid = intval(basename($f, '.json'));
          $json = file_get_contents($f);
          $arr = json_decode($json, true);
          if (!is_array($arr)) continue;
          $vals = array_map('intval', $arr);
          if (!in_array($item_id, $vals)) continue;

          // fetch user email and name
          $u_stmt = mysqli_prepare($connection, "SELECT email, userName FROM users WHERE userId = ? LIMIT 1");
          if (!$u_stmt) continue;
          mysqli_stmt_bind_param($u_stmt, 'i', $uid);
          mysqli_stmt_execute($u_stmt);
          $u_res = mysqli_stmt_get_result($u_stmt);
          $w = mysqli_fetch_assoc($u_res);
          if (!$w) continue;

          $to = $w['email'];
          $uname = $w['userName'];
          if (!$info) continue;
          $title_i = htmlspecialchars($info['title']);
          $final = number_format($info['finalPrice'], 2);
          $winnerId = intval($info['winnerId']);
          $sellerId = intval($info['sellerId']);

          if ($winnerId === $sellerId) {
            // Unsold
            $subject = "Auction ended - unsold: $title_i";
            $body = "<p>Hi " . htmlspecialchars($uname) . ",</p>" .
                    "<p>The auction <strong>$title_i</strong> (Item #$item_id) has ended with no winner (unsold).</p>" .
                    "<p><a href='" . (isset($_SERVER['HTTP_HOST']) ? 'http://' . $_SERVER['HTTP_HOST'] : '') . "/listing.php?item_id=" . $item_id . "'>View listing</a></p>";
            send_email($to, $subject, $body);
          } else {
            // Has winner
            $winnerName = htmlspecialchars($info['winnerName'] ?: ('User #' . $winnerId));
            $subject = "Auction ended - winner: $title_i";
            $body = "<p>Hi " . htmlspecialchars($uname) . ",</p>" .
                    "<p>The auction <strong>$title_i</strong> (Item #$item_id) has ended. Winner: <strong>$winnerName</strong>. Final price: £$final.</p>" .
                    "<p><a href='" . (isset($_SERVER['HTTP_HOST']) ? 'http://' . $_SERVER['HTTP_HOST'] : '') . "/listing.php?item_id=" . $item_id . "'>View listing</a></p>";
            send_email($to, $subject, $body);
          }
        }
      }
    }
  }
}

$hasEnded = $hasEndedByTime || ($status === 'closed');

$end_timestamp        = $end_time->getTimestamp();
$server_now_timestamp = $now->getTimestamp();

$has_session = isset($_SESSION['user_id']);  
$watching = false;                          

// If logged in, check whether the current user is watching this item (file-based)
if ($has_session) {
  $currUid = intval($_SESSION['user_id']);
  $watchFile = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'watchlists' . DIRECTORY_SEPARATOR . $currUid . '.json';
  if (file_exists($watchFile)) {
    $json = file_get_contents($watchFile);
    $arr = json_decode($json, true);
    if (is_array($arr) && in_array($item_id, array_map('intval', $arr))) $watching = true;
  }
}

?>
<div class="container">

<div class="row"> <!-- Row #1 with auction title + watch button -->
  <div class="col-sm-8"> <!-- Left col -->
    <h2 class="my-3"><?php echo htmlspecialchars($title); ?></h2>

    <?php if ($seller_id): ?>
      <p class="text-muted mb-1">
        <strong>Seller:</strong>
        <a href="profile.php?user_id=<?php echo (int)$seller_id; ?>">
          <?php echo htmlspecialchars($seller_name ?: ('User #' . $seller_id)); ?>
        </a>
        &nbsp;|&nbsp;
        <strong>Rating:</strong>
        <?php if ($seller_feedback_cnt > 0 && $seller_avg_rating !== null): ?>
          <span class="font-weight-bold">
            <?php echo number_format($seller_avg_rating, 1); ?>
          </span>/5
          (<?php echo $seller_feedback_cnt; ?> feedback<?php echo $seller_feedback_cnt > 1 ? 's' : ''; ?>)
        <?php else: ?>
          <span>No feedback yet.</span>
        <?php endif; ?>
      </p>
    <?php endif; ?>
  </div>

  <div class="col-sm-4 align-self-center"> <!-- Right col -->
<?php if (!$hasEnded): ?>
    <?php if ($isBuyer): ?>
    <div id="watch_nowatch" <?php if ($has_session && $watching) echo('style="display: none"');?> >
      <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addToWatchlist()">+ Add to watchlist</button>
    </div>
    <div id="watch_watching" <?php if (!$has_session || !$watching) echo('style="display: none"');?> >
      <a class="btn btn-success btn-sm" href="watchlist.php">Watching</a>
      <button type="button" class="btn btn-danger btn-sm" onclick="removeFromWatchlist()">Remove watch</button>
    </div>
    <?php endif; ?>
<?php endif; ?>
  </div>
</div>

<div class="row"> <!-- Row #2 with auction description + bidding info -->
  <div class="col-sm-8"> <!-- Left col with item info -->

    <!-- image display -->
    <?php if (!empty($image_path)): ?>
      <div class="text-center mb-3">
        <img src="<?php echo htmlspecialchars($image_path); ?>" 
             alt="Item Image"
             style="max-width: 350px; height: auto; border-radius: 8px;">
      </div>
    <?php endif; ?>

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

  <div class="col-sm-4">

<?php if ($hasEnded): ?>
    <div class="card shadow-sm mb-3">
      <div class="card-body">

        <div class="text-muted small">
          Auction ended on
        </div>
        <div class="font-weight-semibold mb-3">
          <?php echo date_format($end_time, 'j M Y H:i'); ?>
        </div>

        <?php if ($winner_id > 0 && $num_bids > 0): ?>
          <div class="d-flex justify-content-between align-items-end">
            <div>
              <div class="text-muted small">Final price</div>
              <div class="h4 mb-0">
                £<?php echo number_format($row['finalPrice'] ?: $current_price, 2); ?>
              </div>
            </div>
            <div class="text-right">
              <div class="text-muted small">Winner</div>
              <div class="h5 mb-0">
                <?php echo htmlspecialchars($winner_name ?: ('User #' . $winner_id)); ?>
              </div>
            </div>
          </div>

          <div class="mt-3 text-muted small">
            Bids placed: <strong><?php echo $num_bids; ?></strong>
          </div>
        <?php else: ?>
          <div class="alert alert-secondary mb-0 mt-2">
            No valid bids were placed for this auction.
          </div>
        <?php endif; ?>

      </div>
    </div>
<?php else: ?>
    <p>
      Auction ends <?php echo(date_format($end_time, 'j M H:i')) ?>
    </p>
     
    <p>
      <strong>Time remaining:</strong>
      <span id="time-remaining"
            data-end="<?php echo $end_timestamp; ?>"
            data-server-now="<?php echo $server_now_timestamp; ?>">
        <?php
          if ($now < $end_time) {
            $total_sec = $end_time->getTimestamp() - $now->getTimestamp();
            $d = intdiv($total_sec, 86400);
            $h = intdiv($total_sec % 86400, 3600);
            $m = intdiv($total_sec % 3600, 60);
            $s = $total_sec % 60;
            echo "{$d}d {$h}h {$m}m {$s}s";
          } else {
            echo "0d 0h 0m 0s";
          }
        ?>
      </span>
    </p>
     
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
            min="<?php echo (int)$min_integer_bid; ?>" 
            step="1"
            required
          >
        </div>
      
        <input type="hidden" name="itemId" value="<?php echo $item_id; ?>">
        <button type="submit" class="btn btn-primary form-control mt-2">Place bid</button>
      </form>
    <?php endif; ?>
<?php endif; ?>

  </div> <!-- End of right col with bidding info -->

</div> <!-- End of row #2 -->

<?php include_once("footer.php")?>


<script> 
// JavaScript functions: addToWatchlist and removeFromWatchlist.

document.addEventListener('DOMContentLoaded', function () {
  var span = document.getElementById('time-remaining');
  if (!span) return;

  var endSec       = parseInt(span.getAttribute('data-end'), 10);
  var serverNowSec = parseInt(span.getAttribute('data-server-now'), 10);
  if (isNaN(endSec) || isNaN(serverNowSec)) return;

  var endMs       = endSec * 1000;
  var serverNowMs = serverNowSec * 1000;
  var offset      = Date.now() - serverNowMs;

  function updateTimeRemaining() {
    var nowMs = Date.now() - offset;
    var diff  = endMs - nowMs;

    if (diff <= 0) {
      span.textContent = '0d 0h 0m 0s';
      return;
    }

    var totalSec = Math.floor(diff / 1000);
    var d = Math.floor(totalSec / 86400);
    var h = Math.floor((totalSec % 86400) / 3600);
    var m = Math.floor((totalSec % 3600) / 60);
    var s = totalSec % 60;

    span.textContent = d + 'd ' + h + 'h ' + m + 'm ' + s + 's';
  }

  updateTimeRemaining();
  setInterval(updateTimeRemaining, 1000);
});

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
  });
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
  });
}
</script>
