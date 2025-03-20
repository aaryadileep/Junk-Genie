<?php
require_once 'connect.php'; 

$sql = "CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    address_id INT NULL,
    pickup_date DATE NOT NULL,
    pickup_status ENUM('Pending', 'Accepted', 'Rejected', 'Picked Up','Cancelled') DEFAULT 'Pending',
    assigned_employee_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (address_id) REFERENCES user_addresses(address_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_employee_id) REFERENCES employees(employee_id) ON DELETE SET NULL
)";

// Execute the SQL query to create the table
if ($conn->query($sql) === TRUE) {
    echo "Table cart created successfully or already exists.<br>";

    // Alter the address_id column to NOT NULL
    $alterSql = "ALTER TABLE cart MODIFY COLUMN address_id INT NOT NULL";
    if ($conn->query($alterSql) === TRUE) {
        echo "Column address_id altered to NOT NULL successfully.";
    } else {
        echo "Error altering column address_id: " . $conn->error;
    }
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>