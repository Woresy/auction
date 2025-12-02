<?php include_once('header.php');
require_once('db_connection.php');

if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['user_id']) || empty($_SESSION['account_type']) || $_SESSION['account_type'] !== 'buyer') {
  echo "<div class='container mt-5'><div class='alert alert-warning'>You must be logged in as a buyer to view your watchlist.</div></div>";
  include_once('footer.php');
  exit();
}

$userId = intval($_SESSION['user_id']);

$watchDir = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'watchlists' . DIRECTORY_SEPARATOR;
$file = $watchDir . $userId . '.json';
$ids = [];
if (file_exists($file)) {
  $json = file_get_contents($file);
  $decoded = json_decode($json, true);
  if (is_array($decoded)) $ids = array_map('intval', $decoded);
}

?>
<div class='container mt-4'>
  <h2>My Watchlist</h2>

  <?php if (empty($ids)): ?>
    <div class='alert alert-info mt-3'>You are not watching any auctions.</div>
  <?php else:
    // Fetch items by id (ids are integers from user's file)
    $ids_list = implode(',', $ids);
    $sql = "SELECT * FROM items WHERE itemId IN ($ids_list) ORDER BY FIELD(itemId, $ids_list)";
    $res = mysqli_query($connection, $sql);
  ?>
    <ul class='list-group mt-3'>
      <?php while ($row = mysqli_fetch_assoc($res)): ?>
        <?php
          $item_id = $row['itemId'];
          $title = $row['title'];
          $description = $row['description'];
          $current_price = $row['finalPrice'];
          $end_date = new DateTime($row['endDate']);
        ?>
        <li class='list-group-item'>
          <div class='d-flex justify-content-between'>
            <div>
              <a href='listing.php?item_id=<?php echo $item_id; ?>'><strong><?php echo htmlspecialchars($title); ?></strong></a>
              <div class='text-muted small'><?php echo htmlspecialchars(substr($description,0,200)); ?></div>
            </div>
            <div class='text-right'>
              <div>£<?php echo number_format($current_price,2); ?></div>
              <div class='small text-muted'>Ends: <?php echo $end_date->format('j M Y H:i'); ?></div>
              <div class='mt-2'>
                <a class='btn btn-sm btn-outline-secondary' href='listing.php?item_id=<?php echo $item_id; ?>'>View</a>
                <button class='btn btn-sm btn-danger' onclick='removeFromWatchlist(<?php echo $item_id; ?>)'>Remove</button>
              </div>
            </div>
          </div>
        </li>
      <?php endwhile; ?>
    </ul>
  <?php endif; ?>

</div>

<?php include_once('footer.php'); ?>

<script>
function removeFromWatchlist(itemId) {
  $.ajax('watchlist_funcs.php', {
    type: 'POST',
    data: { functionname: 'remove_from_watchlist', arguments: [itemId] },
    success: function (resp) {
      if (resp.trim() === 'success') location.reload();
      else alert('Remove failed');
    },
    error: function () { alert('Error'); }
  });
}
</script>
