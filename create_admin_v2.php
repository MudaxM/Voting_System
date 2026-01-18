<?php
require_once 'includes/config.php';

function createUser($pdo, $full_name, $email, $password, $student_id, $is_admin)
{
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("UPDATE users SET is_admin = ?, password = ? WHERE email = ?");
        $stmt->execute([$is_admin, hashPassword($password), $email]);
        echo "User $email updated.\n";
    } else {
        $sql = "INSERT INTO users (student_id, full_name, email, password, department, year, is_admin, is_verified) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$student_id, $full_name, $email, hashPassword($password), 'General', 1, $is_admin]);
        echo "User $email created.\n";
    }
}

createUser($pdo, 'System Admin', 'admin@voting.edu', 'Admin@123', 'ADMIN001', 1);
createUser($pdo, 'Standard Voter', 'voter@university.edu', 'Voter@123', 'VOTER001', 0);
?>