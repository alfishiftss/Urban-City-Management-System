<?php
// includes/navbar.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user_role = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : '';
?>
<header class="main-header">
    <div class="logo">
        <h1>City Management</h1>
    </div>
    <nav>
        <ul>
            <li><a href="/city-management/pages/dashboard.php">Dashboard</a></li>
            <?php if ($user_role === 'admin'): ?>
            <li><a href="/city-management/pages/manage_citizens.php">Citizens</a></li>
            <li><a href="/city-management/pages/manage_areas.php">Areas</a></li>
            <?php endif; ?>
            <?php if ($user_role === 'admin' || $user_role === 'police'): ?>
            <li><a href="/city-management/pages/police_verify.php">Police Verify</a></li>
            <li><a href="/city-management/pages/criminal_records.php">Criminal Records</a></li>
            <?php endif; ?>
            <li><a href="/city-management/pages/report_crime.php">Report Crime</a></li>
            <li><a href="/city-management/pages/area_analysis.php">Analytics</a></li>
            <li><a href="/city-management/pages/announcements.php">Announcements</a></li>
            <li><a href="/city-management/logout.php" style="color: #f87171;">Logout</a></li>
        </ul>
    </nav>
</header>
