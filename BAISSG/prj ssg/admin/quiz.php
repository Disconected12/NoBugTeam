<?php
/**
 * ClubHub - Quản Lý Bộ Quiz Trắc Nghiệm (Admin Quiz Management)
 */

define('CLUBHUB_INIT', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$page_title = 'Quản lý Bộ Quiz';
$db = get_db_connection();

// Xử lý Cập nhật hoặc Thêm câu hỏi
$edit_id = (int)($_GET['edit_id'] ?? 0);
$question = null;
$options = [];

if ($edit_id > 0) {
    $stmt = $db->prepare("SELECT * FROM quiz_questions WHERE id = ?");
    $stmt->execute([$edit_id]);
    $question = $stmt->fetch();

    if ($question) {
        $opt_stmt = $db->prepare("
            SELECT o.*, 
                   GROUP_CONCAT(CONCAT(w.attribute_key, ':', w.weight) SEPARATOR ', ') as weights_summary
            FROM quiz_options o
            LEFT JOIN quiz_option_weights w ON o.id = w.option_id
            WHERE o.question_id = ?
            GROUP BY o.id
            ORDER BY o.display_order ASC
        ");
        $opt_stmt->execute([$edit_id]);
        $options = $opt_stmt->fetchAll();
    }
}

// Xử lý Xóa câu hỏi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_question') {
    csrf_protect();
    $del_q_id = (int)($_POST['question_id'] ?? 0);
    if ($del_q_id > 0) {
        $db->prepare("DELETE FROM quiz_questions WHERE id = ?")->execute([$del_q_id]);
        flash_set('success', 'Đã xóa câu hỏi thành công.');
    }
    redirect('admin/quiz.php');
}

// Lưu chỉnh sửa câu hỏi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_question') {
    csrf_protect();

    $q_id = (int)($_POST['question_id'] ?? 0);
    $question_text = trim($_POST['question_text'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $question_type = $_POST['question_type'] ?? 'single';
    $display_order = (int)($_POST['display_order'] ?? 0);
    $is_active = !empty($_POST['is_active']) ? 1 : 0;

    if (empty($question_text)) {
        flash_set('error', 'Nội dung câu hỏi không được để trống.');
    } else {
        if ($q_id > 0) {
            $stmt = $db->prepare("
                UPDATE quiz_questions SET
                    question_text = ?, description = ?, question_type = ?,
                    display_order = ?, is_active = ?
                WHERE id = ?
            ");
            $stmt->execute([$question_text, $description, $question_type, $display_order, $is_active, $q_id]);
            flash_set('success', 'Cập nhật câu hỏi thành công.');
        } else {
            $stmt = $db->prepare("
                INSERT INTO quiz_questions (question_text, description, question_type, display_order, is_active)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$question_text, $description, $question_type, $display_order, $is_active]);
            flash_set('success', 'Thêm câu hỏi mới thành công.');
        }
        redirect('admin/quiz.php');
    }
}

// Xử lý Thêm đáp án mới cho câu hỏi đang sửa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_option') {
    csrf_protect();

    $q_id = (int)($_POST['question_id'] ?? 0);
    $option_text = trim($_POST['option_text'] ?? '');
    $attr_key = trim($_POST['attribute_key'] ?? '');
    $weight = floatval($_POST['weight'] ?? 3.0);
    $disp_order = (int)($_POST['display_order'] ?? 0);

    if (!empty($option_text) && $q_id > 0) {
        $db->beginTransaction();
        $ins_opt = $db->prepare("INSERT INTO quiz_options (question_id, option_text, display_order) VALUES (?, ?, ?)");
        $ins_opt->execute([$q_id, $option_text, $disp_order]);
        $new_opt_id = $db->lastInsertId();

        if (!empty($attr_key)) {
            $ins_w = $db->prepare("INSERT INTO quiz_option_weights (option_id, attribute_key, weight) VALUES (?, ?, ?)");
            $ins_w->execute([$new_opt_id, $attr_key, $weight]);
        }
        $db->commit();
        flash_set('success', 'Thêm đáp án và gán trọng số thành công.');
    }
    redirect('admin/quiz.php?edit_id=' . $q_id);
}

// Xử lý Xóa đáp án
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_option') {
    csrf_protect();
    $opt_id = (int)($_POST['option_id'] ?? 0);
    $q_id = (int)($_POST['question_id'] ?? 0);
    if ($opt_id > 0) {
        $db->prepare("DELETE FROM quiz_options WHERE id = ?")->execute([$opt_id]);
        flash_set('success', 'Đã xóa đáp án.');
    }
    redirect('admin/quiz.php?edit_id=' . $q_id);
}

