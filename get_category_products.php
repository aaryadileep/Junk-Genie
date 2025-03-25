<?php
require_once 'connect.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fetch all active categories and their products
$query = "SELECT c.category_id, c.category_name, c.description as category_description,
          p.product_name, p.description as product_description, p.price_per_pc
          FROM category c 
          LEFT JOIN products p ON c.category_id = p.category_id 
          WHERE c.is_active = 1 AND p.is_active = 1
          ORDER BY c.category_name, p.product_name";

$result = $conn->query($query);

if (!$result) {
    // Log database error
    error_log("Database Error: " . $conn->error);
    echo json_encode(['error' => 'Database query failed']);
    exit;
}

$products = [];

while ($row = $result->fetch_assoc()) {
    $products[] = [
        'category_name' => htmlspecialchars($row['category_name']),
        'product_name' => htmlspecialchars($row['product_name']),
        'description' => htmlspecialchars($row['product_description']),
        'base_price' => number_format($row['price_per_pc'], 2),
        'additional_info' => htmlspecialchars($row['category_description'])
    ];
}

// Log the output for debugging
error_log("Products JSON: " . json_encode($products));

header('Content-Type: application/json');
echo json_encode($products); 