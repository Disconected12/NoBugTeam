<?php
/**
 * ClubHub - Header dùng chung cho các trang người dùng
 */

if (!defined('CLUBHUB_INIT')) {
    define('CLUBHUB_INIT', true);
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

$site_name = get_setting('site_name', 'ClubHub');
$page_title_text = !empty($page_title) ? $page_title . ' - ' . $site_name : $site_name . ' - Cổng Khám Phá & Kết Nối Câu Lạc Bộ Sinh Viên';
$page_desc_text = !empty($page_desc) ? $page_desc : get_setting('intro_text', 'Cổng thông tin khám phá các câu lạc bộ đại học, tham gia trắc nghiệm định hướng, theo dõi sự kiện và các đợt tuyển thành viên mới.');
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($page_title_text) ?></title>
  <meta name="description" content="<?= e($page_desc_text) ?>">
  <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body>

  <!-- Thanh điều hướng chính (Site Header) -->
  <header class="site-header">
    <div class="container header-container">
      <a href="<?= url('/') ?>" class="brand-logo" title="<?= e($site_name) ?>">
        <div class="brand-icon">C</div>
        <span class="brand-text"><?= e($site_name) ?></span>
      </a>

      <!-- Menu Điều Hướng -->
      <ul class="nav-menu">
        <li>
          <a href="<?= url('index.php') ?>" class="nav-link <?= $current_page === 'index.php' ? 'active' : '' ?>">
            Trang chủ
          </a>
        </li>
        <li>
          <a href="<?= url('clubs.php') ?>" class="nav-link <?= in_array($current_page, ['clubs.php', 'club-detail.php']) ? 'active' : '' ?>">
            Danh sách CLB
          </a>
        </li>
        <li>
          <a href="<?= url('quiz.php') ?>" class="nav-link <?= in_array($current_page, ['quiz.php', 'quiz-result.php']) ? 'active' : '' ?>">
            Quiz chọn CLB
          </a>
        </li>
        <li>
          <a href="<?= url('events.php') ?>" class="nav-link <?= in_array($current_page, ['events.php', 'event-detail.php']) ? 'active' : '' ?>">
            Sự kiện
          </a>
        </li>
        <li>
          <a href="<?= url('ads-booking.php') ?>" class="nav-link <?= $current_page === 'ads-booking.php' ? 'active' : '' ?>">
            Đăng ký quảng bá
          </a>
        </li>
      </ul>

      <!-- Thanh hành động & Tìm kiếm nhanh -->
      <div class="nav-actions">
        <form action="<?= url('clubs.php') ?>" method="GET" class="header-search-form" role="search">
          <span class="header-search-icon">🔍</span>
          <input type="text" name="q" class="header-search-input" placeholder="Tìm tên CLB..." value="<?= isset($_GET['q']) ? e($_GET['q']) : '' ?>" aria-label="Tìm kiếm câu lạc bộ">
        </form>

        <!-- Nút menu trên điện thoại -->
        <button type="button" class="mobile-menu-toggle" aria-label="Mở danh mục menu" aria-expanded="false">
          ☰
        </button>
      </div>
    </div>
  </header>

  <!-- Vùng thông báo Flash Messages -->
  <?= flash_render() ?>
