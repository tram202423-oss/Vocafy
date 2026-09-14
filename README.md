# 🚀 Vocafy - Smart English Vocabulary & Writing Assistant

<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="220" alt="Laravel Logo">
</p>

<p align="center">
  <strong>Nền tảng học từ vựng tiếng Anh thông minh kết hợp đánh giá bài viết qua AI (Google Gemini)</strong>
</p>

<p align="center">
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-11.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 11"></a>
  <a href="https://php.net"><img src="https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.3"></a>
  <a href="https://filamentphp.com"><img src="https://img.shields.io/badge/Filament-3.x-F59E0B?style=for-the-badge&logo=laravel&logoColor=white" alt="Filament"></a>
  <a href="https://www.docker.com"><img src="https://img.shields.io/badge/Docker-Enabled-2496ED?style=for-the-badge&logo=docker&logoColor=white" alt="Docker"></a>
  <a href="https://www.mysql.com"><img src="https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL"></a>
  <a href="https://tailwindcss.com"><img src="https://img.shields.io/badge/TailwindCSS-3.x-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white" alt="Tailwind CSS"></a>
</p>

---

## 📖 Giới thiệu (Overview)

**Vocafy** là hệ thống hỗ trợ người học tiếng Anh nâng cao vốn từ vựng và kỹ năng viết một cách khoa học:
- **Học từ vựng theo danh mục & chủ đề**: Phân cấp Category -> Topic -> Vocabulary trực quan.
- **Theo dõi tiến độ học tập**: Đánh dấu từ đã thuộc (*Master*), cần ôn tập (*Review*), và đặt lại tiến độ (*Reset*).
- **AI Writing Assistant**: Đánh giá, chấm điểm và góp ý bài viết tiếng Anh tức thì sử dụng sức mạnh của **Google Gemini API**.
- **Hệ thống Quản trị Filament 3**: Giao diện Admin Panel hiện đại quản lý toàn bộ dữ liệu (Categories, Topics, Lessons, Quizzes, Vocabularies, Users, Roles & Permissions).

---

## 🛠️ Công nghệ sử dụng (Tech Stack)

