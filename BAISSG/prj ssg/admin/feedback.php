<?php
/**
 * ClubHub - Quản Lý Phản Ánh Thông Tin Sai & Link Hỏng (Admin Feedback Reports)
 */

define('CLUBHUB_INIT', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$page_title = 'Quản lý Phản Ánh & Báo Cáo';
$db = get_db_connection();

// Xử lý Cập nhật trạng thái đã xử lý hoặc Xóa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_protect();
    $action = $_POST['action'] ?? '';
    $report_id = (int)($_POST['report_id'] ?? 0);

    if ($report_id > 0) {
        if ($action === 'resolve') {
            $stmt = $db->prepare("UPDATE feedback_reports SET status = 'resolved', resolved_at = NOW() WHERE id = ?");
            $stmt->execute([$report_id]);
            flash_set('success', 'Đã đánh dấu xử lý xong phản ánh này.');
        } elseif ($action === 'delete') {
            $stmt = $db->prepare("DELETE FROM feedback_reports WHERE id = ?");
            $stmt->execute([$report_id]);
            flash_set('success', 'Đã xóa bản ghi phản ánh.');
        }
    }
    redirect('admin/feedback.php');
}

$status_filter = $_GET['status'] ?? 'pending';
$where_sql = ($status_filter === 'resolved') ? "f.status = 'resolved'" : (($status_filter === 'all') ? "1=1" : "f.status = 'pending'");

$reports = $db->query("
    SELECT f.*, c.name as club_name, c.slug as club_slug, e.title as event_title, e.slug as event_slug
    FROM feedback_reports f
    LEFT JOIN clubs c ON f.club_id = c.id
    LEFT JOIN events e ON f.event_id = e.id
    WHERE {$where_sql}
    ORDER BY f.id DESC
")->fetchAll();

include __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-card">
  <div class="admin-card-header">
    <h2 class="admin-card-title">Danh Sách Báo Cáo Sai Sót & Ý Kiến Đóng Góp (<?= count($reports) ?>)</h2>

    <div style="display: flex; gap: 8px;">
      <a href="<?= url('admin/feedback.php?status=pending') ?>" class="btn-admin <?= $status_filter === 'pending' ? 'btn-admin-primary' : 'btn-admin-outline' ?>">
        Chưa xử lý
      </a>
      <a href="<?= url('admin/feedback.php?status=resolved') ?>" class="btn-admin <?= $status_filter === 'resolved' ? 'btn-admin-primary' : 'btn-admin-outline' ?>">
        Đã xử lý
      </a>
      <a href="<?= url('admin/feedback.php?status=all') ?>" class="btn-admin <?= $status_filter === 'all' ? 'btn-admin-primary' : 'btn-admin-outline' ?>">
        Tất cả
      </a>
    </div>
  </div>

  <?php if (!empty($reports)): ?>
    <table class="admin-table">
      <thead>
        <tr>
          <th>Thời gian</th>
          <th>Loại phản ánh</th>
          <th>Đối tượng liên quan</th>
          <th>Nội dung chi tiết</th>
          <th>Người gửi</th>
          <th>Trạng thái</th>
          <th style="text-align: right;">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($reports as $r): ?>
          <tr>
            <td style="font-size: 0.85rem; white-space: nowrap;">
              <?= format_date_vn($r['created_at'], true) ?>
            </td>
            <td>
              <?php if ($r['report_type'] === 'wrong_info'): ?>
                <span class="badge badge-warning">Thông tin sai</span>
              <?php elseif ($r['report_type'] === 'broken_link'): ?>
                <span class="badge badge-closed">Link hỏng</span>
              <?php else: ?>
                <span class="badge badge-category">Đóng góp khác</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!empty($r['club_name'])): ?>
                CLB: <a href="<?= url('club-detail.php?slug=' . e($r['club_slug'])) ?>" target="_blank" style="font-weight: 700;">
                  <?= e($r['club_name']) ?>
                </a>
              <?php elseif (!empty($r['event_title'])): ?>
                Sự kiện: <a href="<?= url('event-detail.php?slug=' . e($r['event_slug'])) ?>" target="_blank" style="font-weight: 700;">
                  <?= e($r['event_title']) ?>
                </a>
              <?php else: ?>
                <span style="color: var(--admin-text-muted);">Toàn trang</span>
              <?php endif; ?>
            </td>
            <td style="max-width: 320px; line-height: 1.5;">
              <?= nl2br(e($r['details'])) ?>
            </td>
            <td>
              <?= !empty($r['reporter_name']) ? '<strong>' . e($r['reporter_name']) . '</strong><br>' : '' ?>
              <span style="font-size: 0.82rem; color: var(--admin-text-muted);"><?= e($r['reporter_email'] ?? '') ?></span>
            </td>
            <td>
              <?php if ($r['status'] === 'resolved'): ?>
                <span style="color: var(--admin-success); font-weight: 700;">✓ Đã xử lý</span>
              <?php else: ?>
                <span style="color: var(--admin-danger); font-weight: 700;">● Đang chờ</span>
              <?php endif; ?>
            </td>
            <td style="text-align: right;">
              <div class="action-buttons" style="justify-content: flex-end;">
                <?php if ($r['status'] !== 'resolved'): ?>
                  <form action="<?= url('admin/feedback.php') ?>" method="POST" style="display: inline;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="resolve">
                    <input type="hidden" name="report_id" value="<?= $r['id'] ?>">
                    <button type="submit" class="btn-admin btn-admin-primary" style="padding: 4px 8px; font-size: 0.8rem;">
                      Xử lý xong
                    </button>
                  </form>
                <?php endif; ?>
                <form action="<?= url('admin/feedback.php') ?>" method="POST" style="display: inline;" class="btn-confirm-delete" data-confirm="Xóa bản ghi phản ánh này?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="report_id" value="<?= $r['id'] ?>">
                  <button type="submit" class="btn-admin btn-admin-danger" style="padding: 4px 8px; font-size: 0.8rem;">
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
      Không có phản ánh nào trong mục này.
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
