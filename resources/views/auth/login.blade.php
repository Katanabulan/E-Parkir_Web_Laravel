<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - E-Parkir IoT (Sigma Boys)</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100 py-4" style="font-family: 'Plus Jakarta Sans', system-ui, sans-serif;">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-10 col-lg-8 col-xl-7">
                
                <!-- Main Login Card -->
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="row g-0">
                        
                        <!-- Left Info / Brand Column -->
                        <div class="col-md-5 bg-primary text-white p-4 p-lg-5 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-4">
                                    <span class="badge bg-white text-primary p-2 fs-5 rounded-3">
                                        <i class="bi bi-p-square-fill"></i>
                                    </span>
                                    <div>
                                        <h5 class="fw-bold mb-0 text-white">E-Parkir</h5>
                                        <small class="badge bg-warning text-dark fw-bold">IoT GATE</small>
                                    </div>
                                </div>
                                <h4 class="fw-bold mb-2">Smart Barrier Gate</h4>
                                <p class="text-white-50 small mb-4">Sistem kendali otomatis RFID & penagihan cerdas terintegrasi.</p>
                                
                                <ul class="list-unstyled small text-white-50 d-flex flex-column gap-2 mb-0">
                                    <li class="d-flex align-items-center gap-2">
                                        <i class="bi bi-check-circle-fill text-warning"></i>
                                        <span>RFID Reader RC522</span>
                                    </li>
                                    <li class="d-flex align-items-center gap-2">
                                        <i class="bi bi-check-circle-fill text-warning"></i>
                                        <span>Servo Palang Presisi</span>
                                    </li>
                                    <li class="d-flex align-items-center gap-2">
                                        <i class="bi bi-check-circle-fill text-warning"></i>
                                        <span>Flat Tarif Rp2.000 / Jam</span>
                                    </li>
                                </ul>
                            </div>

                            <div class="pt-4 border-top border-white-50 text-white-50 small mt-4">
                                &copy; {{ date('Y') }} Tim Sigma Boys
                            </div>
                        </div>

                        <!-- Right Form Column -->
                        <div class="col-md-7 p-4 p-lg-5 bg-white d-flex flex-column justify-content-center">
                            <div class="mb-4">
                                <h4 class="fw-bold text-dark mb-1">Masuk Portal Admin</h4>
                                <p class="text-muted small">Khusus Administrator. Pengendara &amp; Owner silakan masuk melalui aplikasi Mobile.</p>
                            </div>

                            <!-- Alert Messages -->
                            @if(session('error') || $errors->any())
                                <div class="alert alert-danger d-flex align-items-center gap-2 py-2 px-3 small rounded-3" role="alert">
                                    <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                                    <div>{{ session('error') ?? $errors->first() }}</div>
                                </div>
                            @endif

                            @if(session('success'))
                                <div class="alert alert-success d-flex align-items-center gap-2 py-2 px-3 small rounded-3" role="alert">
                                    <i class="bi bi-check-circle-fill flex-shrink-0"></i>
                                    <div>{{ session('success') }}</div>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('login') }}">
                                @csrf

                                <!-- Email -->
                                <div class="mb-3">
                                    <label for="email" class="form-label small fw-semibold text-secondary">Alamat Email</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0 text-muted">
                                            <i class="bi bi-envelope"></i>
                                        </span>
                                        <input type="email" id="email" name="email" required
                                            value="{{ old('email', '') }}"
                                            placeholder="nama@email.com"
                                            autocomplete="email"
                                            class="form-control bg-light border-start-0 ps-0">
                                    </div>
                                </div>

                                <!-- Password -->
                                <div class="mb-4">
                                    <label for="password" class="form-label small fw-semibold text-secondary">Kata Sandi</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0 text-muted">
                                            <i class="bi bi-key"></i>
                                        </span>
                                        <input type="password" id="password" name="password" required
                                            placeholder="••••••••"
                                            autocomplete="current-password"
                                            class="form-control bg-light border-start-0 border-end-0 ps-0">
                                        <button class="btn btn-light border border-start-0 text-muted" type="button" onclick="togglePasswordVisibility()">
                                            <i id="eye-icon" class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <div class="d-grid mb-3">
                                    <button type="submit" class="btn btn-primary py-2 fw-semibold d-flex align-items-center justify-content-center gap-2 rounded-3 shadow-sm">
                                        <span>Masuk ke Sistem</span>
                                        <i class="bi bi-arrow-right"></i>
                                    </button>
                                </div>

                                <div class="text-center">
                                    <a href="{{ route('status') }}" class="text-decoration-none small text-muted">
                                        <i class="bi bi-broadcast me-1"></i> Lihat Status Publik Slot Parkir
                                    </a>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Bootstrap 5.3 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePasswordVisibility() {
            const pwd = document.getElementById('password');
            const icon = document.getElementById('eye-icon');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                pwd.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }
    </script>
</body>
</html>
