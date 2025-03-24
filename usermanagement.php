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
            background: #f5f6fa;
            min-height: 100vh;
        }

        .main-content {
            margin-left: 280px;
            padding: 20px;
        }

        .header {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
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
        color: var(--primary);
        padding: 10px;
        text-decoration: none;
        display: block;
        transition: background-color 0.3s;
    }

    .profile-dropdown-content a:hover {
        background-color: var(--primary-light);
        color: var(--primary-dark);
    }

    .profile-dropdown:hover .profile-dropdown-content {
        display: block;
    }

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

    .toggle-btn:hover {
        opacity: 0.8;
    }

    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input {
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 5px 10px;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 5px 10px;
        border-radius: 5px;
        margin: 0 2px;
        transition: background-color 0.3s;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: var(--primary-light);
        color: var(--primary-dark);
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: var(--primary);
        color: white;
    }

    .dataTables_wrapper .dataTables_info {
        color: #666;
    }
    </style>
</head>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | JunkGenie Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'sidebar.php'; ?>

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