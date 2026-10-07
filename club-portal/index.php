<?php
/**
 * Demension - Dashboard Quản Trị CLB (Club Portal Overview)
 */

define('CLUBHUB_INIT', true);
$page_title = 'Tổng quan CLB';
require_once __DIR__ . '/includes/header.php';

// Thống kê thông tin CLB
$club_id = current_club_id();
$events_count = 0;
$images_count = 0;
$upcoming_events = [];

if ($db && $club_id > 0) {
    $e_stmt = $db->prepare("SELECT COUNT(*) FROM events WHERE club_id = ?");
    $e_stmt->execute([$club_id]);
    $events_count = (int)$e_stmt->fetchColumn();

    $i_stmt = $db->prepare("SELECT COUNT(*) FROM club_images WHERE club_id = ?");
    $i_stmt->execute([$club_id]);
    $images_count = (int)$i_stmt->fetchColumn();

    $up_stmt = $db->prepare("SELECT * FROM events WHERE club_id = ? AND is_active = 1 AND end_time >= NOW() ORDER BY start_time ASC LIMIT 5");
    $up_stmt->execute([$club_id]);
    $upcoming_events = $up_stmt->fetchAll();
}

$recruitment_badge = [
    'open' => ['text' => 'ĐANG TUYỂN THÀNH VIÊN', 'color' => '#10B981', 'bg' => 'rgba(16, 185, 129, 0.15)'],
    'upcoming' => ['text' => 'SẮP MỞ ĐỢT TUYỂN', 'color' => '#F59E0B', 'bg' => 'rgba(245, 158, 11, 0.15)'],
    'closed' => ['text' => 'ĐÃ ĐÓNG ĐƠN', 'color' => '#94A3B8', 'bg' => 'rgba(148, 163, 184, 0.15)'],
][$club_data['recruitment_status'] ?? 'closed'];
?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
  <!-- Card 1: Trạng thái tuyển -->
  <div class="cp-card" style="border-left: 4px solid <?= $recruitment_badge['color'] ?>; margin-bottom: 0;">
    <div style="font-size: 0.85rem; color: #94a3b8; margin-bottom: 0.5rem; text-transform: uppercase;">Trạng Thái Đợt Tuyển</div>
    <div style="font-size: 1.25rem; font-weight: 800; color: <?= $recruitment_badge['color'] ?>; margin-bottom: 0.5rem;">
      <?= $recruitment_badge['text'] ?>
    </div>
    <div style="font-size: 0.85rem; color: #cbd5e1;">
      <?php if (!empty($club_data['recruitment_deadline'])): ?>
        Hạn nộp: <strong><?= date('d/m/Y - H:i', strtotime($club_data['recruitment_deadline'])) ?></strong>
      <?php else: ?>
        Chưa cài đặt hạn chót
      <?php endif; ?>
    </div>
    <div style="margin-top: 1rem;">
      <a href="<?= url('club-portal/recruitment.php') ?>" class="btn btn-outline btn-sm" style="color: #60A5FA; border-color: rgba(96, 165, 250, 0.4); font-size: 0.8rem;">
        ⚙️ Chỉnh sửa đợt tuyển
      </a>
    </div>
  </div>

  <!-- Card 2: Sự kiện CLB -->
  <div class="cp-card" style="border-left: 4px solid #F6C453; margin-bottom: 0;">
    <div style="font-size: 0.85rem; color: #94a3b8; margin-bottom: 0.5rem; text-transform: uppercase;">Sự Kiện Đã Đăng</div>
    <div style="font-size: 2rem; font-weight: 800; color: #F6C453; margin-bottom: 0.5rem;">
      <?= $events_count ?>
    </div>
    <div style="font-size: 0.85rem; color: #cbd5e1;">
      Bao gồm sự kiện sắp tới và đã diễn ra
    </div>
    <div style="margin-top: 1rem;">
      <a href="<?= url('club-portal/events.php') ?>" class="btn btn-outline btn-sm" style="color: #F6C453; border-color: rgba(246, 196, 83, 0.4); font-size: 0.8rem;">
        📅 Quản lý sự kiện
      </a>
    </div>
  </div>

  <!-- Card 3: Thư viện ảnh -->
  <div class="cp-card" style="border-left: 4px solid #A855F7; margin-bottom: 0;">
    <div style="font-size: 0.85rem; color: #94a3b8; margin-bottom: 0.5rem; text-transform: uppercase;">Thư Viện Ảnh Hoạt Động</div>
    <div style="font-size: 2rem; font-weight: 800; color: #C084FC; margin-bottom: 0.5rem;">
      <?= $images_count ?>
    </div>
    <div style="font-size: 0.85rem; color: #cbd5e1;">
      Hình ảnh lưu giữ khoảnh khắc CLB
    </div>
    <div style="margin-top: 1rem;">
      <a href="<?= url('club-portal/gallery.php') ?>" class="btn btn-outline btn-sm" style="color: #C084FC; border-color: rgba(192, 132, 252, 0.4); font-size: 0.8rem;">
        🖼️ Tải thêm ảnh
      </a>
    </div>
  </div>

  <!-- Card 4: Tùy biến Theme/Background -->
  <div class="cp-card" style="border-left: 4px solid #38BDF8; margin-bottom: 0;">
    <div style="font-size: 0.85rem; color: #94a3b8; margin-bottom: 0.5rem; text-transform: uppercase;">Theme Trang Cá Nhân</div>
    <div style="display: flex; align-items: center; gap: 10px; margin: 0.5rem 0;">
      <div style="width: 24px; height: 24px; border-radius: 6px; background: <?= e($club_data['custom_bg_color'] ?? '#06102B') ?>; border: 1px solid #fff;"></div>
      <div style="font-size: 1rem; font-weight: 700; color: #fff;">
        <?= e($club_data['custom_theme'] ?? 'dimension-classic') ?>
      </div>
    </div>
    <div style="font-size: 0.85rem; color: #cbd5e1;">
      Màu nền độc bản trên trang chi tiết
    </div>
    <div style="margin-top: 1rem;">
      <a href="<?= url('club-portal/profile.php') ?>" class="btn btn-outline btn-sm" style="color: #38BDF8; border-color: rgba(56, 189, 248, 0.4); font-size: 0.8rem;">
        🎨 Đổi màu & Giao diện
      </a>
    </div>
  </div>
