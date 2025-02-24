<?php
require_once 'connect.php';

if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];

    // Prevent deletion of Admin
    $check_admin = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
    $check_admin->bind_param("i", $user_id);
    $check_admin->execute();
    $result = $check_admin->get_result();
    $user = $result->fetch_assoc();

    if ($user['role'] === 'Admin') {
        echo "<script>alert('Admin users cannot be deleted!'); window.location.href='usermanagement.php';</script>";
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        echo "<script>alert('User deleted successfully'); window.location.href='usermanagement.php';</script>";
    } else {
        echo "<script>alert('Error deleting user'); window.location.href='usermanagement.php';</script>";
    }

    $stmt->close();
}

$conn->close();
?>