| Thành phần | Công nghệ / Thư viện |
| :--- | :--- |
| **Backend Framework** | Laravel 11.x (PHP 8.3 FPM) |
| **Admin Panel** | [Filament PHP v3](https://filamentphp.com/) |
| **Authentication & RBAC** | Laravel Breeze & [Spatie Laravel-Permission](https://spatie.be/docs/laravel-permission) |
| **AI Integration** | Google Gemini API (Writing Evaluator) |
| **Frontend** | Blade, Tailwind CSS, Alpine.js, Vite 6 |
| **Database** | MySQL 8.0 |
| **Web Server & Container** | Nginx Alpine, Docker & Docker Compose |
| **Media & Logging** | Spatie MediaLibrary, Spatie ActivityLog, Maatwebsite Excel |

---

## 🏛️ Cấu trúc hệ thống Docker (Services & Ports)

Toàn bộ môi trường phát triển được đóng gói qua Docker Compose:

| Service Name | Container Name | Image / Dockerfile | Port (Host : Container) | Chức năng |
| :--- | :--- | :--- | :--- | :--- |
| `app` | `vocafy_app` | `docker/php/Dockerfile` (PHP 8.3 + Node 22 + Composer 2.7) | `5173:5173` | PHP-FPM Backend & Vite Dev Server |
| `web` | `vocafy_nginx` | `nginx:alpine` | **`8080:80`** | Nginx Web Server chính |
| `db` | `vocafy_db` | `mysql:8.0` | **`3307:3306`** | MySQL Database Server |

> 📌 **Địa chỉ truy cập ứng dụng**: [http://localhost:8080](http://localhost:8080)  
> 📌 **Admin Panel**: [http://localhost:8080/admin](http://localhost:8080/admin)  
> 📌 **MySQL từ máy Host (DBeaver, TablePlus, Navicat)**: `127.0.0.1:3307`

---

## 📋 Yêu cầu hệ thống (Prerequisites)

- [Docker](https://docs.docker.com/get-docker/) & [Docker Compose](https://docs.docker.com/compose/install/) (khuyến nghị Docker Desktop hoặc Docker Engine trên Ubuntu/WSL2).
- [Git](https://git-scm.com/).

---

## 🚀 Hướng dẫn cài đặt chi tiết (Step-by-Step Setup)

### 1. Clone repository
```bash
git clone https://github.com/your-username/vocafy.git
cd vocafy
```

### 2. Cấu hình biến môi trường (`.env`)
Tạo file `.env` từ file mẫu:
```bash
cp src/.env.example src/.env
```

Mở file `src/.env` và cập nhật các thông số kết nối Database tương thích với Docker:
```dotenv
APP_NAME=Vocafy
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8080

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=vocafy
DB_USERNAME=vocafy
DB_PASSWORD=secret

# Khóa API để dùng tính năng AI Writing Evaluation
GEMINI_API_KEY=your_gemini_api_key_here
```

*(Tùy chọn: Nếu muốn dùng tài khoản root MySQL: `DB_USERNAME=root` và `DB_PASSWORD=root`)*

---

### 3. Khởi chạy Docker Containers
Build và khởi động các container ở chế độ chạy ngầm (background):
```bash
docker compose up -d --build
```

Kiểm tra trạng thái các container:
```bash
docker compose ps
```
*Đảm bảo 3 container `vocafy_app`, `vocafy_nginx`, và `vocafy_db` đều ở trạng thái `Up`.*

---

### 4. Cài đặt Dependencies & Khởi tạo ứng dụng
Truy cập vào trong container `vocafy_app`:
```bash
docker exec -it vocafy_app bash
```

Thực hiện các lệnh sau **bên trong container**:

```bash
# 1. Cài đặt các thư viện PHP
composer install

# 2. Cài đặt các thư viện Frontend & Build tài nguyên
npm install
npm run build

# 3. Tạo Application Key
php artisan key:generate

# 4. Chạy Migration và nạp dữ liệu mẫu (Roles, Admin, Categories, Topics, Vocabularies)
php artisan migrate --seed

# 5. Cấp quyền truy cập cho thư mục storage & bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# 6. Thoát khỏi container
exit
```

> 💡 **Mẹo cho người dùng Linux / WSL2**:  
> Nếu gặp lỗi phân quyền file khi chỉnh sửa code trên máy host, bạn có thể cấp quyền cho user hiện tại:
> ```bash
> sudo chown -R $USER:$USER .
> ```

---

## 🔑 Thông tin đăng nhập mặc định (Default Credentials)

Sau khi chạy lệnh `php artisan db:seed`, hệ thống sẽ tự động khởi tạo tài khoản quản trị:

| Vai trò | Đường dẫn | Email | Mật khẩu |
| :--- | :--- | :--- | :--- |
| **SuperAdmin** | [/admin](http://localhost:8080/admin) | `admin@vocafy.com` | `12345678` |

---

## 💻 Hướng dẫn phát triển (Development Workflow)

### Chạy Vite Hot-Reload (Frontend Dev)
Khi chỉnh sửa giao diện Tailwind CSS hoặc JavaScript, bạn có thể chạy Vite dev server:
```bash
docker exec -it vocafy_app npm run dev
```

### Chạy lệnh Artisan trực tiếp không cần bash vào container
```bash
# Chạy migration
docker exec -it vocafy_app php artisan migrate

# Xóa toàn bộ cache
docker exec -it vocafy_app php artisan optimize:clear

# Mở Laravel Tinker
docker exec -it vocafy_app php artisan tinker
```

---

## 🧰 Các lệnh thường dùng (Cheatsheet)

### Quản lý Docker
```bash
# Khởi động toàn bộ dịch vụ
docker compose up -d

# Dừng toàn bộ dịch vụ
docker compose down

# Dừng và xóa luôn volumes (LƯU Ý: sẽ mất dữ liệu database)
docker compose down -v

# Xem logs thời gian thực của toàn bộ hệ thống
docker compose logs -f

# Xem logs của dịch vụ cụ thể
docker compose logs -f app
docker compose logs -f web
docker compose logs -f db
```

### Bảo trì & Dọn dẹp Cache Laravel
```bash
docker exec -it vocafy_app php artisan config:clear
docker exec -it vocafy_app php artisan cache:clear
docker exec -it vocafy_app php artisan route:clear
docker exec -it vocafy_app php artisan view:clear
```

---

## ❓ Xử lý sự cố thường gặp (Troubleshooting)

<details>
<summary><strong>1. Lỗi: SQLSTATE[HY000] [2002] Connection refused</strong></summary>

- Kiểm tra xem service `db` đã khởi động thành công chưa: `docker compose ps`.
- Trong file `src/.env`, giá trị `DB_HOST` phải là **`db`** (tên service trong `docker-compose.yml`), không phải `localhost` hay `127.0.0.1`.
- Container MySQL có thể mất 10-15 giây để hoàn tất khởi động lần đầu tiên. Chờ vài giây rồi chạy lại migrate.
</details>

<details>
<summary><strong>2. Lỗi phân quyền: The stream or file ".../laravel.log" could not be opened: failed to open stream: Permission denied</strong></summary>

Chạy lệnh cấp quyền lại cho container:
```bash
docker exec -it vocafy_app bash -c "chown -R www-data:www-data storage bootstrap/cache && chmod -R 775 storage bootstrap/cache"
```
</details>

<details>
<summary><strong>3. Giao diện bị vỡ, không load được CSS/JS</strong></summary>

Kiểm tra xem đã build assets chưa:
```bash
docker exec -it vocafy_app npm run build
```
</details>

---

## 📁 Cấu trúc thư mục (Directory Structure)

```text
Vocafy/
├── docker/                 # Cấu hình Docker
│   ├── nginx/              # Config Nginx (default.conf)
│   └── php/                # Dockerfile PHP 8.3 FPM + Node.js
├── docker-compose.yml      # Định nghĩa các dịch vụ Docker (app, web, db)
├── README.md               # Tài liệu dự án
└── src/                    # Mã nguồn chính Laravel 11
    ├── app/
    │   ├── Enums/          # Enum định nghĩa Role, Status...
    │   ├── Filament/       # Resources & Pages của Admin Panel
    │   ├── Http/           # Controllers, Middleware, Requests
    │   ├── Models/         # Eloquent Models (Category, Topic, Vocabulary...)
    │   └── Services/       # Gemini AI Service & Business Logics
    ├── database/           # Migrations, Seeders & Factories
    ├── resources/          # Views (Blade), CSS, JS
    ├── routes/             # Web & API routes
    └── composer.json       # PHP Dependencies
```

---

## 📄 Bản quyền & Đóng góp (License & Contributing)

- Dự án được phát triển dưới giấy phép [MIT License](https://opensource.org/licenses/MIT).
- Mọi đóng góp, báo lỗi (issue) và đề xuất tính năng (pull request) đều được hoan nghênh!