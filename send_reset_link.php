<?php
session_start();
require 'connect.php'; // Include the MySQLi database connection file
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    // Check if the email exists in the database
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        // Generate a unique token
        $token = bin2hex(random_bytes(50));
        $expiry = date("Y-m-d H:i:s", strtotime("+2 hours")); // Token expires in 2 hours

        // Save the token and expiry in the database
        $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE email = ?");
        $stmt->bind_param("sss", $token, $expiry, $email);
        $stmt->execute();

        // Debug: Print the token and expiry for testing
        echo "Token: $token<br>";
        echo "Expiry: $expiry<br>";

        // Define the reset link
        $reset_link = "http://localhost/Junkgenie/resetpassword.php?token=$token";

        // Send the reset link via email using PHPMailer
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com'; // Gmail SMTP server
            $mail->SMTPAuth   = true;
            $mail->Username   = 'ayoradileep@gmail.com'; // Your Gmail address
            $mail->Password   = 'zuat fckg zoge knzb'; // Your Gmail password or App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Recipients
            $mail->setFrom('junkgenie@ewaste.com', 'Junk Genie');
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $mail->Body    = "Click the link to reset your password: <a href='$reset_link'>$reset_link</a>";
            $mail->AltBody = "Click the link to reset your password: $reset_link";

            $mail->send();
            $_SESSION['message'] = "Password reset link sent to your email.";
        } catch (Exception $e) {
            $_SESSION['error'] = "Failed to send email. Error: " . $mail->ErrorInfo;
        }
    } else {
        $_SESSION['error'] = "Email not found.";
    }

    header("Location: login.php");
    exit();
}
?>