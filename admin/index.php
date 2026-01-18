<?php
require_once '../includes/config.php';
redirectIfLoggedIn();

// Admin-specific login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $error = '';
    
    // Check brute force
    if (checkBruteForce($pdo, $email)) {
        $error = 'Too many failed login attempts. Please try again in 15 minutes.';
    } else {
        // Check if user exists and is admin
        $sql = "SELECT * FROM users WHERE email = ? AND is_admin = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && verifyPassword($password, $user['password'])) {
            // Check if verified
            if (!$user['is_verified']) {
                $error = 'Admin account not verified.';
            } else {
                // Successful login
                addLoginAttempt($pdo, $email, true);
                
                // Set admin session
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
                
                // Log admin login
                logActivity($pdo, $user['id'], 'admin_login', 'Admin logged in');
                
                // Redirect to admin dashboard
                header('Location: dashboard.php');
                exit();
            }
        } else {
            addLoginAttempt($pdo, $email, false);
            $error = 'Invalid admin credentials.';
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
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            padding: 20px;
        }
        .admin-login-box {
            background: white;
            padding: 3rem;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 450px;
            position: relative;
            overflow: hidden;
        }
        .admin-login-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }
        .admin-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .admin-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 2rem;
            color: var(--primary-dark);
            margin-bottom: 1rem;
        }
        .admin-logo i {
            font-size: 2.5rem;
        }
        .admin-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 5px;
        }
        .security-notice {
            background-color: #fef3c7;
            border: 1px solid #fde68a;
            border-radius: var(--radius);
            padding: 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
        .security-notice i {
            color: #f59e0b;
            margin-right: 8px;
        }
        .login-hint {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--gray);
            font-size: 0.9rem;
        }
        .back-to-site {
            text-align: center;
            margin-top: 2rem;
        }
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

                <button type="submit" class="btn btn-primary" style="width: 100%;">
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
            
            return true;
        });
        
        // Detect and block screen capture attempts
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey && e.shiftKey && e.key === 'S') || 
                (e.ctrlKey && e.key === 'PrintScreen')) {
                alert('Screenshots are disabled in the admin panel for security reasons.');
                e.preventDefault();
                return false;
            }
        });
        
        // Disable right-click context menu
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            alert('Right-click is disabled in the admin panel.');
            return false;
        });
    </script>
</body>
</html>