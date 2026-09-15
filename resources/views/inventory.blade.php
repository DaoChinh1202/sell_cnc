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

  <form action="{{ route('inventory') }}" method="GET" class="d-flex gap-2 mb-3" role="search">
    <label for="productSearch" class="visually-hidden">Tìm sản phẩm</label>
    <input type="search" class="form-control" id="productSearch" name="q" value="{{ $search }}" placeholder="Tìm theo tên hoặc SKU..." style="max-width: 320px;">
    <button class="btn btn-outline-secondary" type="submit"><i class="ti ti-search me-1"></i> Tìm</button>
    @if ($search !== '')
      <a href="{{ route('inventory') }}" class="btn btn-outline-secondary">Xóa lọc</a>
    @endif
  </form>

  <div class="card table-responsive">
    <table class="table mb-0 table-hover align-middle">
      <thead class="table-light border-light">
        <tr>
          <th class="ps-4">Ảnh / sản phẩm</th>
          <th>Mã SP</th>
          <th>Danh mục</th>
          <th>Thương hiệu</th>
          <th>Giá</th>
          <th>Đơn vị</th>
          <th>Số lượng</th>
          <th>Trạng thái</th>
          <th class="pe-4 text-end">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($products as $product)
          <tr>
            <td class="ps-4">
              <div class="d-flex align-items-center gap-3" style="min-width: 220px;">
                <img src="{{ '/storage/'.$product->image }}" alt="{{ $product->name }}" class="avatar avatar-md rounded object-fit-cover" width="48" height="48">
                <span class="fw-semibold">{{ $product->name }}</span>
              </div>
            </td>
            <td><code>{{ $product->sku }}</code></td>
            <td>{{ $product->category->name }}</td>
            <td>{{ $product->brand ?: '—' }}</td>
            <td>{{ number_format((float) $product->price, 2) }}</td>
            <td>{{ $product->unit }}</td>
            <td>{{ $product->quantity }}</td>
            <td>
              <span class="badge {{ $product->status === 'active' ? 'text-bg-success' : ($product->status === 'draft' ? 'text-bg-warning' : 'text-bg-secondary') }}">
                {{ ['active' => 'Hoạt động', 'inactive' => 'Ngừng hoạt động', 'draft' => 'Bản nháp'][$product->status] }}
              </span>
            </td>
            <td class="pe-4 text-end text-nowrap">
              <a href="{{ route('products.show', $product) }}" class="btn btn-sm btn-outline-secondary" aria-label="Xem {{ $product->name }}"><i class="ti ti-eye"></i></a>
              <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-outline-primary" aria-label="Sửa {{ $product->name }}"><i class="ti ti-edit"></i></a>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="9" class="text-center text-muted py-5">
              {{ $search !== '' ? 'Không tìm thấy sản phẩm phù hợp.' : 'Chưa có sản phẩm nào. Hãy thêm sản phẩm đầu tiên.' }}
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>

    @if ($products->hasPages())
      <div class="card-footer bg-white">{{ $products->links() }}</div>
    @endif
  </div>
@endsection
