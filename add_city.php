<?php
session_start();
require_once 'connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['city'])) {
    $city = trim($_POST['city']);
    
    // Basic validation
    if (strlen($city) < 2) {
        echo json_encode(['success' => false, 'message' => 'City name too short']);
        exit;
    }
    
    // Check if city already exists
    $stmt = $conn->prepare("SELECT city_name FROM cities WHERE city_name = ?");
    $stmt->bind_param("s", $city);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        echo json_encode(['success' => true]); // City already exists
        exit;
    }
    
    // Add new city
    $stmt = $conn->prepare("INSERT INTO cities (city_name, is_active) VALUES (?, 1)");
    $stmt->bind_param("s", $city);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add city']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>

<div class="mb-3">
    <label for="city" class="form-label">City</label>
    <select class="form-select" id="city" name="city" required onchange="validateCity(this)">
        <option value="">Select your city</option>
        <?php foreach ($cities as $cityName): ?>
            <option value="<?php echo htmlspecialchars($cityName); ?>">
                <?php echo htmlspecialchars($cityName); ?>
            </option>
        <?php endforeach; ?>
        <option value="other">Other</option>
    </select>
    <div class="invalid-feedback" id="cityError"></div>
</div>