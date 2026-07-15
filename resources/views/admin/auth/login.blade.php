@extends('admin.layouts.auth')

@section('title', 'Login')

@section('content')
    <div class="mb-4">
        <div class="display-font fs-2 text-dark">CurbCream</div>
        <p class="text-secondary mb-0">Sign in to the admin panel</p>
    </div>

    <form action="{{ route('admin.login.submit') }}" method="POST" class="js-action-form">
        @csrf
        <div class="mb-3">
            <label class="form-label fw-semibold">Email</label>
            <input type="email" name="email" class="form-control form-control-lg" value="{{ old('email', 'admin@curbcream.com') }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">Password</label>
            <input type="password" name="password" class="form-control form-control-lg" value="admin123" required>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <label class="form-check mb-0">
                <input type="checkbox" name="remember" class="form-check-input" value="1">
                <span class="form-check-label">Remember me</span>
            </label>
            <a href="{{ route('admin.forgot-password') }}" class="text-decoration-none fw-semibold" style="color:#1f8f58">Forgot password?</a>
        </div>
        <button type="submit" class="btn btn-success btn-lg w-100 btn-action" style="background:#2bb673;border:0">
            <span class="btn-label">Login</span>
            <span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span> Logging in...</span>
        </button>
    </form>
@endsection
