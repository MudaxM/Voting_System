<?php
require_once '../includes/config.php';
requireAdmin();

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? 0;
$message = '';
$error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'position_id' => intval($_POST['position_id'] ?? 0),
        'student_id' => sanitize($_POST['student_id'] ?? ''),
        'full_name' => sanitize($_POST['full_name'] ?? ''),
        'department' => sanitize($_POST['department'] ?? ''),
        'year' => intval($_POST['year'] ?? 0),
        'manifesto' => sanitize($_POST['manifesto'] ?? ''),
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];
    
    // Handle file upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (in_array($_FILES['photo']['type'], $allowed_types) && 
            $_FILES['photo']['size'] <= $max_size) {
            
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $filename = 'candidate_' . time() . '_' . uniqid() . '.' . $ext;
            $upload_path = '../uploads/candidates/';
            
            if (!is_dir($upload_path)) {
                mkdir($upload_path, 0755, true);
            }
            
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path . $filename)) {
                $data['photo'] = $filename;
                
                // Resize image if needed
                resizeImage($upload_path . $filename, 400, 400);
            }
        }
    }
    
    if ($action === 'add') {
        // Check if student exists and is verified
        $sql = "SELECT id FROM users WHERE student_id = ? AND is_verified = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$data['student_id']]);
        
        if (!$stmt->fetch()) {
            $error = 'Student not found or not verified.';
        } else {
            // Check if already a candidate for this position
            $sql = "SELECT id FROM candidates WHERE student_id = ? AND position_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$data['student_id'], $data['position_id']]);
            
            if ($stmt->fetch()) {
                $error = 'Student is already a candidate for this position.';
            } else {
                // Insert candidate
                $sql = "INSERT INTO candidates (position_id, student_id, full_name, department, year, manifesto, photo, is_active) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                
                if ($stmt->execute([
                    $data['position_id'],
                    $data['student_id'],
                    $data['full_name'],
                    $data['department'],
                    $data['year'],
                    $data['manifesto'],
                    $data['photo'] ?? 'default.jpg',
                    $data['is_active']
                ])) {
                    $message = 'Candidate added successfully!';
                    $action = 'list';
                } else {
                    $error = 'Failed to add candidate.';
                }
            }
        }
    } elseif ($action === 'edit' && $id > 0) {
        // Update candidate
        $update_fields = [
            'position_id' => $data['position_id'],
            'student_id' => $data['student_id'],
            'full_name' => $data['full_name'],
            'department' => $data['department'],
            'year' => $data['year'],
            'manifesto' => $data['manifesto'],
            'is_active' => $data['is_active']
        ];
        
        if (isset($data['photo'])) {
            $update_fields['photo'] = $data['photo'];
        }
        
        $set_clause = implode(', ', array_map(function($field) {
            return "$field = ?";
        }, array_keys($update_fields)));
        
        $sql = "UPDATE candidates SET $set_clause WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        
        $params = array_values($update_fields);
        $params[] = $id;
        
        if ($stmt->execute($params)) {
            $message = 'Candidate updated successfully!';
            $action = 'list';
        } else {
            $error = 'Failed to update candidate.';
        }
    }
} elseif (isset($_GET['delete']) && $id > 0) {
    // Delete candidate
    $sql = "DELETE FROM candidates WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$id])) {
        $message = 'Candidate deleted successfully!';
    } else {
        $error = 'Failed to delete candidate.';
    }
    $action = 'list';
}

// Get candidate for editing
$candidate = null;
if ($action === 'edit' && $id > 0) {
    $sql = "SELECT * FROM candidates WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $candidate = $stmt->fetch();
    
    if (!$candidate) {
        $error = 'Candidate not found.';
        $action = 'list';
    }
}

// Get positions for dropdown
$sql = "SELECT * FROM positions WHERE is_active = 1 ORDER BY title";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$positions = $stmt->fetchAll();

