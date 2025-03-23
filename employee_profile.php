<?php
session_start();
require_once 'connect.php';

// Redirect if not logged in or not an employee
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Employee') {
    echo "<script>window.location.href = 'login.php';</script>";
    exit();
}

// Get employee details
$user_id = $_SESSION['user_id'];
$query = "SELECT u.*, e.employee_id, e.Availability, c.city_name
          FROM users u 
          JOIN employees e ON u.user_id = e.user_id
          LEFT JOIN user_addresses ua ON u.user_id = ua.user_id AND ua.is_default = 1
          LEFT JOIN cities c ON ua.city_id = c.city_id
          WHERE u.user_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();

// In the PHP section, add this function to calculate days remaining
function getDaysRemaining($pickup_date) {
    $today = new DateTime();
    $pickup = new DateTime($pickup_date);
    $interval = $today->diff($pickup);
    $days = $interval->days;
    
    if ($today > $pickup) {
        return ['text' => 'Overdue', 'class' => 'text-danger'];
    } elseif ($days == 0) {
        return ['text' => 'Today', 'class' => 'text-warning'];
    } elseif ($days == 1) {
        return ['text' => 'Tomorrow', 'class' => 'text-success'];
    } else {
        return ['text' => $days . ' days left', 'class' => 'text-success'];
    }
}

function getPickupCountdown($pickup_date) {
    $pickup = new DateTime($pickup_date);
    $today = new DateTime();
    $interval = $today->diff($pickup);
    
    if ($today > $pickup) {
        return '<span class="countdown overdue"><i class="fas fa-exclamation-circle"></i> Overdue</span>';
    } elseif ($interval->days == 0) {
        return '<span class="countdown today"><i class="fas fa-clock"></i> Today</span>';
    } elseif ($interval->days == 1) {
        return '<span class="countdown tomorrow"><i class="fas fa-clock"></i> Tomorrow</span>';
    } else {
        return '<span class="countdown upcoming"><i class="fas fa-calendar"></i> ' . $interval->days . ' days left</span>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Profile | JunkGenie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-green: #2E7D32;
            --light-green: #4CAF50;
            --sidebar-width: 250px;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: var(--sidebar-width);
            background-color: var(--primary-green);
            padding: 1rem;
            z-index: 1000;
            height: 100vh;
            overflow-y: auto;
        }

        .logo-container {
            text-align: center;
            padding: 0.8rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 1rem;
        }

        .logo {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 0.5rem;
            border: 2px solid white;
        }

        .brand-name {
            font-size: 1.1rem;
            margin: 0;
            color: white;
            font-weight: 500;
        }

        .menu {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 0.8rem 1rem;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .menu-item i {
            width: 24px;
            margin-right: 10px;
            font-size: 1rem;
        }

        .menu-item span {
            font-size: 0.9rem;
        }

        .menu-item:hover, .menu-item.active {
            background: var(--light-green);
            transform: translateX(5px);
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            min-height: 100vh;
        }

        .profile-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #eee;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin-bottom: 1rem;
            border: 3px solid var(--primary-green);
            padding: 3px;
        }

        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-available {
            background: #E8F5E9;
            color: #2E7D32;
        }

        .status-unavailable {
            background: #FFEBEE;
            color: #C62828;
        }

        .info-group {
            margin-bottom: 1.5rem;
        }

        .info-label {
            font-weight: 600;
            color: #666;
            margin-bottom: 0.5rem;
        }

        .info-value {
            color: #333;
            font-size: 1.1rem;
        }

        .edit-button {
            background-color: var(--primary-green);
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .edit-button:hover {
            background-color: var(--light-green);
            transform: translateY(-2px);
        }

        /* Modal styles */
        .modal-header {
            background-color: var(--primary-green);
            color: white;
        }

        .modal-body {
            padding: 2rem;
        }

        .days-remaining {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
            margin-top: 0.5rem;
        }

        .days-remaining.text-danger {
            background-color: #FFEBEE;
            color: #C62828;
        }

        .days-remaining.text-warning {
            background-color: #FFF3E0;
            color: #E65100;
        }

        .days-remaining.text-success {
            background-color: #E8F5E9;
            color: #2E7D32;
        }

        .countdown {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            margin-top: 10px;
        }

        .countdown.overdue {
            background-color: #FFE7E7;
            color: #D32F2F;
            border: 1px solid #FFCDD2;
        }

        .countdown.today {
            background-color: #FFF3E0;
            color: #E65100;
            border: 1px solid #FFE0B2;
        }

        .countdown.tomorrow {
            background-color: #E3F2FD;
            color: #1565C0;
            border: 1px solid #BBDEFB;
        }

        .countdown.upcoming {
            background-color: #E8F5E9;
            color: #2E7D32;
            border: 1px solid #C8E6C9;
        }

        .view-details-btn {
            background-color: #fff;
            border: 2px solid #2E7D32;
            color: #2E7D32;
            padding: 8px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .view-details-btn:hover {
            background-color: #2E7D32;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .pickup-details-modal .modal-header {
            background-color: #2E7D32;
            color: white;
        }

        .detail-section {
            padding: 15px;
            border-radius: 8px;
            background-color: #f8f9fa;
            margin-bottom: 15px;
        }

        .detail-section h6 {
            color: #2E7D32;
            margin-bottom: 15px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php include 'esidebar.php'; ?>

    <div class="main-content">
        <div class="profile-card">
            <div class="profile-header">
            <img src="images/ep.png" alt="User Profile Picture" class="profile-avatar" width="20" height="20">
                <h2><?php echo htmlspecialchars($employee['fullname']); ?></h2>
                <p class="text-muted mb-2">Employee ID: <?php echo htmlspecialchars($employee['employee_id']); ?></p>
                <span class="status-badge <?php echo strtolower($employee['Availability']) === 'available' ? 'status-available' : 'status-unavailable'; ?>">
                    <?php echo htmlspecialchars($employee['Availability']); ?>
                </span>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="info-group">
                        <div class="info-label">Email Address</div>
                        <div class="info-value"><?php echo htmlspecialchars($employee['email']); ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-group">
                        <div class="info-label">Phone Number</div>
                        <div class="info-value"><?php echo htmlspecialchars($employee['phone']); ?></div>
                        <small><a href="#" data-bs-toggle="modal" data-bs-target="#editPhoneModal">Change Phone Number</a></small>
                    </div>
                </div>
            </div>

            <div class="info-group">
                <div class="info-label">City</div>
                <div class="info-value">
                    <?php echo htmlspecialchars($employee['city_name'] ?? 'Not specified'); ?>
                </div>
            </div>

            <div class="info-group">
                <div class="info-label">Member Since</div>
                <div class="info-value">
                    <?php echo date('F d, Y', strtotime($employee['created_at'])); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Phone Number Modal -->
    <div class="modal fade" id="editPhoneModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Change Phone Number</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editPhoneForm" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">New Phone Number</label>
                            <input type="tel" class="form-control" name="phone" 
                                   value="<?php echo htmlspecialchars($employee['phone']); ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('editPhoneForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('update_phone.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error updating phone number: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating phone number');
            });
        });
    </script>
</body>
</html>