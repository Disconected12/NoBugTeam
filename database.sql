-- ==============================================================================
-- ClubHub - Hệ thống Khám phá & Quảng bá Câu lạc bộ Đại học
-- Cơ sở dữ liệu MySQL chuẩn UTF8MB4
-- Chú ý: Không dùng lệnh DROP DATABASE để bảo vệ dữ liệu môi trường máy chủ
-- ==============================================================================

CREATE DATABASE IF NOT EXISTS `clubhub_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `clubhub_db`;

-- 1. Bảng Quản trị viên (Admins)
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Quản trị viên mặc định (Tài khoản: admin / Mật khẩu: AdminClubHub@2026)
-- Bạn có thể dùng ngay tài khoản này hoặc xóa bảng admins để kiểm tra trang admin/setup.php
INSERT INTO `admins` (`id`, `username`, `password_hash`, `full_name`, `email`, `created_at`) 
VALUES (1, 'admin', '$2y$10$2RrLo83IxIpa/cPRLIkSa.mQUldTTQvVLOsUGhFO.QPq1TNyuDtzO', 'Ban Quản Trị ClubHub', 'admin@sinhvien.edu.vn', NOW())
ON DUPLICATE KEY UPDATE `username` = VALUES(`username`);

-- 2. Bảng Danh mục / Lĩnh vực hoạt động (Categories)
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT DEFAULT NULL,
  `icon` VARCHAR(50) NOT NULL DEFAULT 'star',
  `display_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Bảng Câu lạc bộ (Clubs)
CREATE TABLE IF NOT EXISTS `clubs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `name` VARCHAR(200) NOT NULL,
  `short_name` VARCHAR(50) DEFAULT NULL,
  `slug` VARCHAR(200) NOT NULL UNIQUE,
  `logo` VARCHAR(255) DEFAULT NULL,
  `cover_image` VARCHAR(255) DEFAULT NULL,
  `summary` VARCHAR(500) NOT NULL,
  `description` LONGTEXT NOT NULL,
  `objectives` TEXT DEFAULT NULL,
  `target_audience` TEXT DEFAULT NULL,
  `activities_achievements` LONGTEXT DEFAULT NULL,
  `meeting_schedule` VARCHAR(255) DEFAULT NULL,
  `meeting_location` VARCHAR(255) DEFAULT NULL,
  `meeting_frequency` VARCHAR(100) DEFAULT NULL,
  `schedule_confirmed` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1: Đã chốt lịch, 0: Cần xác nhận lịch',
  `requirements` TEXT DEFAULT NULL,
  `fee_info` VARCHAR(255) DEFAULT NULL,
  `recruitment_status` ENUM('closed', 'open', 'upcoming') NOT NULL DEFAULT 'closed',
  `recruitment_deadline` DATETIME DEFAULT NULL,
  `recruitment_process` TEXT DEFAULT NULL,
  `application_link` VARCHAR(500) DEFAULT NULL,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_clubs_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX `idx_clubs_status` (`is_active`, `recruitment_status`),
  INDEX `idx_clubs_featured` (`is_active`, `is_featured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Bảng Kênh liên hệ và mạng xã hội của CLB (Club Socials)
