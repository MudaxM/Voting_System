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
        'manifesto' => $_POST['manifesto'] ?? '',
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];

    // Handle file upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (in_array($_FILES['photo']['type'], $allowed_types) && $_FILES['photo']['size'] <= $max_size) {
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $filename = 'candidate_' . time() . '_' . uniqid() . '.' . $ext;
            $upload_path = '../uploads/candidates/';

            if (!is_dir($upload_path))
                mkdir($upload_path, 0755, true);

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path . $filename)) {
                $data['photo'] = $filename;
                // Proactively cleanup old photo if editing
                if ($action === 'edit' && $id > 0) {
                    $old = $pdo->query("SELECT photo FROM candidates WHERE id = $id")->fetchColumn();
                    if ($old && $old != 'default.jpg' && file_exists($upload_path . $old))
                        unlink($upload_path . $old);
                }
            }
        }
    }

    try {
        if ($action === 'add') {
            $sql = "INSERT INTO candidates (position_id, student_id, full_name, department, year, manifesto, photo, is_active) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$data['position_id'], $data['student_id'], $data['full_name'], $data['department'], $data['year'], $data['manifesto'], $data['photo'] ?? 'default.jpg', $data['is_active']])) {
                $message = 'Candidate profile created successfully!';
                $action = 'list';
            }
        } elseif ($action === 'edit' && $id > 0) {
            $update_fields = ['position_id', 'student_id', 'full_name', 'department', 'year', 'manifesto', 'is_active'];
            $params = [$data['position_id'], $data['student_id'], $data['full_name'], $data['department'], $data['year'], $data['manifesto'], $data['is_active']];
            if (isset($data['photo'])) {
                $update_fields[] = 'photo';
                $params[] = $data['photo'];
            }
            $set_clause = implode(', ', array_map(fn($f) => "$f = ?", $update_fields));
            $params[] = $id;
            if ($pdo->prepare("UPDATE candidates SET $set_clause WHERE id = ?")->execute($params)) {
                $message = 'Candidate profile updated successfully!';
                $action = 'list';
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
} elseif (isset($_GET['delete']) && $id > 0) {
    if ($pdo->prepare("DELETE FROM candidates WHERE id = ?")->execute([$id]))
        $message = 'Candidate removed.';
    $action = 'list';
}

$positions = $pdo->query("SELECT * FROM positions WHERE is_active = 1 ORDER BY title")->fetchAll();
$all_candidates = $pdo->query("SELECT c.*, p.title as position_name FROM candidates c LEFT JOIN positions p ON c.position_id = p.id ORDER BY c.votes DESC")->fetchAll();
$candidate = ($action === 'edit' && $id > 0) ? $pdo->query("SELECT * FROM candidates WHERE id = $id")->fetch() : null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidates | Admin Panel</title>
    <link rel="stylesheet" href="../Assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="admin-body">
    <?php include 'includes/admin_header.php'; ?>

    <div class="dashboard-header"
        style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
        <div class="user-info">
            <p>Manage candidate profiles, photos, and manifestos</p>
        </div>
        <div class="user-actions" style="display: flex; gap: 10px;">
            <a href="dashboard.php" class="btn btn-outline" style="background: white;">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            <?php if ($action === 'list'): ?>
                <a href="?action=add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> New Candidate
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"
            style="background: #d1fae5; border-left: 5px solid #10b981; color: #065f46; padding: 15px; border-radius: 12px; margin-bottom: 25px;">
            <i class="fas fa-check-circle"></i> <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <?php if ($action === 'list'): ?>
        <div class="dashboard-card" style="padding: 0; overflow: hidden;">
            <div
                style="padding: 25px; border-bottom: 1px solid #f1f1f4; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0;"><i class="fas fa-user-tie" style="color: var(--accent);"></i> Candidates Registry
                </h3>
            </div>
            <div style="overflow-x: auto;">
                <table class="data-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8fafc; text-align: left;">
                            <th style="padding: 15px 25px; color: #7e8299; font-size: 0.85rem; text-transform: uppercase;">
                                Profile</th>
                            <th style="padding: 15px 25px; color: #7e8299; font-size: 0.85rem; text-transform: uppercase;">
                                Position</th>
                            <th style="padding: 15px 25px; color: #7e8299; font-size: 0.85rem; text-transform: uppercase;">
                                Department</th>
                            <th
                                style="padding: 15px 25px; color: #7e8299; font-size: 0.85rem; text-transform: uppercase; text-align: center;">
                                Votes</th>
                            <th
                                style="padding: 15px 25px; color: #7e8299; font-size: 0.85rem; text-transform: uppercase; text-align: right;">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_candidates as $c): ?>
                            <tr style="border-bottom: 1px solid #f1f1f4;" onmouseover="this.style.background='#fcfcfd'"
                                onmouseout="this.style.background='white'">
                                <td style="padding: 15px 25px;">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <img src="../uploads/candidates/<?php echo $c['photo']; ?>"
                                            style="width: 45px; height: 45px; border-radius: 12px; object-fit: cover; border: 2px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                                        <div>
                                            <div style="font-weight: 700; color: #181c32;">
                                                <?php echo htmlspecialchars($c['full_name']); ?></div>
                                            <div style="font-size: 0.75rem; color: #7e8299;">ID:
                                                <?php echo htmlspecialchars($c['student_id']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding: 15px 25px;">
                                    <span
                                        style="background: #e1e9ff; color: #4361ee; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700;">
                                        <?php echo htmlspecialchars($c['position_name']); ?>
                                    </span>
                                </td>
                                <td style="padding: 15px 25px; color: #5e6278; font-size: 0.9rem;">
                                    <?php echo htmlspecialchars($c['department']); ?></td>
                                <td style="padding: 15px 25px; text-align: center;">
                                    <div style="font-weight: 800; font-size: 1.1rem; color: var(--accent);">
                                        <?php echo number_format($c['votes']); ?></div>
                                </td>
                                <td style="padding: 15px 25px; text-align: right;">
                                    <div style="display: flex; justify-content: flex-end; gap: 8px;">
                                        <a href="?action=edit&id=<?php echo $c['id']; ?>" class="sidebar-toggle"
                                            style="background: #f3f6f9; color: #4361ee; width: 32px; height: 32px; text-decoration: none;"><i
                                                class="fas fa-edit"></i></a>
                                        <a href="?delete=1&id=<?php echo $c['id']; ?>" onclick="return confirm('Delete?')"
                                            class="sidebar-toggle"
                                            style="background: #ffe2e5; color: #f64e60; width: 32px; height: 32px; text-decoration: none;"><i
                                                class="fas fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="dashboard-card" style="max-width: 900px;">
            <h3><?php echo $action === 'add' ? 'Add New Candidate' : 'Edit Candidate Profile'; ?></h3>
            <form method="POST" enctype="multipart/form-data">
                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 40px;">
                    <div>
                        <div style="background: #f8fafc; padding: 30px; border-radius: 20px; text-align: center;">
                            <img id="preview"
                                src="../uploads/candidates/<?php echo $candidate['photo'] ?? 'default.jpg'; ?>"
                                style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 5px solid white; box-shadow: 0 10px 20px rgba(0,0,0,0.1); margin-bottom: 20px;">
                            <label class="btn btn-outline" style="font-size: 0.8rem; cursor: pointer; display: block;">
                                <i class="fas fa-camera"></i> Change Photo
                                <input type="file" name="photo" style="display:none"
                                    onchange="document.getElementById('preview').src = window.URL.createObjectURL(this.files[0])">
                            </label>
                        </div>
                    </div>
                    <div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="full_name" class="form-control"
                                    value="<?php echo $candidate['full_name'] ?? ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Student ID</label>
                                <input type="text" name="student_id" class="form-control"
                                    value="<?php echo $candidate['student_id'] ?? ''; ?>" required>
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label class="form-label">Position</label>
                                <select name="position_id" class="form-control" required>
                                    <?php foreach ($positions as $p): ?>
                                        <option value="<?php echo $p['id']; ?>" <?php echo (isset($candidate['position_id']) && $candidate['position_id'] == $p['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($p['title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Department</label>
                                <input type="text" name="department" class="form-control"
                                    value="<?php echo $candidate['department'] ?? ''; ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Manifesto / Campaign Message</label>
                            <textarea name="manifesto" class="form-control"
                                style="height: 150px; resize: none;"><?php echo $candidate['manifesto'] ?? ''; ?></textarea>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" id="c_active" class="form-check-input" <?php echo ($candidate['is_active'] ?? 1) ? 'checked' : ''; ?>>
                                <label for="c_active" class="form-check-label">Candidate is Active</label>
                            </div>
                            <button type="submit" class="btn btn-primary" style="padding: 12px 50px;"><i
                                    class="fas fa-save"></i> Save Profile</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    <?php endif; ?>

    </div> <!-- Close admin-content -->
    </div> <!-- Close admin-main -->
</body>

</html>