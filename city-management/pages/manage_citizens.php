<?php
// pages/manage_citizens.php
session_start();

if (!isset($_SESSION['citizen_id'])) {
    header("Location: ../login.php");
    exit();
}

$role = $_SESSION['role'];
if (strtolower($role) !== 'admin') {
    die("<div style='padding: 2rem; text-align: center;'><h2 style='color: #ef4444;'>Access Denied</h2><p>Only Admins can manage citizens.</p></div>");
}

require_once '../config/db.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = $_POST['name'];
        $phone = $_POST['phone'];
        $nid = $_POST['nid'];
        $occupation = $_POST['occupation'];
        $role_input = strtolower($_POST['role']);
        $area_code = !empty($_POST['area_code']) ? $_POST['area_code'] : NULL;
        $building_id = !empty($_POST['building_id']) ? $_POST['building_id'] : NULL;

        try {
            $stmt = $conn->prepare("INSERT INTO Citizen (name, phone, nid, occupation, role, area_code, building_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssii", $name, $phone, $nid, $occupation, $role_input, $area_code, $building_id);
            if ($stmt->execute()) {
                $message = "Citizen added successfully.";
            } else {
                $error = "Failed to add citizen.";
            }
        } catch (Exception $e) {
            $error = "Database Error. NID may already exist.";
        }
    } elseif ($action === 'edit') {
        $id = $_POST['citizen_id'];
        $name = $_POST['name'];
        $phone = $_POST['phone'];
        $occupation = $_POST['occupation'];
        $role_input = strtolower($_POST['role']);
        $area_code = !empty($_POST['area_code']) ? $_POST['area_code'] : NULL;
        $building_id = !empty($_POST['building_id']) ? $_POST['building_id'] : NULL;

        try {
            $stmt = $conn->prepare("UPDATE Citizen SET name=?, phone=?, occupation=?, role=?, area_code=?, building_id=? WHERE citizen_id=?");
            $stmt->bind_param("ssssiii", $name, $phone, $occupation, $role_input, $area_code, $building_id, $id);
            if ($stmt->execute()) {
                $message = "Citizen updated successfully.";
            } else {
                $error = "Failed to update citizen.";
            }
        } catch (Exception $e) {
            $error = "Database Error during update.";
        }
    } elseif ($action === 'delete') {
        $id = $_POST['citizen_id'];
        try {
            $stmt = $conn->prepare("DELETE FROM Citizen WHERE citizen_id=?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $message = "Citizen deleted successfully.";
            } else {
                $error = "Failed to delete citizen.";
            }
        } catch (Exception $e) {
            $error = "Cannot delete citizen due to existing records/dependencies.";
        }
    }
}

// Fetch areas
$areas = [];
try { $res = $conn->query("SELECT area_code, area_name FROM Area ORDER BY area_name ASC"); if($res) while($r=$res->fetch_assoc()) $areas[]=$r; } catch(Exception $e){}

// Fetch buildings
$buildings = [];
try { $res = $conn->query("SELECT building_id, type FROM Building"); if($res) while($r=$res->fetch_assoc()) $buildings[]=$r; } catch(Exception $e){}

// Fetch citizens
$citizens = [];
try {
    $result = $conn->query("SELECT c.*, a.area_name FROM Citizen c LEFT JOIN Area a ON c.area_code = a.area_code ORDER BY c.citizen_id DESC");
    if ($result) {
        while($row = $result->fetch_assoc()) {
            $citizens[] = $row;
        }
    }
} catch (Exception $e) {}

include '../includes/header.php';
include '../includes/navbar.php';
?>

<main>
    <div class="dashboard-header" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2>Citizen Management</h2>
            <p style="color: var(--text-muted); margin-top: 0.5rem;">Manage the city's registered citizens and roles.</p>
        </div>
        <button class="btn" style="width: auto;" onclick="document.getElementById('addModal').style.display='block'">+ Add Citizen</button>
    </div>

    <?php if ($message): ?>
        <div style="padding: 1rem; margin-bottom: 1rem; background-color: #d1fae5; color: #065f46; border-radius: 6px;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div style="padding: 1rem; margin-bottom: 1rem; background-color: #fee2e2; color: #b91c1c; border-radius: 6px;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="card" style="overflow-x: auto;">
        <table class="table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color); text-align: left;">
                    <th style="padding: 1rem 0.5rem;">ID</th>
                    <th style="padding: 1rem 0.5rem;">Name</th>
                    <th style="padding: 1rem 0.5rem;">NID</th>
                    <th style="padding: 1rem 0.5rem;">Phone</th>
                    <th style="padding: 1rem 0.5rem;">Role</th>
                    <th style="padding: 1rem 0.5rem;">Area</th>
                    <th style="padding: 1rem 0.5rem;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($citizens)): ?>
                    <tr><td colspan="7" style="text-align: center; padding: 2rem;">No citizens found.</td></tr>
                <?php else: ?>
                    <?php foreach ($citizens as $c): ?>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 1rem 0.5rem;"><?= htmlspecialchars($c['citizen_id']) ?></td>
                            <td style="padding: 1rem 0.5rem; font-weight: 500;"><?= htmlspecialchars($c['name']) ?></td>
                            <td style="padding: 1rem 0.5rem;"><?= htmlspecialchars($c['nid']) ?></td>
                            <td style="padding: 1rem 0.5rem;"><?= htmlspecialchars($c['phone']) ?></td>
                            <td style="padding: 1rem 0.5rem;">
                                <span style="background: var(--background-color); padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.85rem; font-weight: 600;">
                                    <?= htmlspecialchars(ucfirst($c['role'])) ?>
                                </span>
                            </td>
                            <td style="padding: 1rem 0.5rem;"><?= htmlspecialchars($c['area_name'] ?? 'Unassigned') ?></td>
                            <td style="padding: 1rem 0.5rem; white-space: nowrap;">
                                <button class="btn" style="padding: 0.4rem 0.8rem; font-size: 0.85rem; margin-right: 0.5rem;" onclick='openEditModal(<?= htmlspecialchars(json_encode($c), ENT_QUOTES, "UTF-8") ?>)'>Edit</button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this citizen?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="citizen_id" value="<?= $c['citizen_id'] ?>">
                                    <button type="submit" class="btn" style="background: #ef4444; padding: 0.4rem 0.8rem; font-size: 0.85rem;">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<!-- Add Modal -->
