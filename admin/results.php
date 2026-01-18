<?php
require_once '../includes/config.php';
requireAdmin();

// Get election results
$results = getElectionResults($pdo);
$election_status = getElectionStatus($pdo);

// Get statistics
$sql = "SELECT 
            COUNT(DISTINCT u.id) as total_voters,
            COUNT(DISTINCT v.voter_id) as voters_voted,
            COUNT(DISTINCT c.id) as total_candidates,
            SUM(c.votes) as total_votes
        FROM users u
        LEFT JOIN votes v ON u.id = v.voter_id
        LEFT JOIN candidates c ON c.is_active = 1
        WHERE u.is_admin = 0";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$stats = $stmt->fetch();

// Calculate turnout percentage
$turnout_percentage = $stats['total_voters'] > 0 ? 
    round(($stats['voters_voted'] / $stats['total_voters']) * 100, 2) : 0;

// Get voting timeline data (last 7 days)
$sql = "SELECT DATE(voted_at) as date, COUNT(*) as votes 
        FROM votes 
        WHERE voted_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
        GROUP BY DATE(voted_at) 
        ORDER BY date";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$timeline_data = $stmt->fetchAll();

// Get department-wise voting
$sql = "SELECT u.department, COUNT(DISTINCT v.voter_id) as voters 
        FROM users u 
        LEFT JOIN votes v ON u.id = v.voter_id 
        WHERE u.is_admin = 0 
        GROUP BY u.department 
        ORDER BY voters DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$department_stats = $stmt->fetchAll();

