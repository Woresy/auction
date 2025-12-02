<?php
include_once("header.php");
require_once("db_connection.php");
require_once("utilities.php");

//--------------------------------------
// Step 0: Session & Authentication
//--------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo "<div class='container mt-4'>
            <div class='alert alert-warning'>Please log in to see recommendations.</div>
          </div>";
    include_once("footer.php");
    exit();
}

$buyer_id = intval($_SESSION['user_id']);
$account_type = $_SESSION['account_type'] ?? null;

if ($account_type !== 'buyer') {
    echo "<div class='container mt-4'>
            <div class='alert alert-info'>Only buyer accounts receive recommendations.</div>
          </div>";
    include_once("footer.php");
    exit();
}
?>

<div class="container">
<h2 class="my-3">Recommendations for you</h2>

<?php
//--------------------------------------
// Step 1: 当前用户 bid 过哪些 item
//--------------------------------------
$my_items_sql = "
    SELECT DISTINCT itemId
    FROM bid
    WHERE buyerId = $buyer_id
";
$my_items_res = mysqli_query($connection, $my_items_sql);

$my_item_ids = [];
while ($row = mysqli_fetch_assoc($my_items_res)) {
    $my_item_ids[] = (int)$row['itemId'];
}

if (empty($my_item_ids)) {
    echo "<div class='alert alert-info'>
            You have not placed any bids yet, so we cannot compute collaborative recommendations.
          </div>";
    include_once("footer.php");
    exit();
}

$my_item_list = implode(',', $my_item_ids);

//--------------------------------------
// Step 2: 加权用户协同过滤 (只负责算出 itemId 和排序)
//--------------------------------------
$cf_sql = "
    SELECT 
        i.itemId,
        SUM(su.overlap_count) AS sim_score
    FROM (
        SELECT 
            b2.buyerId AS userId,
            COUNT(DISTINCT b2.itemId) AS overlap_count
        FROM bid b1
        JOIN bid b2
          ON b1.itemId = b2.itemId
         AND b2.buyerId <> b1.buyerId
        WHERE b1.buyerId = $buyer_id
        GROUP BY b2.buyerId
    ) AS su
    JOIN bid b3
      ON b3.buyerId = su.userId
    JOIN items i
      ON i.itemId = b3.itemId
    WHERE i.status = 'active'
      AND i.sellerId <> $buyer_id
      AND i.itemId NOT IN ($my_item_list)
    GROUP BY i.itemId
    HAVING sim_score > 0
    ORDER BY sim_score DESC, i.endDate ASC
    LIMIT 30
";

$cf_res = mysqli_query($connection, $cf_sql);
$has_cf_results = ($cf_res && mysqli_num_rows($cf_res) > 0);

//--------------------------------------
// Step 3: 若 CF 没结果，fallback 按类别推荐（同样只拿 itemId）
//--------------------------------------
if (!$has_cf_results) {

    $cat_sql = "
        SELECT DISTINCT category
        FROM items
        WHERE itemId IN ($my_item_list)
    ";
    $cat_res = mysqli_query($connection, $cat_sql);

    $categories = [];
    while ($row = mysqli_fetch_assoc($cat_res)) {
        $categories[] = "'" . mysqli_real_escape_string($connection, $row['category']) . "'";
    }

    if (empty($categories)) {
        echo "<div class='alert alert-info'>No recommendations available.</div>";
        include_once("footer.php");
        exit();
    }

    $cat_list = implode(",", $categories);

    $fallback_sql = "
        SELECT itemId
        FROM items
        WHERE category IN ($cat_list)
          AND status = 'active'
          AND sellerId <> $buyer_id
          AND itemId NOT IN ($my_item_list)
        ORDER BY endDate ASC
        LIMIT 30
    ";

    $cf_res = mysqli_query($connection, $fallback_sql);
}

//--------------------------------------
// Step 4: 从上面的结果里取出 itemId 列表
//--------------------------------------
if (!$cf_res || mysqli_num_rows($cf_res) == 0) {
    echo "<p class='text-muted'>No recommendations found.</p>";
    include_once("footer.php");
    exit();
}

$recommended_ids = [];
while ($row = mysqli_fetch_assoc($cf_res)) {
    $recommended_ids[] = (int)$row['itemId'];
}

if (empty($recommended_ids)) {
    echo "<p class='text-muted'>No recommendations found.</p>";
    include_once("footer.php");
    exit();
}

$id_list = implode(',', $recommended_ids);

//--------------------------------------
// Step 5: 用「和 browse.php 一模一样」的方式拿价格和 bid 数
//--------------------------------------
// 注意：这里刻意抄的是你 browse.php 的写法：
//   SELECT items.*,
//          (SELECT COUNT(*) FROM bid WHERE bid.itemId = items.itemId) AS bid_count
//   FROM items ...
$items_sql = "
    SELECT 
        items.*,
        (SELECT COUNT(*) FROM bid WHERE bid.itemId = items.itemId) AS bid_count
    FROM items
    WHERE items.itemId IN ($id_list)
    ORDER BY FIELD(items.itemId, $id_list)
";

$items_res = mysqli_query($connection, $items_sql);

//--------------------------------------
// Step 6: 展示结果（和 browse 一样）
//--------------------------------------
if ($has_cf_results) {
    echo "<p class='text-muted'>
            These items are recommended based on bids from users with similar bidding history,
            using a weighted similarity measure.
          </p>";
} else {
    echo "<p class='text-muted'>
            These items are recommended based on categories you have previously bid on.
          </p>";
}

echo '<ul class="list-group">';

while ($item = mysqli_fetch_assoc($items_res)) {

    $item_id      = (int)$item['itemId'];
    $title        = $item['title'];
    $description  = $item['description'];
    $end_time     = new DateTime($item['endDate']);
    $image_path   = $item['imagePath'] ?? null;

    // 👇 完全「跟 browse 走」：
    $current_price = (float)$item['finalPrice'];      // browse 就是用 finalPrice
    $num_bids      = (int)$item['bid_count'];         // 和 browse 的子查询一致

    print_listing_li(
        $item_id,
        $title,
        $description,
        $current_price,
        $num_bids,
        $end_time,
        $image_path
    );
}

echo '</ul>';
?>

</div>

<?php include_once("footer.php")?>
