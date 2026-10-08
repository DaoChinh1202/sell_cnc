<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

# sell_cnc

Ứng dụng Laravel quản lý bán hàng và tồn kho, chạy cục bộ bằng Docker Compose.

## Yêu cầu

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) đang chạy.
- Node.js và npm trên máy host (dùng để build asset Vite).

## Khởi tạo dự án lần đầu

Chạy các lệnh sau tại thư mục gốc của dự án:

```sh
# 1. Tạo file cấu hình môi trường (chỉ chạy khi chưa có .env)
cp .env.example .env

# 2. Build image và khởi động PHP, Caddy, MySQL, Redis
#    Không cần chạy `php artisan serve`.
docker compose up -d --build

# 3. Cài thư viện PHP trong container
docker compose exec app composer install

# 4. Tạo APP_KEY nếu .env chưa có khóa
docker compose exec app php artisan key:generate

# 5. Tạo/cập nhật các bảng database
docker compose exec app php artisan migrate

# 6. Tạo liên kết để Caddy phục vụ ảnh sản phẩm đã watermark
docker compose exec app php artisan storage:link

# 7. Cài và build CSS/JavaScript trên máy host (không chạy trong container app)
npm ci
npm run build

# 7. Xóa cache Laravel
docker compose exec app php artisan optimize:clear
```

Mỗi lần container `app` khởi động, Compose tự tạo (nếu thiếu) và gán quyền sở hữu
`storage` cùng `bootstrap/cache` cho `www-data`, nên PHP-FPM có thể ghi vào các
thư mục bind-mount này. Có thể chạy lại `docker compose up -d --build` an toàn.

Mở ứng dụng tại:

