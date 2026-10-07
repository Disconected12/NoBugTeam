# ClubHub - Cổng Khám Phá & Quảng Bá Câu Lạc Bộ Đại Học

Hệ thống website thực tế dành cho sinh viên khám phá, tìm hiểu và kết nối với các Câu lạc bộ, Đội, Nhóm trong trường đại học; đồng thời cung cấp nền tảng số hỗ trợ các CLB quảng bá tuyển thành viên, giới thiệu sự kiện và tiếp nhận đăng ký vị trí poster nổi bật.

---

## 1. Công nghệ & Kiến trúc
* **Backend:** PHP 8.2+ thuần, sử dụng **PDO Prepared Statements** 100% để thao tác cơ sở dữ liệu MySQL/MariaDB.
* **Cơ sở dữ liệu:** MySQL (InnoDB, khóa ngoại Foreign Keys, Index tối ưu, hỗ trợ bảng mã tiếng Việt `utf8mb4_unicode_ci`).
* **Frontend:** HTML5, CSS3 hiện đại, Vanilla JavaScript (Tương thích Bootstrap, màu chủ đạo Xanh đậm `#0F2854`, Trắng `#FFFFFF` và điểm nhấn Cam năng động `#FF6B00`).
* **Bảo mật:**
  * Mã hóa mật khẩu chuẩn `password_hash()` / `password_verify()`.
  * Cơ chế chống tấn công CSRF bằng Token ngẫu nhiên cho toàn bộ biểu mẫu thay đổi dữ liệu.
  * Lọc và escape đầu ra chống XSS (`htmlspecialchars`).
  * Kiểm soát tải file upload an toàn (kiểm tra MIME thực tế, chặn hoàn toàn SVG/PHP/file thực thi, đổi tên file ngẫu nhiên).
  * Chống tấn công dò quét Brute-force đăng nhập theo địa chỉ IP và khóa an toàn.
  * Tự động khóa trang khởi tạo Admin ban đầu khi đã có tài khoản quản trị hoạt động.

---

## 2. Cấu trúc mã nguồn

