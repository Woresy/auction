<?php
include_once("header.php");
require("utilities.php");

// Initialize search history storage in session
if (!isset($_SESSION['search_history'])) {
  $_SESSION['search_history'] = array();
}

// Handle clearing and removing entries (no redirect to avoid header issues)
if (isset($_GET['clear_history'])) {
  $_SESSION['search_history'] = array();
}
if (isset($_GET['remove_from_history'])) {
  $idx = intval($_GET['remove_from_history']);
  if (isset($_SESSION['search_history'][$idx])) {
    array_splice($_SESSION['search_history'], $idx, 1);
  }
}

// Add current search to history (only for meaningful searches)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && (isset($_GET['keyword']) || (isset($_GET['cat']) && $_GET['cat'] != 'all'))) {
  $entry_keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
  $entry_cat = isset($_GET['cat']) ? $_GET['cat'] : 'all';

  // Avoid empty/no-op adds: require a keyword or a non-all category
  if ($entry_keyword !== '' || $entry_cat !== 'all') {
    $search_entry = array(
      'keyword' => $entry_keyword,
      'category' => $entry_cat,
      'timestamp' => date('Y-m-d H:i:s'),
      'results_count' => 0
    );

    // Remove any existing identical entry (keyword + category) so it moves to front, avoid duplicates
    $new_key = trim($search_entry['keyword']);
    $new_key_norm = function_exists('mb_strtolower') ? mb_strtolower($new_key) : strtolower($new_key);
    $new_cat = $search_entry['category'];

    if (!empty($_SESSION['search_history'])) {
      foreach ($_SESSION['search_history'] as $k => $existing) {
        $exist_key = isset($existing['keyword']) ? trim($existing['keyword']) : '';
        $exist_key_norm = function_exists('mb_strtolower') ? mb_strtolower($exist_key) : strtolower($exist_key);
        $exist_cat = isset($existing['category']) ? $existing['category'] : 'all';
        if ($exist_key_norm === $new_key_norm && $exist_cat === $new_cat) {
          unset($_SESSION['search_history'][$k]);
        }
      }
      // Reindex array after unsets
      $_SESSION['search_history'] = array_values($_SESSION['search_history']);
    }

    // Prepend new entry
    array_unshift($_SESSION['search_history'], $search_entry);
    // Limit to 10 entries
    if (count($_SESSION['search_history']) > 10) {
      $_SESSION['search_history'] = array_slice($_SESSION['search_history'], 0, 10);
    }
  }
}
?>

<div class="container">

<h2 class="my-3">Browse listings</h2>

<?php if (!empty($_SESSION['search_history'])): ?>
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Recent Searches</h5>
    <a href="browse.php?clear_history=1" class="btn btn-sm btn-outline-danger" onclick="return confirm('Clear all search history?')">Clear All</a>
  </div>
  <div class="card-body">
    <div class="row">
      <?php foreach ($_SESSION['search_history'] as $index => $history): ?>
      <div class="col-md-6 col-lg-4 mb-2">
        <div class="d-flex justify-content-between align-items-center border rounded p-2 bg-light">
          <div>
            <?php if (!empty($history['keyword'])): ?>
              <span class="font-weight-bold">"<?php echo htmlspecialchars($history['keyword']); ?>"</span>
            <?php else: ?>
              <span class="text-muted">(No keyword)</span>
            <?php endif; ?>
            <?php if ($history['category'] != 'all'): ?>
              <span class="text-muted"> in </span>
              <span class="badge badge-info"><?php echo htmlspecialchars($history['category']); ?></span>
            <?php endif; ?>
            <small class="d-block text-muted"><i class="fa fa-clock"></i> <?php echo date('M j, g:i a', strtotime($history['timestamp'])); ?></small>
          </div>
          <div class="d-flex">
            <a href="browse.php?keyword=<?php echo urlencode($history['keyword']); ?>&cat=<?php echo urlencode($history['category']); ?>" class="btn btn-sm btn-outline-primary mr-1" title="Repeat this search"><i class="fa fa-search"></i></a>
            <a href="browse.php?remove_from_history=<?php echo $index; ?>" class="btn btn-sm btn-outline-danger" title="Remove from history" onclick="return confirm('Remove this search from history?')"><i class="fa fa-times"></i></a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<div id="searchSpecs">
