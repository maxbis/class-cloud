<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/datefunctions.php';

// Require student authentication
requireStudentAuth();

$sessionId = getCurrentSessionId();
$studentId = getCurrentUserId();
$studentName = getCurrentUserName();

// Get session details
$stmt = $pdo->prepare("
    SELECT s.*, t.username as teacher_name
    FROM sessions s
    JOIN teachers t ON s.teacher_id = t.teacher_id
    WHERE s.session_id = :session_id
");

$stmt->execute([':session_id' => $sessionId]);
$session = $stmt->fetch();

if (!$session) {
    // If session doesn't exist, clear student session and redirect to join
    session_destroy();
    header('Location: ' . getUrl('/student/join.php'));
    exit;
}

if (!$session['is_active']) {
    // If session is not active, clear student session and redirect to join
    session_destroy();
    header('Location: ' . getUrl('/student/join.php'));
    exit;
}

// Handle bullet point submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = sanitizeInput($_POST['content'] ?? '');
    
    if (empty($content)) {
        $error = 'Please enter your bullet point';
    } elseif (strlen($content) < 3 || strlen($content) > 500) {
        $error = 'Bullet point must be between 3 and 500 characters';
    } elseif (hasReachedBulletPointLimit($sessionId, $studentId)) {
        $error = 'You have reached the maximum number of bullet points allowed';
    } else {
        // Insert bullet point
        $stmt = $pdo->prepare("
            INSERT INTO bullet_points (session_id, student_id, content)
            VALUES (:session_id, :student_id, :content)
        ");
        
        if ($stmt->execute([
            ':session_id' => $sessionId,
            ':student_id' => $studentId,
            ':content' => $content
        ])) {
            // Update keyword frequencies
            updateKeywordFrequencies($sessionId, $content);
            $success = 'Bullet point added successfully!';
        } else {
            $error = 'Failed to add bullet point';
        }
    }
}

// Get student's bullet points
$bulletPoints = getStudentBulletPoints($sessionId, $studentId);

// Get remaining bullet points
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

$bulletPointInfo = $stmt->fetch();
$remainingBulletPoints = $bulletPointInfo['bulletpoint_limit'] - $bulletPointInfo['current_count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Bullet Points - Interactive Classroom Participation System</title>
    <link rel="stylesheet" href="<?php echo getUrl('/assets/css/style.css'); ?>">
    <meta name="session-id" content="<?php echo $sessionId; ?>">
    <style>
        .toast {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.5);
            background: rgba(46, 204, 113, 0.95);
            color: white;
            padding: 2rem 3rem;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            cursor: pointer;
        }

        .toast:hover {
            transform: translate(-50%, -50%) scale(1.05);
            background: rgba(46, 204, 113, 1);
        }

        .toast.show {
            transform: translate(-50%, -50%) scale(1);
            opacity: 1;
        }

        .toast .icon {
            font-size: 3rem;
            margin-bottom: 0.5rem;
        }

        .toast .message {
            font-size: 1.5rem;
            font-weight: 500;
            text-align: center;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .toast .icon {
            animation: bounce 0.6s ease infinite;
        }

        .toast-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }

        .toast-overlay.show {
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="toast-overlay"></div>
    <div id="toast" class="toast">
        <span class="icon">🎉</span>
        <span class="message">Thank you!</span>
    </div>
    <header class="header">
        <div class="container">
            <h1>Submit Bullet Points</h1>
            <div class="nav">
                <span>Welcome, <?php echo htmlspecialchars($studentName); ?></span>
                <span>Remaining Bullet Points: <?php echo $remainingBulletPoints; ?></span>
                <a href="<?php echo getUrl('/student/logout.php'); ?>" class="btn btn-danger">Leave Session</a>
            </div>
        </div>
    </header>

    <main class="container">
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card fade-in">
            <h1><?php echo htmlspecialchars($session['name'] ?? 'Session #' . $sessionId); ?></h1>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="content">Your Bullet Point or Short Answer</label>
                    <textarea id="content" name="content" class="form-control" 
                              rows="4" minlength="3" maxlength="500" required></textarea>
                    <small>Enter your bullet point (3-500 characters)</small>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary" 
                            <?php echo $remainingBulletPoints <= 0 ? 'disabled' : ''; ?>>
                        Submit Bullet Point
                    </button>
                </div>
            </form>
        </div>

        <!-- Your Bullet Points -->
        <div class="card fade-in">
            <h2>Your Bullet Points</h2>
            <?php if (empty($bulletPoints)): ?>
                <p>You haven't submitted any bullet points yet.</p>
            <?php else: ?>
                <div id="bullet-points-container">
                    <?php foreach ($bulletPoints as $point): ?>
                        <div class="card">
                            <div class="card-header">
                                <small><?php echo date('M j, Y g:i A', strtotime($point['created_at'])); ?></small>
                            </div>
                            <p><?php echo nl2br(htmlspecialchars($point['content'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="header" style="margin-top: 2rem; text-align: center;">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Interactive Classroom Participation System. All rights reserved.</p>
        </div>
    </footer>

    <script src="<?php echo getUrl('/assets/js/main.js'); ?>"></script>
    <script>
        let toastTimeout;

        // Show toast notification
        function showToast() {
            const toast = document.getElementById('toast');
            const overlay = document.querySelector('.toast-overlay');
            
            // Clear any existing timeout
            if (toastTimeout) {
                clearTimeout(toastTimeout);
            }
            
            // Show overlay first
            overlay.classList.add('show');
            
            // Show toast with a slight delay
            setTimeout(() => {
                toast.classList.add('show');
            }, 100);

            // Set timeout to auto-hide after 2 seconds
            toastTimeout = setTimeout(hideToast, 2000);
        }

        // Hide toast notification
        function hideToast() {
            const toast = document.getElementById('toast');
            const overlay = document.querySelector('.toast-overlay');
            
            toast.classList.remove('show');
            setTimeout(() => {
                overlay.classList.remove('show');
            }, 300);
        }

        // Show toast on successful submission
        <?php if (isset($success)): ?>
            document.addEventListener('DOMContentLoaded', () => {
                showToast();
            });
        <?php endif; ?>

        // Add click event to toast
        document.getElementById('toast').addEventListener('click', () => {
            // Clear the auto-hide timeout when clicked
            if (toastTimeout) {
                clearTimeout(toastTimeout);
            }
            hideToast();
        });

        // Handle form submission
        document.querySelector('form').addEventListener('submit', (e) => {
            const submitButton = e.target.querySelector('button[type="submit"]');
            if (!submitButton.disabled) {
                submitButton.disabled = true;
                submitButton.innerHTML = 'Submitting...';
            }
        });
    </script>
</body>
</html> 