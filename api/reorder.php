<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Verify teacher is logged in
if (!isset($_SESSION['teacher_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$sessionId = $data['session_id'] ?? null;
$bulletPointIds = $data['bullet_point_ids'] ?? [];

if (!$sessionId || empty($bulletPointIds)) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit;
}

try {
    // Verify teacher owns the session
    $stmt = $pdo->prepare("
        SELECT 1 FROM sessions s
        JOIN teachers t ON s.teacher_id = t.teacher_id
        WHERE s.session_id = :session_id AND t.teacher_id = :teacher_id
    ");
    $stmt->execute([
        ':session_id' => $sessionId,
        ':teacher_id' => $_SESSION['teacher_id']
    ]);

    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    // Update order for each bullet point
    $stmt = $pdo->prepare("
        UPDATE bullet_points 
        SET order_position = :position 
        WHERE bulletpoint_id = :bulletpoint_id 
        AND session_id = :session_id
    ");

    foreach ($bulletPointIds as $position => $bulletPointId) {
        $stmt->execute([
            ':position' => $position,
            ':bulletpoint_id' => $bulletPointId,
            ':session_id' => $sessionId
        ]);
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
} 