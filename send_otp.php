<?php
session_start();
require_once 'connect.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    
    try {
        // Generate OTP
        $otp = sprintf("%06d", random_int(0, 999999));
        $expiry = date("Y-m-d H:i:s", strtotime("+15 minutes"));
        
        // Update database with OTP
        $stmt = $conn->prepare("UPDATE users SET reset_otp = ?, reset_otp_expiry = ? WHERE email = ?");
        $stmt->bind_param("sss", $otp, $expiry, $email);
        
        if ($stmt->execute()) {
            // Send OTP email
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'ayoradileep@gmail.com';
            $mail->Password = 'tqti aeky gtep frza';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('ayoradileep@gmail.com', 'Junk Genie');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset OTP';
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #7FE0A1;'>Password Reset OTP</h2>
                    <p>Your OTP for password reset is: <strong style='color: #333;'>{$otp}</strong></p>
                    <p>This OTP will expire in 15 minutes.</p>
                    <p style='color: #666;'>If you didn't request this, please ignore this email.</p>
                </div>
            ";

            if ($mail->send()) {
                $_SESSION['email'] = $email;
                $_SESSION['message'] = "OTP has been sent to your email.";
                header("Location: verify_otp.php");
                exit();
            }
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        $_SESSION['error'] = "Failed to send OTP. Please try again.";
        header("Location: forgotpassword.php");
        exit();
    }
}

// If something goes wrong, redirect back
$_SESSION['error'] = "Something went wrong. Please try again.";
header("Location: forgotpassword.php");
exit();
?>