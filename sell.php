<?php
session_start();
require_once 'connect.php';

// Fetch categories and products
$categories = $conn->query("SELECT * FROM category WHERE is_active = 1");
$products = $conn->query("SELECT * FROM products WHERE is_active = 1");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
    
    $user_id = $_SESSION['user_id'];
    $pickup_date = $_POST['pickup_date']; // Get pickup date from the form
    
    // Create a new cart entry
    $stmt = $conn->prepare("INSERT INTO cart (user_id, pickup_address, pickup_date, pickup_time) VALUES (?, '', ?, CURTIME())");
    $stmt->bind_param("is", $user_id, $pickup_date);
    $stmt->execute();
    $cart_id = $conn->insert_id;

    // Process each uploaded item
    foreach ($_POST['product_id'] as $key => $product_id) {
        $description = $_POST['description'][$key];

        // Handle image upload
        $imageName = time() . '_' . $_FILES['image']['name'][$key];
        $imagePath = "uploads/cart/" . $imageName;
        
        if (!file_exists("uploads/cart/")) {
            mkdir("uploads/cart/", 0777, true);
        }
        
        move_uploaded_file($_FILES['image']['tmp_name'][$key], $imagePath);

        // Insert item into cart_items
        $stmt = $conn->prepare("INSERT INTO cart_items (cart_id, product_id, image, description) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $cart_id, $product_id, $imagePath, $description);
        $stmt->execute();
    }

    echo "<script>
            Swal.fire({
                title: 'Success!',
                text: 'Items added to cart',
                icon: 'success',
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                window.location.href='cart.php';
            });
          </script>";
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
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .container {
            max-width: 1000px;
            margin-bottom: 20px;
        }
        .sell-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid #dee2e6;
            transition: all 0.3s ease;
        }
        .item:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 8px;
        }
        .form-control, .form-select {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #dee2e6;
        }
        .btn-remove {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        .btn-remove:hover {
            background: #c82333;
            transform: translateY(-1px);
        }
        .btn-add {
            background: #28a745;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-add:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        .btn-proceed {
            background: #007bff;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-proceed:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }
        .preview-image {
            max-width: 150px;
            border-radius: 8px;
            margin-top: 10px;
        }
        .loading-spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-left: 10px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .error-message {
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="sell-card">
            <h2 class="mb-4">Sell Your E-Waste</h2>
            <form action="" method="POST" enctype="multipart/form-data">
                <div id="items-container">
                    <div class="item">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category</label>
                                <select class="form-select category" name="category[]" required>
                                    <option value="">Select Category</option>
                                    <?php while ($row = $categories->fetch_assoc()): ?>
                                        <option value="<?= htmlspecialchars($row['category_id']); ?>">
                                            <?= htmlspecialchars($row['category_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Product</label>
                                <select class="form-select product" name="product_id[]" required>
                                    <option value="">Select Product</option>
                                </select>
                                <div class="loading-spinner" style="display: none;"></div>
                                <div class="error-message" style="display: none;">Error loading products</div>
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
                        <i class="fas fa-shopping-cart me-1"></i>Proceed to Cart
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function addItem() {
            let container = document.getElementById('items-container');
            let newItem = document.querySelector('.item').cloneNode(true);
            
            // Clear values
            newItem.querySelectorAll('select, textarea, input[type="file"]').forEach(input => {
                input.value = '';
            });
            newItem.querySelector('.image-preview').innerHTML = '';
            
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
        document.addEventListener('change', function(event) {
            if (event.target.classList.contains('category')) {
                const productSelect = event.target.closest('.item').querySelector('.product');
                const loadingSpinner = event.target.closest('.item').querySelector('.loading-spinner');
                const errorMessage = event.target.closest('.item').querySelector('.error-message');
                const categoryId = event.target.value;

                // Show loading indicator
                productSelect.innerHTML = '<option value="">Loading...</option>';
                loadingSpinner.style.display = 'inline-block';
                errorMessage.style.display = 'none';

                fetch('get_products.php?category_id=' + categoryId)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.text();
                    })
                    .then(html => {
                        productSelect.innerHTML = html;
                        loadingSpinner.style.display = 'none';
                    })
                    .catch(error => {
                        console.error('Error fetching products:', error);
                        productSelect.innerHTML = '<option value="">Error loading products</option>';
                        loadingSpinner.style.display = 'none';
                        errorMessage.style.display = 'block';
                    });
            }
        });
    </script>
</body>
</html>