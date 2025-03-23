<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Require teacher authentication
requireTeacherAuth();

// Get session ID from URL
$sessionId = $_GET['id'] ?? null;

if (!$sessionId) {
    header('Location: ' . getUrl('/teacher/dashboard.php'));
    exit;
}

// Verify teacher owns the session
$stmt = $pdo->prepare("SELECT * FROM sessions WHERE session_id = :session_id AND teacher_id = :teacher_id");
$stmt->execute([
    ':session_id' => $sessionId,
    ':teacher_id' => $_SESSION['teacher_id']
]);

$session = $stmt->fetch();

if (!$session) {
    header('Location: ' . getUrl('/teacher/dashboard.php'));
    exit;
}

// Toggle session status
$stmt = $pdo->prepare("UPDATE sessions SET is_active = NOT is_active WHERE session_id = :session_id");
$stmt->execute([':session_id' => $sessionId]);

// Redirect back to dashboard
header('Location: ' . getUrl('/teacher/dashboard.php'));
exit; 