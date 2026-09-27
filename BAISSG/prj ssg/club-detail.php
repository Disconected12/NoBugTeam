<?php
/**
 * ClubHub - Chi Tiết Câu Lạc Bộ
 */

define('CLUBHUB_INIT', true);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$db = get_db_connection();
if (!$db) {
    flash_set('error', 'Lỗi kết nối cơ sở dữ liệu.');
    redirect('clubs.php');
}

$slug = trim($_GET['slug'] ?? '');
$id = (int)($_GET['id'] ?? 0);

if (empty($slug) && $id <= 0) {
    flash_set('error', 'Không tìm thấy câu lạc bộ yêu cầu.');
    redirect('clubs.php');
}

// Truy vấn thông tin CLB
$club = null;
try {
    if (!empty($slug)) {
        $stmt = $db->prepare("
            SELECT c.*, cat.name as category_name, cat.slug as category_slug
            FROM clubs c
            JOIN categories cat ON c.category_id = cat.id
            WHERE c.slug = ? AND c.is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$slug]);
    } else {
        $stmt = $db->prepare("
            SELECT c.*, cat.name as category_name, cat.slug as category_slug
            FROM clubs c
            JOIN categories cat ON c.category_id = cat.id
            WHERE c.id = ? AND c.is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$id]);
    }
    $club = $stmt->fetch();
} catch (Exception $e) {
    error_log("Lỗi tải chi tiết CLB: " . $e->getMessage());
}

if (!$club) {
    flash_set('error', 'Câu lạc bộ không tồn tại hoặc đã bị ẩn.');
    redirect('clubs.php');
}

$club_id = $club['id'];

// Lấy danh sách kênh mạng xã hội của CLB (Chỉ hiển thị kênh có URL hợp lệ)
$socials = [];
try {
    $soc_stmt = $db->prepare("SELECT platform, url FROM club_socials WHERE club_id = ?");
    $soc_stmt->execute([$club_id]);
    $raw_socials = $soc_stmt->fetchAll();
    foreach ($raw_socials as $s) {
        $clean = safe_url($s['url']);
        if (!empty($clean)) {
            $socials[$s['platform']] = $clean;
        }
    }
} catch (Exception $e) {
    error_log("Lỗi tải mạng xã hội CLB: " . $e->getMessage());
}

// Lấy thư viện hình ảnh của CLB
$images = [];
try {
    $img_stmt = $db->prepare("SELECT image_url, caption FROM club_images WHERE club_id = ? ORDER BY display_order ASC");
    $img_stmt->execute([$club_id]);
    $images = $img_stmt->fetchAll();
} catch (Exception $e) {
    error_log("Lỗi tải ảnh CLB: " . $e->getMessage());
}

