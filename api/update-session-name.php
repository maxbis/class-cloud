<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Require teacher authentication
requireTeacherAuth();

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['session_id']) || !isset($data['name'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit;
}

try {
    // Verify teacher owns the session
    $stmt = $pdo->prepare("
        SELECT session_id 
        FROM sessions 
        WHERE session_id = :session_id AND teacher_id = :teacher_id
    ");
    $stmt->execute([
        ':session_id' => $data['session_id'],
        ':teacher_id' => $_SESSION['teacher_id']
    ]);
    $session = $stmt->fetch();

    if (!$session) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    // Update session name
    $stmt = $pdo->prepare("
        UPDATE sessions 
        SET name = :name
        WHERE session_id = :session_id
    ");
    $stmt->execute([
        ':session_id' => $data['session_id'],
        ':name' => trim($data['name'])
    ]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
} 