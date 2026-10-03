<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_role('Student');

$active = 'student-dashboard.php';
$pageTitle = 'Student Dashboard';
$pageSubtitle = 'Track your submitted complaints and room status';

$studentId = $_SESSION['student_id'];

// ----- Stats -----
$total = $pdo->prepare("SELECT COUNT(*) FROM Complaint WHERE StudentID = ?");
$total->execute([$studentId]);
$totalCount = $total->fetchColumn();

$pending = $pdo->prepare("SELECT COUNT(*) FROM Complaint WHERE StudentID = ? AND Status IN ('Submitted','Verified','Assigned','In Progress')");
$pending->execute([$studentId]);
$pendingCount = $pending->fetchColumn();

$resolved = $pdo->prepare("SELECT COUNT(*) FROM Complaint WHERE StudentID = ? AND Status IN ('Resolved','Closed')");
$resolved->execute([$studentId]);
$resolvedCount = $resolved->fetchColumn();

$emergency = $pdo->prepare("SELECT COUNT(*) FROM Complaint WHERE StudentID = ? AND Priority = 'Emergency'");
$emergency->execute([$studentId]);
$emergencyCount = $emergency->fetchColumn();

// ----- Recent complaints -----
$recent = $pdo->prepare("
    SELECT c.*, cc.CategoryName, r.RoomNumber
    FROM Complaint c
    JOIN ComplaintCategory cc ON c.CategoryID = cc.CategoryID
    JOIN Room r ON c.RoomID = r.RoomID
    WHERE c.StudentID = ?
    ORDER BY c.SubmittedDate DESC
    LIMIT 3
");
$recent->execute([$studentId]);
$recentComplaints = $recent->fetchAll();

// ----- Room details -----
$roomStmt = $pdo->prepare("
    SELECT r.RoomNumber, r.FloorNo, r.Status, h.HostelName
    FROM Room r JOIN Hostel h ON r.HostelID = h.HostelID
    WHERE r.RoomID = ?
");
$roomStmt->execute([$_SESSION['room_id']]);
$room = $roomStmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | HostelCare</title>
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
                <p>Here is an overview of your room details and active complaints.</p>
            </div>

            <div class="stats">
                <div class="stat-card">
                    <div class="stat-label">Total Complaints</div>
                    <div class="stat-number blue"><?= (int)$totalCount ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Pending / In Progress</div>
                    <div class="stat-number orange"><?= (int)$pendingCount ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Resolved</div>
                    <div class="stat-number green"><?= (int)$resolvedCount ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Emergency Flags</div>
                    <div class="stat-number red"><?= (int)$emergencyCount ?></div>
                </div>
            </div>

            <div class="grid-2">
                <div>
                    <div class="panel">
                        <div class="panel-header">
                            <h3>Recent Complaints</h3>
                            <a href="complaint-history.php" class="view-all">View All History</a>
                        </div>

                        <div class="task-list">
                            <?php if (!$recentComplaints): ?>
                                <p>You haven't submitted any complaints yet.</p>
                            <?php endif; ?>

                            <?php foreach ($recentComplaints as $c): ?>
                                <div class="task-item">
                                    <div class="task-top">
                                        <div>
                                            <div class="task-title"><?= h($c['Title']) ?></div>
                                            <div class="task-room"><?= h($c['RoomNumber']) ?> • <?= h($c['CategoryName']) ?></div>
                                        </div>
                                        <span class="badge <?= badge_class($c['Priority']) ?>"><?= h($c['Priority']) ?></span>
                                    </div>
                                    <div class="task-description"><?= h($c['Description']) ?></div>
                                    <div class="task-footer">
                                        <span>Submitted: <?= date('d F Y', strtotime($c['SubmittedDate'])) ?></span>
                                        <span class="badge <?= badge_class($c['Status']) ?>"><?= h($c['Status']) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="panel">
                        <div class="panel-header"><h3>My Room Details</h3></div>
                        <?php if ($room): ?>
                            <p style="margin-bottom: 10px;"><strong>Hostel:</strong> <?= h($room['HostelName']) ?></p>
                            <p style="margin-bottom: 10px;"><strong>Room No:</strong> <?= h($room['RoomNumber']) ?> (Floor <?= h($room['FloorNo']) ?>)</p>
                            <p><strong>Status:</strong> <span class="badge <?= badge_class($room['Status']) ?>"><?= h($room['Status']) ?></span></p>
                        <?php else: ?>
                            <p>No room assigned yet.</p>
                        <?php endif; ?>
                    </div>

                    <div class="panel">
                        <div class="panel-header"><h3>Quick Actions</h3></div>
                        <p style="margin-bottom: 15px; color: #6b7280; font-size: 14px;">
                            Experiencing an issue with electricity, water, or internet in your room?
                        </p>
                        <a href="student-complaint.php" class="btn btn-primary" style="display: block; text-align: center;">
                            + Submit New Complaint
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>
</body>
</html>
