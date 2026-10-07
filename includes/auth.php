<?php
/**
 * ClubHub - Xác thực và phân quyền Quản trị viên (Admin Authentication)
 */

if (!defined('CLUBHUB_INIT')) {
    define('CLUBHUB_INIT', true);
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

const SESSION_TIMEOUT_SECONDS = 7200; // 2 giờ không thao tác sẽ tự đăng xuất
const MAX_LOGIN_ATTEMPTS = 5;
const LOGIN_LOCKOUT_MINUTES = 15;

/**
 * Lấy địa chỉ IP của client một cách an toàn
 */
function get_client_ip(): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '127.0.0.1';
}

/**
 * Kiểm tra xem admin đã đăng nhập và phiên còn hiệu lực không
 */
function is_admin_logged_in(): bool {
    if (empty($_SESSION['admin_user']) || !is_array($_SESSION['admin_user'])) {
        return false;
    }

    // Kiểm tra thời gian hết hạn phiên
    $last_activity = $_SESSION['admin_user']['last_activity'] ?? 0;
    if (time() - $last_activity > SESSION_TIMEOUT_SECONDS) {
        admin_logout();
        return false;
    }

    // Cập nhật mốc thời gian hoạt động mới nhất
    $_SESSION['admin_user']['last_activity'] = time();
    return true;
}

/**
 * Yêu cầu quyền quản trị viên, chuyển hướng nếu chưa đăng nhập
 */
function require_admin(): void {
    if (!is_admin_logged_in()) {
        $_SESSION['admin_redirect'] = $_SERVER['REQUEST_URI'] ?? url('admin/index.php');
        header("Location: " . url('admin/login.php'));
        exit;
    }
}

/**
 * Kiểm tra số lần thử đăng nhập sai để chống brute-force
 */
function check_login_rate_limit(PDO $db, string $ip): array {
    $threshold = date('Y-m-d H:i:s', strtotime('-' . LOGIN_LOCKOUT_MINUTES . ' minutes'));
    $stmt = $db->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempt_time > ?");
    $stmt->execute([$ip, $threshold]);
    $count = (int)$stmt->fetchColumn();

    if ($count >= MAX_LOGIN_ATTEMPTS) {
        return [
            'allowed' => false,
            'message' => 'Bạn đã thử đăng nhập sai quá nhiều lần. Vui lòng đợi ' . LOGIN_LOCKOUT_MINUTES . ' phút trước khi thử lại.'
        ];
    }
    return ['allowed' => true, 'remaining' => MAX_LOGIN_ATTEMPTS - $count];
}

/**
 * Ghi nhận một lần thử đăng nhập thất bại
 */
function record_login_attempt(PDO $db, string $ip): void {
    $stmt = $db->prepare("INSERT INTO login_attempts (ip_address, attempt_time) VALUES (?, NOW())");
    $stmt->execute([$ip]);
}

/**
 * Xóa lịch sử thử đăng nhập khi thành công
 */
function clear_login_attempts(PDO $db, string $ip): void {
    $stmt = $db->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
    $stmt->execute([$ip]);
}

/**
 * Xử lý đăng nhập Quản trị viên
 */
function admin_login(string $username, string $password): array {
    $db = get_db_connection();
    if (!$db) {
        return ['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu.'];
    }

    $ip = get_client_ip();
    $rate_check = check_login_rate_limit($db, $ip);
    if (!$rate_check['allowed']) {
        return ['success' => false, 'message' => $rate_check['message']];
    }

    $stmt = $db->prepare("SELECT id, username, password_hash, full_name, email FROM admins WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        // Đăng nhập thành công -> Đổi session ID ngăn ngừa session fixation
        if (!headers_sent()) {
            session_regenerate_id(true);
        }

        $_SESSION['admin_user'] = [
            'id'            => $admin['id'],
            'username'      => $admin['username'],
            'full_name'     => $admin['full_name'],
            'email'         => $admin['email'],
            'last_activity' => time()
        ];

        clear_login_attempts($db, $ip);

        // Cập nhật lần đăng nhập gần nhất
        $update = $db->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
        $update->execute([$admin['id']]);

        return ['success' => true];
    }

    // Đăng nhập thất bại
    record_login_attempt($db, $ip);
    $attempts_left = max(0, ($rate_check['remaining'] ?? MAX_LOGIN_ATTEMPTS) - 1);
    
    return [
        'success' => false,
        'message' => "Tên đăng nhập hoặc mật khẩu không chính xác. Bạn còn {$attempts_left} lần thử."
    ];
}

