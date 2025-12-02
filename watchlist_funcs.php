<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: text/plain; charset=utf-8');

if (!isset($_POST['functionname']) || !isset($_POST['arguments'])) {
  echo 'error';
  exit;
}

$fn = $_POST['functionname'];
$args = $_POST['arguments'];

// Ensure user is logged in
if (empty($_SESSION['user_id'])) {
  echo 'not_logged_in';
  exit;
}

$userId = intval($_SESSION['user_id']);
// Only buyers may use the watchlist
if (empty($_SESSION['account_type']) || $_SESSION['account_type'] !== 'buyer') {
  echo 'forbidden';
  exit;
}

// File-based watchlist directory
$watchDir = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'watchlists' . DIRECTORY_SEPARATOR;
if (!is_dir($watchDir)) mkdir($watchDir, 0755, true);

function watchlist_file_for($uid) {
  global $watchDir;
  return $watchDir . intval($uid) . '.json';
}

function read_watchlist($uid) {
  $f = watchlist_file_for($uid);
  if (!file_exists($f)) return [];
  $json = file_get_contents($f);
  $arr = json_decode($json, true);
  if (!is_array($arr)) return [];
  // ensure ints
  return array_map('intval', $arr);
}

function write_watchlist($uid, $arr) {
  $f = watchlist_file_for($uid);
  $arr = array_values(array_unique(array_map('intval', $arr)));
  return (bool)file_put_contents($f, json_encode($arr));
}

if ($fn === 'add_to_watchlist') {
  $itemId = intval(is_array($args) ? $args[0] : $args);
  if ($itemId <= 0) { echo 'error'; exit; }

  $list = read_watchlist($userId);
  if (!in_array($itemId, $list)) {
    $list[] = $itemId;
  }
  $ok = write_watchlist($userId, $list);
  echo $ok ? 'success' : 'error';
  exit;
}

else if ($fn === 'remove_from_watchlist') {
  $itemId = intval(is_array($args) ? $args[0] : $args);
  if ($itemId <= 0) { echo 'error'; exit; }

  $list = read_watchlist($userId);
  $list = array_filter($list, function($v) use ($itemId) { return intval($v) !== $itemId; });
  $ok = write_watchlist($userId, $list);
  echo $ok ? 'success' : 'error';
  exit;
}

echo 'error';
exit;

?>