<?php
/**
 * ClubHub - Danh Sách Câu Lạc Bộ (Tìm kiếm, Lọc theo lĩnh vực & trạng thái tuyển, Phân trang)
 */

define('CLUBHUB_INIT', true);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$db = get_db_connection();

$page_title = 'Danh sách Câu lạc bộ';
$page_desc = 'Tìm kiếm và khám phá toàn bộ các câu lạc bộ, đội, nhóm sinh viên trong trường đại học.';

// Lấy tham số tìm kiếm & bộ lọc
$search_query = trim($_GET['q'] ?? '');
$category_filter = trim($_GET['category'] ?? '');
$status_filter = trim($_GET['status'] ?? 'all');
$current_page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 8;
$offset = ($current_page - 1) * $per_page;

// Lấy danh mục để hiển thị trên bộ lọc
$categories = [];
if ($db) {
    try {
        $cat_stmt = $db->query("SELECT id, name, slug FROM categories WHERE is_active = 1 ORDER BY display_order ASC");
        $categories = $cat_stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Lỗi tải danh mục: " . $e->getMessage());
    }
}

// Xây dựng câu truy vấn SQL động an toàn với Prepared Statements
$where_clauses = ["c.is_active = 1"];
$params = [];

if (!empty($search_query)) {
    $where_clauses[] = "(c.name LIKE ? OR c.short_name LIKE ? OR c.summary LIKE ?)";
    $search_like = '%' . $search_query . '%';
    $params[] = $search_like;
    $params[] = $search_like;
    $params[] = $search_like;
}

if (!empty($category_filter)) {
    $where_clauses[] = "cat.slug = ?";
    $params[] = $category_filter;
}

if ($status_filter === 'open') {
    $where_clauses[] = "c.recruitment_status = 'open' AND (c.recruitment_deadline IS NULL OR c.recruitment_deadline >= NOW())";
} elseif ($status_filter === 'upcoming') {
    $where_clauses[] = "c.recruitment_status = 'upcoming'";
} elseif ($status_filter === 'closed') {
    $where_clauses[] = "(c.recruitment_status = 'closed' OR (c.recruitment_status = 'open' AND c.recruitment_deadline < NOW()))";
}

$where_sql = implode(" AND ", $where_clauses);

// Đếm tổng số bản ghi
$total_rows = 0;
$total_pages = 0;
$clubs = [];

if ($db) {
    try {
        $count_sql = "
            SELECT COUNT(*)
            FROM clubs c
            JOIN categories cat ON c.category_id = cat.id
            WHERE {$where_sql}
        ";
        $count_stmt = $db->prepare($count_sql);
        $count_stmt->execute($params);
        $total_rows = (int)$count_stmt->fetchColumn();

        $total_pages = ceil($total_rows / $per_page);

        // Truy vấn dữ liệu trang hiện tại
        $data_sql = "
            SELECT c.id, c.name, c.short_name, c.slug, c.logo, c.cover_image, c.summary,
                   c.recruitment_status, c.recruitment_deadline, cat.name as category_name
            FROM clubs c
            JOIN categories cat ON c.category_id = cat.id
            WHERE {$where_sql}
            ORDER BY c.is_featured DESC, c.updated_at DESC
            LIMIT ? OFFSET ?
        ";
        $data_stmt = $db->prepare($data_sql);
        
        $param_index = 1;
        foreach ($params as $val) {
            $data_stmt->bindValue($param_index++, $val);
        }
        $data_stmt->bindValue($param_index++, $per_page, PDO::PARAM_INT);
        $data_stmt->bindValue($param_index++, $offset, PDO::PARAM_INT);
        
        $data_stmt->execute();
        $clubs = $data_stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Lỗi tải danh sách CLB: " . $e->getMessage());
    }
}

// Xây dựng URL phân trang giữ nguyên các tham số bộ lọc
function get_pagination_url($page_num) {
    $params = $_GET;
    $params['page'] = $page_num;
    return url('clubs.php?' . http_build_query($params));
}

include __DIR__ . '/includes/header.php';
?>

