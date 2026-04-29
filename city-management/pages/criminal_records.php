<?php
// pages/criminal_records.php
session_start();

if (!isset($_SESSION['citizen_id'])) {
    header("Location: ../login.php");
    exit();
}

$role = strtolower($_SESSION['role']);
if ($role !== 'police' && $role !== 'admin') {
    die("<div style='padding: 2rem; text-align: center;'><h2 style='color: #ef4444;'>Access Denied</h2><p>Only Police Officers and Admins can access records.</p></div>");
}

require_once '../config/db.php';

$message = '';
$error = '';
$search_result = null;
$records = [];
$officer_id = $_SESSION['citizen_id'];

// Add New Record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_record') {
    $target_citizen_id = $_POST['citizen_id'];
    $crime_id = $_POST['crime_id'];
    $punishment = $_POST['punishment'];
    $penalty = !empty($_POST['penalty']) ? floatval($_POST['penalty']) : 0.00;
    $timeline = $_POST['timeline'];

    try {
        $stmt = $conn->prepare("INSERT INTO Criminal_Record (citizen_id, crime_id, punishment, penalty, timeline) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisds", $target_citizen_id, $crime_id, $punishment, $penalty, $timeline);
        if ($stmt->execute()) {
            $message = "Criminal record added successfully.";
        } else {
            $error = "Failed to add record.";
        }
    } catch (Exception $e) {
        $error = "Database Error: " . $e->getMessage();
    }
}

