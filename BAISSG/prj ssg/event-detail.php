<?php
/**
 * ClubHub - Chi Tiết Sự Kiện
 */

define('CLUBHUB_INIT', true);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$db = get_db_connection();
if (!$db) {
    flash_set('error', 'Lỗi kết nối cơ sở dữ liệu.');
    redirect('events.php');
}

$slug = trim($_GET['slug'] ?? '');
$id = (int)($_GET['id'] ?? 0);

if (empty($slug) && $id <= 0) {
    flash_set('error', 'Không tìm thấy sự kiện.');
    redirect('events.php');
}

$event = null;
try {
    if (!empty($slug)) {
        $stmt = $db->prepare("
            SELECT e.*, c.name as club_name, c.short_name as club_short_name, c.slug as club_slug, c.logo as club_logo
            FROM events e
            JOIN clubs c ON e.club_id = c.id
            WHERE e.slug = ? AND e.is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$slug]);
    } else {
        $stmt = $db->prepare("
            SELECT e.*, c.name as club_name, c.short_name as club_short_name, c.slug as club_slug, c.logo as club_logo
            FROM events e
            JOIN clubs c ON e.club_id = c.id
            WHERE e.id = ? AND e.is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$id]);
    }
    $event = $stmt->fetch();
} catch (Exception $e) {
    error_log("Lỗi tải chi tiết sự kiện: " . $e->getMessage());
}

if (!$event) {
    flash_set('error', 'Sự kiện không tồn tại hoặc đã bị ẩn.');
    redirect('events.php');
}

$page_title = $event['title'];
$page_desc = mb_substr(strip_tags($event['description']), 0, 160, 'UTF-8');

$now = new DateTime();
$start_dt = new DateTime($event['start_time']);
$end_dt = new DateTime($event['end_time']);

$is_ended = $now > $end_dt;
$is_happening = ($now >= $start_dt && $now <= $end_dt);

include __DIR__ . '/includes/header.php';
?>

