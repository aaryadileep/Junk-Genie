<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch cities for dropdown
$citiesQuery = "SELECT city_id, city_name FROM cities WHERE is_active = 1";
$citiesResult = $conn->query($citiesQuery);
$cities = $citiesResult->fetch_all(MYSQLI_ASSOC);

// Fetch user's current address and city
$query = "SELECT u.*, ua.*, c.city_name 
          FROM users u 
          LEFT JOIN user_addresses ua ON u.user_id = ua.user_id
          LEFT JOIN cities c ON u.city_id = c.city_id 
          WHERE u.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

// Handle form submission for updating address
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address_line = isset($_POST['address_line']) ? trim($_POST['address_line']) : '';
    $landmark = isset($_POST['landmark']) ? trim($_POST['landmark']) : '';
    $pincode = isset($_POST['pincode']) ? trim($_POST['pincode']) : '';
    $city_id = isset($_POST['city_id']) ? (int)$_POST['city_id'] : 0;

    if (empty($address_line) || empty($city_id)) {
        $error_message = "Address and City are required.";
    } else {
        // Start transaction
        $conn->begin_transaction();
        try {
            // Update user's city
            $updateUserCity = "UPDATE users SET city_id = ? WHERE user_id = ?";
            $cityStmt = $conn->prepare($updateUserCity);
            $cityStmt->bind_param("ii", $city_id, $user_id);
            $cityStmt->execute();

            // Update or insert address
            if ($user_data['address_id']) {
                $sql = "UPDATE user_addresses 
                        SET address_line = ?, landmark = ?, pincode = ?, city_id = ? 
                        WHERE user_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssii", $address_line, $landmark, $pincode, $city_id, $user_id);
            } else {
                $sql = "INSERT INTO user_addresses 
                        (address_line, landmark, pincode, user_id, city_id) 
                        VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssii", $address_line, $landmark, $pincode, $user_id, $city_id);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to save address");
            }

            $conn->commit();
            header("Location: addresses.php");
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Failed to save address: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Address | JunkGenie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f7fa;
            min-height: 100vh;
            padding-top: 80px;
        }
        .city-banner {
            background: linear-gradient(135deg, #4CAF50, #388E3C);
            color: white;
            padding: 15px 0;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .address-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .address-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .btn-edit {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .btn-edit:hover {
            background: #388E3C;
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
        }
        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }
        .current-address {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <!-- City Banner -->
    <div class="city-banner text-center">
        <h5 class="mb-0">
            <?php if ($user_data['city_name']): ?>
                Current City: <?= htmlspecialchars($user_data['city_name']); ?>
            <?php else: ?>
                City not set
            <?php endif; ?>
        </h5>
    </div>

    <div class="address-container">
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="address-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">Delivery Address</h4>
                <button class="btn-edit" data-bs-toggle="modal" data-bs-target="#editAddressModal">
                    <i class="fas fa-<?= $user_data['address_line'] ? 'edit' : 'plus'; ?> me-2"></i>
                    <?= $user_data['address_line'] ? 'Edit' : 'Add'; ?> Address
                </button>
            </div>

            <?php if ($user_data['address_line']): ?>
                <div class="current-address">
                    <p class="mb-2"><strong>Full Address:</strong> 
                        <?= htmlspecialchars($user_data['address_line']); ?>
                    </p>
                    <?php if ($user_data['landmark']): ?>
                        <p class="mb-2"><strong>Landmark:</strong> 
                            <?= htmlspecialchars($user_data['landmark']); ?>
                        </p>
                    <?php endif; ?>
                    <p class="mb-0"><strong>Pincode:</strong> 
                        <?= htmlspecialchars($user_data['pincode']); ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="empty-state alert alert-warning">
                    <i class="fas fa-info-circle me-2"></i>
                    You haven't added your address yet. Please add your delivery address.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit Address Modal -->
    <div class="modal fade" id="editAddressModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><?= $user_data['address_line'] ? 'Edit' : 'Add'; ?> Address</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">City</label>
                            <select class="form-control" name="city_id" required>
                                <option value="">Select City</option>
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?= $city['city_id']; ?>" 
                                        <?= ($user_data['city_id'] == $city['city_id']) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($city['city_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Complete Address</label>
                            <textarea class="form-control" name="address_line" rows="3" required>
                                <?= htmlspecialchars($user_data['address_line'] ?? ''); ?>
                            </textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Landmark</label>
                            <input type="text" class="form-control" name="landmark" 
                                   value="<?= htmlspecialchars($user_data['landmark'] ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pincode</label>
                            <input type="text" class="form-control" name="pincode" 
                                   value="<?= htmlspecialchars($user_data['pincode'] ?? ''); ?>" 
                                   pattern="[0-9]{6}" maxlength="6" required>
                            <small class="text-muted">Enter 6-digit pincode</small>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Save Address</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>