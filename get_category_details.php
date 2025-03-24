<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if (isset($_GET['id'])) {
    $category_id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM category WHERE category_id = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $category = $result->fetch_assoc();
    
    if ($category) {
        echo json_encode(['success' => true, 'category_id' => $category['category_id'], 'category_name' => $category['category_name'], 'description' => $category['description']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Category not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>