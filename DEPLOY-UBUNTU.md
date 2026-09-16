# Deploy Duy Hoàng - Kho mẫu CNC trên Ubuntu 24.04

Hướng dẫn cài trực tiếp trên VPS, **không sử dụng Docker**.

| Service trong Compose | Trên VPS |
| --- | --- |
| app | PHP-FPM + Composer |
| caddy | Caddy với HTTPS tự động |
| mysql | MySQL 8 |
| redis | Redis |
| Build giao diện | Node.js 22 + npm |

## 1. Chuẩn bị

Ví dụ trong tài liệu sử dụng:

- Domain: `example.com` và `www.example.com` — thay bằng domain thật.
- Source: `/var/www/sell_cnc`.
- User triển khai: `deploy`.
- Database và user MySQL: `sell_cnc`.
- Repository: `GIT_REPOSITORY_URL` — thay bằng URL repository thật.

Các lệnh `sudo` chạy từ tài khoản quản trị VPS. Các bước ghi rõ **user deploy** chạy sau khi đăng nhập bằng `sudo -iu deploy`.

> **Trước khi public:** các route admin của dự án trước đây là template và chưa có xác thực/phân quyền hoàn chỉnh. Kiểm tra và bổ sung middleware đăng nhập + quyền quản trị cho toàn bộ `/admin` trước khi mở site cho khách. HTTPS và watermark không thay thế kiểm soát truy cập. Có thể dùng Basic Auth tại Caddy như lớp bảo vệ tạm thời (hướng dẫn bên dưới).

### Cập nhật hệ điều hành

```sh
sudo apt update
sudo apt upgrade -y
sudo apt install -y git unzip curl ca-certificates gnupg ufw acl
```

### Firewall

Nếu SSH dùng port tùy chỉnh, mở đúng port đó trước khi bật firewall. Không đóng phiên SSH hiện tại cho tới khi thử kết nối mới thành công.

```sh
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
sudo ufw status
```

Mở tương ứng port 80/443 trong firewall của nhà cung cấp VPS. **Không mở MySQL 3306 hay Redis 6379 ra Internet.**

### User deploy và thư mục source

```sh
sudo adduser deploy
sudo install -d -o deploy -g www-data -m 2755 /var/www/sell_cnc
```

Không cần trao quyền sở hữu toàn bộ `/var/www` cho user deploy. Nếu dùng Git repository private, cấu hình SSH deploy key chỉ đọc cho tài khoản `deploy`; không đặt token trong source hoặc lệnh có thể lưu vào shell history.

## 2. Cài PHP-FPM và font watermark

Ubuntu 24.04 cung cấp PHP 8.3. Source khai báo PHP `^8.2`, nhưng phiên bản dependency trong `composer.lock` cũng cần tương thích. Docker hiện dùng PHP 8.4; nếu Composer báo package yêu cầu 8.4, hãy cài đồng bộ PHP 8.4 từ nguồn tin cậy và đổi toàn bộ đường dẫn/service PHP bên dưới. **Không dùng `--ignore-platform-reqs`.**

```sh
sudo apt install -y \
  php8.3-cli php8.3-fpm php8.3-mysql php8.3-redis \
  php8.3-gd php8.3-zip php8.3-mbstring php8.3-xml \
  php8.3-curl php8.3-bcmath php8.3-intl php8.3-opcache \
  fonts-dejavu-core
sudo systemctl enable --now php8.3-fpm
php -v
php -m
php -r 'print_r(gd_info());'
```

GD cần hỗ trợ JPEG, PNG, WebP và FreeType. Font mặc định watermark:

```text
/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf
```

Chỉnh cấu hình PHP-FPM:

```sh
sudo nano /etc/php/8.3/fpm/php.ini
```

Các giá trị gợi ý:

```ini
upload_max_filesize = 8M
post_max_size = 12M
memory_limit = 512M
max_execution_time = 120
display_errors = Off
log_errors = On
expose_php = Off
```

Laravel vẫn giới hạn ảnh upload theo validation của ứng dụng. Giới hạn PHP cao hơn giúp Laravel trả thông báo hợp lệ khi người dùng chọn file quá lớn. Xử lý ảnh kích thước lớn tốn RAM; điều chỉnh số worker PHP phù hợp dung lượng VPS, không tăng worker vượt khả năng bộ nhớ.

```sh
sudo systemctl restart php8.3-fpm
```

## 3. Composer và Node.js

### Composer

Cách đơn giản, dùng package Ubuntu:

```sh
sudo apt install -y composer
composer --version
```

