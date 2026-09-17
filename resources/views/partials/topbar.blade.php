<nav id="topbar" class="navbar bg-white border-bottom fixed-top topbar px-3">
    <button id="toggleBtn" class="d-none d-lg-inline-flex btn btn-light btn-icon btn-sm" aria-label="Thu gọn thanh bên">
        <i class="ti ti-layout-sidebar-left-expand"></i>
    </button>

    <button id="mobileBtn" class="btn btn-light btn-icon btn-sm d-lg-none me-2" aria-label="Mở thanh bên">
        <i class="ti ti-layout-sidebar-left-expand"></i>
    </button>
    <div>
        <ul class="list-unstyled d-flex align-items-center mb-0 gap-1">
            <li>
                <a class="position-relative btn-icon btn-sm btn-light btn rounded-circle" data-bs-toggle="dropdown"
                   aria-expanded="false" href="#" role="button" aria-label="Thông báo">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                         class="icon icon-tabler icons-tabler-outline icon-tabler-bell">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M10 5a2 2 0 1 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6" />
                        <path d="M9 17v1a3 3 0 0 0 6 0v-1" />
                    </svg>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger mt-2 ms-n2">
                        2 <span class="visually-hidden">thông báo chưa đọc</span>
                    </span>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-md p-0">
                    <ul class="list-unstyled p-0 m-0">
                        <li class="p-3 border-bottom">
                            <div class="d-flex gap-3">
                                <img src="{{ asset('assets/images/avatar/avatar-1.jpg') }}" alt="" class="avatar avatar-sm rounded-circle">
                                <div class="flex-grow-1 small">
                                    <p class="mb-0">Có đơn hàng mới</p>
                                    <p class="mb-1">Đơn hàng #12345 đã được đặt</p>
                                    <div class="text-secondary">5 phút trước</div>
                                </div>
                            </div>
                        </li>
                        <li class="p-3 border-bottom">
                            <div class="d-flex gap-3">
                                <img src="{{ asset('assets/images/avatar/avatar-4.jpg') }}" alt="" class="avatar avatar-sm rounded-circle">
                                <div class="flex-grow-1 small">
                                    <p class="mb-0">Người dùng mới đăng ký</p>
                                    <p class="mb-1">Người dùng @john_doe đã đăng ký</p>
                                    <div class="text-secondary">30 phút trước</div>
                                </div>
                            </div>
                        </li>
                        <li class="p-3 border-bottom">
                            <div class="d-flex gap-3">
                                <img src="{{ asset('assets/images/avatar/avatar-2.jpg') }}" alt="" class="avatar avatar-sm rounded-circle">
                                <div class="flex-grow-1 small">
                                    <p class="mb-0">Đã xác nhận thanh toán</p>
                                    <p class="mb-1">Đã nhận khoản thanh toán $299</p>
                                    <div class="text-secondary">1 giờ trước</div>
                                </div>
                            </div>
                        </li>
                        <li class="px-4 py-3 text-center"><a href="#" class="text-primary">Xem tất cả thông báo</a></li>
                    </ul>
                </div>
            </li>
            <li class="ms-3 dropdown">
                <a href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="{{ asset('assets/images/avatar/avatar-1.jpg') }}" alt="" class="avatar avatar-sm rounded-circle">
                </a>
                <div class="dropdown-menu dropdown-menu-end p-0" style="min-width: 200px;">
                    <div class="d-flex gap-3 align-items-center border-dashed border-bottom px-3 py-3">
                        <img src="{{ asset('assets/images/avatar/avatar-1.jpg') }}" alt="" class="avatar avatar-md rounded-circle">
                        <div><h4 class="mb-0 small">{{ auth()->user()?->name }}</h4><p class="mb-0 small">{{ auth()->user()?->username }}</p></div>
                    </div>
                    <div class="p-3 d-flex flex-column gap-1 small lh-lg">
                        <a href="{{ route('dashboard') }}">Trang chủ</a>
                        @auth
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="btn btn-link p-0 text-danger">Đăng xuất</button>
                            </form>
                        @endauth
                    </div>
                </div>
            </li>
        </ul>
    </div>
</nav>
