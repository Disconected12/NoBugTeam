<?php
/**
 * ClubHub - Admin Header dùng chung cho trang quản trị
 */

if (!defined('CLUBHUB_INIT')) {
    define('CLUBHUB_INIT', true);
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Bắt buộc quyền quản trị viên trên mọi trang trong thư mục admin
require_admin();

$admin_user = $_SESSION['admin_user'] ?? ['username' => 'admin', 'full_name' => 'Quản trị viên'];
$current_admin_page = basename($_SERVER['PHP_SELF']);

$db = get_db_connection();
$pending_ads_count = 0;
$pending_reports_count = 0;

if ($db) {
    try {
        $p_ads = $db->query("SELECT COUNT(*) FROM ad_requests WHERE approval_status = 'pending'");
        $pending_ads_count = (int)$p_ads->fetchColumn();

        $p_rep = $db->query("SELECT COUNT(*) FROM feedback_reports WHERE status = 'pending'");
        $pending_reports_count = (int)$p_rep->fetchColumn();
    } catch (Exception $e) {
        // Bỏ qua lỗi đếm badge nếu bảng chưa sẵn sàng
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= !empty($page_title) ? e($page_title) . ' - ' : '' ?>Quản Trị Hệ Thống ClubHub</title>
  <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body>

  <!-- Sidebar Menu Quản Trị -->
  <aside class="admin-sidebar">
    <div class="admin-sidebar-brand">
      <div style="width: 32px; height: 32px; background: var(--admin-accent); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 800;">C</div>
      <span>Club<span>Hub</span> Admin</span>
    </div>

    <ul class="admin-nav">
      <li class="admin-nav-item <?= $current_admin_page === 'index.php' ? 'active' : '' ?>">
        <a href="<?= url('admin/index.php') ?>">
          <span>📊</span>
          <span>Tổng quan (Dashboard)</span>
        </a>
      </li>

      <li class="admin-nav-item <?= in_array($current_admin_page, ['clubs.php', 'club-form.php']) ? 'active' : '' ?>">
        <a href="<?= url('admin/clubs.php') ?>">
          <span>🏛️</span>
          <span>Quản lý Câu Lạc Bộ</span>
        </a>
      </li>

      <li class="admin-nav-item <?= $current_admin_page === 'categories.php' ? 'active' : '' ?>">
        <a href="<?= url('admin/categories.php') ?>">
          <span>📁</span>
          <span>Quản lý Lĩnh vực</span>
        </a>
      </li>

      <li class="admin-nav-item <?= $current_admin_page === 'events.php' ? 'active' : '' ?>">
        <a href="<?= url('admin/events.php') ?>">
          <span>🎪</span>
          <span>Quản lý Sự Kiện</span>
        </a>
      </li>

      <li class="admin-nav-item <?= $current_admin_page === 'ads.php' ? 'active' : '' ?>">
        <a href="<?= url('admin/ads.php') ?>">
          <span>📢</span>
          <span>Quản lý Quảng Cáo</span>
          <?php if ($pending_ads_count > 0): ?>
            <span class="admin-nav-badge" style="background: var(--admin-accent);"><?= $pending_ads_count ?></span>
          <?php endif; ?>
        </a>
      </li>

      <li class="admin-nav-item <?= $current_admin_page === 'quiz.php' ? 'active' : '' ?>">
        <a href="<?= url('admin/quiz.php') ?>">
          <span>🎯</span>
          <span>Quản lý Bộ Quiz</span>
        </a>
      </li>

      <li class="admin-nav-item <?= $current_admin_page === 'feedback.php' ? 'active' : '' ?>">
        <a href="<?= url('admin/feedback.php') ?>">
          <span>⚠️</span>
          <span>Phản ánh & Báo cáo</span>
          <?php if ($pending_reports_count > 0): ?>
            <span class="admin-nav-badge" style="background: var(--admin-danger);"><?= $pending_reports_count ?></span>
          <?php endif; ?>
        </a>
      </li>

      <li class="admin-nav-item <?= $current_admin_page === 'settings.php' ? 'active' : '' ?>">
        <a href="<?= url('admin/settings.php') ?>">
          <span>⚙️</span>
          <span>Cấu hình Website</span>
        </a>
      </li>
    </ul>

    <div class="admin-sidebar-footer">
      <a href="<?= url('/') ?>" target="_blank" rel="noopener noreferrer" style="color: #94A3B8; font-size: 0.85rem; display: flex; align-items: center; gap: 6px; text-decoration: none;">
        <span>🌐</span> Xem trang người dùng &nearr;
      </a>
    </div>
  </aside>

  <!-- Vùng làm việc chính (Main Content Area) -->
  <div class="admin-main">
    <header class="admin-header">
      <div style="display: flex; align-items: center; gap: 14px;">
        <button type="button" class="admin-mobile-toggle" style="background: none; border: none; font-size: 1.4rem; cursor: pointer; display: none;">☰</button>
        <h1 class="admin-page-title"><?= !empty($page_title) ? e($page_title) : 'Bảng Điều Khiển' ?></h1>
      </div>

      <div class="admin-header-actions">
        <div class="admin-user-pill">
          <span>👤 <?= e($admin_user['full_name']) ?></span>
        </div>
        <a href="<?= url('admin/logout.php') ?>" class="btn-admin btn-admin-outline" style="color: var(--admin-danger);">
          Đăng xuất
        </a>
      </div>
    </header>

    <!-- Flash message thông báo -->
    <div style="padding: 16px 32px 0 32px;">
      <?= flash_render() ?>
    </div>

    <div class="admin-content">
