<?php
// Include the database connection file
require_once 'connect.php';

// Fetch user ID from session (assuming user is logged in)
session_start();
if (!isset($_SESSION['user_id'])) {
    die("User not logged in.");
}
$user_id = $_SESSION['user_id'];

// Update Zip Code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_zip_code'])) {
    $zip_code = $_POST['zip_code'];

    // Validate zip code (6 digits for Indian zip codes)
    if (!preg_match('/^\d{6}$/', $zip_code)) {
        $error_message = "Invalid zip code. It must be 6 digits.";
    } else {
        $stmt = $conn->prepare("UPDATE users SET zip_code = :zip_code WHERE id = :user_id");
        $stmt->bindParam(':zip_code', $zip_code);
        $stmt->bindParam(':user_id', $user_id);

        if ($stmt->execute()) {
            $success_message = "Zip code updated successfully!";
        } else {
            $error_message = "Failed to update zip code.";
        }
    }
}

// Fetch user details
$stmt = $conn->prepare("SELECT address, city, zip_code FROM users WHERE id = :user_id");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Location Details</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4CAF50;
            --primary-dark: #388E3C;
            --secondary: #F5F5F5;
            --text-dark: #333;
            --text-light: #777;
            --white: #fff;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --radius: 8px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }

        body { 
            background-color: var(--secondary); 
            color: var(--text-dark); 
            padding: 2rem;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
        }

        h1 { 
            color: var(--primary-dark); 
            margin-bottom: 1.5rem; 
            text-align: center;
        }

        .form-container {
            background-color: var(--white);
            padding: 2rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }

        .form-container h2 {
            margin-bottom: 1rem;
            color: var(--primary-dark);
        }

        .form-container input {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: var(--radius);
            font-size: 1rem;
        }

        .form-container button {
            padding: 10px 20px;
            background-color: var(--primary);
            color: var(--white);
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            font-size: 1rem;
        }

        .form-container button:hover {
            background-color: var(--primary-dark);
        }

        .message {
            padding: 10px;
            margin-bottom: 1rem;
            border-radius: var(--radius);
            text-align: center;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
        }

        .info-container {
            background-color: var(--white);
            padding: 1.5rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }

        .info-container div {
            margin-bottom: 10px;
        }

        .info-container label {
            font-weight: 500;
            color: var(--primary-dark);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Location Details</h1>

        <!-- Display Messages -->
        <?php if (isset($success_message)): ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Display User Location Info -->
        <div class="info-container">
            <h2>Your Current Location</h2>
            <div>
                <label>Address:</label> <?php echo htmlspecialchars($user['address'] ?? 'Not provided'); ?>
            </div>
            <div>
                <label>City:</label> <?php echo htmlspecialchars($user['city'] ?? 'Not provided'); ?>
            </div>
            <div>
                <label>Zip Code:</label> <?php echo htmlspecialchars($user['zip_code'] ?? 'Not provided'); ?>
            </div>
        </div>

        <!-- Update Zip Code Form -->
        <div class="form-container">
            <h2>Update Zip Code</h2>
            <form method="POST" action="">
                <input type="text" name="zip_code" placeholder="Enter Zip Code (6 digits)" required>
                <button type="submit" name="update_zip_code">Update Zip Code</button>
            </form>
        </div>
    </div>
</body>
</html>