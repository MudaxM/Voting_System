<?php
require_once '../includes/config.php';
requireAdmin();

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? 0;
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $max_candidates = intval($_POST['max_candidates'] ?? 1);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    try {
        if ($action === 'add') {
            $stmt = $pdo->prepare("INSERT INTO positions (title, description, max_candidates, is_active) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$title, $description, $max_candidates, $is_active])) {
                $message = 'New position successfully registered.';
                $action = 'list';
            }
        } elseif ($action === 'edit' && $id > 0) {
            $stmt = $pdo->prepare("UPDATE positions SET title = ?, description = ?, max_candidates = ?, is_active = ? WHERE id = ?");
            if ($stmt->execute([$title, $description, $max_candidates, $is_active, $id])) {
                $message = 'Position configuration updated.';
                $action = 'list';
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
} elseif (isset($_GET['delete']) && $id > 0) {
    $has_c = $pdo->query("SELECT COUNT(*) FROM candidates WHERE position_id = $id")->fetchColumn();
    if ($has_c > 0)
        $error = 'Cannot delete a position that has active candidates.';
    else if ($pdo->prepare("DELETE FROM positions WHERE id = ?")->execute([$id]))
        $message = 'Position removed.';
    $action = 'list';
}

$positions = $pdo->query("SELECT p.*, (SELECT COUNT(*) FROM candidates c WHERE c.position_id = p.id) as c_count, (SELECT COUNT(*) FROM votes v WHERE v.position_id = p.id) as v_count FROM positions p ORDER BY sort_order ASC")->fetchAll();
$position = ($action === 'edit' && $id > 0) ? $pdo->query("SELECT * FROM positions WHERE id = $id")->fetch() : null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Positions | Admin Panel</title>
    <link rel="stylesheet" href="../Assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
</head>

<body class="admin-body">
    <?php include 'includes/admin_header.php'; ?>

    <div class="dashboard-header"
        style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
        <div class="user-info">
            <p>Define election chairs, quotas, and voting rules for each category</p>
        </div>
        <div class="user-actions" style="display: flex; gap: 10px;">
            <a href="dashboard.php" class="btn btn-outline" style="background: white;">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            <?php if ($action === 'list'): ?>
                <a href="?action=add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> New Position
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
        <div id="position-list"
            style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px;">
            <?php foreach ($positions as $p): ?>
                <div class="dashboard-card" data-id="<?php echo $p['id']; ?>" style="padding: 0; position: relative;">
                    <div
                        style="background: <?php echo $p['is_active'] ? 'var(--accent)' : '#6c7293'; ?>; height: 5px; border-radius: 20px 20px 0 0;">
                    </div>
                    <div style="padding: 25px;">
                        <div
                            style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                            <h3 style="margin: 0; font-size: 1.1rem;"><?php echo htmlspecialchars($p['title']); ?></h3>
                            <div class="reorder-handle" style="cursor: move; color: #ccc;"><i class="fas fa-grip-vertical"></i>
                            </div>
                        </div>
                        <p style="font-size: 0.85rem; color: #7e8299; min-height: 40px; line-height: 1.5; margin-bottom: 20px;">
                            <?php echo $p['description'] ?: 'No description provided for this role.'; ?>
                        </p>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px;">
                            <div style="background: #f8fafc; padding: 12px; border-radius: 10px; text-align: center;">
                                <div style="font-size: 1.25rem; font-weight: 800; color: #181c32;"><?php echo $p['c_count']; ?>
                                </div>
                                <div style="font-size: 0.7rem; text-transform: uppercase; color: #b5b5c3; font-weight: 700;">
                                    Candidates</div>
                            </div>
                            <div style="background: #f8fafc; padding: 12px; border-radius: 10px; text-align: center;">
                                <div style="font-size: 1.25rem; font-weight: 800; color: #181c32;"><?php echo $p['v_count']; ?>
                                </div>
                                <div style="font-size: 0.7rem; text-transform: uppercase; color: #b5b5c3; font-weight: 700;">
                                    Votes</div>
                            </div>
                        </div>

                        <div
                            style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f1f1f4; padding-top: 15px;">
                            <div style="font-size: 0.8rem; color: #181c32; font-weight: 600;">
                                <i class="fas fa-users" style="color: #4361ee; margin-right: 5px;"></i> quota:
                                <?php echo $p['max_candidates']; ?>
                            </div>
                            <div style="display: flex; gap: 8px;">
                                <a href="?action=edit&id=<?php echo $p['id']; ?>" class="sidebar-toggle"
                                    style="background: #f3f6f9; color: #4361ee; width: 32px; height: 32px; text-decoration: none;"><i
                                        class="fas fa-edit"></i></a>
                                <a href="?delete=1&id=<?php echo $p['id']; ?>" onclick="return confirm('Delete role?')"
                                    class="sidebar-toggle"
                                    style="background: #ffe2e5; color: #f64e60; width: 32px; height: 32px; text-decoration: none;"><i
                                        class="fas fa-trash"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <script>
            new Sortable(document.getElementById('position-list'), {
                handle: '.reorder-handle',
                animation: 150,
                onEnd: function () {
                    let order = [];
                    document.querySelectorAll('#position-list .dashboard-card').forEach(el => order.push(el.dataset.id));
                    fetch('api/save_position_order.php', {
                        method: 'POST',
                        body: JSON.stringify({ position_ids: order }),
                        headers: { 'Content-Type': 'application/json' }
                    });
                }
            });
        </script>
    <?php else: ?>
        <div class="dashboard-card" style="max-width: 600px;">
            <h3><?php echo $action === 'add' ? 'Define New Position' : 'Update Position Configuration'; ?></h3>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Position Title</label>
                    <input type="text" name="title" class="form-control" value="<?php echo $position['title'] ?? ''; ?>"
                        required placeholder="e.g. Student Union President">
                </div>
                <div class="form-group">
                    <label class="form-label">Description / Qualifications</label>
                    <textarea name="description" class="form-control"
                        style="height: 100px; resize: none;"><?php echo $position['description'] ?? ''; ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Maximum Candidates Allowed in Ballot</label>
                    <input type="number" name="max_candidates" class="form-control"
                        value="<?php echo $position['max_candidates'] ?? 1; ?>" min="1" required>
                </div>
                <div class="form-group" style="background: #f8fafc; padding: 15px; border-radius: 12px; margin-top: 25px;">
                    <div class="form-check">
                        <input type="checkbox" name="is_active" id="p_active" class="form-check-input" <?php echo ($position['is_active'] ?? 1) ? 'checked' : ''; ?>>
                        <label for="p_active" class="form-check-label">Include this position in the next election</label>
                    </div>
                </div>
                <div style="margin-top: 30px; text-align: right;">
                    <button type="submit" class="btn btn-primary" style="padding: 12px 40px;"><i class="fas fa-save"></i>
                        Save Categories</button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    </div> <!-- Close admin-content -->
    </div> <!-- Close admin-main -->
</body>

</html>