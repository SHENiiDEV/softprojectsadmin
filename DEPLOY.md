# Деплой на VPS (Ubuntu 24.04, nginx + PHP-FPM 8.4 + MySQL + Certbot)

- Каталог приложения: `/var/www/softprojects-admin`
- Домен: `soft-projects.io` (+ `www`). Замените, если домен другой (в командах и в `nginx.conf`).
- Все команды выполняются от **root**, в конце владельцем файлов становится `www-data`.
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

## 2. Настройка PHP 8.4

```bash
cat > /etc/php/8.4/fpm/conf.d/99-softprojects.ini <<'INI'
upload_max_filesize = 50M
post_max_size = 55M
memory_limit = 512M
max_execution_time = 120
expose_php = Off
opcache.enable = 1
opcache.validate_timestamps = 0
INI
systemctl restart php8.4-fpm
```

> `opcache.validate_timestamps = 0` — PHP не перечитывает файлы сам, поэтому `deploy.sh`
> в конце делает `systemctl reload php8.4-fpm`. Если не нужно — поставьте `1`.

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

Очередь обрабатывает Telegram и Gmail (`QUEUE_CONNECTION=database`), без воркера уведомления не уходят.

```bash
cat > /etc/systemd/system/softprojects-queue.service <<'UNIT'
[Unit]
Description=SoftProjects queue worker
After=network.target mysql.service

[Service]
User=www-data
Group=www-data
WorkingDirectory=/var/www/softprojects-admin
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
UNIT
systemctl daemon-reload && systemctl enable --now softprojects-queue
```

Планировщик (дедлайны, дайджест, `gmail:sync-recent` каждые 2 минуты):

```bash
echo '* * * * * www-data cd /var/www/softprojects-admin && /usr/bin/php artisan schedule:run >> /dev/null 2>&1' > /etc/cron.d/softprojects
chmod 644 /etc/cron.d/softprojects
```

## 11. Кэши, Telegram, первый запуск

```bash
cd /var/www/softprojects-admin
php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache
chown -R www-data:www-data storage bootstrap/cache

php artisan telegram:set-webhook https://soft-projects.io/telegram/webhook
php artisan gmail:watch        # если настроен Gmail
```

Проверка: откройте https://soft-projects.io, войдите, отправьте боту `/start`.

## Обновление (каждый следующий деплой)

```bash
cd /var/www/softprojects-admin && ./deploy.sh
```

`deploy.sh` делает `git pull`, `composer install`, миграции, пересборку кэшей и Vite, `queue:restart`
(systemd поднимает воркер заново), возвращает права `www-data` (`.env` остаётся `root:www-data 640`)
и перезагружает PHP-FPM.

## Чек-лист безопасности

- [ ] `APP_DEBUG=false`, `APP_ENV=production` (`php artisan about`)
- [ ] демо-пользователи удалены, свой админ создан
- [ ] `TELEGRAM_WEBHOOK_SECRET` и `GMAIL_WEBHOOK_SECRET` заданы
- [ ] порт 3306 закрыт снаружи (`ss -tlnp | grep 3306` → только 127.0.0.1)
- [ ] `.env` не читается всеми: `ls -l .env` → `-rw-r----- root www-data`
- [ ] резервные копии: `mysqldump softprojects | gzip` + `storage/app` + копия `.env`
- [ ] `certbot renew --dry-run` проходит

## Частые проблемы

| Симптом | Причина / решение |
|---|---|
| 502 Bad Gateway | сокет не совпадает: `ls /var/run/php/`, должен быть `php8.4-fpm.sock` |
| 500, в логе `Permission denied` | после запуска от root: `chown -R www-data:www-data storage bootstrap/cache` |
| Не грузятся стили | `npm run build` не выполнен или нет Node |
| Telegram/Gmail не работают | нет секретов в `.env` (ответ 503/403), не запущен воркер очереди |
| Изменения не видны после деплоя | `./deploy.sh` (он перезагружает FPM) или вручную `systemctl reload php8.4-fpm` |
| Загрузка файла > 2 МБ падает | проверьте `99-softprojects.ini` и `client_max_body_size` в nginx |
