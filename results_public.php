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
    <!-- Google Fonts for Premium Typography -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --primary-light: #818cf8;
            --secondary: #0ea5e9;
            --dark: #0f172a;
            --gray: #64748b;
            --light: #f8fafc;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --radius: 12px;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background-color: #f1f5f9;
            color: var(--dark);
            line-height: 1.6;
        }

        /* Navigation */
        .navbar {
            background: white;
            box-shadow: var(--shadow);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        /* Hero Header */
        .results-hero {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 4rem 0 3rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .results-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
            position: relative;
            z-index: 1;
        }

        .hero-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            letter-spacing: -0.5px;
        }

        .hero-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }

        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 50px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            font-weight: 600;
        }

        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: var(--success);
            box-shadow: 0 0 10px var(--success);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: -30px;
            margin-bottom: 3rem;
            position: relative;
            z-index: 10;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .stat-label {
            color: var(--gray);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        /* Winner Showcase */
        .winner-section {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 2rem;
            position: relative;
            display: inline-block;
        }

        .section-title::after {
            content: '';
            display: block;
            width: 60px;
            height: 4px;
            background: var(--primary);
            margin: 8px auto 0;
            border-radius: 2px;
        }

        /* Candidate Grid */
        .candidates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 4rem;
        }

        .candidate-card {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .candidate-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
        }

        /* Rank Badge */
        .rank-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--dark);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            box-shadow: var(--shadow);
            z-index: 2;
        }

        .rank-1 {
            background: linear-gradient(135deg, #FFD700, #FDB931);
            color: #000;
            font-size: 1.2rem;
            width: 50px;
            height: 50px;
            top: 10px;
            right: 10px;
            border: 2px solid white;
        }

        .rank-2 {
            background: linear-gradient(135deg, #E0E0E0, #BDBDBD);
            color: #000;
        }

        .rank-3 {
            background: linear-gradient(135deg, #CD7F32, #A0522D);
            color: white;
        }

        /* Photo Area */
        .card-photo-wrapper {
            height: 250px;
            position: relative;
            overflow: hidden;
            background: #e2e8f0;
        }

        .card-photo {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background-color: white;
            object-position: center bottom;
            transition: transform 0.5s ease;
        }

        .candidate-card:hover .card-photo {
            transform: scale(1.05);
        }

        .card-body {
            padding: 1.5rem;
            text-align: center;
        }

        .candidate-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.2rem;
        }

        .candidate-dept {
            color: var(--secondary);
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }

        .vote-stats {
            background: var(--light);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .vote-count {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--primary);
            line-height: 1;
        }

        .vote-label {
            font-size: 0.8rem;
            color: var(--gray);
            text-transform: uppercase;
        }

        .progress-container {
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 1rem;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border-radius: 4px;
            transition: width 1s ease-out;
        }

        .vote-percentage {
            margin-top: 5px;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--gray);
            text-align: right;
        }

        /* Winner Card Special Styling */
        .winner-card {
            border: 2px solid var(--warning);
            transform: scale(1.02);
        }

        .winner-banner {
            background: var(--warning);
            color: white;
            text-align: center;
            padding: 5px;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 1px;
        }

        /* Navigation Links */
        .nav-links {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--gray);
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .btn-outline {
            border: 2px solid var(--primary);
            color: var(--primary);
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 600;
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        @media (max-width: 768px) {
            .results-hero {
                padding: 3rem 0 2rem;
            }

            .hero-title {
                font-size: 2rem;
            }

            .candidates-grid {
                grid-template-columns: 1fr;
            }

            .card-photo-wrapper {
                height: 300px;
            }
        }
    </style>
</head>

<body>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center;">
            <a href="index.php" class="logo">
                <i class="fas fa-vote-yea"></i> StudentVote
            </a>
            <div class="nav-links">
                <a href="<?php echo SITE_URL; ?>index.php">Home</a>
                <a href="<?php echo SITE_URL; ?>vote.php">Back to Voting</a>
                <a href="<?php echo SITE_URL; ?>logout.php" class="btn-outline">Logout</a>
            </div>
        </div>
    </nav>
    <!-- Hero Section -->
    <div class="results-hero">
        <div class="container">
            <h1 class="hero-title">Election Results 2026</h1>
            <p class="hero-subtitle">Live real-time updates from the Student Union Election</p>

            <div class="status-badge">
                <div class="status-indicator"></div>
                <?php echo ucfirst($election_status['status']); ?> - Voting in Progress
            </div>
        </div>
    </div>

    <!-- Stats Section -->
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($total_voters); ?></div>
                <div class="stat-label">Total Votes</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $turnout_percentage; ?>%</div>
                <div class="stat-label">Turnout</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">6</div>
                <div class="stat-label">Candidates</div>
            </div>
        </div>
    </div>

    <!-- Results Content -->
    <div class="container">

        <?php foreach ($results as $position): ?>

            <div class="winner-section">
                <h2 class="section-title"><?php echo htmlspecialchars($position['position']); ?> Results</h2>
            </div>

            <?php if (empty($position['candidates'])): ?>
                <div style="text-align: center; padding: 4rem; color: var(--gray);">
                    <h3>No candidates for this position.</h3>
                </div>
            <?php else: ?>

                <div class="candidates-grid">
                    <?php
                    // Sort candidates by votes descending just to be safe
                    usort($position['candidates'], function ($a, $b) {
                        return $b['votes'] <=> $a['votes'];
                    });

                    foreach ($position['candidates'] as $index => $candidate):
                        $rank = $index + 1;
                        $is_winner = ($index == 0);

                        $photo_filename = !empty($candidate['photo']) ? htmlspecialchars($candidate['photo']) : 'default.jpg';
                        if ($photo_filename === 'default.jpg') {
                            $photo_path = SITE_URL . 'Assets/images/default.jpg';
                        } else {
                            $photo_path = SITE_URL . 'uploads/candidates/' . $photo_filename;
                        }
                        ?>
                        <div class="candidate-card <?php echo $is_winner ? 'winner-card' : ''; ?>">
                            <?php if ($is_winner): ?>
                                <div class="winner-banner"><i class="fas fa-crown"></i> Current Leader</div>
                            <?php endif; ?>

                            <div class="rank-badge rank-<?php echo $rank <= 3 ? $rank : 'other'; ?>">
                                #<?php echo $rank; ?>
                            </div>

                            <div class="card-photo-wrapper">
                                <img src="<?php echo $photo_path; ?>" alt="<?php echo htmlspecialchars($candidate['candidate']); ?>"
                                    class="card-photo" onerror="this.onerror=null;this.src='Assets/images/default.jpg';">
                            </div>

                            <div class="card-body">
                                <h3 class="candidate-name"><?php echo htmlspecialchars($candidate['candidate']); ?></h3>
                                <div class="candidate-dept"><?php echo htmlspecialchars($candidate['department']); ?></div>

                                <div class="vote-stats">
                                    <div class="vote-count"><?php echo number_format($candidate['votes']); ?></div>
                                    <div class="vote-label">Votes Received</div>
                                </div>

                                <div class="progress-bar-wrapper">
                                    <div class="progress-container">
                                        <div class="progress-bar" style="width: <?php echo $candidate['percentage']; ?>%"></div>
                                    </div>
                                    <div class="vote-percentage"><?php echo $candidate['percentage']; ?>%</div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>

        <?php endforeach; ?>

        <?php if (empty($results)): ?>
            <div style="text-align: center; padding: 5rem;">
                <h2 style="color: var(--gray);">Waiting for results...</h2>
            </div>
        <?php endif; ?>

    </div>

    <footer style="text-align: center; padding: 2rem; opacity: 0.6; font-size: 0.9rem;">
        &copy; <?php echo date('Y'); ?> Student Union Voting System
    </footer>

</body>

</html>