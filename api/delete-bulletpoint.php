<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Require teacher authentication
requireTeacherAuth();

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['bulletpoint_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit;
}

try {
    // Verify teacher owns the bullet point
    $stmt = $pdo->prepare("
        SELECT bp.*, s.teacher_id
        FROM bullet_points bp
        JOIN sessions s ON bp.session_id = s.session_id
        WHERE bp.bulletpoint_id = :bulletpoint_id
    ");
    $stmt->execute([':bulletpoint_id' => $data['bulletpoint_id']]);
    $bulletPoint = $stmt->fetch();

    if (!$bulletPoint || $bulletPoint['teacher_id'] !== $_SESSION['teacher_id']) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    // Delete the bullet point
    $stmt = $pdo->prepare("DELETE FROM bullet_points WHERE bulletpoint_id = :bulletpoint_id");
    $stmt->execute([':bulletpoint_id' => $data['bulletpoint_id']]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
} 