<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in as student
if (isStudentLoggedIn()) {
    header('Location: ' . getUrl('/student/submit.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accessCode = strtoupper(sanitizeInput($_POST['access_code'] ?? ''));
    $name = sanitizeInput($_POST['name'] ?? '');
    
    if (empty($accessCode) || empty($name)) {
        $error = 'Please fill in all fields';
    } elseif (!preg_match('/^[A-Z0-9]{6}$/', $accessCode)) {
        $error = 'Invalid access code format';
    } elseif (strlen($name) < 2 || strlen($name) > 50) {
        $error = 'Name must be between 2 and 50 characters';
    } else {
        // Check if session exists and is active
        $session = getSessionByAccessCode($accessCode);
        
        if (!$session) {
            $error = 'Invalid access code';
        } elseif (!$session['is_active']) {
            $error = 'This session is not active';
        } else {
            // Register student
            $studentId = registerStudent($session['session_id'], $name);
            
            if ($studentId) {
                // Log student in
                if (loginStudent($session['session_id'], $studentId)) {
                    header('Location: ' . getUrl('/student/submit.php'));
                    exit;
                } else {
                    $error = 'Failed to join session';
                }
            } else {
                $error = 'Failed to register student';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Session - Interactive Classroom Participation System</title>
    <link rel="stylesheet" href="<?php echo getUrl('/assets/css/style.css'); ?>">
</head>
<body>
    <header class="header">
        <div class="container">
            <h1>Join Session</h1>
        </div>
    </header>

    <main class="container">
        <div class="card fade-in" style="max-width: 400px; margin: 0 auto;">
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="access_code">Access Code</label>
                    <input type="text" id="access_code" name="access_code" class="form-control" 
                           pattern="[A-Z0-9]{6}" maxlength="6" required>
                    <small>Enter the 6-character code provided by your teacher</small>
                </div>

                <div class="form-group">
                    <label for="name">Your Name</label>
                    <input type="text" id="name" name="name" class="form-control" 
                           minlength="2" maxlength="50" required>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Join Session</button>
                </div>
            </form>

            <div class="nav" style="margin-top: 1rem;">
                <a href="<?php echo getUrl('/'); ?>">Back to Home</a>
            </div>
        </div>
    </main>

    <footer class="header" style="margin-top: 2rem; text-align: center;">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Interactive Classroom Participation System. All rights reserved.</p>
        </div>
    </footer>

    <script src="<?php echo getUrl('/assets/js/main.js'); ?>"></script>
</body>
</html> 