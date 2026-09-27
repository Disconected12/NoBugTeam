<?php
/**
 * ClubHub - Danh Sách Sự Kiện (Sắp diễn ra & Đã kết thúc)
 */

define('CLUBHUB_INIT', true);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$db = get_db_connection();

$page_title = 'Sự kiện sinh viên';
$page_desc = 'Tổng hợp các sự kiện, workshop, giải đấu và đêm nhạc do các Câu lạc bộ sinh viên tổ chức.';

$tab = trim($_GET['tab'] ?? 'upcoming'); // 'upcoming' hoặc 'past'

$upcoming_events = [];
$past_events = [];

if ($db) {
    try {
        // Sự kiện sắp diễn ra (end_time >= NOW())
        $up_stmt = $db->query("
            SELECT e.*, c.name as club_name, c.short_name as club_short_name, c.slug as club_slug
            FROM events e
            JOIN clubs c ON e.club_id = c.id
            WHERE e.is_active = 1 AND e.end_time >= NOW()
            ORDER BY e.start_time ASC
        ");
        $upcoming_events = $up_stmt->fetchAll();

        // Sự kiện đã kết thúc (end_time < NOW())
        $past_stmt = $db->query("
            SELECT e.*, c.name as club_name, c.short_name as club_short_name, c.slug as club_slug
            FROM events e
            JOIN clubs c ON e.club_id = c.id
            WHERE e.is_active = 1 AND e.end_time < NOW()
            ORDER BY e.end_time DESC
        ");
        $past_events = $past_stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Lỗi tải sự kiện: " . $e->getMessage());
    }
}

$display_events = ($tab === 'past') ? $past_events : $upcoming_events;

include __DIR__ . '/includes/header.php';
?>

<main class="container" style="padding-top: 30px; padding-bottom: 60px;">

  <!-- Tiêu đề & Tab chuyển đổi -->
  <div class="section-header" style="flex-direction: column; align-items: flex-start; gap: 16px;">
    <div class="section-title-wrap">
      <span class="section-tag">Hoạt Động Trải Nghiệm</span>
      <h1 class="section-title">Lịch Sự Kiện Câu Lạc Bộ</h1>
      <p class="section-subtitle">
        Khám phá các buổi hội thảo, đêm nhạc, ngày hội tuyển sinh và giải thi đấu sôi động
      </p>
    </div>

    <!-- Tabs Sắp diễn ra vs Đã kết thúc -->
    <div style="display: flex; gap: 8px; border-bottom: 2px solid var(--color-border); width: 100%; padding-bottom: 2px;">
      <a href="<?= url('events.php?tab=upcoming') ?>" class="btn <?= $tab !== 'past' ? 'btn-primary' : 'btn-outline' ?>" style="border-radius: 8px 8px 0 0;">
        🔥 Sắp diễn ra (<?= count($upcoming_events) ?>)
      </a>
      <a href="<?= url('events.php?tab=past') ?>" class="btn <?= $tab === 'past' ? 'btn-primary' : 'btn-outline' ?>" style="border-radius: 8px 8px 0 0;">
        ⏳ Đã kết thúc (<?= count($past_events) ?>)
      </a>
    </div>
  </div>

  <!-- Danh sách Sự kiện -->
  <?php if (!empty($display_events)): ?>
    <div class="grid grid-cols-3">
      <?php foreach ($display_events as $event): ?>
        <?php
          $start_dt = new DateTime($event['start_time']);
          $day = $start_dt->format('d');
          $month = 'Tháng ' . $start_dt->format('m');
        ?>
        <div class="event-card">
          <div class="event-card-poster">
            <img src="<?= upload_url($event['poster_image']) ?>" alt="<?= e($event['title']) ?>" loading="lazy">
            <div class="event-date-ribbon">
              <div class="event-date-day"><?= $day ?></div>
              <div class="event-date-month"><?= $month ?></div>
            </div>
          </div>
          <div class="event-card-body">
            <span class="event-organizer">Tổ chức bởi: <?= e($event['club_name']) ?></span>
            <h3 class="event-card-title">
              <a href="<?= url('event-detail.php?slug=' . e($event['slug'])) ?>">
                <?= e($event['title']) ?>
              </a>
            </h3>
            <ul class="event-meta-list">
              <li class="event-meta-item">
                <span>🕒</span>
                <span><?= format_date_vn($event['start_time'], true) ?></span>
              </li>
              <li class="event-meta-item">
                <span>📍</span>
                <span><?= e($event['location']) ?></span>
              </li>
              <li class="event-meta-item">
                <span>💰</span>
                <span>Phí tham gia: <strong><?= format_money_vn($event['fee']) ?></strong></span>
              </li>
            </ul>
            <div style="margin-top: auto; padding-top: 12px; border-top: 1px solid var(--color-border);">
              <a href="<?= url('event-detail.php?slug=' . e($event['slug'])) ?>" class="btn btn-outline btn-block btn-sm">
                Xem chi tiết chương trình &rarr;
              </a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="empty-state">
      <div class="empty-icon">📅</div>
      <h3 class="empty-title">
        <?= $tab === 'past' ? 'Chưa có sự kiện nào trong danh sách đã kết thúc' : 'Hiện chưa có sự kiện nào sắp diễn ra' ?>
      </h3>
      <p class="empty-desc">
        <?= $tab === 'past' ? 'Lịch sử các sự kiện đã tổ chức sẽ được lưu trữ tại đây.' : 'Các CLB đang lên kế hoạch chương trình mới. Vui lòng quay lại sau!' ?>
      </p>
    </div>
  <?php endif; ?>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
