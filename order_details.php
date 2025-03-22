<?php
session_start();
require_once 'connect.php';

// Redirect if user is not logged in or cart_id is not provided
if (!isset($_SESSION['user_id']) || !isset($_GET['cart_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$cart_id = $_GET['cart_id'];

// Fetch order details
$stmt = $conn->prepare("
    SELECT c.*, 
           ci.image, ci.description,
           p.product_name,
           cat.category_name,
           ua.address_line, ua.landmark, ua.pincode,
           city.city_name
    FROM cart c
    JOIN cart_items ci ON c.id = ci.cart_id
    JOIN products p ON ci.product_id = p.product_id
    JOIN category cat ON p.category_id = cat.category_id
    LEFT JOIN user_addresses ua ON c.address_id = ua.address_id
    LEFT JOIN cities city ON ua.city_id = city.city_id
    WHERE c.id = ? AND c.user_id = ?
");
$stmt->bind_param("ii", $cart_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Redirect if no order is found
if ($result->num_rows === 0) {
    header("Location: order_history.php");
    exit();
}

$order_details = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details | JunkGenie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .details-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .order-header {
            border-bottom: 2px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .status-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .item-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 10px;
        }
        .item-card {
            border: 1px solid #eee;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .status-badge.status-Pending {
            background-color: #ffd700;
            color: #000;
        }
        .status-badge.status-Confirmed {
            background-color: #ff9800;
            color: white;
        }
        .status-badge.status-Completed {
            background-color: #4CAF50;
            color: white;
        }
        .status-badge.status-Rejected,
        .status-badge.status-Cancelled {
            background-color: #ff5252;
            color: white;
        }
        .btn-cancel {
            background-color: #ff5252;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .btn-cancel:hover {
            background-color: #ff1744;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="details-card">
            <div class="order-header">
                <h4>Order #<?= $cart_id ?></h4>
                <span class="status-badge status-<?= $order_details['pickup_status'] ?>">
                    <?= $order_details['pickup_status'] ?>
                </span>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <h5>Pickup Details</h5>
                    <p><strong>Date:</strong> <?= date('d M Y', strtotime($order_details['pickup_date'])) ?></p>
                    <p><strong>Address:</strong> 
                        <?= htmlspecialchars($order_details['address_line']) ?>
                        <?php if ($order_details['landmark']): ?>
                            <br><strong>Landmark:</strong> <?= htmlspecialchars($order_details['landmark']) ?>
                        <?php endif; ?>
                        <?php if ($order_details['city_name']): ?>
                            <br><strong>City:</strong> <?= htmlspecialchars($order_details['city_name']) ?>
                        <?php endif; ?>
                        <?php if ($order_details['pincode']): ?>
                            <br><strong>Pincode:</strong> <?= htmlspecialchars($order_details['pincode']) ?>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-6">
                    <h5>Order Information</h5>
                    <p><strong>Order Date:</strong> <?= date('d M Y, h:i A', strtotime($order_details['created_at'])) ?></p>
                </div>
            </div>

            <h5>Items</h5>
            <?php $result->data_seek(0); ?>
            <?php while ($item = $result->fetch_assoc()): ?>
                <div class="item-card">
                    <div class="row align-items-center">
                        <div class="col-md-2">
                            <img src="<?= htmlspecialchars($item['image']) ?>" class="item-image" alt="Item Image">
                        </div>
                        <div class="col-md-10">
                            <h6><?= htmlspecialchars($item['product_name']) ?></h6>
                            <p class="text-muted mb-1">Category: <?= htmlspecialchars($item['category_name']) ?></p>
                            <p class="mb-0"><?= htmlspecialchars($item['description']) ?></p>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>

            <div class="text-end mt-4">
                <a href="order_history.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Orders
                </a>
                <?php if (in_array($order_details['pickup_status'], ['Pending', 'Confirmed'])): ?>
                    <button onclick="cancelOrder(<?= $cart_id ?>)" class="btn btn-cancel">
                        <i class="fas fa-times me-2"></i>Cancel Order
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    function cancelOrder(cartId) {
        Swal.fire({
            title: 'Are you sure?',
            text: "Do you want to cancel this order?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff5252',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, cancel it!',
            cancelButtonText: 'No, keep it'
        }).then((result) => {
            if (result.isConfirmed) {
                // Send AJAX request to cancel order
                fetch('cancel_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'cart_id=' + cartId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire(
                            'Cancelled!',
                            'Your order has been cancelled.',
                            'success'
                        ).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire(
                            'Error!',
                            data.message || 'Failed to cancel order.',
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