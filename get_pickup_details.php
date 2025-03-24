<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$cart_id = $data['cart_id'];

$query = "SELECT 
    c.id, 
    u.fullname AS customer_name,
    u.phone AS customer_phone,
    ua.address_line,
    ua.landmark,
    ci.city_name,
    c.pickup_date,
    c.pickup_status,
    c.created_at
    FROM cart c 
    JOIN users u ON c.user_id = u.user_id 
    JOIN user_addresses ua ON c.address_id = ua.address_id
    JOIN cities ci ON ua.city_id = ci.city_id
    WHERE c.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $cart_id);
$stmt->execute();
$result = $stmt->get_result();
$details = $result->fetch_assoc();

if ($details) {
    echo json_encode(['success' => true, 'details' => $details]);
} else {
    echo json_encode(['success' => false, 'message' => 'No details found']);
}