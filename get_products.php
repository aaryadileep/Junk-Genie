<?php
require_once 'connect.php';

if (isset($_GET['category_id'])) {
    $category_id = intval($_GET['category_id']);
    
    $stmt = $conn->prepare("SELECT product_id, product_name FROM products 
                           WHERE category_id = ? AND is_active = 1 
                           ORDER BY product_name ASC");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo '<option value="">Select Product</option>';
    while ($row = $result->fetch_assoc()) {
        echo '<option value="' . $row['product_id'] . '">' . 
             htmlspecialchars($row['product_name']) . '</option>';
    }
}
?>