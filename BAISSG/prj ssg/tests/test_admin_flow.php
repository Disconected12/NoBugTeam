<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/admin_cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/admin_cookies.txt');
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$base = "http://localhost:8080/cbl";

// 1. Get login page
curl_setopt($ch, CURLOPT_URL, "$base/admin/login.php");
$loginHtml = curl_exec($ch);
preg_match('/name="csrf_token" value="([^"]+)"/', $loginHtml, $m);
$token = $m[1] ?? '';

// 2. Perform login
curl_setopt($ch, CURLOPT_URL, "$base/admin/login.php");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'csrf_token' => $token,
    'username'   => 'admin',
    'password'   => 'AdminClubHub@2026'
]);
$dashboardHtml = curl_exec($ch);

if (str_contains($dashboardHtml, 'Tổng quan (Dashboard)') && str_contains($dashboardHtml, 'Ban Quản Trị ClubHub')) {
    echo "  [PASS] Admin logged in successfully to Dashboard!\n";
} else {
    echo "  [FAIL] Admin login failed.\n";
}

// 3. View feedback
curl_setopt($ch, CURLOPT_URL, "$base/admin/feedback.php");
curl_setopt($ch, CURLOPT_POST, false);
$feedbackHtml = curl_exec($ch);

if (str_contains($feedbackHtml, 'Link đăng ký workshop bị báo 404')) {
    echo "  [PASS] Feedback report is present in Admin Feedback queue!\n";
} else {
    echo "  [FAIL] Feedback report missing in Admin panel.\n";
}

// 4. View new ad booking
curl_setopt($ch, CURLOPT_URL, "$base/admin/ads.php");
$adsHtml = curl_exec($ch);

if (str_contains($adsHtml, 'CLB Thử Nghiệm Tự Động')) {
    echo "  [PASS] Newly submitted Ad Request appears in Admin Ads Queue!\n";
} else {
    echo "  [FAIL] Ad request not found in Admin Ads Queue.\n";
}

@unlink(__DIR__ . '/admin_cookies.txt');
echo "=== Admin Flow Tests Complete ===\n";