<form method="get" action="browse.php">
  <div class="row">
    <div class="col-md-5 pr-0">
      <div class="form-group">
        <label for="keyword" class="sr-only">Search keyword:</label>
      <div class="input-group">
          <div class="input-group-prepend">
            <span class="input-group-text bg-transparent pr-0 text-muted">
              <i class="fa fa-search"></i>
            </span>
          </div>
          <input type="text" class="form-control border-left-0" id="keyword" name="keyword" placeholder="Search for anything" value="<?php echo isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : ''; ?>">
          <?php if (!empty($_SESSION['search_history'])): ?>
          <div class="input-group-append">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">History</button>
            <div class="dropdown-menu dropdown-menu-right">
              <?php foreach ($_SESSION['search_history'] as $h): ?>
                <a class="dropdown-item" href="browse.php?keyword=<?php echo urlencode($h['keyword']); ?>&cat=<?php echo urlencode($h['category']); ?>">
                  <?php if (!empty($h['keyword'])): ?>"<?php echo htmlspecialchars(mb_strimwidth($h['keyword'], 0, 20, '...')); ?>"<?php else: ?>(No keyword)<?php endif; ?>
                  <?php if ($h['category'] != 'all'): ?> <small class="text-muted">[<?php echo htmlspecialchars($h['category']); ?>]</small><?php endif; ?>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="col-md-3 pr-0">
      <div class="form-group">
        <label for="cat" class="sr-only">Search within:</label>
        <select class="form-control" id="cat" name="cat">
          <option value="all" <?php echo (isset($_GET['cat']) && $_GET['cat']==='all') ? 'selected' : ''; ?>>All categories</option>
          <option value="electronics" <?php echo (isset($_GET['cat']) && $_GET['cat']==='electronics') ? 'selected' : ''; ?>>Electronics</option>
          <option value="fashion" <?php echo (isset($_GET['cat']) && $_GET['cat']==='fashion') ? 'selected' : ''; ?>>Fashion & Accessories</option>
          <option value="home" <?php echo (isset($_GET['cat']) && $_GET['cat']==='home') ? 'selected' : ''; ?>>Home & Kitchen</option>
          <option value="sports" <?php echo (isset($_GET['cat']) && $_GET['cat']==='sports') ? 'selected' : ''; ?>>Sports & Outdoors</option>
          <option value="toys" <?php echo (isset($_GET['cat']) && $_GET['cat']==='toys') ? 'selected' : ''; ?>>Toys & Games</option>
          <option value="collectibles" <?php echo (isset($_GET['cat']) && $_GET['cat']==='collectibles') ? 'selected' : ''; ?>>Collectibles & Art</option>
          <option value="books" <?php echo (isset($_GET['cat']) && $_GET['cat']==='books') ? 'selected' : ''; ?>>Books & Media</option>
          <option value="automotive" <?php echo (isset($_GET['cat']) && $_GET['cat']==='automotive') ? 'selected' : ''; ?>>Automotive</option>
          <option value="beauty" <?php echo (isset($_GET['cat']) && $_GET['cat']==='beauty') ? 'selected' : ''; ?>>Beauty & Personal Care</option>
          <option value="other" <?php echo (isset($_GET['cat']) && $_GET['cat']==='other') ? 'selected' : ''; ?>>Other</option>
        </select>
      </div>
    </div>
    <div class="col-md-3 pr-0">
      <div class="form-inline">
        <label class="mx-2" for="order_by">Sort by:</label>
        <select class="form-control" id="order_by" name="order_by">
          <option value="pricelow" <?php echo (isset($_GET['order_by']) && $_GET['order_by']==='pricelow') ? 'selected' : ''; ?>>Price (low to high)</option>
          <option value="pricehigh" <?php echo (isset($_GET['order_by']) && $_GET['order_by']==='pricehigh') ? 'selected' : ''; ?>>Price (high to low)</option>
          <option value="date" <?php echo (isset($_GET['order_by']) && $_GET['order_by']==='date') ? 'selected' : ''; ?>>Soonest expiry</option>
        </select>
      </div>
    </div>
    <div class="col-md-1 px-0">
      <button type="submit" class="btn btn-primary">Search</button>
    </div>
  </div>
</form>
</div> </div>

