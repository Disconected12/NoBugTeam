<?php
/**
 * ClubHub - Bộ hàm tiện ích dùng chung
 */

if (!defined('CLUBHUB_INIT')) {
    define('CLUBHUB_INIT', true);
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/csrf.php';

/**
 * Escape an toàn chống XSS
 */
function e(?string $string): string {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

/**
 * Sinh đường dẫn nội bộ đầy đủ dựa trên BASE_URL
 */
function url(string $path = ''): string {
    $path = ltrim($path, '/');
    return BASE_URL . ($path !== '' ? '/' . $path : '');
}

/**
 * Sinh đường dẫn tài nguyên assets
 */
function asset(string $path = ''): string {
    $path = ltrim($path, '/');
    return url('assets/' . $path);
}

/**
 * Sinh đường dẫn file upload
 */
function upload_url(?string $path, string $default_placeholder = ''): string {
    if (empty($path)) {
        return $default_placeholder ? asset('images/' . $default_placeholder) : asset('images/default-club.png');
    }
    // Nếu là URL bên ngoài
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    return url(ltrim($path, '/'));
}

/**
 * Chuyển hướng an toàn
 */
function redirect(string $path): void {
    $target = (preg_match('#^https?://#i', $path)) ? $path : url($path);
    header("Location: " . $target);
    exit;
}

/**
 * Làm sạch và kiểm tra URL an toàn (chỉ chấp nhận http://, https://, mailto: hoặc đường dẫn tương đối nội bộ)
 * Chặn javascript:, data:, vbscript: để chống XSS và Open Redirect
 */
function safe_url(?string $url): string {
    if (empty($url)) {
        return '';
    }
    $url = trim($url);

    // Chặn triệt để các pseudo-protocol nguy hiểm
    if (preg_match('#^(javascript|data|vbscript):#i', $url)) {
        return '';
    }

    // Xử lý giao thức email mailto:
    if (str_starts_with(strtolower($url), 'mailto:')) {
        $email_part = substr($url, 7);
        return filter_var($email_part, FILTER_VALIDATE_EMAIL) ? 'mailto:' . $email_part : '';
    }

    // Nếu là đường dẫn tương đối nội bộ (ví dụ: "/cbl/club-detail.php" hoặc "club-detail.php?slug=...")
    if (str_starts_with($url, '/') || !preg_match('#^[a-z0-9+.-]+:#i', $url)) {
        if (str_starts_with($url, '//')) {
            return ''; // Chặn protocol-relative URL
        }
        return filter_var($url, FILTER_SANITIZE_URL) ?: '';
    }

    // Kiểm tra scheme hợp lệ
    $parsed = parse_url($url);
    if (!$parsed || !isset($parsed['scheme']) || !in_array(strtolower($parsed['scheme']), ['http', 'https'])) {
        return '';
    }

    $host = strtolower($parsed['host'] ?? '');

    // Môi trường phát triển cục bộ (localhost, 127.0.0.1 hoặc cờ APP_DEBUG)
    $current_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $is_local_env = (defined('APP_DEBUG') && APP_DEBUG) || 
                    in_array($current_host, ['localhost', '127.0.0.1']) || 
                    str_starts_with($current_host, 'localhost:') || 
                    str_starts_with($current_host, '127.0.0.1:');

    if (!$is_local_env) {
        // Trên môi trường production, chặn các IP nội bộ / private IP range để bảo mật
        if (in_array($host, ['localhost', '127.0.0.1', '::1', '0.0.0.0']) || 
            preg_match('#^10\.|^192\.168\.|^172\.(1[6-9]|2[0-9]|3[0-1])\.|^169\.254\.#', $host)) {
            return '';
        }
    }

    return filter_var($url, FILTER_SANITIZE_URL) ?: '';
}

/**
 * Kiểm tra xem một URL có phải là liên kết ra bên ngoài trang web không
 */
function is_external_url(?string $url): bool {
    if (empty($url) || str_starts_with($url, '#') || str_starts_with($url, 'mailto:')) {
        return false;
    }
    if (str_starts_with($url, '/') || !preg_match('#^[a-z0-9+.-]+:#i', $url)) {
        return false;
    }
    $parsed = parse_url($url);
    if (!isset($parsed['host'])) {
        return false;
    }
    $parsed_host = strtolower($parsed['host']);
    $current_host = strtolower($_SERVER['HTTP_HOST'] ?? 'localhost');
    $curr_host_no_port = explode(':', $current_host)[0];

    // Trùng host hiện tại hoặc cùng là localhost/127.0.0.1
    if ($parsed_host === $curr_host_no_port) {
        return false;
    }
    if (in_array($parsed_host, ['localhost', '127.0.0.1']) && in_array($curr_host_no_port, ['localhost', '127.0.0.1'])) {
        return false;
    }

    return true;
}

/**
 * Thuộc tính target và rel an toàn cho liên kết
 */
function link_target_attr(?string $url): string {
    return is_external_url($url) ? 'target="_blank" rel="noopener noreferrer"' : '';
}

/**
 * Chuẩn hóa liên kết đích (hỗ trợ chuyển đổi link demo localhost hoặc relative path sang BASE_URL hiện tại)
 */
function normalize_destination_url(?string $url): string {
    if (empty($url)) return '';
    $clean = safe_url($url);
    if (empty($clean)) return '';

    // Nếu là đường dẫn tương đối nội bộ
    if (!preg_match('#^[a-z0-9+.-]+:#i', $clean)) {
        return url($clean);
    }

    // Nếu link trỏ tới trang nội bộ của web (club-detail, events, clubs) dưới dạng localhost
    if (preg_match('#https?://(?:localhost|127\.0\.0\.1)(?::\d+)?(?:/cbl)?/(club-detail\.php\S*|events\.php\S*|clubs\.php\S*)#i', $clean, $m)) {
        return url($m[1]);
    }
    return $clean;
}

/**
 * Chuyển đổi chuỗi tiếng Việt có dấu thành URL Slug chuẩn SEO
 */
function slugify(string $text): string {
    $text = mb_strtolower($text, 'UTF-8');
    
    // Bảng thay thế ký tự có dấu
    $char_map = [
        'à'=>'a','á'=>'a','ả'=>'a','ã'=>'a','ạ'=>'a','ă'=>'a','ằ'=>'a','ắ'=>'a','ẳ'=>'a','ẵ'=>'a','ặ'=>'a',
        'â'=>'a','ầ'=>'a','ấ'=>'a','ẩ'=>'a','ẫ'=>'a','ậ'=>'a','è'=>'e','é'=>'e','ẻ'=>'e','ẽ'=>'e','ẹ'=>'e',
        'ê'=>'e','ề'=>'e','ế'=>'e','ể'=>'e','ễ'=>'e','ệ'=>'e','ì'=>'i','í'=>'i','ỉ'=>'i','ĩ'=>'i','ị'=>'i',
        'ò'=>'o','ó'=>'o','ỏ'=>'o','õ'=>'o','ọ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ổ'=>'o','ỗ'=>'o','ộ'=>'o',
        'ơ'=>'o','ờ'=>'o','ớ'=>'o','ở'=>'o','ỡ'=>'o','ợ'=>'o','ù'=>'u','ú'=>'u','ủ'=>'u','ũ'=>'u','ụ'=>'u',
        'ư'=>'u','ừ'=>'u','ứ'=>'u','ử'=>'u','ữ'=>'u','ự'=>'u','ỳ'=>'y','ý'=>'y','ỷ'=>'y','ỹ'=>'y','ỵ'=>'y',
        'đ'=>'d'
    ];
    $text = strtr($text, $char_map);
    // Thay thế ký tự không phải chữ hoặc số bằng dấu gạch ngang
    $text = preg_replace('/[^a-z0-9\-]/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    return trim($text, '-');
}

/**
 * Định dạng ngày giờ chuẩn tiếng Việt
 */
function format_date_vn(?string $date_string, bool $include_time = false): string {
    if (empty($date_string) || $date_string === '0000-00-00' || $date_string === '0000-00-00 00:00:00') {
        return 'Chưa xác định';
    }
    try {
        $date = new DateTime($date_string);
        return $include_time ? $date->format('H:i, d/m/Y') : $date->format('d/m/Y');
    } catch (Exception $e) {
        return $date_string;
    }
}

/**
 * Định dạng tiền tệ VNĐ
 */
function format_money_vn($amount): string {
    if ($amount === null || $amount === '' || (float)$amount == 0) {
        return 'Miễn phí';
    }
    return number_format((float)$amount, 0, ',', '.') . ' đ';
}

/**
 * Xử lý tải lên file ảnh an toàn
 * Kiểm tra MIME thực tế, kích thước, đuôi file, cấm hoàn toàn file thực thi và SVG
 */
function handle_image_upload(array $file, string $subfolder, int $max_size_mb = 2): array {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'error' => 'Dữ liệu file tải lên không hợp lệ.'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE   => 'Kích thước file vượt quá cấu hình php.ini.',
            UPLOAD_ERR_FORM_SIZE  => 'Kích thước file vượt quá giới hạn biểu mẫu.',
            UPLOAD_ERR_PARTIAL    => 'File chỉ được tải lên một phần.',
            UPLOAD_ERR_NO_FILE    => 'Không có file nào được chọn.',
            UPLOAD_ERR_NO_TMP_DIR => 'Thiếu thư mục tạm thời trên máy chủ.',
            UPLOAD_ERR_CANT_WRITE => 'Không thể ghi file vào ổ đĩa.',
            UPLOAD_ERR_EXTENSION  => 'Quá trình tải file bị dừng bởi extension PHP.'
        ];
        return ['success' => false, 'error' => $error_messages[$file['error']] ?? 'Lỗi tải file không xác định.'];
    }

    // Kiểm tra dung lượng (mặc định tối đa 2MB)
    if ($file['size'] > ($max_size_mb * 1024 * 1024)) {
        return ['success' => false, 'error' => "Dung lượng file vượt quá giới hạn {$max_size_mb}MB."];
    }

    // Kiểm tra MIME type thực tế bằng Fileinfo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file['tmp_name']);

    $allowed_mimes = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp'
    ];

    $ext = array_search($mime_type, $allowed_mimes, true);
    if ($ext === false) {
        return ['success' => false, 'error' => 'Định dạng ảnh không được hỗ trợ. Chỉ chấp nhận JPG, PNG hoặc WEBP.'];
    }

    // Kiểm tra tính toàn vẹn của ảnh qua getimagesize
    $img_info = @getimagesize($file['tmp_name']);
    if (!$img_info) {
        return ['success' => false, 'error' => 'Nội dung file không phải là ảnh hợp lệ.'];
    }

    // Tạo thư mục đích nếu chưa có
    $target_dir = ROOT_PATH . '/uploads/' . trim($subfolder, '/');
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    // Đặt tên ngẫu nhiên an toàn để tránh ghi đè và tấn công path traversal
    $filename = bin2hex(random_bytes(16)) . '_' . time() . '.' . $ext;
    $destination = $target_dir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'error' => 'Không thể lưu file vào thư mục đích. Vui lòng kiểm tra quyền thư mục.'];
    }

    $relative_path = 'uploads/' . trim($subfolder, '/') . '/' . $filename;
    return ['success' => true, 'path' => $relative_path, 'filename' => $filename];
}