/**
 * Đăng xuất quản trị viên
 */
function admin_logout(): void {
    unset($_SESSION['admin_user']);
    if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
        session_regenerate_id(true);
    }
}

/**
 * ==============================================================================
 * PHÂN HỆ TÀI KHOẢN CÂU LẠC BỘ (CLUB PORTAL AUTHENTICATION)
 * ==============================================================================
 */

/**
 * Kiểm tra xem tài khoản CLB đã đăng nhập chưa
 */
function is_club_logged_in(): bool {
    if (empty($_SESSION['club_user']) || !is_array($_SESSION['club_user'])) {
        return false;
    }

    $last_activity = $_SESSION['club_user']['last_activity'] ?? 0;
    if (time() - $last_activity > SESSION_TIMEOUT_SECONDS) {
        club_logout();
        return false;
    }

    $_SESSION['club_user']['last_activity'] = time();
    return true;
}

/**
 * Bắt buộc quyền CLB, chuyển hướng nếu chưa đăng nhập
 */
function require_club(): void {
    if (!is_club_logged_in()) {
        $_SESSION['club_redirect'] = $_SERVER['REQUEST_URI'] ?? url('club-portal/index.php');
        header("Location: " . url('club-portal/login.php'));
        exit;
    }
}

/**
 * Lấy ID của CLB hiện tại đang đăng nhập
 */
function current_club_id(): int {
    return (int)($_SESSION['club_user']['club_id'] ?? 0);
}

/**
 * Lấy toàn bộ thông tin phiên CLB hiện tại
 */
function current_club_user(): ?array {
    return $_SESSION['club_user'] ?? null;
}

/**
 * Xử lý đăng nhập tài khoản đại diện CLB
 */
function club_login(string $username, string $password): array {
    $db = get_db_connection();
    if (!$db) {
        return ['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu.'];
    }

    $ip = get_client_ip();
    $rate_check = check_login_rate_limit($db, $ip);
    if (!$rate_check['allowed']) {
        return ['success' => false, 'message' => $rate_check['message']];
    }

    $stmt = $db->prepare("
        SELECT ca.*, c.name as club_name, c.slug as club_slug, c.logo as club_logo
        FROM club_accounts ca
        JOIN clubs c ON ca.club_id = c.id
        WHERE ca.username = ? AND ca.is_active = 1
        LIMIT 1
    ");
    $stmt->execute([$username]);
    $account = $stmt->fetch();

    if ($account && password_verify($password, $account['password_hash'])) {
        if (!headers_sent()) {
            session_regenerate_id(true);
        }

        $_SESSION['club_user'] = [
            'id'             => $account['id'],
            'club_id'        => $account['club_id'],
            'username'       => $account['username'],
            'email'          => $account['email'],
            'contact_person' => $account['contact_person'],
            'club_name'      => $account['club_name'],
            'club_slug'      => $account['club_slug'],
            'club_logo'      => $account['club_logo'],
            'last_activity'  => time()
        ];

        clear_login_attempts($db, $ip);

        $update = $db->prepare("UPDATE club_accounts SET last_login = NOW() WHERE id = ?");
        $update->execute([$account['id']]);

        return ['success' => true];
    }

    record_login_attempt($db, $ip);
    $attempts_left = max(0, ($rate_check['remaining'] ?? MAX_LOGIN_ATTEMPTS) - 1);
    
    return [
        'success' => false,
        'message' => "Tên đăng nhập hoặc mật khẩu của CLB không chính xác. Bạn còn {$attempts_left} lần thử."
    ];
}

/**
 * Đăng xuất tài khoản CLB
 */
function club_logout(): void {
    unset($_SESSION['club_user']);
    if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
        session_regenerate_id(true);
    }
}

