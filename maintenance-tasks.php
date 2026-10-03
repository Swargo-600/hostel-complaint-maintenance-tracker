<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_role('MaintenanceStaff');

$active = 'maintenance-tasks.php';
$pageTitle = 'My Tasks';
$pageSubtitle = 'Update the status of complaints assigned to you';

$staffId = $_SESSION['staff_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $complaintId = $_POST['complaint_id'];
    $newStatus   = $_POST['new_status'];
    $remarks     = trim($_POST['remarks'] ?? '');

    $update = $pdo->prepare("UPDATE Complaint SET Status = ? WHERE ComplaintID = ?");
    $update->execute([$newStatus, $complaintId]);

    if ($newStatus === 'Resolved') {
        $pdo->prepare("UPDATE Complaint SET ResolutionDate = NOW() WHERE ComplaintID = ?")->execute([$complaintId]);
    }

    $pdo->prepare("
        INSERT INTO ComplaintStatusHistory (ComplaintID, UpdatedBy, Status, Remarks, UpdateTime)
        VALUES (?, ?, ?, ?, NOW())
    ")->execute([$complaintId, $_SESSION['user_id'], $newStatus, $remarks ?: 'Status updated by maintenance staff.']);

    $_SESSION['flash'] = "Task #$complaintId updated to $newStatus.";
    header("Location: maintenance-tasks.php");
    exit;
}

$tasks = $pdo->prepare("
    SELECT c.ComplaintID, c.Title, c.Description, c.Priority, c.Status, r.RoomNumber, cc.CategoryName
    FROM ComplaintAssignment ca
    JOIN Complaint c ON ca.ComplaintID = c.ComplaintID
    JOIN Room r ON c.RoomID = r.RoomID
    JOIN ComplaintCategory cc ON c.CategoryID = cc.CategoryID
    WHERE ca.StaffID = ?
    ORDER BY FIELD(c.Status, 'Assigned','In Progress','Resolved','Closed'), c.SubmittedDate DESC
");
$tasks->execute([$staffId]);
$allTasks = $tasks->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tasks | HostelCare</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main">
        <?php include __DIR__ . '/includes/topbar.php'; ?>

        <section class="content">
            <div class="page-header">
                <h2>Assigned Tasks</h2>
                <p>Move a task forward as you work on it.</p>
            </div>

            <div class="panel">
                <div class="task-list">
                    <?php if (!$allTasks): ?>
                        <p style="color:#6b7280;">No tasks assigned to you yet.</p>
                    <?php endif; ?>

                    <?php foreach ($allTasks as $t): ?>
                        <div class="task-item">
                            <div class="task-top">
                                <div>
                                    <div class="task-title"><?= h($t['Title']) ?></div>
                                    <div class="task-room"><?= h($t['RoomNumber']) ?> • <?= h($t['CategoryName']) ?></div>
                                </div>
                                <span class="badge <?= badge_class($t['Priority']) ?>"><?= h($t['Priority']) ?></span>
                            </div>
                            <div class="task-description"><?= h($t['Description']) ?></div>
                            <div class="task-footer">
                                <span class="badge <?= badge_class($t['Status']) ?>"><?= h($t['Status']) ?></span>
                            </div>

                            <?php if (!in_array($t['Status'], ['Resolved', 'Closed'])): ?>
                                <form method="post" style="margin-top:12px; display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                                    <input type="hidden" name="complaint_id" value="<?= $t['ComplaintID'] ?>">
                                    <select name="new_status" required>
                                        <option value="In Progress" <?= $t['Status'] === 'Assigned' ? 'selected' : '' ?>>In Progress</option>
                                        <option value="Resolved">Resolved</option>
                                    </select>
                                    <input type="text" name="remarks" placeholder="Remarks (optional)" style="flex:1; min-width:150px;">
                                    <button type="submit" class="btn btn-primary">Update</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>
</div>
</body>
</html>
