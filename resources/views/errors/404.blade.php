@extends('layouts.app')

@section('title', 'Không tìm thấy trang')

@section('content')
  <div class="container d-flex align-items-center justify-content-center min-vh-100">
    <div class="" style="max-width: 500px; width: 100%;">
      <div class="text-center">
        <div class="mb-4">
          <a href="{{ route('dashboard') }}" class="d-inline-block mb-4">
            <img src="{{ asset('assets/images/logo-icon.svg') }}" alt="" width="36">
            <span class="ms-2"><img src="{{ asset('assets/images/logo.svg') }}" alt=""></span>
          </a>
        </div>

        <h1 class="display-1 fw-bold text-primary mb-2">404</h1>
        <h2 class="card-title h4 mb-3">Không tìm thấy trang</h2>
        <p class="text-muted mb-4">Rất tiếc, trang bạn đang tìm kiếm không tồn tại hoặc đã được chuyển đi.</p>

        <a href="{{ route('dashboard') }}" class="btn btn-primary">Về trang chủ</a>
      </div>
    </div>
  </div>
@endsection
