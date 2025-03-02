<?php
session_start();
require 'connect.php';

if (isset($_GET['token'])) {
    $token = trim($_GET['token']);
    
    $stmt = $conn->prepare("SELECT id FROM users WHERE verification_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE users SET email_verified = 1, verification_token = NULL WHERE verification_token = ?");
        $stmt->bind_param("s", $token);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Email verified successfully! You can now login.";
        } else {
            $_SESSION['error'] = "Verification failed. Please try again.";
        }
    } else {
        $_SESSION['error'] = "Invalid verification token.";
    }
} else {
    $_SESSION['error'] = "No verification token provided.";
}

header("Location: login.php");
exit();
?>