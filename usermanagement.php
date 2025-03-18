<?php
session_start();
require_once 'connect.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Handle user status toggle
if (isset($_POST['toggle_status'])) {
    $user_id = $_POST['user_id'];
    $new_status = $_POST['new_status'];
    
    $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE user_id = ?");
    $stmt->bind_param("ii", $new_status, $user_id);
    $stmt->execute();
}

// Fetch users with row number
$query = "SELECT 
            ROW_NUMBER() OVER (ORDER BY u.created_at) as serial_no,
            u.user_id,
            u.fullname,
            u.email,
            u.phone,
            c.city_name,
            u.created_at,
            u.is_active
          FROM users u
          LEFT JOIN cities c ON u.city_id = c.city_id
          WHERE u.role = 'End User'
          ORDER BY u.created_at DESC";

$result = $conn->query($query);

if (!$result) {
    die("Query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | JunkGenie Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
         {
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
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo img {
            width: 40px;
            height: 40px;
            margin-right: 10px;
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

        /* Additional styles for user management */
        .table-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-active {
            background: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
        }

        .status-inactive {
            background: rgba(244, 67, 54, 0.1);
            color: #F44336;
        }

        .toggle-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s ease;
        }

        .toggle-activate {
            background: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
        }

        .toggle-deactivate {
            background: rgba(244, 67, 54, 0.1);
            color: #F44336;
        }

        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 5px 10px;
        }
    </style>
</head>

<body>
<div class="sidebar">
        <div class="logo">
            <img src="logo.jpg" alt="JunkGenie Logo">
            JunkGenie
        </div>
        <ul class="nav-menu">
            <li class="nav-item"><a href="admindashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i>Dashboard</a></li>
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
            <h1>
                <i class="fas fa-users me-2"></i>
                User Management
            </h1>
            <div class="profile-dropdown">
                <button class="btn btn-primary"><?php echo htmlspecialchars($_SESSION['fullname']); ?></button>
                <div class="profile-dropdown-content">
                    <a href="#"><i class="fas fa-user me-2"></i>Profile</a>
                    <a href="#"><i class="fas fa-cog me-2"></i>Settings</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
                </div>
            </div>
        </div>

        <div class="table-container">
            <table id="usersTable" class="table table-hover">
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>City</th>
                        <th>Joined Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['serial_no']; ?></td>
                        <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['phone']); ?></td>
                        <td><?php echo htmlspecialchars($row['city_name'] ?? 'N/A'); ?></td>
                        <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                        <td>
                            <span class="status-badge <?php echo $row['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
                                <input type="hidden" name="new_status" value="<?php echo $row['is_active'] ? 0 : 1; ?>">
                                <button type="submit" name="toggle_status" 
                                        class="toggle-btn <?php echo $row['is_active'] ? 'toggle-deactivate' : 'toggle-activate'; ?>">
                                    <?php echo $row['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#usersTable').DataTable({
                "order": [[0, "asc"]],
                "pageLength": 10,
                "language": {
                    "search": "Search users:",
                    "lengthMenu": "Show _MENU_ users per page",
                    "info": "Showing _START_ to _END_ of _TOTAL_ users",
                    "emptyTable": "No users found"
                }
            });
        });
    </script>
</body>
</html>