# Deploy Duy Hoàng - Kho mẫu CNC trên Ubuntu 24.04

Hướng dẫn cài trực tiếp trên VPS, **không sử dụng Docker**, đăng nhập bằng **root**. Chạy lần lượt từng bước; nếu lệnh báo lỗi, dừng và xử lý trước khi tiếp tục. Các giá trị domain, repository và mật khẩu mẫu phải được thay bằng giá trị thật.

| Service trong Compose | Trên VPS |
| --- | --- |
| app | PHP 8.4-FPM (≥ 8.4.1) + Composer 2 |
| caddy | Caddy với HTTPS tự động |
| mysql | MySQL 8.0 |
| redis | Redis 7 |
| Build giao diện | Node.js 22 + npm |

## 1. Chuẩn bị

Ví dụ trong tài liệu sử dụng:

- Domain: `example.com` và `www.example.com` — thay bằng domain thật.
- Source: `/var/www/sell_cnc`.
- User triển khai: `root`; không cần tạo user `deploy`.
- Database và user MySQL: `sell_cnc`.
- Repository: `GIT_REPOSITORY_URL` — thay bằng URL repository thật.

Các lệnh shell bên dưới chạy trong phiên root. PHP-FPM xử lý request và queue worker chạy bằng `www-data`; Caddy chạy bằng `caddy`. Không đổi user của các dịch vụ này thành root. Composer và Node/npm chỉ phục vụ cài dependency/build, không phải dịch vụ chạy thường trực.

Giả định VPS Ubuntu 24.04 mới, chưa có website khác cần giữ cấu hình PHP/Caddy. Trước khi cài trên VPS đang sử dụng, kiểm tra dịch vụ và port hiện có.

> **Quản trị:** nhóm route `/admin` đã được bảo vệ bằng đăng nhập và middleware kiểm tra quyền quản trị trong Laravel. Trang đăng nhập là `/admin/signin`, không có đăng ký. Chạy migration và `AdminUserSeeder` ở mục 9 trước khi sử dụng; tài khoản cũ không tự được cấp quyền quản trị.

### Cập nhật hệ điều hành

```sh
apt update
apt upgrade -y
apt install -y git unzip curl ca-certificates gnupg ufw acl nano less software-properties-common
```

### Firewall

Nếu SSH dùng port tùy chỉnh, mở đúng port đó trước khi bật firewall. Không đóng phiên SSH hiện tại cho tới khi thử kết nối mới thành công.

```sh
ufw allow OpenSSH
ufw allow 80/tcp
ufw allow 443/tcp
ufw enable
ufw status
```

Mở tương ứng port 80/443 trong firewall của nhà cung cấp VPS. **Không mở MySQL 3306 hay Redis 6379 ra Internet.**

### Thư mục source

```sh
umask 0027
install -d -o root -g www-data -m 2751 /var/www/sell_cnc
```

Dùng `umask 0027` trong mỗi phiên root triển khai để file mới không mặc định cho mọi user đọc. Nếu repository private, cấu hình SSH deploy key chỉ đọc cho root; không đặt token trong source hoặc lệnh có thể lưu vào shell history.

## 2. Cài PHP-FPM và font watermark

`composer.lock` hiện tại có dependency production Symfony yêu cầu **PHP ≥ 8.4.1**; Docker cũng dùng nhánh PHP 8.4. Hướng dẫn này dùng bản vá mới nhất của PHP 8.4 cho cả CLI và FPM. **Không dùng `--ignore-platform-reqs` hoặc `composer update` để né yêu cầu phiên bản.**

