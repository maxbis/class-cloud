<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Require teacher authentication
requireTeacherAuth();

// Get session ID from URL
$sessionId = $_GET['id'] ?? null;
if (!$sessionId) {
    header('Location: ' . getUrl('/teacher/dashboard.php'));
    exit;
}

// Verify teacher owns the session
$stmt = $pdo->prepare("
    SELECT s.*, t.username as teacher_name
    FROM sessions s
    JOIN teachers t ON s.teacher_id = t.teacher_id
    WHERE s.session_id = :session_id AND t.teacher_id = :teacher_id
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

// Get all bullet points for this session
$stmt = $pdo->prepare("
    SELECT bp.*, s.name as student_name 
    FROM bullet_points bp
    JOIN students s ON bp.student_id = s.student_id
    WHERE bp.session_id = :session_id
    ORDER BY bp.order_position ASC, bp.created_at ASC
");

$stmt->execute([':session_id' => $sessionId]);
$bulletPoints = $stmt->fetchAll();

// Generate colors for each student
$studentColors = [];
$colors = [
    '#3498db', '#2ecc71', '#e74c3c', '#f1c40f', '#9b59b6', 
    '#1abc9c', '#e67e22', '#34495e', '#16a085', '#d35400'
];
$colorIndex = 0;

foreach ($bulletPoints as $point) {
    if (!isset($studentColors[$point['student_id']])) {
        $studentColors[$point['student_id']] = $colors[$colorIndex % count($colors)];
        $colorIndex++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Display - Interactive Classroom Participation System</title>
    <link rel="stylesheet" href="<?php echo getUrl('/assets/css/style.css'); ?>">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #fff;
        }
        .container {
            max-width: 100%;
            padding: 0;
            margin: 0;
        }
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1001;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0rem 2rem;
        }
        .header-title {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        .header-title h1 {
            margin: 0;
            font-size: 1.5rem;
            color: #2c3e50;
        }
        .header-title .session-name {
            font-size: 1.25rem;
            color: #34495e;
            font-weight: 500;
        }
        .header-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .access-code {
            background:rgb(16, 0, 88);
            padding: 0.2rem 1rem;
            border-radius: 4px;
            font-family: monospace;
            font-size: 1.3rem;
            font-weight: 600;
            letter-spacing: 0.1em;
            color:rgb(2, 254, 2);
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        .access-code:hover {
            background:rgb(0, 0, 0);
        }
        .access-code a {
            text-decoration: none;
            color: inherit;
        }
        .word-cloud {
            margin-top: 80px;
            min-height: calc(100vh - 80px);
            padding: 2rem;
            position: relative;
            background: #f8f9fa;
            overflow: hidden;
        }
        .bullet-point {
            cursor: move;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            background: white;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            min-width: 200px;
            max-width: 300px;
            position: absolute;
            border-left: 4px solid;
            z-index: 1;
            transform: translate(0, 0);
        }
        .bullet-point.dragging {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            z-index: 1000;
            opacity: 0.8;
        }
        .word-cloud.drag-over {
            background: #e9ecef;
            border: 2px dashed #6c757d;
        }
        .drop-indicator {
            position: absolute;
            width: 2px;
            height: 100px;
            background-color: #3498db;
            pointer-events: none;
            z-index: 999;
            display: none;
        }
        .drop-indicator.visible {
            display: block;
        }
        .drop-indicator.horizontal {
            width: 100px;
            height: 2px;
        }
        .bullet-point p {
            margin: 0;
            font-size: 1.1rem;
            line-height: 1.4;
        }
        .bullet-point .tooltip {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            font-size: 0.9rem;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s ease, visibility 0.2s ease;
            pointer-events: none;
            z-index: 1002;
        }
        .bullet-point .tooltip.top {
            bottom: 100%;
            top: auto;
        }
        .bullet-point .tooltip.bottom {
            top: 100%;
            bottom: auto;
        }
        .bullet-point:hover .tooltip {
            opacity: 1;
            visibility: visible;
        }
        .bullet-point .tooltip::after {
            content: '';
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            border-width: 5px;
            border-style: solid;
        }
        .bullet-point .tooltip.top::after {
            top: 100%;
            border-color: rgba(0, 0, 0, 0.8) transparent transparent transparent;
        }
        .bullet-point .tooltip.bottom::after {
            bottom: 100%;
            border-color: transparent transparent rgba(0, 0, 0, 0.8) transparent;
        }
    </style>
</head>
<body>
    <header class="header" style="background-color:rgb(255, 253, 239);">
        <div class="header-content">
            <div class="header-title">
                <div class="session-name"><h1><?php echo htmlspecialchars($session['name'] ?? 'Unnamed Session'); ?></h1></div>
            </div>
            <div class="header-info">
                <div class="access-code">
                    <a href="<?php echo getUrl('/teacher/session.php?id=' . $sessionId); ?>">
                        <?php echo $session['access_code']; ?>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="container" style="padding: 0px;">
        <div class="card fade-in" style="padding: 0px;">
            <div id="word-cloud" class="word-cloud"></div>
        </div>
    </main>

    <script>
        // Initialize word cloud with bullet points
        document.addEventListener('DOMContentLoaded', () => {
            const bulletPoints = <?php echo json_encode($bulletPoints); ?>;
            const studentColors = <?php echo json_encode($studentColors); ?>;
            const wordCloudContainer = document.getElementById('word-cloud');
            
            // Create bullet point elements
            bulletPoints.forEach(point => {
                const bulletPoint = document.createElement('div');
                bulletPoint.className = 'bullet-point';
                bulletPoint.draggable = true;
                bulletPoint.dataset.id = point.bulletpoint_id;
                bulletPoint.style.borderLeftColor = studentColors[point.student_id];
                
                // Set initial position if stored
                if (point.position_x !== null && point.position_y !== null) {
                    bulletPoint.style.left = `${point.position_x}px`;
                    bulletPoint.style.top = `${point.position_y}px`;
                } else {
                    // Random initial position if not stored
                    const randomX = Math.random() * (wordCloudContainer.offsetWidth - 300);
                    const randomY = Math.random() * (wordCloudContainer.offsetHeight - 100);
                    bulletPoint.style.left = `${randomX}px`;
                    bulletPoint.style.top = `${randomY}px`;
                }
                
                bulletPoint.innerHTML = `
                    <p>${point.content}</p>
                    <div class="tooltip">
                        ${point.student_name}<br>
                        ${new Date(point.created_at).toLocaleString()}
                    </div>
                `;

                // Add mouseenter event to check position and adjust tooltip
                bulletPoint.addEventListener('mouseenter', (e) => {
                    const tooltip = bulletPoint.querySelector('.tooltip');
                    const bulletRect = bulletPoint.getBoundingClientRect();
                    const tooltipHeight = 60;
                    const headerHeight = 40;
                    
                    if (bulletRect.top - tooltipHeight > headerHeight) {
                        tooltip.classList.remove('bottom');
                        tooltip.classList.add('top');
                    } else {
                        tooltip.classList.remove('top');
                        tooltip.classList.add('bottom');
                    }
                });

                // Add drag event listeners
                let dragOffsetX = 0;
                let dragOffsetY = 0;

                bulletPoint.addEventListener('dragstart', (e) => {
                    e.dataTransfer.effectAllowed = 'move';
                    bulletPoint.classList.add('dragging');
                    e.dataTransfer.setData('text/plain', point.bulletpoint_id);
                    
                    // Calculate the offset between mouse position and bullet point position
                    const rect = bulletPoint.getBoundingClientRect();
                    dragOffsetX = e.clientX - rect.left;
                    dragOffsetY = e.clientY - rect.top;
                    
                    // Create a transparent image to remove the ghost image
                    const transparent = document.createElement('img');
                    transparent.src = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
                    e.dataTransfer.setDragImage(transparent, 0, 0);
                });

                bulletPoint.addEventListener('dragend', () => {
                    bulletPoint.classList.remove('dragging');
                    saveBulletPointPosition(bulletPoint);
                });

                wordCloudContainer.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    const draggingPoint = document.querySelector('.dragging');
                    if (!draggingPoint) return;

                    const containerRect = wordCloudContainer.getBoundingClientRect();
                    const bulletRect = draggingPoint.getBoundingClientRect();
                    const headerHeight = 5; // Height of the header

                    // Calculate new position accounting for the drag offset
                    let newX = e.clientX - containerRect.left - dragOffsetX;
                    let newY = e.clientY - containerRect.top - dragOffsetY;

                    // Prevent dragging under header
                    if (newY < headerHeight) {
                        newY = headerHeight;
                    }

                    // Prevent dragging outside container boundaries
                    if (newX < 0) {
                        newX = 0;
                    }
                    if (newX + bulletRect.width > containerRect.width) {
                        newX = containerRect.width - bulletRect.width;
                    }
                    if (newY + bulletRect.height > containerRect.height) {
                        newY = containerRect.height - bulletRect.height;
                    }

                    // Apply constrained position
                    draggingPoint.style.left = `${newX}px`;
                    draggingPoint.style.top = `${newY}px`;
                });

                wordCloudContainer.addEventListener('drop', (e) => {
                    e.preventDefault();
                    const draggingPoint = document.querySelector('.dragging');
                    if (!draggingPoint) return;

                    draggingPoint.classList.remove('dragging');
                    saveBulletPointPosition(draggingPoint);
                });

                wordCloudContainer.appendChild(bulletPoint);
            });
        });

        function saveBulletPointPosition(bulletPoint) {
            const rect = bulletPoint.getBoundingClientRect();
            const containerRect = document.getElementById('word-cloud').getBoundingClientRect();
            
            // Calculate position relative to container
            const x = rect.left - containerRect.left;
            const y = rect.top - containerRect.top;

            fetch('<?php echo getUrl('/api/update-position.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    bulletpoint_id: bulletPoint.dataset.id,
                    position_x: x,
                    position_y: y
                })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    console.error('Failed to save position:', data.message);
                }
            })
            .catch(error => {
                console.error('Error saving position:', error);
            });
        }
    </script>
</body>
</html> 