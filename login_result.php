<?php
// login_result.php
// Validate login credentials, set session variables and redirect with alerts

include_once('db_connection.php');
session_start();

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if (!$email || !$password) {
	echo "<script>alert('Please enter email and password.'); window.history.back();</script>";
	exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
	echo "<script>alert('Please enter a valid email address.'); window.history.back();</script>";
	exit;
}

// Prepare and execute query to fetch user by email
$sql = "SELECT userId, userName, password, role FROM users WHERE email = ? LIMIT 1";
if ($stmt = mysqli_prepare($connection, $sql)) {
	mysqli_stmt_bind_param($stmt, 's', $email);
	mysqli_stmt_execute($stmt);
	mysqli_stmt_store_result($stmt);

	if (mysqli_stmt_num_rows($stmt) === 0) {
		mysqli_stmt_close($stmt);
		echo "<script>alert('Login failed: Invalid email or password.'); window.history.back();</script>";
		exit;
	}

	mysqli_stmt_bind_result($stmt, $userId, $userName, $hashedPassword, $role);
	mysqli_stmt_fetch($stmt);
	mysqli_stmt_close($stmt);

	if (password_verify($password, $hashedPassword)) {
		// Successful login
		$_SESSION['logged_in'] = true;
		$_SESSION['username'] = $userName;
		$_SESSION['account_type'] = $role;
		$_SESSION['user_id'] = $userId;

		echo "<script>alert('Login successful'); window.location.href='index.php';</script>";
		exit;
	} else {
		echo "<script>alert('Login failed: Invalid email or password.'); window.history.back();</script>";
		exit;
	}
} else {
	$err = mysqli_error($connection);
	echo "<script>alert('Login failed: DB error.\n$err'); window.history.back();</script>";
	exit;
}

?>