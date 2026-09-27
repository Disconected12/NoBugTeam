<?php
/**
 * ClubHub - Đăng Ký Thuê Vị Trí Quảng Bá Poster (Dành cho đại diện CLB)
 */

define('CLUBHUB_INIT', true);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';

$page_title = 'Đăng ký quảng bá';
$page_desc = 'Gửi yêu cầu thuê vị trí hiển thị poster quảng bá cho Câu lạc bộ trên các vị trí nổi bật của ClubHub.';

$db = get_db_connection();

// Lấy danh sách vị trí quảng cáo và giá niêm yết
$placements = [];
if ($db) {
    try {
        $pl_stmt = $db->query("SELECT * FROM ad_placements WHERE is_active = 1 ORDER BY price_per_day DESC");
        $placements = $pl_stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Lỗi tải vị trí quảng cáo: " . $e->getMessage());
    }
}

$last_booking = $_SESSION['last_ad_booking'] ?? null;
unset($_SESSION['last_ad_booking']);

$old = $_SESSION['old_ad_request'] ?? [];
unset($_SESSION['old_ad_request']);

include __DIR__ . '/includes/header.php';
?>

<main class="container" style="padding-top: 30px; padding-bottom: 60px;">

  <!-- Banner thông báo khi vừa gửi yêu cầu thành công -->
  <?php if (!empty($last_booking)): ?>
    <div style="max-width: 860px; margin: 0 auto 30px auto; background-color: var(--color-success-bg); border: 2px solid var(--color-success); border-radius: var(--radius-xl); padding: 30px; box-shadow: var(--shadow-md);">
      <div style="display: flex; gap: 16px; align-items: flex-start;">
        <div style="font-size: 2.2rem; line-height: 1;">✅</div>
        <div>
          <h2 style="font-size: 1.4rem; font-weight: 800; color: #065F46; margin-bottom: 8px;">
            Đã tiếp nhận yêu cầu thành công!
          </h2>
          <p style="color: #065F46; font-size: 0.95rem; line-height: 1.6; margin-bottom: 14px;">
            Quản trị viên sẽ liên hệ để xác nhận nội dung, lịch và chi phí trong vòng 24 giờ làm việc.
          </p>
          <div style="background-color: #FFFFFF; padding: 14px 18px; border-radius: 8px; border: 1px solid rgba(16, 185, 129, 0.4); display: inline-block;">
            <span style="font-size: 0.85rem; color: var(--color-text-muted);">Mã tra cứu yêu cầu của bạn:</span><br>
            <strong style="font-size: 1.25rem; color: var(--color-primary); letter-spacing: 1px;">
              <?= e($last_booking['tracking_code']) ?>
            </strong>
          </div>
          <p style="font-size: 0.85rem; color: #065F46; margin-top: 12px;">
            Vui lòng lưu lại mã này để đối chiếu khi ban quản trị liên hệ xác nhận và tiến hành thủ tục thanh toán.
          </p>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div class="booking-form-wrap">
    
    <div style="text-align: center; margin-bottom: 30px;">
      <span class="quiz-cta-badge">📢 Tiếp Cận Sinh Viên Hiệu Quả</span>
      <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--color-primary); margin-top: 8px;">
        Đăng Ký Thuê Vị Trí Quảng Bá Poster
      </h1>
      <p style="color: var(--color-text-muted); font-size: 0.95rem; max-width: 620px; margin: 8px auto 0 auto;">
        Dành cho đại diện Ban Chủ nhiệm các Câu lạc bộ muốn lan tỏa thông tin đợt tuyển thành viên hoặc sự kiện lớn đến toàn thể sinh viên trong trường.
      </p>
    </div>

    <!-- Quy trình 4 bước minh bạch -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 36px; text-align: center;">
      <div style="background-color: #F8FAFC; border: 1px solid var(--color-border); border-radius: 10px; padding: 14px 10px;">
        <div style="font-weight: 800; color: var(--color-accent); font-size: 1.1rem; margin-bottom: 4px;">1. Gửi form</div>
        <div style="font-size: 0.8rem; color: var(--color-text-muted);">Cung cấp poster & thông tin liên hệ</div>
      </div>
      <div style="background-color: #F8FAFC; border: 1px solid var(--color-border); border-radius: 10px; padding: 14px 10px;">
        <div style="font-weight: 800; color: var(--color-primary-light); font-size: 1.1rem; margin-bottom: 4px;">2. Báo giá</div>
        <div style="font-size: 0.8rem; color: var(--color-text-muted);">Admin kiểm tra lịch & gửi báo phí</div>
      </div>
      <div style="background-color: #F8FAFC; border: 1px solid var(--color-border); border-radius: 10px; padding: 14px 10px;">
        <div style="font-weight: 800; color: var(--color-primary-light); font-size: 1.1rem; margin-bottom: 4px;">3. Xác nhận</div>
        <div style="font-size: 0.8rem; color: var(--color-text-muted);">Xác nhận thanh toán thủ công</div>
      </div>
      <div style="background-color: #F8FAFC; border: 1px solid var(--color-border); border-radius: 10px; padding: 14px 10px;">
        <div style="font-weight: 800; color: var(--color-success); font-size: 1.1rem; margin-bottom: 4px;">4. Lên lịch</div>
        <div style="font-size: 0.8rem; color: var(--color-text-muted);">Poster tự động hiển thị đúng hẹn</div>
      </div>
    </div>

    <!-- Biểu mẫu Gửi Yêu Cầu -->
    <form action="<?= url('api/ad-request.php') ?>" method="POST" enctype="multipart/form-data">
      <?= csrf_field() ?>

      <!-- 1. Chọn vị trí muốn thuê -->
      <div style="margin-bottom: 24px;">
        <label class="form-label" style="font-size: 0.95rem; font-weight: 700; color: var(--color-primary); margin-bottom: 12px;">
          Chọn vị trí hiển thị mong muốn <span style="color:red;">*</span>
        </label>
        
        <div class="placement-options-grid">
          <?php foreach ($placements as $idx => $pl): ?>
            <?php $is_checked = ($old['placement_code'] ?? PLACEMENT_TOP_BANNER) === $pl['code']; ?>
            <label class="placement-card <?= $is_checked ? 'selected' : '' ?>" style="display: block; position: relative;">
              <input type="radio" name="placement_code" value="<?= e($pl['code']) ?>" <?= $is_checked ? 'checked' : '' ?> style="position: absolute; opacity: 0;" onchange="document.querySelectorAll('.placement-card').forEach(c => c.classList.remove('selected')); this.closest('.placement-card').classList.add('selected');">
              <div class="placement-card-name"><?= e($pl['name']) ?></div>
              <div class="placement-card-price"><?= format_money_vn($pl['price_per_day']) ?> / ngày</div>
              <p style="font-size: 0.8rem; color: var(--color-text-muted); margin-top: 6px; line-height: 1.4;">
                <?= e($pl['description']) ?>
              </p>
              <div style="margin-top: 8px; font-size: 0.75rem; color: var(--color-primary-light); font-weight: 700;">
                Tối đa <?= (int)$pl['max_slots'] ?> suất luân phiên
              </div>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- 2. Thông tin CLB & Người liên hệ -->
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
        <div>
          <label class="form-label" for="club_name">Tên Câu Lạc Bộ / Đơn vị <span style="color:red;">*</span></label>
          <input type="text" name="club_name" id="club_name" class="form-control" placeholder="Ví dụ: CLB Lập trình DevAI" value="<?= e($old['club_name'] ?? '') ?>" required>
        </div>
        <div>
          <label class="form-label" for="contact_name">Họ tên người liên hệ / Trưởng ban <span style="color:red;">*</span></label>
          <input type="text" name="contact_name" id="contact_name" class="form-control" placeholder="Nguyễn Văn A" value="<?= e($old['contact_name'] ?? '') ?>" required>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
        <div>
          <label class="form-label" for="email">Email liên hệ nhận phản hồi & báo giá <span style="color:red;">*</span></label>
          <input type="email" name="email" id="email" class="form-control" placeholder="clb@sinhvien.edu.vn" value="<?= e($old['email'] ?? '') ?>" required>
        </div>
        <div>
          <label class="form-label" for="phone">Số điện thoại liên hệ <span style="color:red;">*</span></label>
          <input type="tel" name="phone" id="phone" class="form-control" placeholder="0901234567" value="<?= e($old['phone'] ?? '') ?>" required>
        </div>
      </div>

      <!-- 3. Liên kết đích & Thời gian chạy -->
      <div style="margin-bottom: 20px;">
        <label class="form-label" for="destination_url">Liên kết đích khi người dùng bấm vào poster <span style="color:red;">*</span></label>
        <input type="url" name="destination_url" id="destination_url" class="form-control" placeholder="https://facebook.com/... hoặc https://forms.gle/..." value="<?= e($old['destination_url'] ?? '') ?>" required>
        <span style="font-size: 0.8rem; color: var(--color-text-muted);">
          Chỉ chấp nhận liên kết an toàn bắt đầu bằng <code>http://</code> hoặc <code>https://</code>.
        </span>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
        <div>
          <label class="form-label" for="start_date">Ngày bắt đầu hiển thị <span style="color:red;">*</span></label>
          <input type="date" name="start_date" id="start_date" class="form-control" min="<?= date('Y-m-d') ?>" value="<?= e($old['start_date'] ?? date('Y-m-d')) ?>" required>
        </div>
        <div>
          <label class="form-label" for="end_date">Ngày kết thúc hiển thị <span style="color:red;">*</span></label>
          <input type="date" name="end_date" id="end_date" class="form-control" min="<?= date('Y-m-d') ?>" value="<?= e($old['end_date'] ?? date('Y-m-d', strtotime('+7 days'))) ?>" required>
        </div>
      </div>

      <!-- 4. Tải lên file Poster -->
      <div style="margin-bottom: 20px;">
        <label class="form-label" for="poster">File hình ảnh poster quảng cáo <span style="color:red;">*</span></label>
        <input type="file" name="poster" id="poster" class="form-control" accept="image/png,image/jpeg,image/webp" required>
        <span style="font-size: 0.8rem; color: var(--color-text-muted);">
          Chấp nhận JPG, PNG, WEBP. Dung lượng tối đa 2MB. Tỷ lệ khuyến nghị 16:9 hoặc 21:9 đối với banner đầu trang.
        </span>
      </div>

      <!-- 5. Ghi chú thêm -->
      <div style="margin-bottom: 20px;">
        <label class="form-label" for="notes">Ghi chú hoặc yêu cầu đặc biệt (nếu có)</label>
        <textarea name="notes" id="notes" rows="3" class="form-control" placeholder="Ví dụ: Mong muốn hiển thị trước ngày Khai mạc 3 ngày..."><?= e($old['notes'] ?? '') ?></textarea>
      </div>

      <!-- 6. Xác nhận điều khoản -->
      <div style="margin-bottom: 30px; background-color: #F8FAFC; border-radius: 8px; padding: 16px; border: 1px solid var(--color-border);">
        <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; font-size: 0.9rem; color: var(--color-text-main);">
          <input type="checkbox" name="consent_agreed" value="1" <?= !empty($old['consent_agreed']) ? 'checked' : '' ?> required style="margin-top: 3px;">
          <span>
            Tôi xác nhận thông tin cung cấp là chính xác và đồng ý để Ban Quản trị ClubHub liên hệ qua email / số điện thoại nhằm xác nhận nội dung, đối chiếu lịch trống và hoàn tất thủ tục thanh toán.
          </span>
        </label>
      </div>

      <div style="text-align: center;">
        <button type="submit" class="btn btn-primary btn-lg" style="min-width: 240px;">
          Gửi yêu cầu quảng bá &rarr;
        </button>
      </div>

    </form>

  </div>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
