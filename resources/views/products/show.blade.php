@extends('layouts.app')

@section('title', $product->name)

@section('content')
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
      <div class="text-muted small mb-1"><a href="{{ route('inventory') }}" class="text-decoration-none">Kho hàng</a> <span class="mx-1">/</span> Chi tiết sản phẩm</div>
      <h1 class="fs-3 mb-1">{{ $product->name }}</h1>
      <p class="mb-0">SKU: <code>{{ $product->sku }}</code></p>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('inventory') }}" class="btn btn-outline-secondary"><i class="ti ti-arrow-left me-1"></i> Quay lại</a>
      <a href="{{ route('products.edit', $product) }}" class="btn btn-primary"><i class="ti ti-edit me-1"></i> Chỉnh sửa</a>
    </div>
  </div>

  @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
    </div>
  @endif

  <div class="row g-4">
    <div class="col-lg-5">
      <div class="card h-100">
        <div class="card-body p-4 d-flex align-items-center justify-content-center">
          <img src="{{ '/storage/'.$product->image }}" alt="{{ $product->name }}" class="img-fluid rounded" style="max-height: 460px; object-fit: contain;">
        </div>
        <div class="card-footer bg-white text-muted small">Ảnh hiển thị là bản WebP đã watermark.</div>
      </div>
    </div>
    <div class="col-lg-7">
      <div class="row g-3 mb-4">

        <div class="col-sm-6"><div class="card h-100"><div class="card-body p-3"><div class="text-muted small">Giá bán</div><div class="fs-4 fw-semibold">{{ number_format((float) $product->price, 2) }}</div></div></div></div>
        <div class="col-sm-6"><div class="card h-100"><div class="card-body p-3"><div class="text-muted small">Trạng thái</div><div class="mt-1"><span class="badge {{ $product->status === 'active' ? 'text-bg-success' : ($product->status === 'draft' ? 'text-bg-warning' : 'text-bg-secondary') }}">{{ ['active' => 'Hoạt động', 'inactive' => 'Ngừng hoạt động', 'draft' => 'Bản nháp'][$product->status] }}</span></div></div></div></div>
      </div>

      <div class="card mb-4">
        <div class="card-header bg-white py-3"><h2 class="h5 mb-0">Thông tin sản phẩm</h2></div>
        <div class="card-body p-4">
          <div class="text-muted small">Danh mục</div>
          <div class="fw-semibold">{{ $product->category->name }}</div>
          <hr>
          <div class="text-muted small mb-1">Mô tả</div>
          <p class="mb-0">{{ $product->description ?: 'Chưa có mô tả.' }}</p>
        </div>
      </div>

      <div class="card"><div class="card-header bg-white py-3"><h2 class="h5 mb-0">Timeline</h2></div><div class="card-body p-4"><div class="row g-3"><div class="col-sm-6"><div class="text-muted small">Tạo lúc</div><div>{{ $product->created_at->format('d/m/Y H:i') }}</div></div><div class="col-sm-6"><div class="text-muted small">Cập nhật lần cuối</div><div>{{ $product->updated_at->format('d/m/Y H:i') }}</div></div></div></div></div>
    </div>
  </div>
@endsection
