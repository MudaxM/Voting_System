<?php
require_once 'includes/config.php';

$election_status = getElectionStatus($pdo);
$results = getElectionResults($pdo);
$total_votes = 0;

// Calculate total votes
$sql = "SELECT COUNT(DISTINCT voter_id) as total_voters FROM votes";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$stats = $stmt->fetch();
$total_voters = $stats['total_voters'] ?? 0;

// Get total registered voters
$sql = "SELECT COUNT(*) as total_registered FROM users WHERE is_verified = 1 AND is_admin = 0";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$registered = $stmt->fetch();
$total_registered = $registered['total_registered'] ?? 0;

// Calculate turnout percentage
$turnout_percentage = $total_registered > 0 ? round(($total_voters / $total_registered) * 100, 2) : 0;

// Check if user has voted
$has_voted = false;
if (isLoggedIn()) {
    $has_voted = hasUserVoted($pdo, $_SESSION['user_id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Results - Student Union Voting System</title>
    <link rel="stylesheet" href="Assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .results-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        .results-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            text-align: center;
        }
        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            display: block;
        }
        .stat-card.success i { color: var(--success); }
        .stat-card.info i { color: var(--info); }
        .stat-card.warning i { color: var(--warning); }
        .stat-card.primary i { color: var(--primary); }
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .stat-label {
            color: var(--gray);
            font-size: 0.9rem;
        }
        .chart-container {
            background: white;
            padding: 2rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }
        .winner-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: var(--success);
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-left: 1rem;
        }
        .result-winner {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            border: 2px solid var(--success);
            border-radius: var(--radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .winner-info {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        .winner-photo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid white;
            box-shadow: var(--shadow);
        }
        .winner-details h3 {
            margin-bottom: 0.5rem;
            color: var(--dark);
        }
        .winner-details p {
            color: var(--gray);
            margin-bottom: 0.5rem;
        }
        .vote-percentage {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--success);
        }
        .results-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        .results-tab {
            padding: 12px 24px;
            background-color: white;
            border: 2px solid var(--gray-light);
            border-radius: var(--radius);
            cursor: pointer;
            font-weight: 600;
            transition: var(--transition);
            color: var(--dark);
        }
        .results-tab:hover {
            border-color: var(--primary);
            color: var(--primary);
        }
        .results-tab.active {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        .results-content {
            display: none;
            animation: fadeIn 0.5s ease;
        }
        .results-content.active {
            display: block;
        }
        .no-results {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--gray);
        }
        .no-results i {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }
        .download-options {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }
        .legend {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1rem;
            justify-content: center;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
        }
        .refresh-button {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--primary);
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: var(--shadow-lg);
            transition: var(--transition);
            z-index: 100;
        }
        .refresh-button:hover {
            transform: rotate(180deg);
            background: var(--primary-dark);
        }
        .share-results {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }
        .share-button {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: var(--transition);
        }
        .share-button.facebook { background-color: #1877f2; }
        .share-button.twitter { background-color: #1da1f2; }
        .share-button.whatsapp { background-color: #25d366; }
        .share-button:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow);
        }
        .result-status {
            text-align: center;
            padding: 1.5rem;
            border-radius: var(--radius);
            margin-bottom: 2rem;
            font-weight: 600;
        }
        .result-status.active {
            background-color: #d1fae5;
            color: #065f46;
            border: 2px solid #a7f3d0;
        }
        .result-status.ended {
            background-color: #fee2e2;
            color: #991b1b;
            border: 2px solid #fecaca;
        }
        .result-status.upcoming {
            background-color: #fef3c7;
            color: #92400e;
            border: 2px solid #fde68a;
        }
        .position-winners {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        .position-winner-card {
            background: white;
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
        }
        .position-winner-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 1.5rem;
            text-align: center;
        }
        .position-winner-body {
            padding: 1.5rem;
            text-align: center;
        }
        .position-winner-photo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 1rem;
            border: 3px solid var(--gray-light);
        }
        .position-winner-name {
            font-weight: 600;
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }
        .position-winner-votes {
            color: var(--success);
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        .position-winner-percentage {
            color: var(--gray);
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo">
                <i class="fas fa-vote-yea"></i>
                <span>StudentVote</span>
            </a>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <?php if (isLoggedIn()): ?>
                    <?php if ($has_voted || isAdmin()): ?>
                        <a href="results_public.php" class="active">Results</a>
                    <?php else: ?>
                        <a href="vote.php">Vote</a>
                    <?php endif; ?>
                    <a href="logout.php" class="btn-login">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn-login">Login</a>
                    <a href="register.php" class="btn-register">Register</a>
                <?php endif; ?>
            </div>
            <div class="menu-toggle">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </nav>

    <!-- Results Header -->
    <div class="results-header">
        <div class="container">
            <h1 style="color: white; margin-bottom: 1rem;">Election Results 2024</h1>
            <p style="font-size: 1.2rem; opacity: 0.9;">
                <?php if ($election_status['status'] === 'active'): ?>
                    <i class="fas fa-spinner fa-spin"></i> Live Results - Voting in Progress
                <?php elseif ($election_status['status'] === 'ended'): ?>
                    <i class="fas fa-flag-checkered"></i> Final Results - Election Concluded
                <?php else: ?>
                    <i class="fas fa-clock"></i> Results will be available after voting ends
                <?php endif; ?>
            </p>
            
            <div class="result-status <?php echo $election_status['status']; ?>">
                <i class="fas fa-<?php echo $election_status['status'] === 'active' ? 'play-circle' : 
                                        ($election_status['status'] === 'ended' ? 'stop-circle' : 'clock'); ?>"></i>
                <?php echo ucfirst($election_status['status']); ?> - <?php echo $election_status['message']; ?>
            </div>
        </div>
    </div>

    <div class="dashboard">
        <div class="container">
            <!-- Statistics -->
            <div class="results-stats">
                <div class="stat-card info">
                    <i class="fas fa-users"></i>
                    <div class="stat-number"><?php echo number_format($total_registered); ?></div>
                    <div class="stat-label">Registered Voters</div>
                </div>
                
                <div class="stat-card success">
                    <i class="fas fa-vote-yea"></i>
                    <div class="stat-number"><?php echo number_format($total_voters); ?></div>
                    <div class="stat-label">Total Votes Cast</div>
                </div>
                
                <div class="stat-card warning">
                    <i class="fas fa-percentage"></i>
                    <div class="stat-number"><?php echo $turnout_percentage; ?>%</div>
                    <div class="stat-label">Voter Turnout</div>
                </div>
                
                <div class="stat-card primary">
                    <i class="fas fa-user-tie"></i>
                    <div class="stat-number">
                        <?php 
                        $total_candidates = 0;
                        foreach ($results as $position) {
                            $total_candidates += count($position['candidates']);
                        }
                        echo $total_candidates;
                        ?>
                    </div>
                    <div class="stat-label">Candidates</div>
                </div>
            </div>

            <?php if ($election_status['status'] === 'ended' || $election_status['status'] === 'active'): ?>
            
            <!-- Results Tabs -->
            <div class="results-tabs">
                <button class="results-tab active" onclick="showTab('overview')">
                    <i class="fas fa-chart-pie"></i> Overview
                </button>
                <button class="results-tab" onclick="showTab('winners')">
                    <i class="fas fa-trophy"></i> Winners
                </button>
                <button class="results-tab" onclick="showTab('detailed')">
                    <i class="fas fa-list"></i> Detailed Results
                </button>
                <?php if ($election_status['status'] === 'ended'): ?>
                <button class="results-tab" onclick="showTab('charts')">
                    <i class="fas fa-chart-bar"></i> Charts
                </button>
                <?php endif; ?>
            </div>

            <!-- Overview Tab -->
            <div class="results-content active" id="overviewTab">
                <h2 style="margin-bottom: 1.5rem;">Election Overview</h2>
                
                <?php if (empty($results)): ?>
                <div class="no-results">
                    <i class="fas fa-chart-line"></i>
                    <h3>No Results Available Yet</h3>
                    <p>Results will be displayed here once voting begins.</p>
                </div>
                <?php else: ?>
                
                <!-- Overall Winner -->
                <?php
                $overall_winner = null;
                $max_votes = 0;
                
                foreach ($results as $position) {
                    foreach ($position['candidates'] as $candidate) {
                        if ($candidate['votes'] > $max_votes) {
                            $max_votes = $candidate['votes'];
                            $overall_winner = $candidate;
                            $overall_winner['position'] = $position['position'];
                        }
                    }
                }
                
                if ($overall_winner && $max_votes > 0):
                ?>
                <div class="result-winner">
                    <div class="winner-info">
                        <div class="winner-photo" style="background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; display: flex; align-items: center; justify-content: center; font-size: 2.5rem;">
                            <?php echo strtoupper(substr($overall_winner['candidate'], 0, 1)); ?>
                        </div>
                        <div class="winner-details">
                            <h3>
                                <?php echo htmlspecialchars($overall_winner['candidate']); ?>
                                <span class="winner-badge">
                                    <i class="fas fa-crown"></i> Top Performer
                                </span>
                            </h3>
                            <p>
                                <i class="fas fa-graduation-cap"></i> 
                                <?php echo htmlspecialchars($overall_winner['department']); ?> | 
                                <i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($overall_winner['position']); ?>
                            </p>
                            <div class="vote-percentage">
                                <?php echo number_format($overall_winner['votes']); ?> votes
                                (<?php echo $overall_winner['percentage']; ?>%)
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Position Winners Grid -->
                <div class="position-winners">
                    <?php foreach ($results as $position): 
                        if (!empty($position['candidates'])):
                            $winner = $position['candidates'][0]; // First candidate has most votes
                    ?>
                    <div class="position-winner-card">
                        <div class="position-winner-header">
                            <h3 style="margin: 0; color: white;"><?php echo htmlspecialchars($position['position']); ?></h3>
                        </div>
                        <div class="position-winner-body">
                            <div class="position-winner-photo" style="background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; display: flex; align-items: center; justify-content: center; font-size: 2rem;">
                                <?php echo strtoupper(substr($winner['candidate'], 0, 1)); ?>
                            </div>
                            <div class="position-winner-name">
                                <?php echo htmlspecialchars($winner['candidate']); ?>
                            </div>
                            <div class="position-winner-votes">
                                <?php echo number_format($winner['votes']); ?> votes
                            </div>
                            <div class="position-winner-percentage">
                                <?php echo $winner['percentage']; ?>% of votes
                            </div>
                        </div>
                    </div>
                    <?php endif; endforeach; ?>
                </div>
                
                <!-- Turnout Chart -->
                <div class="chart-container">
                    <h3 style="margin-bottom: 1.5rem;">Voter Turnout</h3>
                    <canvas id="turnoutChart" height="100"></canvas>
                    <div class="legend">
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: #4361ee;"></div>
                            <span>Voted: <?php echo number_format($total_voters); ?> (<?php echo $turnout_percentage; ?>%)</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: #64748b;"></div>
                            <span>Did Not Vote: <?php echo number_format($total_registered - $total_voters); ?> (<?php echo 100 - $turnout_percentage; ?>%)</span>
                        </div>
                    </div>
                </div>
                
                <?php endif; ?>
            </div>

            <!-- Winners Tab -->
            <div class="results-content" id="winnersTab">
                <h2 style="margin-bottom: 1.5rem;">Position Winners</h2>
                
                <?php if (empty($results)): ?>
                <div class="no-results">
                    <i class="fas fa-trophy"></i>
                    <h3>No Winners Declared Yet</h3>
                    <p>Winners will be announced after voting ends.</p>
                </div>
                <?php else: ?>
                
                <?php foreach ($results as $position): ?>
                <div class="result-position">
                    <div class="result-header">
                        <h3>
                            <?php echo htmlspecialchars($position['position']); ?>
                            <?php if (!empty($position['candidates'])): ?>
                            <span class="winner-badge">
                                <i class="fas fa-trophy"></i> Winner: <?php echo htmlspecialchars($position['candidates'][0]['candidate']); ?>
                            </span>
                            <?php endif; ?>
                        </h3>
                    </div>
                    
                    <div class="candidates-results">
                        <?php if (empty($position['candidates'])): ?>
                        <div class="no-results" style="padding: 2rem;">
                            <i class="fas fa-user-slash"></i>
                            <p>No candidates contested this position</p>
                        </div>
                        <?php else: ?>
                        
                        <?php foreach ($position['candidates'] as $index => $candidate): ?>
                        <div class="candidate-result <?php echo $index === 0 ? 'winner' : ''; ?>">
                            <div style="display: flex; align-items: center; gap: 1rem; min-width: 200px;">
                                <div style="font-weight: 600; color: var(--dark);">
                                    #<?php echo $index + 1; ?>
                                </div>
                                <div>
                                    <strong style="font-size: 1.1rem;"><?php echo htmlspecialchars($candidate['candidate']); ?></strong>
                                    <div style="color: var(--gray); font-size: 0.9rem;">
                                        <?php echo htmlspecialchars($candidate['department']); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="vote-bar">
                                <div class="vote-bar-container">
                                    <div class="vote-progress" style="width: <?php echo $candidate['percentage']; ?>%"></div>
                                </div>
                                <div class="vote-stats">
                                    <span><?php echo number_format($candidate['votes']); ?> votes</span>
                                    <span><?php echo $candidate['percentage']; ?>%</span>
                                </div>
                            </div>
                            
                            <?php if ($index === 0): ?>
                            <div class="winner-badge">
                                <i class="fas fa-trophy"></i> Winner
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php endif; ?>
            </div>

            <!-- Detailed Results Tab -->
            <div class="results-content" id="detailedTab">
                <h2 style="margin-bottom: 1.5rem;">Detailed Results</h2>
                
                <?php if (empty($results)): ?>
                <div class="no-results">
                    <i class="fas fa-file-alt"></i>
                    <h3>No Detailed Results Available</h3>
                    <p>Detailed results will be available after voting ends.</p>
                </div>
                <?php else: ?>
                
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Position</th>
                                <th>Candidate</th>
                                <th>Department</th>
                                <th>Votes</th>
                                <th>Percentage</th>
                                <th>Rank</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $position): ?>
                                <?php if (!empty($position['candidates'])): ?>
                                    <?php foreach ($position['candidates'] as $index => $candidate): ?>
                                    <tr class="<?php echo $index === 0 ? 'winner-row' : ''; ?>">
                                        <td><strong><?php echo htmlspecialchars($position['position']); ?></strong></td>
                                        <td>
                                            <?php echo htmlspecialchars($candidate['candidate']); ?>
                                            <?php if ($index === 0): ?>
                                            <span class="winner-badge" style="font-size: 0.8rem; padding: 2px 8px;">
                                                <i class="fas fa-trophy"></i> Winner
                                            </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($candidate['department']); ?></td>
                                        <td><?php echo number_format($candidate['votes']); ?></td>
                                        <td><?php echo $candidate['percentage']; ?>%</td>
                                        <td>
                                            <span class="status-badge <?php echo $index === 0 ? 'status-active' : 'status-inactive'; ?>">
                                                #<?php echo $index + 1; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($position['position']); ?></strong></td>
                                    <td colspan="5" style="text-align: center; color: var(--gray);">
                                        No candidates contested this position
                                    </td>
                                </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Download Options -->
                <div class="download-options">
                    <button class="btn btn-primary" onclick="downloadResults('pdf')">
                        <i class="fas fa-file-pdf"></i> Download PDF
                    </button>
                    <button class="btn btn-success" onclick="downloadResults('csv')">
                        <i class="fas fa-file-csv"></i> Download CSV
                    </button>
                    <button class="btn btn-outline" onclick="printResults()">
                        <i class="fas fa-print"></i> Print Results
                    </button>
                </div>
                
                <?php endif; ?>
            </div>

            <!-- Charts Tab -->
            <?php if ($election_status['status'] === 'ended'): ?>
            <div class="results-content" id="chartsTab">
                <h2 style="margin-bottom: 1.5rem;">Analytical Charts</h2>
                
                <div class="chart-container">
                    <h3>Votes Distribution by Position</h3>
                    <canvas id="votesByPositionChart" height="100"></canvas>
                </div>
                
                <div class="chart-container">
                    <h3>Top 10 Candidates by Votes</h3>
                    <canvas id="topCandidatesChart" height="100"></canvas>
                </div>
                
                <div class="chart-container">
                    <h3>Voter Turnout Timeline</h3>
                    <canvas id="turnoutTimelineChart" height="100"></canvas>
                </div>
            </div>
            <?php endif; ?>
            
            <?php else: ?>
            
            <!-- Election hasn't started yet -->
            <div class="no-results">
                <i class="fas fa-clock"></i>
                <h3>Results Not Available</h3>
                <p>Election results will be displayed here once voting <?php 
                    echo $election_status['status'] === 'upcoming' ? 'begins' : 'ends';
                ?>.</p>
                <p style="margin-top: 1rem; color: var(--primary);">
                    <strong>Voting Period:</strong> 
                    <?php echo date('F j, Y, g:i a', strtotime($election_status['start_date'])); ?> 
                    to 
                    <?php echo date('F j, Y, g:i a', strtotime($election_status['end_date'])); ?>
                </p>
                <a href="index.php" class="btn btn-primary" style="margin-top: 1.5rem;">
                    <i class="fas fa-home"></i> Return to Homepage
                </a>
            </div>
            
            <?php endif; ?>
            
            <!-- Share Results -->
            <?php if ($election_status['status'] === 'ended' && !empty($results)): ?>
            <div class="share-results">
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(SITE_URL . 'results_public.php'); ?>" 
                   target="_blank" class="share-button facebook">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="https://twitter.com/intent/tweet?text=Check%20out%20the%20Student%20Union%20Election%20Results%202024!&url=<?php echo urlencode(SITE_URL . 'results_public.php'); ?>" 
                   target="_blank" class="share-button twitter">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="https://wa.me/?text=Student%20Union%20Election%20Results%202024%20-%20<?php echo urlencode(SITE_URL . 'results_public.php'); ?>" 
                   target="_blank" class="share-button whatsapp">
                    <i class="fab fa-whatsapp"></i>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Refresh Button -->
    <?php if ($election_status['status'] === 'active'): ?>
    <div class="refresh-button" onclick="refreshResults()" title="Refresh Results">
        <i class="fas fa-sync-alt"></i>
    </div>
    <?php endif; ?>

    <script>
        // Tab switching
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.results-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Deactivate all tab buttons
            document.querySelectorAll('.results-tab').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + 'Tab').classList.add('active');
            
            // Activate selected button
            event.target.classList.add('active');
            
            // Update charts if needed
            if (tabName === 'charts') {
                setTimeout(renderCharts, 100);
            }
        }
        
        // Initialize Charts
        function renderCharts() {
            // Turnout Chart
            const turnoutCtx = document.getElementById('turnoutChart').getContext('2d');
            new Chart(turnoutCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Voted', 'Did Not Vote'],
                    datasets: [{
                        data: [<?php echo $total_voters; ?>, <?php echo $total_registered - $total_voters; ?>],
                        backgroundColor: ['#4361ee', '#64748b'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
            
            <?php if ($election_status['status'] === 'ended'): ?>
            // Votes by Position Chart
            const positionCtx = document.getElementById('votesByPositionChart').getContext('2d');
            const positions = [];
            const positionVotes = [];
            const positionColors = [];
            
            <?php foreach ($results as $position): 
                $totalPositionVotes = 0;
                foreach ($position['candidates'] as $candidate) {
                    $totalPositionVotes += $candidate['votes'];
                }
            ?>
            positions.push("<?php echo addslashes($position['position']); ?>");
            positionVotes.push(<?php echo $totalPositionVotes; ?>);
            positionColors.push(getRandomColor());
            <?php endforeach; ?>
            
            new Chart(positionCtx, {
                type: 'bar',
                data: {
                    labels: positions,
                    datasets: [{
                        label: 'Total Votes',
                        data: positionVotes,
                        backgroundColor: positionColors,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
            
            // Top Candidates Chart
            const topCandidatesCtx = document.getElementById('topCandidatesChart').getContext('2d');
            const topCandidates = [];
            const topVotes = [];
            
            <?php 
            $allCandidates = [];
            foreach ($results as $position) {
                foreach ($position['candidates'] as $candidate) {
                    $allCandidates[] = [
                        'name' => $candidate['candidate'] . ' (' . $position['position'] . ')',
                        'votes' => $candidate['votes']
                    ];
                }
            }
            
            // Sort by votes descending
            usort($allCandidates, function($a, $b) {
                return $b['votes'] - $a['votes'];
            });
            
            // Take top 10
            $top10 = array_slice($allCandidates, 0, 10);
            ?>
            
            <?php foreach ($top10 as $candidate): ?>
            topCandidates.push("<?php echo addslashes($candidate['name']); ?>");
            topVotes.push(<?php echo $candidate['votes']; ?>);
            <?php endforeach; ?>
            
            new Chart(topCandidatesCtx, {
                type: 'horizontalBar',
                data: {
                    labels: topCandidates,
                    datasets: [{
                        label: 'Votes',
                        data: topVotes,
                        backgroundColor: '#4361ee',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    indexAxis: 'y',
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
            
            // Turnout Timeline Chart (mock data - in real app, fetch from server)
            const timelineCtx = document.getElementById('turnoutTimelineChart').getContext('2d');
            new Chart(timelineCtx, {
                type: 'line',
                data: {
                    labels: ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5', 'Day 6', 'Day 7', 'Day 8', 'Day 9', 'Day 10'],
                    datasets: [{
                        label: 'Cumulative Votes',
                        data: [
                            <?php echo round($total_voters * 0.1); ?>,
                            <?php echo round($total_voters * 0.2); ?>,
                            <?php echo round($total_voters * 0.3); ?>,
                            <?php echo round($total_voters * 0.4); ?>,
                            <?php echo round($total_voters * 0.5); ?>,
                            <?php echo round($total_voters * 0.6); ?>,
                            <?php echo round($total_voters * 0.7); ?>,
                            <?php echo round($total_voters * 0.8); ?>,
                            <?php echo round($total_voters * 0.9); ?>,
                            <?php echo $total_voters; ?>
                        ],
                        borderColor: '#4361ee',
                        backgroundColor: 'rgba(67, 97, 238, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
            <?php endif; ?>
        }
        
        // Generate random color
        function getRandomColor() {
            const colors = [
                '#4361ee', '#3a0ca3', '#4cc9f0', '#7209b7', 
                '#f72585', '#4ade80', '#f59e0b', '#ef4444',
                '#8b5cf6', '#10b981', '#f43f5e', '#0ea5e9'
            ];
            return colors[Math.floor(Math.random() * colors.length)];
        }
        
        // Download results
        function downloadResults(format) {
            alert(`Downloading results in ${format.toUpperCase()} format...\n\nIn a production system, this would generate and download the file.`);
            // In production, this would make an AJAX call to generate the file
        }
        
        // Print results
        function printResults() {
            window.print();
        }
        
        // Refresh results
        function refreshResults() {
            const refreshBtn = document.querySelector('.refresh-button');
            refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            setTimeout(() => {
                location.reload();
            }, 1000);
        }
        
        // Auto-refresh for live results (every 30 seconds)
        <?php if ($election_status['status'] === 'active'): ?>
        setInterval(() => {
            if (!document.hidden) {
                fetch('api/get_live_results.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.updated) {
                            // Update statistics if changed
                            document.querySelectorAll('.stat-number')[1].textContent = 
                                data.total_voters.toLocaleString();
                            
                            // Update turnout percentage
                            const turnout = data.total_registered > 0 ? 
                                ((data.total_voters / data.total_registered) * 100).toFixed(2) : 0;
                            document.querySelectorAll('.stat-number')[2].textContent = turnout + '%';
                        }
                    })
                    .catch(error => console.log('Error fetching live results:', error));
            }
        }, 30000);
        <?php endif; ?>
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle
            document.querySelector('.menu-toggle').addEventListener('click', function() {
                document.querySelector('.nav-links').classList.toggle('active');
            });
            
            // Render charts on page load
            renderCharts();
            
            // Show active tab based on URL hash
            const hash = window.location.hash.substring(1);
            if (hash && ['overview', 'winners', 'detailed', 'charts'].includes(hash)) {
                showTab(hash);
            }
        });
        
        // Update URL hash when switching tabs
        function showTab(tabName) {
            // ... existing showTab code ...
            window.location.hash = tabName;
        }
    </script>
</body>
</html>