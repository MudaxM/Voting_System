<?php
require_once 'includes/config.php';

$full_name = 'System Admin';
$email = 'admin@voting.edu';
$password = 'Admin@123';
$student_id = 'ADMIN001';
$dept = 'Administration';
$year = 1;

// Check if user already exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    $stmt = $pdo->prepare("UPDATE users SET is_admin = 1, password = ? WHERE email = ?");
    $stmt->execute([hashPassword($password), $email]);
    echo "Existing user updated to Admin.\n";
} else {
    $sql = "INSERT INTO users (student_id, full_name, email, password, department, year, is_admin, is_verified) 
            VALUES (?, ?, ?, ?, ?, ?, 1, 1)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$student_id, $full_name, $email, hashPassword($password), $dept, $year]);
    echo "New Admin user created.\n";
}
echo "Email: $email\n";
echo "Password: $password\n";
?>