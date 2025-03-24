<?php
require_once 'connect.php'; 

$sql = "CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    address_id INT NULL,
    pickup_date DATE NOT NULL,
    pickup_status ENUM('Pending', 'Accepted', 'Rejected', 'Completed', 'Cancelled') DEFAULT 'Pending',
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
        echo "Column address_id altered to NOT NULL successfully.<br>";
    } else {
        echo "Error altering column address_id: " . $conn->error;
    }

    // Update existing 'Picked Up' status to 'Completed'
    $updateStatus = "UPDATE cart SET pickup_status = 'Completed' WHERE pickup_status = 'Picked Up'";
    if ($conn->query($updateStatus) === TRUE) {
        echo "Updated 'Picked Up' statuses to 'Completed' successfully.<br>";
    }

    // Alter the pickup_status ENUM
    $alterEnum = "ALTER TABLE cart MODIFY COLUMN pickup_status 
                 ENUM('Pending', 'Accepted', 'Rejected', 'Completed', 'Cancelled') 
                 DEFAULT 'Pending'";
    if ($conn->query($alterEnum) === TRUE) {
        echo "Modified pickup_status ENUM successfully.<br>";
    } else {
        echo "Error modifying pickup_status ENUM: " . $conn->error;
    }

    // Add updated_at column if it doesn't exist
    $alter_query = "ALTER TABLE cart 
                    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP 
                    DEFAULT CURRENT_TIMESTAMP 
                    ON UPDATE CURRENT_TIMESTAMP";

    if ($conn->query($alter_query)) {
        echo "Successfully added updated_at column to cart table<br>";
    } else {
        echo "Error adding column: " . $conn->error;
    }
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>