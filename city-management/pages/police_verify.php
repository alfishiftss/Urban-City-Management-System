<?php
// pages/police_verify.php
session_start();

if (!isset($_SESSION['citizen_id'])) {
    header("Location: ../login.php");
    exit();
}

$role = strtolower($_SESSION['role']);
if ($role !== 'police' && $role !== 'admin') {
    die("<div style='padding: 2rem; text-align: center;'><h2 style='color: #ef4444;'>Access Denied</h2><p>Only Police Officers and Admins can verify reports.</p></div>");
}

require_once '../config/db.php';

$message = '';
$error = '';
$officer_id = $_SESSION['citizen_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $report_id = $_POST['crime_report_id'];

    if ($action === 'verify') {
        try {
            $stmt = $conn->prepare("UPDATE Crime_Report SET status = 'verified', verified_by = ? WHERE crime_report_id = ?");
            $stmt->bind_param("ii", $officer_id, $report_id);
            if ($stmt->execute()) $message = "Report #$report_id marked as VERIFIED.";
            else $error = "Failed to verify report.";
        } catch (Exception $e) { $error = "Database Error."; }
    } elseif ($action === 'reject') {
        try {
            $stmt = $conn->prepare("UPDATE Crime_Report SET status = 'rejected', verified_by = ? WHERE crime_report_id = ?");
            $stmt->bind_param("ii", $officer_id, $report_id);
            if ($stmt->execute()) $message = "Report #$report_id marked as REJECTED.";
            else $error = "Failed to reject report.";
        } catch (Exception $e) { $error = "Database Error."; }
    }
}

// Fetch pending reports
$pending_reports = [];
try {
    $sql = "SELECT cr.*, c.type as crime_name, a.area_name, reporter.name as reporter_name 
            FROM Crime_Report cr 
            JOIN Crime c ON cr.crime_id = c.crime_id 
            JOIN Area a ON cr.area_code = a.area_code 
            LEFT JOIN Citizen reporter ON cr.reported_by = reporter.citizen_id 
            WHERE cr.status = 'pending' 
            ORDER BY cr.crime_report_id DESC";
    $res = $conn->query($sql);
    if($res) while($r = $res->fetch_assoc()) $pending_reports[] = $r;
} catch (Exception $e) {}

// Fetch verified reports
$history_reports = [];
try {
    $sql = "SELECT cr.*, c.type as crime_name, a.area_name, reporter.name as reporter_name 
            FROM Crime_Report cr 
            JOIN Crime c ON cr.crime_id = c.crime_id 
            JOIN Area a ON cr.area_code = a.area_code 
            LEFT JOIN Citizen reporter ON cr.reported_by = reporter.citizen_id 
            WHERE cr.status != 'pending' 
            ORDER BY cr.crime_report_id DESC";
    $res = $conn->query($sql);
    if($res) while($r = $res->fetch_assoc()) $history_reports[] = $r;
} catch (Exception $e) {}

include '../includes/header.php';
include '../includes/navbar.php';
?>

<main>
    <div class="dashboard-header">
        <h2>Police Verification Portal</h2>
        <p style="color: var(--text-muted); margin-top: 0.5rem;">Review, verify, or reject pending citizen crime reports.</p>
    </div>

    <?php if ($message): ?><div style="padding:1rem;margin-bottom:1rem;background-color:#d1fae5;color:#065f46;border-radius:6px;"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div style="padding:1rem;margin-bottom:1rem;background-color:#fee2e2;color:#b91c1c;border-radius:6px;"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <h3 style="color: var(--primary-color); margin-bottom: 1rem;">Action Queue (Pending)</h3>
    <div class="card" style="overflow-x: auto; margin-bottom: 3rem;">
        <table class="table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color); text-align: left;">
                    <th style="padding: 1rem 0.5rem;">ID</th>
                    <th style="padding: 1rem 0.5rem;">Type</th>
                    <th style="padding: 1rem 0.5rem;">Details</th>
                    <th style="padding: 1rem 0.5rem;">Location</th>
                    <th style="padding: 1rem 0.5rem;">Reporter</th>
                    <th style="padding: 1rem 0.5rem;">Date</th>
                    <th style="padding: 1rem 0.5rem;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pending_reports)): ?>
                    <tr><td colspan="7" style="text-align: center; padding: 2rem;">No pending reports requiring verification.</td></tr>
                <?php else: ?>
                    <?php foreach ($pending_reports as $r): ?>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 1rem 0.5rem;">#<?= htmlspecialchars($r['crime_report_id']) ?></td>
                            <td style="padding: 1rem 0.5rem; font-weight: 500;"><?= htmlspecialchars($r['crime_name']) ?></td>
                            <td style="padding: 1rem 0.5rem; max-width: 300px;"><?= htmlspecialchars($r['details']) ?></td>
                            <td style="padding: 1rem 0.5rem;"><?= htmlspecialchars($r['area_name']) ?></td>
                            <td style="padding: 1rem 0.5rem;"><?= $r['reporter_name'] ? htmlspecialchars($r['reporter_name']) : '<i>Anonymous</i>' ?></td>
                            <td style="padding: 1rem 0.5rem;"><?= date('M d, Y', strtotime($r['date_of_report'])) ?></td>
                            <td style="padding: 1rem 0.5rem; white-space: nowrap;">
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Verify this report?');">
                                    <input type="hidden" name="action" value="verify">
                                    <input type="hidden" name="crime_report_id" value="<?= $r['crime_report_id'] ?>">
                                    <button type="submit" class="btn" style="background: #22c55e; padding: 0.4rem 0.8rem; font-size: 0.85rem; margin-right: 0.5rem;">Verify</button>
                                </form>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Reject this report as false/invalid?');">
                                    <input type="hidden" name="action" value="reject">
                                    <input type="hidden" name="crime_report_id" value="<?= $r['crime_report_id'] ?>">
                                    <button type="submit" class="btn" style="background: #ef4444; padding: 0.4rem 0.8rem; font-size: 0.85rem;">Reject</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <h3 style="color: var(--primary-color); margin-bottom: 1rem;">Action History</h3>
    <div class="card" style="overflow-x: auto;">
        <table class="table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color); text-align: left; opacity: 0.8;">
                    <th style="padding: 1rem 0.5rem;">ID</th>
                    <th style="padding: 1rem 0.5rem;">Type</th>
                    <th style="padding: 1rem 0.5rem;">Location</th>
                    <th style="padding: 1rem 0.5rem;">Reporter</th>
                    <th style="padding: 1rem 0.5rem;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($history_reports)): ?>
                    <tr><td colspan="5" style="text-align: center; padding: 2rem;">No historical records found.</td></tr>
                <?php else: ?>
                    <?php foreach ($history_reports as $r): ?>
                        <tr style="border-bottom: 1px solid var(--border-color); opacity: 0.7;">
                            <td style="padding: 1rem 0.5rem;">#<?= htmlspecialchars($r['crime_report_id']) ?></td>
                            <td style="padding: 1rem 0.5rem;"><?= htmlspecialchars($r['crime_name']) ?></td>
                            <td style="padding: 1rem 0.5rem;"><?= htmlspecialchars($r['area_name']) ?></td>
                            <td style="padding: 1rem 0.5rem;"><?= $r['reporter_name'] ? htmlspecialchars($r['reporter_name']) : '<i>Anonymous</i>' ?></td>
                            <td style="padding: 1rem 0.5rem; text-transform: uppercase; font-weight: 600; color: <?= $r['status'] === 'verified' ? '#15803d' : '#b91c1c' ?>;">
                                <?= htmlspecialchars($r['status']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
