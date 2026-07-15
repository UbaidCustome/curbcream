<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Login') · CurbCream Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:wght@600;700&display=swap" rel="stylesheet">
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
</head>
<body class="auth-body">
    <div class="auth-card">
        @yield('content')
    </div>
    <div id="toast-stack" class="toast-stack"></div>
    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            @if(session('success'))
                AdminUI.toast(@json(session('success')), 'success');
            @endif
            @if(session('error'))
                AdminUI.toast(@json(session('error')), 'error');
            @endif
            @if($errors->any())
                AdminUI.toast(@json($errors->first()), 'error');
            @endif
        });
    </script>
</body>
</html>
