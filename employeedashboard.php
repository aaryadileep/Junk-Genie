<?php
session_start();
require_once 'connect.php';

// Check if user is logged in and is an employee
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Employee') {
    echo "<script>window.location.href = 'login.php';</script>";
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Set session timeout (e.g., 30 minutes)
$timeout_duration = 1800; // 30 minutes in seconds

// Check if session has expired
if (isset($_SESSION['last_activity']) && 
    (time() - $_SESSION['last_activity'] > $timeout_duration)) {
    // Session has expired, destroy it and redirect to login
    session_unset();
    session_destroy();
    echo "<script>window.location.href = 'login.php?msg=timeout';</script>";
    exit();
}

// Store login time if not already set
if (!isset($_SESSION['login_time'])) {
    $_SESSION['login_time'] = date('Y-m-d H:i:s');
}

// Get employee details from session
$user_id = $_SESSION['user_id'];
$employee_name = $_SESSION['fullname'] ?? 'Employee';
$employee_role = $_SESSION['role'];

// You can also store these in session if needed
$_SESSION['current_page'] = basename($_SERVER['PHP_SELF']);
$_SESSION['last_page_access'] = date('Y-m-d H:i:s');

// Get employee ID
$emp_query = "SELECT employee_id FROM employees WHERE user_id = ?";
$emp_stmt = $conn->prepare($emp_query);
$emp_stmt->bind_param("i", $user_id);
$emp_stmt->execute();
$emp_result = $emp_stmt->get_result();
$emp_data = $emp_result->fetch_assoc();
$employee_id = $emp_data['employee_id'];

// Fetch statistics
$stats_query = "SELECT 
    COUNT(*) as total_pickups,
    SUM(CASE WHEN pickup_status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN pickup_status = 'Completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN pickup_status = 'Rejected' THEN 1 ELSE 0 END) as rejected
    FROM cart 
    WHERE assigned_employee_id = ?";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("i", $employee_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

// Fetch today's pickups
$today_query = "SELECT c.id, u.fullname AS customer_name, 
    ua.address_line, ci.city_name, c.pickup_status
    FROM cart c 
    JOIN users u ON c.user_id = u.user_id 
    JOIN user_addresses ua ON c.address_id = ua.address_id
    JOIN cities ci ON ua.city_id = ci.city_id
    WHERE c.assigned_employee_id = ? 
    AND DATE(c.pickup_date) = CURDATE()
    ORDER BY c.pickup_date ASC";
$today_stmt = $conn->prepare($today_query);
$today_stmt->bind_param("i", $employee_id);
$today_stmt->execute();
$today_pickups = $today_stmt->get_result();

// Fetch recent activity
$recent_query = "SELECT c.id, u.fullname AS customer_name, 
    c.pickup_status, c.pickup_date
    FROM cart c 
    JOIN users u ON c.user_id = u.user_id 
    WHERE c.assigned_employee_id = ? 
    ORDER BY c.pickup_date DESC LIMIT 5";
$recent_stmt = $conn->prepare($recent_query);
$recent_stmt->bind_param("i", $employee_id);
$recent_stmt->execute();
$recent_activity = $recent_stmt->get_result();

// At the top with other queries, add this:
$user_query = $conn->prepare("SELECT u.fullname, e.Availability 
                            FROM users u 
                            JOIN employees e ON u.user_id = e.user_id 
                            WHERE u.user_id = ?");
$user_query->bind_param("i", $_SESSION['user_id']);
$user_query->execute();
$user_data = $user_query->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard | JunkGenie</title>
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
            width: 50px;  /* Adjusted logo size */
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
            margin-right:5px;
            padding: 2rem;
            min-height: 100vh;
        }

        .dashboard-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
        }

        .stats-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-pending { background: #FFF3E0; color: #E65100; }
        .status-completed { background: #E8F5E9; color: #2E7D32; }
        .status-rejected { background: #FFEBEE; color: #C62828; }

        .activity-item {
            padding: 1rem;
            border-bottom: 1px solid #eee;
            transition: background-color 0.3s ease;
        }

        .activity-item:hover {
            background-color: #f8f9fa;
        }

        .today-pickup-card {
            border-left: 4px solid var(--primary-green);
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .today-pickup-card:hover {
            transform: translateX(5px);
        }

        .welcome-section {
           
            padding: 2rem;
        }

        .welcome-message {
            color: #2E7D32;
            font-weight: 500;
        }

        .date-text {
            color: #666;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <?php include 'esidebar.php'; ?>

    <div class="main-content">
        <div class="welcome-section">
            <div class="dashboard-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="welcome-message">Welcome back, <?php echo htmlspecialchars($user_data['fullname'] ?? 'Employee'); ?>!</h2>
                        <p class="date-text mb-0"><?php echo date('l, F d, Y'); ?></p>
                    </div>
                    
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="dashboard-card text-center">
                    <div class="stats-icon text-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3><?php echo $stats['pending'] ?? 0; ?></h3>
                    <p class="text-muted mb-0">Pending Pickups</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-card text-center">
                    <div class="stats-icon text-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3><?php echo $stats['completed'] ?? 0; ?></h3>
                    <p class="text-muted mb-0">Completed Pickups</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-card text-center">
                    <div class="stats-icon text-danger">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <h3><?php echo $stats['rejected'] ?? 0; ?></h3>
                    <p class="text-muted mb-0">Rejected Pickups</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-card text-center">
                    <div class="stats-icon text-primary">
                        <i class="fas fa-truck"></i>
                    </div>
                    <h3><?php echo $stats['total_pickups'] ?? 0; ?></h3>
                    <p class="text-muted mb-0">Total Pickups</p>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-8">
                <div class="dashboard-card">
                    <h4 class="mb-4">Today's Pickups</h4>
                    <?php if ($today_pickups->num_rows > 0): ?>
                        <?php while ($pickup = $today_pickups->fetch_assoc()): ?>
                            <div class="today-pickup-card p-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5>OI<?php echo $pickup['id']; ?></h5>
                                        <p class="mb-1"><?php echo htmlspecialchars($pickup['customer_name']); ?></p>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($pickup['address_line'] . ', ' . $pickup['city_name']); ?>
                                        </small>
                                    </div>
                                    <span class="status-badge status-<?php echo strtolower($pickup['pickup_status']); ?>">
                                        <?php echo $pickup['pickup_status']; ?>
                                    </span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-center text-muted">No pickups scheduled for today</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-md-4">
                <div class="dashboard-card">
                    <h4 class="mb-4">Recent Activity</h4>
                    <?php while ($activity = $recent_activity->fetch_assoc()): ?>
                        <div class="activity-item">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="mb-1">OI<?php echo $activity['id']; ?></h6>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($activity['customer_name']); ?>
                                    </small>
                                </div>
                                <span class="status-badge status-<?php echo strtolower($activity['pickup_status']); ?>">
                                    <?php echo $activity['pickup_status']; ?>
                                </span>
                            </div>
                            <small class="text-muted">
                                <?php echo date('M d, Y', strtotime($activity['pickup_date'])); ?>
                            </small>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>