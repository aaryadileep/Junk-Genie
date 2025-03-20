<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch order history
$orderStmt = $conn->prepare("
    SELECT oh.*, c.city_name,
           GROUP_CONCAT(CONCAT(p.product_name, ' (', ci.description, ')') SEPARATOR '<br>') as items
    FROM cart oh
    JOIN cities c ON oh.city_id = c.city_id
    JOIN cart_items ci ON oh.cart_id = ci.cart_id
    JOIN products p ON ci.product_id = p.product_id
    WHERE oh.user_id = ?
    GROUP BY oh.order_id
    ORDER BY oh.created_at DESC
");
$orderStmt->bind_param("i", $user_id);
$orderStmt->execute();
$orders = $orderStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History | JunkGenie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #007bff;
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 20px;
            text-align: center;
        }
        .card-body {
            padding: 30px;
        }
        .order-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .order-card:hover {
            transform: translateY(-5px);
        }
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        .status-Pending { background-color: #ffd700; }
        .status-Confirmed { background-color: #87ceeb; }
        .status-Picked { background-color: #98fb98; }
        .status-Completed { background-color: #90ee90; }
        .status-Cancelled { background-color: #ff6b6b; }
        .no-orders {
            text-align: center;
            color: #6c757d;
            padding: 50px 0;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container py-5">
        <h2 class="mb-4">My Pickup History</h2>

        <?php if ($orders->num_rows > 0): ?>
            <?php while ($order = $orders->fetch_assoc()): ?>
                <div class="order-card">
                    <div class="row">
                        <div class="col-md-8">
                            <h5>Order #<?= $order['order_id'] ?></h5>
                            <p class="text-muted mb-2">
                                Placed on: <?= date('F j, Y', strtotime($order['created_at'])) ?>
                            </p>
                            <p><strong>Pickup Date:</strong> <?= date('F j, Y', strtotime($order['pickup_date'])) ?></p>
                            <p><strong>Address:</strong> <?= htmlspecialchars($order['pickup_address']) ?></p>
                            <p><strong>City:</strong> <?= htmlspecialchars($order['city_name']) ?></p>
                            <div class="mt-3">
                                <strong>Items:</strong><br>
                                <?= $order['items'] ?>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <span class="status-badge status-<?= $order['order_status'] ?>">
                                <?= $order['order_status'] ?>
                            </span>
                            <p class="mt-3">
                                <strong>Total Items:</strong> <?= $order['total_items'] ?>
                            </p>
                            <?php if ($order['order_status'] == 'Pending'): ?>
                                <button class="btn btn-danger btn-sm mt-2" 
                                        onclick="cancelOrder(<?= $order['order_id'] ?>)">
                                    Cancel Order
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-box-open fa-3x mb-3 text-muted"></i>
                <h4>No orders yet</h4>
                <p class="text-muted">Start selling your e-waste today!</p>
                <a href="sell.php" class="btn btn-success">Sell Now</a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function cancelOrder(orderId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, cancel it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Add AJAX call to cancel order
                    fetch('cancel_order.php?order_id=' + orderId)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire(
                                    'Cancelled!',
                                    'Your order has been cancelled.',
                                    'success'
                                ).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire(
                                    'Error!',
                                    'Failed to cancel order.',
                                    'error'
                                );
                            }
                        });
                }
            });
        }
    </script>
</body>
</html>