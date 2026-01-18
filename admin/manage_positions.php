<?php
require_once '../includes/config.php';
requireAdmin();

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? 0;
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'title' => sanitize($_POST['title'] ?? ''),
        'description' => sanitize($_POST['description'] ?? ''),
        'max_candidates' => intval($_POST['max_candidates'] ?? 3),
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];
    
    if ($action === 'add') {
        $sql = "INSERT INTO positions (title, description, max_candidates, is_active) 
                VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([
            $data['title'],
            $data['description'],
            $data['max_candidates'],
            $data['is_active']
        ])) {
            $message = 'Position added successfully!';
            $action = 'list';
        } else {
            $error = 'Failed to add position.';
        }
    } elseif ($action === 'edit' && $id > 0) {
        $sql = "UPDATE positions SET title = ?, description = ?, max_candidates = ?, is_active = ? 
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([
            $data['title'],
            $data['description'],
            $data['max_candidates'],
            $data['is_active'],
            $id
        ])) {
            $message = 'Position updated successfully!';
            $action = 'list';
        } else {
            $error = 'Failed to update position.';
        }
    }
} elseif (isset($_GET['delete']) && $id > 0) {
    // Check if position has candidates
    $sql = "SELECT COUNT(*) as count FROM candidates WHERE position_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $candidate_count = $stmt->fetch()['count'];
    
    if ($candidate_count > 0) {
        $error = 'Cannot delete position with candidates. Remove candidates first.';
    } else {
        $sql = "DELETE FROM positions WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$id])) {
            $message = 'Position deleted successfully!';
        } else {
            $error = 'Failed to delete position.';
        }
    }
    $action = 'list';
}

// Get position for editing
$position = null;
if ($action === 'edit' && $id > 0) {
    $sql = "SELECT * FROM positions WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $position = $stmt->fetch();
    
    if (!$position) {
        $error = 'Position not found.';
        $action = 'list';
    }
}

// Get all positions
$sql = "SELECT p.*, 
               (SELECT COUNT(*) FROM candidates c WHERE c.position_id = p.id AND c.is_active = 1) as candidate_count,
               (SELECT COUNT(*) FROM votes v WHERE v.position_id = p.id) as vote_count
        FROM positions p 
        ORDER BY p.is_active DESC, p.title";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$positions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Positions - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .position-card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .position-card {
            background: white;
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: relative;
        }
        .position-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        .position-card-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 1.5rem;
            position: relative;
        }
        .position-card-header h3 {
            margin: 0;
            font-size: 1.2rem;
        }
        .position-status {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-active { background-color: rgba(255, 255, 255, 0.2); }
        .status-inactive { background-color: rgba(255, 255, 255, 0.1); }
        .position-card-body {
            padding: 1.5rem;
        }
        .position-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--gray-light);
        }
        .stat-item {
            text-align: center;
        }
        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }
        .stat-label {
            font-size: 0.8rem;
            color: var(--gray);
        }
        .position-card-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--gray-light);
            display: flex;
            gap: 10px;
        }
        .max-candidates {
            background-color: #f8fafc;
            padding: 1rem;
            border-radius: var(--radius);
            margin-top: 1rem;
            font-size: 0.9rem;
        }
        .drag-drop-area {
            border: 2px dashed var(--gray-light);
            border-radius: var(--radius);
            padding: 3rem;
            text-align: center;
            margin-top: 2rem;
            cursor: pointer;
            transition: var(--transition);
        }
        .drag-drop-area:hover {
            border-color: var(--primary);
            background-color: #f8fafc;
        }
        .drag-drop-area i {
            font-size: 3rem;
            color: var(--gray);
            margin-bottom: 1rem;
        }
        .reorder-handle {
            cursor: move;
            color: var(--gray);
            padding: 5px;
        }
        .reorder-handle:hover {
            color: var(--primary);
        }
        .sort-options {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        .sort-btn {
            padding: 8px 16px;
            background-color: white;
            border: 2px solid var(--gray-light);
            border-radius: var(--radius);
            cursor: pointer;
            transition: var(--transition);
        }
        .sort-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
        }
        .sort-btn.active {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }
    </style>
