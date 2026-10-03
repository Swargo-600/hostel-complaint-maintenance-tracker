<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_role('Student');

$active = 'complaint-history.php';
$pageTitle = 'My Complaints';
$pageSubtitle = 'Full history of complaints you have submitted';

$stmt = $pdo->prepare("
    SELECT c.*, cc.CategoryName, r.RoomNumber
    FROM Complaint c
    JOIN ComplaintCategory cc ON c.CategoryID = cc.CategoryID
    JOIN Room r ON c.RoomID = r.RoomID
    WHERE c.StudentID = ?
    ORDER BY c.SubmittedDate DESC
");
$stmt->execute([$_SESSION['student_id']]);
$complaints = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Complaints | HostelCare</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main">
        <?php include __DIR__ . '/includes/topbar.php'; ?>

        <section class="content">
            <div class="page-header">
                <h2>Complaint History</h2>
                <p>Every complaint you've submitted, and its current status.</p>
            </div>

            <div class="panel">
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Room</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$complaints): ?>
                                <tr><td colspan="7">No complaints submitted yet.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($complaints as $c): ?>
                                <tr>
                                    <td>#<?= str_pad($c['ComplaintID'], 3, '0', STR_PAD_LEFT) ?></td>
                                    <td><?= h($c['Title']) ?></td>
                                    <td><?= h($c['CategoryName']) ?></td>
                                    <td><?= h($c['RoomNumber']) ?></td>
                                    <td><span class="badge <?= badge_class($c['Priority']) ?>"><?= h($c['Priority']) ?></span></td>
                                    <td><span class="badge <?= badge_class($c['Status']) ?>"><?= h($c['Status']) ?></span></td>
                                    <td><?= date('d M Y', strtotime($c['SubmittedDate'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
</div>
</body>
</html>
