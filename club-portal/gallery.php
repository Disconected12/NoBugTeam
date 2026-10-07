<?php
/**
 * Demension - Quản Lý Thư Viện Ảnh CLB (Club Gallery Manager)
 */

define('CLUBHUB_INIT', true);
$page_title = 'Thư Viện Ảnh Hoạt Động';
require_once __DIR__ . '/includes/header.php';

$club_id = current_club_id();
$errors = [];

// Xử lý Xóa ảnh
if (isset($_GET['delete'])) {
    csrf_protect();
    $img_id = (int)$_GET['delete'];
    try {
        $del = $db->prepare("DELETE FROM club_images WHERE id = ? AND club_id = ?");
        $del->execute([$img_id, $club_id]);
        flash_set('success', 'Đã xóa ảnh hoạt động.');
    } catch (Exception $e) {
        flash_set('error', 'Lỗi khi xóa ảnh: ' . $e->getMessage());
    }
    redirect('club-portal/gallery.php');
}

// Xử lý Upload ảnh mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['new_images'])) {
    csrf_protect();
    $caption = trim($_POST['caption'] ?? '');

    $files = $_FILES['new_images'];
    $uploaded_count = 0;

    if (!empty($files['name'][0])) {
        $total = count($files['name']);
        for ($i = 0; $i < $total; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $single_file = [
                    'name'     => $files['name'][$i],
                    'type'     => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error'    => $files['error'][$i],
                    'size'     => $files['size'][$i]
                ];
                $up = handle_image_upload($single_file, 'clubs', 4);
                if ($up['success']) {
                    $ins = $db->prepare("INSERT INTO club_images (club_id, image_url, caption, display_order) VALUES (?, ?, ?, ?)");
                    $ins->execute([$club_id, $up['path'], $caption, 0]);
                    $uploaded_count++;
                } else {
                    $errors[] = "Lỗi tải ảnh '{$files['name'][$i]}': " . $up['error'];
                }
            }
        }
        if ($uploaded_count > 0) {
            flash_set('success', "✨ Đã tải lên thành công {$uploaded_count} hình ảnh hoạt động mới!");
            redirect('club-portal/gallery.php');
        }
    } else {
        $errors[] = 'Vui lòng chọn ít nhất một hình ảnh để tải lên.';
    }
}

// Lấy danh sách ảnh hiện tại
$images = [];
if ($db) {
    $stmt = $db->prepare("SELECT * FROM club_images WHERE club_id = ? ORDER BY id DESC");
    $stmt->execute([$club_id]);
    $images = $stmt->fetchAll();
}
?>

<div class="cp-card">
  <div class="cp-card-header">
    <div class="cp-card-title">
      <span>🖼️</span>
      <span>Thêm Hình Ảnh Hoạt Động Vào Thư Viện (Gallery)</span>
    </div>
    <span class="badge-star">KHOẢNH KHẮC CLB</span>
  </div>

  <?php if (!empty($errors)): ?>
    <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4); color: #fca5a5; padding: 14px 18px; border-radius: 12px; margin-bottom: 1.5rem;">
      <strong>⚠️ Lỗi:</strong>
      <ul style="margin-left: 20px; margin-top: 6px;">
        <?php foreach ($errors as $er): ?>
          <li><?= e($er) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="POST" action="" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; align-items: end;">
      <div>
        <label style="display: block; font-weight: 700; color: #fff; margin-bottom: 6px;">Chọn một hoặc nhiều tệp ảnh (JPG, PNG, WebP tối đa 4MB/ảnh)</label>
        <input type="file" name="new_images[]" multiple accept="image/*" class="form-control" required style="padding: 10px;">
      </div>
      <div>
        <label style="display: block; font-weight: 700; color: #fff; margin-bottom: 6px;">Chú thích chung cho đợt ảnh</label>
        <input type="text" name="caption" class="form-control" placeholder="VD: Sinh hoạt định kỳ, Hoạt động Teambuilding...">
      </div>
    </div>

    <button type="submit" class="btn btn-primary" style="margin-top: 1.25rem; background: linear-gradient(135deg, #F6C453, #D97706); color: #06102B; font-weight: 800; padding: 10px 24px; border-radius: 10px; border: none; cursor: pointer;">
      📤 Tải Ảnh Lên Thư Viện
    </button>
  </form>
</div>

<!-- Danh sách ảnh hiện tại -->
<div class="cp-card">
  <div class="cp-card-header">
    <div class="cp-card-title">
      <span>✨</span>
      <span>Bộ Sưu Tập Đã Đăng (<?= count($images) ?> ảnh)</span>
    </div>
  </div>

  <?php if (empty($images)): ?>
    <div style="text-align: center; padding: 3rem 1rem; color: #94a3b8;">
      <div style="font-size: 3rem; margin-bottom: 0.5rem;">📷</div>
      <p>Chưa có hình ảnh nào trong thư viện hoạt động của CLB.</p>
    </div>
  <?php else: ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1.5rem;">
      <?php foreach ($images as $img): ?>
        <div style="background: rgba(6,16,43,0.7); border: 1px solid rgba(255,255,255,0.1); border-radius: 14px; overflow: hidden; display: flex; flex-direction: column;">
          <div style="height: 160px; overflow: hidden; position: relative;">
            <img src="<?= asset($img['image_url']) ?>" alt="Ảnh hoạt động" style="width: 100%; height: 100%; object-fit: cover;">
          </div>
          <div style="padding: 12px; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
            <div style="font-size: 0.85rem; color: #cbd5e1; margin-bottom: 8px;">
              <?= e($img['caption'] ?: 'Không có chú thích') ?>
            </div>
            <div style="text-align: right; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 8px;">
              <form method="POST" action="<?= url('club-portal/gallery.php?delete=' . $img['id']) ?>" onsubmit="return confirm('Bạn có chắc chắn muốn xóa ảnh này không?');" style="display: inline;">
                <?= csrf_field() ?>
                <button type="submit" style="background: none; border: none; color: #f87171; cursor: pointer; font-size: 0.8rem; padding: 0;">🗑️ Xóa ảnh</button>
              </form>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>