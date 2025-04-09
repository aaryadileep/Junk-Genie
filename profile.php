<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user basic info
$query = "SELECT u.fullname, u.email, u.phone, c.city_name 
          FROM users u 
          LEFT JOIN cities c ON u.city_id = c.city_id 
          WHERE u.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Fetch completed orders count
$orders_query = "SELECT COUNT(*) as completed_orders 
                 FROM cart 
                 WHERE user_id = ? AND pickup_status = 'Completed'";
$stmt_orders = $conn->prepare($orders_query);
$stmt_orders->bind_param("i", $user_id);
$stmt_orders->execute();
$orders_result = $stmt_orders->get_result();
$completed_orders = $orders_result->fetch_assoc()['completed_orders'];
$stmt_orders->close();

// Calculate loyalty points (5 points per completed order)
$total_points = $completed_orders * 5;

// Fetch total earnings from completed orders (assuming cart_items and products tables exist)
$earnings_query = "SELECT SUM(p.price_per_pc) as total_earnings 
                   FROM cart c 
                   LEFT JOIN cart_items ci ON c.id = ci.cart_id 
                   LEFT JOIN products p ON ci.product_id = p.product_id 
                   WHERE c.user_id = ? AND c.pickup_status = 'Completed'";
$stmt_earnings = $conn->prepare($earnings_query);
$stmt_earnings->bind_param("i", $user_id);
$stmt_earnings->execute();
$earnings_result = $stmt_earnings->get_result();
$total_earnings = $earnings_result->fetch_assoc()['total_earnings'] ?? 0;
$stmt_earnings->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | JunkGenie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
       :root {
            --primary: #4CAF50;
            --primary-dark: #388E3C;
            --primary-light: #C8E6C9;
            --secondary: #F5F5F5;
            --success: #4CAF50;
            --danger: #f44336;
            --warning: #ff9800;
            --info: #2196f3;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .profile-container {
            max-width: 800px;
            margin: 100px auto 30px;
            padding: 0 20px;
        }

        .profile-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .profile-header {
            background: linear-gradient(45deg, var(--primary), var(--primary-dark));
            padding: 30px;
            color: white;
            text-align: center;
            position: relative;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            margin: 0 auto 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-stats {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 20px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 600;
        }

        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }

        .profile-body {
            padding: 30px;
        }

        .info-group {
            margin-bottom: 25px;
        }

        .info-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 16px;
            color: #333;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-value i {
            color: var(--primary);
        }

        .edit-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .edit-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.3);
        }

        .verification-badge {
            background: var(--success);
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            margin-left: 10px;
        }

        /* Modal Styles */
        .modal-content {
            border-radius: 15px;
        }

        .modal-header {
            background: var(--primary);
            color: white;
            border-radius: 15px 15px 0 0;
        }

        .form-control {
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 15px;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25);
        }
       
.profile-action-btn {
    padding: 15px;
    border-radius: 12px;
    font-weight: 500;
    transition: all 0.3s ease;
    border: 1px solid #eee;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
}

.profile-action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.profile-action-btn i {
    font-size: 1.2rem;
}

.btn-danger {
    background: #ff4444;
    border-color: #ff4444;
    color: white;
}