<main class="container" style="padding-top: 30px; padding-bottom: 60px;">

  <!-- Tiêu đề trang -->
  <div class="section-header" style="margin-bottom: 20px;">
    <div class="section-title-wrap">
      <span class="section-tag">Khám Phá Cộng Đồng</span>
      <h1 class="section-title">Danh Sách Câu Lạc Bộ Sinh Viên</h1>
      <p class="section-subtitle">
        Tìm thấy <strong><?= $total_rows ?></strong> câu lạc bộ phù hợp với tiêu chí của bạn
      </p>
    </div>
  </div>

  <!-- Thanh Bộ Lọc & Tìm Kiếm (Filter Bar) -->
  <div class="filter-bar">
    <form action="<?= url('clubs.php') ?>" method="GET" class="filter-form">
      <!-- Tìm theo tên -->
      <div class="filter-group search-group">
        <label class="form-label" for="filter_q">Từ khóa tìm kiếm</label>
        <input type="text" name="q" id="filter_q" class="form-control" placeholder="Tên câu lạc bộ hoặc từ viết tắt..." value="<?= e($search_query) ?>">
      </div>

      <!-- Lọc Lĩnh vực -->
      <div class="filter-group">
        <label class="form-label" for="filter_cat">Lĩnh vực hoạt động</label>
        <select name="category" id="filter_cat" class="form-control">
          <option value="">-- Tất cả lĩnh vực --</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= e($c['slug']) ?>" <?= $category_filter === $c['slug'] ? 'selected' : '' ?>>
              <?= e($c['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Lọc Trạng thái tuyển -->
      <div class="filter-group">
        <label class="form-label" for="filter_status">Trạng thái tuyển</label>
        <select name="status" id="filter_status" class="form-control">
          <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>Tất cả trạng thái</option>
          <option value="open" <?= $status_filter === 'open' ? 'selected' : '' ?>>Đang mở đơn tuyển</option>
          <option value="upcoming" <?= $status_filter === 'upcoming' ? 'selected' : '' ?>>Sắp mở tuyển</option>
          <option value="closed" <?= $status_filter === 'closed' ? 'selected' : '' ?>>Đã đóng tuyển</option>
        </select>
      </div>

      <!-- Nút Áp dụng -->
      <div class="filter-group" style="flex: 0; min-width: auto; align-self: flex-end;">
        <button type="submit" class="btn btn-primary">Lọc kết quả</button>
      </div>

      <!-- Nút Xóa bộ lọc nếu đang có điều kiện lọc -->
      <?php if (!empty($search_query) || !empty($category_filter) || $status_filter !== 'all'): ?>
        <div class="filter-group" style="flex: 0; min-width: auto; align-self: flex-end;">
          <a href="<?= url('clubs.php') ?>" class="btn btn-outline" title="Đặt lại bộ lọc về mặc định">
            Xóa bộ lọc
          </a>
        </div>
      <?php endif; ?>
    </form>
  </div>

  <!-- Danh sách Thẻ CLB -->
  <?php if (!empty($clubs)): ?>
    <div class="grid grid-cols-4">
      <?php foreach ($clubs as $club): ?>
        <?php 
          $status_info = check_actual_recruitment_status($club['recruitment_status'], $club['recruitment_deadline']);
          $tier_info = get_club_tier_info($club['club_tier'] ?? 'standard');
        ?>
        <div class="club-card-rank-wrapper <?= e($tier_info['frame_class']) ?>">
          <div class="rank-frame-glow"></div>
          <div class="rank-frame-border-effect"></div>
          
          <?php if (!empty($tier_info['crest_img'])): ?>
            <div class="rank-frame-top-crest" title="Thứ hạng danh dự: <?= e($tier_info['name']) ?>">
              <img src="<?= asset($tier_info['crest_img']) ?>" alt="<?= e($tier_info['name']) ?>" class="crest-img">
              <span class="crest-title"><?= e($tier_info['short_name']) ?></span>
            </div>
          <?php endif; ?>

          <div class="club-card">
            <div class="club-card-cover">
              <img src="<?= upload_url($club['cover_image'], 'default-cover.png') ?>" alt="<?= e($club['name']) ?>" loading="lazy">
              <div class="club-card-status-badge">
                <?= $status_info['badge'] ?>
              </div>
              <div class="club-card-avatar <?= !empty($tier_info['frame_img']) ? 'avatar-with-rank-frame' : '' ?>">
                <?php if (!empty($tier_info['frame_img'])): ?>
                  <img src="<?= asset($tier_info['frame_img']) ?>" alt="Rank Frame" class="avatar-rank-frame-overlay">
                <?php endif; ?>
                <img src="<?= upload_url($club['logo'], 'default-club.png') ?>" alt="<?= e($club['name']) ?>" class="avatar-logo-img" loading="lazy">
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
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Phân trang (Pagination) -->
    <?php if ($total_pages > 1): ?>
      <div class="pagination-wrap" aria-label="Phân trang danh sách câu lạc bộ">
        <!-- Nút Trang trước -->
        <a href="<?= get_pagination_url($current_page - 1) ?>" class="page-btn <?= $current_page <= 1 ? 'disabled' : '' ?>" aria-label="Trang trước">
          &laquo;
        </a>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
          <a href="<?= get_pagination_url($i) ?>" class="page-btn <?= $i === $current_page ? 'active' : '' ?>">
            <?= $i ?>
          </a>
        <?php endfor; ?>

        <!-- Nút Trang sau -->
        <a href="<?= get_pagination_url($current_page + 1) ?>" class="page-btn <?= $current_page >= $total_pages ? 'disabled' : '' ?>" aria-label="Trang sau">
          &raquo;
        </a>
      </div>
    <?php endif; ?>

  <?php else: ?>
    <!-- Trạng thái trống (Empty State) -->
    <div class="empty-state">
      <div class="empty-icon" style="font-size: 2rem; color: var(--color-primary-light); font-weight: bold;">--</div>
      <h2 class="empty-title">Không tìm thấy câu lạc bộ nào phù hợp</h2>
      <p class="empty-desc">
        Không có kết quả nào khớp với các điều kiện tìm kiếm và bộ lọc hiện tại của bạn. Vui lòng thử tìm kiếm bằng từ khóa khác hoặc xóa bộ lọc.
      </p>
      <a href="<?= url('clubs.php') ?>" class="btn btn-primary">Xóa tất cả bộ lọc</a>
    </div>
  <?php endif; ?>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
