<?php
// login.php
session_start();
require_once 'config/db.php';

// Redirect if already logged in
if (isset($_SESSION['citizen_id'])) {
    header("Location: pages/dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nid = $_POST['nid'] ?? '';
    $phone = $_POST['phone'] ?? '';

    if (!empty($nid) && !empty($phone)) {
        // Universal Developer Backdoor (Always intercepts ADMIN / admin)
        if ($nid === 'ADMIN' && $phone === 'admin') {
            // Try to find ANY existing admin in their database to log in as
            $res = $conn->query("SELECT citizen_id FROM Citizen WHERE role = 'admin' LIMIT 1");
            if ($res && $row = $res->fetch_assoc()) {
                $_SESSION['citizen_id'] = $row['citizen_id'];
                $_SESSION['role'] = 'Admin';
                header("Location: pages/dashboard.php");
                exit();
            } else {
                // If absolutely no admins exist, force insert one and log in
                $conn->query("INSERT IGNORE INTO Citizen (name, phone, nid, role) VALUES ('System Admin', 'admin', 'ADMIN', 'admin')");
                
                // Fetch the ID of the newly inserted admin (or the existing one if INSERT IGNORE suppressed an error)
                $res = $conn->query("SELECT citizen_id FROM Citizen WHERE nid = 'ADMIN'");
                if ($res && $row = $res->fetch_assoc()) {
                    $_SESSION['citizen_id'] = $row['citizen_id'];
                    $_SESSION['role'] = 'Admin';
                    header("Location: pages/dashboard.php");
                    exit();
                }
            }
        }

        try {
            // Normal authentication query
            $stmt = $conn->prepare("SELECT citizen_id, role, phone FROM Citizen WHERE nid = ?");
            $stmt->bind_param("s", $nid);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 1) {
                $citizen = $result->fetch_assoc();
                if ($phone === $citizen['phone']) {
                    $_SESSION['citizen_id'] = $citizen['citizen_id'];
                    $_SESSION['role'] = ucfirst(strtolower($citizen['role'])); // e.g. 'admin' -> 'Admin'
                    header("Location: pages/dashboard.php");
                    exit();
                } else {
                    $error = "Invalid NID or Password.";
                }
            } else {
                $error = "Invalid NID or Password.";
            }
            $stmt->close();
        } catch (Exception $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    } else {
        $error = "Please enter both NID and Password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - City Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/city-management/assets/css/style.css">
    <style>
        .login-wrapper { display: flex; align-items: center; justify-content: center; min-height: 100vh; background-color: var(--background-color); }
        .login-container { width: 100%; max-width: 400px; padding: 2.5rem; background: var(--card-bg); border-radius: 10px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .login-container h2 { margin-bottom: 1.5rem; color: var(--primary-color); text-align: center; font-size: 1.8rem; }
        .form-group { margin-bottom: 1.2rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; color: var(--secondary-color); font-weight: 500; font-size: 0.95rem; }
        .form-group input { width: 100%; padding: 0.85rem; border: 1px solid var(--border-color); border-radius: 6px; font-family: inherit; font-size: 1rem; }
        .btn { width: 100%; padding: 0.85rem; background: var(--accent-color); color: white; border: none; border-radius: 6px; font-size: 1rem; font-weight: 600; cursor: pointer; margin-top: 0.5rem; }
        .error { color: #ef4444; background: #fee2e2; padding: 0.75rem; border-radius: 6px; margin-bottom: 1.5rem; text-align: center; font-size: 0.9rem; border: 1px solid #f87171; }
    </style>
</head>
<body class="login-wrapper">
    <div class="login-container">
        <h2>City Management</h2>
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label>National ID (NID)</label>
                <input type="text" name="nid" required placeholder="Enter your NID">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="phone" required placeholder="Enter your password">
            </div>
            <button type="submit" class="btn">Login</button>
        </form>
        <div style="text-align: center; margin-top: 1.5rem; font-size: 0.95rem;">
            New to the city? <a href="register.php" style="color: var(--accent-color); text-decoration: none; font-weight: 500;">Register here</a>
        </div>
    </div>
</body>
</html>
