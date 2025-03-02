<?php
session_start();
require_once 'connect.php';

// Restrict Access to Admins Only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Fetch Active Cities for Dropdown
$cities = [];
$cityQuery = "SELECT city_id, city_name FROM cities WHERE is_active = 1";
if ($result = $conn->query($cityQuery)) {
    while ($row = $result->fetch_assoc()) {
        $cities[] = $row;
    }
}

// Handle Employee Addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_employee'])) {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = password_hash(trim($_POST['password']), PASSWORD_BCRYPT);
    $city_id = trim($_POST['city_id']);

    $stmt = $conn->prepare("INSERT INTO users (fullname, email, phone, password, city_id, role, is_active) VALUES (?, ?, ?, ?, ?, 'Employee', 1)");
    $stmt->bind_param("ssssi", $fullname, $email, $phone, $password, $city_id);
    $_SESSION[$stmt->execute() ? 'success' : 'error'] = $stmt->execute() ? "Employee added successfully" : "Error adding employee";
    header("Location: employeemanagement.php");
    exit();
}

// Handle Employee Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_employee'])) {
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ? AND role='Employee'");
    $stmt->bind_param("i", $_POST['user_id']);
    $_SESSION[$stmt->execute() ? 'success' : 'error'] = $stmt->execute() ? "Employee deleted successfully" : "Error deleting employee";
    header("Location: employeemanagement.php");
    exit();
}

// Fetch Employees List
$employees = $conn->query("SELECT u.user_id, u.fullname, u.email, u.phone, c.city_name, u.is_active FROM users u LEFT JOIN cities c ON u.city_id = c.city_id WHERE u.role = 'Employee' ORDER BY u.fullname");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Management - Junk Genie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/admin.css" rel="stylesheet">
</head>
<body class="d-flex bg-light">
    <nav class="sidebar bg-dark text-white p-3">
        <h3 class="text-center">Admin Panel</h3>
        <ul class="nav flex-column">
            <li><a href="admindashboard.php" class="nav-link text-white"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="employeemanagement.php" class="nav-link text-white"><i class="fas fa-user-tie"></i> Employee Management</a></li>
        </ul>
    </nav>

    <main class="p-4 w-100">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Employee Management</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmployeeModal"><i class="fas fa-plus"></i> Add Employee</button>
        </div>

        <div class="table-responsive bg-white p-3 rounded shadow">
            <table class="table table-striped">
                <thead class="bg-dark text-white">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>City</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $employees->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['fullname']); ?></td>
                            <td><?= htmlspecialchars($row['email']); ?></td>
                            <td><?= htmlspecialchars($row['phone']); ?></td>
                            <td><?= htmlspecialchars($row['city_name']); ?></td>
                            <td><?= $row['is_active'] ? 'Active' : 'Inactive'; ?></td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="user_id" value="<?= $row['user_id']; ?>">
                                    <button type="submit" name="delete_employee" class="btn btn-danger btn-sm" onclick="return confirm('Delete this employee?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Add Employee Modal -->
    <div class="modal fade" id="addEmployeeModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Employee</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="text" name="fullname" placeholder="Full Name" required class="form-control mb-2">
                        <input type="email" name="email" placeholder="Email" required class="form-control mb-2">
                        <input type="tel" name="phone" placeholder="Phone" required class="form-control mb-2">
                        <input type="password" name="password" placeholder="Password" required class="form-control mb-2">
                        <select name="city_id" class="form-control mb-2" required>
                            <option value="">Select City</option>
                            <?php foreach ($cities as $city): ?>
                                <option value="<?= $city['city_id']; ?>"><?= htmlspecialchars($city['city_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="add_employee" class="btn btn-primary">Add</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>