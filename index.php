<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="<?php echo getUrl('/assets/css/style.css'); ?>">
    <style>
    
    </style>
</head>

<body>
    <header class="header">
        <div class="container">
            <h1>Interactive Classroom Participation System</h1>
        </div>
    </header>

    <main class="container">
        <div class="grid">
            <div class="card fade-in">
                <h2><i class="fas fa-chalkboard-teacher"></i> For Teachers</h2>
                <p>Create and manage interactive classroom sessions where students can participate in real-time.</p>
                <div class="nav">
                    <a href="<?php echo getUrl('/teacher/login.php'); ?>" class="btn btn-primary">Login</a>
                    <a href="<?php echo getUrl('/teacher/register.php'); ?>" class="btn btn-primary">Register</a>
                </div>
            </div>

            <div class="card fade-in">
                <h2><i class="fas fa-user-graduate"></i> For Students</h2>
                <p>Join your teacher's session and participate in collaborative discussions.</p>
                <div class="nav">
                    <a href="<?php echo getUrl('/student/join.php'); ?>" class="btn btn-primary">Join Session</a>
                </div>
            </div>
        </div>

        <section class="card fade-in" style="margin-top: 2rem;">
            <h2><i class="fas fa-star"></i> Features</h2>
            <div class="grid">
                <div>
                    <h3><i class="fas fa-sync-alt"></i> Real-time Updates</h3>
                    <p>See student contributions appear instantly in the word cloud visualization.</p>
                </div>
                <div>
                    <h3><i class="fas fa-cloud"></i> Interactive Word Cloud</h3>
                    <p>Visualize common themes and keywords from student submissions.</p>
                </div>
                <div>
                    <h3><i class="fas fa-tools"></i> Session Control</h3>
                    <p>Teachers can easily manage and moderate student participation.</p>
                </div>
                <div>
                    <h3><i class="fas fa-lock"></i> Secure Access</h3>
                    <p>Simple access codes ensure only authorized students can join sessions.</p>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Interactive Classroom Participation System. All rights reserved.</p>
        </div>
    </footer>

    <script src="<?php echo getUrl('/assets/js/main.js'); ?>"></script>
</body>

</html>
