<?php
session_start();
require_once 'connect.php';

// Ensure the user is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Handle assigning pickup request to employee
if (isset($_POST['assign_employee'])) {
    try {
        $cart_id = $_POST['cart_id'];
        $employee_id = $_POST['employee_id'];

        // Check if the employee is available and in the same city as the user
        $stmt = $conn->prepare("
            SELECT e.employee_id 
            FROM employees e
            JOIN users u ON e.user_id = u.user_id
            WHERE e.employee_id = ? AND e.availability = 'Available' 
            AND u.city_id = (
                SELECT u2.city_id 
                FROM users u2 
                WHERE u2.user_id = (
                    SELECT c.user_id FROM cart c WHERE c.id = ?
                )
            )
        ");
        $stmt->bind_param("ii", $employee_id, $cart_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("Employee is not available or not in the same city.");
        }

        // Assign the employee
        $stmt = $conn->prepare("UPDATE cart SET assigned_employee_id = ? WHERE id = ?");

        $stmt->bind_param("ii", $employee_id, $cart_id);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Employee assigned successfully!";
        } else {
            throw new Exception("Failed to assign employee.");
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    header("Location: pickuprequestmanagement.php");
    exit();
}

// Fetch pickup requests with matching employees in the same city
$query = "
    SELECT c.*, u.fullname AS user_name, ua.address_line, ua.landmark, ua.pincode, city.city_name, 
           e.employee_id, eu.fullname AS employee_name, u.city_id
    FROM cart c
    JOIN users u ON c.user_id = u.user_id
    LEFT JOIN user_addresses ua ON c.address_id = ua.address_id
    LEFT JOIN cities city ON u.city_id = city.city_id
    LEFT JOIN employees e ON c.assigned_employee_id = e.employee_id
    LEFT JOIN users eu ON e.user_id = eu.user_id
    ORDER BY c.created_at DESC
";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pickup Request Management | Admin</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    
    <style>
        /* Custom Variables */
        :root {
            --primary-green: #2ecc71;
            --secondary-green: #27ae60;
            --light-green: #d4edda;
            --dark-green: #155724;
            --background-soft: #f4f6f7;
        }

        body {
            background-color: var(--background-soft);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .sidebar {
            background-color: var(--primary-green);
            color: white;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            transition: all 0.3s ease;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }

        .sidebar-logo {
            padding: 20px;
            text-align: center;
            background-color: var(--secondary-green);
        }

        .sidebar-menu {
            padding: 20px;
        }

        .sidebar-menu a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 10px 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .sidebar-menu a:hover {
            background-color: rgba(255,255,255,0.2);
        }

        .container-fluid {
            margin-left: 280px;
            width: calc(100% - 280px);
            padding: 20px;
            transition: all 0.3s ease;
        }

        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-left: 5px solid var(--primary-green);
            transition: transform 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stats-icon {
            color: var(--primary-green);
            font-size: 2.5rem;
            margin-bottom: 15px;
        }


        /* Container Fluid Styles */
        .container-fluid {
            margin-left: 280px;
            width: calc(100% - 280px);
            padding: 30px;
            transition: margin-left 0.3s ease;
        }

        /* Card Styles */
        .card {
            border-radius: var(--border-radius);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.08);
            border: none;
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-top-left-radius: var(--border-radius);
            border-top-right-radius: var(--border-radius);
            padding: 15px 20px;
        }

        /* Table Styles */
        .table {
            margin-bottom: 0;
        }

        .table thead {
            background-color: #E8F5E9;
        }

        .table th {
            font-weight: 600;
            color: var(--primary-color);
            border-bottom: 2px solid var(--secondary-color);
        }

        .table-hover tbody tr:hover {
            background-color: rgba(76, 175, 80, 0.05);
            transition: background-color 0.3s ease;
        }

        /* Status Badge Styles */
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .status-Pending {
            background: #FFC107;
            color: #212121;
        }

        .status-Confirmed {
            background: #4CAF50;
            color: white;
        }

        .status-Completed {
            background: #2E7D32;
            color: white;
        }

        .status-Rejected,
        .status-Cancelled {
            background: #F44336;
            color: white;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .container-fluid {
                margin-left: 0;
                width: 100%;
            }
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.show {
                transform: translateX(0);
            }
        }

        /* Typography */
        h2, h5 {
            font-weight: 600;
            color: var(--primary-color);
        }

        /* Form Styles */
        .form-select, .btn {
            border-radius: 8px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="container-fluid">
        <h2 class="mb-4">
            <i class="fas fa-truck me-2" style="color: var(--primary-color);"></i> Pickup Request Management
        </h2>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="m-0">
                    <i class="fas fa-list me-2"></i> Pickup Requests
                </h5>
                
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Pickup Date</th>
                                <th>Address</th>
                                <th>Status</th>
                                <th>Assigned Employee</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                                <td><?php echo date('d M Y', strtotime($row['pickup_date'])); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($row['address_line']); ?>
                                    <?php if ($row['landmark']): ?><br><small class="text-muted">Landmark: <?php echo htmlspecialchars($row['landmark']); ?></small><?php endif; ?>
                                    <?php if ($row['city_name']): ?><br><small class="text-muted">City: <?php echo htmlspecialchars($row['city_name']); ?></small><?php endif; ?>
                                    <?php if ($row['pincode']): ?><br><small class="text-muted">Pincode: <?php echo htmlspecialchars($row['pincode']); ?></small><?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo str_replace(' ', '', $row['pickup_status']); ?>">
                                        <?php echo $row['pickup_status']; ?>
                                    </span>
                                </td>
                                <td><?php echo $row['employee_name'] ? htmlspecialchars($row['employee_name']) : 'Not Assigned'; ?></td>
                                <td>
                                    <?php if (!$row['employee_name']): ?>
                                    <form method="POST" class="d-flex align-items-center">
                                        <input type="hidden" name="cart_id" value="<?php echo $row['id']; ?>">
                                        <select name="employee_id" class="form-select form-select-sm me-2" required>
                                            <option value="">Select Employee</option>
                                            <?php
                                            $city_id = $row['city_id'];
                                            $employee_query = $conn->prepare("
                                                SELECT e.employee_id, u.fullname 
                                                FROM employees e
                                                JOIN users u ON e.user_id = u.user_id
                                                WHERE e.availability = 'Available' AND u.city_id = ?
                                            ");
                                            $employee_query->bind_param("i", $city_id);
                                            $employee_query->execute();
                                            $employee_result = $employee_query->get_result();

                                            while ($employee = $employee_result->fetch_assoc()):
                                            ?>
                                                <option value="<?php echo $employee['employee_id']; ?>">
                                                    <?php echo htmlspecialchars($employee['fullname']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <button type="submit" name="assign_employee" class="btn btn-primary btn-sm">
                                            <i class="fas fa-user-plus"></i>
                                        </button>
                                    </form>
                                    <?php else: ?>
                                        <span class="badge bg-success">Assigned</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
</body>
</html>