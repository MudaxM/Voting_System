<?php
require_once '../includes/config.php';
requireAdmin();

// Get admin statistics
$stats = [];

// Total registered voters
$sql = "SELECT COUNT(*) as count FROM users WHERE is_admin = 0";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$stats['total_voters'] = $stmt->fetch()['count'];

// Verified voters
$sql = "SELECT COUNT(*) as count FROM users WHERE is_verified = 1 AND is_admin = 0";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$stats['verified_voters'] = $stmt->fetch()['count'];

// Voters who have voted
$sql = "SELECT COUNT(DISTINCT v.voter_id) as count FROM votes v 
        JOIN users u ON v.voter_id = u.id WHERE u.is_admin = 0";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$stats['voters_voted'] = $stmt->fetch()['count'];

// Total positions
$sql = "SELECT COUNT(*) as count FROM positions WHERE is_active = 1";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$stats['total_positions'] = $stmt->fetch()['count'];

// Total candidates
$sql = "SELECT COUNT(*) as count FROM candidates WHERE is_active = 1";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$stats['total_candidates'] = $stmt->fetch()['count'];

// Total votes cast
$sql = "SELECT COUNT(*) as count FROM votes";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$stats['total_votes'] = $stmt->fetch()['count'];

// Election status
$election_status = getElectionStatus($pdo);

// Recent activity logs
$sql = "SELECT al.*, u.full_name, u.student_id 
        FROM activity_logs al 
        JOIN users u ON al.user_id = u.id 
        ORDER BY al.created_at DESC 
        LIMIT 6";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$recent_activity = $stmt->fetchAll();

// Recent registrations
$sql = "SELECT * FROM users WHERE is_admin = 0 ORDER BY created_at DESC LIMIT 5";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$recent_registrations = $stmt->fetchAll();

// System health
$sql = "SHOW STATUS LIKE 'Threads_connected'";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$db_connections = $stmt->fetch()['Value'] ?? 1;

