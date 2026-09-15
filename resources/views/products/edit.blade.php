@extends('layouts.app')

@section('title', 'Chỉnh sửa sản phẩm')

@section('content')
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
      <h1 class="fs-3 mb-1">Chỉnh sửa sản phẩm</h1>
      <p class="mb-0">Cập nhật thông tin sản phẩm</p>
    </div>
    <a href="{{ route('inventory') }}" class="btn btn-outline-secondary">
      <i class="ti ti-arrow-left me-1"></i> Quay lại kho hàng
    </a>
  </div>

  <form id="editProductForm" action="#" method="POST" enctype="multipart/form-data">
    <div class="card mb-4">
      <div class="card-header bg-white py-3">
        <h2 class="h5 mb-0">Thông tin sản phẩm</h2>
      </div>
      <div class="card-body p-4">
        <div class="row g-3">
          <div class="col-md-6">
            <label for="productName" class="form-label">Tên sản phẩm</label>
            <input type="text" class="form-control" id="productName" name="name" value="{{ $product['name'] }}" required>
          </div>
          <div class="col-md-6">
            <label for="productSku" class="form-label">SKU</label>
            <input type="text" class="form-control" id="productSku" name="sku" value="{{ $product['sku'] }}" required>
          </div>
          <div class="col-md-6">
            <label for="productCategory" class="form-label">Danh mục</label>
            <select class="form-select" id="productCategory" name="category" required>
              <option value="electronics" @selected($product['category'] === 'electronics')>Điện tử</option>
              <option value="clothing" @selected($product['category'] === 'clothing')>Thời trang</option>
              <option value="food" @selected($product['category'] === 'food')>Thực phẩm</option>
              <option value="accessories" @selected($product['category'] === 'accessories')>Phụ kiện</option>
            </select>
          </div>
          <div class="col-md-6">
            <label for="productBrand" class="form-label">Thương hiệu</label>
            <select class="form-select" id="productBrand" name="brand" required>
              <option value="brand-name" @selected($product['brand'] === 'brand-name')>Brand Name</option>
              <option value="tech-pro" @selected($product['brand'] === 'tech-pro')>Tech Pro</option>
              <option value="apple" @selected($product['brand'] === 'apple')>Apple</option>
              <option value="dell" @selected($product['brand'] === 'dell')>Dell</option>
            </select>
          </div>
          <div class="col-12">
            <label for="productDescription" class="form-label">Mô tả</label>
            <textarea class="form-control" id="productDescription" name="description" rows="4" placeholder="Nhập mô tả sản phẩm">{{ $product['name'] }} là sản phẩm chất lượng cao, sẵn sàng phục vụ khách hàng.</textarea>
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header bg-white py-3">
        <h2 class="h5 mb-0">Giá và tồn kho</h2>
      </div>
      <div class="card-body p-4">
        <div class="row g-3">
          <div class="col-md-4">
            <label for="productPrice" class="form-label">Giá ($)</label>
            <input type="number" class="form-control" id="productPrice" name="price" value="{{ $product['price'] }}" min="0" step="0.01" required>
          </div>
          <div class="col-md-4">
            <label for="productTax" class="form-label">Thuế (%)</label>
            <input type="number" class="form-control" id="productTax" name="tax" value="0" min="0" max="100" step="0.01">
          </div>
          <div class="col-md-4">
            <label for="productDiscount" class="form-label">Giảm giá (%)</label>
            <input type="number" class="form-control" id="productDiscount" name="discount" value="0" min="0" max="100" step="0.01">
          </div>
          <div class="col-md-4">
            <label for="productQuantity" class="form-label">Số lượng</label>
            <input type="number" class="form-control" id="productQuantity" name="quantity" value="{{ $product['quantity'] }}" min="0" required>
          </div>
          <div class="col-md-4">
            <label for="productMinimumQuantity" class="form-label">Số lượng tối thiểu</label>
            <input type="number" class="form-control" id="productMinimumQuantity" name="minimum_quantity" value="10" min="0">
          </div>
          <div class="col-md-4">
            <label for="productUnit" class="form-label">Đơn vị</label>
            <select class="form-select" id="productUnit" name="unit">
              <option value="pcs" selected>Cái</option>
              <option value="kg">Kg</option>
              <option value="box">Hộp</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header bg-white py-3">
        <h2 class="h5 mb-0">Hình ảnh sản phẩm</h2>
      </div>
      <div class="card-body p-4">
        <div class="d-flex align-items-center gap-3 mb-3">
          <img src="{{ asset('assets/images/' . $product['image']) }}" alt="{{ $product['name'] }}" class="avatar avatar-xl rounded">
          <div>
            <div class="fw-semibold">Ảnh hiện tại</div>
            <small class="text-muted">PNG, JPG hoặc WEBP. Kích thước tối đa 5 MB.</small>
          </div>
        </div>
        <label for="productImage" class="form-label">Thay ảnh mới</label>
        <input type="file" class="form-control" id="productImage" name="image" accept="image/png,image/jpeg,image/webp">
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header bg-white py-3">
        <h2 class="h5 mb-0">Trạng thái</h2>
      </div>
      <div class="card-body p-4">
        <label for="productStatus" class="form-label">Trạng thái sản phẩm</label>
        <select class="form-select" id="productStatus" name="status" style="max-width: 360px;">
          <option value="active" selected>Đang hoạt động</option>
          <option value="inactive">Ngừng hoạt động</option>
          <option value="draft">Bản nháp</option>
        </select>
      </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mb-4">
      <a href="{{ route('inventory') }}" class="btn btn-outline-secondary">Hủy</a>
      <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i> Cập nhật sản phẩm</button>
    </div>
  </form>
@endsection
