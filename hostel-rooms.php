<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_role('Admin');

$active = 'hostel-rooms.php';
$pageTitle = 'Hostel & Rooms';
$pageSubtitle = 'Manage hostels, rooms and room availability';

// ----- Add Hostel -----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_hostel') {
    $stmt = $pdo->prepare("INSERT INTO Hostel (HostelName, Address, TotalRooms) VALUES (?, ?, ?)");
    $stmt->execute([
        trim($_POST['hostel_name']),
        trim($_POST['address']),
        $_POST['total_rooms'] ?: 0,
    ]);
    $_SESSION['flash'] = "Hostel added successfully.";
    header("Location: hostel-rooms.php");
    exit;
}

// ----- Add Room -----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_room') {
    $stmt = $pdo->prepare("
        INSERT INTO Room (HostelID, RoomNumber, FloorNo, Capacity, Status)
        VALUES (?, ?, ?, ?, 'Available')
    ");
    $stmt->execute([
        $_POST['hostel_id'],
        trim($_POST['room_number']),
        $_POST['floor_no'],
        $_POST['capacity'],
    ]);
    $_SESSION['flash'] = "Room added successfully.";
    header("Location: hostel-rooms.php");
    exit;
}

// ----- Overall stats (computed live from real Room rows, not the static Hostel.TotalRooms field) -----
$hostelCount     = $pdo->query("SELECT COUNT(*) FROM Hostel")->fetchColumn();
$roomCount       = $pdo->query("SELECT COUNT(*) FROM Room")->fetchColumn();
$availableCount  = $pdo->query("SELECT COUNT(*) FROM Room WHERE Status = 'Available'")->fetchColumn();
$maintenanceCount= $pdo->query("SELECT COUNT(*) FROM Room WHERE Status = 'Maintenance'")->fetchColumn();

