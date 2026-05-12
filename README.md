# 🚀 Vocafy Project

Đây là project Laravel chạy bằng Docker. Làm theo các bước dưới đây để setup và chạy project trên máy local.

Trước tiên, bạn cần đảm bảo đã cài sẵn Docker, Docker Compose và Git.

Đầu tiên, tiến hành clone project về máy:

git clone <repo-url>
cd <project>

Sau khi vào thư mục project, bạn cần tạo file môi trường bằng cách copy từ file mẫu:

cp src/.env.example src/.env

Sau đó mở file .env và cấu hình lại thông tin database cho phù hợp với môi trường Docker, ví dụ:

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=vocafy
DB_USERNAME=root
DB_PASSWORD=root

Tiếp theo, bạn tiến hành build và khởi chạy toàn bộ hệ thống Docker:

docker compose up -d --build

Sau khi container đã chạy xong, kiểm tra lại bằng:

docker ps

Tiếp theo, truy cập vào container của ứng dụng Laravel:

docker exec -it vocafy_app bash

Bên trong container, tiến hành cài đặt các package PHP:

composer install

Sau đó tạo application key cho Laravel:

php artisan key:generate

Tiếp theo, cần cấp quyền cho các thư mục storage và bootstrap/cache để tránh lỗi permission:

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

Sau đó tiến hành migrate database:

php artisan migrate

Nếu project có dữ liệu mẫu thì chạy thêm seed:

php artisan db:seed

Khi hoàn tất tất cả các bước trên, bạn có thể truy cập project qua trình duyệt:

http://localhost

Nếu gặp lỗi hoặc cần chạy lại toàn bộ hệ thống, có thể dùng:

docker compose down
docker compose up -d --build

Một số lệnh Laravel hữu ích khi debug:

php artisan config:clear
php artisan cache:clear
php artisan route:clear

Nếu gặp lỗi không kết nối database, hãy kiểm tra lại file .env và đảm bảo container database đang chạy bằng lệnh docker ps.

Sau khi hoàn tất, project đã sẵn sàng để sử dụng và phát triển.