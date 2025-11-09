<?php
// Affiliate Marketing System Functions

// Generate unique referral code
function generateReferralCode($userId) {
    global $conn;
    do {
        $code = strtoupper(substr(md5(uniqid($userId, true)), 0, 8));
        $stmt = $conn->prepare("SELECT id FROM affiliates WHERE referral_code = ?");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $result = $stmt->get_result();
    } while ($result->num_rows > 0);
    
    return $code;
}

// Create affiliate account
function createAffiliate($userId, $commissionType = 'percentage', $commissionValue = 10) {
    global $conn;
    
    // Check if user already has affiliate account
    $stmt = $conn->prepare("SELECT id FROM affiliates WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        return false;
    }
    
    $referralCode = generateReferralCode($userId);
    
    // Use provided commission or default to 10% percentage
    $stmt = $conn->prepare("INSERT INTO affiliates (user_id, referral_code, commission_type, commission_value) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issd", $userId, $referralCode, $commissionType, $commissionValue);
    
    return $stmt->execute();
}

// Get affiliate by user ID
function getAffiliateByUserId($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM affiliates WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Get affiliate by referral code
function getAffiliateByCode($code) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM affiliates WHERE referral_code = ? AND status = 'active'");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Track affiliate click
function trackAffiliateClick($referralCode) {
    global $conn;
    
    $affiliate = getAffiliateByCode($referralCode);
    if (!$affiliate) return false;
    
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    $landingPage = $_SERVER['REQUEST_URI'];
    
    $stmt = $conn->prepare("INSERT INTO affiliate_clicks (affiliate_id, referral_code, ip_address, user_agent, referer_url, landing_page) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $affiliate['id'], $referralCode, $ipAddress, $userAgent, $referer, $landingPage);
    
    return $stmt->execute();
}

// Track affiliate referral (only records, doesn't count until purchase)
function trackAffiliateReferral($referralCode, $userId = null) {
    global $conn;
    
    $affiliate = getAffiliateByCode($referralCode);
    if (!$affiliate) return false;
    
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    
    // Check if referral already exists for this user
    if ($userId) {
        $checkStmt = $conn->prepare("SELECT id FROM affiliate_referrals WHERE affiliate_id = ? AND referred_user_id = ?");
        $checkStmt->bind_param("ii", $affiliate['id'], $userId);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            return false; // Already tracked
        }
    }
    
    $stmt = $conn->prepare("INSERT INTO affiliate_referrals (affiliate_id, referred_user_id, referral_code, ip_address, user_agent, converted = FALSE, purchase_made = FALSE) VALUES (?, ?, ?, ?, ?, FALSE, FALSE)");
    $stmt->bind_param("iisss", $affiliate['id'], $userId, $referralCode, $ipAddress, $userAgent);
    
    if ($stmt->execute()) {
        // Don't update total_referrals here - only after purchase
        return $conn->insert_id;
    }
    
    return false;
}

// Mark referral as converted (purchased)
function markReferralConverted($referralId) {
    global $conn;
    
    // Get referral info
    $stmt = $conn->prepare("SELECT affiliate_id, purchase_made FROM affiliate_referrals WHERE id = ?");
    $stmt->bind_param("i", $referralId);
    $stmt->execute();
    $referral = $stmt->get_result()->fetch_assoc();
    
    if (!$referral) return false;
    
    // Update referral
    $updateStmt = $conn->prepare("UPDATE affiliate_referrals SET converted = 1, conversion_date = NOW(), purchase_made = 1, first_purchase_date = NOW() WHERE id = ?");
    $updateStmt->bind_param("i", $referralId);
    $updateStmt->execute();
    
    // Only increment total_referrals if this is first purchase
    if (!$referral['purchase_made']) {
        $affiliateStmt = $conn->prepare("UPDATE affiliates SET total_referrals = total_referrals + 1 WHERE id = ?");
        $affiliateStmt->bind_param("i", $referral['affiliate_id']);
        $affiliateStmt->execute();
    }
    
    return true;
}

// Calculate commission
function calculateCommission($orderAmount, $commissionType, $commissionValue) {
    if ($commissionType === 'percentage') {
        return ($orderAmount * $commissionValue) / 100;
    } else {
        return $commissionValue;
    }
}

// Create commission for order
function createAffiliateCommission($orderId, $affiliateId, $orderAmount) {
    global $conn;
    
    $affiliate = getAffiliateById($affiliateId);
    if (!$affiliate) return false;
    
    // IMPORTANT: Prevent self-referral commission
    // Get the user who made the order
    $orderStmt = $conn->prepare("SELECT user_id FROM orders WHERE id = ?");
    $orderStmt->bind_param("i", $orderId);
    $orderStmt->execute();
    $orderResult = $orderStmt->get_result();
    $order = $orderResult->fetch_assoc();
    
    // If the buyer is the affiliate themselves, don't create commission
    if ($order && $order['user_id'] == $affiliate['user_id']) {
        return false; // Affiliates cannot earn commission on their own purchases
    }
    
    $commissionAmount = calculateCommission($orderAmount, $affiliate['commission_type'], $affiliate['commission_value']);
    
    $stmt = $conn->prepare("INSERT INTO affiliate_commissions (affiliate_id, order_id, commission_amount, commission_type, commission_rate, order_amount, level) VALUES (?, ?, ?, ?, ?, ?, 1)");
    $stmt->bind_param("iidsdd", $affiliateId, $orderId, $commissionAmount, $affiliate['commission_type'], $affiliate['commission_value'], $orderAmount);
    
    if ($stmt->execute()) {
        // Update affiliate earnings
        $updateStmt = $conn->prepare("UPDATE affiliates SET pending_earnings = pending_earnings + ?, total_earnings = total_earnings + ?, total_sales = total_sales + 1 WHERE id = ?");
        $updateStmt->bind_param("ddi", $commissionAmount, $commissionAmount, $affiliateId);
        $updateStmt->execute();
        
        // Process MLM commissions
        processMLMCommissions($affiliateId, $orderId, $orderAmount);
        
        return true;
    }
    
    return false;
}

// Process Multi-Level Marketing commissions (up to 10 levels)
function processMLMCommissions($affiliateId, $orderId, $orderAmount) {
    global $conn;
    
    $mlmEnabled = getAffiliateSetting('mlm_enabled', 0);
    if (!$mlmEnabled) return;
    
    $maxLevels = (int)getAffiliateSetting('mlm_levels', 10);
    
    // Get parent affiliates up to max levels
    $currentAffiliateId = $affiliateId;
    
    for ($level = 2; $level <= $maxLevels; $level++) {
        // Get parent affiliate
        $stmt = $conn->prepare("SELECT parent_affiliate_id FROM affiliate_mlm_structure WHERE affiliate_id = ? LIMIT 1");
        $stmt->bind_param("i", $currentAffiliateId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) break; // No more parents
        
        $row = $result->fetch_assoc();
        $parentAffiliateId = $row['parent_affiliate_id'];
        
        if (!$parentAffiliateId) break;
        
        $commissionRate = (float)getAffiliateSetting("level_{$level}_commission", 0);
        
        if ($commissionRate > 0) {
            $commissionAmount = ($orderAmount * $commissionRate) / 100;
            
            $insertStmt = $conn->prepare("INSERT INTO affiliate_commissions (affiliate_id, order_id, commission_amount, commission_type, commission_rate, order_amount, level, parent_affiliate_id) VALUES (?, ?, ?, 'percentage', ?, ?, ?, ?)");
            $insertStmt->bind_param("iidddii", $parentAffiliateId, $orderId, $commissionAmount, $commissionRate, $orderAmount, $level, $currentAffiliateId);
            $insertStmt->execute();
            
            // Update parent earnings
            $updateStmt = $conn->prepare("UPDATE affiliates SET pending_earnings = pending_earnings + ?, total_earnings = total_earnings + ? WHERE id = ?");
            $updateStmt->bind_param("ddi", $commissionAmount, $commissionAmount, $parentAffiliateId);
            $updateStmt->execute();
        }
        
        // Move to next level
        $currentAffiliateId = $parentAffiliateId;
    }
}

// Get affiliate by ID
function getAffiliateById($affiliateId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM affiliates WHERE id = ?");
    $stmt->bind_param("i", $affiliateId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Get affiliate setting
function getAffiliateSetting($key, $default = '') {
    global $conn;
    $stmt = $conn->prepare("SELECT setting_value FROM affiliate_settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['setting_value'];
    }
    return $default;
}

// Get affiliate stats (only count referrals who made purchases)
function getAffiliateStats($affiliateId) {
    global $conn;
    
    $stats = [];
    
    // Total clicks
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM affiliate_clicks WHERE affiliate_id = ?");
    $stmt->bind_param("i", $affiliateId);
    $stmt->execute();
    $stats['total_clicks'] = $stmt->get_result()->fetch_assoc()['total'];
    
    // Total referrals (only those who made purchases)
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM affiliate_referrals WHERE affiliate_id = ? AND purchase_made = 1");
    $stmt->bind_param("i", $affiliateId);
    $stmt->execute();
    $stats['total_referrals'] = $stmt->get_result()->fetch_assoc()['total'];
    
    // Converted referrals (same as total since we only count purchases)
    $stats['converted_referrals'] = $stats['total_referrals'];
    
    // Total signups (including those who haven't purchased)
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM affiliate_referrals WHERE affiliate_id = ?");
    $stmt->bind_param("i", $affiliateId);
    $stmt->execute();
    $stats['total_signups'] = $stmt->get_result()->fetch_assoc()['total'];
    
    // Conversion rate (purchases / signups)
    $stats['conversion_rate'] = $stats['total_signups'] > 0 ? 
        round(($stats['total_referrals'] / $stats['total_signups']) * 100, 2) : 0;
    
    return $stats;
}

// Request payout
function requestPayout($affiliateId, $amount, $paymentMethod, $paymentDetails) {
    global $conn;
    
    $affiliate = getAffiliateById($affiliateId);
    if (!$affiliate) return false;
    
    $minPayout = (float)getAffiliateSetting('min_payout_amount', 500);
    
    if ($amount < $minPayout) {
        return ['success' => false, 'message' => "Minimum payout amount is ₹{$minPayout}"];
    }
    
    if ($amount > $affiliate['pending_earnings']) {
        return ['success' => false, 'message' => 'Insufficient balance'];
    }
    
    $stmt = $conn->prepare("INSERT INTO affiliate_payouts (affiliate_id, amount, payment_method, payment_details) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("idss", $affiliateId, $amount, $paymentMethod, $paymentDetails);
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Payout request submitted successfully'];
    }
    
    return ['success' => false, 'message' => 'Failed to submit payout request'];
}

// Approve commission
function approveCommission($commissionId) {
    global $conn;
    $stmt = $conn->prepare("UPDATE affiliate_commissions SET status = 'approved', approved_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $commissionId);
    return $stmt->execute();
}

// Process payout
function processPayout($payoutId, $transactionId, $status = 'completed') {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM affiliate_payouts WHERE id = ?");
    $stmt->bind_param("i", $payoutId);
    $stmt->execute();
    $payout = $stmt->get_result()->fetch_assoc();
    
    if (!$payout) return false;
    
    $updateStmt = $conn->prepare("UPDATE affiliate_payouts SET status = ?, transaction_id = ?, processed_at = NOW(), completed_at = NOW() WHERE id = ?");
    $updateStmt->bind_param("ssi", $status, $transactionId, $payoutId);
    
    if ($updateStmt->execute() && $status === 'completed') {
        // Update affiliate earnings
        $affiliateStmt = $conn->prepare("UPDATE affiliates SET pending_earnings = pending_earnings - ?, paid_earnings = paid_earnings + ? WHERE id = ?");
        $affiliateStmt->bind_param("ddi", $payout['amount'], $payout['amount'], $payout['affiliate_id']);
        $affiliateStmt->execute();
        
        // Mark commissions as paid
        $commissionStmt = $conn->prepare("UPDATE affiliate_commissions SET status = 'paid', paid_at = NOW() WHERE affiliate_id = ? AND status = 'approved' LIMIT ?");
        $limit = 1000; // Safety limit
        $commissionStmt->bind_param("ii", $payout['affiliate_id'], $limit);
        $commissionStmt->execute();
        
        return true;
    }
    
    return false;
}

// Get affiliate referral link
function getAffiliateLink($referralCode, $path = '') {
    $baseUrl = rtrim(SITE_URL, '/');
    $path = ltrim($path, '/');
    return $baseUrl . '/' . $path . '?ref=' . $referralCode;
}

// Add affiliate to MLM structure
function addToMLMStructure($affiliateId, $parentAffiliateId = null) {
    global $conn;
    
    $level = 1;
    $path = $affiliateId;
    
    if ($parentAffiliateId) {
        // Get parent info
        $stmt = $conn->prepare("SELECT level, path FROM affiliate_mlm_structure WHERE affiliate_id = ?");
        $stmt->bind_param("i", $parentAffiliateId);
        $stmt->execute();
        $parent = $stmt->get_result()->fetch_assoc();
        
        if ($parent) {
            $level = $parent['level'] + 1;
            $path = $parent['path'] . '/' . $affiliateId;
        }
    }
    
    $insertStmt = $conn->prepare("INSERT INTO affiliate_mlm_structure (affiliate_id, parent_affiliate_id, level, path) VALUES (?, ?, ?, ?)");
    $insertStmt->bind_param("iiis", $affiliateId, $parentAffiliateId, $level, $path);
    
    return $insertStmt->execute();
}
?>
