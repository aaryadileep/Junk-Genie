<?php
session_start();
require 'connect.php';

if (!isset($_GET['token'])) {
    $_SESSION['error'] = "Invalid reset link.";
    header("Location: login.php");
    exit();
}

$token = $_GET['token'];

// Check if the token is valid and not expired
$stmt = $conn->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    $_SESSION['error'] = "Invalid or expired token. Please request a new password reset link.";
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate password
    if (strlen($new_password) < 8) {
        $_SESSION['error'] = "Password must be at least 8 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match.";
    } else {
        // Hash password and update database
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        
        // Update the user's password and clear the reset token
        $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $user['id']);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Password reset successfully. You can now login.";
            header("Location: login.php");
            exit();
        } else {
            $_SESSION['error'] = "Failed to update password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Junk Genie</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 400px;
            margin: 40px auto;
            padding: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .error {
            color: #dc3545;
            margin-bottom: 15px;
        }
        .btn {
            background-color: #28a745;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }
        .btn:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <h2>Reset Password</h2>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <form action="resetpassword.php?token=<?php echo htmlspecialchars($token); ?>" method="POST">
        <div class="form-group">
            <label for="new_password">New Password:</label>
            <input type="password" name="new_password" id="new_password" required 
                   minlength="8" placeholder="Enter new password">
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm Password:</label>
            <input type="password" name="confirm_password" id="confirm_password" required 
                   minlength="8" placeholder="Confirm new password">
        </div>
        <button type="submit" class="btn">Reset Password</button>
    </form>
</body>
</html>