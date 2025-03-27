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

// Fetch order details with price_per_pc from products table
$stmt = $conn->prepare("
    SELECT c.*, 
           ci.image, ci.description,
           p.product_name, p.price_per_pc,
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

// Calculate total price
$total_price = 0;
$result->data_seek(0);
while ($row = $result->fetch_assoc()) {
    $total_price += $row['price_per_pc'];
}

// Fetch rejection reason if pickup_status is Rejected
$rejection_reason = null;
if ($order_details['pickup_status'] === 'Rejected') {
    $rejection_stmt = $conn->prepare("
        SELECT reason 
        FROM rejections 
        WHERE cart_id = ?
    ");
    $rejection_stmt->bind_param("i", $cart_id);
    $rejection_stmt->execute();
    $rejection_result = $rejection_stmt->get_result();
    if ($rejection_result->num_rows > 0) {
        $rejection_data = $rejection_result->fetch_assoc();
        $rejection_reason = $rejection_data['reason'];
    }
}
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
            background-color: #e6f3e6;
            padding-top: 20px;
            font-family: 'Montserrat', sans-serif;
        }
        .details-card {
            background: linear-gradient(135deg, #ffffff, #f0f7f0);
            border-radius: 15px;
            padding: 25px;
            margin-top: 80px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(39, 174, 96, 0.15);
            border: 2px solid #2ecc71;
        }
        .order-header {
            border-bottom: 2px solid #27ae60;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .status-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .item-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 10px;
            border: 3px solid #2ecc71;
        }
        .item-card {
            border: 1px solid #27ae60;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #f0f7f0;
            transition: all 0.3s ease;
        }
        .item-card:hover {
            box-shadow: 0 8px 25px rgba(39, 174, 96, 0.2);
            transform: translateY(-5px);
        }
        .status-badge.status-Pending {
            background-color: #ffd700;
            color: #2c3e50;
        }
        .status-badge.status-Confirmed {
            background-color: #ff9800;
            color: white;
        }
        .status-badge.status-Completed {
            background-color: #2ecc71;
            color: white;
        }
        .status-badge.status-Rejected,
        .status-badge.status-Cancelled {
            background-color: #e74c3c;
            color: white;
        }
        .btn-cancel {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 600;
            text-transform: uppercase;
        }
        .btn-cancel:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
        }
        .btn-reason {
            background-color: #2c3e50;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 600;
            text-transform: uppercase;
        }
        .btn-reason:hover {
            background-color: #34495e;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(44, 62, 80, 0.3);
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <!-- Display success/error messages -->
    <?php if (isset($_GET['cancel_success'])): ?>
        <div class="container mt-3">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Order has been successfully cancelled.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php elseif (isset($_GET['error']) && $_GET['error'] === 'cancel_failed'): ?>
        <div class="container mt-3">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                Failed to cancel the order. Please try again.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>

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
                    <p><strong>Total Earnings:</strong> ₹<?= number_format($total_price, 2) ?></p>
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
                            <p class="mb-1">Price per piece: ₹<?= number_format($item['price_per_pc'], 2) ?></p>
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
                    <form action="cancel_order.php" method="GET" style="display: inline;">
                        <input type="hidden" name="cart_id" value="<?= $cart_id ?>">
                        <button type="button" onclick="cancelOrder(<?= $cart_id ?>)" class="btn btn-cancel">
                            <i class="fas fa-times me-2"></i>Cancel Order
                        </button>
                    </form>
                <?php elseif ($order_details['pickup_status'] === 'Rejected' && $rejection_reason): ?>
                    <button type="button" class="btn btn-reason" data-bs-toggle="modal" data-bs-target="#rejectionModal">
                        <i class="fas fa-info-circle me-2"></i>Show Rejection Reason
                    </button>
                <?php endif; ?>
            </div>

            <?php if ($order_details['pickup_status'] === 'Rejected' && $rejection_reason): ?>
                <!-- Modal for displaying rejection reason -->
                <div class="modal fade" id="rejectionModal" tabindex="-1" aria-labelledby="rejectionModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="rejectionModalLabel">Rejection Reason</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p><?= htmlspecialchars($rejection_reason) ?></p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    function cancelOrder(cartId) {
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
                // Submit the form
                document.querySelector('form[action="cancel_order.php"]').submit();
            }
        });
    }
    </script>
</body>
</html>