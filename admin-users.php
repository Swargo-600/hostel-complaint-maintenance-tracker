<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_role('Admin');

$active = 'admin-users.php';
$pageTitle = 'User Management';
$pageSubtitle = 'Manage students, staff and admin accounts';

$errors = [];

// ----- Add new user -----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? '';

    if (!$fullName || !$email || !$password || !$role) {
        $errors[] = "Please fill in all required fields.";
    }

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("
                INSERT INTO Users (FullName, Email, Phone, Password, Role, Status)
                VALUES (?, ?, ?, ?, ?, 'Active')
            ");
            $stmt->execute([$fullName, $email, $phone, $password, $role]);
            $userId = $pdo->lastInsertId();

            if ($role === 'Student') {
                $pdo->prepare("
                    INSERT INTO Student (UserID, StudentNumber, Department, RoomID)
                    VALUES (?, ?, ?, ?)
                ")->execute([
                    $userId,
                    $_POST['student_number'] ?: null,
                    $_POST['department'] ?: null,
                    $_POST['room_id'] ?: null,
                ]);
            } elseif ($role === 'MaintenanceStaff') {
                $pdo->prepare("
                    INSERT INTO MaintenanceStaff (UserID, Department, Skill)
                    VALUES (?, ?, ?)
                ")->execute([$userId, $_POST['staff_department'] ?: null, $_POST['skill'] ?: null]);
            }

            $pdo->commit();
            $_SESSION['flash'] = "User '$fullName' added successfully.";
            header("Location: admin-users.php");
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Could not add user (is the email already used?): " . $e->getMessage();
        }
    }
}

// ----- Toggle active/inactive -----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    $stmt = $pdo->prepare("UPDATE Users SET Status = ? WHERE UserID = ?");
    $stmt->execute([$_POST['new_status'], $_POST['user_id']]);
    header("Location: admin-users.php");
    exit;
}

$users = $pdo->query("SELECT * FROM Users ORDER BY Role, FullName")->fetchAll();
$rooms = $pdo->query("SELECT RoomID, RoomNumber FROM Room ORDER BY RoomNumber")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | HostelCare</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main">
        <?php include __DIR__ . '/includes/topbar.php'; ?>

        <section class="content">
            <div class="page-header">
                <h2>Users</h2>
                <p>Add students, staff and admins, and manage their status.</p>
            </div>

            <div class="grid-2">
                <div class="panel">
                    <h3>Add New User</h3>

                    <?php foreach ($errors as $e): ?>
                        <div class="alert alert-info" style="background:#fee2e2;color:#991b1b;"><?= h($e) ?></div>
                    <?php endforeach; ?>

                    <form method="post" style="margin-top:15px;">
                        <input type="hidden" name="action" value="add_user">

                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="full_name" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone">
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="text" name="password" required>
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            <select name="role" id="role" required onchange="
                                document.getElementById('student-fields').style.display = this.value === 'Student' ? 'block' : 'none';
                                document.getElementById('staff-fields').style.display = this.value === 'MaintenanceStaff' ? 'block' : 'none';
                            ">
                                <option value="">Select Role</option>
                                <option value="Student">Student</option>
                                <option value="MaintenanceStaff">Maintenance Staff</option>
                                <option value="Admin">Admin</option>
                            </select>
                        </div>

                        <div id="student-fields" style="display:none;">
                            <div class="form-group">
                                <label>Student Number</label>
                                <input type="text" name="student_number">
                            </div>
                            <div class="form-group">
                                <label>Department</label>
                                <input type="text" name="department">
                            </div>
                            <div class="form-group">
                                <label>Room</label>
                                <select name="room_id">
                                    <option value="">No Room</option>
                                    <?php foreach ($rooms as $r): ?>
                                        <option value="<?= $r['RoomID'] ?>"><?= h($r['RoomNumber']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div id="staff-fields" style="display:none;">
                            <div class="form-group">
                                <label>Staff Department</label>
                                <input type="text" name="staff_department">
                            </div>
                            <div class="form-group">
                                <label>Skill</label>
                                <input type="text" name="skill">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Add User</button>
                    </form>
                </div>

                <div class="panel">
                    <h3>All Users</h3>
                    <div class="table-wrapper">
                        <table class="table">
                            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Action</th></tr></thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td><?= h($u['FullName']) ?></td>
                                        <td><?= h($u['Email']) ?></td>
                                        <td><?= h($u['Role']) ?></td>
                                        <td><span class="badge <?= $u['Status'] === 'Active' ? 'resolved' : 'badge-gray' ?>"><?= h($u['Status']) ?></span></td>
                                        <td>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="user_id" value="<?= $u['UserID'] ?>">
                                                <input type="hidden" name="new_status" value="<?= $u['Status'] === 'Active' ? 'Inactive' : 'Active' ?>">
                                                <button type="submit" class="action-btn">
                                                    <?= $u['Status'] === 'Active' ? 'Deactivate' : 'Activate' ?>
                                                </button>
                                            </form>
                                        </td>
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
