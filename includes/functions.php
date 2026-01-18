<?php
/**
 * Sanitize input data
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Hash password with pepper
 */
function hashPassword($password) {
    return password_hash($password . PEPPER, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password . PEPPER, $hash);
}

/**
 * Generate random string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

/**
 * Check if election is active
 */
function isElectionActive($pdo) {
    $sql = "SELECT * FROM election_settings WHERE is_active = 1 
            AND NOW() BETWEEN start_date AND end_date 
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetch() ? true : false;
}

/**
 * Get election status
 */
function getElectionStatus($pdo) {
    $sql = "SELECT * FROM election_settings WHERE is_active = 1 LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $election = $stmt->fetch();
    
    if (!$election) return ['status' => 'not_started', 'message' => 'Election not scheduled'];
    
    $now = new DateTime();
    $start = new DateTime($election['start_date']);
    $end = new DateTime($election['end_date']);
    
    if ($now < $start) {
        $interval = $now->diff($start);
        return [
            'status' => 'upcoming',
            'message' => 'Election starts in ' . $interval->format('%d days, %h hours'),
            'start_date' => $election['start_date'],
            'end_date' => $election['end_date']
        ];
    } elseif ($now > $end) {
        return [
            'status' => 'ended',
            'message' => 'Election has ended',
            'start_date' => $election['start_date'],
            'end_date' => $election['end_date']
        ];
    } else {
        $interval = $now->diff($end);
        return [
            'status' => 'active',
            'message' => 'Election ends in ' . $interval->format('%d days, %h hours'),
            'start_date' => $election['start_date'],
            'end_date' => $election['end_date']
        ];
    }
}

/**
 * Get positions with candidates
 */
function getPositionsWithCandidates($pdo, $active_only = true) {
    $sql = "SELECT p.*, 
                   COUNT(c.id) as candidate_count,
                   GROUP_CONCAT(c.full_name) as candidate_names
            FROM positions p
            LEFT JOIN candidates c ON p.id = c.position_id AND c.is_active = 1
            WHERE " . ($active_only ? "p.is_active = 1" : "1") . "
            GROUP BY p.id
            ORDER BY p.id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Check if user has voted
 */
function hasUserVoted($pdo, $user_id, $position_id = null) {
    if ($position_id) {
        $sql = "SELECT COUNT(*) FROM votes 
                WHERE voter_id = ? AND position_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $position_id]);
        return $stmt->fetchColumn() > 0;
    } else {
        $sql = "SELECT has_voted FROM users WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        return $user ? $user['has_voted'] : false;
    }
}

/**
 * Get user votes
 */
function getUserVotes($pdo, $user_id) {
    $sql = "SELECT v.*, p.title as position_name, c.full_name as candidate_name
            FROM votes v
            JOIN positions p ON v.position_id = p.id
            JOIN candidates c ON v.candidate_id = c.id
            WHERE v.voter_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

/**
 * Get election results
 */
function getElectionResults($pdo) {
    $sql = "SELECT 
                p.id as position_id,
                p.title as position,
                c.id as candidate_id,
                c.full_name as candidate,
                c.department,
                c.votes,
                ROUND((c.votes * 100.0 / NULLIF((
                    SELECT SUM(votes) 
                    FROM candidates c2 
                    WHERE c2.position_id = p.id AND c2.is_active = 1
                ), 0)), 2) as percentage
            FROM positions p
            LEFT JOIN candidates c ON p.id = c.position_id AND c.is_active = 1
            WHERE p.is_active = 1
            ORDER BY p.id, c.votes DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll();
    
    // Organize by position
    $organized = [];
    foreach ($results as $row) {
        $position_id = $row['position_id'];
        if (!isset($organized[$position_id])) {
            $organized[$position_id] = [
                'position' => $row['position'],
                'candidates' => []
            ];
        }
        if ($row['candidate_id']) {
            $organized[$position_id]['candidates'][] = $row;
        }
    }
    
    return $organized;
}
/**
 * Send email notification (Safe version)
 */
function sendEmail($to, $subject, $message) {
    // Check prerequisites
    if (!function_exists('mail')) {
        // Log error but don't show to user
        error_log("Email function not available");
        return false;
    }
    
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        error_log("Invalid email: $to");
        return false;
    }
    
    // Prepare email
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: "Student Voting System" <noreply@voting.edu>',
        'Reply-To: no-reply@voting.edu',
        'X-Mailer: PHP/' . phpversion()
    ];
    
    // Send with error suppression during development
    $result = @mail($to, $subject, $message, implode("\r\n", $headers));
    
    return $result;
}
/**
 * Log activity
 */
function logActivity($pdo, $user_id, $action, $details = null) {
    $sql = "INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $user_id,
        $action,
        $details,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    ]);
}

/**
 * Check for brute force attacks
 */
function checkBruteForce($pdo, $email) {
    $sql = "SELECT COUNT(*) as attempts 
            FROM login_attempts 
            WHERE email = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL ? SECOND)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email, LOCKOUT_TIME]);
    $result = $stmt->fetch();
    
    return $result['attempts'] >= MAX_LOGIN_ATTEMPTS;
}

/**
 * Add login attempt
 */
function addLoginAttempt($pdo, $email, $success = false) {
    $sql = "INSERT INTO login_attempts (email, success, ip_address, user_agent) 
            VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $email,
        $success ? 1 : 0,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    ]);
}

/**
 * Validate student ID 
 */
function validateStudentID($student_id) {
    // Initialize return array
    $result = [
        'valid' => false,
        'message' => '',
        'formatted_id' => ''
    ];
    
    // Clean the input
    $student_id = strtoupper(trim($student_id));
    
    // Basic validation
    if (empty($student_id)) {
        $result['message'] = 'Student ID is required';
        return $result;
    }
    
    if (strlen($student_id) < 5 || strlen($student_id) > 30) {
        $result['message'] = 'Student ID must be 5-30 characters';
        return $result;
    }
    
    // Check for allowed characters
    if (!preg_match('/^[A-Z0-9\/\-\.\_]+$/', $student_id)) {
        $result['message'] = 'Invalid characters in Student ID. Use only letters, numbers, /, -, ., _';
        return $result;
    }
    
    // Check if it contains both letters and numbers
    if (!preg_match('/[A-Z]/', $student_id) || !preg_match('/[0-9]/', $student_id)) {
        $result['message'] = 'Student ID must contain both letters and numbers';
        return $result;
    }
    
    // Check if already exists in database
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE student_id = ?");
        $stmt->execute([$student_id]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            $result['message'] = 'Student ID already registered';
            return $result;
        }
    } catch (Exception $e) {
        // If database error, just continue (registration will fail later if duplicate)
        error_log("Database error in validateStudentID: " . $e->getMessage());
    }
    
    // If all checks pass
    $result['valid'] = true;
    $result['message'] = 'Valid Student ID';
    $result['formatted_id'] = $student_id;
    
    return $result;
}

/**
 * Validate email domain
 */
function validateEmailDomain($email) {
    $allowed_domains = ['edu', 'ac', 'school'];
    $domain = substr(strrchr($email, "@"), 1);
    $tld = pathinfo($domain, PATHINFO_EXTENSION);
    return in_array($tld, $allowed_domains);
}
?>