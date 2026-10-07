<?php
/**
 * ClubHub - Bộ xử lý bảo vệ chống tấn công CSRF (Cross-Site Request Forgery)
 */

if (!defined('CLUBHUB_INIT')) {
    define('CLUBHUB_INIT', true);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Sinh mã CSRF token ngẫu nhiên và lưu trong Session
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Tạo thẻ input ẩn chứa CSRF token để nhúng vào form
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Kiểm tra tính hợp lệ của CSRF token
 */
function verify_csrf_token(?string $token = null): bool {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    }
    
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Chặn request POST nếu CSRF không hợp lệ
 */
function csrf_protect(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verify_csrf_token()) {
            http_response_code(403);
            die("Yêu cầu không hợp lệ hoặc phiên bảo mật đã hết hạn (Lỗi xác thực CSRF). Vui lòng tải lại trang và thử lại.");
        }
    }
}
