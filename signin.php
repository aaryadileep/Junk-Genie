<?php
include 'connect.php'; // Database connection file

// Fetch cities from the database
$sql = "SELECT city_name FROM cities WHERE is_active = 1"; // Assuming cities are active if is_active is 1
$result = $conn->query($sql);
$cities = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $cities[] = $row['city_name']; // Store city names in an array
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT); // Secure password
    $role = "End User"; // Since it's for end users

    // Handle city and address only for end users
    $city = isset($_POST['city']) ? trim($_POST['city']) : NULL;
    $address = (!empty($_POST['otherCity']) && $_POST['city'] === "other") ? trim($_POST['otherCity']) : $city;

    // Check if email or phone already exists
    $checkQuery = "SELECT * FROM users WHERE email = ? OR phone = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("ss", $email, $phone);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('Error: Email or phone number already in use! Please use a different one.'); window.location.href='signin.php';</script>";
        exit();
    }

    // Generate email verification token
    $email_verification_token = bin2hex(random_bytes(50));

    // Insert the new user into the database
    $query = "INSERT INTO users (fullname, email, phone, password, role, city, address, email_verification_token) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    
    if ($stmt) {
        $stmt->bind_param("ssssssss", $fullname, $email, $phone, $password, $role, $city, $address, $email_verification_token);
        if ($stmt->execute()) {
            // Send verification email
            $verification_link = "http://localhost/Junkgenie/verify_email.php?token=$email_verification_token";
            $subject = "Verify Your Email Address";
            $message = "Click the link to verify your email: $verification_link";
            $headers = "From: no-reply@yourwebsite.com";

            if (mail($email, $subject, $message, $headers)) {
                echo "<script>alert('Registration successful! Please check your email to verify your account.'); window.location.href='login.php';</script>";
            } else {
                echo "<script>alert('Registration successful, but failed to send verification email.');</script>";
            }
        } else {
            echo "<script>alert('Error executing query: " . $stmt->error . "');</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('Error preparing statement: " . $conn->error . "');</script>";
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JunkGenie Registration</title>
    <link href="https://fonts.googleapis.com/css2?family=Yusei+Magic&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        .container {
            width: 100%;
            max-width: 800px;
            margin: 2rem auto;
            display: flex;
            flex-wrap: wrap;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            border-radius: 15px;
            height:700vh;
        }
        .left-section {
            flex: 1;
            padding: 2rem;
            text-align: center;
        }
        .right-section {
            flex: 2;
            padding: 1rem;
        }
        .form-group {
            margin-bottom: 0.5rem;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 8px;
        }
        .error {
            color: red;
            font-size: 0.9rem;
            margin-top: 0.2rem;
        }
        .success {
            color: green;
            font-size: 1rem;
        }
        #otherCityInput {
            display: none;
            margin-top: 0.5rem;
        }
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            .right-section {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="left-section">
            <h1>Welcome to<br>JunkGenie</h1>
            <img src="images/genie.png" alt="JunkGenie Mascot" class="mascot">
        </div>
        <div class="right-section">
            <h2>Register Now</h2>
            <form id="registrationForm" method="POST">
                <div class="form-group">
                    <label for="fullname">Full name *</label>
                    <input type="text" id="fullname" name="fullname" required>
                    <p class="error" id="fullnameError"></p>
                </div>
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required>
                    <p class="error" id="emailError"></p>
                </div>
                <div class="form-group">
                    <label for="phone">Phone number *</label>
                    <input type="tel" id="phone" name="phone" required>
                    <p class="error" id="phoneError"></p>
                </div>
                <div class="form-group">
                    <label for="city">City *</label>
                    <select id="city" name="city" required>
                        <option value="">Select your city</option>
                        <?php
                        // Loop through the cities array and create an option for each city
                        foreach ($cities as $city) {
                            echo "<option value='$city'>$city</option>";
                        }
                        ?>
                        <option value="other">Other</option>
                    </select>
                    <input type="text" id="otherCityInput" name="otherCity" placeholder="Enter your city">
                    <p class="error" id="cityError"></p>
                </div>
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required>
                    <p class="error" id="passwordError"></p>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <p class="error" id="confirmPasswordError"></p>
                </div>
                <button type="submit" class="sign-in-btn">Sign Up</button>
                <div class="login-link">
                    Already have an account? <a href="login.php">Login here</a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        document.getElementById('city').addEventListener('change', function () {
            const citySelect = document.getElementById('city');
            const otherCityInput = document.getElementById('otherCityInput');
            otherCityInput.style.display = (citySelect.value === 'other') ? 'block' : 'none';
            document.getElementById('cityError').textContent = '';
        });

        document.querySelectorAll('#fullname, #email, #phone, #password, #confirm_password, #otherCityInput').forEach(field => {
            field.addEventListener('input', function () {
                validateField(field.id);
            });
        });

        document.getElementById('registrationForm').addEventListener('submit', function (event) {
            if (!validateForm()) event.preventDefault();
        });

        function validateField(id) {
            if (id === 'fullname') validateFullName();
            if (id === 'email') validateEmail();
            if (id === 'phone') validatePhone();
            if (id === 'password') validatePassword();
            if (id === 'confirm_password') validateConfirmPassword();
            if (id === 'otherCityInput') validateCity();
        }

        function validateFullName() {
            const fullname = document.getElementById('fullname').value.trim();
            const fullnameError = document.getElementById('fullnameError');
            
            // Check if the full name is at least 3 characters long
            if (fullname.length < 3) {
                fullnameError.textContent = 'Full name must be at least 3 characters';
                return false;
            }
            
            // Check if the full name starts with capital letters and contains no numbers
            const re = /^[A-Z][a-z]*(?: [A-Z][a-z]*)*$/;
            if (!re.test(fullname)) {
                fullnameError.textContent = 'Full name must start with capital letters and contain no numbers';
                return false;
            }
            
            fullnameError.textContent = '';
            return true;
        }

        function validateEmail() {
            const email = document.getElementById('email').value.trim();
            document.getElementById('emailError').textContent = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) ? '' : 'Invalid email format';
        }

        function validatePhone() {
            document.getElementById('phoneError').textContent = /^[6789]\d{9}$/.test(document.getElementById('phone').value.trim()) ? '' : 'Invalid phone number';
        }

        function validatePassword() {
            document.getElementById('passwordError').textContent = document.getElementById('password').value.length < 8 ? 'Password must be at least 8 characters' : '';
        }

        function validateConfirmPassword() {
            document.getElementById('confirmPasswordError').textContent = document.getElementById('password').value !== document.getElementById('confirm_password').value ? 'Passwords do not match' : '';
        }
    </script>
</body>
</html>