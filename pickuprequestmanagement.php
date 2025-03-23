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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        .stats-card {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .stats-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .stats-icon.primary {
            color: #007bff;
        }
        .stats-icon.success {
            color: #28a745;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        .status-Pending {
            background: #ffd700;
            color: #000;
        }
        .status-Confirmed {
            background: #ff9800;
            color: #fff;
        }
        .status-Completed {
            background: #4CAF50;
            color: #fff;
        }
        .status-Rejected,
        .status-Cancelled {
            background: #ff5252;
            color: #fff;
        }
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }
        .container-fluid {
            margin-left: 280px; /* Same width as sidebar */
            width: calc(100% - 280px);
            transition: margin-left 0.3s ease;
        }
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 280px;
            z-index: 1000;
            background: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
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
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="container-fluid p-4">
        <h2><i class="fas fa-truck me-2"></i> Pickup Request Management</h2>

        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list me-2"></i> Pickup Requests</h5>
            </div>
            <div class="card-body">
                <table class="table table-hover">
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
                                <?php if ($row['landmark']): ?><br><strong>Landmark:</strong> <?php echo htmlspecialchars($row['landmark']); ?><?php endif; ?>
                                <?php if ($row['city_name']): ?><br><strong>City:</strong> <?php echo htmlspecialchars($row['city_name']); ?><?php endif; ?>
                                <?php if ($row['pincode']): ?><br><strong>Pincode:</strong> <?php echo htmlspecialchars($row['pincode']); ?><?php endif; ?>
                            </td>
                            <td><?php echo $row['pickup_status']; ?></td>
                            <td><?php echo $row['employee_name'] ? htmlspecialchars($row['employee_name']) : 'Not Assigned'; ?></td>
                            <td>
                                <?php if (!$row['employee_name']): ?>
                                <form method="POST" class="d-flex">
                                    <input type="hidden" name="cart_id" value="<?php echo $row['id']; ?>">
                                    <select name="employee_id" class="form-select me-2" required>
                                        <option value="">Select Employee</option>
                                        <?php
                                        // Fetch employees from the same city as the user
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
                                    <button type="submit" name="assign_employee" class="btn btn-primary">Assign</button>
                                </form>
                                <?php else: ?>
                                    <span class="text-success">Assigned</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
     <!-- Bootstrap JS and dependencies -->
     <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
    <script>
    function assignEmployee(cartId) {
        const employeeId = document.querySelector(`select[data-cart-id="${cartId}"]`).value;
        
        if (!employeeId) {
            alert('Please select an employee');
            return;
        }

        $.ajax({
            url: 'assign_employee.php',
            type: 'POST',
            data: {
                cart_id: cartId,
                employee_id: employeeId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Employee assigned successfully',
                        icon: 'success'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: response.message,
                        icon: 'error'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: 'Error!',
                    text: 'Failed to assign employee',
                    icon: 'error'
                });
            }
        });
    }
    </script>
</body>
</html>