Chạy Composer bằng user `deploy`, không chạy `composer install` bằng root.

### Node.js 22

Vite 7 cần Node.js tương thích (Node 22.12 trở lên). Bước sau thêm repository bên thứ ba NodeSource. Tải và kiểm tra script trước khi thực thi với quyền quản trị:

```sh
curl -fsSL https://deb.nodesource.com/setup_22.x -o /tmp/nodesource-setup.sh
less /tmp/nodesource-setup.sh
sudo bash /tmp/nodesource-setup.sh
sudo apt install -y nodejs
rm /tmp/nodesource-setup.sh
node --version
npm --version
```

Node chỉ cần để build asset, không cần chạy `npm run dev` trên production.

## 4. MySQL

```sh
sudo apt install -y mysql-server
sudo systemctl enable --now mysql
sudo mysql_secure_installation
sudo mysql
```

Trong MySQL, thay mật khẩu mẫu bằng mật khẩu mạnh, riêng cho production:

```sql
CREATE DATABASE sell_cnc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'sell_cnc'@'localhost' IDENTIFIED BY 'REPLACE_WITH_A_STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON sell_cnc.* TO 'sell_cnc'@'localhost';
EXIT;
```

Kiểm tra `/etc/mysql/mysql.conf.d/mysqld.cnf` có:

```ini
bind-address = 127.0.0.1
```

Nếu sửa cấu hình:

```sh
sudo systemctl restart mysql
```

## 5. Redis

```sh
sudo apt install -y redis-server
sudo systemctl enable --now redis-server
redis-cli ping
```

Kết quả mong đợi: `PONG`. Kiểm tra `/etc/redis/redis.conf`:

```conf
bind 127.0.0.1 -::1
protected-mode yes
```

Nếu sửa cấu hình:

```sh
sudo systemctl restart redis-server
```

## 6. Cài Caddy và quyền kết nối PHP-FPM

```sh
sudo apt install -y debian-keyring debian-archive-keyring apt-transport-https
curl -1sLf https://dl.cloudsmith.io/public/caddy/stable/gpg.key | sudo gpg --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg
curl -1sLf https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt | sudo tee /etc/apt/sources.list.d/caddy-stable.list
sudo apt update
sudo apt install -y caddy
```

Để Caddy kết nối socket mà không cấp quyền đọc `.env` thông qua group `www-data`, dùng ACL của PHP-FPM.

```sh
sudo nano /etc/php/8.3/fpm/pool.d/www.conf
```

Đảm bảo các giá trị sau được bật (không bị comment và không khai báo trùng):

```ini
user = www-data
group = www-data
listen = /run/php/php8.3-fpm.sock
listen.acl_users = caddy
```

```sh
sudo systemctl restart php8.3-fpm
getfacl /run/php/php8.3-fpm.sock
```

ACL socket phải cho `caddy` quyền đọc/ghi. Không dùng `chmod 777` cho socket hoặc source.

## 7. Đưa source lên VPS

Chạy bằng **user deploy**:

```sh
sudo -iu deploy
cd /var/www/sell_cnc
git clone GIT_REPOSITORY_URL .
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
composer check-platform-reqs --no-dev
npm ci
npm run build
```

Nếu chuyển source bằng SFTP/ZIP, đặt source trực tiếp vào `/var/www/sell_cnc`, không lồng thêm thư mục. Không upload `node_modules`, `vendor`, `.env` local hoặc `public/hot`; cài dependency và tạo `.env` riêng trên server.

```sh
rm -f public/hot
cp .env.example .env
nano .env
```

Ví dụ cấu hình (thay domain/password):

```dotenv
APP_NAME="Duy Hoàng - Kho mẫu CNC"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://example.com

LOG_CHANNEL=stack
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sell_cnc
DB_USERNAME=sell_cnc
DB_PASSWORD="REPLACE_WITH_A_STRONG_PASSWORD"

CACHE_STORE=redis
SESSION_DRIVER=redis
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=redis

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

IMAGE_WATERMARK_LOGO_COVER=true
IMAGE_WATERMARK_LOGO_OPACITY=40
IMAGE_WATERMARK_FONT=/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf
```

Không dùng hostname `mysql`, `redis`, `app` của Docker. Dùng port local MySQL `3306`, không phải port host Docker `3308`.

Không đặt `IMAGE_WATERMARK_LOGO` thành đường dẫn macOS hoặc `/var/www/html/...`. Có thể bỏ biến này để dùng mặc định trong `config/images.php`; đảm bảo file `public/assets/images/logo-duy-hoang-gold-brown.png` được đưa lên cùng source.

