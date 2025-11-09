<?php
require_once '../config/config.php';
requireAdmin();
$pageTitle = 'View OTPs - Admin';

// Get all pending OTPs (check if columns exist first)
$otps = [];
$checkColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'otp_code'");
if ($checkColumn && $checkColumn->num_rows > 0) {
    $result = $conn->query("
        SELECT u.id, u.name, u.email, u.otp_code, u.otp_expiry, u.created_at, u.status
        FROM users u
        WHERE u.otp_code IS NOT NULL
        ORDER BY u.created_at DESC
    ");
    if ($result) {
        $otps = $result->fetch_all(MYSQLI_ASSOC);
    }
}

// Get OTP email logs if table exists
$otpLogs = [];
$result = $conn->query("SHOW TABLES LIKE 'otp_emails'");
if ($result->num_rows > 0) {
    $otpLogs = $conn->query("
        SELECT oe.*, u.name, u.email as user_email
        FROM otp_emails oe
        JOIN users u ON oe.user_id = u.id
        ORDER BY oe.created_at DESC
        LIMIT 50
    ")->fetch_all(MYSQLI_ASSOC);
}

include 'includes/admin-header.php';
?>

<div class="container-fluid my-4">
    <h2 class="mb-4"><i class="fas fa-key"></i> View OTP Codes</h2>
    
    <?php if (empty($otps) && $checkColumn->num_rows == 0): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> <strong>OTP System Not Active:</strong> 
        The OTP verification system has been removed or is not configured in your database.
        Users are automatically activated upon registration.
    </div>
    <?php else: ?>
    <div class="alert alert-warning">
        <i class="fas fa-info-circle"></i> <strong>Development Mode:</strong> 
        This page shows OTP codes for testing when email is not configured.
        <strong>Remove this page in production!</strong>
    </div>
    <?php endif; ?>
    
    <!-- Current OTPs -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fas fa-clock"></i> Active OTP Codes
                <span class="badge bg-light text-dark"><?php echo count($otps); ?></span>
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($otps)): ?>
                <p class="text-muted text-center">No active OTPs</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>OTP Code</th>
                                <th>Status</th>
                                <th>Expires</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($otps as $otp): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($otp['name']); ?></td>
                                <td><?php echo htmlspecialchars($otp['email']); ?></td>
                                <td>
                                    <code style="font-size: 1.2rem; font-weight: bold; color: #1266f1;">
                                        <?php echo $otp['otp_code']; ?>
                                    </code>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $otp['status'] == 'pending' ? 'warning' : 'secondary'; ?>">
                                        <?php echo ucfirst($otp['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    $expired = strtotime($otp['otp_expiry']) < time();
                                    $color = $expired ? 'text-danger' : 'text-success';
                                    ?>
                                    <span class="<?php echo $color; ?>">
                                        <?php echo date('M d, H:i', strtotime($otp['otp_expiry'])); ?>
                                        <?php if ($expired): ?>
                                            <i class="fas fa-exclamation-triangle"></i> Expired
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, H:i', strtotime($otp['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-success" 
                                            onclick="copyOTP('<?php echo $otp['otp_code']; ?>')">
                                        <i class="fas fa-copy"></i> Copy
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- OTP Email Logs -->
    <?php if (!empty($otpLogs)): ?>
    <div class="card">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-history"></i> OTP Email Log</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>OTP</th>
                            <th>Purpose</th>
                            <th>Status</th>
                            <th>Attempts</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($otpLogs as $log): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($log['name']); ?></td>
                            <td><?php echo htmlspecialchars($log['user_email']); ?></td>
                            <td><code><?php echo $log['otp_code']; ?></code></td>
                            <td><?php echo ucfirst($log['purpose']); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $log['status'] == 'sent' ? 'success' : 
                                        ($log['status'] == 'failed' ? 'danger' : 'warning'); 
                                ?>">
                                    <?php echo ucfirst($log['status']); ?>
                                </span>
                            </td>
                            <td><?php echo $log['attempts']; ?></td>
                            <td><?php echo date('M d, H:i', strtotime($log['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function copyOTP(otp) {
    navigator.clipboard.writeText(otp).then(function() {
        alert('OTP copied to clipboard: ' + otp);
    }, function() {
        // Fallback
        prompt('Copy this OTP:', otp);
    });
}
</script>

<?php include 'includes/admin-footer.php'; ?>
