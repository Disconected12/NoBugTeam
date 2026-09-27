<?php
/**
 * ClubHub - Trang Chủ Cổng Thông Tin CLB Sinh Viên Đại Học
 */

define('CLUBHUB_INIT', true);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$db = get_db_connection();

$page_title = 'Trang chủ';
$page_desc = 'Cổng thông tin khám phá các câu lạc bộ đại học, tham gia trắc nghiệm định hướng, theo dõi sự kiện và các đợt tuyển thành viên mới.';

// 1. Lấy danh sách Banner quảng bá đầu trang (B. Banner/poster quảng bá đầu trang)
// Điều kiện hiển thị nghiêm ngặt: Đã duyệt + Đã thanh toán + Đang bật + Trong khoảng thời gian hiệu lực
$top_banners = [];
if ($db) {
    try {
        $banner_stmt = $db->prepare("
            SELECT id, tracking_code, club_name, poster_image, destination_url
            FROM ad_requests
            WHERE placement_code = ?
              AND approval_status = ?
              AND payment_status = ?
              AND is_enabled = 1
              AND start_date <= CURDATE()
              AND end_date >= CURDATE()
            ORDER BY id DESC
        ");
        $banner_stmt->execute([PLACEMENT_TOP_BANNER, AD_APPROVAL_APPROVED, AD_PAYMENT_PAID]);
        $top_banners = $banner_stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Lỗi tải banner: " . $e->getMessage());
    }
}

// 2. Lấy danh sách CLB nổi bật do Quản trị viên lựa chọn (D. CLB nổi bật - Tách biệt hoàn toàn với quảng cáo)
$featured_clubs = [];
if ($db) {
    try {
        $feat_stmt = $db->query("
            SELECT c.id, c.name, c.short_name, c.slug, c.logo, c.cover_image, c.summary,
                   c.recruitment_status, c.recruitment_deadline, cat.name as category_name
            FROM clubs c
            JOIN categories cat ON c.category_id = cat.id
            WHERE c.is_active = 1 AND c.is_featured = 1
            ORDER BY c.updated_at DESC
            LIMIT 4
        ");
        $featured_clubs = $feat_stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Lỗi tải CLB nổi bật: " . $e->getMessage());
    }
}

// 3. Lấy danh sách CLB đang tuyển thành viên (E. CLB đang tuyển thành viên)
// Tự ngắt hiển thị khi hạn đăng ký đã qua
$recruiting_clubs = [];
if ($db) {
    try {
        $recruit_stmt = $db->query("
            SELECT c.id, c.name, c.short_name, c.slug, c.logo, c.cover_image, c.summary,
                   c.recruitment_status, c.recruitment_deadline, cat.name as category_name
            FROM clubs c
            JOIN categories cat ON c.category_id = cat.id
            WHERE c.is_active = 1
              AND c.recruitment_status = 'open'
              AND (c.recruitment_deadline IS NULL OR c.recruitment_deadline >= NOW())
            ORDER BY c.recruitment_deadline ASC
            LIMIT 4
        ");
        $recruiting_clubs = $recruit_stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Lỗi tải CLB đang tuyển: " . $e->getMessage());
    }
}

// 4. Lấy danh sách Sự kiện sắp diễn ra (F. Sự kiện sắp diễn ra)
// Điều kiện: end_time >= NOW(), sắp xếp theo start_time ASC
$upcoming_events = [];
if ($db) {
    try {
        $event_stmt = $db->query("
            SELECT e.id, e.title, e.slug, e.poster_image, e.start_time, e.end_time, e.location,
                   c.name as club_name, c.short_name as club_short_name
            FROM events e
            JOIN clubs c ON e.club_id = c.id
            WHERE e.is_active = 1 AND e.end_time >= NOW()
            ORDER BY e.start_time ASC
            LIMIT 3
        ");
        $upcoming_events = $event_stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Lỗi tải sự kiện sắp tới: " . $e->getMessage());
    }
}

// 5. Lấy danh mục lĩnh vực hoạt động (G. Khám phá theo lĩnh vực)
$categories = [];
if ($db) {
    try {
        $cat_stmt = $db->query("
            SELECT cat.id, cat.name, cat.slug, cat.description, cat.icon,
                   COUNT(c.id) as club_count
            FROM categories cat
            LEFT JOIN clubs c ON cat.id = c.category_id AND c.is_active = 1
            WHERE cat.is_active = 1
            GROUP BY cat.id
            ORDER BY cat.display_order ASC
        ");
        $categories = $cat_stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Lỗi tải danh mục: " . $e->getMessage());
    }
}

// 6. Lấy banner quảng bá khối nổi bật trang chủ (Home Featured Ads nếu có)
$home_featured_ads = [];
if ($db) {
    try {
        $hf_stmt = $db->prepare("
            SELECT id, club_name, poster_image, destination_url
            FROM ad_requests
            WHERE placement_code = ?
              AND approval_status = ?
              AND payment_status = ?
              AND is_enabled = 1
              AND start_date <= CURDATE()
              AND end_date >= CURDATE()
            ORDER BY id DESC
            LIMIT 2
        ");
        $hf_stmt->execute([PLACEMENT_HOME_FEATURED, AD_APPROVAL_APPROVED, AD_PAYMENT_PAID]);
        $home_featured_ads = $hf_stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Lỗi tải quảng cáo trang chủ: " . $e->getMessage());
    }
}

include __DIR__ . '/includes/header.php';
?>

<main id="mainContent">

  <!-- B. BANNER / POSTER QUẢNG BÁ ĐẦU TRANG -->
  <section class="top-banner-section" aria-label="Banner quảng bá và tiêu điểm">
    <div class="container">
      <div class="banner-slider-container" tabindex="0" role="region" aria-roledescription="carousel" aria-label="Poster nổi bật">
        
        <?php if (!empty($top_banners)): ?>
          <!-- Có quảng cáo được duyệt & còn hạn -->
          <div class="banner-slides-wrapper">
            <?php foreach ($top_banners as $idx => $banner): ?>
              <div class="banner-slide" role="group" aria-roledescription="slide" aria-label="Slide <?= $idx + 1 ?> trên <?= count($top_banners) ?>">
                <img src="<?= upload_url($banner['poster_image']) ?>" alt="<?= e($banner['club_name']) ?>" loading="<?= $idx === 0 ? 'eager' : 'lazy' ?>">
                <div class="banner-overlay">
                  <div class="banner-badges">
                    <span class="badge badge-sponsored">Được tài trợ</span>
                  </div>
                  <h2 class="banner-title"><?= e($banner['club_name']) ?></h2>
                  <p class="banner-desc">Khám phá các hoạt động đặc sắc và cơ hội kết nối cùng cộng đồng sinh viên.</p>
                  <div class="banner-btn-wrap">
                    <?php 
                      $b_url = normalize_destination_url($banner['destination_url']);
                      $b_target = link_target_attr($b_url);
                    ?>
                    <a href="<?= e($b_url) ?>" <?= $b_target ?> class="btn btn-primary">
                      Xem chi tiết ngay &rarr;
                    </a>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Nút điều khiển chuyển slide -->
          <?php if (count($top_banners) > 1): ?>
            <button type="button" class="banner-nav-btn banner-prev" aria-label="Poster trước">&lang;</button>
            <button type="button" class="banner-nav-btn banner-next" aria-label="Poster tiếp theo">&rang;</button>
            <div class="banner-indicators" role="tablist" aria-label="Chọn poster">
              <?php foreach ($top_banners as $idx => $b): ?>
                <button type="button" class="banner-indicator-dot <?= $idx === 0 ? 'active' : '' ?>" role="tab" aria-selected="<?= $idx === 0 ? 'true' : 'false' ?>" aria-label="Xem poster <?= $idx + 1 ?>"></button>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

        <?php else: ?>
          <!-- Banner biên tập mặc định khi chưa có quảng cáo (Không tạo quảng cáo giả) -->
          <div class="banner-slides-wrapper">
            <div class="banner-slide">
              <img src="<?= asset('images/default-cover.png') ?>" alt="Khám phá CLB Sinh viên" loading="eager">
              <div class="banner-overlay">
                <div class="banner-badges">
                  <span class="badge badge-category">Cổng thông tin chính thức</span>
                </div>
                <h1 class="banner-title">Hòa Nhịp Đam Mê - Kết Nối Tương Lai Cùng ClubHub</h1>
                <p class="banner-desc">Hơn 50 Câu lạc bộ, Đội, Nhóm học thuật, thể thao và nghệ thuật đang chờ đón bạn khám phá và khẳng định bản thân.</p>
                <div class="banner-btn-wrap">
                  <a href="<?= url('clubs.php') ?>" class="btn btn-primary">Khám phá tất cả CLB</a>
                  <a href="<?= url('quiz.php') ?>" class="btn btn-secondary">Làm quiz tìm CLB</a>
                </div>
              </div>
            </div>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </section>


  <!-- C. QUIZ TÌM CLB PHÙ HỢP -->
  <section class="quiz-cta-section" aria-label="Gợi ý câu lạc bộ phù hợp">
    <div class="container">
      <div class="quiz-cta-card">
        <div class="quiz-cta-content">
          <span class="quiz-cta-badge">🎯 Định Hướng Bản Thân</span>
          <h2 class="quiz-cta-title">Chưa biết nên tham gia CLB nào?</h2>
          <p class="quiz-cta-desc">
            Trả lời vài câu hỏi để khám phá CLB phù hợp với bạn nhất dựa trên sở thích, mục tiêu, thời gian rảnh và phong cách hoạt động.
          </p>
        </div>
        <div class="quiz-cta-action">
          <a href="<?= url('quiz.php') ?>" class="btn btn-primary btn-lg">
            Làm quiz ngay &rarr;
          </a>
        </div>
      </div>
    </div>
  </section>


  <!-- D. CLB NỔI BẬT (Do Quản trị viên lựa chọn) -->
  <section style="padding: 30px 0 40px 0;" aria-label="Câu lạc bộ nổi bật">
    <div class="container">
      <div class="section-header">
        <div class="section-title-wrap">
          <span class="section-tag">Tiêu Điểm Sinh Viên</span>
          <h2 class="section-title">Câu Lạc Bộ Nổi Bật</h2>
          <p class="section-subtitle">Được ban biên tập lựa chọn nhờ thành tích và phong trào hoạt động xuất sắc</p>
        </div>
        <a href="<?= url('clubs.php') ?>" class="btn btn-outline btn-sm">Xem tất cả &rarr;</a>
      </div>

      <?php if (!empty($featured_clubs)): ?>
        <div class="grid grid-cols-4">
          <?php foreach ($featured_clubs as $club): ?>
            <?php 
              $status_info = check_actual_recruitment_status($club['recruitment_status'], $club['recruitment_deadline']);
            ?>
            <div class="club-card">
              <div class="club-card-cover">
                <img src="<?= upload_url($club['cover_image'], 'default-cover.png') ?>" alt="<?= e($club['name']) ?>" loading="lazy">
                <div class="club-card-status-badge">
                  <?= $status_info['badge'] ?>
                </div>
                <div class="club-card-avatar">
                  <img src="<?= upload_url($club['logo'], 'default-club.png') ?>" alt="<?= e($club['name']) ?>" loading="lazy">
                </div>
              </div>
              <div class="club-card-body">
                <span class="club-card-category"><?= e($club['category_name']) ?></span>
                <h3 class="club-card-title">
                  <a href="<?= url('club-detail.php?slug=' . e($club['slug'])) ?>">
                    <?= e($club['name']) ?>
                  </a>
                </h3>
                <p class="club-card-summary"><?= e($club['summary']) ?></p>
                <div class="club-card-footer">
                  <span class="club-card-deadline">
                    <?= !empty($club['short_name']) ? '<strong>' . e($club['short_name']) . '</strong>' : '' ?>
                  </span>
                  <a href="<?= url('club-detail.php?slug=' . e($club['slug'])) ?>" class="btn btn-outline btn-sm">
                    Chi tiết &rarr;
                  </a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <div class="empty-icon">📁</div>
          <h3 class="empty-title">Chưa có CLB nổi bật</h3>
          <p class="empty-desc">Danh sách câu lạc bộ nổi bật đang được cập nhật thêm.</p>
        </div>
      <?php endif; ?>
    </div>
  </section>


  <!-- KHU QUẢNG BÁ RIÊNG TRÊN TRANG CHỦ (Nếu có đơn vị thuê vị trí home_featured) -->
  <?php if (!empty($home_featured_ads)): ?>
    <section style="padding: 10px 0 40px 0;" aria-label="Quảng bá được tài trợ">
      <div class="container">
        <div class="grid grid-cols-2">
          <?php foreach ($home_featured_ads as $hf_ad): ?>
            <div style="position: relative; border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-md);">
              <?php 
                $hf_url = normalize_destination_url($hf_ad['destination_url']);
                $hf_target = link_target_attr($hf_url);
              ?>
              <a href="<?= e($hf_url) ?>" <?= $hf_target ?>>
                <img src="<?= upload_url($hf_ad['poster_image']) ?>" alt="<?= e($hf_ad['club_name']) ?>" style="width: 100%; aspect-ratio: 16/9; object-fit: cover;">
                <span class="badge badge-sponsored" style="position: absolute; top: 12px; right: 12px;">Được tài trợ</span>
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>


  <!-- E. CLB ĐANG TUYỂN THÀNH VIÊN -->
  <section style="padding: 30px 0 40px 0; background-color: #FFFFFF; border-top: 1px solid var(--color-border); border-bottom: 1px solid var(--color-border);" aria-label="Câu lạc bộ đang tuyển thành viên">
    <div class="container">
      <div class="section-header">
        <div class="section-title-wrap">
          <span class="section-tag">Gia Nhập Ngay</span>
          <h2 class="section-title">Câu Lạc Bộ Đang Mở Đơn Tuyển</h2>
          <p class="section-subtitle">Cơ hội trở thành tân thành viên trước khi hết hạn nhận đơn</p>
        </div>
        <a href="<?= url('clubs.php?status=open') ?>" class="btn btn-outline btn-sm">Xem tất cả đơn tuyển &rarr;</a>
      </div>

      <?php if (!empty($recruiting_clubs)): ?>
        <div class="grid grid-cols-4">
          <?php foreach ($recruiting_clubs as $club): ?>
            <?php 
              $status_info = check_actual_recruitment_status($club['recruitment_status'], $club['recruitment_deadline']);
            ?>
            <div class="club-card">
              <div class="club-card-cover">
                <img src="<?= upload_url($club['cover_image'], 'default-cover.png') ?>" alt="<?= e($club['name']) ?>" loading="lazy">
                <div class="club-card-status-badge">
                  <?= $status_info['badge'] ?>
                </div>
                <div class="club-card-avatar">
                  <img src="<?= upload_url($club['logo'], 'default-club.png') ?>" alt="<?= e($club['name']) ?>" loading="lazy">
                </div>
              </div>
              <div class="club-card-body">
                <span class="club-card-category"><?= e($club['category_name']) ?></span>
                <h3 class="club-card-title">
                  <a href="<?= url('club-detail.php?slug=' . e($club['slug'])) ?>">
                    <?= e($club['name']) ?>
                  </a>
                </h3>
                <p class="club-card-summary"><?= e($club['summary']) ?></p>
                <div class="club-card-footer">
                  <span class="club-card-deadline" title="Hạn nộp hồ sơ">
                    ⏳ Hạn: <?= !empty($club['recruitment_deadline']) ? format_date_vn($club['recruitment_deadline']) : 'Mở liên tục' ?>
                  </span>
                  <a href="<?= url('club-detail.php?slug=' . e($club['slug'])) ?>" class="btn btn-primary btn-sm">
                    Nộp đơn &rarr;
                  </a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <div class="empty-icon">📢</div>
          <h3 class="empty-title">Hiện tại chưa có đợt tuyển thành viên mới</h3>
          <p class="empty-desc">Các CLB đang trong giai đoạn huấn luyện nội bộ hoặc chuẩn bị mở đợt tuyển vào học kỳ tới.</p>
          <a href="<?= url('clubs.php') ?>" class="btn btn-outline">Xem danh sách các CLB</a>
        </div>
      <?php endif; ?>
    </div>
  </section>


  <!-- F. SỰ KIỆN SẮP DIỄN RA -->
  <section style="padding: 40px 0;" aria-label="Sự kiện sắp diễn ra">
    <div class="container">
      <div class="section-header">
        <div class="section-title-wrap">
          <span class="section-tag">Lịch Hoạt Động</span>
          <h2 class="section-title">Sự Kiện Sắp Diễn Ra</h2>
          <p class="section-subtitle">Hội thảo, workshop kỹ năng, đêm nhạc và giải đấu giao hữu sinh viên</p>
        </div>
        <a href="<?= url('events.php') ?>" class="btn btn-outline btn-sm">Xem tất cả sự kiện &rarr;</a>
      </div>

      <?php if (!empty($upcoming_events)): ?>
        <div class="grid grid-cols-3">
          <?php foreach ($upcoming_events as $event): ?>
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
                </ul>
                <div style="margin-top: auto; padding-top: 12px; border-top: 1px solid var(--color-border);">
                  <a href="<?= url('event-detail.php?slug=' . e($event['slug'])) ?>" class="btn btn-outline btn-block btn-sm">
                    Xem thông tin & Đăng ký
                  </a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <div class="empty-icon">📅</div>
          <h3 class="empty-title">Chưa có sự kiện nào sắp tới</h3>
          <p class="empty-desc">Các câu lạc bộ đang lên kế hoạch tổ chức chương trình mới.</p>
        </div>
      <?php endif; ?>
    </div>
  </section>


  <!-- G. KHÁM PHÁ THEO LĨNH VỰC (Dynamic from database) -->
  <section style="padding: 40px 0 60px 0; background-color: #FFFFFF; border-top: 1px solid var(--color-border);" aria-label="Khám phá theo lĩnh vực">
    <div class="container">
      <div class="section-header">
        <div class="section-title-wrap">
          <span class="section-tag">Đa Dạng Lĩnh Vực</span>
          <h2 class="section-title">Khám Phá Theo Sở Thích</h2>
          <p class="section-subtitle">Tìm kiếm các câu lạc bộ phù hợp với định hướng chuyên môn và năng khiếu của bạn</p>
        </div>
      </div>

      <?php if (!empty($categories)): ?>
        <div class="grid grid-cols-3">
          <?php foreach ($categories as $cat): ?>
            <a href="<?= url('clubs.php?category=' . e($cat['slug'])) ?>" class="category-card">
              <div class="category-icon-box">
                <?php
                  $icon_emoji = match($cat['slug']) {
                    'cong-nghe-ky-thuat' => '💻',
                    'hoc-thuat-ky-nang' => '📚',
                    'nghe-thuat-am-nhac' => '🎸',
                    'the-duc-the-thao'   => '🏀',
                    'tinh-nguyen-xa-hoi' => '🤝',
                    'truyen-thong-su-kien' => '🎬',
                    default => '⭐'
                  };
                  echo $icon_emoji;
                ?>
              </div>
              <h3 class="category-name"><?= e($cat['name']) ?></h3>
              <p style="font-size: 0.85rem; color: var(--color-text-muted); margin-bottom: 12px; line-height: 1.4;">
                <?= e($cat['description']) ?>
              </p>
              <span class="category-count">
                <strong><?= (int)$cat['club_count'] ?></strong> câu lạc bộ trực thuộc
              </span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
