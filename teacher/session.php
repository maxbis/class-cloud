<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Require teacher authentication
requireTeacherAuth();

// Get session ID from URL
$sessionId = (int) ($_GET['id'] ?? 0);

// Verify teacher owns this session
$stmt = $pdo->prepare("
    SELECT s.*, t.username as teacher_name
    FROM sessions s
    JOIN teachers t ON s.teacher_id = t.teacher_id
    WHERE s.session_id = :session_id AND s.teacher_id = :teacher_id
");

$stmt->execute([
    ':session_id' => $sessionId,
    ':teacher_id' => $_SESSION['teacher_id']
]);

$session = $stmt->fetch();

if (!$session) {
    header('Location: ' . getUrl('/teacher/dashboard.php'));
    exit;
}

// Handle bullet point moderation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_bulletpoint'])) {
        $bulletpointId = (int) $_POST['bulletpoint_id'];

        $stmt = $pdo->prepare("
            DELETE FROM bullet_points 
            WHERE bulletpoint_id = :bulletpoint_id AND session_id = :session_id
        ");

        if (
            $stmt->execute([
                ':bulletpoint_id' => $bulletpointId,
                ':session_id' => $sessionId
            ])
        ) {
            $success = 'Bullet point deleted successfully.';
        } else {
            $error = 'Failed to delete bullet point.';
        }
    }
}

