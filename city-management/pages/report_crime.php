<?php
// pages/report_crime.php
session_start();

if (!isset($_SESSION['citizen_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../config/db.php';

$message = '';
$error = '';
$citizen_id = $_SESSION['citizen_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $crime_id = $_POST['crime_id'];
    $details = $_POST['details'];
    $area_code = $_POST['area_code'];
    $anonymous = isset($_POST['anonymous']) ? true : false;
    
    $reported_by = $anonymous ? NULL : $citizen_id;

    try {
        $stmt = $conn->prepare("INSERT INTO Crime_Report (crime_id, area_code, reported_by, date_of_report, details, status) VALUES (?, ?, ?, CURDATE(), ?, 'pending')");
        $stmt->bind_param("iiis", $crime_id, $area_code, $reported_by, $details);
        if ($stmt->execute()) {
            $message = "Crime reported successfully. The police will review it shortly.";
        } else {
            $error = "Failed to report crime.";
        }
    } catch (Exception $e) {
        $error = "Database Error: " . $e->getMessage();
    }
}

// Fetch crime types for dropdown
$crime_types = [];
try { $res = $conn->query("SELECT crime_id, type FROM Crime ORDER BY type ASC"); if($res) while($r=$res->fetch_assoc()) $crime_types[]=$r; } catch(Exception $e){}

// Fetch areas
$areas = [];
try { $res = $conn->query("SELECT area_code, area_name FROM Area ORDER BY area_name ASC"); if($res) while($r=$res->fetch_assoc()) $areas[]=$r; } catch(Exception $e){}

// Fetch user's previous reports
$my_reports = [];
try {
    $stmt = $conn->prepare("SELECT cr.*, c.type as crime_name, a.area_name 
                            FROM Crime_Report cr 
                            JOIN Crime c ON cr.crime_id = c.crime_id 
                            JOIN Area a ON cr.area_code = a.area_code 
                            WHERE cr.reported_by = ? 
                            ORDER BY cr.crime_report_id DESC");
    $stmt->bind_param("i", $citizen_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res) while($r = $res->fetch_assoc()) $my_reports[] = $r;
} catch (Exception $e) {}

include '../includes/header.php';
include '../includes/navbar.php';
?>

<main>
    <div class="dashboard-header">
        <h2>Report a Crime</h2>
        <p style="color: var(--text-muted); margin-top: 0.5rem;">Submit an official report to the city police department.</p>
    </div>

    <?php if ($message): ?><div style="padding:1rem;margin-bottom:1rem;background-color:#d1fae5;color:#065f46;border-radius:6px;"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div style="padding:1rem;margin-bottom:1rem;background-color:#fee2e2;color:#b91c1c;border-radius:6px;"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem;">
        
        <!-- Reporting Form -->
        <div class="card">
            <h3 style="color: var(--primary-color); margin-bottom: 1.5rem;">File New Report</h3>
            <form method="POST" action="">
                <div class="form-group" style="margin-bottom: 1rem;"><label>Crime Type</label>
                    <select name="crime_id" required style="width:100%; padding:0.85rem; border:1px solid var(--border-color); border-radius:6px;">
                        <option value="">-- Select Type --</option>
                        <?php foreach($crime_types as $c): ?>
                            <option value="<?= $c['crime_id'] ?>"><?= htmlspecialchars($c['type']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom: 1rem;"><label>Area Where it Occurred</label>
                    <select name="area_code" required style="width:100%; padding:0.85rem; border:1px solid var(--border-color); border-radius:6px;">
                        <option value="">-- Select Area --</option>
                        <?php foreach($areas as $a): ?>
                            <option value="<?= $a['area_code'] ?>"><?= htmlspecialchars($a['area_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 1rem;"><label>Details & Description</label>
                    <textarea name="details" required rows="5" placeholder="Provide detailed information about the incident..." style="width:100%; padding:0.85rem; border:1px solid var(--border-color); border-radius:6px; font-family:inherit;"></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <input type="checkbox" name="anonymous" id="anonymous" value="1">
                    <label for="anonymous" style="margin-bottom: 0; color: var(--secondary-color); cursor: pointer;">Submit anonymously (Hide my identity)</label>
                </div>

                <button type="submit" class="btn">Submit Official Report</button>
            </form>
        </div>

        <!-- History -->
        <div class="card">
            <h3 style="color: var(--primary-color); margin-bottom: 1.5rem;">My Reporting History</h3>
            
            <?php if(empty($my_reports)): ?>
                <p style="color: var(--text-muted); text-align: center; padding: 2rem 0;">You haven't filed any reports.</p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php foreach($my_reports as $r): ?>
                        <div style="border: 1px solid var(--border-color); padding: 1rem; border-radius: 6px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <strong style="font-size: 1.1rem; color: var(--primary-color);"><?= htmlspecialchars($r['crime_name']) ?></strong>
                                <?php 
                                    $color = '#eab308'; // yellow for pending
                                    if ($r['status'] === 'verified') $color = '#22c55e';
                                    if ($r['status'] === 'rejected') $color = '#ef4444';
                                ?>
                                <span style="background: <?= $color ?>22; color: <?= $color ?>; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase;">
                                    <?= htmlspecialchars($r['status']) ?>
                                </span>
                            </div>
                            <span style="font-size: 0.85rem; color: var(--text-muted); display: block; margin-bottom: 0.5rem;">
                                📍 <?= htmlspecialchars($r['area_name']) ?> | 📅 <?= date('M d, Y', strtotime($r['date_of_report'])) ?>
                            </span>
                            <p style="font-size: 0.95rem; color: var(--secondary-color);"><?= htmlspecialchars($r['details']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