</head>
<body>
    <?php include 'includes/admin_header.php'; ?>

    <div class="admin-dashboard">
        <div class="container">
            <div class="dashboard-header">
                <div class="user-info">
                    <h2>Manage Positions</h2>
                    <p>Create and manage election positions</p>
                </div>
                <div class="user-actions">
                    <a href="dashboard.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                    <?php if ($action === 'list'): ?>
                    <a href="?action=add" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Position
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong>Success!</strong>
                    <p><?php echo htmlspecialchars($message); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <strong>Error</strong>
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($action === 'list'): ?>
            <!-- Position List -->
            <div class="sort-options">
                <button class="sort-btn active" onclick="sortPositions('all')">All Positions</button>
                <button class="sort-btn" onclick="sortPositions('active')">Active Only</button>
                <button class="sort-btn" onclick="sortPositions('inactive')">Inactive</button>
                <button class="sort-btn" onclick="sortPositions('candidates')">Most Candidates</button>
                <button class="sort-btn" onclick="sortPositions('votes')">Most Votes</button>
            </div>

            <div class="position-card-grid" id="positionsGrid">
                <?php if (empty($positions)): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 3rem; color: var(--gray);">
                    <i class="fas fa-briefcase" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                    No positions found. <a href="?action=add">Create the first position</a>
                </div>
                <?php else: ?>
                <?php foreach ($positions as $pos): ?>
                <div class="position-card" data-active="<?php echo $pos['is_active']; ?>" 
                     data-candidates="<?php echo $pos['candidate_count']; ?>"
                     data-votes="<?php echo $pos['vote_count']; ?>">
                    <div class="position-card-header">
                        <h3><?php echo htmlspecialchars($pos['title']); ?></h3>
                        <div class="position-status <?php echo $pos['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo $pos['is_active'] ? 'ACTIVE' : 'INACTIVE'; ?>
                        </div>
                    </div>
                    
                    <div class="position-card-body">
                        <?php if (!empty($pos['description'])): ?>
                        <p style="color: var(--gray); margin-bottom: 1rem;">
                            <?php echo htmlspecialchars(substr($pos['description'], 0, 100)); ?>
                            <?php if (strlen($pos['description']) > 100): ?>...<?php endif; ?>
                        </p>
                        <?php endif; ?>
                        
                        <div class="position-stats">
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $pos['candidate_count']; ?></div>
                                <div class="stat-label">Candidates</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $pos['vote_count']; ?></div>
                                <div class="stat-label">Votes</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $pos['max_candidates']; ?></div>
                                <div class="stat-label">Max</div>
                            </div>
                        </div>
                        
                        <div class="max-candidates">
                            <i class="fas fa-info-circle"></i>
                            Maximum <?php echo $pos['max_candidates']; ?> candidates allowed
                        </div>
                    </div>
                    
                    <div class="position-card-footer">
                        <a href="?action=edit&id=<?php echo $pos['id']; ?>" 
                           class="btn-action btn-edit" title="Edit">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="manage_candidates.php?position=<?php echo $pos['id']; ?>" 
                           class="btn-action btn-view" title="View Candidates">
                            <i class="fas fa-user-tie"></i> Candidates
                        </a>
                        <?php if ($pos['candidate_count'] == 0): ?>
                        <a href="?delete=1&id=<?php echo $pos['id']; ?>" 
                           class="btn-action btn-delete" 
                           title="Delete"
                           onclick="return confirm('Are you sure you want to delete this position?')">
                            <i class="fas fa-trash"></i>
                        </a>
                        <?php else: ?>
                        <span class="btn-action btn-delete disabled" title="Cannot delete - has candidates">
                            <i class="fas fa-trash"></i>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Drag & Drop Reordering -->
            <div class="drag-drop-area" onclick="toggleReorder()" id="reorderArea">
                <i class="fas fa-arrows-alt"></i>
                <h3>Drag & Drop to Reorder Positions</h3>
                <p>Click to enable position reordering</p>
                <div id="reorderInstructions" style="display: none; margin-top: 1rem;">
                    <p>Drag positions to reorder them. Click "Save Order" when done.</p>
                    <button class="btn btn-primary" onclick="savePositionOrder()">
                        <i class="fas fa-save"></i> Save Order
                    </button>
                    <button class="btn btn-outline" onclick="toggleReorder()">
                        Cancel
                    </button>
                </div>
            </div>

            <?php elseif ($action === 'add' || $action === 'edit'): ?>
            <!-- Add/Edit Position Form -->
            <div style="max-width: 600px; margin: 0 auto;">
                <div class="dashboard-card">
                    <h3>
                        <?php echo $action === 'add' ? 'Add New Position' : 'Edit Position'; ?>
                        <a href="?action=list" class="btn btn-outline" style="font-size: 0.9rem;">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </h3>
                    
                    <form method="POST" action="" id="positionForm">
                        <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label class="form-label">Position Title *</label>
                            <input type="text" 
                                   name="title" 
                                   class="form-control" 
                                   value="<?php echo $position ? htmlspecialchars($position['title']) : ''; ?>"
                                   required
                                   maxlength="100">
                            <p class="form-hint">e.g., President, Vice President, Secretary</p>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea name="description" 
                                      class="form-control" 
                                      rows="4"
                                      maxlength="500"><?php echo $position ? htmlspecialchars($position['description']) : ''; ?></textarea>
                            <p class="form-hint">Brief description of responsibilities (max 500 characters)</p>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Maximum Candidates *</label>
                            <select name="max_candidates" class="form-control form-select" required>
                                <option value="">Select Maximum</option>
                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                <option value="<?php echo $i; ?>"
                                    <?php echo ($position && $position['max_candidates'] == $i) ? 'selected' : ''; ?>>
                                    <?php echo $i; ?> candidate<?php echo $i > 1 ? 's' : ''; ?>
                                </option>
                                <?php endfor; ?>
                            </select>
                            <p class="form-hint">Maximum number of candidates allowed for this position</p>
                        </div>
                        
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" 
                                       name="is_active" 
                                       id="is_active" 
                                       class="form-check-input"
                                       <?php echo (!$position || $position['is_active']) ? 'checked' : ''; ?>>
                                <label for="is_active" class="form-check-label">
                                    Active Position
                                </label>
                            </div>
                            <p class="form-hint">Inactive positions won't be visible to voters</p>
                        </div>
                        
                        <div class="form-group" style="margin-top: 2rem;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> 
                                <?php echo $action === 'add' ? 'Create Position' : 'Update Position'; ?>
                            </button>
                            <a href="?action=list" class="btn btn-outline">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        // Sort positions
        function sortPositions(criteria) {
            const positionsGrid = document.getElementById('positionsGrid');
            const cards = Array.from(positionsGrid.querySelectorAll('.position-card'));
            
            // Update active button
            document.querySelectorAll('.sort-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            let sortedCards;
            
            switch(criteria) {
                case 'active':
                    sortedCards = cards.sort((a, b) => {
                        const aActive = parseInt(a.dataset.active);
                        const bActive = parseInt(b.dataset.active);
                        return bActive - aActive;
                    });
                    break;
                    
                case 'inactive':
                    sortedCards = cards.sort((a, b) => {
                        const aActive = parseInt(a.dataset.active);
                        const bActive = parseInt(b.dataset.active);
                        return aActive - bActive;
                    });
                    break;
                    
                case 'candidates':
                    sortedCards = cards.sort((a, b) => {
                        const aCandidates = parseInt(a.dataset.candidates);
                        const bCandidates = parseInt(b.dataset.candidates);
                        return bCandidates - aCandidates;
                    });
                    break;
                    
                case 'votes':
                    sortedCards = cards.sort((a, b) => {
                        const aVotes = parseInt(a.dataset.votes);
                        const bVotes = parseInt(b.dataset.votes);
                        return bVotes - aVotes;
                    });
                    break;
                    
                default:
                    // Default order (by creation)
                    sortedCards = cards;
            }
            
            // Reattach cards in new order
            positionsGrid.innerHTML = '';
            sortedCards.forEach(card => {
                positionsGrid.appendChild(card);
            });
        }
        
        // Drag & drop reordering
        let sortable = null;
        let isReorderMode = false;
        
        function toggleReorder() {
            const reorderArea = document.getElementById('reorderArea');
            const instructions = document.getElementById('reorderInstructions');
            const positionsGrid = document.getElementById('positionsGrid');
            
            if (!isReorderMode) {
                // Enable reordering
                isReorderMode = true;
                reorderArea.style.backgroundColor = '#f8fafc';
                reorderArea.style.borderColor = 'var(--primary)';
                instructions.style.display = 'block';
                
                // Initialize Sortable
                sortable = new Sortable(positionsGrid, {
                    animation: 150,
                    handle: '.reorder-handle',
                    onEnd: function() {
                        console.log('Position order changed');
                    }
                });
                
                // Add reorder handles to cards
                document.querySelectorAll('.position-card').forEach(card => {
                    const handle = document.createElement('div');
                    handle.className = 'reorder-handle';
                    handle.innerHTML = '<i class="fas fa-arrows-alt"></i>';
                    handle.style.position = 'absolute';
                    handle.style.top = '10px';
                    handle.style.left = '10px';
                    handle.style.zIndex = '10';
                    card.style.position = 'relative';
                    card.appendChild(handle);
                });
                
            } else {
                // Disable reordering
                isReorderMode = false;
                reorderArea.style.backgroundColor = '';
                reorderArea.style.borderColor = '';
                instructions.style.display = 'none';
                
                // Destroy Sortable
                if (sortable) {
                    sortable.destroy();
                    sortable = null;
                }
                
                // Remove reorder handles
                document.querySelectorAll('.reorder-handle').forEach(handle => {
                    handle.remove();
                });
            }
        }
        
        function savePositionOrder() {
            const positionsGrid = document.getElementById('positionsGrid');
            const positionIds = Array.from(positionsGrid.querySelectorAll('.position-card')).map(card => {
                // Extract position ID from edit link
                const editLink = card.querySelector('a[href*="action=edit"]');
                if (editLink) {
                    const match = editLink.href.match(/id=(\d+)/);
                    return match ? match[1] : null;
                }
                return null;
            }).filter(id => id !== null);
            
            // Send AJAX request to save order
            fetch('api/save_position_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ position_ids: positionIds })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Position order saved successfully!');
                    toggleReorder();
                } else {
                    alert('Error saving order: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error saving order. Please try again.');
                console.error('Error:', error);
            });
        }
        
        // Character counter for description
        const descriptionTextarea = document.querySelector('textarea[name="description"]');
        if (descriptionTextarea) {
            const counter = document.createElement('div');
            counter.className = 'form-hint';
            counter.style.textAlign = 'right';
            counter.style.marginTop = '5px';
            descriptionTextarea.parentNode.appendChild(counter);
            
            function updateCounter() {
                const length = descriptionTextarea.value.length;
                counter.textContent = `${length}/500 characters`;
                counter.style.color = length > 500 ? 'var(--danger)' : 'var(--gray)';
            }
            
            descriptionTextarea.addEventListener('input', updateCounter);
            updateCounter();
        }
        
        // Form validation
        document.getElementById('positionForm')?.addEventListener('submit', function(e) {
            const title = this.querySelector('input[name="title"]').value.trim();
            const maxCandidates = this.querySelector('select[name="max_candidates"]').value;
            
            if (!title) {
                e.preventDefault();
                alert('Please enter a position title.');
                return false;
            }
            
            if (!maxCandidates) {
                e.preventDefault();
                alert('Please select maximum number of candidates.');
                return false;
            }
            
            return true;
        });
        
        // Auto-generate slug from title
        const titleInput = document.querySelector('input[name="title"]');
        if (titleInput) {
            titleInput.addEventListener('input', function() {
                // In a real application, you might want to generate a slug
                console.log('Title changed:', this.value);
            });
        }
    </script>
</body>
</html>