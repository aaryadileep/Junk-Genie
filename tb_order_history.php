<?php
require_once 'connect.php';

try {
    // SQL to create order_history table
    $sql = "CREATE TABLE IF NOT EXISTS order_history (
        order_id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        cart_id INT NOT NULL,
        pickup_date DATE NOT NULL,
        pickup_address TEXT NOT NULL,
        city_id INT NOT NULL,
        order_status ENUM('Pending', 'Confirmed', 'Picked Up', 'Completed', 'Cancelled') DEFAULT 'Pending',
        total_items INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id),
        FOREIGN KEY (cart_id) REFERENCES cart(id),
        FOREIGN KEY (city_id) REFERENCES cities(city_id)
    )";

    // Execute the SQL query
    if ($conn->query($sql) === TRUE) {
        echo "Table order_history created successfully";
    } else {
        echo "Error creating table: " . $conn->error;
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} finally {
    // Close the connection
    $conn->close();
}
?>