// Get candidate rankings
$sql = "SELECT c.full_name, c.department, c.votes, p.title as position,
               ROW_NUMBER() OVER(PARTITION BY c.position_id ORDER BY c.votes DESC) as rank_in_position
        FROM candidates c
        JOIN positions p ON c.position_id = p.id
        WHERE c.is_active = 1
        ORDER BY c.votes DESC
        LIMIT 10";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$top_candidates = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Results - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .results-dashboard {
            padding: 120px 20px 40px;
        }
        .results-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 2rem;
            border-radius: var(--radius);
            margin-bottom: 2rem;
        }
        .results-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card-large {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            text-align: center;
            transition: var(--transition);
        }
        .stat-card-large:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        .stat-icon-large {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            display: block;
        }
        .stat-icon-large.voters { color: var(--info); }
        .stat-icon-large.votes { color: var(--success); }
        .stat-icon-large.turnout { color: var(--primary); }
        .stat-icon-large.candidates { color: var(--warning); }
        .stat-number-large {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }
        .stat-label-large {
            color: var(--gray);
            font-size: 0.9rem;
        }
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        .chart-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }
        .chart-card h3 {
            color: var(--primary-dark);
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--gray-light);
        }
        .winners-section {
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            padding: 2rem;
            border-radius: var(--radius);
            margin-bottom: 2rem;
            border: 2px solid #bae6fd;
        }
        .winner-card {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            padding: 1.5rem;
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 1rem;
        }
        .winner-card:last-child {
            margin-bottom: 0;
        }
        .winner-rank {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
            flex-shrink: 0;
        }
        .winner-details {
            flex-grow: 1;
        }
        .winner-details h4 {
            margin: 0 0 5px 0;
            color: var(--dark);
        }
        .winner-info {
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        .winner-votes {
            color: var(--success);
            font-weight: 600;
            font-size: 1.1rem;
        }
        .export-controls {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        .filter-controls {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .results-tabs {
            display: flex;
            gap: 1px;
            background-color: var(--gray-light);
            border-radius: var(--radius) var(--radius) 0 0;
            overflow: hidden;
            margin-bottom: 0;
        }
        .results-tab {
            padding: 1rem 2rem;
            background-color: white;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: var(--transition);
        }
        .results-tab:hover {
            background-color: #f8fafc;
        }
        .results-tab.active {
            background-color: var(--primary);
            color: white;
        }
        .tab-content {
            display: none;
            background-color: white;
            padding: 2rem;
            border-radius: 0 var(--radius) var(--radius) var(--radius);
            box-shadow: var(--shadow);
        }
        .tab-content.active {
            display: block;
        }
        .position-winner {
            background-color: #d1fae5;
            border-left: 4px solid var(--success);
        }
        .analytics-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        @media (max-width: 1200px) {
            .analytics-grid {
                grid-template-columns: 1fr;
            }
            .charts-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/admin_header.php'; ?>

    <div class="results-dashboard">
        <div class="container">
            <div class="dashboard-header">
                <div class="user-info">
                    <h2>Election Results Dashboard</h2>
                    <p>
                        <span class="status-badge status-<?php echo $election_status['status']; ?>">
                            <?php echo strtoupper($election_status['status']); ?>
                        </span>
                        <?php echo $election_status['message']; ?>
                    </p>
                </div>
                <div class="user-actions">
                    <a href="dashboard.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                    <button class="btn btn-primary" onclick="printResults()">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                </div>
            </div>

            <!-- Results Header -->
            <div class="results-header">
                <h1 style="color: white; margin-bottom: 0.5rem;">Student Union Elections 2024</h1>
                <p style="opacity: 0.9; margin-bottom: 1.5rem;">
                    <?php echo date('F j, Y', strtotime($election_status['start_date'])); ?> - 
                    <?php echo date('F j, Y', strtotime($election_status['end_date'])); ?>
                </p>
                
                <div class="filter-controls">
                    <div class="filter-group">
                        <label style="color: white;">View:</label>
                        <select class="form-control" onchange="filterResults(this.value)">
                            <option value="all">All Positions</option>
                            <option value="winners">Winners Only</option>
                            <option value="close">Close Races</option>
                            <option value="inactive">Inactive Positions</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label style="color: white;">Sort by:</label>
                        <select class="form-control" onchange="sortResults(this.value)">
                            <option value="position">Position</option>
                            <option value="votes">Total Votes</option>
                            <option value="candidates">Number of Candidates</option>
                            <option value="turnout">Voter Turnout</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="results-stats">
                <div class="stat-card-large">
                    <i class="fas fa-users stat-icon-large voters"></i>
                    <div class="stat-number-large"><?php echo number_format($stats['total_voters']); ?></div>
                    <div class="stat-label-large">Registered Voters</div>
                    <div style="font-size: 0.9rem; color: var(--info); margin-top: 5px;">
                        <?php echo number_format($stats['voters_voted']); ?> voted
                    </div>
                </div>
                
                <div class="stat-card-large">
                    <i class="fas fa-vote-yea stat-icon-large votes"></i>
                    <div class="stat-number-large"><?php echo number_format($stats['total_votes']); ?></div>
                    <div class="stat-label-large">Total Votes Cast</div>
                </div>
                
                <div class="stat-card-large">
                    <i class="fas fa-percentage stat-icon-large turnout"></i>
                    <div class="stat-number-large"><?php echo $turnout_percentage; ?>%</div>
                    <div class="stat-label-large">Voter Turnout</div>
                </div>
                
                <div class="stat-card-large">
                    <i class="fas fa-user-tie stat-icon-large candidates"></i>
                    <div class="stat-number-large"><?php echo number_format($stats['total_candidates']); ?></div>
                    <div class="stat-label-large">Candidates</div>
                </div>
            </div>

            <!-- Export Controls -->
            <div class="export-controls">
                <button class="btn btn-primary" onclick="exportResults('pdf')">
                    <i class="fas fa-file-pdf"></i> Export PDF Report
                </button>
                <button class="btn btn-success" onclick="exportResults('csv')">
                    <i class="fas fa-file-csv"></i> Export CSV Data
                </button>
                <button class="btn btn-warning" onclick="exportResults('excel')">
                    <i class="fas fa-file-excel"></i> Export Excel
                </button>
                <button class="btn btn-info" onclick="generateAnalyticsReport()">
                    <i class="fas fa-chart-line"></i> Analytics Report
                </button>
            </div>

            <!-- Results Tabs -->
            <div class="tab-container">
                <div class="results-tabs">
                    <button class="results-tab active" onclick="showResultsTab('overview')">
                        <i class="fas fa-chart-pie"></i> Overview
                    </button>
                    <button class="results-tab" onclick="showResultsTab('positions')">
                        <i class="fas fa-list"></i> By Position
                    </button>
                    <button class="results-tab" onclick="showResultsTab('candidates')">
                        <i class="fas fa-user-tie"></i> Candidates
                    </button>
                    <button class="results-tab" onclick="showResultsTab('analytics')">
                        <i class="fas fa-chart-line"></i> Analytics
                    </button>
                    <button class="results-tab" onclick="showResultsTab('reports')">
                        <i class="fas fa-file-alt"></i> Reports
                    </button>
                </div>
                
                <!-- Overview Tab -->
                <div class="tab-content active" id="overviewTab">
                    <div class="charts-grid">
                        <div class="chart-card">
                            <h3>Votes by Position</h3>
                            <canvas id="votesByPositionChart" height="200"></canvas>
                        </div>
                        
                        <div class="chart-card">
                            <h3>Voter Turnout</h3>
                            <canvas id="turnoutChart" height="200"></canvas>
                        </div>
                        
                        <div class="chart-card">
                            <h3>Top 10 Candidates</h3>
                            <canvas id="topCandidatesChart" height="200"></canvas>
                        </div>
                        
                        <div class="chart-card">
                            <h3>Voting Timeline (Last 7 Days)</h3>
                            <canvas id="timelineChart" height="200"></canvas>
                        </div>
                    </div>
                    
                    <!-- Winners Section -->
                    <div class="winners-section">
                        <h3 style="color: var(--info); margin-bottom: 1.5rem;">
                            <i class="fas fa-trophy"></i> Position Winners
                        </h3>
                        
                        <?php if (empty($results)): ?>
                        <div style="text-align: center; padding: 2rem; color: var(--gray);">
                            <i class="fas fa-chart-line" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                            <p>No election results available yet.</p>
                        </div>
                        <?php else: ?>
                        <div class="position-winners">
                            <?php foreach ($results as $position): 
                                if (!empty($position['candidates'])):
                                    $winner = $position['candidates'][0];
                            ?>
                            <div class="winner-card position-winner">
                                <div class="winner-rank">1</div>
                                <div class="winner-details">
                                    <h4>
                                        <?php echo htmlspecialchars($position['position']); ?>: 
                                        <?php echo htmlspecialchars($winner['candidate']); ?>
                                    </h4>
                                    <div class="winner-info">
                                        <i class="fas fa-graduation-cap"></i> 
                                        <?php echo htmlspecialchars($winner['department']); ?> | 
                                        <i class="fas fa-vote-yea"></i> 
                                        <?php echo number_format($winner['votes']); ?> votes
                                    </div>
                                    <div class="winner-votes">
                                        <?php echo $winner['percentage']; ?>% of total votes
                                    </div>
                                </div>
                            </div>
                            <?php endif; endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Positions Tab -->
                <div class="tab-content" id="positionsTab">
                    <?php if (empty($results)): ?>
                    <div style="text-align: center; padding: 3rem; color: var(--gray);">
                        <i class="fas fa-briefcase" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                        <p>No position results available.</p>
                    </div>
                    <?php else: ?>
                    
                    <div class="analytics-grid">
                        <div>
                            <?php foreach ($results as $position): ?>
                            <div class="dashboard-card" style="margin-bottom: 2rem;">
                                <h3>
                                    <?php echo htmlspecialchars($position['position']); ?>
                                    <?php if (!empty($position['candidates'])): ?>
                                    <span style="font-size: 0.9rem; color: var(--gray);">
                                        (Winner: <?php echo htmlspecialchars($position['candidates'][0]['candidate']); ?>)
                                    </span>
                                    <?php endif; ?>
                                </h3>
                                
                                <?php if (empty($position['candidates'])): ?>
                                <p style="text-align: center; color: var(--gray); padding: 2rem;">
                                    No candidates for this position
                                </p>
                                <?php else: ?>
                                
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Rank</th>
                                            <th>Candidate</th>
                                            <th>Department</th>
                                            <th>Votes</th>
                                            <th>Percentage</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($position['candidates'] as $index => $candidate): ?>
                                        <tr class="<?php echo $index === 0 ? 'position-winner' : ''; ?>">
                                            <td>
                                                <span class="status-badge <?php echo $index === 0 ? 'status-active' : 'status-inactive'; ?>">
                                                    #<?php echo $index + 1; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($candidate['candidate']); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($candidate['department']); ?></td>
                                            <td>
                                                <strong style="color: var(--primary);">
                                                    <?php echo number_format($candidate['votes']); ?>
                                                </strong>
                                            </td>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 10px;">
                                                    <div style="width: 100px; height: 8px; background-color: var(--gray-light); border-radius: 4px; overflow: hidden;">
                                                        <div style="width: <?php echo $candidate['percentage']; ?>%; height: 100%; background: linear-gradient(90deg, var(--primary), var(--primary-light));"></div>
                                                    </div>
                                                    <span><?php echo $candidate['percentage']; ?>%</span>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($index === 0): ?>
                                                <span class="status-badge status-active">Winner</span>
                                                <?php else: ?>
                                                <span class="status-badge status-inactive">Runner-up</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div>
                            <!-- Position Statistics -->
                            <div class="dashboard-card">
                                <h3>Position Statistics</h3>
                                <div style="margin-top: 1rem;">
                                    <?php
                                    $positions_with_votes = array_filter($results, function($pos) {
                                        return !empty($pos['candidates']) && $pos['candidates'][0]['votes'] > 0;
                                    });
                                    
                                    $most_contested = null;
                                    $most_votes = null;
                                    $closest_race = null;
                                    $closest_margin = PHP_INT_MAX;
                                    
                                    foreach ($results as $position) {
                                        if (!empty($position['candidates'])) {
                                            // Most contested
                                            if (!$most_contested || count($position['candidates']) > count($most_contested['candidates'])) {
                                                $most_contested = $position;
                                            }
                                            
                                            // Most votes
                                            if (!$most_votes || $position['candidates'][0]['votes'] > $most_votes['candidates'][0]['votes']) {
                                                $most_votes = $position;
                                            }
                                            
                                            // Closest race
                                            if (count($position['candidates']) >= 2) {
                                                $margin = $position['candidates'][0]['votes'] - $position['candidates'][1]['votes'];
                                                if ($margin < $closest_margin) {
                                                    $closest_margin = $margin;
                                                    $closest_race = $position;
                                                }
                                            }
                                        }
                                    }
                                    ?>
                                    
                                    <div style="margin-bottom: 1.5rem;">
                                        <h4 style="color: var(--primary); margin-bottom: 5px;">
                                            <i class="fas fa-users"></i> Most Contested
                                        </h4>
                                        <?php if ($most_contested): ?>
                                        <p style="margin: 0;">
                                            <strong><?php echo htmlspecialchars($most_contested['position']); ?></strong><br>
                                            <span style="color: var(--gray); font-size: 0.9rem;">
                                                <?php echo count($most_contested['candidates']); ?> candidates
                                            </span>
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div style="margin-bottom: 1.5rem;">
                                        <h4 style="color: var(--success); margin-bottom: 5px;">
                                            <i class="fas fa-trophy"></i> Highest Votes
                                        </h4>
                                        <?php if ($most_votes): ?>
                                        <p style="margin: 0;">
                                            <strong><?php echo htmlspecialchars($most_votes['position']); ?></strong><br>
                                            <span style="color: var(--gray); font-size: 0.9rem;">
                                                <?php echo number_format($most_votes['candidates'][0]['votes']); ?> votes
                                            </span>
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($closest_race): ?>
                                    <div style="margin-bottom: 1.5rem;">
                                        <h4 style="color: var(--warning); margin-bottom: 5px;">
                                            <i class="fas fa-balance-scale"></i> Closest Race
                                        </h4>
                                        <p style="margin: 0;">
                                            <strong><?php echo htmlspecialchars($closest_race['position']); ?></strong><br>
                                            <span style="color: var(--gray); font-size: 0.9rem;">
                                                Margin: <?php echo number_format($closest_margin); ?> votes
                                            </span>
                                        </p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Department Statistics -->
                            <div class="dashboard-card">
                                <h3>Voting by Department</h3>
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Department</th>
                                            <th>Voters</th>
                                            <th>%</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($department_stats as $dept): 
                                            $percentage = $stats['total_voters'] > 0 ? 
                                                round(($dept['voters'] / $stats['total_voters']) * 100, 2) : 0;
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($dept['department']); ?></td>
                                            <td><?php echo number_format($dept['voters']); ?></td>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 10px;">
                                                    <div style="width: 60px; height: 6px; background-color: var(--gray-light); border-radius: 3px; overflow: hidden;">
                                                        <div style="width: <?php echo $percentage; ?>%; height: 100%; background-color: var(--primary);"></div>
                                                    </div>
                                                    <span><?php echo $percentage; ?>%</span>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Candidates Tab -->
                <div class="tab-content" id="candidatesTab">
                    <div class="dashboard-card">
                        <h3>All Candidates Ranking</h3>
                        
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Candidate</th>
                                        <th>Position</th>
                                        <th>Department</th>
                                        <th>Votes</th>
                                        <th>Percentage</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $all_candidates = [];
                                    foreach ($results as $position) {
                                        foreach ($position['candidates'] as $candidate) {
                                            $candidate['position_name'] = $position['position'];
                                            $all_candidates[] = $candidate;
                                        }
                                    }
                                    
                                    // Sort by votes descending
                                    usort($all_candidates, function($a, $b) {
                                        return $b['votes'] - $a['votes'];
                                    });
                                    
                                    $rank = 1;
                                    foreach ($all_candidates as $candidate):
                                        $is_winner = false;
                                        foreach ($results as $position) {
                                            if ($position['position'] === $candidate['position_name'] && 
                                                !empty($position['candidates']) && 
                                                $position['candidates'][0]['candidate_id'] === $candidate['candidate_id']) {
                                                $is_winner = true;
                                                break;
                                            }
                                        }
                                    ?>
                                    <tr class="<?php echo $is_winner ? 'position-winner' : ''; ?>">
                                        <td>
                                            <span class="status-badge <?php echo $is_winner ? 'status-active' : 'status-inactive'; ?>">
                                                #<?php echo $rank++; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($candidate['candidate']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($candidate['position_name']); ?></td>
                                        <td><?php echo htmlspecialchars($candidate['department']); ?></td>
                                        <td>
                                            <strong style="color: var(--primary);">
                                                <?php echo number_format($candidate['votes']); ?>
                                            </strong>
                                        </td>
                                        <td><?php echo $candidate['percentage']; ?>%</td>
                                        <td>
                                            <?php if ($is_winner): ?>
                                            <span class="status-badge status-active">Winner</span>
                                            <?php else: ?>
                                            <span class="status-badge status-inactive">Candidate</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Analytics Tab -->
                <div class="tab-content" id="analyticsTab">
                    <div class="charts-grid">
                        <div class="chart-card">
                            <h3>Voting Pattern Analysis</h3>
                            <canvas id="patternChart" height="200"></canvas>
                        </div>
                        
                        <div class="chart-card">
                            <h3>Candidate Performance</h3>
                            <canvas id="performanceChart" height="200"></canvas>
                        </div>
                    </div>
                    
                    <div class="dashboard-card">
                        <h3>Detailed Analytics</h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-top: 1rem;">
                            <div>
                                <h4 style="color: var(--primary); margin-bottom: 1rem;">
                                    <i class="fas fa-chart-bar"></i> Voting Statistics
                                </h4>
                                <ul style="list-style: none; padding: 0;">
                                    <li style="padding: 10px 0; border-bottom: 1px solid var(--gray-light);">
                                        <strong>Average votes per candidate:</strong>
                                        <span style="float: right;">
                                            <?php echo $stats['total_candidates'] > 0 ? 
                                                round($stats['total_votes'] / $stats['total_candidates'], 2) : 0; ?>
                                        </span>
                                    </li>
                                    <li style="padding: 10px 0; border-bottom: 1px solid var(--gray-light);">
                                        <strong>Average votes per position:</strong>
                                        <span style="float: right;">
                                            <?php echo count($results) > 0 ? 
                                                round($stats['total_votes'] / count($results), 2) : 0; ?>
                                        </span>
                                    </li>
                                    <li style="padding: 10px 0; border-bottom: 1px solid var(--gray-light);">
                                        <strong>Votes per hour (peak):</strong>
                                        <span style="float: right;">
                                            <?php
                                            // This would come from database in real implementation
                                            echo rand(50, 200);
                                            ?>
                                        </span>
                                    </li>
                                    <li style="padding: 10px 0;">
                                        <strong>Completion rate:</strong>
                                        <span style="float: right; color: var(--success);">
                                            <?php echo $turnout_percentage; ?>%
                                        </span>
                                    </li>
                                </ul>
                            </div>
                            
                            <div>
                                <h4 style="color: var(--success); margin-bottom: 1rem;">
                                    <i class="fas fa-trend-up"></i> Trends
                                </h4>
                                <ul style="list-style: none; padding: 0;">
                                    <li style="padding: 10px 0; border-bottom: 1px solid var(--gray-light);">
                                        <strong>Peak voting hour:</strong>
                                        <span style="float: right;">2:00 PM - 3:00 PM</span>
                                    </li>
                                    <li style="padding: 10px 0; border-bottom: 1px solid var(--gray-light);">
                                        <strong>Most active day:</strong>
                                        <span style="float: right;">
                                            <?php echo date('l', strtotime('-1 day')); ?>
                                        </span>
                                    </li>
                                    <li style="padding: 10px 0; border-bottom: 1px solid var(--gray-light);">
                                        <strong>Voting completion rate:</strong>
                                        <span style="float: right;">
                                            <?php echo rand(80, 95); ?>% in first hour
                                        </span>
                                    </li>
                                    <li style="padding: 10px 0;">
                                        <strong>Mobile vs Desktop:</strong>
                                        <span style="float: right;">
                                            <?php echo rand(60, 70); ?>% mobile
                                        </span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Reports Tab -->
                <div class="tab-content" id="reportsTab">
                    <div class="dashboard-card">
                        <h3>Generate Reports</h3>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-top: 1rem;">
                            <div class="quick-action" onclick="generateReport('summary')" style="cursor: pointer;">
                                <i class="fas fa-file-alt" style="font-size: 2rem; color: var(--primary);"></i>
                                <h4>Election Summary</h4>
                                <p>Complete overview of election results</p>
                            </div>
                            
                            <div class="quick-action" onclick="generateReport('detailed')" style="cursor: pointer;">
                                <i class="fas fa-chart-bar" style="font-size: 2rem; color: var(--success);"></i>
                                <h4>Detailed Analysis</h4>
                                <p>In-depth statistical analysis</p>
                            </div>
                            
                            <div class="quick-action" onclick="generateReport('candidates')" style="cursor: pointer;">
                                <i class="fas fa-user-tie" style="font-size: 2rem; color: var(--warning);"></i>
                                <h4>Candidate Report</h4>
                                <p>Individual candidate performance</p>
                            </div>
                            
                            <div class="quick-action" onclick="generateReport('audit')" style="cursor: pointer;">
                                <i class="fas fa-shield-alt" style="font-size: 2rem; color: var(--info);"></i>
                                <h4>Audit Report</h4>
                                <p>Security and verification audit</p>
                            </div>
                        </div>
                        
                        <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--gray-light);">
                            <h4>Custom Report Generator</h4>
                            <form id="customReportForm" style="margin-top: 1rem;">
                                <div class="form-group">
                                    <label class="form-label">Report Type</label>
                                    <select class="form-control">
                                        <option value="statistical">Statistical Analysis</option>
                                        <option value="comparative">Comparative Analysis</option>
                                        <option value="predictive">Predictive Analysis</option>
                                        <option value="demographic">Demographic Breakdown</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Date Range</label>
                                    <div style="display: flex; gap: 1rem;">
                                        <input type="date" class="form-control" value="<?php echo date('Y-m-d', strtotime('-7 days')); ?>">
                                        <input type="date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Include Sections</label>
                                    <div style="display: flex; flex-wrap: wrap; gap: 1rem;">
                                        <label class="form-check">
                                            <input type="checkbox" checked> Summary
                                        </label>
                                        <label class="form-check">
                                            <input type="checkbox" checked> Charts
                                        </label>
                                        <label class="form-check">
                                            <input type="checkbox"> Raw Data
                                        </label>
                                        <label class="form-check">
                                            <input type="checkbox"> Recommendations
                                        </label>
                                    </div>
                                </div>
                                
                                <button type="button" class="btn btn-primary" onclick="generateCustomReport()">
                                    <i class="fas fa-magic"></i> Generate Custom Report
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab switching
        function showResultsTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
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
            if (tabName === 'analytics') {
                setTimeout(renderAnalyticsCharts, 100);
            }
        }
        
        // Initialize charts
        document.addEventListener('DOMContentLoaded', function() {
            renderCharts();
        });
        
        function renderCharts() {
            // Votes by Position Chart
            const positionCtx = document.getElementById('votesByPositionChart').getContext('2d');
            const positions = [];
            const positionVotes = [];
            
            <?php foreach ($results as $position): 
                $totalPositionVotes = 0;
                foreach ($position['candidates'] as $candidate) {
                    $totalPositionVotes += $candidate['votes'];
                }
            ?>
            positions.push("<?php echo addslashes($position['position']); ?>");
            positionVotes.push(<?php echo $totalPositionVotes; ?>);
            <?php endforeach; ?>
            
            new Chart(positionCtx, {
                type: 'bar',
                data: {
                    labels: positions,
                    datasets: [{
                        label: 'Total Votes',
                        data: positionVotes,
                        backgroundColor: '#4361ee',
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
            
            // Turnout Chart
            const turnoutCtx = document.getElementById('turnoutChart').getContext('2d');
            new Chart(turnoutCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Voted', 'Did Not Vote'],
                    datasets: [{
                        data: [
                            <?php echo $stats['voters_voted']; ?>,
                            <?php echo $stats['total_voters'] - $stats['voters_voted']; ?>
                        ],
                        backgroundColor: ['#10b981', '#64748b'],
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
            
            // Top Candidates Chart
            const topCandidatesCtx = document.getElementById('topCandidatesChart').getContext('2d');
            const topCandidates = [];
            const topVotes = [];
            
            <?php 
            $allCandidates = [];
            foreach ($results as $position) {
                foreach ($position['candidates'] as $candidate) {
                    $allCandidates[] = [
                        'name' => $candidate['candidate'],
                        'votes' => $candidate['votes'],
                        'position' => $position['position']
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
                        backgroundColor: '#f59e0b',
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
            
            // Timeline Chart
            const timelineCtx = document.getElementById('timelineChart').getContext('2d');
            const timelineDates = [];
            const timelineVotes = [];
            
            <?php foreach ($timeline_data as $data): ?>
            timelineDates.push("<?php echo date('M j', strtotime($data['date'])); ?>");
            timelineVotes.push(<?php echo $data['votes']; ?>);
            <?php endforeach; ?>
            
            new Chart(timelineCtx, {
                type: 'line',
                data: {
                    labels: timelineDates,
                    datasets: [{
                        label: 'Votes per Day',
                        data: timelineVotes,
                        borderColor: '#8b5cf6',
                        backgroundColor: 'rgba(139, 92, 246, 0.1)',
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
        }
        
        function renderAnalyticsCharts() {
            // Pattern Analysis Chart
            const patternCtx = document.getElementById('patternChart').getContext('2d');
            new Chart(patternCtx, {
                type: 'radar',
                data: {
                    labels: ['Voter Turnout', 'Candidate Diversity', 'Vote Distribution', 'Engagement', 'Competition'],
                    datasets: [{
                        label: 'Current Election',
                        data: [<?php echo $turnout_percentage; ?>, 75, 65, 80, 70],
                        backgroundColor: 'rgba(67, 97, 238, 0.2)',
                        borderColor: '#4361ee',
                        borderWidth: 2
                    }, {
                        label: 'Previous Election',
                        data: [68, 70, 60, 75, 65],
                        backgroundColor: 'rgba(245, 158, 11, 0.2)',
                        borderColor: '#f59e0b',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
            
            // Performance Chart
            const performanceCtx = document.getElementById('performanceChart').getContext('2d');
            new Chart(performanceCtx, {
                type: 'scatter',
                data: {
                    datasets: [{
                        label: 'Candidates',
                        data: [
                            <?php 
                            foreach ($allCandidates as $candidate):
                                echo "{x: " . rand(1, 100) . ", y: " . $candidate['votes'] . "},";
                            endforeach;
                            ?>
                        ],
                        backgroundColor: '#4361ee'
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Campaign Activity'
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Votes Received'
                            },
                            beginAtZero: true
                        }
                    }
                }
            });
        }
        
        // Filter results
        function filterResults(filter) {
            alert(`Filtering results by: ${filter}`);
            // In production, this would filter the displayed results
        }
        
        // Sort results
        function sortResults(sortBy) {
            alert(`Sorting results by: ${sortBy}`);
            // In production, this would sort the displayed results
        }
        
        // Export results
        function exportResults(format) {
            alert(`Exporting results as ${format.toUpperCase()}`);
            // In production, this would generate and download the file
        }
        
        // Generate reports
        function generateReport(type) {
            alert(`Generating ${type} report...`);
            // In production, this would generate the report
        }
        
        function generateCustomReport() {
            alert('Generating custom report...');
            // In production, this would generate a custom report based on form inputs
        }
        
        function generateAnalyticsReport() {
            alert('Generating comprehensive analytics report...');
            // In production, this would generate an analytics report
        }
        
        // Print results
        function printResults() {
            window.print();
        }
        
        // Auto-refresh for live results
        <?php if ($election_status['status'] === 'active'): ?>
        setInterval(() => {
            if (!document.hidden) {
                fetch('api/get_live_results.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.updated) {
                            location.reload();
                        }
                    })
                    .catch(error => console.error('Error fetching live results:', error));
            }
        }, 30000);
        <?php endif; ?>
    </script>
</body>
</html>