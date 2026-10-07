<?php
/**
 * ClubHub - Quản Lý Danh Mục / Lĩnh Vực Hoạt Động (Admin Categories)
 */

define('CLUBHUB_INIT', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$page_title = 'Quản lý Lĩnh vực';
$db = get_db_connection();

// Xử lý Xóa danh mục
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    csrf_protect();
    $cat_id = (int)($_POST['category_id'] ?? 0);

    if ($cat_id > 0) {
        // Kiểm tra xem có CLB nào đang trực thuộc danh mục này không
        $check = $db->prepare("SELECT COUNT(*) FROM clubs WHERE category_id = ?");
        $check->execute([$cat_id]);
        $clubs_in_cat = (int)$check->fetchColumn();

        if ($clubs_in_cat > 0) {
            flash_set('error', "Không thể xóa danh mục này vì hiện đang có {$clubs_in_cat} câu lạc bộ trực thuộc. Vui lòng chuyển các CLB sang danh mục khác trước khi xóa!");
        } else {
            try {
                $del = $db->prepare("DELETE FROM categories WHERE id = ?");
                $del->execute([$cat_id]);
                flash_set('success', 'Đã xóa danh mục thành công.');
            } catch (Exception $e) {
                error_log("Lỗi xóa danh mục: " . $e->getMessage());
                flash_set('error', 'Lỗi khi xóa danh mục: ' . $e->getMessage());
            }
        }
    }
    redirect('admin/categories.php');
}

// Xử lý Thêm mới hoặc Sửa danh mục
$edit_id = (int)($_GET['edit_id'] ?? 0);
$edit_cat = null;
if ($edit_id > 0) {
    $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_cat = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    csrf_protect();

    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    if (empty($slug)) $slug = slugify($name);
    $description = trim($_POST['description'] ?? '');
    $icon = trim($_POST['icon'] ?? 'star');
    $display_order = (int)($_POST['display_order'] ?? 0);
    $is_active = !empty($_POST['is_active']) ? 1 : 0;

    if (empty($name)) {
        flash_set('error', 'Tên danh mục không được để trống.');
    } else {
        try {
            if ($edit_id > 0) {
                $stmt = $db->prepare("
                    UPDATE categories SET
                        name = ?, slug = ?, description = ?, icon = ?,
                        display_order = ?, is_active = ?
                    WHERE id = ?
                ");
                $stmt->execute([$name, $slug, $description, $icon, $display_order, $is_active, $edit_id]);
                flash_set('success', 'Cập nhật danh mục thành công.');
            } else {
                $stmt = $db->prepare("
                    INSERT INTO categories (name, slug, description, icon, display_order, is_active)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$name, $slug, $description, $icon, $display_order, $is_active]);
                flash_set('success', 'Thêm danh mục mới thành công.');
            }
            redirect('admin/categories.php');
        } catch (Exception $e) {
            error_log("Lỗi lưu danh mục: " . $e->getMessage());
            flash_set('error', 'Không thể lưu danh mục (có thể slug đã tồn tại).');
        }
    }
}

