<?php
session_start();
require 'connect.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    try {
        // Check if email exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            // Generate token
            $token = bin2hex(random_bytes(32));
            $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));
            
            $conn->begin_transaction();

            // Update token in database
            $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE email = ?");
            $stmt->bind_param("sss", $token, $expiry, $email);
            
            if ($stmt->execute()) {
                $reset_link = "http://localhost/Junkgenie/resetpassword.php?token=" . urlencode($token);
                
                $mail = new PHPMailer(true);
                
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'ayoradileep@gmail.com';
                $mail->Password = 'tqti aeky gtep frza';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                // Disable debug output
                $mail->SMTPDebug = 0;

                // Recipients
                $mail->setFrom('ayoradileep@gmail.com', 'Junk Genie');
                $mail->addAddress($email);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Request';
                $mail->Body = "
                    <h2>Password Reset Request</h2>
                    <p>Click the button below to reset your password:</p>
                    <p><a href='$reset_link' style='background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Reset Password</a></p>
                    <p>If the button doesn't work, copy and paste this link:</p>
                    <p>$reset_link</p>
                    <p>This link will expire in 1 hour.</p>
                ";
                $mail->AltBody = "Reset your password by clicking this link: $reset_link";

                if ($mail->send()) {
                    $conn->commit();
                    $_SESSION['message'] = "Password reset instructions have been sent to your email.";
                } else {
                    throw new Exception("Email could not be sent");
                }
            }
        } else {
            $_SESSION['message'] = "If the email exists in our system, you will receive password reset instructions.";
        }
    } catch (Exception $e) {
        $conn->rollback();
        error_log($e->getMessage());
        $_SESSION['error'] = "An error occurred. Please try again later.";
    }

    header("Location: login.php");
    exit();
}
?>