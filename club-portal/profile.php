<?php
/**
 * Demension - Chỉnh Sửa Thông Tin & Tùy Biến Màu Nền Trang Cá Nhân CLB (Club Profile & Theme Customizer)
 */

define('CLUBHUB_INIT', true);
$page_title = 'Trang Cá Nhân & Tùy Biến Màu Nền';
require_once __DIR__ . '/includes/header.php';

$club_id = current_club_id();
$errors = [];
$success_msg = '';

// Lấy danh sách mạng xã hội hiện tại
$socials_data = [];
if ($db) {
    $s_stmt = $db->prepare("SELECT platform, url FROM club_socials WHERE club_id = ?");
    $s_stmt->execute([$club_id]);
    while ($r = $s_stmt->fetch(PDO::FETCH_ASSOC)) {
        $socials_data[$r['platform']] = $r['url'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_protect();

    $name = trim($_POST['name'] ?? '');
    $short_name = trim($_POST['short_name'] ?? '');
    $summary = trim($_POST['summary'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $objectives = trim($_POST['objectives'] ?? '');
    $target_audience = trim($_POST['target_audience'] ?? '');
    $activities_achievements = trim($_POST['activities_achievements'] ?? '');
    $meeting_schedule = trim($_POST['meeting_schedule'] ?? '');
    $meeting_location = trim($_POST['meeting_location'] ?? '');
    $meeting_frequency = trim($_POST['meeting_frequency'] ?? '');
    $schedule_confirmed = isset($_POST['schedule_confirmed']) ? 1 : 0;
    $requirements = trim($_POST['requirements'] ?? '');
    $fee_info = trim($_POST['fee_info'] ?? '');

    // CÁC THUỘC TÍNH MÀU SẮC & GIAO DIỆN MỚI
    $custom_bg_color = trim($_POST['custom_bg_color'] ?? '#06102B');
    $custom_theme = trim($_POST['custom_theme'] ?? 'dimension-classic');
    $custom_accent_color = trim($_POST['custom_accent_color'] ?? '#F6C453');
    $custom_motion_effect = trim($_POST['custom_motion_effect'] ?? 'nebula-wave');

    // Mạng xã hội
    $socials_input = [
        'facebook'  => trim($_POST['social_facebook'] ?? ''),
        'tiktok'    => trim($_POST['social_tiktok'] ?? ''),
        'instagram' => trim($_POST['social_instagram'] ?? ''),
        'youtube'   => trim($_POST['social_youtube'] ?? ''),
        'website'   => trim($_POST['social_website'] ?? ''),
        'email'     => trim($_POST['social_email'] ?? ''),
        'messenger' => trim($_POST['social_messenger'] ?? '')
    ];

    if (empty($name)) $errors[] = 'Tên câu lạc bộ không được để trống.';
    if (empty($summary)) $errors[] = 'Vui lòng nhập lời giới thiệu ngắn (summary).';
    if (empty($description)) $errors[] = 'Vui lòng nhập mô tả chi tiết về CLB.';

    // Upload Logo nếu có
    $logo_path = $club_data['logo'] ?? '';
    if (!empty($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $up = handle_image_upload($_FILES['logo'], 'clubs', 2);
        if ($up['success']) {
            $logo_path = $up['path'];
        } else {
            $errors[] = 'Lỗi tải ảnh Logo: ' . $up['error'];
        }
    }

    // Upload Cover nếu có
    $cover_path = $club_data['cover_image'] ?? '';
    if (!empty($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $up_c = handle_image_upload($_FILES['cover_image'], 'clubs', 4);
        if ($up_c['success']) {
            $cover_path = $up_c['path'];
        } else {
            $errors[] = 'Lỗi tải ảnh Bìa (Cover): ' . $up_c['error'];
        }
    }

    // Upload Ảnh Nền Đáy Riêng (Custom Background Image) nếu có
    $custom_bg_image_path = $club_data['custom_bg_image'] ?? '';
    if (!empty($_FILES['custom_bg_image']) && $_FILES['custom_bg_image']['error'] === UPLOAD_ERR_OK) {
        $up_bg = handle_image_upload($_FILES['custom_bg_image'], 'club_backgrounds', 5);
        if ($up_bg['success']) {
            $custom_bg_image_path = $up_bg['path'];
        } else {
            $errors[] = 'Lỗi tải ảnh nền riêng: ' . $up_bg['error'];
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $db->prepare("
                UPDATE clubs SET
                    name = ?, short_name = ?, summary = ?, description = ?,
                    objectives = ?, target_audience = ?, activities_achievements = ?,
                    meeting_schedule = ?, meeting_location = ?, meeting_frequency = ?, schedule_confirmed = ?,
                    requirements = ?, fee_info = ?,
                    custom_bg_color = ?, custom_theme = ?, custom_accent_color = ?,
                    custom_bg_image = ?, custom_motion_effect = ?,
                    logo = ?, cover_image = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $name, $short_name, $summary, $description,
                $objectives, $target_audience, $activities_achievements,
                $meeting_schedule, $meeting_location, $meeting_frequency, $schedule_confirmed,
                $requirements, $fee_info,
                $custom_bg_color, $custom_theme, $custom_accent_color,
                $custom_bg_image_path, $custom_motion_effect,
                $logo_path, $cover_path, $club_id
            ]);

            // Cập nhật mạng xã hội
            $db->prepare("DELETE FROM club_socials WHERE club_id = ?")->execute([$club_id]);
            $ins_soc = $db->prepare("INSERT INTO club_socials (club_id, platform, url) VALUES (?, ?, ?)");
            foreach ($socials_input as $platform => $url) {
                if (!empty($url)) {
                    $ins_soc->execute([$club_id, $platform, $url]);
                }
            }

            flash_set('success', '✨ Đã lưu cập nhật thông tin, ảnh nền riêng & hiệu ứng động của CLB thành công!');
            redirect('club-portal/profile.php');
        } catch (Exception $e) {
            $errors[] = 'Lỗi cơ sở dữ liệu: ' . $e->getMessage();
        }
    }
}
?>

<form method="POST" action="" enctype="multipart/form-data">
  <?= csrf_field() ?>

  <?php if (!empty($errors)): ?>
    <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4); color: #fca5a5; padding: 14px 18px; border-radius: 12px; margin-bottom: 1.5rem;">
      <strong>⚠️ Vui lòng kiểm tra lại:</strong>
      <ul style="margin-left: 20px; margin-top: 6px;">
        <?php foreach ($errors as $er): ?>
          <li><?= e($er) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <?php 
    $portal_tier = get_club_tier_info($club_data['club_tier'] ?? 'standard');
  ?>
  <!-- KHỐI 0: THỨ HẠNG DANH DỰ (RANK CLB - DO ADMIN PHÊ DUYỆT) -->
  <div class="cp-card" style="border: 1.5px solid rgba(246, 196, 83, 0.45); background: linear-gradient(135deg, rgba(14, 34, 82, 0.95), rgba(7, 18, 48, 0.98)); margin-bottom: 1.5rem; position: relative; overflow: hidden;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1.5rem; position: relative; z-index: 2;">
      <div style="display: flex; align-items: center; gap: 1.25rem;">
        <?php if (!empty($portal_tier['crest_img'])): ?>
          <div style="width: 70px; height: 90px; display: flex; align-items: center; justify-content: center; filter: drop-shadow(0 0 15px <?= e($portal_tier['color']) ?>88);">
            <img src="<?= asset($portal_tier['crest_img']) ?>" alt="Rank Crest" style="max-width: 100%; max-height: 100%; object-fit: contain;">
          </div>
        <?php else: ?>
          <div style="font-size: 2.8rem; filter: drop-shadow(0 0 12px rgba(246,196,83,0.5)); line-height: 1;">
            <?= $portal_tier['icon'] ?>
          </div>
        <?php endif; ?>
        <div>
          <div style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; font-weight: 700;">
            Thứ Hạng Danh Dự CLB (Honor Rank)
          </div>
          <div style="font-size: 1.35rem; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 10px; margin-top: 2px;">
            <span style="background: linear-gradient(135deg, #FFF, <?= e($portal_tier['color']) ?>); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
              <?= e($portal_tier['name']) ?>
            </span>
            <span style="font-size: 0.75rem; padding: 3px 10px; border-radius: 999px; background: rgba(255, 255, 255, 0.08); border: 1px solid <?= e($portal_tier['color']) ?>66; color: <?= e($portal_tier['color']) ?>;">
              <?= e($portal_tier['desc']) ?>
            </span>
          </div>
        </div>
      </div>
      <div style="display: flex; align-items: center; gap: 14px;">
        <?php if (!empty($portal_tier['frame_img'])): ?>
          <div style="display: flex; align-items: center; gap: 8px; font-size: 0.82rem; color: #cbd5e1; background: rgba(0,0,0,0.3); padding: 6px 14px; border-radius: 999px; border: 1px solid <?= e($portal_tier['color']) ?>44;">
            <img src="<?= asset($portal_tier['frame_img']) ?>" alt="Frame Icon" style="width: 24px; height: 28px; object-fit: contain;">
            <span>Khung Vinh Dự Đang Kích Hoạt</span>
          </div>
        <?php endif; ?>
        <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.8rem; color: #94a3b8; background: rgba(255,255,255,0.06); padding: 6px 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1);">
          🔒 <em>Quyền do Ban Giám Hiệu & Admin phê duyệt</em>
        </span>
      </div>
    </div>
  </div>

  <!-- KHỐI 1: TÙY BIẾN MÀU NỀN & PHONG CÁCH TRANG CÁ NHÂN (THEME & BACKGROUND) -->
  <div class="cp-card" style="border: 2px solid rgba(246, 196, 83, 0.4); box-shadow: 0 0 30px rgba(246, 196, 83, 0.1);">
    <div class="cp-card-header">
      <div class="cp-card-title">
        <span>🎨</span>
        <span>Tùy Biến Màu Nền & Phong Cách Trang Cá Nhân (Nét Riêng Của CLB)</span>
      </div>
      <span class="badge-star">ĐỘC BẢN CLB</span>
    </div>

    <p style="color: #cbd5e1; font-size: 0.9rem; margin-bottom: 1.5rem;">
      Khi sinh viên bấm vào xem trang chi tiết của CLB bạn, toàn bộ không gian trang cá nhân sẽ áp dụng màu nền, phong cách chủ đạo và màu điểm nhấn do CLB bạn tự chọn dưới đây:
    </p>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
      <!-- Chọn Preset Theme -->
      <div>
        <label style="display: block; font-weight: 700; color: #fff; margin-bottom: 8px;">1. Chọn Bộ Phong Cách (Preset Theme)</label>
        <select name="custom_theme" id="custom_theme_select" class="form-control" style="background: #091536;" onchange="applyPresetTheme(this.value)">
          <option value="dimension-classic" <?= ($club_data['custom_theme'] ?? '') === 'dimension-classic' ? 'selected' : '' ?>>🌌 Dimension Classic (Vũ trụ đêm & Vàng kim)</option>
          <option value="cyber-neon" <?= ($club_data['custom_theme'] ?? '') === 'cyber-neon' ? 'selected' : '' ?>>⚡ Cyber Neon (Xanh điện quang & Công nghệ số)</option>
          <option value="crimson-flame" <?= ($club_data['custom_theme'] ?? '') === 'crimson-flame' ? 'selected' : '' ?>>🔥 Crimson Flame (Nhiệt huyết bốc lửa & Năng động)</option>
          <option value="emerald-nature" <?= ($club_data['custom_theme'] ?? '') === 'emerald-nature' ? 'selected' : '' ?>>🌿 Emerald Mystic (Xanh lục bảo & Tình nguyện)</option>
          <option value="sunset-dream" <?= ($club_data['custom_theme'] ?? '') === 'sunset-dream' ? 'selected' : '' ?>>🌅 Sunset Nebula (Hoàng hôn tím hồng mộng mơ)</option>
          <option value="midnight-ocean" <?= ($club_data['custom_theme'] ?? '') === 'midnight-ocean' ? 'selected' : '' ?>>🌊 Midnight Ocean (Đại dương xanh thẳm)</option>
          <option value="custom" <?= ($club_data['custom_theme'] ?? '') === 'custom' ? 'selected' : '' ?>>✨ Tự phối màu riêng (Custom Palette)</option>
        </select>
      </div>

      <!-- Chọn Màu Background bằng Color Picker -->
      <div>
        <label style="display: block; font-weight: 700; color: #fff; margin-bottom: 8px;">2. Màu Nền Chính (Background Color)</label>
        <div style="display: flex; align-items: center; gap: 12px;">
          <input type="color" id="bg_color_picker" value="<?= e($club_data['custom_bg_color'] ?: '#06102B') ?>" style="width: 50px; height: 44px; padding: 0; border: none; border-radius: 8px; cursor: pointer; background: transparent;" oninput="syncColorInput('bg_color_picker', 'custom_bg_color')">
          <input type="text" name="custom_bg_color" id="custom_bg_color" value="<?= e($club_data['custom_bg_color'] ?: '#06102B') ?>" class="form-control" placeholder="#06102B" oninput="syncColorPicker('custom_bg_color', 'bg_color_picker')">
        </div>
        <small style="color: #94a3b8; font-size: 0.8rem;">Mã màu Hex hoặc Gradient (VD: #091536, #0e1e47, #130a2a)</small>
      </div>

      <!-- Chọn Màu Accent (Điểm nhấn) -->
      <div>
        <label style="display: block; font-weight: 700; color: #fff; margin-bottom: 8px;">3. Màu Điểm Nhấn (Accent Star Color)</label>
        <div style="display: flex; align-items: center; gap: 12px;">
          <input type="color" id="accent_color_picker" value="<?= e($club_data['custom_accent_color'] ?: '#F6C453') ?>" style="width: 50px; height: 44px; padding: 0; border: none; border-radius: 8px; cursor: pointer; background: transparent;" oninput="syncColorInput('accent_color_picker', 'custom_accent_color')">
          <input type="text" name="custom_accent_color" id="custom_accent_color" value="<?= e($club_data['custom_accent_color'] ?: '#F6C453') ?>" class="form-control" placeholder="#F6C453" oninput="syncColorPicker('custom_accent_color', 'accent_color_picker')">
        </div>
        <small style="color: #94a3b8; font-size: 0.8rem;">Màu ngôi sao, viền khung và nút bấm chính</small>
      </div>

      <!-- Chọn Hiệu Ứng Động (Dynamic Motion Effects) -->
      <div>
        <label style="display: block; font-weight: 700; color: #fff; margin-bottom: 8px;">4. Hiệu Ứng Động Độc Quyền (Particle & Motion Effects)</label>
        <select name="custom_motion_effect" id="custom_motion_effect" class="form-control" style="background: #091536;" onchange="updateMotionPreview(this.value)">
          <optgroup label="🔥 Khí Thế & Chiến Binh">
            <option value="fire-burst" <?= ($club_data['custom_motion_effect'] ?? '') === 'fire-burst' ? 'selected' : '' ?>>🔥 Lửa Bùng Lên & Tàn Tro Rực Đỏ (Blazing Crimson Flame)</option>
            <option value="thunder-storm" <?= ($club_data['custom_motion_effect'] ?? '') === 'thunder-storm' ? 'selected' : '' ?>>⚡ Sấm Sét Tinh Vân & Lôi Điện (Cosmic Lightning Storm)</option>
          </optgroup>
          <optgroup label="💻 Kỹ Thuật & Công Nghệ Số">
            <option value="cyber-matrix" <?= ($club_data['custom_motion_effect'] ?? '') === 'cyber-matrix' ? 'selected' : '' ?>>💻 Ma Trận Công Nghệ Số & Mạch Điện Tử (Cyber Tech Grid)</option>
            <option value="time-warp" <?= ($club_data['custom_motion_effect'] ?? '') === 'time-warp' ? 'selected' : '' ?>>⏳ Dòng Chảy Lỗ Sâu Thời Không (Space-Time Warp Stream)</option>
          </optgroup>
          <optgroup label="🌌 Nghệ Thuật & Tinh Tú Thần Thoại">
            <option value="golden-rain" <?= ($club_data['custom_motion_effect'] ?? '') === 'golden-rain' ? 'selected' : '' ?>>✨ Mưa Bụi Vàng Hoàng Kim Lấp Lánh (Golden Stardust Rain)</option>
            <option value="nebula-wave" <?= ($club_data['custom_motion_effect'] ?? '') === 'nebula-wave' ? 'selected' : '' ?>>🌊 Sóng Tinh Vân Uốn Lượn Huyền Ảo (Cosmic Ocean Wave)</option>
            <option value="sakura-drift" <?= ($club_data['custom_motion_effect'] ?? '') === 'sakura-drift' ? 'selected' : '' ?>>🌸 Cánh Hoa Thời Không & Gió Cuốn (Cherry Blossom Breeze)</option>
            <option value="frost-vortex" <?= ($club_data['custom_motion_effect'] ?? '') === 'frost-vortex' ? 'selected' : '' ?>>❄️ Bão Tuyết Băng Tinh Bắc Cực (Glacial Frost Vortex)</option>
          </optgroup>
        </select>
        <small style="color: #94a3b8; font-size: 0.8rem;">Hiệu ứng Canvas hoạt họa chân thực bùng nổ toàn màn hình khi người xem vào CLB!</small>
      </div>
    </div>

    <!-- Tải Lên Ảnh Lớp Đáy Riêng Của CLB (Theo chủ đề sự kiện) -->
    <div style="background: rgba(9, 21, 54, 0.7); border: 1px dashed rgba(246, 196, 83, 0.35); border-radius: 12px; padding: 1.25rem; margin-bottom: 1.5rem;">
      <label style="display: block; font-weight: 700; color: #F6C453; margin-bottom: 6px; font-size: 0.95rem;">
        🖼️ 5. Ảnh Nền Lớp Đáy Riêng Của CLB (Theo từng chủ đề sự kiện / mùa tuyển)
      </label>
      <p style="color: #cbd5e1; font-size: 0.85rem; margin-bottom: 10px;">
        CLB có thể tải lên bức ảnh nền độc quyền (ví dụ: poster sự kiện lớn, ảnh toàn đoàn, wallpaper concept riêng) để làm nền đáy toàn trang khi sinh viên vào xem CLB của bạn!
      </p>
      <div style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap;">
        <?php if (!empty($club_data['custom_bg_image'])): ?>
          <div style="width: 140px; height: 80px; border-radius: 8px; overflow: hidden; border: 1px solid #F6C453;">
            <img src="<?= upload_url($club_data['custom_bg_image']) ?>" alt="Ảnh nền CLB" style="width: 100%; height: 100%; object-fit: cover;">
          </div>
        <?php endif; ?>
        <div style="flex: 1; min-width: 250px;">
          <input type="file" name="custom_bg_image" class="form-control" accept="image/png, image/jpeg, image/webp">
          <small style="color: #94a3b8; font-size: 0.8rem;">Định dạng: JPG, PNG, WebP (Tối đa 5MB). Để trống nếu muốn giữ ảnh cũ hoặc dùng màu nền.</small>
        </div>
      </div>
    </div>

    <!-- Khung xem trước màu sắc (Live Preview Box) -->
    <div style="background: rgba(6, 16, 43, 0.8); border: 1px solid rgba(255,255,255,0.1); border-radius: 14px; padding: 1.25rem;">
      <div style="font-size: 0.85rem; color: #94a3b8; margin-bottom: 8px;">🌟 Xem trước hiệu ứng màu sắc trên trang cá nhân:</div>
      <div id="theme_preview_box" style="padding: 1.5rem; border-radius: 12px; background: <?= e($club_data['custom_bg_color'] ?: '#06102B') ?>; border: 2px solid <?= e($club_data['custom_accent_color'] ?: '#F6C453') ?>; transition: all 0.3s ease; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
        <div style="display: flex; align-items: center; gap: 14px;">
          <div id="preview_star" style="width: 36px; height: 36px; border-radius: 50%; background: <?= e($club_data['custom_accent_color'] ?: '#F6C453') ?>; display: flex; align-items: center; justify-content: center; color: #000; font-size: 1.2rem; font-weight: bold;">✦</div>
          <div>
            <h4 id="preview_title" style="color: #ffffff; font-size: 1.15rem; font-weight: 800; margin: 0;"><?= e($club_data['name']) ?></h4>
            <span id="preview_tag" style="color: <?= e($club_data['custom_accent_color'] ?: '#F6C453') ?>; font-size: 0.85rem; font-weight: 600;">✦ Phong Cách Không Gian Độc Bản</span>
          </div>
        </div>
        <p style="color: rgba(255,255,255,0.8); font-size: 0.9rem; margin-top: 10px; margin-bottom: 0;">
          Đây là mẫu minh họa màu nền trang chi tiết của CLB bạn khi người xem ghé thăm!
        </p>
      </div>
    </div>
  </div>

  <!-- KHỐI 2: THÔNG TIN CƠ BẢN & HÌNH ẢNH NHẬN DIỆN -->
  <div class="cp-card">
    <div class="cp-card-header">
      <div class="cp-card-title">
        <span>🏛️</span>
        <span>Thông Tin Cơ Bản & Hình Ảnh Nhận Diện</span>
      </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
      <div>
        <div class="form-group" style="margin-bottom: 1.25rem;">
          <label style="display: block; font-weight: 700; color: #fff; margin-bottom: 6px;">Tên đầy đủ Câu lạc bộ *</label>
          <input type="text" name="name" class="form-control" value="<?= e($club_data['name']) ?>" required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
          <div>
            <label style="display: block; font-weight: 700; color: #fff; margin-bottom: 6px;">Tên viết tắt / Tên tiếng Anh</label>
            <input type="text" name="short_name" class="form-control" value="<?= e($club_data['short_name'] ?? '') ?>" placeholder="VD: DevAI, Melody...">
          </div>
          <div>
            <label style="display: block; font-weight: 700; color: #fff; margin-bottom: 6px;">Lĩnh vực hoạt động</label>
            <input type="text" class="form-control" value="<?= e($club_data['category_id'] ?? '') ?>" disabled style="opacity: 0.6; cursor: not-allowed;" title="Lĩnh vực do Super Admin quản lý">
          </div>
        </div>

        <div class="form-group" style="margin-bottom: 1.25rem;">
          <label style="display: block; font-weight: 700; color: #fff; margin-bottom: 6px;">Lời giới thiệu ngắn (Hiển thị ngoài thẻ trang chủ) *</label>
          <textarea name="summary" class="form-control" rows="2" required><?= e($club_data['summary']) ?></textarea>
        </div>

        <div class="form-group" style="margin-bottom: 1.25rem;">
          <label style="display: block; font-weight: 700; color: #fff; margin-bottom: 6px;">Mô tả chi tiết câu chuyện & hoạt động của CLB *</label>
          <textarea name="description" class="form-control" rows="5" required><?= e($club_data['description']) ?></textarea>
        </div>
      </div>

      <!-- Cột Logo & Ảnh Bìa -->
      <div>
        <div class="form-group" style="margin-bottom: 1.5rem; text-align: center; background: rgba(6,16,43,0.5); padding: 1.25rem; border-radius: 12px; border: 1px solid rgba(255,255,255,0.08);">
          <label style="display: block; font-weight: 700; color: #fff; margin-bottom: 8px;">Logo CLB</label>
          <img src="<?= !empty($club_data['logo']) ? asset($club_data['logo']) : asset('images/dimension-logo.png') ?>" alt="Logo" style="width: 100px; height: 100px; border-radius: 16px; object-fit: cover; border: 2px solid #F6C453; margin-bottom: 12px;">
          <input type="file" name="logo" accept="image/*" class="form-control" style="font-size: 0.8rem; padding: 6px;">
        </div>

        <div class="form-group" style="text-align: center; background: rgba(6,16,43,0.5); padding: 1.25rem; border-radius: 12px; border: 1px solid rgba(255,255,255,0.08);">
          <label style="display: block; font-weight: 700; color: #fff; margin-bottom: 8px;">Ảnh Bìa (Cover Banner)</label>
          <img src="<?= !empty($club_data['cover_image']) ? asset($club_data['cover_image']) : asset('images/dimension-banner.png') ?>" alt="Cover" style="width: 100%; height: 90px; border-radius: 10px; object-fit: cover; border: 1px solid rgba(255,255,255,0.2); margin-bottom: 12px;">
          <input type="file" name="cover_image" accept="image/*" class="form-control" style="font-size: 0.8rem; padding: 6px;">
        </div>
      </div>
    </div>
  </div>

  <!-- KHỐI 3: LỊCH SINH HOẠT & THÔNG TIN HOẠT ĐỘNG -->
  <div class="cp-card">
    <div class="cp-card-header">
      <div class="cp-card-title">
        <span>⏰</span>
        <span>Lịch Sinh Hoạt & Tiêu Chí Thành Viên</span>
      </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; margin-bottom: 1.25rem;">
      <div>
        <label style="display: block; font-weight: 700; color: #fff; margin-bottom: 6px;">Thời gian sinh hoạt</label>
        <input type="text" name="meeting_schedule" class="form-control" value="<?= e($club_data['meeting_schedule'] ?? '') ?>" placeholder="VD: 18:30 - 20:30 Thứ Tư">
      </div>
      <div>
        <label style="display: block; font-weight: 700; color: #fff; margin-bottom: 6px;">Địa điểm sinh hoạt</label>
        <input type="text" name="meeting_location" class="form-control" value="<?= e($club_data['meeting_location'] ?? '') ?>" placeholder="VD: Phòng Lab B4.02 hoặc Online">
      </div>
      <div>
        <label style="display: block; font-weight: 700; color: #fff; margin-bottom: 6px;">Tần suất sinh hoạt</label>
        <input type="text" name="meeting_frequency" class="form-control" value="<?= e($club_data['meeting_frequency'] ?? '') ?>" placeholder="VD: 1 buổi/tuần, 2 tuần/lần">
      </div>
    </div>

    <div style="margin-bottom: 1.5rem; background: rgba(6,16,43,0.5); padding: 12px 16px; border-radius: 10px;">
      <label style="display: flex; align-items: center; gap: 10px; color: #fff; font-weight: 600; cursor: pointer;">
        <input type="checkbox" name="schedule_confirmed" value="1" <?= (!empty($club_data['schedule_confirmed'])) ? 'checked' : '' ?> style="width: 18px; height: 18px;">
        <span>CLB đã chốt lịch sinh hoạt cố định (Bỏ tích nếu lịch còn phụ thuộc vào đầu kỳ mới và cần xác nhận lại)</span>
      </label>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 1.25rem;">
      <div>
        <label style="display: block; font-weight: 700; color: #fff; margin-bottom: 6px;">Yêu cầu đối với thành viên</label>
        <textarea name="requirements" class="form-control" rows="3"><?= e($club_data['requirements'] ?? '') ?></textarea>
      </div>
      <div>
        <label style="display: block; font-weight: 700; color: #fff; margin-bottom: 6px;">Lệ phí quỹ CLB</label>
        <textarea name="fee_info" class="form-control" rows="3"><?= e($club_data['fee_info'] ?? '') ?></textarea>
      </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
      <div>
        <label style="display: block; font-weight: 700; color: #fff; margin-bottom: 6px;">Mục tiêu & Lợi ích khi tham gia</label>
        <textarea name="objectives" class="form-control" rows="3"><?= e($club_data['objectives'] ?? '') ?></textarea>
      </div>
      <div>
        <label style="display: block; font-weight: 700; color: #fff; margin-bottom: 6px;">Đối tượng sinh viên phù hợp</label>
        <textarea name="target_audience" class="form-control" rows="3"><?= e($club_data['target_audience'] ?? '') ?></textarea>
      </div>
    </div>
  </div>

  <!-- KHỐI 4: KÊNH MẠNG XÃ HỘI CHÍNH THỨC -->
  <div class="cp-card">
    <div class="cp-card-header">
      <div class="cp-card-title">
        <span>🌐</span>
        <span>Kênh Mạng Xã Hội Chính Thức (Official Socials)</span>
      </div>
    </div>

    <p style="color: #94a3b8; font-size: 0.85rem; margin-bottom: 1.25rem;">
      Điền đường link chính thức của CLB. Kênh nào để trống sẽ tự động được ẩn khỏi trang người xem để đảm bảo tính xác thực.
    </p>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem;">
      <div>
        <label style="display: block; font-size: 0.85rem; color: #cbd5e1; margin-bottom: 4px;">Facebook Fanpage</label>
        <input type="url" name="social_facebook" class="form-control" value="<?= e($socials_data['facebook'] ?? '') ?>" placeholder="https://facebook.com/clb...">
      </div>
      <div>
        <label style="display: block; font-size: 0.85rem; color: #cbd5e1; margin-bottom: 4px;">TikTok Channel</label>
        <input type="url" name="social_tiktok" class="form-control" value="<?= e($socials_data['tiktok'] ?? '') ?>" placeholder="https://tiktok.com/@clb...">
      </div>
      <div>
        <label style="display: block; font-size: 0.85rem; color: #cbd5e1; margin-bottom: 4px;">Instagram Profile</label>
        <input type="url" name="social_instagram" class="form-control" value="<?= e($socials_data['instagram'] ?? '') ?>" placeholder="https://instagram.com/clb...">
      </div>
      <div>
        <label style="display: block; font-size: 0.85rem; color: #cbd5e1; margin-bottom: 4px;">YouTube Channel</label>
        <input type="url" name="social_youtube" class="form-control" value="<?= e($socials_data['youtube'] ?? '') ?>" placeholder="https://youtube.com/@clb...">
      </div>
      <div>
        <label style="display: block; font-size: 0.85rem; color: #cbd5e1; margin-bottom: 4px;">Website CLB / GitHub / Notion</label>
        <input type="url" name="social_website" class="form-control" value="<?= e($socials_data['website'] ?? '') ?>" placeholder="https://clb.example.com">
      </div>
      <div>
        <label style="display: block; font-size: 0.85rem; color: #cbd5e1; margin-bottom: 4px;">Email Liên Hệ</label>
        <input type="text" name="social_email" class="form-control" value="<?= e($socials_data['email'] ?? '') ?>" placeholder="mailto:clb@fpt.edu.vn">
      </div>
    </div>
  </div>

  <div style="position: sticky; bottom: 1.5rem; background: rgba(9, 21, 54, 0.95); backdrop-filter: blur(12px); padding: 1.25rem 2rem; border-radius: 16px; border: 1px solid rgba(246,196,83,0.3); display: flex; justify-content: space-between; align-items: center; z-index: 50; box-shadow: 0 10px 40px rgba(0,0,0,0.6);">
    <div style="color: #cbd5e1; font-size: 0.9rem;">
      ✦ Kiểm tra kỹ các thông tin trước khi lưu lên hệ thống Demension.
    </div>
    <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #F6C453, #D97706); color: #06102B; font-weight: 800; padding: 12px 28px; border-radius: 12px; font-size: 1rem; border: none; cursor: pointer; box-shadow: 0 4px 20px rgba(246,196,83,0.4);">
      💾 Lưu Cập Nhật Trang Cá Nhân
    </button>
  </div>
</form>

<script>
// Bộ preset phối màu nhanh
const themePresets = {
  'dimension-classic': { bg: '#06102B', accent: '#F6C453' },
  'cyber-neon':        { bg: '#051923', accent: '#00F0FF' },
  'crimson-flame':     { bg: '#25090F', accent: '#FF4D6D' },
  'emerald-nature':    { bg: '#071E17', accent: '#10B981' },
  'sunset-dream':      { bg: '#1A0B2E', accent: '#F472B6' },
  'midnight-ocean':    { bg: '#0A1931', accent: '#60A5FA' }
};

function applyPresetTheme(themeName) {
  if (themePresets[themeName]) {
    const p = themePresets[themeName];
    document.getElementById('custom_bg_color').value = p.bg;
    document.getElementById('bg_color_picker').value = p.bg;
    document.getElementById('custom_accent_color').value = p.accent;
    document.getElementById('accent_color_picker').value = p.accent;
    updateLivePreview();
  }
}

function syncColorInput(pickerId, inputId) {
  const val = document.getElementById(pickerId).value;
  document.getElementById(inputId).value = val;
  updateLivePreview();
}

function syncColorPicker(inputId, pickerId) {
  const val = document.getElementById(inputId).value;
  if (/^#[0-9A-F]{6}$/i.test(val)) {
    document.getElementById(pickerId).value = val;
    updateLivePreview();
  }
}

function updateLivePreview() {
  const bg = document.getElementById('custom_bg_color').value;
  const accent = document.getElementById('custom_accent_color').value;
  const box = document.getElementById('theme_preview_box');
  const star = document.getElementById('preview_star');
  const tag = document.getElementById('preview_tag');

  box.style.background = bg;
  box.style.borderColor = accent;
  star.style.background = accent;
  tag.style.color = accent;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>