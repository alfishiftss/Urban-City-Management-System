<?php
// pages/area_analysis.php
session_start();

if (!isset($_SESSION['citizen_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../config/db.php';

// Dynamically Calculate and UPDATE live stats for each Area based on the user's custom tables
try {
    $res = $conn->query("SELECT area_code FROM Area");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $ac = $row['area_code'];
            
            // Calc population
            $pop = 0;
            $res_pop = $conn->query("SELECT COUNT(*) as c FROM Citizen WHERE area_code = $ac");
            if ($res_pop) { $r = $res_pop->fetch_assoc(); $pop = $r['c']; }
            
            // Calc avg rent
            $avg = 0;
            $res_rent = $conn->query("SELECT AVG(rent_amount) as a FROM Rent WHERE building_id IN (SELECT building_id FROM Building WHERE area_code = $ac)");
            if ($res_rent) { $r = $res_rent->fetch_assoc(); $avg = $r['a'] ? floatval($r['a']) : 0; }
            
            // Calc crime rate (using verified Crime_Reports)
            $cr = 0;
            $res_crime = $conn->query("SELECT COUNT(*) as c FROM Crime_Report WHERE area_code = $ac AND status = 'verified'");
            if ($res_crime) { $r = $res_crime->fetch_assoc(); $cr = $r['c']; }
            
            $conn->query("UPDATE Area SET population = $pop, avg_rent = $avg, crime_rate = $cr WHERE area_code = $ac");
        }
    }
} catch (Exception $e) {}

// Fetch final stats
$areas = [];
$max_pop = 1; $max_rent = 1; $max_crime = 1;

try {
    $res = $conn->query("SELECT * FROM Area ORDER BY area_name ASC");
    if ($res) {
        while($r = $res->fetch_assoc()) {
            $areas[] = $r;
            if ($r['population'] > $max_pop) $max_pop = $r['population'];
            if ($r['avg_rent'] > $max_rent) $max_rent = $r['avg_rent'];
            if ($r['crime_rate'] > $max_crime) $max_crime = $r['crime_rate'];
        }
    }
} catch (Exception $e) {}

include '../includes/header.php';
include '../includes/navbar.php';
?>

<main>
    <div class="dashboard-header">
        <h2>Area Data & Analytics</h2>
        <p style="color: var(--text-muted); margin-top: 0.5rem;">Live insights into city demographics, real estate, and safety metrics.</p>
    </div>

    <div class="card">
        <h3 style="color: var(--primary-color); margin-bottom: 2rem;">Comparative Analytics</h3>
        
        <?php if(empty($areas)): ?>
            <p style="text-align: center; color: var(--text-muted);">No areas available for analysis.</p>
        <?php else: ?>
            <table style="width: 100%; border-collapse: separate; border-spacing: 0 1.5rem;">
                <?php foreach($areas as $a): 
                    $pop_pct = ($a['population'] / $max_pop) * 100;
                    $rent_pct = ($a['avg_rent'] / $max_rent) * 100;
                    $crime_pct = ($a['crime_rate'] / $max_crime) * 100;
                ?>
                <tr>
                    <td style="width: 150px; vertical-align: top; padding-top: 0.5rem;">
                        <strong style="font-size: 1.1rem;"><?= htmlspecialchars($a['area_name']) ?></strong>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">Code: <?= $a['area_code'] ?></div>
                    </td>
                    <td>
                        <!-- Population -->
                        <div style="margin-bottom: 0.8rem;">
                            <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 0.2rem;">
                                <span>Population (<?= $a['population'] ?>)</span>
                            </div>
                            <div style="width: 100%; background: #e5e7eb; height: 12px; border-radius: 6px; overflow: hidden;">
                                <div style="width: <?= $pop_pct ?>%; background: #3b82f6; height: 100%; border-radius: 6px;"></div>
                            </div>
                        </div>
                        
                        <!-- Avg Rent -->
                        <div style="margin-bottom: 0.8rem;">
                            <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 0.2rem;">
                                <span>Avg Rent ($<?= number_format($a['avg_rent'], 2) ?>)</span>
                            </div>
                            <div style="width: 100%; background: #e5e7eb; height: 12px; border-radius: 6px; overflow: hidden;">
                                <div style="width: <?= $rent_pct ?>%; background: #10b981; height: 100%; border-radius: 6px;"></div>
                            </div>
                        </div>

                        <!-- Crime Rate -->
                        <div>
                            <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 0.2rem;">
                                <span>Verified Crimes (<?= $a['crime_rate'] ?>)</span>
                            </div>
                            <div style="width: 100%; background: #e5e7eb; height: 12px; border-radius: 6px; overflow: hidden;">
                                <div style="width: <?= $crime_pct ?>%; background: #ef4444; height: 100%; border-radius: 6px;"></div>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
