<?php
session_start();
include 'connect.php'; // Database connection

$user_id = $_SESSION['user_id'] ?? 1; // Replace with actual user ID logic

// Fetch cart items
$cartQuery = "SELECT c.*, p.product_name, cat.category_name 
              FROM cart c 
              JOIN products p ON c.product_id = p.product_id 
              JOIN category cat ON c.category_id = cat.category_id 
              WHERE c.user_id = '$user_id'";
$cartResult = mysqli_query($conn, $cartQuery);

// Fetch user address (assuming it's stored in the users table)
$userQuery = "SELECT address FROM users WHERE user_id = '$user_id'";
$userResult = mysqli_query($conn, $userQuery);
$userAddress = mysqli_fetch_assoc($userResult)['address'] ?? 'Address not specified';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart | JunkGenie</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="cart-container">
        <h2>Your Cart</h2>
        <div class="cart-items">
            <?php while ($row = mysqli_fetch_assoc($cartResult)): ?>
                <div class="cart-item">
                    <h3><?= $row['product_name'] ?></h3>
                    <p>Category: <?= $row['category_name'] ?></p>
                    <p>Description: <?= $row['description'] ?></p>
                    <p>Pickup Date: <?= $row['pickup_date'] ?> at <?= $row['pickup_time'] ?></p>
                    <?php if ($row['image']): ?>
                        <img src="<?= $row['image'] ?>" alt="Product Image" width="100">
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>

        <div class="address-section">
            <h3>Delivery Address</h3>
            <p><?= $userAddress ?></p>
        </div>

        <button id="confirm-order-btn">Confirm Order</button>
    </div>

    <script>
        // Confirm order and send to admin
        $('#confirm-order-btn').click(function () {
            $.ajax({
                url: 'confirm_order.php',
                type: 'POST',
                success: function (response) {
                    var data = JSON.parse(response);
                    if (data.success) {
                        alert("Order confirmed and sent to admin!");
                        window.location.href = "dashboard.php"; // Redirect to dashboard
                    } else {
                        alert(data.message || "Failed to confirm order.");
                    }
                }
            });
        });
    </script>
</body>
</html>