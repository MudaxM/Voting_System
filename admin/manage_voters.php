<?php
require_once '../includes/config.php';
requireAdmin();

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? 0;
$message = '';
$error = '';

// Handle actions (Verification/Deletion)
if (isset($_GET['verify']) && $id > 0) {
    $sql = "UPDATE users SET is_verified = 1 WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([$id])) {
        $message = 'Voter verified successfully!';
    } else {
        $error = 'Failed to verify voter.';
    }
    $action = 'list';
}

if (isset($_GET['delete']) && $id > 0) {
    $sql = "DELETE FROM users WHERE id = ? AND is_admin = 0";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([$id])) {
        $message = 'Voter deleted successfully!';
    } else {
        $error = 'Failed to delete voter.';
    }
    $action = 'list';
}

// Get voters for listing
$sql = "SELECT * FROM users WHERE is_admin = 0 ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$voters = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Voters - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <?php include 'includes/admin_header.php'; ?>

    <div class="admin-dashboard">
        <div class="container">
            <div class="dashboard-header">
                <div class="user-info">
                    <h2>Manage Voters</h2>
                    <p>Review and manage registered students</p>
                </div>
                <div class="user-actions">
                    <a href="dashboard.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-card">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Dept</th>
                            <th>Status/Voted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($voters as $voter): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($voter['student_id']); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($voter['full_name']); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($voter['email']); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($voter['department']); ?>
                                </td>
                                <td>
                                    <?php if ($voter['is_verified']): ?>
                                        <span class="status-badge status-active">Verified</span>
                                    <?php else: ?>
                                        <span class="status-badge status-pending">Pending</span>
                                    <?php endif; ?>

                                    <?php if ($voter['has_voted']): ?>
                                        <span class="status-badge status-active"
                                            style="background:#d1fae5; color:#065f46">Voted</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!$voter['is_verified']): ?>
                                        <a href="?verify=1&id=<?php echo $voter['id']; ?>" class="btn-action btn-view"
                                            title="Verify Voter">
                                            <i class="fas fa-check"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="?delete=1&id=<?php echo $voter['id']; ?>" class="btn-action btn-delete"
                                        onclick="return confirm('Delete this voter?')" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>