<main class="container" style="padding-top: 30px; padding-bottom: 60px;">

  <div class="detail-grid">
    
    <!-- Cột chính: Poster & Chi tiết chương trình -->
    <div class="detail-main-column">
      
      <div class="detail-main-card" style="padding: 0; overflow: hidden;">
        <div style="aspect-ratio: 16/9; background-color: var(--color-primary); overflow: hidden;">
          <img src="<?= upload_url($event['poster_image']) ?>" alt="<?= e($event['title']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
        </div>

        <div style="padding: 30px;">
          <!-- Trạng thái diễn ra -->
          <div style="margin-bottom: 12px; display: flex; gap: 8px;">
            <?php if ($is_ended): ?>
              <span class="badge badge-closed">Sự kiện đã kết thúc</span>
            <?php elseif ($is_happening): ?>
              <span class="badge badge-open">Đang diễn ra</span>
            <?php else: ?>
              <span class="badge badge-upcoming">Sắp diễn ra</span>
            <?php endif; ?>
          </div>

          <h1 style="font-size: 1.7rem; font-weight: 800; color: var(--color-primary); margin-bottom: 12px; line-height: 1.35;">
            <?= e($event['title']) ?>
          </h1>

          <div style="font-size: 0.9rem; color: var(--color-text-muted); margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--color-border); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
            <span>
              Đơn vị tổ chức: 
              <a href="<?= url('club-detail.php?slug=' . e($event['club_slug'])) ?>" style="font-weight: 700; color: var(--color-accent);">
                <?= e($event['club_name']) ?>
              </a>
            </span>
            <button type="button" class="btn btn-outline btn-sm btn-open-report-modal" data-event-id="<?= $event['id'] ?>" data-entity-name="<?= e($event['title']) ?>">
              ⚠️ Báo sai sót sự kiện
            </button>
          </div>

          <!-- Nội dung chi tiết chương trình -->
          <h3 class="card-heading">📋 Nội Dung Chương Trình</h3>
          <div style="font-size: 1rem; line-height: 1.7; color: var(--color-text-main);">
            <?= nl2br(e($event['description'])) ?>
          </div>

          <!-- Nút đăng ký tham gia -->
          <?php if (!$is_ended && !empty($event['registration_link'])): ?>
            <div style="margin-top: 32px; padding: 20px; background-color: var(--color-bg-light); border-radius: var(--radius-lg); text-align: center;">
              <h4 style="font-size: 1.1rem; color: var(--color-primary); margin-bottom: 8px; font-weight: 700;">
                Đăng Ký Tham Gia Ngay
              </h4>
              <p style="font-size: 0.9rem; color: var(--color-text-muted); margin-bottom: 16px;">
                Vui lòng điền thông tin để ban tổ chức chuẩn bị chỗ ngồi và tài liệu chu đáo nhất.
              </p>
              <a href="<?= safe_url($event['registration_link']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-lg" onclick="alert('Biểu mẫu đăng ký được mở qua liên kết bên ngoài.');">
                Mở đơn đăng ký sự kiện &rarr;
              </a>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>

    <!-- Cột phụ: Thông tin thời gian & địa điểm -->
    <div class="detail-side-column">
      
      <div class="detail-side-card">
        <h3 class="card-heading">⏰ Thời Gian & Địa Điểm</h3>
        <ul class="info-list">
          <li class="info-item">
            <div class="info-icon">🕒</div>
            <div class="info-content">
              <div class="info-label">Bắt đầu</div>
              <div class="info-value"><?= format_date_vn($event['start_time'], true) ?></div>
            </div>
          </li>
          <li class="info-item">
            <div class="info-icon">🏁</div>
            <div class="info-content">
              <div class="info-label">Kết thúc</div>
              <div class="info-value"><?= format_date_vn($event['end_time'], true) ?></div>
            </div>
          </li>
          <li class="info-item">
            <div class="info-icon">📍</div>
            <div class="info-content">
              <div class="info-label">Địa điểm tổ chức</div>
              <div class="info-value"><?= e($event['location']) ?></div>
            </div>
          </li>
          <li class="info-item">
            <div class="info-icon">👥</div>
            <div class="info-content">
              <div class="info-label">Đối tượng tham gia</div>
              <div class="info-value"><?= !empty($event['target_audience']) ? e($event['target_audience']) : 'Mọi sinh viên quan tâm' ?></div>
            </div>
          </li>
          <li class="info-item">
            <div class="info-icon">💰</div>
            <div class="info-content">
              <div class="info-label">Chi phí tham gia</div>
              <div class="info-value"><strong><?= format_money_vn($event['fee']) ?></strong></div>
            </div>
          </li>
          <?php if (!empty($event['registration_deadline'])): ?>
            <li class="info-item">
              <div class="info-icon">⏳</div>
              <div class="info-content">
                <div class="info-label">Hạn chót đăng ký</div>
                <div class="info-value"><?= format_date_vn($event['registration_deadline'], true) ?></div>
              </div>
            </li>
          <?php endif; ?>
          <?php if (!empty($event['contact_info'])): ?>
            <li class="info-item">
              <div class="info-icon">📞</div>
              <div class="info-content">
                <div class="info-label">Đầu mối liên hệ</div>
                <div class="info-value"><?= e($event['contact_info']) ?></div>
              </div>
            </li>
          <?php endif; ?>
        </ul>
      </div>

      <!-- Khối câu lạc bộ tổ chức -->
      <div class="detail-side-card">
        <h3 class="card-heading">🏛️ Đơn Vị Tổ Chức</h3>
        <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 14px;">
          <img src="<?= upload_url($event['club_logo'], 'default-club.png') ?>" alt="<?= e($event['club_name']) ?>" style="width: 50px; height: 50px; border-radius: 8px; border: 1px solid var(--color-border); object-fit: cover;">
          <div>
            <h4 style="font-size: 1rem; font-weight: 700; color: var(--color-primary); margin-bottom: 2px;">
              <?= e($event['club_name']) ?>
            </h4>
            <span style="font-size: 0.85rem; color: var(--color-text-muted);"><?= e($event['club_short_name']) ?></span>
          </div>
        </div>
        <a href="<?= url('club-detail.php?slug=' . e($event['club_slug'])) ?>" class="btn btn-outline btn-block btn-sm">
          Xem thông tin CLB này &rarr;
        </a>
      </div>

    </div>

  </div>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
