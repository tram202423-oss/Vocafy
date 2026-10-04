# Setup Vocafy sau khi clone hoặc pull repository

Tài liệu này dành cho môi trường phát triển Docker trên macOS; xem thêm [chi tiết Compose Mac](docker-macos.md). Chạy các lệnh từ **thư mục gốc repository** (thư mục chứa `src/` và `docker-compose.mac.yml`). Các lệnh dùng `-f docker-compose.mac.yml` để luôn thao tác đúng project Compose. Ứng dụng chạy trên Laravel 11, PHP 8.3 FPM, MySQL 8, Nginx, Vite/Alpine và Filament.

## 1. Những thứ Git không mang theo

| Thành phần | Nơi lưu | Cần làm sau khi clone |
| --- | --- | --- |
| Biến môi trường và `APP_KEY` | `src/.env` | Tạo từ `src/.env.example`; tạo key một lần trên máy mới |
| Thư viện PHP | `vendor` Docker volume | Chạy `composer install` trong container |
| Thư viện frontend | `node_modules` Docker volume | Chạy `npm ci` trong container |
| CSS/JS đã build | `src/public/build` | Chạy `npm run build` |
| Liên kết file công khai | `src/public/storage` | Chạy `php artisan storage:link` **trong container** |
| MySQL và dữ liệu đã nhập | Docker volume `vocafy_dbdata` | Máy mới cần `migrate` và seed có chọn lọc; máy cũ giữ volume |
| Audio, ảnh, bản thu và backup | `src/storage/app` | Giữ hoặc sao lưu riêng nếu chuyển máy; migration/seeder không khôi phục media |

`src/public/storage` là symlink tới đường dẫn `/var/www/storage/app/public` trong container. Tạo nó bằng Artisan trong container để Nginx đọc đúng đường dẫn. `docker/php/Dockerfile` không đóng gói mã nguồn; các service mount `src/` lúc chạy.

## 2. Lần đầu clone trên Mac

### Bước 1 — Chuẩn bị Docker và `.env`

Cần Git, Docker Desktop và Docker Compose. Mở Docker Desktop trước khi chạy Compose. Tạo file môi trường:

```bash
cp src/.env.example src/.env
```

Trong `src/.env`, đặt ít nhất:

```dotenv
APP_NAME=Vocafy
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8080

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=vocafy
DB_USERNAME=vocafy
DB_PASSWORD=secret

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
MAIL_MAILER=log
```

Compose Mac hiện truyền `DB_*` trực tiếp vào container với các giá trị trên. Nếu đổi tài khoản/mật khẩu MySQL, cập nhật Compose và `.env` cho khớp. Từ máy Mac, MySQL ở `127.0.0.1:3307`; **trong container** phải dùng `db:3306`. Giữ nguyên `APP_KEY` khi chuyển hoặc phục hồi một database cũ; thay key sẽ làm mất khả năng đọc session và dữ liệu đã mã hóa bằng key cũ.

Các tích hợp tùy chọn: `GEMINI_API_KEY` cho tính năng AI và đánh giá Writing/Speaking; `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` cho đăng nhập Google; cấu hình `MAIL_*` nếu muốn gửi email thật. Mặc định `MAIL_MAILER=log` ghi email vào log, không gửi ra ngoài. Không đưa khóa API hoặc `.env` vào Git.

### Bước 2 — Khởi động nền tảng và cài dependency

```bash
docker compose -f docker-compose.mac.yml up -d --build db app web
docker compose -f docker-compose.mac.yml exec -T app composer install --no-interaction --prefer-dist
docker compose -f docker-compose.mac.yml exec -T app npm ci
docker compose -f docker-compose.mac.yml exec -T app npm run build
docker compose -f docker-compose.mac.yml exec -T app php artisan key:generate
```

`composer install` đọc `src/composer.lock`; `npm ci` đọc `src/package-lock.json`. File `src/public/build/manifest.json` phải xuất hiện sau `npm run build`. Trên Mac, `vendor` và `node_modules` là Docker named volumes: thư viện có sẵn ở host không thay thế hai bước cài đặt trên.

### Bước 3 — Tạo schema, link storage và dữ liệu nền

```bash
docker compose -f docker-compose.mac.yml exec -T app php artisan migrate --force
docker compose -f docker-compose.mac.yml exec -T app php artisan storage:link
docker compose -f docker-compose.mac.yml exec -T app php artisan db:seed --class=DatabaseSeeder --force
```

