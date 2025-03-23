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
$bulletPointId = $data['bulletpoint_id'] ?? null;
$x = $data['x'] ?? null;
$y = $data['y'] ?? null;

if (!$sessionId || !$bulletPointId || $x === null || $y === null) {
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

    // Update bullet point position
    $stmt = $pdo->prepare("
        UPDATE bullet_points 
        SET cloud_x = :x, cloud_y = :y, is_in_cloud = TRUE
        WHERE bulletpoint_id = :bulletpoint_id 
        AND session_id = :session_id
    ");

    $stmt->execute([
        ':x' => $x,
        ':y' => $y,
        ':bulletpoint_id' => $bulletPointId,
        ':session_id' => $sessionId
    ]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
} 