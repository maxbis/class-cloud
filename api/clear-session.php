<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Require teacher authentication
requireTeacherAuth();

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['session_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Session ID is required'
    ]);
    exit;
}

$sessionId = (int)$data['session_id'];

// Verify teacher owns this session
$stmt = $pdo->prepare("
    SELECT teacher_id 
    FROM sessions 
    WHERE session_id = :session_id
");

$stmt->execute([':session_id' => $sessionId]);
$session = $stmt->fetch();

if (!$session || $session['teacher_id'] !== $_SESSION['teacher_id']) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

try {
    // Delete all bullet points for this session
    $stmt = $pdo->prepare("
        DELETE FROM bullet_points 
        WHERE session_id = :session_id
    ");
    
    $stmt->execute([':session_id' => $sessionId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Session cleared successfully'
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to clear session'
    ]);
} 