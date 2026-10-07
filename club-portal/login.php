<?php
/**
 * Demension - Đăng Nhập Cổng Quản Trị Câu Lạc Bộ (Club Portal Login)
 */

define('CLUBHUB_INIT', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

// Nếu đã đăng nhập thì chuyển hướng thẳng vào dashboard
if (is_club_logged_in()) {
    redirect('club-portal/index.php');
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_protect();

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error_message = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu của CLB.';
    } else {
        $result = club_login($username, $password);
        if ($result['success']) {
            $redirect_url = $_SESSION['club_redirect'] ?? 'club-portal/index.php';
            unset($_SESSION['club_redirect']);
            redirect($redirect_url);
        } else {
            $error_message = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đăng Nhập Cổng Quản Trị CLB | Demension Space</title>
  <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
  <style>
    body {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: radial-gradient(circle at center, #0e1e47 0%, #06102B 70%, #030816 100%);
      color: #fff;
      padding: 1.5rem;
      position: relative;
      overflow-x: hidden;
    }
    body::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0; bottom: 0;
      background-image: 
        radial-gradient(1px 1px at 25px 35px, #fff, rgba(0,0,0,0)),
        radial-gradient(2px 2px at 150px 180px, #F6C453, rgba(0,0,0,0)),
        radial-gradient(1.5px 1.5px at 280px 70px, #60A5FA, rgba(0,0,0,0)),
        radial-gradient(2px 2px at 420px 310px, #fff, rgba(0,0,0,0));
      background-repeat: repeat;
      background-size: 500px 500px;
      opacity: 0.6;
      pointer-events: none;
    }
    .club-login-card {
      width: 100%;
      max-width: 440px;
      background: rgba(13, 27, 65, 0.85);
      border: 1px solid rgba(246, 196, 83, 0.3);
      border-radius: 24px;
      padding: 2.5rem 2rem;
      backdrop-filter: blur(16px);
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5), 0 0 30px rgba(246, 196, 83, 0.15);
      position: relative;
      z-index: 10;
    }
    .club-login-logo {
      width: 84px;
      height: 84px;
      margin: 0 auto 1.25rem;
      display: block;
      filter: drop-shadow(0 0 15px rgba(246, 196, 83, 0.4));
    }
    .club-login-title {
      font-size: 1.45rem;
      font-weight: 800;
      text-align: center;
      color: #ffffff;
      margin-bottom: 0.35rem;
    }
    .club-login-subtitle {
      text-align: center;
      color: #94a3b8;
      font-size: 0.85rem;
      margin-bottom: 1.75rem;
    }
    .form-group {
      margin-bottom: 1.25rem;
    }
    .form-label {
      display: block;
      font-size: 0.85rem;
      font-weight: 600;
      color: #e2e8f0;
      margin-bottom: 0.5rem;
    }
    .form-control {
      width: 100%;
      padding: 12px 16px;
      background: rgba(6, 16, 43, 0.8);
      border: 1px solid rgba(255, 255, 255, 0.15);
      border-radius: 12px;
      color: #ffffff;
      font-size: 0.95rem;
      transition: all 0.2s;
    }
    .form-control:focus {
      outline: none;
      border-color: #F6C453;
      box-shadow: 0 0 15px rgba(246, 196, 83, 0.25);
      background: rgba(9, 23, 61, 0.95);
    }
    .btn-club-submit {
      width: 100%;
      padding: 13px;
      background: linear-gradient(135deg, #F6C453 0%, #D97706 100%);
      color: #06102B;
      font-weight: 800;
      font-size: 1rem;
      border: none;
      border-radius: 12px;
      cursor: pointer;
      box-shadow: 0 4px 20px rgba(246, 196, 83, 0.35);
      transition: all 0.25s;
      margin-top: 0.5rem;
    }
    .btn-club-submit:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 25px rgba(246, 196, 83, 0.5);
    }
    .portal-switch {
      margin-top: 1.5rem;
      text-align: center;
      font-size: 0.85rem;
      color: #94a3b8;
      border-top: 1px solid rgba(255, 255, 255, 0.08);
      padding-top: 1.25rem;
    }
    .portal-switch a {
      color: #60A5FA;
      text-decoration: none;
      font-weight: 600;
    }
    .portal-switch a:hover {
      text-decoration: underline;
    }
    .alert-error {
      background: rgba(239, 68, 68, 0.15);
      border: 1px solid rgba(239, 68, 68, 0.4);
      color: #fca5a5;
      padding: 12px 14px;
      border-radius: 10px;
      font-size: 0.85rem;
      margin-bottom: 1.25rem;
    }
  </style>
</head>
<body>

  <div class="club-login-card">
    <img src="<?= asset('images/dimension-logo.png') ?>" alt="Demension Logo" class="club-login-logo">
    <h1 class="club-login-title">Cổng Quản Trị CLB</h1>
    <p class="club-login-subtitle">Không gian thiết lập trang cá nhân & đợt tuyển dành riêng cho CLB</p>

    <?php if (!empty($error_message)): ?>
      <div class="alert-error">
        ⚠️ <?= e($error_message) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="">
      <?= csrf_field() ?>

      <div class="form-group">
        <label class="form-label">Tài khoản CLB</label>
        <input type="text" name="username" class="form-control" placeholder="Ví dụ: clb_devai, clb_debate..." value="<?= isset($_POST['username']) ? e($_POST['username']) : '' ?>" required autofocus>
      </div>

      <div class="form-group">
        <label class="form-label">Mật khẩu</label>
        <input type="password" name="password" class="form-control" placeholder="Nhập mật khẩu..." required>
      </div>

      <button type="submit" class="btn-club-submit">
        ✨ Đăng Nhập Không Gian CLB
      </button>
    </form>

    <div class="portal-switch">
      Bạn là Quản trị viên tối cao? <a href="<?= url('admin/login.php') ?>">Vào Super Admin</a><br>
      <a href="<?= url('/') ?>" style="color: #94a3b8; display: inline-block; margin-top: 6px;">← Quay lại trang chủ Demension</a>
    </div>
  </div>

</body>
</html>