```
d:/cbl/
├── .htaccess                      # Chặn duyệt thư mục và bảo vệ file nhạy cảm
├── index.php                      # Trang chủ (Banner, Quiz CTA, CLB nổi bật, Đang tuyển, Sự kiện, Lĩnh vực)
├── clubs.php                      # Danh sách CLB (Tìm kiếm, lọc lĩnh vực & tuyển quân, phân trang)
├── club-detail.php                # Chi tiết CLB (Mạng xã hội, thư viện ảnh lightbox, lịch sinh hoạt, báo sai)
├── quiz.php                       # Giao diện làm Quiz tương tác nhiều bước (Multi-step wizard)
├── quiz-result.php                # Kết quả đề xuất CLB phù hợp (Top 3, lý do, điểm lưu ý)
├── events.php                     # Danh sách sự kiện (Sắp diễn ra & Đã kết thúc)
├── event-detail.php               # Chi tiết sự kiện & Đăng ký tham gia
├── ads-booking.php                # Biểu mẫu đăng ký thuê vị trí quảng bá poster dành cho CLB
├── database.sql                   # File cấu trúc CSDL và dữ liệu mẫu minh họa đầy đủ
│
├── admin/                         # Phân hệ Quản trị viên (Kiểm tra quyền đăng nhập chặt chẽ)
│   ├── index.php                  # Dashboard thống kê thực tế số liệu hệ thống
│   ├── login.php                  # Đăng nhập quản trị viên (Chống brute-force)
│   ├── logout.php                 # Đăng xuất an toàn & hủy session
│   ├── setup.php                  # Khởi tạo Admin đầu tiên (Tự khóa sau khi tạo)
│   ├── clubs.php                  # Quản lý danh sách CLB (Thêm, sửa, ẩn/hiện, nổi bật, xóa)
│   ├── club-form.php              # Form chi tiết CLB (Thông tin, mạng xã hội, thuộc tính quiz, ảnh gallery)
│   ├── categories.php             # Quản lý lĩnh vực CLB (Ràng buộc không xóa danh mục đang có CLB)
│   ├── events.php                 # Quản lý sự kiện (Liên kết CLB, kiểm tra thời gian hợp lệ)
│   ├── ads.php                    # Quản lý quảng cáo (Duyệt, báo giá, thanh toán, kiểm tra sức chứa)
│   ├── quiz.php                   # Quản lý bộ câu hỏi quiz, đáp án và trọng số
│   ├── feedback.php               # Tiếp nhận & xử lý phản ánh thông tin sai / link hỏng
│   ├── settings.php               # Cấu hình thông tin website (Tên, logo, hotline, chính sách)
│   └── includes/                  # Giao diện Header/Footer của trang quản trị
│
├── api/                           # Endpoint xử lý dữ liệu Backend
│   ├── ad-request.php             # Tiếp nhận đăng ký quảng cáo & sinh mã tra cứu
│   ├── quiz-evaluate.php          # Thuật toán tính điểm ma trận & đề xuất CLB
│   └── report-submit.php          # Xử lý gửi phản ánh sai sót
│
├── config/                        # Cấu hình hệ thống
│   ├── config.php                 # Cấu hình môi trường & tự nhận diện BASE_URL linh hoạt
│   ├── constants.php              # Định nghĩa hằng số trạng thái tuyển, vị trí quảng cáo
│   ├── database.php               # Kết nối CSDL PDO bảo mật
│   └── config.local.php.example   # File mẫu ghi đè cấu hình khi đưa lên môi trường khác
│
├── includes/                      # Thư viện hàm & giao diện dùng chung
│   ├── header.php                 # Thanh điều hướng, menu di động & tìm kiếm nhanh
│   ├── footer.php                 # Chân trang, hotline, modal báo sai sót, modal chính sách
│   ├── functions.php              # Bộ hàm xử lý an toàn (safe_url, slugify, upload, format)
│   ├── auth.php                   # Xác thực & phân quyền phiên làm việc quản trị
│   └── csrf.php                   # Sinh và xác minh CSRF Token
│
├── assets/                        # Tài nguyên tĩnh
│   ├── css/style.css              # Giao diện người dùng Responsive & thẩm mỹ
│   ├── css/admin.css              # Giao diện quản trị hiện đại
│   ├── js/main.js                 # Điều khiển slider poster, lightbox ảnh, copy link, modal
│   ├── js/quiz.js                 # Điều khiển quiz đa bước, giữ câu trả lời, thanh tiến độ
│   └── images/                    # Ảnh mặc định và biểu tượng
│
├── uploads/                       # Thư mục lưu ảnh upload (Đã cấu hình chặn thực thi PHP)
│   ├── clubs/                     # Logo, ảnh bìa và ảnh hoạt động CLB
│   ├── events/                    # Poster sự kiện
│   └── ads/                       # Poster quảng cáo trả phí
│
└── tests/                         # Bộ kiểm thử tự động (Unit & Integration Tests)
    ├── test_suite.php             # Test CSDL, Auth, Search, Quiz, Ads, Events
    └── test_http_features.php     # Test tích hợp qua giao thức HTTP
```

---

## 3. Hướng dẫn cài đặt trên máy tính (XAMPP / Laragon)

### Bước 1: Chuẩn bị mã nguồn
* Nếu đặt tại thư mục con: ví dụ `C:\xampp\htdocs\cbl` (hoặc `D:\cbl` liên kết qua Junction/VirtualHost).
* Hệ thống **tự động nhận diện đường dẫn `BASE_URL`**, hoạt động tốt cả ở `http://localhost/cbl/`, `http://localhost:8080/cbl/` hoặc thư mục gốc `http://localhost/` mà không cần sửa mã nguồn.

### Bước 2: Tạo Cơ sở dữ liệu MySQL
1. Mở **phpMyAdmin** (hoặc mở Command Line / MySQL Workbench).
2. Tạo database mới tên: `clubhub_db` với bảng mã `utf8mb4_unicode_ci`.
3. Nhập (Import) toàn bộ nội dung file `database.sql` vào database `clubhub_db`.
   *(Lưu ý: File `database.sql` sử dụng câu lệnh an toàn `CREATE TABLE IF NOT EXISTS`, không tự động xóa hay DROP dữ liệu có sẵn).*

### Bước 3: Cấu hình thông tin kết nối (nếu cần)
* Mặc định trong `config/config.php` đã cấu hình thông số chuẩn XAMPP:
  * Host: `localhost` | Port: `3306` | User: `root` | Pass: *(trống)* | DB: `clubhub_db`.
* Nếu MySQL của bạn có mật khẩu riêng, chỉ cần tạo file `config/config.local.php` (dựa trên `config/config.local.php.example`) để ghi đè thông số mà không cần sửa file gốc.

