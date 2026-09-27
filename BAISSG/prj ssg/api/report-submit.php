<?php
/**
 * ClubHub - Xử lý gửi phản ánh thông tin sai / link hỏng
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

csrf_protect();

$db = get_db_connection();
if (!$db) {
    flash_set('error', 'Lỗi hệ thống: Không thể kết nối cơ sở dữ liệu.');
    redirect('index.php');
}

$club_id = !empty($_POST['club_id']) ? (int)$_POST['club_id'] : null;
$event_id = !empty($_POST['event_id']) ? (int)$_POST['event_id'] : null;
$report_type = $_POST['report_type'] ?? 'wrong_info';
$reporter_name = trim($_POST['reporter_name'] ?? '');
$reporter_email = trim($_POST['reporter_email'] ?? '');
$details = trim($_POST['details'] ?? '');

if (empty($details)) {
    flash_set('error', 'Vui lòng nhập chi tiết phản ánh sai sót.');
    $referer = $_SERVER['HTTP_REFERER'] ?? url('index.php');
    header("Location: " . $referer);
    exit;
}

if (!in_array($report_type, ['wrong_info', 'broken_link', 'other'])) {
    $report_type = 'wrong_info';
}

try {
    $stmt = $db->prepare("
        INSERT INTO feedback_reports (club_id, event_id, report_type, reporter_name, reporter_email, details, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    $stmt->execute([$club_id, $event_id, $report_type, $reporter_name, $reporter_email, $details]);

    flash_set('success', 'Cảm ơn bạn đã phản ánh! Ban quản trị sẽ xác minh và điều chỉnh thông tin sớm nhất.');
} catch (Exception $e) {
    error_log("Lỗi lưu phản ánh: " . $e->getMessage());
    flash_set('error', 'Không thể lưu phản ánh vào lúc này. Vui lòng thử lại sau.');
}

$referer = $_SERVER['HTTP_REFERER'] ?? url('index.php');
header("Location: " . $referer);
exit;
