<?php
/**
 * ClubHub - Thêm Mới / Chỉnh Sửa Câu Lạc Bộ (Admin Club Form)
 */

define('CLUBHUB_INIT', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$db = get_db_connection();
$id = (int)($_GET['id'] ?? 0);
$is_edit = ($id > 0);

$page_title = $is_edit ? 'Chỉnh sửa Câu Lạc Bộ' : 'Thêm Câu Lạc Bộ Mới';

$categories = $db->query("SELECT id, name FROM categories ORDER BY display_order ASC")->fetchAll();
$attribute_keys = $db->query("SELECT attribute_key, name, group_name FROM quiz_attribute_keys ORDER BY group_name, attribute_key")->fetchAll();

$club = [
    'name' => '',
    'short_name' => '',
    'slug' => '',
    'category_id' => $categories[0]['id'] ?? 1,
    'logo' => '',
    'cover_image' => '',
    'summary' => '',
    'description' => '',
    'objectives' => '',
    'target_audience' => '',
    'activities_achievements' => '',
    'meeting_schedule' => '',
    'meeting_location' => '',
    'meeting_frequency' => '',
    'schedule_confirmed' => 1,
    'requirements' => '',
    'fee_info' => '',
    'recruitment_status' => 'closed',
    'recruitment_deadline' => '',
    'recruitment_process' => '',
    'application_link' => '',
    'club_tier' => 'standard',
    'is_featured' => 0,
    'is_active' => 1
];

$club_socials = [];
$club_attributes = [];
$club_gallery_images = [];

if ($is_edit) {
    $stmt = $db->prepare("SELECT * FROM clubs WHERE id = ?");
    $stmt->execute([$id]);
    $existing = $stmt->fetch();
    if (!$existing) {
        flash_set('error', 'Không tìm thấy câu lạc bộ.');
        redirect('admin/clubs.php');
    }
    $club = array_merge($club, $existing);

    // Lấy mạng xã hội
    $s_stmt = $db->prepare("SELECT platform, url FROM club_socials WHERE club_id = ?");
    $s_stmt->execute([$id]);
    $club_socials = $s_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Lấy điểm thuộc tính quiz
    $a_stmt = $db->prepare("SELECT attribute_key, score FROM club_quiz_attributes WHERE club_id = ?");
    $a_stmt->execute([$id]);
    $club_attributes = $a_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Lấy thư viện ảnh hoạt động
    $g_stmt = $db->prepare("SELECT * FROM club_images WHERE club_id = ? ORDER BY display_order ASC, id ASC");
    $g_stmt->execute([$id]);
    $club_gallery_images = $g_stmt->fetchAll();
}

// Xử lý nộp form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_protect();

    $name = trim($_POST['name'] ?? '');
    $short_name = trim($_POST['short_name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    if (empty($slug)) {
        $slug = slugify($name);
    } else {
        $slug = slugify($slug);
    }
    $category_id = (int)($_POST['category_id'] ?? 1);
    $summary = trim($_POST['summary'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $objectives = trim($_POST['objectives'] ?? '');
    $target_audience = trim($_POST['target_audience'] ?? '');
    $activities_achievements = trim($_POST['activities_achievements'] ?? '');
    $meeting_schedule = trim($_POST['meeting_schedule'] ?? '');
    $meeting_location = trim($_POST['meeting_location'] ?? '');
    $meeting_frequency = trim($_POST['meeting_frequency'] ?? '');
    $schedule_confirmed = !empty($_POST['schedule_confirmed']) ? 1 : 0;
    $requirements = trim($_POST['requirements'] ?? '');
    $fee_info = trim($_POST['fee_info'] ?? '');
    $recruitment_status = $_POST['recruitment_status'] ?? 'closed';
    $recruitment_deadline = !empty($_POST['recruitment_deadline']) ? $_POST['recruitment_deadline'] : null;
    $recruitment_process = trim($_POST['recruitment_process'] ?? '');
    $application_link = safe_url(trim($_POST['application_link'] ?? ''));
    $club_tier = trim($_POST['club_tier'] ?? 'standard');
    $valid_tiers = array_keys(get_club_tiers());
    if (!in_array($club_tier, $valid_tiers)) {
        $club_tier = 'standard';
    }
    $is_featured = !empty($_POST['is_featured']) ? 1 : 0;
    $is_active = !empty($_POST['is_active']) ? 1 : 0;

    $errors = [];
    if (empty($name)) $errors[] = 'Tên câu lạc bộ không được để trống.';
    if (empty($summary)) $errors[] = 'Giới thiệu ngắn không được để trống.';
    if (empty($description)) $errors[] = 'Mô tả chi tiết không được để trống.';

    // Xử lý upload logo mới nếu có
    $logo_path = $club['logo'];
    if (!empty($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $up = handle_image_upload($_FILES['logo'], 'clubs', 2);
        if ($up['success']) {
            $logo_path = $up['path'];
        } else {
            $errors[] = 'Lỗi upload Logo: ' . $up['error'];
        }
    }

    // Xử lý upload ảnh bìa mới nếu có
    $cover_path = $club['cover_image'];
    if (!empty($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $up = handle_image_upload($_FILES['cover_image'], 'clubs', 2);
        if ($up['success']) {
            $cover_path = $up['path'];
        } else {
            $errors[] = 'Lỗi upload Ảnh bìa: ' . $up['error'];
        }
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            if ($is_edit) {
                $sql = "
                    UPDATE clubs SET
                        category_id = ?, name = ?, short_name = ?, slug = ?,
                        logo = ?, cover_image = ?, summary = ?, description = ?,
                        objectives = ?, target_audience = ?, activities_achievements = ?,
                        meeting_schedule = ?, meeting_location = ?, meeting_frequency = ?,
                        schedule_confirmed = ?, requirements = ?, fee_info = ?,
                        recruitment_status = ?, recruitment_deadline = ?, recruitment_process = ?,
                        application_link = ?, club_tier = ?, is_featured = ?, is_active = ?, updated_at = NOW()
                    WHERE id = ?
                ";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $category_id, $name, $short_name, $slug,
                    $logo_path, $cover_path, $summary, $description,
                    $objectives, $target_audience, $activities_achievements,
                    $meeting_schedule, $meeting_location, $meeting_frequency,
                    $schedule_confirmed, $requirements, $fee_info,
                    $recruitment_status, $recruitment_deadline, $recruitment_process,
                    $application_link, $club_tier, $is_featured, $is_active, $id
                ]);
                $club_target_id = $id;
            } else {
                $sql = "
                    INSERT INTO clubs (
                        category_id, name, short_name, slug, logo, cover_image,
                        summary, description, objectives, target_audience, activities_achievements,
                        meeting_schedule, meeting_location, meeting_frequency,
                        schedule_confirmed, requirements, fee_info,
                        recruitment_status, recruitment_deadline, recruitment_process,
                        application_link, club_tier, is_featured, is_active, created_at, updated_at
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, ?,
                        ?, ?, ?,
                        ?, ?, ?,
                        ?, ?, ?, ?, NOW(), NOW()
                    )
                ";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $category_id, $name, $short_name, $slug, $logo_path, $cover_path,
                    $summary, $description, $objectives, $target_audience, $activities_achievements,
                    $meeting_schedule, $meeting_location, $meeting_frequency,
                    $schedule_confirmed, $requirements, $fee_info,
                    $recruitment_status, $recruitment_deadline, $recruitment_process,
                    $application_link, $club_tier, $is_featured, $is_active
                ]);
                $club_target_id = (int)$db->lastInsertId();
            }

            // Cập nhật Mạng xã hội
            $db->prepare("DELETE FROM club_socials WHERE club_id = ?")->execute([$club_target_id]);
            $social_inputs = $_POST['socials'] ?? [];
            $ins_soc = $db->prepare("INSERT INTO club_socials (club_id, platform, url) VALUES (?, ?, ?)");
            foreach ($social_inputs as $platform => $s_url) {
                $clean_soc = safe_url(trim($s_url));
                if (!empty($clean_soc)) {
                    $ins_soc->execute([$club_target_id, $platform, $clean_soc]);
                }
            }

            // Cập nhật Thuộc tính Quiz
            $db->prepare("DELETE FROM club_quiz_attributes WHERE club_id = ?")->execute([$club_target_id]);
            $attr_inputs = $_POST['quiz_attrs'] ?? [];
            $ins_attr = $db->prepare("INSERT INTO club_quiz_attributes (club_id, attribute_key, score) VALUES (?, ?, ?)");
            foreach ($attr_inputs as $k => $score_val) {
                $score = floatval($score_val);
                if ($score > 0) {
                    $ins_attr->execute([$club_target_id, $k, min(5.0, max(1.0, $score))]);
                }
            }

            // Xóa ảnh thư viện được chọn
            if (!empty($_POST['delete_gallery_images']) && is_array($_POST['delete_gallery_images'])) {
                $del_g_stmt = $db->prepare("DELETE FROM club_images WHERE id = ? AND club_id = ?");
                foreach ($_POST['delete_gallery_images'] as $del_img_id) {
                    $del_g_stmt->execute([(int)$del_img_id, $club_target_id]);
                }
            }

            // Cập nhật chú thích ảnh cũ
            if (!empty($_POST['existing_captions']) && is_array($_POST['existing_captions'])) {
                $up_cap_stmt = $db->prepare("UPDATE club_images SET caption = ? WHERE id = ? AND club_id = ?");
                foreach ($_POST['existing_captions'] as $img_id => $cap_text) {
                    $up_cap_stmt->execute([trim($cap_text), (int)$img_id, $club_target_id]);
                }
            }

            // Tải lên ảnh thư viện mới
            if (!empty($_FILES['gallery_files']) && !empty($_FILES['gallery_files']['name'][0])) {
                $new_caption = trim($_POST['new_gallery_caption'] ?? '');
                $file_count = count($_FILES['gallery_files']['name']);
                $ins_g = $db->prepare("INSERT INTO club_images (club_id, image_url, caption, display_order) VALUES (?, ?, ?, ?)");
                
                for ($i = 0; $i < $file_count; $i++) {
                    if ($_FILES['gallery_files']['error'][$i] === UPLOAD_ERR_OK) {
                        $single_file = [
                            'name'     => $_FILES['gallery_files']['name'][$i],
                            'type'     => $_FILES['gallery_files']['type'][$i],
                            'tmp_name' => $_FILES['gallery_files']['tmp_name'][$i],
                            'error'    => $_FILES['gallery_files']['error'][$i],
                            'size'     => $_FILES['gallery_files']['size'][$i]
                        ];
                        $up = handle_image_upload($single_file, 'clubs', 3);
                        if ($up['success']) {
                            $ins_g->execute([$club_target_id, $up['path'], $new_caption, $i]);
                        }
                    }
                }
            }

            $db->commit();
            flash_set('success', $is_edit ? 'Cập nhật thông tin CLB thành công!' : 'Thêm mới CLB thành công!');
            redirect('admin/clubs.php');
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Lỗi lưu CLB: " . $e->getMessage());
            $errors[] = 'Lỗi hệ thống khi lưu: ' . $e->getMessage();
        }
    }

    foreach ($errors as $err) {
        flash_set('error', $err);
    }
}

