<?php
session_start();
require_once 'connect.php';

// Check if user is admin
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user role
$roleStmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
$roleStmt->bind_param("i", $_SESSION['user_id']);
$roleStmt->execute();
$roleResult = $roleStmt->get_result();
$userRole = $roleResult->fetch_assoc()['role'];

if ($userRole !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle employee assignment
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_employee'])) {
    $cart_id = $_POST['cart_id'];
    $employee_id = $_POST['employee_id'];
    
    try {
        $conn->begin_transaction();
        
        // Update cart with assigned employee
        $updateStmt = $conn->prepare("UPDATE cart SET employee_id = ? WHERE id = ?");
        $updateStmt->bind_param("ii", $employee_id, $cart_id);
        
        if (!$updateStmt->execute()) {
            throw new Exception("Failed to assign employee");
        }
        
        $conn->commit();
        
        echo "<script>
                Swal.fire({
                    title: 'Success!',
                    text: 'Employee assigned successfully',
                    icon: 'success',
                    showConfirmButton: false,
                    timer: 1500
                });
              </script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>
                Swal.fire({
                    title: 'Error!',
                    text: 'Failed to assign employee: " . $e->getMessage() . "',
                    icon: 'error'
                });
              </script>";
    }
}

// Fetch all pickup requests with user and city details
$query = "SELECT c.*, u.fullname as user_name, u.phone as user_phone, 
          ci.city_name, e.fullname as employee_name, e.phone as employee_phone
          FROM cart c
          JOIN users u ON c.user_id = u.user_id
          JOIN cities ci ON u.city_id = ci.city_id
          LEFT JOIN employees e ON c.employee_id = e.employee_id
          WHERE c.pickup_status = 'Pending'
          ORDER BY c.pickup_date ASC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pickup Requests | Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        .request-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .request-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
        }
        .status-pending {
            background-color: #ffc107;
            color: #000;
        }
        .status-assigned {
            background-color: #17a2b8;
            color: white;
        }
        .status-completed {
            background-color: #28a745;
            color: white;
        }
        .employee-select {
            max-width: 200px;
        }
    </style>
</head>
<body>
    <?php include 'admin_navbar.php'; ?>

    <div class="container py-5">
        <h2 class="mb-4">Pickup Requests</h2>
        
        <?php if ($result->num_rows > 0): ?>
            <?php while ($request = $result->fetch_assoc()): ?>
                <div class="request-card">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Request #<?= $request['id'] ?></h5>
                            <p><strong>Customer:</strong> <?= htmlspecialchars($request['user_name']) ?></p>
                            <p><strong>Phone:</strong> <?= htmlspecialchars($request['user_phone']) ?></p>
                            <p><strong>City:</strong> <?= htmlspecialchars($request['city_name']) ?></p>
                            <p><strong>Pickup Date:</strong> <?= date('d M Y', strtotime($request['pickup_date'])) ?></p>
                        </div>
                        <div class="col-md-6">
                            <?php if (!$request['employee_id']): ?>
                                <form method="POST" class="mt-3">
                                    <input type="hidden" name="cart_id" value="<?= $request['id'] ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Assign Employee</label>
                                        <select name="employee_id" class="form-select employee-select" required>
                                            <option value="">Select Employee</option>
                                            <?php
                                            // Fetch employees in the same city
                                            $empStmt = $conn->prepare("SELECT e.* FROM employees e 
                                                                   JOIN cities c ON e.city_id = c.city_id 
                                                                   WHERE c.city_id = ?");
                                            $empStmt->bind_param("i", $request['city_id']);
                                            $empStmt->execute();
                                            $employees = $empStmt->get_result();
                                            
                                            while ($emp = $employees->fetch_assoc()):
                                            ?>
                                                <option value="<?= $emp['employee_id'] ?>">
                                                    <?= htmlspecialchars($emp['fullname']) ?> 
                                                    (<?= htmlspecialchars($emp['phone']) ?>)
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <button type="submit" name="assign_employee" class="btn btn-primary">
                                        Assign Employee
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="mt-3">
                                    <p><strong>Assigned Employee:</strong> <?= htmlspecialchars($request['employee_name']) ?></p>
                                    <p><strong>Employee Phone:</strong> <?= htmlspecialchars($request['employee_phone']) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="alert alert-info">
                No pending pickup requests found.
            </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html> 