<?php
require_once '../../includes/config.php';
header('Content-Type: application/json');

if (!isAdmin()) {
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['action'])) {
    $details = isset($data['duration']) ? "Duration: " . $data['duration'] . "s" : "";
    logActivity($pdo, $_SESSION['user_id'], $data['action'], $details);
}
