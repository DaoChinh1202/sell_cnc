@extends('layouts.app')

@section('title', 'Thêm sản phẩm')

@section('content')
  <div class="row">
    <div class="col-12">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div class="">
          <h1 class="fs-3 mb-1">Thêm sản phẩm</h1>
          <p class="mb-0">Quản lý các mặt hàng kho của bạn</p>
        </div>
        <div>
          <a href="{{ route('inventory') }}" class="btn btn-primary">Về danh sách kho hàng</a>
        </div>
      </div>
    </div>
  </div>
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-body p-4">
          <form id="addProductForm">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="productName" class="form-label">Tên sản phẩm</label>
                <input type="text" class="form-control" id="productName" placeholder="Nhập tên sản phẩm..." required>
              </div>
              <div class="col-md-6 mb-3">
                <label for="productSKU" class="form-label">Mã SKU</label>
                <input type="text" class="form-control" id="productSKU" placeholder="Nhập mã SKU" required>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="productPrice" class="form-label">Giá</label>
                <input type="number" class="form-control" id="productPrice" placeholder="0.00" step="0.01" required>
              </div>
              <div class="col-md-6 mb-3">
                <label for="productStock" class="form-label">Số lượng tồn kho</label>
                <input type="number" class="form-control" id="productStock" placeholder="0" required>
              </div>
            </div>

            <div class="mb-3">
              <label for="productCategory" class="form-label">Danh mục</label>
              <select class="form-select" id="productCategory" required>
                <option value="">Chọn danh mục</option>
                <option value="electronics">Điện tử</option>
                <option value="clothing">Thời trang</option>
                <option value="food">Thực phẩm</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="productImage" class="form-label">Ảnh sản phẩm</label>
              <input type="file" class="form-control" id="productImage" accept="image/*" required>
            </div>
            <div class="mb-3">
              <label for="productDescription" class="form-label">Mô tả</label>
              <textarea class="form-control" id="productDescription" rows="4"
                placeholder="Nhập mô tả sản phẩm"></textarea>
            </div>
            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary">Thêm sản phẩm</button>
              <button type="reset" class="btn btn-secondary">Làm mới</button>
            </div>

          </form>
        </div>
      </div>


    </div>

  </div>
@endsection
