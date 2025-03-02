<?php
session_start();
require_once 'connect.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Fetch cities from database
$sql = "SELECT city_id, city_name FROM cities WHERE is_active = 1";
$result = $conn->query($sql);
$cities = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $cities[] = $row;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $city_id = isset($_POST['city']) ? trim($_POST['city']) : NULL;
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $other_city = isset($_POST['otherCity']) ? trim($_POST['otherCity']) : NULL;

    if ($city_id === "other") {
        $city_id = NULL;
        $address = $other_city;
    }

    try {
        // Check existing email/phone
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? OR phone = ?");
        $stmt->bind_param("ss", $email, $phone);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception('Email or phone already registered');
        }

        // Generate OTP
        $otp = sprintf("%06d", random_int(0, 999999));
        $expiry = date("Y-m-d H:i:s", strtotime("+15 minutes"));

        // Store registration data in session
        $_SESSION['registration'] = [
            'fullname' => $fullname,
            'email' => $email,
            'phone' => $phone,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'End User',
            'city_id' => $city_id,
            'address' => $address ?? NULL,
            'otp' => $otp,
            'otp_expiry' => $expiry
        ];

        // Configure PHPMailer
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'ayoradileep@gmail.com'; // Replace with your email
        $mail->Password = 'wcfe huos pvsk dwzs'; // Replace with your app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('ayoradileep@gmail.com', 'JunkGenie');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Verify Your Email - JunkGenie';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #4CAF50;'>Welcome to JunkGenie!</h2>
                <p>Your verification code is: <strong style='font-size: 24px;'>{$otp}</strong></p>
                <p>This code will expire in 15 minutes.</p>
            </div>";

        if ($mail->send()) {
            header("Location: verify_registration.php");
            exit();
        } else {
            throw new Exception('Failed to send verification email');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - JunkGenie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color:rgb(136, 243, 188);
            --secondary-color:rgb(179, 240, 181);
            --background-gradient: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--background-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: auto;
        }

        .card {
            border: none;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            background: rgba(255, 255, 255, 0.9);
        }

        .left-section {
            background: var(--primary-color);
            color: white;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            height: 100%;
        }

        .mascot {
            max-width: 200px;
            margin: 2rem 0;
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }

        .right-section {
            padding: 3rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group label {
            font-weight: 500;
            color: #344767;
            margin-bottom: 0.5rem;
            display: block;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
            outline: none;
        }

        .error {
            color: #dc3545;
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }

        .sign-in-btn {
            background: var(--primary-color);
            color: white;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 10px;
            font-weight: 500;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .sign-in-btn:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.3);
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #666;
        }

        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .input-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            cursor: pointer;
        }

        #otherCityInput {
            display: none;
            margin-top: 0.5rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 0;
            }
            .left-section {
                padding: 2rem;
            }
            .right-section {
                padding: 2rem;
            }
            .mascot {
                max-width: 150px;
            }
        }
    </style>
</head>


