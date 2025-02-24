<!DOCTYPE html>
<html lang="en">
<head>
<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit();
}

// Get user details from session
$fullname = $_SESSION['fullname'];
?>
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
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #3498db;
            --success-color: #2ecc71;
            --warning-color: #f1c40f;
            --danger-color: #e74c3c;
            --background-color: #f5f6fa;
        }

        body {
            display: flex;
            background-color: var(--background-color);
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            height: 100vh;
            background-color: var(--primary-color);
            padding: 20px;
            position: fixed;
            left: 0;
            top: 0;
            overflow-y: auto;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }

        .logo {
            color: white;
            font-size: 24px;
            margin-bottom: 30px;
            text-align: center;
            font-weight: bold;
        }

        .nav-menu {
            list-style: none;
        }

        .nav-item {
            margin-bottom: 10px;
        }

        .nav-link {
            color: #fff;
            text-decoration: none;
            padding: 10px;
            display: flex;
            align-items: center;
            border-radius: 5px;
            transition: background-color 0.3s, transform 0.2s;
        }

        .nav-link:hover {
            background-color: var(--secondary-color);
            transform: translateX(5px);
        }

        .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Main Content Styles */
        .main-content {
            margin-left: 250px;
            padding: 20px;
            width: calc(100% - 250px);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background-color: white;
            padding: 15px;
           
            0.1);
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
            background-color: #2980b9;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main-content {
                margin-left: 0;
                width: 100%;
            }

            .dashboard-cards {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">Admin</div>
        <ul class="nav-menu">
            <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-tachometer-alt"></i>Dashboard</a></li>
            <li class="nav-item"><a href="usermanagement.php" class="nav-link"><i class="fas fa-users"></i>User Management</a></li>
            <li class="nav-item"><a href="employeemanagement.php" class="nav-link"><i class="fas fa-user-tie"></i>Employee Management</a></li>
            <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-box"></i>Categories & Products</a></li>
            <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-truck"></i>Pickup Requests</a></li>
            <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-recycle"></i>E-Waste Collection</a></li>
            <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-chart-line"></i>Reports & Analytics</a></li>
            <li class="nav-item"><a href="citymanagement.php" class="nav-link"><i class="fas fa-city"></i>City Management</a></li>
            <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-cog"></i>Settings</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Dashboard Overview</h1>
            <div class="profile-dropdown">
                <button class="btn btn-primary">Admin Profile</button>
                <p><?php echo htmlspecialchars($fullname); ?></p>
                <div class="profile-dropdown-content">
                    <a href="#">Profile</a>
                    <a href="#">Settings</a>
                    <a href="#">Logout</a>
                </div>
            </div>
        </div>

        <div class="dashboard-cards">
            <div class="card">
                <div class="card-title">Total Users</div>
                <div class="card-value">1,234</div>
            </div>
            <div class="card">
                <div class="card-title">Active Employees</div>
                <div class="card-value">45</div>
            </div>
            <div class="card">
                <div class="card-title">Pending Pickups</div>
                <div class="card-value">28</div>
            </div>
            <div class="card">
                <div class="card-title">Total E-Waste Collected</div>
                <div class="card-value">2.5 tons</div>
            </div>

            <!-- City Count Card -->
            <div class="card">
                <div class="card-title">Total Cities</div>
                <div class="card-value" id="city-count">Loading...</div>
            </div>
        </div>

        <div class="chart-container">
            <canvas id="myChart"></canvas>
        </div>
    </div>

    <script>
        // Fetch City Count using AJAX
        function fetchCityCount() {
            fetch('getCityCount.php')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('city-count').textContent = data.cityCount;
                })
                .catch(error => console.log(error));
        }

        // Call the function when page loads
        window.onload = function() {
            fetchCityCount();
        };
    </script>
</body>
</html>
