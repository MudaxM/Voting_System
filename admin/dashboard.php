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
        LIMIT 10";
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
$db_connections = $stmt->fetch()['Value'];

// Current admin
$admin = getCurrentUser($pdo);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard Voting_System</title>
    <link rel="stylesheet" href="../Assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .admin-dashboard {
            padding: 120px 20px 40px;
            min-height: 100vh;
            background-color: #f5f7ff;
        }

        .admin-sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            color: white;
            padding-top: 80px;
            z-index: 1000;
            transition: transform 0.3s ease;
        }

        .admin-sidebar.collapsed {
            transform: translateX(-250px);
        }

        .admin-main {
            margin-left: 250px;
            transition: margin-left 0.3s ease;
        }

        .admin-main.expanded {
            margin-left: 0;
        }

        .admin-nav {
            list-style: none;
            padding: 0;
        }

        .admin-nav-item {
            padding: 0;
        }

        .admin-nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 20px;
            color: #94a3b8;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }

        .admin-nav-link:hover,
        .admin-nav-link.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            border-left-color: var(--primary);
        }

        .admin-nav-link i {
            width: 20px;
            text-align: center;
        }

        .admin-nav-submenu {
            list-style: none;
            padding-left: 40px;
            background-color: rgba(0, 0, 0, 0.2);
            display: none;
        }

        .admin-nav-submenu.active {
            display: block;
        }

        .admin-nav-submenu .admin-nav-link {
            padding: 10px 20px;
            font-size: 0.9rem;
        }

        .admin-header {
            background: white;
            padding: 1rem 2rem;
            box-shadow: var(--shadow);
            position: fixed;
            top: 0;
            right: 0;
            left: 250px;
            z-index: 999;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: left 0.3s ease;
        }

        .admin-header.expanded {
            left: 0;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .sidebar-toggle {
            background: none;
            border: none;
            color: var(--dark);
            font-size: 1.2rem;
            cursor: pointer;
            padding: 8px;
            border-radius: var(--radius);
            transition: var(--transition);
        }

        .sidebar-toggle:hover {
            background-color: var(--gray-light);
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .admin-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .admin-name {
            font-weight: 600;
            color: var(--dark);
        }

        .admin-role {
            font-size: 0.8rem;
            color: var(--gray);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 1.5rem;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }

        .stat-icon.voters {
            background-color: #dbeafe;
            color: var(--info);
        }

        .stat-icon.positions {
            background-color: #f3e8ff;
            color: #8b5cf6;
        }

        .stat-icon.candidates {
            background-color: #fef3c7;
            color: var(--warning);
        }

        .stat-icon.votes {
            background-color: #d1fae5;
            color: var(--success);
        }

        .stat-content h3 {
            font-size: 2rem;
            margin-bottom: 5px;
            color: var(--dark);
        }

        .stat-content p {
            color: var(--gray);
            margin: 0;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        .dashboard-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }

        .dashboard-card h3 {
            color: var(--primary-dark);
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--gray-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid var(--gray-light);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .activity-icon.login {
            background-color: #dbeafe;
            color: var(--info);
        }

        .activity-icon.vote {
            background-color: #d1fae5;
            color: var(--success);
        }

        .activity-icon.register {
            background-color: #fef3c7;
            color: var(--warning);
        }

        .activity-icon.admin {
            background-color: #f3e8ff;
            color: #8b5cf6;
        }

        .activity-details {
            flex-grow: 1;
        }

        .activity-user {
            font-weight: 600;
            color: var(--dark);
        }

        .activity-action {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .activity-time {
            font-size: 0.8rem;
            color: var(--gray);
        }

        .status-badge.status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .election-status-card {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 2rem;
            border-radius: var(--radius);
            margin-bottom: 2rem;
        }

        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .quick-action {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1.5rem;
            background: white;
            border-radius: var(--radius);
            text-decoration: none;
            color: var(--dark);
            transition: var(--transition);
            text-align: center;
            border: 2px solid var(--gray-light);
        }

        .quick-action:hover {
            transform: translateY(-5px);
            border-color: var(--primary);
            box-shadow: var(--shadow);
        }

        .quick-action i {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--primary);
        }

        .system-health {
            background-color: #f8fafc;
            padding: 1.5rem;
            border-radius: var(--radius);
            margin-top: 2rem;
        }

        .health-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid var(--gray-light);
        }

        .health-item:last-child {
            border-bottom: none;
        }

        .health-status {
            font-weight: 600;
        }

        .health-status.good {
            color: var(--success);
        }

        .health-status.warning {
            color: var(--warning);
        }

        .health-status.critical {
            color: var(--danger);
        }

        @media (max-width: 1200px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 992px) {
            .admin-sidebar {
                transform: translateX(-250px);
            }

            .admin-sidebar.active {
                transform: translateX(0);
            }

            .admin-main {
                margin-left: 0;
            }

            .admin-header {
                left: 0;
            }
        }
    </style>
</head>

<body>
    <?php include 'includes/admin_header.php'; ?>

    <!-- Dashboard Content -->
    <div class="admin-dashboard">
        <div class="container">
            <!-- Election Status -->
            <div class="election-status-card">
                <div class="status-indicator">
                    <i
                        class="fas fa-<?php echo $election_status['status'] === 'active' ? 'play-circle' :
                            ($election_status['status'] === 'ended' ? 'stop-circle' : 'clock'); ?>"></i>
                    <?php echo strtoupper($election_status['status']); ?>
                </div>
                <h2 style="color: white; margin-bottom: 0.5rem;">Student Union Elections 2024</h2>
                <p style="opacity: 0.9; margin-bottom: 1rem;"><?php echo $election_status['message']; ?></p>
                <div style="display: flex; gap: 2rem; margin-top: 1.5rem;">
                    <div>
                        <div style="font-size: 0.9rem; opacity: 0.8;">Start Date</div>
                        <div style="font-size: 1.1rem; font-weight: 600;">
                            <?php echo date('F j, Y, g:i a', strtotime($election_status['start_date'])); ?>
                        </div>
                    </div>
                    <div>
                        <div style="font-size: 0.9rem; opacity: 0.8;">End Date</div>
                        <div style="font-size: 1.1rem; font-weight: 600;">
                            <?php echo date('F j, Y, g:i a', strtotime($election_status['end_date'])); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon voters">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['total_voters']); ?></h3>
                        <p>Total Registered Voters</p>
                        <small style="color: var(--success);">
                            <?php echo number_format($stats['verified_voters']); ?> verified
                        </small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon positions">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['total_positions']); ?></h3>
                        <p>Active Positions</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon candidates">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['total_candidates']); ?></h3>
                        <p>Candidates</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon votes">
                        <i class="fas fa-vote-yea"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['total_votes']); ?></h3>
                        <p>Total Votes Cast</p>
                        <?php if ($stats['total_voters'] > 0): ?>
                            <small style="color: var(--info);">
                                <?php echo round(($stats['voters_voted'] / $stats['total_voters']) * 100, 2); ?>% turnout
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="dashboard-card">
                <h3>Quick Actions</h3>
                <div class="quick-actions">
                    <a href="manage_voters.php" class="quick-action">
                        <i class="fas fa-users"></i>
                        <span>Manage Voters</span>
                    </a>
                    <a href="manage_candidates.php?action=add" class="quick-action">
                        <i class="fas fa-user-tie"></i>
                        <span>Add Candidate</span>
                    </a>
                    <a href="manage_positions.php?action=add" class="quick-action">
                        <i class="fas fa-plus-circle"></i>
                        <span>Create Position</span>
                    </a>
                    <a href="results.php" class="quick-action">
                        <i class="fas fa-chart-line"></i>
                        <span>View Results</span>
                    </a>
                    <a href="settings.php" class="quick-action">
                        <i class="fas fa-cog"></i>
                        <span>System Settings</span>
                    </a>
                    <a href="../logout.php" class="quick-action" style="color: var(--danger);">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>

            <div class="dashboard-grid">
                <!-- Left Column -->
                <div>
                    <!-- Recent Activity -->
                    <div class="dashboard-card">
                        <h3>
                            Recent Activity
                            <a href="activity_logs.php" class="btn btn-outline" style="font-size: 0.9rem;">
                                View All
                            </a>
                        </h3>
                        <div class="activity-list">
                            <?php if (empty($recent_activity)): ?>
                                <p style="text-align: center; color: var(--gray); padding: 2rem;">No recent activity</p>
                            <?php else: ?>
                                <?php foreach ($recent_activity as $activity):
                                    $icon_class = '';
                                    $icon = '';
                                    if (strpos($activity['action'], 'login') !== false) {
                                        $icon_class = 'login';
                                        $icon = 'fas fa-sign-in-alt';
                                    } elseif (strpos($activity['action'], 'vote') !== false) {
                                        $icon_class = 'vote';
                                        $icon = 'fas fa-vote-yea';
                                    } elseif (strpos($activity['action'], 'register') !== false) {
                                        $icon_class = 'register';
                                        $icon = 'fas fa-user-plus';
                                    } else {
                                        $icon_class = 'admin';
                                        $icon = 'fas fa-cog';
                                    }
                                    ?>
                                    <div class="activity-item">
                                        <div class="activity-icon <?php echo $icon_class; ?>">
                                            <i class="<?php echo $icon; ?>"></i>
                                        </div>
                                        <div class="activity-details">
                                            <div class="activity-user">
                                                <?php echo htmlspecialchars($activity['full_name']); ?>
                                                <small>(<?php echo htmlspecialchars($activity['student_id']); ?>)</small>
                                            </div>
                                            <div class="activity-action">
                                                <?php echo htmlspecialchars($activity['action']); ?>
                                                <?php if (!empty($activity['details'])): ?>
                                                    <br><small><?php echo htmlspecialchars($activity['details']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="activity-time">
                                            <?php echo time_ago($activity['created_at']); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Recent Registrations -->
                    <div class="dashboard-card">
                        <h3>
                            Recent Registrations
                            <a href="manage_voters.php" class="btn btn-outline" style="font-size: 0.9rem;">
                                Manage All
                            </a>
                        </h3>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Name</th>
                                        <th>Department</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_registrations as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['student_id']); ?></td>
                                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['department']); ?></td>
                                            <td>
                                                <?php if ($user['is_verified']): ?>
                                                    <span class="status-badge status-active">Verified</span>
                                                <?php else: ?>
                                                    <span class="status-badge status-inactive">Pending</span>
                                                <?php endif; ?>
                                                <?php if ($user['has_voted']): ?>
                                                    <span class="status-badge status-active"
                                                        style="margin-left: 5px;">Voted</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="manage_voters.php"
                                                    class="btn-action btn-view" title="Manage Voters">
                                                    <i class="fas fa-users"></i>
                                                </a>
                                                <?php if (!$user['is_verified']): ?>
                                                <a href="manage_voters.php?verify=1&id=<?php echo $user['id']; ?>"
                                                    class="btn-action btn-view" title="Verify" style="background-color: var(--success);">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div>
                    <!-- System Health -->
                    <div class="dashboard-card">
                        <h3>System Health</h3>
                        <div class="system-health">
                            <div class="health-item">
                                <span>Database Connections</span>
                                <span class="health-status good"><?php echo $db_connections; ?> active</span>
                            </div>
                            <div class="health-item">
                                <span>Server Uptime</span>
                                <span class="health-status good">99.9%</span>
                            </div>
                            <div class="health-item">
                                <span>Storage</span>
                                <span class="health-status warning">75% used</span>
                            </div>
                            <div class="health-item">
                                <span>Last Backup</span>
                                <span class="health-status good">Today, 02:00 AM</span>
                            </div>
                            <div class="health-item">
                                <span>Security</span>
                                <span class="health-status good">All systems secure</span>
                            </div>
                        </div>
                        <button class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
                            <i class="fas fa-sync-alt"></i> Run System Check
                        </button>
                    </div>

                    <!-- Election Timeline -->
                    <div class="dashboard-card">
                        <h3>Election Timeline</h3>
                        <div style="position: relative; padding-left: 20px;">
                            <div
                                style="position: absolute; left: 8px; top: 0; bottom: 0; width: 2px; background-color: var(--primary);">
                            </div>

                            <?php
                            $timeline_items = [
                                [
                                    'date' => 'Feb 15-28, 2024',
                                    'title' => 'Candidate Registration',
                                    'status' => 'completed',
                                    'icon' => 'fas fa-user-check'
                                ],
                                [
                                    'date' => 'Mar 1-10, 2024',
                                    'title' => 'Voting Period',
                                    'status' => $election_status['status'] === 'active' ? 'current' :
                                        ($election_status['status'] === 'ended' ? 'completed' : 'upcoming'),
                                    'icon' => 'fas fa-vote-yea'
                                ],
                                [
                                    'date' => 'Mar 11, 2024',
                                    'title' => 'Results Announcement',
                                    'status' => $election_status['status'] === 'ended' ? 'current' : 'upcoming',
                                    'icon' => 'fas fa-bullhorn'
                                ]
                            ];

                            foreach ($timeline_items as $item):
                                ?>
                                <div style="position: relative; margin-bottom: 1.5rem;">
                                    <div
                                        style="position: absolute; left: -16px; top: 0; width: 10px; height: 10px; border-radius: 50%; 
                                          background-color: <?php echo $item['status'] === 'completed' ? 'var(--success)' :
                                              ($item['status'] === 'current' ? 'var(--primary)' : 'var(--gray-light)'); ?>;">
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
                                        <i class="<?php echo $item['icon']; ?>" style="color: var(--primary);"></i>
                                        <strong><?php echo $item['title']; ?></strong>
                                        <?php if ($item['status'] === 'current'): ?>
                                            <span class="status-badge status-active" style="font-size: 0.7rem;">NOW</span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="color: var(--gray); font-size: 0.9rem;"><?php echo $item['date']; ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Important Notices -->
                    <div class="dashboard-card">
                        <h3>Important Notices</h3>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <div>
                                <strong>System Backup</strong>
                                <p>Daily backup scheduled at 02:00 AM</p>
                            </div>
                        </div>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div>
                                <strong>Security Alert</strong>
                                <p>5 failed login attempts detected today</p>
                            </div>
                        </div>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <div>
                                <strong>All Systems Operational</strong>
                                <p>No critical issues reported</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <?php
    // Helper function for time ago
    function time_ago($datetime)
    {
        $time = strtotime($datetime);
        $now = time();
        $diff = $now - $time;

        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } else {
            return date('M j, Y', $time);
        }
    }
    ?>

    <script>
        // Sidebar toggle
        const sidebarToggle = document.getElementById('sidebarToggle');
        const adminSidebar = document.getElementById('adminSidebar');
        const adminMain = document.getElementById('adminMain');
        const adminHeader = document.getElementById('adminHeader');

        sidebarToggle.addEventListener('click', function () {
            adminSidebar.classList.toggle('active');
            adminMain.classList.toggle('expanded');
            adminHeader.classList.toggle('expanded');
        });

        // Auto-collapse sidebar on mobile
        function checkScreenSize() {
            if (window.innerWidth <= 992) {
                adminSidebar.classList.remove('active');
                adminMain.classList.add('expanded');
                adminHeader.classList.add('expanded');
            } else {
                adminSidebar.classList.add('active');
                adminMain.classList.remove('expanded');
                adminHeader.classList.remove('expanded');
            }
        }

        window.addEventListener('resize', checkScreenSize);
        checkScreenSize();

        // Initialize charts
        document.addEventListener('DOMContentLoaded', function () {
            // You can add Chart.js visualizations here
            // Example: Voting statistics chart
        });

        // Auto-refresh dashboard every 60 seconds
        setInterval(() => {
            if (!document.hidden) {
                fetch('api/dashboard_stats.php')
                    .then(response => response.json())
                    .then(data => {
                        // Update statistics
                        document.querySelectorAll('.stat-content h3')[0].textContent =
                            data.total_voters.toLocaleString();
                        document.querySelectorAll('.stat-content h3')[3].textContent =
                            data.total_votes.toLocaleString();
                    })
                    .catch(error => console.error('Error updating dashboard:', error));
            }
        }, 60000);

        // Log admin activity
        window.addEventListener('beforeunload', function () {
            if (navigator.sendBeacon) {
                navigator.sendBeacon('api/log_admin_activity.php', JSON.stringify({
                    action: 'admin_dashboard_view',
                    duration: Math.floor((Date.now() - window.performance.timing.navigationStart) / 1000)
                }));
            }
        });
    </script>
</body>

</html>