<body>
    <div class="container">
        <div class="card">
            <div class="row g-0">
                <div class="col-md-5">
                    <div class="left-section">
                        <h1 class="display-4 fw-bold">Welcome to<br>JunkGenie</h1>
                        <img src="images/genie.png" alt="JunkGenie Mascot" class="mascot">
                        <p class="lead">Join our community of eco-conscious individuals!</p>
                    </div>
                </div>
                <div class="col-md-7">
                    <div class="right-section">
                        <h2 class="mb-4">Create Account</h2>
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        <form id="registrationForm" method="POST" novalidate>
                            <div class="form-group">
                                <label for="fullname">
                                    <i class="fas fa-user me-2"></i>Full Name
                                </label>
                                <input type="text" id="fullname" name="fullname" required>
                                <p class="error" id="fullnameError"></p>
                            </div>

                            <div class="form-group">
                                <label for="email">
                                    <i class="fas fa-envelope me-2"></i>Email Address
                                </label>
                                <input type="email" id="email" name="email" required>
                                <p class="error" id="emailError"></p>
                            </div>

                            <div class="form-group">
                                <label for="phone">
                                    <i class="fas fa-phone me-2"></i>Phone Number
                                </label>
                                <input type="tel" id="phone" name="phone" required>
                                <p class="error" id="phoneError"></p>
                            </div>

                            <div class="form-group">
                                <label for="city">
                                    <i class="fas fa-city me-2"></i>City
                                </label>
                                <select id="city" name="city" required>
                                    <option value="">Select your city</option>
                                    <?php foreach ($cities as $city): ?>
                                        <option value="<?php echo htmlspecialchars($city['city_id']); ?>">
                                        <?php echo htmlspecialchars($city['city_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="other">Other</option>
                                </select>
                                <input type="text" id="otherCityInput" name="otherCity" placeholder="Enter your city">
                                <p class="error" id="cityError"></p>
                            </div>

                            <div class="form-group">
                                <label for="password">
                                    <i class="fas fa-lock me-2"></i>Password
                                </label>
                                <div class="password-wrapper">
                                    <input type="password" id="password" name="password" required>
                                    <i class="fas fa-eye input-icon" onclick="togglePassword('password')"></i>
                                </div>
                                <p class="error" id="passwordError"></p>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password">
                                    <i class="fas fa-lock me-2"></i>Confirm Password
                                </label>
                                <div class="password-wrapper">
                                    <input type="password" id="confirm_password" name="confirm_password" required>
                                    <i class="fas fa-eye input-icon" onclick="togglePassword('confirm_password')"></i>
                                </div>
                                <p class="error" id="confirmPasswordError"></p>
                            </div>

                            <button type="submit" class="sign-in-btn">
                                <i class="fas fa-user-plus me-2"></i>Create Account
                            </button>

                            <div class="login-link">
                                Already have an account? <a href="login.php">Login here</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
    const form = document.getElementById("registrationForm");

    form.fullname.addEventListener("input", () => validateFullName());
    form.email.addEventListener("input", () => validateEmail());
    form.phone.addEventListener("input", () => validatePhone());
    form.city.addEventListener("change", () => validateCity());
    form.otherCityInput.addEventListener("input", () => validateOtherCity());
    form.password.addEventListener("input", () => validatePassword());
    form.confirm_password.addEventListener("input", () => validateConfirmPassword());

    form.addEventListener("submit", function(event) {
        if (!validateForm()) {
            event.preventDefault();
        }
    });

    function validateFullName() {
        const fullname = document.getElementById("fullname").value.trim();
        const error = document.getElementById("fullnameError");
        const regex = /^[A-Z][a-z]*(?: [A-Z][a-z]*)*$/;

        if (fullname.length < 3) {
            error.textContent = "Name must be at least 3 characters long";
            return false;
        }
        if (!regex.test(fullname)) {
            error.textContent = "Name must start with a capital letter";
            return false;
        }

        error.textContent = "";
        return true;
    }

    function validateEmail() {
        const email = document.getElementById("email").value.trim();
        const error = document.getElementById("emailError");
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!regex.test(email)) {
            error.textContent = "Enter a valid email address";
            return false;
        }

        // Check if email already exists via AJAX
        fetch("check_email.php?email=" + encodeURIComponent(email))
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    error.textContent = "Email is already registered";
                } else {
                    error.textContent = "";
                }
            });

        return true;
    }

    function validatePhone() {
        const phone = document.getElementById("phone").value.trim();
        const error = document.getElementById("phoneError");
        const regex = /^[6789]\d{9}$/;

        if (!regex.test(phone)) {
            error.textContent = "Enter a valid 10-digit phone number";
            return false;
        }

        error.textContent = "";
        return true;
    }

    function validateCity() {
        const city = document.getElementById("city").value;
        const error = document.getElementById("cityError");

        if (!city) {
            error.textContent = "Please select a city";
            return false;
        } else if (city === "other") {
            return validateOtherCity();
        }

        error.textContent = "";
        return true;
    }

    function validateOtherCity() {
        const otherCity = document.getElementById("otherCityInput").value.trim();
        const error = document.getElementById("cityError");

        if (!otherCity) {
            error.textContent = "Please enter your city";
            return false;
        }

        error.textContent = "";
        return true;
    }

    function validatePassword() {
        const password = document.getElementById("password").value;
        const error = document.getElementById("passwordError");

        if (password.length < 8) {
            error.textContent = "Password must be at least 8 characters";
            return false;
        }
        if (!/[A-Z]/.test(password)) {
            error.textContent = "Password must contain at least one uppercase letter";
            return false;
        }
        if (!/[a-z]/.test(password)) {
            error.textContent = "Password must contain at least one lowercase letter";
            return false;
        }
        if (!/[0-9]/.test(password)) {
            error.textContent = "Password must contain at least one number";
            return false;
        }

        error.textContent = "";
        return true;
    }

    function validateConfirmPassword() {
        const password = document.getElementById("password").value;
        const confirmPassword = document.getElementById("confirm_password").value;
        const error = document.getElementById("confirmPasswordError");

        if (password !== confirmPassword) {
            error.textContent = "Passwords do not match";
            return false;
        }

        error.textContent = "";
        return true;
    }

    function validateForm() {
        return (
            validateFullName() &&
            validateEmail() &&
            validatePhone() &&
            validateCity() &&
            validatePassword() &&
            validateConfirmPassword()
        );
    }
});

        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling;
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        document.getElementById('city').addEventListener('change', function() {
            const otherCityInput = document.getElementById('otherCityInput');
            otherCityInput.style.display = this.value === 'other' ? 'block' : 'none';
            if (this.value !== 'other') {
                otherCityInput.value = '';
            }
        });

        // Form validation
        const form = document.getElementById('registrationForm');
        form.addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
            }
        });

        function validateForm() {
            let isValid = true;
            
            // Validate full name
            const fullname = document.getElementById('fullname').value.trim();
            if (!/^[A-Z][a-z]*(?: [A-Z][a-z]*)*$/.test(fullname)) {
                setError('fullname', 'Please enter a valid full name');
                isValid = false;
            } else {
                clearError('fullname');
            }

            // Validate email
            const email = document.getElementById('email').value.trim();
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                setError('email', 'Please enter a valid email address');
                isValid = false;
            } else {
                clearError('email');
            }

            // Validate phone
            const phone = document.getElementById('phone').value.trim();
            if (!/^[6789]\d{9}$/.test(phone)) {
                setError('phone', 'Please enter a valid 10-digit phone number');
                isValid = false;
            } else {
                clearError('phone');
            }

            // Validate city
            const city = document.getElementById('city').value;
            if (!city) {
                setError('city', 'Please select a city');
                isValid = false;
            } else if (city === 'other') {
                const otherCity = document.getElementById('otherCityInput').value.trim();
                if (!otherCity) {
                    setError('city', 'Please enter your city');
                    isValid = false;
                } else {
                    clearError('city');
                }
            } else {
                clearError('city');
            }

            // Validate password
            const password = document.getElementById('password').value;
            if (password.length < 8) {
                setError('password', 'Password must be at least 8 characters long');
                isValid = false;
            } else {
                clearError('password');
            }

            // Validate confirm password
            const confirmPassword = document.getElementById('confirm_password').value;
            if (confirmPassword !== password) {
                setError('confirm_password', 'Passwords do not match');
                isValid = false;
            } else {
                clearError('confirm_password');
            }

            return isValid;
        }

        function setError(field, message) {
            document.getElementById(field + 'Error').textContent = message;
        }

        function clearError(field) {
            document.getElementById(field + 'Error').textContent = '';
        }
    </script>
</body>
</html>