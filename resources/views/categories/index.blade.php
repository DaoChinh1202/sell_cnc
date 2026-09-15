@extends('layouts.app')

@section('title', 'Danh mục sản phẩm')

@section('content')
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
      <h1 class="fs-3 mb-1">Danh mục sản phẩm</h1>
      <p class="mb-0">Quản lý danh mục sản phẩm của bạn</p>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
      <i class="ti ti-plus me-1"></i> Thêm danh mục
    </button>
  </div>

  @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
    </div>
  @endif
  @if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
    </div>
  @endif

  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
      <div class="card h-100"><div class="card-body p-4">
        <p class="text-muted mb-2">Tổng danh mục</p><h2 class="mb-0">{{ $totalCategories }}</h2>
      </div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card h-100"><div class="card-body p-4">
        <p class="text-muted mb-2">Đang hoạt động</p><h2 class="mb-0 text-success">{{ $activeCategories }}</h2>
      </div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card h-100"><div class="card-body p-4">
        <p class="text-muted mb-2">Ngừng hoạt động</p><h2 class="mb-0 text-secondary">{{ $inactiveCategories }}</h2>
      </div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card h-100"><div class="card-body p-4">
        <p class="text-muted mb-2">Tổng sản phẩm</p><h2 class="mb-0">{{ $totalProducts }}</h2>
      </div></div>
    </div>
  </div>

  <div class="card">
    <div class="card-header bg-white py-3">
      <h2 class="h5 mb-1">Danh mục sản phẩm</h2>
      <p class="text-muted mb-0">Danh sách toàn bộ danh mục sản phẩm</p>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 text-nowrap">
        <thead class="table-light">
          <tr>
            <th class="ps-4">Danh mục</th>
            <th>Slug</th>
            <th>Mô tả</th>
            <th>Sản phẩm</th>
            <th>Trạng thái</th>
            <th>Ngày tạo</th>
            <th class="text-end pe-4">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($categories as $category)
            <tr>
              <td class="ps-4 fw-semibold">{{ $category->name }}</td>
              <td><code>{{ $category->slug }}</code></td>
              <td class="text-wrap" style="min-width: 220px;">{{ $category->description ?: '—' }}</td>
              <td>{{ $category->products_count }}</td>
              <td>
                <span class="badge {{ $category->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">
                  {{ $category->status === 'active' ? 'Hoạt động' : 'Ngừng hoạt động' }}
                </span>
              </td>
              <td>{{ $category->created_at->format('d/m/Y') }}</td>
              <td class="text-end pe-4">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editCategoryModal{{ $category->id }}" aria-label="Sửa {{ $category->name }}">
                  <i class="ti ti-edit"></i>
                </button>
                <form action="{{ route('categories.destroy', $category) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa danh mục này?');">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Xóa {{ $category->name }}"><i class="ti ti-trash"></i></button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center text-muted py-5">Chưa có danh mục nào. Hãy thêm danh mục đầu tiên.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if ($categories->hasPages())
      <div class="card-footer bg-white">{{ $categories->links() }}</div>
    @endif
  </div>

  <div class="modal fade" id="createCategoryModal" tabindex="-1" aria-labelledby="createCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
      <form action="{{ route('categories.store') }}" method="POST">
        @csrf
        <div class="modal-header"><h2 class="modal-title fs-5" id="createCategoryModalLabel">Thêm danh mục</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
        <div class="modal-body">
          @include('categories.partials.form', ['category' => null])
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button><button type="submit" class="btn btn-primary">Thêm danh mục</button></div>
      </form>
    </div></div>
  </div>

  @foreach ($categories as $category)
    <div class="modal fade" id="editCategoryModal{{ $category->id }}" tabindex="-1" aria-labelledby="editCategoryModalLabel{{ $category->id }}" aria-hidden="true">
      <div class="modal-dialog"><div class="modal-content">
        <form action="{{ route('categories.update', $category) }}" method="POST">
          @csrf
          @method('PUT')
          <div class="modal-header"><h2 class="modal-title fs-5" id="editCategoryModalLabel{{ $category->id }}">Chỉnh sửa danh mục</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
          <div class="modal-body">
            @include('categories.partials.form', ['category' => $category])
          </div>
          <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button><button type="submit" class="btn btn-primary">Lưu thay đổi</button></div>
        </form>
      </div></div>
    </div>
  @endforeach
@endsection
