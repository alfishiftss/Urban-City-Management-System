<?php
// pages/dashboard.php
session_start();

if (!isset($_SESSION['citizen_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../config/db.php';

$total_citizens = 0;
$total_areas = 0;
$total_crimes = 0;
$total_buildings = 0;

if (isset($conn) && !$conn->connect_error) {
    try {
        $res = $conn->query("SELECT COUNT(*) as count FROM Citizen");
        if ($res) { $row = $res->fetch_assoc(); $total_citizens = $row['count']; }
    } catch (Exception $e) {}

    try {
        $res = $conn->query("SELECT COUNT(*) as count FROM Area");
        if ($res) { $row = $res->fetch_assoc(); $total_areas = $row['count']; }
    } catch (Exception $e) {}

    try {
        $res = $conn->query("SELECT COUNT(*) as count FROM Crime_Report");
        if ($res) { $row = $res->fetch_assoc(); $total_crimes = $row['count']; }
    } catch (Exception $e) {}

    try {
        $res = $conn->query("SELECT COUNT(*) as count FROM Building");
        if ($res) { $row = $res->fetch_assoc(); $total_buildings = $row['count']; }
    } catch (Exception $e) {}
}

include '../includes/header.php';
include '../includes/navbar.php';
?>

<main>
    <div class="dashboard-header">
        <h2>Dashboard</h2>
        <p style="color: var(--text-muted); margin-top: 0.5rem;">Welcome back, <?= htmlspecialchars(ucfirst($_SESSION['role'])) ?>!</p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <div class="card stat-card">
            <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
            <div class="stat-info">
                <h3>Total Citizens</h3>
                <p><?= $total_citizens ?></p>
            </div>
        </div>
        <div class="card stat-card">
            <div class="stat-icon"><i class="fa-solid fa-map-location-dot"></i></div>
            <div class="stat-info">
                <h3>City Areas</h3>
                <p><?= $total_areas ?></p>
            </div>
        </div>
        <div class="card stat-card">
            <div class="stat-icon"><i class="fa-solid fa-building"></i></div>
            <div class="stat-info">
                <h3>Buildings</h3>
                <p><?= $total_buildings ?></p>
            </div>
        </div>
        <div class="card stat-card">
            <div class="stat-icon"><i class="fa-solid fa-shield-halved"></i></div>
            <div class="stat-info">
                <h3>Crime Reports</h3>
                <p><?= $total_crimes ?></p>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
