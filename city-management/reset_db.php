<?php
require_once 'config/db.php';

// Disable foreign key checks to allow dropping tables with relationships
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

// Drop all tables
$tables = [
    'announcements',
    'criminal_records',
    'crimes',
    'buildings',
    'areas',
    'citizens'
];

foreach ($tables as $table) {
    if ($conn->query("DROP TABLE IF EXISTS $table")) {
        echo "<p style='color: green;'>Dropped table: $table</p>";
    } else {
        echo "<p style='color: red;'>Error dropping $table: " . $conn->error . "</p>";
    }
}

// Re-enable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

echo "<h2>Reset Complete!</h2>";
echo "<p>All manual tables have been successfully cleared.</p>";
echo "<p><b>Next Step:</b> <a href='pages/dashboard.php'>Click here to go back to your Dashboard</a> and click through the navigation links (Citizens, Areas, etc.) to automatically generate the perfect tables!</p>";
?>