.btn-danger:hover {
    background: #cc0000;
    border-color: #cc0000;
    color: white;
}
 
        .is-invalid {
            border-color: #dc3545 !important;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }
        
        #phoneError {
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body>
ody>
    <!-- Navigation Header -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="logo.jpg" alt="JunkGenie" height="40">
                <span class="ms-2 text-success fw-bold">JunkGenie</span>
            </a>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-success" onclick="window.location.href='userdashboard.php'">
                    <i class="fas fa-arrow-left me-2"></i>Back
                </button>
            </div>
        </div>
    </nav>

    <!-- Profile Content -->
    <div class="profile-container">
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar">
                    <img src="images/profile.jpg" alt="Profile Picture">
                </div>
                <h4 class="mb-1"><?php echo htmlspecialchars($user['fullname']); ?></h4>
                <p class="mb-3"><?php echo htmlspecialchars($user['city_name'] ?? 'City not set'); ?></p>
                <div class="profile-stats">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $completed_orders; ?></div>
                        <div class="stat-label">Orders</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $total_points; ?></div>
                        <div class="stat-label">Points</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">₹<?php echo number_format($total_earnings, 2); ?></div>
                        <div class="stat-label">Earnings</div>
                    </div>
                </div>
            </div>

            <div class="profile-body">
                <div class="info-group">
                    <div class="info-label">Email Address</div>
                    <div class="info-value">
                        <i class="fas fa-envelope"></i>
                        <?php echo htmlspecialchars($user['email']); ?>
                        <span class="verification-badge"><i class="fas fa-check me-1"></i>Verified</span>
                    </div>
                </div>

                <div class="info-group">
                    <div class="info-label">Phone Number</div>
                    <div class="info-value">
                        <i class="fas fa-phone"></i>
                        <span id="displayPhone"><?php echo htmlspecialchars($user['phone']); ?></span>
                    </div>
                    <a href="#" class="text-muted small" data-bs-toggle="modal" data-bs-target="#changePhoneModal">
                        Change Phone Number
                    </a>
                </div>

                <div class="d-grid gap-2 mt-4">
                    <div class="row g-3">
                        <div class="col-6">
                            <button class="btn btn-light w-100 profile-action-btn" onclick="window.location.href='addresses.php'">
                                <i class="fas fa-map-marker-alt text-info me-2"></i>
                                <span>Addresses</span>
                            </button>
                        </div>
                        <div class="col-6">
                            <button class="btn btn-light w-100 profile-action-btn" onclick="window.location.href='order_history.php'">
                                <i class="fas fa-shopping-bag text-success me-2"></i>
                                <span>My Orders</span>
                            </button>
                        </div>
                        <div class="col-6">
                            <button class="btn btn-danger w-100 profile-action-btn" onclick="confirmLogout()">
                                <i class="fas fa-sign-out-alt me-2"></i>
                                <span>Logout</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Phone Modal -->
    <div class="modal fade" id="changePhoneModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Change Phone Number</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="changePhoneForm">
                        <div class="mb-3">
                            <label class="form-label">Current Phone Number</label>
                            <input type="tel" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Phone Number</label>
                            <input type="tel" class="form-control" id="newPhone" name="newPhone" 
                                   pattern="[6-9][0-9]{9}" maxlength="10" required
                                   oninput="validatePhone(this)">
                            <div class="invalid-feedback" id="phoneError"></div>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Update Phone</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Real-time phone number validation
    function validatePhone(input) {
        const phone = input.value;
        const phoneError = document.getElementById('phoneError');
        
        // Clear previous error states
        phoneError.textContent = '';
        input.classList.remove('is-invalid');
        
        // Validate only numbers are entered
        if (!/^[0-9]*$/.test(phone)) {
            phoneError.textContent = 'Only numbers are allowed';
            input.classList.add('is-invalid');
            return false;
        }
        
        // Validate first digit is 6-9 (only when something is entered)
        if (phone.length >= 1 && !/^[6-9]/.test(phone)) {
            phoneError.textContent = 'Must start with 6, 7, 8 or 9';
            input.classList.add('is-invalid');
            return false;
        }
        
        // Validate length (only show error when full 10 digits entered incorrectly)
        if (phone.length === 10 && !/^[6-9][0-9]{9}$/.test(phone)) {
            phoneError.textContent = 'Please enter exactly 10 digits starting with 6-9';
            input.classList.add('is-invalid');
            return false;
        }
        
        return true;
    }

    // Form submission handler
    document.getElementById('changePhoneForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const phoneInput = document.getElementById('newPhone');
        const phone = phoneInput.value;
        const phoneError = document.getElementById('phoneError');
        
        // Final validation
        if (!/^[6-9][0-9]{9}$/.test(phone)) {
            phoneError.textContent = 'Please enter a valid 10-digit number starting with 6-9';
            phoneInput.classList.add('is-invalid');
            return;
        }

        // Show loading state
        const submitButton = this.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...';

        // Send to server
        fetch('update_phone.php', {
            method: 'POST',
            body: new FormData(this)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('displayPhone').textContent = phone;
                bootstrap.Modal.getInstance(document.getElementById('changePhoneModal')).hide();
                alert('Phone number updated successfully!');
            } else {
                phoneError.textContent = data.error || 'This phone number is already in use';
                phoneInput.classList.add('is-invalid');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            phoneError.textContent = 'Network error - please try again';
            phoneInput.classList.add('is-invalid');
        })
        .finally(() => {
            submitButton.disabled = false;
            submitButton.innerHTML = 'Update Phone';
        });
    });

    function confirmLogout() {
        if (confirm('Are you sure you want to logout?')) {
            window.location.href = 'logout.php';
        }
    }
    </script>
</body>
</html>