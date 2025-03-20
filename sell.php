<?php
session_start();
require_once 'connect.php';

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch active categories
$categories = $conn->query("SELECT * FROM category WHERE is_active = 1");

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $pickup_date = $_POST['pickup_date'];

    // First try to get default address
    $addressStmt = $conn->prepare("SELECT address_id FROM user_addresses 
                                 WHERE user_id = ? AND is_default = 1 AND is_active = 1
                                 LIMIT 1");
    $addressStmt->bind_param("i", $user_id);
    $addressStmt->execute();
    $addressResult = $addressStmt->get_result();
    $address = $addressResult->fetch_assoc();
    
    // If no default address, get any active address
    if (!$address) {
        $addressStmt = $conn->prepare("SELECT address_id FROM user_addresses 
                                     WHERE user_id = ? AND is_active = 1
                                     LIMIT 1");
        $addressStmt->bind_param("i", $user_id);
        $addressStmt->execute();
        $addressResult = $addressStmt->get_result();
        $address = $addressResult->fetch_assoc();
    }

    // If still no address found, redirect to add address page
    if (!$address) {
        echo "<script>
                Swal.fire({
                    title: 'No Address Found',
                    text: 'Please add an address before creating an order',
                    icon: 'warning',
                    confirmButtonText: 'Add Address'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'addresses.php';
                    }
                });
              </script>";
        exit();
    }

    // Create a new cart entry with address_id and pickup_status
    $stmt = $conn->prepare("INSERT INTO cart (user_id, pickup_date, address_id, pickup_status) 
                          VALUES (?, ?, ?, ?)");
    $pickup_status = 'Pending';
    $stmt->bind_param("isss", $user_id, $pickup_date, $address['address_id'], $pickup_status);
    
    if (!$stmt->execute()) {
        die("Error creating cart: " . $stmt->error);
    }
    $cart_id = $conn->insert_id;

    // Verify the cart was created with the correct status
    $verifyStmt = $conn->prepare("SELECT pickup_status FROM cart WHERE id = ?");
    $verifyStmt->bind_param("i", $cart_id);
    $verifyStmt->execute();
    $result = $verifyStmt->get_result();
    $cart = $result->fetch_assoc();
    
    if (!$cart || $cart['pickup_status'] !== 'Pending') {
        // Try to update the status if it wasn't set correctly
        $updateStmt = $conn->prepare("UPDATE cart SET pickup_status = ? WHERE id = ?");
        $updateStmt->bind_param("si", $pickup_status, $cart_id);
        if (!$updateStmt->execute()) {
            die("Error updating cart status: " . $updateStmt->error);
        }
    }

    // Process each uploaded item
    foreach ($_POST['product_id'] as $key => $product_id) {
        $description = $_POST['description'][$key];

        // Validate product ID and description
        if (empty($product_id) || empty($description)) {
            die("Product ID and description are required for all items.");
        }

        // Handle image upload
        if (!isset($_FILES['image']['name'][$key])) {
            die("Image is required for all items.");
        }

        $imageName = time() . '_' . basename($_FILES['image']['name'][$key]);
        $imagePath = "uploads/cart/" . $imageName;

        // Create uploads directory if it doesn't exist
        if (!file_exists("uploads/cart/")) {
            mkdir("uploads/cart/", 0777, true);
        }

        // Move uploaded file to the target directory
        if (!move_uploaded_file($_FILES['image']['tmp_name'][$key], $imagePath)) {
            die("Error uploading image.");
        }

        // Insert item into cart_items
        $stmt = $conn->prepare("INSERT INTO cart_items (cart_id, product_id, image, description) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $cart_id, $product_id, $imagePath, $description);
        if (!$stmt->execute()) {
            die("Error adding item to cart: " . $stmt->error);
        }
    }

    // Redirect to confirm_pickup.php with cart_id
    header("Location: confirm_pickup.php?cart_id=" . $cart_id);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sell E-Waste | JunkGenie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; padding-top: 20px; }
        .container { max-width: 1000px; margin-bottom: 20px; }
        .sell-card { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 2px 15px rgba(0,0,0,0.1); margin-bottom: 20px; margin-top: 75px; }
        .item { background: #f8f9fa; border-radius: 10px; padding: 20px; margin-bottom: 15px; border: 1px solid #dee2e6; transition: all 0.3s ease; }
        .item:hover { box-shadow: 0 5px 15px rgba(0,0,0,0.1); transform: translateY(-2px); }
        .form-label { font-weight: 500; color: #495057; margin-bottom: 8px; }
        .form-control, .form-select { border-radius: 8px; padding: 10px 15px; border: 1px solid #dee2e6; }
        .btn-remove { background: #dc3545; color: white; border: none; padding: 8px 15px; border-radius: 6px; transition: all 0.3s ease; }
        .btn-remove:hover { background: #c82333; transform: translateY(-1px); }
        .btn-add { background: #28a745; color: white; border: none; padding: 12px 25px; border-radius: 8px; font-weight: 500; transition: all 0.3s ease; }
        .btn-add:hover { background: #218838; transform: translateY(-2px); }
        .btn-proceed { background: #007bff; color: white; border: none; padding: 15px 30px; border-radius: 8px; font-weight: 500; transition: all 0.3s ease; }
        .btn-proceed:hover { background: #0056b3; transform: translateY(-2px); }
        .preview-image { max-width: 150px; border-radius: 8px; margin-top: 10px; }
        .loading-spinner { border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; width: 20px; height: 20px; animation: spin 1s linear infinite; display: inline-block; margin-left: 10px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .error-message { color: #dc3545; font-size: 0.875em; margin-top: 5px; }
        .loading-spinner {
            display: none;
            position: absolute;
            right: 30px;
            top: 50%;
            transform: translateY(-50%);
        }
        .product-select:disabled {
            background-color: #f8f9fa;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="sell-card">
            <h2 class="mb-4">Sell Your E-Waste</h2>
            <form action="sell.php" method="POST" enctype="multipart/form-data">
                <div id="items-container">
                    <div class="item">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category</label>
                                <select class="form-select category-select" required>
                                    <option value="">Select Category</option>
                                    <?php 
                                    $categories->data_seek(0);
                                    while ($category = $categories->fetch_assoc()): 
                                    ?>
                                        <option value="<?= $category['category_id'] ?>">
                                            <?= htmlspecialchars($category['category_name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Product</label>
                                <select class="form-select product-select" name="product_id[]" required>
                                    <option value="">Select Category First</option>
                                </select>
                                <div class="loading-spinner" style="display: none;">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </div>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description[]" rows="3" required 
                                          placeholder="Describe your item's condition and details"></textarea>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">Image</label>
                                <input type="file" class="form-control" name="image[]" required 
                                       accept="image/*" onchange="previewImage(this)">
                                <div class="image-preview mt-2"></div>
                            </div>
                            
                            <div class="col-12 text-end">
                                <button type="button" class="btn-remove" onclick="removeItem(this)">
                                    <i class="fas fa-trash me-1"></i>Remove
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pickup Date Field -->
                <div class="row mt-4">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Pickup Date</label>
                        <input type="date" class="form-control" name="pickup_date" required 
                               min="<?= date('Y-m-d'); ?>" value="<?= date('Y-m-d'); ?>">
                    </div>
                </div>
                
                <div class="d-flex justify-content-between mt-4">
                    <button type="button" class="btn-add" onclick="addItem()">
                        <i class="fas fa-plus me-1"></i>Add Another Item
                    </button>
                    <button type="submit" class="btn-proceed">
                        <i class="fas fa-shopping-cart me-1"></i>Confirm Pickup
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function addItem() {
            const container = document.getElementById('items-container');
            const newItem = container.querySelector('.item').cloneNode(true);
            
            // Clear values
            newItem.querySelectorAll('select, textarea, input[type="file"]').forEach(input => {
                input.value = '';
            });
            newItem.querySelector('.image-preview').innerHTML = '';
            
            // Reinitialize category change event
            const categorySelect = newItem.querySelector('.category-select');
            categorySelect.addEventListener('change', function() {
                const productSelect = this.closest('.row').querySelector('.product-select');
                const loadingSpinner = this.closest('.row').querySelector('.loading-spinner');
                
                loadingSpinner.style.display = 'block';
                productSelect.disabled = true;
                
                fetch('get_products.php?category_id=' + this.value)
                    .then(response => response.text())
                    .then(html => {
                        productSelect.innerHTML = html;
                        productSelect.disabled = false;
                        loadingSpinner.style.display = 'none';
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        productSelect.innerHTML = '<option value="">Error loading products</option>';
                        productSelect.disabled = false;
                        loadingSpinner.style.display = 'none';
                    });
            });
            
            container.appendChild(newItem);
        }

        function removeItem(button) {
            if (document.querySelectorAll('.item').length > 1) {
                button.closest('.item').remove();
            } else {
                Swal.fire({
                    title: 'Cannot Remove',
                    text: 'You need at least one item',
                    icon: 'warning'
                });
            }
        }

        function previewImage(input) {
            const preview = input.parentElement.querySelector('.image-preview');
            preview.innerHTML = '';
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'preview-image';
                    preview.appendChild(img);
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Update products when category changes
        document.querySelectorAll('.category-select').forEach(select => {
            select.addEventListener('change', function() {
                const productSelect = this.closest('.row').querySelector('.product-select');
                const loadingSpinner = this.closest('.row').querySelector('.loading-spinner');
                
                // Show loading spinner
                loadingSpinner.style.display = 'block';
                productSelect.disabled = true;
                
                // Fetch products for selected category
                fetch('get_products.php?category_id=' + this.value)
                    .then(response => response.text())
                    .then(html => {
                        productSelect.innerHTML = html;
                        productSelect.disabled = false;
                        loadingSpinner.style.display = 'none';
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        productSelect.innerHTML = '<option value="">Error loading products</option>';
                        productSelect.disabled = false;
                        loadingSpinner.style.display = 'none';
                    });
            });
        });
    </script>
</body>
</html>