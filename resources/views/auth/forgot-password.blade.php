@extends('layouts.auth')

@section('title', 'Quên mật khẩu quản trị · khomau3d CNC')
@section('content')
<div class="container d-flex align-items-center justify-content-center min-vh-100 py-4">
    <div class="card" style="max-width:460px; width:100%;">
        <div class="card-body p-4 p-sm-5">
            <div class="text-center mb-4">
                <a href="{{ route('home') }}" class="mb-3 d-inline-block"><img src="{{ asset('assets/images/khomau3d_logo_no_tagline.svg') }}" alt="Kho mẫu 3D" width="132" height="88"></a>
                <h1 class="h5">Quên mật khẩu?</h1>
                <p class="small text-muted">Nhập tên đăng nhập quản trị. Liên kết đặt lại mật khẩu sẽ được gửi tới email khôi phục của tài khoản.</p>
            </div>
            @if (session('status'))
                <div class="alert alert-success small" role="status">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger small" role="alert">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="mb-3">
                    <label for="username" class="form-label">Tên đăng nhập</label>
                    <input id="username" name="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username') }}" autocomplete="username" autocapitalize="none" spellcheck="false" maxlength="100" required autofocus>
                </div>
                <button class="btn btn-primary w-100" type="submit">Gửi liên kết khôi phục</button>
            </form>
            <p class="small text-muted mt-3">Nếu chưa có email khôi phục hoặc không nhận được thư, hãy liên hệ người quản lý hệ thống.</p>
            <div class="text-center mt-3 small"><a href="{{ route('signin') }}">Quay lại đăng nhập</a></div>
        </div>
    </div>
</div>
@endsection
