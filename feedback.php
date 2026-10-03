<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_role('Student');

$active = 'feedback.php';
$pageTitle = 'Feedback';
$pageSubtitle = 'Rate completed maintenance services';

$studentId = $_SESSION['student_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $complaintId = $_POST['complaint_id'] ?? '';
    $rating      = $_POST['rating'] ?? '';
    $comment     = trim($_POST['comment'] ?? '');

    if ($complaintId && $rating) {
        $stmt = $pdo->prepare("
            INSERT INTO Feedback (ComplaintID, StudentID, Rating, Comment, FeedbackDate)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$complaintId, $studentId, $rating, $comment]);
        $_SESSION['flash'] = "Thank you! Your feedback has been recorded.";
        header("Location: feedback.php");
        exit;
    }
}

// Resolved/Closed complaints that don't have feedback yet.
$pending = $pdo->prepare("
    SELECT c.ComplaintID, c.Title, c.ResolutionDate
    FROM Complaint c
    LEFT JOIN Feedback f ON f.ComplaintID = c.ComplaintID
    WHERE c.StudentID = ? AND c.Status IN ('Resolved','Closed') AND f.FeedbackID IS NULL
    ORDER BY c.ResolutionDate DESC
");
$pending->execute([$studentId]);
$pendingComplaints = $pending->fetchAll();

// Feedback already given.
$given = $pdo->prepare("
    SELECT f.*, c.Title FROM Feedback f
    JOIN Complaint c ON c.ComplaintID = f.ComplaintID
    WHERE f.StudentID = ?
    ORDER BY f.FeedbackDate DESC
");
$given->execute([$studentId]);
$givenFeedback = $given->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback | HostelCare</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main">
        <?php include __DIR__ . '/includes/topbar.php'; ?>

        <section class="content">
            <div class="page-header">
                <h2>Give Feedback</h2>
                <p>Your feedback helps improve hostel services.</p>
            </div>

            <div class="grid-2">
                <div class="panel">
                    <h3>Complaints Awaiting Feedback</h3>

                    <?php if (!$pendingComplaints): ?>
                        <p style="margin-top:10px;color:#6b7280;">No resolved complaints are waiting for feedback right now.</p>
                    <?php else: ?>
                        <form action="feedback.php" method="post" style="margin-top:15px;">
                            <div class="form-group">
                                <label for="complaint_id">Complaint</label>
                                <select id="complaint_id" name="complaint_id" required>
                                    <?php foreach ($pendingComplaints as $c): ?>
                                        <option value="<?= $c['ComplaintID'] ?>">
                                            #<?= str_pad($c['ComplaintID'], 3, '0', STR_PAD_LEFT) ?> - <?= h($c['Title']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="rating">Rating</label>
                                <select id="rating" name="rating" required>
                                    <option value="">Select Rating</option>
                                    <option value="5">5 - Excellent</option>
                                    <option value="4">4 - Good</option>
                                    <option value="3">3 - Average</option>
                                    <option value="2">2 - Poor</option>
                                    <option value="1">1 - Very Poor</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="comment">Comment</label>
                                <textarea id="comment" name="comment" placeholder="Tell us about your experience..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">Submit Feedback</button>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="panel">
                    <h3>Your Past Feedback</h3>
                    <div class="task-list" style="margin-top:10px;">
                        <?php if (!$givenFeedback): ?>
                            <p style="color:#6b7280;">You haven't left any feedback yet.</p>
                        <?php endif; ?>
                        <?php foreach ($givenFeedback as $f): ?>
                            <div class="task-item">
                                <div class="task-top">
                                    <div class="task-title"><?= h($f['Title']) ?></div>
                                    <span class="badge resolved"><?= h($f['Rating']) ?> / 5</span>
                                </div>
                                <div class="task-description"><?= h($f['Comment']) ?></div>
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
