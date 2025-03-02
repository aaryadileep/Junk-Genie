<?php
session_start();
require_once 'connect.php';

// Ensure only Admins can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Fetch Cities for Dropdown
$cityQuery = "SELECT city_id, city_name FROM cities WHERE is_active = 1";
$cityResult = $conn->query($cityQuery);
$cities = [];
while ($row = $cityResult->fetch_assoc()) {
    $cities[] = $row;
}

// Handle Employee Addition
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $city_id = trim($_POST['city_id']);

    // Validations
    if (empty($fullname) || empty($email) || empty($phone) || empty($password) || empty($confirm_password) || empty($city_id)) {
        $_SESSION['error'] = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format.";
    } elseif ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match.";
    } elseif (!preg_match("/^[0-9]{10}$/", $phone)) {
        $_SESSION['error'] = "Phone number must be 10 digits.";
    } else {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Insert Employee into Database
        $stmt = $conn->prepare("INSERT INTO users (fullname, email, phone, password, city_id, role, is_active) VALUES (?, ?, ?, ?, ?, 'Employee', 1)");
        $stmt->bind_param("ssssi", $fullname, $email, $phone, $hashed_password, $city_id);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Employee added successfully.";
        } else {
            $_SESSION['error'] = "Error adding employee.";
        }
    }
    header("Location: employeemanagement.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Employee - Junk Genie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        function validateForm() {
            let fullname = document.getElementById("fullname").value.trim();
            let email = document.getElementById("email").value.trim();
            let phone = document.getElementById("phone").value.trim();
            let password = document.getElementById("password").value.trim();
            let confirm_password = document.getElementById("confirm_password").value.trim();
            let city = document.getElementById("city_id").value;

            if (!fullname || !email || !phone || !password || !confirm_password || !city) {
                alert("All fields are required.");
                return false;
            }
            if (!/^\S+@\S+\.\S+$/.test(email)) {
                alert("Invalid email format.");
                return false;
            }
            if (!/^\d{10}$/.test(phone)) {
                alert("Phone number must be 10 digits.");
                return false;
            }
            if (password !== confirm_password) {
                alert("Passwords do not match.");
                return false;
            }
            return true;
        }
    </script>
</head>
<body class="container mt-5">
    <h2 class="mb-3">Add Employee</h2>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <form method="POST" onsubmit="return validateForm()">
        <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" id="fullname" name="fullname" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" id="email" name="email" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" id="phone" name="phone" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">City</label>
            <select id="city_id" name="city_id" class="form-select" required>
                <option value="">Select City</option>
                <?php foreach ($cities as $city): ?>
                    <option value="<?= $city['city_id']; ?>"><?= htmlspecialchars($city['city_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" id="password" name="password" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary">Add Employee</button>
        <a href="employeemanagement.php" class="btn btn-secondary">Back</a>
    </form>
</body>
</html>
