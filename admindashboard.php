<?php
session_start();
require_once 'connect.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user details from session
$fullname = $_SESSION['fullname'];

// Fetch total users count
$users_query = "SELECT COUNT(*) as total_users FROM users WHERE role = 'End User'";
$users_result = $conn->query($users_query);
$total_users = $users_result->fetch_assoc()['total_users'];

// Fetch active employees count
$employees_query = "SELECT COUNT(*) as total_employees 
                   FROM employees e 
                   JOIN users u ON e.user_id = u.user_id 
                   WHERE e.availability = 'Available'";
$employees_result = $conn->query($employees_query);
$active_employees = $employees_result->fetch_assoc()['total_employees'];

// Fetch pending pickups count
$pending_query = "SELECT COUNT(*) as pending_pickups FROM cart WHERE pickup_status = 'Pending'";
$pending_result = $conn->query($pending_query);
$pending_pickups = $pending_result->fetch_assoc()['pending_pickups'];

// Fetch total e-waste collected (from completed pickups)
$ewaste_query = "SELECT COUNT(*) as total_items 
                 FROM cart c 
                 JOIN cart_items ci ON c.id = ci.cart_id 
                 WHERE c.pickup_status = 'Completed'";
$ewaste_result = $conn->query($ewaste_query);
$total_ewaste = $ewaste_result->fetch_assoc()['total_items'];

// Fetch cities count
$cities_query = "SELECT COUNT(*) as total_cities FROM cities";
$cities_result = $conn->query($cities_query);
$total_cities = $cities_result->fetch_assoc()['total_cities'];

// Fetch daily pickup data for bar chart (last 7 days)
$daily_data_query = "SELECT 
    DATE_FORMAT(pickup_date, '%d %b') as day,
    COUNT(*) as pickup_count
    FROM cart
    WHERE pickup_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(pickup_date)
    ORDER BY pickup_date";
$daily_data_result = $conn->query($daily_data_query);

$days = [];
$daily_counts = [];
while ($row = $daily_data_result->fetch_assoc()) {
    $days[] = $row['day'];
    $daily_counts[] = $row['pickup_count'];
}

// Fetch pickup status data for pie chart
$status_query = "SELECT 
    pickup_status,
    COUNT(*) as status_count
    FROM cart
    GROUP BY pickup_status";
$status_result = $conn->query($status_query);

$status_labels = [];
$status_counts = [];
$status_colors = [
    'Completed' => 'rgba(46, 204, 113, 0.8)',
    'Pending' => 'rgba(241, 196, 15, 0.8)',
    'Cancelled' => 'rgba(231, 76, 60, 0.8)'
];

while ($row = $status_result->fetch_assoc()) {
    $status_labels[] = $row['pickup_status'];
    $status_counts[] = $row['status_count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Waste Management Admin Dashboard</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        :root {
            --primary-color: #4CAF50; /* Green */
            --secondary-color: #388E3C; /* Dark Green */
            --accent-color: #8BC34A; /* Light Green */
            --success-color: #2ecc71;
            --warning-color: #f1c40f;
            --danger-color: #e74c3c;
            --background-color: #f5f6fa;
            --text-color: #333;
        }

        body {
            display: flex;
            background-color: var(--background-color);
        }

        /* Main Content Styles */
        .main-content {
            margin-left: 280px; /* Match sidebar width from sidebar.php */
            padding: 20px;
            width: calc(100% - 280px);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background-color: white;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            color: var(--primary-color);
            font-size: 24px;
            display: flex;
            align-items: center;
        }

        .header h1 img {
            width: 40px;
            height: 40px;
            margin-right: 10px;
        }

        .profile-dropdown {
            position: relative;
            display: inline-block;
        }

        .profile-dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: white;
            min-width: 150px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            z-index: 1;
            border-radius: 5px;
            overflow: hidden;
        }

        .profile-dropdown-content a {
            color: var(--primary-color);
            padding: 10px;
            text-decoration: none;
            display: block;
            transition: background-color 0.3s;
        }

        .profile-dropdown-content a:hover {
            background-color: var(--secondary-color);
            color: white;
        }

        .profile-dropdown:hover .profile-dropdown-content {
            display: block;
        }

        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .card-title {
            font-size: 16px;
            color: var(--secondary-color);
            margin-bottom: 10px;
        }

        .card-value {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-color);
        }

        .chart-container {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
        }

        .btn-primary {
            background-color: var(--accent-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                width: 100%;
            }

            .dashboard-cards {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -15px;
        }
        .col-md-8 {
            flex: 0 0 66.666667%;
            max-width: 66.666667%;
            padding: 0 15px;
        }
        .col-md-4 {
            flex: 0 0 33.333333%;
            max-width: 33.333333%;
            padding: 0 15px;
        }
        @media (max-width: 768px) {
            .col-md-8, .col-md-4 {
                flex: 0 0 100%;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>
                <img src="logo.png" alt="JunkGenie Logo">
                JunkGenie Dashboard
            </h1>
            <div class="profile-dropdown">
                <button class="btn btn-primary"><?php echo htmlspecialchars($fullname); ?></button>
                <div class="profile-dropdown-content">
                   
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </div>

        <div class="dashboard-cards">
            <div class="card">
                <div class="card-title">Total Users</div>
                <div class="card-value"><?php echo number_format($total_users); ?></div>
            </div>
            <div class="card">
                <div class="card-title">Active Employees</div>
                <div class="card-value"><?php echo number_format($active_employees); ?></div>
            </div>
            <div class="card">
                <div class="card-title">Pending Pickups</div>
                <div class="card-value"><?php echo number_format($pending_pickups); ?></div>
            </div>
            <div class="card">
                <div class="card-title">Total Items Collected</div>
                <div class="card-value"><?php echo number_format($total_ewaste); ?> </div>
            </div>
            <div class="card">
                <div class="card-title">Total Cities</div>
                <div class="card-value"><?php echo number_format($total_cities); ?></div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="chart-container">
                    <canvas id="dailyChart"></canvas>
                </div>
            </div>
            <div class="col-md-4">
                <div class="chart-container">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Daily Pickups Bar Chart
    const dailyCtx = document.getElementById('dailyChart').getContext('2d');
    new Chart(dailyCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($days); ?>,
            datasets: [{
                label: 'Daily Pickups',
                data: <?php echo json_encode($daily_counts); ?>,
                backgroundColor: 'rgba(76, 175, 80, 0.6)',
                borderColor: 'rgba(76, 175, 80, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Daily Pickup Trends (Last 7 Days)',
                    font: { size: 16 }
                },
                legend: {
                    position: 'bottom'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            }
        }
    });

    // Pickup Status Pie Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($status_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($status_counts); ?>,
                backgroundColor: <?php echo json_encode(array_values($status_colors)); ?>,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Pickup Status Distribution',
                    font: { size: 16 }
                },
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Auto-refresh dashboard data every 5 minutes
    setInterval(function() {
        location.reload();
    }, 300000);
    </script>
</body>
</html>