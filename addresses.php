<?php
session_start();
require_once 'connect.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle form submission for saving a new address
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save new address
    if (isset($_POST['save_address'])) {
        $address_type = isset($_POST['address_type']) ? trim($_POST['address_type']) : '';
        $address_line = isset($_POST['address_line']) ? trim($_POST['address_line']) : '';
        $landmark = isset($_POST['landmark']) ? trim($_POST['landmark']) : '';

        // Validate required fields
        if (empty($address_type) || empty($address_line)) {
            $error_message = "Address type and address line are required.";
        } else {
            // Validate address type
            $allowed_types = ['Home', 'Work', 'Other'];
            if (!in_array($address_type, $allowed_types)) {
                $error_message = "Invalid address type.";
            } else {
                // Insert the new address into the database
                $insert_sql = "INSERT INTO user_addresses (user_id, address_type, city_id, address_line, landmark, is_default)
                               VALUES (?, ?, (SELECT city_id FROM users WHERE user_id = ?), ?, ?, FALSE)";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("issis", $user_id, $address_type, $user_id, $address_line, $landmark);

                if ($insert_stmt->execute()) {
                    $success_message = "Address saved successfully.";
                } else {
                    $error_message = "Failed to save address: " . $insert_stmt->error;
                }

                $insert_stmt->close();
            }
        }
    }

    // Handle address update
    if (isset($_POST['update_address'])) {
        $address_id = intval($_POST['address_id']);
        $address_type = isset($_POST['address_type']) ? trim($_POST['address_type']) : '';
        $address_line = isset($_POST['address_line']) ? trim($_POST['address_line']) : '';
        $landmark = isset($_POST['landmark']) ? trim($_POST['landmark']) : '';

        // Validate required fields
        if (empty($address_type) || empty($address_line)) {
            $error_message = "Address type and address line are required.";
        } else {
            // Validate address type
            $allowed_types = ['Home', 'Work', 'Other'];
            if (!in_array($address_type, $allowed_types)) {
                $error_message = "Invalid address type.";
            } else {
                // Update the address in the database
                $update_sql = "UPDATE user_addresses 
                               SET address_type = ?, address_line = ?, landmark = ?
                               WHERE address_id = ? AND user_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("sssii", $address_type, $address_line, $landmark, $address_id, $user_id);

                if ($update_stmt->execute()) {
                    $success_message = "Address updated successfully.";
                } else {
                    $error_message = "Failed to update address: " . $update_stmt->error;
                }

                $update_stmt->close();
            }
        }
    }

    // Handle city update
    if (isset($_POST['update_city'])) {
        $city_id = intval($_POST['city_id']);

        // Validate city_id (ensure it exists in the cities table)
        $city_check_sql = "SELECT city_id FROM cities WHERE city_id = ?";
        $city_check_stmt = $conn->prepare($city_check_sql);
        $city_check_stmt->bind_param("i", $city_id);
        $city_check_stmt->execute();
        $city_check_result = $city_check_stmt->get_result();

        if ($city_check_result->num_rows === 0) {
            $error_message = "Invalid city selected.";
        } else {
            // Update the user's city in the users table
            $update_city_sql = "UPDATE users SET city_id = ? WHERE user_id = ?";
            $update_city_stmt = $conn->prepare($update_city_sql);
            $update_city_stmt->bind_param("ii", $city_id, $user_id);

            if ($update_city_stmt->execute()) {
                $success_message = "City updated successfully.";
            } else {
                $error_message = "Failed to update city: " . $update_city_stmt->error;
            }

            $update_city_stmt->close();
        }

        $city_check_stmt->close();
    }
}

// Handle address deletion
if (isset($_GET['delete_address'])) {
    $address_id = intval($_GET['delete_address']);

    // Delete the address from the database
    $delete_sql = "DELETE FROM user_addresses WHERE address_id = ? AND user_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("ii", $address_id, $user_id);

    if ($delete_stmt->execute()) {
        $success_message = "Address deleted successfully.";
    } else {
        $error_message = "Failed to delete address: " . $delete_stmt->error;
    }

    $delete_stmt->close();
}

// Fetch user's current city
$user_city_query = "SELECT u.*, c.city_name 
                    FROM users u 
                    LEFT JOIN cities c ON u.city_id = c.city_id 
                    WHERE u.user_id = ?";
