@extends('admin.layouts.auth')

@section('title', 'Forgot Password')

@section('content')
    <div class="mb-4">
        <div class="display-font fs-2 text-dark">Reset password</div>
        <p class="text-secondary mb-0">
            {{ session('show_reset') ? 'Enter OTP and your new password.' : 'Enter your admin email to receive an OTP.' }}
        </p>
    </div>

    @unless(session('show_reset'))
        <form action="{{ route('admin.forgot-password.send') }}" method="POST" class="js-action-form">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold">Email</label>
                <input type="email" name="email" class="form-control form-control-lg" value="{{ old('email') }}" required>
            </div>
            <button type="submit" class="btn btn-success btn-lg w-100 btn-action" style="background:#2bb673;border:0">
                <span class="btn-label">Send OTP</span>
                <span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span> Sending...</span>
            </button>
        </form>
    @else
        @if(session('dev_otp'))
            <div class="alert alert-success">Dev OTP: <strong>{{ session('dev_otp') }}</strong></div>
        @endif
        <form action="{{ route('admin.reset-password') }}" method="POST" class="js-action-form">
            @csrf
            <input type="hidden" name="email" value="{{ session('otp_email', old('email')) }}">
            <div class="mb-3">
                <label class="form-label fw-semibold">OTP</label>
                <input type="text" name="otp" class="form-control form-control-lg" maxlength="6" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">New password</label>
                <input type="password" name="password" class="form-control form-control-lg" minlength="6" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Confirm password</label>
                <input type="password" name="password_confirmation" class="form-control form-control-lg" minlength="6" required>
            </div>
            <button type="submit" class="btn btn-success btn-lg w-100 btn-action" style="background:#2bb673;border:0">
                <span class="btn-label">Reset password</span>
                <span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span> Resetting...</span>
            </button>
        </form>
    @endunless

    <div class="text-center mt-3">
        <a href="{{ route('admin.login') }}" class="fw-semibold text-decoration-none" style="color:#1f8f58">Back to login</a>
    </div>
@endsection
