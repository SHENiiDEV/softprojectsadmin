# Деплой на VPS (Ubuntu 24.04, nginx + PHP-FPM 8.4 + MySQL + Certbot)

- Каталог приложения: `/var/www/softprojects-admin`
- Домен: `soft-projects.io` (+ `www`). Замените, если домен другой (в командах и в `nginx.conf`).
- Все команды выполняются от **root**, в конце владельцем файлов становится `www-data`.
- Профиль сервера: **8 vCPU, 24 GB RAM, NVMe** (настройки ниже рассчитаны на него и выделяют ресурсы под админку).
  Если сервер меньше или на нём живут другие сервисы, уменьшите значения из таблицы «Бюджет памяти».
- DNS: A/AAAA-записи `soft-projects.io` и `www` должны указывать на сервер **до** шага 8 (Certbot).

## 1. Системные пакеты

```bash
apt update && apt install -y software-properties-common curl git unzip ufw
add-apt-repository -y ppa:ondrej/php
apt update
apt install -y nginx mysql-server certbot python3-certbot-nginx \
  php8.4-fpm php8.4-cli php8.4-mysql php8.4-mbstring php8.4-xml php8.4-curl \
  php8.4-zip php8.4-gd php8.4-intl php8.4-bcmath php8.4-opcache
```

Composer и Node.js (Node нужен для `npm run build`, Vite):

```bash
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
curl -fsSL https://deb.nodesource.com/setup_22.x | bash - && apt install -y nodejs
```

Файрвол:

```bash
ufw allow OpenSSH && ufw allow "Nginx Full" && ufw --force enable
```

## 2. Настройка PHP 8.4 и пул PHP-FPM

Лимиты PHP и OPcache:

```bash
cat > /etc/php/8.4/fpm/conf.d/99-softprojects.ini <<'INI'
memory_limit = 512M
upload_max_filesize = 100M
post_max_size = 110M
max_execution_time = 300
max_input_time = 300
expose_php = Off

opcache.enable = 1
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 32
opcache.max_accelerated_files = 20000
opcache.validate_timestamps = 0
INI
cp /etc/php/8.4/fpm/conf.d/99-softprojects.ini /etc/php/8.4/cli/conf.d/99-softprojects.ini
sed -i 's/^memory_limit.*/memory_limit = 1G/' /etc/php/8.4/cli/conf.d/99-softprojects.ini   # CLI: воркеры, импорт, PDF
```

Отдельный пул вместо стандартного `www` (свой сокет, лимиты процессов):

```bash
mv /etc/php/8.4/fpm/pool.d/www.conf /etc/php/8.4/fpm/pool.d/www.conf.disabled
cat > /etc/php/8.4/fpm/pool.d/softprojects.conf <<'POOL'
[softprojects]
user = www-data
group = www-data
listen = /run/php/softprojects.sock
listen.owner = www-data
listen.group = www-data
listen.backlog = 1024

pm = dynamic
pm.max_children = 40
pm.start_servers = 10
pm.min_spare_servers = 8
pm.max_spare_servers = 16
pm.max_requests = 500

request_terminate_timeout = 300s
catch_workers_output = yes
POOL
php-fpm8.4 -t && systemctl restart php8.4-fpm
```

> `opcache.validate_timestamps = 0` — PHP не перечитывает файлы сам, поэтому `deploy.sh`
> в конце делает `systemctl reload php8.4-fpm`. Если не нужно — поставьте `1`.
> `pm.max_children = 40` ≈ 40 одновременных запросов (~100 MB на процесс ≈ 4 GB максимум).

### Бюджет памяти (24 GB)

| Компонент | Настройка | ~RAM |
|---|---|---|
| MySQL | `innodb_buffer_pool_size = 6G` | 6.5 GB |
| PHP-FPM | 40 процессов × ~100 MB | до 4 GB |
| Воркеры очереди | 4 + 1 gmail × ≤256 MB | до 1.3 GB |
| OPcache, nginx, ОС | | ~1.5 GB |
| Файловый кэш ОС (остаток) | | ~10 GB |

## 3. Код

```bash
git config --global --add safe.directory /var/www/softprojects-admin
git clone -b main https://github.com/SHENiiDEV/softprojectsadmin.git /var/www/softprojects-admin
cd /var/www/softprojects-admin
```

Репозиторий приватный: используйте deploy key (`git clone git@github.com:SHENiiDEV/softprojectsadmin.git`)
или fine-grained токен с правом Contents: Read.

