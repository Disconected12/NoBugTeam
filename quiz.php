<?php
/**
 * ClubHub - Bài Trắc Nghiệm Khám Phá Câu Lạc Bộ Phù Hợp (Quiz)
 */

define('CLUBHUB_INIT', true);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$db = get_db_connection();
if (!$db) {
    flash_set('error', 'Lỗi hệ thống: Không thể tải dữ liệu câu hỏi.');
    redirect('index.php');
}

$page_title = 'Quiz chọn CLB';
$page_desc = 'Trả lời 7 câu hỏi định hướng để tìm ra Câu lạc bộ đại học phù hợp nhất với sở thích, mục tiêu và quỹ thời gian của bạn.';

// Lấy danh sách câu hỏi đang bật từ database
$questions = [];
try {
    $q_stmt = $db->query("
        SELECT id, question_text, description, question_type, display_order
        FROM quiz_questions
        WHERE is_active = 1
        ORDER BY display_order ASC
    ");
    $questions = $q_stmt->fetchAll();

    // Lấy đáp án của từng câu hỏi
    $options_stmt = $db->query("
        SELECT id, question_id, option_text, display_order
        FROM quiz_options
        ORDER BY display_order ASC
    ");
    $options_raw = $options_stmt->fetchAll();

    $options_map = [];
    foreach ($options_raw as $opt) {
        $options_map[$opt['question_id']][] = $opt;
    }
} catch (Exception $e) {
    error_log("Lỗi tải câu hỏi quiz: " . $e->getMessage());
}

$extra_scripts = ['quiz.js'];
include __DIR__ . '/includes/header.php';
?>

<main class="container" style="padding-top: 30px; padding-bottom: 60px;">

  <div class="quiz-wizard-container" id="quizWizard">

    <!-- Tiêu đề mở đầu bài Quiz -->
    <div style="text-align: center; margin-bottom: 30px;">
      <span class="quiz-cta-badge">Trắc Nghiệm Định Hướng Ẩn Danh</span>
      <h1 style="font-size: 1.8rem; font-weight: 800; color: var(--color-primary); margin-top: 6px;">
        Khám Phá Câu Lạc Bộ Phù Hợp Với Bạn
      </h1>
      <p style="color: var(--color-text-muted); font-size: 0.95rem; max-width: 580px; margin: 8px auto 0 auto;">
        Dành 2 phút trả lời các câu hỏi về sở thích, mục tiêu và thời gian để hệ thống đề xuất tối đa 3 CLB tương thích nhất. Không cần đăng nhập!
      </p>
    </div>

    <!-- Thanh Tiến Độ (Progress Bar) -->
    <div class="quiz-progress-bar-wrap">
      <div class="quiz-progress-header">
        <span id="quizProgressText">Câu hỏi 1 / <?= count($questions) ?></span>
        <button type="button" id="quizRestartBtn" style="background: none; border: none; color: var(--color-text-muted); font-size: 0.85rem; cursor: pointer; text-decoration: underline;">
          ↺ Làm lại từ đầu
        </button>
      </div>
      <div class="quiz-progress-track">
        <div class="quiz-progress-fill" id="quizProgressBar" style="width: <?= count($questions) > 0 ? round(100 / count($questions)) : 0 ?>%;"></div>
      </div>
    </div>

    <!-- Thông báo lỗi khi chưa chọn câu trả lời -->
    <div id="quizAlertBox" class="alert alert-warning" style="display: none; margin-bottom: 20px;"></div>

    <!-- Khối Danh Sách Câu Hỏi -->
    <?php if (!empty($questions)): ?>
      <div class="quiz-questions-wrapper">
        <?php foreach ($questions as $idx => $q): ?>
          <div class="quiz-question-box <?= $idx === 0 ? 'active' : '' ?>" data-question-id="<?= $q['id'] ?>" data-question-type="<?= e($q['question_type']) ?>">
            <h2 class="quiz-question-title">
              <?= $idx + 1 ?>. <?= e($q['question_text']) ?>
            </h2>
            <p class="quiz-question-hint">
              <?= !empty($q['description']) ? e($q['description']) : ($q['question_type'] === 'multiple' ? 'Bạn có thể chọn nhiều đáp án' : 'Vui lòng chọn 1 đáp án phù hợp nhất') ?>
            </p>

            <!-- Danh sách Đáp án dạng Thẻ Tương Tác -->
            <div class="quiz-options-list">
              <?php $opts = $options_map[$q['id']] ?? []; ?>
              <?php foreach ($opts as $opt): ?>
                <div class="quiz-option-tile" data-option-id="<?= $opt['id'] ?>">
                  <div class="quiz-option-indicator"></div>
                  <div class="quiz-option-text"><?= e($opt['option_text']) ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Form Ẩn Dùng Để Gửi Kết Quả Lên Backend Chấm Điểm -->
      <form action="<?= url('api/quiz-evaluate.php') ?>" method="POST" id="quizForm">
        <?= csrf_field() ?>
        <input type="hidden" name="answers_payload" value="">
      </form>

      <!-- Nút Chuyển Bước (Back / Next) -->
      <div class="quiz-wizard-actions">
        <button type="button" class="btn btn-outline" id="quizPrevBtn" style="display: none;">
          &larr; Quay lại câu trước
        </button>
        <button type="button" class="btn btn-primary" id="quizNextBtn" style="margin-left: auto;">
          Tiếp theo &rarr;
        </button>
      </div>

    <?php else: ?>
      <div class="empty-state">
        <div class="empty-icon">🧩</div>
        <h3 class="empty-title">Bộ câu hỏi trắc nghiệm đang được cập nhật</h3>
        <p class="empty-desc">Quản trị viên đang điều chỉnh lại ngân hàng câu hỏi định hướng.</p>
        <a href="<?= url('clubs.php') ?>" class="btn btn-primary">Khám phá danh sách CLB</a>
      </div>
    <?php endif; ?>

  </div>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
