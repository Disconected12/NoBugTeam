<?php
/**
 * ClubHub - Quản Lý Yêu Cầu Quảng Bá & Lịch Hiển Thị Poster (Admin Ads Management)
 */

define('CLUBHUB_INIT', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$page_title = 'Quản lý Quảng Cáo & Poster';
$db = get_db_connection();

$placements = $db->query("SELECT * FROM ad_placements")->fetchAll();
$placements_map = [];
foreach ($placements as $p) {
    $placements_map[$p['code']] = $p;
}

// Xử lý Xóa quảng cáo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    csrf_protect();
    $ad_id = (int)($_POST['ad_id'] ?? 0);
    if ($ad_id > 0) {
        $db->prepare("DELETE FROM ad_requests WHERE id = ?")->execute([$ad_id]);
        flash_set('success', 'Đã xóa yêu cầu quảng cáo.');
    }
    redirect('admin/ads.php');
}

// Xử lý Tạm dừng / Bật hiển thị nhanh
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_enabled') {
    csrf_protect();
    $ad_id = (int)($_POST['ad_id'] ?? 0);
    if ($ad_id > 0) {
        $db->prepare("UPDATE ad_requests SET is_enabled = IF(is_enabled=1, 0, 1) WHERE id = ?")->execute([$ad_id]);
        flash_set('success', 'Đã thay đổi trạng thái bật/tạm dừng quảng cáo.');
    }
    redirect('admin/ads.php');
}

