<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['cart_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$user_id = $_SESSION['user_id'];
$cart_id = $_POST['cart_id'];

// Check if the user is authorized to cancel this order
$stmt = $conn->prepare("SELECT id FROM cart WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $cart_id, $user_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Order not found or unauthorized']);
    exit();
}

// Update the pickup_status to 'Cancelled'
$stmt = $conn->prepare("UPDATE cart SET pickup_status = 'Cancelled' WHERE id = ?");
$stmt->bind_param("i", $cart_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to cancel order']);
}

$stmt->close();
$conn->close();
?>