<?php
if (!isset($_SESSION)) {
    session_start();
}

// Get current page name
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>

<!-- Sidebar -->
<div class="sidebar">
    <div class="logo-container">
        <img src="logo.jpg" alt="JunkGenie Logo" class="logo-img">
        <span class="logo-text">JunkGenie</span>
    </div>

    <ul class="nav-menu">
        <li class="nav-item">
            <a href="admindashboard.php" class="nav-link <?php echo $current_page == 'admindashboard' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="usermanagement.php" class="nav-link <?php echo $current_page == 'usermanagement' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>User Management</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="employeemanagement.php" class="nav-link <?php echo $current_page == 'employeemanagement' ? 'active' : ''; ?>">
                <i class="fas fa-user-tie"></i>
                <span>Employee Management</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="citymanagement.php" class="nav-link <?php echo $current_page == 'citymanagement' ? 'active' : ''; ?>">
                <i class="fas fa-city"></i>
                <span>City Management</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="pickuprequestmanagement.php" class="nav-link <?php echo $current_page == 'pickuprequests' ? 'active' : ''; ?>">
                <i class="fas fa-truck"></i>
                <span>Pickup Requests</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="ewaste.php" class="nav-link <?php echo $current_page == 'ewaste' ? 'active' : ''; ?>">
                <i class="fas fa-recycle"></i>
                <span>E-Waste Collection</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="analytics.php" class="nav-link <?php echo $current_page == 'analytics' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i>
                <span>Reports & Analytics</span>
            </a>
        </li>
    </ul>
</div>

<!-- Sidebar CSS -->
<style>
    /* Sidebar Styles */
    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        width: 280px;
        background-color: #4CAF50; /* Green background */
        color: white;
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        z-index: 1000;
        transition: all 0.3s ease;
    }

    /* Logo Container */
    .logo-container {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding-bottom: 2rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        margin-bottom: 2rem;
    }

    .logo-img {
        width: 40px;
        height: 40px;
        object-fit: contain;
    }

    .logo-text {
        font-size: 1.5rem;
        font-weight: 600;
    }

    /* Navigation Menu */
    .nav-menu {
        list-style: none;
        padding: 0;
        margin: 0;
        flex-grow: 1;
    }

    .nav-item {
        margin-bottom: 0.5rem;
    }

    .nav-link {
        display: flex;
        align-items: center;
        padding: 0.75rem 1rem;
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    .nav-link:hover, .nav-link.active {
        background: rgba(255, 255, 255, 0.1);
        color: white;
        transform: translateX(5px);
    }

    .nav-link i {
        width: 20px;
        margin-right: 10px;
        font-size: 1.1rem;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
        }

        .sidebar.show {
            transform: translateX(0);
        }
    }
</style>

<!-- Main Content Adjustment -->
<style>
    /* Add margin to main content to avoid overlap with sidebar */
    .main-content {
        margin-left: 280px; /* Same as sidebar width */
        padding: 20px;
        transition: margin-left 0.3s ease;
    }

    @media (max-width: 768px) {
        .main-content {
            margin-left: 0;
        }
    }
</style>