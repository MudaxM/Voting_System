<?php
require_once 'includes/config.php';
requireVoter();

$user = getCurrentUser($pdo);
$election_status = getElectionStatus($pdo);
$has_voted = hasUserVoted($pdo, $_SESSION['user_id']);

// Check if election is active
if ($election_status['status'] !== 'active') {
    $_SESSION['error'] = 'Voting is currently not active.';
    header('Location: index.php');
    exit();
}

// Check if user has already voted
if ($has_voted) {
    $_SESSION['info'] = 'You have already voted. You can view the results.';
    header('Location: results_public.php');
    exit();
}

// Get positions with candidates
$sql = "SELECT p.* FROM positions p WHERE p.is_active = 1 ORDER BY p.id";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$positions = $stmt->fetchAll();

// Get candidates for each position
foreach ($positions as &$position) {
    $sql = "SELECT c.*, u.department, u.year 
            FROM candidates c 
            LEFT JOIN users u ON c.student_id = u.student_id 
            WHERE c.position_id = ? AND c.is_active = 1 
            ORDER BY c.full_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$position['id']]);
    $position['candidates'] = $stmt->fetchAll();
}

// Handle vote submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        $voter_id = $_SESSION['user_id'];
        $votes = $_POST['votes'] ?? [];
        $success_count = 0;
        
        // Validate each vote
        foreach ($votes as $position_id => $candidate_id) {
            if (!empty($position_id) && !empty($candidate_id)) {
                // Check if candidate exists and is active
                $sql = "SELECT id FROM candidates WHERE id = ? AND position_id = ? AND is_active = 1";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$candidate_id, $position_id]);
                
                if ($stmt->fetch()) {
                    // Check if user hasn't already voted for this position
                    $sql = "SELECT id FROM votes WHERE voter_id = ? AND position_id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$voter_id, $position_id]);
                    
                    if (!$stmt->fetch()) {
                        // Insert vote
                        $sql = "INSERT INTO votes (voter_id, position_id, candidate_id, ip_address) 
                                VALUES (?, ?, ?, ?)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$voter_id, $position_id, $candidate_id, $_SERVER['REMOTE_ADDR']]);
                        
                        // Update candidate vote count
                        $sql = "UPDATE candidates SET votes = votes + 1 WHERE id = ?";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$candidate_id]);
                        
                        $success_count++;
                    }
                }
            }
        }
        
        // Update user's voted status
        if ($success_count > 0) {
            $sql = "UPDATE users SET has_voted = 1 WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$voter_id]);
            
            // Log the voting activity
            logActivity($pdo, $voter_id, 'vote_cast', 'Cast ' . $success_count . ' votes');
        }
        
        $pdo->commit();
        
        // Send confirmation email
        $message = "
            <h2>Voting Confirmation</h2>
            <p>Dear " . htmlspecialchars($user['full_name']) . ",</p>
            <p>Thank you for participating in the Student Union Elections 2024!</p>
            <p>You have successfully cast your vote for " . $success_count . " position(s).</p>
            <p><strong>Important:</strong> Your vote is anonymous and cannot be changed.</p>
            <p>You can view the election results after voting closes on " . 
               date('F j, Y, g:i a', strtotime($election_status['end_date'])) . ".</p>
            <p>Best regards,<br>Student Union Election Committee</p>
        ";
        
        sendEmail($user['email'], 'Voting Confirmation - Student Union Elections', $message);
        
        // Set success message and redirect
        $_SESSION['success'] = 'Thank you! Your vote has been submitted successfully.';
        header('Location: vote_success.php');
        exit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'An error occurred while submitting your vote. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cast Your Vote - Student Union Voting System</title>
    <link rel="stylesheet" href="Assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .voting-progress-bar {
            height: 6px;
            background-color: var(--gray-light);
            border-radius: 3px;
            margin-bottom: 2rem;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
            border-radius: 3px;
            transition: width 0.5s ease;
        }
        .voting-step {
            display: none;
            animation: fadeIn 0.5s ease;
        }
        .voting-step.active {
            display: block;
        }
        .step-navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--gray-light);
        }
        .candidate-option {
            display: flex;
            align-items: flex-start;
            padding: 1.5rem;
            border: 2px solid var(--gray-light);
            border-radius: var(--radius);
            cursor: pointer;
            transition: var(--transition);
            margin-bottom: 1rem;
            position: relative;
        }
        .candidate-option:hover {
            border-color: var(--primary);
            background-color: #f8fafc;
        }
        .candidate-option.selected {
            border-color: var(--primary);
            background-color: #e0e7ff;
        }
        .candidate-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }
        .checkmark {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 2px solid var(--gray);
            margin-right: 1rem;
            position: relative;
            flex-shrink: 0;
            margin-top: 5px;
        }
        .candidate-option.selected .checkmark {
            border-color: var(--primary);
            background-color: var(--primary);
        }
        .candidate-option.selected .checkmark::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: white;
        }
        .candidate-photo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 1.5rem;
            border: 3px solid var(--gray-light);
        }
        .candidate-details {
            flex-grow: 1;
        }
        .candidate-name {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 5px;
            color: var(--dark);
        }
        .candidate-info {
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        .candidate-manifesto {
            background-color: #f8fafc;
            padding: 1rem;
            border-radius: var(--radius);
            margin-top: 10px;
            font-size: 0.95rem;
            line-height: 1.5;
        }
        .candidate-manifesto h5 {
            color: var(--primary-dark);
            margin-bottom: 5px;
        }
        .vote-summary-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid var(--gray-light);
        }
        .vote-summary-item:last-child {
            border-bottom: none;
        }
        .summary-candidate {
            color: var(--gray);
            font-size: 0.9rem;
        }
        .confirmation-step {
            text-align: center;
            padding: 3rem;
        }
        .confirmation-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--success), #10b981);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
        }
        .confirmation-icon i {
            font-size: 3rem;
            color: white;
        }
        .voting-timer {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 1rem;
            border-radius: var(--radius);
            text-align: center;
            margin-bottom: 2rem;
        }
        .timer-display {
            font-size: 2rem;
            font-weight: 600;
            font-family: monospace;
            margin: 10px 0;
        }
        .timer-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        .no-candidates {
            text-align: center;
            padding: 3rem;
            color: var(--gray);
        }
        .no-candidates i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        .position-description {
            color: var(--gray);
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--gray-light);
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
                <a href="#help" onclick="showHelp()">Help</a>
                <a href="logout.php" class="btn-login">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
            <div class="menu-toggle">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </nav>

    <div class="dashboard">
        <div class="container">
            <!-- User Info Header -->
            <div class="dashboard-header">
                <div class="user-info">
                    <h2>Cast Your Vote</h2>
                    <p>
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($user['full_name']); ?> | 
                        <i class="fas fa-id-card"></i> <?php echo htmlspecialchars($user['student_id']); ?> | 
                        <i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($user['department']); ?> - Year <?php echo $user['year']; ?>
                    </p>
                </div>
                <div class="user-actions">
                    <a href="index.php" class="btn btn-outline">
                        <i class="fas fa-home"></i> Home
                    </a>
                    <a href="logout.php" class="btn btn-primary">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>

            <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <strong>Error</strong>
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Voting Timer -->
            <div class="voting-timer">
                <div class="timer-label">
                    <i class="fas fa-clock"></i> Voting Closes In
                </div>
                <div class="timer-display" id="countdownTimer">00:00:00</div>
                <div class="timer-label">
                    Make sure to submit your vote before time runs out!
                </div>
            </div>

            <!-- Voting Progress Bar -->
            <div class="voting-progress-bar">
                <div class="progress-fill" id="progressFill" style="width: 0%"></div>
            </div>

            <!-- Voting Steps -->
            <form method="POST" action="" id="votingForm">
                <?php $step = 1; ?>
                <?php foreach ($positions as $position): ?>
                <div class="voting-step" id="step<?php echo $step; ?>" <?php echo $step === 1 ? 'class="active"' : ''; ?>>
                    <div class="position-card">
                        <div class="position-header">
                            <h3>
                                <?php echo htmlspecialchars($position['title']); ?>
                                <span style="font-size: 0.9rem; opacity: 0.9;">(Step <?php echo $step; ?> of <?php echo count($positions); ?>)</span>
                            </h3>
                        </div>
                        
                        <div class="position-body">
                            <?php if (!empty($position['description'])): ?>
                            <div class="position-description">
                                <?php echo htmlspecialchars($position['description']); ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (empty($position['candidates'])): ?>
                            <div class="no-candidates">
                                <i class="fas fa-user-slash"></i>
                                <h4>No Candidates Available</h4>
                                <p>There are no candidates running for this position.</p>
                            </div>
                            <?php else: ?>
                            <div class="candidates-list">
                                <?php foreach ($position['candidates'] as $candidate): ?>
                                <label class="candidate-option" data-candidate-id="<?php echo $candidate['id']; ?>">
                                    <input type="radio" 
                                           name="votes[<?php echo $position['id']; ?>]" 
                                           value="<?php echo $candidate['id']; ?>"
                                           class="candidate-radio"
                                           data-position="<?php echo $position['id']; ?>"
                                           required>
                                    <div class="checkmark"></div>
                                    
                                    <?php if (!empty($candidate['photo']) && $candidate['photo'] !== 'default.jpg'): ?>
                                    <img src="uploads/candidates/<?php echo htmlspecialchars($candidate['photo']); ?>" 
                                         alt="<?php echo htmlspecialchars($candidate['full_name']); ?>"
                                         class="candidate-photo">
                                    <?php else: ?>
                                    <div class="candidate-photo" style="background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; display: flex; align-items: center; justify-content: center; font-size: 2rem;">
                                        <?php echo strtoupper(substr($candidate['full_name'], 0, 1)); ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="candidate-details">
                                        <div class="candidate-name">
                                            <?php echo htmlspecialchars($candidate['full_name']); ?>
                                        </div>
                                        <div class="candidate-info">
                                            <i class="fas fa-graduation-cap"></i> 
                                            <?php echo htmlspecialchars($candidate['department']); ?> - Year <?php echo $candidate['year']; ?>
                                        </div>
                                        
                                        <?php if (!empty($candidate['manifesto'])): ?>
                                        <div class="candidate-manifesto">
                                            <h5><i class="fas fa-bullhorn"></i> Manifesto</h5>
                                            <?php echo nl2br(htmlspecialchars(substr($candidate['manifesto'], 0, 300))); ?>
                                            <?php if (strlen($candidate['manifesto']) > 300): ?>
                                            <span style="color: var(--primary); cursor: pointer;" onclick="showFullManifesto(<?php echo $candidate['id']; ?>)">... Read more</span>
                                            <div id="manifesto-full-<?php echo $candidate['id']; ?>" style="display: none;">
                                                <?php echo nl2br(htmlspecialchars(substr($candidate['manifesto'], 300))); ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </label>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php $step++; ?>
                <?php endforeach; ?>
                
                <!-- Summary Step -->
                <div class="voting-step" id="stepSummary">
                    <div class="confirmation-step">
                        <div class="confirmation-icon">
                            <i class="fas fa-check"></i>
                        </div>
                        <h2>Review Your Votes</h2>
                        <p class="section-subtitle">Please review your selections before submitting</p>
                        
                        <div class="vote-summary" id="voteSummary">
                            <!-- Vote summary will be populated by JavaScript -->
                        </div>
                        
                        <div class="alert alert-info" style="margin: 2rem 0;">
                            <i class="fas fa-info-circle"></i>
                            <div>
                                <strong>Important Information</strong>
                                <p>Once you submit your vote, it cannot be changed. Please make sure your selections are correct.</p>
                                <p>Your vote is anonymous and confidential.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Submission Step -->
                <div class="voting-step" id="stepSubmit">
                    <div class="confirmation-step">
                        <div class="confirmation-icon" style="background: linear-gradient(135deg, var(--warning), #f59e0b);">
                            <i class="fas fa-exclamation"></i>
                        </div>
                        <h2>Final Confirmation</h2>
                        <p class="section-subtitle">Are you ready to submit your vote?</p>
                        
                        <div class="alert alert-warning" style="max-width: 600px; margin: 0 auto 2rem;">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div>
                                <strong>Warning: This action cannot be undone!</strong>
                                <p>Once you submit your vote, you cannot change your selections or vote again.</p>
                                <p>Your vote will be recorded anonymously and counted in the final results.</p>
                            </div>
                        </div>
                        
                        <div class="form-group" style="text-align: center;">
                            <div class="form-check" style="display: inline-block;">
                                <input type="checkbox" 
                                       id="confirmSubmission" 
                                       name="confirm_submission" 
                                       class="form-check-input" 
                                       required>
                                <label for="confirmSubmission" class="form-check-label">
                                    I confirm that my votes are final and I understand they cannot be changed
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Step Navigation -->
                <div class="step-navigation">
                    <button type="button" class="btn btn-outline" id="prevBtn" onclick="prevStep()" style="display: none;">
                        <i class="fas fa-arrow-left"></i> Previous
                    </button>
                    
                    <div style="text-align: center;">
                        <span id="stepIndicator">Step 1 of <?php echo count($positions) + 2; ?></span>
                    </div>
                    
                    <button type="button" class="btn btn-primary" id="nextBtn" onclick="nextStep()">
                        Next <i class="fas fa-arrow-right"></i>
                    </button>
                    
                    <button type="submit" class="btn btn-success" id="submitBtn" style="display: none;">
                        <i class="fas fa-paper-plane"></i> Submit Vote
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Help Modal -->
    <div id="helpModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Voting Instructions</h3>
                <button class="modal-close" onclick="closeModal('helpModal')">&times;</button>
            </div>
            <div class="modal-body">
                <h4>How to Vote:</h4>
                <ol>
                    <li>Review each candidate's information and manifesto</li>
                    <li>Select one candidate for each position by clicking on their card</li>
                    <li>Use the "Next" button to proceed to the next position</li>
                    <li>Review your selections on the summary page</li>
                    <li>Confirm your votes on the final page</li>
                    <li>Click "Submit Vote" to cast your ballot</li>
                </ol>
                
                <h4>Important Notes:</h4>
                <ul>
                    <li>You can only vote once for each position</li>
                    <li>You cannot change your votes after submission</li>
                    <li>Your vote is anonymous and confidential</li>
                    <li>Make sure to submit before the voting deadline</li>
                    <li>If you encounter issues, contact the election committee</li>
                </ul>
                
                <div class="alert alert-info" style="margin-top: 1rem;">
                    <i class="fas fa-clock"></i>
                    <div>
                        <strong>Time Remaining:</strong>
                        <p id="modalTimer">Loading...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="closeModal('helpModal')">Got It!</button>
            </div>
        </div>
    </div>

    <script>
        // Initialize variables
        const totalSteps = <?php echo count($positions) + 2; ?>;
        let currentStep = 1;
        const votes = {};
        
        // Update progress bar
        function updateProgress() {
            const progress = ((currentStep - 1) / (totalSteps - 1)) * 100;
            document.getElementById('progressFill').style.width = progress + '%';
            document.getElementById('stepIndicator').textContent = `Step ${currentStep} of ${totalSteps}`;
        }
        
        // Show specific step
        function showStep(step) {
            // Hide all steps
            document.querySelectorAll('.voting-step').forEach(s => {
                s.classList.remove('active');
            });
            
            // Show current step
            document.getElementById(`step${step}`).classList.add('active');
            
            // Update navigation buttons
            document.getElementById('prevBtn').style.display = step === 1 ? 'none' : 'inline-flex';
            document.getElementById('nextBtn').style.display = step >= totalSteps - 1 ? 'none' : 'inline-flex';
            document.getElementById('submitBtn').style.display = step === totalSteps ? 'inline-flex' : 'none';
            
            // Update step indicator
            document.getElementById('stepIndicator').textContent = `Step ${step} of ${totalSteps}`;
            
            // Update progress bar
            const progress = ((step - 1) / (totalSteps - 1)) * 100;
            document.getElementById('progressFill').style.width = progress + '%';
            
            // If on summary step, update summary
            if (step === totalSteps - 1) {
                updateVoteSummary();
            }
            
            // If on submit step, validate all votes
            if (step === totalSteps) {
                validateAllVotes();
            }
            
            currentStep = step;
        }
        
        // Next step
        function nextStep() {
            // Validate current step
            if (currentStep <= <?php echo count($positions); ?>) {
                const positionId = <?php echo $positions[$currentStep - 1]['id']; ?>;
                const selectedCandidate = document.querySelector(`input[name="votes[${positionId}]"]:checked`);
                
                if (!selectedCandidate) {
                    alert('Please select a candidate for this position before proceeding.');
                    return;
                }
                
                // Store vote
                votes[positionId] = selectedCandidate.value;
            }
            
            if (currentStep < totalSteps) {
                showStep(currentStep + 1);
            }
        }
        
        // Previous step
        function prevStep() {
            if (currentStep > 1) {
                showStep(currentStep - 1);
            }
        }
        
        // Update vote summary
        function updateVoteSummary() {
            const summaryDiv = document.getElementById('voteSummary');
            let summaryHTML = '<div style="max-width: 600px; margin: 0 auto;">';
            
            <?php foreach ($positions as $position): ?>
            const positionId = <?php echo $position['id']; ?>;
            const selectedCandidateId = votes[positionId];
            
            if (selectedCandidateId) {
                const candidateOption = document.querySelector(`.candidate-option[data-candidate-id="${selectedCandidateId}"]`);
                if (candidateOption) {
                    const candidateName = candidateOption.querySelector('.candidate-name').textContent;
                    const candidateInfo = candidateOption.querySelector('.candidate-info').textContent;
                    
                    summaryHTML += `
                        <div class="vote-summary-item">
                            <div>
                                <strong><?php echo htmlspecialchars($position['title']); ?></strong>
                                <div style="color: var(--gray); font-size: 0.9rem;">${candidateName} - ${candidateInfo}</div>
                            </div>
                            <button type="button" class="btn btn-outline" onclick="goToStep(<?php echo array_search($position, $positions) + 1; ?>)">
                                Change
                            </button>
                        </div>
                    `;
                }
            }
            <?php endforeach; ?>
            
            summaryHTML += '</div>';
            summaryDiv.innerHTML = summaryHTML;
        }
        
        // Go to specific step
        function goToStep(step) {
            showStep(step);
        }
        
        // Validate all votes are complete
        function validateAllVotes() {
            const requiredPositions = <?php echo count($positions); ?>;
            const votedPositions = Object.keys(votes).length;
            
            if (votedPositions < requiredPositions) {
                alert(`You have only voted for ${votedPositions} out of ${requiredPositions} positions. Please complete all votes.`);
                goToStep(1);
            }
        }
        
        // Countdown timer
        function updateCountdown() {
            const endDate = new Date("<?php echo $election_status['end_date']; ?>").getTime();
            const now = new Date().getTime();
            const distance = endDate - now;
            
            if (distance < 0) {
                document.getElementById('countdownTimer').innerHTML = "VOTING ENDED";
                document.getElementById('modalTimer').innerHTML = "Voting has ended";
                return;
            }
            
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            const timerString = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            document.getElementById('countdownTimer').innerHTML = timerString;
            document.getElementById('modalTimer').innerHTML = `${hours}h ${minutes}m ${seconds}s remaining`;
        }
        
        // Initialize timer
        setInterval(updateCountdown, 1000);
        updateCountdown();
        
        // Candidate selection
        document.querySelectorAll('.candidate-option').forEach(option => {
            option.addEventListener('click', function(e) {
                if (e.target.type === 'radio' || e.target.type === 'checkbox') return;
                
                const radio = this.querySelector('input[type="radio"]');
                if (radio) {
                    radio.checked = true;
                    
                    // Update visual selection
                    document.querySelectorAll('.candidate-option').forEach(opt => {
                        opt.classList.remove('selected');
                    });
                    this.classList.add('selected');
                    
                    // Store vote
                    const positionId = radio.getAttribute('data-position');
                    votes[positionId] = radio.value;
                }
            });
        });
        
        // Show full manifesto
        function showFullManifesto(candidateId) {
            const fullDiv = document.getElementById(`manifesto-full-${candidateId}`);
            if (fullDiv) {
                fullDiv.style.display = fullDiv.style.display === 'none' ? 'block' : 'none';
            }
        }
        
        // Modal functions
        function showHelp() {
            event.preventDefault();
            document.getElementById('helpModal').style.display = 'flex';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Form submission confirmation
        document.getElementById('votingForm').addEventListener('submit', function(e) {
            if (!document.getElementById('confirmSubmission').checked) {
                e.preventDefault();
                alert('Please confirm that you understand your votes cannot be changed.');
                return false;
            }
            
            const confirmation = confirm('Are you absolutely sure you want to submit your vote? This action cannot be undone.');
            if (!confirmation) {
                e.preventDefault();
                return false;
            }
            
            // Show loading state
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
            document.getElementById('submitBtn').disabled = true;
            
            return true;
        });
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Pre-select if returning to a step
            <?php foreach ($positions as $position): ?>
            const positionId = <?php echo $position['id']; ?>;
            if (votes[positionId]) {
                const radio = document.querySelector(`input[name="votes[${positionId}]"][value="${votes[positionId]}"]`);
                if (radio) {
                    radio.checked = true;
                    radio.closest('.candidate-option').classList.add('selected');
                }
            }
            <?php endforeach; ?>
            
            // Mobile menu toggle
            document.querySelector('.menu-toggle').addEventListener('click', function() {
                document.querySelector('.nav-links').classList.toggle('active');
            });
        });
        
        // Auto-save progress (every 30 seconds)
        setInterval(function() {
            localStorage.setItem('vote_progress', JSON.stringify({
                votes: votes,
                currentStep: currentStep,
                timestamp: new Date().getTime()
            }));
        }, 30000);
        
        // Load saved progress
        const savedProgress = localStorage.getItem('vote_progress');
        if (savedProgress) {
            try {
                const progress = JSON.parse(savedProgress);
                const oneHour = 60 * 60 * 1000;
                
                if (progress.timestamp && (new Date().getTime() - progress.timestamp) < oneHour) {
                    Object.assign(votes, progress.votes || {});
                    if (progress.currentStep) {
                        showStep(progress.currentStep);
                    }
                }
            } catch (e) {
                console.log('Could not load saved progress');
            }
        }
        
        // Clear saved progress on successful submission
        document.getElementById('votingForm').addEventListener('submit', function() {
            localStorage.removeItem('vote_progress');
        });
        
        // Warn before leaving page
        window.addEventListener('beforeunload', function(e) {
            if (Object.keys(votes).length > 0) {
                e.preventDefault();
                e.returnValue = 'You have unsaved votes. Are you sure you want to leave?';
                return e.returnValue;
            }
        });
    </script>
</body>
</html>