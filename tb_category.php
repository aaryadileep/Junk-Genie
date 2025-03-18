<?php
require_once 'connect.php'; 

$sql = "CREATE TABLE IF NOT EXISTS category (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

// Execute the SQL query
if ($conn->query($sql) === TRUE) {
    echo "Table category created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>