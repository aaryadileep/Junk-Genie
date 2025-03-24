<?php
session_start();
require_once 'connect.php';

// Redirect if not logged in or not an employee
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

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

// Fetch completed pickups for the employee
$query = "
    SELECT c.id AS cart_id, c.pickup_date, c.pickup_status, 
           ci.description AS product_description, ci.image AS product_image,
           p.product_name, cat.category_name, p.price_per_pc,
           u.fullname AS customer_name,
           ua.address_line, ua.landmark, ua.pincode, city.city_name
    FROM cart c
    JOIN cart_items ci ON c.id = ci.cart_id
    JOIN products p ON ci.product_id = p.product_id
    JOIN category cat ON p.category_id = cat.category_id
    JOIN users u ON c.user_id = u.user_id
    LEFT JOIN user_addresses ua ON c.address_id = ua.address_id
    LEFT JOIN cities city ON ua.city_id = city.city_id
    WHERE c.assigned_employee_id = ? AND c.pickup_status = 'Completed'
    ORDER BY c.pickup_date DESC
";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Error in query: " . $conn->error);
}
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();

// Group pickups by cart_id
$pickups = [];
while ($row = $result->fetch_assoc()) {
    $cart_id = $row['cart_id'];
    if (!isset($pickups[$cart_id])) {
        $pickups[$cart_id] = [
            'cart_id' => $row['cart_id'],
            'pickup_date' => $row['pickup_date'],
            'customer_name' => $row['customer_name'],
            'address_line' => $row['address_line'],
            'landmark' => $row['landmark'],
            'pincode' => $row['pincode'],
            'city_name' => $row['city_name'],
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pickup History | JunkGenie</title>
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

        .menu-item:hover, .menu-item.active {
            background: var(--light-green);
            transform: translateX(5px);
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            min-height: 100vh;
        }

        .pickup-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }

        .pickup-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 1rem;
            margin-bottom: 1rem;
        }

        .product-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 10px;
            margin-right: 1rem;
        }

        .product-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 1rem;
        }

        .product-details {
            flex-grow: 1;
        }
    </style>
</head>
<body>
    <?php include 'esidebar.php'; ?>

    <div class="main-content">
        <h1 class="mb-4">Pickup History</h1>

        <?php if (empty($pickups)): ?>
            <div class="alert alert-info">No completed pickups found.</div>
        <?php else: ?>
            <?php foreach ($pickups as $pickup): ?>
                <div class="pickup-card">
                    <div class="pickup-header">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Pickup #<?php echo htmlspecialchars($pickup['cart_id']); ?></h5>
                                <p><strong>Customer:</strong> <?php echo htmlspecialchars($pickup['customer_name']); ?></p>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <p><strong>Pickup Date:</strong> <?php echo date('F d, Y', strtotime($pickup['pickup_date'])); ?></p>
                            </div>
                        </div>
                        <p><strong>Address:</strong> 
                            <?php echo htmlspecialchars($pickup['address_line']); ?>
                            <?php if ($pickup['landmark']): ?>, <?php echo htmlspecialchars($pickup['landmark']); ?><?php endif; ?>
                            <?php if ($pickup['city_name']): ?>, <?php echo htmlspecialchars($pickup['city_name']); ?><?php endif; ?>
                            <?php if ($pickup['pincode']): ?>, <?php echo htmlspecialchars($pickup['pincode']); ?><?php endif; ?>
                        </p>
                    </div>

                    <div class="product-list">
                        <?php foreach ($pickup['products'] as $product): ?>
                            <div class="product-item">
                                <img src="<?php echo htmlspecialchars($product['product_image']); ?>" alt="Product Image" class="product-image">
                                <div class="product-details">
                                    <div class="product-name"><strong><?php echo htmlspecialchars($product['product_name']); ?></strong></div>
                                    <div class="product-category">Category: <?php echo htmlspecialchars($product['category_name']); ?></div>
                                    <div class="product-description"><?php echo htmlspecialchars($product['product_description']); ?></div>
                                    <div class="product-price">Price: ₹<?php echo htmlspecialchars($product['price_per_pc']); ?> per kg</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="item-count"><strong>Total Items:</strong> <?php echo count($pickup['products']); ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>