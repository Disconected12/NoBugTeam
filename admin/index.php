<?php
/**
 * ClubHub - Bảng Điều Khiển Quản Trị (Admin Dashboard)
 */

define('CLUBHUB_INIT', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'Tổng quan hệ thống';

$db = get_db_connection();

// Thống kê số liệu thực tế từ cơ sở dữ liệu
$total_clubs = 0;
$recruiting_clubs = 0;
$pending_ads = 0;
$active_ads = 0;
$upcoming_events = 0;
$unresolved_reports = 0;

$recent_ad_requests = [];
$active_recruiting_clubs = [];

if ($db) {
    try {
        // 1. Tổng số CLB
        $total_clubs = (int)$db->query("SELECT COUNT(*) FROM clubs")->fetchColumn();

        // 2. CLB đang mở tuyển (và còn hạn)
        $recruiting_clubs = (int)$db->query("
            SELECT COUNT(*) FROM clubs
            WHERE recruitment_status = 'open'
              AND (recruitment_deadline IS NULL OR recruitment_deadline >= NOW())
        ")->fetchColumn();

        // 3. Yêu cầu quảng cáo chờ xử lý
        $pending_ads = (int)$db->query("
            SELECT COUNT(*) FROM ad_requests
            WHERE approval_status = 'pending'
        ")->fetchColumn();

        // 4. Quảng cáo đang hiển thị hợp lệ
        $active_ads = (int)$db->query("
            SELECT COUNT(*) FROM ad_requests
            WHERE approval_status = 'approved'
              AND payment_status = 'paid'
              AND is_enabled = 1
              AND start_date <= CURDATE()
              AND end_date >= CURDATE()
        ")->fetchColumn();

        // 5. Sự kiện sắp diễn ra
        $upcoming_events = (int)$db->query("
            SELECT COUNT(*) FROM events
            WHERE is_active = 1 AND end_time >= NOW()
        ")->fetchColumn();

        // 6. Phản ánh chưa xử lý
        $unresolved_reports = (int)$db->query("
            SELECT COUNT(*) FROM feedback_reports
            WHERE status = 'pending'
        ")->fetchColumn();

        // Danh sách 5 yêu cầu quảng bá mới nhất
        $recent_stmt = $db->query("
            SELECT r.*, p.name as placement_name
            FROM ad_requests r
            JOIN ad_placements p ON r.placement_code = p.code
            ORDER BY r.id DESC
            LIMIT 5
        ");
        $recent_ad_requests = $recent_stmt->fetchAll();

        // Danh sách CLB đang mở tuyển
        $rec_stmt = $db->query("
            SELECT c.id, c.name, c.short_name, c.recruitment_deadline, cat.name as category_name
            FROM clubs c
            JOIN categories cat ON c.category_id = cat.id
            WHERE c.recruitment_status = 'open'
              AND (c.recruitment_deadline IS NULL OR c.recruitment_deadline >= NOW())
            ORDER BY c.recruitment_deadline ASC
            LIMIT 5
        ");
        $active_recruiting_clubs = $rec_stmt->fetchAll();

    } catch (Exception $e) {
        error_log("Lỗi tải thống kê dashboard: " . $e->getMessage());
    }
}

include __DIR__ . '/includes/admin_header.php';
?>

<!-- Các Thẻ Thống Kê Số Liệu Thực Tế -->
<div class="stat-cards-grid">
  
  <div class="stat-card">
    <div>
      <div class="stat-val"><?= $total_clubs ?></div>
      <div class="stat-label">Tổng số Câu lạc bộ</div>
    </div>
    <div class="stat-icon primary">🏛️</div>
  </div>

  <div class="stat-card">
    <div>
      <div class="stat-val" style="color: var(--admin-success);"><?= $recruiting_clubs ?></div>
      <div class="stat-label">CLB đang mở tuyển</div>
    </div>
    <div class="stat-icon success">📢</div>
  </div>

  <div class="stat-card">
    <div>
      <div class="stat-val" style="color: var(--admin-accent);"><?= $pending_ads ?></div>
      <div class="stat-label">Yêu cầu quảng cáo chờ duyệt</div>
    </div>
    <div class="stat-icon accent">⏳</div>
  </div>

  <div class="stat-card">
    <div>
      <div class="stat-val" style="color: var(--admin-info);"><?= $active_ads ?></div>
      <div class="stat-label">Quảng cáo đang hiển thị</div>
    </div>
    <div class="stat-icon primary">🟢</div>
  </div>

</div>

<!-- Thao Tác Nhanh (Quick Actions) -->
<div class="admin-card" style="padding: 18px 24px; margin-bottom: 24px;">
  <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
    <div style="font-weight: 700; color: var(--admin-primary);">⚡ Lối tắt quản lý:</div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
      <a href="<?= url('admin/club-form.php') ?>" class="btn-admin btn-admin-primary">
        + Thêm CLB Mới
      </a>
      <a href="<?= url('admin/events.php?action=create') ?>" class="btn-admin btn-admin-outline">
        + Đăng Sự Kiện Mới
      </a>
      <a href="<?= url('admin/ads.php') ?>" class="btn-admin btn-admin-outline">
        Kiểm Duyệt Quảng Cáo (<?= $pending_ads ?>)
      </a>
      <a href="<?= url('admin/feedback.php') ?>" class="btn-admin btn-admin-outline">
        Xử Lý Phản Ánh (<?= $unresolved_reports ?>)
      </a>
    </div>
  </div>
</div>

<!-- Bảng: Yêu cầu thuê quảng cáo mới gửi -->
<div class="admin-card">
  <div class="admin-card-header">
    <h2 class="admin-card-title">📢 Yêu Cầu Thuê Vị Trí Quảng Bá Gần Đây</h2>
    <a href="<?= url('admin/ads.php') ?>" class="btn-admin btn-admin-outline">Xem tất cả &rarr;</a>
  </div>

  <?php if (!empty($recent_ad_requests)): ?>
    <table class="admin-table">
      <thead>
        <tr>
          <th>Mã tra cứu</th>
          <th>Câu lạc bộ</th>
          <th>Vị trí thuê</th>
          <th>Thời gian chạy</th>
          <th>Xét duyệt</th>
          <th>Thanh toán</th>
          <th>Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($recent_ad_requests as $req): ?>
          <tr>
            <td>
              <code><strong><?= e($req['tracking_code']) ?></strong></code>
            </td>
            <td>
              <strong><?= e($req['club_name']) ?></strong><br>
              <span style="font-size: 0.8rem; color: var(--admin-text-muted);"><?= e($req['contact_name']) ?> (<?= e($req['phone']) ?>)</span>
            </td>
            <td><?= e($req['placement_name']) ?></td>
            <td>
              <?= format_date_vn($req['start_date']) ?> - <?= format_date_vn($req['end_date']) ?>
            </td>
            <td>
              <?php if ($req['approval_status'] === 'approved'): ?>
                <span class="badge badge-open">Đã duyệt</span>
              <?php elseif ($req['approval_status'] === 'rejected'): ?>
                <span class="badge badge-closed">Từ chối</span>
              <?php else: ?>
                <span class="badge badge-upcoming">Chờ duyệt</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($req['payment_status'] === 'paid'): ?>
                <span style="color: var(--admin-success); font-weight: 700;">✓ Đã thanh toán</span>
              <?php elseif ($req['payment_status'] === 'refunded'): ?>
                <span style="color: var(--admin-text-muted);">Đã hoàn tiền</span>
              <?php else: ?>
                <span style="color: var(--admin-warning); font-weight: 700;">Chưa thanh toán</span>
              <?php endif; ?>
            </td>
            <td>
              <a href="<?= url('admin/ads.php?edit_id=' . $req['id']) ?>" class="btn-admin btn-admin-primary" style="padding: 4px 10px; font-size: 0.8rem;">
                Xử lý
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p style="color: var(--admin-text-muted); padding: 20px; text-align: center;">Chưa có yêu cầu quảng cáo nào được gửi.</p>
  <?php endif; ?>
</div>

<!-- Bảng: CLB đang mở tuyển thành viên -->
<div class="admin-card">
  <div class="admin-card-header">
    <h2 class="admin-card-title">📝 Đợt Tuyển Thành Viên Đang Mở</h2>
    <a href="<?= url('admin/clubs.php') ?>" class="btn-admin btn-admin-outline">Quản lý CLB &rarr;</a>
  </div>

  <?php if (!empty($active_recruiting_clubs)): ?>
    <table class="admin-table">
      <thead>
        <tr>
          <th>Tên Câu Lạc Bộ</th>
          <th>Lĩnh vực</th>
          <th>Hạn nộp hồ sơ</th>
          <th>Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($active_recruiting_clubs as $rc): ?>
          <tr>
            <td>
              <strong><?= e($rc['name']) ?></strong>
              <?php if (!empty($rc['short_name'])): ?>
                (<?= e($rc['short_name']) ?>)
              <?php endif; ?>
            </td>
            <td><?= e($rc['category_name']) ?></td>
            <td>
              ⏳ <?= !empty($rc['recruitment_deadline']) ? format_date_vn($rc['recruitment_deadline'], true) : 'Mở liên tục' ?>
            </td>
            <td>
              <a href="<?= url('admin/club-form.php?id=' . $rc['id']) ?>" class="btn-admin btn-admin-outline" style="padding: 4px 10px; font-size: 0.8rem;">
                Chỉnh sửa
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p style="color: var(--admin-text-muted); padding: 20px; text-align: center;">Hiện không có CLB nào đang mở đợt tuyển.</p>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