include __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-card">
  <div class="admin-card-header">
    <h2 class="admin-card-title"><?= e($page_title) ?></h2>
    <a href="<?= url('admin/clubs.php') ?>" class="btn-admin btn-admin-outline">&larr; Quay lại danh sách</a>
  </div>

  <form action="" method="POST" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <!-- 1. THÔNG TIN CƠ BẢN -->
    <h3 style="font-size: 1.1rem; color: var(--admin-primary); margin-bottom: 16px; border-bottom: 2px solid var(--admin-border); padding-bottom: 6px;">
      1. Thông Tin Nhận Diện
    </h3>
    <div class="form-grid" style="margin-bottom: 24px;">
      <div>
        <label class="admin-label" for="name">Tên Câu Lạc Bộ <span style="color:red;">*</span></label>
        <input type="text" name="name" id="name" class="admin-input" value="<?= e($club['name']) ?>" required>
      </div>

      <div>
        <label class="admin-label" for="short_name">Tên viết tắt / Tiếng Anh</label>
        <input type="text" name="short_name" id="short_name" class="admin-input" placeholder="Ví dụ: DevAI, Melody..." value="<?= e($club['short_name']) ?>">
      </div>

      <div>
        <label class="admin-label" for="slug">URL Slug (Để trống sẽ tự sinh)</label>
        <input type="text" name="slug" id="slug" class="admin-input" value="<?= e($club['slug']) ?>">
      </div>

      <div>
        <label class="admin-label" for="category_id">Lĩnh vực hoạt động <span style="color:red;">*</span></label>
        <select name="category_id" id="category_id" class="admin-select">
          <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= $club['category_id'] == $cat['id'] ? 'selected' : '' ?>>
              <?= e($cat['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="admin-label" for="logo">Logo CLB (Tỷ lệ 1:1)</label>
        <input type="file" name="logo" id="logo" class="admin-input" accept="image/png,image/jpeg,image/webp" data-preview="#logoPreview">
        <div class="img-preview-box">
          <img id="logoPreview" src="<?= upload_url($club['logo'], 'default-club.png') ?>" alt="Logo">
        </div>
      </div>

      <div>
        <label class="admin-label" for="cover_image">Ảnh bìa CLB (Tỷ lệ 16:9)</label>
        <input type="file" name="cover_image" id="cover_image" class="admin-input" accept="image/png,image/jpeg,image/webp" data-preview="#coverPreview">
        <div class="img-preview-box" style="width: 160px; height: 90px;">
          <img id="coverPreview" src="<?= upload_url($club['cover_image'], 'default-cover.png') ?>" alt="Cover">
        </div>
      </div>

      <div class="form-full">
        <label class="admin-label" for="summary">Giới thiệu ngắn gọn (Hiển thị ngoài thẻ) <span style="color:red;">*</span></label>
        <textarea name="summary" id="summary" rows="2" class="admin-textarea" required><?= e($club['summary']) ?></textarea>
      </div>

      <div class="form-full">
        <label class="admin-label" for="description">Mô tả đầy đủ chi tiết <span style="color:red;">*</span></label>
        <textarea name="description" id="description" rows="5" class="admin-textarea" required><?= e($club['description']) ?></textarea>
      </div>

      <div>
        <label class="admin-label" for="objectives">Mục tiêu hoạt động</label>
        <textarea name="objectives" id="objectives" rows="3" class="admin-textarea"><?= e($club['objectives']) ?></textarea>
      </div>

      <div>
        <label class="admin-label" for="target_audience">Đối tượng sinh viên phù hợp</label>
        <textarea name="target_audience" id="target_audience" rows="3" class="admin-textarea"><?= e($club['target_audience']) ?></textarea>
      </div>

      <div class="form-full">
        <label class="admin-label" for="activities_achievements">Hoạt động tiêu biểu & Thành tích</label>
        <textarea name="activities_achievements" id="activities_achievements" rows="4" class="admin-textarea"><?= e($club['activities_achievements']) ?></textarea>
      </div>
    </div>

    <!-- 2. LỊCH SINH HOẠT & HẬU CẦN -->
    <h3 style="font-size: 1.1rem; color: var(--admin-primary); margin-bottom: 16px; border-bottom: 2px solid var(--admin-border); padding-bottom: 6px;">
      2. Lịch Sinh Hoạt & Địa Điểm
    </h3>
    <div class="form-grid" style="margin-bottom: 24px;">
      <div>
        <label class="admin-label" for="meeting_schedule">Thời gian sinh hoạt</label>
        <input type="text" name="meeting_schedule" id="meeting_schedule" class="admin-input" placeholder="Ví dụ: 18:00 Thứ Bảy hàng tuần" value="<?= e($club['meeting_schedule']) ?>">
      </div>

      <div>
        <label class="admin-label" for="meeting_location">Địa điểm sinh hoạt</label>
        <input type="text" name="meeting_location" id="meeting_location" class="admin-input" placeholder="Ví dụ: Phòng Lab B4.02" value="<?= e($club['meeting_location']) ?>">
      </div>

      <div>
        <label class="admin-label" for="meeting_frequency">Tần suất sinh hoạt</label>
        <input type="text" name="meeting_frequency" id="meeting_frequency" class="admin-input" placeholder="Ví dụ: 1 buổi/tuần" value="<?= e($club['meeting_frequency']) ?>">
      </div>

      <div>
        <label class="admin-label">Trạng thái chốt lịch</label>
        <label style="display: flex; align-items: center; gap: 8px; margin-top: 10px; cursor: pointer;">
          <input type="checkbox" name="schedule_confirmed" value="1" <?= $club['schedule_confirmed'] ? 'checked' : '' ?>>
          <span>Đã chốt lịch cố định (Bỏ chọn nếu đang chờ xác nhận lịch)</span>
        </label>
      </div>

      <div>
        <label class="admin-label" for="requirements">Yêu cầu kinh nghiệm / sàng lọc</label>
        <input type="text" name="requirements" id="requirements" class="admin-input" placeholder="Ví dụ: Không yêu cầu kinh nghiệm..." value="<?= e($club['requirements']) ?>">
      </div>

      <div>
        <label class="admin-label" for="fee_info">Kinh phí / Quỹ tham gia</label>
        <input type="text" name="fee_info" id="fee_info" class="admin-input" placeholder="Ví dụ: Miễn phí hoặc 50.000 đ/kỳ..." value="<?= e($club['fee_info']) ?>">
      </div>
    </div>

    <!-- 3. TUYỂN THÀNH VIÊN -->
    <h3 style="font-size: 1.1rem; color: var(--admin-primary); margin-bottom: 16px; border-bottom: 2px solid var(--admin-border); padding-bottom: 6px;">
      3. Đợt Tuyển Thành Viên
    </h3>
    <div class="form-grid" style="margin-bottom: 24px;">
      <div>
        <label class="admin-label" for="recruitment_status">Trạng thái tuyển</label>
        <select name="recruitment_status" id="recruitment_status" class="admin-select">
          <option value="closed" <?= $club['recruitment_status'] === 'closed' ? 'selected' : '' ?>>Đã đóng tuyển</option>
          <option value="open" <?= $club['recruitment_status'] === 'open' ? 'selected' : '' ?>>Đang mở đơn tuyển</option>
          <option value="upcoming" <?= $club['recruitment_status'] === 'upcoming' ? 'selected' : '' ?>>Sắp mở tuyển</option>
        </select>
      </div>

      <div>
        <label class="admin-label" for="recruitment_deadline">Hạn chót nộp đơn (Tự ngắt khi qua hạn)</label>
        <input type="datetime-local" name="recruitment_deadline" id="recruitment_deadline" class="admin-input" value="<?= !empty($club['recruitment_deadline']) ? date('Y-m-d\TH:i', strtotime($club['recruitment_deadline'])) : '' ?>">
      </div>

      <div class="form-full">
        <label class="admin-label" for="application_link">Liên kết nộp đơn ứng tuyển (Google Form / Microsoft Form)</label>
        <input type="url" name="application_link" id="application_link" class="admin-input" placeholder="https://docs.google.com/forms/..." value="<?= e($club['application_link']) ?>">
      </div>

      <div class="form-full">
        <label class="admin-label" for="recruitment_process">Quy trình tuyển chọn thành viên</label>
        <textarea name="recruitment_process" id="recruitment_process" rows="2" class="admin-textarea" placeholder="Ví dụ: Vòng 1: Đơn trực tuyến -> Vòng 2: Phỏng vấn trực tiếp"><?= e($club['recruitment_process']) ?></textarea>
      </div>
    </div>

    <!-- 4. KÊNH MẠNG XÃ HỘI CHÍNH THỨC -->
    <h3 style="font-size: 1.1rem; color: var(--admin-primary); margin-bottom: 16px; border-bottom: 2px solid var(--admin-border); padding-bottom: 6px;">
      4. Kênh Mạng Xã Hội Chính Thức (Để trống nếu chưa có)
    </h3>
    <div class="form-grid" style="margin-bottom: 24px;">
      <?php foreach (SUPPORTED_SOCIALS as $plat_key => $plat_data): ?>
        <div>
          <label class="admin-label" for="soc_<?= $plat_key ?>"><?= e($plat_data['name']) ?></label>
          <input type="url" name="socials[<?= $plat_key ?>]" id="soc_<?= $plat_key ?>" class="admin-input" placeholder="https://..." value="<?= e($club_socials[$plat_key] ?? '') ?>">
        </div>
      <?php endforeach; ?>
    </div>

    <!-- 5. ĐIỂM THUỘC TÍNH PHỤC VỤ QUIZ (Thang điểm 1.0 đến 5.0) -->
    <h3 style="font-size: 1.1rem; color: var(--admin-primary); margin-bottom: 16px; border-bottom: 2px solid var(--admin-border); padding-bottom: 6px;">
      5. Thuộc Tính Định Hướng Quiz (Thang điểm 1.0 - 5.0)
    </h3>
    <p style="font-size: 0.85rem; color: var(--admin-text-muted); margin-bottom: 16px;">
      Điểm số phản ánh mức độ phù hợp của CLB đối với từng tiêu chí để backend tự động ghép nối chính xác với câu trả lời của sinh viên.
    </p>
    <div class="form-grid" style="margin-bottom: 24px;">
      <?php foreach ($attribute_keys as $ak): ?>
        <div>
          <label class="admin-label" for="attr_<?= $ak['attribute_key'] ?>">
            <?= e($ak['name']) ?>
          </label>
          <input type="number" step="0.5" min="1.0" max="5.0" name="quiz_attrs[<?= $ak['attribute_key'] ?>]" id="attr_<?= $ak['attribute_key'] ?>" class="admin-input" value="<?= e($club_attributes[$ak['attribute_key']] ?? '3.0') ?>">
        </div>
      <?php endforeach; ?>
    </div>

    <!-- 6. THƯ VIỆN HÌNH ẢNH HOẠT ĐỘNG -->
    <h3 style="font-size: 1.1rem; color: var(--admin-primary); margin-bottom: 16px; border-bottom: 2px solid var(--admin-border); padding-bottom: 6px;">
      6. Thư Viện Hình Ảnh Hoạt Động (Photo Gallery)
    </h3>

    <?php if (!empty($club_gallery_images)): ?>
      <p style="font-size: 0.88rem; color: var(--admin-text-muted); margin-bottom: 12px;">
        Các hình ảnh hiện có (Có thể sửa chú thích hoặc tích chọn để xóa ảnh khi bấm Lưu):
      </p>
      <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <?php foreach ($club_gallery_images as $g_img): ?>
          <div style="background: #F8FAFC; border: 1px solid var(--admin-border); border-radius: 8px; padding: 12px; text-align: center;">
            <div style="width: 100%; aspect-ratio: 4/3; border-radius: 6px; overflow: hidden; margin-bottom: 8px; border: 1px solid var(--admin-border);">
              <img src="<?= upload_url($g_img['image_url']) ?>" alt="Ảnh gallery" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
            <input type="text" name="existing_captions[<?= $g_img['id'] ?>]" class="admin-input" style="font-size: 0.82rem; margin-bottom: 8px;" placeholder="Chú thích ảnh..." value="<?= e($g_img['caption'] ?? '') ?>">
            <label style="font-size: 0.82rem; color: var(--admin-danger); display: flex; align-items: center; justify-content: center; gap: 4px; cursor: pointer;">
              <input type="checkbox" name="delete_gallery_images[]" value="<?= $g_img['id'] ?>">
              <span>Xóa ảnh này</span>
            </label>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p style="font-size: 0.88rem; color: var(--admin-text-muted); margin-bottom: 16px;">
        CLB chưa có hình ảnh hoạt động nào trong thư viện ảnh.
      </p>
    <?php endif; ?>

    <div style="background: #F8FAFC; border: 1px dashed var(--admin-border); border-radius: 8px; padding: 18px; margin-bottom: 28px;">
      <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--admin-primary); margin-bottom: 10px;">
        + Tải lên thêm hình ảnh hoạt động mới
      </h4>
      <div class="form-grid">
        <div>
          <label class="admin-label">Chọn một hoặc nhiều file ảnh (JPG, PNG, WEBP)</label>
          <input type="file" name="gallery_files[]" class="admin-input" multiple accept="image/png,image/jpeg,image/webp">
        </div>
        <div>
          <label class="admin-label">Chú thích ảnh mới (Áp dụng cho đợt tải này)</label>
          <input type="text" name="new_gallery_caption" class="admin-input" placeholder="Ví dụ: Sinh hoạt dã ngoại, Buổi lễ ra quân...">
        </div>
      </div>
    </div>

    <!-- 7. THIẾT LẬP HIỂN THỊ & XẾP HẠNG (CHỈ SUPER ADMIN CÓ QUYỀN) -->
    <h3 style="font-size: 1.1rem; color: var(--admin-primary); margin-bottom: 16px; border-bottom: 2px solid var(--admin-border); padding-bottom: 6px;">
      7. Trạng Thái Hiển Thị & Thứ Hạng Vinh Danh (Rank CLB)
    </h3>
    <div style="background: #F8FAFC; border: 1.5px solid #E2E8F0; border-radius: 8px; padding: 16px; margin-bottom: 24px;">
      <label class="admin-label" for="club_tier" style="font-weight: 700; color: #1E293B; margin-bottom: 6px;">
        🛡️ Thứ hạng / Rank CLB (Quyền quản trị tối cao của Ban Giám Hiệu & Admin)
      </label>
      <p style="font-size: 0.85rem; color: var(--admin-text-muted); margin-bottom: 10px;">
        Rank thể hiện mức độ sôi nổi, cống hiến và phong trào của CLB. Khi đặt rank cao, CLB sẽ sở hữu <strong>Khung Viền Hào Quang Độc Quyền</strong> (hiệu ứng tinh vân ma pháp, viền kim cương/vàng động, huy hiệu danh dự) trên toàn website. CLB trong portal <strong>không thể tự ý sửa đổi</strong> mục này.
      </p>
      <select name="club_tier" id="club_tier" class="admin-select" style="max-width: 450px; font-weight: 600;">
        <?php foreach (get_club_tiers() as $tier_k => $tier_v): ?>
          <option value="<?= $tier_k ?>" <?= ($club['club_tier'] ?? 'standard') === $tier_k ? 'selected' : '' ?>>
            <?= $tier_v['icon'] ?> <?= e($tier_v['name']) ?> &mdash; <?= e($tier_v['desc']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="display: flex; gap: 30px; margin-bottom: 30px;">
      <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
        <input type="checkbox" name="is_featured" value="1" <?= $club['is_featured'] ? 'checked' : '' ?>>
        <strong>Chọn làm CLB Nổi Bật (Featured)</strong>
      </label>
      <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
        <input type="checkbox" name="is_active" value="1" <?= $club['is_active'] ? 'checked' : '' ?>>
        <strong>Công khai CLB trên website (Active)</strong>
      </label>
    </div>

    <div style="display: flex; gap: 12px;">
      <button type="submit" class="btn-admin btn-admin-primary" style="padding: 12px 28px; font-size: 1rem;">
        <?= $is_edit ? 'Lưu Thay Đổi' : 'Tạo Câu Lạc Bộ Mới' ?>
      </button>
      <a href="<?= url('admin/clubs.php') ?>" class="btn-admin btn-admin-outline" style="padding: 12px 20px;">
        Hủy
      </a>
    </div>

  </form>
</div>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
