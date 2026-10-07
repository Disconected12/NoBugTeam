<?php
/**
 * ClubHub - Kết nối cơ sở dữ liệu PDO an toàn
 */

require_once __DIR__ . '/config.php';

function get_db_connection(): ?PDO {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = sprintf(
        "mysql:host=%s;port=%s;dbname=%s;charset=%s",
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, time_zone = '+07:00'"
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Ghi log lỗi máy chủ, không hiển thị trực tiếp thông tin kết nối ra giao diện người dùng
        error_log("Database connection error: " . $e->getMessage());
        if (defined('APP_DEBUG') && APP_DEBUG) {
            $msg = $e->getMessage();
            echo "<div style='font-family:sans-serif;padding:20px;margin:20px;border-left:4px solid #dc3545;background:#f8d7da;color:#721c24;'>";
            echo "<h3>Lỗi kết nối cơ sở dữ liệu</h3>";
            echo "<p>Không thể kết nối đến MySQL với cơ sở dữ liệu <strong>" . htmlspecialchars(DB_NAME) . "</strong>.</p>";
            echo "<p>Chi tiết: <code>" . htmlspecialchars($msg) . "</code></p>";
            echo "<p>Vui lòng đảm bảo MySQL đang chạy và đã nhập file <code>database.sql</code>.</p>";
            echo "</div>";
            exit;
        } else {
            echo "<div style='font-family:sans-serif;padding:30px;text-align:center;'>";
            echo "<h2>Hệ thống đang bảo trì</h2>";
            echo "<p>Không thể kết nối cơ sở dữ liệu vào lúc này. Vui lòng quay lại sau ít phút.</p>";
            echo "</div>";
            exit;
        }
    }
}
