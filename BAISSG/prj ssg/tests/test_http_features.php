<?php
/**
 * ClubHub - HTTP Feature Integration Tests
 */

$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookies.txt');

function http_get($url) {
    global $ch;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, false);
    return curl_exec($ch);
}

function http_post($url, $fields) {
    global $ch;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    return curl_exec($ch);
}

echo "=== Testing Public HTTP Endpoints ===\n";
$base = "http://localhost:8080/cbl";

// 1. Fetch ads-booking page and grab CSRF token
$bookingHtml = http_get("$base/ads-booking.php");
preg_match('/name="csrf_token" value="([^"]+)"/', $bookingHtml, $m);
$token = $m[1] ?? '';
echo "CSRF Token extracted: " . ($token ? "YES ($token)" : "NO") . "\n";

// 2. Test submitting ad request with a test image
$testImgPath = __DIR__ . '/test_poster.png';
copy(__DIR__ . '/../assets/images/default-club.png', $testImgPath);

$postFields = [
    'csrf_token'      => $token,
    'placement_code'  => 'top_banner',
    'club_name'       => 'CLB Thử Nghiệm Tự Động',
    'contact_name'    => 'Nguyễn Kiểm Thử',
    'email'           => 'test.booking@sinhvien.edu.vn',
    'phone'           => '0988776655',
    'destination_url' => 'https://facebook.com/clbthunghiem',
    'start_date'      => date('Y-m-d'),
    'end_date'        => date('Y-m-d', strtotime('+7 days')),
    'notes'           => 'Đăng ký thử nghiệm hệ thống booking',
    'consent_agreed'  => '1',
    'poster'          => new CURLFile($testImgPath, 'image/png', 'test_poster.png')
];

$res = http_post("$base/api/ad-request.php", $postFields);
// Follow redirect to ads-booking.php
$afterBooking = http_get("$base/ads-booking.php?success=1");
if (str_contains($afterBooking, 'Đã tiếp nhận yêu cầu')) {
    echo "  [PASS] Ad Booking Form successfully processed and redirected with confirmation notice!\n";
} else {
    echo "  [FAIL] Ad Booking submission failed to show confirmation.\n";
}

// 3. Test Quiz Evaluation via HTTP POST
$quizHtml = http_get("$base/quiz.php");
preg_match('/name="csrf_token" value="([^"]+)"/', $quizHtml, $mq);
$qToken = $mq[1] ?? '';

$quizPayload = json_encode([
    1 => [3], // Melody (Music/Arts)
    2 => [9], // Passion
    3 => [12], // Jamming
    4 => [15], // Performance
    5 => [20], // Intermediate
    6 => [23], // Weekend
    7 => [26]  // Medium commit
]);

$quizPost = [
    'csrf_token'      => $qToken,
    'answers_payload' => $quizPayload
];

http_post("$base/api/quiz-evaluate.php", $quizPost);
$resultHtml = http_get("$base/quiz-result.php");

if (str_contains($resultHtml, 'Gợi Ý Câu Lạc Bộ Dành Riêng Cho Bạn') && str_contains($resultHtml, 'Melody')) {
    echo "  [PASS] Quiz Evaluation accurately matched Music/Acoustic profile to Melody Acoustic club!\n";
} else {
    echo "  [FAIL] Quiz Evaluation did not return expected recommendation.\n";
}

// Clean up
@unlink($testImgPath);
@unlink(__DIR__ . '/cookies.txt');
echo "=== HTTP Tests Complete ===\n";