<?php
/**
 * Demension - Cổng Quản Trị Câu Lạc Bộ (Club Portal Header)
 */

if (!defined('CLUBHUB_INIT')) {
    define('CLUBHUB_INIT', true);
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_club();

$club_user = current_club_user();
$club_id = current_club_id();
$current_page = basename($_SERVER['PHP_SELF']);

$db = get_db_connection();
$club_data = null;
if ($db && $club_id > 0) {
    $stmt = $db->prepare("SELECT * FROM clubs WHERE id = ? LIMIT 1");
    $stmt->execute([$club_id]);
    $club_data = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= !empty($page_title) ? e($page_title) . ' - ' : '' ?>Cổng Quản Trị CLB | Demension</title>
  <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
  <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
  <style>
    /* Dimension Club Portal Custom Theme */
    body {
      background: #060e22;
      color: #e2e8f0;
      font-family: 'Plus Jakarta Sans', sans-serif;
    }
    .cp-layout {
      display: flex;
      min-height: 100vh;
    }
    .cp-sidebar {
      width: 270px;
      background: linear-gradient(180deg, #091536 0%, #060e22 100%);
      border-right: 1px solid rgba(246, 196, 83, 0.2);
      display: flex;
      flex-direction: column;
      position: sticky;
      top: 0;
      height: 100vh;
      overflow-y: auto;
      z-index: 100;
    }
    .cp-brand {
      padding: 1.5rem 1.25rem;
      display: flex;
      align-items: center;
      gap: 12px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
      background: rgba(11, 22, 59, 0.5);
    }
    .cp-brand-logo {
      width: 42px;
      height: 42px;
      border-radius: 12px;
      object-fit: cover;
      border: 2px solid #F6C453;
      box-shadow: 0 0 15px rgba(246, 196, 83, 0.3);
    }
    .cp-brand-info h3 {
      font-size: 0.95rem;
      font-weight: 700;
      color: #ffffff;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      max-width: 170px;
    }
    .cp-brand-info span {
      font-size: 0.75rem;
      color: #F6C453;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .cp-nav {
      list-style: none;
      padding: 1rem 0.75rem;
      flex: 1;
    }
    .cp-nav-item {
      margin-bottom: 6px;
    }
    .cp-nav-link {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 10px 14px;
      border-radius: 10px;
      color: #94a3b8;
      text-decoration: none;
      font-size: 0.9rem;
      font-weight: 600;
      transition: all 0.2s ease;
      border: 1px solid transparent;
    }
    .cp-nav-link:hover {
      color: #ffffff;
      background: rgba(255, 255, 255, 0.05);
      border-color: rgba(255, 255, 255, 0.1);
    }
    .cp-nav-link.active {
      color: #F6C453;
      background: rgba(246, 196, 83, 0.12);
      border-color: rgba(246, 196, 83, 0.3);
      box-shadow: 0 0 15px rgba(246, 196, 83, 0.15);
    }
    .cp-nav-icon {
      font-size: 1.15rem;
    }
    .cp-main {
      flex: 1;
      display: flex;
      flex-direction: column;
      min-width: 0;
    }
    .cp-topbar {
      height: 70px;
      background: rgba(9, 21, 54, 0.85);
      backdrop-filter: blur(12px);
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 2rem;
      position: sticky;
      top: 0;
      z-index: 90;
    }
    .cp-topbar-title {
      font-size: 1.15rem;
      font-weight: 700;
      color: #ffffff;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .cp-topbar-actions {
      display: flex;
      align-items: center;
      gap: 15px;
    }
    .cp-content {
      padding: 2rem;
      flex: 1;
    }
    .cp-card {
      background: rgba(13, 27, 65, 0.7);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 16px;
      padding: 1.75rem;
      backdrop-filter: blur(10px);
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.25);
      margin-bottom: 1.75rem;
    }
    .cp-card-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 1.5rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }
    .cp-card-title {
      font-size: 1.1rem;
      font-weight: 700;
      color: #ffffff;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .badge-star {
      background: linear-gradient(135deg, #F6C453, #e0a325);
      color: #06102B;
      padding: 4px 10px;
      border-radius: 999px;
      font-size: 0.75rem;
      font-weight: 800;
    }
    .theme-color-preview {
      width: 36px;
      height: 36px;
      border-radius: 8px;
      border: 2px solid rgba(255,255,255,0.2);
      display: inline-block;
      vertical-align: middle;
      margin-right: 10px;
    }
    
    /* Club Portal Form Controls & Text Readability */
    .form-control, .admin-input, .admin-select, .admin-textarea {
      background: #FFFFFF !important;
      color: #0F172A !important;
      border: 1px solid rgba(255, 255, 255, 0.2);
      font-weight: 500;
    }
    .form-control:focus, .admin-input:focus, .admin-select:focus, .admin-textarea:focus,
    input:focus, textarea:focus, select:focus {
      background: #FFFFFF !important;
      color: #0F172A !important;
      outline: none !important;
      border-color: #F6C453 !important;
      box-shadow: 0 0 0 3px rgba(246, 196, 83, 0.25) !important;
    }
    label, .form-label {
      color: #FFFFFF !important;
      font-weight: 600;
    }
  </style>
</head>
<body>

<div class="cp-layout">
  <!-- Sidebar -->
  <aside class="cp-sidebar">
    <div class="cp-brand">
      <img src="<?= !empty($club_data['logo']) ? asset($club_data['logo']) : asset('images/dimension-logo.png') ?>" alt="Logo" class="cp-brand-logo">
      <div class="cp-brand-info">
        <h3><?= e($club_data['name'] ?? $club_user['club_name']) ?></h3>
        <span>Club Space</span>
      </div>
    </div>

    <ul class="cp-nav">
      <li class="cp-nav-item">
        <a href="<?= url('club-portal/index.php') ?>" class="cp-nav-link <?= $current_page === 'index.php' ? 'active' : '' ?>">
          <span>Tổng quan CLB</span>
        </a>
      </li>
      <li class="cp-nav-item">
        <a href="<?= url('club-portal/profile.php') ?>" class="cp-nav-link <?= $current_page === 'profile.php' ? 'active' : '' ?>">
          <span>Trang cá nhân & Màu sắc</span>
        </a>
      </li>
      <li class="cp-nav-item">
        <a href="<?= url('club-portal/recruitment.php') ?>" class="cp-nav-link <?= $current_page === 'recruitment.php' ? 'active' : '' ?>">
          <span>Quản lý Đợt tuyển</span>
        </a>
      </li>
      <li class="cp-nav-item">
        <a href="<?= url('club-portal/events.php') ?>" class="cp-nav-link <?= in_array($current_page, ['events.php', 'event-form.php']) ? 'active' : '' ?>">
          <span>Sự kiện của CLB</span>
        </a>
      </li>
      <li class="cp-nav-item">
        <a href="<?= url('club-portal/gallery.php') ?>" class="cp-nav-link <?= $current_page === 'gallery.php' ? 'active' : '' ?>">
          <span>Thư viện ảnh hoạt động</span>
        </a>
      </li>
      
      <li class="cp-nav-item" style="margin-top: 1.5rem; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 1rem;">
        <a href="<?= url('club-detail.php?slug=' . ($club_data['slug'] ?? '')) ?>" target="_blank" class="cp-nav-link">
          <span>Xem trang công khai ↗</span>
        </a>
      </li>
      <li class="cp-nav-item">
        <a href="<?= url('club-portal/logout.php') ?>" class="cp-nav-link" style="color: #f87171;">
          <span>Đăng xuất</span>
        </a>
      </li>
    </ul>
  </aside>

  <!-- Main Content -->
  <div class="cp-main">
    <header class="cp-topbar">
      <div class="cp-topbar-title">
        <span><?= !empty($page_title) ? e($page_title) : 'Không gian Quản trị CLB' ?></span>
      </div>
      <div class="cp-topbar-actions">
        <a href="<?= url('club-detail.php?slug=' . ($club_data['slug'] ?? '')) ?>" target="_blank" class="btn btn-outline" style="border-color: rgba(246, 196, 83, 0.4); color: #F6C453; font-size: 0.85rem; padding: 6px 14px;">
          Xem giao diện thực tế
        </a>
        <div style="font-size: 0.85rem; color: #94a3b8;">
          Tài khoản: <strong style="color: #fff;"><?= e($club_user['username']) ?></strong>
        </div>
      </div>
    </header>

    <div class="cp-content">
      <?= flash_render() ?>