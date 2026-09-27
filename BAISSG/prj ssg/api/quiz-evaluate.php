<?php
/**
 * ClubHub - Thuật toán Chấm Điểm & Đề Xuất CLB (Quiz Recommendation Engine)
 * Chấm điểm hoàn toàn ở Backend theo ma trận trọng số và thuộc tính có cấu trúc
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('quiz.php');
}

csrf_protect();

$db = get_db_connection();
if (!$db) {
    flash_set('error', 'Lỗi hệ thống: Không thể kết nối cơ sở dữ liệu.');
    redirect('quiz.php');
}

// Lấy payload đáp án từ request POST
$payload_raw = $_POST['answers_payload'] ?? '';
$user_answers = json_decode($payload_raw, true);

if (empty($user_answers) || !is_array($user_answers)) {
    flash_set('error', 'Dữ liệu bài trắc nghiệm không hợp lệ. Vui lòng thử lại.');
    redirect('quiz.php');
}

// 1. Thu thập danh sách các option_id mà người dùng đã chọn
$selected_option_ids = [];
foreach ($user_answers as $question_id => $options) {
    if (is_array($options)) {
        foreach ($options as $opt_id) {
            $selected_option_ids[] = (int)$opt_id;
        }
    } else {
        $selected_option_ids[] = (int)$options;
    }
}
$selected_option_ids = array_unique(array_filter($selected_option_ids));

if (empty($selected_option_ids)) {
    flash_set('error', 'Bạn chưa chọn câu trả lời nào.');
    redirect('quiz.php');
}

// 2. Lấy trọng số các thuộc tính tương ứng với các đáp án đã chọn từ MySQL
$in_placeholders = implode(',', array_fill(0, count($selected_option_ids), '?'));
$weight_sql = "
    SELECT qow.attribute_key, qow.weight, qak.name as attribute_name, qak.group_name
    FROM quiz_option_weights qow
    JOIN quiz_attribute_keys qak ON qow.attribute_key = qak.attribute_key
    WHERE qow.option_id IN ($in_placeholders)
";
$stmt = $db->prepare($weight_sql);
$stmt->execute($selected_option_ids);
$user_weights_rows = $stmt->fetchAll();

// Tổng hợp trọng số của người dùng theo từng attribute_key
$user_attribute_weights = [];
$user_selected_groups = [];

foreach ($user_weights_rows as $row) {
    $key = $row['attribute_key'];
    $w = (float)$row['weight'];
    if (!isset($user_attribute_weights[$key])) {
        $user_attribute_weights[$key] = 0;
    }
    $user_attribute_weights[$key] += $w;
    $user_selected_groups[$row['group_name']][] = $key;
}

// 3. Lấy toàn bộ các CLB đang công khai (is_active = 1) kèm điểm thuộc tính của chúng
// LƯU Ý BẢO VỆ TÍNH TRUNG THỰC: Hoàn toàn KHÔNG xét đến trạng thái quảng cáo ở đây!
$clubs_sql = "
    SELECT c.id, c.name, c.short_name, c.slug, c.logo, c.cover_image, c.summary,
           c.category_id, cat.name as category_name,
           c.meeting_schedule, c.schedule_confirmed, c.requirements,
           c.recruitment_status, c.recruitment_deadline, c.application_link,
           c.updated_at
    FROM clubs c
    JOIN categories cat ON c.category_id = cat.id
    WHERE c.is_active = 1
";
$clubs_stmt = $db->query($clubs_sql);
$all_clubs = $clubs_stmt->fetchAll();

// Lấy thuộc tính của tất cả các CLB
$club_attr_stmt = $db->query("SELECT club_id, attribute_key, score FROM club_quiz_attributes");
$club_attrs_raw = $club_attr_stmt->fetchAll();

$club_attributes = [];
foreach ($club_attrs_raw as $ar) {
    $club_attributes[$ar['club_id']][$ar['attribute_key']] = (float)$ar['score'];
}

// Lấy kênh mạng xã hội của các CLB
$socials_stmt = $db->query("SELECT club_id, platform, url FROM club_socials");
$socials_raw = $socials_stmt->fetchAll();
$club_socials_map = [];
foreach ($socials_raw as $s) {
    $club_socials_map[$s['club_id']][$s['platform']] = $s['url'];
}

// 4. Chấm điểm từng CLB theo công thức trọng số chuẩn hóa
$evaluated_clubs = [];

foreach ($all_clubs as $club) {
    $club_id = $club['id'];
    $attrs = $club_attributes[$club_id] ?? [];
    
    $total_score = 0.0;
    $matched_reasons = [];
    $considerations = [];

    // Ưu tiên 1: Điểm Lĩnh vực & Sở thích (Weight nhân đôi)
    $domain_score = 0.0;
    // Ưu tiên 2: Điểm Mục tiêu
    $goal_score = 0.0;

    foreach ($user_attribute_weights as $attr_key => $user_weight) {
        $club_score = $attrs[$attr_key] ?? 0.0;
        
        if ($club_score > 0) {
            // Hệ số nhân tùy theo nhóm thuộc tính
            if (str_starts_with($attr_key, 'domain_')) {
                $weighted = ($user_weight * $club_score) * 2.0;
                $domain_score += $weighted;
                if ($club_score >= 4.0) {
                    $matched_reasons[] = "Đúng sở thích và lĩnh vực hoạt động của bạn ({$club['category_name']}).";
                }
            } elseif (str_starts_with($attr_key, 'goal_')) {
                $weighted = ($user_weight * $club_score) * 1.5;
                $goal_score += $weighted;
                if ($club_score >= 4.0) {
                    if ($attr_key === 'goal_skills') $matched_reasons[] = "Môi trường lý tưởng để cọ xát và rèn luyện kỹ năng thực chiến.";
                    if ($attr_key === 'goal_networking') $matched_reasons[] = "Không khí gắn kết, giúp bạn mở rộng mối quan hệ với nhiều bạn bè mới.";
                    if ($attr_key === 'goal_passion') $matched_reasons[] = "Không gian tự do thỏa mãn đam mê và giải tỏa căng thẳng sau giờ học.";
                    if ($attr_key === 'goal_cv') $matched_reasons[] = "Hỗ trợ tích lũy kinh nghiệm dự án và giấy chứng nhận hoạt động chất lượng.";
                }
            } else {
                $weighted = ($user_weight * $club_score);
            }
            $total_score += $weighted;
        }
    }

    // 5. Kiểm tra thời gian & lịch sinh hoạt có cấu trúc
    if ($club['schedule_confirmed'] == 0) {
        $considerations[] = "Lịch sinh hoạt của CLB hiện đang điều chỉnh, cần xác nhận lại thời gian cụ thể trước khi đăng ký.";
    } else {
        // Kiểm tra tương thích khung giờ rảnh
        if (isset($user_attribute_weights['time_evening']) && !empty($attrs['time_evening']) && $attrs['time_evening'] >= 4.0) {
            $matched_reasons[] = "Lịch sinh hoạt vào buổi tối các ngày trong tuần phù hợp với thời gian rảnh của bạn.";
        }
        if (isset($user_attribute_weights['time_weekend']) && !empty($attrs['time_weekend']) && $attrs['time_weekend'] >= 4.0) {
            $matched_reasons[] = "Lịch sinh hoạt tập trung vào cuối tuần tương thích với quỹ thời gian của bạn.";
        }
    }

    // 6. Kiểm tra mức cam kết & điều kiện
    if (!empty($attrs['commit_high']) && $attrs['commit_high'] >= 4.5) {
        $considerations[] = "CLB đòi hỏi tinh thần trách nhiệm và mức cam kết thời gian cao cho các dự án.";
    }
    if (!empty($club['requirements'])) {
        $considerations[] = "Yêu cầu tuyển chọn: " . $club['requirements'];
    }

    // Làm sạch và lọc trùng lý do
    $matched_reasons = array_unique($matched_reasons);
    if (empty($matched_reasons)) {
        $matched_reasons[] = "Các hoạt động cơ bản của CLB có những điểm tương đồng với sở thích của bạn.";
    }

    $actual_recruit = check_actual_recruitment_status($club['recruitment_status'], $club['recruitment_deadline']);

    $evaluated_clubs[] = [
        'id'                  => $club['id'],
        'name'                => $club['name'],
        'short_name'          => $club['short_name'],
        'slug'                => $club['slug'],
        'logo'                => $club['logo'],
        'cover_image'         => $club['cover_image'],
        'summary'             => $club['summary'],
        'category_name'       => $club['category_name'],
        'recruitment_status'  => $actual_recruit['status'],
        'recruitment_badge'   => $actual_recruit['badge'],
        'recruitment_is_open' => $actual_recruit['is_open'],
        'application_link'    => $club['application_link'],
        'facebook_url'        => $club_socials_map[$club['id']]['facebook'] ?? '',
        'total_score'         => round($total_score, 1),
        'domain_score'        => $domain_score,
        'matched_reasons'     => array_slice($matched_reasons, 0, 3),
        'considerations'      => array_slice($considerations, 0, 2),
        'updated_at'          => $club['updated_at']
    ];
}

// 7. Sắp xếp thứ hạng (Ranking)
// Quy tắc phân định rõ ràng khi bằng điểm:
// 1. Điểm tổng cao hơn
// 2. Điểm Lĩnh vực & Sở thích cao hơn
// 3. Thời điểm cập nhật thông tin mới nhất
usort($evaluated_clubs, function($a, $b) {
    if (abs($b['total_score'] - $a['total_score']) > 0.001) {
        return ($b['total_score'] > $a['total_score']) ? 1 : -1;
    }
    if (abs($b['domain_score'] - $a['domain_score']) > 0.001) {
        return ($b['domain_score'] > $a['domain_score']) ? 1 : -1;
    }
    $date_cmp = strcmp($b['updated_at'], $a['updated_at']);
    if ($date_cmp !== 0) {
        return $date_cmp;
    }
    return strcmp($a['name'], $b['name']);
});

// 8. Lấy tối đa 3 CLB phù hợp nhất
// Đặt ngưỡng tối thiểu để tránh gợi ý bừa nếu người dùng trả lời ngẫu nhiên không ăn khớp
$threshold_score = 15.0;
$top_matches = [];

foreach ($evaluated_clubs as $item) {
    if ($item['total_score'] >= $threshold_score) {
        $top_matches[] = $item;
    }
    if (count($top_matches) >= 3) {
        break;
    }
}

// Lưu kết quả vào Session để hiển thị ở trang quiz-result.php
$_SESSION['quiz_evaluation'] = [
    'has_results' => !empty($top_matches),
    'top_matches' => $top_matches,
    'total_evaluated' => count($evaluated_clubs),
    'evaluated_at' => date('H:i, d/m/Y')
];

redirect('quiz-result.php');
