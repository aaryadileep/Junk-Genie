<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JunkGenie - User Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --main-green: #98FFB3;
        }
        
        .top-bar {
            background-color: var(--main-green);
            padding: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .menu-icon {
            font-size: 24px;
            cursor: pointer;
        }

        .user-controls {
            display: flex;
            gap: 15px;
        }

        .category-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .category-item {
            background: #f5f5f5;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .category-item:hover {
            transform: scale(1.05);
        }

        .category-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .logo-section {
            text-align: center;
            padding: 20px;
        }

        .logo-section img {
            max-width: 200px;
        }

        .stats-card {
            background-color: var(--main-green);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .activity-card {
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .empty-state {
            text-align: center;
            padding: 30px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .top-bar .user-dropdown {
            position: relative;
            display: inline-block;
        }

        .top-bar .dropdown-menu {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .top-bar .dropdown-item {
            padding: 8px 20px;
        }

        .top-bar .dropdown-item i {
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <!-- Updated Top Bar -->
    <div class="top-bar">
        <div class="menu-icon">
            <i class="fas fa-bars"></i>
        </div>
        <div class="user-controls">
            <div class="dropdown">
                <a href="#" class="text-dark dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fas fa-user"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-circle"></i> My Profile</a></li>
                    <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Logo Section -->
    <div class="logo-section">
        <img src="logo.jpg" alt="JunkGenie Logo">
    </div>

    <div class="container">
        <!-- Welcome Section -->
        <h2 class="mt-4">Welcome, <?php 
            if(isset($_SESSION['fullname'])) {
                echo htmlspecialchars($_SESSION['fullname']);
            } 
        ?>!</h2>
        
        <!-- Quick Stats with Empty States -->
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="stats-card">
                    <h5>Total Recycled</h5>
                    <div class="empty-state">
                        <i class="fas fa-recycle"></i>
                        <h3>0 kg</h3>
                        <p>Start recycling to see your impact!</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <h5>Green Points</h5>
                    <div class="empty-state">
                        <i class="fas fa-star"></i>
                        <h3>0</h3>
                        <p>Earn points by recycling e-waste</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <h5>Collections</h5>
                    <div class="empty-state">
                        <i class="fas fa-truck"></i>
                        <h3>0</h3>
                        <p>Schedule your first pickup!</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Categories Section -->
        <h4 class="mt-4 mb-3">Categories</h4>
        <div class="category-grid">
            <div class="category-item">
                <i class="fas fa-desktop category-icon"></i>
                <p>Household Appliances</p>
            </div>
            <div class="category-item">
                <i class="fas fa-mobile-alt category-icon"></i>
                <p>Computing and Communication Equipment</p>
            </div>
            <div class="category-item">
                <i class="fas fa-tablet-alt category-icon"></i>
                <p>Consumer Electronics</p>
            </div>
            <div class="category-item">
                <i class="fas fa-gamepad category-icon"></i>
                <p>Electrical and Electronic Tools</p>
            </div>
            <div class="category-item">
                <i class="fas fa-laptop category-icon"></i>
                <p>Batteries and Lighting Equipment</p>
            </div>
            <div class="category-item">
                <i class="fas fa-ellipsis-h category-icon"></i>
                <p>Others</p>
            </div>
        </div>

        <!-- Updated Recent Activities with Empty State -->
        <div class="row">
            <div class="col-md-8">
                <div class="activity-card card">
                    <div class="card-header">
                        <h5>Recent Activities</h5>
                    </div>
                    <div class="card-body">
                        <div class="empty-state">
                            <i class="fas fa-history"></i>
                            
                            <p>Your recycling journey starts here!</p>
                            <a href="#" class="btn btn-success mt-3">Schedule First Pickup</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="activity-card card">
                    <div class="card-header">
                        <h5>Environmental Impact</h5>
                    </div>
                    <div class="card-body">
                        <div class="empty-state">
                            <i class="fas fa-leaf"></i>
                            
                            <p>Recycle e-waste to see your environmental impact</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-light text-center text-muted py-3 mt-5">
        <p>&copy; 2024 JunkGenie. All rights reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js"></script>
</body>
</html>