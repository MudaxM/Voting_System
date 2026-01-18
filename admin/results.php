<?php
require_once '../includes/config.php';
requireAdmin();

// Get election results
$results = getElectionResults($pdo);
$election_status = getElectionStatus($pdo);

// Get statistics
$sql = "SELECT 
            (SELECT COUNT(*) FROM users WHERE is_admin = 0) as total_voters,
            (SELECT COUNT(DISTINCT voter_id) FROM votes) as voters_voted,
            (SELECT COUNT(*) FROM candidates WHERE is_active = 1) as total_candidates,
            (SELECT COUNT(*) FROM votes) as total_votes";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$stats = $stmt->fetch();

$turnout_percentage = $stats['total_voters'] > 0 ? round(($stats['voters_voted'] / $stats['total_voters']) * 100, 2) : 0;

// Department breakdown
$dept_sql = "SELECT department, COUNT(*) as count FROM users WHERE is_admin = 0 GROUP BY department";
$dept_stats = $pdo->query($dept_sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Results | Admin Panel</title>
    <link rel="stylesheet" href="../Assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="admin-body">
    <?php include 'includes/admin_header.php'; ?>

    <div class="dashboard-header"
        style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
        <div class="user-info">
            <p>Real-time analytics and candidate standing for ongoing ballots</p>
        </div>
        <div class="user-actions">
            <button onclick="window.print()" class="btn btn-outline" style="background: white;">
                <i class="fas fa-print"></i> Generate Report
            </button>
        </div>
    </div>

    <!-- Quick Stats for Results -->
    <div
        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div class="stat-widget">
            <div class="stat-info">
                <h2><?php echo number_format($stats['total_votes']); ?></h2>
                <p>Total Ballots Cast</p>
            </div>
        </div>
        <div class="stat-widget">
            <div class="stat-info">
                <h2><?php echo $turnout_percentage; ?>%</h2>
                <p>Global Participation</p>
            </div>
        </div>
        <div class="stat-widget">
            <div class="stat-info">
                <h2><?php echo $stats['voters_voted']; ?>/<?php echo $stats['total_voters']; ?></h2>
                <p>Voters Attendance</p>
            </div>
        </div>
    </div>

    <!-- Results Breakdown -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 25px;">
        <div>
            <?php foreach ($results as $pos_id => $pos_data): ?>
                <?php
                $position_title = $pos_data['position'];
                $candidates = $pos_data['candidates'];
                ?>
                <div class="dashboard-card" style="margin-bottom: 25px;">
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f1f4; padding-bottom: 15px; margin-bottom: 20px;">
                        <h3 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-poll" style="color: var(--accent);"></i>
                            <?php echo htmlspecialchars($position_title); ?>
                        </h3>
                        <span
                            style="font-size: 0.75rem; font-weight: 700; color: #7e8299; background: #f3f6f9; padding: 4px 10px; border-radius: 6px;">
                            <?php echo count($candidates); ?> Candidates
                        </span>
                    </div>

                    <?php
                    $max_votes = 0;
                    foreach ($candidates as $c) {
                        if ($c['votes'] > $max_votes) {
                            $max_votes = $c['votes'];
                        }
                    }
                    ?>

                    <div style="display: flex; flex-direction: column; gap: 20px;">
                        <?php foreach ($candidates as $index => $c): ?>
                            <?php
                            $votes_sum = array_sum(array_column($candidates, 'votes'));
                            $pct = ($votes_sum > 0) ? round(($c['votes'] / $votes_sum) * 100, 1) : 0;
                            $is_winner = ($max_votes > 0 && $c['votes'] === $max_votes);
                            ?>
                            <div style="position: relative;">
                                <div
                                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <img src="../uploads/candidates/<?php echo $c['photo']; ?>"
                                            style="width: 40px; height: 40px; border-radius: 8px; object-fit: cover;">
                                        <div>
                                            <div style="font-weight: 700; color: #181c32; font-size: 0.95rem;">
                                                <?php echo htmlspecialchars($c['candidate']); ?>
                                                <?php if ($is_winner && $c['votes'] > 0): ?>
                                                    <i class="fas fa-crown"
                                                        style="color: #ffa800; margin-left: 5px; font-size: 0.8rem;"
                                                        title="Current Leader"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div style="font-size: 0.75rem; color: #7e8299;">
                                                <?php echo htmlspecialchars($c['department']); ?></div>
                                        </div>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-weight: 800; color: #181c32;"><?php echo number_format($c['votes']); ?>
                                            votes</div>
                                        <div style="font-size: 0.75rem; color: var(--accent); font-weight: 700;">
                                            <?php echo $pct; ?>%</div>
                                    </div>
                                </div>
                                <div style="width: 100%; height: 8px; background: #f3f6f9; border-radius: 10px;">
                                    <div
                                        style="width: <?php echo $pct; ?>%; height: 100%; background: <?php echo $is_winner ? 'var(--accent)' : '#b5b5c3'; ?>; border-radius: 10px; transition: width 1s ease;">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div>
            <div class="dashboard-card">
                <h3><i class="fas fa-chart-pie" style="color: #8950fc;"></i> Attendance Data</h3>
                <canvas id="participationChart" height="300"></canvas>
            </div>

            <div class="dashboard-card">
                <h3><i class="fas fa-university" style="color: #3699ff;"></i> Faculty Engagement</h3>
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <?php foreach ($dept_stats as $ds): ?>
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; border-bottom: 1px solid #f1f1f4;">
                            <span style="font-size: 0.85rem; color: #7e8299;"><?php echo $ds['department']; ?></span>
                            <span style="font-weight: 700; color: #181c32; font-size: 0.9rem;"><?php echo $ds['count']; ?>
                                Voters</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('participationChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Voted', 'Abstained'],
                datasets: [{
                    data: [<?php echo $stats['voters_voted']; ?>, <?php echo $stats['total_voters'] - $stats['voters_voted']; ?>],
                    backgroundColor: ['#4361ee', '#f3f6f9'],
                    borderWidth: 0
                }]
            },
            options: {
                cutout: '80%',
                plugins: { legend: { position: 'bottom' } }
            }
        });
    </script>

    </div> <!-- Close admin-content -->
    </div> <!-- Close admin-main -->
</body>

</html>