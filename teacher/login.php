<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in
if (isTeacherLoggedIn()) {
    header('Location: ' . getUrl('/teacher/dashboard.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        if (loginTeacher($username, $password)) {
            header('Location: ' . getUrl('/teacher/dashboard.php'));
            exit;
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Login - Interactive Classroom Participation System</title>
    <link rel="stylesheet" href="<?php echo getUrl('/assets/css/style.css'); ?>">
</head>
<body>
    <header class="header">
        <div class="container">
            <h1>Teacher Login</h1>
        </div>
    </header>

    <main class="container">
        <div class="card fade-in" style="max-width: 400px; margin: 0 auto;">
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Login</button>
                </div>
            </form>

            <div class="nav" style="margin-top: 1rem;">
                <a href="<?php echo getUrl('/teacher/register.php'); ?>">Don't have an account? Register</a>
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