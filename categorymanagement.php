<?php
session_start();
require_once 'connect.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Handle category status toggle
if (isset($_POST['toggle_status'])) {
    try {
        $category_id = $_POST['category_id'];
        $new_status = $_POST['new_status'];
        
        $stmt = $conn->prepare("UPDATE category SET is_active = ? WHERE category_id = ?");
        $stmt->bind_param("ii", $new_status, $category_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            throw new Exception("Failed to update status");
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// Handle product status toggle
if (isset($_POST['toggle_product_status'])) {
    try {
        $product_id = $_POST['product_id'];
        $new_status = $_POST['new_status'];
        
        $stmt = $conn->prepare("UPDATE products SET is_active = ? WHERE product_id = ?");
        $stmt->bind_param("ii", $new_status, $product_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            throw new Exception("Failed to update status");
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// Handle adding new category
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_category'])) {
    try {
        $category_name = trim($_POST['category_name']);
        $description = trim($_POST['description']);

        $stmt = $conn->prepare("INSERT INTO category (category_name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $category_name, $description);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Category added successfully";
        } else {
            throw new Exception("Failed to add category");
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    
    header("Location: categorymanagement.php");
    exit();
}

// Handle adding new product
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_product'])) {
    try {
        $category_id = $_POST['category_id'];
        $product_name = trim($_POST['product_name']);
        $description = trim($_POST['description']);
        $price_per_pc = trim($_POST['price_per_pc']);

        $stmt = $conn->prepare("INSERT INTO products (category_id, product_name, description, price_per_pc) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issd", $category_id, $product_name, $description, $price_per_pc);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Product added successfully";
        } else {
            throw new Exception("Failed to add product");
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    
    header("Location: categorymanagement.php");
    exit();
}

// Handle updating category
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_category'])) {
    try {
        $category_id = $_POST['category_id'];
        $category_name = trim($_POST['category_name']);
        $description = trim($_POST['description']);

        $stmt = $conn->prepare("UPDATE category SET category_name = ?, description = ? WHERE category_id = ?");
        $stmt->bind_param("ssi", $category_name, $description, $category_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Category updated successfully";
        } else {
            throw new Exception("Failed to update category");
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    
    header("Location: categorymanagement.php");
    exit();
}

// Handle updating product
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_product'])) {
    try {
        $product_id = $_POST['product_id'];
        $category_id = $_POST['category_id'];
        $product_name = trim($_POST['product_name']);
        $description = trim($_POST['description']);
        $price_per_pc = trim($_POST['price_per_pc']);

        $stmt = $conn->prepare("UPDATE products SET category_id = ?, product_name = ?, description = ?, price_per_pc = ? WHERE product_id = ?");
        $stmt->bind_param("issdi", $category_id, $product_name, $description, $price_per_pc, $product_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Product updated successfully";
        } else {
            throw new Exception("Failed to update product");
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    
    header("Location: categorymanagement.php");
    exit();
}

// Fetch all categories
$query = "SELECT * FROM category ORDER BY created_at DESC";
$result = $conn->query($query);

// Fetch all products
$product_query = "SELECT p.*, c.category_name FROM products p JOIN category c ON p.category_id = c.category_id ORDER BY p.created_at DESC";
$product_result = $conn->query($product_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category and Product Management</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-green: #2ecc71;
            --secondary-green: #27ae60;
            --light-green: #d4edda;
            --dark-green: #155724;
            --background-soft: #f4f6f7;
        }

        body {
            background-color: var(--background-soft);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .sidebar {
            background-color: var(--primary-green);
            color: white;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            transition: all 0.3s ease;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }

        .sidebar-logo {
            padding: 20px;
            text-align: center;
            background-color: var(--secondary-green);
        }

        .sidebar-menu {
            padding: 20px;
        }

        .sidebar-menu a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 10px 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .sidebar-menu a:hover {
            background-color: rgba(255,255,255,0.2);
        }

        .container-fluid {
            margin-left: 280px;
            width: calc(100% - 280px);
            padding: 20px;
            transition: all 0.3s ease;
        }

        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-left: 5px solid var(--primary-green);
            transition: transform 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stats-icon {
            color: var(--primary-green);
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .status-toggle {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .status-toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: 0.4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: 0.4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: var(--primary-green);
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        .table-hover tbody tr:hover {
            background-color: var(--light-green);
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 250px;
            }

            .container-fluid {
                margin-left: 0;
                width: 100%;
            }

            .sidebar.show {
                transform: translateX(0);
            }
        }
    </style>
</head>

<body>
<?php include 'sidebar.php'; ?>
    <div class="container-fluid p-4 " >
        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-icon primary">
                        <i class="fas fa-list"></i>
                    </div>
                    <div class="stats-info">
                        <h3><?php echo $result->num_rows; ?></h3>
                        <p>Total Categories</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-icon success">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="stats-info">
                        <h3><?php echo $product_result->num_rows; ?></h3>
                        <p>Total Products</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-icon primary">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stats-info">
                        <?php
                        $active_categories = 0;
                        $result->data_seek(0);
                        while($row = $result->fetch_assoc()) {
                            if ($row['is_active']) $active_categories++;
                        }
                        ?>
                        <h3><?php echo $active_categories; ?></h3>
                        <p>Active Categories</p>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Tabs -->
        <ul class="nav nav-tabs" id="managementTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="categories-tab" data-bs-toggle="tab" data-bs-target="#categories" type="button" role="tab" aria-controls="categories" aria-selected="true">
                    <i class="fas fa-list me-2"></i>Categories
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="products-tab" data-bs-toggle="tab" data-bs-target="#products" type="button" role="tab" aria-controls="products" aria-selected="false">
                    <i class="fas fa-boxes me-2"></i>Products
                </button>
            </li>
        </ul>

        <div class="tab-content" id="managementTabsContent">
            <!-- Categories Tab -->
            <div class="tab-pane fade show active" id="categories" role="tabpanel" aria-labelledby="categories-tab">
                <div class="card mt-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-list me-2"></i>Category List</h5>
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                            <i class="fas fa-plus me-2"></i>Add New Category
                        </button>
                    </div>
                    <div class="card-body">
                        <table id="categoriesTable" class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Category Name</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Created Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $result->data_seek(0);
                                while($row = $result->fetch_assoc()): 
                                ?>
                                <tr>
                                    <td><?php echo $row['category_id']; ?></td>
                                    <td>
                                        <span class="fw-medium"><?php echo htmlspecialchars($row['category_name']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $row['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-sm btn-outline-primary" onclick="editCategory(<?php echo $row['category_id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <label class="status-toggle">
                                                <input type="checkbox" 
                                                    onchange="toggleStatus(<?php echo $row['category_id']; ?>, this.checked)"
                                                    <?php echo $row['is_active'] ? 'checked' : ''; ?>>
                                                <span class="slider"></span>
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Products Tab -->
            <div class="tab-pane fade" id="products" role="tabpanel" aria-labelledby="products-tab">
                <div class="card mt-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-boxes me-2"></i>Product List</h5>
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addProductModal">
                            <i class="fas fa-plus me-2"></i>Add New Product
                        </button>
                    </div>
                    <div class="card-body">
                        <table id="productsTable" class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Product Name</th>
                                    <th>Description</th>
                                    <th>Price per pc</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Created Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $product_result->data_seek(0);
                                while($product_row = $product_result->fetch_assoc()): 
                                ?>
                                <tr>
                                    <td><?php echo $product_row['product_id']; ?></td>
                                    <td>
                                        <span class="fw-medium"><?php echo htmlspecialchars($product_row['product_name']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($product_row['description']); ?></td>
                                    <td>₹<?php echo number_format($product_row['price_per_pc'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($product_row['category_name']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $product_row['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo $product_row['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($product_row['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-sm btn-outline-primary" onclick="editProduct(<?php echo $product_row['product_id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <label class="status-toggle">
                                                <input type="checkbox" 
                                                    onchange="toggleProductStatus(<?php echo $product_row['product_id']; ?>, this.checked)"
                                                    <?php echo $product_row['is_active'] ? 'checked' : ''; ?>>
                                                <span class="slider"></span>
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2 text-success"></i>
                        Add New Category
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addCategoryForm" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Category Name</label>
                            <input type="text" class="form-control" name="category_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" required></textarea>
                        </div>
                        <button type="submit" name="add_category" class="btn btn-success w-100">
                            <i class="fas fa-plus-circle me-2"></i>Add Category
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2 text-success"></i>
                        Add New Product
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addProductForm" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category_id" required>
                                <?php
                                $result->data_seek(0);
                                while($row = $result->fetch_assoc()): ?>
                                    <option value="<?php echo $row['category_id']; ?>"><?php echo htmlspecialchars($row['category_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Product Name</label>
                            <input type="text" class="form-control" name="product_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price per Pc (₹)</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="0.01" class="form-control" name="price_per_pc" required>
                            </div>
                        </div>
                        <button type="submit" name="add_product" class="btn btn-success w-100">
                            <i class="fas fa-plus-circle me-2"></i>Add Product
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2 text-primary"></i>
                        Edit Category
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editCategoryForm" method="POST">
                        <input type="hidden" name="category_id" id="edit_category_id">
                        <div class="mb-3">
                            <label class="form-label">Category Name</label>
                            <input type="text" class="form-control" name="category_name" id="edit_category_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="3" required></textarea>
                        </div>
                        <button type="submit" name="update_category" class="btn btn-primary w-100">
                            <i class="fas fa-save me-2"></i>Update Category
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2 text-primary"></i>
                        Edit Product
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editProductForm" method="POST">
                        <input type="hidden" name="product_id" id="edit_product_id">
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category_id" id="edit_product_category_id" required>
                                <?php
                                $result->data_seek(0);
                                while($row = $result->fetch_assoc()): ?>
                                    <option value="<?php echo $row['category_id']; ?>"><?php echo htmlspecialchars($row['category_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Product Name</label>
                            <input type="text" class="form-control" name="product_name" id="edit_product_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="edit_product_description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price per Pc (₹)</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="0.01" class="form-control" name="price_per_pc" id="edit_product_price" required>
                            </div>
                        </div>
                        <button type="submit" name="update_product" class="btn btn-primary w-100">
                            <i class="fas fa-save me-2"></i>Update Product
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
    <!-- Custom JS -->
    <script>
        // Function to toggle category status
        function toggleStatus(categoryId, isChecked) {
            const newStatus = isChecked ? 1 : 0;
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `toggle_status=true&category_id=${categoryId}&new_status=${newStatus}`,
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert(data.message || "Failed to update status");
                }
            })
            .catch(error => console.error('Error:', error));
        }

        // Function to toggle product status
        function toggleProductStatus(productId, isChecked) {
            const newStatus = isChecked ? 1 : 0;
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `toggle_product_status=true&product_id=${productId}&new_status=${newStatus}`,
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert(data.message || "Failed to update status");
                }
            })
            .catch(error => console.error('Error:', error));
        }

        // Function to populate and open the edit category modal
        function editCategory(categoryId) {
            fetch(`get_category_details.php?id=${categoryId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('edit_category_id').value = data.category.category_id;
                        document.getElementById('edit_category_name').value = data.category.category_name;
                        document.getElementById('edit_description').value = data.category.description;
                        new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
                    } else {
                        alert(data.message || "Failed to fetch category details");
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        // Function to populate and open the edit product modal
        function editProduct(productId) {
            fetch(`get_product_details.php?id=${productId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('edit_product_id').value = data.product.product_id;
                        document.getElementById('edit_product_category_id').value = data.product.category_id;
                        document.getElementById('edit_product_name').value = data.product.product_name;
                        document.getElementById('edit_product_description').value = data.product.description;
                        document.getElementById('edit_product_price').value = data.product.price_per_pc;
                        new bootstrap.Modal(document.getElementById('editProductModal')).show();
                    } else {
                        alert(data.message || "Failed to fetch product details");
                    }
                })
                .catch(error => console.error('Error:', error));
        }
    </script>
</body>
</html>