<?php
// pages/announcements.php
session_start();

if (!isset($_SESSION['citizen_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../config/db.php';

$message = '';
$error = '';
$citizen_id = $_SESSION['citizen_id'];
$role = strtolower($_SESSION['role']);
$is_admin = ($role === 'admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$is_admin) {
        $error = "Only Admins can post announcements.";
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'post') {
            $title = $_POST['title'];
            $description = $_POST['description'];
            $area_code = !empty($_POST['area_code']) ? $_POST['area_code'] : NULL;
            $building_id = !empty($_POST['building_id']) ? $_POST['building_id'] : NULL;
            $status = 'active';

            try {
                $stmt = $conn->prepare("INSERT INTO Announcement (title, description, area_code, building_id, status, posted_by, start_time, end_time, publish_date) VALUES (?, ?, ?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY), CURDATE())");
                $stmt->bind_param("ssiisi", $title, $description, $area_code, $building_id, $status, $citizen_id);
                if ($stmt->execute()) {
                    $message = "Announcement posted successfully.";
                } else {
                    $error = "Failed to post announcement.";
                }
            } catch (Exception $e) {
                $error = "Database Error: " . $e->getMessage();
            }
        } elseif ($action === 'delete') {
            $id = $_POST['announcement_id'];
            try {
                $stmt = $conn->prepare("DELETE FROM Announcement WHERE announcement_id=?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $message = "Announcement deleted successfully.";
                } else {
                    $error = "Failed to delete announcement.";
                }
            } catch (Exception $e) {
                $error = "Database Error during deletion.";
            }
        }
    }
}

// Fetch areas and buildings for the form
$areas = [];
$buildings = [];
if ($is_admin) {
    try { $res = $conn->query("SELECT area_code, area_name FROM Area ORDER BY area_name ASC"); if($res) while($r=$res->fetch_assoc()) $areas[]=$r; } catch(Exception $e){}
    try { $res = $conn->query("SELECT building_id, type FROM Building"); if($res) while($r=$res->fetch_assoc()) $buildings[]=$r; } catch(Exception $e){}
}

// Determine the user's area and building so they only see relevant announcements
$user_area = NULL;
$user_building = NULL;
try {
    $stmt = $conn->prepare("SELECT area_code, building_id FROM Citizen WHERE citizen_id = ?");
    $stmt->bind_param("i", $citizen_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $user_area = $row['area_code'];
        $user_building = $row['building_id'];
    }
} catch (Exception $e) {}

// Fetch announcements
$announcements = [];
try {
    if ($is_admin) {
        // Admin sees all
        $sql = "SELECT a.*, ar.area_name, b.type as building_type 
                FROM Announcement a 
                LEFT JOIN Area ar ON a.area_code = ar.area_code 
                LEFT JOIN Building b ON a.building_id = b.building_id 
                ORDER BY a.announcement_id DESC";
        $result = $conn->query($sql);
    } else {
        // Users see global, their area, or their building
        $sql = "SELECT a.*, ar.area_name, b.type as building_type 
                FROM Announcement a 
                LEFT JOIN Area ar ON a.area_code = ar.area_code 
                LEFT JOIN Building b ON a.building_id = b.building_id 
                WHERE a.status = 'active' 
                AND (
                    (a.area_code IS NULL AND a.building_id IS NULL) 
                    OR (a.area_code = ? AND a.building_id IS NULL)
                    OR (a.building_id = ?)
                )
                ORDER BY a.announcement_id DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $user_area, $user_building);
        $stmt->execute();
        $result = $stmt->get_result();
    }
    
    if ($result) {
        while($row = $result->fetch_assoc()) {
            $announcements[] = $row;
        }
    }
} catch (Exception $e) {}

include '../includes/header.php';
include '../includes/navbar.php';
?>