// Lấy danh sách danh mục và đếm số CLB
$categories = $db->query("
    SELECT cat.*, COUNT(c.id) as club_count
    FROM categories cat
    LEFT JOIN clubs c ON cat.id = c.category_id
    GROUP BY cat.id
    ORDER BY cat.display_order ASC
")->fetchAll();

include __DIR__ . '/includes/admin_header.php';
?>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px;">

  <!-- Form Thêm / Sửa -->
  <div class="admin-card">
    <h3 class="admin-card-title" style="margin-bottom: 16px;">
      <?= $edit_cat ? 'Chỉnh Sửa Lĩnh Vực' : 'Thêm Lĩnh Vực Mới' ?>
    </h3>

    <form action="<?= url('admin/categories.php' . ($edit_id ? '?edit_id=' . $edit_id : '')) ?>" method="POST">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save">

      <div style="margin-bottom: 16px;">
        <label class="admin-label" for="name">Tên lĩnh vực <span style="color:red;">*</span></label>
        <input type="text" name="name" id="name" class="admin-input" value="<?= e($edit_cat['name'] ?? '') ?>" required placeholder="Ví dụ: Công nghệ & Kỹ thuật">
      </div>

      <div style="margin-bottom: 16px;">
        <label class="admin-label" for="slug">URL Slug</label>
        <input type="text" name="slug" id="slug" class="admin-input" value="<?= e($edit_cat['slug'] ?? '') ?>" placeholder="cong-nghe-ky-thuat">
      </div>

      <div style="margin-bottom: 16px;">
        <label class="admin-label" for="description">Mô tả ngắn</label>
        <textarea name="description" id="description" rows="3" class="admin-textarea" placeholder="Giới thiệu về các nhóm hoạt động..."><?= e($edit_cat['description'] ?? '') ?></textarea>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
        <div>
          <label class="admin-label" for="display_order">Thứ tự hiển thị</label>
          <input type="number" name="display_order" id="display_order" class="admin-input" value="<?= e($edit_cat['display_order'] ?? '0') ?>">
        </div>
        <div>
          <label class="admin-label" for="icon">Biểu tượng</label>
          <input type="text" name="icon" id="icon" class="admin-input" value="<?= e($edit_cat['icon'] ?? 'star') ?>" placeholder="cpu, book, music...">
        </div>
      </div>

      <div style="margin-bottom: 20px;">
        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
          <input type="checkbox" name="is_active" value="1" <?= ($edit_cat['is_active'] ?? 1) ? 'checked' : '' ?>>
          <span>Kích hoạt hiển thị</span>
        </label>
      </div>

      <div style="display: flex; gap: 10px;">
        <button type="submit" class="btn-admin btn-admin-primary" style="flex-grow: 1;">
          <?= $edit_cat ? 'Cập Nhật' : 'Thêm Mới' ?>
        </button>
        <?php if ($edit_cat): ?>
          <a href="<?= url('admin/categories.php') ?>" class="btn-admin btn-admin-outline">Hủy</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- Bảng Danh Sách Danh Mục -->
  <div class="admin-card">
    <h3 class="admin-card-title" style="margin-bottom: 16px;">
      Danh Sách Các Lĩnh Vực (<?= count($categories) ?>)
    </h3>

    <table class="admin-table">
      <thead>
        <tr>
          <th>Thứ tự</th>
          <th>Tên lĩnh vực</th>
          <th>Slug</th>
          <th>Số CLB</th>
          <th>Trạng thái</th>
          <th style="text-align: right;">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($categories as $c): ?>
          <tr>
            <td><?= (int)$c['display_order'] ?></td>
            <td>
              <strong><?= e($c['name']) ?></strong>
            </td>
            <td><code><?= e($c['slug']) ?></code></td>
            <td>
              <span class="badge badge-category"><?= (int)$c['club_count'] ?> CLB</span>
            </td>
            <td>
              <span style="color: <?= $c['is_active'] ? 'var(--admin-success)' : 'var(--admin-danger)' ?>; font-weight: 700;">
                <?= $c['is_active'] ? 'Bật' : 'Tắt' ?>
              </span>
            </td>
            <td style="text-align: right;">
              <div class="action-buttons" style="justify-content: flex-end;">
                <a href="<?= url('admin/categories.php?edit_id=' . $c['id']) ?>" class="btn-admin btn-admin-outline" style="padding: 4px 10px; font-size: 0.8rem;">
                  Sửa
                </a>
                <form action="<?= url('admin/categories.php') ?>" method="POST" style="display: inline;" class="btn-confirm-delete" data-confirm="Bạn có chắc chắn muốn xóa danh mục '<?= e($c['name']) ?>'?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="category_id" value="<?= $c['id'] ?>">
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

</div>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
