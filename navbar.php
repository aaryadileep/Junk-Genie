<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-green: #4CAF50;
            --secondary-green: #81C784;
            --light-background: #F0F4F0;
        }

        .dashboard-header {
            background: white;
            padding: 0.5rem 0;
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

        .nav-item {
            margin: 0 0.75rem;
        }

        .nav-link {
            color: #333 !important;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: var(--primary-green) !important;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid var(--primary-green);
            cursor: pointer;
            transition: transform 0.3s;
        }

        .user-avatar:hover {
            transform: scale(1.1);
        }

        .dropdown-menu {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 0.5rem;
        }

        .dropdown-item {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .dropdown-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }

        .dropdown-item.text-danger:hover {
            background-color: #fee2e2;
        }

        @media (max-width: 991.98px) {
            .navbar-collapse {
                background: white;
                padding: 1rem;
                border-radius: 15px;
                margin-top: 1rem;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            }
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
                
                <div class="d-flex align-items-center">
                    <ul class="navbar-nav d-flex flex-row align-items-center">
                        <li class="nav-item">
                            <a class="nav-link" href="userdashboard.php">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="order_history.php">My Orders</a>
                        </li>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li class="nav-item dropdown">
                                <img src="images/profile.jpg" alt="Profile" class="user-avatar" data-bs-toggle="dropdown">
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                                    <li><a class="dropdown-item" href="logout.php"><i class="fas fa-user me-2"></i>Log Out</a></li>
                                </ul>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>