### Database mới

```sh
php artisan key:generate --force
php artisan migrate --force
php artisan storage:link
```

Nếu `public/storage` là symlink copy từ máy khác trỏ sai đường dẫn, kiểm tra và xóa **chỉ symlink đó** trước khi chạy lại `storage:link`; không xóa thư mục upload thật.

### Chuyển dữ liệu hiện có

Nếu đã có sản phẩm, cần chuyển cả:

1. Bản dump MySQL sang database server (dùng công cụ MySQL dump/import).
2. Toàn bộ `storage/app/public` chứa ảnh đã watermark.
3. `APP_KEY` cũ nếu cần giữ khả năng đọc dữ liệu đã mã hóa của ứng dụng.

Không tạo `APP_KEY` mới tùy tiện khi chuyển database đã có dữ liệu mã hóa. Không chạy `migrate:fresh`, `db:wipe` hoặc xóa volume/database để deploy.

## 8. Quyền truy cập source

Thoát user deploy để quay về tài khoản quản trị:

```sh
exit
sudo chown -R deploy:www-data /var/www/sell_cnc
sudo find /var/www/sell_cnc -type d -exec chmod 755 {} \;
sudo find /var/www/sell_cnc -type f -exec chmod 644 {} \;
sudo chmod 640 /var/www/sell_cnc/.env
```

Cho PHP-FPM và deploy cùng ghi Laravel runtime bằng ACL kế thừa:

```sh
sudo setfacl -R -m u:deploy:rwX,u:www-data:rwX /var/www/sell_cnc/storage /var/www/sell_cnc/bootstrap/cache
sudo find /var/www/sell_cnc/storage /var/www/sell_cnc/bootstrap/cache -type d -exec setfacl -m d:u:deploy:rwx,d:u:www-data:rwx {} \;
```

Caddy cần đọc ảnh upload được phục vụ qua `public/storage`:

```sh
sudo mkdir -p /var/www/sell_cnc/storage/app/public
sudo setfacl -m u:caddy:--x /var/www/sell_cnc/storage /var/www/sell_cnc/storage/app
sudo setfacl -R -m u:caddy:r-X /var/www/sell_cnc/storage/app/public
sudo find /var/www/sell_cnc/storage/app/public -type d -exec setfacl -m d:u:caddy:r-x {} \;
```

Không đặt `.env`, source PHP, database dump hoặc ảnh gốc private trong `public`. Cache cấu hình Laravel cũng chứa secrets: không cấp Caddy quyền đọc toàn bộ `bootstrap/cache` nếu không cần.

## 9. Domain, HTTPS và Caddyfile

Tạo bản ghi DNS `A` của domain trỏ về IP VPS. Chỉ thêm `www` vào cấu hình nếu đã tạo DNS cho nó. Nếu có bản ghi `AAAA`, địa chỉ IPv6 phải trỏ đúng server.

```sh
sudo nano /etc/caddy/Caddyfile
```

```caddy
example.com, www.example.com {
    root * /var/www/sell_cnc/public
    encode zstd gzip

    php_fastcgi unix//run/php/php8.3-fpm.sock
    file_server

    log
}
```

Caddy dùng port tiêu chuẩn **80/443**, không cần `:83` như Docker local. Root bắt buộc là thư mục `public`, không phải gốc source.

### Bảo vệ admin tạm thời

Nếu chưa hoàn thiện xác thực/phân quyền Laravel, tạo hash password:

```sh
caddy hash-password
```

Lệnh yêu cầu nhập password tương tác. Thêm block sau bên trong site block, trước `php_fastcgi`:

```caddy
    @admin path /admin /admin/*
    basic_auth @admin {
        admin REPLACE_WITH_HASH_FROM_CADDY
    }
```

Basic Auth bảo vệ toàn bộ các trang và thao tác admin qua Caddy, nhưng không thay thế hệ thống phân quyền Laravel dài hạn. Không bỏ lớp này trước khi có bảo vệ tương đương.

Kiểm tra và áp dụng:

```sh
sudo caddy validate --config /etc/caddy/Caddyfile
sudo systemctl enable --now caddy
sudo systemctl reload caddy
```

Nếu chưa có domain, có thể dùng site address `:80` tạm thời để kiểm tra HTTP. Khi đó đặt `APP_URL` theo IP, `SESSION_SECURE_COOKIE=false`; không nhập thông tin nhạy cảm qua HTTP công khai. Khi chuyển HTTPS, bật lại secure cookie và cache config.

## 10. Cache production và kiểm tra

