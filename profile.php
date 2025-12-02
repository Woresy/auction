<?php include_once("header.php");
require_once("db_connection.php");

// Require logged-in user
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
  echo "<div class='container mt-5'><div class='alert alert-warning'>You must be logged in to view your profile. <a href='#' data-toggle='modal' data-target='#loginModal'>Sign in</a></div></div>";
  include_once("footer.php");
  exit();
}

$userId = null;
if (isset($_SESSION['userId'])) {
  $userId = intval($_SESSION['userId']);
}
else if (isset($_SESSION['username'])) {
  $uname = mysqli_real_escape_string($connection, $_SESSION['username']);
  $sql = "SELECT userId FROM users WHERE userName = '$uname' OR email = '$uname' LIMIT 1";
  $res = mysqli_query($connection, $sql);
  if ($res && mysqli_num_rows($res) > 0) {
    $row = mysqli_fetch_assoc($res);
    $userId = intval($row['userId']);
  }
}

if (!$userId) {
  echo "<div class='container mt-5'><div class='alert alert-danger'>Unable to determine user identity.</div></div>";
  include_once("footer.php");
  exit();
}

$msg = '';
// Handle avatar upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
  $file = $_FILES['avatar'];
  if ($file['error'] === UPLOAD_ERR_OK) {
    $allowed = ['image/jpeg' => '.jpg', 'image/png' => '.png', 'image/gif' => '.gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!array_key_exists($mime, $allowed)) {
      $msg = "Invalid file type. Allowed: JPG, PNG, GIF.";
    } else if ($file['size'] > 2 * 1024 * 1024) {
      $msg = "File too large. Max 2MB.";
    } else {
      $ext = $allowed[$mime];
      $destDir = __DIR__ . DIRECTORY_SEPARATOR . 'avatars' . DIRECTORY_SEPARATOR;
      if (!is_dir($destDir)) mkdir($destDir, 0755, true);
      $dest = $destDir . $userId . $ext;
      // remove any existing avatar with other extensions
      foreach ($allowed as $e) {
        $old = $destDir . $userId . $e;
        if (file_exists($old) && $old !== $dest) @unlink($old);
      }
      if (move_uploaded_file($file['tmp_name'], $dest)) {
        $msg = "Avatar uploaded successfully.";
      } else {
        $msg = "Failed to move uploaded file.";
      }
    }
  } else {
    $msg = "Upload error (code: " . $file['error'] . ").";
  }
}

// Fetch user info
$safeId = intval($userId);
$sql = "SELECT userId, userName, email, role FROM users WHERE userId = $safeId LIMIT 1";
$res = mysqli_query($connection, $sql);
if (!$res || mysqli_num_rows($res) == 0) {
  echo "<div class='container mt-5'><div class='alert alert-danger'>User not found.</div></div>";
  include_once("footer.php");
  exit();
}
$user = mysqli_fetch_assoc($res);

// Determine avatar path if exists
$avatarUrl = null;
$publicDir = __DIR__ . DIRECTORY_SEPARATOR . 'avatars' . DIRECTORY_SEPARATOR;
$publicPathWeb = 'avatars/';
$exts = ['.jpg', '.png', '.gif'];
foreach ($exts as $e) {
  if (file_exists($publicDir . $userId . $e)) {
    $avatarUrl = $publicPathWeb . $userId . $e;
    break;
  }
}

// If seller, count auctions
$auctionCount = null;
if (isset($user['role']) && strtolower($user['role']) === 'seller') {
  $q = "SELECT COUNT(*) AS cnt FROM items WHERE sellerId = $safeId";
  $r = mysqli_query($connection, $q);
  if ($r) {
    $row = mysqli_fetch_assoc($r);
    $auctionCount = intval($row['cnt']);
  }
}

?>
<div class="container mt-5">
  <div class="row">
    <div class="col-md-4">
      <div class="card">
        <div class="card-body text-center">
          <?php if ($avatarUrl): ?>
            <img src="<?php echo htmlspecialchars($avatarUrl); ?>" class="img-fluid rounded-circle mb-3" style="max-width:160px;" alt="Avatar">
          <?php else: ?>
            <img src="https://via.placeholder.com/160?text=Avatar" class="img-fluid rounded-circle mb-3" alt="No avatar">
          <?php endif; ?>
          <h5 class="card-title"><?php echo htmlspecialchars($user['userName']); ?></h5>
          <p class="text-muted mb-1"><?php echo htmlspecialchars($user['email']); ?></p>
          <p class="small text-secondary">Role: <?php echo htmlspecialchars($user['role']); ?></p>
          <?php if ($auctionCount !== null): ?>
            <p class="mt-2"><strong>Auctions:</strong> <?php echo $auctionCount; ?></p>
          <?php endif; ?>
        </div>
      </div>
      <div class="card mt-3">
        <div class="card-body">
          <h6>Upload avatar</h6>
          <?php if ($msg): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($msg); ?></div>
          <?php endif; ?>
          <form method="post" enctype="multipart/form-data">
            <div class="form-group">
              <input type="file" name="avatar" accept="image/*" class="form-control-file">
            </div>
            <button class="btn btn-primary" type="submit">Upload</button>
          </form>
        </div>
      </div>
    </div>
    <div class="col-md-8">
      <div class="card">
        <div class="card-body">
          <h4>Profile Details</h4>
          <dl class="row">
            <dt class="col-sm-3">Username</dt>
            <dd class="col-sm-9"><?php echo htmlspecialchars($user['userName']); ?></dd>

            <dt class="col-sm-3">Email</dt>
            <dd class="col-sm-9"><?php echo htmlspecialchars($user['email']); ?></dd>

            <dt class="col-sm-3">Role</dt>
            <dd class="col-sm-9"><?php echo htmlspecialchars($user['role']); ?></dd>
          </dl>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include_once("footer.php");
