@extends('layouts.app')

@section('title', 'Báo cáo')
@section('content')

      <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
          <div class="">
            <h1 class="fs-3 mb-1">Báo cáo</h1>
            <p class="mb-0">Xem phân tích và báo cáo kho hàng</p>
          </div>

          </div>
        </div>
      </div>

      <div class="row g-3 mb-3">
        <!-- Stat cards -->
        <div class="col-12 col-sm-6 col-md-3">
          <div class="card h-100">
            <div class="card-body p-4">
              <h6 class="mb-4 ">Tổng doanh thu</h6>
              <h3 class="mb-1 fw-bold">$45,231</h3>
              <p class="mb-0 text-success small"><i class="ti ti-arrow-up"> </i>12% so với tháng trước</p>
            </div>
          </div>
        </div>

        <div class="col-12 col-sm-6 col-md-3">
          <div class="card h-100">
            <div class="card-body p-4">
              <h6 class="mb-4 ">Sản phẩm đã bán</h6>
              <h3 class="mb-1 fw-bold">1,234</h3>
              <p class="mb-0 text-success small"><i class="ti ti-arrow-up"> </i> 8% so với tháng trước</p>
            </div>
          </div>
        </div>

        <div class="col-12 col-sm-6 col-md-3">
          <div class="card h-100">
            <div class="card-body p-4">
              <h6 class="mb-4 ">Sản phẩm sắp hết</h6>
              <h3 class="mb-1 fw-bold">23</h3>
              <p class="mb-0 text-danger small"><i class="ti ti-arrow-down"> </i> 3% so với tháng trước</p>
            </div>
          </div>
        </div>

        <div class="col-12 col-sm-6 col-md-3">
          <div class="card h-100">
            <div class="card-body p-4">
              <h6 class="mb-4 ">Hết hàng</h6>
              <h3 class="mb-1 fw-bold">5</h3>
              <p class="mb-0 text-danger small"><i class="ti ti-arrow-down"> </i> 2% so với tháng trước</p>
            </div>
          </div>
        </div>
      </div>

      <div class="row mb-3">
        <!-- Tổng quan doanh số (full width) -->
        <div class="col-12">
          <div class="card">
            <div class="card-body p-4">
              <div class="d-flex flex-column flex-md-row justify-content-between align-items-start mb-3 gap-2">
                <div>
                  <h2 class="mb-0 fs-5">Tổng quan doanh số</h2>

                </div>
                 <div class="controls">
        <button id="btn-random" class="btn btn-light btn-sm">Dữ liệu ngẫu nhiên</button>
        <button id="btn-update" class="btn btn-primary btn-sm">Chỉ hiển thị năm nay</button>
      </div>
              </div>

              <!-- Chart placeholder: replace with canvas or chart container when integrating chart library -->

                <div id="salesChart"></div>


              <div class="d-flex justify-content-end">
                <a href="#" class="small">Xem báo cáo chi tiết</a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <!-- Sản phẩm hàng đầu -->
        <div class="col-12">
          <div class="card">
            <div class="card-body p-4">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                  <h2 class="mb-0 fs-5">Sản phẩm hàng đầu</h2>

                </div>
              </div>

              <!-- Product rows -->
              <div class="list-group list-group-flush">
                <div class="list-group-item p-3 d-flex align-items-center">
                  <div class="me-3">
                    <img src="{{ asset('assets/images/product-1.png') }}" alt="Sản phẩm A" class="rounded" style="width:48px; height:48px; object-fit:cover;">
                  </div>
                  <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center">
                      <div>
                        <h6 class="mb-0">Tay cầm chơi game</h6>
                        <small class="text-secondary">156 đã bán</small>
                      </div>
                      <div class="text-end">
                        <strong>$3,120</strong>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="list-group-item p-3 d-flex align-items-center">
                  <div class="me-3">
                    <img src="{{ asset('assets/images/product-2.png') }}" alt="Sản phẩm B" class="rounded" style="width:48px; height:48px; object-fit:cover;">
                  </div>
                  <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center">
                      <div>
                        <h6 class="mb-0">Tai nghe không dây</h6>
                        <small class="text-secondary">134 đã bán</small>
                      </div>
                      <div class="text-end">
                        <strong>$2,680</strong>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="list-group-item p-3 d-flex align-items-center">
                  <div class="me-3">
                    <img src="{{ asset('assets/images/product-3.png') }}" alt="Sản phẩm C" class="rounded" style="width:48px; height:48px; object-fit:cover;">
                  </div>
                  <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center">
                      <div>
                        <h6 class="mb-0">Đồng hồ thông minh</h6>
                        <small class="text-secondary">98 đã bán</small>
                      </div>
                      <div class="text-end">
                        <strong>$1,960</strong>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const chartElement = document.querySelector('#salesChart');
    if (!chartElement) return;
    const salesThisYear = [42000, 53000, 48000, 61000, 72000, 69000, 74000, 82000, 78000, 86000, 91000, 97000];
    const salesLastYear = [38000, 45000, 47000, 56000, 65000, 63000, 68000, 70000, 69000, 75000, 80000, 84000];
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const formatCurrency = value => value == null ? '-' : '₹' + Number(value).toLocaleString('en-IN', { maximumFractionDigits: 0 });
    const chart = new window.ApexCharts(chartElement, {
        chart: { id: 'sales-overview', type: 'area', height: 420, zoom: { enabled: false }, toolbar: { show: false } },
        colors: ['#E66239', '#198754'], stroke: { width: [3, 2.5], curve: 'smooth' }, markers: { size: 4, hover: { sizeOffset: 2 } },
        series: [{ name: 'Năm nay', data: salesThisYear }, { name: 'Năm trước', data: salesLastYear }],
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, inverseColors: false, opacityFrom: 0.45, opacityTo: 0.05, stops: [20, 60, 100] } },
        yaxis: { labels: { formatter: formatCurrency }, title: { text: 'Doanh số (INR)' } }, xaxis: { categories: months, tickPlacement: 'on' },
        tooltip: { shared: true, y: { formatter: formatCurrency } }, legend: { position: 'top', horizontalAlign: 'right' },
        responsive: [{ breakpoint: 640, options: { chart: { height: 340 }, legend: { position: 'bottom', horizontalAlign: 'center' } } }]
    });
    chart.render();
    document.getElementById('btn-random')?.addEventListener('click', () => {
        const rand = () => Math.round((Math.random() * 80 + 20) * 1000);
        chart.updateSeries([{ name: 'Năm nay', data: Array.from({ length: 12 }, rand) }, { name: 'Năm trước', data: Array.from({ length: 12 }, rand) }]);
    });
    let showingBoth = true;
    document.getElementById('btn-update')?.addEventListener('click', event => {
        if (showingBoth) { chart.updateSeries([{ name: 'Năm nay', data: salesThisYear }]); event.currentTarget.textContent = 'Hiển thị so sánh'; }
        else { chart.updateSeries([{ name: 'Năm nay', data: salesThisYear }, { name: 'Năm trước', data: salesLastYear }]); event.currentTarget.textContent = 'Chỉ hiển thị năm nay'; }
        showingBoth = !showingBoth;
    });
});
</script>
@endpush