// Lấy danh sách toàn bộ câu hỏi
$questions = $db->query("
    SELECT q.*, COUNT(o.id) as option_count
    FROM quiz_questions q
    LEFT JOIN quiz_options o ON q.id = o.question_id
    GROUP BY q.id
    ORDER BY q.display_order ASC
")->fetchAll();

$attribute_keys = $db->query("SELECT attribute_key, name FROM quiz_attribute_keys ORDER BY attribute_key")->fetchAll();

include __DIR__ . '/includes/admin_header.php';
?>

<!-- Form Thêm / Sửa Câu Hỏi (Nếu có edit_id hoặc tạo mới) -->
<?php if ($edit_id > 0 || (isset($_GET['action']) && $_GET['action'] === 'create')): ?>
  <div class="admin-card">
    <div class="admin-card-header">
      <h2 class="admin-card-title"><?= $edit_id ? 'Chỉnh Sửa Câu Hỏi #' . $edit_id : 'Thêm Câu Hỏi Mới' ?></h2>
      <a href="<?= url('admin/quiz.php') ?>" class="btn-admin btn-admin-outline">&larr; Quay lại</a>
    </div>

    <form action="<?= url('admin/quiz.php') ?>" method="POST">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save_question">
      <input type="hidden" name="question_id" value="<?= $edit_id ?>">

      <div class="form-grid">
        <div class="form-full">
          <label class="admin-label" for="question_text">Nội dung câu hỏi <span style="color:red;">*</span></label>
          <input type="text" name="question_text" id="question_text" class="admin-input" value="<?= e($question['question_text'] ?? '') ?>" required>
        </div>

        <div>
          <label class="admin-label" for="description">Gợi ý / Mô tả phụ</label>
          <input type="text" name="description" id="description" class="admin-input" value="<?= e($question['description'] ?? '') ?>">
        </div>

        <div>
          <label class="admin-label" for="question_type">Loại câu hỏi <span style="color:red;">*</span></label>
          <select name="question_type" id="question_type" class="admin-select">
            <option value="single" <?= ($question['question_type'] ?? '') === 'single' ? 'selected' : '' ?>>Chọn một (Single Choice)</option>
            <option value="multiple" <?= ($question['question_type'] ?? '') === 'multiple' ? 'selected' : '' ?>>Chọn nhiều (Multiple Choice)</option>
          </select>
        </div>

        <div>
          <label class="admin-label" for="display_order">Thứ tự hiển thị</label>
          <input type="number" name="display_order" id="display_order" class="admin-input" value="<?= e($question['display_order'] ?? '1') ?>">
        </div>

        <div style="display: flex; align-items: center;">
          <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin-top: 20px;">
            <input type="checkbox" name="is_active" value="1" <?= ($question['is_active'] ?? 1) ? 'checked' : '' ?>>
            <strong>Bật câu hỏi này trong bài quiz</strong>
          </label>
        </div>
      </div>

      <div style="margin-top: 20px;">
        <button type="submit" class="btn-admin btn-admin-primary">
          <?= $edit_id ? 'Lưu Thay Đổi Câu Hỏi' : 'Tạo Câu Hỏi' ?>
        </button>
      </div>
    </form>

    <!-- Quản lý các đáp án của câu hỏi này -->
    <?php if ($edit_id > 0): ?>
      <div style="margin-top: 36px; border-top: 2px solid var(--admin-border); padding-top: 24px;">
        <h3 style="font-size: 1.15rem; color: var(--admin-primary); margin-bottom: 16px;">
          Danh Sách Đáp Án & Trọng Số Thuộc Tính
        </h3>

        <table class="admin-table" style="margin-bottom: 24px;">
          <thead>
            <tr>
              <th>Thứ tự</th>
              <th>Nội dung đáp án</th>
              <th>Thuộc tính & Trọng số gắn kết</th>
              <th style="text-align: right;">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($options as $opt): ?>
              <tr>
                <td><?= (int)$opt['display_order'] ?></td>
                <td><strong><?= e($opt['option_text']) ?></strong></td>
                <td>
                  <span style="background-color: #F8FAFC; padding: 4px 8px; border-radius: 4px; border: 1px solid var(--admin-border); font-size: 0.85rem;">
                    <?= !empty($opt['weights_summary']) ? e($opt['weights_summary']) : 'Chưa gắn thuộc tính' ?>
                  </span>
                </td>
                <td style="text-align: right;">
                  <form action="<?= url('admin/quiz.php?edit_id=' . $edit_id) ?>" method="POST" style="display: inline;" class="btn-confirm-delete" data-confirm="Xóa đáp án này?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete_option">
                    <input type="hidden" name="option_id" value="<?= $opt['id'] ?>">
                    <input type="hidden" name="question_id" value="<?= $edit_id ?>">
                    <button type="submit" class="btn-admin btn-admin-danger" style="padding: 2px 8px; font-size: 0.8rem;">
                      Xóa
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <!-- Form Thêm Đáp Án Mới -->
        <div style="background-color: #F8FAFC; border: 1px solid var(--admin-border); border-radius: 8px; padding: 18px;">
          <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--admin-primary); margin-bottom: 12px;">
            + Thêm Đáp Án Mới
          </h4>
          <form action="<?= url('admin/quiz.php?edit_id=' . $edit_id) ?>" method="POST" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add_option">
            <input type="hidden" name="question_id" value="<?= $edit_id ?>">

            <div style="flex: 2; min-width: 240px;">
              <label class="admin-label">Nội dung đáp án</label>
              <input type="text" name="option_text" class="admin-input" placeholder="Ví dụ: Thích lập trình, thích biểu diễn..." required>
            </div>

            <div style="flex: 1; min-width: 180px;">
              <label class="admin-label">Gắn với Thuộc tính</label>
              <select name="attribute_key" class="admin-select">
                <option value="">-- Chọn thuộc tính --</option>
                <?php foreach ($attribute_keys as $ak): ?>
                  <option value="<?= e($ak['attribute_key']) ?>">
                    <?= e($ak['name']) ?> (<?= e($ak['attribute_key']) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div style="width: 100px;">
              <label class="admin-label">Trọng số (1-5)</label>
              <input type="number" step="0.5" min="0.5" max="5.0" name="weight" class="admin-input" value="3.5">
            </div>

            <div style="width: 80px;">
              <label class="admin-label">Thứ tự</label>
              <input type="number" name="display_order" class="admin-input" value="<?= count($options) + 1 ?>">
            </div>

            <div>
              <button type="submit" class="btn-admin btn-admin-primary">Thêm</button>
            </div>
          </form>
        </div>
      </div>
    <?php endif; ?>

  </div>
<?php else: ?>

  <!-- Danh sách Toàn bộ Câu Hỏi Quiz -->
  <div class="admin-card">
    <div class="admin-card-header">
      <h2 class="admin-card-title">Ngân Hàng Câu Hỏi Định Hướng (<?= count($questions) ?>)</h2>
      <a href="<?= url('admin/quiz.php?action=create') ?>" class="btn-admin btn-admin-primary">
        + Thêm Câu Hỏi Mới
      </a>
    </div>

    <table class="admin-table">
      <thead>
        <tr>
          <th>Thứ tự</th>
          <th>Nội dung câu hỏi</th>
          <th>Loại câu hỏi</th>
          <th>Số đáp án</th>
          <th>Trạng thái</th>
          <th style="text-align: right;">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($questions as $q): ?>
          <tr>
            <td><strong>#<?= (int)$q['display_order'] ?></strong></td>
            <td>
              <strong><?= e($q['question_text']) ?></strong><br>
              <span style="font-size: 0.8rem; color: var(--admin-text-muted);"><?= e($q['description']) ?></span>
            </td>
            <td>
              <?= $q['question_type'] === 'multiple' ? 'Chọn nhiều' : 'Chọn một' ?>
            </td>
            <td>
              <span class="badge badge-category"><?= (int)$q['option_count'] ?> đáp án</span>
            </td>
            <td>
              <span style="color: <?= $q['is_active'] ? 'var(--admin-success)' : 'var(--admin-danger)' ?>; font-weight: 700;">
                <?= $q['is_active'] ? '● Bật' : '○ Tắt' ?>
              </span>
            </td>
            <td style="text-align: right;">
              <div class="action-buttons" style="justify-content: flex-end;">
                <a href="<?= url('admin/quiz.php?edit_id=' . $q['id']) ?>" class="btn-admin btn-admin-outline" style="padding: 4px 10px; font-size: 0.8rem;">
                  Sửa & Gán đáp án
                </a>
                <form action="<?= url('admin/quiz.php') ?>" method="POST" style="display: inline;" class="btn-confirm-delete" data-confirm="Bạn có chắc muốn xóa câu hỏi #<?= $q['id'] ?> và toàn bộ đáp án liên quan?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete_question">
                  <input type="hidden" name="question_id" value="<?= $q['id'] ?>">
                  <button type="submit" class="btn-admin btn-admin-danger" style="padding: 4px 10px; font-size: 0.8rem;">
                    Xóa
                  </button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

<?php endif; ?>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