/**
 * Lấy cấu hình website từ cơ sở dữ liệu
 */
function get_setting(string $key, string $default = ''): string {
    static $settings_cache = null;

    if ($settings_cache === null) {
        $db = get_db_connection();
        if ($db) {
            try {
                $stmt = $db->query("SELECT setting_key, setting_value FROM site_settings");
                $settings_cache = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            } catch (Exception $e) {
                $settings_cache = [];
            }
        } else {
            $settings_cache = [];
        }
    }

    return $settings_cache[$key] ?? $default;
}

/**
 * Thiết lập thông báo tạm thời (Flash Message)
 */
function flash_set(string $type, string $message): void {
    $_SESSION['flash_messages'][$type][] = $message;
}

/**
 * Hiển thị và xóa các thông báo Flash Message
 */
function flash_render(): string {
    if (empty($_SESSION['flash_messages'])) {
        return '';
    }

    $html = '<div class="flash-messages-container">';
    foreach ($_SESSION['flash_messages'] as $type => $messages) {
        $alert_class = match ($type) {
            'success' => 'alert-success',
            'error'   => 'alert-error',
            'warning' => 'alert-warning',
            default   => 'alert-info'
        };
        foreach ($messages as $msg) {
            $html .= '<div class="alert ' . $alert_class . ' alert-dismissible" role="alert">';
            $html .= '<span class="alert-icon"></span>';
            $html .= '<span class="alert-text">' . e($msg) . '</span>';
            $html .= '<button type="button" class="alert-close" aria-label="Đóng" onclick="this.parentElement.remove();">&times;</button>';
            $html .= '</div>';
        }
    }
    $html .= '</div>';

    unset($_SESSION['flash_messages']);
    return $html;
}

