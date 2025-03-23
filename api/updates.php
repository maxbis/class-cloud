<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// Get session ID and last update timestamp
$sessionId = (int)($_GET['session_id'] ?? 0);
$lastUpdate = (int)($_GET['last_update'] ?? 0);

if (!$sessionId) {
    echo json_encode(['success' => false, 'error' => 'Invalid session ID']);
    exit;
}

// Get session details
$session = getSessionByAccessCode($_SESSION['access_code'] ?? '');

if (!$session) {
    echo json_encode(['success' => false, 'error' => 'Invalid session']);
    exit;
}

// Get latest bullet points
$stmt = $pdo->prepare("
    SELECT bp.*, s.name as student_name
    FROM bullet_points bp
    JOIN students s ON bp.student_id = s.student_id
    WHERE bp.session_id = :session_id
    AND UNIX_TIMESTAMP(bp.created_at) > :last_update
    ORDER BY bp.created_at DESC
");

$stmt->execute([
    ':session_id' => $sessionId,
    ':last_update' => $lastUpdate
]);

$bulletPoints = $stmt->fetchAll();

// Get latest keywords
$stmt = $pdo->prepare("
    SELECT word, frequency
    FROM keywords
    WHERE session_id = :session_id
    ORDER BY frequency DESC
    LIMIT 20
");

$stmt->execute([':session_id' => $sessionId]);
$keywords = $stmt->fetchAll();

// Check if there are any updates
$hasUpdates = !empty($bulletPoints) || !empty($keywords);

// Get current timestamp
$timestamp = time();

echo json_encode([
    'success' => true,
    'hasUpdates' => $hasUpdates,
    'timestamp' => $timestamp,
    'updates' => [
        'bulletPoints' => $bulletPoints,
        'keywords' => $keywords
    ]
]); 