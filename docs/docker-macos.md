# Chạy Vocafy bằng Docker Desktop trên macOS

Dùng [docker-compose.mac.yml](../docker-compose.mac.yml) từ thư mục gốc repository. File này giữ project name `vocafy` và volume `vocafy_dbdata`, nên dùng lại dữ liệu MySQL của project Compose hiện tại. **Đừng chạy `down -v`** nếu muốn giữ dữ liệu.

## Chuẩn bị

1. Mở Docker Desktop và chờ Docker Engine sẵn sàng.
2. Nếu chưa có `src/.env`, tạo từ `src/.env.example`. Đặt `APP_URL=http://localhost:8080`. Compose Mac cung cấp biến `DB_*` cho container và dùng MySQL tại service `db`.
3. Nếu đây là lần đầu chạy ứng dụng, tạo `APP_KEY` sau khi cài Composer ở bước dưới. Nếu đã có `src/.env`, giữ nguyên key cũ.

## Khởi động lần đầu

```bash
docker compose -f docker-compose.mac.yml up -d --build db app web
docker compose -f docker-compose.mac.yml exec app composer install
docker compose -f docker-compose.mac.yml exec app npm ci
docker compose -f docker-compose.mac.yml exec app npm run build
```

Nếu đây là môi trường mới chưa có `APP_KEY` và schema database:

```bash
docker compose -f docker-compose.mac.yml exec app php artisan key:generate
docker compose -f docker-compose.mac.yml exec app php artisan migrate
docker compose -f docker-compose.mac.yml exec app php artisan storage:link
```

Sau khi cài dependency và chuẩn bị database, khởi động worker và scheduler:

```bash
docker compose -f docker-compose.mac.yml up -d queue ielts_queue scheduler
docker compose -f docker-compose.mac.yml ps
```

Ứng dụng: `http://localhost:8080`. MySQL từ máy Mac: `127.0.0.1:3307`. Cổng `5173` dành cho Vite khi bật chế độ phát triển. Các cổng trong file này chỉ lắng nghe trên localhost.

## Phát triển giao diện

`app` chạy PHP-FPM; cổng 5173 chỉ hoạt động khi khởi chạy Vite:

```bash
docker compose -f docker-compose.mac.yml exec app npm run dev -- --host 0.0.0.0
```

Giữ terminal này mở khi dùng Vite. Nếu chỉ cần giao diện build sẵn, dùng `npm run build` như trên.

Sau khi `git pull` có thay đổi trong `src/resources/js` hoặc `src/resources/css`, chạy lại `docker compose -f docker-compose.mac.yml exec app npm run build` rồi tải lại trang thi. Bundle cũ có thể thiếu mã Alpine mới và làm các nhóm câu hỏi bị ẩn.

## Các điểm khác với Compose mặc định

- Không yêu cầu biến shell `UID`/`GID`, vì chúng thường chưa được khai báo khi mở terminal trên Mac.
- `app`, `queue`, `ielts_queue` và `scheduler` dùng cùng image `vocafy-app:mac`; worker không phụ thuộc vào một tag image khác chưa được build.
- `vendor` và `node_modules` nằm trong Docker named volumes. Source trong `src` vẫn được bind mount để sửa code trên Mac; Composer/NPM chạy trong container.
- Worker chỉ khởi động sau khi MySQL trả lời truy vấn healthcheck.
- `.dockerignore` bỏ source và thư mục Git khỏi build context. Dockerfile PHP hiện không copy source vào image; source được mount khi chạy.
- Nginx mount source và config ở chế độ chỉ đọc. PHP-FPM vẫn ghi được vào `src/storage` qua mount riêng của service `app`.
- Upload audio Listening tối đa 50 MiB. Nginx nhận request tới 64 MiB; PHP cho phép file 50 MiB và POST 64 MiB. Các giới hạn PHP nằm trong `docker/php/uploads.ini` và được chép vào image khi build.

Sau khi đổi giới hạn upload, build lại image PHP và tạo lại container để áp dụng cấu hình mới:

```bash
docker compose -f docker-compose.mac.yml up -d --build app queue ielts_queue scheduler
docker compose -f docker-compose.mac.yml exec web nginx -s reload
```

Nếu chuyển từ Compose mặc định sang file Mac trên cùng thư mục, giữ cùng project name sẽ dùng lại volume database. Có thể cần tạo dependency mới trong named volumes bằng `composer install` và `npm ci` dù máy Mac đã có các thư mục này ở host.
