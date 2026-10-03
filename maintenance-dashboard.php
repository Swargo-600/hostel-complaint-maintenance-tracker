<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_role('MaintenanceStaff');

$active = 'maintenance-dashboard.php';
$pageTitle = 'Maintenance Dashboard';
$pageSubtitle = 'Your assigned maintenance tasks';

$staffId = $_SESSION['staff_id'];

$total = $pdo->prepare("SELECT COUNT(*) FROM ComplaintAssignment WHERE StaffID = ?");
$total->execute([$staffId]);
$totalCount = $total->fetchColumn();

$active_stmt = $pdo->prepare("
    SELECT COUNT(*) FROM ComplaintAssignment ca
    JOIN Complaint c ON ca.ComplaintID = c.ComplaintID
    WHERE ca.StaffID = ? AND c.Status IN ('Assigned','In Progress')
");
$active_stmt->execute([$staffId]);
$activeCount = $active_stmt->fetchColumn();

$done = $pdo->prepare("
    SELECT COUNT(*) FROM ComplaintAssignment ca
    JOIN Complaint c ON ca.ComplaintID = c.ComplaintID
    WHERE ca.StaffID = ? AND c.Status IN ('Resolved','Closed')
");
$done->execute([$staffId]);
$doneCount = $done->fetchColumn();

$tasks = $pdo->prepare("
    SELECT c.ComplaintID, c.Title, c.Priority, c.Status, r.RoomNumber, cc.CategoryName
    FROM ComplaintAssignment ca
    JOIN Complaint c ON ca.ComplaintID = c.ComplaintID
    JOIN Room r ON c.RoomID = r.RoomID
    JOIN ComplaintCategory cc ON c.CategoryID = cc.CategoryID
    WHERE ca.StaffID = ?
    ORDER BY ca.AssignedDate DESC
    LIMIT 5
");
$tasks->execute([$staffId]);
$recentTasks = $tasks->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Dashboard | HostelCare</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main">
        <?php include __DIR__ . '/includes/topbar.php'; ?>

        <section class="content">
            <div class="page-header">
                <h2>Welcome, <?= h($_SESSION['full_name']) ?></h2>
                <p>Here's a summary of your maintenance workload.</p>
            </div>

            <div class="stats">
                <div class="stat-card">
                    <div class="stat-label">Total Assigned</div>
                    <div class="stat-number blue"><?= (int)$totalCount ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Active Tasks</div>
                    <div class="stat-number orange"><?= (int)$activeCount ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Completed</div>
                    <div class="stat-number green"><?= (int)$doneCount ?></div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <h3>Recent Tasks</h3>
                    <a href="maintenance-tasks.php" class="view-all">View All Tasks</a>
                </div>

                <div class="task-list">
                    <?php if (!$recentTasks): ?>
                        <p style="color:#6b7280;">No tasks assigned yet.</p>
                    <?php endif; ?>
                    <?php foreach ($recentTasks as $t): ?>
                        <div class="task-item">
                            <div class="task-top">
                                <div>
                                    <div class="task-title"><?= h($t['Title']) ?></div>
                                    <div class="task-room"><?= h($t['RoomNumber']) ?> • <?= h($t['CategoryName']) ?></div>
                                </div>
                                <span class="badge <?= badge_class($t['Priority']) ?>"><?= h($t['Priority']) ?></span>
                            </div>
                            <div class="task-footer">
                                <span class="badge <?= badge_class($t['Status']) ?>"><?= h($t['Status']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>
</div>
</body>
</html>