</div>

<!-- Khối Xem nhanh thông tin trang cá nhân -->
<div class="cp-card">
  <div class="cp-card-header">
    <div class="cp-card-title">
      <span>🌌</span>
      <span>Trang Cá Nhân & Nhận Diện Câu Lạc Bộ</span>
    </div>
    <a href="<?= url('club-portal/profile.php') ?>" class="btn btn-primary btn-sm" style="background: linear-gradient(135deg, #F6C453, #D97706); color: #06102B; font-weight: 700;">
      ✏️ Chỉnh sửa toàn bộ thông tin
    </a>
  </div>

  <div style="display: flex; gap: 2rem; flex-wrap: wrap;">
    <div style="text-align: center;">
      <img src="<?= !empty($club_data['logo']) ? asset($club_data['logo']) : asset('images/dimension-logo.png') ?>" alt="Logo" style="width: 110px; height: 110px; border-radius: 20px; object-fit: cover; border: 3px solid #F6C453; box-shadow: 0 0 20px rgba(246, 196, 83, 0.3);">
      <div style="margin-top: 10px; font-size: 0.8rem; color: #94a3b8;">Logo chính thức</div>
    </div>
    <div style="flex: 1; min-width: 280px;">
      <h2 style="color: #fff; font-size: 1.4rem; font-weight: 800; margin-bottom: 0.25rem;">
        <?= e($club_data['name']) ?>
      </h2>
      <p style="color: #F6C453; font-size: 0.9rem; font-weight: 600; margin-bottom: 1rem;">
        ✦ Tên viết tắt: <?= e($club_data['short_name'] ?? 'Chưa đặt') ?> | Đường dẫn: <code><?= e($club_data['slug']) ?></code>
      </p>
      <p style="color: #cbd5e1; font-size: 0.95rem; line-height: 1.6; margin-bottom: 1rem;">
        <?= e($club_data['summary']) ?>
      </p>

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; background: rgba(6, 16, 43, 0.6); padding: 1rem; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.05);">
        <div>
          <span style="color: #94a3b8; font-size: 0.8rem;">⏰ Lịch sinh hoạt:</span><br>
          <strong style="color: #fff; font-size: 0.9rem;"><?= e($club_data['meeting_schedule'] ?? 'Chưa cập nhật') ?></strong>
        </div>
        <div>
          <span style="color: #94a3b8; font-size: 0.8rem;">📍 Địa điểm:</span><br>
          <strong style="color: #fff; font-size: 0.9rem;"><?= e($club_data['meeting_location'] ?? 'Chưa cập nhật') ?></strong>
        </div>
        <div>
          <span style="color: #94a3b8; font-size: 0.8rem;">💰 Lệ phí / Quỹ:</span><br>
          <strong style="color: #fff; font-size: 0.9rem;"><?= e($club_data['fee_info'] ?? 'Miễn phí') ?></strong>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Khối Sự kiện sắp tới của CLB -->
