<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Employee') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$user_id = $_SESSION['user_id'];
$new_phone = $_POST['phone'];

// Validate phone number
if (empty($new_phone)) {
    echo json_encode(['success' => false, 'message' => 'Phone number is required']);
    exit();
}

// Update phone number in the database
$query = "UPDATE users SET phone = ? WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $new_phone, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error updating phone number']);
}

$stmt->close();
$conn->close();
?>