<?php
session_start();
require_once 'connect.php';
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
} else {
    die('Please run "composer require google/apiclient" to install dependencies');
}

$page_title = 'Login - Junk Genie';
require_once 'includes/header.php';

$errors = [];

// Handle Google Sign-In
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['google_id_token'])) {
    $client = new Google_Client(['client_id' => '131560699137-kda6v9j8sjl619k7v7lpp36o7n0b5saj.apps.googleusercontent.com']);
    
    try {
        $payload = $client->verifyIdToken($_POST['google_id_token']);
        if ($payload) {
            $email = $payload['email'];
            $fullname = $payload['name'] ?? 'User';
            
            // Query without city column
            $stmt = $conn->prepare("SELECT user_id, fullname, password, role, email, phone FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                // Bind results without city
                $stmt->bind_result($user_id, $db_fullname, $hashed_password, $role, $db_email, $phone);
                $stmt->fetch();
                
                $_SESSION['user_id'] = $user_id;
                $_SESSION['fullname'] = $db_fullname;
                $_SESSION['email'] = $db_email;
                $_SESSION['phone'] = $phone;
                $_SESSION['role'] = $role;

                header("Location: " . ($role == 'Admin' ? 'admindashboard.php' : 
                      ($role == 'Employee' ? 'employeedashboard.php' : 'userdashboard.php')));
                exit();
            } else {
                $_SESSION['google_signup_data'] = [
                    'email' => $email,
                    'fullname' => $fullname
                ];
                header("Location: signup.php?source=google");
                exit();
            }
        }
    } catch (Exception $e) {
        $errors[] = 'Google login failed: ' . $e->getMessage();
        error_log("Google Sign-In Error: " . $e->getMessage());
    }
}

// Handle regular email/password login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['google_id_token'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }

    if (empty($password)) {
        $errors[] = 'Password is required';
    }

    if (empty($errors)) {
        // Query without city column
        $stmt = $conn->prepare("SELECT user_id, fullname, password, role, email, phone FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Bind results without city
            $stmt->bind_result($user_id, $fullname, $hashed_password, $role, $db_email, $phone);
            $stmt->fetch();

            if (password_verify($password, $hashed_password)) {
                $_SESSION['user_id'] = $user_id;
                $_SESSION['fullname'] = $fullname;
                $_SESSION['email'] = $db_email;
                $_SESSION['phone'] = $phone;
                $_SESSION['role'] = $role;

                if ($role == 'Admin') {
                    header("Location: admindashboard.php");
                } elseif($role == 'Employee') {
                    header("Location: employeedashboard.php");
                } else {
                    header("Location: userdashboard.php");
                }
                exit();
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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script src="main.js" defer></script>
    <style>
        .google-btn-container {
            margin: 20px 0;
            margin-left: 180px;
            text-align: center;
        }
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 20px 0;
        }
        .divider::before, .divider::after {
            content: "";
            flex: 1;
            border-bottom: 1px solid #ddd;
        }
        .divider-text {
            padding: 0 10px;
        }
        
        /* Improved Google Button Styling */
        .google-btn-wrapper {
            display: flex;
            justify-content: center;
            margin: 550px 0;
            padding: 0 50px;
        }
        
        .g_id_signin {
            transform: scale(1.15);
            width: 300px !important;
        }
        
        /* Divider Styling */
        .divider {
            display: flex;
            align-items: center;
            margin: 20px 0;
            color: #6c757d;
        }
        
        .divider::before,
        .divider::after {
            content: "";
            flex: 1;
            border-bottom: 1px solid #dee2e6;
        }
        
        .divider-text {
            padding: 0 10px;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 576px) {
            .g_id_signin {
                transform: scale(1);
                width: 100% !important;
            }
        }
    
    </style>
</head>
<body>
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
                                    <input type="email" class="form-control" id="email" name="email" required>
                                    <div class="invalid-feedback" id="emailError"></div>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <div class="invalid-feedback" id="passwordError"></div>
                                </div>

                                <button type="submit" class="btn btn-primary w-100">Login</button>

                                <div class="divider">
                                    <span class="divider-text">OR</span>
                                </div>

                                <div class="google-btn-container">
                                    <div id="g_id_onload"
                                         data-client_id="131560699137-kda6v9j8sjl619k7v7lpp36o7n0b5saj.apps.googleusercontent.com"
                                         data-context="signin"
                                         data-ux_mode="popup"
                                         data-callback="handleGoogleSignIn"
                                         data-auto_prompt="false">
                                    </div>
                                    <div class="g_id_signin"
                                         data-type="standard"
                                         data-shape="rectangular"
                                         data-theme="outline"
                                         data-text="signin_with"
                                         data-size="large"
                                         data-logo_alignment="left">
                                    </div>
                                </div>

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
function handleGoogleSignIn(response) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'login.php';
    
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'google_id_token';
    input.value = response.credential;
    
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
}

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