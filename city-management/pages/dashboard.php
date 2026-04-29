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
        <p style="color: var(--text-muted); margin-top: 0.5rem;">Welcome back, <?= htmlspecialchars($_SESSION['role']) ?>!</p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <div class="card" style="text-align: center;">
            <h3 style="color: var(--text-muted); font-size: 1rem; margin-bottom: 0.5rem;">Total Citizens</h3>
            <p style="font-size: 2.5rem; font-weight: 700; color: var(--primary-color);"><?= $total_citizens ?></p>
        </div>
        <div class="card" style="text-align: center;">
            <h3 style="color: var(--text-muted); font-size: 1rem; margin-bottom: 0.5rem;">City Areas</h3>
            <p style="font-size: 2.5rem; font-weight: 700; color: var(--primary-color);"><?= $total_areas ?></p>
        </div>
        <div class="card" style="text-align: center;">
            <h3 style="color: var(--text-muted); font-size: 1rem; margin-bottom: 0.5rem;">Buildings</h3>
            <p style="font-size: 2.5rem; font-weight: 700; color: var(--primary-color);"><?= $total_buildings ?></p>
        </div>
        <div class="card" style="text-align: center;">
            <h3 style="color: var(--text-muted); font-size: 1rem; margin-bottom: 0.5rem;">Crime Reports</h3>
            <p style="font-size: 2.5rem; font-weight: 700; color: var(--primary-color);"><?= $total_crimes ?></p>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
