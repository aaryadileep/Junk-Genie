<?php
require_once 'connect.php';

// Create user_addresses table
$sql = "CREATE TABLE IF NOT EXISTS user_addresses (
    address_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    address_type ENUM('Home', 'Work', 'Other') NOT NULL DEFAULT 'Home',
    city_id INT NOT NULL,
    address_line TEXT NOT NULL,
    landmark VARCHAR(255),
    pincode VARCHAR(6),
    is_default BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (city_id) REFERENCES cities(city_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "Table user_addresses created successfully\n";

    // Add index for faster lookups
    $conn->query("CREATE INDEX idx_user_addresses_user_id ON user_addresses(user_id)");
    $conn->query("CREATE INDEX idx_user_addresses_city_id ON user_addresses(city_id)");
    
    // Add trigger to ensure only one default address per user
    $trigger = "CREATE TRIGGER before_address_update
                BEFORE UPDATE ON user_addresses
                FOR EACH ROW
                BEGIN
                    IF NEW.is_default = TRUE THEN
                        UPDATE user_addresses 
                        SET is_default = FALSE 
                        WHERE user_id = NEW.user_id 
                        AND address_id != NEW.address_id;
                    END IF;
                END;";
    
    if ($conn->multi_query($trigger)) {
        echo "Trigger created successfully\n";
    } else {
        echo "Error creating trigger: " . $conn->error . "\n";
    }
} else {
    echo "Error creating table: " . $conn->error . "\n";
}
 //new
 
$conn->close();
?>