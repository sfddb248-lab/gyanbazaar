<?php
// Authentication Functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'editor']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ' . SITE_URL . '/index.php');
        exit;
    }
}

// Sanitization
function clean($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Generate slug
function generateSlug($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'n-a' : $text;
}

// Format currency
function formatCurrency($amount) {
    $currency = getSetting('currency', 'USD');
    $symbols = ['USD' => '$', 'EUR' => '€', 'GBP' => '£', 'INR' => '₹'];
    $symbol = $symbols[$currency] ?? $currency;
    return $symbol . number_format($amount, 2);
}

// Generate order number
function generateOrderNumber() {
    return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

// Calculate tax
function calculateTax($amount) {
    $taxRate = getSetting('tax_percentage', 0);
    return ($amount * $taxRate) / 100;
}

// Apply coupon
function applyCoupon($code, $amount) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM coupons WHERE code = ? AND status = 'active' AND (expiry_date IS NULL OR expiry_date >= CURDATE()) AND (usage_limit IS NULL OR used_count < usage_limit)");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($coupon = $result->fetch_assoc()) {
        if ($amount >= $coupon['min_purchase']) {
            if ($coupon['type'] == 'flat') {
                return min($coupon['value'], $amount);
            } else {
                return ($amount * $coupon['value']) / 100;
            }
        }
    }
    return 0;
}

// File upload handler
function uploadFile($file, $folder = 'products') {
    $targetDir = UPLOAD_PATH . $folder . '/';
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $fileName = time() . '_' . basename($file['name']);
    $targetFile = $targetDir . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        return $folder . '/' . $fileName;
    }
    return false;
}

// Get cart items
function getCartItems() {
    if (!isset($_SESSION['cart'])) {
        return [];
    }
    
    global $conn;
    $productIds = array_keys($_SESSION['cart']);
    if (empty($productIds)) {
        return [];
    }
    
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $stmt = $conn->prepare("SELECT * FROM products WHERE id IN ($placeholders) AND status = 'active'");
    $stmt->bind_param(str_repeat('i', count($productIds)), ...$productIds);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Calculate cart total
function getCartTotal() {
    $items = getCartItems();
    $total = 0;
    foreach ($items as $item) {
        $total += $item['price'];
    }
    return $total;
}

// Send Email Function
function sendEmail($to, $subject, $message) {
    global $conn;
    
    // Get site settings
    $siteName = getSetting('site_name', 'GyanBazaar');
    $adminEmail = getSetting('admin_email', 'admin@gyanbazaar.com');
    
    // Email headers
    $headers = "From: $siteName <$adminEmail>\r\n";
    $headers .= "Reply-To: $adminEmail\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    // Format message as HTML
    $htmlMessage = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #1266f1; color: white; padding: 20px; text-align: center; }
            .content { background: #f8f9fa; padding: 20px; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            .button { display: inline-block; padding: 10px 20px; background: #1266f1; color: white; text-decoration: none; border-radius: 5px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>$siteName</h2>
            </div>
            <div class='content'>
                " . nl2br($message) . "
            </div>
            <div class='footer'>
                <p>This is an automated email from $siteName</p>
                <p>Please do not reply to this email</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Try to send email
    $sent = @mail($to, $subject, $htmlMessage, $headers);
    
    // Log email attempt
    error_log("Email sent to: $to | Subject: $subject | Status: " . ($sent ? 'Success' : 'Failed'));
    
    return $sent;
}

// Get course image with category-based fallback
function getCourseImage($product) {
    // If product has screenshots, use the first one
    if (!empty($product['screenshots'])) {
        $screenshots = explode(',', $product['screenshots']);
        return UPLOAD_URL . $screenshots[0];
    }
    
    // Category-based placeholder images from Unsplash
    $categoryImages = [
        'programming' => 'https://images.unsplash.com/photo-1516116216624-53e697fedbea?w=400&h=300&fit=crop',
        'web development' => 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=400&h=300&fit=crop',
        'design' => 'https://images.unsplash.com/photo-1561070791-2526d30994b5?w=400&h=300&fit=crop',
        'business' => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=400&h=300&fit=crop',
        'marketing' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=400&h=300&fit=crop',
        'photography' => 'https://images.unsplash.com/photo-1452587925148-ce544e77e70d?w=400&h=300&fit=crop',
        'music' => 'https://images.unsplash.com/photo-1511379938547-c1f69419868d?w=400&h=300&fit=crop',
        'health' => 'https://images.unsplash.com/photo-1505751172876-fa1923c5c528?w=400&h=300&fit=crop',
        'fitness' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=400&h=300&fit=crop',
        'language' => 'https://images.unsplash.com/photo-1546410531-bb4caa6b424d?w=400&h=300&fit=crop',
        'science' => 'https://images.unsplash.com/photo-1532094349884-543bc11b234d?w=400&h=300&fit=crop',
        'mathematics' => 'https://images.unsplash.com/photo-1509228468518-180dd4864904?w=400&h=300&fit=crop',
        'data science' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=400&h=300&fit=crop',
        'artificial intelligence' => 'https://images.unsplash.com/photo-1677442136019-21780ecad995?w=400&h=300&fit=crop',
        'mobile development' => 'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=400&h=300&fit=crop',
        'game development' => 'https://images.unsplash.com/photo-1511512578047-dfb367046420?w=400&h=300&fit=crop',
        'cybersecurity' => 'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=400&h=300&fit=crop',
        'cloud computing' => 'https://images.unsplash.com/photo-1544197150-b99a580bb7a8?w=400&h=300&fit=crop',
        'blockchain' => 'https://images.unsplash.com/photo-1639762681485-074b7f938ba0?w=400&h=300&fit=crop',
        'finance' => 'https://images.unsplash.com/photo-1579621970563-ebec7560ff3e?w=400&h=300&fit=crop',
    ];
    
    // Get category name
    global $conn;
    if (isset($product['category_id'])) {
        $catResult = $conn->query("SELECT name FROM categories WHERE id = " . (int)$product['category_id']);
        if ($catResult && $catRow = $catResult->fetch_assoc()) {
            $categoryName = strtolower($catRow['name']);
            
            // Check for exact match
            if (isset($categoryImages[$categoryName])) {
                return $categoryImages[$categoryName];
            }
            
            // Check for partial match
            foreach ($categoryImages as $key => $image) {
                if (strpos($categoryName, $key) !== false || strpos($key, $categoryName) !== false) {
                    return $image;
                }
            }
        }
    }
    
    // Product type based fallback
    $productType = $product['product_type'] ?? 'digital';
    $typeImages = [
        'course' => 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=400&h=300&fit=crop',
        'ebook' => 'https://images.unsplash.com/photo-1512820790803-83ca734da794?w=400&h=300&fit=crop',
        'digital' => 'https://images.unsplash.com/photo-1488590528505-98d2b5aba04b?w=400&h=300&fit=crop',
    ];
    
    return $typeImages[$productType] ?? $typeImages['digital'];
}
