<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect to appropriate dashboard if already logged in
if (isTeacherLoggedIn()) {
    header('Location: ' . getUrl('/teacher/dashboard.php'));
    exit;
} elseif (isStudentLoggedIn()) {
    header('Location: ' . getUrl('/student/submit.php'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interactive Classroom Participation System</title>
    <link rel="stylesheet" href="<?php echo getUrl('/assets/css/style.css'); ?>">
</head>
<body>
    <header class="header">
        <div class="container">
            <h1>Interactive Classroom Participation System</h1>
        </div>
    </header>

    <main class="container">
        <div class="grid">
            <!-- Teacher Section -->
            <div class="card fade-in">
                <h2>For Teachers</h2>
                <p>Create and manage interactive classroom sessions where students can participate in real-time.</p>
                <div class="nav">
                    <a href="<?php echo getUrl('/teacher/login.php'); ?>" class="btn btn-primary">Login</a>
                    <a href="<?php echo getUrl('/teacher/register.php'); ?>" class="btn btn-primary">Register</a>
                </div>
            </div>

            <!-- Student Section -->
            <div class="card fade-in">
                <h2>For Students</h2>
                <p>Join your teacher's session and participate in collaborative discussions.</p>
                <div class="nav">
                    <a href="<?php echo getUrl('/student/join.php'); ?>" class="btn btn-primary">Join Session</a>
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <section class="card fade-in" style="margin-top: 2rem;">
            <h2>Features</h2>
            <div class="grid">
                <div>
                    <h3>Real-time Updates</h3>
                    <p>See student contributions appear instantly in the word cloud visualization.</p>
                </div>
                <div>
                    <h3>Interactive Word Cloud</h3>
                    <p>Visualize common themes and keywords from student submissions.</p>
                </div>
                <div>
                    <h3>Session Control</h3>
                    <p>Teachers can easily manage and moderate student participation.</p>
                </div>
                <div>
                    <h3>Secure Access</h3>
                    <p>Simple access codes ensure only authorized students can join sessions.</p>
                </div>
            </div>
        </section>
    </main>

    <footer class="header" style="margin-top: 2rem; text-align: center;">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Interactive Classroom Participation System. All rights reserved.</p>
        </div>
    </footer>

    <script src="<?php echo getUrl('/assets/js/main.js'); ?>"></script>
</body>
</html> 