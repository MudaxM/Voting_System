<?php
/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isLoggedIn() && $_SESSION['user_role'] === 'admin';
}

/**
 * Check if user is voter
 */
function isVoter() {
    return isLoggedIn() && $_SESSION['user_role'] === 'voter';
}

/**
 * Require login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['error'] = 'Please login to access this page';
        header('Location: ../login.php');
        exit();
    }
}

/**
 * Require admin
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        $_SESSION['error'] = 'Access denied. Admin privileges required.';
        header('Location: ../index.php');
        exit();
    }
}

/**
 * Require voter
 */
function requireVoter() {
    requireLogin();
    if (!isVoter()) {
        $_SESSION['error'] = 'Access denied. Voter privileges required.';
        header('Location: ../index.php');
        exit();
    }
}

/**
 * Redirect if logged in
 */
function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        if (isAdmin()) {
            header('Location: admin/dashboard.php');
        } else {
            header('Location: vote.php');
        }
        exit();
    }
}

/**
 * Get current user data
 */
function getCurrentUser($pdo) {
    if (!isLoggedIn()) return null;
    
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Login user
 */
function loginUser($pdo, $email, $password) {
    // Check brute force
    if (checkBruteForce($pdo, $email)) {
        addLoginAttempt($pdo, $email, false);
        return ['success' => false, 'message' => 'Too many failed attempts. Please try again later.'];
    }
    
    // Get user
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        addLoginAttempt($pdo, $email, false);
        return ['success' => false, 'message' => 'Invalid email or password'];
    }
    
    // Verify password
    if (!verifyPassword($password, $user['password'])) {
        addLoginAttempt($pdo, $email, false);
        return ['success' => false, 'message' => 'Invalid email or password'];
    }
    
    // Check if verified
    if (!$user['is_verified']) {
        return ['success' => false, 'message' => 'Account not verified. Please contact administrator.'];
    }
    
    // Successful login
    addLoginAttempt($pdo, $email, true);
    
    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $user['is_admin'] ? 'admin' : 'voter';
    $_SESSION['student_id'] = $user['student_id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $user['email'];
    
    // Update last login
    $sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user['id']]);
    
    return ['success' => true, 'is_admin' => $user['is_admin']];
}

/**
 * Register user
 */
function registerUser($pdo, $data) {
    // Validate input
    $errors = [];
    
    if (empty($data['student_id'])) {
        $errors[] = 'Student ID is required';
    } elseif (!validateStudentID($data['student_id'])) {
        $errors[] = 'Invalid Student ID format';
    }
    
    if (empty($data['full_name'])) {
        $errors[] = 'Full name is required';
    }
    
    if (empty($data['email'])) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    } elseif (!validateEmailDomain($data['email'])) {
        $errors[] = 'Only educational email addresses are allowed';
    }
    
    if (empty($data['password'])) {
        $errors[] = 'Password is required';
    } elseif (strlen($data['password']) < 8) {
        $errors[] = 'Password must be at least 8 characters';
    } elseif ($data['password'] !== $data['confirm_password']) {
        $errors[] = 'Passwords do not match';
    }
    
    if (empty($data['department'])) {
        $errors[] = 'Department is required';
    }
    
    if (empty($data['year']) || $data['year'] < 1 || $data['year'] > 5) {
        $errors[] = 'Year must be between 1 and 5';
    }
    
    // Check if student ID or email exists
    $sql = "SELECT id FROM users WHERE student_id = ? OR email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$data['student_id'], $data['email']]);
    if ($stmt->fetch()) {
        $errors[] = 'Student ID or Email already registered';
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    // Insert user
    $sql = "INSERT INTO users (student_id, full_name, email, password, department, year) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    $hashed_password = hashPassword($data['password']);
    
    try {
        $stmt->execute([
            $data['student_id'],
            $data['full_name'],
            $data['email'],
            $hashed_password,
            $data['department'],
            $data['year']
        ]);
        
        // Send verification email
        $verification_code = generateRandomString(32);
        $verification_link = SITE_URL . "verify.php?code=" . $verification_code;
        
        $message = "
            <h2>Welcome to Student Union Voting System</h2>
            <p>Dear " . $data['full_name'] . ",</p>
            <p>Your account has been created successfully. Please wait for administrator verification.</p>
            <p>You will receive another email once your account is verified.</p>
            <p>Best regards,<br>Student Union Election Committee</p>
        ";
        
        sendEmail($data['email'], 'Account Registration - Student Union Voting', $message);
        
        return ['success' => true, 'message' => 'Registration successful! Please wait for administrator verification.'];
        
    } catch (PDOException $e) {
        return ['success' => false, 'errors' => ['Registration failed: ' . $e->getMessage()]];
    }
}

/**
 * Logout user
 */
function logoutUser() {
    // Unset all session variables
    $_SESSION = array();
    
    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy session
    session_destroy();
}
?>