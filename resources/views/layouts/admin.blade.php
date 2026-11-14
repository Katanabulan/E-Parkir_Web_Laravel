<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel') - E-Parkir IoT (Sigma Boys)</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Chart.js for analytics & reports -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
        }
        .sidebar {
            width: 260px;
            min-height: 100vh;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.85);
            font-weight: 500;
            border-radius: 0.5rem;
            padding: 0.65rem 1rem;
            margin-bottom: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.15s ease;
        }
        .sidebar .nav-link:hover {
            color: #ffffff;
            background-color: rgba(255, 255, 255, 0.12);
        }
        .sidebar .nav-link.active {
            color: #ffffff;
            background-color: rgba(255, 255, 255, 0.22);
            font-weight: 700;
        }
        @media print {
            .no-print, .sidebar, #sidebarOffcanvas, header, footer, .modal {
                display: none !important;
            }
            body, main {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
        }
    </style>
    @stack('styles')
</head>
<body class="bg-light">

    <div class="d-flex min-vh-100">
        
        <!-- DESKTOP SIDEBAR -->
        <aside class="sidebar bg-primary text-white d-none d-lg-flex flex-column flex-shrink-0 p-3 sticky-top h-100 no-print" style="max-height: 100vh; overflow-y: auto;">
            <!-- Brand -->
            <a href="{{ route('admin.dashboard') }}" class="d-flex align-items-center gap-2 text-white text-decoration-none pb-3 mb-3 border-bottom border-white-50">
                <span class="badge bg-white text-primary fs-5 p-2 rounded-3">
                    <i class="bi bi-p-square-fill"></i>
                </span>
                <div>
                    <div class="fw-bold fs-5 lh-1 text-white">E-Parkir</div>
                    <small class="text-white-50">Industrial IoT Gate</small>
                </div>
            </a>

            <!-- Navigation Links -->
            @if (auth()->user()->role === 'petugas')
                <div class="nav-heading text-uppercase text-white-50 small fw-bold px-2 mb-2" style="font-size: 0.75rem;">Pos Palang Keluar</div>
                <ul class="nav nav-pills flex-column mb-auto">
                    <li class="nav-item">
                        <a href="{{ route('admin.monitoring') }}" class="nav-link d-flex align-items-center justify-content-between {{ request()->routeIs('admin.monitoring') ? 'active' : '' }}">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-cpu-fill fs-5"></i>
                                <span>Pos Gerbang & Kasir</span>
                            </div>
                            <span class="badge bg-danger">LIVE</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.status.index') }}" class="nav-link {{ request()->routeIs('admin.status.*') ? 'active' : '' }}">
                            <i class="bi bi-grid-3x3-gap-fill fs-5"></i>
                            <span>Status Slot Parkir</span>
                        </a>
                    </li>
                </ul>
            @else
                <div class="nav-heading text-uppercase text-white-50 small fw-bold px-2 mb-2" style="font-size: 0.75rem;">Menu Utama</div>
                <ul class="nav nav-pills flex-column mb-auto">
                    <li class="nav-item">
                        <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                            <i class="bi bi-speedometer2 fs-5"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.status.index') }}" class="nav-link {{ request()->routeIs('admin.status.*') ? 'active' : '' }}">
                            <i class="bi bi-grid-3x3-gap-fill fs-5"></i>
                            <span>Status Slot Parkir</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.cards.index') }}" class="nav-link {{ request()->routeIs('admin.cards.*') ? 'active' : '' }}">
                            <i class="bi bi-credit-card-2-front-fill fs-5"></i>
                            <span>Kelola Kartu RFID</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.monitoring') }}" class="nav-link d-flex align-items-center justify-content-between {{ request()->routeIs('admin.monitoring') ? 'active' : '' }}">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-cpu-fill fs-5"></i>
                                <span>Gate Simulator</span>
                            </div>
                            <span class="badge bg-danger">LIVE</span>
                        </a>
                    </li>

                    <li class="nav-heading text-uppercase text-white-50 small fw-bold px-2 mt-3 mb-2" style="font-size: 0.75rem;">Laporan & Audit</li>
                    <li class="nav-item">
                        <a href="{{ route('admin.reports.index') }}" class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                            <i class="bi bi-graph-up-arrow fs-5"></i>
                            <span>Laporan Finansial</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.logs.index') }}" class="nav-link {{ request()->routeIs('admin.logs.*') ? 'active' : '' }}">
                            <i class="bi bi-clock-history fs-5"></i>
                            <span>Audit Log Akses</span>
                        </a>
                    </li>

                    <li class="nav-heading text-uppercase text-white-50 small fw-bold px-2 mt-3 mb-2" style="font-size: 0.75rem;">Master Data</li>
                    <li class="nav-item">
                        <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                            <i class="bi bi-people-fill fs-5"></i>
                            <span>Kelola Pengguna</span>
                        </a>
                    </li>
                </ul>
            @endif

            <!-- Sidebar Bottom Info -->
            <div class="pt-3 border-top border-white-50 small text-white-50 d-flex align-items-center justify-content-between">
                <span>E-Parkir v2.0</span>
                <span class="badge bg-warning text-dark fw-bold">ONLINE</span>
            </div>
        </aside>

        <!-- MOBILE OFFCANVAS SIDEBAR -->
        <div class="offcanvas offcanvas-start bg-primary text-white no-print" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel">
            <div class="offcanvas-header border-bottom border-white-50">
                <div class="d-flex align-items-center gap-2" id="sidebarOffcanvasLabel">
                    <span class="badge bg-white text-primary fs-5 p-2 rounded-3">
                        <i class="bi bi-p-square-fill"></i>
                    </span>
                    <h5 class="offcanvas-title fw-bold text-white mb-0">E-Parkir IoT</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body d-flex flex-column p-3">
                @if (auth()->user()->role === 'petugas')
                    <div class="nav-heading text-uppercase text-white-50 small fw-bold px-2 mb-2" style="font-size: 0.75rem;">Pos Palang Keluar</div>
                    <ul class="nav nav-pills flex-column mb-auto">
                        <li class="nav-item">
                            <a href="{{ route('admin.monitoring') }}" class="nav-link text-white {{ request()->routeIs('admin.monitoring') ? 'active' : '' }}">
                                <i class="bi bi-cpu-fill me-2"></i> Pos Gerbang & Kasir
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.status.index') }}" class="nav-link text-white {{ request()->routeIs('admin.status.*') ? 'active' : '' }}">
                                <i class="bi bi-grid-3x3-gap-fill me-2"></i> Status Slot Parkir
                            </a>
                        </li>
                    </ul>
                @else
                    <ul class="nav nav-pills flex-column mb-auto">
                        <li class="nav-item">
                            <a href="{{ route('admin.dashboard') }}" class="nav-link text-white {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                                <i class="bi bi-speedometer2 me-2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.status.index') }}" class="nav-link text-white {{ request()->routeIs('admin.status.*') ? 'active' : '' }}">
                                <i class="bi bi-grid-3x3-gap-fill me-2"></i> Status Slot Parkir
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.cards.index') }}" class="nav-link text-white {{ request()->routeIs('admin.cards.*') ? 'active' : '' }}">
                                <i class="bi bi-credit-card-2-front-fill me-2"></i> Kelola Kartu RFID
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.monitoring') }}" class="nav-link text-white {{ request()->routeIs('admin.monitoring') ? 'active' : '' }}">
                                <i class="bi bi-cpu-fill me-2"></i> Gate Simulator
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.reports.index') }}" class="nav-link text-white {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                                <i class="bi bi-graph-up-arrow me-2"></i> Laporan Finansial
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.logs.index') }}" class="nav-link text-white {{ request()->routeIs('admin.logs.*') ? 'active' : '' }}">
                                <i class="bi bi-clock-history me-2"></i> Audit Log Akses
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.users.index') }}" class="nav-link text-white {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                                <i class="bi bi-people-fill me-2"></i> Kelola Pengguna
                            </a>
                        </li>
                    </ul>
                @endif
            </div>
        </div>

        <!-- MAIN CONTENT AREA -->
        <div class="d-flex flex-column flex-grow-1 min-vw-0">
            
            <!-- TOP NAVBAR -->
            <header class="navbar navbar-expand bg-white border-bottom shadow-sm sticky-top px-3 px-lg-4 py-2 no-print">
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-outline-secondary d-lg-none py-1 px-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas">
                        <i class="bi bi-list fs-5"></i>
                    </button>
                    <div>
                        <h5 class="fw-bold mb-0 text-dark">@yield('header_title', 'Dashboard')</h5>
                        <small class="text-muted d-none d-sm-inline">@yield('header_subtitle', 'Sistem Manajemen Parkir & Palang Pintu IoT')</small>
                    </div>
                </div>

                <div class="ms-auto d-flex align-items-center gap-2 gap-sm-3">
                    <!-- Live Clock -->
                    <div class="d-none d-md-flex align-items-center gap-2 bg-light border rounded-3 px-3 py-1 text-secondary small fw-semibold">
                        <i class="bi bi-clock-fill text-primary"></i>
                        <span id="live-clock">--:--:-- WIB</span>
                    </div>

                    <!-- User Profile Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-light border dropdown-toggle d-flex align-items-center gap-2 py-1 px-2.5 rounded-3" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="{{ auth()->user()->avatar_url }}" alt="Avatar" class="rounded-circle object-fit-cover border" style="width: 28px; height: 28px;" onerror="this.onerror=null; this.src='{{ asset('assets/images/default-avatar.svg') }}';">
                            <span class="fw-semibold small d-none d-sm-inline">{{ explode(' ', auth()->user()->name ?? 'Admin')[0] }}</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 mt-2">
                            <li class="px-3 py-2 border-bottom">
                                <p class="mb-0 fw-bold small text-dark">{{ auth()->user()->name ?? 'Admin' }}</p>
                                <small class="text-muted d-block text-truncate" style="max-width: 180px;">{{ auth()->user()->email ?? '' }}</small>
                                @if (auth()->user()->role === 'admin')
                                    <span class="badge bg-primary-subtle text-primary mt-1">Administrator</span>
                                @elseif (auth()->user()->role === 'petugas')
                                    <span class="badge bg-success-subtle text-success mt-1">Petugas Palang Keluar</span>
                                @elseif (auth()->user()->role === 'owner')
                                    <span class="badge bg-warning-subtle text-warning mt-1">Owner</span>
                                @endif
                            </li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}" id="logout-form">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger d-flex align-items-center gap-2 py-2">
                                        <i class="bi bi-box-arrow-right"></i>
                                        <span>Keluar (Logout)</span>
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>

            <!-- MAIN CONTENT -->
            <main class="flex-grow-1 p-3 p-md-4 bg-light">
                @yield('content')
            </main>

            <!-- FOOTER -->
            <footer class="bg-white border-top py-3 px-3 px-lg-4 text-center text-sm text-muted no-print">
                <div class="container-fluid d-flex flex-column flex-sm-row align-items-center justify-content-between gap-2">
                    <small>&copy; {{ date('Y') }} <strong>E-Parkir IoT Platform</strong> &bull; Tim Sigma Boys</small>
                    <div class="d-flex align-items-center gap-1">
                        <span class="badge bg-light text-secondary border">ESP8266 Gate</span>
                        <span class="badge bg-light text-secondary border">RFID RC522</span>
                        <span class="badge bg-light text-secondary border">MySQL</span>
                    </div>
                </div>
            </footer>

        </div>

    </div>

    @if (in_array(auth()->user()->role, ['admin', 'petugas']))
    <!-- EMERGENCY OVERRIDE MODAL -->
    <div class="modal fade no-print" id="emergencyModal" tabindex="-1" aria-labelledby="emergencyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-danger p-2 fs-6 rounded-3">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </span>
                        <h5 class="modal-title fw-bold text-dark" id="emergencyModalLabel">Emergency Override Gerbang</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="emergency-form" onsubmit="submitEmergencyOverride(event)">
                    <div class="modal-body p-4">
                        <p class="small text-muted mb-3">
                            Membuka paksa palang pintu gerbang dari jarak jauh untuk evakuasi darurat atau kendaraan prioritas.
                        </p>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Pilih Gerbang Tujuan</label>
                            <select id="emergency-device-code" required class="form-select rounded-3">
                                <option value="GATE_MASUK_01">GATE_MASUK_01 (Palang Gerbang Masuk)</option>
                                <option value="GATE_KELUAR_01">GATE_KELUAR_01 (Palang Gerbang Keluar)</option>
                                <option value="Gate-01">Gate-01 (Palang Gerbang Utama)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Alasan Pembukaan Darurat</label>
                            <textarea id="emergency-reason" rows="2" required class="form-control" placeholder="Contoh: Ambulans darurat / Palang macet">Instruksi darurat manual administrator</textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light">
                        <button type="button" class="btn btn-secondary rounded-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" id="btn-submit-override" class="btn btn-danger rounded-3 d-flex align-items-center gap-2">
                            <i class="bi bi-lightning-charge-fill"></i>
                            <span>Buka Palang Sekarang</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- TOAST CONTAINER -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3 no-print" id="toastContainer" style="z-index: 1090;"></div>

    <!-- Bootstrap 5.3 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Live Clock
        function updateLiveClock() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('id-ID', { hour12: false });
            const clockEl = document.getElementById('live-clock');
            if (clockEl) {
                clockEl.textContent = timeStr + ' WIB';
            }
        }
        setInterval(updateLiveClock, 1000);
        updateLiveClock();

        // Emergency Modal Trigger
        function openEmergencyModal(deviceCode = null) {
            const modalEl = document.getElementById('emergencyModal');
            if (!modalEl) return;
            if (deviceCode) {
                const selectEl = document.getElementById('emergency-device-code');
                if (selectEl) {
                    let exists = false;
                    for (let i = 0; i < selectEl.options.length; i++) {
                        if (selectEl.options[i].value === deviceCode) {
                            selectEl.selectedIndex = i;
                            exists = true;
                            break;
                        }
                    }
                    if (!exists) {
                        const opt = new Option(deviceCode, deviceCode, true, true);
                        selectEl.add(opt);
                    }
                }
            }
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }

        function closeEmergencyModal() {
            const modalEl = document.getElementById('emergencyModal');
            if (modalEl) {
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            }
        }

        async function submitEmergencyOverride(e) {
            e.preventDefault();
            const btn = document.getElementById('btn-submit-override');
            const deviceCode = document.getElementById('emergency-device-code').value;
            const reason = document.getElementById('emergency-reason').value;

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Memproses...';

            try {
                const res = await fetch('{{ url('/api/override') }}', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        device_code: deviceCode,
                        reason: reason
                    })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    showToast('Berhasil!', data.message, 'success');
                    playWebBuzzer(2);
                    closeEmergencyModal();
                    if (typeof refreshDashboardData === 'function') {
                        refreshDashboardData();
                    }
                    if (typeof openBarrierGate === 'function') {
                        const target = (deviceCode === 'GATE_KELUAR_01') ? 'OUT' : ((deviceCode === 'GATE_MASUK_01') ? 'IN' : 'BOTH');
                        openBarrierGate(8000, target);
                    }
                } else {
                    showToast('Gagal', data.message, 'danger');
                }
            } catch (err) {
                showToast('Error', 'Gagal menghubungi server: ' + err.message, 'danger');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-lightning-charge-fill me-1"></i> Buka Palang Sekarang';
            }
        }

        // Bootstrap 5 Toast Helper
        function showToast(title, message, type = 'info') {
            const container = document.getElementById('toastContainer');
            const bgClass = (type === 'error' || type === 'danger') ? 'bg-danger text-white' : (type === 'success' ? 'bg-success text-white' : 'bg-primary text-white');
            
            const toastEl = document.createElement('div');
            toastEl.className = `toast align-items-center ${bgClass} border-0 shadow mb-2`;
            toastEl.setAttribute('role', 'alert');
            toastEl.setAttribute('aria-live', 'assertive');
            toastEl.setAttribute('aria-atomic', 'true');

            toastEl.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        <strong>${title}</strong>: ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            `;
            container.appendChild(toastEl);
            const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
            toast.show();
            toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
        }

        // Web Audio Buzzer
        function playWebBuzzer(count = 1, tone = 'high') {
            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (!AudioCtx) return;
                const ctx = new AudioCtx();
                const freq = (tone === 'low') ? 400 : 1200;

                for (let i = 0; i < count; i++) {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'square';
                    osc.frequency.setValueAtTime(freq, ctx.currentTime);

                    const startTime = ctx.currentTime + (i * 0.16);
                    const duration = (tone === 'low') ? 0.35 : 0.08;

                    gain.gain.setValueAtTime(0.15, startTime);
                    gain.gain.exponentialRampToValueAtTime(0.001, startTime + duration);

                    osc.connect(gain);
                    gain.connect(ctx.destination);

                    osc.start(startTime);
                    osc.stop(startTime + duration);
                }
            } catch (e) {
                console.log('Audio buzzer disabled or blocked:', e);
            }
        }
    </script>
    @stack('scripts')
</body>
</html>
