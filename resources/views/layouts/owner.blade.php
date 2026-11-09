<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Portal Owner') - E-Parkir IoT (Sigma Boys)</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Chart.js -->
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
            <a href="{{ route('owner.reports.index') }}" class="d-flex align-items-center gap-2 text-white text-decoration-none pb-3 mb-3 border-bottom border-white-50">
                <span class="badge bg-white text-primary fs-5 p-2 rounded-3">
                    <i class="bi bi-briefcase-fill"></i>
                </span>
                <div>
                    <div class="fw-bold fs-5 lh-1 text-white">E-Parkir</div>
                    <small class="text-white-50">Portal Eksekutif Owner</small>
                </div>
            </a>

            <!-- Navigation Links -->
            <div class="nav-heading text-uppercase text-white-50 small fw-bold px-2 mb-2" style="font-size: 0.75rem;">Menu Owner</div>
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item">
                    <a href="{{ route('owner.reports.index') }}" class="nav-link {{ request()->routeIs('owner.reports.index') ? 'active' : '' }}">
                        <i class="bi bi-pie-chart-fill fs-5"></i>
                        <span>Dashboard & Laporan</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('owner.transactions.index') }}" class="nav-link {{ request()->routeIs('owner.transactions.index') ? 'active' : '' }}">
                        <i class="bi bi-table fs-5"></i>
                        <span>Rincian Transaksi</span>
                    </a>
                </li>
            </ul>

            <!-- Sidebar Bottom Info -->
            <div class="pt-3 border-top border-white-50 small text-white-50 d-flex align-items-center justify-content-between">
                <span>Portal Owner v2.0</span>
                <span class="badge bg-warning text-dark fw-bold">ONLINE</span>
            </div>
        </aside>

        <!-- MOBILE OFFCANVAS SIDEBAR -->
        <div class="offcanvas offcanvas-start bg-primary text-white no-print" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel">
            <div class="offcanvas-header border-bottom border-white-50">
                <div class="d-flex align-items-center gap-2" id="sidebarOffcanvasLabel">
                    <span class="badge bg-white text-primary fs-5 p-2 rounded-3">
                        <i class="bi bi-briefcase-fill"></i>
                    </span>
                    <h5 class="offcanvas-title fw-bold text-white mb-0">E-Parkir Owner</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body d-flex flex-column p-3">
                <ul class="nav nav-pills flex-column mb-auto">
                    <li class="nav-item">
                        <a href="{{ route('owner.reports.index') }}" class="nav-link text-white {{ request()->routeIs('owner.reports.index') ? 'active' : '' }}">
                            <i class="bi bi-pie-chart-fill me-2"></i> Dashboard & Laporan
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('owner.transactions.index') }}" class="nav-link text-white {{ request()->routeIs('owner.transactions.index') ? 'active' : '' }}">
                            <i class="bi bi-table me-2"></i> Rincian Transaksi
                        </a>
                    </li>
                </ul>
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
                        <h5 class="fw-bold mb-0 text-dark">@yield('header_title', 'Laporan & Finansial')</h5>
                        <small class="text-muted d-none d-sm-inline">@yield('header_subtitle', 'Dashboard Eksekutif Pendapatan & Transaksi')</small>
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
                            <span class="badge bg-primary rounded-circle p-2">
                                {{ strtoupper(substr(auth()->user()->name ?? 'O', 0, 1)) }}
                            </span>
                            <span class="fw-semibold small d-none d-sm-inline">{{ explode(' ', auth()->user()->name ?? 'Owner')[0] }}</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 mt-2">
                            <li class="px-3 py-2 border-bottom">
                                <p class="mb-0 fw-bold small text-dark">{{ auth()->user()->name ?? 'Owner' }}</p>
                                <small class="text-muted d-block text-truncate" style="max-width: 180px;">{{ auth()->user()->email ?? '' }}</small>
                                <span class="badge bg-warning text-dark mt-1">Role: Owner</span>
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
                    <small>&copy; {{ date('Y') }} <strong>E-Parkir IoT Platform</strong> &bull; Executive Portal</small>
                    <div class="d-flex align-items-center gap-1">
                        <span class="badge bg-light text-secondary border">Executive View</span>
                        <span class="badge bg-light text-secondary border">MySQL</span>
                    </div>
                </div>
            </footer>

        </div>

    </div>

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
    </script>
    @stack('scripts')
</body>
</html>
