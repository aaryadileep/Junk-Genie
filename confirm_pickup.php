<?php
session_start();
require_once 'connect.php';

// Redirect if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$cart_id = $_GET['cart_id'] ?? null;

// Fetch user details with city
$userStmt = $conn->prepare("SELECT u.*, c.city_name
                           FROM users u 
                           JOIN cities c ON u.city_id = c.city_id 
                           WHERE u.user_id = ?");
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();

// Add a new query to fetch user addresses
$addressStmt = $conn->prepare("SELECT * FROM user_addresses WHERE user_id = ? AND is_active = 1");
$addressStmt->bind_param("i", $user_id);
$addressStmt->execute();
$addresses = $addressStmt->get_result();

if (!$user) {
    echo "<script>
            Swal.fire({
                title: 'Error!',
                text: 'User not found',
                icon: 'error',
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                window.location.href='login.php';
            });
          </script>";
    exit();
}

// Fetch cart details with items
$cartStmt = $conn->prepare("SELECT c.*, ci.*, p.product_name, cat.category_name 
                           FROM cart c
                           JOIN cart_items ci ON c.id = ci.cart_id
                           JOIN products p ON ci.product_id = p.product_id
                           JOIN category cat ON p.category_id = cat.category_id
                           WHERE c.id = ? AND c.user_id = ?");
$cartStmt->bind_param("ii", $cart_id, $user_id);
$cartStmt->execute();
$cart_items = $cartStmt->get_result();

// Fetch categories and products for edit form
$categories = $conn->query("SELECT * FROM category WHERE is_active = 1");
$products = $conn->query("SELECT * FROM products WHERE is_active = 1");

// Handle cart item updates
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_item'])) {
    $item_id = $_POST['item_id'];
    $product_id = $_POST['product_id'];
    $description = $_POST['description'];
    $pickup_date = $_POST['pickup_date'];

    if ($_FILES['image']['name']) {
        $imageName = time() . '_' . $_FILES['image']['name'];
        $imagePath = "uploads/cart/" . $imageName;
        move_uploaded_file($_FILES['image']['tmp_name'], $imagePath);
    } else {
        $imagePath = $_POST['existing_image'];
    }

    $updateStmt = $conn->prepare("UPDATE cart_items SET product_id = ?, description = ?, image = ? WHERE id = ?");
    $updateStmt->bind_param("issi", $product_id, $description, $imagePath, $item_id);
    
    if ($updateStmt->execute()) {
        // Update pickup date in cart table
        $updateCartStmt = $conn->prepare("UPDATE cart SET pickup_date = ? WHERE id = ?");
        $updateCartStmt->bind_param("si", $pickup_date, $cart_id);
        $updateCartStmt->execute();
        
        header("Location: confirm_pickup.php?cart_id=" . $cart_id);
        exit();
    }
}

// Handle pickup confirmation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_pickup'])) {
    if (empty($_POST['pickup_address_id'])) {
        echo "<script>
                Swal.fire({
                    title: 'Error!',
                    text: 'Please select a pickup address',
                    icon: 'error',
                    showConfirmButton: true
                });
              </script>";
    } else {
        $pickup_address_id = $_POST['pickup_address_id'];
        
        try {
            $conn->begin_transaction();

            // Get city_id and address details
            $cityStmt = $conn->prepare("SELECT ua.*, c.city_name 
                                       FROM user_addresses ua 
                                       JOIN cities c ON ua.city_id = c.city_id 
                                       WHERE ua.address_id = ?");
            $cityStmt->bind_param("i", $pickup_address_id);
            $cityStmt->execute();
            $addressData = $cityStmt->get_result()->fetch_assoc();
            
            if (!$addressData) {
                throw new Exception("Invalid address selected");
            }

            // Combine address details
            $fullAddress = $addressData['address_line'];
            if (!empty($addressData['landmark'])) {
                $fullAddress .= ", " . $addressData['landmark'];
            }
            $fullAddress .= " - " . $addressData['pincode'];

            // Get cart details including pickup date
            $cartStmt = $conn->prepare("SELECT pickup_date FROM cart WHERE id = ?");
            $cartStmt->bind_param("i", $cart_id);
            $cartStmt->execute();
            $cartData = $cartStmt->get_result()->fetch_assoc();

            // Count total items in cart
            $itemCountStmt = $conn->prepare("SELECT COUNT(*) as total FROM cart_items WHERE cart_id = ?");
            $itemCountStmt->bind_param("i", $cart_id);
            $itemCountStmt->execute();
            $totalItems = $itemCountStmt->get_result()->fetch_assoc()['total'];

            // Insert into order_history
            $insertOrderStmt = $conn->prepare("INSERT INTO order_history 
                (user_id, cart_id, pickup_date, pickup_address, city_id, total_items, order_status) 
                VALUES (?, ?, ?, ?, ?, ?, 'Confirmed')");
            
            $insertOrderStmt->bind_param("iissis", 
                $user_id, 
                $cart_id, 
                $cartData['pickup_date'],
                $fullAddress,
                $addressData['city_id'],
                $totalItems
            );

            if (!$insertOrderStmt->execute()) {
                throw new Exception("Failed to create order history");
            }

            // Update cart status
            $updateCartStmt = $conn->prepare("UPDATE cart 
                SET pickup_status = 'Confirmed', 
                    pickup_address_id = ?,
                    city_id = ?,
                    pickup_address = ?
                WHERE id = ?");
            $updateCartStmt->bind_param("iisi", 
                $pickup_address_id, 
                $addressData['city_id'],
                $fullAddress,
                $cart_id
            );

            if (!$updateCartStmt->execute()) {
                throw new Exception("Failed to update cart status");
            }

            $conn->commit();
            
            echo "<script>
                    Swal.fire({
                        title: 'Success!',
                        text: 'Pickup confirmed successfully',
                        icon: 'success',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.href = 'order_history.php';
                    });
                  </script>";

        } catch (Exception $e) {
            $conn->rollback();
            echo "<script>
                    Swal.fire({
                        title: 'Error!',
                        text: 'Failed to confirm pickup: " . $e->getMessage() . "',
                        icon: 'error',
                        showConfirmButton: true
                    });
                  </script>";
        }
    }
}
?>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Pickup | JunkGenie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        .confirmation-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .address-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .address-card:hover {
            border-color: #0d6efd;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .address-card.selected {
            border-color: #198754;
            background-color: #f8f9fa;
        }
        .preview-image {
            max-width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }
        .item-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .btn-edit {
            background: #ffc107;
            color: #000;
            border: none;
            padding: 5px 15px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .btn-edit:hover {
            background: #ffb300;
            transform: translateY(-2px);
        }
        .btn-confirm {
            background: #28a745;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 500;
            padding: 0.75rem 1.25rem;
            transition: all 0.3s ease;
        }
        .btn-confirm:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        .address-box {
            background-color: #f8f9fa;
            transition: all 0.3s ease;
        }
        
        .address-box:hover {
            background-color: #e9ecef;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container py-5">
        <div class="confirmation-card">
            <h2 class="mb-4">Confirm Pickup Details</h2>

            <!-- User Details -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Customer Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Name:</strong> <?= htmlspecialchars($user['fullname']) ?></p>
                            <p><strong>City:</strong> <?= htmlspecialchars($user['city_name']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="mb-3">Address</h6>
                            <?php if ($addresses->num_rows > 0): ?>
                                <?php while ($address = $addresses->fetch_assoc()): ?>
                                    <div class="address-box mb-3 p-2 border rounded">
                                        <strong><?= htmlspecialchars($address['address_type']) ?></strong><br>
                                        <?= htmlspecialchars($address['address_line']) ?><br>
                                        <?php if (!empty($address['landmark'])): ?>
                                            Landmark: <?= htmlspecialchars($address['landmark']) ?><br>
                                        <?php endif; ?>
                                        Pincode: <?= htmlspecialchars($address['pincode']) ?>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p class="text-danger">No addresses found. Please add an address.</p>
                                <a href="addresses.php" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i> Add Address
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cart Items -->
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Items for Pickup</h5>
                </div>
                <div class="card-body">
                    <?php while ($item = $cart_items->fetch_assoc()): ?>
                        <div class="item-card">
                            <div class="row align-items-center">
                                <div class="col-md-2">
                                    <img src="<?= htmlspecialchars($item['image']) ?>" alt="Item" class="preview-image">
                                </div>
                                <div class="col-md-8">
                                    <h5><?= htmlspecialchars($item['product_name']) ?></h5>
                                    <p>Category: <?= htmlspecialchars($item['category_name']) ?></p>
                                    <p>Description: <?= htmlspecialchars($item['description']) ?></p>
                                    <p>Pickup Date: <?= htmlspecialchars($item['pickup_date']) ?></p>
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-warning" data-bs-toggle="modal" 
                                            data-bs-target="#editModal<?= $item['id'] ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Edit Modal -->
                        <div class="modal fade" id="editModal<?= $item['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Item</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form method="POST" enctype="multipart/form-data">
                                            <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                            <input type="hidden" name="existing_image" value="<?= $item['image'] ?>">

                                            <div class="mb-3">
                                                <label>Category</label>
                                                <select class="form-select category-select" required>
                                                    <?php 
                                                    $categories->data_seek(0);
                                                    while ($cat = $categories->fetch_assoc()): 
                                                    ?>
                                                        <option value="<?= $cat['category_id'] ?>">
                                                            <?= htmlspecialchars($cat['category_name']) ?>
                                                        </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label>Product</label>
                                                <select class="form-select" name="product_id" required>
                                                    <?php 
                                                    $products->data_seek(0);
                                                    while ($prod = $products->fetch_assoc()): 
                                                    ?>
                                                        <option value="<?= $prod['product_id'] ?>"
                                                            <?= ($prod['product_id'] == $item['product_id']) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($prod['product_name']) ?>
                                                        </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label>Description</label>
                                                <textarea class="form-control" name="description" required>
                                                    <?= htmlspecialchars($item['description']) ?>
                                                </textarea>
                                            </div>

                                            <div class="mb-3">
                                                <label>Pickup Date</label>
                                                <input type="date" class="form-control" name="pickup_date"
                                                       value="<?= $item['pickup_date'] ?>" required>
                                            </div>

                                            <div class="mb-3">
                                                <label>Image</label>
                                                <input type="file" class="form-control" name="image" accept="image/*">
                                                <img src="<?= $item['image'] ?>" class="preview-image mt-2">
                                            </div>

                                            <button type="submit" name="update_item" class="btn btn-success w-100">
                                                Update Item
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>

                    <div class="text-end mt-4">
                        <a href="order_history.php" class="btn btn-success">
                            <i class="fas fa-check"></i> Confirm Pickup
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Update products when category changes
        document.querySelectorAll('.category-select').forEach(select => {
            select.addEventListener('change', function() {
                const productSelect = this.closest('form').querySelector('[name="product_id"]');
                fetch('get_products.php?category_id=' + this.value)
                    .then(response => response.text())
                    .then(html => productSelect.innerHTML = html);
            });
        });
    </script>
</body>
</html>