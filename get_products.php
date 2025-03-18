<?php
require_once 'connect.php';

$category_id = $_GET['category_id'];

$products = $conn->query("SELECT * FROM products WHERE category_id = $category_id AND is_active = 1");

$options = '<option value="">Select Product</option>';
while ($row = $products->fetch_assoc()) {
    $options .= '<option value="' . $row['product_id'] . '">' . htmlspecialchars($row['product_name']) . '</option>';
}

echo $options;
?>