<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Reuse the same styles from the dashboard */
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

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background-color: #c0392b;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: var(--primary-color);
            color: white;
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            width: 400px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h2 {
            margin: 0;
        }

        .modal-body {
            margin-bottom: 20px;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--accent-color);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">Admin</div>
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

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h1>User Management</h1>
           
        </div>
<!--table -->
        <table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>City</th>
            <th>Address</th>
            <th>Status</th>
            <th>Registered At</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php
        require_once 'connect.php';

        $query = "SELECT user_id, fullname, email, phone, city, address, status, created_at FROM users WHERE role = 'End User'";

        $result = $conn->query($query);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                    <td>{$row['user_id']}</td>
                    <td>{$row['fullname']}</td>
                    <td>{$row['email']}</td>
                    <td>{$row['phone']}</td>
                    <td>{$row['city']}</td>
                    <td>{$row['address']}</td>
                    <td>{$row['status']}</td>
                    <td>{$row['created_at']}</td>
                    <td class='action-buttons'>
                        <button class='btn btn-danger' onclick='deleteUser({$row['user_id']})'>Delete</button>
                    </td>
                </tr>";
            }
        } else {
            echo "<tr><td colspan='9' style='text-align:center;'>No users found</td></tr>";
        }

        $conn->close();
        ?>
    </tbody>
</table>


       

    <script>
        // Modal Functions
        function openAddUserModal() {
            document.getElementById('addUserModal').style.display = 'flex';
        }

        function closeAddUserModal() {
            document.getElementById('addUserModal').style.display = 'none';
        }

        function openEditUserModal(id) {
            // Fetch user data by ID and populate the form
            document.getElementById('editUserModal').style.display = 'flex';
        }

        function closeEditUserModal() {
            document.getElementById('editUserModal').style.display = 'none';
        }

        // Save User Function
        function saveUser() {
            // Add logic to save user data
            closeAddUserModal();
        }

        // Update User Function
        function updateUser() {
            // Add logic to update user data
            closeEditUserModal();
        }
//delete user
        function deleteUser(userId) {
    if (confirm("Are you sure you want to delete this user?")) {
        window.location.href = "delete_user.php?user_id=" + userId;
    }
}

    </script>
</body>
</html>