// Get all candidates for listing
$sql = "SELECT c.*, p.title as position_name 
        FROM candidates c 
        LEFT JOIN positions p ON c.position_id = p.id 
        ORDER BY c.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$all_candidates = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Candidates - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .candidate-form-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .photo-upload {
            text-align: center;
            margin-bottom: 2rem;
        }
        .photo-preview {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            margin: 0 auto 1rem;
            overflow: hidden;
            border: 3px solid var(--gray-light);
            position: relative;
        }
        .photo-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .upload-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 10px;
            transform: translateY(100%);
            transition: transform 0.3s;
        }
        .photo-preview:hover .upload-overlay {
            transform: translateY(0);
        }
        .candidate-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .candidate-card {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.5rem;
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }
        .candidate-photo-small {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
        }
        .candidate-info h4 {
            margin: 0 0 5px 0;
        }
        .candidate-info p {
            margin: 0;
            color: var(--gray);
            font-size: 0.9rem;
        }
        .bulk-actions {
            background-color: #f8fafc;
            padding: 1rem;
            border-radius: var(--radius);
            margin-bottom: 1rem;
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }
        .search-box {
            flex-grow: 1;
            max-width: 400px;
        }
        .import-export {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            justify-content: flex-end;
        }
        .manifesto-editor {
            min-height: 200px;
            border: 1px solid var(--gray-light);
            border-radius: var(--radius);
            padding: 1rem;
        }
        .tab-container {
            margin-bottom: 2rem;
        }
        .tabs {
            display: flex;
            gap: 1px;
            background-color: var(--gray-light);
            border-radius: var(--radius) var(--radius) 0 0;
            overflow: hidden;
        }
        .tab {
            padding: 1rem 2rem;
            background-color: white;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: var(--transition);
        }
        .tab:hover {
            background-color: #f8fafc;
        }
        .tab.active {
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
    </style>
</head>
<body>
    <?php include 'includes/admin_header.php'; ?>

    <div class="admin-dashboard">
        <div class="container">
            <div class="dashboard-header">
                <div class="user-info">
                    <h2>Manage Candidates</h2>
                    <p>Add, edit, or remove election candidates</p>
                </div>
                <div class="user-actions">
                    <a href="dashboard.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                    <?php if ($action === 'list'): ?>
                    <a href="?action=add" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Candidate
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
            <!-- Candidate List -->
            <div class="candidate-stats">
                <div class="candidate-card">
                    <div style="background-color: #dbeafe; color: var(--info); width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div>
                        <h4><?php echo count($all_candidates); ?></h4>
                        <p>Total Candidates</p>
                    </div>
                </div>
                <?php
                $active_candidates = array_filter($all_candidates, function($c) {
                    return $c['is_active'] == 1;
                });
                ?>
                <div class="candidate-card">
                    <div style="background-color: #d1fae5; color: var(--success); width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <h4><?php echo count($active_candidates); ?></h4>
                        <p>Active Candidates</p>
                    </div>
                </div>
                <?php
                $total_votes = array_sum(array_column($all_candidates, 'votes'));
                ?>
                <div class="candidate-card">
                    <div style="background-color: #fef3c7; color: var(--warning); width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                        <i class="fas fa-vote-yea"></i>
                    </div>
                    <div>
                        <h4><?php echo number_format($total_votes); ?></h4>
                        <p>Total Votes Received</p>
                    </div>
                </div>
            </div>

            <div class="bulk-actions">
                <div class="search-box">
                    <input type="text" id="searchCandidates" class="form-control" placeholder="Search candidates...">
                </div>
                <select id="bulkAction" class="form-control" style="max-width: 200px;">
                    <option value="">Bulk Actions</option>
                    <option value="activate">Activate Selected</option>
                    <option value="deactivate">Deactivate Selected</option>
                    <option value="delete">Delete Selected</option>
                </select>
                <button class="btn btn-primary" onclick="applyBulkAction()">
                    Apply
                </button>
                <button class="btn btn-outline" onclick="selectAllCandidates()">
                    Select All
                </button>
            </div>

            <div class="table-container">
                <table class="data-table" id="candidatesTable">
                    <thead>
                        <tr>
                            <th width="50">
                                <input type="checkbox" id="selectAll">
                            </th>
                            <th>Photo</th>
                            <th>Name</th>
                            <th>Student ID</th>
                            <th>Position</th>
                            <th>Department</th>
                            <th>Votes</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($all_candidates)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 3rem; color: var(--gray);">
                                <i class="fas fa-user-slash" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                                No candidates found. <a href="?action=add">Add the first candidate</a>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($all_candidates as $candidate): ?>
                        <tr data-candidate-id="<?php echo $candidate['id']; ?>">
                            <td>
                                <input type="checkbox" class="candidate-checkbox" value="<?php echo $candidate['id']; ?>">
                            </td>
                            <td>
                                <?php if (!empty($candidate['photo']) && $candidate['photo'] !== 'default.jpg'): ?>
                                <img src="../uploads/candidates/<?php echo htmlspecialchars($candidate['photo']); ?>" 
                                     alt="<?php echo htmlspecialchars($candidate['full_name']); ?>"
                                     class="candidate-photo-small">
                                <?php else: ?>
                                <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; display: flex; align-items: center; justify-content: center; font-size: 1rem;">
                                    <?php echo strtoupper(substr($candidate['full_name'], 0, 1)); ?>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($candidate['full_name']); ?></strong>
                                <div style="font-size: 0.9rem; color: var(--gray);">Year <?php echo $candidate['year']; ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($candidate['student_id']); ?></td>
                            <td><?php echo htmlspecialchars($candidate['position_name']); ?></td>
                            <td><?php echo htmlspecialchars($candidate['department']); ?></td>
                            <td>
                                <strong style="color: var(--primary);"><?php echo number_format($candidate['votes']); ?></strong>
                            </td>
                            <td>
                                <?php if ($candidate['is_active']): ?>
                                <span class="status-badge status-active">Active</span>
                                <?php else: ?>
                                <span class="status-badge status-inactive">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="?action=edit&id=<?php echo $candidate['id']; ?>" 
                                       class="btn-action btn-edit" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?action=view&id=<?php echo $candidate['id']; ?>" 
                                       class="btn-action btn-view" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($candidate['votes'] == 0): ?>
                                    <a href="?delete=1&id=<?php echo $candidate['id']; ?>" 
                                       class="btn-action btn-delete" 
                                       title="Delete"
                                       onclick="return confirm('Are you sure you want to delete this candidate?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php else: ?>
                                    <span class="btn-action btn-delete disabled" title="Cannot delete - has votes">
                                        <i class="fas fa-trash"></i>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="import-export">
                <button class="btn btn-outline" onclick="exportCandidates()">
                    <i class="fas fa-download"></i> Export Candidates
                </button>
                <button class="btn btn-primary" onclick="importCandidates()">
                    <i class="fas fa-upload"></i> Import Candidates
                </button>
            </div>

            <?php elseif ($action === 'add' || $action === 'edit'): ?>
            <!-- Add/Edit Candidate Form -->
            <div class="candidate-form-container">
                <div class="dashboard-card">
                    <h3>
                        <?php echo $action === 'add' ? 'Add New Candidate' : 'Edit Candidate'; ?>
                        <a href="?action=list" class="btn btn-outline" style="font-size: 0.9rem;">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </h3>
                    
                    <form method="POST" action="" enctype="multipart/form-data" id="candidateForm">
                        <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                        <?php endif; ?>
                        
                        <div class="tab-container">
                            <div class="tabs">
                                <button type="button" class="tab active" onclick="showTab('basic')">Basic Info</button>
                                <button type="button" class="tab" onclick="showTab('manifesto')">Manifesto</button>
                                <button type="button" class="tab" onclick="showTab('photo')">Photo</button>
                            </div>
                            
                            <!-- Basic Info Tab -->
                            <div class="tab-content active" id="basicTab">
                                <div class="form-group">
                                    <label class="form-label">Position *</label>
                                    <select name="position_id" class="form-control form-select" required>
                                        <option value="">Select Position</option>
                                        <?php foreach ($positions as $position): ?>
                                        <option value="<?php echo $position['id']; ?>"
                                            <?php echo ($candidate && $candidate['position_id'] == $position['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($position['title']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Student ID *</label>
                                    <input type="text" 
                                           name="student_id" 
                                           class="form-control" 
                                           value="<?php echo $candidate ? htmlspecialchars($candidate['student_id']) : ''; ?>"
                                           required
                                           pattern="[A-Z]{1,2}[0-9]{6,8}"
                                           title="Format: 1-2 letters followed by 6-8 digits">
                                    <p class="form-hint">Enter the student's ID (e.g., S1234567)</p>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" 
                                           name="full_name" 
                                           class="form-control" 
                                           value="<?php echo $candidate ? htmlspecialchars($candidate['full_name']) : ''; ?>"
                                           required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Department *</label>
                                    <select name="department" class="form-control form-select" required>
                                        <option value="">Select Department</option>
                                        <option value="software Engineering" <?php echo ($candidate && $candidate['department'] == 'software engineering') ? 'selected' : ''; ?>>software Engineering</option>
                                        <option value="Electrical Engineering" <?php echo ($candidate && $candidate['department'] == 'Electrical Engineering') ? 'selected' : ''; ?>>Electrical Engineering</option>
                                        <option value="Mechanical Engineering" <?php echo ($candidate && $candidate['department'] == 'Mechanical Engineering') ? 'selected' : ''; ?>>Mechanical Engineering</option>
                                        <option value="Civil Engineering" <?php echo ($candidate && $candidate['department'] == 'Civil Engineering') ? 'selected' : ''; ?>>Civil Engineering</option>
                                        <option value="Information Technology" <?php echo ($candidate && $candidate['department'] == 'Information Technology') ? 'selected' : ''; ?>>Information Technology</option>
                                        <option value="copmputer science" <?php echo ($candidate && $candidate['department'] == 'copmputer science') ? 'selected' : ''; ?>>copmputer science</option>
                                        <option value="wateer supply" <?php echo ($candidate && $candidate['department'] == 'wateer supply') ? 'selected' : ''; ?>></option>
                                        <option value="architectural engineering" <?php echo ($candidate && $candidate['department'] == 'architectural engineering') ? 'selected' : ''; ?>>architectural engineering</option>
                                        <option value="hydrology and metrology" <?php echo ($candidate && $candidate['department'] == 'hydrology and metrology') ? 'selected' : ''; ?>>hydrology and metrology</option>
                                        <option value="Servie" <?php echo ($candidate && $candidate['department'] == 'Servie') ? 'selected' : ''; ?>>Servie</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Year *</label>
                                    <select name="year" class="form-control form-select" required>
                                        <option value="">Select Year</option>
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <option value="<?php echo $i; ?>"
                                            <?php echo ($candidate && $candidate['year'] == $i) ? 'selected' : ''; ?>>
                                            Year <?php echo $i; ?>
                                        </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <div class="form-check">
                                        <input type="checkbox" 
                                               name="is_active" 
                                               id="is_active" 
                                               class="form-check-input"
                                               <?php echo (!$candidate || $candidate['is_active']) ? 'checked' : ''; ?>>
                                        <label for="is_active" class="form-check-label">
                                            Active Candidate
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Manifesto Tab -->
                            <div class="tab-content" id="manifestoTab">
                                <div class="form-group">
                                    <label class="form-label">Manifesto</label>
                                    <div class="manifesto-editor" id="manifestoEditor" contenteditable="true">
                                        <?php echo $candidate ? htmlspecialchars($candidate['manifesto']) : ''; ?>
                                    </div>
                                    <textarea name="manifesto" id="manifestoTextarea" style="display: none;"></textarea>
                                    <p class="form-hint">You can use basic formatting. Maximum 2000 characters.</p>
                                </div>
                                
                                <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                                    <button type="button" class="btn btn-outline" onclick="formatText('bold')">
                                        <i class="fas fa-bold"></i> Bold
                                    </button>
                                    <button type="button" class="btn btn-outline" onclick="formatText('italic')">
                                        <i class="fas fa-italic"></i> Italic
                                    </button>
                                    <button type="button" class="btn btn-outline" onclick="formatText('underline')">
                                        <i class="fas fa-underline"></i> Underline
                                    </button>
                                    <button type="button" class="btn btn-outline" onclick="insertBulletList()">
                                        <i class="fas fa-list-ul"></i> List
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Photo Tab -->
                            <div class="tab-content" id="photoTab">
                                <div class="photo-upload">
                                    <div class="photo-preview" id="photoPreview">
                                        <?php if ($candidate && !empty($candidate['photo']) && $candidate['photo'] !== 'default.jpg'): ?>
                                        <img src="../uploads/candidates/<?php echo htmlspecialchars($candidate['photo']); ?>" 
                                             alt="Candidate Photo"
                                             id="previewImage">
                                        <?php else: ?>
                                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; display: flex; align-items: center; justify-content: center; font-size: 4rem;">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <?php endif; ?>
                                        <div class="upload-overlay">
                                            <i class="fas fa-camera"></i> Change Photo
                                        </div>
                                    </div>
                                    <input type="file" 
                                           name="photo" 
                                           id="photoInput" 
                                           accept="image/*"
                                           style="display: none;"
                                           onchange="previewPhoto(event)">
                                    <button type="button" class="btn btn-outline" onclick="document.getElementById('photoInput').click()">
                                        <i class="fas fa-upload"></i> Upload Photo
                                    </button>
                                    <p class="form-hint">Recommended: 400x400px, JPG/PNG format, max 2MB</p>
                                </div>
                                
                                <?php if ($candidate && !empty($candidate['photo']) && $candidate['photo'] !== 'default.jpg'): ?>
                                <div class="form-group">
                                    <div class="form-check">
                                        <input type="checkbox" 
                                               name="remove_photo" 
                                               id="remove_photo" 
                                               class="form-check-input">
                                        <label for="remove_photo" class="form-check-label">
                                            Remove current photo
                                        </label>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="form-group" style="margin-top: 2rem;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> 
                                <?php echo $action === 'add' ? 'Add Candidate' : 'Update Candidate'; ?>
                            </button>
                            <a href="?action=list" class="btn btn-outline">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Tab switching
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Deactivate all tab buttons
            document.querySelectorAll('.tab').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + 'Tab').classList.add('active');
            
            // Activate selected button
            event.target.classList.add('active');
        }
        
        // Photo preview
        function previewPhoto(event) {
            const input = event.target;
            const preview = document.getElementById('previewImage');
            const photoPreview = document.getElementById('photoPreview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    if (preview) {
                        preview.src = e.target.result;
                    } else {
                        // Create new image element
                        const img = document.createElement('img');
                        img.id = 'previewImage';
                        img.src = e.target.result;
                        img.style.width = '100%';
                        img.style.height = '100%';
                        img.style.objectFit = 'cover';
                        
                        // Clear existing content and add new image
                        photoPreview.innerHTML = '';
                        photoPreview.appendChild(img);
                        
                        // Add overlay back
                        const overlay = document.createElement('div');
                        overlay.className = 'upload-overlay';
                        overlay.innerHTML = '<i class="fas fa-camera"></i> Change Photo';
                        photoPreview.appendChild(overlay);
                    }
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Manifesto editor functions
        function formatText(command) {
            document.execCommand(command, false, null);
            updateManifestoTextarea();
        }
        
        function insertBulletList() {
            document.execCommand('insertUnorderedList', false, null);
            updateManifestoTextarea();
        }
        
        function updateManifestoTextarea() {
            document.getElementById('manifestoTextarea').value = 
                document.getElementById('manifestoEditor').innerHTML;
        }
        
        // Update textarea before form submission
        document.getElementById('candidateForm').addEventListener('submit', function() {
            document.getElementById('manifestoTextarea').value = 
                document.getElementById('manifestoEditor').innerHTML;
        });
        
        // Character counter for manifesto
        const manifestoEditor = document.getElementById('manifestoEditor');
        if (manifestoEditor) {
            manifestoEditor.addEventListener('input', function() {
                const text = this.innerText || this.textContent;
                if (text.length > 2000) {
                    this.innerHTML = text.substring(0, 2000);
                    alert('Manifesto is limited to 2000 characters.');
                }
            });
        }
        
        // Bulk actions for candidate list
        function selectAllCandidates() {
            const checkboxes = document.querySelectorAll('.candidate-checkbox');
            const selectAll = document.getElementById('selectAll');
            const isChecked = selectAll.checked;
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = !isChecked;
            });
            
            selectAll.checked = !isChecked;
        }
        
        function applyBulkAction() {
            const action = document.getElementById('bulkAction').value;
            const selectedIds = [];
            
            document.querySelectorAll('.candidate-checkbox:checked').forEach(checkbox => {
                selectedIds.push(checkbox.value);
            });
            
            if (selectedIds.length === 0) {
                alert('Please select at least one candidate.');
                return;
            }
            
            if (!action) {
                alert('Please select an action.');
                return;
            }
            
            if (confirm(`Are you sure you want to ${action} ${selectedIds.length} candidate(s)?`)) {
                // In production, make an AJAX call here
                alert(`Bulk ${action} action would be applied to selected candidates.`);
            }
        }
        
        // Search functionality
        document.getElementById('searchCandidates').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#candidatesTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
        
        // Export candidates
        function exportCandidates() {
            // In production, make an AJAX call to generate CSV/Excel
            alert('Export functionality would generate a CSV file with all candidates.');
        }
        
        // Import candidates
        function importCandidates() {
            // In production, show a modal for file upload
            alert('Import functionality would allow uploading a CSV file with candidates.');
        }
        
        // Auto-save draft
        let saveTimeout;
        function autoSaveDraft() {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(() => {
                const formData = new FormData(document.getElementById('candidateForm'));
                // In production, make an AJAX call to save draft
                console.log('Auto-saving draft...');
            }, 2000);
        }
        
        // Add auto-save to form inputs
        document.querySelectorAll('#candidateForm input, #candidateForm select, #candidateForm textarea').forEach(input => {
            input.addEventListener('input', autoSaveDraft);
            input.addEventListener('change', autoSaveDraft);
        });
    </script>
</body>
</html>