## 4. MySQL: изолированная база и пользователь

Пользователь видит **только** свою базу и подключается только с localhost.

```bash
DB_PASS=$(openssl rand -base64 24 | tr -d '=+/')
mysql <<SQL
CREATE DATABASE softprojects CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'softprojects'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON softprojects.* TO 'softprojects'@'localhost';
FLUSH PRIVILEGES;
SQL
echo "DB password: ${DB_PASS}"   # сохраните в менеджере паролей, дальше он попадёт в .env
```

Проверьте, что MySQL слушает только localhost (`bind-address = 127.0.0.1` в
`/etc/mysql/mysql.conf.d/mysqld.cnf` — значение по умолчанию) и порт 3306 не открыт в `ufw`.
Запустите `mysql_secure_installation` один раз.

Настройка MySQL под NVMe и 24 GB RAM:

```bash
cat > /etc/mysql/mysql.conf.d/99-softprojects.cnf <<'CNF'
[mysqld]
bind-address = 127.0.0.1
max_connections = 200

innodb_buffer_pool_size = 6G
innodb_redo_log_capacity = 2G
innodb_flush_method = O_DIRECT
innodb_io_capacity = 2000
innodb_io_capacity_max = 4000

tmp_table_size = 128M
max_heap_table_size = 128M

slow_query_log = 1
long_query_time = 1
CNF
systemctl restart mysql
```

> `innodb_flush_log_at_trx_commit` оставляем по умолчанию (`1`): данные не теряются при сбое питания.

## 5. Зависимости и сборка

```bash
cd /var/www/softprojects-admin
export COMPOSER_ALLOW_SUPERUSER=1
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
npm ci --no-audit --no-fund && npm run build
```

> `deploy.sh` ставит dev-зависимости (`composer install` без `--no-dev`) и `npm install`.
> Для прода лучше `--no-dev`; при желании поправьте скрипт.

## 6. Готовый `.env`

В репозитории лежит шаблон `.env.production.example`. Копируем и подставляем секреты:

```bash
cd /var/www/softprojects-admin
cp .env.production.example .env
php artisan key:generate --force

sed -i "s|__DB_PASSWORD__|${DB_PASS}|" .env
sed -i "s|__TELEGRAM_WEBHOOK_SECRET__|$(openssl rand -hex 32)|" .env
sed -i "s|__GMAIL_WEBHOOK_SECRET__|$(openssl rand -hex 32)|" .env
sed -i "s|__GMAIL_PUBSUB_TOKEN__|$(openssl rand -hex 32)|" .env
nano .env    # заполните __SMTP_*__, __TELEGRAM_BOT_TOKEN__, GOOGLE_*, при необходимости ENABLE_*
```

Если `DB_PASS` потерялась (новая сессия), задайте пароль заново:
`mysql -e "ALTER USER 'softprojects'@'localhost' IDENTIFIED BY 'новый_пароль';"` и впишите его в `.env`.

Чтобы убедиться, что плейсхолдеров не осталось: `grep -n "__" .env` (должно быть пусто).

> **`APP_KEY` нельзя менять после запуска**: зашифрованные пароли в Credential Vault перестанут расшифровываться.
> Сразу сохраните копию `.env` в безопасном месте.

## 7. Миграции, роли, storage

```bash
cd /var/www/softprojects-admin
php artisan migrate --force
php artisan db:seed --class=RolesAndPermissionsSeeder --force
php artisan storage:link
```

> ⚠️ Сидер создаёт **демо-пользователей** (`admin@projecthub.com`, `manager@…`, `curator@…`, `worker@…`)
> с паролем `password`. Сразу создайте своего администратора и удалите демо-аккаунты:

```bash
php artisan tinker --execute '
$u = App\Models\User::updateOrCreate(
    ["email" => "you@example.com"],
    ["name" => "Your Name", "password" => Hash::make("СЛОЖНЫЙ_ПАРОЛЬ"), "email_verified_at" => now()]
);
$u->syncRoles(["admin"]);
App\Models\User::whereIn("email", ["admin@projecthub.com","manager@projecthub.com","curator@projecthub.com","worker@projecthub.com"])->delete();
'
```

## 8. nginx и сертификат

```bash
cd /var/www/softprojects-admin
cp nginx.conf /etc/nginx/sites-available/softprojects-admin
ln -sf /etc/nginx/sites-available/softprojects-admin /etc/nginx/sites-enabled/softprojects-admin
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

certbot --nginx -d soft-projects.io -d www.soft-projects.io --redirect -m you@example.com --agree-tos -n
systemctl status certbot.timer   # автопродление; проверка: certbot renew --dry-run
```

