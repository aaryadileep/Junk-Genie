<?php
require_once 'connect.php'; 

$sql = "CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    pickup_date DATE NOT NULL,
    pickup_status ENUM('Pending', 'Accepted', 'Rejected', 'Picked Up','Cancelled') DEFAULT 'Pending',
    assigned_employee_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_employee_id) REFERENCES employees(employee_id) ON DELETE SET NULL
)";

// Execute the SQL query
if ($conn->query($sql) === TRUE) {
    echo "Table cart created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>