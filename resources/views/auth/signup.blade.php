@extends('layouts.auth')

@section('title', 'Đăng ký · InApp Admin')
@section('content')
<div class="container d-flex align-items-center justify-content-center min-vh-100">
    <div class="card" style="max-width:420px; width:100%;">
        <div class="card-body p-5">
            <div class="text-center mb-3">
                <a href="{{ route('dashboard') }}" class="mb-4 d-inline-block">
                    <img src="{{ asset('assets/images/logo-duy-hoang-gold-brown.svg') }}" alt="Duy Hoàng CNC" width="88" height="88">
                </a>
                <h1 class="card-title mb-5 h5">Tạo tài khoản</h1>
            </div>

            <form class="needs-validation mt-3" novalidate>
                <div class="mb-3">
                    <label for="fullName" class="form-label">Họ và tên</label>
                    <input id="fullName" type="text" class="form-control" placeholder="Nguyễn Văn A" required>
                    <div class="invalid-feedback">Vui lòng nhập họ tên.</div>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input id="email" type="email" class="form-control" placeholder="name@example.com" required>
                    <div class="invalid-feedback">Vui lòng nhập email hợp lệ.</div>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Mật khẩu</label>
                    <input id="password" type="password" class="form-control" placeholder="Tạo mật khẩu" required minlength="6">
                    <div class="invalid-feedback">Vui lòng nhập mật khẩu (tối thiểu 6 ký tự).</div>
                </div>
                <div class="mb-3">
                    <label for="confirmPassword" class="form-label">Xác nhận mật khẩu</label>
                    <input id="confirmPassword" type="password" class="form-control" placeholder="Nhập lại mật khẩu" required minlength="6" oninput="this.setCustomValidity(document.getElementById('password').value !== this.value ? 'Mật khẩu không trùng khớp.' : '')">
                    <div class="invalid-feedback">Mật khẩu phải trùng khớp.</div>
                </div>
                <div class="mb-3 form-check">
                    <input id="terms" class="form-check-input" type="checkbox" required>
                    <label class="form-check-label small" for="terms">Tôi đồng ý với <a href="#" class="text-decoration-none">điều khoản và chính sách bảo mật</a></label>
                    <div class="invalid-feedback">Bạn phải đồng ý trước khi tiếp tục.</div>
                </div>
                <button class="btn btn-primary w-100" type="submit">Đăng ký</button>
            </form>
            <div class="text-center mt-3 small text-muted">
                Đã có tài khoản? <a href="{{ route('signin') }}" class="link-primary">Đăng nhập</a>
            </div>
        </div>
    </div>
</div>
@endsection