// ----- Per-hostel breakdown -----
$hostels = $pdo->query("
    SELECT h.*,
        (SELECT COUNT(*) FROM Room WHERE HostelID = h.HostelID) AS RoomsAdded,
        (SELECT COUNT(*) FROM Room WHERE HostelID = h.HostelID AND Status = 'Available') AS AvailableRooms,
        (SELECT COUNT(*) FROM Room WHERE HostelID = h.HostelID AND Status = 'Occupied') AS OccupiedRooms,
        (SELECT COUNT(*) FROM Room WHERE HostelID = h.HostelID AND Status = 'Maintenance') AS MaintenanceRooms
    FROM Hostel h
    ORDER BY h.HostelName
")->fetchAll();

// ----- Room list with filters -----
$search      = trim($_GET['search'] ?? '');
$hostelFilter= $_GET['hostel_id'] ?? '';
$floorFilter = $_GET['floor'] ?? '';
$statusFilter= $_GET['status'] ?? '';

$sql = "
    SELECT r.*, h.HostelName
    FROM Room r JOIN Hostel h ON r.HostelID = h.HostelID
    WHERE 1=1
";
$params = [];
if ($search !== '') {
    $sql .= " AND r.RoomNumber LIKE ?";
    $params[] = "%$search%";
}
if ($hostelFilter !== '') {
    $sql .= " AND r.HostelID = ?";
    $params[] = $hostelFilter;
}
if ($floorFilter !== '') {
    $sql .= " AND r.FloorNo = ?";
    $params[] = $floorFilter;
}
if ($statusFilter !== '') {
    $sql .= " AND r.Status = ?";
    $params[] = $statusFilter;
}
$sql .= " ORDER BY h.HostelName, r.RoomNumber";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rooms = $stmt->fetchAll();

$floors = $pdo->query("SELECT DISTINCT FloorNo FROM Room ORDER BY FloorNo")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hostel & Rooms | HostelCare</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main">
        <?php include __DIR__ . '/includes/topbar.php'; ?>

        <section class="content">
            <div class="page-header">
                <h2>Hostel & Room Management</h2>
                <p>Manage hostel buildings, rooms, capacity and occupancy.</p>
            </div>

            <div class="stats">
                <div class="stat-card">
                    <div class="stat-label">Total Hostels</div>
                    <div class="stat-number blue"><?= (int)$hostelCount ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Rooms</div>
                    <div class="stat-number blue"><?= (int)$roomCount ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Available Rooms</div>
                    <div class="stat-number green"><?= (int)$availableCount ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Maintenance Rooms</div>
                    <div class="stat-number red"><?= (int)$maintenanceCount ?></div>
                </div>
            </div>

            <!-- ADD HOSTEL -->
            <div class="panel">
                <div class="panel-header"><h3>Add New Hostel</h3></div>
                <form method="post" class="form-grid">
                    <input type="hidden" name="action" value="add_hostel">
                    <div class="form-group">
                        <label for="hostel-name">Hostel Name</label>
                        <input type="text" id="hostel-name" name="hostel_name" placeholder="Enter hostel name" required>
                    </div>
                    <div class="form-group">
                        <label for="address">Address</label>
                        <input type="text" id="address" name="address" placeholder="Enter hostel address">
                    </div>
                    <div class="form-group">
                        <label for="total-rooms">Total Rooms (planned capacity)</label>
                        <input type="number" id="total-rooms" name="total_rooms" placeholder="Rooms" min="1">
                    </div>
                    <div class="form-group full">
                        <button type="submit" class="btn btn-primary">+ Add Hostel</button>
                    </div>
                </form>
            </div>

            <!-- HOSTEL OVERVIEW -->
            <div class="panel">
                <div class="panel-header"><h3>Registered Hostels</h3></div>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Hostel</th><th>Address</th><th>Planned Rooms</th>
                                <th>Rooms Added</th><th>Available</th><th>Occupied</th><th>Maintenance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($hostels as $h): ?>
                                <tr>
                                    <td><strong><?= h($h['HostelName']) ?></strong></td>
                                    <td><?= h($h['Address']) ?></td>
                                    <td><?= (int)$h['TotalRooms'] ?></td>
                                    <td><?= (int)$h['RoomsAdded'] ?></td>
                                    <td><span class="badge available"><?= (int)$h['AvailableRooms'] ?></span></td>
                                    <td><span class="badge occupied"><?= (int)$h['OccupiedRooms'] ?></span></td>
                                    <td><span class="badge maintenance"><?= (int)$h['MaintenanceRooms'] ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ROOM MANAGEMENT -->
            <div class="panel" id="rooms">
                <div class="panel-header">
                    <h3>Room Management</h3>
                    <a href="#add-room" class="btn btn-primary">+ Add Room</a>
                </div>

                <form class="filters" method="get" action="hostel-rooms.php#rooms">
                    <input type="text" name="search" class="filter-input" placeholder="Search room number..." value="<?= h($search) ?>">

                    <select name="hostel_id" class="filter-select" onchange="this.form.submit()">
                        <option value="">All Hostels</option>
                        <?php foreach ($hostels as $h): ?>
                            <option value="<?= $h['HostelID'] ?>" <?= (string)$hostelFilter === (string)$h['HostelID'] ? 'selected' : '' ?>>
                                <?= h($h['HostelName']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="floor" class="filter-select" onchange="this.form.submit()">
                        <option value="">All Floors</option>
                        <?php foreach ($floors as $f): ?>
                            <option value="<?= $f['FloorNo'] ?>" <?= (string)$floorFilter === (string)$f['FloorNo'] ? 'selected' : '' ?>>
                                Floor <?= (int)$f['FloorNo'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="status" class="filter-select" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="Available" <?= $statusFilter === 'Available' ? 'selected' : '' ?>>Available</option>
                        <option value="Occupied" <?= $statusFilter === 'Occupied' ? 'selected' : '' ?>>Occupied</option>
                        <option value="Maintenance" <?= $statusFilter === 'Maintenance' ? 'selected' : '' ?>>Maintenance</option>
                    </select>

                    <button type="submit" class="btn btn-primary">Filter</button>
                </form>

                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Room ID</th><th>Hostel</th><th>Room Number</th>
                                <th>Floor</th><th>Capacity</th><th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$rooms): ?>
                                <tr><td colspan="6">No rooms match this filter.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($rooms as $r): ?>
                                <tr>
                                    <td>#<?= str_pad($r['RoomID'], 3, '0', STR_PAD_LEFT) ?></td>
                                    <td><?= h($r['HostelName']) ?></td>
                                    <td><strong><?= h($r['RoomNumber']) ?></strong></td>
                                    <td><?= (int)$r['FloorNo'] ?></td>
                                    <td><?= (int)$r['Capacity'] ?> Students</td>
                                    <td><span class="badge <?= badge_class($r['Status']) ?>"><?= h($r['Status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <p style="margin-top:10px; color:#6b7280; font-size:14px;">
                    Showing <?= count($rooms) ?> of <?= (int)$roomCount ?> total rooms.
                </p>
            </div>

            <!-- ADD ROOM -->
            <div class="panel" id="add-room">
                <div class="panel-header"><h3>Add New Room</h3></div>
                <form method="post" class="form-grid">
                    <input type="hidden" name="action" value="add_room">
                    <div class="form-group">
                        <label for="room-hostel">Hostel</label>
                        <select id="room-hostel" name="hostel_id" required>
                            <option value="">Select Hostel</option>
                            <?php foreach ($hostels as $h): ?>
                                <option value="<?= $h['HostelID'] ?>"><?= h($h['HostelName']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="room-number">Room Number</label>
                        <input type="text" id="room-number" name="room_number" placeholder="Example: A103" required>
                    </div>
                    <div class="form-group">
                        <label for="floor">Floor Number</label>
                        <input type="number" id="floor" name="floor_no" placeholder="Example: 1" min="1" required>
                    </div>
                    <div class="form-group">
                        <label for="capacity">Capacity</label>
                        <input type="number" id="capacity" name="capacity" placeholder="Students" min="1" required>
                    </div>
                    <div class="form-group full">
                        <button type="submit" class="btn btn-primary">+ Add Room</button>
                    </div>
                </form>
            </div>
        </section>
    </main>
</div>
</body>
</html>
