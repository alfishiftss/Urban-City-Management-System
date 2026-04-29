<?php
// register.php
session_start();
require_once 'config/db.php';

// Redirect if already logged in
if (isset($_SESSION['citizen_id'])) {
    header("Location: pages/dashboard.php");
    exit();
}

$error = '';
$success = '';

// Fetch Area Codes for Dropdown
$areas = [];
try {
    $res = $conn->query("SELECT area_code, area_name FROM Area ORDER BY area_name ASC");
    if($res) {
        while($r = $res->fetch_assoc()) $areas[] = $r;
    }
} catch(Exception $e) {}

// Fetch Building IDs for Dropdown
$buildings = [];
try {
    $res = $conn->query("SELECT building_id, type FROM Building");
    if($res) {
        while($r = $res->fetch_assoc()) $buildings[] = $r;
    }
} catch(Exception $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $nid = $_POST['nid'] ?? '';
    $occupation = $_POST['occupation'] ?? '';
    $role = $_POST['role'] ?? 'renter';
    $area_code = !empty($_POST['area_code']) ? $_POST['area_code'] : NULL;
    $building_id = !empty($_POST['building_id']) ? $_POST['building_id'] : NULL;
    
    // Prevent standard users from registering as Admin via frontend
    if ($role === 'admin') {
        $role = 'renter';
    }

    if (!empty($name) && !empty($phone) && !empty($nid)) {
        try {
            $stmt = $conn->prepare("INSERT INTO Citizen (name, phone, nid, occupation, role, area_code, building_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssii", $name, $phone, $nid, $occupation, $role, $area_code, $building_id);
            if ($stmt->execute()) {
                $success = "Account created successfully! You can now log in.";
            } else {
                $error = "Error creating account.";
            }
        } catch (Exception $e) {
            $error = "Database Error. Ensure NID is unique.";
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - City Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/city-management/assets/css/style.css">
    <style>
        .login-wrapper { display: flex; align-items: center; justify-content: center; min-height: 100vh; background-color: var(--background-color); padding: 2rem; }
        .login-container { width: 100%; max-width: 450px; padding: 2.5rem; background: var(--card-bg); border-radius: 10px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .login-container h2 { margin-bottom: 1.5rem; color: var(--primary-color); text-align: center; font-size: 1.8rem; }
        .form-group { margin-bottom: 1.2rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; color: var(--secondary-color); font-weight: 500; font-size: 0.95rem; }
        .form-group input, .form-group select { width: 100%; padding: 0.85rem; border: 1px solid var(--border-color); border-radius: 6px; font-family: inherit; font-size: 1rem; }
        .btn { width: 100%; padding: 0.85rem; background: var(--accent-color); color: white; border: none; border-radius: 6px; font-size: 1rem; font-weight: 600; cursor: pointer; margin-top: 0.5rem; }
        .error { color: #ef4444; background: #fee2e2; padding: 0.75rem; border-radius: 6px; margin-bottom: 1.5rem; text-align: center; border: 1px solid #f87171; font-size: 0.95rem; }
        .success { color: #15803d; background: #dcfce7; padding: 0.75rem; border-radius: 6px; margin-bottom: 1.5rem; text-align: center; border: 1px solid #22c55e; font-size: 0.95rem; }
        .link-text { text-align: center; margin-top: 1.5rem; font-size: 0.95rem; }
        .link-text a { color: var(--accent-color); text-decoration: none; font-weight: 500; }
    </style>
</head>
<body class="login-wrapper">
    <div class="login-container">
        <h2>Create Account</h2>
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php else: ?>
        <form method="POST" action="">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" required placeholder="John Doe">
            </div>
            <div class="form-group">
                <label>National ID (NID)</label>
                <input type="text" name="nid" required placeholder="Enter NID">
            </div>
            <div class="form-group">
                <label>Phone Number (Used for Login)</label>
                <input type="text" name="phone" required placeholder="017...">
            </div>
            <div class="form-group">
                <label>Occupation</label>
                <input type="text" name="occupation" placeholder="e.g. Engineer">
            </div>
            <div class="form-group">
                <label>Register As</label>
                <select name="role" required>
                    <option value="renter">Renter</option>
                    <option value="owner">Owner</option>
                    <option value="police">Police Officer</option>
                </select>
            </div>
            <div class="form-group">
                <label>Area</label>
                <select name="area_code">
                    <option value="">-- Select Area --</option>
                    <?php foreach($areas as $a): ?>
                        <option value="<?= $a['area_code'] ?>"><?= htmlspecialchars($a['area_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Building ID</label>
                <select name="building_id">
                    <option value="">-- Select Building --</option>
                    <?php foreach($buildings as $b): ?>
                        <option value="<?= $b['building_id'] ?>">Building #<?= $b['building_id'] ?> (<?= ucfirst($b['type']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn">Register</button>
        </form>
        <?php endif; ?>
        
        <div class="link-text">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
</body>
</html>
