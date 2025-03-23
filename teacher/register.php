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
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = 'Please fill in all fields';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address';
    } else {
        if (registerTeacher($username, $email, $password)) {
            $success = 'Registration successful! You can now login.';
        } else {
            $error = 'Registration failed. Username or email might already be taken.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Registration - Interactive Classroom Participation System</title>
    <link rel="stylesheet" href="<?php echo getUrl('/assets/css/style.css'); ?>">
</head>
<body>
    <header class="header">
        <div class="container">
            <h1>Teacher Registration</h1>
        </div>
    </header>

    <main class="container">
        <div class="card fade-in" style="max-width: 400px; margin: 0 auto;">
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Register</button>
                </div>
            </form>

            <div class="nav" style="margin-top: 1rem;">
                <a href="<?php echo getUrl('/teacher/login.php'); ?>">Already have an account? Login</a>
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