<div id="addModal" class="modal" style="display:none; position:fixed; z-index:1; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.5);">
    <div class="modal-content" style="background-color: var(--card-bg); margin: 5% auto; padding: 2rem; border-radius: 8px; width: 90%; max-width: 500px;">
        <span onclick="document.getElementById('addModal').style.display='none'" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
        <h3 style="margin-bottom: 1.5rem; color: var(--primary-color);">Add New Citizen</h3>
        <form method="POST" action="">
            <input type="hidden" name="action" value="create">
            <div class="form-group" style="margin-bottom: 1rem;"><label>Name</label><input type="text" name="name" required style="width:100%; padding:0.5rem;"></div>
            <div class="form-group" style="margin-bottom: 1rem;"><label>NID</label><input type="text" name="nid" required style="width:100%; padding:0.5rem;"></div>
            <div class="form-group" style="margin-bottom: 1rem;"><label>Phone</label><input type="text" name="phone" required style="width:100%; padding:0.5rem;"></div>
            <div class="form-group" style="margin-bottom: 1rem;"><label>Occupation</label><input type="text" name="occupation" style="width:100%; padding:0.5rem;"></div>
            <div class="form-group" style="margin-bottom: 1rem;"><label>Role</label>
                <select name="role" required style="width:100%; padding:0.5rem;">
                    <option value="renter">Renter</option>
                    <option value="owner">Owner</option>
                    <option value="police">Police</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 1rem;"><label>Area</label>
                <select name="area_code" style="width:100%; padding:0.5rem;">
                    <option value="">-- Select Area --</option>
                    <?php foreach($areas as $a): ?><option value="<?= $a['area_code'] ?>"><?= htmlspecialchars($a['area_name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 1rem;"><label>Building ID</label>
                <select name="building_id" style="width:100%; padding:0.5rem;">
                    <option value="">-- Select Building --</option>
                    <?php foreach($buildings as $b): ?><option value="<?= $b['building_id'] ?>">Bld #<?= $b['building_id'] ?> (<?= ucfirst($b['type']) ?>)</option><?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn">Save Citizen</button>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal" style="display:none; position:fixed; z-index:1; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.5);">
    <div class="modal-content" style="background-color: var(--card-bg); margin: 5% auto; padding: 2rem; border-radius: 8px; width: 90%; max-width: 500px;">
        <span onclick="document.getElementById('editModal').style.display='none'" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
        <h3 style="margin-bottom: 1.5rem; color: var(--primary-color);">Edit Citizen</h3>
        <form method="POST" action="">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="citizen_id" id="edit_citizen_id">
            <div class="form-group" style="margin-bottom: 1rem;"><label>Name</label><input type="text" name="name" id="edit_name" required style="width:100%; padding:0.5rem;"></div>
            <div class="form-group" style="margin-bottom: 1rem;"><label>Phone</label><input type="text" name="phone" id="edit_phone" required style="width:100%; padding:0.5rem;"></div>
            <div class="form-group" style="margin-bottom: 1rem;"><label>Occupation</label><input type="text" name="occupation" id="edit_occupation" style="width:100%; padding:0.5rem;"></div>
            <div class="form-group" style="margin-bottom: 1rem;"><label>Role</label>
                <select name="role" id="edit_role" required style="width:100%; padding:0.5rem;">
                    <option value="renter">Renter</option>
                    <option value="owner">Owner</option>
                    <option value="police">Police</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 1rem;"><label>Area</label>
                <select name="area_code" id="edit_area" style="width:100%; padding:0.5rem;">
                    <option value="">-- Select Area --</option>
                    <?php foreach($areas as $a): ?><option value="<?= $a['area_code'] ?>"><?= htmlspecialchars($a['area_name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 1rem;"><label>Building ID</label>
                <select name="building_id" id="edit_building" style="width:100%; padding:0.5rem;">
                    <option value="">-- Select Building --</option>
                    <?php foreach($buildings as $b): ?><option value="<?= $b['building_id'] ?>">Bld #<?= $b['building_id'] ?> (<?= ucfirst($b['type']) ?>)</option><?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn">Update Citizen</button>
        </form>
    </div>
</div>

<script>
function openEditModal(citizen) {
    document.getElementById('edit_citizen_id').value = citizen.citizen_id;
    document.getElementById('edit_name').value = citizen.name;
    document.getElementById('edit_phone').value = citizen.phone;
    document.getElementById('edit_occupation').value = citizen.occupation;
    document.getElementById('edit_role').value = citizen.role;
    document.getElementById('edit_area').value = citizen.area_code;
    document.getElementById('edit_building').value = citizen.building_id;
    document.getElementById('editModal').style.display = 'block';
}
window.onclick = function(event) {
    if (event.target == document.getElementById('addModal')) document.getElementById('addModal').style.display = 'none';
    if (event.target == document.getElementById('editModal')) document.getElementById('editModal').style.display = 'none';
}
</script>

<?php include '../includes/footer.php'; ?>
