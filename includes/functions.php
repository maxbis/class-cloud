<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Generate a random access code for sessions
 */
function generateAccessCode($length = 6) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}

/**
 * Extract keywords from text
 */
function extractKeywords($text) {
    // Convert to lowercase and remove special characters
    $text = strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $text));
    
    // Split into words and remove common words
    $words = explode(' ', $text);
    $commonWords = ['the', 'be', 'to', 'of', 'and', 'a', 'in', 'that', 'have', 'i', 'it', 'for', 'not', 'on', 'with', 'he', 'as', 'you', 'do', 'at'];
    
    $keywords = array_filter($words, function($word) use ($commonWords) {
        return strlen($word) > 2 && !in_array($word, $commonWords);
    });
    
    return array_values(array_unique($keywords));
}

/**
 * Update keyword frequencies for a session
 */
function updateKeywordFrequencies($sessionId, $content) {
    global $pdo;
    
    // Instead of splitting into words, treat the entire bullet point as one keyword
    $word = strtolower(trim($content));
    
    // Update or insert keyword frequency
    $stmt = $pdo->prepare("
        INSERT INTO keywords (session_id, word, frequency)
        VALUES (:session_id, :word, 1)
        ON DUPLICATE KEY UPDATE frequency = frequency + 1
    ");
    
    $stmt->execute([
        ':session_id' => $sessionId,
        ':word' => $word
    ]);
}

/**
 * Get session details by access code
 */
function getSessionByAccessCode($accessCode) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT s.*, t.username as teacher_name
        FROM sessions s
        JOIN teachers t ON s.teacher_id = t.teacher_id
        WHERE s.access_code = :access_code
    ");
    
    $stmt->execute([':access_code' => $accessCode]);
    return $stmt->fetch();
}

/**
 * Get student's bullet points for a session
 */
function getStudentBulletPoints($sessionId, $studentId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT *
        FROM bullet_points
        WHERE session_id = :session_id AND student_id = :student_id
        ORDER BY created_at DESC
    ");
    
    $stmt->execute([
        ':session_id' => $sessionId,
        ':student_id' => $studentId
    ]);
    
    return $stmt->fetchAll();
}

/**
 * Get all bullet points for a session with student names
 */
function getAllBulletPoints($sessionId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT bp.*, s.name as student_name
        FROM bullet_points bp
        JOIN students s ON bp.student_id = s.student_id
        WHERE bp.session_id = :session_id
        ORDER BY bp.created_at DESC
    ");
    
    $stmt->execute([':session_id' => $sessionId]);
    return $stmt->fetchAll();
}

/**
 * Get top keywords for a session
 */
function getTopKeywords($sessionId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT word, frequency
        FROM keywords
        WHERE session_id = :session_id
        ORDER BY frequency DESC
        LIMIT 50
    ");
    
    $stmt->execute([':session_id' => $sessionId]);
    return $stmt->fetchAll();
}

/**
 * Sanitize user input
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate session access
 */
function validateSessionAccess($sessionId, $studentId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM students
        WHERE session_id = :session_id AND student_id = :student_id
    ");
    
    $stmt->execute([
        ':session_id' => $sessionId,
        ':student_id' => $studentId
    ]);
    
    return $stmt->fetch()['count'] > 0;
}

/**
 * Check if student has reached bullet point limit
 */
function hasReachedBulletPointLimit($sessionId, $studentId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT s.bulletpoint_limit, COUNT(bp.bulletpoint_id) as current_count
        FROM sessions s
        LEFT JOIN bullet_points bp ON s.session_id = bp.session_id AND bp.student_id = :student_id
        WHERE s.session_id = :session_id
        GROUP BY s.session_id
    ");
    
    $stmt->execute([
        ':session_id' => $sessionId,
        ':student_id' => $studentId
    ]);
    
    $result = $stmt->fetch();
    return $result['current_count'] >= $result['bulletpoint_limit'];
} 