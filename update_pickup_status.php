<?php
session_start();
require_once 'connect.php';

// Check if user is logged in and is an employee
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Employee') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get the raw POST data
$data = json_decode(file_get_contents('php://input'), true);

// Debugging: Log the received data
error_log('Received data: ' . print_r($data, true));

// Extract cart_id and status
$cart_id = $data['cart_id'] ?? null;
$status = $data['status'] ?? null;

if (!$cart_id || !$status) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit();
}

try {
    // Begin transaction
    $conn->begin_transaction();

    // Update the pickup status in cart table
    $update_cart = "UPDATE cart SET pickup_status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_cart);
    $stmt->bind_param("si", $status, $cart_id); // Bind status and cart_id
    
    if ($stmt->execute()) {
        // Commit the transaction
        $conn->commit();
        echo json_encode(['success' => true]);
    } else {
        // Rollback on failure
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }
} catch (Exception $e) {
    // Rollback on exception
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>