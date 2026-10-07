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
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700;900&family=Cinzel:wght@600;700;800;900&family=Montserrat:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400;1,600&family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400;1,600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('css/style.css') ?>?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
  <?php 
    $site_bg_img = get_setting('site_background', '');
    if (!empty($site_bg_img)): 
  ?>
  <style>
    body {
      background-image: url('<?= upload_url($site_bg_img) ?>') !important;
      background-size: cover !important;
      background-position: center center !important;
      background-attachment: fixed !important;
      background-repeat: no-repeat !important;
    }
  </style>
  <?php endif; ?>
</head>
<body>

  <!-- Thanh điều hướng chính (Site Header) -->
  <header class="site-header">
    <div class="container header-container">
      <a href="<?= url('/') ?>" class="brand-logo" title="<?= e($site_name) ?>">
        <img src="<?= asset('images/dimension-logo.png') ?>" alt="Demension Logo" style="width: 38px; height: 38px; border-radius: 10px; object-fit: cover; filter: drop-shadow(0 0 8px rgba(246, 196, 83, 0.4)); flex-shrink: 0;">
        <span class="brand-text" style="font-weight: 800; letter-spacing: 2px; text-transform: uppercase; margin-right: 18px; background: linear-gradient(135deg, #FFFFFF 0%, #F6C453 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Demension</span>
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
            Khám phá CLB
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
            Đặt poster
          </a>
        </li>
      </ul>

      <!-- Thanh hành động & Tìm kiếm nhanh -->
      <div class="nav-actions">
        <form action="<?= url('clubs.php') ?>" method="GET" class="header-search-form" role="search">
          <input type="text" name="q" class="header-search-input" placeholder="Tìm kiếm CLB..." value="<?= isset($_GET['q']) ? e($_GET['q']) : '' ?>" aria-label="Tìm kiếm câu lạc bộ">
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
