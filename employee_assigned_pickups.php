<?php
session_start();
require_once 'connect.php';

// Redirect if not logged in or not an employee
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get logged-in user's ID
$user_id = $_SESSION['user_id'];

// Fetch employee ID for the logged-in user
$empQuery = "SELECT employee_id FROM employees WHERE user_id = ?";
$empStmt = $conn->prepare($empQuery);
$empStmt->bind_param("i", $user_id);
$empStmt->execute();
$empResult = $empStmt->get_result();
$employee = $empResult->fetch_assoc();

if (!$employee) {
    die("No employee record found for this user.");
}

$employee_id = $employee['employee_id'];

// Fetch assigned pickups for the employee
$query = "SELECT c.id AS cart_id, c.pickup_date, c.pickup_status, 
                 ci.description AS product_description, 
                 ci.image AS product_image, 
                 p.product_name,
                 cat.category_name,
                 p.price_per_pc
          FROM cart c
          JOIN cart_items ci ON c.id = ci.cart_id
          JOIN products p ON ci.product_id = p.product_id
          JOIN category cat ON p.category_id = cat.category_id
          WHERE c.assigned_employee_id = ? AND c.pickup_status = 'Pending'";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Error in query: " . $conn->error);
}
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
$pickups = [];

// Group products by cart_id
while ($row = $result->fetch_assoc()) {
    $cart_id = $row['cart_id'];
    if (!isset($pickups[$cart_id])) {
        $pickups[$cart_id] = [
            'cart_id' => $row['cart_id'],
            'pickup_date' => $row['pickup_date'],
            'pickup_status' => $row['pickup_status'],
            'products' => []
        ];
    }
    $pickups[$cart_id]['products'][] = [
        'product_name' => $row['product_name'],
        'product_description' => $row['product_description'],
        'product_image' => $row['product_image'],
        'category_name' => $row['category_name'],
        'price_per_pc' => $row['price_per_pc']
    ];
}

// Handle Accept/Reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $cart_id = $_POST['cart_id'];

        if ($_POST['action'] === 'accept') {
            // Update pickup_status to 'Accepted'
            $update_query = "UPDATE cart SET pickup_status = 'Accepted' WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("i", $cart_id);
            $stmt->execute();
        } elseif ($_POST['action'] === 'reject') {
            $reason = trim($_POST['reason']); // Remove whitespace from reason

            if (empty($reason)) {
                // Optionally add error handling if reason is empty
                $_SESSION['error'] = "Please provide a reason for rejection.";
            } else {
                // Update pickup_status to 'Rejected'
                $update_query = "UPDATE cart SET pickup_status = 'Rejected' WHERE id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("i", $cart_id);
                $stmt->execute();

                // Insert rejection reason into rejections table
                $insert_query = "INSERT INTO rejections (cart_id, reason) VALUES (?, ?)";
                $stmt = $conn->prepare($insert_query);
                $stmt->bind_param("is", $cart_id, $reason); // "is" for integer (cart_id) and string (reason)
                $stmt->execute();
            }
        }

        // Refresh the page to reflect changes
        header("Location: employee_assigned_pickups.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assigned Pickups | JunkGenie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
       :root {
            --primary-green: #2E7D32;
            --light-green: #4CAF50;
            --sidebar-width: 250px;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: var(--sidebar-width);
            background-color: var(--primary-green);
            padding: 1rem;
            z-index: 1000;
            height: 100vh;
            overflow-y: auto;
        }

        .logo-container {
            text-align: center;
            padding: 0.8rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 1rem;
        }

        .logo {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 0.5rem;
            border: 2px solid white;
        }

        .brand-name {
            font-size: 1.1rem;
            margin: 0;
            color: white;
            font-weight: 500;
        }

        .menu {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 0.8rem 1rem;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .menu-item i {
            width: 24px;
            margin-right: 10px;
            font-size: 1rem;
        }

        .menu-item span {
            font-size: 0.9rem;
        }

        .menu-item:hover, .menu-item.active {
            background: var(--light-green);
            transform: translateX(5px);
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            min-height: 100vh;
        }

        .profile-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #eee;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin-bottom: 1rem;
            border: 3px solid var(--primary-green);
            padding: 3px;
        }

        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-available {
            background: #E8F5E9;
            color: #2E7D32;
        }

        .status-unavailable {
            background: #FFEBEE;
            color: #C62828;
        }

        .info-group {
            margin-bottom: 1.5rem;
        }

        .info-label {
            font-weight: 600;
            color: #666;
            margin-bottom: 0.5rem;
        }

        .info-value {
            color: #333;
            font-size: 1.1rem;
        }

        .edit-button {
            background-color: var(--primary-green);
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .edit-button:hover {
            background-color: var(--light-green);
            transform: translateY(-2px);
        }

        /* Modal styles */
        .modal-header {
            background-color: var(--primary-green);
            color: white;
        }

        .modal-body {
            padding: 2rem;
        }

        .dashboard-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
        }

        .stats-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        .pickup-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }

        .product-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 10px;
            margin-right: 1rem;
        }
    </style>
</head>
<body>
    <?php include 'esidebar.php'; ?>

    <div class="main-content">
        <h1 class="mb-4">Assigned Pickups</h1>

        <?php if (empty($pickups)): ?>
            <div class="alert alert-info">No pending pickups assigned to you.</div>
        <?php else: ?>
            <?php foreach ($pickups as $pickup): ?>
                <div class="pickup-card">
                    <div class="pickup-header">
                        <div class="pickup-id">Pickup #<?php echo htmlspecialchars($pickup['cart_id']); ?></div>
                        <div class="pickup-date">Pickup Date: <?php echo date('F d, Y', strtotime($pickup['pickup_date'])); ?></div>
                    </div>

                    <div class="product-list">
                        <?php foreach ($pickup['products'] as $product): ?>
                            <div class="product-item">
                                <img src="<?php echo htmlspecialchars($product['product_image']); ?>" alt="Product Image" class="product-image">
                                <div class="product-details">
                                    <div class="product-name"><?php echo htmlspecialchars($product['product_name']); ?></div>
                                    <div class="product-category">Category: <?php echo htmlspecialchars($product['category_name']); ?></div>
                                    <div class="product-description"><?php echo htmlspecialchars($product['product_description']); ?></div>
                                    <div class="product-price">Price: ₹<?php echo htmlspecialchars($product['price_per_pc']); ?> per kg</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="item-count">Total Items: <?php echo count($pickup['products']); ?></div>

                    <!-- Accept/Reject Buttons -->
                    <div class="mt-3">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="cart_id" value="<?php echo $pickup['cart_id']; ?>">
                            <button type="submit" name="action" value="accept" class="btn btn-success">Accept</button>
                        </form>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo $pickup['cart_id']; ?>">Reject</button>
                    </div>

                    <!-- Reject Modal -->
                    <div class="modal fade" id="rejectModal<?php echo $pickup['cart_id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Reject Pickup</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST">
                                    <div class="modal-body">
                                        <input type="hidden" name="cart_id" value="<?php echo $pickup['cart_id']; ?>">
                                        <div class="mb-3">
                                            <label class="form-label">Reason for Rejection</label>
                                            <textarea name="reason" class="form-control" rows="3" required></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" name="action" value="reject" class="btn btn-danger">Reject</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>