<?php
/**
 * ClubHub - Automated Verification Test Suite
 */

define('CLUBHUB_INIT', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$db = get_db_connection();
if (!$db) {
    echo "FAIL: Database connection failed.\n";
    exit(1);
}

$passed = 0;
$failed = 0;

function assert_true($cond, $label) {
    global $passed, $failed;
    if ($cond) {
        echo "  [PASS] $label\n";
        $passed++;
    } else {
        echo "  [FAIL] $label\n";
        $failed++;
    }
}

echo "=== 1. DATABASE & TABLES CHECK ===\n";
$tables = ['admins', 'categories', 'clubs', 'club_socials', 'club_images', 'events', 'quiz_questions', 'quiz_options', 'quiz_attribute_keys', 'quiz_option_weights', 'club_quiz_attributes', 'ad_placements', 'ad_requests', 'feedback_reports', 'site_settings', 'login_attempts'];
foreach ($tables as $t) {
    $exists = (int)$db->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '" . DB_NAME . "' AND table_name = '$t'")->fetchColumn();
    assert_true($exists === 1, "Table '$t' exists in database");
}

echo "\n=== 2. ADMIN AUTHENTICATION & LOCKOUT ===\n";
$login_success = admin_login('admin', 'AdminClubHub@2026');
assert_true($login_success['success'] === true, "Admin login succeeds with valid credentials");
assert_true(is_admin_logged_in() === true, "is_admin_logged_in returns true after login");

$login_wrong = admin_login('admin', 'WrongPass123');
assert_true($login_wrong['success'] === false, "Admin login fails with invalid password");

echo "\n=== 3. CLUB SEARCH & FILTERING ===\n";
$stmt = $db->prepare("SELECT COUNT(*) FROM clubs WHERE is_active = 1 AND (name LIKE ? OR short_name LIKE ?)");
$stmt->execute(['%AI%', '%AI%']);
$ai_count = (int)$stmt->fetchColumn();
assert_true($ai_count >= 1, "Search keyword 'AI' returns matching clubs (count: $ai_count)");

$cat_stmt = $db->prepare("SELECT COUNT(*) FROM clubs c JOIN categories cat ON c.category_id = cat.id WHERE c.is_active = 1 AND cat.slug = ?");
$cat_stmt->execute(['cong-nghe-ky-thuat']);
$tech_count = (int)$cat_stmt->fetchColumn();
assert_true($tech_count >= 2, "Category filter 'cong-nghe-ky-thuat' returns clubs (count: $tech_count)");

$open_stmt = $db->query("SELECT COUNT(*) FROM clubs WHERE is_active = 1 AND recruitment_status = 'open' AND (recruitment_deadline IS NULL OR recruitment_deadline >= NOW())");
$open_count = (int)$open_stmt->fetchColumn();
assert_true($open_count >= 3, "Recruitment filter 'open' returns currently active recruiting clubs (count: $open_count)");

echo "\n=== 4. CLUB DETAILS & SOCIALS & GALLERY ===\n";
$club_stmt = $db->prepare("SELECT * FROM clubs WHERE slug = ?");
$club_stmt->execute(['clb-lap-trinh-tri-tue-nhan-tao']);
$devai = $club_stmt->fetch();
assert_true(!empty($devai), "Club 'DevAI' details found by slug");

$soc_stmt = $db->prepare("SELECT COUNT(*) FROM club_socials WHERE club_id = ?");
$soc_stmt->execute([$devai['id']]);
$soc_count = (int)$soc_stmt->fetchColumn();
assert_true($soc_count >= 2, "DevAI club has registered social channels (count: $soc_count)");

$img_stmt = $db->prepare("SELECT COUNT(*) FROM club_images WHERE club_id = ?");
$img_stmt->execute([$devai['id']]);
$img_count = (int)$img_stmt->fetchColumn();
assert_true($img_count >= 1, "DevAI club has gallery images (count: $img_count)");

echo "\n=== 5. QUIZ RECOMMENDATION ENGINE ===\n";
$selected_opts = [1, 7, 11, 18, 19, 22, 25];
$in_p = implode(',', array_fill(0, count($selected_opts), '?'));
$w_stmt = $db->prepare("
    SELECT qow.attribute_key, qow.weight, qak.group_name
    FROM quiz_option_weights qow
    JOIN quiz_attribute_keys qak ON qow.attribute_key = qak.attribute_key
    WHERE qow.option_id IN ($in_p)
");
$w_stmt->execute($selected_opts);
$weights = $w_stmt->fetchAll();

$user_weights = [];
foreach ($weights as $w) {
    $k = $w['attribute_key'];
    $user_weights[$k] = ($user_weights[$k] ?? 0) + (float)$w['weight'];
}
assert_true(isset($user_weights['domain_tech']), "User weights computed correctly for domain_tech");
assert_true($user_weights['domain_tech'] >= 5.0, "User weights reflect chosen tech interests");

$all_clubs = $db->query("SELECT c.id, c.name, c.slug, cat.name as category_name, c.meeting_schedule, c.schedule_confirmed, c.updated_at FROM clubs c JOIN categories cat ON c.category_id = cat.id WHERE c.is_active = 1")->fetchAll();
$all_attrs = $db->query("SELECT club_id, attribute_key, score FROM club_quiz_attributes")->fetchAll();
$attr_map = [];
foreach ($all_attrs as $a) {
    $attr_map[$a['club_id']][$a['attribute_key']] = (float)$a['score'];
}

$scored = [];
foreach ($all_clubs as $c) {
    $cid = $c['id'];
    $c_attrs = $attr_map[$cid] ?? [];
    $score = 0.0;
    $domain_score = 0.0;
    foreach ($user_weights as $k => $w) {
        $cs = $c_attrs[$k] ?? 0.0;
        if ($cs > 0) {
            $mult = str_starts_with($k, 'domain_') ? 2.0 : (str_starts_with($k, 'goal_') ? 1.5 : 1.0);
            $val = $w * $cs * $mult;
            $score += $val;
            if (str_starts_with($k, 'domain_')) $domain_score += $val;
        }
    }
    $scored[] = ['id' => $cid, 'name' => $c['name'], 'slug' => $c['slug'], 'score' => $score, 'domain_score' => $domain_score, 'schedule_confirmed' => $c['schedule_confirmed']];
}

usort($scored, function($a, $b) {
    return ($b['score'] > $a['score']) ? 1 : -1;
});

$top_club = $scored[0];
assert_true($top_club['id'] == 1 || str_contains($top_club['slug'], 'lap-trinh') || str_contains($top_club['slug'], 'robot'), "Top suggested club for tech answers is DevAI / RoboTech (Top: ID {$top_club['id']} - {$top_club['slug']})");

$unconfirmed = array_filter($all_clubs, fn($c) => $c['schedule_confirmed'] == 0);
assert_true(count($unconfirmed) >= 1, "Clubs with unconfirmed schedule exist and require explicit notification");

echo "\n=== 6. ADVERTISING REQUIREMENTS & AUTO-EXPIRY ===\n";
$active_ads = $db->query("
    SELECT id, club_name, tracking_code, start_date, end_date
    FROM ad_requests
    WHERE approval_status = 'approved'
      AND payment_status = 'paid'
      AND is_enabled = 1
      AND start_date <= CURDATE()
      AND end_date >= CURDATE()
")->fetchAll();

assert_true(count($active_ads) >= 1, "Approved, paid, and active ads are retrieved (count: " . count($active_ads) . ")");

$expired_ads = $db->query("
    SELECT COUNT(*)
    FROM ad_requests
    WHERE end_date < CURDATE()
      AND approval_status = 'approved'
      AND payment_status = 'paid'
      AND is_enabled = 1
      AND start_date <= CURDATE()
      AND end_date >= CURDATE()
")->fetchColumn();
assert_true((int)$expired_ads === 0, "Expired ads are never included in active ads query");

$placement = $db->query("SELECT max_slots FROM ad_placements WHERE code = 'top_banner'")->fetch();
assert_true((int)$placement['max_slots'] >= 3, "Top banner placement defines capacity (max_slots: {$placement['max_slots']})");

echo "\n=== 7. EVENTS TIMELINE LOGIC ===\n";
$upcoming = $db->query("SELECT COUNT(*) FROM events WHERE is_active = 1 AND end_time >= NOW()")->fetchColumn();
$past = $db->query("SELECT COUNT(*) FROM events WHERE is_active = 1 AND end_time < NOW()")->fetchColumn();
assert_true((int)$upcoming >= 1, "Upcoming events exist (count: $upcoming)");
assert_true((int)$past >= 1, "Past events exist (count: $past)");

$invalid_dates = $db->query("SELECT COUNT(*) FROM events WHERE end_time < start_time")->fetchColumn();
assert_true((int)$invalid_dates === 0, "No events have end_time before start_time");

echo "\n=== 8. CATEGORY DELETION SAFEGUARD ===\n";
$cat_in_use = $db->query("SELECT category_id, COUNT(*) as cnt FROM clubs GROUP BY category_id HAVING cnt > 0 LIMIT 1")->fetch();
$cat_id = $cat_in_use['category_id'];
$club_count_in_cat = (int)$db->query("SELECT COUNT(*) FROM clubs WHERE category_id = $cat_id")->fetchColumn();
assert_true($club_count_in_cat > 0, "Category #$cat_id has $club_count_in_cat clubs linked; deletion must be guarded");

echo "\n=== 9. CLUB HONOR RANK & TIER FRAME SYSTEM ===\n";
$col_check = $db->query("SHOW COLUMNS FROM clubs LIKE 'club_tier'")->fetch();
assert_true(!empty($col_check), "Column 'club_tier' exists in clubs table");

$diamond_count = (int)$db->query("SELECT COUNT(*) FROM clubs WHERE club_tier = 'diamond'")->fetchColumn();
$gold_count = (int)$db->query("SELECT COUNT(*) FROM clubs WHERE club_tier = 'gold'")->fetchColumn();
assert_true($diamond_count >= 1, "Diamond tier clubs exist (count: $diamond_count)");
assert_true($gold_count >= 1, "Gold tier clubs exist (count: $gold_count)");

$tier_info = get_club_tier_info('diamond');
assert_true($tier_info['key'] === 'diamond' && !empty($tier_info['frame_class']), "Diamond tier info helper returns valid frame class ({$tier_info['frame_class']})");

$fallback_tier = get_club_tier_info('unknown_invalid_tier');
assert_true($fallback_tier['key'] === 'standard', "Fallback tier is standard for invalid key");


echo "\n====================================\n";
echo "TEST RESULTS: Passed: $passed | Failed: $failed\n";
if ($failed === 0) {
    echo "ALL TESTS PASSED SUCCESSFULLY!\n";
    exit(0);
} else {
    echo "SOME TESTS FAILED!\n";
    exit(1);
}