`DatabaseSeeder` chỉ tạo role, tài khoản admin mẫu và dữ liệu từ vựng cơ bản; **không nạp đề IELTS**. Seeder tạo tài khoản local `admin@vocafy.com` với mật khẩu mẫu `12345678`. Chỉ dùng tài khoản này để khởi tạo môi trường local và đổi mật khẩu trước khi chia sẻ môi trường.

Nếu cần hai bộ đề IELTS hiện có trong repository, chạy riêng:

```bash
docker compose -f docker-compose.mac.yml exec -T app php artisan db:seed --class=IeltsTeaTransportInnovationSeeder --force
docker compose -f docker-compose.mac.yml exec -T app php artisan db:seed --class=IeltsCallUnlimitedListeningSeeder --force
```

Bộ Reading có 3 passages/40 câu. Bộ Listening có 4 Parts, 9 nhóm/40 câu. **Seeder Listening không kèm file audio**: admin cần tải audio lên hoặc nhập liên kết hợp lệ trong phần Listening. Nếu chuyển dữ liệu từ máy cũ, cần chuyển cả database lẫn media trong `src/storage/app`; chạy seeder không phục hồi các tệp đã upload.

### Bước 4 — Chạy queue và scheduler

```bash
docker compose -f docker-compose.mac.yml up -d queue ielts_queue scheduler
docker compose -f docker-compose.mac.yml ps
```

`queue` xử lý job mặc định như email; `ielts_queue` xử lý đánh giá AI Writing/Speaking trên queue `ielts`; `scheduler` chạy lệnh hoàn tất các lượt IELTS hết giờ mỗi phút. Trang web có thể mở khi worker chưa chạy, nhưng các tác vụ này sẽ không hoàn tất.

Truy cập `http://localhost:8080`, đề IELTS tại `/ielts/tests`, admin tại `/admin`.

## 3. Sau mỗi lần `git pull` trên máy đã có dữ liệu

Giữ `src/.env`, `APP_KEY`, MySQL volume và `src/storage/app`. Chạy lần lượt:

```bash
git pull
docker compose -f docker-compose.mac.yml up -d --build db app web
docker compose -f docker-compose.mac.yml exec -T app composer install --no-interaction --prefer-dist
docker compose -f docker-compose.mac.yml exec -T app npm ci
docker compose -f docker-compose.mac.yml exec -T app npm run build
docker compose -f docker-compose.mac.yml exec -T app php artisan migrate --force
docker compose -f docker-compose.mac.yml exec -T app php artisan storage:link
docker compose -f docker-compose.mac.yml exec -T app php artisan optimize:clear
docker compose -f docker-compose.mac.yml up -d --build queue ielts_queue scheduler
docker compose -f docker-compose.mac.yml exec -T app php artisan queue:restart
```

Nếu `public/storage` đã có, Artisan chỉ báo link đã tồn tại. Có thể bỏ qua `composer install` khi `composer.lock` không đổi và bỏ qua `npm ci` khi `package-lock.json` không đổi; vẫn chạy `npm run build` nếu JS/CSS/Blade có thay đổi liên quan frontend. Sau đó tải lại trang trình duyệt; trên Mac có thể dùng `Cmd + Shift + R` nếu tab cũ vẫn hiển thị giao diện cũ. Worker queue là tiến trình sống lâu, nên cần khởi động lại sau khi pull mã PHP mới.

`migrate` chỉ cập nhật schema. Không tự chạy `db:seed` sau mọi lần pull: seeder là thao tác thay đổi nội dung dữ liệu. Chỉ chạy seeder IELTS cụ thể khi muốn tạo/cập nhật bộ đề tương ứng. **Không chạy** `ReplaceIeltsWithTeaTransportInnovationSeeder` như một bước setup thông thường: seeder này xóa dữ liệu đề và lượt thi IELTS trước khi nhập lại Reading. `migrate:fresh`, `db:wipe` và `docker compose down -v` cũng xóa dữ liệu, không thuộc quy trình cập nhật.

## 4. Các điểm dễ bị bỏ sót

