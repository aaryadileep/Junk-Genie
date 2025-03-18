<?php
include 'connect.php';
$category_id = $_POST['category_id'];
$productQuery = "SELECT * FROM products WHERE category_id = '$category_id' AND is_active = 1";
$productResult = mysqli_query($conn, $productQuery);

echo '<option value="">Select Product</option>';
while ($row = mysqli_fetch_assoc($productResult)) {
    echo "<option value='{$row['product_id']}'>{$row['product_name']}</option>";
}
?>
