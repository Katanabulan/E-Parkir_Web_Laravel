<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'E-Parkir') }}</title>
    <!-- Bootstrap 5.3 & Bootstrap Icons CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100">
    <div class="card border-0 shadow-sm rounded-4 p-4 text-center" style="max-width: 420px;">
        <div class="card-body">
            <div class="d-inline-flex align-items-center justify-content-center rounded-3 text-white mb-3" style="width: 3.5rem; height: 3.5rem; background-color: #00719C; font-size: 1.75rem;">
                <i class="bi bi-p-square-fill"></i>
            </div>
            <h1 class="h4 fw-bold text-dark mb-2">E-Parkir Platform</h1>
            <p class="small text-secondary mb-4">Sistem Manajemen &amp; Monitoring Parkir Otomatis Berbasis IoT</p>
            <div class="d-grid gap-2">
                <a href="{{ route('login') }}" class="btn btn-primary fw-bold rounded-3 py-2 shadow-sm" style="background-color: #00719C; border-color: #00719C;">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Masuk ke Portal
                </a>
                <a href="{{ route('status') }}" class="btn btn-outline-secondary fw-semibold rounded-3 py-2">
                    <i class="bi bi-display me-1"></i> Layar Monitor Publik TV
                </a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
