<?php
/**
 * Demension - Quản Lý Đợt Tuyển Thành Viên (Club Recruitment Manager)
 */

define('CLUBHUB_INIT', true);
$page_title = 'Quản Lý Đợt Tuyển Thành Viên';
require_once __DIR__ . '/includes/header.php';

$club_id = current_club_id();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_protect();

    $recruitment_status = $_POST['recruitment_status'] ?? 'closed';
    if (!in_array($recruitment_status, ['open', 'upcoming', 'closed'])) {
        $recruitment_status = 'closed';
    }

    $recruitment_deadline = !empty($_POST['recruitment_deadline']) ? $_POST['recruitment_deadline'] : null;
    $application_link = trim($_POST['application_link'] ?? '');
    $recruitment_process = trim($_POST['recruitment_process'] ?? '');

    // Xác thực an toàn cho đường link Google Form / MS Form
    if (!empty($application_link) && !filter_var($application_link, FILTER_VALIDATE_URL)) {
        $errors[] = 'Đường link nhận đơn tuyển không đúng định dạng URL hợp lệ (Phải bắt đầu bằng http:// hoặc https://).';
    }

    if (empty($errors)) {
        try {
            $stmt = $db->prepare("
                UPDATE clubs SET
                    recruitment_status = ?,
                    recruitment_deadline = ?,
                    application_link = ?,
                    recruitment_process = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $recruitment_status,
                $recruitment_deadline,
                $application_link,
                $recruitment_process,
                $club_id
            ]);

            flash_set('success', '✨ Đã lưu thông tin đợt tuyển thành viên thành công!');
            redirect('club-portal/recruitment.php');
        } catch (Exception $e) {
            $errors[] = 'Lỗi cập nhật CSDL: ' . $e->getMessage();
        }
    }
}
?>

<form method="POST" action="">
  <?= csrf_field() ?>

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

  <div class="cp-card">
    <div class="cp-card-header">
      <div class="cp-card-title">
        <span>📢</span>
        <span>Thiết Lập Trạng Thái Đợt Tuyển & Đơn Ứng Tuyển</span>
      </div>
      <span class="badge-star">TUYỂN THÀNH VIÊN</span>
    </div>

    <p style="color: #cbd5e1; font-size: 0.9rem; margin-bottom: 1.5rem;">
      Khi bạn chuyển trạng thái sang <strong>"Đang mở đợt tuyển"</strong>, CLB của bạn sẽ tự động xuất hiện tại khu vực <em>"CLB Đang Tuyển Thành Viên"</em> trên Trang Chủ Demension. Khi hết hạn chót, hệ thống sẽ tự động chuyển về trạng thái hết hạn để bảo vệ quyền lợi sinh viên.
    </p>

    <!-- Chọn trạng thái đợt tuyển -->
    <div style="margin-bottom: 1.5rem;">
      <label style="display: block; font-weight: 700; color: #fff; margin-bottom: 8px;">1. Trạng Thái Hiện Tại *</label>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
        <label style="display: flex; align-items: center; gap: 12px; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); padding: 14px; border-radius: 12px; cursor: pointer;">
          <input type="radio" name="recruitment_status" value="open" <?= ($club_data['recruitment_status'] === 'open') ? 'checked' : '' ?> style="width: 18px; height: 18px;">
          <div>
            <strong style="color: #10B981; display: block;">🟢 Đang mở đợt tuyển</strong>
            <small style="color: #94a3b8; font-size: 0.8rem;">Đang nhận đơn ứng tuyển</small>
          </div>
        </label>

        <label style="display: flex; align-items: center; gap: 12px; background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); padding: 14px; border-radius: 12px; cursor: pointer;">
          <input type="radio" name="recruitment_status" value="upcoming" <?= ($club_data['recruitment_status'] === 'upcoming') ? 'checked' : '' ?> style="width: 18px; height: 18px;">
          <div>
            <strong style="color: #F59E0B; display: block;">🟡 Sắp mở đợt tuyển</strong>
            <small style="color: #94a3b8; font-size: 0.8rem;">Chuẩn bị mở trong vài ngày tới</small>
          </div>
        </label>

        <label style="display: flex; align-items: center; gap: 12px; background: rgba(148, 163, 184, 0.1); border: 1px solid rgba(148, 163, 184, 0.3); padding: 14px; border-radius: 12px; cursor: pointer;">
          <input type="radio" name="recruitment_status" value="closed" <?= ($club_data['recruitment_status'] === 'closed') ? 'checked' : '' ?> style="width: 18px; height: 18px;">
          <div>
            <strong style="color: #94A3B8; display: block;">⚪ Đã đóng đơn</strong>
            <small style="color: #94a3b8; font-size: 0.8rem;">Không nhận thêm thành viên</small>
          </div>
        </label>
      </div>
    </div>

    <!-- Hạn chót và link Google Form -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
      <div>
        <label style="display: block; font-weight: 700; color: #fff; margin-bottom: 6px;">2. Hạn chót nộp hồ sơ (Deadline)</label>
        <?php
          $raw_dl = $club_data['recruitment_deadline'] ?? '';
          $dl_val = !empty($raw_dl) ? date('Y-m-d\TH:i', strtotime($raw_dl)) : '';
        ?>
        <input type="datetime-local" name="recruitment_deadline" class="form-control" value="<?= e($dl_val) ?>">
        <small style="color: #94a3b8; font-size: 0.8rem;">Hệ thống tự động so khớp để đổi sang trạng thái hết hạn</small>
      </div>

      <div>
        <label style="display: block; font-weight: 700; color: #fff; margin-bottom: 6px;">3. Đường link nhận đơn (Google Form / MS Forms)</label>
        <input type="url" name="application_link" class="form-control" value="<?= e($club_data['application_link'] ?? '') ?>" placeholder="https://docs.google.com/forms/d/e/.../viewform">
        <small style="color: #94a3b8; font-size: 0.8rem;">Sinh viên bấm nút "Đăng Ký Ứng Tuyển" sẽ được chuyển hướng tới link này</small>
      </div>
    </div>

    <!-- Quy trình tuyển chọn -->
    <div class="form-group" style="margin-bottom: 1.5rem;">
      <label style="display: block; font-weight: 700; color: #fff; margin-bottom: 6px;">4. Quy trình tuyển chọn (Recruitment Process)</label>
      <textarea name="recruitment_process" class="form-control" rows="4" placeholder="VD: Vòng 1: Đơn đăng ký trực tuyến (CV/Portfolio) -> Vòng 2: Phỏng vấn trực tiếp -> Vòng 3: Thử thách Teamwork"><?= e($club_data['recruitment_process'] ?? '') ?></textarea>
      <small style="color: #94a3b8; font-size: 0.8rem;">Mô tả các vòng thi để ứng viên chuẩn bị kỹ càng</small>
    </div>

    <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #F6C453, #D97706); color: #06102B; font-weight: 800; padding: 12px 28px; border-radius: 12px; font-size: 1rem; border: none; cursor: pointer; box-shadow: 0 4px 20px rgba(246,196,83,0.4);">
      📢 Cập Nhật Thông Tin Đợt Tuyển
    </button>
  </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>