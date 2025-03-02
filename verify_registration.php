<?php
session_start();
require_once 'connect.php';
$page_title = 'Verify Registration - Junk Genie';
require_once 'includes/header.php';

if (!isset($_SESSION['registration'])) {
    header("Location: signin.php");
    exit();
}

$error = '';
$registration = $_SESSION['registration'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $submitted_otp = trim($_POST['otp']);
    
    if ($submitted_otp === $registration['otp'] && time() < strtotime($registration['otp_expiry'])) {
        try {
            $conn->begin_transaction();
            
            // Insert user data
            $stmt = $conn->prepare("INSERT INTO users (fullname, email, phone, password, role, city_id) VALUES (?, ?, ?, ?, 'End User', ?)");
            $stmt->bind_param("ssssi", 
                $registration['fullname'],
                $registration['email'],
                $registration['phone'],
                $registration['password'],
                $registration['city_id'] // Ensure this is an integer
            );
            if ($stmt->execute()) {
                $conn->commit();
                unset($_SESSION['registration']);
                $_SESSION['message'] = "Registration successful! Please login.";
                header("Location: login.php");
                exit();
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Registration failed. Please try again.";
            error_log($e->getMessage());
        }
    } else {
        $error = "Invalid or expired OTP.";
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
                            <h1 class="display-6">Verify<br>Account</h1>
                            <img src="images/genie.png" alt="JunkGenie Mascot" class="img-fluid mascot mt-4">
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card-body p-4">
                            <h2 class="card-title text-center mb-4">Enter Verification Code</h2>
                            
                            <?php if ($error): ?>
                                <div class="alert alert-danger alert-dismissible fade show">
                                    <?php echo htmlspecialchars($error); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <div class="text-center mb-4">
                                <p class="text-muted">
                                    We've sent a verification code to<br>
                                    <strong><?php echo htmlspecialchars($registration['email']); ?></strong>
                                </p>
                            </div>
                            
                            <form method="POST" id="verifyForm" class="needs-validation" novalidate>
                                <div class="mb-4">
                                    <div class="otp-input-container">
                                        <input type="text" 
                                               class="form-control form-control-lg text-center" 
                                               id="otp" 
                                               name="otp" 
                                               required 
                                               minlength="6" 
                                               maxlength="6"
                                               pattern="\d{6}"
                                               placeholder="Enter 6-digit OTP"
                                               autocomplete="off">
                                        <div class="invalid-feedback" id="otpError">
                                            Please enter a valid 6-digit code
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 btn-lg mb-3" id="verifyBtn" disabled>
                                    Verify Account
                                </button>

                                <div class="text-center">
                                    <p class="mb-0 text-muted">Didn't receive the code?</p>
                                    <button type="button" class="btn btn-link" id="resendBtn" disabled>
                                        Resend Code <span id="timer">(2:00)</span>
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
document.addEventListener('DOMContentLoaded', function() {
    const otpInput = document.getElementById('otp');
    const verifyBtn = document.getElementById('verifyBtn');
    const resendBtn = document.getElementById('resendBtn');
    const timerSpan = document.getElementById('timer');

    // OTP input validation
    otpInput.addEventListener('input', function(e) {
        // Remove non-numeric characters
        this.value = this.value.replace(/[^0-9]/g, '');
        
        const isValid = /^\d{6}$/.test(this.value);
        if (isValid) {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
            verifyBtn.disabled = false;
        } else {
            this.classList.remove('is-valid');
            this.classList.add('is-invalid');
            verifyBtn.disabled = true;
        }
    });

    // Resend timer
    function startResendTimer() {
        let timeLeft = 120; // 2 minutes
        resendBtn.disabled = true;

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
    startResendTimer();

    // Form validation
    document.getElementById('verifyForm').addEventListener('submit', function(e) {
        if (!/^\d{6}$/.test(otpInput.value)) {
            e.preventDefault();
            otpInput.classList.add('is-invalid');
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
</body>
</html>