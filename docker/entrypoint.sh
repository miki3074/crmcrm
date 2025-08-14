#!/usr/bin/env bash
set -e

cd /var/www/html

echo "==> Bootstrapping Laravel container..."

# 0) Удаляем старые кеши, чтобы не цеплялись dev-провайдеры и старые настройки
rm -f bootstrap/cache/config.php \
      bootstrap/cache/packages.php \
      bootstrap/cache/routes*.php \
      bootstrap/cache/services.php || true

# 1) Готовим .env
if [ ! -f .env ]; then
  echo "==> .env not found, copying from .env.example"
  cp -n .env.example .env 2>/dev/null || true
fi

# 2) Создаём необходимые директории и права
mkdir -p \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs
touch storage/logs/laravel.log
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

# 3) Ждём БД (до 60 сек)
echo "==> Waiting for database (${DB_HOST:-db}:${DB_PORT:-3306})..."
for i in {1..60}; do
  if php -r '
    $h=getenv("DB_HOST") ?: "db";
    $p=getenv("DB_PORT") ?: "3306";
    $u=getenv("DB_USERNAME") ?: "root";
    $pw=getenv("DB_PASSWORD") ?: "";
    $d=getenv("DB_DATABASE") ?: "laravel";
    try { new PDO("mysql:host=$h;port=$p;dbname=$d",$u,$pw); exit(0);} catch(Exception $e){ exit(1);}
  '; then
    echo "==> DB is up!"; break
  fi
  sleep 1
done

# 4) Генерируем APP_KEY, если он пустой в текущем окружении
#    (artisan key:generate запишет ключ в .env)
if [ -z "${APP_KEY}" ]; then
  echo "==> APP_KEY is empty, generating..."
  php artisan key:generate --force || true
else
  echo "==> APP_KEY is provided by environment."
fi

# 5) Линки и кеши
php artisan storage:link || true
php artisan config:clear || true
php artisan route:clear  || true
php artisan view:clear   || true
php artisan optimize     || true

# 6) Миграции
php artisan migrate --force || true

echo "==> Starting Apache..."
exec apache2-foreground






# #!/usr/bin/env bash
# set -e

# # Если нет APP_KEY — сгенерируем
# if [ -z "${APP_KEY}" ]; then
#   php artisan key:generate --force
# fi

# # Ссылки и кэш (не падаем, если уже есть)
# php artisan key:generate --force || true
# php artisan storage:link || true
# php artisan config:cache
# php artisan route:cache
# php artisan migrate --force
# exec apache2-foreground

# # Миграции (можно снять, если не хочешь авто‑мигрировать)
# if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
#   php artisan migrate --force || true
# fi

# # Запуск Apache
# apache2-foreground
