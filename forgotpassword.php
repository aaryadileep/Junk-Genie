<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <style>
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <h2>Forgot Password</h2>

    <!-- Display success or error messages -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="message success">
            <?php echo $_SESSION['message']; ?>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="message error">
            <?php echo $_SESSION['error']; ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Forgot Password Form -->
    <form action="send_reset_link.php" method="POST">
        <label for="email">Email:</label>
        <input type="email" name="email" id="email" required>
        <button type="submit">Send Reset Link</button>
    </form>

    <p>Remember your password? <a href="login.php">Login here</a></p>
</body>
</html>