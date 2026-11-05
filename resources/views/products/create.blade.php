@extends('layouts.app')

@section('title', 'Thêm sản phẩm')

@section('content')
  <div class="row">
    <div class="col-12">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
          <h1 class="fs-3 mb-1">Thêm sản phẩm</h1>
          <p class="mb-0">Ảnh tải lên được chuyển sang WebP. Bạn có thể chọn chèn watermark trước khi lưu.</p>
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
      @if ($errors->has('product'))
        {{ $errors->first('product') }}
      @else
        <strong>Không thể lưu sản phẩm.</strong> Vui lòng kiểm tra các trường bên dưới.
      @endif
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
                  <input type="text" class="form-control" id="productSku" value="Tự động tạo khi lưu sản phẩm" disabled aria-describedby="productSkuHelp">
                  <div id="productSkuHelp" class="form-text">Mã có dạng CNC-XXXXXXXXXX, bạn không cần nhập.</div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="productPrice" class="form-label">Giá</label>
                  <input type="number" class="form-control @error('price') is-invalid @enderror" id="productPrice" name="price" value="{{ old('price') }}" min="0" step="0.01" required>
                  @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
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
              </div>
              <div class="mb-3">
                <label for="productImage" class="form-label">Ảnh sản phẩm</label>
                <input type="file" class="form-control @error('image') is-invalid @enderror" id="productImage" name="image" accept="image/jpeg,image/png,image/webp" required>
                <div class="form-text">Chỉ nhận JPEG, PNG, WebP; tối đa 5 MB; kích thước từ 100×100 đến 6000×6000 px.</div>
                @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                @include('products._watermark-option')
              </div>
              <div class="mb-3">
                <label for="productFreeDownloadUrl" class="form-label">Link tải miễn phí</label>
                <input type="url" class="form-control @error('free_download_url') is-invalid @enderror" id="productFreeDownloadUrl" name="free_download_url" value="{{ old('free_download_url') }}" aria-describedby="productFreeDownloadUrlHelp">
                <div id="productFreeDownloadUrlHelp" class="form-text">Không bắt buộc. Nhập URL chia sẻ Google Drive để khách tải miễn phí; để trống nếu không có.</div>
                @error('free_download_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              @include('products._file-metadata', ['product' => null])
              <div class="mb-3">
                <label for="productTags" class="form-label">Từ khóa</label>
                <textarea class="form-control @error('tags') is-invalid @enderror" id="productTags" name="tags" rows="3" maxlength="2000" aria-describedby="productTagsHelp @error('tags') productTagsError @enderror" @error('tags') aria-invalid="true" @enderror>{{ old('tags') }}</textarea>
                <div id="productTagsHelp" class="form-text">Không bắt buộc. Phân cách bằng dấu phẩy hoặc xuống dòng; tối đa 20 từ khóa, mỗi từ khóa tối đa 60 ký tự, tổng nội dung tối đa 2000 ký tự.</div>
                @error('tags') <div id="productTagsError" class="invalid-feedback">{{ $message }}</div> @enderror
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
