<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$errors = [];
$old = ['full_name' => '', 'email' => '', 'phone' => '', 'student_number' => '', 'department' => '', 'room_id' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['full_name']      = trim($_POST['full_name'] ?? '');
    $old['email']          = trim($_POST['email'] ?? '');
    $old['phone']          = trim($_POST['phone'] ?? '');
    $old['student_number'] = trim($_POST['student_number'] ?? '');
    $old['department']     = trim($_POST['department'] ?? '');
    $old['room_id']        = $_POST['room_id'] ?? '';
    $password              = $_POST['password'] ?? '';
    $confirmPassword       = $_POST['confirm_password'] ?? '';

    if (!$old['full_name'] || !$old['email'] || !$password) {
        $errors[] = "Name, email and password are required.";
    }
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
    }
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }
    if (!$old['student_number']) {
        $errors[] = "Student number is required.";
    }

    if (!$errors) {
        $check = $pdo->prepare("SELECT UserID FROM Users WHERE Email = ?");
        $check->execute([$old['email']]);
        if ($check->fetch()) {
            $errors[] = "An account with this email already exists. Try logging in instead.";
        }
    }

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            // NOTE: storing the plain password here to match how the rest
            // of this project's sample data (and login_process.php) works.
            // In a real app, replace this with:
            //   password_hash($password, PASSWORD_DEFAULT)
            // and verify with password_verify() in login_process.php.
            $stmt = $pdo->prepare("
                INSERT INTO Users (FullName, Email, Phone, Password, Role, Status)
                VALUES (?, ?, ?, ?, 'Student', 'Active')
            ");
            $stmt->execute([$old['full_name'], $old['email'], $old['phone'], $password]);
            $userId = $pdo->lastInsertId();

            $stmt2 = $pdo->prepare("
                INSERT INTO Student (UserID, StudentNumber, Department, RoomID)
                VALUES (?, ?, ?, ?)
            ");
            $stmt2->execute([$userId, $old['student_number'], $old['department'], $old['room_id'] ?: null]);
            $studentId = $pdo->lastInsertId();

            $pdo->commit();

            // Log the new student straight in.
            $_SESSION['user_id']    = $userId;
            $_SESSION['full_name']  = $old['full_name'];
            $_SESSION['email']      = $old['email'];
            $_SESSION['role']       = 'Student';
            $_SESSION['student_id'] = $studentId;
            $_SESSION['room_id']    = $old['room_id'] ?: null;
            $_SESSION['flash']      = "Welcome, " . $old['full_name'] . "! Your account has been created.";

            header("Location: student-dashboard.php");
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Could not create account: " . $e->getMessage();
        }
    }
}

$rooms = $pdo->query("
    SELECT r.RoomID, r.RoomNumber, h.HostelName
    FROM Room r JOIN Hostel h ON r.HostelID = h.HostelID
    WHERE r.Status = 'Available'
    ORDER BY h.HostelName, r.RoomNumber
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | HostelCare</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="login-page">

    <div class="login-left">
        <div class="brand">
            <div class="brand-icon">🏠</div>
            <span>HostelCare</span>
        </div>
        <div class="welcome-content">
            <h1>Create Your<br>Student Account</h1>
            <p>Registration is open to students only. Admin and Maintenance
               Staff accounts are created by the hostel administration for
               security reasons — see the README for why.</p>
            <div class="features">
                <div>✓ Submit maintenance complaints</div>
                <div>✓ Track complaint progress</div>
                <div>✓ Receive notifications</div>
                <div>✓ Rate completed services</div>
            </div>
        </div>
    </div>

    <div class="login-right">
        <div class="login-box">
            <h2>Student Registration</h2>
            <p class="login-subtitle">Fill in your details to create an account</p>

            <?php foreach ($errors as $e): ?>
                <div class="alert alert-info" style="background:#fee2e2;color:#991b1b; margin-bottom: 10px;">
                    <?= h($e) ?>
                </div>
            <?php endforeach; ?>

            <form action="register.php" method="post" class="login-form">

                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" value="<?= h($old['full_name']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= h($old['email']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone" value="<?= h($old['phone']) ?>">
                </div>

                <div class="form-group">
                    <label for="student_number">Student Number</label>
                    <input type="text" id="student_number" name="student_number" placeholder="e.g. 2023-1-60-006" value="<?= h($old['student_number']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="department">Department</label>
                    <input type="text" id="department" name="department" placeholder="e.g. Computer Science and Engineering" value="<?= h($old['department']) ?>">
                </div>

                <div class="form-group">
                    <label for="room_id">Room (optional — can be assigned later)</label>
                    <select id="room_id" name="room_id">
                        <option value="">No room yet</option>
                        <?php foreach ($rooms as $r): ?>
                            <option value="<?= $r['RoomID'] ?>" <?= $old['room_id'] == $r['RoomID'] ? 'selected' : '' ?>>
                                <?= h($r['HostelName']) ?> - <?= h($r['RoomNumber']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <button type="submit" class="login-button">Create Account</button>
            </form>

            <div class="login-footer">
                Already have an account? <a href="login.php">Login here</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>
