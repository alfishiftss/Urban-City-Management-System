<?php
// index.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// Redirect to dashboard if logged in, otherwise to login page
if (isset($_SESSION['citizen_id'])) {
    header("Location: pages/dashboard.php");
    exit();
} else {
    header("Location: login.php");
    exit();
}
?>
