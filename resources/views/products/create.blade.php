@extends('layouts.app')

@section('title', 'Thêm sản phẩm')

@section('content')
  <div class="row">
    <div class="col-12">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
          <h1 class="fs-3 mb-1">Thêm sản phẩm</h1>
          <p class="mb-0">Ảnh tải lên sẽ được chuyển sang WebP và đóng watermark trước khi lưu.</p>
        </div>
        <a href="{{ route('inventory') }}" class="btn btn-primary">Về danh sách kho hàng</a>
      </div>
    </div>
  </div>

  @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
    </div>
  @endif

  @if ($errors->any())
    <div class="alert alert-danger">
      <strong>Không thể lưu sản phẩm.</strong> Vui lòng kiểm tra các trường bên dưới.
    </div>
  @endif

  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-body p-4">
          @if ($categories->isEmpty())
            <div class="alert alert-warning mb-0">Bạn cần <a href="{{ route('categories.index') }}" class="alert-link">tạo ít nhất một danh mục đang hoạt động</a> trước khi thêm sản phẩm.</div>
          @else
            <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
              @csrf
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="productName" class="form-label">Tên sản phẩm</label>
                  <input type="text" class="form-control @error('name') is-invalid @enderror" id="productName" name="name" value="{{ old('name') }}" required>
                  @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6 mb-3">
                  <label for="productSku" class="form-label">Mã SKU</label>
                  <input type="text" class="form-control @error('sku') is-invalid @enderror" id="productSku" name="sku" value="{{ old('sku') }}" required>
                  @error('sku') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="productPrice" class="form-label">Giá</label>
                  <input type="number" class="form-control @error('price') is-invalid @enderror" id="productPrice" name="price" value="{{ old('price') }}" min="0" step="0.01" required>
                  @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6 mb-3">
                  <label for="productQuantity" class="form-label">Số lượng tồn kho</label>
                  <input type="number" class="form-control @error('quantity') is-invalid @enderror" id="productQuantity" name="quantity" value="{{ old('quantity', 0) }}" min="0" required>
                  @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="productCategory" class="form-label">Danh mục</label>
                  <select class="form-select @error('category_id') is-invalid @enderror" id="productCategory" name="category_id" required>
                    <option value="">Chọn danh mục</option>
                    @foreach ($categories as $category)
                      <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                  </select>
                  @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6 mb-3">
                  <label for="productBrand" class="form-label">Thương hiệu</label>
                  <input type="text" class="form-control @error('brand') is-invalid @enderror" id="productBrand" name="brand" value="{{ old('brand') }}">
                  @error('brand') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
              </div>
              <div class="mb-3">
                <label for="productImage" class="form-label">Ảnh sản phẩm</label>
                <input type="file" class="form-control @error('image') is-invalid @enderror" id="productImage" name="image" accept="image/jpeg,image/png,image/webp" required>
                <div class="form-text">Chỉ nhận JPEG, PNG, WebP; tối đa 5 MB; kích thước từ 100×100 đến 6000×6000 px.</div>
                @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="mb-3">
                <label for="productDescription" class="form-label">Mô tả</label>
                <textarea class="form-control @error('description') is-invalid @enderror" id="productDescription" name="description" rows="4">{{ old('description') }}</textarea>
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="mb-4">
                <label for="productStatus" class="form-label">Trạng thái</label>
                <select class="form-select @error('status') is-invalid @enderror" id="productStatus" name="status">
                  <option value="active" @selected(old('status', 'active') === 'active')>Đang hoạt động</option>
                  <option value="inactive" @selected(old('status') === 'inactive')>Ngừng hoạt động</option>
                  <option value="draft" @selected(old('status') === 'draft')>Bản nháp</option>
                </select>
                @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="border rounded p-3 mb-4 bg-light-subtle">
                <div class="form-check form-switch mb-1">
                  <input class="form-check-input" type="checkbox" role="switch" id="productFeatured" name="is_featured" value="1" @checked(old('is_featured'))>
                  <label class="form-check-label fw-semibold" for="productFeatured">Sản phẩm nổi bật</label>
                </div>
                <div class="form-text">Hiển thị sản phẩm trong khu vực “Sản phẩm nổi bật” trên trang chủ.</div>
              </div>
              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Thêm sản phẩm</button>
                <button type="reset" class="btn btn-secondary">Làm mới</button>
              </div>
            </form>
          @endif
        </div>
      </div>
    </div>
  </div>
@endsection
