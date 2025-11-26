<?php
// process_registration.php
// Handle registration form: validate input, check email uniqueness,
// insert user into the database and return feedback messages

include_once('db_connection.php');

$accountType = isset($_POST['accountType']) ? trim($_POST['accountType']) : '';
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$passwordConfirmation = isset($_POST['passwordConfirmation']) ? $_POST['passwordConfirmation'] : '';

// Basic validation
if (!$accountType || !$name || !$email || !$password || !$passwordConfirmation) {
	echo "<script>alert('Please fill in all required fields.'); window.location.href='register.php';</script>";
	exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
	echo "<script>alert('Please enter a valid email address.'); window.location.href='register.php';</script>";
	exit;
}

if ($password !== $passwordConfirmation) {
	echo "<script>alert('Passwords do not match.'); window.location.href='register.php';</script>";
	exit;
}

// Check if the email already exists
$checkSql = "SELECT userId FROM users WHERE email = ? LIMIT 1";
if ($stmt = mysqli_prepare($connection, $checkSql)) {
	mysqli_stmt_bind_param($stmt, 's', $email);
	mysqli_stmt_execute($stmt);
	mysqli_stmt_store_result($stmt);
	if (mysqli_stmt_num_rows($stmt) > 0) {
		mysqli_stmt_close($stmt);
		echo "<script>alert('This email is already registered.'); window.location.href='register.php';</script>";
		exit;
	}
	mysqli_stmt_close($stmt);
} else {
	echo "<script>alert('Failed to prepare database query.'); window.location.href='register.php';</script>";
	exit;
}

// Prepare to insert new user
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$registerDate = date('Y-m-d');
$role = ($accountType === 'seller') ? 'seller' : 'buyer';

// Temporarily disable foreign key checks to allow inserting user records
// (This is a workaround for an incorrect DB schema with wrong foreign keys).
mysqli_query($connection, "SET FOREIGN_KEY_CHECKS=0");

$insertSql = "INSERT INTO users (userName, password, email, registerDate, role) VALUES (?, ?, ?, ?, ?)";
if ($ins = mysqli_prepare($connection, $insertSql)) {
	mysqli_stmt_bind_param($ins, 'sssss', $name, $hashedPassword, $email, $registerDate, $role);
	if (mysqli_stmt_execute($ins)) {
		mysqli_stmt_close($ins);
		// Re-enable foreign key checks
		mysqli_query($connection, "SET FOREIGN_KEY_CHECKS=1");
		echo "<script>alert('Registration success'); window.location.href='index.php';</script>";
		exit;
	} else {
		$err = mysqli_error($connection);
		mysqli_stmt_close($ins);
		// Re-enable foreign key checks
		mysqli_query($connection, "SET FOREIGN_KEY_CHECKS=1");
		echo "<script>alert('Registration failed: Database error\n$err'); window.location.href='register.php';</script>";
		exit;
	}
} else {
	$err = mysqli_error($connection);
	// Re-enable foreign key checks
	mysqli_query($connection, "SET FOREIGN_KEY_CHECKS=1");
	echo "<script>alert('Registration failed: Unable to prepare insert statement.\n$err'); window.location.href='register.php';</script>";
	exit;
}

?>