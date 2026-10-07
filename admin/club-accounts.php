<?php
/**
 * Demension Super Admin - Quản Lý & Cấp Tài Khoản Cho Các CLB (Club Accounts Manager)
 */

define('CLUBHUB_INIT', true);
$page_title = 'Quản Lý Tài Khoản CLB';
require_once __DIR__ . '/includes/admin_header.php';

$errors = [];
$success_msg = '';

// Xử lý Đặt lại mật khẩu cho CLB
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    csrf_protect();
    $account_id = (int)$_POST['account_id'];
    $new_password = trim($_POST['new_password'] ?? '');

    if (empty($new_password) || strlen($new_password) < 6) {
        $errors[] = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
    } else {
        try {
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $upd = $db->prepare("UPDATE club_accounts SET password_hash = ? WHERE id = ?");
            $upd->execute([$hash, $account_id]);
            flash_set('success', '✨ Đã đặt lại mật khẩu cho tài khoản CLB thành công!');
            redirect('admin/club-accounts.php');
        } catch (Exception $e) {
            $errors[] = 'Lỗi cơ sở dữ liệu: ' . $e->getMessage();
        }
    }
}

// Xử lý Bật/Tắt kích hoạt tài khoản
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_active') {
    csrf_protect();
    $account_id = (int)$_POST['account_id'];
    $status = (int)$_POST['status'];
    try {
        $upd = $db->prepare("UPDATE club_accounts SET is_active = ? WHERE id = ?");
        $upd->execute([$status, $account_id]);
        flash_set('success', 'Đã cập nhật trạng thái tài khoản.');
        redirect('admin/club-accounts.php');
    } catch (Exception $e) {
        $errors[] = 'Lỗi: ' . $e->getMessage();
    }
}

// Lấy danh sách tài khoản CLB kèm thông tin CLB
$accounts = [];
if ($db) {
    $stmt = $db->query("
        SELECT ca.*, c.name as club_name, c.slug as club_slug, c.logo as club_logo
        FROM club_accounts ca
        JOIN clubs c ON ca.club_id = c.id
        ORDER BY ca.id ASC
    ");
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="admin-content-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
  <div>
    <h1 style="color: #fff; font-size: 1.6rem; font-weight: 800; display: flex; align-items: center; gap: 10px;">
      <span>🔑</span>
      <span>Phân Quyền & Quản Lý Tài Khoản Câu Lạc Bộ</span>
    </h1>
    <p style="color: #94a3b8; font-size: 0.9rem; margin-top: 4px;">
      Cấp quyền và quản lý tài khoản để các Câu lạc bộ tự đăng nhập vào <strong>Club Portal</strong> tự quản lý sự kiện, đợt tuyển và màu sắc trang cá nhân.
    </p>
  </div>
  <a href="<?= url('club-portal/login.php') ?>" target="_blank" class="btn btn-outline" style="border-color: #F6C453; color: #F6C453; font-weight: 700;">
    ↗ Mở Cổng Đăng Nhập CLB (Club Portal)
  </a>
</div>

<?php if (!empty($errors)): ?>
  <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4); color: #fca5a5; padding: 14px 18px; border-radius: 12px; margin-bottom: 1.5rem;">
    <strong>⚠️ Có lỗi xảy ra:</strong>
    <ul style="margin-left: 20px; margin-top: 6px;">
      <?php foreach ($errors as $er): ?>
        <li><?= e($er) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="admin-card" style="background: rgba(13, 27, 65, 0.7); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 1.75rem;">
  <div style="overflow-x: auto;">
    <table class="table" style="width: 100%; border-collapse: collapse; color: #cbd5e1;">
      <thead>
        <tr style="border-bottom: 1px solid rgba(255,255,255,0.1); text-align: left; font-size: 0.85rem; color: #94a3b8;">
          <th style="padding: 12px 10px;">CÂU LẠC BỘ</th>
          <th style="padding: 12px 10px;">TÀI KHOẢN (USERNAME)</th>
          <th style="padding: 12px 10px;">EMAIL ĐẠI DIỆN</th>
          <th style="padding: 12px 10px;">ĐĂNG NHẬP GẦN NHẤT</th>
          <th style="padding: 12px 10px;">TRẠNG THÁI</th>
          <th style="padding: 12px 10px; text-align: right;">THAO TÁC / ĐỔI MẬT KHẨU</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($accounts as $acc): ?>
          <tr style="border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.9rem;">
            <td style="padding: 12px 10px;">
              <div style="display: flex; align-items: center; gap: 10px;">
                <img src="<?= !empty($acc['club_logo']) ? asset($acc['club_logo']) : asset('images/dimension-logo.png') ?>" alt="Logo" style="width: 38px; height: 38px; border-radius: 8px; object-fit: cover; border: 1px solid #F6C453;">
                <div>
                  <strong style="color: #fff; display: block;"><?= e($acc['club_name']) ?></strong>
                  <small style="color: #94a3b8;">ID: #<?= $acc['club_id'] ?></small>
                </div>
              </div>
            </td>
            <td style="padding: 12px 10px;">
              <code style="background: rgba(0,0,0,0.4); padding: 4px 8px; border-radius: 6px; color: #F6C453; font-weight: bold;">
                <?= e($acc['username']) ?>
              </code>
            </td>
            <td style="padding: 12px 10px; color: #94a3b8; font-size: 0.85rem;">
              <?= e($acc['email']) ?>
            </td>
            <td style="padding: 12px 10px; font-size: 0.85rem; color: #cbd5e1;">
              <?= !empty($acc['last_login']) ? date('d/m/Y H:i', strtotime($acc['last_login'])) : '<span style="color: #64748b;">Chưa đăng nhập</span>' ?>
            </td>
            <td style="padding: 12px 10px;">
              <?php if ($acc['is_active']): ?>
                <span style="color: #10B981; background: rgba(16,185,129,0.15); padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: bold;">ĐANG HOẠT ĐỘNG</span>
              <?php else: ?>
                <span style="color: #f87171; background: rgba(239,68,68,0.15); padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: bold;">ĐÃ KHÓA</span>
              <?php endif; ?>
            </td>
            <td style="padding: 12px 10px; text-align: right;">
              <!-- Form Đổi mật khẩu nhanh -->
              <form method="POST" action="" style="display: inline-flex; align-items: center; gap: 6px;">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="account_id" value="<?= $acc['id'] ?>">
                <input type="text" name="new_password" placeholder="MK mới..." style="padding: 6px 10px; background: rgba(6,16,43,0.8); border: 1px solid rgba(255,255,255,0.2); border-radius: 6px; color: #fff; font-size: 0.8rem; width: 100px;">
                <button type="submit" class="btn btn-sm" style="background: #2563EB; color: #fff; border: none; padding: 6px 10px; border-radius: 6px; font-size: 0.8rem; cursor: pointer;">
                  Đổi MK
                </button>
              </form>

              <!-- Form Bật/Tắt kích hoạt -->
              <form method="POST" action="" style="display: inline-block; margin-left: 6px;">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle_active">
                <input type="hidden" name="account_id" value="<?= $acc['id'] ?>">
                <input type="hidden" name="status" value="<?= $acc['is_active'] ? '0' : '1' ?>">
                <button type="submit" style="background: none; border: 1px solid <?= $acc['is_active'] ? '#f87171' : '#10B981' ?>; color: <?= $acc['is_active'] ? '#f87171' : '#10B981' ?>; padding: 5px 8px; border-radius: 6px; font-size: 0.8rem; cursor: pointer;">
                  <?= $acc['is_active'] ? 'Khóa' : 'Mở' ?>
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>