<?php
require_once '../config/config.php';
requireAdmin();

$pageTitle = 'Commission Levels - Admin';
$currentPage = 'commission-levels';

// Handle form submission
if (isset($_POST['save_levels'])) {
    $mlmEnabled = isset($_POST['mlm_enabled']) ? 1 : 0;
    $mlmLevels = (int)$_POST['mlm_levels'];
    
    // Save MLM settings
    $conn->query("INSERT INTO affiliate_settings (setting_key, setting_value) VALUES ('mlm_enabled', '$mlmEnabled') ON DUPLICATE KEY UPDATE setting_value = '$mlmEnabled'");
    $conn->query("INSERT INTO affiliate_settings (setting_key, setting_value) VALUES ('mlm_levels', '$mlmLevels') ON DUPLICATE KEY UPDATE setting_value = '$mlmLevels'");
    
    // Save level commissions
    for ($level = 1; $level <= 10; $level++) {
        if (isset($_POST["level_{$level}_commission"])) {
            $levelCommission = (float)$_POST["level_{$level}_commission"];
            $conn->query("INSERT INTO affiliate_settings (setting_key, setting_value) VALUES ('level_{$level}_commission', '$levelCommission') ON DUPLICATE KEY UPDATE setting_value = '$levelCommission'");
        }
    }
    
    $message = "Commission levels saved successfully!";
    $messageType = "success";
}

// Get current settings
$mlmEnabled = (int)getAffiliateSetting('mlm_enabled', 0);
$mlmLevels = (int)getAffiliateSetting('mlm_levels', 10);

// Get level commissions
$levelCommissions = [];
for ($i = 1; $i <= 10; $i++) {
    $levelCommissions[$i] = (float)getAffiliateSetting("level_{$i}_commission", $i == 1 ? 30 : 0);
}

// Calculate total commission percentage
$totalCommission = array_sum(array_slice($levelCommissions, 0, $mlmLevels));

include 'includes/admin-header.php';
?>

<style>
.level-card {
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 15px;
    transition: all 0.3s;
    background: white;
}

.level-card:hover {
    border-color: #1266f1;
    box-shadow: 0 4px 12px rgba(18, 102, 241, 0.15);
}

.level-card.active {
    border-color: #1266f1;
    background: #f0f7ff;
}

.level-number {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    font-weight: bold;
}

.commission-input {
    font-size: 24px;
    font-weight: bold;
    text-align: center;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    padding: 10px;
}

.commission-input:focus {
    border-color: #1266f1;
    box-shadow: 0 0 0 3px rgba(18, 102, 241, 0.1);
}

.tree-diagram {
    text-align: center;
    padding: 30px;
    background: #f8f9fa;
    border-radius: 12px;
    margin: 20px 0;
}

.tree-level {
    margin: 20px 0;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 20px;
}

.tree-node {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.tree-node small {
    font-size: 10px;
    opacity: 0.9;
}

.tree-connector {
    width: 2px;
    height: 30px;
    background: #ccc;
    margin: 0 auto;
}

.stats-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px;
    padding: 25px;
    text-align: center;
}

.stats-card h2 {
    font-size: 48px;
    margin: 10px 0;
}
</style>

