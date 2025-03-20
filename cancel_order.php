<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['cart_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$user_id = $_SESSION['user_id'];
$cart_id = $_POST['cart_id'];

try {
    // Check if order exists and belongs to user
    $checkStmt = $conn->prepare("SELECT pickup_status FROM cart WHERE id = ? AND user_id = ?");
    $checkStmt->bind_param("ii", $cart_id, $user_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Order not found");
    }

    $order = $result->fetch_assoc();
    if ($order['pickup_status'] === 'Completed' || $order['pickup_status'] === 'Cancelled') {
        throw new Exception("Cannot cancel this order");
    }

    // Update order status
    $updateStmt = $conn->prepare("UPDATE cart SET pickup_status = 'Cancelled' WHERE id = ?");
    $updateStmt->bind_param("i", $cart_id);
    
    if (!$updateStmt->execute()) {
        throw new Exception("Failed to cancel order");
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>