<?php
require_once 'connect.php';

$sql = "CREATE TABLE IF NOT EXISTS employee_activity (
    activity_id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    description TEXT NOT NULL,
    icon VARCHAR(50) DEFAULT 'fa-info-circle',
    activity_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Table employee_activity created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

// Insert some sample activities
$sample_activities = [
    "SELECT employee_id FROM employees LIMIT 1",
    "INSERT INTO employee_activity (employee_id, description, icon) VALUES 
    (?, 'Logged into the system', 'fa-sign-in-alt'),
    (?, 'Updated availability status to Available', 'fa-user-clock'),
    (?, 'Completed pickup request #1234', 'fa-check-circle')"
];

$result = $conn->query($sample_activities[0]);
if ($row = $result->fetch_assoc()) {
    $employee_id = $row['employee_id'];
    $stmt = $conn->prepare($sample_activities[1]);
    $stmt->bind_param("iii", $employee_id, $employee_id, $employee_id);
    $stmt->execute();
}

$conn->close();
?>