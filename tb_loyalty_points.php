
<?php
require_once 'connect.php';
//Simplified loyalty points table
$sql = "CREATE TABLE IF NOT EXISTS loyalty_points (
    point_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    points_earned INT NOT NULL,
    points_reason VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES cart(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table loyalty points created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

$sql2 = "CREATE TABLE IF NOT EXISTS loyalty_milestones (
    milestone_id INT AUTO_INCREMENT PRIMARY KEY,
    milestone_name VARCHAR(100) NOT NULL,
    required_points INT NOT NULL,
    badge_image VARCHAR(255) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql2) === TRUE) {
    echo "Table loyalty milestones created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

$sql3 = "CREATE TABLE IF NOT EXISTS user_achievements (
    achievement_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    milestone_id INT NOT NULL,
    achieved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (milestone_id) REFERENCES loyalty_milestones(milestone_id)
)";

if ($conn->query($sql3) === TRUE) {
    echo "Table user achievements created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>