// Get all bullet points for this session
$stmt = $pdo->prepare("
    SELECT bp.*, s.name as student_name 
    FROM bullet_points bp
    JOIN students s ON bp.student_id = s.student_id
    WHERE bp.session_id = :session_id
    ORDER BY bp.order_position ASC, bp.created_at ASC
");

$stmt->execute([
    ':session_id' => $sessionId
]);

$bulletPoints = $stmt->fetchAll();

// Get top keywords
$keywords = getTopKeywords($sessionId);

// Get session messages
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session #<?php echo $sessionId; ?> - Interactive Classroom Participation System</title>
    <link rel="stylesheet" href="<?php echo getUrl('/assets/css/style.css'); ?>">
    <meta name="session-id" content="<?php echo $sessionId; ?>">
    <style>
        .submission-point {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 1rem;
            position: relative;
            border-left: 4px solid;
            width: 100%;
            max-width: none;
            cursor: default;
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }

        .delete-btn {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
            cursor: pointer;
            transition: background-color 0.2s ease;
            z-index: 10;
        }

        .delete-btn:hover {
            background: #c0392b;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            max-width: 400px;
            width: 90%;
            text-align: center;
        }

        .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .modal-buttons button {
            padding: 0.5rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background-color 0.2s ease;
        }

        .modal-buttons .confirm-delete {
            background: #e74c3c;
            color: white;
        }

        .modal-buttons .confirm-delete:hover {
            background: #c0392b;
        }

        .modal-buttons .cancel-delete {
            background: #95a5a6;
            color: white;
        }

        .modal-buttons .cancel-delete:hover {
            background: #7f8c8d;
        }
    </style>
</head>

<body>
    <header class="header">
        <div class="container" style="display: flex; justify-content: space-between; align-items: flex-start;">
            <h1 style="margin: 0;">
                <div style="margin-right: 1rem;">Access Code: <?php echo $session['access_code']; ?></div>
            </h1>

            <div class="nav" style="margin: 0;">

                <button onclick="window.location.href='<?php echo getUrl('/teacher/dashboard.php'); ?>'"
                    class="btn btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">
                    Dashboard
                </button>
                <button onclick="window.location.href='<?php echo getUrl('/teacher/logout.php'); ?>'"
                    class="btn btn-danger" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">
                    Logout
                </button>
            </div>
        </div>
    </header>

    <main class="container">
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Session Status -->
        <div class="card hop fade-in" style="margin-top: 1.5rem;">
            <h2><?php echo $session["name"]; ?></h2>
            <p>Status: <strong><?php echo $session['is_active'] ? 'Active' : 'Inactive'; ?></strong></p>
            <p>Created: <?php echo date('M j, Y g:i A', strtotime($session['created_at'])); ?></p>
            <p>Bullet Point Limit: <?php echo $session['bulletpoint_limit']; ?> per student</p>
            <div class="nav" style="margin-top: 10px;">
                <?php if ($session['is_active']): ?>
                    <button
                        onclick="window.location.href='<?php echo getUrl('/teacher/toggle-session.php?id=' . $sessionId); ?>'"
                        class="btn btn-warning" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">
                        End Session
                    </button>
                <?php else: ?>
                    <a href="<?php echo getUrl('/teacher/toggle-session.php?id=' . $sessionId); ?>" class="btn btn-primary"
                        style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">
                        Start Session
                    </a>
                <?php endif; ?>
                <button
                    onclick="window.open('<?php echo getUrl('/teacher/display.php?id=' . $sessionId); ?>', '_blank')"
                    class="btn btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">
                    Full Screen
                </button>
                <button onclick="showClearConfirmation()" class="btn btn-danger"
                    style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">
                    Clear Session
                </button>
            </div>
        </div>

        <!-- Bullet Points -->
        <div class="card hop fade-in">
            <div class="card-header">
                <h2 onclick="toggleSubmissions()" style="cursor: pointer;">
                    <span id="toggleTriangle">&#9654;</span> Student Submissions
                </h2>
            </div>
            <div id="submissionsContent" class="card-body" style="display: none;">
                <?php if (empty($bulletPoints)): ?>
                    <p class="text-muted">No submissions yet.</p>
                <?php else: ?>
                    <?php foreach ($bulletPoints as $point): ?>
                        <div class="submission-point"
                            style="border-left-color: <?php echo $studentColors[$point['student_id']]; ?>"
                            data-id="<?php echo $point['bulletpoint_id']; ?>">
                            <button class="delete-btn"
                                onclick="showDeleteConfirmation(<?php echo $point['bulletpoint_id']; ?>)">
                                Delete
                            </button>
                            <p><?php echo htmlspecialchars($point['content']); ?></p>
                            <small class="text-muted">
                                Submitted by <?php echo htmlspecialchars($point['student_name']); ?>
                                on <?php echo date('M j, Y g:i A', strtotime($point['created_at'])); ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <!-- Delete Confirmation Modal -->
        <div id="deleteModal" class="modal">
            <div class="modal-content">
                <h3>Delete Submission</h3>
                <p>Are you sure you want to delete this submission? This action cannot be undone.</p>
                <div class="modal-buttons">
                    <button class="confirm-delete" onclick="confirmDelete()">Delete</button>
                    <button class="cancel-delete" onclick="hideDeleteConfirmation()">Cancel</button>
                </div>
            </div>
        </div>

        <!-- Clear Session Confirmation Modal -->
        <div id="clearModal" class="modal">
            <div class="modal-content">
                <h3>Clear Session</h3>
                <p>Are you sure you want to clear all student submissions? This action cannot be undone.</p>
                <div class="modal-buttons">
                    <button class="confirm-delete" onclick="confirmClear()">Clear All</button>
                    <button class="cancel-delete" onclick="hideClearConfirmation()">Cancel</button>
                </div>
            </div>
        </div>
    </main>

    <footer class="header" style="margin-top: 2rem; text-align: center;">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Interactive Classroom Participation System. All rights reserved.</p>
        </div>
    </footer>

    <script src="<?php echo getUrl('/assets/js/main.js'); ?>"></script>
    <script>
        let bulletPointToDelete = null;

        function showDeleteConfirmation(bulletpointId) {
            bulletPointToDelete = bulletpointId;
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function hideDeleteConfirmation() {
            document.getElementById('deleteModal').style.display = 'none';
            bulletPointToDelete = null;
        }

        function showClearConfirmation() {
            document.getElementById('clearModal').style.display = 'flex';
        }

        function hideClearConfirmation() {
            document.getElementById('clearModal').style.display = 'none';
        }

        function confirmClear() {
            fetch('<?php echo getUrl('/api/clear-session.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    session_id: <?php echo $sessionId; ?>
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove all bullet points from the DOM
                        const bulletPoints = document.querySelectorAll('.submission-point');
                        bulletPoints.forEach(point => point.remove());
                    } else {
                        alert('Failed to clear session: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while clearing the session.');
                })
                .finally(() => {
                    hideClearConfirmation();
                });
        }

        function confirmDelete() {
            if (!bulletPointToDelete) return;

            fetch('<?php echo getUrl('/api/delete-bulletpoint.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    bulletpoint_id: bulletPointToDelete
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the bullet point from the DOM
                        const bulletPoint = document.querySelector(`.submission-point[data-id="${bulletPointToDelete}"]`);
                        if (bulletPoint) {
                            bulletPoint.remove();
                        }
                    } else {
                        alert('Failed to delete submission: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the submission.');
                })
                .finally(() => {
                    hideDeleteConfirmation();
                });
        }

        function toggleSubmissions() {
            var content = document.getElementById('submissionsContent');
            var triangle = document.getElementById('toggleTriangle');
            if (content.style.display === 'none') {
                content.style.display = 'block';
                triangle.innerHTML = "&#9660;"; // Downward triangle when open
            } else {
                content.style.display = 'none';
                triangle.innerHTML = "&#9654;"; // Rightward triangle when closed
            }
        }
    </script>
</body>

</html>