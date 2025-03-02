<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'errors' => ['general' => 'Unauthorized access']]);
    exit();
}

$user_id = $_SESSION['user_id'];
$phone = trim($_POST['phone']);
$oldPassword = trim($_POST['oldPassword']);
$newPassword = trim($_POST['newPassword']);

$errors = [];

// Validate phone number
if (!preg_match('/^[6789]\d{9}$/', $phone)) {
    $errors['phone'] = 'Invalid phone number';
}

// Fetch current password from the database
$stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error); // Debugging
    echo json_encode(['success' => false, 'errors' => ['general' => 'Database error']]);
    exit();
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($currentPassword);
$stmt->fetch();
$stmt->close();

// Validate old password
if (!password_verify($oldPassword, $currentPassword)) {
    $errors['oldPassword'] = 'Incorrect old password';
}

// Validate new password
if (strlen($newPassword) < 8) {
    $errors['newPassword'] = 'Password must be at least 8 characters';
}

if (empty($errors)) {
    // Update phone and password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET phone = ?, password = ? WHERE user_id = ?");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error); // Debugging
        echo json_encode(['success' => false, 'errors' => ['general' => 'Database error']]);
        exit();
    }

    $stmt->bind_param("ssi", $phone, $hashedPassword, $user_id);
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error); // Debugging
        echo json_encode(['success' => false, 'errors' => ['general' => 'Database error']]);
        exit();
    }

    $stmt->close();
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'errors' => $errors]);
}
?>