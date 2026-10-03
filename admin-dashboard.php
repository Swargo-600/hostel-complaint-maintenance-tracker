<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_role('Admin');

$active = 'admin-dashboard.php';
$pageTitle = 'Admin Dashboard';
$pageSubtitle = 'Monitor hostel maintenance operations';

$total    = $pdo->query("SELECT COUNT(*) FROM Complaint")->fetchColumn();
$pending  = $pdo->query("SELECT COUNT(*) FROM Complaint WHERE Status IN ('Submitted','Verified')")->fetchColumn();
$progress = $pdo->query("SELECT COUNT(*) FROM Complaint WHERE Status IN ('Assigned','In Progress')")->fetchColumn();
$resolved = $pdo->query("SELECT COUNT(*) FROM Complaint WHERE Status IN ('Resolved','Closed')")->fetchColumn();

$recent = $pdo->query("
    SELECT c.ComplaintID, s.StudentNumber, c.Title, c.Priority, c.Status
    FROM Complaint c JOIN Student s ON c.StudentID = s.StudentID
    ORDER BY c.SubmittedDate DESC LIMIT 5
")->fetchAll();

$staffLoad = $pdo->query("
    SELECT u.FullName, ms.Department, COUNT(ca.AssignmentID) AS TaskCount
    FROM MaintenanceStaff ms
    JOIN Users u ON u.UserID = ms.UserID
    LEFT JOIN ComplaintAssignment ca ON ca.StaffID = ms.StaffID
    GROUP BY ms.StaffID
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | HostelCare</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main">
        <?php include __DIR__ . '/includes/topbar.php'; ?>

        <section class="content">
            <div class="page-header">
                <h2>System Overview</h2>
                <p>Monitor complaints, users and maintenance staff.</p>
            </div>

            <div class="stats">
                <div class="stat-card">
                    <div class="stat-top">
                        <span class="stat-label">TOTAL COMPLAINTS</span>
                        <span class="stat-icon">▤</span>
                    </div>
                    <div class="stat-value"><?= (int)$total ?></div>
                    <div class="stat-note">All complaints</div>
                </div>

                <div class="stat-card">
                    <div class="stat-top">
                        <span class="stat-label">PENDING</span>
                        <span class="stat-icon">!</span>
                    </div>
                    <div class="stat-value"><?= (int)$pending ?></div>
                    <div class="stat-note">Need verification</div>
                </div>

                <div class="stat-card">
                    <div class="stat-top">
                        <span class="stat-label">IN PROGRESS</span>
                        <span class="stat-icon">◔</span>
                    </div>
                    <div class="stat-value"><?= (int)$progress ?></div>
                    <div class="stat-note">Maintenance underway</div>
                </div>

                <div class="stat-card">
                    <div class="stat-top">
                        <span class="stat-label">RESOLVED</span>
                        <span class="stat-icon">✓</span>
                    </div>
                    <div class="stat-value"><?= (int)$resolved ?></div>
                    <div class="stat-note">Completed complaints</div>
                </div>
            </div>

            <div class="grid-2">
                <div class="panel">
                    <h3>Recent Complaints</h3>
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr><th>ID</th><th>Student</th><th>Complaint</th><th>Priority</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent as $c): ?>
                                    <tr>
                                        <td>#<?= str_pad($c['ComplaintID'], 3, '0', STR_PAD_LEFT) ?></td>
                                        <td><?= h($c['StudentNumber']) ?></td>
                                        <td><?= h($c['Title']) ?></td>
                                        <td><span class="badge <?= badge_class($c['Priority']) ?>"><?= h($c['Priority']) ?></span></td>
                                        <td><span class="badge <?= badge_class($c['Status']) ?>"><?= h($c['Status']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="panel">
                    <h3>Maintenance Staff</h3>
                    <div class="timeline">
                        <?php foreach ($staffLoad as $s): ?>
                            <div class="timeline-item">
                                <div class="timeline-dot"></div>
                                <div>
                                    <div class="timeline-title"><?= h($s['FullName']) ?></div>
                                    <div class="timeline-meta"><?= h($s['Department']) ?> · <?= (int)$s['TaskCount'] ?> assigned tasks</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>
</body>
</html>
