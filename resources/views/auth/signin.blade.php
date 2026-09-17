@extends('layouts.auth')

@section('title', 'Đăng nhập quản trị · Duy Hoàng CNC')
@section('content')
<div class="container d-flex align-items-center justify-content-center min-vh-100 py-4">
    <div class="card" style="max-width:420px; width:100%;">
        <div class="card-body p-4 p-sm-5">
            <div class="text-center mb-4">
                <a href="{{ route('home') }}" class="mb-3 d-inline-block">
                    <img src="{{ asset('assets/images/logo-duy-hoang-gold-brown.svg') }}" alt="Duy Hoàng CNC" width="88" height="88">
                </a>
                <h1 class="card-title h5">Đăng nhập quản trị</h1>
                <p class="small text-muted">Quản lý kho mẫu thiết kế CNC Duy Hoàng.</p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger small" role="alert">{{ $errors->first() }}</div>
            @endif

            <form action="{{ route('signin.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="username" class="form-label">Tên đăng nhập</label>
                    <input id="username" name="username" type="text" class="form-control @error('username') is-invalid @enderror"
                           value="{{ old('username') }}" autocomplete="username" autocapitalize="none" spellcheck="false"
                           maxlength="100" required autofocus>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Mật khẩu</label>
                    <input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror"
                           autocomplete="current-password" required>
                </div>
                <div class="form-check mb-3">
                    <input id="remember" name="remember" value="1" class="form-check-input" type="checkbox" @checked(old('remember'))>
                    <label class="form-check-label small" for="remember">Ghi nhớ đăng nhập</label>
                </div>
                <button class="btn btn-primary w-100" type="submit">Đăng nhập</button>
            </form>
            <div class="text-center mt-3 small">
                <a href="{{ route('home') }}">Về kho mẫu CNC</a>
            </div>
        </div>
    </div>
</div>
@endsection
