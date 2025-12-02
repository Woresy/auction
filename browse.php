<?php include_once("header.php")?>
<?php require("utilities.php")?>

<div class="container">

<h2 class="my-3">Browse listings</h2>

<div id="searchSpecs">
<!-- When this form is submitted, this PHP page is what processes it.
     Search/sort specs are passed to this page through parameters in the URL
     (GET method of passing data to a page). -->
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

        
        <!-- <select class="form-control" id="cat">
          <option selected value="all">All categories</option>
          <option value="fill">Fill me in</option>
          <option value="with">with options</option>
          <option value="populated">populated from a database?</option>
        </select> -->
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
</div> <!-- end search specs bar -->


</div>

<?php
  // Retrieve these from the URL
  if (!isset($_GET['keyword'])) {
    // TODO: Define behavior if a keyword has not been specified.
    $keyword = "";
  }
  else {
    $keyword = $_GET['keyword'];
  }

  if (!isset($_GET['cat'])) {
    // TODO: Define behavior if a category has not been specified.
    $category = "all";
  }
  else {
    $category = $_GET['cat'];
  }
  
  if (!isset($_GET['order_by'])) {
    // TODO: Define behavior if an order_by value has not been specified.
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

  /* TODO: Use above values to construct a query. Use this query to 
     retrieve data from the database. (If there is no form data entered,
     decide on appropriate default value/default query to make. */
  
  /* For the purposes of pagination, it would also be helpful to know the
     total number of results that satisfy the above query */

  require_once("db_connection.php");
  require_once("db_connection.php");

  // Build parameterized query based on search inputs (keyword, category), ordering and pagination
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
    // assuming items table has a `category` column; if different, adjust accordingly
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

  // 1) Get total count for pagination
  $count_sql = "SELECT COUNT(*) AS cnt FROM items $where";
  $stmt = mysqli_prepare($connection, $count_sql);
  if ($stmt === false) {
    // fallback: run simple query
    $res = mysqli_query($connection, $count_sql);
    $row = mysqli_fetch_assoc($res);
    $num_results = (int)$row['cnt'];
  } else {
    if ($types !== '') {
      // bind params dynamically
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

  // 2) Retrieve result rows with limit/offset
  $select_sql = "SELECT * FROM items $where $order_sql LIMIT ?, ?";
  $stmt = mysqli_prepare($connection, $select_sql);
  if ($stmt === false) {
    // fallback to simple query (no params) - less secure
    $items_result = mysqli_query($connection, $select_sql);
  } else {
    // bind params: existing $params (if any) + offset + results_per_page
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
    // get result set (requires mysqlnd). If not available, consider binding results manually.
    $items_result = mysqli_stmt_get_result($stmt);
    if ($items_result === false) {
      // fallback: build array by binding columns (simpler approach omitted here)
      $items_result = array();
    }
  }
?>

<div class="container mt-5">

<!-- TODO: If result set is empty, print an informative message. Otherwise... -->

<ul class="list-group">

<!-- TODO: Use a while loop to print a list item for each auction listing
     retrieved from the query -->

<?php
  // Demonstration of what listings will look like using dummy data.
  // $item_id = "87021";
  // $title = "Dummy title";
  // $description = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum eget rutrum ipsum. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Phasellus feugiat, ipsum vel egestas elementum, sem mi vestibulum eros, et facilisis dui nisi eget metus. In non elit felis. Ut lacus sem, pulvinar ultricies pretium sed, viverra ac sapien. Vivamus condimentum aliquam rutrum. Phasellus iaculis faucibus pellentesque. Sed sem urna, maximus vitae cursus id, malesuada nec lectus. Vestibulum scelerisque vulputate elit ut laoreet. Praesent vitae orci sed metus varius posuere sagittis non mi.";
  // $current_price = 30;
  // $num_bids = 1;
  // $end_date = new DateTime('2020-09-16T11:00:00');
  
  // // This uses a function defined in utilities.php
  // print_listing_li($item_id, $title, $description, $current_price, $num_bids, $end_date);
  
  // $item_id = "516";
  // $title = "Different title";
  // $description = "Very short description.";
  // $current_price = 13.50;
  // $num_bids = 3;
  // $end_date = new DateTime('2020-11-02T00:00:00');
  
  // print_listing_li($item_id, $title, $description, $current_price, $num_bids, $end_date);

  if ($num_results == 0) {
    echo "<p>No items found.</p>";
  } else {

    // TODO: Use a while loop to print a list item for each auction listing
    while ($row = mysqli_fetch_assoc($items_result)) {

        $item_id = $row['itemId'];
        $title = $row['title'];
        $description = $row['description'];
        $current_price = $row['finalPrice'];
        $num_bids = 0;   // bidding not included in part 2
        $end_date = new DateTime($row['endDate']);

        $image_path = $row['imagePath'];
        
        // 调试：输出图片路径（确保传入 htmlspecialchars() 的是字符串，避免 PHP 抛出弃用警告）
        $safe_image_path = htmlspecialchars($image_path ?? '');
        echo "<!-- DEBUG: Item $item_id, Image path: $safe_image_path -->";

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

<!-- Pagination for results listings -->
<nav aria-label="Search results pages" class="mt-5">
  <ul class="pagination justify-content-center">
  
<?php

  // Copy any currently-set GET variables to the URL.
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
      // Highlight the link
      echo('
    <li class="page-item active">');
    }
    else {
      // Non-highlighted link
      echo('
    <li class="page-item">');
    }
    
    // Do this in any case
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