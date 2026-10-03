<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

$role     = $_POST['role'] ?? '';
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// 1. Find a user with this email + role.
$stmt = $pdo->prepare("SELECT * FROM Users WHERE Email = ? AND Role = ? AND Status = 'Active'");
$stmt->execute([$email, $role]);
$user = $stmt->fetch();

// NOTE: the sample data stores plain-text passwords (e.g. 'Student@123'),
// so we compare directly here. In a real app you would store a hash with
// password_hash() when the account is created and check it with
// password_verify($password, $user['Password']).
if (!$user || $password !== $user['Password']) {
    $_SESSION['login_error'] = "Invalid email, password, or role.";
    header("Location: login.php");
    exit;
}

// 2. Save the logged-in user's info in the session.
$_SESSION['user_id']   = $user['UserID'];
$_SESSION['full_name'] = $user['FullName'];
$_SESSION['email']     = $user['Email'];
$_SESSION['role']      = $user['Role'];

// 3. If this user is a Student or MaintenanceStaff, also remember their
//    StudentID / StaffID, since most queries key off those, not UserID.
if ($role === 'Student') {
    $s = $pdo->prepare("SELECT StudentID, RoomID FROM Student WHERE UserID = ?");
    $s->execute([$user['UserID']]);
    $row = $s->fetch();
    $_SESSION['student_id'] = $row['StudentID'] ?? null;
    $_SESSION['room_id']    = $row['RoomID'] ?? null;
}

if ($role === 'MaintenanceStaff') {
    $s = $pdo->prepare("SELECT StaffID FROM MaintenanceStaff WHERE UserID = ?");
    $s->execute([$user['UserID']]);
    $row = $s->fetch();
    $_SESSION['staff_id'] = $row['StaffID'] ?? null;
}

// 4. Redirect to the correct dashboard.
$map = [
    'Student'          => 'student-dashboard.php',
    'Admin'            => 'admin-dashboard.php',
    'MaintenanceStaff' => 'maintenance-dashboard.php',
];
header("Location: " . $map[$role]);
exit;
