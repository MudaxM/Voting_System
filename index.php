<?php
require_once 'includes/config.php';
$election_status = getElectionStatus($pdo);

$stats = [];
// Total candidates
$sql = "SELECT COUNT(*) as count FROM candidates WHERE is_active = 1";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$stats['total_candidates'] = $stmt->fetch()['count'];

// Total registered voters
$sql = "SELECT COUNT(*) as count FROM users WHERE is_admin = 0";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$stats['total_voters'] = $stmt->fetch()['count'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Secure Online Voting</title>
    <link rel="stylesheet" href="Assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap"
        rel="stylesheet">
    <style>
        .hero-title {
            margin-top: 1.5rem;
            color: #3a0ca3;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-pill.active {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .status-pill.upcoming {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .status-pill.ended {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .status-pill i {
            font-size: 8px;
        }

        .floating-card {
            position: absolute;
            bottom: -30px;
            left: -30px;
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            display: flex;
            align-items: center;
            gap: 1rem;
            border: 1px solid var(--gray-100);
            z-index: 5;
            animation: float 4s ease-in-out infinite;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        .floating-card i {
            width: 44px;
            height: 44px;
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
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
                <a href="index.php" class="active btn-login">Home</a>
                <a href="#about" class="btn-login">About</a>
                <a href="results_public.php" class="btn-login">Live Results</a>
                <a href="login.php" class="btn-login">Sign In</a>
                <a href="register.php" class="btn-register">Register</a>
            </div>
            <div class="menu-toggle">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="status-pill <?php echo $election_status['status']; ?>">
                    <i class="fas fa-circle"></i>
                    <?php echo ucfirst($election_status['status']); ?> Election
                </div>
                <h1 class="hero-title">Empowering the Next Generation of Student Leaders</h1>
                <p class="subtitle">Secure, transparent, and accessible voting system. Make your voice heard and shape
                    the future of our student community today.</p>

                <div class="hero-buttons">
                    <?php if ($election_status['status'] == 'active'): ?>
                        <a href="vote.php" class="btn btn-primary">
                            Cast Your Ballot <i class="fas fa-arrow-right"></i>
                        </a>
                    <?php elseif ($election_status['status'] == 'upcoming'): ?>
                        <a href="register.php" class="btn btn-primary">
                            Get Registered <i class="fas fa-user-plus"></i>
                        </a>
                    <?php endif; ?>
                    <a href="results_public.php" class="btn btn-outline">
                        View Live Turnout <i class="fas fa-chart-line"></i>
                    </a>
                </div>

                <div class="stats">
                    <div class="stat-item">
                        <i class="fas fa-users"></i>
                        <div>
                            <h3><?php echo number_format($stats['total_voters']); ?></h3>
                            <p>Voters</p>
                        </div>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-user-tie"></i>
                        <div>
                            <h3><?php echo number_format($stats['total_candidates']); ?></h3>
                            <p>Candidates</p>
                        </div>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-check-double"></i>
                        <div>
                            <h3>100%</h3>
                            <p>Secure</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="hero-image">
                <img src="Assets/images/hero_vote.png"
                    alt="Students Voting">
                <div class="floating-card">
                    <i class="fas fa-shield-check"></i>
                    <div>
                        <strong style="display: block; font-size: 1rem;">Verified Identity</strong>
                        <p style="margin: 0; font-size: 0.875rem;">Powered by Student ID</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about-section">
        <div class="container">
            <h2 class="section-title">Why Choose StudentVote?</h2>
            <div class="about-grid">
                <div class="about-card">
                    <div class="icon">
                        <i class="fas fa-fingerprint"></i>
                    </div>
                    <h3>Biometric Security</h3>
                    <p>Advanced security protocols ensure each student can only vote once per position.</p>
                </div>
                <div class="about-card">
                    <div class="icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h3>Total Transparency</h3>
                    <p>Every vote is counted accurately with real-time auditing capabilities for the committee.</p>
                </div>
                <div class="about-card">
                    <div class="icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3>Instant Tabulation</h3>
                    <p>No more manual counting. Results are available seconds after the voting period ends.</p>
                </div>
                <div class="about-card">
                    <div class="icon">
                        <i class="fas fa-universal-access"></i>
                    </div>
                    <h3>Universal Access</h3>
                    <p>Designed for accessibility, allowing every student to vote regardless of their device or ability.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Positions Section -->
    <section id="positions" class="positions-section">
        <div class="container">
            <h2 class="section-title">Election Positions</h2>
            <div class="positions-grid">
                <?php
                $positions = getPositionsWithCandidates($pdo);
                foreach ($positions as $position):
                    ?>
                    <div class="position-card">
                        <div class="position-header">
                            <h3><?php echo htmlspecialchars($position['title']); ?></h3>
                            <p><?php echo $position['candidate_count']; ?> Candidates contesting</p>
                        </div>
                        <div class="position-body">
                            <p style="font-size: 0.9rem; line-height: 1.5;">
                                <?php echo htmlspecialchars($position['description']); ?></p>
                            <div class="candidates-preview">
                                <?php
                                $candidates = explode(',', $position['candidate_names']);
                                foreach (array_slice($candidates, 0, 3) as $candidate):
                                    if (!empty(trim($candidate))):
                                        ?>
                                        <span class="candidate-tag"><?php echo htmlspecialchars(trim($candidate)); ?></span>
                                        <?php
                                    endif;
                                endforeach;
                                if ($position['candidate_count'] > 3):
                                    ?>
                                    <span class="candidate-tag more">+<?php echo $position['candidate_count'] - 3; ?>
                                        others</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="position-footer">
                            <?php if ($election_status['status'] == 'active'): ?>
                                <a href="vote.php" class="btn-vote">Pick Your Leader</a>
                            <?php else: ?>
                                <div class="btn-vote disabled">Election <?php echo ucfirst($election_status['status']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Election Timeline -->
    <section class="timeline-section">
        <div class="container">
            <h2 class="section-title">Road to Leadership</h2>
            <div class="timeline">
                <div class="timeline-progress" style="width: <?php echo $election_status['status'] == 'active' ? '50%' : ($election_status['status'] == 'ended' ? '100%' : '15%'); ?>"></div>
                <div class="timeline-item completed">
                    <div class="step-icon"><i class="fas fa-file-signature"></i></div>
                    <div class="timeline-content">
                        <span class="step-label">Phase 1</span>
                        <h3>Nominations Open</h3>
                        <p>Candidates submit their vision and manifesto for the student union.</p>
                    </div>
                </div>
                <div class="timeline-item <?php echo $election_status['status'] == 'active' ? 'current' : ($election_status['status'] == 'ended' ? 'completed' : ''); ?>">
                    <div class="step-icon"><i class="fas fa-vote-yea"></i></div>
                    <div class="timeline-content">
                        <span class="step-label">Phase 2</span>
                        <h3>Voting Window</h3>
                        <p>Main election period where all verified students cast their digital ballots.</p>
                    </div>
                </div>
                <div class="timeline-item <?php echo $election_status['status'] == 'ended' ? 'current' : ''; ?>">
                    <div class="step-icon"><i class="fas fa-trophy"></i></div>
                    <div class="timeline-content">
                        <span class="step-label">Phase 3</span>
                        <h3>Final Declaration</h3>
                        <p>Public announcement of results and official inauguration of the new union.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <a href="index.php" class="logo" style="margin-bottom: 2rem;">
                        <i class="fas fa-vote-yea"></i>
                        <span style="color: white;">StudentVote</span>
                    </a>
                    <p>Pioneering democratic technology for student communities. Built for trust, speed, and
                        transparency.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#"><i class="fab fa-github"></i></a>
                    </div>
                </div>
                <div class="footer-col">
                    <h3>Resources</h3>
                    <ul>
                        <li><a href="results_public.php">Election Data</a></li>
                        <li><a href="#">Voting Policy</a></li>
                        <li><a href="#">How it Works</a></li>
                        <li><a href="#">FAQs</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Student Union</h3>
                    <ul>
                        <li><a href="#">Union Portal</a></li>
                        <li><a href="#">Constitution</a></li>
                        <li><a href="#">Contact Us</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Support Hub</h3>
                    <ul class="contact-info">
                        <li><i class="fas fa-envelope"></i> help@vote.union.edu</li>
                        <li><i class="fas fa-phone-alt"></i> +1 (555) 000-VOTE</li>
                        <li><i class="fas fa-map-marker-alt"></i> Student Hub, Level 2</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Student Union. Developed for transparent democracy.</p>
                <p>Designed with <i class="fas fa-heart"></i> for students</p>
            </div>
        </div>
    </footer>

    <script>
        // Election status countdown and mobile menu
        document.addEventListener('DOMContentLoaded', () => {
            const menuToggle = document.querySelector('.menu-toggle');
            const navLinks = document.querySelector('.nav-links');

            menuToggle.addEventListener('click', () => {
                navLinks.classList.toggle('active');
            });

            // Handle scroll navbar effect
            window.addEventListener('scroll', () => {
                const navbar = document.querySelector('.navbar');
                if (window.scrollY > 50) {
                    navbar.style.padding = '0.5rem 0';
                    navbar.style.boxShadow = 'var(--shadow-md)';
                } else {
                    navbar.style.padding = '1rem 0';
                    navbar.style.boxShadow = '0 1px 0 0 rgba(0, 0, 0, 0.05)';
                }
            });
        });

        <?php if ($election_status['status'] == 'active'): ?>
            const endDate = new Date("<?php echo $election_status['end_date']; ?>").getTime();
            setInterval(() => {
                const distance = endDate - new Date().getTime();
                if (distance < 0) return location.reload();

                const d = Math.floor(distance / (1000 * 60 * 60 * 24));
                const h = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const m = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const s = Math.floor((distance % (1000 * 60)) / 1000);

                const pill = document.querySelector('.status-pill');
                if (pill) pill.innerHTML = `<i class="fas fa-circle"></i> Running: ${d}d ${h}h ${m}m ${s}s`;
            }, 1000);
        <?php endif; ?>
    </script>
</body>

</html>