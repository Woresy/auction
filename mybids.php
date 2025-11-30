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

$userId = (int)$_SESSION['user_id'];

$sql = "
SELECT 
    i.itemId,
    i.title,
    i.endDate,
    i.status,
    i.finalPrice,
    i.winnerId,
    MAX(b.bidAmount) AS my_max_bid
FROM bid b
JOIN items i ON b.itemId = i.itemId
WHERE b.buyerId = ?
GROUP BY i.itemId
ORDER BY i.endDate DESC
";

$stmt = mysqli_prepare($connection, $sql);
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$now = new DateTime();
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
        </tr>
      </thead>
      <tbody>
      <?php while ($row = mysqli_fetch_assoc($result)): 
        $end = new DateTime($row['endDate']);
        $isEnded = $end < $now || $row['status'] === 'closed';
        $won = ($isEnded && (int)$row['winnerId'] === $userId);
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
                      echo '<span class="badge badge-secondary">Lost</span>';
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