<?php
  // Retrieve these from the URL
  if (!isset($_GET['keyword'])) {
    $keyword = "";
  }
  else {
    $keyword = $_GET['keyword'];
  }

  if (!isset($_GET['cat'])) {
    $category = "all";
  }
  else {
    $category = $_GET['cat'];
  }
  
  if (!isset($_GET['order_by'])) {
    $ordering = "none";
  }
  else {
    $ordering = $_GET['order_by'];
  }
  
  if (!isset($_GET['page'])) {
    $curr_page = 1;
  }
  else {
    $curr_page = $_GET['page'];
  }

  require_once("db_connection.php");

  // Build parameterized query based on search inputs
  $results_per_page = 10;
  $curr_page = max(1, (int)$curr_page);
  $offset = ($curr_page - 1) * $results_per_page;

  $conditions = array();
  $params = array();
  $types = '';

  if ($keyword !== '') {
    $conditions[] = "(title LIKE ? OR description LIKE ? )";
    $kw = "%" . $keyword . "%";
    $params[] = $kw;
    $params[] = $kw;
    $types .= 'ss';
  }

  if ($category !== '' && $category !== 'all') {
    $conditions[] = "category = ?";
    $params[] = $category;
    $types .= 's';
  }

  $where = '';
  if (count($conditions) > 0) {
    $where = 'WHERE ' . implode(' AND ', $conditions);
  }

  // Determine ordering
  $order_sql = '';
  if ($ordering === 'pricelow') {
    $order_sql = 'ORDER BY finalPrice ASC';
  } elseif ($ordering === 'pricehigh') {
    $order_sql = 'ORDER BY finalPrice DESC';
  } elseif ($ordering === 'date') {
    $order_sql = 'ORDER BY endDate ASC';
  }

  // If ordering by date (Soonest expiry), exclude already-expired auctions
  if ($ordering === 'date') {
    $conditions[] = "endDate > NOW()";
  }

  // Rebuild WHERE clause in case we added the expiry condition
  $where = '';
  if (count($conditions) > 0) {
    $where = 'WHERE ' . implode(' AND ', $conditions);
  }

  $count_sql = "SELECT COUNT(*) AS cnt FROM items $where";
  $stmt = mysqli_prepare($connection, $count_sql);
  if ($stmt === false) {
    $res = mysqli_query($connection, $count_sql);
    $row = mysqli_fetch_assoc($res);
    $num_results = (int)$row['cnt'];
  } else {
    if ($types !== '') {
      $bind_names = array();
      $bind_names[] = $stmt;
      $bind_names[] = $types;
      for ($i = 0; $i < count($params); $i++) {
        $bind_names[] = &$params[$i];
      }
      call_user_func_array('mysqli_stmt_bind_param', $bind_names);
    }
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $num_results);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
  }

  $max_page = max(1, (int)ceil($num_results / $results_per_page));

  // Update most recent search history entry results_count when it matches current search
  if (!empty($_SESSION['search_history'])) {
    $first = &$_SESSION['search_history'][0];
    $kw_trim = trim($keyword);
    if ($first['keyword'] === $kw_trim && $first['category'] === $category) {
      $first['results_count'] = (int)$num_results;
    }
  }


  $select_sql = "SELECT items.*, 
                 (SELECT COUNT(*) FROM bid WHERE bid.itemId = items.itemId) AS bid_count 
                 FROM items $where $order_sql LIMIT ?, ?";
  // ------------------------------------

  $stmt = mysqli_prepare($connection, $select_sql);
  if ($stmt === false) {
    // fallback to simple query (no params)
    $items_result = mysqli_query($connection, $select_sql);
  } else {
    $params2 = $params;
    $types2 = $types . 'ii';
    $params2[] = $offset;
    $params2[] = $results_per_page;

    $bind_names = array();
    $bind_names[] = $stmt;
    $bind_names[] = $types2;
    for ($i = 0; $i < count($params2); $i++) {
      $bind_names[] = &$params2[$i];
    }
    call_user_func_array('mysqli_stmt_bind_param', $bind_names);

    mysqli_stmt_execute($stmt);
    $items_result = mysqli_stmt_get_result($stmt);
    if ($items_result === false) {
      $items_result = array();
    }
  }
?>

<div class="container mt-5">

<ul class="list-group">

<?php
  if ($num_results == 0) {
    echo "<p>No items found.</p>";
  } else {

    while ($row = mysqli_fetch_assoc($items_result)) {

        $item_id = $row['itemId'];
        $title = $row['title'];
        $description = $row['description'];
        $current_price = $row['finalPrice'];
        
        $num_bids = $row['bid_count']; 
        
        $end_date = new DateTime($row['endDate']);
        $image_path = $row['imagePath'];
        $safe_image_path = htmlspecialchars($image_path ?? '');

        print_listing_li(
          $item_id,
          $title,
          $description,
          $current_price,
          $num_bids,
          $end_date,
          $image_path
        );
    }
  }
?>

</ul>

<nav aria-label="Search results pages" class="mt-5">
  <ul class="pagination justify-content-center">
  
<?php
  $querystring = "";
  foreach ($_GET as $key => $value) {
    if ($key != "page") {
      $querystring .= "$key=$value&amp;";
    }
  }
  
  $high_page_boost = max(3 - $curr_page, 0);
  $low_page_boost = max(2 - ($max_page - $curr_page), 0);
  $low_page = max(1, $curr_page - 2 - $low_page_boost);
  $high_page = min($max_page, $curr_page + 2 + $high_page_boost);
  
  if ($curr_page != 1) {
    echo('
    <li class="page-item">
      <a class="page-link" href="browse.php?' . $querystring . 'page=' . ($curr_page - 1) . '" aria-label="Previous">
        <span aria-hidden="true"><i class="fa fa-arrow-left"></i></span>
        <span class="sr-only">Previous</span>
      </a>
    </li>');
  }
    
  for ($i = $low_page; $i <= $high_page; $i++) {
    if ($i == $curr_page) {
      echo('<li class="page-item active">');
    }
    else {
      echo('<li class="page-item">');
    }
    
    echo('
      <a class="page-link" href="browse.php?' . $querystring . 'page=' . $i . '">' . $i . '</a>
    </li>');
  }
  
  if ($curr_page != $max_page) {
    echo('
    <li class="page-item">
      <a class="page-link" href="browse.php?' . $querystring . 'page=' . ($curr_page + 1) . '" aria-label="Next">
        <span aria-hidden="true"><i class="fa fa-arrow-right"></i></span>
        <span class="sr-only">Next</span>
      </a>
    </li>');
  }
?>

  </ul>
</nav>

</div>

<?php include_once("footer.php")?>