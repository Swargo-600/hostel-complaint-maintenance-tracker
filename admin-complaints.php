<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_role('Admin');

$active = 'admin-complaints.php';
$pageTitle = 'Complaint Management';
$pageSubtitle = 'Review, verify and manage hostel complaints';

// Quick status-change action (Verify / Reject / Close etc.)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complaint_id'], $_POST['new_status'])) {
    $stmt = $pdo->prepare("UPDATE Complaint SET Status = ? WHERE ComplaintID = ?");
    $stmt->execute([$_POST['new_status'], $_POST['complaint_id']]);

    $hist = $pdo->prepare("
        INSERT INTO ComplaintStatusHistory (ComplaintID, UpdatedBy, Status, Remarks, UpdateTime)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $hist->execute([$_POST['complaint_id'], $_SESSION['user_id'], $_POST['new_status'], 'Status updated by admin.']);

    $_SESSION['flash'] = "Complaint #" . $_POST['complaint_id'] . " updated to " . $_POST['new_status'] . ".";
    header("Location: admin-complaints.php");
    exit;
}

// Filters
$search   = trim($_GET['search'] ?? '');
$category = $_GET['category'] ?? '';

$sql = "
    SELECT c.*, s.StudentNumber, u.FullName AS StudentName, cc.CategoryName, r.RoomNumber
    FROM Complaint c
    JOIN Student s ON c.StudentID = s.StudentID
    JOIN Users u ON s.UserID = u.UserID
    JOIN ComplaintCategory cc ON c.CategoryID = cc.CategoryID
    JOIN Room r ON c.RoomID = r.RoomID
    WHERE 1=1
";
$params = [];
if ($search !== '') {
    $sql .= " AND c.Title LIKE ?";
    $params[] = "%$search%";
}
if ($category !== '') {
    $sql .= " AND cc.CategoryName = ?";
    $params[] = $category;
}
$sql .= " ORDER BY c.SubmittedDate DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$complaints = $stmt->fetchAll();

$categories = $pdo->query("SELECT CategoryName FROM ComplaintCategory ORDER BY CategoryName")->fetchAll();

// Stats
$statTotal    = $pdo->query("SELECT COUNT(*) FROM Complaint")->fetchColumn();
$statPending  = $pdo->query("SELECT COUNT(*) FROM Complaint WHERE Status IN ('Submitted','Verified','Assigned')")->fetchColumn();
$statResolved = $pdo->query("SELECT COUNT(*) FROM Complaint WHERE Status IN ('Resolved','Closed')")->fetchColumn();
$statEmergency= $pdo->query("SELECT COUNT(*) FROM Complaint WHERE Priority = 'Emergency'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaint Management | HostelCare</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main">
        <?php include __DIR__ . '/includes/topbar.php'; ?>

        <section class="content">
            <div class="page-header">
                <h2>All Complaints</h2>
                <p>Monitor and manage complaints submitted by students.</p>
            </div>

            <div class="stats">
                <div class="stat-card">
                    <div class="stat-label">Total Complaints</div>
                    <div class="stat-number stat-blue"><?= (int)$statTotal ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Pending</div>
                    <div class="stat-number stat-orange"><?= (int)$statPending ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Resolved</div>
                    <div class="stat-number stat-green"><?= (int)$statResolved ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Emergency</div>
                    <div class="stat-number stat-red"><?= (int)$statEmergency ?></div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header"><h3>Complaint List</h3></div>

                <form class="filters" method="get" action="admin-complaints.php">
                    <input type="text" name="search" class="filter-input" placeholder="Search complaint..." value="<?= h($search) ?>">
                    <select name="category" class="filter-select" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= h($cat['CategoryName']) ?>" <?= $category === $cat['CategoryName'] ? 'selected' : '' ?>>
                                <?= h($cat['CategoryName']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary">Filter</button>
                </form>

                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th><th>Student</th><th>Complaint</th><th>Category</th>
                                <th>Room</th><th>Priority</th><th>Status</th><th>Date</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$complaints): ?>
                                <tr><td colspan="9">No complaints found.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($complaints as $c): ?>
                                <tr>
                                    <td>#<?= str_pad($c['ComplaintID'], 3, '0', STR_PAD_LEFT) ?></td>
                                    <td><?= h($c['StudentName']) ?></td>
                                    <td>
                                        <div class="complaint-title"><?= h($c['Title']) ?></div>
                                        <div class="complaint-description"><?= h($c['Description']) ?></div>
                                    </td>
                                    <td><?= h($c['CategoryName']) ?></td>
                                    <td><?= h($c['RoomNumber']) ?></td>
                                    <td><span class="badge <?= badge_class($c['Priority']) ?>"><?= h($c['Priority']) ?></span></td>
                                    <td><span class="badge <?= badge_class($c['Status']) ?>"><?= h($c['Status']) ?></span></td>
                                    <td><?= date('d M Y', strtotime($c['SubmittedDate'])) ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if ($c['Status'] === 'Submitted'): ?>
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="complaint_id" value="<?= $c['ComplaintID'] ?>">
                                                    <input type="hidden" name="new_status" value="Verified">
                                                    <button type="submit" class="action-btn view-btn">Verify</button>
                                                </form>
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="complaint_id" value="<?= $c['ComplaintID'] ?>">
                                                    <input type="hidden" name="new_status" value="Rejected">
                                                    <button type="submit" class="action-btn">Reject</button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if (in_array($c['Status'], ['Verified'])): ?>
                                                <a href="admin-assignment.php?complaint_id=<?= $c['ComplaintID'] ?>" class="action-btn assign-btn">Assign</a>
                                            <?php endif; ?>

                                            <?php if ($c['Status'] === 'Resolved'): ?>
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="complaint_id" value="<?= $c['ComplaintID'] ?>">
                                                    <input type="hidden" name="new_status" value="Closed">
                                                    <button type="submit" class="action-btn view-btn">Close</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
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
