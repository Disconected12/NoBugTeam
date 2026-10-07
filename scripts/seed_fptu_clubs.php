<?php
/**
 * Script cập nhật và đồng bộ 48 Câu Lạc Bộ FPTU vào cơ sở dữ liệu
 */

define('CLUBHUB_INIT', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$db = get_db_connection();
if (!$db) {
    die("Database connection failed\n");
}

// Danh sách 48 CLB người dùng cung cấp kèm phân loại lĩnh vực, motion effect, và màu chủ đạo
$clubs_raw = [
    [
        'name' => 'FPTU Basketball Club',
        'short_name' => 'FBC - Bóng Rổ',
        'email' => 'bongrofpt@gmail.com',
        'fb' => 'https://www.facebook.com/FPTBasketballClub',
        'category_id' => 4, // Thể dục & Thể thao
        'motion' => 'fire-burst',
        'theme' => 'crimson-flame',
        'bg_color' => '#1A0B2E',
        'accent_color' => '#F59E0B',
        'summary' => 'Câu lạc bộ Bóng rổ Đại học FPT - Nơi quy tụ những baller đam mê trái bóng cam, nhiệt huyết thi đấu các giải phong trào và toàn quốc.',
        'username' => 'clb_basketball'
    ],
    [
        'name' => 'FPTU Street Workout',
        'short_name' => 'FUSW',
        'email' => 'fuswclub@gmail.com',
        'fb' => 'https://www.facebook.com/FuStreetWorkout',
        'category_id' => 4,
        'motion' => 'fire-burst',
        'theme' => 'crimson-flame',
        'bg_color' => '#180E29',
        'accent_color' => '#EF4444',
        'summary' => 'CLB Thể hình đường phố FPTU - Rèn luyện sức mạnh thể chất, calisthenics và tinh thần thép vượt qua giới hạn bản thân.',
        'username' => 'clb_streetworkout'
    ],
    [
        'name' => 'FPTU Esports Club',
        'short_name' => 'ESC FPTHN',
        'email' => 'fuesportsclub@gmail.com',
        'fb' => 'https://www.facebook.com/ESCFPTHN',
        'category_id' => 4,
        'motion' => 'cyber-matrix',
        'theme' => 'cyber-neon',
        'bg_color' => '#071838',
        'accent_color' => '#00F0FF',
        'summary' => 'CLB Thể thao điện tử Đại học FPT Hà Nội - Đấu trường rèn luyện kỹ năng LMHT, Valorant, Tốc Chiến, tổ chức giải đấu chuyên nghiệp.',
        'username' => 'clb_esports'
    ],
    [
        'name' => 'FPTU Karate Club',
        'short_name' => 'FKC - Karate',
        'email' => 'fkc.fptukarateclub@gmail.com',
        'fb' => 'https://www.facebook.com/profile.php?id=61550706956382',
        'category_id' => 4,
        'motion' => 'thunder-storm',
        'theme' => 'dimension-classic',
        'bg_color' => '#0B1D42',
        'accent_color' => '#38BDF8',
        'summary' => 'CLB Karate FPTU - Đạo quán rèn luyện tinh thần võ đạo truyền thống, đòn thế chuẩn mực và kỹ năng tự vệ thực chiến.',
        'username' => 'clb_karate'
    ],
    [
        'name' => 'FPTU Gymnastic',
        'short_name' => 'FuGymnastic',
        'email' => 'fugymnastic@gmail.com',
        'fb' => 'https://www.facebook.com/FuGymnastic',
        'category_id' => 4,
        'motion' => 'golden-rain',
        'theme' => 'dimension-classic',
        'bg_color' => '#0B2252',
        'accent_color' => '#F6C453',
        'summary' => 'CLB Thể dục dụng cụ & Thể hình FPTU - Phát triển vóc dáng chuẩn, sự dẻo dai linh hoạt và lối sống sinh viên lành mạnh.',
        'username' => 'clb_gymnastic'
    ],
    [
        'name' => 'FPTU Volleyball Club',
        'short_name' => 'FUVOLLEYBALL',
        'email' => 'fptuvolleyballclub@gmail.com',
        'fb' => 'https://www.facebook.com/FUVOLLEYBALLCLUB',
        'category_id' => 4,
        'motion' => 'nebula-wave',
        'theme' => 'midnight-ocean',
        'bg_color' => '#0A2540',
        'accent_color' => '#60A5FA',
        'summary' => 'CLB Bóng chuyền Đại học FPT - Kết nối niềm đam mê bóng chuyền, phối hợp đồng đội ăn ý và giao lưu các giải đấu lớn.',
        'username' => 'clb_volleyball'
    ],
    [
        'name' => 'FPTU Nunchaku club',
        'short_name' => 'FNC - Côn Nhị Khúc',
        'email' => 'fncnunchakuclub@gmail.com',
        'fb' => 'https://www.facebook.com/nunchaku.fnc',
        'category_id' => 4,
        'motion' => 'thunder-storm',
        'theme' => 'dimension-classic',
        'bg_color' => '#121C3B',
        'accent_color' => '#EAB308',
        'summary' => 'CLB Côn nhị khúc FPTU - Nghệ thuật múa côn uyển chuyển, tốc độ và đòn thế tấn công tự vệ đẹp mắt.',
        'username' => 'clb_nunchaku'
    ],
    [
        'name' => 'FPTU Muay Club',
        'short_name' => 'FMUC - Muay Thai',
        'email' => 'fptumuayclub@gmail.com',
        'fb' => 'https://www.facebook.com/fmuc.fptu',
        'category_id' => 4,
        'motion' => 'fire-burst',
        'theme' => 'crimson-flame',
        'bg_color' => '#210B1B',
        'accent_color' => '#EF4444',
        'summary' => 'CLB Muay Thái FPTU - Bộ môn nghệ thuật 8 chi đầy sức mạnh, rèn luyện sự bền bỉ, ý chí kiên cường và thể lực đỉnh cao.',
        'username' => 'clb_muay'
    ],
    [
        'name' => 'FPTU VOVINAM CLUB',
        'short_name' => 'FVCHN - Vovinam',
        'email' => 'fptvovinamclub@gmail.com',
        'fb' => 'https://www.facebook.com/fvchn',
        'category_id' => 4,
        'motion' => 'fire-burst',
        'theme' => 'dimension-classic',
        'bg_color' => '#0A1E4A',
        'accent_color' => '#38BDF8',
        'summary' => 'CLB Vovinam Đại học FPT Hà Nội - Nơi thăng hoa Việt Võ Đạo, các đòn chân kẹp cổ trứ danh và tinh thần thượng võ dân tộc.',
        'username' => 'clb_vovinam'
    ],
    [
        'name' => 'FPTU Taekwondo Club',
        'short_name' => 'FTC - Taekwondo',
        'email' => 'taekwondofpt@gmail.com',
        'fb' => 'https://www.facebook.com/FTCTaekwondo',
        'category_id' => 4,
        'motion' => 'thunder-storm',
        'theme' => 'dimension-classic',
        'bg_color' => '#0E1F47',
        'accent_color' => '#F59E0B',
        'summary' => 'CLB Taekwondo FPTU - Những cú đá xoay 360 độ điêu luyện, rèn luyện tốc độ, kỷ luật và phản xạ thi đấu đối kháng.',
        'username' => 'clb_taekwondo'
    ],
    [
        'name' => 'FPTU Badminton Club',
        'short_name' => 'FBC - Cầu Lông',
        'email' => 'caulongfpt@gmail.com',
        'fb' => 'https://www.facebook.com/FPTUbadminton',
        'category_id' => 4,
        'motion' => 'golden-rain',
        'theme' => 'dimension-classic',
        'bg_color' => '#0B2252',
        'accent_color' => '#F6C453',
        'summary' => 'CLB Cầu lông FPTU - Sân chơi giao lưu kỹ thuật smash, bỏ nhỏ và các trận đấu đôi kịch tính của các tay vợt sinh viên.',
        'username' => 'clb_badminton'
    ],
    [
        'name' => 'FPTU Pickleball Club',
        'short_name' => 'FPC - Pickleball',
        'email' => 'fptupickleballclub@gmail.com',
        'fb' => 'https://www.facebook.com/profile.php?id=61564857898057',
        'category_id' => 4,
        'motion' => 'nebula-wave',
        'theme' => 'cyber-neon',
        'bg_color' => '#092540',
        'accent_color' => '#10B981',
        'summary' => 'CLB Pickleball FPTU - Môn thể thao xu hướng kết hợp tennis, bóng bàn và cầu lông, rèn luyện phản xạ nhanh và sự vui vẻ.',
        'username' => 'clb_pickleball'
    ],
    [
        'name' => 'FPTU Football Club',
        'short_name' => 'FFC - Bóng Đá',
        'email' => 'ffc.fptu.football.club@gmail.com',
        'fb' => 'https://www.facebook.com/fu.football.team',
        'category_id' => 4,
        'motion' => 'fire-burst',
        'theme' => 'crimson-flame',
        'bg_color' => '#0F2618',
        'accent_color' => '#10B981',
        'summary' => 'Đội bóng đá Đại học FPT - Đại diện tranh tài tại các giải bóng đá sinh viên toàn quốc, đam mê sân cỏ và tinh thần đồng đội bất diệt.',
        'username' => 'clb_football'
    ],
    [
        'name' => 'FPTU Soleil Crew',
        'short_name' => 'Soleil Crew',
        'email' => 'soleilcrewfptu@gmail.com',
        'fb' => 'https://www.facebook.com/soleilcrewfptu',
        'category_id' => 3, // Nghệ thuật & Âm nhạc
        'motion' => 'sakura-drift',
        'theme' => 'sunset-dream',
        'bg_color' => '#210C35',
        'accent_color' => '#F472B6',
        'summary' => 'CLB Nhảy Vũ đạo Soleil Crew - Những bước nhảy cuốn hút, năng lượng sân khấu bùng nổ trong các sự kiện đại nhạc hội FPTU.',
        'username' => 'clb_soleil'
    ],
    [
        'name' => 'FPTU - Blazie Dance Team',
        'short_name' => 'Blazie',
        'email' => 'bl4213ee@gmail.com',
        'fb' => 'https://www.facebook.com/bl4213',
        'category_id' => 3,
        'motion' => 'fire-burst',
        'theme' => 'crimson-flame',
        'bg_color' => '#260A1D',
        'accent_color' => '#FB7185',
        'summary' => 'Đội nhảy Blazie Dance Team - Phong cách K-Pop, Hip-hop hiện đại, biên đạo ấn tượng và tinh thần cháy hết mình vì đam mê nhảy.',
        'username' => 'clb_blazie'
    ],
    [
        'name' => 'FPTU - Melody Club',
        'short_name' => 'Melody Club',
        'email' => 'fu.melody.club@gmail.com',
        'fb' => 'https://www.facebook.com/fptu.melody.club',
        'category_id' => 3,
        'motion' => 'sakura-drift',
        'theme' => 'sunset-dream',
        'bg_color' => '#1E103A',
        'accent_color' => '#C084FC',
        'summary' => 'CLB Âm nhạc Melody - Khúc ca thanh xuân của sinh viên FPTU, ban nhạc acoustic hòa ca những giai điệu ngọt ngào sâu lắng.',
        'username' => 'clb_melody'
    ],
    [
        'name' => 'color team',
        'short_name' => 'Color Team',
        'email' => 'colorteam.vn@gmail.com',
        'fb' => 'https://www.facebook.com/colorteamvn',
        'category_id' => 6, // Truyền thông & Sự kiện
        'motion' => 'cosmic-shimmer',
        'theme' => 'dimension-classic',
        'bg_color' => '#121C42',
        'accent_color' => '#F6C453',
        'summary' => 'Color Team - Đội ngũ sáng tạo nội dung, thiết kế truyền thông và lan tỏa sắc màu trẻ trung của đời sống đại học.',
        'username' => 'clb_colorteam'
    ],
    [
        'name' => 'Câu lạc bộ Vì cộng đồng iGo',
        'short_name' => 'iGo Club',
        'email' => 'igoclubvicongdong@gmail.com',
        'fb' => 'https://www.facebook.com/iGoClub',
        'category_id' => 5, // Tình nguyện & Xã hội
        'motion' => 'golden-rain',
        'theme' => 'emerald-nature',
        'bg_color' => '#0A2525',
        'accent_color' => '#10B981',
        'summary' => 'CLB Vì cộng đồng iGo - Những bước chân thiện nguyện đến vùng cao, trao yêu thương và kiến tạo các giá trị xã hội nhân văn.',
        'username' => 'clb_igo'
    ],
    [
        'name' => 'Mây Mưa Club - FPTU Japan Club',
        'short_name' => 'MayMua FJC',
        'email' => 'fjcmaymuaclub@gmail.com',
        'fb' => 'https://www.facebook.com/maymuaclub',
        'category_id' => 2, // Học thuật & Kỹ năng
        'motion' => 'sakura-drift',
        'theme' => 'sunset-dream',
        'bg_color' => '#24102C',
        'accent_color' => '#F472B6',
        'summary' => 'CLB Văn hóa & Tiếng Nhật Mây Mưa - Khám phá văn hóa xứ sở Phù Tang, lễ hội Cosplay, Anime và nâng cao năng lực Nhật ngữ.',
        'username' => 'clb_maymua'
    ],
    [
        'name' => 'Moviemaniacs - FPTU Acting & Filming Club',
        'short_name' => 'Moviemaniacs',
        'email' => 'fptumoviemaniacs.contact@gmail.com',
        'fb' => 'https://www.facebook.com/Fptumoviemaniacsclub',
        'category_id' => 3,
        'motion' => 'cosmic-shimmer',
        'theme' => 'dimension-classic',
        'bg_color' => '#131B38',
        'accent_color' => '#F6C453',
        'summary' => 'CLB Diễn xuất & Điện ảnh Moviemaniacs - Làm phim ngắn, kịch bản nghệ thuật và chắp cánh cho những ước mơ đạo diễn sinh viên.',
        'username' => 'clb_moviemaniacs'
    ],
    [
        'name' => 'Branché - FPTU Fashion & Model Club',
        'short_name' => 'Branché',
        'email' => 'Brancheclub@gmail.com',
        'fb' => 'https://www.facebook.com/branche.fptu.vietnam',
        'category_id' => 3,
        'motion' => 'sakura-drift',
        'theme' => 'sunset-dream',
        'bg_color' => '#250B25',
        'accent_color' => '#E879F9',
        'summary' => 'CLB Thời trang & Người mẫu Branché - Sàn diễn runway chuyên nghiệp, định hình phong cách cá nhân và gu thời trang dẫn đầu xu hướng.',
        'username' => 'clb_branche'
    ],
    [
        'name' => 'FPTU Art Club',
        'short_name' => 'Art Club',
        'email' => 'fptuartclub@gmail.com',
        'fb' => 'https://www.facebook.com/Artclub.fpt',
        'category_id' => 3,
        'motion' => 'golden-rain',
        'theme' => 'dimension-classic',
        'bg_color' => '#102242',
        'accent_color' => '#F6C453',
        'summary' => 'CLB Mỹ thuật FPTU - Vẽ tranh, đồ họa kỹ thuật số, triển lãm tranh nghệ thuật và kết nối những tâm hồn hội họa mộng mơ.',
        'username' => 'clb_art'
    ],
    [
        'name' => 'FPTU BoardGame Club',
        'short_name' => 'FBC - Boardgame',
        'email' => 'fptuboardgameclub@gmail.com',
        'fb' => 'https://www.facebook.com/fuboardgameclub',
        'category_id' => 2,
        'motion' => 'nebula-wave',
        'theme' => 'dimension-classic',
        'bg_color' => '#16193E',
        'accent_color' => '#FBBF24',
        'summary' => 'CLB Board Game FPTU - Khấu trí Ma Sói, Catan, Avalon và hàng trăm tựa game chiến thuật kết nối bạn bè sau giờ học.',
        'username' => 'clb_boardgame'
    ],
    [
        'name' => 'FPTU - Photography',
        'short_name' => 'FU Photography',
        'email' => 'fuphotography.club@gmail.com',
        'fb' => 'https://www.facebook.com/FUphotography.club',
        'category_id' => 3,
        'motion' => 'cosmic-shimmer',
        'theme' => 'dimension-classic',
        'bg_color' => '#0C1C45',
        'accent_color' => '#38BDF8',
        'summary' => 'CLB Nhiếp ảnh FPTU - Bắt trọn những khoảnh khắc thanh xuân qua ống kính máy ảnh, kỹ thuật hậu kỳ và sáng tạo thị giác.',
        'username' => 'clb_photography'
    ],
    [
        'name' => 'FPTU Dango Club',
        'short_name' => 'Dango Club',
        'email' => 'dangofpt.clb@gmail.com',
        'fb' => 'https://www.facebook.com/FPTUDANGO',
        'category_id' => 3,
        'motion' => 'sakura-drift',
        'theme' => 'sunset-dream',
        'bg_color' => '#28132D',
        'accent_color' => '#F472B6',
        'summary' => 'CLB Dango FPTU - Giao lưu văn hóa đại chúng, dance cover, cosplay và không gian ấm áp gắn kết cộng đồng sinh viên.',
        'username' => 'clb_dango'
    ],
    [
        'name' => 'HEBE CLUB',
        'short_name' => 'HEBE',
        'email' => 'Hebeclbfu@gmail.com',
        'fb' => 'https://www.facebook.com/HebeFPT',
        'category_id' => 5,
        'motion' => 'golden-rain',
        'theme' => 'emerald-nature',
        'bg_color' => '#0F2624',
        'accent_color' => '#34D399',
        'summary' => 'CLB Hebe FPTU - Hoạt động phụng sự cộng đồng, bảo vệ môi trường và những dự án lan tỏa lối sống xanh bền vững.',
        'username' => 'clb_hebe'
    ],
    [
        'name' => 'FPTU Go Club - CLB Cờ vây FPTU',
        'short_name' => 'FPT Go Club',
        'email' => 'Fptgoclub@gmail.com',
        'fb' => 'https://www.facebook.com/fptgoclub',
        'category_id' => 2,
        'motion' => 'time-warp',
        'theme' => 'dimension-classic',
        'bg_color' => '#0F1C3F',
        'accent_color' => '#F6C453',
        'summary' => 'CLB Cờ vây FPTU - Đỉnh cao tư duy chiến lược phương Đông trên bàn cờ 19x19, rèn luyện sự tĩnh tâm và nhãn quan sâu rộng.',
        'username' => 'clb_goclub'
    ],
    [
        'name' => 'Câu lạc bộ Võ thuật Điện ảnh FPTU',
        'short_name' => 'FMVC - Võ Thuật Điện Ảnh',
        'email' => 'fmvc.vothuatdienanh@gmail.com',
        'fb' => 'https://www.facebook.com/clbvothuatdienanhfptu',
        'category_id' => 4,
        'motion' => 'fire-burst',
        'theme' => 'crimson-flame',
        'bg_color' => '#280A18',
        'accent_color' => '#EF4444',
        'summary' => 'CLB Võ thuật Điện ảnh FPTU - Kỹ thuật cascadeur, dàn dựng phân cảnh hành động kịch tính và kỹ năng võ thuật trước máy quay.',
        'username' => 'clb_vothuatdienanh'
    ],
    [
        'name' => 'FPTU - Hibiki Yosakoi',
        'short_name' => 'Hibiki Yosakoi',
        'email' => 'fptjunioryosakoi151@gmail.com',
        'fb' => 'https://www.facebook.com/HibikiYosakoi',
        'category_id' => 3,
        'motion' => 'sakura-drift',
        'theme' => 'dimension-classic',
        'bg_color' => '#1F1138',
        'accent_color' => '#F6C453',
        'summary' => 'Đội múa Yosakoi Hibiki FPTU - Điệu múa tràn đầy nụ cười, tiếng gõ Naruko rộn rã và tinh thần đồng đội rực lửa văn hóa Nhật Bản.',
        'username' => 'clb_yosakoi'
    ],
    [
        'name' => 'Hiphop FPT University',
        'short_name' => 'HFU - Hiphop',
        'email' => 'Hiphopclubfptuniversity@gmail.com',
        'fb' => 'https://www.facebook.com/hiphopfptuniversity',
        'category_id' => 3,
        'motion' => 'fire-burst',
        'theme' => 'cyber-neon',
        'bg_color' => '#180B2B',
        'accent_color' => '#A855F7',
        'summary' => 'CLB Hiphop Đại học FPT - Không gian underground bùng nổ, b-boy, popping, waacking và những màn battle đường phố rực cháy.',
        'username' => 'clb_hiphop'
    ],
    [
        'name' => 'FPTU Chess Club',
        'short_name' => 'FPT Chess',
        'email' => 'Chessclub.fpt@gmail.com',
        'fb' => 'https://www.facebook.com/chessclub.fptu',
        'category_id' => 2,
        'motion' => 'time-warp',
        'theme' => 'dimension-classic',
        'bg_color' => '#0B1C47',
        'accent_color' => '#F6C453',
        'summary' => 'CLB Cờ Vua FPTU - Đấu trường trí tuệ đối kháng, rèn luyện chiến thuật khai cuộc, tàn cuộc và thi đấu các giải cờ sinh viên.',
        'username' => 'clb_chess'
    ],
    [
        'name' => 'FPTU Cóc Gia Đường Club',
        'short_name' => 'Cóc Gia Đường',
        'email' => 'fu.cocgiaduong.club@gmail.com',
        'fb' => 'https://www.facebook.com/fptucocgiaduongclub',
        'category_id' => 2,
        'motion' => 'golden-rain',
        'theme' => 'dimension-classic',
        'bg_color' => '#16224A',
        'accent_color' => '#F59E0B',
        'summary' => 'CLB Cóc Gia Đường FPTU - Nơi gìn giữ và phát huy các nét đẹp văn hóa truyền thống, trà đạo, cờ thế và tâm sự tri kỷ học đường.',
        'username' => 'clb_cocgiaduong'
    ],
    [
        'name' => 'FPTU Psychology Club',
        'short_name' => 'PsyClub - Tâm Lý Học',
        'email' => 'fptupsyclub@gmail.com',
        'fb' => 'https://www.facebook.com/FPT.Psy',
        'category_id' => 2,
        'motion' => 'frost-vortex',
        'theme' => 'midnight-ocean',
        'bg_color' => '#0A1C3E',
        'accent_color' => '#38BDF8',
        'summary' => 'CLB Tâm lý học FPTU - Lắng nghe, thấu cảm, tìm hiểu hành vi con người và tổ chức các workshop chăm sóc sức khỏe tinh thần sinh viên.',
        'username' => 'clb_psychology'
    ],
    [
        'name' => 'FPTU BOOK CLUB',
        'short_name' => 'FBC - Sách',
        'email' => 'fptu.bookclub@gmail.com',
        'fb' => 'https://www.facebook.com/fptu.bookclub',
        'category_id' => 2,
        'motion' => 'golden-rain',
        'theme' => 'dimension-classic',
        'bg_color' => '#151F42',
        'accent_color' => '#FDE68A',
        'summary' => 'CLB Sách FPTU - Nơi hội tụ những người yêu văn học, chia sẻ những cuốn sách làm thay đổi cuộc đời và văn hóa đọc đại học.',
        'username' => 'clb_book'
    ],
    [
        'name' => 'CLB Nhạc Cụ Truyền Thống FTIC',
        'short_name' => 'FTIC',
        'email' => 'clbnhaccutruyenthongfu@gmail.com',
        'fb' => 'https://www.facebook.com/FTIC.FUHL',
        'category_id' => 3,
        'motion' => 'golden-rain',
        'theme' => 'dimension-classic',
        'bg_color' => '#1A1845',
        'accent_color' => '#F6C453',
        'summary' => 'CLB Nhạc cụ truyền thống FTIC - Tiếng đàn tranh, sáo trúc, đàn bầu ngân vang tự hào di sản âm nhạc dân tộc tại Đại học FPT.',
        'username' => 'clb_ftic'
    ],
    [
        'name' => 'FPTU MonStage Club - CLB MC & Thuyết trình',
        'short_name' => 'MonStage MC',
        'email' => 'monstageclub.fpthn@gmail.com',
        'fb' => 'https://www.facebook.com/FPTU.MonStageClub',
        'category_id' => 2,
        'motion' => 'golden-rain',
        'theme' => 'dimension-classic',
        'bg_color' => '#112250',
        'accent_color' => '#F6C453',
        'summary' => 'CLB MC & Thuyết trình MonStage - Làm chủ sân khấu, tự tin trước đám đông và đào tạo các thế hệ dẫn chương trình tài năng.',
        'username' => 'clb_monstage'
    ],
    [
        'name' => 'FPTU - Ethical Hackers Club',
        'short_name' => 'EHC - An Toàn Thông Tin',
        'email' => 'ehc.fpt@gmail.com',
        'fb' => 'https://www.facebook.com/ehc.fptu',
        'category_id' => 1, // Công nghệ & Kỹ thuật
        'motion' => 'cyber-matrix',
        'theme' => 'cyber-neon',
        'bg_color' => '#061935',
        'accent_color' => '#00F0FF',
        'summary' => 'CLB An toàn thông tin EHC - Đấu trường CTF, bảo mật mạng, nghiên cứu lỗ hổng bảo mật và phòng thủ an ninh mạng hàng đầu.',
        'username' => 'clb_ehc'
    ],
    [
        'name' => 'FPTU Data Science Club',
        'short_name' => 'DSC - Khoa Học Dữ Liệu',
        'email' => 'dsclub.fu@gmail.com',
        'fb' => 'https://www.facebook.com/dsclub.fu',
        'category_id' => 1,
        'motion' => 'cyber-matrix',
        'theme' => 'cyber-neon',
        'bg_color' => '#0A1E4A',
        'accent_color' => '#38BDF8',
        'summary' => 'CLB Khoa học Dữ liệu FPTU - Khai phá Big Data, Machine Learning, phân tích kinh doanh và trực quan hóa dữ liệu thực tế.',
        'username' => 'clb_datascience'
    ],
    [
        'name' => 'FPTU Business Club',
        'short_name' => 'FBC - Kinh Doanh',
        'email' => 'fptubusinessclub1@gmail.com',
        'fb' => 'https://www.facebook.com/fptubusinessclub',
        'category_id' => 2,
        'motion' => 'golden-rain',
        'theme' => 'dimension-classic',
        'bg_color' => '#0D214F',
        'accent_color' => '#F6C453',
        'summary' => 'CLB Kinh doanh FPTU - Tư duy khởi nghiệp, kỹ năng đàm phán thương trường và giải quyết các bài toán tình huống doanh nghiệp.',
        'username' => 'clb_business'
    ],
    [
        'name' => 'FPTU Artificial Intelligence Club',
        'short_name' => 'AI Club FPTU',
        'email' => 'fptuaiclub@gmail.com',
        'fb' => 'https://www.facebook.com/aiclub.fptu',
        'category_id' => 1,
        'motion' => 'cyber-matrix',
        'theme' => 'cyber-neon',
        'bg_color' => '#06173B',
        'accent_color' => '#00F0FF',
        'summary' => 'CLB Trí tuệ nhân tạo AI FPTU - Nghiên cứu Deep Learning, Xử lý ảnh Computer Vision, Xử lý ngôn ngữ tự nhiên NLP và ứng dụng AI.',
        'username' => 'clb_ai'
    ],
    [
        'name' => 'CLB Tiếng Trung - Đại Học FPT',
        'short_name' => 'FCC - Tiếng Trung',
        'email' => 'tiengtrungfpt@gmail.com',
        'fb' => 'https://www.facebook.com/tiengtrungFPT',
        'category_id' => 2,
        'motion' => 'sakura-drift',
        'theme' => 'dimension-classic',
        'bg_color' => '#210C18',
        'accent_color' => '#EF4444',
        'summary' => 'CLB Tiếng Trung FPTU - Giao lưu Hán ngữ, văn hóa trà đạo, thư pháp và hỗ trợ ôn thi chứng chỉ HSK cho sinh viên.',
        'username' => 'clb_tiengtrung'
    ],
    [
        'name' => 'No Shy Club',
        'short_name' => 'NSC - No Shy',
        'email' => 'noshy.fpt@gmail.com',
        'fb' => 'https://www.facebook.com/noshyclub',
        'category_id' => 2,
        'motion' => 'golden-rain',
        'theme' => 'sunset-dream',
        'bg_color' => '#1F123C',
        'accent_color' => '#F472B6',
        'summary' => 'No Shy Club - Đập tan sự ngại ngùng rụt rè, xây dựng sự tự tin, phát triển kỹ năng mềm và kết nối không rào cản.',
        'username' => 'clb_noshy'
    ],
    [
        'name' => 'Japanese Software Engineers',
        'short_name' => 'JS Club',
        'email' => 'jsclub.fpt@gmail.com',
        'fb' => 'https://www.facebook.com/fu.jsclub',
        'category_id' => 1,
        'motion' => 'cyber-matrix',
        'theme' => 'cyber-neon',
        'bg_color' => '#0A1E4A',
        'accent_color' => '#38BDF8',
        'summary' => 'CLB Kỹ sư phần mềm Nhật Bản JS Club - Nơi đào tạo kỹ sư IT làm việc thị trường Nhật Bản, IT Nihongo và công nghệ hiện đại.',
        'username' => 'clb_jsclub'
    ],
    [
        'name' => 'FPTU English Club',
        'short_name' => 'FEC - Tiếng Anh',
        'email' => 'englishclub.fu@gmail.com',
        'fb' => 'https://www.facebook.com/englishclub.fu',
        'category_id' => 2,
        'motion' => 'nebula-wave',
        'theme' => 'midnight-ocean',
        'bg_color' => '#0C2050',
        'accent_color' => '#60A5FA',
        'summary' => 'CLB Tiếng Anh FEC - Môi trường 100% tiếng Anh, thảo luận chủ đề quốc tế, tranh biện và phản xạ giao tiếp tự nhiên.',
        'username' => 'clb_fec'
    ],
    [
        'name' => 'FPTU Debate Club',
        'short_name' => 'FUDC - Tranh Biện',
        'email' => 'fu.debate@gmail.com',
        'fb' => 'https://www.facebook.com/FUDebateClub',
        'category_id' => 2,
        'motion' => 'time-warp',
        'theme' => 'dimension-classic',
        'bg_color' => '#0F1D44',
        'accent_color' => '#F6C453',
        'summary' => 'CLB Tranh biện FPTU - Rèn giũa tư duy phản biện đa chiều theo luật tranh biện quốc tế British Parliamentary (BP) và World Schools.',
        'username' => 'clb_fudebate'
    ],
    [
        'name' => 'HLRC - FPTU Rock Club',
        'short_name' => 'HLRC - Rock',
        'email' => 'fptholarcstagedive@gmail.com',
        'fb' => 'https://www.facebook.com/holarcstagedive',
        'category_id' => 3,
        'motion' => 'fire-burst',
        'theme' => 'crimson-flame',
        'bg_color' => '#210915',
        'accent_color' => '#EF4444',
        'summary' => 'CLB Rock FPTU Hòa Lạc - Những cú riff guitar điện cuồng nhiệt, tiếng trống dồn dập và năng lượng rock bất diệt của tuổi trẻ.',
        'username' => 'clb_rock'
    ],
    [
        'name' => 'FPTU Doodle Bloom',
        'short_name' => 'Doodle Bloom',
        'email' => 'doodlebloom.fptu@gmail.com',
        'fb' => 'https://www.facebook.com/profile.php?id=61594231682120',
        'category_id' => 3,
        'motion' => 'sakura-drift',
        'theme' => 'sunset-dream',
        'bg_color' => '#220E32',
        'accent_color' => '#E879F9',
        'summary' => 'CLB Doodle Bloom - Nét vẽ doodle mộc mạc, sáng tạo nghệ thuật giải tỏa căng thẳng và lan tỏa năng lượng tích cực.',
        'username' => 'clb_doodlebloom'
    ],
    [
        'name' => 'FPTU Logistics Club - Câu lạc bộ Logistics Trường Đại học FPT Hà Nội',
        'short_name' => 'FLC - Logistics',
        'email' => 'logisticsclub.fptu@gmail.com',
        'fb' => 'https://www.facebook.com/fptu.logisticsclub',
        'category_id' => 2,
        'motion' => 'nebula-wave',
        'theme' => 'dimension-classic',
        'bg_color' => '#0B2252',
        'accent_color' => '#38BDF8',
        'summary' => 'CLB Logistics FPTU - Kiến thức chuỗi cung ứng, vận tải quốc tế, tham quan thực tế cảng biển và kết nối mạng lưới nghề nghiệp.',
        'username' => 'clb_logistics'
    ]
];

echo "Bắt đầu cập nhật 48 Câu Lạc Bộ FPTU...\n";

// Chuẩn bị statement
$check_stmt = $db->prepare("SELECT id, slug FROM clubs WHERE name = ? OR slug = ?");
$update_stmt = $db->prepare("
    UPDATE clubs SET 
        name = ?, short_name = ?, category_id = ?, summary = ?, 
        custom_motion_effect = ?, custom_theme = ?, custom_bg_color = ?, custom_accent_color = ?,
        recruitment_status = 'open', recruitment_deadline = DATE_ADD(NOW(), INTERVAL 14 DAY),
        is_active = 1, updated_at = NOW()
    WHERE id = ?
");

$insert_stmt = $db->prepare("
    INSERT INTO clubs (
        category_id, name, short_name, slug, summary, description,
        recruitment_status, recruitment_deadline, custom_motion_effect, custom_theme, custom_bg_color, custom_accent_color, is_active, is_featured, created_at, updated_at
    ) VALUES (
        ?, ?, ?, ?, ?, ?, 'open', DATE_ADD(NOW(), INTERVAL 14 DAY), ?, ?, ?, ?, 1, 0, NOW(), NOW()
    )
");

$acc_check = $db->prepare("SELECT id FROM club_accounts WHERE club_id = ? OR username = ?");
$acc_ins = $db->prepare("
    INSERT INTO club_accounts (club_id, username, password_hash, email, contact_person, is_active, created_at)
    VALUES (?, ?, ?, ?, ?, 1, NOW())
    ON DUPLICATE KEY UPDATE email = VALUES(email), club_id = VALUES(club_id)
");

$soc_del = $db->prepare("DELETE FROM club_socials WHERE club_id = ?");
$soc_ins = $db->prepare("INSERT INTO club_socials (club_id, platform, url) VALUES (?, ?, ?)");

$password_hash = password_hash('Club@2026', PASSWORD_DEFAULT);

$count = 0;
foreach ($clubs_raw as $c) {
    $slug = slugify($c['name']);
    
    // Tìm CLB cũ theo slug hoặc tên
    $check_stmt->execute([$c['name'], $slug]);
    $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $club_id = $existing['id'];
        $update_stmt->execute([
            $c['name'], $c['short_name'], $c['category_id'], $c['summary'],
            $c['motion'], $c['theme'], $c['bg_color'], $c['accent_color'],
            $club_id
        ]);
        echo " -> Cập nhật: {$c['name']} (ID: $club_id)\n";
    } else {
        $desc = "Chào mừng bạn đến với {$c['name']} tại Đại học FPT. Chúng tôi là nơi kết nối những sinh viên có chung niềm đam mê, cùng nhau học hỏi, giao lưu và phát triển bản thân qua các sự kiện, workshop và giải đấu thường niên.";
        $insert_stmt->execute([
            $c['category_id'], $c['name'], $c['short_name'], $slug, $c['summary'], $desc,
            $c['motion'], $c['theme'], $c['bg_color'], $c['accent_color']
        ]);
        $club_id = $db->lastInsertId();
        echo " -> Thêm mới: {$c['name']} (ID: $club_id, Slug: $slug)\n";
    }

    // Cập nhật mạng xã hội FB và Email
    $soc_del->execute([$club_id]);
    if (!empty($c['fb'])) {
        $soc_ins->execute([$club_id, 'facebook', $c['fb']]);
    }
    if (!empty($c['email'])) {
        $soc_ins->execute([$club_id, 'email', 'mailto:' . $c['email']]);
    }

    // Cập nhật hoặc tạo tài khoản Portal cho CLB
    $acc_ins->execute([
        $club_id, $c['username'], $password_hash, $c['email'], $c['name'] . ' Admin'
    ]);

    $count++;
}

echo "Hoàn thành! Đã xử lý $count Câu Lạc Bộ FPTU.\n";
