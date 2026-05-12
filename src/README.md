git clone <repo-url>
cd <project>
cp src/.env.example src/.env
docker compose up -d --build
docker exec -it vocafy_app bash
composer install
php artisan key:generate
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
php artisan migrate
php artisan db:seed
