<?php
session_start();
require_once 'connect.php';

// Ensure only Admins can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Fetch Cities from Database
$cityOptions = "";
$cityQuery = "SELECT city_name FROM cities ORDER BY city_name ASC";
$cityResult = $conn->query($cityQuery);
while ($row = $cityResult->fetch_assoc()) {
    $cityOptions .= "<option value='{$row['city_name']}'>{$row['city_name']}</option>";
}

// Handle Employee Update
if (isset($_POST['update_employee'])) {
    $employee_id = $_POST['employee_id'];
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $city = trim($_POST['city']);
    
    $stmt = $conn->prepare("UPDATE users u 
                           JOIN employees e ON u.user_id = e.user_id 
                           SET u.fullname = ?, u.email = ?, u.phone = ?, u.city = ? 
                           WHERE e.employee_id = ?");
    $stmt->bind_param("ssssi", $fullname, $email, $phone, $city, $employee_id);
    
    if ($stmt->execute()) {
        echo "<script>alert('Employee updated successfully'); window.location.href='employeemanagement.php';</script>";
    } else {
        echo "<script>alert('Error updating employee');</script>";
    }
    $stmt->close();
}

// Handle Employee Deletion
if (isset($_POST['delete_employee'])) {
    $employee_id = $_POST['employee_id'];
    $stmt = $conn->prepare("DELETE FROM employees WHERE employee_id = ?");
    $stmt->bind_param("i", $employee_id);

    if ($stmt->execute()) {
        echo "<script>alert('Employee deleted successfully'); window.location.href='employeemanagement.php';</script>";
    } else {
        echo "<script>alert('Error deleting employee');</script>";
    }
    $stmt->close();
}

// Handle Adding Employees
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_employee'])) {
    $errors = [];
    
    // Validate inputs
    $fullname = trim($_POST['fullname']);
    if (strlen($fullname) < 3 || strlen($fullname) > 50) {
        $errors[] = "Full name must be between 3 and 50 characters";
    }
    
    $email = trim($_POST['email']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    $phone = trim($_POST['phone']);
    if (!preg_match("/^[0-9]{10}$/", $phone)) {
        $errors[] = "Phone number must be 10 digits";
    }
    
    $city = trim($_POST['city']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $availability = 'Available';
        $role = 'Employee';
        $status = 'Active';

        // Start transaction
        $conn->begin_transaction();

        try {
            // Check if email already exists
            $checkEmail = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
            $checkEmail->bind_param("s", $email);
            $checkEmail->execute();
            $result = $checkEmail->get_result();
            
            if ($result->num_rows > 0) {
                throw new Exception("Email already exists");
            }

            // Insert into users table
            $userSql = "INSERT INTO users (fullname, email, phone, password, role, status, city) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmtUser = $conn->prepare($userSql);
            $stmtUser->bind_param("sssssss", $fullname, $email, $phone, $password_hash, $role, $status, $city);
            $stmtUser->execute();
            $user_id = $stmtUser->insert_id;

            // Insert into employees table
            $employeeSql = "INSERT INTO employees (user_id, availability) VALUES (?, ?)";
            $stmtEmployee = $conn->prepare($employeeSql);
            $stmtEmployee->bind_param("is", $user_id, $availability);
            $stmtEmployee->execute();

            $conn->commit();
            echo "<script>alert('Employee added successfully'); window.location.href='employeemanagement.php';</script>";

        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
            font-family: 'Segoe UI', sans-serif; 
        }

        body { 
            display: flex; 
            background-color: #f5f6fa; 
        }

        .sidebar { 
            width: 250px; 
            height: 100vh; 
            background-color: #2c3e50; 
            padding: 20px; 
            position: fixed; 
        }

        .logo { 
            color: white; 
            font-size: 24px; 
            text-align: center; 
            font-weight: bold; 
            margin-bottom: 30px; 
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
            transition: all 0.3s ease; 
        }

        .nav-link i { 
            margin-right: 10px; 
        }

        .nav-link:hover { 
            background-color: #34495e; 
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
            background-color: white; 
            padding: 20px; 
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .btn { 
            padding: 10px 20px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn:hover { 
            transform: translateY(-2px); 
        }

        .btn-primary { 
            background-color: #3498db; 
            color: white; 
        }

        .btn-warning {
            background-color: #f39c12;
            color: white;
            margin-right: 5px;
        }

        .btn-danger { 
            background-color: #e74c3c; 
            color: white; 
        }

        .table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        table { 
            width: 100%; 
            border-collapse: collapse; 
        }

        th, td { 
            padding: 15px; 
            text-align: left; 
            border-bottom: 1px solid #ddd; 
        }

        th { 
            background-color: #2c3e50; 
            color: white; 
        }

        tr:hover { 
            background-color: #f8f9fa; 
        }

        .modal { 
            display: none; 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            background: rgba(0,0,0,0.5); 
            justify-content: center; 
            align-items: center;
            z-index: 1000;
        }

        .modal-content { 
            background: white; 
            padding: 30px; 
            width: 500px; 
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-content h2 {
            margin-bottom: 20px;
            color: #2c3e50;
        }

        .input-wrapper {
            position: relative;
            margin-bottom: 20px;
        }

        .input-wrapper label {
            display: block;
            margin-bottom: 5px;
            color: #2c3e50;
            font-weight: 500;
        }

        .input-wrapper input,
        .input-wrapper select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .input-wrapper input:focus,
        .input-wrapper select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        .validation-message {
            position: absolute;
            bottom: -20px;
            left: 0;
            font-size: 12px;
            color: #e74c3c;
            display: none;
        }

        input.invalid {
            border-color: #e74c3c !important;
        }

        input.valid {
            border-color: #2ecc71 !important;
        }

        .modal-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .error-message {
            background-color: #fff3cd;
            color: #856404;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            border: 1px solid #ffeeba;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="logo">Admin Panel</div>
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="admindashboard.php" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="employeemanagement.php" class="nav-link">
                    <i class="fas fa-user-tie"></i> Employee Management
                </a>
            </li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Employee Management</h1>
            <button class="btn btn-primary" onclick="openModal('add')">
                <i class="fas fa-plus"></i> Add Employee
            </button>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>City</th>
                        <th>Availability</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT e.employee_id, u.fullname, u.email, u.phone, u.city, e.availability 
                              FROM employees e
                              JOIN users u ON e.user_id = u.user_id";
                    $result = $conn->query($query);
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                            <td>{$row['employee_id']}</td>
                            <td>{$row['fullname']}</td>
                            <td>{$row['email']}</td>
                            <td>{$row['phone']}</td>
                            <td>{$row['city']}</td>
                            <td>{$row['availability']}</td>
                            <td>
                                <button class='btn btn-warning' onclick='openUpdateModal({
                                    id: \"{$row['employee_id']}\",
                                    fullname: \"{$row['fullname']}\",
                                    email: \"{$row['email']}\",
                                    phone: \"{$row['phone']}\",
                                    city: \"{$row['city']}\"
                                })'>
                                    <i class='fas fa-edit'></i> Edit
                                </button>
                                <form method='POST' style='display: inline;'>
                                    <input type='hidden' name='employee_id' value='{$row['employee_id']}'>
                                    <button class='btn btn-danger' name='delete_employee' onclick='return confirm(\"Are you sure you want to delete this employee?\")'>
                                        <i class='fas fa-trash'></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Employee Modal -->
    <div class="modal" id="addEmployeeModal">
        <div class="modal-content">
            <h2><i class="fas fa-user-plus"></i> Add New Employee</h2>
            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form method="POST" id="employeeForm" onsubmit="return validateForm('add')">
                <div class="input-wrapper">
                    <label for="fullname">Full Name</label>
                    <input type="text" id="fullname" name="fullname" required>
                    <span class="validation-message" id="fullname-validation"></span>
                </div>
                
                <div class="input-wrapper">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                    <span class="validation-message" id="email-validation"></span>
                </div>
                
                <div class="input-wrapper">
                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone" required>
                    <span class="validation-message" id="phone-validation"></span>
                </div>
                
                <div class="input-wrapper">
                    <label for="city">City</label>
                    <select id="city" name="city" required>
                        <option value="">Select City</option>
                        <?php echo $cityOptions; ?>
                    </select>
                    <span class="validation-message" id="city-validation"></span>
                </div>
                
                <div class="input-wrapper">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                    <span class="validation-message" id="password-validation"></span>
                </div>
                
                <div class="input-wrapper">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <span class="validation-message" id="confirm-password-validation"></span>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="btn btn-danger" onclick="closeModal('add')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" name="save_employee">
                        <i class="fas fa-save"></i> Save Employee
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Update Employee Modal -->
    <div class="modal" id="updateEmployeeModal">
        <div class="modal-content">
            <h2><i class="fas fa-user-edit"></i> Update Employee</h2>
            <form method="POST" id="updateForm" onsubmit="return validateForm('update')">
                <input type="hidden" id="update_employee_id" name="employee_id">
                
                <div class="input-wrapper">
                    <label for="update_fullname">Full Name</label>
                    <input type="text" id="update_fullname" name="fullname" required>
                    <span class="validation-message" id="update-fullname-validation"></span>
                </div>
                
                <div class="input-wrapper">
                    <label for="update_email">Email</label>
                    <input type="email" id="update_email" name="email" required>
                    <span class="validation-message" id="update-email-validation"></span>
                </div>
                
                <div class="input-wrapper">
                    <label for="update_phone">Phone</label>
                    <input type="text" id="update_phone" name="phone" required>
                    <span class="validation-message" id="update-phone-validation"></span>
                </div>
                
                <div class="input-wrapper">
                    <label for="update_city">City</label>
                    <select id="update_city" name="city" required>
                        <option value="">Select City</option>
                        <?php echo $cityOptions; ?>
                    </select>
                    <span class="validation-message" id="update-city-validation"></span>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="btn btn-danger" onclick="closeModal('update')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" name="update_employee">
                        <i class="fas fa-save"></i> Update Employee
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Validation functions
        function validateForm(type) {
            const prefix = type === 'update' ? 'update_' : '';
            let isValid = true;
            
            // Validate full name
            const fullname = document.getElementById(prefix + 'fullname').value.trim();
            if (fullname.length < 3 || fullname.length > 50 || !/^[a-zA-Z\s]*$/.test(fullname)) {
                isValid = false;
            }
            
            // Validate email
            const email = document.getElementById(prefix + 'email').value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                isValid = false;
            }
            
            // Validate phone
            const phone = document.getElementById(prefix + 'phone').value.replace(/\D/g, '');
            if (phone.length !== 10) {
                isValid = false;
            }
            
            // Validate city
            const city = document.getElementById(prefix + 'city').value;
            if (!city) {
                isValid = false;
            }
            
            // Validate password fields for new employee
            if (type !== 'update') {
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                
                if (password.length < 8 || !/\d/.test(password) || !/[A-Z]/.test(password)) {
                    isValid = false;
                }
                
                if (password !== confirmPassword) {
                    isValid = false;
                }
            }
            
            return isValid;
        }

        // Real-time validation
        function setupValidation(prefix = '') {
            const fullnameInput = document.getElementById(prefix + 'fullname');
            const emailInput = document.getElementById(prefix + 'email');
            const phoneInput = document.getElementById(prefix + 'phone');
            const passwordInput = document.getElementById(prefix + 'password');
            const confirmPasswordInput = document.getElementById(prefix + 'confirm_password');
            
            if (fullnameInput) {
                fullnameInput.addEventListener('input', function() {
                    const value = this.value.trim();
                    const validationMessage = document.getElementById((prefix ? 'update-' : '') + 'fullname-validation');
                    
                    if (value.length < 3) {
                        showError(this, validationMessage, 'Name must be at least 3 characters long');
                    } else if (value.length > 50) {
                        showError(this, validationMessage, 'Name must be less than 50 characters');
                    } else if (!/^[a-zA-Z\s]*$/.test(value)) {
                        showError(this, validationMessage, 'Name can only contain letters and spaces');
                    } else {
                        showSuccess(this, validationMessage);
                    }
                });
            }

            if (emailInput) {
                emailInput.addEventListener('input', function() {
                    const value = this.value.trim();
                    const validationMessage = document.getElementById((prefix ? 'update-' : '') + 'email-validation');
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    
                    if (!emailRegex.test(value)) {
                        showError(this, validationMessage, 'Please enter a valid email address');
                    } else {
                        showSuccess(this, validationMessage);
                    }
                });
            }

            if (phoneInput) {
                phoneInput.addEventListener('input', function() {
                    const value = this.value.replace(/\D/g, '');
                    const validationMessage = document.getElementById((prefix ? 'update-' : '') + 'phone-validation');
                    
                    this.value = value.slice(0, 10);
                    
                    if (value.length !== 10) {
                        showError(this, validationMessage, 'Phone number must be 10 digits');
                    } else {
                        showSuccess(this, validationMessage);
                    }
                });
            }

            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    const value = this.value;
                    const validationMessage = document.getElementById('password-validation');
                    
                    if (value.length < 8) {
                        showError(this, validationMessage, 'Password must be at least 8 characters long');
                    } else if (!/\d/.test(value)) {
                        showError(this, validationMessage, 'Password must contain at least one number');
                    } else if (!/[A-Z]/.test(value)) {
                        showError(this, validationMessage, 'Password must contain at least one uppercase letter');
                    } else {
                        showSuccess(this, validationMessage);
                    }
                });
            }

            if (confirmPasswordInput) {
                confirmPasswordInput.addEventListener('input', function() {
                    const password = document.getElementById('password').value;
                    const validationMessage = document.getElementById('confirm-password-validation');
                    
                    if (this.value !== password) {
                        showError(this, validationMessage, 'Passwords do not match');
                    } else {
                        showSuccess(this, validationMessage);
                    }
                });
            }
        }

        function showError(input, messageElement, message) {
            input.classList.add('invalid');
            input.classList.remove('valid');
            messageElement.style.display = 'block';
            messageElement.textContent = message;
        }

        function showSuccess(input, messageElement) {
            input.classList.remove('invalid');
            input.classList.add('valid');
            messageElement.style.display = 'none';
        }

        // Modal functions
        function openModal(type = 'add') {
            const modal = document.getElementById(type === 'add' ? 'addEmployeeModal' : 'updateEmployeeModal');
            modal.style.display = 'flex';
            setupValidation(type === 'add' ? '' : 'update_');
        }

        function closeModal(type = 'add') {
            const modal = document.getElementById(type === 'add' ? 'addEmployeeModal' : 'updateEmployeeModal');
            const form = document.getElementById(type === 'add' ? 'employeeForm' : 'updateForm');
            modal.style.display = 'none';
            form.reset();
            
            // Reset validation styles
            const inputs = form.querySelectorAll('input');
            inputs.forEach(input => {
                input.classList.remove('invalid', 'valid');
            });
            
            const messages = form.querySelectorAll('.validation-message');
            messages.forEach(message => {
                message.style.display = 'none';
            });
        }

        function openUpdateModal(employee) {
            document.getElementById('update_employee_id').value = employee.id;
            document.getElementById('update_fullname').value = employee.fullname;
            document.getElementById('update_email').value = employee.email;
            document.getElementById('update_phone').value = employee.phone;
            document.getElementById('update_city').value = employee.city;
            openModal('update');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                closeModal(event.target.id === 'addEmployeeModal' ? 'add' : 'update');
            }
        };

        // Initialize validation for both forms
        setupValidation('');  // For add form
        setupValidation('update_');  // For update form
    </script>
</body>
</html>