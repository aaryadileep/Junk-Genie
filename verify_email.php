<?php
session_start();
require 'connect.php'; // Include the MySQLi database connection file

if (!isset($_GET['token'])) {
    header("Location: login.php");
    exit();
}

$token = $_GET['token'];

// Verify the token
$stmt = $conn->prepare("SELECT user_id FROM users WHERE email_verification_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    // Mark the email as verified
    $stmt = $conn->prepare("UPDATE users SET is_verified = 'Yes', email_verification_token = NULL WHERE email_verification_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();

    $_SESSION['message'] = "Email verified successfully. You can now login.";
} else {
    $_SESSION['error'] = "Invalid verification token.";
}

header("Location: login.php");
exit();
?>