| Hiện tượng | Kiểm tra và cách xử lý |
| --- | --- |
| Trang thi Listening chỉ thấy khung, câu hỏi trống | `src/public/build` là file sinh ra và bị Git bỏ qua. Chạy `npm run build`, tải lại trang. Bundle cũ thiếu `ieltsRecording` khiến Alpine không khởi tạo và phần `x-cloak` vẫn ẩn. |
| Đề không xuất hiện tại `/ielts/tests` | `migrate` không thêm câu hỏi. Kiểm tra đã chạy seeder IELTS hoặc tạo đề trong admin; đề cần được publish, section active và có nhóm/câu hỏi. |
| Listening có câu hỏi nhưng không nghe được | Seeder Listening không kèm audio. Kiểm tra `audio_url` trong admin; với file nội bộ cần `storage:link` và file thật trong `src/storage/app/public`. |
| Upload audio báo 413 hoặc vượt giới hạn | Form cho phép 50 MiB; Nginx nhận request 64 MiB ở `docker/nginx/default.conf`; PHP-FPM dùng `docker/php/uploads.ini` (`upload_max_filesize=50M`, `post_max_size=64M`). Sau khi sửa cấu hình này phải build lại image PHP và nạp lại Nginx. |
| Upload thành công nhưng `/storage/...` trả 404 | Chạy `php artisan storage:link` **trong app container** và kiểm tra media có trong `src/storage/app/public`. |
| Đăng nhập/CSRF hoặc link sai host | Kiểm tra `APP_URL=http://localhost:8080`, giữ `APP_KEY` ổn định, rồi chạy `php artisan optimize:clear`. |
| Writing/Speaking nộp xong nhưng AI mãi pending | Kiểm tra `ielts_queue`, `GEMINI_API_KEY`, log của worker và bảng `failed_jobs`. |
| Speaking không mở micro | Dùng `localhost` hoặc HTTPS, cho phép quyền micro trong trình duyệt. Bản thu được lưu riêng trên disk `local`, không phải đường dẫn public `/storage`. |
| Password reset không đến email | Mặc định `MAIL_MAILER=log`; cần cấu hình mailer thật và chạy `queue` để gửi email. |

## 5. Kiểm tra nhanh sau setup

```bash
docker compose -f docker-compose.mac.yml ps
docker compose -f docker-compose.mac.yml exec -T app php artisan migrate:status
docker compose -f docker-compose.mac.yml exec -T app ls -l public/storage public/build/manifest.json
docker compose -f docker-compose.mac.yml exec -T web nginx -t
docker compose -f docker-compose.mac.yml logs --tail=80 app web queue ielts_queue scheduler
```

Khi có lỗi Laravel, xem thêm `src/storage/logs/laravel.log`. Database và media là hai nơi lưu dữ liệu khác nhau; khi sao lưu hoặc chuyển máy cần giữ cả hai cùng `APP_KEY`.

## 6. Compose mặc định và môi trường khác

Hướng dẫn trên dùng `docker-compose.mac.yml`. File `docker-compose.yml` mặc định hiện yêu cầu biến shell `UID`/`GID` và các worker tham chiếu image `vocafy-app:latest`, trong khi service `app` không gắn tag đó. Trên Mac hãy dùng file Compose Mac một cách nhất quán; đừng trộn lệnh của hai file trên cùng volume/database nếu chưa kiểm tra cấu hình. Cổng web của Compose Mac là `127.0.0.1:8080`, MySQL là `127.0.0.1:3307`.

Tài liệu chi tiết về IELTS: [tổng quan module](ielts-simulator/README.md), [hướng dẫn admin](ielts-simulator/ADMIN-GUIDE.md), [trạng thái dạng câu hỏi](ielts-simulator/QUESTION-TYPES.md).

## 7. Bản đồ mã nguồn cần biết khi setup

| Khu vực trong `src/` | Vai trò khi vận hành |
| --- | --- |
| `routes/web.php`, `routes/auth.php` | Trang học từ vựng, Writing AI, đăng nhập và các endpoint bài thi IELTS |
| `app/Filament/` | Admin `/admin`, quản lý nội dung, đề IELTS, section và bài nộp |
| `app/Services/Ielts*`, `app/Http/Controllers/IeltsExamController.php` | Tạo đề, lưu bài, snapshot, upload audio, chấm điểm và hiển thị phòng thi |
| `app/Jobs/EvaluateIeltsSubmission.php`, `app/Console/Commands/` | AI chạy nền và hoàn tất lượt thi hết giờ; cần queue/scheduler |
| `database/migrations/`, `database/seeders/` | Schema và dữ liệu mẫu; hai bước phải chạy riêng khi cần |
| `resources/views/ielts/`, `resources/js/app.js`, `resources/js/ielts-recording.js` | Giao diện phòng thi và logic Alpine; thay đổi JS cần build Vite |
| `config/filesystems.php`, `config/livewire.php`, `config/queue.php`, `config/services.php` | Public media, giới hạn upload tạm, worker và tích hợp bên ngoài |

Các bản ghi Speaking nằm trên disk `local` riêng tư và được trả qua controller sau khi kiểm tra quyền. Audio Listening admin tải lên nằm trên disk `public` và cần `storage:link` để phát qua Nginx.
