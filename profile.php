<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
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
    </style>
    
</head>
<body>
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
                        <div class="stat-value">0</div>
                        <div class="stat-label">Orders</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">0</div>
                        <div class="stat-label">Points</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">₹0</div>
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

                    <div class="info-group">
                    <div class="info-label">Phone Number</div>
                    <div class="info-value">
                        <i class="fas fa-phone"></i>
                        <span id="displayPhone"><?php echo htmlspecialchars($user['phone']); ?></span>
                    </div>
                </div>

                
                    <div class="d-grid gap-2">
        <button class="edit-btn" data-bs-toggle="modal" data-bs-target="#editProfileModal">
            <i class="fas fa-edit me-2"></i>Edit Profile
        </button>

        <!-- 4 Buttons -->
        <div class="mt-4">
            <div class="row g-3">
                <div class="col-6">
                    <button class="btn btn-light w-100 profile-action-btn" onclick="window.location.href='loyalty-points.php'">
                        <i class="fas fa-star text-warning me-2"></i>
                        <span>Loyalty Points</span>
                        <span class="badge bg-warning text-dark ms-2">0</span>
                    </button>
                </div>
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

             

                </div>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editProfileForm">
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                            <div class="invalid-feedback" id="phoneError"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Old Password</label>
                            <input type="password" class="form-control" id="oldPassword" name="oldPassword" required>
                            <div class="invalid-feedback" id="oldPasswordError"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" id="newPassword" name="newPassword" required>
                            <div class="invalid-feedback" id="newPasswordError"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                            <div class="invalid-feedback" id="confirmPasswordError"></div>
                        </div>
                        <button type="button" class="btn btn-success w-100" onclick="updateProfile()">
                            Save Changes
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateProfile() {
    console.log("Update Profile function called"); // Debugging

    const phone = document.getElementById('phone').value.trim();
    const oldPassword = document.getElementById('oldPassword').value.trim();
    const newPassword = document.getElementById('newPassword').value.trim();
    const confirmPassword = document.getElementById('confirmPassword').value.trim();

    // Clear previous errors
    document.getElementById('phoneError').textContent = '';
    document.getElementById('oldPasswordError').textContent = '';
    document.getElementById('newPasswordError').textContent = '';
    document.getElementById('confirmPasswordError').textContent = '';

    // Validate phone number
    if (!/^[6789]\d{9}$/.test(phone)) {
        document.getElementById('phoneError').textContent = 'Enter a valid 10-digit phone number';
        return;
    }

    // Validate old password
    if (oldPassword === '') {
        document.getElementById('oldPasswordError').textContent = 'Old password is required';
        return;
    }

    // Validate new password
    if (newPassword.length < 8) {
        document.getElementById('newPasswordError').textContent = 'Password must be at least 8 characters';
        return;
    }

    // Validate confirm password
    if (newPassword !== confirmPassword) {
        document.getElementById('confirmPasswordError').textContent = 'Passwords do not match';
        return;
    }

    // Send data to server
    const formData = new FormData();
    formData.append('phone', phone);
    formData.append('oldPassword', oldPassword);
    formData.append('newPassword', newPassword);

    console.log("Sending data to server:", { phone, oldPassword, newPassword }); // Debugging

    fetch('updateprofile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log("Response received:", response); // Debugging
        return response.json();
    })
    .then(data => {
        console.log("Data received:", data); // Debugging
        if (data.success) {
            // Update displayed phone number
            document.getElementById('displayPhone').textContent = phone;
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('editProfileModal')).hide();
            alert('Profile updated successfully!');
        } else {
            // Display errors
            document.getElementById('oldPasswordError').textContent = data.errors.oldPassword ?? '';
            document.getElementById('newPasswordError').textContent = data.errors.newPassword ?? '';
        }
    })
    .catch(error => {
        console.error("Error:", error); // Debugging
        alert('An error occurred. Please try again.');
    });
}
// Add this to your existing <script> section
function confirmLogout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = 'logout.php';
    }
}
    </script>
</body>
</html>