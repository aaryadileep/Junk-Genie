<?php
require_once 'connect.php'; 

$sql = "CREATE TABLE IF NOT EXISTS cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cart_id INT NOT NULL,
    product_id INT NOT NULL,
    image VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    FOREIGN KEY (cart_id) REFERENCES cart(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
) ENGINE=InnoDB";

// Execute the SQL query
if ($conn->query($sql) === TRUE) {
    echo "Table cart_items created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>
