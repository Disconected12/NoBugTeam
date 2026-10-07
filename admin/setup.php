<?php
/**
 * ClubHub - Khởi Tạo Tài Khoản Quản Trị Viên Đầu Tiên (First Admin Setup)
 * Cơ chế tự khóa an toàn: Chỉ cho phép tạo tài khoản khi bảng `admins` đang trống hoàn toàn!
 */

define('CLUBHUB_INIT', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

$db = get_db_connection();
if (!$db) {
    die("Không thể kết nối cơ sở dữ liệu để khởi tạo quản trị viên.");
}

// Kiểm tra xem đã có tài khoản admin nào chưa
$admin_count = 0;
try {
    $stmt = $db->query("SELECT COUNT(*) FROM admins");
    $admin_count = (int)$stmt->fetchColumn();
} catch (Exception $e) {
    die("Lỗi kiểm tra bảng quản trị viên: " . htmlspecialchars($e->getMessage()));
}

$is_locked = ($admin_count > 0);
$error_msg = '';
$success_msg = '';

if (!$is_locked && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_protect();

    $username = trim($_POST['username'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $password_confirm = trim($_POST['password_confirm'] ?? '');

    if (empty($username) || empty($full_name) || empty($email) || empty($password)) {
        $error_msg = 'Vui lòng điền đầy đủ tất cả các trường thông tin.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = 'Địa chỉ email không đúng định dạng.';
    } elseif (strlen($password) < 8) {
        $error_msg = 'Mật khẩu phải có độ dài tối thiểu 8 ký tự để đảm bảo an toàn.';
    } elseif ($password !== $password_confirm) {
        $error_msg = 'Xác nhận mật khẩu không khớp.';
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $insert = $db->prepare("
                INSERT INTO admins (username, password_hash, full_name, email, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $insert->execute([$username, $password_hash, $full_name, $email]);

            flash_set('success', 'Tạo tài khoản quản trị viên đầu tiên thành công! Vui lòng đăng nhập.');
            redirect('admin/login.php');
        } catch (Exception $e) {
            error_log("Lỗi tạo admin: " . $e->getMessage());
            $error_msg = 'Không thể tạo tài khoản. Tên đăng nhập hoặc email có thể đã tồn tại.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Khởi Tạo Quản Trị Viên Đầu Tiên - ClubHub</title>
  <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body style="background-color: var(--admin-primary-dark); display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px;">

  <div style="max-width: 500px; width: 100%; background: #FFFFFF; border-radius: 16px; padding: 40px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.4);">
    
    <div style="text-align: center; margin-bottom: 24px;">
      <div style="width: 52px; height: 52px; background: var(--admin-accent); border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font-weight: 800; color: #FFFFFF; font-size: 1.5rem; margin-bottom: 12px;">
        🛡️
      </div>
      <h1 style="font-size: 1.6rem; font-weight: 800; color: var(--admin-primary);">Khởi Tạo Quản Trị Viên</h1>
      <p style="color: var(--admin-text-muted); font-size: 0.9rem; margin-top: 4px;">
        Thiết lập tài khoản quản trị tối cao đầu tiên cho hệ thống
      </p>
    </div>

    <?php if ($is_locked): ?>
      <!-- Trang đã tự khóa vì đã có admin -->
      <div style="background-color: #FFFBEB; border: 1px solid var(--admin-warning); border-radius: 10px; padding: 20px; text-align: center;">
        <div style="font-size: 2rem; margin-bottom: 8px;">🔒</div>
        <h3 style="font-size: 1.15rem; color: #92400E; margin-bottom: 8px; font-weight: 700;">
          Trang cài đặt này đã bị khóa an toàn!
        </h3>
        <p style="font-size: 0.9rem; color: #92400E; line-height: 1.6; margin-bottom: 16px;">
          Hệ thống đã có tài khoản quản trị viên đang hoạt động. Để đảm bảo an ninh, không thể tạo thêm tài khoản qua trang công khai này.
        </p>
        <a href="<?= url('admin/login.php') ?>" class="btn-admin btn-admin-primary">
          Đến trang đăng nhập quản trị &rarr;
        </a>
      </div>

    <?php else: ?>

      <?php if (!empty($error_msg)): ?>
        <div style="padding: 12px 16px; border-radius: 8px; background-color: #FEF2F2; color: #991B1B; border: 1px solid rgba(239, 68, 68, 0.4); font-size: 0.88rem; margin-bottom: 20px;">
          <?= e($error_msg) ?>
        </div>
      <?php endif; ?>

      <form action="<?= url('admin/setup.php') ?>" method="POST">
        <?= csrf_field() ?>

        <div style="margin-bottom: 16px;">
          <label class="admin-label" for="full_name">Họ và tên quản trị viên <span style="color:red;">*</span></label>
          <input type="text" name="full_name" id="full_name" class="admin-input" placeholder="Ví dụ: Nguyễn Văn Quản Trị" value="<?= isset($_POST['full_name']) ? e($_POST['full_name']) : '' ?>" required>
        </div>

        <div style="margin-bottom: 16px;">
          <label class="admin-label" for="email">Email quản trị <span style="color:red;">*</span></label>
          <input type="email" name="email" id="email" class="admin-input" placeholder="admin@sinhvien.edu.vn" value="<?= isset($_POST['email']) ? e($_POST['email']) : '' ?>" required>
        </div>

        <div style="margin-bottom: 16px;">
          <label class="admin-label" for="username">Tên đăng nhập (Username) <span style="color:red;">*</span></label>
          <input type="text" name="username" id="username" class="admin-input" placeholder="admin" value="<?= isset($_POST['username']) ? e($_POST['username']) : '' ?>" required autocomplete="username">
        </div>

        <div style="margin-bottom: 16px;">
          <label class="admin-label" for="password">Mật khẩu (Tối thiểu 8 ký tự) <span style="color:red;">*</span></label>
          <input type="password" name="password" id="password" class="admin-input" placeholder="Nhập mật khẩu an toàn..." required autocomplete="new-password">
        </div>

        <div style="margin-bottom: 24px;">
          <label class="admin-label" for="password_confirm">Xác nhận mật khẩu <span style="color:red;">*</span></label>
          <input type="password" name="password_confirm" id="password_confirm" class="admin-input" placeholder="Nhập lại mật khẩu..." required autocomplete="new-password">
        </div>

        <button type="submit" class="btn-admin btn-admin-primary" style="width: 100%; padding: 12px; font-size: 1rem;">
          Tạo tài khoản & Đăng nhập ngay &rarr;
        </button>
      </form>

    <?php endif; ?>

  </div>

</body>
</html>