### Bước 4: Khởi tạo tài khoản Quản trị viên đầu tiên
1. Truy cập đường dẫn: `http://localhost/cbl/admin/setup.php` (hoặc theo cổng Apache của bạn).
2. Điền họ tên, email, tên đăng nhập và mật khẩu (tối thiểu 8 ký tự).
3. Bấm **"Tạo tài khoản & Đăng nhập ngay"**.
4. Sau khi tài khoản được tạo thành công, trang `setup.php` sẽ **tự động khóa vĩnh viễn** để ngăn chặn kẻ xấu tạo thêm tài khoản trái phép.

---

## 4. Hướng dẫn triển khai lên Hosting PHP/MySQL (cPanel / DirectAdmin / VPS)

1. **Phiên bản PHP:** Thiết lập phiên bản PHP trên hosting là **PHP 8.2 trở lên**, kích hoạt các extension: `pdo_mysql`, `fileinfo`, `mbstring`, `curl`, `json`.
2. **Cơ sở dữ liệu:**
   * Tạo MySQL Database và MySQL User trên cPanel.
   * Gán quyền `ALL PRIVILEGES` cho user vào database.
   * Dùng tính năng Import của phpMyAdmin trên hosting để nhập file `database.sql`.
3. **Tải mã nguồn:**
   * Nén toàn bộ thư mục dự án thành file `.zip` và tải lên hosting.
   * Giải nén vào thư mục `public_html` (hoặc thư mục con theo tên miền con của bạn).
4. **Cấu hình thông số DB:**
   * Tạo file `config/config.local.php` trong thư mục cài đặt với nội dung:
     ```php
     <?php
     define('DB_HOST', 'localhost');
     define('DB_PORT', '3306');
     define('DB_NAME', 'ten_database_cua_ban');
     define('DB_USER', 'ten_user_cua_ban');
     define('DB_PASS', 'mat_khau_cua_ban');
     define('APP_DEBUG', false); // Tắt chế độ debug trên môi trường production
     ```
5. **Bảo mật thư mục:**
   * File `.htaccess` tại thư mục gốc và thư mục `uploads/.htaccess` đã được thiết lập sẵn để chặn tải trực tiếp file cấu hình nhạy cảm (`.sql`, `.log`, `.env`) và chặn hoàn toàn việc thực thi file PHP trong thư mục ảnh tải lên.
   * Cấp quyền ghi (Write Permission - `chmod 755` hoặc `775`) cho thư mục `uploads/` và các thư mục con `uploads/clubs`, `uploads/events`, `uploads/ads`.

---

## 5. Hướng dẫn vận hành các phân hệ chức năng

### A. Quản lý Câu Lạc Bộ
* Truy cập **Quản lý CLB** (`/admin/clubs.php`).
* Bấm **"+ Thêm CLB Mới"** hoặc **"Sửa"**:
  * Điền tên, slug, lĩnh vực, giới thiệu ngắn, mô tả chi tiết, mục tiêu, đối tượng phù hợp.
  * Cập nhật đợt tuyển thành viên: Trạng thái (Đang mở, Sắp mở, Đã đóng), hạn chót nộp hồ sơ, quy trình tuyển và đường link Google Form nhận đơn.
  * Cung cấp các liên kết mạng xã hội chính thức (Fanpage, TikTok, Instagram, YouTube, Website, Email/Messenger).
  * **Thư viện ảnh hoạt động:** Cho phép tải lên nhiều hình ảnh hoạt động cùng lúc, gán chú thích và xem trước phóng to.
  * **Thuộc tính định hướng Quiz:** Chấm điểm theo thang điểm 1.0 đến 5.0 đối với các tiêu chí (công nghệ, nghệ thuật, thể thao, mục tiêu kỹ năng, thời gian sinh hoạt, mức cam kết...).
  * Chọn **"Chọn làm CLB Nổi Bật"** để hiển thị tại mục D trên trang chủ.

### B. Quản lý Bộ câu hỏi Quiz & Thuật toán đề xuất
* Truy cập **Quản lý Bộ Quiz** (`/admin/quiz.php`).
* Xem danh sách các câu hỏi, bấm sửa câu hỏi để:
  * Thay đổi nội dung, gợi ý, loại câu hỏi (chọn 1 hoặc chọn nhiều).
  * Quản lý danh sách đáp án: Thêm đáp án mới, gán đáp án liên kết với từ khóa thuộc tính và trọng số (1.0 đến 5.0).
  * Có thể xóa câu hỏi hoặc xóa từng đáp án nếu không còn phù hợp.
