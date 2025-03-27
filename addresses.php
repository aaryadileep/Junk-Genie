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

    // Server-side validation
    $errors = [];
    
    if (empty($city_id)) {
        $errors['city_id'] = "City is required";
    }
    
    if (empty($address_line) || strlen($address_line) < 10 || !preg_match('/^(?=.*[a-zA-Z0-9])[\w\s\/\-.,#]+$/', $address_line)) {
        $errors['address_line'] = "Valid address is required (minimum 10 characters)";
    }
    
    if (!empty($landmark) && (strlen($landmark) < 3 || !preg_match('/^(?=.*[a-zA-Z0-9])[\w\s\-.,#]+$/', $landmark))) {
        $errors['landmark'] = "Landmark must be meaningful (minimum 3 characters)";
    }
    
    if (empty($pincode) || !preg_match('/^(67|68|69)\d{4}$/', $pincode)) {
        $errors['pincode'] = "Pincode must be 6 digits starting with 67, 68 or 69";
    }

    if (empty($errors)) {
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
        .is-invalid {
            border-color: #dc3545;
        }
        .is-valid {
            border-color: #28a745;
        }
        .invalid-feedback {
            display: none;
            color: #dc3545;
            font-size: 0.875em;
        }
        .is-invalid ~ .invalid-feedback {
            display: block;
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
                    <form method="POST" action="" id="addressForm">
                        <div class="mb-3">
                            <label class="form-label">City*</label>
                            <select class="form-control" name="city_id" id="city_id" required>
                                <option value="">Select City</option>
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?= $city['city_id']; ?>" 
                                        <?= ($user_data['city_id'] == $city['city_id']) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($city['city_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select your city</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Complete Address*</label>
                            <textarea class="form-control" name="address_line" id="address_line" rows="3" required><?= htmlspecialchars($user_data['address_line'] ?? ''); ?></textarea>
                            <div class="invalid-feedback">Address must be at least 10 characters with proper format</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Landmark</label>
                            <input type="text" class="form-control" name="landmark" id="landmark" value="<?= htmlspecialchars($user_data['landmark'] ?? ''); ?>">
                            <div class="invalid-feedback">Landmark must be meaningful (minimum 3 characters)</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pincode*</label>
                            <input type="text" class="form-control" name="pincode" id="pincode" value="<?= htmlspecialchars($user_data['pincode'] ?? ''); ?>" maxlength="6" required>
                            <div class="invalid-feedback">Pincode must be 6 digits starting with 67, 68 or 69</div>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Save Address</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Real-time validation
        function validateField(field) {
            const value = $(field).val().trim();
            let isValid = true;
            let errorMsg = '';
            
            // City validation
            if (field.id === 'city_id') {
                if (value === '') {
                    isValid = false;
                    errorMsg = 'Please select your city';
                }
            }
            
            // Address validation
            else if (field.id === 'address_line') {
                if (value.length < 10) {
                    isValid = false;
                    errorMsg = 'Address must be at least 10 characters';
                } else if (!/^(?=.*[a-zA-Z0-9])[\w\s\/\-.,#]+$/.test(value)) {
                    isValid = false;
                    errorMsg = 'Please enter a valid address format';
                }
            }
            
            // Landmark validation
            else if (field.id === 'landmark' && value !== '') {
                if (value.length < 3) {
                    isValid = false;
                    errorMsg = 'Landmark must be at least 3 characters';
                } else if (!/^(?=.*[a-zA-Z0-9])[\w\s\-.,#]+$/.test(value)) {
                    isValid = false;
                    errorMsg = 'Please enter a valid landmark';
                }
            }
            
            // Pincode validation
            else if (field.id === 'pincode') {
                if (!/^(67|68|69)\d{4}$/.test(value)) {
                    isValid = false;
                    errorMsg = 'Pincode must be 6 digits starting with 67, 68 or 69';
                }
            }
            
            // Update UI
            if (isValid) {
                $(field).removeClass('is-invalid').addClass('is-valid');
                $(field).next('.invalid-feedback').text('').hide();
            } else {
                $(field).removeClass('is-valid').addClass('is-invalid');
                $(field).next('.invalid-feedback').text(errorMsg).show();
            }
            
            return isValid;
        }
        
        // Validate on input/change
        $('#city_id, #address_line, #landmark, #pincode').on('input change', function() {
            validateField(this);
        });
        
        // Validate on blur
        $('#city_id, #address_line, #landmark, #pincode').on('blur', function() {
            validateField(this);
        });
        
        // Form submission
        $('#addressForm').on('submit', function(e) {
            let formValid = true;
            
            // Validate all fields
            $('#city_id, #address_line, #pincode').each(function() {
                if (!validateField(this)) {
                    formValid = false;
                }
            });
            
            // Validate landmark if not empty
            if ($('#landmark').val().trim() !== '') {
                if (!validateField($('#landmark')[0])) {
                    formValid = false;
                }
            }
            
            if (!formValid) {
                e.preventDefault();
                // Scroll to first error
                $('.is-invalid').first().focus();
            }
        });
        
        // Validate existing values when modal opens
        $('#editAddressModal').on('shown.bs.modal', function() {
            $('#city_id, #address_line, #landmark, #pincode').each(function() {
                validateField(this);
            });
        });
        
        // Prevent non-numeric input in pincode
        $('#pincode').on('keypress', function(e) {
            const charCode = e.which ? e.which : e.keyCode;
            if (charCode > 31 && (charCode < 48 || charCode > 57)) {
                return false;
            }
            return true;
        });
    });
    </script>
</body>
</html>