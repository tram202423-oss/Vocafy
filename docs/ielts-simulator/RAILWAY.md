# Railway: chạy Scheduler chung với Queue Mail

Module IELTS có lịch `ielts:complete-expired` mỗi phút trong `src/routes/console.php`. Queue Mail và Scheduler có thể dùng chung một Railway service, nhưng phải là hai tiến trình chạy song song. Script [start-mail-and-scheduler.sh](../../src/scripts/start-mail-and-scheduler.sh) quản lý cả hai: nếu một tiến trình dừng, service thoát để Railway khởi động lại.

## Cấu hình service Queue Mail

1. Deploy bản code có script mới.
2. Mở service **Queue Mail → Settings → Deploy → Custom Start Command**.
3. Nếu **Root Directory** là `src`, nhập `bash scripts/start-mail-and-scheduler.sh`. Nếu là root repository, nhập `bash src/scripts/start-mail-and-scheduler.sh`. Script tự chuyển vào thư mục Laravel trước khi chạy Artisan.
4. Giữ một replica, chạy liên tục và không cấu hình Railway Cron Schedule hay public domain cho service này.
5. Giữ `APP_KEY`, kết nối database, `QUEUE_CONNECTION=database` và cache giống App. Với worker timeout 90 giây, đặt `DB_QUEUE_RETRY_AFTER=120` trên Queue Mail để job không được nhận lại khi worker còn xử lý.
6. Deploy lại service. Trong logs sẽ có `Starting default queue worker...` và `Starting Laravel scheduler...`.

Scheduler chỉ chạy một bản cho môi trường production. Railway Cron hiện chỉ hỗ trợ lịch cách nhau ít nhất 5 phút, còn lệnh IELTS này cần mỗi phút. Nếu đã có service Scheduler riêng, tắt service đó sau khi Queue Mail chạy script mới để tránh hai scheduler cùng thực thi.

IELTS AI Queue vẫn là tiến trình riêng: `php artisan queue:work ielts --queue=ielts --tries=2 --timeout=660`. File Speaking/Writing cần lưu trữ mà App và IELTS worker đều đọc được; script này không thay đổi nơi lưu file.
