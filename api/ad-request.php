<?php
/**
 * ClubHub - Tiếp nhận đăng ký thuê vị trí quảng cáo poster từ đại diện CLB
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

csrf_protect();

$db = get_db_connection();
if (!$db) {
    flash_set('error', 'Lỗi hệ thống: Không thể kết nối cơ sở dữ liệu.');
    redirect('ads-booking.php');
}

$club_name = trim($_POST['club_name'] ?? '');
$contact_name = trim($_POST['contact_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$destination_url = trim($_POST['destination_url'] ?? '');
$placement_code = trim($_POST['placement_code'] ?? '');
$start_date = trim($_POST['start_date'] ?? '');
$end_date = trim($_POST['end_date'] ?? '');
$notes = trim($_POST['notes'] ?? '');
$consent_agreed = !empty($_POST['consent_agreed']) ? 1 : 0;

$errors = [];

if (empty($club_name)) $errors[] = 'Vui lòng nhập tên Câu lạc bộ.';
if (empty($contact_name)) $errors[] = 'Vui lòng nhập họ tên người liên hệ.';
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email liên hệ không hợp lệ.';
if (empty($phone) || !preg_match('/^[0-9+() \-\.]{8,20}$/', $phone)) $errors[] = 'Số điện thoại liên hệ không hợp lệ.';

$cleaned_url = safe_url($destination_url);
if (empty($cleaned_url)) {
    $errors[] = 'Liên kết đích không hợp lệ. Chỉ chấp nhận liên kết an toàn bắt đầu bằng http:// hoặc https://.';
}

if (!in_array($placement_code, [PLACEMENT_TOP_BANNER, PLACEMENT_HOME_FEATURED, PLACEMENT_CATEGORY_PAGE])) {
    $errors[] = 'Vị trí quảng cáo được chọn không hợp lệ.';
}

$today = date('Y-m-d');
if (empty($start_date) || $start_date < $today) {
    $errors[] = 'Ngày bắt đầu quảng bá phải từ hôm nay (' . date('d/m/Y') . ') trở đi.';
}
if (empty($end_date) || $end_date < $start_date) {
    $errors[] = 'Ngày kết thúc phải diễn ra sau hoặc trùng với ngày bắt đầu.';
}

if (!$consent_agreed) {
    $errors[] = 'Bạn cần tích chọn xác nhận đồng ý cung cấp thông tin để chúng tôi liên hệ xử lý yêu cầu.';
}

// Kiểm tra upload file poster
if (empty($_FILES['poster']) || $_FILES['poster']['error'] === UPLOAD_ERR_NO_FILE) {
    $errors[] = 'Vui lòng chọn file hình ảnh poster quảng cáo.';
}

if (!empty($errors)) {
    foreach ($errors as $err) {
        flash_set('error', $err);
    }
    // Lưu lại dữ liệu cũ vào session để điền lại
    $_SESSION['old_ad_request'] = $_POST;
    redirect('ads-booking.php');
}

// Upload file ảnh poster an toàn
$upload_result = handle_image_upload($_FILES['poster'], 'ads', 2);
if (!$upload_result['success']) {
    flash_set('error', 'Lỗi tải ảnh poster: ' . $upload_result['error']);
    $_SESSION['old_ad_request'] = $_POST;
    redirect('ads-booking.php');
}

$poster_path = $upload_result['path'];

// Sinh mã tra cứu duy nhất (Tracking Code)
$tracking_code = 'AD-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 5));

try {
    $stmt = $db->prepare("
        INSERT INTO ad_requests (
            tracking_code, club_name, contact_name, email, phone, poster_image,
            destination_url, placement_code, start_date, end_date, notes,
            consent_agreed, quote_amount, payment_status, approval_status, is_enabled, created_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, 0, 'unpaid', 'pending', 1, NOW()
        )
    ");
    $stmt->execute([
        $tracking_code, $club_name, $contact_name, $email, $phone, $poster_path,
        $cleaned_url, $placement_code, $start_date, $end_date, $notes,
        $consent_agreed
    ]);

    unset($_SESSION['old_ad_request']);
    
    // Lưu mã tra cứu vào session để trang hiển thị banner chúc mừng
    $_SESSION['last_ad_booking'] = [
        'tracking_code' => $tracking_code,
        'club_name'     => $club_name,
        'contact_name'  => $contact_name,
        'email'         => $email,
        'start_date'    => $start_date,
        'end_date'      => $end_date
    ];

    flash_set('success', "Đã tiếp nhận yêu cầu. Quản trị viên sẽ liên hệ để xác nhận nội dung, lịch và chi phí. Mã tra cứu của bạn là: {$tracking_code}");
    redirect('ads-booking.php?success=1');
} catch (Exception $e) {
    error_log("Lỗi lưu yêu cầu quảng cáo: " . $e->getMessage());
    flash_set('error', 'Có lỗi xảy ra khi gửi yêu cầu. Vui lòng thử lại sau.');
    redirect('ads-booking.php');
}
