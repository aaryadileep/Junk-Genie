<?php
session_start();
include 'connect.php'; // Database connection

$user_id = $_SESSION['user_id'] ?? 1; // Replace with actual user ID logic

// Fetch cart items
$cartQuery = "SELECT * FROM cart WHERE user_id = '$user_id'";
$cartResult = mysqli_query($conn, $cartQuery);

// Prepare order details
$orderDetails = [];
while ($row = mysqli_fetch_assoc($cartResult)) {
    $orderDetails[] = $row;
}

// Insert into orders table
$orderDetailsJson = json_encode($orderDetails);
$addressQuery = "SELECT address FROM users WHERE user_id = '$user_id'";
$addressResult = mysqli_query($conn, $addressQuery);
$address = mysqli_fetch_assoc($addressResult)['address'] ?? 'Address not specified';

$query = "INSERT INTO orders (user_id, order_details, address) 
          VALUES ('$user_id', '$orderDetailsJson', '$address')";
if (mysqli_query($conn, $query)) {
    // Clear the cart
    mysqli_query($conn, "DELETE FROM cart WHERE user_id = '$user_id'");
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to confirm order']);
}
?>