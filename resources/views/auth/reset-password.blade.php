@extends('layouts.auth')

@section('title', 'Đặt lại mật khẩu quản trị · khomau3d CNC')
@section('content')
<div class="container d-flex align-items-center justify-content-center min-vh-100 py-4">
    <div class="card" style="max-width:460px; width:100%;">
        <div class="card-body p-4 p-sm-5">
            <div class="text-center mb-4">
                <a href="{{ route('home') }}" class="mb-3 d-inline-block"><img src="{{ asset('assets/images/khomau3d_logo_no_tagline.svg') }}" alt="Kho mẫu 3D" width="132" height="88"></a>
                <h1 class="h5">Đặt lại mật khẩu</h1>
                <p id="password-help" class="small text-muted">Dùng ít nhất 8 ký tự, gồm chữ hoa, chữ thường, chữ số và ký tự đặc biệt.</p>
            </div>
            @if ($errors->any())
                <div class="alert alert-danger small" role="alert">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $email }}">
                <div class="mb-3">
                    <label for="password" class="form-label">Mật khẩu mới</label>
                    <input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password" aria-describedby="password-help" minlength="8" maxlength="128" required autofocus>
                </div>
                <div class="mb-3">
                    <label for="password_confirmation" class="form-label">Nhập lại mật khẩu mới</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" autocomplete="new-password" minlength="8" maxlength="128" required>
                </div>
                <button class="btn btn-primary w-100" type="submit">Lưu mật khẩu mới</button>
            </form>
            <div class="text-center mt-3 small"><a href="{{ route('password.request') }}">Yêu cầu liên kết mới</a></div>
            <div class="text-center mt-2 small"><a href="{{ route('signin') }}">Quay lại đăng nhập</a></div>
        </div>
    </div>
</div>
@endsection
