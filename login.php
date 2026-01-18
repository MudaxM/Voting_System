<?php
require_once 'includes/config.php';
redirectIfLoggedIn();

$error = '';
$email = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(sanitize($_POST['email'] ?? ''));
    $password = trim($_POST['password'] ?? '');
    $remember = isset($_POST['remember']);

    // Check brute force
    if (checkBruteForce($pdo, $email)) {
        $error = 'Too many failed login attempts. Please try again in 15 minutes.';
    } else {
        $result = loginUser($pdo, $email, $password);

        if ($result['success']) {
            // Set remember me cookie
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $expiry = time() + (30 * 24 * 60 * 60); // 30 days

                $sql = "UPDATE users SET remember_token = ?, token_expiry = ? WHERE email = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$token, date('Y-m-d H:i:s', $expiry), $email]);

                setcookie('remember_token', $token, $expiry, '/', '', true, true);
            }

            // Redirect based on role
            if ($result['is_admin']) {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: vote.php');
            }
            exit();
        } else {
            $error = $result['message'];
        }
    }
}

// Check for remember token
if (isset($_COOKIE['remember_token']) && !isLoggedIn()) {
    $token = $_COOKIE['remember_token'];

    $sql = "SELECT * FROM users WHERE remember_token = ? AND token_expiry > NOW()";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['is_admin'] ? 'admin' : 'voter';
        $_SESSION['student_id'] = $user['student_id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];

        if ($user['is_admin']) {
            header('Location: admin/dashboard.php');
        } else {
            header('Location: vote.php');
        }
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Student Union Voting System</title>
    <link rel="stylesheet" href="Assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .login-options {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 1.5rem;
        }

        .login-option {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            border: 2px solid var(--gray-light);
            border-radius: var(--radius);
            text-decoration: none;
            color: var(--dark);
            transition: var(--transition);
        }

        .login-option:hover {
            border-color: var(--primary);
            background-color: #f8fafc;
        }

        .login-option i {
            font-size: 1.2rem;
            color: var(--primary);
        }

        .forgot-password {
            text-align: center;
            margin-top: 1.5rem;
        }

        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-logo .logo {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .demo-credentials {
            background-color: #f0f9ff;
            border: 2px solid #bae6fd;
            border-radius: var(--radius);
            padding: 1rem;
            margin-top: 1.5rem;
            font-size: 0.9rem;
        }

        .demo-credentials h4 {
            color: var(--info);
            margin-bottom: 10px;
        }

        .demo-credentials ul {
            margin: 0;
            padding-left: 20px;
        }

        .demo-credentials li {
            margin-bottom: 5px;
        }
    </style>
</head>

<body>
    <div class="form-container">
        <div class="form-box">
            <div class="login-logo">
                <a href="index.php" class="logo">
                    <i class="fas fa-vote-yea"></i>
                    <span>StudentVote</span>
                </a>
                <p>Student Union Voting System</p>
            </div>

            <div class="form-header">
                <h2>Welcome Back</h2>
                <p>Login to access your voting dashboard</p>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <strong>Success!</strong>
                        <p><?php echo htmlspecialchars($_SESSION['success']);
                        unset($_SESSION['success']); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <strong>Login Failed</strong>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <input type="email" id="email" name="email" class="form-control"
                        value="<?php echo htmlspecialchars($email); ?>" required
                        placeholder="your.email@university.edu">
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <div style="position: relative;">
                        <input type="password" id="password" name="password" class="form-control" required
                            placeholder="Enter your password">
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group" style="display: flex; justify-content: space-between; align-items: center;">
                    <div class="form-check">
                        <input type="checkbox" id="remember" name="remember" class="form-check-input">
                        <label for="remember" class="form-check-label">
                            Remember me for 30 days
                        </label>
                    </div>
                    <a href="forgot_password.php" class="form-link">Forgot Password?</a>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-sign-in-alt"></i> Login to Dashboard
                </button>



                <div class="form-footer">
                    <p>Don't have an account? <a href="register.php" class="form-link">Register here</a></p>
                </div>
            </form>

            <div class="login-options">
                <a href="index.php" class="login-option">
                    <i class="fas fa-home"></i>
                    <div>
                        <strong>Return to Homepage</strong>
                        <p>Back to election information</p>
                    </div>
                </a>
                <a href="results_public.php" class="login-option">
                    <i class="fas fa-chart-bar"></i>
                    <div>
                        <strong>View Election Results</strong>
                        <p>See current voting statistics</p>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <script>
        // Password visibility toggle
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');

        togglePassword.addEventListener('click', function () {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });

        // Auto-focus email field
        document.getElementById('email').focus();

        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function (e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            if (!email || !password) {
                e.preventDefault();
                alert('Please fill in all required fields!');
                return false;
            }

            // Basic email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address!');
                return false;
            }

            return true;
        });

        // Enter key to submit
        document.addEventListener('keypress', function (e) {
            if (e.key === 'Enter' && e.target.type !== 'textarea') {
                if (e.target.id === 'password') {
                    document.getElementById('loginForm').submit();
                }
            }
        });

        // Show/hide demo credentials
        const demoCredentials = document.querySelector('.demo-credentials');
        const showDemoBtn = document.createElement('button');
        showDemoBtn.innerHTML = '<i class="fas fa-eye"></i> Show Demo Credentials';
        showDemoBtn.style.cssText = 'width: 100%; padding: 10px; margin-top: 15px; background: transparent; border: 2px solid var(--gray-light); border-radius: var(--radius); cursor: pointer; color: var(--gray);';
        showDemoBtn.addEventListener('click', function () {
            demoCredentials.style.display = demoCredentials.style.display === 'none' ? 'block' : 'none';
            this.innerHTML = demoCredentials.style.display === 'none' ?
                '<i class="fas fa-eye"></i> Show Demo Credentials' :
                '<i class="fas fa-eye-slash"></i> Hide Demo Credentials';
        });

        // Insert show/hide button
        demoCredentials.insertAdjacentElement('beforebegin', showDemoBtn);
    </script>
</body>

</html>