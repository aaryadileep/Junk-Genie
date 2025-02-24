<?php
include 'connect.php'; // Database connection file

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $stmt = $conn->prepare("UPDATE users SET verified = 1, token = NULL WHERE token = ?");
        $stmt->bind_param("s", $token);
        $successful = $stmt->execute();
    } else {
        $successful = false;
    }
    $stmt->close();
    $conn->close();
} else {
    $successful = false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - JunkGenie</title>
    <link href="https://fonts.googleapis.com/css2?family=Yusei+Magic&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        .verification-container {
            max-width: 600px;
            margin: 50px auto;
            text-align: center;
            padding: 30px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            border-radius: 15px;
        }
        .success-message {
            color: #4CAF50;
            font-size: 1.2rem;
            margin: 20px 0;
        }
        .error-message {
            color: #f44336;
            font-size: 1.2rem;
            margin: 20px 0;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin-top: 20px;
        }
        .mascot {
            width: 150px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <img src="images/genie.png" alt="JunkGenie Mascot" class="mascot">
        <h1>Email Verification</h1>
        
        <?php if($successful): ?>
            <div class="success-message">
                <p>Your email has been successfully verified!</p>
                <p>You can now login to your account.</p>
            </div>
            <a href="login.php" class="button">Login Now</a>
        <?php else: ?>
            <div class="error-message">
                <p>Invalid or expired verification link.</p>
                <p>Please try registering again or contact support.</p>
            </div>
            <a href="signin.php" class="button">Register Again</a>
        <?php endif; ?>
    </div>
</body>
</html>