Chạy bằng user deploy:

```sh
sudo -iu deploy
cd /var/www/sell_cnc
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate:status
php artisan about
```

Mở `https://example.com`, thử danh mục, chi tiết sản phẩm, admin và upload một ảnh để kiểm tra watermark/font/quyền lưu file. Kiểm tra ảnh tại `/storage/products/...` trả về thành công.

Không cần chạy `php artisan serve`, `npm run dev` hoặc `composer run dev` trên production.

## 11. Queue worker (nếu có job chạy nền)

Chỉ cần khi ứng dụng dispatch jobs vào queue. Tạo file bằng tài khoản quản trị:

```sh
sudo nano /etc/systemd/system/sell-cnc-worker.service
```

```ini
[Unit]
Description=Sell CNC Laravel queue worker
After=network.target mysql.service redis-server.service

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/sell_cnc
ExecStart=/usr/bin/php artisan queue:work redis --sleep=3 --tries=3 --timeout=60 --max-time=3600
Restart=always
RestartSec=5
TimeoutStopSec=90

[Install]
WantedBy=multi-user.target
```

```sh
sudo systemctl daemon-reload
sudo systemctl enable --now sell-cnc-worker
sudo systemctl status sell-cnc-worker --no-pager
```

Nếu thay timeout worker, đảm bảo `retry_after` trong cấu hình queue lớn hơn timeout để tránh job bị xử lý trùng.

## 12. Scheduler (nếu có tác vụ định kỳ)

```sh
sudo crontab -u www-data -e
```

Thêm:

```cron
* * * * * cd /var/www/sell_cnc && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

## 13. Quy trình cập nhật source

Trước deploy: backup database, `.env` và `storage/app/public` ra nơi lưu trữ an toàn ngoài VPS. Giữ lịch backup và thử khôi phục định kỳ. Các migration có thể thay đổi dữ liệu; đọc thay đổi trước khi chạy.

Ví dụ deploy đơn giản có maintenance mode, chạy bằng **user deploy**:

```sh
cd /var/www/sell_cnc
php artisan down
# Nếu một bước thất bại, dừng lại xử lý, không tiếp tục bật ứng dụng.
git pull --ff-only
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
composer check-platform-reqs --no-dev
npm ci
npm run build
rm -f public/hot
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Từ tài khoản quản trị, reload PHP để nhận code/OPcache mới; restart worker nếu đã cài:

```sh
sudo systemctl reload php8.3-fpm
sudo systemctl restart sell-cnc-worker
```

Sau khi mọi bước thành công, quay lại user deploy:

```sh
cd /var/www/sell_cnc
php artisan up
```

Không copy đè `.env`, không tạo lại `APP_KEY`, không xóa upload khi deploy. Chỉ reload Caddy khi Caddyfile thay đổi. Không cần chạy `optimize:clear` thường xuyên trên production vì lệnh này có thể xóa cache ứng dụng; các lệnh cache riêng ở trên đủ để cập nhật cấu hình/routes/views.

## 14. Chẩn đoán lỗi

```sh
sudo systemctl status caddy php8.3-fpm mysql redis-server --no-pager
sudo journalctl -u caddy -n 100 --no-pager
sudo journalctl -u php8.3-fpm -n 100 --no-pager
sudo tail -n 100 /var/www/sell_cnc/storage/logs/laravel.log
redis-cli ping
```

| Lỗi | Kiểm tra |
| --- | --- |
| 502 Bad Gateway | PHP-FPM đang chạy, đúng socket, ACL cho user caddy |
| 500 Laravel | Laravel log, APP_KEY, DB, extension PHP, quyền storage/cache |
| Upload lỗi watermark | GD WebP/FreeType, font DejaVu, logo PNG, quyền ghi storage |
| Ảnh `/storage` trả 403/404 | storage link đúng đường dẫn, file tồn tại, quyền đọc/traverse của Caddy |
| Upload quá lớn/413 | upload_max_filesize, post_max_size, validation Laravel |
| CSS/JS không tải | npm run build, manifest, xóa public/hot, APP_URL |
| Không cấp được SSL | DNS A/AAAA, port 80/443, dịch vụ khác chiếm port |
| Composer báo sai phiên bản PHP | Kiểm tra composer.lock; đồng bộ PHP CLI/FPM, không bỏ qua platform requirements |

> Tài liệu là quy trình triển khai tham khảo, chưa được thực thi trên VPS của bạn. Sau cài đặt cần kiểm tra thực tế DNS, HTTPS, quyền truy cập và upload trước khi đưa vào sử dụng.
