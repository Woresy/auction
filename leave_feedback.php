<?php
require_once 'db_connection.php';
include_once 'header.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id']) || ($_SESSION['account_type'] ?? '') !== 'buyer') {
    echo "<div class='container my-4'>
            <div class='alert alert-warning'>
              You must be logged in as a buyer to leave feedback.
              <a href='#' data-toggle='modal' data-target='#loginModal'>Sign in</a>
            </div>
          </div>";
    include_once 'footer.php';
    exit;
}

$currentBuyerId = (int)$_SESSION['user_id'];

$itemId = isset($_GET['item_id']) ? (int)$_GET['item_id'] : (int)($_POST['item_id'] ?? 0);
if ($itemId <= 0) {
    echo "<div class='container my-4'><div class='alert alert-danger'>Invalid feedback request.</div></div>";
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
        us.userName AS sellerName
    FROM items i
    LEFT JOIN users us ON i.sellerId = us.userId
    WHERE i.itemId = ?
";
$stmt_item = mysqli_prepare($connection, $sql_item);
mysqli_stmt_bind_param($stmt_item, 'i', $itemId);
mysqli_stmt_execute($stmt_item);
$res_item = mysqli_stmt_get_result($stmt_item);
$item     = mysqli_fetch_assoc($res_item);

if (!$item) {
    echo "<div class='container my-4'><div class='alert alert-danger'>Item not found.</div></div>";
    include_once 'footer.php';
    exit;
}

$sellerId   = (int)$item['sellerId'];
$winnerId   = (int)$item['winnerId'];
$sellerName = $item['sellerName'] ?? 'Seller';

if ($item['status'] !== 'closed' || $winnerId !== $currentBuyerId) {
    echo "<div class='container my-4'><div class='alert alert-danger'>
            You can only leave feedback for auctions you have won and that have finished.
          </div></div>";
    include_once 'footer.php';
    exit;
}

$sql_check = "SELECT 1 FROM feedback WHERE itemId = ? AND buyerId = ? LIMIT 1";
$stmt_check = mysqli_prepare($connection, $sql_check);
mysqli_stmt_bind_param($stmt_check, 'ii', $itemId, $currentBuyerId);
mysqli_stmt_execute($stmt_check);
$res_check = mysqli_stmt_get_result($stmt_check);
$alreadyGiven = mysqli_fetch_assoc($res_check) ? true : false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($alreadyGiven) {
        echo "<div class='container my-4'><div class='alert alert-info'>
                You have already left feedback for this auction.
              </div></div>";
        include_once 'footer.php';
        exit;
    }

    $rating  = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $comment = trim($_POST['comment'] ?? '');
    if (strlen($comment) > 200) {
        $comment = substr($comment, 0, 200);
    }

    if ($rating < 1 || $rating > 5) {
        echo "<div class='container my-4'><div class='alert alert-danger'>
                Please select a star rating between 1 and 5.
              </div></div>";
        include_once 'footer.php';
        exit;
    }

    $sql_ins = "
        INSERT INTO feedback (itemId, buyerId, sellerId, rating, comment)
        VALUES (?, ?, ?, ?, ?)
    ";
    $stmt_ins = mysqli_prepare($connection, $sql_ins);
    mysqli_stmt_bind_param($stmt_ins, 'iiiis', $itemId, $currentBuyerId, $sellerId, $rating, $comment);
    mysqli_stmt_execute($stmt_ins);

    header("Location: mybids.php?feedback=success");
    exit;
}
?>

<div class="container my-5">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="mb-4">
        <h2>Congratulations!</h2>
        <p class="lead">
          You successfully bought <strong><?php echo htmlspecialchars($item['title']); ?></strong>.
        </p>
        <h5 class="mt-4">Please rate your seller:</h5>
      </div>

      <div class="card shadow-sm">
        <div class="card-body">

          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <div class="text-muted small">Seller</div>
              <div class="h5 mb-0">
                <a href="profile.php?user_id=<?php echo (int)$sellerId; ?>">
                  <?php echo htmlspecialchars($sellerName); ?>
                </a>
              </div>
            </div>

            <div class="text-right">
              <div class="text-muted small">Stars</div>
              <div class="star-rating h3 mb-0">
                <span class="star" data-value="1">&#9733;</span>
                <span class="star" data-value="2">&#9733;</span>
                <span class="star" data-value="3">&#9733;</span>
                <span class="star" data-value="4">&#9733;</span>
                <span class="star" data-value="5">&#9733;</span>
              </div>
            </div>
          </div>

          <?php if ($alreadyGiven): ?>
            <div class="alert alert-info mt-3 mb-0">
              You have already left feedback for this auction.
            </div>
          <?php else: ?>
            <form method="POST" action="leave_feedback.php" id="feedback-form" class="mt-3">
              <input type="hidden" name="item_id" value="<?php echo (int)$itemId; ?>">
              <input type="hidden" name="rating" id="rating-input" value="">

              <div class="form-group">
                <label for="comment" class="font-weight-bold">Comment:</label>
                <textarea 
                  class="form-control" 
                  id="comment" 
                  name="comment" 
                  rows="4" 
                  maxlength="200"
                  placeholder="Share your experience with this seller (optional)."></textarea>
                <div class="text-right text-muted small mt-1">
                  <span id="char-remaining">200</span> characters remaining
                </div>
              </div>

              <button type="submit" class="btn btn-primary">
                Submit
              </button>
              <a href="mybids.php" class="btn btn-secondary ml-2">
                Cancel
              </a>
            </form>
          <?php endif; ?>

        </div>
      </div>
    </div>
  </div>
</div>

<style>
  .star-rating .star {
    cursor: pointer;
    color: #ddd;
    transition: color 0.15s ease-in-out, transform 0.1s ease-in-out;
  }
  .star-rating .star.selected,
  .star-rating .star.hover {
    color: #ffc107; 
    transform: scale(1.05);
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var stars       = document.querySelectorAll('.star-rating .star');
  var ratingInput = document.getElementById('rating-input');

  function setStars(value) {
    stars.forEach(function (star) {
      var v = parseInt(star.getAttribute('data-value'), 10);
      if (v <= value) {
        star.classList.add('selected');
      } else {
        star.classList.remove('selected');
      }
    });
  }

  stars.forEach(function (star) {
    star.addEventListener('click', function () {
      var val = parseInt(this.getAttribute('data-value'), 10);
      ratingInput.value = val;
      setStars(val);
    });

    star.addEventListener('mouseenter', function () {
      var val = parseInt(this.getAttribute('data-value'), 10);
      stars.forEach(function (s) {
        var v = parseInt(s.getAttribute('data-value'), 10);
        if (v <= val) {
          s.classList.add('hover');
        } else {
          s.classList.remove('hover');
        }
      });
    });

    star.addEventListener('mouseleave', function () {
      stars.forEach(function (s) {
        s.classList.remove('hover');
      });
    });
  });

  var form = document.getElementById('feedback-form');
  if (form) {
    form.addEventListener('submit', function (e) {
      if (!ratingInput.value) {
        e.preventDefault();
        alert('Please select a star rating before submitting.');
      }
    });
  }

  var comment      = document.getElementById('comment');
  var remainingDom = document.getElementById('char-remaining');
  if (comment && remainingDom) {
    var maxLen = parseInt(comment.getAttribute('maxlength'), 10) || 200;
    function updateRemaining() {
      var len = comment.value.length;
      var left = maxLen - len;
      remainingDom.textContent = left;
    }
    comment.addEventListener('input', updateRemaining);
    updateRemaining();
  }
});
</script>

<?php include_once 'footer.php'; ?>
