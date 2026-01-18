<?php
require_once '../../includes/config.php';
header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$stats = [];

$stats['total_voters'] = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0")->fetchColumn();
$stats['total_votes'] = $pdo->query("SELECT COUNT(*) FROM votes")->fetchColumn();

echo json_encode($stats);