/**
 * Kiểm tra trạng thái tuyển thành viên thực tế của CLB (Tự ngắt khi qua hạn)
 */
function check_actual_recruitment_status(string $status, ?string $deadline): array {
    $now = new DateTime();

    if ($status === RECRUIT_OPEN) {
        if (!empty($deadline)) {
            $deadline_dt = new DateTime($deadline);
            if ($now > $deadline_dt) {
                return [
                    'status' => RECRUIT_CLOSED,
                    'badge'  => '<span class="badge badge-closed"><i class="icon-clock"></i> Đã hết hạn tuyển</span>',
                    'text'   => 'Đã hết hạn tuyển',
                    'is_open'=> false
                ];
            }
        }
        return [
            'status' => RECRUIT_OPEN,
            'badge'  => '<span class="badge badge-open"><i class="icon-check"></i> Đang mở đơn tuyển</span>',
            'text'   => 'Đang tuyển thành viên',
            'is_open'=> true
        ];
    }

    if ($status === RECRUIT_UPCOMING) {
        return [
            'status' => RECRUIT_UPCOMING,
            'badge'  => '<span class="badge badge-upcoming"><i class="icon-calendar"></i> Sắp mở tuyển</span>',
            'text'   => 'Sắp mở đơn tuyển',
            'is_open'=> false
        ];
    }

    return [
        'status' => RECRUIT_CLOSED,
        'badge'  => '<span class="badge badge-closed"><i class="icon-ban"></i> Chưa có đợt tuyển</span>',
        'text'   => 'Đã đóng tuyển',
        'is_open'=> false
    ];
}