$stmt = $conn->prepare($user_city_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$current_city = $user_data['city_name'] ?? 'Not Set';

// Fetch user's addresses
$addresses_query = "SELECT a.*, c.city_name 
                   FROM user_addresses a 
                   JOIN cities c ON a.city_id = c.city_id 
                   WHERE a.user_id = ?";
$stmt = $conn->prepare($addresses_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$addresses_result = $stmt->get_result();

// Fetch cities for dropdown
$cities_query = "SELECT * FROM cities ORDER BY city_name ASC";
$cities_result = $conn->query($cities_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Addresses | JunkGenie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        <style>
    body {
        background: #f5f7fa;
        min-height: 100vh;
        padding-top: 80px;
    }

    .addresses-container {
        max-width: 800px;
        margin: 0 auto;
        margin-bottom: 80px;
        padding: 20px;
    }

    .current-city-box {
        background: white;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 80px;
        margin-top: 80px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        transition: transform 0.2s;
    }

    .current-city-box:hover {
        transform: translateY(-5px);
    }

    .city-icon {
        width: 60px;
        height: 60px;
        background: #e8f5e9;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 20px;
    }

    .city-icon i {
        font-size: 28px;
        color: #4CAF50;
    }

    .city-info h6 {
        color: #666;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }

    .city-info .current-city-name {
        font-size: 24px;
        font-weight: 600;
        color: #4CAF50;
        margin: 0;
        margin-bottom: 10px;
    }

    .address-card {
        background: white;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        position: relative;
        transition: transform 0.2s;
    }

    .address-card:hover {
        transform: translateY(-5px);
    }

    .address-type {
        position: absolute;
        top: 15px;
        right: 15px;
        background: #4CAF50;
        color: white;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        letter-spacing: 0.5px;
    }

    .address-actions {
        margin-top: 15px;
        display: flex;
        gap: 10px;
    }

    .section-title {
        color: #333;
        font-weight: 600;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e0e0e0;
    }

    .btn-outline-primary {
        color: #4CAF50;
        border-color: #4CAF50;
    }

    .btn-outline-primary:hover {
        background-color: #4CAF50;
        border-color: #4CAF50;
        color: white;
    }

    .btn-success {
        background-color: #4CAF50;
        border-color: #4CAF50;
    }

    .btn-success:hover {
        background-color: #388E3C;
        border-color: #388E3C;
    }

    .alert {
        border-radius: 10px;
        margin-bottom: 20px;
    }

    .modal-content {
        border-radius: 15px;
        border: none;
    }

    .modal-header {
        border-bottom: none;
        padding: 20px;
    }

    .modal-body {
        padding: 20px;
    }

    .form-control, .form-select {
        border-radius: 10px;
        padding: 12px;
        border: 1px solid #e0e0e0;
    }

    .form-control:focus, .form-select:focus {
        border-color: #4CAF50;
        box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25);
    }

    .form-label {
        color: #666;
        font-weight: 500;
        margin-bottom: 8px;
    }
</style>
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="addresses-container">
        <!-- Display success or error messages -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <!-- Current City Box -->
        <div class="current-city-box">
            <div class="d-flex align-items-center">
                <div class="city-icon">
                    <i class="fas fa-city"></i>
                </div>
                <div class="city-info">
                    <h6>Your City</h6>
                    <p class="current-city-name"><?php echo htmlspecialchars($current_city); ?></p>
                    <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#updateCityModal">
                        <i class="fas fa-edit me-2"></i>Change City
                    </button>
                </div>
            </div>
        </div>

        <!-- Update City Modal -->
        <div class="modal fade" id="updateCityModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Update City</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">Select City</label>
                                <select class="form-select" name="city_id" required>
                                    <option value="">Select City</option>
                                    <?php 
                                    $cities_result->data_seek(0);
                                    while($city = $cities_result->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $city['city_id']; ?>" <?php echo $city['city_id'] == $user_data['city_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($city['city_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <button type="submit" name="update_city" class="btn btn-success w-100">
                                <i class="fas fa-save me-2"></i>Update City
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="section-title mb-0">Saved Addresses</h4>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                <i class="fas fa-plus me-2"></i>Add New Address
            </button>
        </div>

        <?php if ($addresses_result->num_rows > 0): ?>
            <?php while($address = $addresses_result->fetch_assoc()): ?>
                <div class="address-card">
                    <span class="address-type"><?php echo htmlspecialchars($address['address_type']); ?></span>
                    <h5 class="mb-3"><?php echo htmlspecialchars($address['address_line']); ?></h5>
                    
                    <p class="mb-2">
                        <i class="fas fa-map-pin text-success me-2"></i>
                        <?php echo htmlspecialchars($address['landmark']); ?>
                    </p>
                    <div class="address-actions">
                        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editAddressModal<?php echo $address['address_id']; ?>">
                            <i class="fas fa-edit me-2"></i>Edit
                        </button>
                        <a href="?delete_address=<?php echo $address['address_id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to delete this address?');">
                            <i class="fas fa-trash me-2"></i>Delete
                        </a>
                    </div>
                </div>

                <!-- Edit Address Modal -->
                <div class="modal fade" id="editAddressModal<?php echo $address['address_id']; ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Address</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form method="POST" action="">
                                    <input type="hidden" name="address_id" value="<?php echo $address['address_id']; ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Address Type</label>
                                        <select class="form-select" name="address_type" required>
                                            <option value="Home" <?php echo $address['address_type'] === 'Home' ? 'selected' : ''; ?>>Home</option>
                                            <option value="Work" <?php echo $address['address_type'] === 'Work' ? 'selected' : ''; ?>>Work</option>
                                            <option value="Other" <?php echo $address['address_type'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Complete Address</label>
                                        <textarea class="form-control" name="address_line" rows="3" required><?php echo htmlspecialchars($address['address_line']); ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Landmark</label>
                                        <input type="text" class="form-control" name="landmark" value="<?php echo htmlspecialchars($address['landmark']); ?>" required>
                                    </div>
                                    <button type="submit" name="update_address" class="btn btn-success w-100">
                                        <i class="fas fa-save me-2"></i>Update Address
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-map-marker-alt text-muted mb-3" style="font-size: 3rem;"></i>
                <h5>No addresses found</h5>
                <p class="text-muted">Add your first address to get started</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Add Address Modal -->
    <div class="modal fade" id="addAddressModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Address</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Address Type</label>
                            <select class="form-select" name="address_type" required>
                                <option value="Home">Home</option>
                                <option value="Work">Work</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Complete Address</label>
                            <textarea class="form-control" name="address_line" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Landmark</label>
                            <input type="text" class="form-control" name="landmark" required>
                        </div>
                        <button type="submit" name="save_address" class="btn btn-success w-100">
                            <i class="fas fa-save me-2"></i>Save Address
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>