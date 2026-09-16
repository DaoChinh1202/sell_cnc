<aside id="sidebar" class="sidebar">
    <div class="logo-area">
        <a href="{{ route('dashboard') }}" class="d-inline-flex">
            <img src="{{ asset('assets/images/logo-duy-hoang-gold-brown.svg') }}" alt="Duy Hoàng CNC" width="48" height="48">
        </a>
    </div>
    <ul class="nav flex-column">
        <li class="px-4 py-2"><small class="nav-text">Chính</small></li>
        <li><a class="nav-link {{ ($navActive ?? null) === 'dashboard' ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="ti ti-home"></i><span class="nav-text">Tổng quan</span></a></li>
        <li><a class="nav-link {{ ($navActive ?? null) === 'inventory' ? 'active' : '' }}" href="{{ route('inventory') }}"><i class="ti ti-box-seam"></i><span class="nav-text">Kho hàng</span></a></li>
        <li><a class="nav-link {{ ($navActive ?? null) === 'products.create' ? 'active' : '' }}" href="{{ route('products.create') }}"><i class="ti ti-plus"></i><span class="nav-text">Thêm sản phẩm</span></a></li>
        <li><a class="nav-link {{ ($navActive ?? null) === 'categories' ? 'active' : '' }}" href="{{ route('categories.index') }}"><i class="ti ti-category"></i><span class="nav-text">Danh mục</span></a></li>
        <li><a class="nav-link {{ ($navActive ?? null) === 'reports' ? 'active' : '' }}" href="{{ route('reports') }}"><i class="ti ti-receipt"></i><span class="nav-text">Báo cáo</span></a></li>
        <li><a class="nav-link {{ ($navActive ?? null) === 'errors.404' ? 'active' : '' }}" href="{{ route('errors.404') }}"><i class="ti ti-alert-circle"></i><span class="nav-text">Lỗi 404</span></a></li>
        <li><a class="nav-link {{ ($navActive ?? null) === 'docs' ? 'active' : '' }}" href="{{ route('docs') }}"><i class="ti ti-file-text"></i><span class="nav-text">Tài liệu</span></a></li>

        <li class="px-4 pt-4 pb-2"><small class="nav-text">Tài khoản</small></li>
        <li><a class="nav-link" href="{{ route('signin') }}"><i class="ti ti-logout"></i><span class="nav-text">Đăng nhập</span></a></li>
        <li><a class="nav-link" href="{{ route('signup') }}"><i class="ti ti-user-plus"></i><span class="nav-text">Đăng ký</span></a></li>
    </ul>
</aside>
