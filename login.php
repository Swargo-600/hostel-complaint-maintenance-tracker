<?php
require_once __DIR__ . '/includes/auth.php'; // starts the session + gives us h()
// If already logged in, skip straight to the right dashboard.
if (isset($_SESSION['user_id'])) {
    $map = [
        'Student' => 'student-dashboard.php',
        'Admin' => 'admin-dashboard.php',
        'MaintenanceStaff' => 'maintenance-dashboard.php',
    ];
    header("Location: " . $map[$_SESSION['role']]);
    exit;
}
$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HostelCare - Login</title>
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
            <h1>Hostel Complaint<br>Maintenance Tracker</h1>
            <p>Report hostel problems, track maintenance requests
               and communicate with maintenance staff from one
               convenient portal.</p>
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
            <h2>Welcome Back</h2>
            <p class="login-subtitle">Select your role and login to your account</p>

            <?php if ($error): ?>
                <div class="alert alert-info" style="margin-bottom: 15px;">
                    <?= h($error) ?>
                </div>
            <?php endif; ?>

            <form action="login_process.php" method="post">

                <div class="role-title">Login As</div>

                <div class="role-selection">
                    <label class="role-card">
                        <input type="radio" name="role" value="Student" checked>
                        <div class="role-content">
                            <div class="role-icon">👨‍🎓</div>
                            <strong>Student</strong>
                            <span>Student Portal</span>
                        </div>
                    </label>

                    <label class="role-card">
                        <input type="radio" name="role" value="Admin">
                        <div class="role-content">
                            <div class="role-icon">👨‍💼</div>
                            <strong>Admin</strong>
                            <span>Admin Portal</span>
                        </div>
                    </label>

                    <label class="role-card">
                        <input type="radio" name="role" value="MaintenanceStaff">
                        <div class="role-content">
                            <div class="role-icon">🔧</div>
                            <strong>Maintenance Staff</strong>
                            <span>Staff Portal</span>
                        </div>
                    </label>
                </div>

                <div class="login-form">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    </div>

                    <button type="submit" class="login-button">Login</button>
                </div>
            </form>

            <div class="login-footer">
                HostelCare © 2026 | Hostel Complaint Maintenance Tracker
                <br>
                New student? <a href="register.php">Create an account</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>
