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
        $price_per_kg = trim($_POST['price_per_kg']);

        $stmt = $conn->prepare("INSERT INTO products (category_id, product_name, description, price_per_kg) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issd", $category_id, $product_name, $description, $price_per_kg);
        
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
    <title>Category Management | JunkGenie Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3a86ff;
            --secondary-color: #8338ec;
            --success-color: #06d6a0;
            --danger-color: #ef476f;
            --warning-color: #ffd166;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --border-radius: 10px;
            --box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f7f9fc;
            color: #333;
            min-height: 100vh;
            display: flex;
        }
        
        .main-content {
            flex: 1;
            padding: 2rem;
            margin-left: 280px;
            transition: margin-left 0.3s;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        .header h4 {
            font-weight: 600;
            color: var(--dark-color);
            display: flex;
            align-items: center;
        }
        
        .header i {
            color: var(--primary-color);
        }
        
        .page-subtitle {
            color: #6c757d;
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
        }
        
        .card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border: none;
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid #e9ecef;
            padding: 1.25rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h5 {
            font-weight: 600;
            margin: 0;
            color: var(--dark-color);
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 6px;
            padding: 0.5rem 1.25rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: #2970e0;
            border-color: #2970e0;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(58, 134, 255, 0.3);
        }
        
        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
            border-radius: 6px;
            padding: 0.5rem 1.25rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-success:hover {
            background-color: #05b689;
            border-color: #05b689;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(6, 214, 160, 0.3);
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            border-radius: 4px;
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(58, 134, 255, 0.2);
        }
        
        .table-container {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .table {
            width: 100%;
            margin-bottom: 0;
        }
        
        .table th {
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            background-color: #f8f9fa;
        }
        
        .table td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #e9ecef;
        }
        
        .table tr:hover {
            background-color: #f8f9fb;
        }
        
        .status-badge {
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 600;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-active {
            background-color: rgba(6, 214, 160, 0.1);
            color: var(--success-color);
        }
        
        .status-inactive {
            background-color: rgba(239, 71, 111, 0.1);
            color: var(--danger-color);
        }
        
        .status-toggle {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
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
            transition: .4s;
            border-radius: 24px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background-color: var(--success-color);
        }
        
        input:focus + .slider {
            box-shadow: 0 0 1px var(--success-color);
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        .modal-content {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .modal-header {
            border-bottom: 1px solid #e9ecef;
            padding: 1.5rem;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        
        .form-control, .form-select {
            border-radius: 6px;
            border: 1px solid #ced4da;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(58, 134, 255, 0.25);
        }
        
        .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter {
            margin-bottom: 1.5rem;
        }
        
        .dataTables_wrapper .dataTables_length select, .dataTables_wrapper .dataTables_filter input {
            border-radius: 6px;
            border: 1px solid #ced4da;
            padding: 0.375rem 0.75rem;
        }
        
        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(58, 134, 255, 0.25);
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border-radius: 6px;
            padding: 0.375rem 0.75rem;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white !important;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: rgba(58, 134, 255, 0.1);
            border-color: rgba(58, 134, 255, 0.1);
            color: var(--primary-color) !important;
        }
        
        .alert {
            border-radius: var(--border-radius);
            border: none;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }
        
        .alert-success {
            background-color: rgba(6, 214, 160, 0.1);
            color: var(--success-color);
        }
        
        .alert-danger {
            background-color: rgba(239, 71, 111, 0.1);
            color: var(--danger-color);
        }
        
        .btn-close {
            opacity: 0.5;
            transition: opacity 0.3s ease;
        }
        
        .btn-close:hover {
            opacity: 1;
        }
        
        .stats-card {
            display: flex;
            align-items: center;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 1.5rem;
            margin-bottoheight: 100%;
            transition: all 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .stats-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 60px;
            height: 60px;
            border-radius: 12px;
            margin-right: 1.5rem;
            font-size: 1.5rem;
        }
        
        .stats-icon.primary {
            background-color: rgba(58, 134, 255, 0.1);
            color: var(--primary-color);
        }
        
        .stats-icon.success {
            background-color: rgba(6, 214, 160, 0.1);
            color: var(--success-color);
        }
        
        .stats-info h3 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: var(--dark-color);
        }
        
        .stats-info p {
            color: #6c757d;
            margin-bottom: 0;
        }
        
        .tab-content {
            padding: 1.5rem 0;
        }
        
        .nav-tabs {
            border-bottom: 1px solid #e9ecef;
            margin-bottom: 1.5rem;
        }
        
        .nav-tabs .nav-link {
            border: none;
            border-bottom: 2px solid transparent;
            color: #6c757d;
            font-weight: 500;
            padding: 0.75rem 1.25rem;
            transition: all 0.3s ease;
        }
        
        .nav-tabs .nav-link:hover {
            color: var(--primary-color);
            border-color: transparent;
        }
        
        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            border-color: var(--primary-color);
            background-color: transparent;
        }
        
        .page-title-wrapper {
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }
        
        .breadcrumb {
            background-color: transparent;
            padding: 0;
            margin-bottom: 0;
        }
        
        .breadcrumb-item {
            font-size: 0.875rem;
        }
        
        .breadcrumb-item a {
            color: #6c757d;
            text-decoration: none;
        }
        
        .breadcrumb-item a:hover {
            color: var(--primary-color);
        }
        
        .breadcrumb-item.active {
            color: var(--primary-color);
        }
        
        .breadcrumb-item + .breadcrumb-item::before {
            content: ">";
        }
        
        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 6px;
        }
        
        .action-buttons {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .action-buttons .btn {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }
        
        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
                padding: 1.5rem;
            }
            
            .stats-card {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="page-title-wrapper">
            <h1 class="page-title">Category Management</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Category Management</li>
                </ol>
            </nav>
        </div>

        <!-- Stats Cards -->
        <div class="row">
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
                <div class="card">
                    <div class="card-header">
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
                <div class="card">
                    <div class="card-header">
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
                                    <th>Price per Kg</th>
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
                                    <td>₹<?php echo number_format($product_row['price_per_kg'], 2); ?></td>
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
                                // Reset the pointer of the result set
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
                            <label class="form-label">Price per Kg (₹)</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="0.01" class="form-control" name="price_per_kg" required>
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

    <!-- Edit Category Modal (Placeholder) -->
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
                        <input type="hidden" name="edit_category_id" id="edit_category_id">
                        <div class="mb-3">
                            <label class="form-label">Category Name</label>
                            <input type="text" class="form-control" name="edit_category_name" id="edit_category_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="edit_description" id="edit_description" rows="3" required></textarea>
                        </div>
                        <button type="submit" name="update_category" class="btn btn-primary w-100">
                            <i class="fas fa-save me-2"></i>Update Category
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    .php
<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addProductModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>Add New Product
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addProductForm" method="POST">
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php
                            $result->data_seek(0);
                            while($row = $result->fetch_assoc()): ?>
                                <option value="<?php echo $row['category_id']; ?>">
                                    <?php echo htmlspecialchars($row['category_name']); ?>
                                </option>
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
                        <label class="form-label">Price per Kg (₹)</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" step="0.01" min="0" class="form-control" name="price_per_kg" required>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTables with responsive design
            $('#categoriesTable').DataTable({
                "order": [[0, "desc"]],
                "pageLength": 10,
                "responsive": true,
                "language": {
                    "search": "Search:",
                    "lengthMenu": "Show _MENU_ entries",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "infoEmpty": "Showing 0 to 0 of 0 entries",
                    "infoFiltered": "(filtered from _MAX_ total entries)",
                    "paginate": {
                        "first": "First",
                        "last": "Last",
                        "next": "<i class='fas fa-chevron-right'></i>",
                        "previous": "<i class='fas fa-chevron-left'></i>"
                    },
                }
            });

            $('#productsTable').DataTable({
                "order": [[0, "desc"]],
                "pageLength": 10,
                "responsive": true,
                "language": {
                    "search": "Search:",
                    "lengthMenu": "Show _MENU_ entries",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "infoEmpty": "Showing 0 to 0 of 0 entries",
                    "infoFiltered": "(filtered from _MAX_ total entries)",
                    "paginate": {
                        "first": "First",
                        "last": "Last",
                        "next": "<i class='fas fa-chevron-right'></i>",
                        "previous": "<i class='fas fa-chevron-left'></i>"
                    },
                }
            });

            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);

            // Save active tab to session storage
            $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
                localStorage.setItem('activeTab', $(e.target).attr('href'));
            });

            // Check for active tab in session storage
            var activeTab = localStorage.getItem('activeTab');
            if(activeTab){
                $('#managementTabs a[href="' + activeTab + '"]').tab('show');
            }

            // Form validations
            $('#addCategoryForm').on('submit', function() {
                var categoryName = $('input[name="category_name"]').val().trim();
                if (categoryName === '') {
                    alert('Please enter a category name');
                    return false;
                }
                return true;
            });

            $('#addProductForm').on('submit', function() {
                var productName = $('input[name="product_name"]').val().trim();
                var price = $('input[name="price_per_kg"]').val().trim();
                
                if (productName === '') {
                    alert('Please enter a product name');
                    return false;
                }
                
                if (price === '' || isNaN(price) || parseFloat(price) <= 0) {
                    alert('Please enter a valid price');
                    return false;
                }
                
                return true;
            });
        });

        // Toggle category status function
        function toggleStatus(categoryId, status) {
            if (!confirm(status ? 'Activate this category?' : 'Deactivate this category?')) {
                // Reset the checkbox to its previous state if the user cancels
                const checkbox = event.target;
                checkbox.checked = !checkbox.checked;
                return;
            }
            
            fetch('categorymanagement.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `toggle_status=1&category_id=${categoryId}&new_status=${status ? 1 : 0}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show a toast notification
                    showToast(status ? 'Category activated successfully' : 'Category deactivated successfully', 'success');
                    
                    // Reload only the table data instead of the whole page
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showToast('Failed to update status: ' + data.message, 'error');
                    // Reset the checkbox to its previous state
                    const checkbox = event.target;
                    checkbox.checked = !checkbox.checked;
                }
            })
            .catch(error => {
                showToast('An error occurred: ' + error, 'error');
                // Reset the checkbox to its previous state
                const checkbox = event.target;
                checkbox.checked = !checkbox.checked;
            });
        }

        // Toggle product status function
        function toggleProductStatus(productId, status) {
            if (!confirm(status ? 'Activate this product?' : 'Deactivate this product?')) {
                // Reset the checkbox to its previous state if the user cancels
                const checkbox = event.target;
                checkbox.checked = !checkbox.checked;
                return;
            }
            
            fetch('categorymanagement.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `toggle_product_status=1&product_id=${productId}&new_status=${status ? 1 : 0}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show a toast notification
                    showToast(status ? 'Product activated successfully' : 'Product deactivated successfully', 'success');
                    
                    // Reload only the table data instead of the whole page
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showToast('Failed to update status: ' + data.message, 'error');
                    // Reset the checkbox to its previous state
                    const checkbox = event.target;
                    checkbox.checked = !checkbox.checked;
                }
            })
            .catch(error => {
                showToast('An error occurred: ' + error, 'error');
                // Reset the checkbox to its previous state
                const checkbox = event.target;
                checkbox.checked = !checkbox.checked;
            });
        }

        // Edit category placeholder function - To be implemented
        function editCategory(categoryId) {
            // Placeholder for edit functionality
            $('#editCategoryModal').modal('show');
            // Here you would typically fetch the category data and populate the form
            $('#edit_category_id').val(categoryId);
        }

        // Edit product placeholder function - To be implemented
        function editProduct(productId) {
            // Placeholder for edit functionality
            $('#editProductModal').modal('show');
            // Here you would typically fetch the product data and populate the form
            $('#edit_product_id').val(productId);
        }

        // Toast notification function
        function showToast(message, type = 'info') {
            // Create toast container if it doesn't exist
            if (!document.getElementById('toast-container')) {
                const toastContainer = document.createElement('div');
                toastContainer.id = 'toast-container';
                toastContainer.style.position = 'fixed';
                toastContainer.style.top = '20px';
                toastContainer.style.right = '20px';
                toastContainer.style.zIndex = '9999';
                document.body.appendChild(toastContainer);
            }
            
            // Create toast element
            const toast = document.createElement('div');
            toast.className = 'toast';
            toast.style.minWidth = '250px';
            toast.style.backgroundColor = type === 'success' ? '#06d6a0' : (type === 'error' ? '#ef476f' : '#3a86ff');
            toast.style.color = 'white';
            toast.style.padding = '15px';
            toast.style.borderRadius = '10px';
            toast.style.marginBottom = '10px';
            toast.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.15)';
            toast.style.animation = 'fadeIn 0.3s, fadeOut 0.3s 2.7s forwards';
            toast.style.display = 'flex';
            toast.style.alignItems = 'center';
            toast.style.justifyContent = 'space-between';
                        // Add message and close button to toast
                        toast.innerHTML = `
                <div style="display: flex; align-items: center;">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} me-2"></i>
                    <span>${message}</span>
                </div>
                <button onclick="this.parentElement.remove()" style="background: none; border: none; color: white; cursor: pointer; padding: 0 0 0 10px;">
                    <i class="fas fa-times"></i>
                </button>
            `;

            // Add toast to container
            document.getElementById('toast-container').appendChild(toast);

            // Remove toast after 3 seconds
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }

        // Add CSS animations for toast
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeIn {
                from { opacity: 0; transform: translateX(100%); }
                to { opacity: 1; transform: translateX(0); }
            }
            @keyframes fadeOut {
                from { opacity: 1; transform: translateX(0); }
                to { opacity: 0; transform: translateX(100%); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
<script>
    // Handle category form submission
    $('#addCategoryForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('categorymanagement.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Category added successfully', 'success');
                $('#addCategoryModal').modal('hide');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast(data.message || 'Failed to add category', 'error');
            }
        })
        .catch(error => {
            showToast('An error occurred', 'error');
        });
    });

    // Handle product form submission
    $('#addProductForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('categorymanagement.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Product added successfully', 'success');
                $('#addProductModal').modal('hide');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast(data.message || 'Failed to add product', 'error');
            }
        })
        .catch(error => {
            showToast('An error occurred', 'error');
        });
    });

    // Reset form when modal is closed
    $('.modal').on('hidden.bs.modal', function () {
        $(this).find('form')[0].reset();
    });

    // Add loading state to buttons
    $('form').on('submit', function() {
        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>Processing...').prop('disabled', true);
    });
    <script>
$(document).ready(function() {
    // Initialize Bootstrap modals
    var addProductModal = new bootstrap.Modal(document.getElementById('addProductModal'));
    
    // Add Product button click handler
    $('.btn-add-product').click(function() {
        addProductModal.show();
    });
    
    // Form submission handler
    $('#addProductForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('categorymanagement.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Product added successfully', 'success');
                addProductModal.hide();
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast(data.message || 'Failed to add product', 'error');
            }
        })
        .catch(error => {
            showToast('An error occurred', 'error');
        });
    });
});
</script>