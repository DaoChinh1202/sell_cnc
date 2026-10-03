# SEO cho khomau3d

## Đã triển khai trong code

- Title theo trang; title phân trang có hậu tố `Trang N`.
- Meta description riêng từ mô tả sản phẩm/danh mục; bỏ HTML và giới hạn độ dài. Khi chưa có mô tả, dùng nội dung dự phòng theo tên mẫu/danh mục.
- Canonical dùng route thật của ứng dụng, bỏ tham số tracking; giữ `page` từ trang 2 và các tham số tìm kiếm/bộ lọc có ý nghĩa.
- Trang tìm kiếm có từ khóa, trang lọc/sắp xếp danh mục và sản phẩm thuộc danh mục không hoạt động dùng `noindex,follow`.
- Trang đăng nhập, quản trị và giao diện lỗi 404 có `noindex,nofollow`; trang không tồn tại vẫn trả HTTP 404.
- Open Graph/Twitter để chia sẻ link, dùng ảnh sản phẩm/danh mục nếu có và banner dự phòng.
- JSON-LD `Organization`, `WebSite`, `BreadcrumbList`, `Product`. Giá VND khớp giá hiển thị. Không tạo đánh giá, tồn kho, địa chỉ hoặc ảnh sản phẩm giả; không khai báo offer khi giá chưa biết.
- Breadcrumb sản phẩm liên kết tới danh mục đang hoạt động.
- `/sitemap.xml`: trang chủ, kho mẫu, danh mục hoạt động và sản phẩm công khai thuộc danh mục hoạt động. Không chứa admin, bản nháp hoặc URL tìm kiếm/lọc.
- `/robots.txt`: khai báo URL sitemap và hướng dẫn bot không crawl `/admin`. Đây không phải biện pháp bảo mật và cũng không tự xóa URL đã được Google lập chỉ mục.
- Trang chủ có H1 hiển thị và nội dung giới thiệu bằng HTML.
- Banner có các bản WebP 640, 1280 và 1983 px, dùng `srcset` để trình duyệt chọn ảnh theo màn hình/mật độ điểm ảnh. Giữ PNG gốc làm fallback; ưu tiên tải banner, không lazy-load ảnh này. Bố cục mobile vẫn hiển thị toàn ảnh.

## Cập nhật trên VPS

1. Cập nhật source và đưa bộ ảnh `public/assets/images/khomau3d-banner.png`, `public/assets/images/khomau3d-banner-*.webp` lên server.
2. Build frontend bằng `npm ci` rồi `npm run build`, hoặc upload toàn bộ `public/build/` đã build ở local.
3. Đảm bảo file tĩnh `public/robots.txt` cũ đã được xóa theo thay đổi trong Git. Nếu upload thủ công và còn file này, web server có thể phục vụ file cũ thay vì route động.
4. Đặt `APP_NAME=khomau3d` và `APP_URL` bằng URL HTTPS của tên miền chính thức trong `.env`.
5. Chạy tại thư mục source:

```sh
php artisan config:cache
php artisan route:cache
php artisan view:clear
php artisan view:cache
```

Không có migration hoặc dependency mới cho thay đổi SEO này.

URL tuyệt đối dùng bộ tạo URL của Laravel, thường lấy scheme/host của request web. Vì vậy cần cấu hình web server chuyển hướng về một tên miền HTTPS chính thức. Nếu có reverse proxy/CDN, cấu hình trusted proxies phù hợp để Laravel nhận đúng HTTPS; chỉ sửa `APP_URL` chưa đủ để chuyển hướng mọi request.

## Kiểm tra sau deploy

- Mở `/sitemap.xml`: HTTP 200, XML hợp lệ, các URL dùng đúng tên miền/HTTPS.
- Mở `/robots.txt`: HTTP 200, dòng `Sitemap` trỏ đúng URL.
- Xem source HTML trang chủ, danh mục, sản phẩm: có một title, description, canonical, JSON-LD.
- Kiểm tra phân trang: trang 2 có canonical riêng với `?page=2`, không trỏ tất cả về trang 1.
- Trang `/products?q=...` và bộ lọc danh mục có `noindex,follow`.
- Xóa cache HTML/CDN nếu đang dùng.
- Dùng Google Rich Results Test kiểm tra dữ liệu có cấu trúc. Schema hợp lệ không đảm bảo Google hiển thị rich result.
- Dùng PageSpeed Insights trên mobile để đo hiệu năng thực tế; chưa có điểm PageSpeed production được xác nhận.

## Các việc vẫn cần chủ website thực hiện

- Xác minh tên miền trong Google Search Console và gửi sitemap.
- Chọn một tên miền chính, bật HTTPS và redirect 301 các phiên bản còn lại tại web server/CDN. Code không tự đoán tên miền hay ép HTTPS trên môi trường local.
- Viết mô tả riêng, chính xác cho từng danh mục/sản phẩm; nhập thông số định dạng file, kích thước hoặc phần mềm tương thích chỉ khi có dữ liệu thực tế.
- Theo dõi crawl/index và từ khóa trong Search Console. Không có cam kết thời điểm hoặc thứ hạng SEO.
- Sitemap hiện là một file. Nếu vượt 50.000 URL hoặc 50 MB XML chưa nén, cần chia sitemap và thêm sitemap index.
- Nếu đổi ảnh `khomau3d-banner.png` về sau, cần tạo lại các bản WebP đi kèm để desktop/mobile không hiển thị nội dung khác nhau.
