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
