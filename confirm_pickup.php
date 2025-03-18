<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $selected_address_id = $_POST['selected_address'];

    // Fetch the latest cart for the user
    $cart_stmt = $conn->prepare("SELECT id FROM cart WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $cart_stmt->bind_param("i", $user_id);
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();
    $cart = $cart_result->fetch_assoc();

    if ($cart) {
        // Update the cart with the selected address
        $update_stmt = $conn->prepare("UPDATE cart SET pickup_address_id = ? WHERE id = ?");
        $update_stmt->bind_param("ii", $selected_address_id, $cart['id']);
        $update_stmt->execute();

        // Redirect to a success page
        header("Location: success.php");
        exit();
    } else {
        die("No cart found for this user.");
    }
}