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
    <link rel="stylesheet" href="css/style.css">
    <style>
        .dashboard-header {
            background: white;
            padding: 1rem 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .navbar-brand h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #4CAF50;
            margin: 0;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid #4CAF50;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .user-avatar:hover {
            transform: scale(1.1);
        }

        .city-btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 0.5rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .city-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .dashboard-content {
            margin-top: 120px;
            padding: 2rem 0;
        }

        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
            height: 100%;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .feature-icon {
            font-size: 3rem;
            color: #4CAF50;
            margin-bottom: 1.5rem;
            transition: transform 0.3s;
        }

        .feature-card:hover .feature-icon {
            transform: scale(1.2) rotate(10deg);
        }

        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: white;
            padding: 1rem 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            display: none;
            z-index: 1000;
        }

        .toast.show {
            display: block;
            animation: slideIn 0.3s ease forwards;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="dashboard-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <a href="index.php" class="navbar-brand">
                    <img src="logo.jpg" alt="JunkGenie" height="40">
                    <h1>Junk Genie</h1>
                </a>
                <div class="user-info">
                    <div class="dropdown">
                        <img src="images/profile.jpg" alt="Profile" class="user-avatar" data-bs-toggle="dropdown">
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="orders.php"><i class="fas fa-box me-2"></i>My Orders</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- City Button -->
    <div class="container text-center mt-4">
        <button class="city-btn">
            <i class="fas fa-map-marker-alt"></i>
            <?php echo htmlspecialchars($city); ?>
        </button>
    </div>

    <main class="dashboard-content">
    <div class="container">
        <h1 class="text-center mb-5">Welcome back, <?php echo htmlspecialchars($fullname); ?>!</h1>
        
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="feature-card" onclick="showToast('Browse e-waste categories')">
                    <i class="fas fa-recycle feature-icon"></i>
                    <h3 class="feature-title">Sell E-Waste</h3>
                    <p>Choose from various categories of electronic waste</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card" onclick="showToast('Schedule a pickup')">
                    <i class="fas fa-truck feature-icon"></i>
                    <h3 class="feature-title">Schedule Pickup</h3>
                    <p>Convenient doorstep collection service</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card" onclick="showToast('View best rates')">
                    <i class="fas fa-coins feature-icon"></i>
                    <h3 class="feature-title">Best Rates</h3>
                    <p>Get competitive prices for your e-waste</p>
                </div>
            </div>
        </div>

        <!-- Green Button -->
        <div class="text-center">
            <button class="btn btn-success btn-lg" onclick="startSelling()">
                <i class="fas fa-plus-circle me-2"></i>Start Selling Now
            </button>
        </div>
    </div>
</main>

    <!-- Toast -->
    <div class="toast" id="toast">
        <i class="fas fa-info-circle me-2"></i>
        <span id="toastMessage"></span>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showToast(message) {
            const toast = document.getElementById("toast");
            document.getElementById("toastMessage").textContent = message;
            toast.classList.add("show");
            setTimeout(() => toast.classList.remove("show"), 3000);
        }

        function startSelling() {
            window.location.href = "sell.php";
        }
    </script>
</body>
</html>