// Xử lý Cập nhật xét duyệt, báo giá, thanh toán, lịch hiển thị
$edit_id = (int)($_GET['edit_id'] ?? 0);
$edit_ad = null;
if ($edit_id > 0) {
    $stmt = $db->prepare("SELECT r.*, p.name as placement_name FROM ad_requests r JOIN ad_placements p ON r.placement_code = p.code WHERE r.id = ?");
    $stmt->execute([$edit_id]);
    $edit_ad = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_ad') {
    csrf_protect();

    $ad_id = (int)($_POST['ad_id'] ?? 0);
    $quote_amount = floatval($_POST['quote_amount'] ?? 0);
    $payment_status = $_POST['payment_status'] ?? 'unpaid';
    $approval_status = $_POST['approval_status'] ?? 'pending';
    $placement_code = $_POST['placement_code'] ?? 'top_banner';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $destination_url = safe_url(trim($_POST['destination_url'] ?? ''));
    $admin_notes = trim($_POST['admin_notes'] ?? '');
    $is_enabled = !empty($_POST['is_enabled']) ? 1 : 0;

    $errors = [];
    if (empty($start_date) || empty($end_date)) $errors[] = 'Vui lòng chọn ngày bắt đầu và kết thúc.';
    if ($end_date < $start_date) $errors[] = 'Ngày kết thúc không được trước ngày bắt đầu.';
    if (empty($destination_url)) $errors[] = 'Liên kết đích không hợp lệ.';

    // Kiểm tra sức chứa của vị trí quảng cáo trong khoảng ngày này
    // Đếm số lượng quảng cáo KHÁC đang active (approved, paid, is_enabled = 1) trùng khoảng ngày
    $max_slots = $placements_map[$placement_code]['max_slots'] ?? 3;
    $slot_stmt = $db->prepare("
        SELECT COUNT(*)
        FROM ad_requests
        WHERE id != ?
          AND placement_code = ?
          AND approval_status = 'approved'
          AND payment_status = 'paid'
          AND is_enabled = 1
          AND start_date <= ?
          AND end_date >= ?
    ");
    $slot_stmt->execute([$ad_id, $placement_code, $end_date, $start_date]);
    $overlapping_count = (int)$slot_stmt->fetchColumn();

    if ($approval_status === 'approved' && $payment_status === 'paid' && $is_enabled == 1) {
        if ($overlapping_count >= $max_slots) {
            $errors[] = "Vị trí này chỉ hỗ trợ tối đa {$max_slots} suất luân phiên. Hiện đã có {$overlapping_count} quảng cáo đang chạy trong khoảng thời gian này (Vượt quá sức chứa)!";
        }
    }

    // Xử lý upload thay thế poster nếu có
    $poster_path = $edit_ad['poster_image'] ?? '';
    if (!empty($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
        $up = handle_image_upload($_FILES['poster'], 'ads', 2);
        if ($up['success']) {
            $poster_path = $up['path'];
        } else {
            $errors[] = 'Lỗi upload Poster: ' . $up['error'];
        }
    }

    if (empty($errors)) {
        try {
            $update = $db->prepare("
                UPDATE ad_requests SET
                    quote_amount = ?, payment_status = ?, approval_status = ?,
                    placement_code = ?, start_date = ?, end_date = ?,
                    destination_url = ?, poster_image = ?, admin_notes = ?,
                    is_enabled = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $update->execute([
                $quote_amount, $payment_status, $approval_status,
                $placement_code, $start_date, $end_date,
                $destination_url, $poster_path, $admin_notes,
                $is_enabled, $ad_id
            ]);

            flash_set('success', 'Cập nhật yêu cầu quảng cáo thành công!');
            redirect('admin/ads.php?edit_id=' . $ad_id);
        } catch (Exception $e) {
            error_log("Lỗi cập nhật quảng cáo: " . $e->getMessage());
            flash_set('error', 'Lỗi khi lưu dữ liệu: ' . $e->getMessage());
        }
    } else {
        foreach ($errors as $err) flash_set('error', $err);
    }
}

// Lọc danh sách yêu cầu
$filter_status = $_GET['status'] ?? 'all';
$where = ["1=1"];
$params = [];

if ($filter_status === 'pending') {
    $where[] = "r.approval_status = 'pending'";
} elseif ($filter_status === 'approved') {
    $where[] = "r.approval_status = 'approved'";
} elseif ($filter_status === 'rejected') {
    $where[] = "r.approval_status = 'rejected'";
} elseif ($filter_status === 'paid') {
    $where[] = "r.payment_status = 'paid'";
} elseif ($filter_status === 'unpaid') {
    $where[] = "r.payment_status = 'unpaid'";
} elseif ($filter_status === 'running') {
    $where[] = "r.approval_status = 'approved' AND r.payment_status = 'paid' AND r.is_enabled = 1 AND r.start_date <= CURDATE() AND r.end_date >= CURDATE()";
} elseif ($filter_status === 'expired') {
    $where[] = "r.end_date < CURDATE()";
}

$where_sql = implode(" AND ", $where);

$all_requests = $db->query("
    SELECT r.*, p.name as placement_name, p.max_slots
    FROM ad_requests r
    JOIN ad_placements p ON r.placement_code = p.code
    WHERE {$where_sql}
    ORDER BY r.id DESC
")->fetchAll();

include __DIR__ . '/includes/admin_header.php';
?>

<!-- Form Chỉnh Sửa & Duyệt Yêu Cầu Cụ Thể (Nếu có edit_id) -->
<?php if ($edit_ad): ?>
  <div class="admin-card" style="border: 2px solid var(--admin-accent);">
    <div class="admin-card-header">
      <h2 class="admin-card-title">
        Xử Lý Yêu Cầu: <code><?= e($edit_ad['tracking_code']) ?></code> - <?= e($edit_ad['club_name']) ?>
      </h2>
      <a href="<?= url('admin/ads.php') ?>" class="btn-admin btn-admin-outline">&larr; Đóng form</a>
    </div>

    <!-- Thông tin người gửi -->
    <div style="background-color: #F8FAFC; border-radius: 8px; padding: 16px; margin-bottom: 24px; border: 1px solid var(--admin-border);">
      <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; font-size: 0.9rem;">
        <div><strong>Người liên hệ:</strong> <?= e($edit_ad['contact_name']) ?></div>
        <div><strong>Email:</strong> <a href="mailto:<?= e($edit_ad['email']) ?>"><?= e($edit_ad['email']) ?></a></div>
        <div><strong>Điện thoại:</strong> <a href="tel:<?= e($edit_ad['phone']) ?>"><?= e($edit_ad['phone']) ?></a></div>
        <div><strong>Ngày gửi yêu cầu:</strong> <?= format_date_vn($edit_ad['created_at'], true) ?></div>
      </div>
      <?php if (!empty($edit_ad['notes'])): ?>
        <div style="margin-top: 10px; font-size: 0.88rem; color: var(--admin-text);">
          <strong>Ghi chú của CLB:</strong> <?= nl2br(e($edit_ad['notes'])) ?>
        </div>
      <?php endif; ?>
    </div>

    <form action="<?= url('admin/ads.php?edit_id=' . $edit_ad['id']) ?>" method="POST" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="update_ad">
      <input type="hidden" name="ad_id" value="<?= $edit_ad['id'] ?>">

      <div class="form-grid">
        <!-- Xét duyệt -->
        <div>
          <label class="admin-label" for="approval_status">Trạng thái xét duyệt nội dung <span style="color:red;">*</span></label>
          <select name="approval_status" id="approval_status" class="admin-select">
            <option value="pending" <?= $edit_ad['approval_status'] === 'pending' ? 'selected' : '' ?>>⏳ Chờ xét duyệt</option>
            <option value="approved" <?= $edit_ad['approval_status'] === 'approved' ? 'selected' : '' ?>>✅ Đã phê duyệt</option>
            <option value="rejected" <?= $edit_ad['approval_status'] === 'rejected' ? 'selected' : '' ?>>❌ Từ chối yêu cầu</option>
          </select>
        </div>

        <!-- Thanh toán -->
        <div>
          <label class="admin-label" for="payment_status">Trạng thái thanh toán (Xác nhận thủ công) <span style="color:red;">*</span></label>
          <select name="payment_status" id="payment_status" class="admin-select">
            <option value="unpaid" <?= $edit_ad['payment_status'] === 'unpaid' ? 'selected' : '' ?>>⚠️ Chưa thanh toán</option>
            <option value="paid" <?= $edit_ad['payment_status'] === 'paid' ? 'selected' : '' ?>>💵 Đã nhận thanh toán</option>
            <option value="refunded" <?= $edit_ad['payment_status'] === 'refunded' ? 'selected' : '' ?>>↩️ Đã hoàn tiền</option>
          </select>
        </div>

        <!-- Báo giá -->
        <div>
          <label class="admin-label" for="quote_amount">Báo giá chi phí (VNĐ)</label>
          <input type="number" name="quote_amount" id="quote_amount" class="admin-input" value="<?= e($edit_ad['quote_amount']) ?>">
        </div>

        <!-- Vị trí -->
        <div>
          <label class="admin-label" for="placement_code">Vị trí hiển thị <span style="color:red;">*</span></label>
          <select name="placement_code" id="placement_code" class="admin-select">
            <?php foreach ($placements as $pl): ?>
              <option value="<?= e($pl['code']) ?>" <?= $edit_ad['placement_code'] === $pl['code'] ? 'selected' : '' ?>>
                <?= e($pl['name']) ?> (Tối đa <?= (int)$pl['max_slots'] ?> suất)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Lịch chạy -->
        <div>
          <label class="admin-label" for="start_date">Ngày bắt đầu chạy <span style="color:red;">*</span></label>
          <input type="date" name="start_date" id="start_date" class="admin-input" value="<?= e($edit_ad['start_date']) ?>" required>
        </div>

        <div>
          <label class="admin-label" for="end_date">Ngày kết thúc chạy <span style="color:red;">*</span></label>
          <input type="date" name="end_date" id="end_date" class="admin-input" value="<?= e($edit_ad['end_date']) ?>" required>
        </div>

        <!-- Link đích -->
        <div class="form-full">
          <label class="admin-label" for="destination_url">Liên kết đích khi click vào poster <span style="color:red;">*</span></label>
          <input type="url" name="destination_url" id="destination_url" class="admin-input" value="<?= e($edit_ad['destination_url']) ?>" required>
        </div>

        <!-- Thay poster -->
        <div>
          <label class="admin-label" for="poster">Hình ảnh Poster (Chọn file nếu muốn thay thế)</label>
          <input type="file" name="poster" id="poster" class="admin-input" accept="image/png,image/jpeg,image/webp" data-preview="#adPosterPreview">
          <div class="img-preview-box" style="width: 220px; height: 110px; margin-top: 8px;">
            <img id="adPosterPreview" src="<?= upload_url($edit_ad['poster_image']) ?>" alt="Poster">
          </div>
        </div>

        <!-- Ghi chú nội bộ -->
        <div>
          <label class="admin-label" for="admin_notes">Ghi chú nội bộ (Chỉ admin xem)</label>
          <textarea name="admin_notes" id="admin_notes" rows="4" class="admin-textarea" placeholder="Ghi chú xác nhận chuyển khoản, số tài khoản, mã hóa đơn..."><?= e($edit_ad['admin_notes'] ?? '') ?></textarea>
        </div>

        <div class="form-full">
          <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
            <input type="checkbox" name="is_enabled" value="1" <?= $edit_ad['is_enabled'] ? 'checked' : '' ?>>
            <strong>Bật quảng cáo này (Nếu bỏ chọn, quảng cáo sẽ tạm dừng ngay lập tức)</strong>
          </label>
        </div>
      </div>

      <div style="margin-top: 24px; display: flex; gap: 12px;">
        <button type="submit" class="btn-admin btn-admin-primary" style="padding: 10px 24px;">
          Lưu Cập Nhật Quảng Cáo
        </button>
        <a href="<?= url('admin/ads.php') ?>" class="btn-admin btn-admin-outline" style="padding: 10px 18px;">
          Đóng
        </a>
      </div>
    </form>
  </div>
<?php endif; ?>

<!-- Danh Sách Toàn Bộ Yêu Cầu Quảng Cáo -->
<div class="admin-card">
  <div class="admin-card-header">
    <h2 class="admin-card-title">Danh Sách Yêu Cầu Quảng Bá Poster (<?= count($all_requests) ?>)</h2>

    <!-- Bộ lọc trạng thái nhanh -->
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
      <a href="<?= url('admin/ads.php?status=all') ?>" class="btn-admin <?= $filter_status === 'all' ? 'btn-admin-primary' : 'btn-admin-outline' ?>">Tất cả</a>
      <a href="<?= url('admin/ads.php?status=pending') ?>" class="btn-admin <?= $filter_status === 'pending' ? 'btn-admin-primary' : 'btn-admin-outline' ?>">Chờ duyệt</a>
      <a href="<?= url('admin/ads.php?status=running') ?>" class="btn-admin <?= $filter_status === 'running' ? 'btn-admin-primary' : 'btn-admin-outline' ?>">Đang chạy</a>
      <a href="<?= url('admin/ads.php?status=unpaid') ?>" class="btn-admin <?= $filter_status === 'unpaid' ? 'btn-admin-primary' : 'btn-admin-outline' ?>">Chưa thanh toán</a>
      <a href="<?= url('admin/ads.php?status=expired') ?>" class="btn-admin <?= $filter_status === 'expired' ? 'btn-admin-primary' : 'btn-admin-outline' ?>">Đã hết hạn</a>
    </div>
  </div>

  <?php if (!empty($all_requests)): ?>
    <table class="admin-table">
      <thead>
        <tr>
          <th>Poster</th>
          <th>Mã & CLB</th>
          <th>Vị trí</th>
          <th>Thời gian</th>
          <th>Xét duyệt</th>
          <th>Thanh toán</th>
          <th>Bật/Tắt</th>
          <th style="text-align: right;">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($all_requests as $ad): ?>
          <?php
            $today = date('Y-m-d');
            $is_running = ($ad['approval_status'] === 'approved' && $ad['payment_status'] === 'paid' && $ad['is_enabled'] == 1 && $ad['start_date'] <= $today && $ad['end_date'] >= $today);
            $is_expired = ($ad['end_date'] < $today);
          ?>
          <tr>
            <td style="width: 70px;">
              <img src="<?= upload_url($ad['poster_image']) ?>" alt="" style="width: 60px; height: 34px; object-fit: cover; border-radius: 4px; border: 1px solid var(--admin-border);">
            </td>
            <td>
              <code><?= e($ad['tracking_code']) ?></code><br>
              <strong><?= e($ad['club_name']) ?></strong><br>
              <span style="font-size: 0.8rem; color: var(--admin-text-muted);"><?= e($ad['contact_name']) ?> - <?= e($ad['phone']) ?></span>
            </td>
            <td>
              <?= e($ad['placement_name']) ?>
            </td>
            <td>
              <?= format_date_vn($ad['start_date']) ?> &rarr; <?= format_date_vn($ad['end_date']) ?><br>
              <?php if ($is_running): ?>
                <span class="badge badge-open">🟢 Đang chạy</span>
              <?php elseif ($is_expired): ?>
                <span class="badge badge-closed">Đã hết hạn</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($ad['approval_status'] === 'approved'): ?>
                <span class="badge badge-open">Đã duyệt</span>
              <?php elseif ($ad['approval_status'] === 'rejected'): ?>
                <span class="badge badge-closed">Từ chối</span>
              <?php else: ?>
                <span class="badge badge-upcoming">Chờ duyệt</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($ad['payment_status'] === 'paid'): ?>
                <span style="color: var(--admin-success); font-weight: 700;">✓ Đã thanh toán</span>
              <?php elseif ($ad['payment_status'] === 'refunded'): ?>
                <span style="color: var(--admin-text-muted);">Đã hoàn tiền</span>
              <?php else: ?>
                <span style="color: var(--admin-warning); font-weight: 700;">Chưa trả</span>
              <?php endif; ?>
              <?php if ($ad['quote_amount'] > 0): ?>
                <div style="font-size: 0.8rem; color: var(--admin-text-muted);"><?= format_money_vn($ad['quote_amount']) ?></div>
              <?php endif; ?>
            </td>
            <td>
              <form action="<?= url('admin/ads.php') ?>" method="POST" style="display: inline;">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle_enabled">
                <input type="hidden" name="ad_id" value="<?= $ad['id'] ?>">
                <button type="submit" style="background: none; border: none; cursor: pointer; font-size: 0.82rem; font-weight: 700; color: <?= $ad['is_enabled'] ? 'var(--admin-success)' : 'var(--admin-danger)' ?>;" title="Bấm để bật hoặc tạm dừng">
                  <?= $ad['is_enabled'] ? 'Bật' : 'Tạm dừng' ?>
                </button>
              </form>
            </td>
            <td style="text-align: right;">
              <div class="action-buttons" style="justify-content: flex-end;">
                <a href="<?= url('admin/ads.php?edit_id=' . $ad['id']) ?>" class="btn-admin btn-admin-primary" style="padding: 4px 10px; font-size: 0.8rem;">
                  Xử lý
                </a>
                <form action="<?= url('admin/ads.php') ?>" method="POST" style="display: inline;" class="btn-confirm-delete" data-confirm="Bạn có chắc chắn muốn xóa yêu cầu quảng cáo này không?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="ad_id" value="<?= $ad['id'] ?>">
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
      Không có yêu cầu quảng cáo nào trong mục này.
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