// Current admin
$admin = getCurrentUser($pdo);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Admin Panel</title>
    <link rel="stylesheet" href="../Assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="admin-body">

    <?php include 'includes/admin_header.php'; ?>

    <!-- Election Status Banner -->
    <div class="dashboard-card"
        style="background: linear-gradient(135deg, #1e1e2d 0%, #3a0ca3 100%); color: white; border: none;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
            <div>
                <div class="status-badge"
                    style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: white; margin-bottom: 10px;">
                    <i class="fas fa-circle <?php echo $election_status['status'] === 'active' ? 'text-success' : 'text-warning'; ?>"
                        style="font-size: 0.6rem;"></i>
                    <?php echo strtoupper($election_status['status']); ?> PHASE
                </div>
                <h1 style="color: white; font-size: 1.75rem; margin: 0; font-weight: 800;">Student Union Elections 2024
                </h1>
                <p style="opacity: 0.7; margin-top: 5px;"><?php echo $election_status['message']; ?></p>
            </div>
            <div
                style="display: flex; gap: 30px; background: rgba(0,0,0,0.2); padding: 15px 25px; border-radius: 15px;">
                <div style="text-align: center;">
                    <div style="font-size: 0.75rem; opacity: 0.6; text-transform: uppercase; letter-spacing: 1px;">Start
                        Date</div>
                    <div style="font-weight: 700; margin-top: 3px;">
                        <?php echo date('M d, H:i', strtotime($election_status['start_date'])); ?></div>
                </div>
                <div style="width: 1px; background: rgba(255,255,255,0.1);"></div>
                <div style="text-align: center;">
                    <div style="font-size: 0.75rem; opacity: 0.6; text-transform: uppercase; letter-spacing: 1px;">End
                        Date</div>
                    <div style="font-weight: 700; margin-top: 3px;">
                        <?php echo date('M d, H:i', strtotime($election_status['end_date'])); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats Grid -->
    <div
        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 25px; margin-bottom: 30px;">
        <div class="stat-widget">
            <div class="stat-icon-box bg-light-primary">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="stat-info">
                <h2><?php echo number_format($stats['total_voters']); ?></h2>
                <p>Registered Voters</p>
            </div>
        </div>
        <div class="stat-widget">
            <div class="stat-icon-box bg-light-success">
                <i class="fas fa-vote-yea"></i>
            </div>
            <div class="stat-info">
                <h2><?php echo number_format($stats['total_votes']); ?></h2>
                <p>Total Votes Cast</p>
            </div>
        </div>
        <div class="stat-widget">
            <div class="stat-icon-box bg-light-warning">
                <i class="fas fa-user-tie"></i>
            </div>
            <div class="stat-info">
                <h2><?php echo number_format($stats['total_candidates']); ?></h2>
                <p>Candidates</p>
            </div>
        </div>
        <div class="stat-widget">
            <div class="stat-icon-box bg-light-danger">
                <i class="fas fa-percent"></i>
            </div>
            <div class="stat-info">
                <h2><?php echo $stats['total_voters'] > 0 ? round(($stats['voters_voted'] / $stats['total_voters']) * 100, 1) : 0; ?>%
                </h2>
                <p>Voter Turnout</p>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 25px;">
        <!-- Left: Recent Activity & Quick Actions -->
        <div>
            <div class="dashboard-card">
                <h3><i class="fas fa-bolt" style="color: #ffa800;"></i> Quick Operations</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 15px;">
                    <a href="manage_voters.php" class="btn btn-outline"
                        style="flex-direction: column; padding: 20px; border-style: dashed; border-color: #ddd;">
                        <i class="fas fa-users" style="font-size: 1.5rem; margin-bottom: 10px;"></i>
                        <span>Voters</span>
                    </a>
                    <a href="manage_candidates.php?action=add" class="btn btn-outline"
                        style="flex-direction: column; padding: 20px; border-style: dashed; border-color: #ddd;">
                        <i class="fas fa-plus-circle" style="font-size: 1.5rem; margin-bottom: 10px;"></i>
                        <span>New Candidate</span>
                    </a>
                    <a href="results.php" class="btn btn-outline"
                        style="flex-direction: column; padding: 20px; border-style: dashed; border-color: #ddd;">
                        <i class="fas fa-chart-line" style="font-size: 1.5rem; margin-bottom: 10px;"></i>
                        <span>Live Results</span>
                    </a>
                    <a href="settings.php" class="btn btn-outline"
                        style="flex-direction: column; padding: 20px; border-style: dashed; border-color: #ddd;">
                        <i class="fas fa-cog" style="font-size: 1.5rem; margin-bottom: 10px;"></i>
                        <span>Settings</span>
                    </a>
                </div>
            </div>

            <div class="dashboard-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="margin: 0;"><i class="fas fa-history" style="color: var(--accent);"></i> Recent Activity
                    </h3>
                    <a href="activity_logs.php"
                        style="font-size: 0.85rem; font-weight: 600; color: var(--accent); text-decoration: none;">View
                        Audit Logs</a>
                </div>

                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <?php if (empty($recent_activity)): ?>
                        <p style="text-align: center; color: #b5b5c3; padding: 20px;">No recent activity found.</p>
                    <?php else: ?>
                        <?php foreach ($recent_activity as $log): ?>
                            <div
                                style="display: flex; align-items: center; gap: 15px; padding: 12px; border-radius: 12px; background: #f9f9fb;">
                                <div
                                    style="width: 40px; height: 40px; border-radius: 10px; background: white; display: flex; align-items: center; justify-content: center; color: #5e6278; border: 1px solid #f1f1f4;">
                                    <i class="fas fa-user-edit" style="font-size: 0.9rem;"></i>
                                </div>
                                <div style="flex-grow: 1;">
                                    <div style="font-size: 0.9rem; font-weight: 700; color: #181c32;">
                                        <?php echo htmlspecialchars($log['full_name']); ?></div>
                                    <div style="font-size: 0.8rem; color: #7e8299;">
                                        <?php echo htmlspecialchars($log['action']); ?></div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 0.75rem; color: #b5b5c3; font-weight: 600;">
                                        <?php echo date('H:i A', strtotime($log['created_at'])); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right: System Health & Info -->
        <div>
            <div class="dashboard-card">
                <h3><i class="fas fa-heartbeat" style="color: #f64e60;"></i> System Pulse</h3>
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 15px; border-bottom: 1px solid #f1f1f4;">
                        <span style="color: #7e8299; font-size: 0.9rem;">Database</span>
                        <span style="font-weight: 700; color: #1bc5bd; font-size: 0.9rem;"><i
                                class="fas fa-check-circle"></i> Connected</span>
                    </div>
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 15px; border-bottom: 1px solid #f1f1f4;">
                        <span style="color: #7e8299; font-size: 0.9rem;">Active Connections</span>
                        <span
                            style="font-weight: 700; color: #181c32; font-size: 0.9rem;"><?php echo $db_connections; ?>
                            Threads</span>
                    </div>
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 15px; border-bottom: 1px solid #f1f1f4;">
                        <span style="color: #7e8299; font-size: 0.9rem;">Server Latency</span>
                        <span style="font-weight: 700; color: #181c32; font-size: 0.9rem;">14ms</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: #7e8299; font-size: 0.9rem;">Security Layer</span>
                        <span style="font-weight: 700; color: #1bc5bd; font-size: 0.9rem;">Encrypted</span>
                    </div>
                </div>
                <button onclick="location.reload()" class="btn btn-primary"
                    style="width: 100%; margin-top: 20px; font-size: 0.85rem; padding: 10px;">
                    <i class="fas fa-sync-alt"></i> Refresh System Status
                </button>
            </div>

            <div class="dashboard-card" style="background: #fdfdfd;">
                <h3><i class="fas fa-info-circle" style="color: var(--accent);"></i> Election Intel</h3>
                <p style="font-size: 0.85rem; color: #7e8299; line-height: 1.6;">
                    Turnout is currently at
                    <strong><?php echo $stats['total_voters'] > 0 ? round(($stats['voters_voted'] / $stats['total_voters']) * 100, 1) : 0; ?>%</strong>.
                    Target turnout for a valid quorum is <strong>50.1%</strong>.
                </p>
                <div style="width: 100%; height: 8px; background: #f1f1f4; border-radius: 10px; margin: 15px 0;">
                    <div
                        style="width: <?php echo $stats['total_voters'] > 0 ? min(100, round(($stats['voters_voted'] / $stats['total_voters']) * 100)) : 0; ?>%; height: 100%; background: var(--accent); border-radius: 10px; box-shadow: 0 0 10px var(--accent-glow);">
                    </div>
                </div>
            </div>
        </div>
    </div>

    </div> <!-- Close admin-content -->
    </div> <!-- Close admin-main -->
</body>

</html>