<div class="cp-card">
  <div class="cp-card-header">
    <div class="cp-card-title">
      <span>📅</span>
      <span>Sự Kiện Sắp Tới Của CLB</span>
    </div>
    <a href="<?= url('club-portal/events.php?action=new') ?>" class="btn btn-outline btn-sm" style="color: #60A5FA; border-color: rgba(96, 165, 250, 0.4);">
      + Đăng sự kiện mới
    </a>
  </div>

  <?php if (empty($upcoming_events)): ?>
    <div style="text-align: center; padding: 2.5rem 1rem; color: #94a3b8;">
      <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🌟</div>
      <p>CLB hiện chưa có sự kiện nào sắp tới.</p>
      <a href="<?= url('club-portal/events.php?action=new') ?>" class="btn btn-primary btn-sm" style="margin-top: 10px; background: #2563EB;">
        Tạo sự kiện mới ngay
      </a>
    </div>
  <?php else: ?>
    <div style="overflow-x: auto;">
      <table class="table" style="width: 100%; border-collapse: collapse; color: #cbd5e1;">
        <thead>
          <tr style="border-bottom: 1px solid rgba(255,255,255,0.1); text-align: left; font-size: 0.85rem; color: #94a3b8;">
            <th style="padding: 10px;">TÊN SỰ KIỆN</th>
            <th style="padding: 10px;">THỜI GIAN BẮT ĐẦU</th>
            <th style="padding: 10px;">ĐỊA ĐIỂM</th>
            <th style="padding: 10px; text-align: right;">THAO TÁC</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($upcoming_events as $ev): ?>
            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.9rem;">
              <td style="padding: 12px 10px; font-weight: 700; color: #fff;">
                <?= e($ev['title']) ?>
              </td>
              <td style="padding: 12px 10px; color: #F6C453;">
                <?= date('d/m/Y - H:i', strtotime($ev['start_time'])) ?>
              </td>
              <td style="padding: 12px 10px;">
                <?= e($ev['location']) ?>
              </td>
              <td style="padding: 12px 10px; text-align: right;">
                <a href="<?= url('club-portal/events.php?action=edit&id=' . $ev['id']) ?>" style="color: #60A5FA; text-decoration: none; font-size: 0.85rem; margin-right: 12px;">✏️ Sửa</a>
                <a href="<?= url('event-detail.php?slug=' . $ev['slug']) ?>" target="_blank" style="color: #94a3b8; text-decoration: none; font-size: 0.85rem;">↗ Xem</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>