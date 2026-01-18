<?php
require_once 'includes/config.php';
$election_status = getElectionStatus($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="Assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
                <a href="index.php" class="active">Home</a>
                <a href="#about">About</a>
                <a href="#positions">Positions</a>
                <a href="results_public.php">Results</a>
                <a href="login.php" class="btn-login">Login</a>
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
                <h1>Student Union Elections 2024</h1>
                <p class="subtitle">Your Voice Matters! Cast your vote for the future leaders of our student community.</p>
                
                <div class="election-status">
                    <div class="status-badge status-<?php echo $election_status['status']; ?>">
                        <i class="fas fa-<?php echo $election_status['status'] == 'active' ? 'play-circle' : ($election_status['status'] == 'ended' ? 'stop-circle' : 'clock'); ?>"></i>
                        <span><?php echo ucfirst($election_status['status']); ?></span>
                    </div>
                    <p class="status-message"><?php echo $election_status['message']; ?></p>
                </div>
                
                <div class="hero-buttons">
                    <?php if ($election_status['status'] == 'active'): ?>
                        <a href="vote.php" class="btn btn-primary">
                            <i class="fas fa-vote-yea"></i> Cast Your Vote Now
                        </a>
                    <?php elseif ($election_status['status'] == 'upcoming'): ?>
                        <a href="register.php" class="btn btn-secondary">
                            <i class="fas fa-user-plus"></i> Register to Vote
                        </a>
                    <?php endif; ?>
                    <a href="results_public.php" class="btn btn-outline">
                        <i class="fas fa-chart-bar"></i> View Results
                    </a>
                </div>
                
                <div class="stats">
                    <div class="stat-item">
                        <i class="fas fa-users"></i>
                        <div>
                            <h3>1,500+</h3>
                            <p>Registered Students</p>
                        </div>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-user-tie"></i>
                        <div>
                            <h3>24</h3>
                            <p>Candidates</p>
                        </div>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-vote-yea"></i>
                        <div>
                            <h3>6</h3>
                            <p>Positions</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="hero-image">
                <img src="https://cdn.pixabay.com/photo/2016/11/29/01/34/ballot-1867092_1280.jpg" alt="Voting Illustration">
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about-section">
        <div class="container">
            <h2 class="section-title">About The Election</h2>
            <div class="about-grid">
                <div class="about-card">
                    <div class="icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Secure Voting</h3>
                    <p>Military-grade encryption ensures your vote remains confidential and tamper-proof.</p>
                </div>
                <div class="about-card">
                    <div class="icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <h3>Verified Voters</h3>
                    <p>Only registered students with valid credentials can participate in the voting process.</p>
                </div>
                <div class="about-card">
                    <div class="icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Real-time Results</h3>
                    <p>Watch live election results as votes are cast (visible after voting period ends).</p>
                </div>
                <div class="about-card">
                    <div class="icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Mobile Friendly</h3>
                    <p>Vote from any device - desktop, tablet, or mobile phone with responsive design.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Positions Section -->
    <section id="positions" class="positions-section">
        <div class="container">
            <h2 class="section-title">Available Positions</h2>
            <p class="section-subtitle">Vote for candidates in these key student union positions</p>
            
            <div class="positions-grid">
                <?php
                $positions = getPositionsWithCandidates($pdo);
                foreach ($positions as $position):
                ?>
                <div class="position-card">
                    <div class="position-header">
                        <h3><?php echo htmlspecialchars($position['title']); ?></h3>
                        <p><?php echo $position['candidate_count']; ?> Candidates</p>
                    </div>
                    <div class="position-body">
                        <p><?php echo htmlspecialchars($position['description']); ?></p>
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
                            <span class="candidate-tag more">+<?php echo $position['candidate_count'] - 3; ?> more</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="position-footer">
                        <?php if ($election_status['status'] == 'active'): ?>
                            <a href="vote.php" class="btn-vote">Vote Now</a>
                        <?php else: ?>
                            <button class="btn-vote disabled" disabled>Voting <?php echo $election_status['status']; ?></button>
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
            <h2 class="section-title">Election Timeline</h2>
            <div class="timeline">
                <div class="timeline-item <?php echo $election_status['status'] == 'ended' ? 'completed' : ''; ?>">
                    <div class="timeline-date">Feb 15-28, 2024</div>
                    <div class="timeline-content">
                        <h3>Candidate Registration</h3>
                        <p>Potential candidates submit their nominations</p>
                    </div>
                </div>
                <div class="timeline-item <?php echo $election_status['status'] == 'ended' ? 'completed' : ($election_status['status'] == 'active' ? 'current' : ''); ?>">
                    <div class="timeline-date">Mar 1-10, 2024</div>
                    <div class="timeline-content">
                        <h3>Voting Period</h3>
                        <p>Registered students cast their votes online</p>
                    </div>
                </div>
                <div class="timeline-item <?php echo $election_status['status'] == 'ended' ? 'current' : ''; ?>">
                    <div class="timeline-date">Mar 11, 2024</div>
                    <div class="timeline-content">
                        <h3>Results Announcement</h3>
                        <p>Election results declared publicly</p>
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
                    <div class="logo">
                        <i class="fas fa-vote-yea"></i>
                        <span>StudentVote</span>
                    </div>
                    <p>Secure and transparent online voting system for student union elections.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
                <div class="footer-col">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="#about">About</a></li>
                        <li><a href="results_public.php">Results</a></li>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="register.php">Register</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Positions</h3>
                    <ul>
                        <?php foreach ($positions as $position): ?>
                        <li><a href="vote.php"><?php echo htmlspecialchars($position['title']); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Contact Us</h3>
                    <ul class="contact-info">
                        <li><i class="fas fa-envelope"></i> elections@studentunion.edu</li>
                        <li><i class="fas fa-phone"></i> (123) 456-7890</li>
                        <li><i class="fas fa-map-marker-alt"></i> Student Union Building, University Campus</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 Student Union Voting System. All rights reserved.</p>
                <p>Designed with <i class="fas fa-heart"></i> for transparent student democracy</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/script.js"></script>
    <script>
        // Mobile menu toggle
        document.querySelector('.menu-toggle').addEventListener('click', function() {
            document.querySelector('.nav-links').classList.toggle('active');
        });

        // Update election countdown
        <?php if ($election_status['status'] == 'active'): ?>
        function updateCountdown() {
            const endDate = new Date("<?php echo $election_status['end_date']; ?>").getTime();
            const now = new Date().getTime();
            const distance = endDate - now;
            
            if (distance < 0) {
                location.reload();
                return;
            }
            
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            document.querySelector('.status-message').textContent = 
                `Election ends in ${days}d ${hours}h ${minutes}m ${seconds}s`;
        }
        setInterval(updateCountdown, 1000);
        <?php endif; ?>
    </script>
</body>
</html>