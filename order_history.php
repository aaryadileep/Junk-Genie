<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch orders with cart details
$stmt = $conn->prepare("
    SELECT c.id as cart_id, 
           c.pickup_date,
           c.pickup_status,
           c.created_at,
           COUNT(ci.id) as total_items
    FROM cart c
    LEFT JOIN cart_items ci ON c.id = ci.cart_id
    WHERE c.user_id = ?
    GROUP BY c.id
    ORDER BY c.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders = $stmt->get_result();
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
        background-color: #e6f3e6;
        padding-top: 20px;
        font-family: 'Montserrat', sans-serif;
    }
    .order-box {
        background: linear-gradient(135deg, #ffffff, #f0f7f0);
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 10px 30px rgba(39, 174, 96, 0.15);
        transition: all 0.3s ease;
        border: 2px solid #2ecc71;
    }
    .order-box:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(39, 174, 96, 0.2);
    }
    .order-id {
        color: #2ecc71;
        font-size: 1.2rem;
        font-weight: bold;
        text-transform: uppercase;
    }
    .status-badge {
        padding: 8px 15px;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    .status-Pending {
        background-color: #ffd700;
        color: #2c3e50;
    }
    .status-Confirmed {
        background-color: #2ecc71;
        color: white;
    }
    .status-Cancelled {
        background-color: #e74c3c;
        color: white;
    }
    .status-Completed {
        background-color: #3498db;
        color: white;
    }
    .btn-open {
        background-color: #2ecc71;
        color: white;
        border: none;
        padding: 10px 25px;
        border-radius: 8px;
        transition: all 0.3s ease;
        font-weight: 600;
        text-transform: uppercase;
    }
    .btn-open:hover {
        background-color: #27ae60;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
    }
    .empty-state {
        text-align: center;
        padding: 50px 20px;
        background-color: #f0f7f0;
        border-radius: 15px;
        border: 2px dashed #2ecc71;
    }
    .empty-state i {
        font-size: 4rem;
        color: #2ecc71;
        margin-bottom: 20px;
        opacity: 0.7;
    }
</style>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container py-5">
        <h2 class="mb-4">My Orders</h2>

        <?php if ($orders->num_rows > 0): ?>
            <div class="row">
                <?php while ($order = $orders->fetch_assoc()): ?>
                    <div class="col-md-6 mb-4">
                        <div class="order-box">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="order-id">Order #OI<?= $order['cart_id'] ?></span>
                                <span class="status-badge status-<?= $order['pickup_status'] ?>">
                                    <?= $order['pickup_status'] ?>
                                </span>
                            </div>
                            
                            <div class="mb-3">
                                <p class="mb-2">
                                    <i class="fas fa-calendar-alt me-2"></i>
                                    Pickup Date: <?= date('d M Y', strtotime($order['pickup_date'])) ?>
                                </p>
                                <p class="mb-2">
                                    <i class="fas fa-box me-2"></i>
                                    Items: <?= $order['total_items'] ?>
                                </p>
                                <p class="mb-0">
                                    <i class="fas fa-clock me-2"></i>
                                    Ordered on: <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?>
                                </p>
                            </div>

                            <div class="text-end">
                                <a href="order_details.php?cart_id=<?= $order['cart_id'] ?>" 
                                   class="btn-open">
                                    <i class="fas fa-external-link-alt me-2"></i>Open
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <h3>No Orders Yet</h3>
                <p class="text-muted">Start selling your e-waste today!</p>
                <a href="sell.php" class="btn btn-success mt-3">
                    <i class="fas fa-plus me-2"></i>Sell Now
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 