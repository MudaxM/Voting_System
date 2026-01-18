<?php
require_once '../includes/config.php';
requireAdmin();

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? 0;
$message = '';
$error = '';

// Handle actions (Verification/Deletion)
if (isset($_GET['verify']) && $id > 0) {
    try {
        $sql = "UPDATE users SET is_verified = 1 WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$id])) {
            $message = 'Voter account has been successfully verified!';
        }
    } catch (Exception $e) {
        $error = 'Failed to verify voter: ' . $e->getMessage();
    }
}

if (isset($_GET['delete']) && $id > 0) {
    try {
        $sql = "DELETE FROM users WHERE id = ? AND is_admin = 0";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$id])) {
            $message = 'Voter record has been successfully removed.';
        }
    } catch (Exception $e) {
        $error = 'Failed to remove voter: ' . $e->getMessage();
    }
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
    <title>Voter Directory | Admin Panel</title>
    <link rel="stylesheet" href="../Assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="admin-body">
    <?php include 'includes/admin_header.php'; ?>

    <div class="dashboard-header"
        style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
        <div class="user-info">
            <p>Review and authorize student access to the voting system</p>
        </div>
        <div class="user-actions">
            <a href="dashboard.php" class="btn btn-outline" style="background: white;">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"
            style="background: #d1fae5; border-left: 5px solid #10b981; color: #065f46; padding: 15px; border-radius: 12px; margin-bottom: 25px;">
            <i class="fas fa-check-circle"></i> <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"
            style="background: #fee2e2; border-left: 5px solid #ef4444; color: #991b1b; padding: 15px; border-radius: 12px; margin-bottom: 25px;">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="dashboard-card" style="padding: 0; overflow: hidden;">
        <div
            style="padding: 25px; border-bottom: 1px solid #f1f1f4; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0;"><i class="fas fa-list" style="color: var(--accent);"></i> Enrollment List</h3>
            <div style="position: relative;">
                <input type="text" placeholder="Search voters..."
                    style="padding: 8px 15px 8px 35px; border-radius: 8px; border: 1px solid #ddd; font-size: 0.9rem;">
                <i class="fas fa-search"
                    style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #aaa; font-size: 0.8rem;"></i>
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table class="data-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8fafc; text-align: left;">
                        <th style="padding: 15px 25px; color: #7e8299; font-size: 0.85rem; text-transform: uppercase;">
                            Student ID</th>
                        <th style="padding: 15px 25px; color: #7e8299; font-size: 0.85rem; text-transform: uppercase;">
                            Full Name</th>
                        <th style="padding: 15px 25px; color: #7e8299; font-size: 0.85rem; text-transform: uppercase;">
                            Department</th>
                        <th style="padding: 15px 25px; color: #7e8299; font-size: 0.85rem; text-transform: uppercase;">
                            Auth Status</th>
                        <th style="padding: 15px 25px; color: #7e8299; font-size: 0.85rem; text-transform: uppercase;">
                            Activity</th>
                        <th
                            style="padding: 15px 25px; color: #7e8299; font-size: 0.85rem; text-transform: uppercase; text-align: right;">
                            Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($voters)): ?>
                        <tr>
                            <td colspan="6" style="padding: 40px; text-align: center; color: #aaa;">No registered voters
                                found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($voters as $voter): ?>
                            <tr style="border-bottom: 1px solid #f1f1f4; transition: background 0.2s;"
                                onmouseover="this.style.background='#fcfcfd'" onmouseout="this.style.background='white'">
                                <td style="padding: 15px 25px; font-weight: 700; color: #181c32;">
                                    <?php echo htmlspecialchars($voter['student_id']); ?></td>
                                <td style="padding: 15px 25px;">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div class="admin-avatar"
                                            style="width: 32px; height: 32px; font-size: 0.8rem; background: #f3f6f9; color: #5e6278;">
                                            <?php echo strtoupper(substr($voter['full_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 600; color: #181c32; font-size: 0.95rem;">
                                                <?php echo htmlspecialchars($voter['full_name']); ?></div>
                                            <div style="font-size: 0.75rem; color: #7e8299;">
                                                <?php echo htmlspecialchars($voter['email']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding: 15px 25px;">
                                    <span
                                        style="background: #f1f1f4; padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; color: #5e6278; font-weight: 600;">
                                        <?php echo htmlspecialchars($voter['department']); ?>
                                    </span>
                                </td>
                                <td style="padding: 15px 25px;">
                                    <?php if ($voter['is_verified']): ?>
                                        <span
                                            style="display: flex; align-items: center; gap: 5px; color: #10b981; font-weight: 600; font-size: 0.85rem;">
                                            <i class="fas fa-check-circle"></i> Verified
                                        </span>
                                    <?php else: ?>
                                        <span
                                            style="display: flex; align-items: center; gap: 5px; color: #ffa800; font-weight: 600; font-size: 0.85rem;">
                                            <i class="fas fa-clock"></i> Pending
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 15px 25px;">
                                    <?php if ($voter['has_voted']): ?>
                                        <span
                                            style="background: #10b981; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase;">Voted</span>
                                    <?php else: ?>
                                        <span
                                            style="background: #f1f1f4; color: #7e8299; padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase;">Idle</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 15px 25px; text-align: right;">
                                    <div style="display: flex; justify-content: flex-end; gap: 8px;">
                                        <?php if (!$voter['is_verified']): ?>
                                            <a href="?verify=1&id=<?php echo $voter['id']; ?>" class="sidebar-toggle"
                                                style="background: #c9f7f5; color: #1bc5bd; width: 32px; height: 32px; text-decoration: none;"
                                                title="Verify">
                                                <i class="fas fa-check" style="font-size: 0.8rem;"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="?delete=1&id=<?php echo $voter['id']; ?>" class="sidebar-toggle"
                                            onclick="return confirm('Attention: Are you sure you want to permanently delete this voter record? This action cannot be undone.')"
                                            style="background: #ffe2e5; color: #f64e60; width: 32px; height: 32px; text-decoration: none;"
                                            title="Delete">
                                            <i class="fas fa-trash" style="font-size: 0.8rem;"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    </div> <!-- Close admin-content -->
    </div> <!-- Close admin-main -->
</body>

</html>