// Lấy các sự kiện liên quan của CLB
$events = [];
try {
    $ev_stmt = $db->prepare("
        SELECT id, title, slug, poster_image, start_time, end_time, location
        FROM events
        WHERE club_id = ? AND is_active = 1
        ORDER BY start_time DESC
        LIMIT 4
    ");
    $ev_stmt->execute([$club_id]);
    $events = $ev_stmt->fetchAll();
} catch (Exception $e) {
    error_log("Lỗi tải sự kiện CLB: " . $e->getMessage());
}

$page_title = $club['name'];
$page_desc = $club['summary'];

$status_info = check_actual_recruitment_status($club['recruitment_status'], $club['recruitment_deadline']);

include __DIR__ . '/includes/header.php';
?>

<main class="container" style="padding-top: 24px; padding-bottom: 60px;">

  <!-- Hero Banner Đầu Trang Chi Tiết -->
  <div class="club-detail-hero">
    <div class="club-detail-cover">
      <img src="<?= upload_url($club['cover_image'], 'default-cover.png') ?>" alt="<?= e($club['name']) ?>">
    </div>
    <div class="club-detail-info-bar">
      <div class="club-detail-avatar-wrap">
        <div class="club-detail-logo">
          <img src="<?= upload_url($club['logo'], 'default-club.png') ?>" alt="<?= e($club['name']) ?>">
        </div>
        <div class="club-detail-titles">
          <div class="club-detail-category-badge">
            <span class="badge badge-category"><?= e($club['category_name']) ?></span>
            <?= $status_info['badge'] ?>
          </div>
          <h1 class="club-detail-name">
            <?= e($club['name']) ?>
            <?php if (!empty($club['short_name'])): ?>
              <span class="club-detail-short">(<?= e($club['short_name']) ?>)</span>
            <?php endif; ?>
          </h1>
        </div>
      </div>

      <!-- Các nút hành động chính -->
      <div class="club-detail-actions">
        <!-- Nút Chia sẻ link -->
        <button type="button" class="btn btn-outline btn-share-link" data-url="<?= url('club-detail.php?slug=' . e($club['slug'])) ?>">
          🔗 Chia sẻ
        </button>

        <!-- Nút Báo thông tin sai -->
        <button type="button" class="btn btn-outline btn-open-report-modal" data-club-id="<?= $club['id'] ?>" data-entity-name="<?= e($club['name']) ?>">
          ⚠️ Báo sai sót
        </button>

        <!-- Nút Đăng ký tham gia (Chỉ mở khi đang tuyển và trong thời hạn) -->
        <?php if ($status_info['is_open'] && !empty($club['application_link'])): ?>
          <a href="<?= safe_url($club['application_link']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary" onclick="alert('Đơn đăng ký được mở qua biểu mẫu trực tuyến bên ngoài. Bạn sẽ được chuyển tiếp sang trang nhận hồ sơ chính thức của CLB.');">
            Đăng ký nộp đơn &rarr;
          </a>
        <?php elseif ($status_info['is_open']): ?>
          <button type="button" class="btn btn-primary" disabled title="CLB đang cập nhật đường link nhận đơn">
            Đang mở đơn tuyển (Chờ link)
          </button>
        <?php else: ?>
          <button type="button" class="btn btn-outline" disabled style="opacity: 0.6; cursor: not-allowed;">
            Hiện đang đóng tuyển
          </button>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Bố cục Nội dung 2 Cột (Main Content & Sidebar) -->
  <div class="detail-grid">

    <!-- Cột Chính (Bên Trái) -->
    <div class="detail-main-column">
      
      <!-- 1. Giới thiệu tổng quan & Mục tiêu -->
      <div class="detail-main-card">
        <h2 class="card-heading">📖 Giới Thiệu & Mục Tiêu Hoạt Động</h2>
        <div style="font-size: 1rem; line-height: 1.7; color: var(--color-text-main); margin-bottom: 20px;">
          <?= nl2br(e($club['description'])) ?>
        </div>

        <?php if (!empty($club['objectives'])): ?>
          <div style="background-color: var(--color-bg-light); border-radius: var(--radius-md); padding: 18px 20px; border-left: 4px solid var(--color-primary-light);">
            <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--color-primary); margin-bottom: 6px;">🎯 Mục tiêu trọng tâm:</h4>
            <p style="font-size: 0.92rem; color: var(--color-text-main); line-height: 1.6;"><?= nl2br(e($club['objectives'])) ?></p>
          </div>
        <?php endif; ?>

        <?php if (!empty($club['target_audience'])): ?>
          <div style="margin-top: 16px;">
            <strong style="color: var(--color-primary); font-size: 0.92rem;">👥 Đối tượng phù hợp:</strong>
            <span style="font-size: 0.92rem; color: var(--color-text-main);"><?= e($club['target_audience']) ?></span>
          </div>
        <?php endif; ?>
      </div>

      <!-- 2. Hoạt động, Dự án & Thành tích nổi bật -->
      <?php if (!empty($club['activities_achievements'])): ?>
        <div class="detail-main-card">
          <h2 class="card-heading">🏆 Hoạt Động Tiêu Biểu & Thành Tích</h2>
          <div style="font-size: 0.95rem; line-height: 1.7; color: var(--color-text-main);">
            <?= nl2br(e($club['activities_achievements'])) ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- 3. Quy trình & Đợt Tuyển thành viên -->
      <div class="detail-main-card">
        <h2 class="card-heading">📝 Thông Tin Tuyển Thành Viên</h2>
        <div style="margin-bottom: 16px;">
          <strong>Trạng thái hiện tại:</strong> <?= $status_info['badge'] ?>
        </div>

        <?php if (!empty($club['recruitment_deadline'])): ?>
          <p style="margin-bottom: 12px; font-size: 0.95rem;">
            ⏳ <strong>Hạn nộp hồ sơ:</strong> <?= format_date_vn($club['recruitment_deadline'], true) ?>
            <?php if (!$status_info['is_open']): ?>
              <span style="color: var(--color-danger); font-weight: 600;">(Đã hết hạn nhận đơn)</span>
            <?php endif; ?>
          </p>
        <?php endif; ?>

        <?php if (!empty($club['recruitment_process'])): ?>
          <div style="background-color: #F8FAFC; border-radius: var(--radius-md); padding: 16px; border: 1px solid var(--color-border); margin-bottom: 16px;">
            <h4 style="font-size: 0.9rem; font-weight: 700; color: var(--color-primary); margin-bottom: 6px;">Quy trình xét tuyển:</h4>
            <p style="font-size: 0.92rem; line-height: 1.6;"><?= nl2br(e($club['recruitment_process'])) ?></p>
          </div>
        <?php endif; ?>

        <?php if ($status_info['is_open'] && !empty($club['application_link'])): ?>
          <div style="margin-top: 20px;">
            <a href="<?= safe_url($club['application_link']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary" onclick="alert('Đơn đăng ký được mở qua biểu mẫu Google Form bên ngoài. Bạn sẽ được chuyển tiếp sang trang nhận đơn của CLB.');">
              Mở đơn ứng tuyển trực tuyến &rarr;
            </a>
          </div>
        <?php endif; ?>
      </div>

      <!-- 4. Thư viện hình ảnh CLB -->
      <?php if (!empty($images)): ?>
        <div class="detail-main-card">
          <h2 class="card-heading">📸 Thư Viện Hình Ảnh</h2>
          <p style="font-size: 0.85rem; color: var(--color-text-muted); margin-bottom: 14px;">Bấm vào ảnh để xem kích thước lớn</p>
          <div class="gallery-grid">
            <?php foreach ($images as $img): ?>
              <div class="gallery-thumb">
                <img src="<?= upload_url($img['image_url']) ?>" data-large="<?= upload_url($img['image_url']) ?>" alt="<?= e($img['caption'] ?? $club['name']) ?>" loading="lazy">
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- 5. Sự kiện liên quan do CLB tổ chức -->
      <?php if (!empty($events)): ?>
        <div class="detail-main-card">
          <h2 class="card-heading">🎪 Sự Kiện Liên Quan</h2>
          <div class="grid grid-cols-2">
            <?php foreach ($events as $ev): ?>
              <div class="event-card">
                <div class="event-card-poster" style="aspect-ratio: 16/9;">
                  <img src="<?= upload_url($ev['poster_image']) ?>" alt="<?= e($ev['title']) ?>" loading="lazy">
                </div>
                <div class="event-card-body" style="padding: 16px;">
                  <h4 style="font-size: 1rem; font-weight: 700; margin-bottom: 8px;">
                    <a href="<?= url('event-detail.php?slug=' . e($ev['slug'])) ?>" style="color: var(--color-primary);">
                      <?= e($ev['title']) ?>
                    </a>
                  </h4>
                  <p style="font-size: 0.82rem; color: var(--color-text-muted); margin-bottom: 10px;">
                    📅 <?= format_date_vn($ev['start_time']) ?> | 📍 <?= e($ev['location']) ?>
                  </p>
                  <a href="<?= url('event-detail.php?slug=' . e($ev['slug'])) ?>" class="btn btn-outline btn-sm">Xem sự kiện</a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

    </div>

    <!-- Cột Phụ (Bên Phải - Thông Tin Tổng Hợp) -->
    <div class="detail-side-column">

      <!-- Lịch & Địa Điểm Sinh Hoạt -->
      <div class="detail-side-card">
        <h3 class="card-heading">📍 Lịch & Địa Điểm</h3>
        <ul class="info-list">
          <li class="info-item">
            <div class="info-icon">📅</div>
            <div class="info-content">
              <div class="info-label">Lịch sinh hoạt</div>
              <div class="info-value">
                <?= !empty($club['meeting_schedule']) ? e($club['meeting_schedule']) : 'Chưa có thông tin cố định' ?>
              </div>
            </div>
          </li>
          <li class="info-item">
            <div class="info-icon">🏢</div>
            <div class="info-content">
              <div class="info-label">Địa điểm</div>
              <div class="info-value">
                <?= !empty($club['meeting_location']) ? e($club['meeting_location']) : 'Khuôn viên trường Đại học' ?>
              </div>
            </div>
          </li>
          <li class="info-item">
            <div class="info-icon">🔄</div>
            <div class="info-content">
              <div class="info-label">Tần suất sinh hoạt</div>
              <div class="info-value">
                <?= !empty($club['meeting_frequency']) ? e($club['meeting_frequency']) : 'Định kỳ theo tuần' ?>
              </div>
            </div>
          </li>
          <li class="info-item">
            <div class="info-icon">📋</div>
            <div class="info-content">
              <div class="info-label">Trạng thái chốt lịch</div>
              <div class="info-value">
                <?php if ($club['schedule_confirmed']): ?>
                  <span style="color: var(--color-success); font-weight: 700;">✓ Đã chốt lịch cố định</span>
                <?php else: ?>
                  <span style="color: var(--color-warning); font-weight: 700;">⚠️ Cần xác nhận lịch</span>
                <?php endif; ?>
              </div>
            </div>
          </li>
        </ul>
      </div>

      <!-- Điều Kiện Tham Gia & Chi Phí -->
      <div class="detail-side-card">
        <h3 class="card-heading">💡 Điều Kiện & Chi Phí</h3>
        <ul class="info-list">
          <li class="info-item">
            <div class="info-icon">🎯</div>
            <div class="info-content">
              <div class="info-label">Yêu cầu kinh nghiệm</div>
              <div class="info-value">
                <?= !empty($club['requirements']) ? e($club['requirements']) : 'Chào đón tất cả sinh viên đam mê học hỏi' ?>
              </div>
            </div>
          </li>
          <li class="info-item">
            <div class="info-icon">💰</div>
            <div class="info-content">
              <div class="info-label">Kinh phí / Quỹ hoạt động</div>
              <div class="info-value">
                <?= !empty($club['fee_info']) ? e($club['fee_info']) : 'Miễn phí tham gia' ?>
              </div>
            </div>
          </li>
        </ul>
      </div>

      <!-- Kênh Mạng Xã Hội Chính Thức (Chỉ hiện các kênh có URL) -->
      <?php if (!empty($socials)): ?>
        <div class="detail-side-card">
          <h3 class="card-heading">🌐 Kênh Liên Hệ Chính Thức</h3>
          <p style="font-size: 0.85rem; color: var(--color-text-muted); margin-bottom: 12px;">
            Kết nối với CLB qua các kênh truyền thông đã được kiểm duyệt:
          </p>
          <div class="social-buttons-list">
            <?php foreach ($socials as $platform => $url): ?>
              <?php 
                $plat_info = SUPPORTED_SOCIALS[$platform] ?? ['name' => ucfirst($platform), 'color' => '#0F2854'];
              ?>
              <a href="<?= safe_url($url) ?>" <?= link_target_attr($url) ?> class="social-btn" style="background-color: <?= e($plat_info['color']) ?>;">
                <?= e($plat_info['name']) ?> &nearr;
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- Ngày cập nhật gần nhất -->
      <div style="font-size: 0.8rem; color: var(--color-text-light); text-align: center; margin-top: 16px;">
        Cập nhật lần cuối: <?= format_date_vn($club['updated_at'], true) ?>
      </div>

    </div>

  </div>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
