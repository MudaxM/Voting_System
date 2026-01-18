<?php
// Get current admin user if not already set
if (!isset($admin)) {
    $admin = getCurrentUser($pdo);
}
?>

<!-- Admin Sidebar -->
<nav class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-header" style="padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.1);">
        <div class="admin-logo" style="display: flex; align-items: center; gap: 10px; color: white;">
            <i class="fas fa-shield-alt" style="font-size: 1.5rem;"></i>
            <h3 style="margin: 0; font-size: 1.2rem;">Admin Panel</h3>
        </div>
    </div>

    <ul class="admin-nav">
        <li class="admin-nav-item">
            <a href="dashboard.php"
                class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="manage_voters.php"
                class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_voters.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                Manage Voters
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="manage_candidates.php"
                class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_candidates.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-tie"></i>
                Manage Candidates
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="manage_positions.php"
                class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_positions.php' ? 'active' : ''; ?>">
                <i class="fas fa-briefcase"></i>
                Manage Positions
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="results.php"
                class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'results.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i>
                Election Results
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="settings.php"
                class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i>
                Settings
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="reports.php"
                class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                <i class="fas fa-file-alt"></i>
                Reports
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="activity_logs.php"
                class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'activity_logs.php' ? 'active' : ''; ?>">
                <i class="fas fa-history"></i>
                Activity Logs
            </a>
        </li>
        <li class="admin-nav-item" style="margin-top: 2rem;">
            <a href="../logout.php" class="admin-nav-link" style="color: #fca5a5;">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </li>
    </ul>
</nav>

<!-- Main Content -->
<div class="admin-main" id="adminMain">
    <!-- Header -->
    <header class="admin-header" id="adminHeader">
        <div class="header-left">
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <h1 style="margin: 0; font-size: 1.5rem;">
                <?php
                $current_page = basename($_SERVER['PHP_SELF']);
                switch ($current_page) {
                    case 'dashboard.php':
                        echo 'Admin Dashboard';
                        break;
                    case 'manage_voters.php':
                        echo 'Manage Voters';
                        break;
                    case 'manage_candidates.php':
                        echo 'Manage Candidates';
                        break;
                    case 'manage_positions.php':
                        echo 'Manage Positions';
                        break;
                    case 'results.php':
                        echo 'Election Results';
                        break;
                    default:
                        echo 'Admin Panel';
                }
                ?>
            </h1>
        </div>

        <div class="admin-info">
            <div style="text-align: right;">
                <div class="admin-name">
                    <?php echo htmlspecialchars($admin['full_name'] ?? 'Admin'); ?>
                </div>
                <div class="admin-role">System Administrator</div>
            </div>
            <div class="admin-avatar">
                <?php echo strtoupper(substr($admin['full_name'] ?? 'A', 0, 1)); ?>
            </div>
        </div>
    </header>

    <script>
        // Sidebar Toggle
        document.getElementById('sidebarToggle').addEventListener('click', function () {
            document.getElementById('adminSidebar').classList.toggle('collapsed');
            document.getElementById('adminMain').classList.toggle('expanded');
            document.getElementById('adminHeader').classList.toggle('expanded');
        });
    </script>