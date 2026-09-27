<?php
/**
 * ClubHub - Cấu hình ứng dụng và kết nối môi trường
 */

if (!defined('CLUBHUB_INIT')) {
    define('CLUBHUB_INIT', true);
}

// Thiết lập múi giờ Việt Nam
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Cấu hình môi trường (true: phát triển/kiểm thử, false: vận hành thực tế)
define('APP_DEBUG', true);

if (APP_DEBUG) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/../error.log');
    error_reporting(0);
}

// Cấu hình Session an toàn
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', '1');
    }
    session_start();
}

// Tự động nhận diện đường dẫn BASE_URL (Hỗ trợ cả thư mục gốc và thư mục con như /cbl/, /clubhub/)
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    $project_root = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    $doc_root = !empty($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])) : '';

    $sub_dir = '';
    if ($doc_root && strpos($project_root, $doc_root) === 0) {
        $sub_dir = substr($project_root, strlen($doc_root));
    } else {
        // Dự phòng tính toán qua SCRIPT_NAME nếu DOCUMENT_ROOT có symlink hoặc alias
        $current_script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $script_dir = dirname($current_script);
        // Loại bỏ các thư mục con bên trong ứng dụng nếu script đang chạy trong admin hoặc api
        $sub_dir = preg_replace('#/(admin|api)(/.*)?$#i', '', $script_dir);
    }

    $sub_dir = '/' . trim(str_replace('\\', '/', $sub_dir), '/');
    if ($sub_dir === '/' || $sub_dir === '/.' || $sub_dir === '/..') {
        $sub_dir = '';
    }

    define('BASE_URL', rtrim($protocol . $host . $sub_dir, '/'));
    define('ROOT_PATH', $project_root);
}

// Cấu hình kết nối cơ sở dữ liệu MySQL mặc định
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'clubhub_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Nạp file cấu hình cục bộ nếu tồn tại (để tùy biến thông tin đăng nhập mà không ghi đè mã nguồn)
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

// Nạp các hằng số nghiệp vụ
require_once __DIR__ . '/constants.php';
