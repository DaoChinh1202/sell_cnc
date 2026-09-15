@extends('layouts.auth')

@section('title', 'Đăng nhập · InApp Admin')
@section('content')
<div class="container d-flex align-items-center justify-content-center min-vh-100">
    <div class="card" style="max-width:420px; width:100%;">
        <div class="card-body p-5">
            <div class="text-center mb-3">
                <a href="{{ route('dashboard') }}" class="mb-4 d-inline-block">
                    <img src="{{ asset('assets/images/logo-icon.svg') }}" alt="" width="36">
                    <span class="ms-2"><img src="{{ asset('assets/images/logo.svg') }}" alt=""></span>
                </a>
                <h1 class="card-title mb-5 h5">Chào mừng trở lại</h1>
            </div>

            <form class="needs-validation mt-3" novalidate>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input id="email" type="email" class="form-control" placeholder="name@example.com" required autofocus>
                    <div class="invalid-feedback">Vui lòng nhập email hợp lệ.</div>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label d-flex justify-content-between">
                        <span>Mật khẩu</span>
                        <a href="#" class="small link-primary">Quên mật khẩu?</a>
                    </label>
                    <input id="password" type="password" class="form-control" placeholder="Mật khẩu" required minlength="6">
                    <div class="invalid-feedback">Vui lòng nhập mật khẩu (tối thiểu 6 ký tự).</div>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check">
                        <input id="remember" class="form-check-input" type="checkbox">
                        <label class="form-check-label small" for="remember">Ghi nhớ tôi</label>
                    </div>
                </div>
                <button class="btn btn-primary w-100" type="submit">Đăng nhập</button>
            </form>
            <div class="text-center mt-3 small text-muted">
                Chưa có tài khoản? <a href="{{ route('signup') }}" class="link-primary">Đăng ký</a>
            </div>
        </div>
    </div>
</div>
@endsection