<div class="container-fluid py-4">
    <?php if (isset($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-layer-group"></i> Commission Level Management</h2>
            <p class="text-muted">Configure multi-level commission distribution for your affiliate program</p>
        </div>
    </div>
    
    <form method="POST">
        <div class="row">
            <!-- Left Column: Settings -->
            <div class="col-lg-8">
                <!-- MLM Enable/Disable -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5><i class="fas fa-sitemap"></i> Multi-Level Marketing</h5>
                                <p class="text-muted mb-0">Enable commission distribution across multiple levels</p>
                            </div>
                            <div class="col-md-6 text-end">
                                <div class="form-check form-switch d-inline-block">
                                    <input class="form-check-input" type="checkbox" name="mlm_enabled" id="mlm_enabled" 
                                           <?php echo $mlmEnabled ? 'checked' : ''; ?> style="width: 60px; height: 30px;">
                                    <label class="form-check-label ms-2" for="mlm_enabled">
                                        <strong><?php echo $mlmEnabled ? 'Enabled' : 'Disabled'; ?></strong>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Number of Levels -->
                <div class="card mb-4">
                    <div class="card-body">
                        <label class="form-label"><strong>Number of Active Levels</strong></label>
                        <input type="range" class="form-range" name="mlm_levels" id="mlm_levels" 
                               min="1" max="10" value="<?php echo $mlmLevels; ?>" 
                               oninput="updateLevelCount(this.value)">
                        <div class="text-center mt-2">
                            <span class="badge bg-primary" style="font-size: 20px; padding: 10px 20px;">
                                <span id="level_count"><?php echo $mlmLevels; ?></span> Levels
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Commission Levels -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="mb-4"><i class="fas fa-percentage"></i> Commission Rates by Level</h5>
                        
                        <?php for ($level = 1; $level <= 10; $level++): ?>
                        <div class="level-card level-<?php echo $level; ?>" id="level_card_<?php echo $level; ?>" 
                             style="<?php echo $level > $mlmLevels ? 'opacity: 0.3;' : ''; ?>">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <div class="level-number">
                                        <?php echo $level; ?>
                                    </div>
                                </div>
                                <div class="col">
                                    <h6 class="mb-1">
                                        <strong>Level <?php echo $level; ?></strong>
                                        <?php if ($level == 1): ?>
                                            <span class="badge bg-success">Direct Referrals</span>
                                        <?php else: ?>
                                            <span class="badge bg-info">Indirect (Level <?php echo $level - 1; ?> Referrals)</span>
                                        <?php endif; ?>
                                    </h6>
                                    <small class="text-muted">
                                        <?php if ($level == 1): ?>
                                            Commission for users directly referred by the affiliate
                                        <?php else: ?>
                                            Commission for users referred by Level <?php echo $level - 1; ?> affiliates
                                        <?php endif; ?>
                                    </small>
                                </div>
                                <div class="col-auto">
                                    <div class="input-group" style="width: 150px;">
                                        <input type="number" class="form-control commission-input" 
                                               name="level_<?php echo $level; ?>_commission" 
                                               id="level_<?php echo $level; ?>_commission"
                                               value="<?php echo $levelCommissions[$level]; ?>" 
                                               min="0" max="100" step="0.01"
                                               onchange="updateTotal()">
                                        <span class="input-group-text"><strong>%</strong></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <!-- Save Button -->
                <div class="text-center mt-4">
                    <button type="submit" name="save_levels" class="btn btn-primary btn-lg">
                        <i class="fas fa-save"></i> Save Commission Levels
                    </button>
                </div>
            </div>
            
            <!-- Right Column: Preview & Stats -->
            <div class="col-lg-4">
                <!-- Total Commission -->
                <div class="stats-card mb-4">
                    <h6>Total Commission Distribution</h6>
                    <h2 id="total_commission"><?php echo number_format($totalCommission, 2); ?>%</h2>
                    <p class="mb-0">Across <?php echo $mlmLevels; ?> levels</p>
                </div>
                
                <!-- Visual Tree -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h6 class="text-center mb-3"><i class="fas fa-project-diagram"></i> Commission Flow</h6>
                        <div class="tree-diagram">
                            <div class="tree-level">
                                <div class="tree-node">
                                    <div>YOU</div>
                                    <small>Buyer</small>
                                </div>
                            </div>
                            <div class="tree-connector"></div>
                            <?php for ($i = 1; $i <= min(3, $mlmLevels); $i++): ?>
                            <div class="tree-level">
                                <div class="tree-node" style="background: linear-gradient(135deg, #667eea <?php echo $i * 20; ?>%, #764ba2 100%);">
                                    <div>L<?php echo $i; ?></div>
                                    <small id="tree_level_<?php echo $i; ?>"><?php echo $levelCommissions[$i]; ?>%</small>
                                </div>
                            </div>
                            <?php if ($i < min(3, $mlmLevels)): ?>
                            <div class="tree-connector"></div>
                            <?php endif; ?>
                            <?php endfor; ?>
                            <?php if ($mlmLevels > 3): ?>
                            <div class="text-muted mt-2">
                                <small>+ <?php echo $mlmLevels - 3; ?> more levels</small>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Example Calculation -->
                <div class="card">
                    <div class="card-body">
                        <h6><i class="fas fa-calculator"></i> Example Calculation</h6>
                        <p class="text-muted small">For a ₹1,000 order:</p>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <?php for ($i = 1; $i <= $mlmLevels; $i++): ?>
                                <tr>
                                    <td><strong>Level <?php echo $i; ?></strong></td>
                                    <td class="text-end">
                                        <span class="text-success" id="example_level_<?php echo $i; ?>">
                                            ₹<?php echo number_format(1000 * $levelCommissions[$i] / 100, 2); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endfor; ?>
                                <tr class="table-active">
                                    <td><strong>Total</strong></td>
                                    <td class="text-end">
                                        <strong class="text-primary" id="example_total">
                                            ₹<?php echo number_format(1000 * $totalCommission / 100, 2); ?>
                                        </strong>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function updateLevelCount(value) {
    document.getElementById('level_count').textContent = value;
    
    // Show/hide level cards
    for (let i = 1; i <= 10; i++) {
        const card = document.getElementById('level_card_' + i);
        if (i <= value) {
            card.style.opacity = '1';
            card.classList.add('active');
        } else {
            card.style.opacity = '0.3';
            card.classList.remove('active');
        }
    }
    
    updateTotal();
}

function updateTotal() {
    const levels = parseInt(document.getElementById('mlm_levels').value);
    let total = 0;
    
    for (let i = 1; i <= levels; i++) {
        const input = document.getElementById('level_' + i + '_commission');
        const value = parseFloat(input.value) || 0;
        total += value;
        
        // Update tree
        const treeNode = document.getElementById('tree_level_' + i);
        if (treeNode) {
            treeNode.textContent = value.toFixed(2) + '%';
        }
        
        // Update example
        const example = document.getElementById('example_level_' + i);
        if (example) {
            example.textContent = '₹' + (1000 * value / 100).toFixed(2);
        }
    }
    
    document.getElementById('total_commission').textContent = total.toFixed(2) + '%';
    document.getElementById('example_total').textContent = '₹' + (1000 * total / 100).toFixed(2);
}

// Initialize
updateLevelCount(<?php echo $mlmLevels; ?>);
</script>

<?php include 'includes/admin-footer.php'; ?>
