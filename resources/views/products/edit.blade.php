@extends('layouts.app')

@section('title', 'Chỉnh sửa '.$product->name)

@section('content')
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
      <h1 class="fs-3 mb-1">Chỉnh sửa sản phẩm</h1>
      <p class="mb-0">Cập nhật thông tin và ảnh đã watermark của sản phẩm.</p>
    </div>
    <a href="{{ route('products.show', $product) }}" class="btn btn-outline-secondary"><i class="ti ti-arrow-left me-1"></i> Xem chi tiết</a>
  </div>

  @if ($errors->any())
    <div class="alert alert-danger"><strong>Không thể cập nhật sản phẩm.</strong> Vui lòng kiểm tra các trường bên dưới.</div>
  @endif

  <form action="{{ route('products.update', $product) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="card mb-4"><div class="card-header bg-white py-3"><h2 class="h5 mb-0">Thông tin sản phẩm</h2></div><div class="card-body p-4">
      <div class="row g-3">
        <div class="col-md-6"><label for="productName" class="form-label">Tên sản phẩm</label><input type="text" class="form-control @error('name') is-invalid @enderror" id="productName" name="name" value="{{ old('name', $product->name) }}" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-6"><label for="productSku" class="form-label">SKU</label><input type="text" class="form-control @error('sku') is-invalid @enderror" id="productSku" name="sku" value="{{ old('sku', $product->sku) }}" required>@error('sku')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-6"><label for="productCategory" class="form-label">Danh mục</label><select class="form-select @error('category_id') is-invalid @enderror" id="productCategory" name="category_id" required>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) old('category_id', $product->category_id) === (string) $category->id)>{{ $category->name }}</option>@endforeach</select>@error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>

        <div class="col-12"><label for="productDescription" class="form-label">Mô tả</label><textarea class="form-control @error('description') is-invalid @enderror" id="productDescription" name="description" rows="4">{{ old('description', $product->description) }}</textarea>@error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
      </div>
    </div></div>

    <div class="card mb-4">
      <div class="card-header bg-white py-3"><h2 class="h5 mb-0">Giá sản phẩm</h2></div>
      <div class="card-body p-4">
        <div class="row g-3">
          <div class="col-md-6">
            <label for="productPrice" class="form-label">Giá</label>
            <input type="number" class="form-control @error('price') is-invalid @enderror" id="productPrice" name="price" value="{{ old('price', $product->price) }}" min="0" step="0.01" required>
            @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-4"><div class="card-header bg-white py-3"><h2 class="h5 mb-0">Hình ảnh sản phẩm</h2></div><div class="card-body p-4"><div class="d-flex align-items-center gap-3 mb-3"><img src="{{ '/storage/'.$product->image }}" alt="{{ $product->name }}" class="avatar avatar-xl rounded object-fit-cover"><div><div class="fw-semibold">Ảnh hiện tại</div><small class="text-muted">Đây là ảnh WebP đã watermark.</small></div></div><label for="productImage" class="form-label">Thay ảnh mới</label><input type="file" class="form-control @error('image') is-invalid @enderror" id="productImage" name="image" accept="image/png,image/jpeg,image/webp"><div class="form-text">Nếu thay ảnh, ảnh cũ sẽ bị xóa. Ảnh mới được chuyển sang WebP và watermark trước khi lưu.</div>@error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror</div></div>

    <div class="card mb-4"><div class="card-header bg-white py-3"><h2 class="h5 mb-0">Trạng thái</h2></div><div class="card-body p-4"><label for="productStatus" class="form-label">Trạng thái sản phẩm</label><select class="form-select" id="productStatus" name="status" style="max-width: 360px;"><option value="active" @selected(old('status', $product->status) === 'active')>Đang hoạt động</option><option value="inactive" @selected(old('status', $product->status) === 'inactive')>Ngừng hoạt động</option><option value="draft" @selected(old('status', $product->status) === 'draft')>Bản nháp</option></select></div></div>

    <div class="card mb-4"><div class="card-body p-4"><div class="form-check form-switch mb-1"><input class="form-check-input" type="checkbox" role="switch" id="productFeatured" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured ?? false))><label class="form-check-label fw-semibold" for="productFeatured">Sản phẩm nổi bật</label></div><div class="form-text">Hiển thị sản phẩm trong khu vực “Sản phẩm nổi bật” trên trang chủ.</div></div></div>

    <div class="d-flex justify-content-end gap-2 mb-4"><a href="{{ route('products.show', $product) }}" class="btn btn-outline-secondary">Hủy</a><button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i> Cập nhật sản phẩm</button></div>
  </form>
@endsection
