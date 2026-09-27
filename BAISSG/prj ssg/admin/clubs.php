<?php
/**
 * ClubHub - Quản Lý Danh Sách Câu Lạc Bộ (Admin Clubs Management)
 */

define('CLUBHUB_INIT', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$page_title = 'Quản lý Câu Lạc Bộ';
$db = get_db_connection();

// Xử lý Xóa hoặc Bật/Tắt trạng thái qua POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_protect();
    $action = $_POST['action'] ?? '';
    $club_id = (int)($_POST['club_id'] ?? 0);

    if ($club_id > 0) {
        if ($action === 'delete') {
            try {
                $del = $db->prepare("DELETE FROM clubs WHERE id = ?");
                $del->execute([$club_id]);
                flash_set('success', 'Đã xóa câu lạc bộ thành công.');
            } catch (Exception $e) {
                error_log("Lỗi xóa CLB: " . $e->getMessage());
                flash_set('error', 'Không thể xóa câu lạc bộ này do có dữ liệu liên quan.');
            }
        } elseif ($action === 'toggle_active') {
            $stmt = $db->prepare("UPDATE clubs SET is_active = IF(is_active=1, 0, 1) WHERE id = ?");
            $stmt->execute([$club_id]);
            flash_set('success', 'Đã cập nhật trạng thái ẩn/hiện của CLB.');
        } elseif ($action === 'toggle_featured') {
            $stmt = $db->prepare("UPDATE clubs SET is_featured = IF(is_featured=1, 0, 1) WHERE id = ?");
            $stmt->execute([$club_id]);
            flash_set('success', 'Đã cập nhật trạng thái nổi bật của CLB.');
        }
    }
    redirect('admin/clubs.php');
}

// Lọc và Tìm kiếm
$q = trim($_GET['q'] ?? '');
$cat_id = (int)($_GET['category_id'] ?? 0);

$where = ["1=1"];
$params = [];

if (!empty($q)) {
    $where[] = "(c.name LIKE ? OR c.short_name LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($cat_id > 0) {
    $where[] = "c.category_id = ?";
    $params[] = $cat_id;
}

$where_sql = implode(" AND ", $where);

// Lấy danh mục
$categories = $db->query("SELECT id, name FROM categories ORDER BY display_order ASC")->fetchAll();

// Lấy danh sách CLB
$stmt = $db->prepare("
    SELECT c.*, cat.name as category_name
    FROM clubs c
    JOIN categories cat ON c.category_id = cat.id
    WHERE {$where_sql}
    ORDER BY c.is_featured DESC, c.id DESC
");
$stmt->execute($params);
$clubs = $stmt->fetchAll();

include __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-card">
  <div class="admin-card-header">
    <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
      <h2 class="admin-card-title">Danh Sách Câu Lạc Bộ (<?= count($clubs) ?>)</h2>
      <a href="<?= url('admin/club-form.php') ?>" class="btn-admin btn-admin-primary">
        + Thêm CLB Mới
      </a>
    </div>

    <!-- Bộ lọc tìm kiếm -->
    <form action="<?= url('admin/clubs.php') ?>" method="GET" style="display: flex; gap: 10px; align-items: center;">
      <input type="text" name="q" class="admin-input" placeholder="Tìm theo tên..." value="<?= e($q) ?>" style="width: 200px;">
      <select name="category_id" class="admin-select" style="width: 180px;">
        <option value="0">-- Tất cả lĩnh vực --</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= $cat['id'] ?>" <?= $cat_id === (int)$cat['id'] ? 'selected' : '' ?>>
            <?= e($cat['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn-admin btn-admin-outline">Tìm</button>
      <?php if (!empty($q) || $cat_id > 0): ?>
        <a href="<?= url('admin/clubs.php') ?>" class="btn-admin btn-admin-outline">Đặt lại</a>
      <?php endif; ?>
    </form>
  </div>

  <?php if (!empty($clubs)): ?>
    <table class="admin-table">
      <thead>
        <tr>
          <th>Logo</th>
          <th>Tên CLB</th>
          <th>Lĩnh vực</th>
          <th>Tuyển thành viên</th>
          <th>Nổi bật</th>
          <th>Trạng thái</th>
          <th style="text-align: right;">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($clubs as $c): ?>
          <?php $rec = check_actual_recruitment_status($c['recruitment_status'], $c['recruitment_deadline']); ?>
          <tr>
            <td style="width: 60px;">
              <img src="<?= upload_url($c['logo'], 'default-club.png') ?>" alt="" style="width: 44px; height: 44px; border-radius: 8px; object-fit: cover; border: 1px solid var(--admin-border);">
            </td>
            <td>
              <strong>
                <a href="<?= url('club-detail.php?slug=' . e($c['slug'])) ?>" target="_blank" rel="noopener noreferrer" style="color: var(--admin-primary); text-decoration: none;">
                  <?= e($c['name']) ?>
                </a>
              </strong>
              <?php if (!empty($c['short_name'])): ?>
                <span style="color: var(--admin-text-muted); font-size: 0.85rem;">(<?= e($c['short_name']) ?>)</span>
              <?php endif; ?>
            </td>
            <td><?= e($c['category_name']) ?></td>
            <td><?= $rec['badge'] ?></td>
            <td>
              <form action="<?= url('admin/clubs.php') ?>" method="POST" style="display: inline;">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle_featured">
                <input type="hidden" name="club_id" value="<?= $c['id'] ?>">
                <button type="submit" style="background: none; border: none; cursor: pointer; font-size: 1.2rem;" title="Bấm để bật/tắt nổi bật">
                  <?= $c['is_featured'] ? '⭐' : '☆' ?>
                </button>
              </form>
            </td>
            <td>
              <form action="<?= url('admin/clubs.php') ?>" method="POST" style="display: inline;">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle_active">
                <input type="hidden" name="club_id" value="<?= $c['id'] ?>">
                <button type="submit" style="background: none; border: none; cursor: pointer; font-size: 0.85rem; font-weight: 700; color: <?= $c['is_active'] ? 'var(--admin-success)' : 'var(--admin-danger)' ?>;" title="Bấm để ẩn/hiện CLB">
                  <?= $c['is_active'] ? '● Hiển thị' : '○ Đang ẩn' ?>
                </button>
              </form>
            </td>
            <td style="text-align: right;">
              <div class="action-buttons" style="justify-content: flex-end;">
                <a href="<?= url('admin/club-form.php?id=' . $c['id']) ?>" class="btn-admin btn-admin-outline" style="padding: 4px 10px; font-size: 0.8rem;">
                  Sửa
                </a>
                <form action="<?= url('admin/clubs.php') ?>" method="POST" style="display: inline;" class="btn-confirm-delete" data-confirm="Bạn có chắc muốn xóa CLB '<?= e($c['name']) ?>'? Thao tác này sẽ xóa các ảnh, mạng xã hội và thuộc tính quiz liên quan.">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="club_id" value="<?= $c['id'] ?>">
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
  <?php else: ?>
    <div style="text-align: center; padding: 40px; color: var(--admin-text-muted);">
      Không có câu lạc bộ nào phù hợp với điều kiện tìm kiếm.
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
