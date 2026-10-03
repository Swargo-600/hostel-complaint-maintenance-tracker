<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_role('Student');

$active = 'student-complaint.php';
$pageTitle = 'Submit Complaint';
$pageSubtitle = 'Report a hostel maintenance problem';

$studentId = $_SESSION['student_id'];
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryId  = $_POST['category_id'] ?? '';
    $roomId      = $_POST['room_id'] ?? '';
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority    = $_POST['priority'] ?? 'Medium';

    if (!$categoryId || !$roomId || !$title || !$description) {
        $errors[] = "Please fill in category, room, title and description.";
    }

    if (!$errors) {
        $stmt = $pdo->prepare("
            INSERT INTO Complaint (StudentID, CategoryID, RoomID, Title, Description, Priority, Status, SubmittedDate)
            VALUES (?, ?, ?, ?, ?, ?, 'Submitted', NOW())
        ");
        $stmt->execute([$studentId, $categoryId, $roomId, $title, $description, $priority]);

        $_SESSION['flash'] = "Complaint submitted successfully.";
        header("Location: student-dashboard.php");
        exit;
    }
}

$categories = $pdo->query("SELECT * FROM ComplaintCategory ORDER BY CategoryName")->fetchAll();

$rooms = $pdo->query("
    SELECT r.RoomID, r.RoomNumber, h.HostelName
    FROM Room r JOIN Hostel h ON r.HostelID = h.HostelID
    ORDER BY h.HostelName, r.RoomNumber
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Complaint | HostelCare</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main">
        <?php include __DIR__ . '/includes/topbar.php'; ?>

        <section class="content">
            <div class="page-header">
                <h2>New Complaint</h2>
                <p>Please provide accurate information.</p>
            </div>

            <div class="panel">
                <div class="alert alert-info">
                    <strong>Important:</strong> For serious safety, security, electrical
                    or water problems, select Emergency priority.
                </div>

                <?php foreach ($errors as $e): ?>
                    <div class="alert alert-info" style="background:#fee2e2;color:#991b1b;"><?= h($e) ?></div>
                <?php endforeach; ?>

                <form action="student-complaint.php" method="post" class="form-grid">

                    <div class="form-group">
                        <label>Complaint Category</label>
                        <select name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['CategoryID'] ?>"><?= h($cat['CategoryName']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Room</label>
                        <select name="room_id" required>
                            <option value="">Select Room</option>
                            <?php foreach ($rooms as $r): ?>
                                <option value="<?= $r['RoomID'] ?>" <?= (string)$_SESSION['room_id'] === (string)$r['RoomID'] ? 'selected' : '' ?>>
                                    <?= h($r['HostelName']) ?> - <?= h($r['RoomNumber']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Complaint Title</label>
                        <input type="text" name="title" placeholder="Example: Light Not Working" required>
                    </div>

                    <div class="form-group">
                        <label>Priority</label>
                        <select name="priority" required>
                            <option value="Low">Low</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="High">High</option>
                            <option value="Emergency">Emergency</option>
                        </select>
                    </div>

                    <div class="form-group full">
                        <label>Description</label>
                        <textarea name="description" placeholder="Describe the problem..." required></textarea>
                    </div>

                    <div class="form-group full">
                        <button type="submit" class="btn btn-primary">Submit Complaint</button>
                    </div>

                </form>
            </div>
        </section>
    </main>
</div>
</body>
</html>
