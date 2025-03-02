<?php
session_start();
require_once 'connect.php';
$page_title = 'Login - Junk Genie';
require_once 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $errors = [];

    // Validate Email Format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }

    // Ensure Password is Not Empty
    if (empty($password)) {
        $errors[] = 'Password is required';
    }

    if (empty($errors)) {
        // Prepare SQL Query
        $stmt = $conn->prepare("SELECT user_id, fullname, password, role, email FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($user_id, $fullname, $hashed_password, $role, $email_verified);
            $stmt->fetch();

            // Verify Password
            if (password_verify($password, $hashed_password)) {
                // Check if email is verified
                if (!$email_verified) {
                    $errors[] = 'Please verify your email address before logging in.';
                } else {
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['fullname'] = $fullname;
                    $_SESSION['email'] = $email;
                    $_SESSION['phone']=$phone;
                    $_SESSION['city']=$city;//store city in session
                    // Store name in session
                    $_SESSION['role'] = $role;

                    // Redirect Based on Role
                    if ($role == 'Admin') {
                        header("Location: admindashboard.php");
                    } else {
                        header("Location: userdashboard.php");
                    }
                    exit();
                }
            } else {
                $errors[] = 'Incorrect email or password';
            }
        } else {
            $errors[] = 'No account found with that email address';
        }

        $stmt->close();
    }

    $conn->close();
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="row g-0">
                    <div class="col-md-4 bg-primary text-white text-center p-4">
                        <div class="my-auto">
                            <h1 class="display-6">Welcome to<br>JunkGenie</h1>
                            <img src="images/genie.png" alt="JunkGenie Mascot" class="img-fluid mascot mt-4">
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card-body p-4">
                            <h2 class="card-title text-center mb-4">Login</h2>
                            
                            <?php if (isset($_SESSION['message'])): ?>
                                <div class="alert alert-success">
                                    <?php 
                                    echo htmlspecialchars($_SESSION['message']);
                                    unset($_SESSION['message']);
                                    ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger">
                                    <?php foreach ($errors as $error): ?>
                                        <p class="mb-0"><?php echo htmlspecialchars($error); ?></p>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <form action="login.php" method="POST" id="loginForm">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" 
                                           name="email" required>
                                    <div class="invalid-feedback" id="emailError"></div>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" 
                                           name="password" required>
                                    <div class="invalid-feedback" id="passwordError"></div>
                                </div>

                                <button type="submit" class="btn btn-primary w-100">
                                    Login
                                </button>

                                <div class="text-center mt-3">
                                    <a href="forgotpassword.php" class="d-block mb-2">Forgot Password?</a>
                                    Don't have an account? <a href="signin.php">Register here</a>
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
document.getElementById('loginForm').addEventListener('submit', function(e) {
    let isValid = true;
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    const emailError = document.getElementById('emailError');
    const passwordError = document.getElementById('passwordError');

    // Email validation
    if (!email.value) {
        email.classList.add('is-invalid');
        emailError.textContent = 'Email is required';
        isValid = false;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
        email.classList.add('is-invalid');
        emailError.textContent = 'Please enter a valid email address';
        isValid = false;
    } else {
        email.classList.remove('is-invalid');
        email.classList.add('is-valid');
    }

    // Password validation
    if (!password.value) {
        password.classList.add('is-invalid');
        passwordError.textContent = 'Password is required';
        isValid = false;
    } else {
        password.classList.remove('is-invalid');
        password.classList.add('is-valid');
    }

    if (!isValid) {
        e.preventDefault();
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>