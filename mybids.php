<?php
include_once 'header.php';
require_once 'db_connection.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header("Location: index.php?login_error=auth");
    exit;
}

if (empty($_SESSION['account_type']) || $_SESSION['account_type'] !== 'buyer') {
    echo "<script>alert('You must be logged in as a buyer to view your bids.'); window.location.href='index.php';</script>";
    exit;
}

$userId = (int)$_SESSION['user_id'];

// MOD: 扩展查询，拿到 reservePrice、sellerId、整个拍卖的最高出价和出价次数
$sql = "
SELECT 
    i.itemId,
    i.title,
    i.endDate,
    i.status,
    i.finalPrice,
    i.winnerId,
    i.reservePrice,  -- MOD
    i.sellerId,      -- MOD
    MAX(b.bidAmount) AS my_max_bid,
    (SELECT COALESCE(MAX(b2.bidAmount), i.startPrice)
       FROM bid b2
       WHERE b2.itemId = i.itemId) AS highest_bid,  -- MOD
    (SELECT COUNT(*)
       FROM bid b3
       WHERE b3.itemId = i.itemId) AS num_bids      -- MOD
FROM bid b
JOIN items i ON b.itemId = i.itemId
WHERE b.buyerId = ?
GROUP BY i.itemId
ORDER BY i.endDate DESC
";
// MOD end

$stmt = mysqli_prepare($connection, $sql);
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$now = new DateTime();

$update_sql  = "UPDATE items SET status = 'closed' WHERE itemId = ? AND status <> 'closed'";
$update_stmt = mysqli_prepare($connection, $update_sql);

$fb_check_sql  = "SELECT 1 FROM feedback WHERE itemId = ? AND buyerId = ? LIMIT 1";
$fb_check_stmt = mysqli_prepare($connection, $fb_check_sql);

?>

<div class="container">
  <h2 class="my-3">My bids</h2>

  <?php if (mysqli_num_rows($result) === 0): ?>
    <p>You have not placed any bids yet.</p>
  <?php else: ?>
    <table class="table table-striped">
      <thead>
        <tr>
          <th>Auction</th>
          <th>End time</th>
          <th>My max bid (£)</th>
          <th>Final price (£)</th>
          <th>Status</th>
          <th>Feedback</th>
        </tr>
      </thead>
      <tbody>
      <?php while ($row = mysqli_fetch_assoc($result)):

        $end = new DateTime($row['endDate']);
        $hasEndedByTime = ($end <= $now);

        if ($hasEndedByTime && $row['status'] !== 'closed' && $update_stmt) {
            mysqli_stmt_bind_param($update_stmt, 'i', $row['itemId']);
            mysqli_stmt_execute($update_stmt);

            $row['status'] = 'closed';
        }

        $isEnded = $hasEndedByTime || $row['status'] === 'closed';

        $won = ($isEnded && (int)$row['winnerId'] === $userId);

        // MOD: 取出当前拍卖的 reservePrice / highest_bid / num_bids
        $reservePrice = isset($row['reservePrice']) ? (int)$row['reservePrice'] : 0;
        $highestBid   = isset($row['highest_bid'])   ? (float)$row['highest_bid']   : 0.0;
        $numBids      = isset($row['num_bids'])      ? (int)$row['num_bids']        : 0;

        // 拍卖结束 & 有保留价 & 有出价 & 最高出价仍低于保留价 → 因保留价未达成而流拍
        $unsoldByReserve = (
            $isEnded &&
            $reservePrice > 0 &&
            $numBids > 0 &&
            $highestBid < $reservePrice
        );
        // MOD end
      ?>
        <tr>
          <td>
            <a href="listing.php?item_id=<?php echo $row['itemId']; ?>">
              <?php echo htmlspecialchars($row['title']); ?>
            </a>
          </td>
          <td><?php echo htmlspecialchars($row['endDate']); ?></td>
          <td><?php echo number_format($row['my_max_bid'], 2); ?></td>
          <td>
            <?php 
              if ($row['finalPrice'] !== null) {
                  echo number_format($row['finalPrice'], 2);
              } else {
                  echo '-';
              }
            ?>
          </td>
          <td>
            <?php
              if (!$isEnded) {
                  echo '<span class="badge badge-info">Ongoing</span>';
              } else {
                  if ($won) {
                      echo '<span class="badge badge-success">Won</span>';
                  } else {
                      // MOD: 如果是因为未达到保留价导致没成交，给出单独状态
                      if ($unsoldByReserve) {
                          echo '<span class="badge badge-warning">Not met reserve price</span>';
                      } else {
                          echo '<span class="badge badge-secondary">Lost</span>';
                      }
                      // MOD end
                  }
              }
            ?>
          </td>

          <td>
            <?php
              if ($won) {
                  if ($fb_check_stmt) {
                      mysqli_stmt_bind_param($fb_check_stmt, 'ii', $row['itemId'], $userId);
                      mysqli_stmt_execute($fb_check_stmt);
                      $res_fb = mysqli_stmt_get_result($fb_check_stmt);
                      $hasFb  = mysqli_fetch_assoc($res_fb) ? true : false;
                  } else {
                      $hasFb = false;
                  }

                  if ($hasFb) {
                      echo '<span class="text-muted">Given</span>';
                  } else {
                      echo '<a class="btn btn-sm btn-outline-primary" href="leave_feedback.php?item_id='
                           . (int)$row['itemId'] . '&role=buyer">Leave feedback</a>';
                  }
              } else {
                  echo '-';
              }
            ?>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php include_once 'footer.php'; ?>
