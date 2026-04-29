<?php
// pages/manage_areas.php
session_start();

if (!isset($_SESSION['citizen_id'])) {
    header("Location: ../login.php");
    exit();
}

$role = $_SESSION['role'];
if (strtolower($role) !== 'admin') {
    die("<div style='padding: 2rem; text-align: center;'><h2 style='color: #ef4444;'>Access Denied</h2></div>");
}

require_once '../config/db.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Areas
    if ($action === 'create_area') {
        $area_code = $_POST['area_code'];
        $name = $_POST['name'];
        $size = !empty($_POST['size']) ? floatval($_POST['size']) : 0;
        
        try {
            $stmt = $conn->prepare("INSERT INTO Area (area_code, area_name, area_size) VALUES (?, ?, ?)");
            $stmt->bind_param("isd", $area_code, $name, $size);
            if ($stmt->execute()) $message = "Area added successfully.";
            else $error = "Failed to add Area.";
        } catch (Exception $e) { $error = "Error: Area Code may already exist."; }
    } elseif ($action === 'delete_area') {
        $id = $_POST['area_code'];
        try {
            $stmt = $conn->prepare("DELETE FROM Area WHERE area_code=?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) $message = "Area deleted.";
            else $error = "Failed to delete Area.";
        } catch (Exception $e) { $error = "Cannot delete Area due to existing dependencies."; }
    }
    
    // Buildings
    elseif ($action === 'create_building') {
        $building_id = $_POST['building_id'];
        $area_code = $_POST['area_code'];
        $floors = $_POST['floors'];
        $type = $_POST['type'];
        
        try {
            $stmt = $conn->prepare("INSERT INTO Building (building_id, area_code, floors, type) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiis", $building_id, $area_code, $floors, $type);
            if ($stmt->execute()) $message = "Building added successfully.";
            else $error = "Failed to add Building.";
        } catch (Exception $e) { $error = "Error: Building ID may already exist or Invalid Area."; }
    } elseif ($action === 'delete_building') {
        $id = $_POST['building_id'];
        try {
            $stmt = $conn->prepare("DELETE FROM Building WHERE building_id=?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) $message = "Building deleted.";
            else $error = "Failed to delete Building.";
        } catch (Exception $e) { $error = "Cannot delete Building due to existing dependencies."; }
    }
}

// Fetch Areas
$areas = [];
try {
    $res = $conn->query("SELECT * FROM Area ORDER BY area_code ASC");
    if ($res) while($r = $res->fetch_assoc()) $areas[] = $r;
} catch (Exception $e) {}

// Fetch Buildings
$buildings = [];
try {
    $res = $conn->query("SELECT b.*, a.area_name FROM Building b JOIN Area a ON b.area_code = a.area_code ORDER BY a.area_name ASC, b.building_id ASC");
    if ($res) while($r = $res->fetch_assoc()) $buildings[] = $r;
} catch (Exception $e) {}

include '../includes/header.php';
include '../includes/navbar.php';
?>

<main>
    <div class="dashboard-header">
        <h2>Area & Property Management</h2>
        <p style="color: var(--text-muted); margin-top: 0.5rem;">Manage the city's districts and their constituent buildings.</p>
    </div>

    <?php if ($message): ?><div style="padding:1rem;margin-bottom:1rem;background-color:#d1fae5;color:#065f46;border-radius:6px;"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div style="padding:1rem;margin-bottom:1rem;background-color:#fee2e2;color:#b91c1c;border-radius:6px;"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem;">
        
        <!-- AREAS PANEL -->
        <div class="card" style="align-self: start;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="color: var(--primary-color);">City Areas</h3>
                <button class="btn" style="width: auto; padding: 0.4rem 0.8rem; font-size: 0.9rem;" onclick="document.getElementById('areaModal').style.display='block'">+ Add Area</button>
            </div>
            
            <?php if(empty($areas)): ?>
                <p style="color: var(--text-muted); text-align: center; padding: 2rem 0;">No areas defined.</p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php foreach($areas as $a): ?>
                        <div style="border: 1px solid var(--border-color); padding: 1rem; border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong style="font-size: 1.1rem; display: block;"><?= htmlspecialchars($a['area_name']) ?> (Code: <?= htmlspecialchars($a['area_code']) ?>)</strong>
                                <span style="font-size: 0.85rem; color: var(--text-muted);">Size: <?= $a['area_size'] ?> | Pop: <?= $a['population'] ?> | Rent: $<?= number_format($a['avg_rent'], 2) ?></span>
                            </div>
                            <form method="POST" onsubmit="return confirm('Delete this area?');">
                                <input type="hidden" name="action" value="delete_area">
                                <input type="hidden" name="area_code" value="<?= $a['area_code'] ?>">
                                <button type="submit" style="background: none; border: none; color: #ef4444; cursor: pointer; font-size: 1.2rem;">&times;</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- BUILDINGS PANEL -->
        <div class="card" style="align-self: start;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="color: var(--primary-color);">Buildings</h3>
                <button class="btn" style="width: auto; padding: 0.4rem 0.8rem; font-size: 0.9rem;" onclick="document.getElementById('bldModal').style.display='block'">+ Add Building</button>
            </div>
            
            <?php if(empty($buildings)): ?>
                <p style="color: var(--text-muted); text-align: center; padding: 2rem 0;">No buildings mapped.</p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php foreach($buildings as $b): ?>
                        <div style="border: 1px solid var(--border-color); padding: 1rem; border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong style="font-size: 1.1rem; display: block;">Building #<?= htmlspecialchars($b['building_id']) ?></strong>
                                <span style="font-size: 0.85rem; color: var(--text-muted);">📍 <?= htmlspecialchars($b['area_name']) ?> | <?= htmlspecialchars(ucfirst($b['type'])) ?> | <?= $b['floors'] ?> Floors</span>
                            </div>
                            <form method="POST" onsubmit="return confirm('Delete this building?');">
                                <input type="hidden" name="action" value="delete_building">
                                <input type="hidden" name="building_id" value="<?= $b['building_id'] ?>">
                                <button type="submit" style="background: none; border: none; color: #ef4444; cursor: pointer; font-size: 1.2rem;">&times;</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Area Modal -->
<div id="areaModal" class="modal" style="display:none; position:fixed; z-index:1; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.5);">
    <div class="modal-content" style="background-color: var(--card-bg); margin: 10% auto; padding: 2rem; border-radius: 8px; width: 90%; max-width: 400px;">
        <span onclick="document.getElementById('areaModal').style.display='none'" style="float: right; font-size: 24px; cursor: pointer;">&times;</span>
        <h3 style="margin-bottom: 1.5rem; color: var(--primary-color);">New Area</h3>
        <form method="POST" action="">
            <input type="hidden" name="action" value="create_area">
            <div class="form-group" style="margin-bottom: 1rem;"><label>Area Code (INT)</label><input type="number" name="area_code" required style="width:100%; padding:0.5rem;"></div>
            <div class="form-group" style="margin-bottom: 1rem;"><label>Area Name</label><input type="text" name="name" required style="width:100%; padding:0.5rem;"></div>
            <div class="form-group" style="margin-bottom: 1rem;"><label>Area Size (Float)</label><input type="number" step="0.01" name="size" style="width:100%; padding:0.5rem;"></div>
            <button type="submit" class="btn">Create Area</button>
        </form>
    </div>
</div>

<!-- Building Modal -->
<div id="bldModal" class="modal" style="display:none; position:fixed; z-index:1; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.5);">
    <div class="modal-content" style="background-color: var(--card-bg); margin: 10% auto; padding: 2rem; border-radius: 8px; width: 90%; max-width: 400px;">
        <span onclick="document.getElementById('bldModal').style.display='none'" style="float: right; font-size: 24px; cursor: pointer;">&times;</span>
        <h3 style="margin-bottom: 1.5rem; color: var(--primary-color);">New Building</h3>
        <form method="POST" action="">
            <input type="hidden" name="action" value="create_building">
            <div class="form-group" style="margin-bottom: 1rem;"><label>Building ID (INT)</label><input type="number" name="building_id" required style="width:100%; padding:0.5rem;"></div>
            <div class="form-group" style="margin-bottom: 1rem;"><label>Area</label>
                <select name="area_code" required style="width:100%; padding:0.5rem;">
                    <option value="">-- Select Area --</option>
                    <?php foreach($areas as $a): ?><option value="<?= $a['area_code'] ?>"><?= htmlspecialchars($a['area_name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 1rem;"><label>Number of Floors</label><input type="number" name="floors" required style="width:100%; padding:0.5rem;"></div>
            <div class="form-group" style="margin-bottom: 1rem;"><label>Type</label>
                <select name="type" required style="width:100%; padding:0.5rem;">
                    <option value="residential">Residential</option>
                    <option value="commercial">Commercial</option>
                    <option value="govt">Government</option>
                </select>
            </div>
            <button type="submit" class="btn">Create Building</button>
        </form>
    </div>
</div>

<script>
window.onclick = function(event) {
    if (event.target == document.getElementById('areaModal')) document.getElementById('areaModal').style.display = 'none';
    if (event.target == document.getElementById('bldModal')) document.getElementById('bldModal').style.display = 'none';
}
</script>

<?php include '../includes/footer.php'; ?>
