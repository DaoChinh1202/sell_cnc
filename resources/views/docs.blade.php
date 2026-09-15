@extends('layouts.app')

@section('title', 'Tài liệu')

@section('content')
  <div class="row">
    <div class="col-12">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div class="">
          <h1 class="fs-3 mb-1">Tài liệu</h1>
          <p>
            Tài liệu này hướng dẫn bạn thiết lập và sử dụng trang quản trị kho hàng InApp.
            Bạn có thể theo dõi Tổng quan, Kho hàng, Thêm sản phẩm và Báo cáo từ thanh điều hướng.
          </p>
        </div>

      </div>
    </div>
  </div>

    <div class="row">
        <div class="col-12">
            <div class="">
  <div class="">



    <!-- Prerequisites -->
    <div class="mb-4">
      <div class="mb-2">
      <h2 class="h5 mb-1">Các phần chính</h2>
      <p>Trang quản trị gồm các khu vực sau để bạn quản lý hoạt động kinh doanh:</p>
      </div>
      <ul class="list-group list-group-flush">
        <li class="list-group-item ps-0"><strong>Tổng quan</strong> — xem nhanh các chỉ số và tình hình kho hàng.</li>
        <li class="list-group-item ps-0"><strong>Kho hàng</strong> — tra cứu, lọc và theo dõi các sản phẩm hiện có.</li>
        <li class="list-group-item ps-0"><strong>Thêm sản phẩm</strong> — nhập thông tin sản phẩm mới vào kho.</li>
        <li class="list-group-item ps-0"><strong>Báo cáo</strong> — xem các báo cáo liên quan đến hoạt động kho hàng.</li>
      </ul>
    </div>

    <!-- Installation -->
    <div class="mb-4">
      <h2 class="h5 mb-2">Cài đặt</h2>
      <ol class="list-group list-group-numbered list-group-flush">
        <li class="list-group-item ps-0">Sao chép kho mã nguồn hoặc tải template về máy</li>
        <li class="list-group-item ps-0">Đi đến thư mục dự án</li>
        <li class="list-group-item ps-0">
          Cài đặt các gói phụ thuộc:
          <pre class="bg-light border rounded p-3 mt-2"><code>npm install</code></pre>
        </li>
      </ol>
    </div>



    <!-- Usage -->
    <div class="mb-6">
      <h2 class="h5 mb-2">Chạy ứng dụng</h2>
      <p>Để khởi động máy chủ phát triển:</p>
      <pre class="bg-light border rounded p-3"><code>npm run dev</code></pre>
    </div>
  <!-- Next Steps -->
    <div class="mb-4">
      <h2 class="h5 mb-2">Các bước tiếp theo</h2>
      <ol class="list-group list-group-numbered list-group-flush">
        <li class="list-group-item ps-0">Xem điểm khởi chạy chính tại <code>src/js/main.js</code></li>
        <li class="list-group-item ps-0">Tùy chỉnh các thành phần theo nhu cầu của bạn</li>
        <li class="list-group-item ps-0">
          Xây dựng bản dành cho môi trường production:
          <pre class="bg-light border rounded p-3 mt-4"><code>npm run build</code></pre>
        </li>
      </ol>
    </div>

    <!-- Project Structure -->
    <div class="mb-4">
      <h2 class="h5 mb-0">Cấu trúc dự án</h2>
      <pre>
   <code>
inapp/
├── src/
│   ├── assest/         # Static assets
│   │   ├── images/     # Images
│   │   ├── js/         # JS
│   │   ├── scss/       # CSS and styling
│   └── Pages           # All Pages
├── vite.config.js/     # Config Files
├── package.json        # Project dependencies
├── README.md           # Documentation
└── .gitignore          # Git ignore file
   </code>
   </pre>
    </div>


    <!-- Support -->
    <div class="mb-2">
      <h2 class="h5">Hỗ trợ</h2>
      <p>
        Nếu gặp vấn đề hoặc có câu hỏi, hãy tham khảo tài liệu hoặc tạo issue trong repository.
        Bạn cũng có thể liên hệ với <a href="#!" class="text-primary">CodesCandy</a>.
      </p>
    </div>

  </div>
</div>

          </div>
          </div>
@endsection
