<?php
require_once '../includes/config.php';
require_once '../includes/auth.php'; // ensure loginUser() and helpers are available

redirectIfLoggedIn();

// Admin-specific login
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Normalize and sanitize inputs consistent with login.php
    $email = strtolower(sanitize($_POST['email'] ?? ''));
    $password = trim($_POST['password'] ?? '');

    // Brute-force / rate-limit check (loginUser will also check but we can pre-check)
    if (checkBruteForce($pdo, $email)) {
        $error = 'Too many failed login attempts. Please try again in 15 minutes.';
    } else {
        // Reuse centralized loginUser() which returns ['success' => bool, 'message' => string]
        $result = loginUser($pdo, $email, $password);

        // Debug logging to help determine why login fails (no passwords logged)
        error_log('[admin-login] email=' . $email . ' result=' . json_encode($result));
        $stmtDbg = $pdo->prepare("SELECT id, email, is_admin, is_verified FROM users WHERE email = ? LIMIT 1");
        $stmtDbg->execute([$email]);
        $dbUser = $stmtDbg->fetch();
        error_log('[admin-login] dbUser=' . json_encode($dbUser));

        if ($result['success']) {
            // Ensure the account is an admin
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && $user['is_admin']) {
                // Set admin session (consistent with other code)
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = 'admin';
                $_SESSION['student_id'] = $user['student_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['is_admin'] = true;

                // Update last login
                $sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$user['id']]);

                // Log admin login if helper exists
                if (function_exists('logActivity')) {
                    logActivity($pdo, $user['id'], 'admin_login', 'Admin logged in');
                }

                // Redirect to admin dashboard
                header('Location: dashboard.php');
                exit();
            } else {
                // Authenticated but not an admin
                $error = 'You are not authorized to access the admin panel.';
            }
        } else {
            // Propagate the message from loginUser (e.g. invalid credentials, not verified, etc.)
            $error = $result['message'] ?? 'Invalid admin credentials.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Student Union Voting System</title>
    <link rel="stylesheet" href="../Assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg: #f4f6f8;
            --card: #fff;
            --primary: #2563eb;
            --gray: #6b7280;
            --gray-light: #e6e9ee;
            --radius: 8px;
            --transition: 0.15s ease;
        }
        body {
            background: var(--bg);
            font-family: Arial, Helvetica, sans-serif;
        }
        .admin-login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .admin-login-box {
            width: 420px;
            background: var(--card);
            padding: 2rem;
            border-radius: var(--radius);
            box-shadow: 0 6px 20px rgba(15, 23, 42, 0.08);
        }
        .admin-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .admin-header .admin-logo { font-size: 28px; color: var(--primary); }
        .admin-header h2 { margin: 8px 0 0; }
        .alert { padding: 12px; border-radius: 6px; margin-bottom: 1rem; display: flex; gap: 12px; align-items: flex-start; }
        .alert-error { background: #fff4f4; border: 1px solid #fecaca; color: #b91c1c; }
        .form-group { margin-bottom: 1rem; }
        .form-label { display: block; margin-bottom: 6px; color: var(--gray); font-size: 0.95rem; }
        .form-control { width: 100%; padding: 10px 12px; border: 1px solid var(--gray-light); border-radius: 6px; }
        .password-toggle { position: absolute; right: 8px; top: 36px; background: transparent; border: none; cursor: pointer; }
        .btn { background: var(--primary); color: #fff; padding: 10px 12px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .login-hint { text-align: center; margin-top: 1.5rem; color: var(--gray); font-size: 0.9rem; }
        .back-to-site { text-align: center; margin-top: 2rem; }
    </style>
</head>
<body>
    <div class="admin-login-container">
        <div class="admin-login-box">
            <div class="admin-header">
                <div class="admin-logo">
                    <i class="fas fa-shield-alt"></i>
                    <span>StudentVote</span>
                </div>
                <h2>Admin Panel</h2>
                <span class="admin-badge">Restricted Access</span>
            </div>

            <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>Access Denied</strong>
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <div class="security-notice">
                <i class="fas fa-lock"></i>
                <strong>Security Notice:</strong> This area is restricted to authorized personnel only.
                All activities are logged and monitored.
            </div>

            <form method="POST" action="" id="adminLoginForm">
                <div class="form-group">
                    <label for="email" class="form-label">
                        <i class="fas fa-user-shield"></i> Admin Email
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           class="form-control" 
                           required
                           autocomplete="username"
                           placeholder="admin@voting.edu">
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-key"></i> Password
                    </label>
                    <div style="position: relative;">
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="form-control" 
                               required
                               autocomplete="current-password"
                               placeholder="••••••••">
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" 
                               id="remember" 
                               name="remember" 
                               class="form-check-input">
                        <label for="remember" class="form-check-label">
                            Remember this device (30 days)
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn" style="width: 100%;">
                    <i class="fas fa-sign-in-alt"></i> Access Admin Panel
                </button>
            </form>

            <div class="login-hint">
                <i class="fas fa-info-circle"></i>
                <p>Default admin: admin@voting.edu / Admin@123</p>
            </div>

            <div class="back-to-site">
                <a href="../index.php" class="form-link">
                    <i class="fas fa-arrow-left"></i> Back to Main Site
                </a>
            </div>
        </div>
    </div>

    <script>
        // Password visibility toggle
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        
togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });
        
        // Auto-focus email field
        document.getElementById('email').focus();
        
        // Form validation
        document.getElementById('adminLoginForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                alert('Please enter both email and password.');
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
    </script>
</body>
</html>
