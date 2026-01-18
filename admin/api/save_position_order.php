<?php
require_once '../../includes/config.php';
header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['position_ids']) && is_array($data['position_ids'])) {
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE positions SET sort_order = ? WHERE id = ?");
        foreach ($data['position_ids'] as $index => $id) {
            $stmt->execute([$index, $id]);
        }
        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
}
