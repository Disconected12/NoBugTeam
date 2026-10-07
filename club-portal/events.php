<?php
/**
 * Demension - Quản Lý Sự Kiện Của Câu Lạc Bộ (Club Events Manager)
 */

define('CLUBHUB_INIT', true);
$page_title = 'Quản Lý Sự Kiện Của CLB';
require_once __DIR__ . '/includes/header.php';

$club_id = current_club_id();
$action = $_GET['action'] ?? 'list';
$event_id = (int)($_GET['id'] ?? 0);
$errors = [];

// Xử lý Xóa sự kiện
if ($action === 'delete' && $event_id > 0) {
    csrf_protect();
    try {
        $del = $db->prepare("DELETE FROM events WHERE id = ? AND club_id = ?");
        $del->execute([$event_id, $club_id]);
        flash_set('success', 'Đã xóa sự kiện thành công.');
    } catch (Exception $e) {
        flash_set('error', 'Lỗi khi xóa sự kiện: ' . $e->getMessage());
    }
    redirect('club-portal/events.php');
}

// Xử lý Thêm / Sửa sự kiện
$event_data = null;
if ($action === 'edit' && $event_id > 0) {
    $stmt = $db->prepare("SELECT * FROM events WHERE id = ? AND club_id = ? LIMIT 1");
    $stmt->execute([$event_id, $club_id]);
    $event_data = $stmt->fetch();
    if (!$event_data) {
        flash_set('error', 'Không tìm thấy sự kiện hoặc bạn không có quyền sửa sự kiện này.');
        redirect('club-portal/events.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['new', 'edit'])) {
    csrf_protect();

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $target_audience = trim($_POST['target_audience'] ?? '');
    $fee = (int)($_POST['fee'] ?? 0);
    $registration_deadline = !empty($_POST['registration_deadline']) ? $_POST['registration_deadline'] : null;
    $registration_link = trim($_POST['registration_link'] ?? '');
    $contact_info = trim($_POST['contact_info'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($title)) $errors[] = 'Tiêu đề sự kiện không được để trống.';
    if (empty($start_time)) $errors[] = 'Vui lòng chọn thời gian bắt đầu.';
    if (empty($end_time)) $errors[] = 'Vui lòng chọn thời gian kết thúc.';
    if (!empty($start_time) && !empty($end_time) && strtotime($end_time) <= strtotime($start_time)) {
        $errors[] = 'Thời gian kết thúc phải diễn ra sau thời gian bắt đầu.';
    }

    $poster_path = $event_data['poster_image'] ?? '';
    if (!empty($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
        $up = handle_image_upload($_FILES['poster'], 'events', 3);
        if ($up['success']) {
            $poster_path = $up['path'];
        } else {
            $errors[] = 'Lỗi tải poster: ' . $up['error'];
        }
    }

    if (empty($errors)) {
        $slug = slugify($title);
        // Kiểm tra trùng slug
        $chk = $db->prepare("SELECT id FROM events WHERE slug = ? AND id != ?");
        $chk->execute([$slug, $event_id]);
        if ($chk->fetch()) {
            $slug .= '-' . time();
        }

        try {
            if ($action === 'new') {
                $ins = $db->prepare("
                    INSERT INTO events (
                        club_id, title, slug, poster_image, description,
                        start_time, end_time, location, target_audience,
                        fee, registration_deadline, registration_link, contact_info,
                        is_active, created_at
                    ) VALUES (
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, NOW()
                    )
                ");
                $ins->execute([
                    $club_id, $title, $slug, $poster_path, $description,
                    $start_time, $end_time, $location, $target_audience,
                    $fee, $registration_deadline, $registration_link, $contact_info,
                    $is_active
                ]);
                flash_set('success', '✨ Đã thêm sự kiện mới thành công!');
            } else {
                $upd = $db->prepare("
                    UPDATE events SET
                        title = ?, slug = ?, poster_image = ?, description = ?,
                        start_time = ?, end_time = ?, location = ?, target_audience = ?,
                        fee = ?, registration_deadline = ?, registration_link = ?, contact_info = ?,
                        is_active = ?
                    WHERE id = ? AND club_id = ?
                ");
                $upd->execute([
                    $title, $slug, $poster_path, $description,
                    $start_time, $end_time, $location, $target_audience,
                    $fee, $registration_deadline, $registration_link, $contact_info,
                    $is_active, $event_id, $club_id
                ]);
                flash_set('success', '✨ Đã cập nhật sự kiện thành công!');
            }
            redirect('club-portal/events.php');
        } catch (Exception $e) {
            $errors[] = 'Lỗi CSDL: ' . $e->getMessage();
        }
    }
}
?>

<?php if (in_array($action, ['new', 'edit'])): ?>
  <!-- Form Thêm / Sửa sự kiện -->
  <div class="cp-card">
    <div class="cp-card-header">
      <div class="cp-card-title">
        <span>📅</span>
        <span><?= $action === 'new' ? 'Tạo Sự Kiện Mới Cho CLB' : 'Chỉnh Sửa Sự Kiện: ' . e($event_data['title']) ?></span>
      </div>
      <a href="<?= url('club-portal/events.php') ?>" class="btn btn-outline btn-sm" style="color: #94a3b8; border-color: rgba(255,255,255,0.2);">
        ← Quay lại danh sách
      </a>
    </div>

    <?php if (!empty($errors)): ?>
      <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4); color: #fca5a5; padding: 14px 18px; border-radius: 12px; margin-bottom: 1.5rem;">
        <strong>⚠️ Vui lòng kiểm tra lại:</strong>
        <ul style="margin-left: 20px; margin-top: 6px;">
          <?php foreach ($errors as $er): ?>
            <li><?= e($er) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data">
      <?= csrf_field() ?>

      <div class="form-group" style="margin-bottom: 1.25rem;">
        <label style="display: block; font-weight: 700; color: #fff; margin-bottom: 6px;">Tên Sự Kiện / Workshop / Minishow *</label>
        <input type="text" name="title" class="form-control" value="<?= e($event_data['title'] ?? '') ?>" required placeholder="VD: Workshop Công Nghệ 2026, Đêm Nhạc Giao Lưu...">
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 1.25rem;">
        <div>
          <label style="display: block; font-weight: 700; color: #fff; margin-bottom: 6px;">Thời gian bắt đầu *</label>
          <input type="datetime-local" name="start_time" class="form-control" value="<?= !empty($event_data['start_time']) ? date('Y-m-d\TH:i', strtotime($event_data['start_time'])) : '' ?>" required>
        </div>
        <div>
          <label style="display: block; font-weight: 700; color: #fff; margin-bottom: 6px;">Thời gian kết thúc *</label>
          <input type="datetime-local" name="end_time" class="form-control" value="<?= !empty($event_data['end_time']) ? date('Y-m-d\TH:i', strtotime($event_data['end_time'])) : '' ?>" required>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.25rem; margin-bottom: 1.25rem;">
        <div>
          <label style="display: block; font-weight: 700; color: #fff; margin-bottom: 6px;">Địa điểm tổ chức</label>
          <input type="text" name="location" class="form-control" value="<?= e($event_data['location'] ?? '') ?>" placeholder="VD: Hội trường A, Sảnh Tòa nhà Alpha hoặc Google Meet">
        </div>
        <div>
          <label style="display: block; font-weight: 700; color: #fff; margin-bottom: 6px;">Lệ phí tham gia (VNĐ)</label>
          <input type="number" name="fee" class="form-control" value="<?= e($event_data['fee'] ?? 0) ?>" min="0" placeholder="0 nếu miễn phí">
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 1.25rem;">
        <div>
          <label style="display: block; font-weight: 700; color: #fff; margin-bottom: 6px;">Hạn chót đăng ký</label>
          <input type="datetime-local" name="registration_deadline" class="form-control" value="<?= !empty($event_data['registration_deadline']) ? date('Y-m-d\TH:i', strtotime($event_data['registration_deadline'])) : '' ?>">
        </div>
        <div>
          <label style="display: block; font-weight: 700; color: #fff; margin-bottom: 6px;">Link biểu mẫu đăng ký vé</label>
          <input type="url" name="registration_link" class="form-control" value="<?= e($event_data['registration_link'] ?? '') ?>" placeholder="https://docs.google.com/forms/...">
        </div>
      </div>

      <div class="form-group" style="margin-bottom: 1.25rem;">
        <label style="display: block; font-weight: 700; color: #fff; margin-bottom: 6px;">Mô tả nội dung & lịch trình sự kiện *</label>
        <textarea name="description" class="form-control" rows="5" required placeholder="Giới thiệu mục đích, khách mời, quyền lợi khi tham gia..."><?= e($event_data['description'] ?? '') ?></textarea>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 1.25rem;">
        <div>
          <label style="display: block; font-weight: 700; color: #fff; margin-bottom: 6px;">Poster sự kiện</label>
          <?php if (!empty($event_data['poster_image'])): ?>
            <div style="margin-bottom: 8px;">
              <img src="<?= asset($event_data['poster_image']) ?>" alt="Poster" style="max-height: 120px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.2);">
            </div>
          <?php endif; ?>
          <input type="file" name="poster" accept="image/*" class="form-control">
        </div>
        <div>
          <label style="display: block; font-weight: 700; color: #fff; margin-bottom: 6px;">Thông tin liên hệ giải đáp</label>
          <input type="text" name="contact_info" class="form-control" value="<?= e($event_data['contact_info'] ?? '') ?>" placeholder="Số điện thoại hoặc Email phụ trách">
          
          <div style="margin-top: 1.25rem;">
            <label style="display: flex; align-items: center; gap: 10px; color: #fff; font-weight: 600; cursor: pointer;">
              <input type="checkbox" name="is_active" value="1" <?= (!isset($event_data['is_active']) || $event_data['is_active']) ? 'checked' : '' ?> style="width: 18px; height: 18px;">
              <span>Công khai hiển thị sự kiện ra trang chủ & trang sự kiện</span>
            </label>
          </div>
        </div>
      </div>

      <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #F6C453, #D97706); color: #06102B; font-weight: 800; padding: 12px 28px; border-radius: 12px; font-size: 1rem; border: none; cursor: pointer; box-shadow: 0 4px 20px rgba(246,196,83,0.4);">
        💾 <?= $action === 'new' ? 'Xuất Bản Sự Kiện Mới' : 'Lưu Cập Nhật Sự Kiện' ?>
      </button>
    </form>
  </div>

<?php else: ?>
  <!-- Danh sách sự kiện của CLB -->
  <?php
    $events_list = [];
    if ($db) {
        $stmt = $db->prepare("SELECT * FROM events WHERE club_id = ? ORDER BY start_time DESC");
        $stmt->execute([$club_id]);
        $events_list = $stmt->fetchAll();
    }
  ?>

  <div class="cp-card">
    <div class="cp-card-header">
      <div class="cp-card-title">
        <span>📅</span>
        <span>Danh Sách Sự Kiện Đã Đăng Của CLB</span>
      </div>
      <a href="<?= url('club-portal/events.php?action=new') ?>" class="btn btn-primary btn-sm" style="background: linear-gradient(135deg, #F6C453, #D97706); color: #06102B; font-weight: 700;">
        + Tạo sự kiện mới
      </a>
    </div>

    <?php if (empty($events_list)): ?>
      <div style="text-align: center; padding: 3rem 1rem; color: #94a3b8;">
        <div style="font-size: 3rem; margin-bottom: 0.5rem;">🌟</div>
        <p>CLB của bạn chưa đăng sự kiện nào trên hệ thống.</p>
        <a href="<?= url('club-portal/events.php?action=new') ?>" class="btn btn-primary btn-sm" style="margin-top: 10px; background: #2563EB;">
          Đăng sự kiện đầu tiên
        </a>
      </div>
    <?php else: ?>
      <div style="overflow-x: auto;">
        <table class="table" style="width: 100%; border-collapse: collapse; color: #cbd5e1;">
          <thead>
            <tr style="border-bottom: 1px solid rgba(255,255,255,0.1); text-align: left; font-size: 0.85rem; color: #94a3b8;">
              <th style="padding: 12px 10px;">POSTER</th>
              <th style="padding: 12px 10px;">TIÊU ĐỀ SỰ KIỆN</th>
              <th style="padding: 12px 10px;">THỜI GIAN DIỄN RA</th>
              <th style="padding: 12px 10px;">ĐỊA ĐIỂM</th>
              <th style="padding: 12px 10px;">TRẠNG THÁI</th>
              <th style="padding: 12px 10px; text-align: right;">THAO TÁC</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($events_list as $ev): ?>
              <?php
                $is_ended = strtotime($ev['end_time']) < time();
              ?>
              <tr style="border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.9rem;">
                <td style="padding: 10px;">
                  <img src="<?= !empty($ev['poster_image']) ? asset($ev['poster_image']) : asset('images/dimension-frame.png') ?>" alt="Poster" style="width: 50px; height: 50px; border-radius: 8px; object-fit: cover; border: 1px solid rgba(255,255,255,0.1);">
                </td>
                <td style="padding: 10px; font-weight: 700; color: #fff;">
                  <?= e($ev['title']) ?>
                </td>
                <td style="padding: 10px; color: #F6C453; font-size: 0.85rem;">
                  <?= date('d/m/Y H:i', strtotime($ev['start_time'])) ?><br>
                  <small style="color: #94a3b8;">đến <?= date('d/m/Y H:i', strtotime($ev['end_time'])) ?></small>
                </td>
                <td style="padding: 10px; font-size: 0.85rem;">
                  <?= e($ev['location']) ?>
                </td>
                <td style="padding: 10px;">
                  <?php if (!$ev['is_active']): ?>
                    <span style="color: #94a3b8; background: rgba(148,163,184,0.15); padding: 3px 8px; border-radius: 6px; font-size: 0.75rem;">ẨN</span>
                  <?php elseif ($is_ended): ?>
                    <span style="color: #cbd5e1; background: rgba(255,255,255,0.1); padding: 3px 8px; border-radius: 6px; font-size: 0.75rem;">ĐÃ KẾT THÚC</span>
                  <?php else: ?>
                    <span style="color: #10B981; background: rgba(16,185,129,0.15); padding: 3px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: bold;">SẮP DIỄN RA</span>
                  <?php endif; ?>
                </td>
                <td style="padding: 10px; text-align: right;">
                  <a href="<?= url('club-portal/events.php?action=edit&id=' . $ev['id']) ?>" style="color: #60A5FA; text-decoration: none; font-size: 0.85rem; margin-right: 10px;">✏️ Sửa</a>
                  <a href="<?= url('event-detail.php?slug=' . $ev['slug']) ?>" target="_blank" style="color: #94a3b8; text-decoration: none; font-size: 0.85rem; margin-right: 10px;">↗ Xem</a>
                  
                  <form method="POST" action="<?= url('club-portal/events.php?action=delete&id=' . $ev['id']) ?>" style="display: inline-block;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa sự kiện này không?');">
                    <?= csrf_field() ?>
                    <button type="submit" style="background: none; border: none; color: #f87171; cursor: pointer; font-size: 0.85rem; padding: 0;">🗑️ Xóa</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>