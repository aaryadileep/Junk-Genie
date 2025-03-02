<!DOCTYPE html>
<html lang="en">
<head>
    <?php
    session_start();

    // Check if the user is logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php"); // Redirect to login if not logged in
        exit();
    }

    // Get user details from session
    $fullname = $_SESSION['fullname'];
    $email = $_SESSION['email'];
    $phone = $_SESSION['phone'];
    $city = $_SESSION['city']; // Fetch city from session
    ?>

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
            --text-light: #777;
            --white: #fff;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --radius: 8px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f8f9fa;
            color: var(--text-dark);
        }

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
            font-size: 0.9rem;
            color: var(--text-light);
        }

        .location i {
            color: var(--primary);
            font-size: 1.2rem;
        }

        .profile-menu {
            position: relative;
        }

        .profile-pic {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            cursor: pointer;
            border: 2px solid var(--primary);
            transition: all 0.3s ease;
        }

        .profile-pic:hover {
            transform: scale(1.05);
            box-shadow: 0 0 10px rgba(76, 175, 80, 0.5);
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
            overflow: hidden;
            z-index: 101;
            transform-origin: top right;
            animation: dropdownFade 0.3s ease forwards;
        }

        @keyframes dropdownFade {
            from {
                opacity: 0;
                transform: translateY(-10px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .dropdown-menu.show {
            display: block;
        }

        .dropdown-menu a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            text-decoration: none;
            color: var(--text-dark);
            transition: background 0.3s;
        }

        .dropdown-menu a:hover {
            background-color: #f1f1f1;
            color: var(--primary);
        }

        .dropdown-menu a:not(:last-child) {
            border-bottom: 1px solid #eee;
        }

        /* Main Content Styles */
        main {
            max-width: 1100px;
            margin: 0 auto;
            padding: 2rem 1rem;
            text-align: center;
        }

        h2 {
            font-size: 2.5rem;
            margin-bottom: 2rem;
            color: var(--primary-dark);
            text-shadow: 1px 1px 3px rgba(0,0,0,0.1);
            position: relative;
            display: inline-block;
        }

        h2:after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background-color: var(--primary);
            border-radius: 2px;
        }

        .features {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 2rem;
            margin: 3rem 0;
        }

        .feature {
            background-color: var(--white);
            border-radius: var(--radius);
            padding: 2rem;
            box-shadow: var(--shadow);
            width: 280px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
        }

        .feature:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .feature img {
            width: 100px;
            height: 100px;
            object-fit: contain;
            margin-bottom: 1rem;
            transition: transform 0.3s ease;
        }

        .feature:hover img {
            transform: scale(1.1);
        }

        .feature p {
            font-weight: 500;
            color: var(--text-dark);
        }

        .sell-btn {
            background-color: var(--primary);
            color: var(--white);
            border: none;
            padding: 1rem 3rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
            transition: all 0.3s ease;
            margin-top: 2rem;
            position: relative;
            overflow: hidden;
        }

        .sell-btn:hover {
            background-color: var(--primary-dark);
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
            transform: translateY(-3px);
        }

        .sell-btn:active {
            transform: translateY(0);
        }

        .sell-btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%);
            transform-origin: 50% 50%;
        }

        @keyframes ripple {
            0% {
                transform: scale(0, 0);
                opacity: 0.5;
            }
            100% {
                transform: scale(20, 20);
                opacity: 0;
            }
        }

        .sell-btn:focus:not(:active)::after {
            animation: ripple 1s ease-out;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            header {
                padding: 0.8rem 1rem;
            }

            h2 {
                font-size: 1.8rem;
            }

            .features {
                gap: 1rem;
            }

            .feature {
                width: 100%;
                max-width: 320px;
            }

            .sell-btn {
                padding: 0.8rem 2rem;
            }
        }
    </style>
</head>

<body>
    <header>
        <div class="location">
            <i class="fas fa-map-marker-alt"></i>
            <span><p><?php echo htmlspecialchars($city); ?></p><span>
        </div>
        <div class="profile-menu">
            <img src="images/profile.jpg" alt="Profile" class="profile-pic" onclick="toggleMenu()">
            <p><?php echo htmlspecialchars($fullname); ?></p>
            <div class="dropdown-menu" id="profileDropdown">
                <a href="profile.php">View Profile</a>
                <a href="settings.php">Settings</a>
                <a href="logout.php">Log Out</a>
            </div>
        </div>
    </header>

    <main>
        <h2>SELL E-WASTE NOW!</h2>
        <div class="features">
            <div class="feature" onclick="showToast('Browse e-waste categories')">
                <img src="images/waste.png" alt="Category">
                <p>Sell e-waste by category</p>
            </div>
            <div class="feature" onclick="showToast('Schedule a pickup from your location')">
                <img src="images/pickup.png" alt="Pickup">
                <p>Pickup from your place</p>
            </div>
            <div class="feature" onclick="showToast('Checking best rates for your location')">
                <img src="images/coin.png" alt="Rates">
                <p>Get the best rates</p>
            </div>
        </div>
        <button class="sell-btn" onclick="startSelling()">Sell your scrap now!</button>
    </main>

    <!-- Toast Notification -->
    <div class="toast" id="toast">
        <i class="fas fa-info-circle"></i>
        <span id="toastMessage"></span>
    </div>

    <script>
        // Toggle Profile Dropdown
        function toggleMenu() {
            document.getElementById("profileDropdown").classList.toggle("show");
        }

        // Close dropdown when clicking outside
        window.onclick = function(event) {
            if (!event.target.matches('.profile-pic')) {
                document.getElementById("profileDropdown").classList.remove("show");
            }
        }

        // Toast Notification Function
        function showToast(message) {
            const toast = document.getElementById("toast");
            document.getElementById("toastMessage").textContent = message;

            toast.classList.add("show");

            setTimeout(() => {
                toast.classList.remove("show");
            }, 3000);
        }

        // Start Selling Function
        function startSelling() {
            showToast("Taking you to the selling page...");
            // In production: window.location.href = "sell.php";
        }
    </script>
</body>
</html>