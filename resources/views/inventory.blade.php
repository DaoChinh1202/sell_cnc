@extends('layouts.app')

@section('title', 'Kho hàng')

@section('content')
  <div class="row">
    <div class="col-12">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="">
          <h1 class="fs-3 mb-1">Kho hàng</h1>
          <p class="mb-0">Quản lý kho hàng sản phẩm của bạn</p>
        </div>
        <div>
          <a href="{{ route('products.create') }}" class="btn btn-primary">Thêm sản phẩm</a>
        </div>
      </div>
    </div>
  </div>
  <div class="row">
    <div class="col-12">
      <div>
        <div class="d-flex gap-2 mb-3 flex-wrap justify-content-between">
          <input type="text" class="form-control" placeholder="Tìm sản phẩm..." style="max-width: 250px;">
          <div class="d-flex gap-2">

            <button class="btn btn-outline-secondary">
              <i class="ti ti-filter"></i> Bộ lọc
            </button>
            <button class="btn btn-outline-secondary">
              <i class="ti ti-file-excel"></i> Excel
            </button>
            <button class="btn btn-outline-secondary">
              <i class="ti ti-file-pdf"></i> PDF
            </button>
          </div>
        </div>
      </div>
      <div class="card table-responsive ">
        <table class="table mb-0 text-nowrap table-hover">
          <thead class="table-light border-light">
            <tr>
              <th>Ảnh</th>

              <th>Mã SP</th>
              <th>Danh mục</th>
              <th>Thương hiệu</th>
              <th>Giá</th>
              <th>Đơn vị</th>
              <th>Số lượng</th>
              <th>Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr class="align-middle ">
              <td><a href=""><img src="{{ asset('assets/images/product-1.png') }}" alt="" class="avatar avatar-md rounded" /><span class="ms-3">Gaming Joy Stick</span></a>
              </td>

              <td>PRD001</td>
              <td>Điện tử</td>
              <td>Brand Name</td>
              <td>$99.99</td>
              <td>pcs</td>
              <td>150</td>
              <td class="">
                <a href="{{ route('products.edit', 'PRD001') }}" aria-label="Sửa Gaming Joy Stick"><i class="ti ti-edit"></i></a>
                <a href="#" class="link-danger"><i class="ti ti-trash ms-2"></i></a>
              </td>
            </tr>
            <tr class="align-middle">
              <td><a href=""><img src="{{ asset('assets/images/product-2.png') }}" alt="" class="avatar avatar-md rounded" /><span class="ms-3">Wireless Earphones</span></a>
              </td>
              <td>PRD002</td>
              <td>Điện tử</td>
              <td>Tech Pro</td>
              <td>$89.99</td>
              <td>pcs</td>
              <td>320</td>
              <td class="">
                <a href="{{ route('products.edit', 'PRD002') }}" aria-label="Sửa Wireless Earphones"><i class="ti ti-edit"></i></a>
                <a href="#" class="link-danger"><i class="ti ti-trash ms-2"></i></a>
              </td>
            </tr>
            <tr class="align-middle">
              <td><a href=""><img src="{{ asset('assets/images/product-3.png') }}" alt="" class="avatar avatar-md rounded" /><span class="ms-3">Smart Watch Pro</span></a>
              </td>
              <td>PRD003</td>
              <td>Điện tử</td>
              <td>Tech Pro</td>
              <td>$98.00</td>
              <td>pcs</td>
              <td>200</td>
              <td class="">
                <a href="{{ route('products.edit', 'PRD003') }}" aria-label="Sửa Smart Watch Pro"><i class="ti ti-edit"></i></a>
                <a href="#" class="link-danger"><i class="ti ti-trash ms-2"></i></a>
              </td>
            </tr>
            <tr class="align-middle">
              <td><a href=""><img src="{{ asset('assets/images/product-4.png') }}" alt="" class="avatar avatar-md rounded" /><span class="ms-3">USB-C Fast Charger</span></a>
              </td>
              <td>PRD004</td>
              <td>Điện tử</td>
              <td>Tech Pro</td>
              <td>$86.00</td>
              <td>pcs</td>
              <td>80</td>
              <td class="">
                <a href="{{ route('products.edit', 'PRD004') }}" aria-label="Sửa USB-C Fast Charger"><i class="ti ti-edit"></i></a>
                <a href="#" class="link-danger"><i class="ti ti-trash ms-2"></i></a>
              </td>
            </tr>
            <tr class="align-middle">
              <td><a href=""><img src="{{ asset('assets/images/product-5.png') }}" alt="" class="avatar avatar-md rounded" /><span class="ms-3">Portable Bluetooth Speaker</span></a>
              </td>
              <td>PRD005</td>
              <td>Điện tử</td>
              <td>Tech Pro</td>
              <td>$32.00</td>
              <td>pcs</td>
              <td>110</td>
              <td class="">
                <a href="{{ route('products.edit', 'PRD005') }}" aria-label="Sửa Portable Bluetooth Speaker"><i class="ti ti-edit"></i></a>
                <a href="#" class="link-danger"><i class="ti ti-trash ms-2"></i></a>
              </td>
            </tr>
            <tr class="align-middle">
              <td><a href=""><img src="{{ asset('assets/images/product-6.png') }}" alt="" class="avatar avatar-md rounded" /><span class="ms-3">Magic Keyboard</span></a>
              </td>
              <td>PRD006</td>
              <td>Điện tử</td>
              <td>Tech Pro</td>
              <td>$49.00</td>
              <td>pcs</td>
              <td>10</td>
              <td class="">
                <a href="{{ route('products.edit', 'PRD006') }}" aria-label="Sửa Magic Keyboard"><i class="ti ti-edit"></i></a>
                <a href="#" class="link-danger"><i class="ti ti-trash ms-2"></i></a>
              </td>
            </tr>
            <tr class="align-middle">
              <td><a href=""><img src="{{ asset('assets/images/product-7.png') }}" alt="" class="avatar avatar-md rounded" /><span class="ms-3">MacBook Pro 16&quot;</span></a>
              </td>
              <td>PRD007</td>
              <td>Điện tử</td>
              <td>Tech Pro</td>
              <td>$99.00</td>
              <td>pcs</td>
              <td>10</td>
              <td class="">
                <a href="{{ route('products.edit', 'PRD007') }}" aria-label="Sửa MacBook Pro 16 inch"><i class="ti ti-edit"></i></a>
                <a href="#" class="link-danger"><i class="ti ti-trash ms-2"></i></a>
              </td>
            </tr>
            <tr class="align-middle">
              <td><a href=""><img src="{{ asset('assets/images/product-8.png') }}" alt="" class="avatar avatar-md rounded" /><span class="ms-3">Wireless Earphones</span></a>
              </td>
              <td>PRD008</td>
              <td>Điện tử</td>
              <td>Tech Pro</td>
              <td>$109.00</td>
              <td>pcs</td>
              <td>200</td>
              <td class="">
                <a href="{{ route('products.edit', 'PRD008') }}" aria-label="Sửa Wireless Earphones"><i class="ti ti-edit"></i></a>
                <a href="#" class="link-danger"><i class="ti ti-trash ms-2"></i></a>
              </td>
            </tr>
          </tbody>
          <tfoot class="">

            <tr>
              <td class="border-bottom-0">Số sản phẩm mỗi trang</td>
              <td colspan="9" class="border-bottom-0">
                <nav aria-label="Điều hướng trang" class="d-flex justify-content-end">
                  <ul class="pagination mb-0">
                    <li class="page-item disabled">
                      <a class="page-link" href="#" tabindex="-1">Trước</a>
                    </li>
                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item">
                      <a class="page-link" href="#">Sau</a>
                    </li>
                  </ul>
                </nav>
              </td>
            </tr>

          </tfoot>
        </table>
      </div>


    </div>

  </div>
@endsection
