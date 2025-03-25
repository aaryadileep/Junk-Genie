<?php
session_start();
require_once 'connect.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

// Check if new phone number is provided
if (!isset($_POST['newPhone'])) {
    echo json_encode(['success' => false, 'error' => 'No phone number provided']);
    exit();
}

$user_id = $_SESSION['user_id'];
$new_phone = $_POST['newPhone'];

// Validate phone number
if (!preg_match('/^[0-9]{10}$/', $new_phone)) {
    echo json_encode(['success' => false, 'error' => 'Invalid phone number format']);
    exit();
}

// Update phone number in database
$query = "UPDATE users SET phone = ? WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $new_phone, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database update failed']);
}

$stmt->close();
$conn->close();