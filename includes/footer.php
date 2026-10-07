<?php
/**
 * ClubHub - Footer dùng chung cho các trang người dùng
 */

if (!defined('CLUBHUB_INIT')) {
    define('CLUBHUB_INIT', true);
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

$site_name = get_setting('site_name', 'ClubHub');
$contact_email = get_setting('contact_email', 'clb.contact@sinhvien.edu.vn');
$contact_phone = get_setting('contact_phone', '028 3829 5678');
$contact_address = get_setting('contact_address', 'Phòng Công tác Sinh viên, Trường Đại học');
$intro_text = get_setting('intro_text', 'Nền tảng kết nối và khám phá các Câu lạc bộ, Đội, Nhóm sinh viên trong trường đại học.');
?>

  <!-- Chân trang (Site Footer) -->
  <footer class="site-footer">
    <div class="container">
      <div class="footer-grid">
        <!-- Cột 1: Thông tin thương hiệu -->
        <div class="footer-col">
          <a href="<?= url('/') ?>" class="brand-logo" style="margin-bottom: 16px; display: flex; align-items: center; gap: 10px; text-decoration: none;">
            <img src="<?= asset('images/dimension-logo.png') ?>" alt="Demension Logo" style="width: 36px; height: 36px; border-radius: 8px;">
            <span class="brand-text" style="font-weight: 800; font-size: 1.25rem; color: #fff;">Demension</span>
          </a>
          <p style="line-height: 1.6; margin-bottom: 16px; font-size: 0.9rem; color: #cbd5e1;">
            Không gian kết nối đa chiều, khám phá và tỏa sáng cùng các Câu lạc bộ, Đội, Nhóm sinh viên.
          </p>
          <div style="font-size: 0.85rem; color: #94A3B8;">
            © <?= date('Y') ?> Demension Hub. Phát triển bởi Nhóm Demension.
          </div>
        </div>

        <!-- Cột 2: Khám phá nhanh -->
        <div class="footer-col">
          <h4 class="footer-title">Khám Phá</h4>
          <ul class="footer-links">
            <li><a href="<?= url('clubs.php') ?>">Tất cả Câu lạc bộ</a></li>
            <li><a href="<?= url('clubs.php?status=open') ?>">CLB đang mở tuyển</a></li>
            <li><a href="<?= url('quiz.php') ?>">Trắc nghiệm chọn CLB</a></li>
            <li><a href="<?= url('events.php') ?>">Lịch sự kiện sắp tới</a></li>
            <li><a href="<?= url('club-portal/login.php') ?>" style="color: #F6C453; font-weight: 700;">Cổng Quản Lý CLB</a></li>
            <li><a href="<?= url('admin/login.php') ?>">Quản trị viên (Super Admin)</a></li>
          </ul>
        </div>

        <!-- Cột 3: Hợp tác & Quy định -->
        <div class="footer-col">
          <h4 class="footer-title">Hợp Tác & Hỗ Trợ</h4>
          <ul class="footer-links">
            <li><a href="<?= url('ads-booking.php') ?>">Đăng ký thuê vị trí poster</a></li>
            <li><a href="#" class="btn-open-report-modal" data-entity-name="Chung toàn trang">Báo thông tin sai / link hỏng</a></li>
            <li><a href="#privacyPolicyModal" onclick="document.querySelector('#privacyModal').classList.add('active'); return false;">Chính sách quyền riêng tư</a></li>
          </ul>
        </div>

        <!-- Cột 4: Liên hệ -->
        <div class="footer-col">
          <h4 class="footer-title">Thông Tin Liên Hệ</h4>
          <ul class="footer-links" style="gap: 12px;">
            <li><strong>Địa chỉ:</strong> <?= e($contact_address) ?></li>
            <li><strong>Email:</strong> <a href="mailto:<?= e($contact_email) ?>"><?= e($contact_email) ?></a></li>
            <li><strong>Hotline:</strong> <a href="tel:<?= e($contact_phone) ?>"><?= e($contact_phone) ?></a></li>
          </ul>
        </div>
      </div>

      <div class="footer-bottom">
        <div>
          Cam kết dữ liệu minh bạch, trung thực, hỗ trợ môi trường sinh viên văn minh.
        </div>
        <div>
          Thiết kế tối ưu cho máy tính và thiết bị di động.
        </div>
      </div>
    </div>
  </footer>

  <!-- Modal Báo Cáo Thông Tin Sai / Link Hỏng -->
  <div class="modal-backdrop" id="reportModal" role="dialog" aria-modal="true" aria-labelledby="reportModalTitle">
    <div class="modal-dialog">
      <div class="modal-header">
        <h3 class="modal-title" id="reportModalTitle">Báo Thông Tin Sai Hoặc Link Hỏng</h3>
        <button type="button" class="modal-close" aria-label="Đóng">&times;</button>
      </div>
      <form action="<?= url('api/report-submit.php') ?>" method="POST" id="reportForm">
        <?= csrf_field() ?>
        <input type="hidden" name="club_id" value="">
        <input type="hidden" name="event_id" value="">
        <div class="modal-body">
          <p id="reportTargetName" style="font-weight: 700; color: var(--color-primary); margin-bottom: 12px;"></p>
          <div style="margin-bottom: 14px;">
            <label class="form-label" for="report_type">Loại vấn đề <span style="color:red;">*</span></label>
            <select name="report_type" id="report_type" class="form-control" required>
              <option value="wrong_info">Thông tin CLB/sự kiện chưa chính xác</option>
              <option value="broken_link">Link đăng ký / Fanpage bị hỏng</option>
              <option value="other">Ý kiến đóng góp khác</option>
            </select>
          </div>
          <div style="margin-bottom: 14px;">
            <label class="form-label" for="reporter_name">Họ và tên của bạn</label>
            <input type="text" name="reporter_name" id="reporter_name" class="form-control" placeholder="Nguyễn Văn A">
          </div>
          <div style="margin-bottom: 14px;">
            <label class="form-label" for="reporter_email">Email liên hệ (nếu cần phản hồi)</label>
            <input type="email" name="reporter_email" id="reporter_email" class="form-control" placeholder="sinhvien@edu.vn">
          </div>
          <div>
            <label class="form-label" for="details">Mô tả chi tiết sai sót <span style="color:red;">*</span></label>
            <textarea name="details" id="details" rows="4" class="form-control" placeholder="Vui lòng nêu rõ thông tin cần đính chính..." required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline btn-modal-cancel">Hủy</button>
          <button type="submit" class="btn btn-primary">Gửi phản ánh</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal Chính Sách Quyền Riêng Tư -->
  <div class="modal-backdrop" id="privacyModal" role="dialog" aria-modal="true" aria-labelledby="privacyModalTitle">
    <div class="modal-dialog">
      <div class="modal-header">
        <h3 class="modal-title" id="privacyModalTitle">Chính Sách Quyền Riêng Tư</h3>
        <button type="button" class="modal-close" onclick="document.querySelector('#privacyModal').classList.remove('active');" aria-label="Đóng">&times;</button>
      </div>
      <div class="modal-body" style="font-size: 0.95rem; line-height: 1.6;">
        <p style="margin-bottom: 12px;">
          <?= nl2br(e(get_setting('privacy_policy', 'ClubHub cam kết bảo mật thông tin cá nhân của bạn theo đúng quy định.'))) ?>
        </p>
        <p style="color: var(--color-text-muted);">
          Bài trắc nghiệm định hướng (Quiz) hoàn toàn ẩn danh, không yêu cầu đăng nhập và không lưu trữ câu trả lời cá nhân của bạn dài hạn khi chưa có sự đồng ý.
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" onclick="document.querySelector('#privacyModal').classList.remove('active');">Đã hiểu</button>
      </div>
    </div>
  </div>

  <!-- Lightbox Modal Xem Ảnh Lớn -->
  <div class="lightbox-modal" id="lightboxModal" role="dialog" aria-modal="true">
    <div class="lightbox-content">
      <button type="button" class="lightbox-close" aria-label="Đóng ảnh">&times;</button>
      <img src="" id="lightboxImage" alt="Chi tiết ảnh">
      <div class="lightbox-caption" id="lightboxCaption"></div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="<?= asset('js/main.js') ?>?v=<?= filemtime(__DIR__ . '/../assets/js/main.js') ?>"></script>
  <?php if (!empty($extra_scripts)): ?>
    <?php foreach ($extra_scripts as $script): ?>
      <script src="<?= asset('js/' . $script) ?>"></script>
    <?php endforeach; ?>
  <?php endif; ?>

</body>
</html>
