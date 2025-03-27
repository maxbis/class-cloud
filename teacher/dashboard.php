<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/datefunctions.php';
// Require teacher authentication
requireTeacherAuth();

// Get teacher's sessions
$stmt = $pdo->prepare("
    SELECT s.*, 
           COUNT(DISTINCT st.student_id) as student_count,
           COUNT(DISTINCT bp.bulletpoint_id) as bulletpoint_count
    FROM sessions s
    LEFT JOIN students st ON s.session_id = st.session_id
    LEFT JOIN bullet_points bp ON s.session_id = bp.session_id
    WHERE s.teacher_id = :teacher_id
    GROUP BY s.session_id
    ORDER BY s.created_at DESC
");

$stmt->execute([':teacher_id' => $_SESSION['teacher_id']]);
$sessions = $stmt->fetchAll();

// Handle new session creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_session'])) {
    $bulletpointLimit = (int)($_POST['bulletpoint_limit'] ?? 5);
    $sessionName = trim($_POST['session_name'] ?? '');
    $accessCode = generateAccessCode();

    // Debug: Check if teacher exists
    $checkStmt = $pdo->prepare("SELECT teacher_id FROM teachers WHERE teacher_id = :teacher_id");
    $checkStmt->execute([':teacher_id' => $_SESSION['teacher_id']]);
    $teacher = $checkStmt->fetch();

    if (!$teacher) {
        $error = 'Teacher account not found. Please log in again.';
        // Clear invalid session
        session_destroy();
        header('Location: ' . getUrl('/teacher/login.php'));
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO sessions (teacher_id, access_code, bulletpoint_limit, name)
        VALUES (:teacher_id, :access_code, :bulletpoint_limit, :name)
    ");

    if ($stmt->execute([
        ':teacher_id' => $_SESSION['teacher_id'],
        ':access_code' => $accessCode,
        ':bulletpoint_limit' => $bulletpointLimit,
        ':name' => $sessionName
    ])) {
        $success = 'New session created successfully!';
    } else {
        $error = 'Failed to create new session.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - Interactive Classroom Participation System</title>
    <link rel="stylesheet" href="<?php echo getUrl('/assets/css/style.css'); ?>">
</head>

<body>
    <header class="header">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center;">
            <h1>Teacher Dashboard</h1>
            <div class="nav" style="margin: 0;">
                <span style="margin-right: 1rem;">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="<?php echo getUrl('/teacher/logout.php'); ?>" class="btn btn-danger" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">Logout</a>
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

        <!-- Create New Session -->
        <div class="card fade-in">
            <h2>Create New Session</h2>
            <form method="POST" action="" class="grid" style="grid-template-columns: 2fr 1fr auto; gap: 1rem; align-items: end;">
                <div class="form-group">
                    <label for="session_name">Session Name</label>
                    <input type="text" id="session_name" name="session_name" class="form-control"
                        placeholder="Enter session name" required style="width: 100%;">
                </div>
                <div class="form-group">
                    <label for="bulletpoint_limit">Max Bullet Points per Student</label>
                    <input type="number" id="bulletpoint_limit" name="bulletpoint_limit" class="form-control"
                        value="5" min="1" max="20" required style="width: 100px;">
                </div>
                <div class="form-group">
                    <button type="submit" name="create_session" class="btn btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">Create Session</button>
                </div>
            </form>
        </div>

        <!-- Active Sessions -->
        <div class="card fade-in">
            <h2>Your Sessions</h2>
            <?php if (empty($sessions)): ?>
                <p>No sessions created yet. Create a new session to get started!</p>
            <?php else: ?>
                <div class="grid" style="grid-template-columns: repeat(auto-fill, minmax(600px, 1fr)); gap: 1.5rem;">
                    <?php foreach ($sessions as $session): ?>
                        <div class="card" style="width:90%;margin-bottom: 0px;background-color: #fbfbfb;border: 1px solid #d0d0d0;padding:1rem;">
                            <div class="card-header" style="font-style: normal;display: flex; justify-content: space-between; align-items: center;">
                                <h3 style="color: <?php echo $session['is_active'] ? '#015918' : '#9c5757'; ?>">
                                    <span style="display: inline-block; width: 12px; height: 12px; border-radius: 50%; background-color: <?php echo $session['is_active'] ? '#0c6b01' : '#7a0000'; ?>;"></span>&nbsp;
                                    <span class="session-name"
                                        data-session-id="<?php echo $session['session_id']; ?>"
                                        contenteditable="true"
                                        style="cursor: pointer; padding: 2px 4px; border-radius: 4px; transition: background-color 0.2s;">
                                        <?php echo htmlspecialchars($session['name'] ?? 'Session #' . $session['session_id']); ?>
                                    </span>
                                </h3>
                                <span class="status-indicator" style="font-weight: bold; font-size: 0.85rem; color: <?php echo $session['is_active'] ? '#0c6b01' : '#940000'; ?>">
                                    <?php echo $session['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <?php if ($session['is_active']): ?>
                                        <p style="margin-bottom: 0;">Access Code: <strong><?php echo $session['access_code']; ?></strong></p>
                                        <p style="margin-bottom: 0;">Students: <?php echo $session['student_count']; ?></p>
                                    <?php endif; ?>
                                    <p style="margin-bottom: 0;">Total Bullet Points: <?php echo $session['bulletpoint_count']; ?></p>
                                    <p>Created: <?php echo formatCreationDate($session['created_at']); ?></p>
                                </div>
                                <div class="nav" style="margin-top: 10px;">
                                    <button type="button"
                                        onclick="window.location.href='<?php echo getUrl('/teacher/session.php?id=' . $session['session_id']); ?>'"
                                        class="btn btn-primary"
                                        style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">View</button>
                                    <button type="button"
                                        onclick="window.location.href='<?php echo getUrl('/teacher/toggle-session.php?id=' . $session['session_id']); ?>'"
                                        class="btn <?php echo $session['is_active'] ? 'btn-danger' : 'btn-success'; ?>"
                                        style="padding: 0.4rem 0.8rem; font-size: 0.85rem; background-color: <?php echo $session['is_active'] ? '#9c5757' : '#0c6b01'; ?>; color: #fff;">
                                        <?php echo $session['is_active'] ? '&nbsp;End&nbsp;' : 'Start'; ?>
                                    </button>
                                    
                                        <button type="button" 
                                                class="btn btn-icon delete-session" 
                                                data-session-id="<?php echo $session['session_id']; ?>"
                                                data-session-name="<?php echo htmlspecialchars($session['name'] ?? 'Session #' . $session['session_id']); ?>"
                                                style="padding: 0.4rem; font-size: 1.2rem; background: none; border: none; color: #e74c3c; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                                            🗑️
                                        </button>

                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 1000;">
        <div class="modal-content" style="position: relative; background-color: white; margin: 15% auto; padding: 2rem; border-radius: 8px; width: 90%; max-width: 500px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
            <h2 style="margin-top: 0; color: #2c3e50;">Delete</h2>
            <p>Are you sure you want to delete <span id="sessionName"></span>? This action cannot be undone.</p>
            <div class="nav" style="justify-content: flex-end; gap: 1rem; margin-top: 1.5rem;">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()"
                    style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="confirmDelete()"
                    style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">Delete</button>
            </div>
        </div>
    </div>

    <footer class="header" style="margin-top: 2rem; text-align: center;">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Interactive Classroom Participation System. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Delete session functionality
        let sessionToDelete = null;
        const deleteModal = document.getElementById('deleteModal');
        const sessionNameSpan = document.getElementById('sessionName');

        // Session name editing functionality
        document.querySelectorAll('.session-name').forEach(element => {
            let originalName = element.textContent;
            let timeoutId = null;

            element.addEventListener('blur', () => {
                const newName = element.textContent.trim();
                if (newName !== originalName && newName !== '') {
                    updateSessionName(element.dataset.sessionId, newName);
                    originalName = newName;
                } else if (newName === '') {
                    element.textContent = originalName;
                }
            });

            element.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    element.blur();
                }
            });

            element.addEventListener('focus', () => {
                element.style.backgroundColor = '#f8f9fa';
            });

            element.addEventListener('blur', () => {
                element.style.backgroundColor = 'transparent';
            });
        });

        function updateSessionName(sessionId, newName) {
            fetch('<?php echo getUrl('/api/update-session-name.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    session_id: sessionId,
                    name: newName
                })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert('Failed to update session name: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the session name.');
            });
        }

        // Existing delete session code
        document.querySelectorAll('.delete-session').forEach(button => {
            button.addEventListener('click', () => {
                sessionToDelete = button.dataset.sessionId;
                sessionNameSpan.textContent = button.dataset.sessionName;
                deleteModal.style.display = 'block';
            });
        });

        function closeDeleteModal() {
            deleteModal.style.display = 'none';
            sessionToDelete = null;
        }

        function confirmDelete() {
            if (!sessionToDelete) return;

            fetch('<?php echo getUrl('/api/delete-session.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    session_id: sessionToDelete
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to delete session: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting the session.');
            });
        }
    </script>
    <style>
        .btn-icon {
            transition: transform 0.2s ease;
        }
        .btn-icon:hover {
            transform: scale(1.1);
        }
    </style>
</body>

</html>