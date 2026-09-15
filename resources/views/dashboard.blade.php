@extends('layouts.app')

@section('title', 'Tổng quan')
@section('content')
<div class="row ">
        <div class="col-12">
          <div class="mb-6">
            <h1 class="fs-3 mb-1">Tổng quan</h1>
            <p>Nội dung chính của bạn ở đây…</p>
          </div>
        </div>
      </div>
      <div class="row g-3 mb-3">
        <div class="col-lg-3 col-12">

          <div class="card p-4  bg-primary bg-opacity-10 border border-primary border-opacity-25 rounded-2">

            <div class="d-flex gap-3 ">
              <div class="icon-shape icon-md bg-primary text-white rounded-2">
                <i class="ti ti-report-analytics fs-4"></i>
              </div>
              <div>
                <h2 class="mb-3 fs-6">Tổng doanh số</h2>
                <h3 class="fw-bold mb-0">$25,000</h3>
                <p class="text-primary mb-0 small">+5% so với tháng trước</p>
              </div>
            </div>
          </div>


        </div>
        <div class="col-lg-3 col-12">

          <div class="card p-4  bg-success bg-opacity-10 border border-success border-opacity-25 rounded-2">

            <div class="d-flex gap-3 ">
              <div class="icon-shape icon-md bg-success text-white rounded-2">
                <i class="ti ti-repeat fs-4"></i>
              </div>
              <div>
                <h2 class="mb-3 fs-6">Tổng nhập hàng</h2>
                <h3 class="fw-bold mb-0">$18,000</h3>
                <p class="text-success mb-0 small">+22% so với tháng trước</p>
              </div>
            </div>
          </div>


        </div>
        <div class="col-lg-3 col-12">

          <div class="card p-4  bg-info bg-opacity-10 border border-info border-opacity-25 rounded-2">

            <div class="d-flex gap-3 ">
              <div class="icon-shape icon-md bg-info text-white rounded-2">
                <i class="ti ti-currency-dollar fs-4"></i>
              </div>
              <div>
                <h2 class="mb-3 fs-6">Tổng chi phí</h2>
                <h3 class="fw-bold mb-0">$9,000</h3>
                <p class="text-info mb-0 small">+10% so với tháng trước</p>
              </div>
            </div>
          </div>


        </div>
        <div class="col-lg-3 col-12">

          <div class="card p-4  bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded-2">

            <div class="d-flex gap-3 ">
              <div class="icon-shape icon-md bg-warning text-white rounded-2">
                <i class="ti ti-notes fs-4"></i>
              </div>
              <div>
                <h2 class="mb-3 fs-6">Hóa đơn đơn hạn</h2>
                <h3 class="fw-bold mb-0">$25,000</h3>
                <p class="text-warning mb-0 small">+35% so với tháng trước</p>
              </div>
            </div>
          </div>


        </div>

      </div>
      <div class="row g-3 mb-3">
        <div class="col-lg-4 col-12">
          <div class="card">
            <div class="card-body p-4">
              <div class="d-flex justify-content-between border-bottom pb-5 mb-3">
                <div>
                  <h3 class="fw-bold h4">$25,458</h3>
                  <span>Tổng lợi nhuận</span>
                </div>
                <div>
                  <i class="ti ti-layers-subtract fs-1 text-primary"></i>
                </div>
              </div>
              <div class="d-flex justify-content-between align-items-center small">
                <div class="text-muted"><span class="text-success">+35%</span> so với tháng trước</div>
                <div><a href="#" class="link-primary text-decoration-underline">Xem</a></div>
              </div>
            </div>
          </div>

        </div>
        <div class="col-lg-4 col-12">
          <div class="card">
            <div class="card-body p-4">
              <div class="d-flex justify-content-between border-bottom pb-5 mb-3">
                <div>
                  <h3 class="fw-bold h4">$45,458</h3>
                  <span>Tổng hoàn trả</span>
                </div>
                <div>
                  <i class="ti ti-credit-card fs-1 text-danger"></i>
                </div>
              </div>
              <div class="d-flex justify-content-between align-items-center small">
                <div class="text-muted"><span class="text-danger">-20%</span> so với tháng trước</div>
                 <div><a href="#" class="link-primary text-decoration-underline">Xem</a></div>
              </div>
            </div>
          </div>

        </div>
        <div class="col-lg-4 col-12">
          <div class="card">
            <div class="card-body p-4">
              <div class="d-flex justify-content-between border-bottom pb-5 mb-3">
                <div>
                  <h3 class="fw-bold h4">$34,458</h3>
                  <span>Tổng chi phí</span>
                </div>
                <div>
                  <i class="ti ti-cash-banknote fs-1 text-warning"></i>
                </div>
              </div>
              <div class="d-flex justify-content-between align-items-center small">
                <div class="text-muted"><span class="text-warning">-20%</span> so với tháng trước</div>
                <div><a href="#" class="link-primary text-decoration-underline">Xem</a></div>
              </div>
            </div>
          </div>

        </div>

      </div>
      <div class="row g-3 mb-3">
        <div class="col-12 col-lg-6">
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center bg-transparent px-4 py-3">
              <h3 class="h5 mb-0">Doanh số vs Nhập hàng</h3>
              <div>
                <select class="form-select form-select-sm">
                  <option selected>Năm nay</option>
                  <option>Tháng này</option>
                  <option>Tuần này</option>
                </select>
              </div>
            </div>
            <div class="card-body p-4">

              <div id="salesPurchaseChart"></div>
            </div>
          </div>
        </div>


        <div class="col-12 col-lg-6">
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center bg-transparent px-4 py-3">
              <h3 class="h5 mb-0">Tổng quan chung</h3>
              <div>
                <select class="form-select form-select-sm">
                  <option selected>6 tháng gần nhất</option>
                  <option>Tháng này</option>
                  <option>Tuần này</option>
                </select>
              </div>
            </div>
            <div class="card-body p-4">
              <h3 class="h6">Khách hàng</h3>
              <div class="row align-items-center">
                <div class="col-sm-6">
                  <div id="customerChart">

                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="row">
                    <div class="col-6 border-end">
                      <div class="text-center ">
                        <h2 class="mb-1">5.5K</h2>
                        <p class="text-success mb-2">Lần đầu</p>
                        <span class="badge bg-success"><i class="ti ti-arrow-up-left me-1"></i>25%</span>
                      </div>
                    </div>
                    <div class="col-6">
                      <div class="text-center">
                        <h2 class="mb-1">3.5K</h2>
                        <p class="text-warning mb-2">Quay lại</p>
                        <span class="badge bg-success badge-xs d-inline-flex align-items-center"><i
                            class="ti ti-arrow-up-left me-1"></i>21%</span>
                      </div>
                    </div>
                  </div>
                </div>


              </div>
              <div class="row text-center border-top mt-4 pt-4">
                <div class="col-4 border-end">
                  <h3 class="fw-bold mb-2">6987</h3>
                  <small class="text-secondary">Nhà cung cấp</small>
                </div>
                <div class="col-4 border-end">
                  <h3 class="fw-bold mb-2">4896</h3>
                  <small class="text-secondary">Khách hàng</small>
                </div>
                <div class="col-4">
                  <h3 class="fw-bold mb-2">487</h3>
                  <small class="text-secondary">Đơn hàng</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="row g-3">

        <!-- CARD 1 — Sản phẩm bán chạy -->
        <div class="col-lg-4">
          <div class="card  h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center px-4 py-3">
              <h4 class="mb-0 h5">Sản phẩm bán chạy</h4>
              <button class="btn btn-sm btn-outline-secondary">
                <i class="ti ti-calendar"></i> Hôm nay
              </button>
            </div>

            <ul class="list-group list-group-flush">

              <!-- item -->
              <li class="list-group-item d-flex align-items-center gap-3">
                <img src="{{ asset('assets/images/product-2.png') }}" class="rounded" width="48">
                <div class="flex-grow-1">
                  <p class="mb-1">Tai nghe không dây</p>
                  <div class="d-flex align-items-center gap-2 text-muted">
                    <small class="fw-semibold">$89 </small>
                    <small>•</small>
                    <small>1,250 Đơn vị</small>
                  </div>
                </div>
                <span class="badge bg-danger-subtle text-danger border border-danger">18%</span>
              </li>

              <!-- repeat -->
              <li class="list-group-item d-flex align-items-center gap-3">
                <img src="{{ asset('assets/images/product-1.png') }}" class="rounded" width="48">
                <div class="flex-grow-1">
                  <p class="mb-1">Tay cầm chơi game</p>
                  <div class="d-flex align-items-center gap-2 text-muted">
                    <small class="fw-semibold">$49 </small>
                    <small>•</small>
                    <small>5,420 Đơn vị</small>
                  </div>

                </div>
                <span class="badge bg-primary-subtle text-primary border border-primary">32%</span>
              </li>

              <li class="list-group-item d-flex align-items-center gap-3">
                <img src="{{ asset('assets/images/product-3.png') }}" class="rounded" width="48">
                <div class="flex-grow-1">
                  <p class="mb-1">Đồng hồ thông minh Pro</p>
                  <div class="d-flex align-items-center gap-2 text-muted">
                    <small class="fw-semibold">$98 </small>
                    <small>•</small>
                    <small>862 Đơn vị</small>
                  </div>

                </div>
                <span class="badge bg-info-subtle text-info border border-info">22%</span>
              </li>
              <li class="list-group-item d-flex align-items-center gap-3">
                <img src="{{ asset('assets/images/product-4.png') }}" class="rounded" width="48">
                <div class="flex-grow-1">
                  <p class="mb-1">Sạc nhanh USB-C</p>
                  <div class="d-flex align-items-center gap-2 text-muted">
                    <small class="fw-semibold">$35 </small>
                    <small>•</small>
                    <small>3,200 Đơn vị</small>
                  </div>

                </div>
                <span class="badge bg-success-subtle text-success border border-success">28%</span>
              </li>
              <li class="list-group-item d-flex align-items-center gap-3">
                <img src="{{ asset('assets/images/product-5.png') }}" class="rounded" width="48">
                <div class="flex-grow-1">
                  <p class="mb-1">Loa Bluetooth di động</p>
                  <div class="d-flex align-items-center gap-2 text-muted">
                    <small class="fw-semibold">$65 </small>
                    <small>•</small>
                    <small>2,890 Đơn vị</small>
                  </div>

                </div>
                <span class="badge bg-warning-subtle text-warning border border-warning">25%</span>
              </li>
            </ul>
          </div>
        </div>

        <!-- CARD 2 — Sản phẩm sắp hết hàng -->
        <div class="col-lg-4">
          <div class="card  h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center px-4 py-3">
              <div class="d-flex align-items-center">

                <h4 class="mb-0 h5">Sản phẩm sắp hết hàng</h4>
              </div>
              <a href="#" class="small text-primary text-decoration-underline">Xem tất cả</a>
            </div>

            <ul class="list-group list-group-flush">

              <li class="list-group-item d-flex align-items-center gap-3">
                <img src="{{ asset('assets/images/product-8.png') }}" class="rounded" width="48">
                <div class="flex-grow-1">
                  <p class="mb-1">Tai nghe không dây</p>
                  <small>ID: #554433</small>
                </div>
                <div class="d-flex flex-column gap-0 align-items-center">
                  <span class="fw-semibold text-primary">06</span>
                  <small class="text-muted">Trong kho</small>
                </div>
              </li>

              <li class="list-group-item d-flex align-items-center gap-3">
                <img src="{{ asset('assets/images/product-4.png') }}" class="rounded" width="48">
                <div class="flex-grow-1">
                  <p class="mb-1">Bộ cáp USB-C</p>
                  <small>ID: #887766</small>
                </div>
                <div class="d-flex flex-column gap-0 align-items-center">
                  <span class="fw-semibold text-primary">09</span>
                  <small class="text-muted">Trong kho</small>
                </div>
              </li>

              <li class="list-group-item d-flex align-items-center gap-3">
                <img src="{{ asset('assets/images/product-10.png') }}" class="rounded" width="48">
                <div class="flex-grow-1">
                  <p class="mb-1">Miếng dán màn hình điện thoại</p>
                  <small>ID: #332211</small>
                </div>
                <div class="d-flex flex-column gap-0 align-items-center">
                  <span class="fw-semibold text-primary">03</span>
                  <small class="text-muted">Trong kho</small>
                </div>
              </li>
              <li class="list-group-item d-flex align-items-center gap-3">
                <img src="{{ asset('assets/images/product-4.png') }}" class="rounded" width="48">
                <div class="flex-grow-1">
                  <p class="mb-1">Sạc dự phòng 20000mAh</p>
                  <small>ID: #998877</small>
                </div>
                <div class="d-flex flex-column gap-0 align-items-center">
                  <span class="fw-semibold text-primary">07</span>
                  <small class="text-muted">Trong kho</small>
                </div>
              </li>
              <li class="list-group-item d-flex align-items-center gap-3">
                <img src="{{ asset('assets/images/product-6.png') }}" class="rounded" width="48">
                <div class="flex-grow-1">
                  <p class="mb-1">Bàn phím cơ RGB</p>
                  <small>ID: #665544</small>
                </div>
                <div class="d-flex flex-column gap-0 align-items-center">
                  <span class="fw-semibold text-primary">02</span>
                  <small class="text-muted">Trong kho</small>
                </div>
              </li>
            </ul>
          </div>
        </div>

        <!-- CARD 3 — Đơn hàng gần đây -->
        <div class="col-lg-4">
          <div class="card  h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center px-4 py-3">
              <h4 class="mb-0 h5">Đơn hàng gần đây</h4>
              <button class="btn btn-sm btn-outline-secondary">
                <i class="ti ti-calendar-event"></i> Hàng tuần
              </button>
            </div>

            <ul class="list-group list-group-flush">

              <li class="list-group-item d-flex align-items-center gap-3">
                <img src="{{ asset('assets/images/product-7.png') }}" class="rounded" width="48">
                <div class="flex-grow-1">
                  <p class="mb-1">MacBook Pro 16 inch</p>
                  <div class="d-flex align-items-center gap-2 text-muted">
                    <small class="fw-semibold">Máy tính </small>
                    <small>•</small>
                    <small>2,$2,499</small>
                  </div>

                </div>
                <span class="badge bg-success-subtle text-success">Hoàn thành</span>
              </li>

              <li class="list-group-item d-flex align-items-center gap-3">
                <img src="{{ asset('assets/images/product-9.png') }}" class="rounded" width="48">
                <div class="flex-grow-1">
                  <p class="mb-1">AirPods Pro Max</p>
                  <div class="d-flex align-items-center gap-2 text-muted">
                    <small class="fw-semibold">Âm thanh </small>
                    <small>•</small>
                    <small>$549</small>
                  </div>

                </div>
                <span class="badge bg-primary-subtle text-primary">Đang xử lý</span>
              </li>

              <li class="list-group-item d-flex align-items-center gap-3">
                <img src="{{ asset('assets/images/product-8.png') }}" class="rounded" width="48">
                <div class="flex-grow-1">
                  <p class="mb-1">iPad Air 11 inch</p>
                  <div class="d-flex align-items-center gap-2 text-muted">
                    <small class="fw-semibold">Máy tính bảng </small>
                    <small>•</small>
                    <small>$799</small>
                  </div>
                </div>
                <span class="badge bg-success-subtle text-success">Hoàn thành</span>
              </li>

              <li class="list-group-item d-flex align-items-center gap-3">
                <img src="{{ asset('assets/images/product-3.png') }}" class="rounded" width="48">
                <div class="flex-grow-1">
                  <p class="mb-1">Apple Watch Ultra</p>
                  <div class="d-flex align-items-center gap-2 text-muted">
                    <small class="fw-semibold">Thiết bị đeo </small>
                    <small>•</small>
                    <small>$799</small>
                  </div>
                </div>
                <span class="badge bg-warning-subtle text-warning">Chờ xử lý</span>
              </li>

              <li class="list-group-item d-flex align-items-center gap-3">
                <img src="{{ asset('assets/images/product-6.png') }}" class="rounded" width="48">
                <div class="flex-grow-1">
                  <p class="mb-1">Magic Keyboard</p>
                  <div class="d-flex align-items-center gap-2 text-muted">
                    <small class="fw-semibold">Phụ kiện </small>
                    <small>•</small>
                    <small>$299</small>
                  </div>

                </div>
                <span class="badge bg-danger-subtle text-danger">Đã hủy</span>
              </li>
            </ul>
          </div>
        </div>

      </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('salesPurchaseChart')) {
        const salesPurchaseChart = new window.ApexCharts(document.querySelector('#salesPurchaseChart'), {
            series: [
                { name: 'Doanh số', data: [44, 55, 57, 56, 61, 58, 63, 60, 66] },
                { name: 'Nhập hàng', data: [76, 85, 101, 98, 87, 105, 91, 114, 94] }
            ], colors: ['#f7a085', '#E66239'],
            chart: { type: 'bar', height: 350, width: '100%', parentHeightOffset: 0, toolbar: { show: false } },
            grid: { show: true, borderColor: '#e2e8f0' },
            legend: { show: true, fontFamily: 'Poppins, serif', fontWeight: 500, markers: { size: 5, shape: 'square', strokeWidth: 0, offsetX: -2 } },
            plotOptions: { bar: { horizontal: false, columnWidth: '85%', borderRadius: 3, borderRadiusApplication: 'end' } }, dataLabels: { enabled: false }, stroke: { show: false, width: 2, colors: ['transparent'] },
            xaxis: { categories: ['28 Jan', '29 Jan', '30 Jan', '31 Jan', '1 Feb', '2 Feb', '3 Feb', '4 Feb', '5 Feb'], axisBorder: { show: false }, axisTicks: { show: false } },
            yaxis: { labels: { formatter: value => value + 'k' }, title: { text: '$ (nghìn)' } }, fill: { opacity: 1 }, tooltip: { y: { formatter: value => '$ ' + value + ' nghìn' } }
        });
        salesPurchaseChart.render();
    }
    if (document.getElementById('customerChart')) {
        const customerChart = new window.ApexCharts(document.querySelector('#customerChart'), {
            series: [44, 55], chart: { height: 200, type: 'radialBar' }, colors: ['#5BE49B', '#E66239'],
            plotOptions: { radialBar: { dataLabels: { name: { fontSize: '22px' }, value: { fontSize: '16px' }, total: { show: false } }, hollow: { margin: 3, size: '40%', background: 'transparent' }, track: { show: true, background: '#f0f0f0', strokeWidth: '45%', opacity: 1, margin: 5 } } }, fill: { type: 'gradient', gradient: { shade: 'dark', type: 'vertical', gradientToColors: ['#007867', '#FFD666', '#FFAC82'], stops: [0, 100] } }, stroke: { lineCap: 'round' }, labels: ['Lần đầu', 'Quay lại']
        });
        customerChart.render();
    }
});
</script>
@endpush
