@extends('layouts.app')

@section('title', 'Kho hàng')

@section('content')
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
      <h1 class="fs-3 mb-1">Kho hàng</h1>
      <p class="mb-0">Quản lý kho hàng sản phẩm của bạn</p>
    </div>
    <a href="{{ route('products.create') }}" class="btn btn-primary"><i class="ti ti-plus me-1"></i> Thêm sản phẩm</a>
  </div>

  @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
    </div>
  @endif

  @if (session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
      {{ session('warning') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
    </div>
  @endif

  @if ($errors->any())
    <div class="alert alert-danger" role="alert">
      <p class="mb-1">Vui lòng kiểm tra lại thông tin:</p>
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  @php
    $hasFilters = $search !== '' || !empty($filters['status']) || !empty($filters['date_from']) || !empty($filters['date_to']);
  @endphp

  <form action="{{ route('inventory') }}" method="GET" class="row g-2 mb-3 align-items-end" role="search">
    <div class="col-md-4">
      <label for="productSearch" class="form-label">Tìm sản phẩm</label>
      <input type="search" class="form-control" id="productSearch" name="q" value="{{ $search }}" placeholder="Tìm theo tên hoặc SKU...">
    </div>
    <div class="col-md-2">
      <label for="productStatus" class="form-label">Trạng thái</label>
      <select class="form-select" id="productStatus" name="status">
        <option value="">Tất cả</option>
        @foreach (['active' => 'Hoạt động', 'inactive' => 'Ngừng hoạt động', 'draft' => 'Bản nháp'] as $value => $label)
          <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-2">
      <label for="productDateFrom" class="form-label">Ngày tạo từ</label>
      <input type="date" class="form-control" id="productDateFrom" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
    </div>
    <div class="col-md-2">
      <label for="productDateTo" class="form-label">Ngày tạo đến</label>
      <input type="date" class="form-control" id="productDateTo" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
    </div>
    <div class="col-md-auto d-flex gap-2">
      <button class="btn btn-outline-secondary" type="submit"><i class="ti ti-search me-1"></i> Tìm</button>
      @if ($hasFilters)
        <a href="{{ route('inventory') }}" class="btn btn-outline-secondary">Xóa lọc</a>
      @endif
    </div>
  </form>

  <form action="{{ route('products.bulk') }}" method="POST" data-inventory-bulk>
    @csrf
    <input type="hidden" name="q" value="{{ $search }}">
    @foreach (['status', 'date_from', 'date_to'] as $filter)
      <input type="hidden" name="{{ $filter }}" value="{{ $filters[$filter] ?? '' }}">
    @endforeach
    <div class="d-flex flex-wrap align-items-end gap-2 mb-3">
      <div>
        <label for="bulkAction" class="form-label">Thao tác với sản phẩm đã chọn</label>
        <select class="form-select" id="bulkAction" name="action" required data-bulk-action>
          <option value="">Chọn thao tác</option>
          <option value="active">Hoạt động</option>
          <option value="inactive">Ngừng hoạt động</option>
          <option value="delete">Xóa</option>
        </select>
      </div>
      <button type="submit" class="btn btn-primary" disabled data-bulk-submit>Áp dụng</button>
      <span class="align-self-center" role="status" aria-live="polite" aria-atomic="true" data-bulk-count>Đã chọn 0 sản phẩm</span>
    </div>
    <noscript><p class="text-muted">Vui lòng bật JavaScript để sử dụng thao tác hàng loạt.</p></noscript>

  <div class="card table-responsive">
    <table class="table mb-0 table-hover align-middle">
      <thead class="table-light border-light">
        <tr>
          <th scope="col" class="ps-4">
            <input type="checkbox" class="form-check-input" id="selectAllProducts" data-bulk-all @disabled($products->isEmpty())>
            <label for="selectAllProducts" class="visually-hidden">Chọn tất cả sản phẩm trên trang hiện tại</label>
          </th>
          <th>Ảnh / sản phẩm</th>
          <th>Mã SP</th>
          <th>Danh mục</th>
          <th>Giá</th>
          <th>Trạng thái</th>
          <th>Ngày tạo ({{ config('app.timezone', 'UTC') }})</th>
          <th class="pe-4 text-end">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($products as $product)
          <tr>
            <td class="ps-4">
              <input type="checkbox" class="form-check-input" id="selectProduct{{ $product->id }}" name="ids[]" value="{{ $product->id }}" data-bulk-id>
              <label for="selectProduct{{ $product->id }}" class="visually-hidden">Chọn {{ $product->name }} ({{ $product->sku }})</label>
            </td>
            <td>
              <div class="d-flex align-items-center gap-3" style="min-width: 220px;">
                <img src="{{ '/storage/'.$product->image }}" alt="{{ $product->name }}" class="avatar avatar-md rounded object-fit-cover" width="48" height="48">
                <span class="fw-semibold">{{ $product->name }}</span>
              </div>
            </td>
            <td><code>{{ $product->sku }}</code></td>
            <td>{{ $product->category->name }}</td>
            <td>{{ number_format((float) $product->price, 2) }}</td>
            <td>
              <span class="badge {{ $product->status === 'active' ? 'text-bg-success' : ($product->status === 'draft' ? 'text-bg-warning' : 'text-bg-secondary') }}">
                {{ ['active' => 'Hoạt động', 'inactive' => 'Ngừng hoạt động', 'draft' => 'Bản nháp'][$product->status] }}
              </span>
            </td>
            <td class="text-nowrap">
              @if ($product->created_at)
                <time datetime="{{ $product->created_at->copy()->setTimezone(config('app.timezone', 'UTC'))->toIso8601String() }}">{{ $product->created_at->copy()->setTimezone(config('app.timezone', 'UTC'))->format('d/m/Y') }}</time>
              @else
                —
              @endif
            </td>
            <td class="pe-4 text-end text-nowrap">
              <a href="{{ route('products.show', $product) }}" class="btn btn-sm btn-outline-secondary" aria-label="Xem {{ $product->name }}"><i class="ti ti-eye"></i></a>
              <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-outline-primary" aria-label="Sửa {{ $product->name }}"><i class="ti ti-edit"></i></a>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="8" class="text-center text-muted py-5">
              {{ $hasFilters ? 'Không tìm thấy sản phẩm phù hợp.' : 'Chưa có sản phẩm nào. Hãy thêm sản phẩm đầu tiên.' }}
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>

    @if ($products->hasPages())
      <div class="card-footer bg-white">{{ $products->links() }}</div>
    @endif
  </div>
  </form>
@endsection