Ubuntu 24.04 mặc định cung cấp PHP 8.3, nên cần thêm repository bên thứ ba [PPA Ondřej PHP](https://launchpad.net/~ondrej/+archive/ubuntu/php):

```sh
add-apt-repository -y ppa:ondrej/php
apt update
```

```sh
apt install -y \
  php8.4-cli php8.4-fpm php8.4-mysql php8.4-redis \
  php8.4-gd php8.4-zip php8.4-mbstring php8.4-xml \
  php8.4-curl php8.4-bcmath php8.4-intl php8.4-opcache \
  fonts-dejavu-core
update-alternatives --set php /usr/bin/php8.4
systemctl enable --now php8.4-fpm
php -v
php-fpm8.4 -v
php -r 'exit(version_compare(PHP_VERSION, "8.4.1", ">=") ? 0 : 1);'
php -m
php -r 'print_r(gd_info());'
```

GD cần hỗ trợ JPEG, PNG, WebP và FreeType. Font mặc định watermark:

```text
/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf
```

Chỉnh cấu hình PHP-FPM:

```sh
nano /etc/php/8.4/fpm/php.ini
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
php-fpm8.4 -t
systemctl restart php8.4-fpm
```

## 3. Composer và Node.js

### Composer

Cách đơn giản, dùng package Ubuntu:

```sh
apt install -y --no-install-recommends composer
update-alternatives --set php /usr/bin/php8.4
COMPOSER_ALLOW_SUPERUSER=1 composer --version
php -v
```

Theo lựa chọn triển khai bằng root, đặt `COMPOSER_ALLOW_SUPERUSER=1` trực tiếp trước mỗi lệnh Composer. Script/plugin dependency sẽ có quyền root, vì vậy chỉ cài dependency từ repository/lockfile tin cậy. Không dùng `--no-scripts` vì Laravel cần bước `package:discover`. Xem [hướng dẫn Composer về root](https://getcomposer.org/doc/faqs/how-to-install-untrusted-packages-safely.md).

### Node.js 22

Cài bản vá mới nhất của Node.js 22 từ NodeSource, không cố định ở 22.12: Vite 7 yêu cầu tối thiểu 22.12, nhưng dependency khác trong lockfile có thể cần bản mới hơn. Bước sau thêm repository bên thứ ba NodeSource. Tải và kiểm tra script trước khi thực thi với quyền quản trị:

```sh
curl -fsSL https://deb.nodesource.com/setup_22.x -o /tmp/nodesource-setup.sh
less /tmp/nodesource-setup.sh
bash /tmp/nodesource-setup.sh
apt install -y nodejs
rm /tmp/nodesource-setup.sh
node --version
npm --version
```

Node chỉ cần để build asset, không cần chạy `npm run dev` trên production.

## 4. MySQL

```sh
apt install -y mysql-server
systemctl enable --now mysql
mysql_secure_installation
```

### Các câu hỏi khi chạy `mysql_secure_installation`

| Câu hỏi | Lựa chọn |
| --- | --- |
| Setup VALIDATE PASSWORD component? | `y` |
| Password validation policy: 0 / 1 / 2? | `1` — MEDIUM |
| Remove anonymous users? | `y` |
| Disallow root login remotely? | `y` — chặn MySQL root từ xa, không ảnh hưởng SSH root |
| Remove test database and access to it? | `y` |
| Reload privilege tables now? | `y` |

MEDIUM mặc định yêu cầu ít nhất 8 ký tự, có chữ hoa, chữ thường, số và ký tự đặc biệt. Nên tạo mật khẩu ngẫu nhiên từ 20 ký tự cho user ứng dụng. Xem [chính sách mật khẩu MySQL](https://dev.mysql.com/doc/refman/8.0/en/validate-password.html).

### Tạo database và user ứng dụng

Sau khi hoàn tất, từ phiên root trên VPS chạy:

```sh
mysql
```

Trong MySQL, thay mật khẩu mẫu bằng mật khẩu mạnh, riêng cho production:

```sql
CREATE DATABASE sell_cnc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'sell_cnc'@'localhost' IDENTIFIED BY 'REPLACE_WITH_A_STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON sell_cnc.* TO 'sell_cnc'@'localhost';
SHOW GRANTS FOR 'sell_cnc'@'localhost';
EXIT;
```

Thực hiện từng lệnh và chỉ tiếp tục khi thành công. Lưu mật khẩu vừa đặt để dùng cho `DB_PASSWORD` trong `.env` và phần mềm kết nối database; đây là mật khẩu MySQL, riêng với mật khẩu SSH/passphrase của key.

Nếu `CREATE USER` báo `ERROR 1819`, mật khẩu chưa đạt chính sách: chọn mật khẩu mới đủ các nhóm ký tự rồi chạy lại `CREATE USER`. Nếu tiếp tục `GRANT` khi user chưa được tạo, có thể gặp `ERROR 1410`; tạo user thành công trước rồi chạy lại `GRANT`. Không cần tạo lại database nếu `CREATE DATABASE` đã thành công.

Kiểm tra `/etc/mysql/mysql.conf.d/mysqld.cnf` có:

```ini
bind-address = 127.0.0.1
```

Nếu sửa cấu hình:

```sh
systemctl restart mysql
```

### Đăng nhập MySQL trực tiếp trên VPS

Trong phiên root, lệnh `mysql` ở trên dùng để quản trị MySQL theo cấu hình xác thực local mặc định của Ubuntu. Để kiểm tra tài khoản ứng dụng qua TCP:

```sh
mysql -h 127.0.0.1 -P 3306 -u sell_cnc -p sell_cnc
```

Nhập mật khẩu của user `sell_cnc` khi được hỏi; không ghi mật khẩu trực tiếp vào lệnh. Khi thấy dấu nhắc `mysql>`, đã đăng nhập thành công. Gõ `EXIT;` rồi Enter để thoát.

### Kết nối từ máy local qua SSH tunnel

Trong phần mềm quản lý database, bật **Use SSH tunnel**; MySQL Workbench gọi chế độ này là **Standard TCP/IP over SSH**. Giữ `bind-address = 127.0.0.1`, không cần mở port `3306` trên firewall hay tạo user MySQL với host `%`. Xem [hướng dẫn kết nối qua SSH của MySQL](https://dev.mysql.com/doc/workbench/en/wb-mysql-connections-methods-ssh.html).

Điền thông tin SSH:

| Trường | Giá trị |
| --- | --- |
| SSH Host | IP public của VPS |
| SSH Port | `22`, hoặc port SSH thực tế đang sử dụng |
| SSH User | `root` |
| Authentication | Public Key / Private Key, tùy tên trong phần mềm |
| SSH Private Key | File private key trên máy local, thường là `~/.ssh/id_ed25519` |
| SSH Key Passphrase | Mật khẩu bảo vệ key đã đặt lúc tạo; để trống nếu không đặt |

Nếu đã dùng `ssh-keygen -t ed25519 -C "vultr-vps"` và giữ đường dẫn mặc định:

- **Private key:** `~/.ssh/id_ed25519` — chọn file này trong phần mềm trên máy local.
- **Public key:** `~/.ssh/id_ed25519.pub` — nội dung file này được thêm vào `~/.ssh/authorized_keys` trên VPS; không chọn nó làm private key.
- Trên Windows, private key thường ở `C:\Users\<ten-user>\.ssh\id_ed25519`. Nếu đã chọn tên/đường dẫn khác khi tạo key, dùng đúng file đó.

Không copy private key lên VPS hoặc chia sẻ nội dung key. Khi dùng key, không cần nhập SSH Password; trường Key Passphrase chỉ dùng để mở khóa private key nếu có.

Điền thông tin MySQL trong cùng kết nối:

| Trường | Giá trị |
| --- | --- |
| MySQL Host | `127.0.0.1` — địa chỉ MySQL nhìn từ VPS qua tunnel |
| MySQL Port | `3306` |
| MySQL User | `sell_cnc` |
| MySQL Password | Mật khẩu đã đặt khi `CREATE USER` thành công |
| Database / Default Schema | `sell_cnc` |

Bấm **Test Connection** để kiểm tra. SSH key xác thực việc truy cập VPS; mật khẩu MySQL xác thực user database. Không lấy được mật khẩu MySQL từ SSH key; nếu quên, cần đặt lại bằng tài khoản quản trị MySQL.

### Lỗi `Public Key Retrieval is not allowed`

Lỗi này thuộc bước xác thực MySQL: driver chưa cho phép lấy public key của MySQL để trao đổi mật khẩu. Key này khác với SSH key `id_ed25519`.

Với **DBeaver**, khi đang dùng SSH tunnel tới VPS của bạn:

1. Chuột phải kết nối → **Edit Connection** → **Driver properties**.
2. Tìm `allowPublicKeyRetrieval`, đổi thành `true`.
3. Giữ SSH tunnel bật, MySQL Host `127.0.0.1`, port `3306`.
4. Lưu và bấm **Test Connection** lại.

Không cần đổi SSH key, tắt SSL hoặc đổi phương thức xác thực của user MySQL để xử lý lỗi này. Hướng dẫn bật tùy chọn trên áp dụng cho kết nối qua SSH tunnel đã xác thực tới VPS, không áp dụng tùy tiện cho kết nối trực tiếp qua Internet. Xem [tùy chọn bảo mật của MySQL Connector/J](https://dev.mysql.com/doc/connector-j/en/connector-j-connp-props-security.html).

## 5. Redis

```sh
apt install -y redis-server
systemctl enable --now redis-server
redis-cli ping
```

Kết quả mong đợi: `PONG`. Kiểm tra `/etc/redis/redis.conf`:

```conf
bind 127.0.0.1 -::1
protected-mode yes
```

Nếu sửa cấu hình:

```sh
systemctl restart redis-server
```

## 6. Cài Caddy và quyền kết nối PHP-FPM

```sh
apt install -y debian-keyring debian-archive-keyring apt-transport-https
curl -1sLf https://dl.cloudsmith.io/public/caddy/stable/gpg.key | gpg --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg
curl -1sLf https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt | tee /etc/apt/sources.list.d/caddy-stable.list
chmod a+r /usr/share/keyrings/caddy-stable-archive-keyring.gpg /etc/apt/sources.list.d/caddy-stable.list
apt update
apt install -y caddy
systemctl stop caddy
```

Giữ Caddy dừng trong lúc chuẩn bị source; chỉ bật site sau khi có cache production và cấu hình bảo vệ admin.

Để Caddy kết nối socket mà không cấp quyền đọc `.env` thông qua group `www-data`, dùng ACL của PHP-FPM.

```sh
nano /etc/php/8.4/fpm/pool.d/www.conf
```

Đảm bảo các giá trị sau được bật (không bị comment và không khai báo trùng):

```ini
user = www-data
group = www-data
listen = /run/php/php8.4-fpm.sock
listen.acl_users = caddy
```

```sh
php-fpm8.4 -t
systemctl restart php8.4-fpm
getfacl /run/php/php8.4-fpm.sock
```

ACL socket phải cho `caddy` quyền đọc/ghi. Không dùng `chmod 777` cho socket hoặc source.

## 7. Đưa source lên VPS

Chạy bằng **root**; thư mục phải trống khi clone lần đầu:

```sh
umask 0027
cd /var/www/sell_cnc
git clone GIT_REPOSITORY_URL .
```

Nếu chuyển source bằng SFTP/ZIP, đặt source trực tiếp vào `/var/www/sell_cnc`, không lồng thêm thư mục. Không upload `node_modules`, `vendor`, `.env` local, cache sinh sẵn trong `bootstrap/cache` hoặc `public/hot`; cài dependency và tạo `.env` riêng trên server.

```sh
rm -f public/hot
cp -n .env.example .env
chown root:www-data .env
chmod 640 .env
nano .env
```

Chỉ tạo `.env` khi cài mới; không ghi đè `.env` production đang có. `.env.example` chứa `APP_KEY` mẫu: xóa giá trị đó khi tạo database mới, hoặc dùng `APP_KEY` cũ khi chuyển dữ liệu. Ví dụ cấu hình (thay domain/password):

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

**Nếu chưa có domain và chạy bằng IP với port 8080**, thay các giá trị tương ứng trong `.env` (không thêm dòng trùng):

```dotenv
APP_URL=http://IP_VPS:8080
SESSION_SECURE_COOKIE=false
SESSION_DOMAIN=null
```

Thay `IP_VPS` bằng IP public thật. `APP_URL` khai báo URL ứng dụng, không tự mở port; chọn Caddyfile HTTP `:8080` ở mục 11. Các cấu hình database/Redis vẫn dùng `127.0.0.1`. Nếu đang cài lần đầu, tiếp tục mục 8–10; nếu ứng dụng đã cài dependency và cache cấu hình, chạy lại `php artisan config:cache` sau khi sửa `.env`.

Không dùng hostname `mysql`, `redis`, `app` của Docker. Dùng port local MySQL `3306`, không phải port host Docker `3308`.

Không đặt `IMAGE_WATERMARK_LOGO` thành đường dẫn macOS hoặc `/var/www/html/...`. Có thể bỏ biến này để dùng mặc định trong `config/images.php`; đảm bảo file `public/assets/images/logo-duy-hoang-gold-brown.png` được đưa lên cùng source.

## 8. Quyền truy cập source và runtime

Thực hiện trước khi cài Composer (có script Artisan), khởi tạo database hoặc cache. Chạy lại phần này sau khi import upload cũ, hoặc sau khi cài dependency/build ở lần cập nhật.

```sh
cd /var/www/sell_cnc
mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R root:www-data /var/www/sell_cnc
chmod -R u=rwX,g=rX,o= /var/www/sell_cnc
find /var/www/sell_cnc -type d -exec chmod g+s {} \;
chmod 2751 /var/www/sell_cnc
chmod 640 .env
```

`X` giữ quyền thực thi của executable đã có; không đặt toàn bộ file thành `644`. Bit setgid trên thư mục giúp file mới tiếp tục thuộc group `www-data`. PHP-FPM chỉ có quyền đọc source, quyền ghi giới hạn ở runtime:

```sh
setfacl -R -m u:www-data:rwX,g::rwX,o::--- storage bootstrap/cache
find storage bootstrap/cache -type d -exec setfacl -m d:u::rwx,d:u:www-data:rwx,d:g::rwx,d:m::rwx,d:o::--- {} \;
```

Root không cần ACL riêng. Caddy cần đọc asset trong `public` và ảnh upload qua symlink `public/storage`; cấp ACL hiện tại và kế thừa cho file được build/upload sau này:

```sh
setfacl -R -m u:caddy:r-X public storage/app/public
find public storage/app/public -type d -exec setfacl -m d:u::rwx,d:u:caddy:r-x,d:g::r-x,d:m::r-x,d:o::--- {} \;
# Giữ quyền ghi runtime kế thừa cho PHP-FPM trong upload public.
find storage/app/public -type d -exec setfacl -m d:u:www-data:rwx,d:g::rwx,d:m::rwx {} \;
setfacl -m u:caddy:--x storage storage/app
```

Không thêm `caddy` vào group `www-data`. Caddy chỉ được traverse thư mục gốc source và storage, không đọc `.env`, cache cấu hình hoặc storage private. Không đặt database dump hay ảnh gốc private trong `public`/`storage/app/public`. Các lệnh đệ quy trên không đi theo symlink bên trong cây thư mục; không dùng tùy chọn theo symlink.

## 9. Cài dependency và chuẩn bị dữ liệu

```sh
cd /var/www/sell_cnc
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
COMPOSER_ALLOW_SUPERUSER=1 composer check-platform-reqs --no-dev
npm ci
npm run build
rm -f public/hot
```

Giữ devDependencies của npm trong lúc build vì Vite nằm ở nhóm này; không dùng `npm ci --omit=dev`. Nếu bước kiểm tra PHP/Node báo lỗi, xử lý phiên bản/extension trước khi tiếp tục.

Chạy lại **toàn bộ mục 8** sau khi cài dependency/build để chuẩn hóa quyền của file được giải nén hoặc tạo mới, rồi mới tiếp tục với dữ liệu.

Chọn **một** trong hai trường hợp dữ liệu bên dưới.

### Database mới

```sh
php artisan key:generate --force
php artisan migrate --force
php artisan storage:link
```

### Chuyển dữ liệu hiện có

1. Import bản dump MySQL vào database `sell_cnc` bằng công cụ MySQL, lưu dump ngoài thư mục public.
2. Chuyển toàn bộ `storage/app/public` chứa ảnh đã watermark.
3. Đặt `APP_KEY` cũ vào `.env` để giữ khả năng đọc dữ liệu mã hóa. Không chạy `key:generate` trong nhánh này.
4. Chạy lại quyền truy cập ở mục 8 sau khi chuyển file, rồi chạy:

```sh
php artisan migrate --force
php artisan storage:link
```

Nếu `public/storage` là symlink copy từ máy khác trỏ sai đường dẫn, kiểm tra và xóa **chỉ symlink đó** trước khi chạy lại `storage:link`; không xóa thư mục upload thật. Không chạy `migrate:fresh`, `db:wipe` hoặc xóa database để deploy.

### Tạo tài khoản quản trị (cả database mới và database đã có)

Sau khi chạy migration, tạo tài khoản bằng seeder riêng:

```sh
php artisan db:seed --class=AdminUserSeeder --force
```

Mở `/admin/signin` (hoặc `/admin`, ứng dụng sẽ chuyển tới trang đăng nhập). Tài khoản mẫu:

- Tên đăng nhập: `duyhoangadmin`.
- Mật khẩu: `duyhoang88@!`.

Seeder lưu mật khẩu dưới dạng hash, chỉ tạo tài khoản chưa tồn tại và không đặt lại mật khẩu/quyền của tài khoản đã có. Nếu muốn dùng mật khẩu khác ngay từ đầu, sửa giá trị trong `database/seeders/AdminUserSeeder.php` trước lần chạy đầu tiên. Muốn thêm tài khoản, thêm phần tử vào mảng `$accounts` với `username` viết thường, duy nhất, `name` và `password`, rồi chạy lại lệnh trên. Email `username@admin.invalid` chỉ là giá trị nội bộ để tương thích bảng users, không dùng đăng nhập hay gửi mail.

Không chạy `php artisan db:seed` không có `--class` trên dữ liệu thật: `DatabaseSeeder` còn chứa danh mục và sản phẩm demo. Không cần đăng ký tài khoản qua giao diện. Nút **Đăng xuất** có trong menu tài khoản và thanh bên của admin; sau khi đăng xuất, các trang/thao tác admin yêu cầu đăng nhập lại. Nhập sai 5 lần cho cùng tên tài khoản và IP sẽ tạm chặn đăng nhập trong 60 giây.

Khi cập nhật VPS đã có source cũ, chạy `php artisan migrate --force` và seeder riêng trên, sau đó tạo lại cache theo mục 10. Không tạo lại `APP_KEY` và không xóa database.

## 10. Cache production và kiểm tra quyền

```sh
cd /var/www/sell_cnc
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate:status
php artisan about
```

Kiểm tra bằng tài khoản dịch vụ, không chỉ bằng root. Mỗi lệnh `test` dưới đây phải có exit code `0`; dùng `echo $?` ngay sau lệnh để kiểm tra khi cần:

```sh
runuser -u www-data -- test -r /var/www/sell_cnc/.env
runuser -u www-data -- test -w /var/www/sell_cnc/storage/framework/views
runuser -u www-data -- test -w /var/www/sell_cnc/bootstrap/cache
runuser -u www-data -- test ! -w /var/www/sell_cnc/routes/web.php
runuser -u caddy -- test -r /var/www/sell_cnc/public/build/manifest.json
runuser -u caddy -- test -r /var/www/sell_cnc/public/assets/images/logo-duy-hoang-gold-brown.png
runuser -u caddy -- test -w /run/php/php8.4-fpm.sock
runuser -u caddy -- test ! -r /var/www/sell_cnc/.env
runuser -u caddy -- test ! -r /var/www/sell_cnc/bootstrap/cache/config.php
runuser -u caddy -- test ! -r /var/www/sell_cnc/storage/logs/laravel.log
```

Không cần chạy `php artisan serve`, `npm run dev` hoặc `composer run dev` trên production.

## 11. Caddyfile: domain HTTPS hoặc IP HTTP

Chọn **một** cấu hình: domain với HTTPS, hoặc IP với HTTP trên port `8080` khi chưa có domain. Không cần cấu hình DNS cho phương án IP.

Laravel xử lý đăng nhập và quyền quản trị; Caddyfile bên dưới không dùng Basic Auth. Sửa file:

```sh
nano /etc/caddy/Caddyfile
```

### Có domain: HTTPS trên port 80/443

Tạo bản ghi DNS `A` của domain trỏ về IP VPS. Chỉ thêm `www` vào cấu hình nếu đã tạo DNS cho nó. Nếu có bản ghi `AAAA`, địa chỉ IPv6 phải trỏ đúng server. Dùng `APP_URL=https://example.com` và `SESSION_SECURE_COOKIE=true` trong `.env`.

```caddy
example.com, www.example.com {
    root * /var/www/sell_cnc/public
    encode zstd gzip

    php_fastcgi unix//run/php/php8.4-fpm.sock
    file_server

    log
}
```

Phương án domain dùng port tiêu chuẩn **80/443**, không cần `:83` như Docker local. Root bắt buộc là thư mục `public`, không phải gốc source.

### Chưa có domain: IP và HTTP trên port 8080

Dùng `.env` với `APP_URL=http://IP_VPS:8080`, `SESSION_SECURE_COOKIE=false`, `SESSION_DOMAIN=null` như mục 7. Thay nội dung Caddyfile bằng cấu hình sau:

```caddy
:8080 {
    root * /var/www/sell_cnc/public
    encode zstd gzip

    php_fastcgi unix//run/php/php8.4-fpm.sock
    file_server

    log
}
```

Địa chỉ `:8080` cho Caddy phục vụ HTTP trên port 8080, không cần điền IP vào Caddyfile. Xem [quy tắc địa chỉ của Caddy](https://caddyserver.com/docs/caddyfile/concepts#addresses). Nếu chọn port khác, đổi đồng bộ port trong `.env`, Caddyfile, firewall và URL truy cập.

Mở port trên VPS:

```sh
ufw allow 8080/tcp
ufw status
```

Nếu dùng Vultr Firewall, mở thêm TCP `8080` trong firewall gắn với VPS. Phương án HTTP dùng để kiểm tra giao diện; không nhập mật khẩu admin hoặc thông tin nhạy cảm qua HTTP công khai, vì HTTP không mã hóa đường truyền.

### Kiểm tra và áp dụng cấu hình đã chọn

Kiểm tra và áp dụng:

```sh
chown root:caddy /etc/caddy/Caddyfile
chmod 640 /etc/caddy/Caddyfile
runuser -u caddy -- test -r /etc/caddy/Caddyfile
caddy validate --config /etc/caddy/Caddyfile
systemctl enable --now caddy
systemctl reload caddy
```

### Kiểm tra site bằng IP

Thay `IP_VPS` bằng IP public thật, mở `http://IP_VPS:8080` trong trình duyệt hoặc kiểm tra từ máy local:

```sh
curl -I http://IP_VPS:8080/
curl -I http://IP_VPS:8080/admin
curl -I http://IP_VPS:8080/.env
```

Trang chủ cần trả `200`, admin chưa đăng nhập trả `302` chuyển tới `/admin/signin`, `/.env` trả `404`. Nếu trên VPS chạy `curl -I http://127.0.0.1:8080/` thành công nhưng từ local bị timeout, kiểm tra UFW và Vultr Firewall. Không chạy bước đăng nhập admin qua HTTP công khai.

### Chuyển từ IP sang domain HTTPS

Khi có domain, cấu hình DNS và thay site block `:8080` bằng cấu hình domain ở trên. Đổi `.env` sang `APP_URL=https://DOMAIN_THAT`, `SESSION_SECURE_COOKIE=true`, giữ `SESSION_DOMAIN=null`. Mở TCP 80/443 trên cả UFW và firewall nhà cung cấp, rồi chạy:

```sh
cd /var/www/sell_cnc
php artisan config:cache
caddy validate --config /etc/caddy/Caddyfile
systemctl reload caddy
```

Sau khi HTTPS hoạt động, nếu port 8080 chỉ dùng cho site này, đóng rule tạm:

```sh
ufw delete allow 8080/tcp
```

Xóa rule TCP 8080 tương ứng trong Vultr Firewall nếu đã thêm. Dùng các bước kiểm tra HTTPS bên dưới; bỏ qua chúng khi vẫn đang chạy bằng IP HTTP.

### Kiểm tra site sau khi bật HTTPS

```sh
curl -I https://example.com/
curl -I https://example.com/admin
curl -I https://example.com/admin/inventory
curl -I https://example.com/.env
```

Trang chủ và `/admin/signin` cần trả `200`; admin chưa đăng nhập phải trả `302` chuyển tới `/admin/signin`; `/.env` phải trả `404`. Đăng nhập trong trình duyệt bằng tài khoản đã seed, kiểm tra `/admin` trả `200`, rồi đăng xuất và thử truy cập lại để xác nhận được chuyển về trang đăng nhập.

Mở trình duyệt thử danh mục, chi tiết sản phẩm, đăng nhập quản trị, thêm/sửa sản phẩm và upload ảnh. Kiểm tra watermark/font đúng, ảnh tại `/storage/products/...` trả `200`, CSS/JS tải được. Thử upload ảnh vượt giới hạn ứng dụng 5 MB để kiểm tra thông báo validation. Thay đường dẫn ảnh mẫu bằng URL upload thật khi kiểm tra.

## 12. Queue worker (nếu có job chạy nền)

Source hiện tại chưa thấy dispatch job vào queue, nên có thể bỏ qua mục này. Khi bổ sung job chạy nền, tạo service bằng root:

```sh
nano /etc/systemd/system/sell-cnc-worker.service
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
ExecStart=/usr/bin/php8.4 artisan queue:work redis --sleep=3 --tries=3 --timeout=60 --max-time=3600
Restart=always
RestartSec=5
TimeoutStopSec=90

[Install]
WantedBy=multi-user.target
```

```sh
systemctl daemon-reload
systemctl enable --now sell-cnc-worker
systemctl status sell-cnc-worker --no-pager
```

Nếu thay timeout worker, đảm bảo `retry_after` trong cấu hình queue lớn hơn timeout để tránh job bị xử lý trùng.

## 13. Scheduler (nếu có tác vụ định kỳ)

Source hiện tại chưa đăng ký lịch tác vụ, nên có thể bỏ qua mục này. Khi có lịch, dùng crontab của `www-data`:

```sh
crontab -u www-data -e
```

Thêm:

```cron
* * * * * cd /var/www/sell_cnc && /usr/bin/php8.4 artisan schedule:run >> /dev/null 2>&1
```

## 14. Quy trình cập nhật source

Trước deploy: backup database, `.env` và `storage/app/public` ra nơi lưu trữ an toàn ngoài VPS. Giữ lịch backup và thử khôi phục định kỳ. Các migration có thể thay đổi dữ liệu; đọc thay đổi trước khi chạy.

Ví dụ deploy đơn giản có maintenance mode, chạy bằng **root**. Thực hiện từng khối, dừng khi có lỗi; giữ maintenance mode tới khi xử lý xong:

```sh
umask 0027
cd /var/www/sell_cnc
php artisan down
# Nếu một bước thất bại, dừng lại xử lý, không tiếp tục bật ứng dụng.
git pull --ff-only
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
COMPOSER_ALLOW_SUPERUSER=1 composer check-platform-reqs --no-dev
npm ci
npm run build
rm -f public/hot
```

Chạy lại **toàn bộ mục 8** để chuẩn hóa quyền file mới, rồi tiếp tục:

```sh
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Chạy lại các kiểm tra quyền ở mục 10, rồi reload PHP để nhận code/OPcache mới:

```sh
systemctl reload php8.4-fpm
```

**Chỉ chạy lệnh sau nếu đã cài queue worker ở mục 12:**

```sh
systemctl restart sell-cnc-worker
```

Sau khi mọi bước thành công, bật lại ứng dụng và kiểm tra site theo mục 11:

```sh
cd /var/www/sell_cnc
php artisan up
```

Không copy đè `.env`, không tạo lại `APP_KEY`, không xóa upload khi deploy. Chỉ reload Caddy khi Caddyfile thay đổi. Không cần chạy `optimize:clear` thường xuyên trên production vì lệnh này có thể xóa cache ứng dụng; các lệnh cache riêng ở trên đủ để cập nhật cấu hình/routes/views.

## 15. Chẩn đoán lỗi

```sh
systemctl status caddy php8.4-fpm mysql redis-server --no-pager
journalctl -u caddy -n 100 --no-pager
journalctl -u php8.4-fpm -n 100 --no-pager
tail -n 100 /var/www/sell_cnc/storage/logs/laravel.log
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
