<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

session_start();

/**
 * Check if user is logged in as teacher
 */
function isTeacherLoggedIn() {
    return isset($_SESSION['teacher_id']);
}

/**
 * Check if user is logged in as student
 */
function isStudentLoggedIn() {
    return isset($_SESSION['student_id']) && isset($_SESSION['session_id']);
}

/**
 * Require teacher authentication
 */
function requireTeacherAuth() {
    if (!isTeacherLoggedIn()) {
        header('Location: ' . getUrl('/teacher/login.php'));
        exit;
    }
}

/**
 * Require student authentication
 */
function requireStudentAuth() {
    if (!isStudentLoggedIn()) {
        header('Location: ' . getUrl('/student/join.php'));
        exit;
    }
}

/**
 * Login teacher
 */
function loginTeacher($username, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT teacher_id, username, password_hash
        FROM teachers
        WHERE username = :username
    ");
    
    $stmt->execute([':username' => $username]);
    $teacher = $stmt->fetch();
    
    if ($teacher && password_verify($password, $teacher['password_hash'])) {
        $_SESSION['teacher_id'] = $teacher['teacher_id'];
        $_SESSION['username'] = $teacher['username'];
        return true;
    }
    
    return false;
}

/**
 * Register new teacher
 */
function registerTeacher($username, $email, $password) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO teachers (username, email, password_hash)
            VALUES (:username, :email, :password_hash)
        ");
        
        $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':password_hash' => password_hash($password, PASSWORD_DEFAULT)
        ]);
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Login student to session
 */
function loginStudent($sessionId, $studentId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT student_id, session_id, name
        FROM students
        WHERE student_id = :student_id AND session_id = :session_id
    ");
    
    $stmt->execute([
        ':student_id' => $studentId,
        ':session_id' => $sessionId
    ]);
    
    $student = $stmt->fetch();
    
    if ($student) {
        $_SESSION['student_id'] = $student['student_id'];
        $_SESSION['session_id'] = $student['session_id'];
        $_SESSION['student_name'] = $student['name'];
        return true;
    }
    
    return false;
}

/**
 * Register new student for session
 */
function registerStudent($sessionId, $name) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO students (session_id, name)
            VALUES (:session_id, :name)
        ");
        
        $stmt->execute([
            ':session_id' => $sessionId,
            ':name' => $name
        ]);
        
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Logout current user
 */
function logout() {
    session_destroy();
    header('Location: ' . getUrl('/'));
    exit;
}

/**
 * Get current user's session ID
 */
function getCurrentSessionId() {
    return $_SESSION['session_id'] ?? null;
}

/**
 * Get current user's ID
 */
function getCurrentUserId() {
    return $_SESSION['teacher_id'] ?? $_SESSION['student_id'] ?? null;
}

/**
 * Get current user's name
 */
function getCurrentUserName() {
    return $_SESSION['username'] ?? $_SESSION['student_name'] ?? null;
} 