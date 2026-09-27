<?php
/**
 * ClubHub - Quản Lý Sự Kiện (Admin Events)
 */

define('CLUBHUB_INIT', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$page_title = 'Quản lý Sự Kiện';
$db = get_db_connection();

$clubs = $db->query("SELECT id, name, short_name FROM clubs ORDER BY name ASC")->fetchAll();

// Xử lý Xóa sự kiện
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    csrf_protect();
    $event_id = (int)($_POST['event_id'] ?? 0);
    if ($event_id > 0) {
        $db->prepare("DELETE FROM events WHERE id = ?")->execute([$event_id]);
        flash_set('success', 'Đã xóa sự kiện thành công.');
    }
    redirect('admin/events.php');
}

// Xử lý Lưu sự kiện (Thêm mới hoặc Sửa)
$edit_id = (int)($_GET['edit_id'] ?? 0);
$is_creating = isset($_GET['action']) && $_GET['action'] === 'create';
$event = null;

if ($edit_id > 0) {
    $stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$edit_id]);
    $event = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    csrf_protect();

    $club_id = (int)($_POST['club_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    if (empty($slug)) $slug = slugify($title);
    $description = trim($_POST['description'] ?? '');
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $target_audience = trim($_POST['target_audience'] ?? '');
    $fee = floatval($_POST['fee'] ?? 0);
    $registration_deadline = !empty($_POST['registration_deadline']) ? $_POST['registration_deadline'] : null;
    $registration_link = safe_url(trim($_POST['registration_link'] ?? ''));
    $contact_info = trim($_POST['contact_info'] ?? '');
    $is_active = !empty($_POST['is_active']) ? 1 : 0;

    $errors = [];
    if (empty($title)) $errors[] = 'Tên sự kiện không được để trống.';
    if ($club_id <= 0) $errors[] = 'Vui lòng chọn Câu lạc bộ tổ chức.';
    if (empty($start_time) || empty($end_time)) $errors[] = 'Thời gian bắt đầu và kết thúc không được để trống.';
    if ($end_time < $start_time) $errors[] = 'Thời gian kết thúc không được diễn ra trước thời gian bắt đầu!';

    // Upload poster nếu có
    $poster_path = $event['poster_image'] ?? '';
    if (!empty($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
        $up = handle_image_upload($_FILES['poster'], 'events', 2);
        if ($up['success']) {
            $poster_path = $up['path'];
        } else {
            $errors[] = 'Lỗi upload Poster: ' . $up['error'];
        }
    }

    if (empty($errors)) {
        try {
            if ($edit_id > 0) {
                $stmt = $db->prepare("
                    UPDATE events SET
                        club_id = ?, title = ?, slug = ?, poster_image = ?,
                        description = ?, start_time = ?, end_time = ?, location = ?,
                        target_audience = ?, fee = ?, registration_deadline = ?,
                        registration_link = ?, contact_info = ?, is_active = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $club_id, $title, $slug, $poster_path,
                    $description, $start_time, $end_time, $location,
                    $target_audience, $fee, $registration_deadline,
                    $registration_link, $contact_info, $is_active, $edit_id
                ]);
                flash_set('success', 'Cập nhật sự kiện thành công.');
            } else {
                $stmt = $db->prepare("
                    INSERT INTO events (
                        club_id, title, slug, poster_image, description,
                        start_time, end_time, location, target_audience,
                        fee, registration_deadline, registration_link, contact_info,
                        is_active, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $club_id, $title, $slug, $poster_path, $description,
                    $start_time, $end_time, $location, $target_audience,
                    $fee, $registration_deadline, $registration_link, $contact_info,
                    $is_active
                ]);
                flash_set('success', 'Thêm sự kiện mới thành công.');
            }
            redirect('admin/events.php');
        } catch (Exception $e) {
            error_log("Lỗi lưu sự kiện: " . $e->getMessage());
            flash_set('error', 'Lỗi lưu sự kiện: ' . $e->getMessage());
        }
    } else {
        foreach ($errors as $err) flash_set('error', $err);
    }
}

// Lấy danh sách sự kiện
$events = $db->query("
    SELECT e.*, c.name as club_name
    FROM events e
    JOIN clubs c ON e.club_id = c.id
    ORDER BY e.start_time DESC
")->fetchAll();

include __DIR__ . '/includes/admin_header.php';
?>

<?php if ($edit_id > 0 || $is_creating): ?>
  <!-- Form Thêm / Sửa sự kiện -->
  <div class="admin-card">
    <div class="admin-card-header">
      <h2 class="admin-card-title"><?= $edit_id ? 'Chỉnh Sửa Sự Kiện' : 'Thêm Sự Kiện Mới' ?></h2>
      <a href="<?= url('admin/events.php') ?>" class="btn-admin btn-admin-outline">&larr; Hủy & Quay lại</a>
    </div>

    <form action="" method="POST" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save">

      <div class="form-grid">
        <div class="form-full">
          <label class="admin-label" for="title">Tên sự kiện <span style="color:red;">*</span></label>
          <input type="text" name="title" id="title" class="admin-input" value="<?= e($event['title'] ?? '') ?>" required placeholder="Ví dụ: Đêm Nhạc Acoustic Thanh Xuân...">
        </div>

        <div>
          <label class="admin-label" for="club_id">Câu lạc bộ tổ chức <span style="color:red;">*</span></label>
          <select name="club_id" id="club_id" class="admin-select" required>
            <option value="">-- Chọn CLB --</option>
            <?php foreach ($clubs as $cl): ?>
              <option value="<?= $cl['id'] ?>" <?= ($event['club_id'] ?? 0) == $cl['id'] ? 'selected' : '' ?>>
                <?= e($cl['name']) ?> <?= !empty($cl['short_name']) ? '(' . e($cl['short_name']) . ')' : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="admin-label" for="slug">URL Slug</label>
          <input type="text" name="slug" id="slug" class="admin-input" value="<?= e($event['slug'] ?? '') ?>" placeholder="dem-nhac-acoustic">
        </div>

        <div>
          <label class="admin-label" for="start_time">Thời gian bắt đầu <span style="color:red;">*</span></label>
          <input type="datetime-local" name="start_time" id="start_time" class="admin-input" value="<?= !empty($event['start_time']) ? date('Y-m-d\TH:i', strtotime($event['start_time'])) : '' ?>" required>
        </div>

        <div>
          <label class="admin-label" for="end_time">Thời gian kết thúc <span style="color:red;">*</span></label>
          <input type="datetime-local" name="end_time" id="end_time" class="admin-input" value="<?= !empty($event['end_time']) ? date('Y-m-d\TH:i', strtotime($event['end_time'])) : '' ?>" required>
        </div>

        <div>
          <label class="admin-label" for="location">Địa điểm tổ chức <span style="color:red;">*</span></label>
          <input type="text" name="location" id="location" class="admin-input" value="<?= e($event['location'] ?? '') ?>" required placeholder="Hội trường A1...">
        </div>

        <div>
          <label class="admin-label" for="fee">Phí tham gia (VNĐ)</label>
          <input type="number" name="fee" id="fee" class="admin-input" value="<?= e($event['fee'] ?? '0') ?>">
        </div>

        <div>
          <label class="admin-label" for="target_audience">Đối tượng tham gia</label>
          <input type="text" name="target_audience" id="target_audience" class="admin-input" value="<?= e($event['target_audience'] ?? '') ?>" placeholder="Sinh viên toàn trường...">
        </div>

        <div>
          <label class="admin-label" for="registration_deadline">Hạn chót đăng ký</label>
          <input type="datetime-local" name="registration_deadline" id="registration_deadline" class="admin-input" value="<?= !empty($event['registration_deadline']) ? date('Y-m-d\TH:i', strtotime($event['registration_deadline'])) : '' ?>">
        </div>

        <div>
          <label class="admin-label" for="registration_link">Link đăng ký tham gia</label>
          <input type="url" name="registration_link" id="registration_link" class="admin-input" value="<?= e($event['registration_link'] ?? '') ?>" placeholder="https://forms.gle/...">
        </div>

        <div>
          <label class="admin-label" for="contact_info">Đầu mối liên hệ</label>
          <input type="text" name="contact_info" id="contact_info" class="admin-input" value="<?= e($event['contact_info'] ?? '') ?>" placeholder="SĐT hoặc Email...">
        </div>

        <div class="form-full">
          <label class="admin-label" for="poster">Ảnh poster sự kiện (16:9)</label>
          <input type="file" name="poster" id="poster" class="admin-input" accept="image/png,image/jpeg,image/webp" data-preview="#posterPreview">
          <div class="img-preview-box" style="width: 180px; height: 100px;">
            <img id="posterPreview" src="<?= upload_url($event['poster_image'] ?? '', 'default-cover.png') ?>" alt="">
          </div>
        </div>

        <div class="form-full">
          <label class="admin-label" for="description">Mô tả nội dung chương trình</label>
          <textarea name="description" id="description" rows="5" class="admin-textarea" required><?= e($event['description'] ?? '') ?></textarea>
        </div>

        <div class="form-full">
          <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
            <input type="checkbox" name="is_active" value="1" <?= ($event['is_active'] ?? 1) ? 'checked' : '' ?>>
            <span>Bật hiển thị sự kiện này</span>
          </label>
        </div>
      </div>

      <div style="margin-top: 24px;">
        <button type="submit" class="btn-admin btn-admin-primary" style="padding: 10px 24px;">
          <?= $edit_id ? 'Lưu Thay Đổi' : 'Đăng Sự Kiện' ?>
        </button>
      </div>
    </form>
  </div>

<?php else: ?>
  <!-- Danh sách Sự kiện -->
  <div class="admin-card">
    <div class="admin-card-header">
      <h2 class="admin-card-title">Danh Sách Sự Kiện (<?= count($events) ?>)</h2>
      <a href="<?= url('admin/events.php?action=create') ?>" class="btn-admin btn-admin-primary">
        + Thêm Sự Kiện Mới
      </a>
    </div>

    <table class="admin-table">
      <thead>
        <tr>
          <th>Poster</th>
          <th>Tên sự kiện</th>
          <th>CLB tổ chức</th>
          <th>Thời gian</th>
          <th>Địa điểm</th>
          <th>Trạng thái</th>
          <th style="text-align: right;">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($events as $ev): ?>
          <?php 
            $now = new DateTime();
            $end = new DateTime($ev['end_time']);
            $is_past = $now > $end;
          ?>
          <tr>
            <td style="width: 70px;">
              <img src="<?= upload_url($ev['poster_image']) ?>" alt="" style="width: 60px; height: 34px; object-fit: cover; border-radius: 4px; border: 1px solid var(--admin-border);">
            </td>
            <td>
              <strong>
                <a href="<?= url('event-detail.php?slug=' . e($ev['slug'])) ?>" target="_blank" rel="noopener noreferrer" style="color: var(--admin-primary); text-decoration: none;">
                  <?= e($ev['title']) ?>
                </a>
              </strong>
            </td>
            <td><?= e($ev['club_name']) ?></td>
            <td><?= format_date_vn($ev['start_time'], true) ?></td>
            <td><?= e($ev['location']) ?></td>
            <td>
              <?php if (!$ev['is_active']): ?>
                <span class="badge badge-closed">Đang ẩn</span>
              <?php elseif ($is_past): ?>
                <span class="badge badge-closed">Đã kết thúc</span>
              <?php else: ?>
                <span class="badge badge-open">Sắp diễn ra</span>
              <?php endif; ?>
            </td>
            <td style="text-align: right;">
              <div class="action-buttons" style="justify-content: flex-end;">
                <a href="<?= url('admin/events.php?edit_id=' . $ev['id']) ?>" class="btn-admin btn-admin-outline" style="padding: 4px 10px; font-size: 0.8rem;">
                  Sửa
                </a>
                <form action="<?= url('admin/events.php') ?>" method="POST" style="display: inline;" class="btn-confirm-delete" data-confirm="Bạn có chắc muốn xóa sự kiện '<?= e($ev['title']) ?>'?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                  <button type="submit" class="btn-admin btn-admin-danger" style="padding: 4px 10px; font-size: 0.8rem;">
                    Xóa
                  </button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