Certbot сам добавит блок `listen 443 ssl` и редирект с HTTP на HTTPS.

## Cloudflare (после того как домен привязан)

1. **SSL/TLS → Full (strict).** Режим Flexible даёт бесконечные редиректы вместе с редиректом certbot.
2. **Сертификат:** выпускайте его (шаг 8) при «DNS only» (серое облако), затем включайте проксирование (оранжевое).
3. **Реальный IP клиента.** Без этого приложение видит IP Cloudflare: ломается лимит попыток входа и логи.
   ```bash
   cd /var/www/softprojects-admin
   ./cloudflare-ips.sh                # nginx получает IP из CF-Connecting-IP
   ```
4. **Закрыть прямой доступ к серверу в обход Cloudflare** (443 только с адресов Cloudflare; 80 остаётся открытым для продления сертификата):
   ```bash
   ./cloudflare-ips.sh --ufw
   ufw status | head -20              # SSH (22) должен остаться разрешённым
   ```
   Выполняйте **после** того, как сайт открывается через Cloudflare. Ежемесячное обновление списка:
   ```bash
   echo '0 4 1 * * root /var/www/softprojects-admin/cloudflare-ips.sh --ufw >> /var/log/cloudflare-ips.log 2>&1' > /etc/cron.d/cloudflare-ips
   ```
5. **Кэш и WAF.** Для админки поставьте Cache Rule «Bypass cache» (кроме `/build/*`). Если включаете Bot Fight Mode или WAF-правила,
   добавьте исключения для `/telegram/webhook`, `/api/v1/*` (Telegram и Google ходят туда через Cloudflare).
6. **Gmail Pub/Sub.** В подписке (Push endpoint) укажите URL с токеном из `.env`:
   `https://soft-projects.io/api/v1/gmail/pubsub-webhook?token=<GMAIL_PUBSUB_TOKEN>`. Без токена эндпоинт отвечает 403/503.

## 9. Права (root → www-data)

```bash
cd /var/www/softprojects-admin
chown -R www-data:www-data .
find . -type d -not -path "./node_modules/*" -exec chmod 755 {} +
find . -type f -not -path "./node_modules/*" -exec chmod 644 {} +
chmod -R ug+rwX storage bootstrap/cache
chmod 640 .env && chown root:www-data .env
chmod +x deploy.sh artisan
```

> `.env` принадлежит root, группа `www-data` читает его, но не меняет.

## 10. Очередь и планировщик

Очередь обрабатывает Telegram, Gmail и конвертации медиа (`QUEUE_CONNECTION=database`).
Воркеры разделены на две очереди:

- `default` — **4 параллельных воркера** (Telegram-уведомления, медиа);
- `gmail` — **строго 1 воркер**: письма обрабатываются по очереди, иначе два воркера могут создать дубль тикета по одной и той же переписке.

`DB_QUEUE_RETRY_AFTER=360` в `.env` должен быть больше `--timeout` любого воркера (иначе задача стартует повторно, пока ещё выполняется).

```bash
cat > /etc/systemd/system/softprojects-queue@.service <<'UNIT'
[Unit]
Description=SoftProjects queue worker #%i
After=network.target mysql.service

[Service]
User=www-data
Group=www-data
WorkingDirectory=/var/www/softprojects-admin
ExecStart=/usr/bin/php artisan queue:work --queue=default --sleep=2 --tries=3 --timeout=120 --memory=256 --max-jobs=1000 --max-time=3600
Restart=always
RestartSec=5
LimitNOFILE=65535

[Install]
WantedBy=multi-user.target
UNIT

cat > /etc/systemd/system/softprojects-queue-gmail.service <<'UNIT'
[Unit]
Description=SoftProjects Gmail queue worker (single)
After=network.target mysql.service

[Service]
User=www-data
Group=www-data
WorkingDirectory=/var/www/softprojects-admin
ExecStart=/usr/bin/php artisan queue:work --queue=gmail --sleep=2 --tries=3 --timeout=300 --memory=256 --max-jobs=500 --max-time=3600
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
UNIT

systemctl daemon-reload
systemctl enable --now softprojects-queue@{1..4} softprojects-queue-gmail
systemctl list-units 'softprojects-queue*' --no-legend      # должно быть 5 активных юнитов
```

