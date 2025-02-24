<?php
session_start();
require_once 'connect.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
//hello

// Fetch user details from the database
$query = "SELECT fullname, email, phone FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($fullname, $email, $phone);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile | JunkGenie</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            max-width: 600px;
            width: 100%;
            margin: 2rem;
            padding: 2.5rem;
            background-color: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            text-align: center;
        }

        h2 { 
            color: var(--primary-dark); 
            margin-bottom: 1.5rem; 
            font-size: 2rem;
            font-weight: 600;
        }

        .profile-info { 
            margin-bottom: 2rem; 
        }

        .profile-info div {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px;
            border-bottom: 1px solid #ddd;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }

        .profile-info div:hover {
            background-color: var(--secondary);
        }

        .profile-info i { 
            color: var(--primary); 
            margin-right: 10px; 
            font-size: 1.2rem;
        }

        .edit-form input {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: var(--radius);
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .edit-form input:focus {
            border-color: var(--primary);
            outline: none;
        }

        .error-msg {
            color: red;
            font-size: 0.9rem;
            margin-bottom: 10px;
            text-align: left;
        }

        .save-btn {
            width: 100%;
            padding: 12px;
            background-color: var(--primary);
            color: var(--white);
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }

        .save-btn:hover { 
            background-color: var(--primary-dark); 
        }

        @media (max-width: 768px) {
            .container {
                padding: 1.5rem;
            }

            h2 {
                font-size: 1.75rem;
            }

            .profile-info div {
                font-size: 0.9rem;
            }

            .edit-form input {
                font-size: 0.9rem;
            }

            .save-btn {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <h2>Your Profile</h2>
        
        <div class="profile-info">
            <div><i class="fas fa-user"></i> <span id="displayFullname"><?php echo htmlspecialchars($fullname); ?></span></div>
            <div><i class="fas fa-envelope"></i> <span id="displayEmail"><?php echo htmlspecialchars($email); ?></span></div>
            <div><i class="fas fa-phone"></i> <span id="displayPhone"><?php echo htmlspecialchars($phone); ?></span></div>
        </div>

        <form id="editProfileForm">
            <input type="text" id="fullname" name="fullname" placeholder="Full Name" value="<?php echo htmlspecialchars($fullname); ?>" required>
            <p class="error-msg" id="fullnameError"></p>

            <input type="email" id="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($email); ?>" required>
            <p class="error-msg" id="emailError"></p>

            <input type="tel" id="phone" name="phone" placeholder="Phone Number" value="<?php echo htmlspecialchars($phone); ?>" required>
            <p class="error-msg" id="phoneError"></p>

            <button class="save-btn" type="button" onclick="updateProfile()">Save Changes</button>
        </form>
    </div>

    <script>
        function updateProfile() {
            let fullname = document.getElementById("fullname").value.trim();
            let email = document.getElementById("email").value.trim();
            let phone = document.getElementById("phone").value.trim();

            // Client-side validation
            if (!/^[A-Z][a-z]*(?: [A-Z][a-z]*)*$/.test(fullname)) {
                document.getElementById("fullnameError").innerText = "Full name must start with a capital letter.";
                return;
            } else {
                document.getElementById("fullnameError").innerText = "";
            }

            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                document.getElementById("emailError").innerText = "Invalid email format.";
                return;
            } else {
                document.getElementById("emailError").innerText = "";
            }

            if (!/^[6789]\d{9}$/.test(phone)) {
                document.getElementById("phoneError").innerText = "Invalid phone number.";
                return;
            } else {
                document.getElementById("phoneError").innerText = "";
            }

            // Prepare data to send to PHP
            let formData = new FormData();
            formData.append("fullname", fullname);
            formData.append("email", email);
            formData.append("phone", phone);

            fetch("profile.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === "success") {
                    alert(data.message);
                    document.getElementById("displayFullname").innerText = fullname;
                    document.getElementById("displayEmail").innerText = email;
                    document.getElementById("displayPhone").innerText = phone;
                } else {
                    alert(data.message);
                }
            })
            .catch(error => console.error("Error:", error));
        }
    </script>

    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $fullname = trim($_POST["fullname"]);
        $email = trim($_POST["email"]);
        $phone = trim($_POST["phone"]);

        if (!preg_match("/^[A-Z][a-z]*(?: [A-Z][a-z]*)*$/", $fullname)) {
            echo json_encode(["status" => "error", "message" => "Full name must start with a capital letter."]);
            exit();
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(["status" => "error", "message" => "Invalid email format."]);
            exit();
        }

        if (!preg_match("/^[6789]\d{9}$/", $phone)) {
            echo json_encode(["status" => "error", "message" => "Invalid phone number."]);
            exit();
        }

        $query = "UPDATE users SET fullname = ?, email = ?, phone = ? WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssi", $fullname, $email, $phone, $user_id);

        if ($stmt->execute()) {
            $_SESSION["fullname"] = $fullname;
            $_SESSION["email"] = $email;
            $_SESSION["phone"] = $phone;
            echo json_encode(["status" => "success", "message" => "Profile updated successfully!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to update profile."]);
        }

        $stmt->close();
        $conn->close();
    }
    ?>
</body>
</html>