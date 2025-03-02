<?php
session_start();
require_once 'connect.php';
$page_title = 'Reset Password - Junk Genie';
require_once 'includes/header.php';

if (!isset($_SESSION['reset_authorized']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Debug logging
    error_log("Password Reset Attempt - User ID: " . $user_id);
    
    if (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        try {
            $conn->begin_transaction();
            
            // Simplified password hashing
            $hashed_password = password_hash(trim($new_password), PASSWORD_DEFAULT);
            
            // Debug logging
            error_log("Password hash generated: " . substr($hashed_password, 0, 10) . "...");
            
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $conn->commit();
                // Clear session variables
                unset($_SESSION['reset_authorized']);
                unset($_SESSION['user_id']);
                $_SESSION['message'] = "Password has been reset successfully. Please login with your new password.";
                header("Location: login.php");
                exit();
            } else {
                throw new Exception("Failed to update password");
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Failed to reset password. Please try again.";
            error_log("Password Reset Error: " . $e->getMessage());
        }
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
                            <h1 class="display-6">Reset<br>Password</h1>
                            <img src="images/genie.png" alt="JunkGenie Mascot" class="img-fluid mascot mt-4">
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card-body p-4">
                            <h2 class="card-title text-center mb-4">Create New Password</h2>
                            
                            <?php if ($error): ?>
                                <div class="alert alert-danger alert-dismissible fade show">
                                    <?php echo htmlspecialchars($error); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <form method="POST" id="resetPasswordForm" class="needs-validation" novalidate>
                                <div class="mb-4">
                                    <div class="form-floating">
                                        <input type="password" 
                                               class="form-control" 
                                               id="new_password" 
                                               name="new_password" 
                                               required 
                                               minlength="8"
                                               placeholder="Enter new password">
                                        <label for="new_password">New Password</label>
                                        <div class="invalid-feedback" id="passwordError"></div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <div class="form-floating">
                                        <input type="password" 
                                               class="form-control" 
                                               id="confirm_password" 
                                               name="confirm_password" 
                                               required 
                                               minlength="8"
                                               placeholder="Confirm new password">
                                        <label for="confirm_password">Confirm Password</label>
                                        <div class="invalid-feedback" id="confirmPasswordError"></div>
                                    </div>
                                </div>

                                <div class="password-requirements mb-4">
                                    <small class="text-muted">Password must:</small>
                                    <ul class="small text-muted">
                                        <li id="length">Be at least 8 characters long</li>
                                        <li id="letter">Include at least one letter</li>
                                        <li id="number">Include at least one number</li>
                                        <li id="match">Passwords must match</li>
                                    </ul>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 btn-lg mb-3" id="submitBtn" disabled>
                                    Reset Password
                                </button>
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
    const form = document.getElementById('resetPasswordForm');
    const password = document.getElementById('new_password');
    const confirm = document.getElementById('confirm_password');
    const submitBtn = document.getElementById('submitBtn');

    // Password requirements check
    function checkPassword() {
        const val = password.value;
        let valid = true;

        // Length check
        const lengthValid = val.length >= 8;
        document.getElementById('length').classList.toggle('text-success', lengthValid);
        valid = valid && lengthValid;

        // Letter check
        const letterValid = /[a-zA-Z]/.test(val);
        document.getElementById('letter').classList.toggle('text-success', letterValid);
        valid = valid && letterValid;

        // Number check
        const numberValid = /\d/.test(val);
        document.getElementById('number').classList.toggle('text-success', numberValid);
        valid = valid && numberValid;

        // Match check
        const matchValid = val === confirm.value && val !== '';
        document.getElementById('match').classList.toggle('text-success', matchValid);
        valid = valid && matchValid;

        // Update password field validation
        if (valid) {
            password.classList.remove('is-invalid');
            password.classList.add('is-valid');
        } else {
            password.classList.remove('is-valid');
            password.classList.add('is-invalid');
        }

        submitBtn.disabled = !valid;
        return valid;
    }

    password.addEventListener('input', checkPassword);
    confirm.addEventListener('input', checkPassword);

    form.addEventListener('submit', function(e) {
        if (!checkPassword()) {
            e.preventDefault();
            return false;
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>