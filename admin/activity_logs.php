<?php
require_once '../includes/config.php';
requireAdmin();

$sql = "SELECT al.*, u.full_name, u.student_id 
        FROM activity_logs al 
        JOIN users u ON al.user_id = u.id 
        ORDER BY al.created_at DESC 
        LIMIT 100";
$logs = $pdo->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs | Admin Panel</title>
    <link rel="stylesheet" href="../Assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="admin-body">
    <?php include 'includes/admin_header.php'; ?>

    <div class="dashboard-header" style="margin-bottom: 30px;">
        <div class="user-info">
            <p>Transparency and security tracking of all administrative and voter activity</p>
        </div>
    </div>

    <div class="dashboard-card" style="padding: 0; overflow: hidden;">
        <div style="padding: 25px; border-bottom: 1px solid #f1f1f4; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0;"><i class="fas fa-fingerprint" style="color: var(--accent);"></i> System Activity Feed</h3>
        </div>
        <div style="overflow-x: auto;">
            <table class="data-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8fafc; text-align: left;">
                        <th style="padding: 15px 25px; color: #7e8299; font-size: 0.85rem; text-transform: uppercase;">Timestamp</th>
                        <th style="padding: 15px 25px; color: #7e8299; font-size: 0.85rem; text-transform: uppercase;">User Identity</th>
                        <th style="padding: 15px 25px; color: #7e8299; font-size: 0.85rem; text-transform: uppercase;">Activity Type</th>
                        <th style="padding: 15px 25px; color: #7e8299; font-size: 0.85rem; text-transform: uppercase;">Security Origin</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="4" style="padding: 40px; text-align: center; color: #aaa;">No system logs recorded yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                        <tr style="border-bottom: 1px solid #f1f1f4;">
                            <td style="padding: 15px 25px;">
                                <div style="font-weight: 700; color: #181c32; font-size: 0.9rem;"><?php echo date('M d, Y', strtotime($log['created_at'])); ?></div>
                                <div style="font-size: 0.8rem; color: #b5b5c3;"><?php echo date('H:i:s A', strtotime($log['created_at'])); ?></div>
                            </td>
                            <td style="padding: 15px 25px;">
                                <div style="font-weight: 600; color: #181c32;"><?php echo htmlspecialchars($log['full_name']); ?></div>
                                <div style="font-size: 0.75rem; color: #7e8299;">ID: <?php echo htmlspecialchars($log['student_id']); ?></div>
                            </td>
                            <td style="padding: 15px 25px;">
                                <div style="font-weight: 600; color: #3f4254; font-size: 0.9rem;"><?php echo htmlspecialchars($log['action']); ?></div>
                                <?php if($log['details']): ?>
                                    <div style="font-size: 0.75rem; color: #7e8299; margin-top: 4px;"><?php echo htmlspecialchars($log['details']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px 25px;">
                                <span style="background: #f3f6f9; color: #7e8299; padding: 4px 10px; border-radius: 6px; font-family: monospace; font-size: 0.8rem;">
                                    <?php echo htmlspecialchars($log['ip_address'] ?? '0.0.0.0'); ?>
                                </span>
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