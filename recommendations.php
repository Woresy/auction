<?php
include_once("header.php");
require_once("db_connection.php");
require_once("utilities.php");

//--------------------------------------
// Step 0: Session check
//--------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo "<div class='container mt-4'><div class='alert alert-warning'>Please log in to see recommendations.</div></div>";
    include_once("footer.php");
    exit();
}

$buyer_id = intval($_SESSION['user_id']);
$account_type = $_SESSION['account_type'] ?? null;

if ($account_type !== 'buyer') {
    echo "<div class='container mt-4'><div class='alert alert-info'>Only buyers see recommendations.</div></div>";
    include_once("footer.php");
    exit();
}
?>

<div class="container">
<h2 class="my-3">Recommended for you</h2>

<?php
//--------------------------------------
// Step 1: Find items this user bid on
//--------------------------------------
$sql_my_items = "
    SELECT DISTINCT itemId
    FROM bid
    WHERE buyerId = $buyer_id
";

$res_my_items = mysqli_query($connection, $sql_my_items);

$my_items = [];
while ($row = mysqli_fetch_assoc($res_my_items)) {
    $my_items[] = $row['itemId'];
}

if (empty($my_items)) {
    echo "<div class='alert alert-info'>You haven't bid on anything yet. Browse items to get recommendations!</div>";
    include_once("footer.php");
    exit();
}

$my_items_str = implode(",", $my_items);

//--------------------------------------
// Step 2: Find “similar users”
//--------------------------------------
$sql_sim_users = "
    SELECT DISTINCT buyerId
    FROM bid
    WHERE itemId IN ($my_items_str)
      AND buyerId <> $buyer_id
";

$res_sim_users = mysqli_query($connection, $sql_sim_users);

$sim_users = [];
while ($row = mysqli_fetch_assoc($res_sim_users)) {
    $sim_users[] = $row['buyerId'];
}

if (empty($sim_users)) {
    echo "<div class='alert alert-info'>No similar users found yet. Try bidding on more items!</div>";
    include_once("footer.php");
    exit();
}

$sim_users_str = implode(",", $sim_users);

//--------------------------------------
// Step 3: Find items they bid on but I did NOT bid on
//--------------------------------------
$sql_rec_items = "
    SELECT itemId, COUNT(*) AS score
    FROM bid
    WHERE buyerId IN ($sim_users_str)
      AND itemId NOT IN ($my_items_str)
    GROUP BY itemId
    ORDER BY score DESC
";

$res_rec_items = mysqli_query($connection, $sql_rec_items);

$recommended_item_ids = [];
while ($row = mysqli_fetch_assoc($res_rec_items)) {
    $recommended_item_ids[] = $row['itemId'];
}

if (empty($recommended_item_ids)) {
    echo "<div class='alert alert-info'>No new recommendations right now — check back later!</div>";
    include_once("footer.php");
    exit();
}

$recommended_str = implode(",", $recommended_item_ids);

//--------------------------------------
// Step 4: Fetch item details and print using print_listing_li
//--------------------------------------
$sql_items = "
    SELECT *
    FROM items
    WHERE itemId IN ($recommended_str)
";

$res_items = mysqli_query($connection, $sql_items);

echo '<ul class="list-group">';

while ($row = mysqli_fetch_assoc($res_items)) {
    $item_id = $row['itemId'];
    $title = $row['title'];
    $description = $row['description'];
    $current_price = $row['finalPrice'];
    $num_bids = 0; // optional enhancement if needed
    $end_date = new DateTime($row['endDate']);
    $image_path = $row['imagePath'];

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

echo '</ul>';

?>

</div>

<?php include_once("footer.php") ?>
