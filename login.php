<?php
session_start();
require_once 'connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $errors = [];

    // Validate Email Format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }

    // Ensure Password is Not Empty
    if (empty($password)) {
        $errors[] = 'Password is required';
    }

    if (empty($errors)) {
        // Prepare SQL Query
        $stmt = $conn->prepare("SELECT user_id, fullname, password, role, email FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($user_id, $fullname, $hashed_password, $role, $email_verified);
            $stmt->fetch();

            // Verify Password
            if (password_verify($password, $hashed_password)) {
                // Check if email is verified
                if (!$email_verified) {
                    $errors[] = 'Please verify your email address before logging in.';
                } else {
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['fullname'] = $fullname;
                    $_SESSION['email'] = $email;
                    $_SESSION['phone']=$phone;
                    // Store name in session
                    $_SESSION['role'] = $role;

                    // Redirect Based on Role
                    if ($role == 'Admin') {
                        header("Location: admindashboard.php");
                    } else {
                        header("Location: userdashboard.php");
                    }
                    exit();
                }
            } else {
                $errors[] = 'Incorrect email or password';
            }
        } else {
            $errors[] = 'No account found with that email address';
        }

        $stmt->close();
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JunkGenie Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Yusei+Magic&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        .container {
            width: 800px;
            height: 600px;
            margin: 2rem auto;
            display: flex;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            border-radius: 15px;
        }
        .left-section {
            width: 300px;
            padding: 2rem;
        }
        .right-section {
            width: 500px;
            padding: 2rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group input {
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
    </style>
</head>
<body>
    <div class="container">
        <div class="left-section">
            <h1>Welcome to<br>JunkGenie</h1>
            <img src="images/genie.png" alt="JunkGenie Mascot" class="mascot">
        </div>
        <div class="right-section">
            <h2>Login</h2>
            <form action="login.php" method="POST">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="sign-in-btn">Login</button>
                <div class="login-link">
                    Don't have an account? <a href="signin.php">Register here</a><br>
                    <a href="forgotpassword.php">Forgot Password?</a>
                </div>
            </form>
            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $error): ?>
                    <p class='error'><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>