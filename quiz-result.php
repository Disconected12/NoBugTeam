<?php
/**
 * ClubHub - Kết Quả Trắc Nghiệm Đề Xuất Câu Lạc Bộ
 */

define('CLUBHUB_INIT', true);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Kết quả gợi ý CLB';
$page_desc = 'Danh sách các câu lạc bộ phù hợp nhất với kết quả làm bài trắc nghiệm của bạn.';

$quiz_data = $_SESSION['quiz_evaluation'] ?? null;

// Nếu người dùng truy cập trực tiếp mà chưa làm quiz, chuyển hướng về trang quiz.php
if (!$quiz_data) {
    flash_set('info', 'Vui lòng hoàn thành bài trắc nghiệm để nhận gợi ý câu lạc bộ phù hợp.');
    redirect('quiz.php');
}

$has_results = $quiz_data['has_results'] ?? false;
$top_matches = $quiz_data['top_matches'] ?? [];

// Lấy danh mục gợi ý trong trường hợp không có kết quả phù hợp
$db = get_db_connection();
$categories = [];
if (!$has_results && $db) {
    try {
        $cat_stmt = $db->query("SELECT name, slug, description FROM categories WHERE is_active = 1 ORDER BY display_order ASC");
        $categories = $cat_stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Lỗi tải danh mục: " . $e->getMessage());
    }
}

include __DIR__ . '/includes/header.php';
?>

<main class="container" style="padding-top: 36px; padding-bottom: 60px;">

  <!-- Tiêu đề Kết Quả -->
  <div class="quiz-result-header">
    <span class="quiz-cta-badge">🎉 Kết Quả Đánh Giá Tự Động</span>
    <h1 style="font-size: 2rem; font-weight: 800; color: var(--color-primary); margin-top: 8px;">
      Gợi Ý Câu Lạc Bộ Dành Riêng Cho Bạn
    </h1>
    <p style="color: var(--color-text-muted); font-size: 0.95rem; margin-top: 8px;">
      Kết quả được đối chiếu khách quan dựa trên câu trả lời của bạn với dữ liệu hoạt động, lịch sinh hoạt và tiêu chí tuyển chọn của các CLB.
    </p>
  </div>

  <?php if ($has_results): ?>
    
    <div style="max-width: 860px; margin: 0 auto;">
      
      <?php foreach ($top_matches as $idx => $match): ?>
        <div class="result-card">
          <!-- Huy hiệu Thứ hạng -->
          <div class="result-rank-badge">
            <?= $idx === 0 ? 'Đề xuất Hàng đầu' : ($idx === 1 ? 'Đề xuất #2' : 'Đề xuất #3') ?>
          </div>

          <div class="result-card-header">
            <div class="result-card-logo">
              <img src="<?= upload_url($match['logo'], 'default-club.png') ?>" alt="<?= e($match['name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
            <div>
              <span class="badge badge-category" style="margin-bottom: 4px;"><?= e($match['category_name']) ?></span>
              <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--color-primary);">
                <a href="<?= url('club-detail.php?slug=' . e($match['slug'])) ?>" style="color: inherit;">
                  <?= e($match['name']) ?>
                  <?php if (!empty($match['short_name'])): ?>
                    <span style="font-size: 1rem; color: var(--color-text-muted); font-weight: 600;">(<?= e($match['short_name']) ?>)</span>
                  <?php endif; ?>
                </a>
              </h2>
              <div style="margin-top: 4px;">
                <?= $match['recruitment_badge'] ?>
              </div>
            </div>
          </div>

          <p style="font-size: 0.95rem; color: var(--color-text-main); line-height: 1.6; margin-bottom: 16px;">
            <?= e($match['summary']) ?>
          </p>

          <!-- Lý do được đề xuất dựa trên câu trả lời thực tế -->
          <div class="result-reasons-box">
            <div class="result-reasons-title">Vì sao CLB này phù hợp với bạn?</div>
            <ul style="padding-left: 20px; font-size: 0.9rem; line-height: 1.6; color: var(--color-text-main);">
              <?php foreach ($match['matched_reasons'] as $reason): ?>
                <li><?= e($reason) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>

          <!-- Điểm cần cân nhắc -->
          <?php if (!empty($match['considerations'])): ?>
            <div class="result-considerations-box">
              <strong>Điểm cần lưu ý trước khi tham gia:</strong>
              <ul style="padding-left: 20px; margin-top: 4px; line-height: 1.5;">
                <?php foreach ($match['considerations'] as $con): ?>
                  <li><?= e($con) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <!-- Các nút liên kết hành động -->
          <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; border-top: 1px solid var(--color-border); padding-top: 16px;">
            <div style="display: flex; gap: 10px;">
              <a href="<?= url('club-detail.php?slug=' . e($match['slug'])) ?>" class="btn btn-secondary btn-sm">
                Xem trang chi tiết CLB &rarr;
              </a>
              <?php if (!empty($match['facebook_url'])): ?>
                <a href="<?= safe_url($match['facebook_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm">
                  Fanpage chính thức &nearr;
                </a>
              <?php endif; ?>
            </div>

            <?php if ($match['recruitment_is_open'] && !empty($match['application_link'])): ?>
              <a href="<?= safe_url($match['application_link']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-sm" onclick="alert('Đơn đăng ký được mở qua biểu mẫu Google Form bên ngoài.');">
                Đăng ký nộp đơn ngay &rarr;
              </a>
            <?php endif; ?>
          </div>

        </div>
      <?php endforeach; ?>

      <!-- Nút làm lại hoặc quay về -->
      <div style="text-align: center; margin-top: 36px; display: flex; justify-content: center; gap: 16px;">
        <a href="<?= url('quiz.php') ?>" class="btn btn-outline">
          ↺ Làm lại bài trắc nghiệm
        </a>
        <a href="<?= url('clubs.php') ?>" class="btn btn-primary">
          Xem tất cả các câu lạc bộ &rarr;
        </a>
      </div>

    </div>

  <?php else: ?>

    <!-- Trường hợp không có CLB nào đạt ngưỡng phù hợp -->
    <div class="empty-state" style="max-width: 680px; margin: 0 auto;">
      <div class="empty-icon">🤝</div>
      <h2 class="empty-title">Chưa tìm thấy CLB hoàn toàn trùng khớp</h2>
      <p class="empty-desc">
        Hệ thống nhận thấy các tiêu chí bạn lựa chọn về sở thích, quỹ thời gian và phong cách hoạt động chưa có sự kết hợp tương đồng với các CLB hiện tại. Bạn có thể khám phá theo từng lĩnh vực bên dưới hoặc thử làm lại bài trắc nghiệm với các lựa chọn linh hoạt hơn.
      </p>

      <div style="margin-top: 24px; text-align: left; margin-bottom: 24px;">
        <h4 style="font-size: 1rem; color: var(--color-primary); margin-bottom: 12px; font-weight: 700;">
          Khám phá theo các lĩnh vực hoạt động:
        </h4>
        <div style="display: flex; flex-direction: column; gap: 8px;">
          <?php foreach ($categories as $cat): ?>
            <a href="<?= url('clubs.php?category=' . e($cat['slug'])) ?>" style="padding: 10px 14px; background-color: #F8FAFC; border: 1px solid var(--color-border); border-radius: 8px; font-weight: 600; display: block;">
              📁 <?= e($cat['name']) ?> &rarr;
            </a>
          <?php endforeach; ?>
        </div>
      </div>

      <a href="<?= url('quiz.php') ?>" class="btn btn-primary">Làm lại bài trắc nghiệm</a>
    </div>

  <?php endif; ?>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
