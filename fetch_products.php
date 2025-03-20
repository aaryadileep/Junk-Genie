<?php
// Include the database connection file
include 'connect.php';

// Check if category_id is provided via POST
if (!isset($_POST['category_id'])) {
    die("Category ID is required.");
}

// Sanitize the input to prevent SQL injection
$category_id = intval($_POST['category_id']);

// Prepare and execute the query
$productQuery = "SELECT * FROM products WHERE category_id = ? AND is_active = 1";
$stmt = $conn->prepare($productQuery);
$stmt->bind_param("i", $category_id);
$stmt->execute();
$productResult = $stmt->get_result();

// Check if there are any products
if ($productResult->num_rows > 0) {
    // Output the default option
    echo '<option value="">Select Product</option>';

    // Loop through the products and output options
    while ($row = $productResult->fetch_assoc()) {
        echo "<option value='{$row['product_id']}'>{$row['product_name']}</option>";
    }
} else {
    // No products found for the given category
    echo '<option value="">No products found</option>';
}

// Close the statement and connection
$stmt->close();
$conn->close();
?>