<?php
session_start();
require_once 'connect.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user details from the database
$query = "SELECT fullname, email, phone, city FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($fullname, $email, $phone, $city);
$stmt->fetch();
$stmt->close();

// Store details in session for quick access
$_SESSION['fullname'] = $fullname;
$_SESSION['email'] = $email;
$_SESSION['phone'] = $phone;
$_SESSION['city'] = $city;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard | JunkGenie</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4CAF50;
            --primary-dark: #388E3C;
            --secondary: #F5F5F5;
            --text-dark: #333;
            --white: #fff;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --radius: 8px;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--secondary); color: var(--text-dark); }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            background-color: var(--white);
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .location {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1rem;
            color: var(--text-dark);
            cursor: pointer;
        }

        .profile-menu {
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
        }

        .profile-pic {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            cursor: pointer;
            border: 2px solid var(--primary);
        }

        .dropdown-menu {
            position: absolute;
            right: 0;
            top: 50px;
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            width: 180px;
            display: none;
            z-index: 101;
        }

        .dropdown-menu a {
            display: block;
            padding: 12px 15px;
            text-decoration: none;
            color: var(--text-dark);
            transition: background 0.3s;
        }

        .dropdown-menu a:hover {
            background-color: var(--secondary);
        }

        .main-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            text-align: center;
        }
    </style>
</head>

<body>
    <header>
        <div class="location">
            <i class="fas fa-map-marker-alt"></i>
            <span><?php echo !empty($_SESSION['city']) ? htmlspecialchars($_SESSION['city']); ?></span>
        </div>
        <div class="profile-menu">
            <img src="images/profile.jpg" alt="Profile" class="profile-pic" onclick="toggleMenu()">
            <p><?php echo htmlspecialchars($_SESSION['fullname']); ?></p>
            <div class="dropdown-menu" id="profileDropdown">
                <a href="profile.php">View Profile</a>
                <a href="settings.php">Settings</a>
                <a href="logout.php">Log Out</a>
            </div>
        </div>
    </header>

    <div class="main-container">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['fullname']); ?>!</h2>
        <p>Your registered city: <strong><?php echo htmlspecialchars($_SESSION['city']); ?></strong></p>
    </div>

    <script>
        function toggleMenu() {
            document.getElementById("profileDropdown").classList.toggle("show");
        }
        
        window.onclick = function(event) {
            if (!event.target.matches('.profile-pic')) {
                document.getElementById("profileDropdown").classList.remove("show");
            }
        }
    </script>

</body>
</html>
