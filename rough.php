<?php
session_start();
require_once 'connect.php';

// Redirect if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user details including city_id
$userStmt = $conn->prepare("SELECT u.*, c.city_name 
                           FROM users u 
                           JOIN cities c ON u.city_id = c.city_id 
                           WHERE u.user_id = ?");
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();

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

// Fetch user addresses with city information
$addressStmt = $conn->prepare("SELECT ua.*, c.city_name 
                              FROM user_addresses ua 
                              JOIN cities c ON ua.city_id = c.city_id 
                              WHERE ua.user_id = ? AND ua.is_active = 1");
$addressStmt->bind_param("i", $user_id);
$addressStmt->execute();
$addresses = $addressStmt->get_result();

// Get cart_id from URL or fetch latest pending cart
if (isset($_GET['cart_id'])) {
    $cart_id = $_GET['cart_id'];
} else {
    $cartQuery = "SELECT id FROM cart WHERE user_id = ? AND pickup_status = 'Pending' 
                  ORDER BY created_at DESC LIMIT 1";
    $stmt = $conn->prepare($cartQuery);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cart = $result->fetch_assoc();
    $cart_id = $cart['id'] ?? null;

    if (!$cart_id) {
        echo "<script>
                Swal.fire({
                    title: 'Error!',
                    text: 'No pending cart found',
                    icon: 'error',
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    window.location.href='sell.php';
                });
              </script>";
        exit();
    }
}


// Fetch cart details
$cartStmt = $conn->prepare("SELECT * FROM cart WHERE id = ? AND user_id = ?");
$cartStmt->bind_param("ii", $cart_id, $user_id);
$cartStmt->execute();
$cart = $cartStmt->get_result()->fetch_assoc();

if (!$cart) {
    echo "<script>
            Swal.fire({
                title: 'Error!',
                text: 'Cart not found',
                icon: 'error',
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                window.location.href='sell.php';
            });
          </script>";
    exit();
}

// Fetch cart items with category and product details
$itemsStmt = $conn->prepare("SELECT ci.*, p.product_name, c.category_name 
                            FROM cart_items ci 
                            JOIN products p ON ci.product_id = p.product_id 
                            JOIN category c ON p.category_id = c.category_id
                            WHERE ci.cart_id = ?");
$itemsStmt->bind_param("i", $cart_id);
$itemsStmt->execute();
$cart_items = $itemsStmt->get_result();

// Handle item updates
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_item'])) {
    $item_id = $_POST['item_id'];
    $description = $_POST['description'];
    $image = $_FILES['image'];

    if ($image['name']) {
        $imageName = time() . '_' . $image['name'];
        $imagePath = "uploads/cart/" . $imageName;
        
        if (!file_exists("uploads/cart/")) {
            mkdir("uploads/cart/", 0777, true);
        }
        
        if (move_uploaded_file($image['tmp_name'], $imagePath)) {
            // Delete old image if exists
            if (!empty($_POST['existing_image']) && file_exists($_POST['existing_image'])) {
                unlink($_POST['existing_image']);
            }
        }
    } else {
        $imagePath = $_POST['existing_image'];
    }

    $updateStmt = $conn->prepare("UPDATE cart_items SET description = ?, image = ? WHERE id = ?");
    $updateStmt->bind_param("ssi", $description, $imagePath, $item_id);
    
    if ($updateStmt->execute()) {
        echo "<script>
                Swal.fire({
                    title: 'Success!',
                    text: 'Item updated successfully',
                    icon: 'success',
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    window.location.reload();
                });
              </script>";
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
        
        // Get city_id and address details
        $cityStmt = $conn->prepare("SELECT ua.*, c.city_name 
                                   FROM user_addresses ua 
                                   JOIN cities c ON ua.city_id = c.city_id 
                                   WHERE ua.address_id = ?");
        $cityStmt->bind_param("i", $pickup_address_id);
        $cityStmt->execute();
        $addressData = $cityStmt->get_result()->fetch_assoc();
        
        if (!$addressData) {
            echo "<script>
                    Swal.fire({
                        title: 'Error!',
                        text: 'Invalid address selected',
                        icon: 'error',
                        showConfirmButton: true
                    });
                  </script>";
        } else {
            // Start transaction
            $conn->begin_transaction();

            try {
                // Combine address details
                $fullAddress = $addressData['address_line'];
                if (!empty($addressData['landmark'])) {
                    $fullAddress .= ", " . $addressData['landmark'];
                }
                $fullAddress .= " - " . $addressData['pincode'];

                // Update cart with address and city
                $updateCartStmt = $conn->prepare("UPDATE cart 
                                                SET pickup_address_id = ?,
                                                    city_id = ?,
                                                    pickup_address = ?,
                                                    pickup_status = 'Confirmed',
                                                    updated_at = NOW()
                                                WHERE id = ?");
                $updateCartStmt->bind_param("iiss", 
                    $pickup_address_id, 
                    $addressData['city_id'],
                    $fullAddress,
                    $cart_id
                );
                
                if (!$updateCartStmt->execute()) {
                    throw new Exception("Failed to update cart: " . $conn->error);
                }

                // Create notification for admin
                $notificationStmt = $conn->prepare("INSERT INTO admin_notifications 
                                                  (user_id, cart_id, notification_type, message) 
                                                  VALUES (?, ?, 'pickup_request', 'New pickup request received')");
                $notificationStmt->bind_param("ii", $user_id, $cart_id);
                
                if (!$notificationStmt->execute()) {
                    throw new Exception("Failed to create notification: " . $conn->error);
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
                            window.location.href = 'order_details.php?cart_id=" . $cart_id . "';
                        });
                      </script>";

            } catch (Exception $e) {
                $conn->rollback();
                error_log("Error in confirm_pickup.php: " . $e->getMessage());
                echo "<script>
                        Swal.fire({
                            title: 'Error!',
                            text: 'Failed to confirm pickup. Please try again.',
                            icon: 'error',
                            showConfirmButton: true
                        });
                      </script>";
            }
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
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container py-5">
        <div class="confirmation-card">
            <h2 class="mb-4">Confirm Pickup Details</h2>

            <!-- User Details -->
            <div class="mb-4">
                <h4>Customer Information</h4>
                <p class="mb-1"><strong>Name:</strong> <?= htmlspecialchars($user['fullname']); ?></p>
                <p><strong>City:</strong> <?= htmlspecialchars($user['city_name']); ?></p>
            </div>

            <!-- Cart Items -->
            <div class="mb-4">
                <h4>Items for Pickup</h4>
                <?php while ($item = $cart_items->fetch_assoc()): ?>
                    <div class="item-card">
                        <div class="row align-items-center">
                            <div class="col-md-2">
                                <img src="<?= htmlspecialchars($item['image']); ?>" 
                                     alt="Item Image" class="preview-image">
                            </div>
                            <div class="col-md-8">
                                <h5><?= htmlspecialchars($item['product_name']); ?></h5>
                                <p class="text-muted mb-2">Category: <?= htmlspecialchars($item['category_name']); ?></p>
                                <p class="mb-0"><?= htmlspecialchars($item['description']); ?></p>
                            </div>
                            <div class="col-md-2 text-end">
                                <button type="button" class="btn-edit" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editItemModal<?= $item['id']; ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editItemModal<?= $item['id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Item</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <form action="" method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="item_id" value="<?= $item['id']; ?>">
                                        <input type="hidden" name="existing_image" value="<?= $item['image']; ?>">
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea class="form-control" name="description" rows="3" 
                                                      required><?= htmlspecialchars($item['description']); ?></textarea>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Current Image</label>
                                            <img src="<?= htmlspecialchars($item['image']); ?>" 
                                                 class="d-block preview-image mb-2">
                                            <input type="file" class="form-control" name="image" accept="image/*">
                                        </div>
                                        
                                        <div class="text-end">
                                            <button type="button" class="btn btn-secondary" 
                                                    data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" name="update_item" 
                                                    class="btn btn-primary">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            <!-- Address Selection Form -->
<form action="" method="POST" id="pickupForm">
    <div class="mb-4">
        <h4>Select Pickup Address</h4>
        <?php if ($addresses->num_rows > 0): ?>
            <?php while ($address = $addresses->fetch_assoc()): ?>
                <div class="address-card">
                    <input type="radio" name="pickup_address_id" 
                           value="<?= $address['address_id']; ?>" required
                           class="form-check-input me-2">
                    <strong><?= htmlspecialchars($address['address_type']); ?></strong><br>
                    <?= htmlspecialchars($address['address_line']); ?><br>
                    <?php if ($address['landmark']): ?>
                        <span class="text-muted">Landmark: <?= htmlspecialchars($address['landmark']); ?></span><br>
                    <?php endif; ?>
                    <span class="text-muted">
                        <?= htmlspecialchars($address['city_name']); ?> - 
                        <?= htmlspecialchars($address['pincode']); ?>
                    </span>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="alert alert-warning">
                No addresses found. Please <a href="add_address.php">add an address</a> first.
            </div>
        <?php endif; ?>
    </div>

    <div class="d-flex justify-content-between">
        <a href="sell.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back
        </a>
        <button type="submit" name="confirm_pickup" class="btn-confirm" 
                <?= ($addresses->num_rows == 0) ? 'disabled' : ''; ?>>
            <i class="fas fa-check me-2"></i>Confirm Pickup
        </button>
    </div>
</form>
</div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Highlight selected address
    document.querySelectorAll('input[name="pickup_address_id"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.address-card').forEach(card => {
                card.classList.remove('selected');
            });
            this.closest('.address-card').classList.add('selected');
        });
    });

    // Prevent double submission
    document.getElementById('pickupForm').addEventListener('submit', function(e) {
        if (!document.querySelector('input[name="pickup_address_id"]:checked')) {
            e.preventDefault();
            Swal.fire({
                title: 'Error!',
                text: 'Please select a pickup address',
                icon: 'error',
                showConfirmButton: true
            });
        }
    });
</script>
</body>
</html>