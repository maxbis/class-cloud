<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Require teacher authentication
requireTeacherAuth();

// Set JSON response header
header('Content-Type: application/json');

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$sessionId = $data['session_id'] ?? null;

if (!$sessionId) {
    echo json_encode(['success' => false, 'message' => 'Session ID is required']);
    exit;
}

try {
    // Verify teacher owns this session
    $stmt = $pdo->prepare("
        SELECT s.* FROM sessions s
        WHERE s.session_id = :session_id AND s.teacher_id = :teacher_id
    ");
    
    $stmt->execute([
        ':session_id' => $sessionId,
        ':teacher_id' => $_SESSION['teacher_id']
    ]);
    
    $session = $stmt->fetch();
    
    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'Session not found or unauthorized']);
        exit;
    }
    
    if ($session['is_active']) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete an active session']);
        exit;
    }
    
    // Begin transaction to delete all related data
    $pdo->beginTransaction();
    
    // Delete keywords
    $stmt = $pdo->prepare("DELETE FROM keywords WHERE session_id = :session_id");
    $stmt->execute([':session_id' => $sessionId]);
    
    // Delete bullet points
    $stmt = $pdo->prepare("DELETE FROM bullet_points WHERE session_id = :session_id");
    $stmt->execute([':session_id' => $sessionId]);
    
    // Delete students
    $stmt = $pdo->prepare("DELETE FROM students WHERE session_id = :session_id");
    $stmt->execute([':session_id' => $sessionId]);
    
    // Delete session
    $stmt = $pdo->prepare("DELETE FROM sessions WHERE session_id = :session_id");
    $stmt->execute([':session_id' => $sessionId]);
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Session deleted successfully']);
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Error deleting session: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to delete session']);
} 