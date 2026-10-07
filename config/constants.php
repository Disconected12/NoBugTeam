<?php
/**
 * ClubHub - Các hằng số cấu hình hệ thống
 */

if (!defined('CLUBHUB_INIT')) {
    define('CLUBHUB_INIT', true);
}

// Trạng thái tuyển thành viên của CLB
define('RECRUIT_CLOSED', 'closed');
define('RECRUIT_OPEN', 'open');
define('RECRUIT_UPCOMING', 'upcoming');

// Vị trí quảng cáo poster
define('PLACEMENT_TOP_BANNER', 'top_banner');
define('PLACEMENT_HOME_FEATURED', 'home_featured');
define('PLACEMENT_CATEGORY_PAGE', 'category_page');

// Trạng thái xét duyệt quảng cáo
define('AD_APPROVAL_PENDING', 'pending');
define('AD_APPROVAL_APPROVED', 'approved');
define('AD_APPROVAL_REJECTED', 'rejected');

// Trạng thái thanh toán quảng cáo
define('AD_PAYMENT_UNPAID', 'unpaid');
define('AD_PAYMENT_PAID', 'paid');
define('AD_PAYMENT_REFUNDED', 'refunded');

// Trạng thái phản ánh / báo cáo
define('REPORT_PENDING', 'pending');
define('REPORT_RESOLVED', 'resolved');

// Danh sách nền tảng mạng xã hội được hỗ trợ
define('SUPPORTED_SOCIALS', [
    'facebook'  => ['name' => 'Fanpage Facebook', 'icon' => 'facebook', 'color' => '#1877F2'],
    'tiktok'    => ['name' => 'TikTok', 'icon' => 'tiktok', 'color' => '#000000'],
    'instagram' => ['name' => 'Instagram', 'icon' => 'instagram', 'color' => '#E1306C'],
    'youtube'   => ['name' => 'YouTube', 'icon' => 'youtube', 'color' => '#FF0000'],
    'website'   => ['name' => 'Trang web', 'icon' => 'globe', 'color' => '#0F2854'],
    'messenger' => ['name' => 'Messenger', 'icon' => 'messenger', 'color' => '#0084FF'],
    'email'     => ['name' => 'Email liên hệ', 'icon' => 'envelope', 'color' => '#D93025']
]);
