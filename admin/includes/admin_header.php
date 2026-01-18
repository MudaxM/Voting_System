<!-- Link to modern Admin CSS -->
<link rel="stylesheet" href="../Assets/css/admin.css">

<?php
// Get current admin user if not already set
if (!isset($admin)) {
    $admin = getCurrentUser($pdo);
}
?>

<!-- Admin Sidebar -->
<nav class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-header">
        <div class="admin-logo">
            <i class="fas fa-shield-alt"></i>
            <h3>Admin Portal</h3>
        </div>
    </div>

    <ul class="admin-nav">
        <li class="admin-nav-item">
            <a href="dashboard.php"
                class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="manage_voters.php"
                class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_voters.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-friends"></i>
                <span>Voter Directory</span>
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="manage_candidates.php"
                class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_candidates.php' ? 'active' : ''; ?>">
                <i class="fas fa-id-card"></i>
                <span>Candidates</span>
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="manage_positions.php"
                class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_positions.php' ? 'active' : ''; ?>">
                <i class="fas fa-briefcase"></i>
                <span>Positions</span>
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="results.php"
                class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'results.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-pie"></i>
                <span>Live Results</span>
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="settings.php"
                class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-sliders-h"></i>
                <span>System Settings</span>
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="activity_logs.php"
                class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'activity_logs.php' ? 'active' : ''; ?>">
                <i class="fas fa-list-ul"></i>
                <span>Audit Logs</span>
            </a>
        </li>

        <li class="admin-nav-item" style="margin-top: auto; padding-top: 20px;">
            <a href="../logout.php" class="admin-nav-link" style="color: #f64e60;">
                <i class="fas fa-power-off"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <div class="system-status">
            <div><span class="status-dot dot-online"></span> System Online</div>
            <div style="opacity: 0.6; font-size: 0.7rem;">v2.1.0-stable</div>
        </div>
    </div>
</nav>

<!-- Main Content Wrapper Area -->
<div class="admin-main" id="adminMain">
    <!-- Top Header Navigation -->
    <header class="admin-header" id="adminHeader">
        <div class="header-left" style="display: flex; align-items: center; gap: 20px;">
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="fas fa-align-left"></i>
            </button>
            <div class="page-title">
                <h2 style="margin: 0; font-size: 1.25rem; color: #181c32; font-weight: 700;">
                    <?php
                    $current_page = basename($_SERVER['PHP_SELF']);
                    switch ($current_page) {
                        case 'dashboard.php':
                            echo 'Dashboard Overview';
                            break;
                        case 'manage_voters.php':
                            echo 'Voter Directory';
                            break;
                        case 'manage_candidates.php':
                            echo 'Candidates Registry';
                            break;
                        case 'manage_positions.php':
                            echo 'Election Positions';
                            break;
                        case 'results.php':
                            echo 'Live Election Count';
                            break;
                        case 'settings.php':
                            echo 'Global Settings';
                            break;
                        case 'activity_logs.php':
                            echo 'System Audit Logs';
                            break;
                        default:
                            echo 'Admin Control Panel';
                    }
                    ?>
                </h2>
            </div>
        </div>

        <div class="admin-info">
            <div class="admin-details">
                <span class="admin-name"><?php echo htmlspecialchars($admin['full_name'] ?? 'Administrator'); ?></span>
                <span class="admin-role">Super Admin</span>
            </div>
            <div class="admin-avatar">
                <?php echo strtoupper(substr($admin['full_name'] ?? 'A', 0, 1)); ?>
            </div>
        </div>
    </header>

    <!-- Content Entrance Area -->
    <div class="admin-content">

        <script>
            // Sidebar Toggle with persistence
            const sidebarToggle = document.getElementById('sidebarToggle');
            const adminSidebar = document.getElementById('adminSidebar');
            const adminMain = document.getElementById('adminMain');
            const adminHeader = document.getElementById('adminHeader');

            // Check initial state
            const isCollapsedSaved = localStorage.getItem('adminSidebarCollapsed') === 'true';
            if (isCollapsedSaved) {
                adminSidebar.classList.add('collapsed');
                adminMain.classList.add('expanded');
                adminHeader.classList.add('expanded');
            }

            sidebarToggle.addEventListener('click', function () {
                const collapsed = adminSidebar.classList.toggle('collapsed');
                adminMain.classList.toggle('expanded');
                adminHeader.classList.toggle('expanded');
                localStorage.setItem('adminSidebarCollapsed', collapsed);
            });
        </script>