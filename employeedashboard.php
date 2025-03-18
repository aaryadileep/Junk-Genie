<?php
session_start();
require_once 'connect.php';

// Check if user is logged in and is employee
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Employee') {
    header("Location: login.php");
    exit();
}

// Fetch employee data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT u.*, e.employee_id, e.availability 
                       FROM users u 
                       JOIN employees e ON u.user_id = e.user_id 
                       WHERE u.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();

// Fetch task statistics
$stats_query = "SELECT 
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_tasks,
    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_tasks,
    SUM(CASE WHEN status = 'Overdue' THEN 1 ELSE 0 END) as overdue_tasks
FROM pickup_requests 
WHERE assigned_employee_id = ?";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param("i", $employee['employee_id']);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Fetch recent tasks
$tasks_query = "SELECT * FROM pickup_requests 
                WHERE assigned_employee_id = ? 
                ORDER BY created_at DESC LIMIT 5";
$stmt = $conn->prepare($tasks_query);
$stmt->bind_param("i", $employee['employee_id']);
$stmt->execute();
$tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch recent activity
$activity_query = "SELECT * FROM employee_activity 
                  WHERE employee_id = ? 
                  ORDER BY activity_time DESC LIMIT 5";
$stmt = $conn->prepare($activity_query);
$stmt->bind_param("i", $employee['employee_id']);
$stmt->execute();
$activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard | JunkGenie</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4CAF50;
            --primary-dark: #388E3C;
            --secondary: #F5F5F5;
            --success: #4CAF50;
            --danger: #f44336;
            --warning: #ff9800;
            --info: #2196f3;
        }

        body {
            background: #f5f6fa;
            min-height: 100vh;
            font-family: 'Segoe UI', sans-serif;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 280px;
            background: linear-gradient(180deg, var(--primary-dark) 0%, var(--primary) 100%);
            color: white;
            padding: 2rem;
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .user-profile {
            text-align: center;
            padding: 1rem;
            border-radius: 10px;
            background: rgba(255,255,255,0.1);
        }

        .profile-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-bottom: 1rem;
            border: 3px solid rgba(255,255,255,0.2);
        }

        .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 0.8rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            margin-bottom: 0.5rem;
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(5px);
        }

        /* Main Content Styles */
        .main-content {
            margin-left: 280px;
            padding: 2rem;
        }

        .header {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Stats Cards */
        .stats-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stats-icon.primary { background: rgba(76,175,80,0.1); color: var(--primary); }
        .stats-icon.success { background: rgba(76,175,80,0.1); color: var(--success); }
        .stats-icon.warning { background: rgba(255,152,0,0.1); color: var(--warning); }

        .stats-info h3 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 600;
        }

        .stats-info p {
            margin: 0;
            color: #666;
        }

        /* Task List */
        .task-list {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .task-item {
            padding: 1rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .task-item:last-child {
            border-bottom: none;
        }

        .task-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .task-status.pending { background: rgba(255,152,0,0.1); color: var(--warning); }
        .task-status.completed { background: rgba(76,175,80,0.1); color: var(--success); }
        .task-status.overdue { background: rgba(244,67,54,0.1); color: var(--danger); }

        /* Activity List */
        .recent-activity {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(76,175,80,0.1);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .activity-time {
            font-size: 0.875rem;
            color: #666;
        }

        /* Toast Notification */
        .toast-notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            color: white;
            font-weight: 500;
            z-index: 1000;
            animation: slideIn 0.3s ease-out;
        }

        .toast-notification.success { background: var(--success); }
        .toast-notification.error { background: var(--danger); }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .stats-card {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <i class="fas fa-recycle"></i> JunkGenie
        </div>
        <div class="user-profile mb-4">
            <img src="assets/images/default-avatar.png" alt="Profile" class="profile-img">
            <div class="profile-info">
                <h6 class="mb-1"><?php echo htmlspecialchars($employee['fullname']); ?></h6>
                <span class="badge bg-success"><?php echo htmlspecialchars($employee['availability']); ?></span>
            </div>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="employeedashboard.php">
                    <i class="fas fa-home me-2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="mypickups.php">
                    <i class="fas fa-truck me-2"></i> My Pickups
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="schedule.php">
                    <i class="fas fa-calendar-alt me-2"></i> Schedule
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="myprofile.php">
                    <i class="fas fa-user me-2"></i> My Profile
                </a>
            </li>
            <li class="nav-item mt-auto">
                <a class="nav-link text-danger" href="logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <h4>
                <i class="fas fa-home me-2"></i>Dashboard
                <span class="badge bg-primary ms-2">Employee ID: <?php echo str_pad($employee['employee_id'], 4, '0', STR_PAD_LEFT); ?></span>
            </h4>
            <div class="d-flex align-items-center gap-3">
                <div class="availability-toggle">
                    <select class="form-select form-select-sm" onchange="updateAvailability(this.value)">
                        <option value="Available" <?php echo $employee['availability'] == 'Available' ? 'selected' : ''; ?>>Available</option>
                        <option value="On Duty" <?php echo $employee['availability'] == 'On Duty' ? 'selected' : ''; ?>>On Duty</option>
                        <option value="Off Duty" <?php echo $employee['availability'] == 'Off Duty' ? 'selected' : ''; ?>>Off Duty</option>
                    </select>
                </div>
                <div class="notifications">
                    <button class="btn btn-light position-relative">
                        <i class="fas fa-bell"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            3
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row">
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-icon primary">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="stats-info">
                        <h3><?php echo $stats['pending_tasks'] ?? 0; ?></h3>
                        <p>Pending Pickups</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stats-info">
                        <h3><?php echo $stats['completed_tasks'] ?? 0; ?></h3>
                        <p>Completed Pickups</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-icon warning">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="stats-info">
                        <h3><?php echo $stats['overdue_tasks'] ?? 0; ?></h3>
                        <p>Overdue Pickups</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Task List -->
        <div class="task-list">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Recent Pickups</h5>
                <a href="mypickups.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <?php foreach($tasks as $task): ?>
            <div class="task-item">
                <div class="task-info">
                    <div class="task-title"><?php echo htmlspecialchars($task['pickup_address']); ?></div>
                    <small class="text-muted">Scheduled: <?php echo date('d M Y, h:i A', strtotime($task['pickup_date'])); ?></small>
                </div>
                <div class="task-status <?php echo strtolower($task['status']); ?>">
                    <?php echo $task['status']; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Recent Activity -->
        <div class="recent-activity">
            <h5 class="mb-3">Recent Activity</h5>
            <?php foreach($activities as $activity): ?>
            <div class="activity-item">
                <div class="activity-icon">
                    <i class="fas <?php echo $activity['icon']; ?>"></i>
                </div>
                <div class="activity-details">
                    <div class="activity-title"><?php echo htmlspecialchars($activity['description']); ?></div>
                    <div class="activity-time"><?php echo timeAgo($activity['activity_time']); ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Add necessary JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    function updateAvailability(status) {
        fetch('update_availability.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `employee_id=<?php echo $employee['employee_id']; ?>&availability=${status}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Availability updated successfully', 'success');
            } else {
                showToast('Failed to update availability', 'error');
            }
        });
    }

    function showToast(message, type = 'success') {
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast-notification ${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);

        // Remove toast after 3 seconds
        setTimeout(() => toast.remove(), 3000);
    }
    </script>
</body>
</html>