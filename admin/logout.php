<?php
/**
 * ClubHub - Đăng Xuất Quản Trị Viên
 */

define('CLUBHUB_INIT', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

admin_logout();
flash_set('info', 'Bạn đã đăng xuất khỏi phiên quản trị an toàn.');
redirect('admin/login.php');
