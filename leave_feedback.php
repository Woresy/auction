<?php

require_once 'db_connection.php';
include_once 'header.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header("Location: index.php?login_error=auth");
    exit;
}

$currentUserId = (int)$_SESSION['user_id'];

$itemId = isset($_GET['item_id'])
    ? (int)$_GET['item_id']
    : (int)($_POST['item_id'] ?? 0);

if ($itemId <= 0) {
    echo '<div class="container my-3"><div class="alert alert-danger">Invalid feedback request.</div></div>';
    include_once 'footer.php';
    exit;
}

$sql_item = "
    SELECT 
        i.itemId,
        i.title,
        i.sellerId,
        i.winnerId,
        i.status,
        us.userName AS sellerName,
        uw.userName AS winnerName
    FROM items i
    LEFT JOIN users us ON i.sellerId = us.userId
    LEFT JOIN users uw ON i.winnerId = uw.userId
    WHERE i.itemId = ?
";
$stmt_item = mysqli_prepare($connection, $sql_item);
mysqli_stmt_bind_param($stmt_item, 'i', $itemId);
mysqli_stmt_execute($stmt_item);
$res_item = mysqli_stmt_get_result($stmt_item);
$item     = mysqli_fetch_assoc($res_item);

if (!$item) {
    echo '<div class="container my-3"><div class="alert alert-danger">Item not found.</div></div>';
    include_once 'footer.php';
    exit;
}

if ($item['status'] !== 'closed') {
    echo '<div class="container my-3"><div class="alert alert-warning">You can only leave feedback for finished auctions.</div></div>';
    include_once 'footer.php';
    exit;
}

$sellerId   = (int)$item['sellerId'];
$winnerId   = (int)$item['winnerId'];
$sellerName = $item['sellerName'] ?? 'Seller';

if ($winnerId <= 0) {
    echo '<div class="container my-3"><div class="alert alert-warning">This auction has no winner yet, feedback is not available.</div></div>';
    include_once 'footer.php';
    exit;
}

if ($currentUserId !== $winnerId) {
    echo '<div class="container my-3"><div class="alert alert-danger">Only the winning buyer can leave feedback for this seller.</div></div>';
    include_once 'footer.php';
    exit;
}

$sql_check = "
    SELECT 1
    FROM feedback
    WHERE itemId = ? AND buyerId = ? AND sellerId = ?
    LIMIT 1
";
$stmt_check = mysqli_prepare($connection, $sql_check);
mysqli_stmt_bind_param($stmt_check, 'iii', $itemId, $currentUserId, $sellerId);
mysqli_stmt_execute($stmt_check);
$res_check     = mysqli_stmt_get_result($stmt_check);
$alreadyGiven  = mysqli_fetch_assoc($res_check) ? true : false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($alreadyGiven) {
        echo '<div class="container my-3"><div class="alert alert-info">You have already left feedback for this seller on this auction.</div></div>';
        include_once 'footer.php';
        exit;
    }

    $rating  = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $comment = trim($_POST['comment'] ?? '');

    if ($rating < 1 || $rating > 5) {
        echo '<div class="container my-3"><div class="alert alert-danger">Rating must be between 1 and 5.</div></div>';
        include_once 'footer.php';
        exit;
    }

    $sql_ins = "
        INSERT INTO feedback (itemId, buyerId, sellerId, rating, comment)
        VALUES (?, ?, ?, ?, ?)
    ";
    $stmt_ins = mysqli_prepare($connection, $sql_ins);
    mysqli_stmt_bind_param(
        $stmt_ins,
        'iiiis',
        $itemId,
        $currentUserId,  
        $sellerId,       
        $rating,
        $comment
    );
    mysqli_stmt_execute($stmt_ins);

    header("Location: mybids.php?feedback=success");
    exit;
}

?>

<div class="container my-4">
  <h2>Leave feedback</h2>
  <p>
    Auction: <strong><?php echo htmlspecialchars($item['title']); ?></strong><br>
    You are leaving feedback for:
    <strong><?php echo htmlspecialchars($sellerName); ?></strong>
    <span class="text-muted">(seller)</span>
  </p>

  <?php if ($alreadyGiven): ?>
    <div class="alert alert-info">
      You have already left feedback for this seller on this auction.
    </div>
    <a href="mybids.php" class="btn btn-secondary mt-2">Back to my bids</a>
  <?php else: ?>
    <form method="POST" action="leave_feedback.php">
      <input type="hidden" name="item_id" value="<?php echo (int)$itemId; ?>">

      <div class="form-group">
        <label for="rating">Rating (1–5)</label>
        <select class="form-control" id="rating" name="rating" required>
          <option value="">Please choose…</option>
          <option value="5">5 - Excellent</option>
          <option value="4">4 - Good</option>
          <option value="3">3 - OK</option>
          <option value="2">2 - Poor</option>
          <option value="1">1 - Terrible</option>
        </select>
      </div>

      <div class="form-group">
        <label for="comment">Comment (optional)</label>
        <textarea class="form-control" id="comment" name="comment" rows="3" maxlength="255"></textarea>
      </div>

      <button type="submit" class="btn btn-primary">Submit feedback</button>
      <a href="mybids.php" class="btn btn-secondary ml-2">Cancel</a>
    </form>
  <?php endif; ?>
</div>

<?php include_once 'footer.php'; ?>
