<?php
session_start();
require_once 'connect.php';
$page_title = 'Verify OTP - Junk Genie';
require_once 'includes/header.php';

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$email = $_SESSION['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp']);
    
    // Debug logging
    error_log("Verifying OTP - Email: " . $email . ", OTP: " . $otp);
    
    // Check if OTP exists and is valid
    $stmt = $conn->prepare("SELECT user_id, reset_otp, reset_otp_expiry FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    // Debug logging
    error_log("Database values - OTP: " . $user['reset_otp'] . ", Expiry: " . $user['reset_otp_expiry']);
    
    if ($user && $user['reset_otp'] === $otp && strtotime($user['reset_otp_expiry']) > time()) {
        $_SESSION['reset_authorized'] = true;
        $_SESSION['user_id'] = $user['user_id'];
        
        // Clear OTP after successful verification
        $clear_stmt = $conn->prepare("UPDATE users SET reset_otp = NULL, reset_otp_expiry = NULL WHERE user_id = ?");
        $clear_stmt->bind_param("i", $user['user_id']);
        $clear_stmt->execute();
        
        header("Location: reset_password.php");
        exit();
    } else {
        $error = "Invalid or expired OTP.";
        error_log("OTP Verification failed - Provided: $otp, Stored: " . ($user ? $user['reset_otp'] : 'no user found'));
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="row g-0">
                    <div class="col-md-4 bg-primary text-white text-center p-4 d-flex align-items-center">
                        <div class="w-100">
                            <h1 class="display-6">Verify<br>OTP</h1>
                            <img src="images/genie.png" alt="JunkGenie Mascot" class="img-fluid mascot mt-4">
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card-body p-4">
                            <h2 class="card-title text-center mb-4">Enter Verification Code</h2>
                            
                            <?php if (isset($error) && !empty($error)): ?>
                                <div class="alert alert-danger alert-dismissible fade show">
                                    <?php echo htmlspecialchars($error); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <div class="text-center mb-4">
                                <p class="text-muted">
                                    We've sent a verification code to<br>
                                    <strong><?php echo htmlspecialchars($email); ?></strong>
                                </p>
                            </div>
                            
                            <form method="POST" id="otpForm" class="needs-validation" novalidate>
                                <div class="mb-4">
                                    <div class="form-floating">
                                        <input type="text" 
                                               class="form-control text-center form-control-lg" 
                                               id="otp" 
                                               name="otp" 
                                               required 
                                               minlength="6" 
                                               maxlength="6"
                                               pattern="\d{6}"
                                               placeholder="Enter OTP"
                                               autocomplete="off">
                                        <label for="otp">Enter 6-digit OTP</label>
                                        <div class="invalid-feedback" id="otpError">
                                            Please enter a valid 6-digit OTP
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 btn-lg mb-3" id="verifyButton" disabled>
                                    Verify OTP
                                </button>

                                <div class="text-center">
                                    <p class="mb-0 text-muted">Didn't receive the code?</p>
                                    <button type="button" class="btn btn-link" id="resendButton" disabled>
                                        Resend OTP <span id="timer">(2:00)</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Real-time OTP validation
document.getElementById('otp').addEventListener('input', function(e) {
    const input = e.target;
    const submitBtn = document.getElementById('verifyButton');
    const pattern = /^[0-9]{6}$/;
    
    // Remove any non-numeric characters
    input.value = input.value.replace(/[^0-9]/g, '');
    
    if (pattern.test(input.value)) {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
        submitBtn.disabled = false;
    } else {
        input.classList.remove('is-valid');
        input.classList.add('is-invalid');
        submitBtn.disabled = true;
    }
});

// Resend timer
function startResendTimer() {
    const resendBtn = document.getElementById('resendButton');
    const timerSpan = document.getElementById('timer');
    let timeLeft = 120; // 2 minutes

    const timer = setInterval(() => {
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        timerSpan.textContent = `(${minutes}:${seconds.toString().padStart(2, '0')})`;
        
        if (timeLeft <= 0) {
            clearInterval(timer);
            resendBtn.disabled = false;
            timerSpan.textContent = '';
        }
        timeLeft--;
    }, 1000);
}

// Start timer on page load
document.addEventListener('DOMContentLoaded', startResendTimer);

// Handle form submission
document.getElementById('otpForm').addEventListener('submit', function(e) {
    const otp = document.getElementById('otp');
    if (!/^[0-9]{6}$/.test(otp.value)) {
        e.preventDefault();
        otp.classList.add('is-invalid');
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>