* **Nguyên tắc chấm điểm khách quan:**
  * Thuật toán chạy 100% tại backend thông qua ma trận nhân trọng số câu trả lời của người dùng với điểm thuộc tính CLB.
  * Nhân đôi trọng số sở thích lĩnh vực và mục tiêu chính.
  * So khớp có cấu trúc khung thời gian rảnh với lịch sinh hoạt; CLB chưa chốt lịch sẽ có cảnh báo *"Cần xác nhận lịch"* rõ ràng.
  * Tuyệt đối không ưu tiên hay cộng điểm cho các CLB đang thuê vị trí quảng cáo.

### C. Quản lý Quảng Cáo & Đặt lịch hiển thị Poster
* **Quy trình:**
  1. Đại diện CLB gửi form tại `/ads-booking.php` (tải poster, chọn vị trí, ngày bắt đầu/kết thúc, link đích).
  2. Hệ thống cấp mã tra cứu dạng `AD-2026-XXXXX` và lưu vào hàng chờ.
  3. Quản trị viên vào `/admin/ads.php` xem xét nội dung:
     * Nhập số tiền báo giá.
     * Hệ thống **tự động kiểm tra sức chứa** của vị trí thuê (đối chiếu số suất luân phiên tối đa để cảnh báo lịch trùng vượt sức chứa).
     * Cập nhật trạng thái thanh toán thủ công (*Chưa thanh toán / Đã thanh toán / Hoàn tiền*).
     * Duyệt yêu cầu (*approved*) và bật kích hoạt (*is_enabled = 1*).
  4. Poster sẽ **tự động hiển thị đúng ngày bắt đầu và tự động ngừng hiển thị khi hết hạn**, hoàn toàn độc lập và không phụ thuộc vào việc hosting có chạy Cronjob hay không.

### D. Quản lý Phản ánh thông tin sai & Lĩnh vực
* Tiếp nhận thông báo sai lệch thông tin hoặc liên kết hỏng từ sinh viên tại `/admin/feedback.php`, sau khi xác minh có thể đánh dấu đã xử lý.
* Quản lý danh mục tại `/admin/categories.php`: Hệ thống kiểm tra và **ngăn chặn xóa danh mục nếu đang có CLB trực thuộc**, yêu cầu chuyển CLB sang lĩnh vực khác trước khi xóa.

---

## 6. Kết quả kiểm thử & Giới hạn kỹ thuật

Hệ thống đã trải qua kiểm thử thực tế 100% thành công với kịch bản chi tiết (`tests/test_suite.php`):
* [x] Kết nối PDO an toàn, đầy đủ 16 bảng dữ liệu và ràng buộc khóa ngoại.
* [x] Tạo tài khoản admin đầu tiên thành công và tự động khóa trang cài đặt.
* [x] Đăng nhập quản trị viên, kiểm tra mật khẩu `password_verify`, đổi session ID, kiểm soát tỷ lệ đăng nhập sai chống brute-force.
* [x] Tìm kiếm theo từ khóa tên CLB, lọc lĩnh vực và trạng thái tuyển thành viên.
* [x] CLB hết hạn nộp đơn tự động chuyển trạng thái "Đã hết hạn tuyển".
* [x] Kiểm tra hiển thị chi tiết CLB, thư viện ảnh (lightbox phóng to), các nút mạng xã hội (tự ẩn kênh chưa có, mở link an toàn).
* [x] Quiz tương tác mượt mà: di chuyển giữa các câu giữ nguyên đáp án, kiểm tra câu bắt buộc, chấm điểm chính xác tại backend, trả về tối đa 3 CLB phù hợp nhất kèm lý do cụ thể.
* [x] Quảng cáo trả phí có nhãn "Được tài trợ", độc lập hoàn toàn với điểm quiz, tự động ẩn khi hết hạn hợp đồng.
* [x] Kiểm tra biểu mẫu booking quảng cáo: chặn link độc hại, kiểm tra định dạng ảnh, giới hạn dung lượng file, cảnh báo vượt sức chứa suất chiếu.
* [x] Giao diện hoàn toàn bằng tiếng Việt, hiển thị tối ưu trên máy tính, máy tính bảng và điện thoại di động (Responsive).

---
*ClubHub - Đồng hành cùng phong trào sinh viên năng động và sáng tạo!*