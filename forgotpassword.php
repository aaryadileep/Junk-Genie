<?php
session_start();
$page_title = 'Forgot Password - Junk Genie';
require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="row g-0">
                    <div class="col-md-4 bg-primary text-white text-center p-4">
                        <div class="my-auto">
                            <h1 class="display-6">Reset<br>Password</h1>
                            <img src="images/genie.png" alt="JunkGenie Mascot" class="img-fluid mascot mt-4">
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card-body p-4">
                            <h2 class="card-title text-center mb-4">Forgot Password</h2>
                            
                            <?php if (isset($_SESSION['message'])): ?>
                                <div class="alert alert-success">
                                    <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <form action="send_otp.php" method="POST" id="forgotPasswordForm">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                    <div class="invalid-feedback" id="emailError"></div>
                                </div>

                                <button type="submit" class="btn btn-primary w-100">
                                    Send Reset OTP
                                </button>

                                <div class="text-center mt-3">
                                    <a href="login.php">Back to Login</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>