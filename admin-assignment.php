<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_role('Admin');

$active = 'admin-assignment.php';
$pageTitle = 'Staff Assignment';
$pageSubtitle = 'Assign maintenance staff to verified complaints';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $complaintId = $_POST['complaint_id'];
    $staffId     = $_POST['staff_id'];

    $pdo->prepare("
        INSERT INTO ComplaintAssignment (ComplaintID, StaffID, AssignedBy, AssignedDate)
        VALUES (?, ?, ?, NOW())
    ")->execute([$complaintId, $staffId, $_SESSION['user_id']]);

    $pdo->prepare("UPDATE Complaint SET Status = 'Assigned' WHERE ComplaintID = ?")->execute([$complaintId]);

    $pdo->prepare("
        INSERT INTO ComplaintStatusHistory (ComplaintID, UpdatedBy, Status, Remarks, UpdateTime)
        VALUES (?, ?, 'Assigned', 'Assigned to maintenance staff.', NOW())
    ")->execute([$complaintId, $_SESSION['user_id']]);

    $_SESSION['flash'] = "Complaint #$complaintId assigned successfully.";
    header("Location: admin-assignment.php");
    exit;
}

$preselect = $_GET['complaint_id'] ?? '';

$unassigned = $pdo->query("
    SELECT c.ComplaintID, c.Title, c.Priority, cc.CategoryName, u.FullName AS StudentName
    FROM Complaint c
    JOIN Student s ON c.StudentID = s.StudentID
    JOIN Users u ON s.UserID = u.UserID
    JOIN ComplaintCategory cc ON c.CategoryID = cc.CategoryID
    WHERE c.Status = 'Verified'
    ORDER BY c.SubmittedDate
")->fetchAll();

$staff = $pdo->query("
    SELECT ms.StaffID, u.FullName, ms.Department, ms.Skill
    FROM MaintenanceStaff ms JOIN Users u ON ms.UserID = u.UserID
    ORDER BY u.FullName
")->fetchAll();

$assignments = $pdo->query("
    SELECT ca.AssignmentID, c.ComplaintID, c.Title, u.FullName AS StaffName, ca.AssignedDate
    FROM ComplaintAssignment ca
    JOIN Complaint c ON ca.ComplaintID = c.ComplaintID
    JOIN MaintenanceStaff ms ON ca.StaffID = ms.StaffID
    JOIN Users u ON ms.UserID = u.UserID
    ORDER BY ca.AssignedDate DESC
    LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Assignment | HostelCare</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main">
        <?php include __DIR__ . '/includes/topbar.php'; ?>

        <section class="content">
            <div class="page-header">
                <h2>Assign Maintenance Staff</h2>
                <p>Verified complaints must be assigned to a staff member.</p>
            </div>

            <div class="grid-2">
                <div class="panel">
                    <h3>Assign a Complaint</h3>

                    <?php if (!$unassigned): ?>
                        <p style="margin-top:10px;color:#6b7280;">No verified complaints waiting for assignment.</p>
                    <?php else: ?>
                        <form method="post" style="margin-top:15px;">
                            <div class="form-group">
                                <label for="complaint_id">Complaint</label>
                                <select id="complaint_id" name="complaint_id" required>
                                    <?php foreach ($unassigned as $c): ?>
                                        <option value="<?= $c['ComplaintID'] ?>" <?= $preselect == $c['ComplaintID'] ? 'selected' : '' ?>>
                                            #<?= str_pad($c['ComplaintID'], 3, '0', STR_PAD_LEFT) ?> - <?= h($c['Title']) ?> (<?= h($c['CategoryName']) ?>, <?= h($c['Priority']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="staff_id">Maintenance Staff</label>
                                <select id="staff_id" name="staff_id" required>
                                    <?php foreach ($staff as $s): ?>
                                        <option value="<?= $s['StaffID'] ?>">
                                            <?= h($s['FullName']) ?> - <?= h($s['Department']) ?> (<?= h($s['Skill']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary">Assign Staff</button>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="panel">
                    <h3>Recent Assignments</h3>
                    <div class="table-wrapper">
                        <table class="table">
                            <thead><tr><th>Complaint</th><th>Assigned To</th><th>Date</th></tr></thead>
                            <tbody>
                                <?php foreach ($assignments as $a): ?>
                                    <tr>
                                        <td>#<?= str_pad($a['ComplaintID'], 3, '0', STR_PAD_LEFT) ?> <?= h($a['Title']) ?></td>
                                        <td><?= h($a['StaffName']) ?></td>
                                        <td><?= date('d M Y', strtotime($a['AssignedDate'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>
</body>
</html>