CREATE TABLE IF NOT EXISTS `club_socials` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `club_id` INT NOT NULL,
  `platform` VARCHAR(50) NOT NULL,
  `url` VARCHAR(500) NOT NULL,
  CONSTRAINT `fk_socials_club` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX `idx_socials_club` (`club_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Bảng Thư viện hình ảnh CLB (Club Images)
CREATE TABLE IF NOT EXISTS `club_images` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `club_id` INT NOT NULL,
  `image_url` VARCHAR(255) NOT NULL,
  `caption` VARCHAR(255) DEFAULT NULL,
  `display_order` INT NOT NULL DEFAULT 0,
  CONSTRAINT `fk_images_club` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX `idx_images_club` (`club_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Bảng Sự kiện của CLB (Events)
CREATE TABLE IF NOT EXISTS `events` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `club_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `poster_image` VARCHAR(255) DEFAULT NULL,
  `description` LONGTEXT NOT NULL,
  `start_time` DATETIME NOT NULL,
  `end_time` DATETIME NOT NULL,
  `location` VARCHAR(255) NOT NULL,
  `target_audience` VARCHAR(255) DEFAULT NULL,
  `fee` DECIMAL(12, 0) DEFAULT 0,
  `registration_deadline` DATETIME DEFAULT NULL,
  `registration_link` VARCHAR(500) DEFAULT NULL,
  `contact_info` VARCHAR(255) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_events_club` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX `idx_events_timeline` (`is_active`, `start_time`, `end_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Bảng Bộ câu hỏi Quiz (Quiz Questions)
CREATE TABLE IF NOT EXISTS `quiz_questions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `question_text` VARCHAR(255) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `question_type` ENUM('single', 'multiple') NOT NULL DEFAULT 'single',
  `display_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Bảng Đáp án Quiz (Quiz Options)
CREATE TABLE IF NOT EXISTS `quiz_options` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `question_id` INT NOT NULL,
  `option_text` VARCHAR(255) NOT NULL,
  `display_order` INT NOT NULL DEFAULT 0,
  CONSTRAINT `fk_options_question` FOREIGN KEY (`question_id`) REFERENCES `quiz_questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX `idx_options_question` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Bảng Từ khóa / Thuộc tính Quiz (Quiz Attribute Keys)
CREATE TABLE IF NOT EXISTS `quiz_attribute_keys` (
  `attribute_key` VARCHAR(50) PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `group_name` VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Bảng Trọng số của Đáp án đối với Thuộc tính (Quiz Option Weights)
CREATE TABLE IF NOT EXISTS `quiz_option_weights` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `option_id` INT NOT NULL,
  `attribute_key` VARCHAR(50) NOT NULL,
  `weight` FLOAT NOT NULL DEFAULT 1.0,
  CONSTRAINT `fk_weights_option` FOREIGN KEY (`option_id`) REFERENCES `quiz_options` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_weights_attribute` FOREIGN KEY (`attribute_key`) REFERENCES `quiz_attribute_keys` (`attribute_key`) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX `idx_weights_option` (`option_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Bảng Thuộc tính của CLB để tính điểm Quiz (Club Quiz Attributes)
CREATE TABLE IF NOT EXISTS `club_quiz_attributes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `club_id` INT NOT NULL,
  `attribute_key` VARCHAR(50) NOT NULL,
  `score` FLOAT NOT NULL DEFAULT 3.0 COMMENT 'Thang điểm từ 1.0 đến 5.0 phản ánh mức độ phù hợp',
  CONSTRAINT `fk_club_attr_club` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_club_attr_key` FOREIGN KEY (`attribute_key`) REFERENCES `quiz_attribute_keys` (`attribute_key`) ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY `uk_club_attr` (`club_id`, `attribute_key`),
  INDEX `idx_club_attr` (`club_id`, `attribute_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Bảng Vị trí Quảng cáo (Ad Placements)
CREATE TABLE IF NOT EXISTS `ad_placements` (
  `code` VARCHAR(50) PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `max_slots` INT NOT NULL DEFAULT 3 COMMENT 'Số lượng banner hiển thị luân phiên tối đa',
  `price_per_day` DECIMAL(12, 0) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Bảng Yêu cầu Thuê Vị trí Quảng cáo (Ad Requests)
CREATE TABLE IF NOT EXISTS `ad_requests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tracking_code` VARCHAR(30) NOT NULL UNIQUE,
  `club_name` VARCHAR(150) NOT NULL,
  `contact_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `poster_image` VARCHAR(255) NOT NULL,
  `destination_url` VARCHAR(500) NOT NULL,
  `placement_code` VARCHAR(50) NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `notes` TEXT DEFAULT NULL,
  `consent_agreed` TINYINT(1) NOT NULL DEFAULT 1,
  `quote_amount` DECIMAL(12, 0) DEFAULT 0,
  `payment_status` ENUM('unpaid', 'paid', 'refunded') NOT NULL DEFAULT 'unpaid',
  `approval_status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `admin_notes` TEXT DEFAULT NULL,
  `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_ads_placement` FOREIGN KEY (`placement_code`) REFERENCES `ad_placements` (`code`) ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX `idx_ads_active_display` (`approval_status`, `payment_status`, `is_enabled`, `start_date`, `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Bảng Báo cáo / Phản ánh Thông tin (Feedback Reports)
CREATE TABLE IF NOT EXISTS `feedback_reports` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `club_id` INT DEFAULT NULL,
  `event_id` INT DEFAULT NULL,
  `report_type` ENUM('wrong_info', 'broken_link', 'other') NOT NULL DEFAULT 'wrong_info',
  `reporter_name` VARCHAR(100) DEFAULT NULL,
  `reporter_email` VARCHAR(100) DEFAULT NULL,
  `details` TEXT NOT NULL,
  `status` ENUM('pending', 'resolved') NOT NULL DEFAULT 'pending',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved_at` DATETIME DEFAULT NULL,
  INDEX `idx_feedback_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Bảng Cấu hình Website (Site Settings)
CREATE TABLE IF NOT EXISTS `site_settings` (
  `setting_key` VARCHAR(50) PRIMARY KEY,
  `setting_value` LONGTEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Bảng Chống Brute Force (Login Attempts)
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ip_address` VARCHAR(50) NOT NULL,
  `attempt_time` DATETIME NOT NULL,
  INDEX `idx_login_ip_time` (`ip_address`, `attempt_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- DỮ LIỆU CẤU HÌNH BAN ĐẦU & CÀI ĐẶT HỆ THỐNG
-- ==============================================================================

INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES
('site_name', 'ClubHub'),
('site_tagline', 'Cổng Khám Phá & Kết Nối Câu Lạc Bộ Sinh Viên'),
('logo_text', 'ClubHub'),
('contact_email', 'clb.contact@sinhvien.edu.vn'),
('contact_phone', '028 3829 5678'),
('contact_address', 'Phòng Công tác Sinh viên, Tòa nhà A1, Trường Đại học'),
('intro_text', 'ClubHub là nền tảng chính thức giúp sinh viên dễ dàng khám phá, tìm hiểu thông tin và kết nối với các Câu lạc bộ, Đội, Nhóm trong trường. Tại đây, bạn có thể theo dõi các đợt tuyển thành viên, đăng ký sự kiện và thực hiện bài trắc nghiệm định hướng CLB phù hợp nhất với bản thân.'),
('privacy_policy', 'ClubHub cam kết bảo vệ thông tin cá nhân của người dùng. Thông tin trong biểu mẫu đăng ký quảng bá và phản ánh sai sót chỉ được sử dụng nội bộ để xử lý yêu cầu, tuyệt đối không chia sẻ cho bên thứ ba vì mục đích thương mại.')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- Vị trí Quảng cáo (Ad Placements)
INSERT INTO `ad_placements` (`code`, `name`, `description`, `max_slots`, `price_per_day`, `is_active`) VALUES
('top_banner', 'Banner đầu trang chủ', 'Vị trí nổi bật nhất xuất hiện ngay trên đầu trang chủ với hình ảnh poster lớn', 5, 200000, 1),
('home_featured', 'Khu quảng bá nổi bật trang chủ', 'Khối hiển thị poster giữa trang chủ, phân tách rõ ràng với CLB nổi bật thông thường', 4, 120000, 1),
('category_page', 'Trang danh mục tương ứng', 'Hiển thị ở đầu trang của từng lĩnh vực chuyên môn cụ thể', 3, 80000, 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Danh mục / Lĩnh vực CLB (Categories)
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `icon`, `display_order`, `is_active`) VALUES
(1, 'Công nghệ & Kỹ thuật', 'cong-nghe-ky-thuat', 'Lập trình, AI, Robotics, An toàn thông tin, Thiết kế đồ họa và phát triển sản phẩm công nghệ.', 'cpu', 1, 1),
(2, 'Học thuật & Kỹ năng', 'hoc-thuat-ky-nang', 'Nghiên cứu khoa học, tranh biện, ngoại ngữ, kỹ năng mềm và thuyết trình trước công chúng.', 'book-open', 2, 1),
(3, 'Nghệ thuật & Âm nhạc', 'nghe-thuat-am-nhac', 'Acoustic, vũ đạo, kịch nghệ, nhiếp ảnh, hội họa và tổ chức các đêm nhạc thanh xuân.', 'music', 3, 1),
(4, 'Thể dục & Thể thao', 'the-duc-the-thao', 'Bóng rổ, bóng đá, cầu lông, cờ vua, bóng bàn và các bộ môn võ thuật rèn luyện thể chất.', 'activity', 4, 1),
(5, 'Tình nguyện & Xã hội', 'tinh-nguyen-xa-hoi', 'Chiến dịch Mùa hè xanh, Tiếp sức mùa thi, hiến máu nhân đạo và hỗ trợ cộng đồng hoàn cảnh khó khăn.', 'heart', 5, 1),
(6, 'Truyền thông & Sự kiện', 'truyen-thong-su-kien', 'Báo chí học đường, sản xuất nội dung đa phương tiện, MC và quản trị sự kiện quy mô lớn.', 'camera', 6, 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Từ khóa / Thuộc tính định hướng Quiz (Quiz Attribute Keys)
INSERT INTO `quiz_attribute_keys` (`attribute_key`, `name`, `group_name`) VALUES
('domain_tech', 'Lĩnh vực Công nghệ & Kỹ thuật', 'domain'),
('domain_academic', 'Lĩnh vực Học thuật & Tranh biện', 'domain'),
('domain_arts', 'Lĩnh vực Nghệ thuật & Biểu diễn', 'domain'),
('domain_sports', 'Lĩnh vực Thể thao & Rèn luyện', 'domain'),
('domain_volunteer', 'Lĩnh vực Tình nguyện & Cộng đồng', 'domain'),
('domain_media', 'Lĩnh vực Truyền thông & Sự kiện', 'domain'),
('goal_skills', 'Rèn luyện kỹ năng thực chiến', 'goal'),
('goal_networking', 'Mở rộng mạng lưới quan hệ', 'goal'),
('goal_passion', 'Thỏa mãn đam mê cá nhân', 'goal'),
('goal_cv', 'Tích lũy hồ sơ & chứng nhận', 'goal'),
('style_performance', 'Thích đứng trên sân khấu / biểu diễn', 'style'),
('style_backstage', 'Thích hậu trường / kỹ thuật / chuẩn bị', 'style'),
('style_leadership', 'Thích điều phối / tổ chức / quản lý', 'style'),
('style_small_group', 'Thích làm việc nhóm nhỏ / tập trung', 'style'),
('exp_beginner', 'Phù hợp người mới bắt đầu', 'experience'),
('exp_advanced', 'Đòi hỏi kỹ năng / sàng lọc đầu vào', 'experience'),
('time_evening', 'Sinh hoạt buổi tối các ngày trong tuần', 'time'),
('time_weekend', 'Sinh hoạt tập trung vào cuối tuần', 'time'),
('time_flexible', 'Lịch sinh hoạt linh hoạt theo dự án', 'time'),
('commit_high', 'Đòi hỏi mức độ cam kết và chuyên cần cao', 'commitment'),
('commit_medium', 'Mức độ cam kết vừa phải, cân bằng việc học', 'commitment')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Bộ 7 câu hỏi Quiz chuẩn
INSERT INTO `quiz_questions` (`id`, `question_text`, `description`, `question_type`, `display_order`, `is_active`) VALUES
(1, 'Bạn quan tâm nhiều nhất đến lĩnh vực nào trong thời gian học đại học?', 'Chọn các chủ đề bạn cảm thấy hứng thú nhất khi tham gia (Có thể chọn nhiều)', 'multiple', 1, 1),
(2, 'Mục tiêu hàng đầu của bạn khi tìm kiếm một Câu lạc bộ là gì?', 'Chọn động lực chính thúc đẩy bạn nộp đơn', 'single', 2, 1),
(3, 'Bạn yêu thích loại hình hoạt động nào dưới đây nhất?', 'Chọn hoạt động mang lại cho bạn nhiều năng lượng nhất', 'multiple', 3, 1),
(4, 'Trong một dự án hoặc sự kiện, bạn cảm thấy thoải mái nhất ở vai trò nào?', 'Cách bạn mong muốn đóng góp cho CLB', 'single', 4, 1),
(5, 'Mức độ kinh nghiệm hoặc kỹ năng hiện tại của bạn trong lĩnh vực mong muốn tham gia?', 'Giúp đề xuất CLB có tiêu chuẩn đầu vào phù hợp với bạn', 'single', 5, 1),
(6, 'Khoảng thời gian bạn có thể dành cho các buổi sinh hoạt CLB?', 'Đối chiếu với lịch sinh hoạt thực tế của CLB', 'single', 6, 1),
(7, 'Mức độ cam kết thời gian bạn sẵn sàng dành cho CLB mỗi tuần?', 'Giúp tránh tình trạng quá tải lịch học hoặc bỏ dở giữa chừng', 'single', 7, 1)
ON DUPLICATE KEY UPDATE `question_text` = VALUES(`question_text`);

-- Đáp án Quiz & Trọng số
-- Câu 1: Lĩnh vực quan tâm (multiple)
INSERT INTO `quiz_options` (`id`, `question_id`, `option_text`, `display_order`) VALUES
(1, 1, 'Lập trình, công nghệ phần mềm, dữ liệu, thiết kế đồ họa', 1),
(2, 1, 'Nghiên cứu học thuật, tranh biện, hùng biện, rèn luyện ngoại ngữ', 2),
(3, 1, 'Âm nhạc, ca hát, nhạc cụ, vũ đạo, nhảy hiện đại', 3),
(4, 1, 'Thể thao, võ thuật, rèn luyện thể lực và tinh thần đồng đội', 4),
(5, 1, 'Hoạt động vì cộng đồng, gây quỹ thiện nguyện, hỗ trợ trẻ em khó khăn', 5),
(6, 1, 'Sản xuất video, chụp ảnh, viết bài truyền thông, điều phối sự kiện', 6);

INSERT INTO `quiz_option_weights` (`option_id`, `attribute_key`, `weight`) VALUES
(1, 'domain_tech', 4.0),
(2, 'domain_academic', 4.0),
(3, 'domain_arts', 4.0),
(4, 'domain_sports', 4.0),
(5, 'domain_volunteer', 4.0),
(6, 'domain_media', 4.0);

-- Câu 2: Mục tiêu tham gia (single)
INSERT INTO `quiz_options` (`id`, `question_id`, `option_text`, `display_order`) VALUES
(7, 2, 'Rèn luyện kỹ năng chuyên môn thực tế và cọ xát kinh nghiệm', 1),
(8, 2, 'Kết thêm nhiều bạn bè thân thiết, mở rộng vòng tròn kết nối', 2),
(9, 2, 'Thỏa mãn sở thích, đam mê và xả stress sau giờ học', 3),
(10, 2, 'Tích lũy kinh nghiệm làm việc nhóm, điểm rèn luyện và làm đẹp CV', 4);

INSERT INTO `quiz_option_weights` (`option_id`, `attribute_key`, `weight`) VALUES
(7, 'goal_skills', 3.5),
(8, 'goal_networking', 3.5),
(9, 'goal_passion', 3.5),
(10, 'goal_cv', 3.5);

-- Câu 3: Hoạt động yêu thích (multiple)
INSERT INTO `quiz_options` (`id`, `question_id`, `option_text`, `display_order`) VALUES
(11, 3, 'Tham gia các cuộc thi chuyên môn, Hackathon, giải đấu tranh biện', 1),
(12, 3, 'Tập luyện biểu diễn, jamming âm nhạc hoặc thi đấu thể thao giao lưu', 2),
(13, 3, 'Đi tiền trạm, tổ chức các chuyến đi tình nguyện tại địa phương xa', 3),
(14, 3, 'Lên ý tưởng kịch bản truyền thông, quay dựng clip TikTok, chụp ảnh', 4);

INSERT INTO `quiz_option_weights` (`option_id`, `attribute_key`, `weight`) VALUES
(11, 'domain_tech', 2.0), (11, 'domain_academic', 2.0),
(12, 'domain_arts', 2.5), (12, 'domain_sports', 2.5),
(13, 'domain_volunteer', 3.0),
(14, 'domain_media', 3.0);

-- Câu 4: Vai trò mong muốn (single)
INSERT INTO `quiz_options` (`id`, `question_id`, `option_text`, `display_order`) VALUES
(15, 4, 'Trực tiếp đứng trên sân khấu, diễn thuyết hoặc đại diện phát ngôn', 1),
(16, 4, 'Hậu trường kỹ thuật, chuẩn bị tài liệu, âm thanh ánh sáng, hậu cần', 2),
(17, 4, 'Lên kế hoạch tổng thể, điều phối thành viên và giám sát tiến độ', 3),
(18, 4, 'Làm việc theo nhóm nhỏ, tập trung chuyên sâu vào nhiệm vụ được giao', 4);

INSERT INTO `quiz_option_weights` (`option_id`, `attribute_key`, `weight`) VALUES
(15, 'style_performance', 3.0),
(16, 'style_backstage', 3.0),
(17, 'style_leadership', 3.0),
(18, 'style_small_group', 3.0);

-- Câu 5: Kinh nghiệm hiện tại (single)
INSERT INTO `quiz_options` (`id`, `question_id`, `option_text`, `display_order`) VALUES
(19, 5, 'Tôi là người mới toanh, muốn học hỏi từ những điều căn bản nhất', 1),
(20, 5, 'Tôi đã có nền tảng cơ bản và muốn có môi trường rèn luyện thêm', 2),
(21, 5, 'Tôi đã có nhiều kinh nghiệm và từng đoạt giải hoặc hoạt động tích cực', 3);

INSERT INTO `quiz_option_weights` (`option_id`, `attribute_key`, `weight`) VALUES
(19, 'exp_beginner', 3.0),
(20, 'exp_beginner', 1.5), (20, 'exp_advanced', 1.5),
(21, 'exp_advanced', 3.0);

-- Câu 6: Khung giờ rảnh (single)
INSERT INTO `quiz_options` (`id`, `question_id`, `option_text`, `display_order`) VALUES
(22, 6, 'Buổi tối các ngày trong tuần (sau 17h30)', 1),
(23, 6, 'Thứ Bảy hoặc Chủ Nhật cả ngày', 2),
(24, 6, 'Linh hoạt, có thể sắp xếp tùy theo thời khóa biểu học tập', 3);

INSERT INTO `quiz_option_weights` (`option_id`, `attribute_key`, `weight`) VALUES
(22, 'time_evening', 3.0),
(23, 'time_weekend', 3.0),
(24, 'time_flexible', 3.0);

-- Câu 7: Mức độ cam kết (single)
INSERT INTO `quiz_options` (`id`, `question_id`, `option_text`, `display_order`) VALUES
(25, 7, 'Rất cao (từ 8 - 15 giờ/tuần, sẵn sàng thức đêm làm dự án lớn)', 1),
(26, 7, 'Vừa phải (từ 3 - 6 giờ/tuần, ưu tiên cân bằng tốt việc học trên lớp)', 2),
(27, 7, 'Linh hoạt (tham gia theo từng sự kiện ngắn hạn khi có thời gian rảnh)', 3);

INSERT INTO `quiz_option_weights` (`option_id`, `attribute_key`, `weight`) VALUES
(25, 'commit_high', 3.0),
(26, 'commit_medium', 3.0),
(27, 'commit_medium', 1.5), (27, 'time_flexible', 1.5);

-- ==============================================================================
-- DỮ LIỆU MẪU 10 CÂU LẠC BỘ MINH HỌA ĐA DẠNG LĨNH VỰC
-- (Lưu ý: Dữ liệu nhằm mục đích thử nghiệm tính năng, không phải kênh CLB thật)
-- ==============================================================================

INSERT INTO `clubs` (`id`, `category_id`, `name`, `short_name`, `slug`, `logo`, `cover_image`, `summary`, `description`, `objectives`, `target_audience`, `activities_achievements`, `meeting_schedule`, `meeting_location`, `meeting_frequency`, `schedule_confirmed`, `requirements`, `fee_info`, `recruitment_status`, `recruitment_deadline`, `recruitment_process`, `application_link`, `is_featured`, `is_active`) VALUES
(1, 1, 'Câu lạc bộ Lập trình & Trí tuệ Nhân tạo', 'DevAI Club', 'clb-lap-trinh-tri-tue-nhan-tao', 'uploads/clubs/devai-logo.png', 'uploads/clubs/devai-cover.png', 'Nơi hội tụ các bạn sinh viên đam mê viết code, nghiên cứu AI, thị giác máy tính và xây dựng sản phẩm công nghệ thực chiến.', 'DevAI Club được thành lập với sứ mệnh tạo ra môi trường học tập tương trợ, chia sẻ kiến thức công nghệ mới và ươm mầm các dự án khởi nghiệp công nghệ sinh viên. CLB duy trì các nhóm nghiên cứu (Web/App Development, Data Science, AI/ML) và tổ chức các workshop kỹ thuật định kỳ hàng tháng cùng các lập trình viên kỳ cựu trong ngành.', 'Nâng cao năng lực lập trình, làm việc nhóm theo mô hình Agile/Scrum và hỗ trợ thành viên tham gia các cuộc thi học thuật quốc gia.', 'Sinh viên tất cả các khoa yêu thích công nghệ, đặc biệt là sinh viên ngành CNTT, Khoa học Máy tính, Hệ thống Thông tin.', 'Giải Nhất Hackathon Sinh viên 2025; Phát triển thành công ứng dụng hỗ trợ học tập cho hơn 5.000 sinh viên trường; Tổ chức chuỗi Tech Talk thường niên với 600 người tham dự.', '18:30 - 20:30 Thứ Tư hàng tuần', 'Phòng Lab Công nghệ B4.02', '1 buổi/tuần', 1, 'Có kiến thức căn bản về ít nhất một ngôn ngữ lập trình (Python, C++, JS); Tinh thần tự học và cầu tiến cao.', 'Quỹ hoạt động: 50.000 đ/kỳ (dùng mua server và teabreak workshop)', 'open', DATE_ADD(NOW(), INTERVAL 12 DAY), 'Vòng 1: Đơn đăng ký trực tuyến -> Vòng 2: Phỏng vấn kỹ thuật & văn hóa nhóm', 'https://docs.google.com/forms/d/e/demo-devai/viewform', 1, 1),

(2, 2, 'Câu lạc bộ Tranh biện & Kỹ năng Tư duy', 'DebateHub', 'clb-tranh-bien-tu-duy', 'uploads/clubs/debate-logo.png', 'uploads/clubs/debate-cover.png', 'Rèn luyện tư duy phản biện, kỹ năng tranh luận theo luật Quốc tế và bản lĩnh hùng biện tự tin trước công chúng.', 'DebateHub là sân chơi học thuật giúp sinh viên khai mở góc nhìn đa chiều về các vấn đề kinh tế, xã hội, môi trường và đạo đức học. Tại đây, bạn sẽ được tiếp cận phương pháp tranh biện theo luật Nghị viện Anh (British Parliamentary) và Asian Parliamentary, học cách xây dựng luận điểm sắc bén và phản bác logic.', 'Phát triển tư duy phân tích sắc bén, kiểm soát cảm xúc, làm chủ ngôn ngữ cơ thể và kỹ năng thuyết trình tự tin.', 'Sinh viên mong muốn cải thiện khả năng giao tiếp, thích quan sát các vấn đề xã hội và đam mê lập luận logic.', 'Top 4 Giải Tranh biện Trẻ Toàn quốc 2024; Quán quân Giải Tranh biện Sinh viên Mở rộng; Tổ chức giải đấu nội bộ thường niên Debate Open.', '08:30 - 11:30 Thứ Bảy hàng tuần', 'Hội trường Hội thảo A2.10', '1 buổi/tuần', 1, 'Không yêu cầu kinh nghiệm trước đó; Sẵn sàng mở rộng tư duy và lắng nghe các góc nhìn đa chiều.', 'Không thu phí quỹ cố định', 'open', DATE_ADD(NOW(), INTERVAL 8 DAY), 'Vòng 1: Đơn tư duy logic -> Vòng 2: Tranh biện thử tại chỗ theo cặp', 'https://docs.google.com/forms/d/e/demo-debate/viewform', 1, 1),

(3, 3, 'Câu lạc bộ Âm nhạc & Acoustic Sinh viên', 'Melody Acoustic', 'clb-am-nhac-acoustic', 'uploads/clubs/melody-logo.png', 'uploads/clubs/melody-cover.png', 'Không gian kết nối những tâm hồn yêu âm nhạc, mộc mạc tiếng đàn guitar, giọng hát truyền cảm và những đêm nhạc thanh xuân.', 'Melody Acoustic là nơi những giai điệu mộc mạc gắn kết các bạn sinh viên sau những giờ học căng thẳng. CLB gồm các ban: Guitar, Nhạc cụ dân tộc, Bộ gõ (Cajon), Vocal và Hậu cần. Melody thường xuyên biểu diễn tại sảnh trường, quán cà phê và tổ chức các đêm minishow gây quỹ từ thiện.', 'Lan tỏa năng lượng tích cực thông qua âm nhạc, tạo sân khấu biểu diễn cho sinh viên và rèn luyện kỹ năng biểu diễn nhóm.', 'Tất cả sinh viên có đam mê ca hát hoặc chơi được các loại nhạc cụ (Guitar, Ukulele, Cajon, Piano, Violin...).', 'Minishow thường niên Giai Điệu Mùa Thu thu hút hơn 400 khán giả; Đại diện trường tham gia Liên hoan Tiếng hát Sinh viên Thành phố; Biểu diễn tại nhiều sự kiện quy mô lớn.', '18:00 - 21:00 Thứ Bảy hàng tuần', 'Sảnh tầng lửng Nhà thi đấu thể thao', '1 buổi/tuần', 1, 'Có khả năng cảm thụ âm nhạc tốt; Sẵn sàng dành thời gian tập dượt định kỳ trước các show diễn.', '50.000 đ/học kỳ', 'upcoming', DATE_ADD(NOW(), INTERVAL 25 DAY), 'Vòng 1: Gửi clip thể hiện tài năng 60s -> Vòng 2: Audition trực tiếp', '', 1, 1),

(4, 4, 'Câu lạc bộ Bóng rổ Sinh viên Đại học', 'Warriors Basketball', 'clb-bong-ro-sinh-vien', 'uploads/clubs/warriors-logo.png', 'uploads/clubs/warriors-cover.png', 'Nơi rèn luyện thể lực, chiến thuật bóng rổ đồng đội và tranh tài tại các giải thể thao sinh viên khu vực.', 'Warriors Basketball quy tụ các bạn sinh viên có niềm đam mê mãnh liệt với quả bóng cam. CLB sinh hoạt nghiêm túc với các bài tập rèn thể lực, kỹ thuật ném rổ, phòng ngự cá nhân và phối hợp nhóm. Hàng năm, CLB tổ chức Giải bóng rổ 3x3 nội bộ và tham gia giải Vô địch Thể thao Sinh viên VUG.', 'Nâng cao thể lực, phát huy tinh thần thể thao cao thượng và đoàn kết giữa các thế hệ sinh viên.', 'Sinh viên yêu thích bóng rổ, có sức khỏe tốt và cam kết tham gia tập luyện chuyên cần.', 'Huy chương Bạc Giải Bóng rổ VUG Khu vực 2025; Vô địch Cúp Tứ hùng Sinh viên mở rộng.', '17:30 - 19:30 Thứ Ba và Thứ Năm', 'Sân bóng rổ ngoài trời Khu KTX', '2 buổi/tuần', 1, 'Có sức khỏe tốt; Ưu tiên các bạn đã nắm luật bóng rổ cơ bản và có kinh nghiệm thi đấu.', 'Quỹ sân bãi và nước uống: 100.000 đ/học kỳ', 'closed', DATE_SUB(NOW(), INTERVAL 5 DAY), 'Kiểm tra thể lực tại sân và thi đấu đối kháng', '', 0, 1),

(5, 5, 'Đội Tình nguyện Xung kích Vì Cộng đồng', 'Blue Wave Volunteer', 'doi-tinh-nguyen-xung-kich', 'uploads/clubs/bluewave-logo.png', 'uploads/clubs/bluewave-cover.png', 'Chung tay xây dựng những hành trình thiện nguyện ý nghĩa, Tiếp sức mùa thi, Mùa hè xanh và hỗ trợ trẻ em vùng sâu.', 'Đội Tình nguyện Xung kích Blue Wave là một trong những đội nhóm truyền thống lâu đời nhất của trường. Với màu áo xanh thanh niên, đội đã mang hàng ngàn phần quà, các lớp học tin học và các công trình sân chơi đến cho trẻ em tại các vùng cao khó khăn.', 'Gieo mầm lối sống tử tế, tinh thần cống hiến vì cộng đồng và nâng cao kỹ năng sinh tồn, kỹ năng tổ chức dự án.', 'Tất cả sinh viên có trái tim nhiệt huyết, không ngại gian khó và mong muốn cống hiến cho xã hội.', 'Bằng khen của Thành Đoàn vì thành tích xuất sắc trong chiến dịch Mùa hè xanh 2025; Tổ chức 12 chuyến xe thiện nguyện hàng năm.', '08:00 - 10:30 Chủ Nhật hàng tuần', 'Văn phòng Đoàn Thanh niên - Tầng 1', '1 buổi/tuần', 1, 'Không yêu cầu kỹ năng đặc thù; Tinh thần trách nhiệm cao, hòa đồng và tuân thủ kỷ luật đội.', 'Hoàn toàn không thu phí', 'open', DATE_ADD(NOW(), INTERVAL 5 DAY), 'Vòng 1: Đơn trực tuyến -> Vòng 2: Phỏng vấn truyền cảm hứng', 'https://docs.google.com/forms/d/e/demo-bluewave/viewform', 1, 1),

(6, 6, 'Câu lạc bộ Truyền thông & Sự kiện Sinh viên', 'Youth Media Hub', 'clb-truyen-thong-su-kien', 'uploads/clubs/youthmedia-logo.png', 'uploads/clubs/youthmedia-cover.png', 'Cơ quan truyền thông trẻ trung của sinh viên: sản xuất video Viral, quay chụp phóng sự và tổ chức các sự kiện bùng nổ.', 'Youth Media Hub là cầu nối thông tin sống động của trường học trên TikTok, Facebook và YouTube. CLB hoạt động chuyên nghiệp như một Agency thu nhỏ gồm các ban: Nội dung (Content), Thiết kế (Design), Sản xuất Video (Production), Kỹ thuật & Sự kiện (Event).', 'Đào tạo kỹ năng tư duy truyền thông hiện đại, sử dụng phần mềm đồ họa, máy ảnh cơ và quản trị khủng hoảng truyền thông.', 'Sinh viên thích viết lách, chụp ảnh, quay video, thiết kế hoặc có mong muốn trở thành người dẫn chương trình MC.', 'Kênh TikTok đạt hơn 80.000 người theo dõi; Phụ trách toàn bộ khâu hình ảnh và livestream cho Lễ Khai giảng và Gala Chào Tân sinh viên.', '18:00 - 20:00 Thứ Sáu hàng tuần', 'Phòng Truyền thông & Studio C1.05', '1 buổi/tuần', 1, 'Có đam mê với truyền thông số; Tinh thần ham học hỏi và chịu được áp lực tiến độ (deadline).', '30.000 đ/học kỳ', 'open', DATE_ADD(NOW(), INTERVAL 15 DAY), 'Vòng 1: Gửi Portfolio/Bài làm thử -> Vòng 2: Phỏng vấn sáng tạo', 'https://docs.google.com/forms/d/e/demo-media/viewform', 0, 1),

(7, 1, 'Câu lạc bộ Robot & Trí tuệ Nhân tạo Ứng dụng', 'RoboTech Lab', 'clb-robot-tri-tue-nhan-tao', 'uploads/clubs/robotech-logo.png', 'uploads/clubs/robotech-cover.png', 'Nghiên cứu chế tạo xe tự hành, cánh tay robot công nghiệp, IoT và tranh tài tại giải đấu Robocon Sinh viên.', 'RoboTech Lab là không gian sáng chế phần cứng và lập trình nhúng cho sinh viên khối ngành kỹ thuật. Tại xưởng chế tạo, các thành viên được tiếp cận máy in 3D, máy cắt laser và các kit vi điều khiển hiện đại để biến các ý tưởng thiết kế thành sản phẩm chạy được thực tế.', 'Thực hành chế tạo máy, lập trình vi điều khiển (STM32, ESP32, Arduino) và tư duy cơ điện tử tích hợp.', 'Sinh viên ngành Điện tử, Cơ khí, Tự động hóa, CNTT hoặc những bạn say mê phần cứng cơ điện tử.', 'Giải Ba Cuộc thi Sáng tạo Robot Toàn quốc 2024; Chế tạo máy phân loại rác thông minh đạt giải Tech Award.', '14:00 - 17:00 Thứ Bảy hàng tuần', 'Xưởng Chế tạo Robot MakerSpace Kỹ thuật', '1 buổi/tuần', 1, 'Kiên trì, cẩn thận, không ngại va chạm dầu mỡ và linh kiện điện tử.', '50.000 đ/kỳ (dùng mua ốc vít, linh kiện vặt)', 'closed', DATE_SUB(NOW(), INTERVAL 10 DAY), 'Phỏng vấn kỹ thuật và kiểm tra độ tỉ mỉ qua bài test linh kiện', '', 0, 1),

(8, 2, 'Câu lạc bộ Tiếng Anh & Giao lưu Quốc tế', 'Global Citizens Club', 'clb-tieng-anh-giao-luu-quoc-te', 'uploads/clubs/global-logo.png', 'uploads/clubs/global-cover.png', 'Không gian 100% tiếng Anh tự nhiên, giao lưu văn hóa đa quốc gia, luyện Speaking và chuẩn bị hành trang hội nhập.', 'Global Citizens Club xóa bỏ rào cản sợ nói tiếng Anh thông qua các buổi thảo luận chủ đề (Topic Discussion), Board Games tiếng Anh và các buổi đón tiếp đoàn sinh viên trao đổi quốc tế. CLB giúp bạn cải thiện phản xạ giao tiếp tự nhiên và hiểu biết phong tục các nước.', 'Tăng cường sự tự tin khi nói tiếng Anh, mở rộng vốn từ vựng học thuật và tư duy công dân toàn cầu.', 'Mọi sinh viên muốn vượt qua nỗi sợ nói tiếng Anh hoặc muốn duy trì môi trường thực hành ngôn ngữ mỗi tuần.', 'Tổ chức thành công chuỗi English Festival với 500 sinh viên tham gia; Tiếp đón 4 đoàn giao lưu sinh viên Nhật Bản và Singapore.', 'Lịch sinh hoạt đang điều chỉnh theo học kỳ mới', 'Phòng Ngoại ngữ D2.01', 'Cần xác nhận lịch sinh hoạt cụ thể', 0, 'Không yêu cầu chứng chỉ IELTS; Chỉ cần cam kết sử dụng tiếng Anh trong giờ sinh hoạt.', 'Miễn phí', 'upcoming', DATE_ADD(NOW(), INTERVAL 18 DAY), 'Vòng 1: Đơn giới thiệu bản thân -> Vòng 2: Phỏng vấn Speaking 10 phút', '', 0, 1),

(9, 3, 'Câu lạc bộ Nhiếp ảnh & Nghệ thuật Thị giác', 'Optics Photo Club', 'clb-nhiep-anh-thi-giac', 'uploads/clubs/photo-logo.png', 'uploads/clubs/photo-cover.png', 'Lưu giữ những khoảnh khắc thanh xuân đẹp nhất qua ống kính máy ảnh, học làm chủ ánh sáng, màu sắc và bố cục.', 'Optics Photo Club tập hợp những bạn trẻ yêu thích nhiếp ảnh đường phố, ảnh chân dung và phóng sự học đường. CLB thường xuyên tổ chức các buổi Photo Walk vào sáng Chủ nhật, các workshop chia sẻ kỹ thuật hậu kỳ bằng Lightroom, Photoshop và triển lãm ảnh thường niên.', 'Làm chủ thiết bị chụp ảnh (máy ảnh DSLR/Mirrorless hoặc cả điện thoại), tư duy ánh sáng và xây dựng phong cách ảnh riêng.', 'Các bạn sinh viên đam mê chụp ảnh, muốn tìm bạn đồng hành đi chụp hoặc nâng cao kỹ năng hậu kỳ.', 'Tổ chức Triển lãm ảnh Sắc Màu Giảng Đường; Cung cấp ảnh tư liệu cho website và ấn phẩm kỷ yếu của nhà trường.', '07:30 - 11:30 Chủ Nhật (2 tuần/lần)', 'Khu vực Thảo Cầm Viên hoặc Phố cổ', '2 tuần/lần', 1, 'Có máy ảnh cá nhân hoặc điện thoại có camera chất lượng khá; Tinh thần chịu khó dậy sớm đi chụp.', 'Quỹ triển lãm: 40.000 đ/kỳ', 'closed', NULL, 'Đánh giá qua bộ ảnh (Portfolio) 5 tấm do ứng viên tự chụp', '', 0, 1),

(10, 4, 'Câu lạc bộ Võ thuật & Tự vệ Sinh viên', 'Karate & Vovinam Club', 'clb-vo-thuat-tu-ve-sinh-vien', 'uploads/clubs/martial-logo.png', 'uploads/clubs/martial-cover.png', 'Rèn luyện thể lực bền bỉ, ý chí kiên định, tinh thần thượng võ và các thế võ tự vệ ứng dụng thiết thực.', 'CLB Võ thuật là nơi rèn luyện thân thể và tinh thần võ đạo cho sinh viên. Dưới sự hướng dẫn của các huấn luyện viên có đẳng cấp quốc gia, võ sinh được trang bị nền tảng tấn pháp, đòn đánh tay, chân và các bài tự vệ trước các tình huống nguy hiểm nơi công cộng.', 'Nâng cao sức bền thể chất, rèn tính kỷ luật, tự tin bảo vệ bản thân và bạn bè khi gặp sự cố.', 'Tất cả sinh viên nam và nữ mong muốn rèn luyện thể chất, không phân biệt đã biết võ hay chưa.', 'Đoạt 3 Huy chương Vàng Giải Vô địch Võ thuật Sinh viên Toàn quốc; Đào tạo hơn 200 võ sinh đạt các cấp đai chính thức.', '18:00 - 20:00 Thứ Ba, Năm, Bảy', 'Sân thể thao đa năng khu A', '3 buổi/tuần', 1, 'Có sức khỏe tốt, nghiêm túc tuân thủ đạo đức võ sĩ và trang phục tập luyện quy định.', 'Học phí ưu đãi sinh viên: 60.000 đ/tháng (trả chi phí bảo hộ và thảm tập)', 'open', DATE_ADD(NOW(), INTERVAL 20 DAY), 'Đăng ký và tham gia kiểm tra thể lực khởi động trực tiếp tại võ đường', 'https://docs.google.com/forms/d/e/demo-martial/viewform', 0, 1);

-- Liên kết Mạng xã hội của các CLB
INSERT INTO `club_socials` (`club_id`, `platform`, `url`) VALUES
(1, 'facebook', 'https://facebook.com/devai.student.club.demo'),
(1, 'website', 'https://github.com/devai-student-club-demo'),
(1, 'email', 'mailto:devai.club@edu.vn'),

(2, 'facebook', 'https://facebook.com/debatehub.student.demo'),
(2, 'youtube', 'https://youtube.com/@debatehub_demo'),
(2, 'email', 'mailto:debatehub@edu.vn'),

(3, 'facebook', 'https://facebook.com/melodyacoustic.student.demo'),
(3, 'tiktok', 'https://tiktok.com/@melodyacoustic_demo'),
(3, 'instagram', 'https://instagram.com/melodyacoustic_demo'),
(3, 'youtube', 'https://youtube.com/@melodyacoustic_demo'),

(4, 'facebook', 'https://facebook.com/warriors.basketball.demo'),
(4, 'instagram', 'https://instagram.com/warriors_bk_demo'),

(5, 'facebook', 'https://facebook.com/bluewave.volunteer.demo'),
(5, 'tiktok', 'https://tiktok.com/@bluewave_volunteer_demo'),
(5, 'email', 'mailto:tinhnguyen.bluewave@edu.vn'),

(6, 'facebook', 'https://facebook.com/youthmedia.student.demo'),
(6, 'tiktok', 'https://tiktok.com/@youthmedia_student_demo'),
(6, 'instagram', 'https://instagram.com/youthmedia_demo'),
(6, 'youtube', 'https://youtube.com/@youthmedia_demo'),

(7, 'facebook', 'https://facebook.com/robotech.lab.demo'),
(7, 'website', 'https://robotech.example.com'),

(8, 'facebook', 'https://facebook.com/globalcitizens.club.demo'),
(8, 'instagram', 'https://instagram.com/globalcitizens_demo'),

(9, 'facebook', 'https://facebook.com/opticsphoto.club.demo'),
(9, 'instagram', 'https://instagram.com/opticsphoto_demo'),

(10, 'facebook', 'https://facebook.com/martialarts.student.demo'),
(10, 'messenger', 'https://m.me/martialarts.student.demo');

-- Thư viện hình ảnh hoạt động mẫu của các CLB (Club Gallery Images)
INSERT INTO `club_images` (`club_id`, `image_url`, `caption`, `display_order`) VALUES
(1, 'uploads/clubs/devai-cover.png', 'Buổi sinh hoạt chuyên đề AI & Web Development định kỳ', 1),
(1, 'uploads/events/hackathon-poster.png', 'Đội tuyển DevAI tranh tài tại vòng Chung kết Hackathon Sinh viên', 2),
(1, 'uploads/ads/ad-devai-banner.png', 'Gian hàng trải nghiệm các sản phẩm công nghệ sinh viên', 3),

(2, 'uploads/clubs/debate-cover.png', 'Các thành viên tập luyện tranh biện theo luật Nghị viện Anh (BP)', 1),
(2, 'uploads/events/debate-workshop-poster.png', 'Workshop chuyên đề: Tư duy phản biện và bóc tách ngụy biện', 2),

(3, 'uploads/clubs/melody-cover.png', 'Không gian jamming âm nhạc mộc mạc tại sảnh trường', 1),
(3, 'uploads/events/acoustic-night-poster.png', 'Đêm nhạc minishow Acoustic Thanh xuân ấm áp', 2),

(4, 'uploads/clubs/warriors-cover.png', 'Đội tuyển bóng rổ thi đấu tại giải giao hữu sinh viên', 1),
(4, 'uploads/events/basketball-cup-poster.png', 'Buổi tập huấn thể lực và kỹ thuật ném rổ tại sân đa năng', 2),

(5, 'uploads/clubs/bluewave-cover.png', 'Lễ ra quân chiến dịch tình nguyện Mùa hè xanh rực rỡ', 1),
(5, 'uploads/events/volunteer-winter-poster.png', 'Trao tặng áo ấm và tủ sách cho học sinh vùng cao khó khăn', 2);

-- Điểm thuộc tính phục vụ thuật toán Quiz (Thang điểm 1.0 đến 5.0)
INSERT INTO `club_quiz_attributes` (`club_id`, `attribute_key`, `score`) VALUES
-- DevAI (CLB 1)
(1, 'domain_tech', 5.0), (1, 'goal_skills', 5.0), (1, 'goal_cv', 4.5), (1, 'style_small_group', 4.5), (1, 'style_backstage', 4.0), (1, 'exp_beginner', 3.5), (1, 'exp_advanced', 4.5), (1, 'time_evening', 4.5), (1, 'commit_high', 4.5),

-- DebateHub (CLB 2)
(2, 'domain_academic', 5.0), (2, 'goal_skills', 4.8), (2, 'goal_networking', 4.0), (2, 'style_performance', 4.8), (2, 'style_leadership', 4.5), (2, 'exp_beginner', 4.5), (2, 'time_weekend', 4.8), (2, 'commit_medium', 4.0),

-- Melody Acoustic (CLB 3)
(3, 'domain_arts', 5.0), (3, 'goal_passion', 5.0), (3, 'goal_networking', 4.5), (3, 'style_performance', 4.8), (3, 'style_backstage', 3.5), (3, 'exp_beginner', 3.0), (3, 'exp_advanced', 4.5), (3, 'time_weekend', 4.8), (3, 'commit_medium', 4.2),

-- Warriors Basketball (CLB 4)
(4, 'domain_sports', 5.0), (4, 'goal_passion', 4.8), (4, 'goal_skills', 4.0), (4, 'style_performance', 4.0), (4, 'style_small_group', 4.0), (4, 'exp_beginner', 3.0), (4, 'exp_advanced', 4.5), (4, 'time_evening', 4.8), (4, 'commit_high', 4.5),

-- Blue Wave Volunteer (CLB 5)
(5, 'domain_volunteer', 5.0), (5, 'goal_networking', 4.8), (5, 'goal_passion', 4.5), (5, 'style_leadership', 4.2), (5, 'style_backstage', 4.5), (5, 'exp_beginner', 5.0), (5, 'time_weekend', 4.8), (5, 'commit_medium', 4.0),

-- Youth Media Hub (CLB 6)
(6, 'domain_media', 5.0), (6, 'goal_skills', 4.8), (6, 'goal_cv', 4.8), (6, 'style_backstage', 4.8), (6, 'style_leadership', 4.0), (6, 'exp_beginner', 3.8), (6, 'exp_advanced', 4.2), (6, 'time_evening', 4.5), (6, 'commit_high', 4.8),

-- RoboTech Lab (CLB 7)
(7, 'domain_tech', 4.8), (7, 'goal_skills', 5.0), (7, 'style_backstage', 5.0), (7, 'style_small_group', 4.8), (7, 'exp_advanced', 4.5), (7, 'time_weekend', 4.5), (7, 'commit_high', 4.8),

-- Global Citizens Club (CLB 8)
(8, 'domain_academic', 4.5), (8, 'goal_networking', 5.0), (8, 'goal_skills', 4.2), (8, 'style_performance', 4.0), (8, 'style_small_group', 4.5), (8, 'exp_beginner', 4.8), (8, 'time_flexible', 4.0), (8, 'commit_medium', 4.5),

-- Optics Photo Club (CLB 9)
(9, 'domain_arts', 4.5), (9, 'domain_media', 4.0), (9, 'goal_passion', 4.8), (9, 'style_backstage', 4.5), (9, 'style_small_group', 4.2), (9, 'exp_beginner', 3.5), (9, 'time_weekend', 5.0), (9, 'commit_medium', 4.0),

-- Karate & Vovinam Club (CLB 10)
(10, 'domain_sports', 5.0), (10, 'goal_passion', 4.5), (10, 'goal_skills', 4.5), (10, 'style_performance', 4.0), (10, 'exp_beginner', 4.8), (10, 'time_evening', 5.0), (10, 'commit_high', 4.8);

-- 5 Sự kiện minh họa (Bao gồm sắp diễn ra và đã kết thúc)
INSERT INTO `events` (`id`, `club_id`, `title`, `slug`, `poster_image`, `description`, `start_time`, `end_time`, `location`, `target_audience`, `fee`, `registration_deadline`, `registration_link`, `contact_info`, `is_active`) VALUES
(1, 1, 'Hackathon Sinh Viên 2026: Giải Pháp Số Vì Cộng Đồng', 'hackathon-sinh-vien-2026', 'uploads/events/hackathon-poster.png', 'Cuộc thi lập trình liên tục 24 giờ dành cho sinh viên yêu thích công nghệ. Các đội thi sẽ giải quyết bài toán thực tế về chuyển đổi số giáo dục và bảo vệ môi trường, với sự cố vấn của các chuyên gia công nghệ đầu ngành.', DATE_ADD(NOW(), INTERVAL 7 DAY), DATE_ADD(NOW(), INTERVAL 8 DAY), 'Hội trường Lớn A1 & Phòng Lab C', 'Sinh viên toàn trường yêu thích lập trình, thiết kế UI/UX', 0, DATE_ADD(NOW(), INTERVAL 5 DAY), 'https://docs.google.com/forms/d/e/demo-hackathon/viewform', 'devai.club@edu.vn - 0987 654 321', 1),

(2, 2, 'Workshop: Tư Duy Phản Biện & Kỹ Thuật Tranh Luận Sắc Bén', 'workshop-tu-duy-phan-bien', 'uploads/events/debate-workshop-poster.png', 'Buổi chia sẻ phương pháp bóc tách luận điểm ngụy biện trong giao tiếp hàng ngày, cách xây dựng cấu trúc luận cứ ARE (Assertion - Reasoning - Evidence) và kỹ năng kiểm soát bình tĩnh khi phát biểu trước đám đông.', DATE_ADD(NOW(), INTERVAL 14 DAY), DATE_ADD(DATE_ADD(NOW(), INTERVAL 14 DAY), INTERVAL 3 HOUR), 'Hội thảo B2.03', 'Tất cả sinh viên quan tâm đến kỹ năng mềm', 0, DATE_ADD(NOW(), INTERVAL 12 DAY), 'https://docs.google.com/forms/d/e/demo-debate-ws/viewform', 'debatehub@edu.vn', 1),

(3, 3, 'Đêm Nhạc Acoustic Thanh Xuân: Ký Ức Những Chuyến Đi', 'dem-nhac-acoustic-thanh-xuan', 'uploads/events/acoustic-night-poster.png', 'Không gian âm nhạc mộc mạc dưới ánh đèn vàng ấm cúng. Nơi bạn được lắng nghe những bản tình ca tuổi trẻ, thưởng thức trà bánh và cùng hòa giọng trong những giai điệu thanh xuân đáng nhớ.', DATE_ADD(NOW(), INTERVAL 3 DAY), DATE_ADD(DATE_ADD(NOW(), INTERVAL 3 DAY), INTERVAL 4 HOUR), 'Sân khấu Quán Cà phê Sinh viên Nhà B', 'Sinh viên và cựu sinh viên', 30000, DATE_ADD(NOW(), INTERVAL 2 DAY), 'https://docs.google.com/forms/d/e/demo-acoustic-night/viewform', '0912 345 678 (Bạn Hoàng)', 1),

(4, 5, 'Chiến Dịch Tình Nguyện: Áo Ấm Cho Em - Đông 2026', 'chien-dich-ao-am-cho-em', 'uploads/events/volunteer-winter-poster.png', 'Chiến dịch gây quỹ và trao tặng 500 phần quà, áo ấm, sách vở cho học sinh các điểm trường vùng sâu khó khăn. Đội tuyển tình nguyện viên hỗ trợ phân loại quà tặng và trực tiếp tham gia chuyến đi 3 ngày.', DATE_ADD(NOW(), INTERVAL 21 DAY), DATE_ADD(NOW(), INTERVAL 24 DAY), 'Điểm trường Xã Đắk R’măng', 'Sinh viên có sức khỏe tốt, tinh thần nhiệt huyết', 0, DATE_ADD(NOW(), INTERVAL 15 DAY), 'https://docs.google.com/forms/d/e/demo-volunteer/viewform', 'tinhnguyen.bluewave@edu.vn', 1),

(5, 4, 'Giải Bóng Rổ Giao Hữu Mùa Xuân 2026 (Đã diễn ra)', 'giai-bong-ro-giao-huu-mua-xuan-2026', 'uploads/events/basketball-cup-poster.png', 'Giải đấu giao lưu thể thao giữa 8 đội tuyển bóng rổ các khoa nhằm nâng cao tinh thần rèn luyện thể chất và đoàn kết nội bộ.', DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_SUB(NOW(), INTERVAL 8 DAY), 'Nhà thi đấu Thể thao đa năng', 'Các đội bóng rổ sinh viên đã đăng ký', 0, DATE_SUB(NOW(), INTERVAL 15 DAY), '', 'warriors.basketball@edu.vn', 1);

-- Dữ liệu mẫu Yêu cầu Quảng cáo Poster ở các trạng thái khác nhau
INSERT INTO `ad_requests` (`id`, `tracking_code`, `club_name`, `contact_name`, `email`, `phone`, `poster_image`, `destination_url`, `placement_code`, `start_date`, `end_date`, `notes`, `consent_agreed`, `quote_amount`, `payment_status`, `approval_status`, `admin_notes`, `is_enabled`) VALUES
-- Quảng cáo 1: ĐANG CHẠY HỢP LỆ (Đã duyệt, Đã thanh toán, trong khoảng ngày)
(1, 'AD-2026-X7K9P', 'CLB Lập trình & AI (DevAI)', 'Nguyễn Văn An', 'an.nguyen@devai.edu.vn', '0901234567', 'uploads/ads/ad-devai-banner.png', 'club-detail.php?slug=clb-lap-trinh-tri-tue-nhan-tao', 'top_banner', DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 10 DAY), 'Quảng bá đợt tuyển thành viên mới mùa thu', 1, 1500000, 'paid', 'approved', 'Đã kiểm tra nội dung phù hợp và xác nhận chuyển khoản ngân hàng.', 1),

-- Quảng cáo 2: ĐANG CHẠY HỢP LỆ (Vị trí khối nổi bật trang chủ)
(2, 'AD-2026-M3W8Q', 'CLB Âm nhạc Acoustic', 'Lê Thu Thảo', 'thao.le@melody.edu.vn', '0912345678', 'uploads/ads/ad-acoustic-night.png', 'events.php', 'home_featured', DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 5 DAY), 'Quảng bá Minishow Acoustic Thanh xuân', 1, 800000, 'paid', 'approved', 'Poster đẹp, duyệt nhanh.', 1),

-- Quảng cáo 3: CHỜ DUYỆT (Mới gửi, chưa xử lý)
(3, 'AD-2026-H8B2V', 'CLB Tranh biện DebateHub', 'Trần Minh Đức', 'duc.tran@debate.edu.vn', '0934567890', 'uploads/ads/ad-debate-promo.png', 'https://facebook.com/debatehub.student.demo', 'top_banner', DATE_ADD(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 12 DAY), 'Thuê hiển thị banner giải đấu tranh biện mở rộng', 1, 0, 'unpaid', 'pending', NULL, 1),

-- Quảng cáo 4: ĐÃ HẾT HẠN (Tự ngừng hiển thị theo truy vấn)
(4, 'AD-2026-T1L5Z', 'Đội Tình nguyện Blue Wave', 'Phạm Quỳnh Nga', 'nga.pham@volunteer.edu.vn', '0978123456', 'uploads/ads/ad-volunteer-old.png', 'club-detail.php?slug=doi-tinh-nguyen-xung-kich', 'top_banner', DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'Chiến dịch tuyển quân mùa hè', 1, 1000000, 'paid', 'approved', 'Đã chạy hoàn tất hợp đồng quảng bá.', 1);


-- Bảng Tài khoản đại diện CLB (Club Accounts)
CREATE TABLE IF NOT EXISTS `club_accounts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `club_id` INT NOT NULL UNIQUE,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `contact_person` VARCHAR(100) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` DATETIME DEFAULT NULL,
  CONSTRAINT `fk_club_account_club` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
