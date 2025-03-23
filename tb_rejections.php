<?php
require_once 'connect.php'; 

$sql = "CREATE TABLE IF NOT EXISTS rejections (
    rejection_id INT AUTO_INCREMENT PRIMARY KEY,
    cart_id INT NOT NULL,
    reason TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cart_id) REFERENCES cart(id)
)";

// Execute the SQL query
if ($conn->query($sql) === TRUE) {
    echo "Table rejections created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>