<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookies_rep.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookies_rep.txt');

$base = "http://localhost:8080/cbl";
curl_setopt($ch, CURLOPT_URL, "$base/club-detail.php?slug=clb-lap-trinh-tri-tue-nhan-tao");
$page = curl_exec($ch);
preg_match('/name="csrf_token" value="([^"]+)"/', $page, $m);
$token = $m[1] ?? '';

$fields = [
    'csrf_token'     => $token,
    'club_id'        => 1,
    'report_type'    => 'broken_link',
    'reporter_name'  => 'Sinh Vien Test',
    'reporter_email' => 'sv@sinhvien.edu.vn',
    'details'        => 'Link đăng ký workshop bị báo 404'
];

curl_setopt($ch, CURLOPT_URL, "$base/api/report-submit.php");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
$res = curl_exec($ch);

// Follow referer
curl_setopt($ch, CURLOPT_URL, "$base/club-detail.php?slug=clb-lap-trinh-tri-tue-nhan-tao");
curl_setopt($ch, CURLOPT_POST, false);
$afterPage = curl_exec($ch);

if (str_contains($afterPage, 'Cảm ơn bạn đã phản ánh')) {
    echo "  [PASS] Feedback report successfully submitted and recorded!\n";
} else {
    echo "  [FAIL] Feedback report submission failed.\n";
}
@unlink(__DIR__ . '/cookies_rep.txt');