- Trang chủ: [http://localhost:83](http://localhost:83)
- Quản trị: [http://localhost:83/admin](http://localhost:83/admin)

## Sau khi `git pull`

```sh
# Khởi động lại các service và build lại image khi Dockerfile thay đổi
docker compose up -d --build

# Cập nhật dependency nếu composer.lock hoặc package-lock.json có thay đổi
docker compose exec app composer install
npm ci

# Áp dụng migration mới, tạo storage link nếu chưa có, build asset mới, và xóa cache
docker compose exec app php artisan migrate
docker compose exec app php artisan storage:link
npm run build
docker compose exec app php artisan optimize:clear
```

## Quên mật khẩu quản trị

Trang đăng nhập có liên kết **Quên mật khẩu?**. Nhập tên đăng nhập để nhận liên kết qua email đã lưu của tài khoản admin; mở liên kết và nhập mật khẩu mới hai lần. Liên kết hết hạn sau 60 phút và chỉ dùng được một lần. Tính năng không tạo tài khoản mới.

Email mặc định từ seeder (`username@admin.invalid`) không nhận được thư. Gắn email thật cho tài khoản đã có bằng lệnh sau (thay `your-email@example.com` bằng email bạn kiểm soát):

```sh
docker compose exec app php artisan admin:set-email duyhoangadmin your-email@example.com
```

Lệnh giữ nguyên mật khẩu/quyền quản trị và hủy liên kết khôi phục cũ. Chỉ người có quyền chạy CLI trên server mới cấu hình email; form quên mật khẩu không cho đổi địa chỉ nhận thư.

Để thử ở local, đặt `APP_URL=http://localhost:83`, `MAIL_MAILER=log`, sau đó chạy `docker compose exec app php artisan config:clear`. Email được ghi vào log Laravel (`storage/logs/laravel.log` với cấu hình `single` mặc định), không gửi đến hộp thư. Mở liên kết trong nội dung email để kiểm tra luồng. Liên kết trong log có quyền đặt lại mật khẩu, không chia sẻ hoặc commit log.

Để gửi thư thật, cấu hình SMTP theo phần **Khôi phục mật khẩu quản trị qua email** trong [DEPLOY-UBUNTU.md](DEPLOY-UBUNTU.md). Không cần migration mới nếu đã chạy đủ migration trước đó; bảng `password_reset_tokens` có trong migration khởi tạo.

## Mailtrap Email Sending API

Dự án tích hợp SDK chính thức `railsware/mailtrap-php` vào Laravel Mail. Khi chọn `MAIL_MAILER=mailtrap-sdk`, cả email khôi phục mật khẩu và lệnh gửi thử đều dùng Mailtrap API. Không cần cấu hình SMTP cho chế độ này.

Sau khi pull code, cài dependency từ lock file:

```sh
docker compose exec app composer install
```

Điền vào `.env` (token tự lấy từ Mailtrap; không commit `.env`):

```dotenv
APP_URL=http://localhost:83
MAIL_MAILER=mailtrap-sdk
MAILTRAP_HOST=send.api.mailtrap.io
MAILTRAP_API_KEY="YOUR_API_TOKEN"
MAIL_FROM_ADDRESS="hello@demomailtrap.co"
MAIL_FROM_NAME="Kho mẫu 3D"
MAILTRAP_TEST_TO="chinhcn2312@gmail.com"
```

`hello@demomailtrap.co` là sender demo theo đoạn tích hợp của Mailtrap. Domain demo chỉ gửi được đến địa chỉ đăng ký tài khoản Mailtrap; nếu địa chỉ đăng ký khác, thay `MAILTRAP_TEST_TO` tương ứng. Khi dùng thật, xác minh domain gửi trong Mailtrap rồi đổi `MAIL_FROM_ADDRESS` sang địa chỉ thuộc domain đó. [Hướng dẫn domain của Mailtrap](https://docs.mailtrap.io/email-api-smtp/setup/sending-domain).

Áp dụng cấu hình và tự gửi thử:

```sh
docker compose exec app php artisan config:clear
docker compose exec app php artisan send-mail
# Hoặc chỉ định người nhận cho lần thử này:
docker compose exec app php artisan send-mail chinhcn2312@gmail.com
```

Lệnh `send-mail` luôn dùng mailer `mailtrap-sdk`, ngay cả khi mailer mặc định đang là `log`; chạy lệnh sẽ gửi email thật khi dùng host `send.api.mailtrap.io`. Kết quả thành công chỉ xác nhận Mailtrap đã tiếp nhận, cần kiểm tra hộp thư/spam hoặc Email Logs để xác nhận thư đã đến.

Để dùng **Quên mật khẩu**, gắn email nhận cho admin bằng `admin:set-email` ở phần trên. `MAILTRAP_TEST_TO` chỉ dành cho lệnh gửi thử, không tự đổi email tài khoản admin. Giữ `MAIL_MAILER=mailtrap-sdk` để thư khôi phục gửi qua API.

Nếu muốn xem thư trong **Mailtrap Sandbox** thay vì gửi ra hộp thư thật, dùng `MAILTRAP_HOST=sandbox.api.mailtrap.io`, token có quyền Sandbox và `MAILTRAP_INBOX_ID` của inbox; sau đó cập nhật lại cache cấu hình. [Tài liệu Laravel SDK chính thức](https://github.com/mailtrap/mailtrap-php/blob/main/src/Bridge/Laravel/README.md).

## Phát triển giao diện

Để Vite tự build lại asset khi sửa file, chạy trên **máy host** trong một terminal riêng:

```sh
npm run dev
```

## Lệnh Docker hữu ích

```sh
# Xem trạng thái container
docker compose ps

# Xem log Caddy và PHP/Laravel
docker compose logs --tail=100 caddy app

# Dừng toàn bộ stack
docker compose down

# Dừng stack và xóa các container thừa (hữu ích khi trùng tên container)
docker compose down --remove-orphans
```

> MySQL được lưu trong Docker volume `mysql_data`. Không chạy `docker compose down -v` trừ khi muốn xóa toàn bộ dữ liệu database và khởi tạo lại bằng `php artisan migrate`.

## Xử lý lỗi thường gặp

- **`npm: command not found` trong `laravel-app`:** npm không được cài trong PHP container. Hãy thoát container và chạy `npm run build` trên máy host.
- **`Base table or view not found`:** chạy `docker compose exec app php artisan migrate`, sau đó `docker compose exec app php artisan optimize:clear`.
- **Container name is already in use:** chạy `docker compose down --remove-orphans`, rồi `docker compose up -d --build`.
- **Watermark ảnh sản phẩm:** đặt `IMAGE_WATERMARK_TEXT="its me"` trong `.env` (mặc định đã là `its me`). Ảnh upload chỉ được lưu dưới dạng WebP đã watermark; ảnh gốc không được lưu.
