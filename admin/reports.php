<?php
require_once '../includes/config.php';
requireAdmin();

// Dummy reports data for visualization
$available_reports = [
    ['id' => 'turnout', 'name' => 'Voter Turnout Analysis', 'desc' => 'Detailed breakdown of voting participation across departments.', 'icon' => 'fa-users'],
    ['id' => 'results_final', 'name' => 'Final Election Tally', 'desc' => 'Official certified results for all positions and candidates.', 'icon' => 'fa-file-signature'],
    ['id' => 'audit_comprehensive', 'name' => 'Comprehensive Security Audit', 'desc' => 'Traceability report for every vote cast with hash verification.', 'icon' => 'fa-shield-check'],
    ['id' => 'demographics', 'name' => 'Voter Demographic Insight', 'desc' => 'Statistical distribution of voters by academic year and faculty.', 'icon' => 'fa-chart-pie']
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics & Reports | Admin Panel</title>
    <link rel="stylesheet" href="../Assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="admin-body">
    <?php include 'includes/admin_header.php'; ?>

    <div class="dashboard-header" style="margin-bottom: 30px;">
        <div class="user-info">
            <p>Generate, export and verify election intelligence documentation</p>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px;">
        <?php foreach ($available_reports as $report): ?>
            <div class="dashboard-card" style="display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div
                        style="width: 50px; height: 50px; border-radius: 12px; background: #f3f6f9; color: var(--accent); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 20px;">
                        <i class="fas <?php echo $report['icon']; ?>"></i>
                    </div>
                    <h3 style="margin: 0 0 10px 0; font-size: 1.15rem;"><?php echo $report['name']; ?></h3>
                    <p style="font-size: 0.85rem; color: #7e8299; line-height: 1.5; margin-bottom: 20px;">
                        <?php echo $report['desc']; ?>
                    </p>
                </div>
                <div
                    style="border-top: 1px solid #f1f1f4; padding-top: 20px; display: flex; justify-content: space-between; align-items: center;">
                    <span
                        style="font-size: 0.7rem; font-weight: 800; color: #ffa800; background: #fff4de; padding: 4px 10px; border-radius: 6px; text-transform: uppercase; letter-spacing: 0.5px;">Scheduled
                        v2.5</span>
                    <button class="btn btn-outline" style="font-size: 0.8rem; padding: 8px 15px;" disabled>
                        <i class="fas fa-download"></i> Export PDF
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="dashboard-card"
        style="margin-top: 40px; background: #fdfdfd; border: 1px dashed #ddd; text-align: center; padding: 40px;">
        <i class="fas fa-tools" style="font-size: 2.5rem; color: #ddd; margin-bottom: 15px;"></i>
        <h3 style="margin: 0; color: #7e8299;">Custom Report Engine</h3>
        <p style="color: #b5b5c3; font-size: 0.9rem;">Need a specific data extract? Contact system developers for custom
            SQL reporting.</p>
    </div>

    </div> <!-- Close admin-content -->
    </div> <!-- Close admin-main -->
</body>

</html>