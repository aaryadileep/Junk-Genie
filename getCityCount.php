<?php
// getCityCount.php

// Database connection (assuming you're using MySQLi)
include 'connect.php';

// Query to count the number of cities
$sql = "SELECT COUNT(*) AS cityCount FROM cities";  // Adjust the table name and column accordingly
$result = $conn->query($sql);

// Fetch the count and return it as JSON
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode(['cityCount' => $row['cityCount']]);
} else {
    echo json_encode(['cityCount' => 0]);
}

$conn->close();
?>
