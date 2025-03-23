<?php
session_start();
require_once 'connect.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Handle employee status toggle
if (isset($_POST['toggle_status'])) {
    try {
        $conn->begin_transaction();
        
        $user_id = $_POST['user_id'];
        $new_status = $_POST['new_status'];
        
        $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE user_id = ? AND role = 'Employee'");
        $stmt->bind_param("ii", $new_status, $user_id);
        $stmt->execute();
        
        $conn->commit();
        echo json_encode(['success' => true]);
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
}

// Handle adding new employee
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_employee'])) {
    try {
        $conn->begin_transaction();

        $fullname = trim($_POST['fullname']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
        $city_id = trim($_POST['city_id']);

        // Check if email already exists
        $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            throw new Exception("Email already exists");
        }

        // Insert into users table
        $stmt = $conn->prepare("INSERT INTO users (fullname, email, phone, password, city_id, role, is_active) 
                               VALUES (?, ?, ?, ?, ?, 'Employee', 1)");
        $stmt->bind_param("ssssi", $fullname, $email, $phone, $password, $city_id);
        
        if ($stmt->execute()) {
            $user_id = $conn->insert_id;
            
            // Insert into employees table
            $stmt2 = $conn->prepare("INSERT INTO employees (user_id, availability) VALUES (?, 'Available')");
            $stmt2->bind_param("i", $user_id);
            
            if ($stmt2->execute()) {
                $conn->commit();
                $_SESSION['success'] = "Employee added successfully";
            } else {
                throw new Exception("Failed to create employee record");
            }
        } else {
            throw new Exception("Failed to create user record");
        }
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = $e->getMessage();
    }
    
    header("Location: employeemanagement.php");
    exit();
}

// Handle availability update
if (isset($_POST['update_availability'])) {
    try {
        $employee_id = $_POST['employee_id'];
        $availability = $_POST['availability'];
        
        $stmt = $conn->prepare("UPDATE employees SET availability = ? WHERE employee_id = ?");
        $stmt->bind_param("si", $availability, $employee_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            throw new Exception("Failed to update availability");
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// Fetch cities for dropdown
$cities = $conn->query("SELECT city_id, city_name FROM cities WHERE is_active = 1 ORDER BY city_name")->fetch_all(MYSQLI_ASSOC);

// Fetch employees with row number
$query = "SELECT 
            ROW_NUMBER() OVER (ORDER BY u.created_at) as serial_no,
            u.fullname,
            u.email,
            u.phone,
            c.city_name,
            u.created_at,
            u.is_active,
            e.employee_id,
            e.availability
          FROM users u
          LEFT JOIN cities c ON u.city_id = c.city_id
          LEFT JOIN employees e ON u.user_id = e.user_id
          WHERE u.role = 'Employee'";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Management | JunkGenie Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

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

        .table-container {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .availability-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
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

        .status-toggle {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .status-toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: var(--primary);
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        .modal-content {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            border-radius: 15px 15px 0 0;
            background: linear-gradient(45deg, var(--primary), var(--primary-dark));
        }

        .form-control, .form-select {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #ddd;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25);
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h4 class="mb-0">
                <i class="fas fa-user-tie me-2"></i>
                Employee Management
            </h4>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                <i class="fas fa-plus me-2"></i>Add New Employee
            </button>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="table-container">
            <table id="employeesTable" class="table table-hover">
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>City</th>
                        <th>    </th>
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
                        <td>
                           
                        </td>
                        <td>
                            <span class="status-badge <?php echo $row['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td>
    <label class="status-toggle">
        <input type="checkbox" 
               onchange="toggleStatus(<?php echo $row['employee_id']; ?>, this.checked)"
               <?php echo $row['is_active'] ? 'checked' : ''; ?>>
        <span class="slider"></span>
    </label>
</td>

                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Employee Modal -->
    <div class="modal fade" id="addEmployeeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus me-2"></i>
                        Add New Employee
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addEmployeeForm" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="fullname" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone" pattern="[6789][0-9]{9}" 
                                   title="Please enter valid 10-digit mobile number" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" minlength="8" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">City</label>
                            <select name="city_id" class="form-select" required>
                                <option value="">Select City</option>
                                <?php foreach($cities as $city): ?>
                                    <option value="<?php echo $city['city_id']; ?>">
                                        <?php echo htmlspecialchars($city['city_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="add_employee" class="btn btn-success w-100">
                            <i class="fas fa-plus-circle me-2"></i>Add Employee
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#employeesTable').DataTable({
                "order": [[0, "asc"]],
                "pageLength": 10,
                "language": {
                    "search": "Search employees:",
                    "lengthMenu": "Show _MENU_ employees per page"
                }
            });

            // Form validation
            $('#addEmployeeForm').on('submit', function(e) {
                const password = $('input[name="password"]').val();
                const phone = $('input[name="phone"]').val();
                
                if (password.length < 8) {
                    e.preventDefault();
                    alert('Password must be at least 8 characters long');
                    return false;
                }

                if (!/^[6789]\d{9}$/.test(phone)) {
                    e.preventDefault();
                    alert('Please enter a valid 10-digit mobile number');
                    return false;
                }
            });

            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
        });

        function toggleStatus(user_id, status) {
            fetch('employeemanagement.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `toggle_status=1&user_id=${userId}&new_status=${status ? 1 : 0}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Failed to update status: ' + data.message);
                }
            });
        }

        function updateAvailability(employeeId, availability) {
            fetch('employeemanagement.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `update_availability=1&employee_id=${employeeId}&availability=${availability}`
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert('Failed to update availability: ' + data.message);
                }
            });
        }
    </script>
</body>
</html>