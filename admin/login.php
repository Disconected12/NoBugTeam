<?php
/**
 * ClubHub - Đăng Nhập Quản Trị Viên (Admin Login)
 * Bảo mật cao: Chống Brute-force theo IP, CSRF Protection, Đổi Session ID
 */

define('CLUBHUB_INIT', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

// Nếu đã đăng nhập thì chuyển thẳng vào Dashboard
if (is_admin_logged_in()) {
    redirect('admin/index.php');
}

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_protect();

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error_msg = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.';
    } else {
        $result = admin_login($username, $password);
        if ($result['success']) {
            $redirect_target = $_SESSION['admin_redirect'] ?? url('admin/index.php');
            unset($_SESSION['admin_redirect']);
            header("Location: " . $redirect_target);
            exit;
        } else {
            $error_msg = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đăng Nhập Quản Trị Viên - ClubHub</title>
  <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body style="background-color: var(--admin-primary-dark); display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px;">

  <div style="max-width: 440px; width: 100%; background: #FFFFFF; border-radius: 16px; padding: 40px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.4);">
    
    <div style="text-align: center; margin-bottom: 28px;">
      <div style="width: 52px; height: 52px; background: var(--admin-accent); border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font-weight: 800; color: #FFFFFF; font-size: 1.5rem; margin-bottom: 12px; box-shadow: 0 4px 12px rgba(255, 107, 0, 0.35);">
        C
      </div>
      <h1 style="font-size: 1.6rem; font-weight: 800; color: var(--admin-primary);">Đăng Nhập Quản Trị</h1>
      <p style="color: var(--admin-text-muted); font-size: 0.9rem; margin-top: 4px;">
        Cổng quản lý hệ thống dữ liệu ClubHub
      </p>
    </div>

    <?php if (!empty($error_msg)): ?>
      <div style="padding: 12px 16px; border-radius: 8px; background-color: #FEF2F2; color: #991B1B; border: 1px solid rgba(239, 68, 68, 0.4); font-size: 0.88rem; margin-bottom: 20px;">
        <?= e($error_msg) ?>
      </div>
    <?php endif; ?>

    <form action="<?= url('admin/login.php') ?>" method="POST">
      <?= csrf_field() ?>

      <div style="margin-bottom: 18px;">
        <label class="admin-label" for="username">Tên đăng nhập</label>
        <input type="text" name="username" id="username" class="admin-input" placeholder="Nhập username quản trị..." required autocomplete="username" autofocus>
      </div>

      <div style="margin-bottom: 24px;">
        <label class="admin-label" for="password">Mật khẩu</label>
        <input type="password" name="password" id="password" class="admin-input" placeholder="Nhập mật khẩu..." required autocomplete="current-password">
      </div>

      <button type="submit" class="btn-admin btn-admin-primary" style="width: 100%; padding: 12px; font-size: 1rem; border-radius: 8px;">
        Đăng nhập hệ thống &rarr;
      </button>
    </form>

    <div style="margin-top: 24px; text-align: center; border-top: 1px solid var(--admin-border); padding-top: 16px;">
      <a href="<?= url('/') ?>" style="color: var(--admin-text-muted); font-size: 0.85rem; text-decoration: none;">
        &larr; Quay lại trang chủ người dùng
      </a>
      <div style="margin-top: 8px;">
        <a href="<?= url('admin/setup.php') ?>" style="color: var(--admin-accent); font-size: 0.82rem; font-weight: 600; text-decoration: none;">
          Chưa có tài khoản? Khởi tạo quản trị viên đầu tiên
        </a>
      </div>
    </div>

  </div>

</body>
</html>
