<?php
session_start();
require_once 'connect.php';

// Check if user is logged in and cart_id is provided
if (!isset($_SESSION['user_id']) || !isset($_GET['cart_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$cart_id = $_GET['cart_id'];

// Verify the order belongs to the user and is cancellable
$stmt = $conn->prepare("
    SELECT pickup_status 
    FROM cart 
    WHERE id = ? AND user_id = ? AND pickup_status IN ('Pending', 'Confirmed')
");
$stmt->bind_param("ii", $cart_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Order doesn't exist or can't be cancelled
    header("Location: order_details.php?cart_id=" . $cart_id . "&error=cancel_failed");
    exit();
}

// Update the order status to 'Cancelled'
$update_stmt = $conn->prepare("
    UPDATE cart 
    SET pickup_status = 'Cancelled', 
        updated_at = NOW() 
    WHERE id = ? AND user_id = ?
");
$update_stmt->bind_param("ii", $cart_id, $user_id);

if ($update_stmt->execute()) {
    // Success - redirect back to order details
    header("Location: order_details.php?cart_id=" . $cart_id . "&cancel_success=1");
} else {
    // Error - redirect back to order details
    header("Location: order_details.php?cart_id=" . $cart_id . "&error=cancel_failed");
}
exit();
?>