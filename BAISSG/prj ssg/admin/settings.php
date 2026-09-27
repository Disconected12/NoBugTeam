<?php
/**
 * ClubHub - Cấu Hình Website & Thông Tin Chung (Admin Settings)
 */

define('CLUBHUB_INIT', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$page_title = 'Cấu hình Website';
$db = get_db_connection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_protect();

    $settings_to_update = [
        'site_name'       => trim($_POST['site_name'] ?? 'ClubHub'),
        'site_tagline'    => trim($_POST['site_tagline'] ?? ''),
        'contact_email'   => trim($_POST['contact_email'] ?? ''),
        'contact_phone'   => trim($_POST['contact_phone'] ?? ''),
        'contact_address' => trim($_POST['contact_address'] ?? ''),
        'intro_text'      => trim($_POST['intro_text'] ?? ''),
        'privacy_policy'  => trim($_POST['privacy_policy'] ?? '')
    ];

    try {
        $stmt = $db->prepare("
            INSERT INTO site_settings (setting_key, setting_value)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");
        foreach ($settings_to_update as $k => $v) {
            $stmt->execute([$k, $v]);
        }
        flash_set('success', 'Đã lưu cấu hình website thành công!');
    } catch (Exception $e) {
        error_log("Lỗi lưu cấu hình: " . $e->getMessage());
        flash_set('error', 'Lỗi lưu cấu hình: ' . $e->getMessage());
    }
    redirect('admin/settings.php');
}

// Lấy giá trị hiện tại
$current_settings = [];
if ($db) {
    try {
        $current_settings = $db->query("SELECT setting_key, setting_value FROM site_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (Exception $e) {
        // ...
    }
}

include __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-card" style="max-width: 860px;">
  <div class="admin-card-header">
    <h2 class="admin-card-title">Cấu Hình Thông Tin Toàn Trang</h2>
  </div>

  <form action="<?= url('admin/settings.php') ?>" method="POST">
    <?= csrf_field() ?>

    <h3 style="font-size: 1.05rem; color: var(--admin-primary); margin-bottom: 16px; border-bottom: 2px solid var(--admin-border); padding-bottom: 6px;">
      1. Tên Trang & Thương Hiệu
    </h3>

    <div class="form-grid" style="margin-bottom: 24px;">
      <div>
        <label class="admin-label" for="site_name">Tên Website / Cổng thông tin <span style="color:red;">*</span></label>
        <input type="text" name="site_name" id="site_name" class="admin-input" value="<?= e($current_settings['site_name'] ?? 'ClubHub') ?>" required>
      </div>

      <div>
        <label class="admin-label" for="site_tagline">Khẩu hiệu (Tagline)</label>
        <input type="text" name="site_tagline" id="site_tagline" class="admin-input" value="<?= e($current_settings['site_tagline'] ?? 'Cổng Khám Phá & Kết Nối Câu Lạc Bộ') ?>">
      </div>

      <div class="form-full">
        <label class="admin-label" for="intro_text">Đoạn giới thiệu ngắn ở chân trang</label>
        <textarea name="intro_text" id="intro_text" rows="3" class="admin-textarea"><?= e($current_settings['intro_text'] ?? '') ?></textarea>
      </div>
    </div>

    <h3 style="font-size: 1.05rem; color: var(--admin-primary); margin-bottom: 16px; border-bottom: 2px solid var(--admin-border); padding-bottom: 6px;">
      2. Thông Tin Liên Hệ Chính Thức
    </h3>

    <div class="form-grid" style="margin-bottom: 24px;">
      <div>
        <label class="admin-label" for="contact_email">Email liên hệ nhận phản ánh</label>
        <input type="email" name="contact_email" id="contact_email" class="admin-input" value="<?= e($current_settings['contact_email'] ?? '') ?>">
      </div>

      <div>
        <label class="admin-label" for="contact_phone">Số điện thoại hỗ trợ / Hotline</label>
        <input type="text" name="contact_phone" id="contact_phone" class="admin-input" value="<?= e($current_settings['contact_phone'] ?? '') ?>">
      </div>

      <div class="form-full">
        <label class="admin-label" for="contact_address">Địa chỉ văn phòng điều phối</label>
        <input type="text" name="contact_address" id="contact_address" class="admin-input" value="<?= e($current_settings['contact_address'] ?? '') ?>">
      </div>
    </div>

    <h3 style="font-size: 1.05rem; color: var(--admin-primary); margin-bottom: 16px; border-bottom: 2px solid var(--admin-border); padding-bottom: 6px;">
      3. Chính Sách Quyền Riêng Tư
    </h3>

    <div style="margin-bottom: 28px;">
      <label class="admin-label" for="privacy_policy">Nội dung chính sách quyền riêng tư cơ bản</label>
      <textarea name="privacy_policy" id="privacy_policy" rows="5" class="admin-textarea"><?= e($current_settings['privacy_policy'] ?? '') ?></textarea>
    </div>

    <button type="submit" class="btn-admin btn-admin-primary" style="padding: 10px 24px;">
      Lưu Cấu Hình
    </button>
  </form>
</div>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
