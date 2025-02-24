<?php
include 'connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['email'])) {
        $email = $_POST['email'];
        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
    } elseif (isset($_POST['phone'])) {
        $phone = $_POST['phone'];
        $query = "SELECT * FROM users WHERE phone = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $phone);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(["status" => "exists"]);
    } else {
        echo json_encode(["status" => "available"]);
    }

    $stmt->close();
    $conn->close();
}
?>