// Search Logic
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET['search'])) {
    $search = trim($_GET['search']);
    try {
        // Search by NID or Phone
        $stmt = $conn->prepare("SELECT * FROM Citizen WHERE nid = ? OR phone = ? LIMIT 1");
        $stmt->bind_param("ss", $search, $search);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($res->num_rows === 1) {
            $search_result = $res->fetch_assoc();
            
            // Fetch their records
            $stmt2 = $conn->prepare("SELECT cr.*, c.type as crime_name 
                                     FROM Criminal_Record cr 
                                     JOIN Crime c ON cr.crime_id = c.crime_id 
                                     WHERE cr.citizen_id = ? 
                                     ORDER BY cr.record_id DESC");
            $stmt2->bind_param("i", $search_result['citizen_id']);
            $stmt2->execute();
            $res2 = $stmt2->get_result();
            while($r = $res2->fetch_assoc()) $records[] = $r;
        } else {
            $error = "No citizen found with that NID or Phone Number.";
        }
    } catch (Exception $e) {
        $error = "Search failed.";
    }
}

// Fetch crime types for dropdown
$crime_types = [];
try { $res = $conn->query("SELECT crime_id, type FROM Crime ORDER BY type ASC"); if($res) while($r=$res->fetch_assoc()) $crime_types[]=$r; } catch(Exception $e){}

include '../includes/header.php';
include '../includes/navbar.php';
?>

<main>
    <div class="dashboard-header">
        <h2>Criminal Records Database</h2>
        <p style="color: var(--text-muted); margin-top: 0.5rem;">Secure search and tracking of citizen criminal history.</p>
    </div>

    <?php if ($message): ?><div style="padding:1rem;margin-bottom:1rem;background-color:#d1fae5;color:#065f46;border-radius:6px;"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div style="padding:1rem;margin-bottom:1rem;background-color:#fee2e2;color:#b91c1c;border-radius:6px;"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <!-- Search Section -->
    <div class="card" style="margin-bottom: 2rem;">
        <form method="GET" action="" style="display: flex; gap: 1rem;">
            <input type="text" name="search" placeholder="Search by NID or Phone Number..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" required style="flex: 1; padding: 0.85rem; border: 1px solid var(--border-color); border-radius: 6px; font-size: 1rem;">
            <button type="submit" class="btn" style="width: auto; padding: 0 2rem;">Search</button>
        </form>
    </div>

    <?php if ($search_result): ?>
        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem; margin-bottom: 2rem;">
            
            <!-- Citizen Info -->
            <div class="card">
                <h3 style="color: var(--primary-color); margin-bottom: 1.5rem; border-bottom: 2px solid var(--background-color); padding-bottom: 0.5rem;">Citizen Dossier</h3>
                <div style="display: flex; flex-direction: column; gap: 0.8rem;">
                    <div><span style="color: var(--text-muted); font-size: 0.9rem;">Full Name:</span><br><strong style="font-size: 1.1rem;"><?= htmlspecialchars($search_result['name']) ?></strong></div>
                    <div><span style="color: var(--text-muted); font-size: 0.9rem;">National ID (NID):</span><br><strong><?= htmlspecialchars($search_result['nid']) ?></strong></div>
                    <div><span style="color: var(--text-muted); font-size: 0.9rem;">Phone:</span><br><strong><?= htmlspecialchars($search_result['phone']) ?></strong></div>
                    <div><span style="color: var(--text-muted); font-size: 0.9rem;">Role:</span><br><strong style="text-transform: uppercase;"><?= htmlspecialchars($search_result['role']) ?></strong></div>
                </div>
                
                <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 1.5rem 0;">
                
                <h4 style="color: #ef4444; margin-bottom: 1rem;">+ File New Criminal Record</h4>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add_record">
                    <input type="hidden" name="citizen_id" value="<?= $search_result['citizen_id'] ?>">
                    
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label style="display:block; margin-bottom:0.3rem;">Crime Type</label>
                        <select name="crime_id" required style="width:100%; padding:0.6rem; border:1px solid var(--border-color); border-radius:4px;">
                            <option value="">-- Select Crime --</option>
                            <?php foreach($crime_types as $c): ?>
                                <option value="<?= $c['crime_id'] ?>"><?= htmlspecialchars($c['type']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label style="display:block; margin-bottom:0.3rem;">Punishment</label>
                        <input type="text" name="punishment" required placeholder="e.g. 6 Months Prison" style="width:100%; padding:0.6rem; border:1px solid var(--border-color); border-radius:4px;">
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label style="display:block; margin-bottom:0.3rem;">Financial Penalty ($)</label>
                        <input type="number" step="0.01" name="penalty" placeholder="e.g. 500.00" style="width:100%; padding:0.6rem; border:1px solid var(--border-color); border-radius:4px;">
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label style="display:block; margin-bottom:0.3rem;">Timeline</label>
                        <input type="text" name="timeline" required placeholder="e.g. Jan 2023 - Jun 2023" style="width:100%; padding:0.6rem; border:1px solid var(--border-color); border-radius:4px;">
                    </div>
                    
                    <button type="submit" class="btn" style="background: #ef4444;">Add Official Record</button>
                </form>
            </div>
            
            <!-- Criminal History -->
            <div class="card" style="background: #fef2f2; border: 1px solid #fecaca;">
                <h3 style="color: #b91c1c; margin-bottom: 1.5rem;">Historical Offenses</h3>
                <?php if (empty($records)): ?>
                    <div style="text-align: center; padding: 3rem 0;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">✅</div>
                        <p style="color: #059669; font-weight: 600; font-size: 1.1rem;">CLEAN RECORD</p>
                        <p style="color: var(--text-muted);">No criminal history found for this citizen.</p>
                    </div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <?php foreach($records as $r): ?>
                            <div style="background: white; border-left: 4px solid #ef4444; padding: 1rem; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                    <strong style="color: #b91c1c; font-size: 1.1rem;"><?= htmlspecialchars($r['crime_name']) ?></strong>
                                    <span style="font-size: 0.85rem; color: var(--text-muted);">Record #<?= $r['record_id'] ?></span>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; font-size: 0.95rem;">
                                    <div><strong>Punishment:</strong> <?= htmlspecialchars($r['punishment']) ?></div>
                                    <div><strong>Penalty:</strong> $<?= number_format($r['penalty'], 2) ?></div>
                                    <div style="grid-column: span 2;"><strong>Timeline:</strong> <?= htmlspecialchars($r['timeline']) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php include '../includes/footer.php'; ?>
