<?php
include_once 'header.php';
require_once 'db_connection.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id']) || $_SESSION['account_type'] !== 'seller') {
    echo "<script>alert('You must be logged in as a seller to view your listings.'); window.location.href='index.php';</script>";
    exit;
}

$userId = (int)$_SESSION['user_id'];

$sql = "
SELECT 
    i.itemId,
    i.title,
    i.endDate,
    i.status,
    i.finalPrice,
    i.winnerId,
    i.reservePrice,                                   
    u.userName AS winnerName,
    COALESCE(MAX(b.bidAmount), i.startPrice) AS current_price, 
    COUNT(b.bidId) AS num_bids                                   
FROM items i
LEFT JOIN users u ON i.winnerId = u.userId
LEFT JOIN bid b    ON i.itemId = b.itemId                        
WHERE i.sellerId = ?
GROUP BY i.itemId
ORDER BY i.endDate DESC
";

$stmt = mysqli_prepare($connection, $sql);
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$now = new DateTime();

$update_sql  = "UPDATE items SET status = 'closed' WHERE itemId = ? AND status <> 'closed'";
$update_stmt = mysqli_prepare($connection, $update_sql);

?>

<div class="container">
  <h2 class="my-3">My listings</h2>

  <?php if (mysqli_num_rows($result) === 0): ?>
    <p>You have not listed any items yet.</p>
  <?php else: ?>
    <table class="table table-striped">
      <thead>
        <tr>
          <th>Auction</th>
          <th>End time</th>
          <th>Reserve Price (£)</th>
          <th>Final price (£)</th>
          <th>Winner</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody> 
      <?php while ($row = mysqli_fetch_assoc($result)): 
        $end = new DateTime($row['endDate']);
        $hasEndedByTime = ($end < $now);

        if ($hasEndedByTime && $row['status'] !== 'closed' && $update_stmt) {
            mysqli_stmt_bind_param($update_stmt, 'i', $row['itemId']);
            mysqli_stmt_execute($update_stmt);

            $row['status'] = 'closed';
        }

        $isEnded  = ($row['status'] === 'closed');
        $winnerId = (int)$row['winnerId'];
        $sellerId = $userId;

        $reservePrice  = isset($row['reservePrice']) ? (int)$row['reservePrice'] : 0;
        $currentPrice  = isset($row['current_price']) ? (float)$row['current_price'] : 0.0;
        $numBids       = isset($row['num_bids']) ? (int)$row['num_bids'] : 0;

        $unsoldByReserve = (
            $isEnded &&
            $reservePrice > 0 &&
            $numBids > 0 &&
            $currentPrice < $reservePrice
        );
      ?>
        <tr>
          <td>
            <a href="listing.php?item_id=<?php echo $row['itemId']; ?>">
              <?php echo htmlspecialchars($row['title']); ?>
            </a>
          </td>
          <td><?php echo htmlspecialchars($row['endDate']); ?></td>

          <td>
            <?php 
              if ($reservePrice > 0) {
                  echo number_format($reservePrice, 2);
              } else {
                  echo '-';
              }
            ?>
          </td>

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
                  echo '-';
              } else {
                  if ($winnerId > 0 && $winnerId !== $sellerId) {
                      echo htmlspecialchars($row['winnerName']);
                  } else {
                      echo 'No winner';
                  }
              }
            ?>
          </td>
          <td>
            <?php
              if (!$isEnded) {
                  echo '<span class="badge badge-info">Ongoing</span>';
              } else {
                  if ($unsoldByReserve) {
                      echo '<span class="badge badge-warning">Unsold</span>';
                  } else {
                      echo '<span class="badge badge-secondary">Finished</span>';
                  }
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
