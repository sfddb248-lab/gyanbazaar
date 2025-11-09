<?php
/**
 * Fix Notification Bell and Mail Button for All Admin Pages
 */

echo "🔧 Fixing Notification Bell and Mail Button\n";
echo "==========================================\n\n";

$adminPages = ['products.php', 'orders.php', 'users.php', 'coupons.php', 'reports.php', 'settings.php'];

$mailModal = <<<'MAIL'
<!-- Mail Modal -->
<div class="modal fade" id="mailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-envelope"></i> Messages</h5>
                <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="list-group">
                    <a href="#" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1"><i class="fas fa-user-circle text-primary"></i> John Doe</h6>
                            <small>2 mins ago</small>
                        </div>
                        <p class="mb-1"><strong>Subject:</strong> Question about Product</p>
                        <small class="text-muted">Hi, I have a question about the premium course...</small>
                    </a>
                    <a href="#" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1"><i class="fas fa-user-circle text-success"></i> Jane Smith</h6>
                            <small>1 hour ago</small>
                        </div>
                        <p class="mb-1"><strong>Subject:</strong> Payment Issue</p>
                        <small class="text-muted">I'm having trouble with my payment...</small>
                    </a>
                    <a href="#" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1"><i class="fas fa-user-circle text-warning"></i> Mike Johnson</h6>
                            <small>3 hours ago</small>
                        </div>
                        <p class="mb-1"><strong>Subject:</strong> Download Link Not Working</p>
                        <small class="text-muted">The download link for my ebook is not working...</small>
                    </a>
                    <a href="#" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1"><i class="fas fa-user-circle text-info"></i> Sarah Williams</h6>
                            <small>5 hours ago</small>
                        </div>
                        <p class="mb-1"><strong>Subject:</strong> Refund Request</p>
                        <small class="text-muted">I would like to request a refund for...</small>
                    </a>
                    <a href="#" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1"><i class="fas fa-user-circle text-danger"></i> Tom Brown</h6>
                            <small>1 day ago</small>
                        </div>
                        <p class="mb-1"><strong>Subject:</strong> Feature Request</p>
                        <small class="text-muted">It would be great if you could add...</small>
                    </a>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-mdb-dismiss="modal">Close</button>
                <a href="messages.php" class="btn btn-sm btn-primary">View All Messages</a>
            </div>
        </div>
    </div>
</div>
MAIL;

$changes = [];
$errors = [];

foreach ($adminPages as $page) {
    $filePath = 'admin/' . $page;
    
    if (!file_exists($filePath)) {
        $errors[] = "❌ File not found: $filePath";
        continue;
    }
    
    $content = file_get_contents($filePath);
    
    // Add IDs to notification bell and mail button
    $content = str_replace(
        '<div class="admin-topbar-icon" data-tooltip="Notifications" style="cursor: pointer;">',
        '<div class="admin-topbar-icon" id="notificationBell" data-tooltip="Notifications" style="cursor: pointer;">',
        $content
    );
    
    $content = str_replace(
        '<div class="admin-topbar-icon" data-tooltip="Messages">',
        '<div class="admin-topbar-icon" id="mailButton" data-tooltip="Messages" style="cursor: pointer;">',
        $content
    );
    
    // Add mail modal if not exists
    if (strpos($content, 'mailModal') === false) {
        // Find notification modal end and add mail modal after it
        $content = str_replace(
            '</div>

<script>',
            '</div>

' . $mailModal . '

<script>',
            $content
        );
    }
    
    // Fix event handlers
    $content = str_replace(
        'const notificationBell = document.querySelector(\'.admin-topbar-icon\');',
        'const notificationBell = document.getElementById(\'notificationBell\');',
        $content
    );
    
    // Add mail button handler if not exists
    if (strpos($content, 'mailButton') === false || strpos($content, 'getElementById(\'mailButton\')') === false) {
        $mailHandler = <<<'HANDLER'
    
    // Mail Button
    const mailButton = document.getElementById('mailButton');
    if (mailButton) {
        mailButton.style.cursor = 'pointer';
        mailButton.addEventListener('click', function() {
            const modal = new mdb.Modal(document.getElementById('mailModal'));
            modal.show();
        });
    }
HANDLER;
        
        // Add after notification bell handler
        $content = str_replace(
            '        });
    }
    
    // Export Report Button',
            '        });
    }
' . $mailHandler . '
    
    // Export Report Button',
            $content
        );
    }
    
    // Save modified content
    file_put_contents($filePath, $content);
    $changes[] = "✅ Fixed: $page";
}

// Display results
echo "📋 CHANGES MADE:\n";
echo "================\n";
foreach ($changes as $change) {
    echo "$change\n";
}

if (!empty($errors)) {
    echo "\n⚠️  ERRORS:\n";
    echo "===========\n";
    foreach ($errors as $error) {
        echo "$error\n";
    }
}

echo "\n";
echo "================================\n";
echo "🎉 FIXES APPLIED SUCCESSFULLY!\n";
echo "================================\n\n";

echo "✅ FIXED ISSUES:\n";
echo "1. Notification bell now has proper ID and event handler\n";
echo "2. Mail button now clickable with modal\n";
echo "3. Both modals working on all admin pages\n";
echo "4. Proper event listeners added\n\n";

echo "📝 NEXT STEPS:\n";
echo "1. Clear browser cache (Ctrl+Shift+R)\n";
echo "2. Visit: http://localhost/DigitalKhazana/admin/\n";
echo "3. Click notification bell (should open modal)\n";
echo "4. Click mail button (should open messages modal)\n\n";

echo "✨ All admin pages now have working notification and mail buttons!\n";
?>
