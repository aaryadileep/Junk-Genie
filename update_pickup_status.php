<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Employee') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['cart_id']) || !isset($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$cart_id = $data['cart_id'];
$status = $data['status'];

$stmt = $conn->prepare("UPDATE cart SET pickup_status = ? WHERE id = ? AND assigned_employee_id = ?");
$stmt->bind_param("sii", $status, $cart_id, $_SESSION['employee_id']);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?> 