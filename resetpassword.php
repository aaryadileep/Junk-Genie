<?php
session_start();
require 'connect.php';

// Validate token
$token = isset($_GET['token']) ? trim($_GET['token']) : '';
if (empty($token)) {
    $_SESSION['error'] = "Invalid reset link.";
    header("Location: login.php");
    exit();
}

// For debugging only - remove these lines in production
error_log("Received token: " . $token);

// Check token validity and expiration
$stmt = $conn->prepare("SELECT user_id FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Remove the debug var_dump and exit() statements
// var_dump([
//     'token' => $token,
//     'query' => "SELECT user_id FROM users WHERE reset_token = '$token' AND reset_token_expiry > NOW()"
// ]);
// exit();

if (!$user) {
    $_SESSION['error'] = "Invalid or expired reset token. Please request a new password reset link.";
    header("Location: forgotpassword.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    if (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        try {
            $conn->begin_transaction();
            
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE user_id = ? AND reset_token = ?");
            $stmt->bind_param("sis", $hashed_password, $user['user_id'], $token);
            
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $conn->commit();
                $_SESSION['message'] = "Password has been reset successfully. Please login with your new password.";
                header("Location: login.php");
                exit();
            } else {
                throw new Exception("Failed to update password");
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Failed to reset password. Please try again.";
            error_log($e->getMessage());
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
    <link href="https://fonts.googleapis.com/css2?family=Yusei+Magic&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        .container {
            width: 400px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: #fff;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            border-radius: 15px;
        }
        h2 {
            color: #333;
            text-align: center;
            margin-bottom: 2rem;
            font-family: 'Yusei Magic', sans-serif;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #555;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        .form-group input:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 5px rgba(76, 175, 80, 0.2);
        }
        .password-requirements {
            font-size: 12px;
            color: #666;
            margin-top: 0.5rem;
        }
        .error {
            color: #dc3545;
            background-color: #ffe6e6;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 1rem;
            text-align: center;
        }
        .btn-reset {
            width: 100%;
            padding: 12px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }
        .btn-reset:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Reset Password</h2>
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" 
                       id="new_password" 
                       name="new_password" 
                       required 
                       minlength="8"
                       autocomplete="new-password"
                       placeholder="Enter new password">
                <div class="password-requirements">
                    Password must be at least 8 characters long
                </div>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" 
                       id="confirm_password" 
                       name="confirm_password" 
                       required 
                       minlength="8"
                       autocomplete="new-password"
                       placeholder="Confirm your password">
            </div>
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <button type="submit" class="btn-reset">Reset Password</button>
        </form>
    </div>
</body>
</html>