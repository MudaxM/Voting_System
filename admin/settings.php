<?php
require_once '../includes/config.php';
requireAdmin();

$message = '';
$error = '';

// Get current settings
$sql = "SELECT * FROM election_settings LIMIT 1";
$stmt = $pdo->query($sql);
$settings = $stmt->fetch();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $election_name = sanitize($_POST['election_name']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($settings) {
        $sql = "UPDATE election_settings SET election_name = ?, start_date = ?, end_date = ?, is_active = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$election_name, $start_date, $end_date, $is_active, $settings['id']])) {
            $message = 'Settings updated successfully!';
        } else {
            $error = 'Failed to update settings.';
        }
    } else {
        $sql = "INSERT INTO election_settings (election_name, start_date, end_date, is_active) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$election_name, $start_date, $end_date, $is_active])) {
            $message = 'Settings created successfully!';
        } else {
            $error = 'Failed to create settings.';
        }
    }
    
    // Refresh settings
    $stmt = $pdo->query("SELECT * FROM election_settings LIMIT 1");
    $settings = $stmt->fetch();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/admin_header.php'; ?>

    <div class="dashboard-header" style="margin-bottom: 30px;">
        <div class="user-info">
            <p>Configure electronic voting parameters and system behavior</p>
        </div>
        <div class="user-actions">
            <a href="dashboard.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="dashboard-card" style="max-width: 800px;">
        <h3 style="border-bottom: 1px solid #f1f1f4; padding-bottom: 15px; margin-bottom: 25px;">
            <i class="fas fa-cog" style="color: var(--accent);"></i>
            General Election Configuration
        </h3>
        
        <form method="POST">
            <div style="display: grid; grid-template-columns: 1fr; gap: 20px;">
                <div class="form-group">
                    <label class="form-label">Election Name</label>
                    <input type="text" name="election_name" class="form-control" required 
                           value="<?php echo htmlspecialchars($settings['election_name'] ?? 'Student Union Elections'); ?>"
                           placeholder="e.g. 2024 Student Union Elections">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">Start Date & Time</label>
                        <input type="datetime-local" name="start_date" class="form-control" required
                               value="<?php echo $settings ? date('Y-m-d\TH:i', strtotime($settings['start_date'])) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">End Date & Time</label>
                        <input type="datetime-local" name="end_date" class="form-control" required
                               value="<?php echo $settings ? date('Y-m-d\TH:i', strtotime($settings['end_date'])) : ''; ?>">
                    </div>
                </div>

                <div class="form-group" style="background: #f8f9fa; padding: 20px; border-radius: 12px;">
                    <div class="form-check">
                        <input type="checkbox" name="is_active" id="is_active" class="form-check-input"
                               <?php echo ($settings && $settings['is_active']) ? 'checked' : ''; ?>>
                        <label for="is_active" class="form-check-label" style="font-weight: 600;">
                            Enable Election System
                            <span style="display: block; font-weight: 400; font-size: 0.85rem; color: #64748b;">
                                When disabled, voters will see a "Maintenance" or "Coming Soon" page.
                            </span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-group" style="margin-top: 2rem; text-align: right;">
                <button type="submit" class="btn btn-primary" style="padding: 12px 40px;">
                    <i class="fas fa-save"></i> Save Configuration
                </button>
            </div>
        </form>
    </div>

    </div> <!-- Close admin-content -->
</div> <!-- Close admin-main -->
</body>
</html>
