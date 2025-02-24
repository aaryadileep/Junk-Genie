<?php
require_once 'connect.php'; // Ensure database connection is included

// Create the employees table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS employees (
    employee_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    availability ENUM('Available', 'Unavailable') DEFAULT 'Available',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'employees' checked/created successfully!<br>";
} else {
    echo "❌ Error creating table: " . $conn->error . "<br>";
}

// Alter table to remove unnecessary columns (phone, city, created_at)
$alter_sql = "ALTER TABLE employees 
              DROP COLUMN IF EXISTS phone, 
              DROP COLUMN IF EXISTS city, 
              DROP COLUMN IF EXISTS created_at";

if ($conn->query($alter_sql) === TRUE) {
    echo "✅ Unnecessary columns removed successfully!";
} else {
    echo "❌ Error removing columns: " . $conn->error;
}

$conn->close();
?>
