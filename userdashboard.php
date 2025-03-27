<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$fullname = $_SESSION['fullname'];
$email = $_SESSION['email'];
$phone = $_SESSION['phone'];
$city = $_SESSION['city'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard | JunkGenie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-green: #4CAF50;
            --secondary-green: #81C784;
            --light-background: #F0F4F0;
        }

        body {
            background-color: var(--light-background);
            font-family: 'Poppins', sans-serif;
        }

        .dashboard-header {
            background: white;
            padding: 1rem 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .navbar-brand h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-green);
            margin: 0;
        }

        .dashboard-content {
            margin-top: 100px;
            padding: 2rem 0;
        }

        .welcome-section {
            background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }


        .eco-stat {
            text-align: center;
            padding: 0.5rem;
        }

        .eco-stat i {
            color: var(--primary-green);
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }

        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.1);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        .feature-icon {
            font-size: 3rem;
            color: var(--primary-green);
            margin-bottom: 1rem;
            transition: transform 0.3s;
        }

        .start-selling-btn {
            background-color: var(--primary-green);
            border: none;
            padding: 1rem 2.5rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
        }

        .start-selling-btn:hover {
            background-color: var(--secondary-green);
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <nav class="dashboard-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <a href="index.php" class="navbar-brand">
                    <img src="logo.jpg" alt="JunkGenie" height="40">
                    <h1>Junk Genie</h1>
                </a>
                <div class="dropdown">
                    <img src="images/profile.jpg" alt="Profile" class="rounded-circle" height="45" data-bs-toggle="dropdown" style="cursor: pointer;">
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="order_history.php"><i class="fas fa-box me-2"></i>My Orders</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <main class="dashboard-content container">
        <div class="welcome-section text-center">
            <h2>Welcome back, <?php echo htmlspecialchars($fullname); ?>!</h2>
            <p>Your eco-friendly journey is making a difference. Keep recycling!</p>
        </div>

     

        <div class="feature-grid mb-4">
            <div class="feature-card" onclick="showFeature('Sell E-Waste')">
                <i class="fas fa-mobile-alt feature-icon"></i>
                <h3>Sell E-Waste</h3>
                <p>Choose from various electronic waste categories</p>
            </div>
            <div class="feature-card" onclick="showFeature('Schedule Pickup')">
                <i class="fas fa-truck feature-icon"></i>
                <h3>Schedule Pickup</h3>
                <p>Easy doorstep collection of your e-waste</p>
            </div>
            <div class="feature-card" onclick="showFeature('Best Rates')">
                <i class="fas fa-tags feature-icon"></i>
                <h3>Best Rates</h3>
                <p>Get competitive prices for your electronic items</p>
            </div>
        </div>

        <div class="text-center">
            <button class="btn btn-success start-selling-btn" onclick="startSelling()">
                <i class="fas fa-plus-circle me-2"></i>Start Selling Now
            </button>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showFeature(feature) {
            alert(`You selected: ${feature}`);
        }

        function startSelling() {
            window.location.href = "sell.php";
        }
    </script>
</body>
</html>