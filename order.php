<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit();
}

// Get user details from session
$fullname = $_SESSION['fullname'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Place Order | JunkGenie</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Reuse the same CSS from dashboard.php */
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

        main {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        h2 {
            font-size: 2rem;
            margin-bottom: 1.5rem;
            color: var(--primary-dark);
            text-align: center;
        }

        .order-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .order-form input, .order-form select, .order-form textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: var(--radius);
            font-size: 1rem;
        }

        .order-form button {
            background-color: var(--primary);
            color: var(--white);
            border: none;
            padding: 0.8rem 2rem;
            font-size: 1rem;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .order-form button:hover {
            background-color: var(--primary-dark);
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <h1>JunkGenie</h1>
        </div>
        <div class="profile-menu">
            <img src="images/profile.jpg" alt="Profile" class="profile-pic" onclick="toggleMenu()">
            <div class="dropdown-menu" id="profileDropdown">
                <a href="dashboard.php">Dashboard</a>
                <a href="profile.php">View Profile</a>
                <a href="settings.php">Settings</a>
                <a href="logout.php">Log Out</a>
            </div>
        </div>
    </header>

    <main>
        <h2>Place Your Order</h2>
        <form class="order-form" onsubmit="placeOrder(event)">
            <input type="text" placeholder="Full Name" value="<?php echo htmlspecialchars($fullname); ?>" required>
            <input type="text" placeholder="Address" required>
            <input type="tel" placeholder="Phone Number" required>
            <select required>
                <option value="">Select E-Waste Category</option>
                <option value="laptop">Laptops</option>
                <option value="mobile">Mobile Phones</option>
                <option value="tv">Televisions</option>
                <option value="other">Other</option>
            </select>
            <textarea placeholder="Additional Details" rows="4"></textarea>
            <button type="submit">Place Order</button>
        </form>
    </main>

    <script>
        function toggleMenu() {
            document.getElementById("profileDropdown").classList.toggle("show");
        }

        function placeOrder(event) {
            event.preventDefault();
            alert("Order placed successfully!");
            window.location.href = "dashboard.php";
        }

        window.onclick = function(event) {
            if (!event.target.matches('.profile-pic')) {
                document.getElementById("profileDropdown").classList.remove("show");
            }
        }
    </script>
</body>
</html>