<main>
    <div class="dashboard-header" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2>Announcements</h2>
            <p style="color: var(--text-muted); margin-top: 0.5rem;">Important notices and communications.</p>
        </div>
        <?php if ($is_admin): ?>
            <button class="btn" style="width: auto;" onclick="document.getElementById('postModal').style.display='block'">+ Post Announcement</button>
        <?php endif; ?>
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

    <div style="display: grid; gap: 1.5rem;">
        <?php if (empty($announcements)): ?>
            <div class="card" style="text-align: center; padding: 3rem;">
                <p style="color: var(--text-muted); font-size: 1.1rem;">There are no active announcements for your area.</p>
            </div>
        <?php else: ?>
            <?php foreach ($announcements as $ann): ?>
                <div class="card" style="border-left: 4px solid var(--accent-color);">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                        <div>
                            <h3 style="color: var(--primary-color); font-size: 1.25rem; margin-bottom: 0.25rem;"><?= htmlspecialchars($ann['title']) ?></h3>
                            <span style="font-size: 0.85rem; color: var(--text-muted);">
                                Posted on <?= date('M d, Y', strtotime($ann['publish_date'])) ?> | 
                                Target: 
                                <?php
                                    if ($ann['building_id']) { echo "Building #" . htmlspecialchars($ann['building_id']); }
                                    elseif ($ann['area_code']) { echo "Area: " . htmlspecialchars($ann['area_name']); }
                                    else { echo "Global (All Citizens)"; }
                                ?>
                            </span>
                        </div>
                        <?php if ($is_admin): ?>
                            <form method="POST" onsubmit="return confirm('Delete this announcement?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="announcement_id" value="<?= $ann['announcement_id'] ?>">
                                <button type="submit" style="background: none; border: none; color: #ef4444; cursor: pointer; font-size: 1.2rem;">&times;</button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div style="color: var(--secondary-color); line-height: 1.6;">
                        <?= nl2br(htmlspecialchars($ann['description'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php if ($is_admin): ?>
<!-- Post Modal -->
<div id="postModal" class="modal" style="display:none; position:fixed; z-index:1; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.5);">
    <div class="modal-content" style="background-color: var(--card-bg); margin: 5% auto; padding: 2rem; border-radius: 8px; width: 90%; max-width: 600px;">
        <span onclick="document.getElementById('postModal').style.display='none'" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
        <h3 style="margin-bottom: 1.5rem; color: var(--primary-color);">Post Announcement</h3>
        <form method="POST" action="">
            <input type="hidden" name="action" value="post">
            <div class="form-group" style="margin-bottom: 1rem;"><label>Title</label><input type="text" name="title" required style="width:100%; padding:0.5rem;"></div>
            <div class="form-group" style="margin-bottom: 1rem;"><label>Content</label><textarea name="description" required rows="5" style="width:100%; padding:0.5rem; font-family:inherit;"></textarea></div>
            
            <p style="margin-top: 1rem; margin-bottom: 0.5rem; font-size: 0.9rem; color: var(--text-muted);">Leave both targeting fields blank to post a Global Announcement.</p>
            
            <div class="form-group" style="margin-bottom: 1rem;"><label>Target Area (Optional)</label>
                <select name="area_code" style="width:100%; padding:0.5rem;">
                    <option value="">-- All Areas --</option>
                    <?php foreach($areas as $a): ?><option value="<?= $a['area_code'] ?>"><?= htmlspecialchars($a['area_name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 1rem;"><label>Target Building ID (Optional)</label>
                <select name="building_id" style="width:100%; padding:0.5rem;">
                    <option value="">-- All Buildings --</option>
                    <?php foreach($buildings as $b): ?><option value="<?= $b['building_id'] ?>">Building #<?= $b['building_id'] ?> (<?= ucfirst($b['type']) ?>)</option><?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn">Publish Notice</button>
        </form>
    </div>
</div>
<script>
window.onclick = function(event) {
    if (event.target == document.getElementById('postModal')) document.getElementById('postModal').style.display = 'none';
}
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