Больше 4 воркеров `default` обычно не нужно: очередь на базе данных упирается в блокировки таблицы `jobs`,
а не в CPU. Если очередь копится — смотрите `php artisan queue:monitor default,gmail` и таблицу `failed_jobs`.

Планировщик (дедлайны, дайджест, `gmail:sync-recent` каждые 2 минуты):

```bash
echo '* * * * * www-data cd /var/www/softprojects-admin && /usr/bin/php artisan schedule:run >> /dev/null 2>&1' > /etc/cron.d/softprojects
chmod 644 /etc/cron.d/softprojects
```

## 11. Кэши, Telegram, первый запуск

```bash
cd /var/www/softprojects-admin
php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache

php artisan telegram:set-webhook https://soft-projects.io/telegram/webhook
php artisan gmail:watch        # если настроен Gmail

# artisan запускался от root и мог создать файлы с его владельцем (логи, кэш) — возвращаем www-data
chown -R www-data:www-data storage bootstrap/cache
```

Проверка: откройте https://soft-projects.io, войдите, отправьте боту `/start`.

## Обновление (каждый следующий деплой)

```bash
cd /var/www/softprojects-admin && ./deploy.sh
```

`deploy.sh` делает `git pull`, `composer install`, миграции, пересборку кэшей и Vite, `queue:restart`
(systemd поднимает воркер заново), возвращает права `www-data` (`.env` остаётся `root:www-data 640`)
и перезагружает PHP-FPM.

## Дополнительно: Redis для кэша и сессий (по желанию)

Кэш и сессии в MySQL работают, но Redis разгружает базу и ускоряет отклик. Ресурсов хватает, поэтому рекомендуется:

```bash
apt install -y redis-server php8.4-redis
sed -i 's/^# *maxmemory .*/maxmemory 1gb/; s/^# *maxmemory-policy .*/maxmemory-policy allkeys-lru/' /etc/redis/redis.conf
systemctl restart redis-server php8.4-fpm
```

В `.env` замените:

```
CACHE_STORE=redis
SESSION_DRIVER=redis
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
```

Затем `php artisan config:cache && systemctl restart softprojects-queue@{1..4} softprojects-queue-gmail`.
Очередь оставьте на базе данных: так задачи не потеряются при перезапуске Redis. После смены `SESSION_DRIVER` все пользователи будут разлогинены один раз.

## Чек-лист безопасности

- [ ] `APP_DEBUG=false`, `APP_ENV=production` (`php artisan about`)
- [ ] демо-пользователи удалены, свой админ создан
- [ ] `TELEGRAM_WEBHOOK_SECRET`, `GMAIL_WEBHOOK_SECRET` и `GMAIL_PUBSUB_TOKEN` заданы
- [ ] Cloudflare: Full (strict), `./cloudflare-ips.sh --ufw` выполнен, прямой заход на IP по HTTPS не отвечает
- [ ] порт 3306 закрыт снаружи (`ss -tlnp | grep 3306` → только 127.0.0.1)
- [ ] `.env` не читается всеми: `ls -l .env` → `-rw-r----- root www-data`
- [ ] резервные копии: `mysqldump softprojects | gzip` + `storage/app` + копия `.env`
- [ ] `certbot renew --dry-run` проходит
- [ ] 5 воркеров очереди активны, `failed_jobs` пуста
- [ ] `mysql -e "SHOW VARIABLES LIKE 'innodb_buffer_pool_size'"` → 6 GB; `free -h` без своппинга

## Частые проблемы

| Симптом | Причина / решение |
|---|---|
| 502 Bad Gateway | нет сокета пула: `ls /run/php/` → `softprojects.sock`; `php-fpm8.4 -t`; `journalctl -u php8.4-fpm` |
| Много 502 под нагрузкой | в логе FPM `max_children reached` → поднимите `pm.max_children` (с оглядкой на RAM) |
| Письма не обрабатываются | `systemctl status softprojects-queue-gmail`, `tail storage/logs/laravel-*.log` |
| 500, в логе `Permission denied` | после запуска от root: `chown -R www-data:www-data storage bootstrap/cache` |
| Не грузятся стили | `npm run build` не выполнен или нет Node |
| Telegram/Gmail не работают | нет секретов в `.env` (ответ 503/403), не запущен воркер очереди |
| Изменения не видны после деплоя | `./deploy.sh` (он перезагружает FPM) или вручную `systemctl reload php8.4-fpm` |
| Загрузка файла > 2 МБ падает | проверьте `99-softprojects.ini` (100M) и `client_max_body_size` (100M) в nginx |
