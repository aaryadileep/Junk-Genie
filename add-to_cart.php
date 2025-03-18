<?php
session_start();
include 'connect.php'; // Database connection

if (isset($_POST['category']) && isset($_POST['product']) && isset($_POST['date']) && isset($_POST['time'])) {
    $user_id = $_SESSION['user_id'] ?? 1; // Replace with actual user ID logic
    $category_id = $_POST['category'];
    $product_id = $_POST['product'];
    $description = $_POST['description'];
    $date = $_POST['date'];
    $time = $_POST['time'];

    // Handle image upload
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $target_file);
        $image = $target_file;
    }

    // Insert into cart
    $query = "INSERT INTO cart (user_id, category_id, product_id, description, pickup_date, pickup_time, image) 
              VALUES ('$user_id', '$category_id', '$product_id', '$description', '$date', '$time', '$image')";
    if (mysqli_query($conn, $query)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add to cart']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
}
?>