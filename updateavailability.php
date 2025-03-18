<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Employee') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (isset($_POST['employee_id']) && isset($_POST['availability'])) {
    try {
        $stmt = $conn->prepare("UPDATE employees SET availability = ? WHERE employee_id = ?");
        $stmt->bind_param("si", $_POST['availability'], $_POST['employee_id']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            throw new Exception("Failed to update availability");
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}