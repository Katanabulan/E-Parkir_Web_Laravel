<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>E-Parkir Client - Status Pengendara</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5.3 & Bootstrap Icons CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: #F8FAFC;
            color: #000000;
            min-height: 100vh;
        }
        .btn-primary {
            background-color: #00719C;
            border-color: #00719C;
        }
        .btn-primary:hover, .btn-primary:focus {
            background-color: #005677;
            border-color: #005677;
        }
        .text-primary-custom {
            color: #00719C !important;
        }
        .bg-primary-custom {
            background-color: #00719C !important;
        }
        .pulse-live {
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.6);
            animation: pulse-live 2s infinite;
        }
        @keyframes pulse-live {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.6); }
            70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }
    </style>
</head>
<body class="d-flex flex-column justify-content-between">

    <!-- Container Smartphone Mobile-First (Maks 500px) -->
    <div class="container py-4" style="max-width: 500px;">

        <!-- Top Header & WiFi Info -->
        <header class="d-flex flex-column gap-3 mb-3">
            
            <!-- Top bar -->
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <!-- Icon Box E-Parkir Blue -->
                    <div class="d-flex align-items-center justify-content-center rounded-3 text-white bg-primary-custom" style="width: 2.5rem; height: 2.5rem; font-size: 1.25rem;">
                        <i class="bi bi-p-square-fill"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-1.5">
                            <span class="fs-6 fw-bold text-dark">E-Parkir</span>
                            <span class="badge bg-warning text-dark border fw-bold" style="font-size: 0.65rem;">IoT</span>
                            <span class="badge bg-primary-subtle text-primary fw-bold" style="font-size: 0.65rem;">CLIENT</span>
                        </div>
                        <p class="small text-secondary mb-0" style="font-size: 0.75rem;">Smart Barrier Gate &amp; Live Billing</p>
                    </div>
                </div>
            </div>

            <!-- User Identity & Logout Bar -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-3 d-flex flex-column gap-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <div class="d-flex align-items-center justify-content-center rounded-circle bg-light border text-secondary" style="width: 2rem; height: 2rem;">
                                <i class="bi bi-person-fill"></i>
                            </div>
                            <div>
                                <div class="small fw-bold text-dark">{{ $currentUser->name ?? 'Pengendara' }}</div>
                                <div class="small text-muted" style="font-size: 0.7rem;">{{ $currentUser->email ?? '' }}</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-warning text-dark border fw-bold" style="font-size: 0.7rem;">
                                {{ ($currentUser->role === 'admin') ? 'ADMIN' : 'PENGENDARA' }}
                            </span>
                            <form method="POST" action="{{ route('logout') }}" id="client-logout-form" class="m-0">
                                @csrf
                                <button type="submit" title="Keluar / Logout" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1 py-1 px-2 fw-bold" style="font-size: 0.75rem;">
                                    <i class="bi bi-box-arrow-right"></i>
                                    <span>Keluar</span>
                                </button>
                            </form>
                        </div>
                    </div>

                    @if ($currentUser->role === 'admin')
                    <div class="pt-2 border-top d-flex align-items-center gap-2">
                        <span class="small fw-bold text-secondary text-uppercase" style="font-size: 0.7rem;">Mode:</span>
                        <select id="user-select" onchange="switchUser(this.value)" class="form-select form-select-sm fw-semibold">
                            @foreach ($clients as $cl)
                                <option value="{{ $cl['id'] }}" {{ ($cl['id'] == $defaultUserId) ? 'selected' : '' }}>
                                    {{ $cl['name'] }} (UID: {{ $cl['uid'] ?? '-' }})
                                </option>
                            @endforeach
                        </select>
                        <button onclick="refreshData(true)" title="Sinkronkan data" class="btn btn-sm btn-primary d-inline-flex align-items-center justify-content-center">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>
                    @else
                        <input type="hidden" id="user-select" value="{{ $currentUser->id }}">
                    @endif
                </div>
            </div>
        </header>

        <!-- Main Body: Active Status or Idle Status -->
        <main class="d-flex flex-column gap-3">

            <!-- KARTU SALDO PENGENDARA (E-MONEY RFID BALANCE CARD) -->
            <div id="balance-card" class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between pb-3 border-bottom">
                        <div class="d-flex align-items-center gap-2.5">
                            <div class="d-flex align-items-center justify-content-center rounded-3 text-white bg-primary-custom" style="width: 2.5rem; height: 2.5rem; font-size: 1.25rem;">
                                <i class="bi bi-wallet2"></i>
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="small fw-bold text-uppercase text-dark" style="font-size: 0.75rem;">Saldo Kartu</span>
                                    <span id="card-status-badge" class="badge bg-success-subtle text-success border border-success-subtle fw-bold" style="font-size: 0.65rem;">Aktif</span>
                                </div>
                                <div class="small text-muted font-monospace" id="user-card-uid-sub" style="font-size: 0.75rem;">UID: -</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-1.5">
                            <button type="button" onclick="openPayTicketModal()" class="btn btn-sm btn-primary fw-bold d-inline-flex align-items-center gap-1 shadow-sm" style="font-size: 0.75rem;" title="Scan / Bayar Tiket Karcis via Saldo">
                                <i class="bi bi-qr-code-scan"></i>
                                <span>Bayar</span>
                            </button>
                            <button type="button" onclick="openTopupModal()" class="btn btn-sm btn-warning fw-bold text-dark d-inline-flex align-items-center gap-1 shadow-sm" style="font-size: 0.75rem;">
                                <i class="bi bi-plus-circle"></i>
                                <span>Top Up</span>
                            </button>
                        </div>
                    </div>

                    <div class="pt-3 d-flex align-items-baseline justify-content-between">
                        <div>
                            <span class="small text-uppercase fw-bold text-secondary d-block" style="font-size: 0.7rem;">Saldo Saat Ini</span>
                            <div id="user-balance-display" class="font-monospace fs-3 fw-bold text-dark">
                                Rp 0
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="small text-secondary d-block" style="font-size: 0.7rem;">Batas Minimal Masuk</span>
                            <span class="font-monospace small fw-bold text-dark bg-light px-2 py-0.5 rounded border d-inline-block mt-0.5">Rp 2.000</span>
                        </div>
                    </div>

                    <!-- Banner Informasi Keterangan Potong Saldo -->
                    <div class="mt-3 pt-2.5 border-top d-flex align-items-center justify-content-between small text-secondary" style="font-size: 0.75rem;">
                        <span class="d-flex align-items-center gap-1">
                            <i class="bi bi-info-circle-fill text-primary"></i>
                            <span>Potong otomatis saat keluar &rarr; Admin</span>
                        </span>
                        <span class="badge bg-success-subtle text-success fw-bold">Real-Time</span>
                    </div>
                </div>
            </div>

            <!-- KETERSEDIAAN SLOT PARKIR REALTIME (SLOT AVAILABILITY HERO CARD) -->
            <div id="slot-availability-card" class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 d-flex flex-column gap-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <div id="slot-status-iconbox" class="d-flex align-items-center justify-content-center rounded-3 text-white bg-primary-custom" style="width: 2.75rem; height: 2.75rem; font-size: 1.35rem;">
                                <i id="slot-icon" class="bi bi-p-square-fill"></i>
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="small fw-bold text-uppercase text-secondary" style="font-size: 0.75rem;">Ketersediaan Slot</span>
                                    <span id="slot-badge-status" class="badge {{ $availableInitSlots > 0 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' }} fw-bold" style="font-size: 0.7rem;">
                                        {{ $availableInitSlots > 0 ? 'Tersedia' : 'Penuh' }}
                                    </span>
                                </div>
                                <div class="small text-secondary mt-0.5" id="slot-subtext" style="font-size: 0.75rem;">
                                    Kapasitas maksimal {{ $totalCapacity }} slot (LT 1 &amp; LT 2)
                                </div>
                            </div>
                        </div>

                        <!-- Angka Slot Tersisa -->
                        <div class="text-end ps-3 border-start">
                            <div class="small fw-bold text-uppercase text-secondary" style="font-size: 0.7rem;">Total Tersisa</div>
                            <div class="d-flex align-items-baseline justify-content-end gap-1">
                                <span id="slot-available-count" class="font-monospace fs-3 fw-bold {{ $availableInitSlots > 0 ? 'text-primary' : 'text-danger' }}">
                                    {{ $availableInitSlots }}
                                </span>
                                <span class="small fw-bold text-secondary">/ {{ $totalCapacity }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Breakdown Per Lantai: LT 1 & LT 2 -->
                    <div class="row g-2 pt-2 border-top">
                        <div class="col-6">
                            <div class="p-2.5 rounded-3 bg-light border d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="rounded-circle d-inline-block {{ ($floor1Available ?? 0) > 0 ? 'bg-success' : 'bg-danger' }}" id="lt1-indicator" style="width: 8px; height: 8px;"></span>
                                    <div>
                                        <div class="small fw-bold text-dark d-flex align-items-center gap-1">
                                            <span>LT 1</span>
                                            <span class="badge bg-primary text-white" style="font-size: 0.6rem;">1</span>
                                        </div>
                                        <div class="small text-secondary" id="lt1-status-text" style="font-size: 0.65rem;">{{ ($floor1Available ?? 0) > 0 ? 'Sisa ' . ($floor1Available ?? 0) . ' slot' : 'Penuh' }}</div>
                                    </div>
                                </div>
                                <div class="text-end font-monospace small fw-bold text-secondary">
                                    <span id="lt1-parked-count">{{ $floor1Parked ?? 0 }}</span>/10
                                </div>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="p-2.5 rounded-3 bg-light border d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="rounded-circle d-inline-block {{ ($floor2Available ?? 0) > 0 ? 'bg-success' : 'bg-danger' }}" id="lt2-indicator" style="width: 8px; height: 8px;"></span>
                                    <div>
                                        <div class="small fw-bold text-dark d-flex align-items-center gap-1">
                                            <span>LT 2</span>
                                            <span class="badge bg-warning text-dark" style="font-size: 0.6rem;">2</span>
                                        </div>
                                        <div class="small text-secondary" id="lt2-status-text" style="font-size: 0.65rem;">{{ ($floor2Available ?? 0) > 0 ? 'Sisa ' . ($floor2Available ?? 0) . ' slot' : 'Penuh' }}</div>
                                    </div>
                                </div>
                                <div class="text-end font-monospace small fw-bold text-secondary">
                                    <span id="lt2-parked-count">{{ $floor2Parked ?? 0 }}</span>/10
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CARD UTAMA: STATUS PARKIR REALTIME -->
            <div id="main-status-card" class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-4">
                    <!-- Loading State placeholder -->
                    <div id="loading-spinner" class="py-5 text-center">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="small text-secondary mt-3 fw-semibold">Menghubungkan ke E-Parkir Server...</p>
                    </div>

                    <!-- STATE 1: SEDANG PARKIR (PARKED) -->
                    <div id="state-parked" class="d-none d-flex flex-column gap-3">
                        <!-- Top Status Badge -->
                        <div class="d-flex align-items-center justify-content-between pb-3 border-bottom">
                            <div class="d-flex align-items-center gap-2">
                                <span class="rounded-circle d-inline-block bg-success pulse-live" style="width: 10px; height: 10px;"></span>
                                <span class="small fw-bold text-uppercase text-dark">KENDARAAN PARKIR AKTIF</span>
                            </div>
                            <span id="parked-card-uid" class="font-monospace small px-2.5 py-1 rounded-2 bg-primary-subtle text-primary fw-bold">UID: -</span>
                        </div>

                        <!-- Live Duration Display -->
                        <div class="text-center py-4 bg-light rounded-4 border">
                            <p class="small fw-bold text-uppercase text-secondary mb-1" style="font-size: 0.75rem;">DURASI PARKIR BERJALAN</p>
                            <div id="live-timer" class="font-monospace fs-1 fw-bold text-dark tracking-wider">
                                00 : 00 : 00
                            </div>
                            <p class="small text-muted mb-0 mt-1" style="font-size: 0.7rem;">Format: Jam : Menit : Detik</p>
                        </div>

                        <!-- Estimated Tariff Ticker -->
                        <div class="rounded-4 p-4 text-white shadow-sm" style="background: linear-gradient(135deg, #00719C 0%, #004b68 100%);">
                            <div class="d-flex align-items-center justify-content-between small fw-semibold mb-1">
                                <span class="text-white-50">Estimasi Tagihan</span>
                                <span class="badge bg-warning text-dark fw-bold">Rp 2.000 / Jam</span>
                            </div>
                            <div class="d-flex align-items-baseline justify-content-between mt-2">
                                <div id="current-fee-display" class="fs-2 fw-bold text-white font-monospace">
                                    Rp 0
                                </div>
                                <div class="text-end">
                                    <span id="billed-hours-display" class="badge bg-white bg-opacity-25 text-white fw-bold">1 Jam Dihitung</span>
                                </div>
                            </div>

                            <!-- Progress to next hour -->
                            <div class="mt-3 pt-3 border-top border-white border-opacity-25">
                                <div class="d-flex justify-content-between small text-white-50 mb-1.5 fw-medium" style="font-size: 0.75rem;">
                                    <span>Menuju jam berikutnya:</span>
                                    <span id="next-hour-countdown" class="font-monospace fw-bold text-warning">-- mnt tersisa</span>
                                </div>
                                <div class="progress bg-black bg-opacity-25" style="height: 6px;">
                                    <div id="hourly-progress-bar" class="progress-bar bg-warning" role="progressbar" style="width: 20%;" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Live Balance Calculation Box saat parkir -->
                        <div class="p-3 bg-light rounded-3 border small d-flex flex-column gap-2">
                            <div class="d-flex align-items-center justify-content-between text-secondary">
                                <span class="d-flex align-items-center gap-1.5">
                                    <i class="bi bi-wallet2 text-primary"></i>
                                    <span>Saldo Kartu Anda:</span>
                                </span>
                                <span id="parked-current-balance" class="font-monospace fw-bold text-dark">Rp 0</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between text-secondary">
                                <span class="d-flex align-items-center gap-1.5">
                                    <i class="bi bi-receipt text-danger"></i>
                                    <span>Estimasi Tarif Parkir:</span>
                                </span>
                                <span id="parked-estimated-fee" class="font-monospace fw-bold text-danger">- Rp 0</span>
                            </div>
                            <div class="pt-2 border-top d-flex align-items-center justify-content-between fw-bold">
                                <span class="text-dark d-flex align-items-center gap-1.5">
                                    <i class="bi bi-coin text-warning"></i>
                                    <span>Estimasi Sisa Saldo:</span>
                                </span>
                                <span id="parked-remaining-balance" class="font-monospace text-success fw-bold">Rp 0</span>
                            </div>
                            <div id="insufficient-balance-warning" class="d-none mt-2 p-2.5 rounded-3 bg-danger-subtle border border-danger-subtle text-danger small fw-semibold d-flex align-items-center gap-2">
                                <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0"></i>
                                <div class="flex-grow-1">
                                    <div>Saldo Kurang untuk Keluar!</div>
                                    <span class="small text-danger fw-normal" style="font-size: 0.7rem;">Palang tidak terbuka saat tap-out. Segera isi saldo kartu Anda.</span>
                                </div>
                                <button type="button" onclick="openTopupModal()" class="btn btn-sm btn-danger py-1 px-2 fw-bold" style="font-size: 0.75rem;">Top Up</button>
                            </div>
                        </div>

                        <!-- Gate In & Tap Time Details -->
                        <div class="row g-2 text-center">
                            <div class="col-4">
                                <div class="bg-light p-2 rounded-3 border">
                                    <span class="small text-secondary d-block fw-bold text-uppercase" style="font-size: 0.65rem;">Lantai</span>
                                    <span id="parked-floor-badge" class="badge bg-primary text-white mt-1">LT 1</span>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="bg-light p-2 rounded-3 border">
                                    <span class="small text-secondary d-block fw-bold text-uppercase" style="font-size: 0.65rem;">Palang</span>
                                    <span id="gate-in-name" class="fw-bold text-dark small d-block mt-0.5 text-truncate">Gate-01</span>
                                    <span id="gate-in-loc" class="small text-muted d-block text-truncate" style="font-size: 0.65rem;">Utama</span>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="bg-light p-2 rounded-3 border">
                                    <span class="small text-secondary d-block fw-bold text-uppercase" style="font-size: 0.65rem;">Jam Masuk</span>
                                    <span id="tap-in-time-display" class="fw-bold text-dark small d-block mt-0.5 font-monospace">--:--:--</span>
                                    <span class="small text-success d-block fw-bold" style="font-size: 0.65rem;">✓ Tercatat</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STATE 2: BELUM PARKIR / SIAP TAP-IN (NOT PARKED) -->
                    <div id="state-idle" class="d-none d-flex flex-column gap-3">
                        <!-- Status Badge -->
                        <div class="d-flex align-items-center justify-content-between pb-3 border-bottom">
                            <div class="d-flex align-items-center gap-2">
                                <span class="rounded-circle d-inline-block bg-success" style="width: 10px; height: 10px;"></span>
                                <span class="small fw-bold text-uppercase text-dark">STATUS: TIDAK DALAM PARKIR</span>
                            </div>
                            <span id="idle-card-uid" class="font-monospace small px-2.5 py-1 rounded-2 bg-light text-secondary border fw-bold">UID: -</span>
                        </div>

                        <!-- Saldo Status info box -->
                        <div id="idle-balance-alert" class="p-3 rounded-3 border small d-flex align-items-center justify-content-between bg-success-subtle border-success-subtle">
                            <div class="d-flex align-items-center gap-2">
                                <i id="idle-balance-icon" class="bi bi-check-circle-fill text-success fs-5"></i>
                                <span id="idle-balance-text" class="text-success-emphasis fw-medium">Saldo mencukupi untuk tap masuk (Min. Rp 2.000).</span>
                            </div>
                            <span id="idle-balance-val" class="font-monospace fw-bold text-success-emphasis">Rp 0</span>
                        </div>

                        <!-- Welcome Box -->
                        <div class="text-center py-4 px-3 bg-light rounded-4 border d-flex flex-column align-items-center gap-2">
                            <div class="d-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary" style="width: 3.5rem; height: 3.5rem; font-size: 1.75rem;">
                                <i class="bi bi-credit-card-2-front"></i>
                            </div>
                            <h2 class="fs-6 fw-bold text-dark mb-0">Kartu RFID Siap Digunakan</h2>
                            <p class="small text-secondary mb-0" style="max-width: 320px; font-size: 0.75rem;">
                                Tempelkan kartu RFID Anda ke sensor scanner gerbang (<strong class="text-dark">Gate-01</strong>) untuk membuka palang parkir secara otomatis.
                            </p>
                        </div>

                        <!-- Rate Card -->
                        <div class="p-3 rounded-3 bg-white border d-flex align-items-center justify-content-between small">
                            <div class="d-flex align-items-center gap-2.5">
                                <div class="d-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary" style="width: 2rem; height: 2rem;">
                                    <i class="bi bi-tag-fill"></i>
                                </div>
                                <div>
                                    <span class="text-secondary d-block fw-bold text-uppercase" style="font-size: 0.65rem;">Tarif Flat Parkir</span>
                                    <span class="fw-bold text-dark">Rp 2.000 <span class="text-secondary fw-normal">/ Jam</span></span>
                                </div>
                            </div>
                            <span class="badge bg-warning text-dark border fw-bold">Skema Bulat Atas</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIWAYAT PARKIR TERAKHIR -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 d-flex flex-column gap-3">
                    <div class="d-flex align-items-center justify-content-between pb-2 border-bottom">
                        <h3 class="fs-6 fw-bold text-uppercase text-dark d-flex align-items-center gap-2 mb-0" style="font-size: 0.8rem;">
                            <i class="bi bi-clock-history text-primary"></i>
                            <span>Riwayat Parkir Terakhir</span>
                        </h3>
                        <span id="trip-count-badge" class="badge bg-light text-secondary border fw-bold">0 Sesi</span>
                    </div>

                    <!-- History List -->
                    <div id="history-container" class="d-flex flex-column gap-2">
                        <div class="text-center py-4 text-secondary small">Memuat riwayat transaksi...</div>
                    </div>

                    <!-- Total Stat Summary -->
                    <div class="pt-2 border-top d-flex align-items-center justify-content-between small text-secondary">
                        <span class="fw-semibold">Total Pengeluaran:</span>
                        <strong id="total-spent-display" class="text-dark font-monospace fs-6 fw-bold">Rp 0</strong>
                    </div>
                </div>
            </div>

        </main>

        <!-- Footer -->
        <footer class="pt-4 mt-3 text-center border-top text-secondary" style="font-size: 0.75rem;">
            <p class="fw-medium mb-0">Sistem Palang Pintu &amp; Tarif Parkir Otomatis Berbasis IoT</p>
            <p class="text-muted mt-0.5 mb-0">Kelompok Sigma Boys &copy; {{ date('Y') }}</p>
        </footer>

    </div>

    <!-- Notification Toast Container -->
    <div class="toast-container position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 1090;">
        <div id="clientToast" class="toast align-items-center border-0 shadow-lg rounded-4" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex p-2">
                <div class="toast-body d-flex align-items-center gap-2 small fw-bold">
                    <span id="toast-icon" class="fs-5">⚡</span>
                    <span id="toast-msg">Notifikasi</span>
                </div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <!-- Modal 1: Top Up Saldo Pengendara -->
    <div class="modal fade" id="topupModal" tabindex="-1" aria-labelledby="topupModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 380px;">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom p-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="d-flex align-items-center justify-content-center rounded-3 bg-primary-custom text-white" style="width: 2rem; height: 2rem;">
                            <i class="bi bi-wallet2"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fs-6 fw-bold text-dark" id="topupModalLabel">Isi Ulang Saldo</h5>
                            <p class="small text-secondary mb-0" style="font-size: 0.7rem;">Top Up Kartu Pengendara RFID</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3 d-flex flex-column gap-3">
                    <!-- Saldo Saat Ini -->
                    <div class="p-2.5 rounded-3 bg-light border d-flex align-items-center justify-content-between small">
                        <span class="text-secondary fw-medium">Saldo Saat Ini:</span>
                        <span id="modal-current-balance" class="font-monospace fw-bold text-dark">Rp 0</span>
                    </div>

                    <!-- Pilihan Cepat Nominal -->
                    <div>
                        <label class="small fw-bold text-uppercase text-secondary mb-1.5 d-block" style="font-size: 0.7rem;">Pilihan Cepat</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <button type="button" onclick="selectTopupAmount(10000)" class="btn btn-outline-secondary btn-sm w-100 fw-bold py-2 rounded-3">+Rp 10.000</button>
                            </div>
                            <div class="col-6">
                                <button type="button" onclick="selectTopupAmount(20000)" class="btn btn-outline-secondary btn-sm w-100 fw-bold py-2 rounded-3">+Rp 20.000</button>
                            </div>
                            <div class="col-6">
                                <button type="button" onclick="selectTopupAmount(50000)" class="btn btn-outline-secondary btn-sm w-100 fw-bold py-2 rounded-3">+Rp 50.000</button>
                            </div>
                            <div class="col-6">
                                <button type="button" onclick="selectTopupAmount(100000)" class="btn btn-outline-secondary btn-sm w-100 fw-bold py-2 rounded-3">+Rp 100.000</button>
                            </div>
                        </div>
                    </div>

                    <!-- Input Kustom Nominal -->
                    <div>
                        <label class="small fw-bold text-uppercase text-secondary mb-1 d-block" style="font-size: 0.7rem;">Nominal Sendiri</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light fw-bold text-secondary">Rp</span>
                            <input type="number" id="topup-amount-input" min="1000" step="1000" placeholder="Contoh: 25000" class="form-control font-monospace fw-bold">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top p-3 d-flex gap-2">
                    <button type="button" class="btn btn-light btn-sm fw-bold flex-grow-1" data-bs-dismiss="modal">Batal</button>
                    <button type="button" id="btn-submit-topup" onclick="executeTopup()" class="btn btn-primary btn-sm fw-bold flex-grow-1 d-inline-flex align-items-center justify-content-center gap-1 shadow-sm">
                        <span>Isi Sekarang</span>
                        <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal 2: Bayar Karcis Tiket Menggunakan Saldo Member -->
    <div class="modal fade" id="payTicketModal" tabindex="-1" aria-labelledby="payTicketModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 380px;">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom p-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="d-flex align-items-center justify-content-center rounded-3 bg-primary-custom text-white" style="width: 2rem; height: 2rem;">
                            <i class="bi bi-qr-code-scan"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fs-6 fw-bold text-dark" id="payTicketModalLabel">Bayar Karcis Parkir</h5>
                            <p class="small text-secondary mb-0" style="font-size: 0.7rem;">Gunakan Saldo Kartu RFID Member</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3 d-flex flex-column gap-3">
                    <!-- Info Saldo Member Saat Ini -->
                    <div class="p-2.5 rounded-3 bg-light border d-flex align-items-center justify-content-between small">
                        <span class="text-secondary fw-medium">Saldo Kartu Anda:</span>
                        <span id="pay-modal-balance" class="font-monospace fw-bold text-dark">Rp 0</span>
                    </div>

                    <!-- Input Kode Tiket Karcis -->
                    <div>
                        <label class="small fw-bold text-uppercase text-secondary mb-1 d-block" style="font-size: 0.7rem;">Kode Tiket / Hasil Scan QR</label>
                        <div class="input-group input-group-sm">
                            <input type="text" id="pay-ticket-input" placeholder="Contoh: TKT-261102-1226" class="form-control font-monospace fw-bold text-uppercase">
                            <button type="button" id="btn-check-ticket" onclick="checkTicketCode()" class="btn btn-primary fw-bold">
                                <span>Cek</span>
                            </button>
                        </div>
                        <p class="small text-muted mb-0 mt-1" style="font-size: 0.7rem;">Masukkan kode tiket atau scan QR yang tercetak pada karcis masuk.</p>
                    </div>

                    <!-- Preview Tagihan Tiket -->
                    <div id="ticket-preview-box" class="d-none p-3 rounded-3 bg-light border small d-flex flex-column gap-1.5">
                        <div class="d-flex justify-content-between text-secondary pb-1 border-bottom">
                            <span>Kode Tiket</span>
                            <span id="ticket-prev-code" class="font-monospace fw-bold text-dark"></span>
                        </div>
                        <div class="d-flex justify-content-between text-secondary">
                            <span>Waktu Masuk</span>
                            <span id="ticket-prev-in" class="font-monospace text-dark"></span>
                        </div>
                        <div class="d-flex justify-content-between text-secondary">
                            <span>Durasi Parkir</span>
                            <span id="ticket-prev-dur" class="text-dark"></span>
                        </div>
                        <div class="d-flex justify-content-between pt-1 border-top fw-bold">
                            <span class="text-dark">Total Tagihan:</span>
                            <span id="ticket-prev-fee" class="font-monospace fs-6 text-danger fw-bold"></span>
                        </div>
                        <div id="ticket-balance-warning" class="d-none small text-danger fw-bold bg-danger-subtle p-2 rounded-2 border border-danger-subtle mt-1"></div>
                    </div>
                </div>
                <div class="modal-footer border-top p-3 d-flex gap-2">
                    <button type="button" class="btn btn-light btn-sm fw-bold flex-grow-1" data-bs-dismiss="modal">Batal</button>
                    <button type="button" id="btn-confirm-pay-ticket" onclick="executePayTicket()" class="d-none btn btn-success btn-sm fw-bold flex-grow-1 d-inline-flex align-items-center justify-content-center gap-1 shadow-sm">
                        <i class="bi bi-check-lg"></i>
                        <span>Bayar Pakai Saldo</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5.3 JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let currentUserId = {!! json_encode($defaultUserId) !!};
        let currentUid = '';
        let currentBalance = 0;
        let isParked = false;
        let tapInTimestamp = null;
        let timerInterval = null;
        let pollInterval = null;

        // Inisialisasi Audio Chime
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        function playChime(success = true) {
            try {
                if (audioCtx.state === 'suspended') {
                    audioCtx.resume();
                }
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                
                const now = audioCtx.currentTime;
                if (success) {
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(523.25, now); // C5
                    osc.frequency.setValueAtTime(659.25, now + 0.1); // E5
                    osc.frequency.setValueAtTime(783.99, now + 0.2); // G5
                    gain.gain.setValueAtTime(0.2, now);
                    gain.gain.exponentialRampToValueAtTime(0.001, now + 0.45);
                    osc.start(now);
                    osc.stop(now + 0.45);
                } else {
                    osc.type = 'sawtooth';
                    osc.frequency.setValueAtTime(220, now);
                    gain.gain.setValueAtTime(0.2, now);
                    gain.gain.exponentialRampToValueAtTime(0.001, now + 0.3);
                    osc.start(now);
                    osc.stop(now + 0.3);
                }
            } catch (e) {
                // Ignore audio error if user hasn't interacted
            }
        }

        function showToast(msg, icon = '⚡', isSuccess = true) {
            const toastEl = document.getElementById('clientToast');
            document.getElementById('toast-msg').innerText = msg;
            document.getElementById('toast-icon').innerText = icon;
            
            if (isSuccess) {
                toastEl.className = 'toast align-items-center text-bg-dark border-0 shadow-lg rounded-4';
            } else {
                toastEl.className = 'toast align-items-center text-bg-danger border-0 shadow-lg rounded-4';
            }

            const toast = new bootstrap.Toast(toastEl, { delay: 3500 });
            toast.show();
            playChime(isSuccess);
        }

        function switchUser(userId) {
            currentUserId = parseInt(userId);
            refreshData(true);
        }

        async function refreshData(manual = false) {
            try {
                const res = await fetch(`{{ route('client.api-status') }}?user_id=${currentUserId}`);
                const data = await res.json();

                if (data.status === 'success') {
                    document.getElementById('loading-spinner').classList.add('d-none');
                    renderClientState(data, manual);
                }
            } catch (err) {
                console.error("Gagal sinkron status:", err);
            }
        }

        function renderClientState(data, manual = false) {
            // Update realtime ketersediaan slot parkir
            if (data.parking_slots) {
                const slots = data.parking_slots;
                const countEl = document.getElementById('slot-available-count');
                const badgeEl = document.getElementById('slot-badge-status');
                const subtextEl = document.getElementById('slot-subtext');

                if (countEl) {
                    countEl.innerText = slots.available_slots;
                    if (slots.available_slots <= 0) {
                        countEl.className = 'font-monospace fs-3 fw-bold text-danger';
                    } else if (slots.available_slots <= 2) {
                        countEl.className = 'font-monospace fs-3 fw-bold text-warning';
                    } else {
                        countEl.className = 'font-monospace fs-3 fw-bold text-primary';
                    }
                }

                if (badgeEl) {
                    if (slots.available_slots <= 0) {
                        badgeEl.innerText = 'Penuh';
                        badgeEl.className = 'badge bg-danger-subtle text-danger border border-danger-subtle fw-bold';
                    } else if (slots.available_slots <= 2) {
                        badgeEl.innerText = 'Sisa Sedikit';
                        badgeEl.className = 'badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fw-bold';
                    } else {
                        badgeEl.innerText = 'Tersedia';
                        badgeEl.className = 'badge bg-success-subtle text-success border border-success-subtle fw-bold';
                    }
                }

                if (subtextEl) {
                    subtextEl.innerText = `${slots.parked_count} kendaraan terparkir dari ${slots.total_capacity} kapasitas`;
                }

                // Update Realtime Breakdown LT 1 & LT 2
                if (slots.floors || slots.lt1_available !== undefined) {
                    const lt1Avail = slots.floors ? slots.floors.lt1.available : slots.lt1_available;
                    const lt1Parked = slots.floors ? slots.floors.lt1.parked : slots.lt1_parked;
                    const lt2Avail = slots.floors ? slots.floors.lt2.available : slots.lt2_available;
                    const lt2Parked = slots.floors ? slots.floors.lt2.parked : slots.lt2_parked;

                    const lt1ParkedEl = document.getElementById('lt1-parked-count');
                    const lt1StatusEl = document.getElementById('lt1-status-text');
                    const lt1IndEl = document.getElementById('lt1-indicator');

                    const lt2ParkedEl = document.getElementById('lt2-parked-count');
                    const lt2StatusEl = document.getElementById('lt2-status-text');
                    const lt2IndEl = document.getElementById('lt2-indicator');

                    if (lt1ParkedEl) lt1ParkedEl.innerText = lt1Parked;
                    if (lt1StatusEl) lt1StatusEl.innerText = lt1Avail > 0 ? `Sisa ${lt1Avail} slot` : 'Penuh';
                    if (lt1IndEl) lt1IndEl.className = `rounded-circle d-inline-block ${lt1Avail > 0 ? 'bg-success' : 'bg-danger'}`;

                    if (lt2ParkedEl) lt2ParkedEl.innerText = lt2Parked;
                    if (lt2StatusEl) lt2StatusEl.innerText = lt2Avail > 0 ? `Sisa ${lt2Avail} slot` : 'Penuh';
                    if (lt2IndEl) lt2IndEl.className = `rounded-circle d-inline-block ${lt2Avail > 0 ? 'bg-success' : 'bg-danger'}`;
                }
            }

            // Update dropdown pengendara jika admin
            if (data.available_clients && data.available_clients.length > 0) {
                const select = document.getElementById('user-select');
                if (select && select.tagName === 'SELECT') {
                    const existingIds = Array.from(select.options).map(o => parseInt(o.value));
                    const newIds = data.available_clients.map(c => parseInt(c.id));
                    const isDifferent = existingIds.length !== newIds.length || !newIds.every(id => existingIds.includes(id));
                    
                    if (isDifferent) {
                        select.innerHTML = '';
                        data.available_clients.forEach(c => {
                            const opt = document.createElement('option');
                            opt.value = c.id;
                            opt.textContent = `${c.name} (UID: ${c.uid || '-'})`;
                            if (c.id === currentUserId) opt.selected = true;
                            select.appendChild(opt);
                        });
                    }
                }
            }

            if (!data.user) {
                document.getElementById('state-parked').classList.add('d-none');
                document.getElementById('state-idle').classList.remove('d-none');
                document.getElementById('idle-card-uid').innerText = 'Belum Ada Kartu';
                return;
            }

            const prevParked = isParked;
            isParked = data.is_parked;
            currentUid = data.card ? data.card.uid : '';
            currentBalance = data.card ? parseInt(data.card.balance || 0) : 0;

            // Update Saldo Card Pengendara Utama
            const balDisplay = document.getElementById('user-balance-display');
            if (balDisplay) balDisplay.innerText = data.card ? data.card.balance_formatted : 'Rp 0';

            const cardUidSub = document.getElementById('user-card-uid-sub');
            if (cardUidSub) cardUidSub.innerText = currentUid ? `UID: ${currentUid}` : 'Belum Ada Kartu';

            const badgeStatus = document.getElementById('card-status-badge');
            if (badgeStatus) {
                if (data.card && data.card.status === 'active') {
                    badgeStatus.innerText = 'Aktif';
                    badgeStatus.className = 'badge bg-success-subtle text-success border border-success-subtle fw-bold';
                } else {
                    badgeStatus.innerText = 'Diblokir';
                    badgeStatus.className = 'badge bg-danger-subtle text-danger border border-danger-subtle fw-bold';
                }
            }

            // User info & stats
            document.getElementById('trip-count-badge').innerText = `${data.stats.total_trips} Sesi`;
            document.getElementById('total-spent-display').innerText = data.stats.total_spent_formatted;

            if (isParked) {
                // Show PARKED state
                document.getElementById('state-idle').classList.add('d-none');
                document.getElementById('state-parked').classList.remove('d-none');

                const tx = data.active_transaction;
                document.getElementById('parked-card-uid').innerText = `UID: ${currentUid}`;
                document.getElementById('gate-in-name').innerText = tx.gate_in_code;
                document.getElementById('gate-in-loc').innerText = tx.gate_in_location;
                
                // Update Badge Lantai Parkir
                const floorBadge = document.getElementById('parked-floor-badge');
                if (floorBadge) {
                    const floorNum = tx.parking_floor || 1;
                    floorBadge.innerText = `Lantai ${floorNum} (LT ${floorNum})`;
                    floorBadge.className = floorNum === 2 
                        ? 'badge bg-warning text-dark fw-bold' 
                        : 'badge bg-primary text-white fw-bold';
                }
                
                // Format jam masuk
                const inDate = new Date(tx.tap_in_time);
                document.getElementById('tap-in-time-display').innerText = inDate.toLocaleTimeString('id-ID');

                tapInTimestamp = inDate.getTime();
                updateLiveDuration();

                if (!timerInterval) {
                    timerInterval = setInterval(updateLiveDuration, 1000);
                }

                if (manual) {
                    showToast(`Status Sinkron: Sedang Parkir di ${tx.gate_in_code}`, '🚗', true);
                } else if (!prevParked && isParked) {
                    showToast(`Tap-In Berhasil Terdeteksi! Palang Terbuka`, '✅', true);
                }

            } else {
                // Show IDLE state
                if (timerInterval) {
                    clearInterval(timerInterval);
                    timerInterval = null;
                }

                document.getElementById('state-parked').classList.add('d-none');
                document.getElementById('state-idle').classList.remove('d-none');
                document.getElementById('idle-card-uid').innerText = `UID: ${currentUid || 'Tidak Ada Kartu'}`;

                // Update info kecukupan saldo di IDLE state
                const alertEl = document.getElementById('idle-balance-alert');
                const alertIcon = document.getElementById('idle-balance-icon');
                const alertText = document.getElementById('idle-balance-text');
                const alertVal = document.getElementById('idle-balance-val');
                if (alertVal) alertVal.innerText = `Rp ${currentBalance.toLocaleString('id-ID')}`;

                if (currentBalance >= 2000) {
                    if (alertEl) alertEl.className = 'p-3 rounded-3 border small d-flex align-items-center justify-content-between bg-success-subtle border-success-subtle';
                    if (alertIcon) alertIcon.className = 'bi bi-check-circle-fill text-success fs-5';
                    if (alertText) alertText.innerText = 'Saldo Anda mencukupi untuk tap masuk (Min. Rp 2.000).';
                } else {
                    if (alertEl) alertEl.className = 'p-3 rounded-3 border small d-flex align-items-center justify-content-between bg-danger-subtle border-danger-subtle';
                    if (alertIcon) alertIcon.className = 'bi bi-exclamation-triangle-fill text-danger fs-5';
                    if (alertText) alertText.innerText = 'Saldo kurang dari Rp 2.000. Isi saldo sebelum tap masuk!';
                }

                if (manual) {
                    showToast(`Status: Siap Masuk Gerbang Parkir`, 'ℹ️', true);
                } else if (prevParked && !isParked) {
                    showToast(`Tap-Out Berhasil! Pembayaran Parkir Selesai & Uang Masuk ke Admin`, '🎉', true);
                }
            }

            // Render History
            renderHistory(data.history);
        }

        function updateLiveDuration() {
            if (!isParked || !tapInTimestamp) return;

            const now = Date.now();
            const diffSeconds = Math.max(0, Math.floor((now - tapInTimestamp) / 1000));

            const hours = Math.floor(diffSeconds / 3600);
            const minutes = Math.floor((diffSeconds % 3600) / 60);
            const seconds = diffSeconds % 60;

            const pad = (n) => String(n).padStart(2, '0');
            document.getElementById('live-timer').innerText = `${pad(hours)} : ${pad(minutes)} : ${pad(seconds)}`;

            // Hitung tarif berjalan (Rp2.000 / jam pembulatan ke atas)
            const durationMinutes = Math.max(1, Math.ceil(diffSeconds / 60));
            const billedHours = Math.ceil(durationMinutes / 60);
            const totalFee = billedHours * 2000;

            document.getElementById('current-fee-display').innerText = `Rp ${totalFee.toLocaleString('id-ID')}`;
            document.getElementById('billed-hours-display').innerText = `${billedHours} Jam Dihitung`;

            // Update live breakdown saldo
            const pCurrentBal = document.getElementById('parked-current-balance');
            const pEstFee = document.getElementById('parked-estimated-fee');
            const pRemBal = document.getElementById('parked-remaining-balance');
            const pWarn = document.getElementById('insufficient-balance-warning');

            if (pCurrentBal) pCurrentBal.innerText = `Rp ${currentBalance.toLocaleString('id-ID')}`;
            if (pEstFee) pEstFee.innerText = `- Rp ${totalFee.toLocaleString('id-ID')}`;

            const remaining = currentBalance - totalFee;
            if (pRemBal) {
                if (remaining >= 0) {
                    pRemBal.innerText = `Rp ${remaining.toLocaleString('id-ID')}`;
                    pRemBal.className = 'font-monospace text-success fw-bold';
                    if (pWarn) pWarn.classList.add('d-none');
                } else {
                    pRemBal.innerText = `- Rp ${Math.abs(remaining).toLocaleString('id-ID')} (Kurang)`;
                    pRemBal.className = 'font-monospace text-danger fw-bold';
                    if (pWarn) pWarn.classList.remove('d-none');
                }
            }

            // Progress menuju jam berikutnya
            const minutesIntoCurrentHour = (durationMinutes % 60) === 0 && durationMinutes > 0 ? 60 : (durationMinutes % 60);
            const minutesLeft = 60 - minutesIntoCurrentHour;
            const progressPercent = Math.min(100, Math.max(5, Math.round((minutesIntoCurrentHour / 60) * 100)));

            document.getElementById('next-hour-countdown').innerText = `${minutesLeft} mnt tersisa`;
            document.getElementById('hourly-progress-bar').style.width = `${progressPercent}%`;
        }

        function renderHistory(list) {
            const container = document.getElementById('history-container');
            if (!list || list.length === 0) {
                container.innerHTML = `<div class="text-center py-4 text-secondary small">Belum ada riwayat parkir yang selesai.</div>`;
                return;
            }

            let html = '';
            list.forEach(item => {
                const inDate = new Date(item.tap_in_time);
                const outDate = item.tap_out_time ? new Date(item.tap_out_time) : null;
                const dateStr = inDate.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
                const timeInStr = inDate.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                const timeOutStr = outDate ? outDate.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '--:--';

                const fee = parseInt(item.total_fee || 0);
                const floorNum = item.parking_floor || 1;
                const floorBadge = floorNum === 2 
                    ? '<span class="badge bg-warning text-dark">LT 2</span>'
                    : '<span class="badge bg-primary text-white">LT 1</span>';
                const balAfter = item.balance_after !== null && item.balance_after !== undefined 
                    ? `<span class="d-block small text-secondary font-monospace" style="font-size: 0.7rem;">Sisa: Rp ${parseInt(item.balance_after).toLocaleString('id-ID')}</span>` 
                    : '';

                html += `
                    <div class="p-3 rounded-3 bg-light border d-flex align-items-center justify-content-between">
                        <div>
                            <div class="d-flex align-items-center gap-1.5 mb-1">
                                <span class="fw-bold text-dark small">${dateStr}</span>
                                ${floorBadge}
                                <span class="small text-secondary font-monospace">(${timeInStr} - ${timeOutStr})</span>
                            </div>
                            <div class="small text-secondary" style="font-size: 0.75rem;">
                                <span>Durasi: <strong class="text-dark">${item.duration_minutes} mnt</strong></span>
                                <span class="mx-1">•</span>
                                <span>${item.gate_in_code || 'Gate-01'} &rarr; ${item.gate_out_code || 'Gate-01'}</span>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="font-monospace fw-bold small text-danger">- Rp ${fee.toLocaleString('id-ID')}</span>
                            ${balAfter}
                            <span class="badge bg-success-subtle text-success border border-success-subtle d-inline-block mt-0.5" style="font-size: 0.65rem;">Lunas (Admin)</span>
                            <div class="mt-1">
                                <a href="{{ url('receipt') }}/${item.id}" target="_blank" class="text-decoration-none small fw-bold text-primary" style="font-size: 0.75rem;">
                                    <i class="bi bi-receipt"></i> Struk 58mm
                                </a>
                            </div>
                        </div>
                    </div>
                `;
            });
            container.innerHTML = html;
        }

        // ================= TOP UP SALDO MODAL LOGIC =================
        let topupBsModal = null;
        function openTopupModal() {
            document.getElementById('modal-current-balance').innerText = `Rp ${currentBalance.toLocaleString('id-ID')}`;
            document.getElementById('topup-amount-input').value = '';
            if (!topupBsModal) {
                topupBsModal = new bootstrap.Modal(document.getElementById('topupModal'));
            }
            topupBsModal.show();
        }

        function closeTopupModal() {
            if (topupBsModal) {
                topupBsModal.hide();
            }
        }

        function selectTopupAmount(amount) {
            document.getElementById('topup-amount-input').value = amount;
        }

        async function executeTopup() {
            const input = document.getElementById('topup-amount-input');
            const amount = parseInt(input.value);

            if (!amount || amount < 1000) {
                showToast('Nominal top up minimal Rp 1.000!', '⚠️', false);
                input.focus();
                return;
            }

            const btn = document.getElementById('btn-submit-topup');
            const origHtml = btn.innerHTML;
            btn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span><span>Memproses...</span>`;
            btn.disabled = true;

            try {
                const formData = new FormData();
                formData.append('action', 'topup');
                formData.append('user_id', currentUserId);
                formData.append('amount', amount);

                const res = await fetch('{{ route('client.api-status') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: formData
                });
                const result = await res.json();

                if (result.status === 'success') {
                    closeTopupModal();
                    showToast(result.message, '💰', true);
                    currentBalance = result.balance_after;
                    refreshData(true);
                } else {
                    showToast(result.message || 'Gagal melakukan top up', '❌', false);
                }
            } catch (err) {
                showToast('Kesalahan koneksi top up: ' + err.message, '❌', false);
            } finally {
                btn.innerHTML = origHtml;
                btn.disabled = false;
            }
        }

        // ================= BAYAR TIKET KARCIS VIA SALDO MEMBER =================
        let payTicketBsModal = null;
        function openPayTicketModal(initialCode = '') {
            const input = document.getElementById('pay-ticket-input');
            document.getElementById('pay-modal-balance').innerText = `Rp ${currentBalance.toLocaleString('id-ID')}`;
            document.getElementById('ticket-preview-box').classList.add('d-none');
            document.getElementById('btn-confirm-pay-ticket').classList.add('d-none');
            
            if (initialCode) {
                input.value = initialCode;
                checkTicketCode();
            } else {
                input.value = '';
            }

            if (!payTicketBsModal) {
                payTicketBsModal = new bootstrap.Modal(document.getElementById('payTicketModal'));
            }
            payTicketBsModal.show();
            setTimeout(() => input.focus(), 300);
        }

        function closePayTicketModal() {
            if (payTicketBsModal) {
                payTicketBsModal.hide();
            }
        }

        let pendingTicketData = null;

        async function checkTicketCode() {
            const input = document.getElementById('pay-ticket-input');
            const code = input.value.trim();
            if (!code) {
                showToast('Masukkan kode tiket karcis terlebih dahulu!', '⚠️', false);
                input.focus();
                return;
            }

            const btn = document.getElementById('btn-check-ticket');
            const origHtml = btn.innerHTML;
            btn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`;
            btn.disabled = true;

            try {
                const res = await fetch(`{{ route('client.api-status') }}?action=check_ticket&ticket_code=${encodeURIComponent(code)}`);
                const result = await res.json();

                if (result.status === 'success') {
                    pendingTicketData = result.data;
                    document.getElementById('ticket-prev-code').innerText = result.data.ticket_code;
                    document.getElementById('ticket-prev-in').innerText = result.data.tap_in_time + ' WIB';
                    document.getElementById('ticket-prev-dur').innerText = `${result.data.duration_minutes} Menit (${result.data.billed_hours} Jam)`;
                    document.getElementById('ticket-prev-fee').innerText = result.data.total_fee_formatted;
                    
                    const payBtn = document.getElementById('btn-confirm-pay-ticket');
                    const warnEl = document.getElementById('ticket-balance-warning');
                    
                    if (currentBalance >= result.data.total_fee) {
                        warnEl.classList.add('d-none');
                        payBtn.classList.remove('d-none');
                        payBtn.disabled = false;
                    } else {
                        const kurang = result.data.total_fee - currentBalance;
                        warnEl.innerText = `Saldo Anda tidak mencukupi (Kurang Rp ${kurang.toLocaleString('id-ID')}). Silakan isi saldo terlebih dahulu!`;
                        warnEl.classList.remove('d-none');
                        payBtn.classList.add('d-none');
                    }

                    document.getElementById('ticket-preview-box').classList.remove('d-none');
                } else {
                    document.getElementById('ticket-preview-box').classList.add('d-none');
                    document.getElementById('btn-confirm-pay-ticket').classList.add('d-none');
                    showToast(result.message || 'Tiket tidak ditemukan!', '❌', false);
                }
            } catch (err) {
                showToast('Gagal memeriksa tiket: ' + err.message, '❌', false);
            } finally {
                btn.innerHTML = origHtml;
                btn.disabled = false;
            }
        }

        async function executePayTicket() {
            if (!pendingTicketData) return;

            const btn = document.getElementById('btn-confirm-pay-ticket');
            const origHtml = btn.innerHTML;
            btn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span><span>Memproses...</span>`;
            btn.disabled = true;

            try {
                const formData = new FormData();
                formData.append('action', 'pay_ticket');
                formData.append('ticket_code', pendingTicketData.ticket_code);
                formData.append('user_id', currentUserId);

                const res = await fetch('{{ route('client.api-status') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: formData
                });
                const result = await res.json();

                if (result.status === 'success') {
                    closePayTicketModal();
                    showToast(result.message, '🎉', true);
                    currentBalance = result.data.balance_after;
                    refreshData(true);

                    // Tawarkan membuka struk bukti bayar
                    if (result.data.receipt_url) {
                        setTimeout(() => {
                            if (confirm('Pembayaran karcis berhasil! Ingin melihat dan mencetak bukti pembayaran struk LUNAS?')) {
                                window.open(result.data.receipt_url, '_blank');
                            }
                        }, 500);
                    }
                } else {
                    showToast(result.message || 'Pembayaran karcis gagal', '❌', false);
                }
            } catch (err) {
                showToast('Kesalahan koneksi: ' + err.message, '❌', false);
            } finally {
                btn.innerHTML = origHtml;
                btn.disabled = false;
            }
        }

        // Mulai Sinkronisasi Otomatis saat Halaman Dimuat
        window.addEventListener('DOMContentLoaded', () => {
            refreshData();
            // Polling realtime setiap 2.5 detik
            pollInterval = setInterval(refreshData, 2500);

            // Cek apakah ada query param ?pay_ticket=... dari scan QR
            const urlParams = new URLSearchParams(window.location.search);
            const payTicketCode = urlParams.get('pay_ticket');
            if (payTicketCode) {
                setTimeout(() => openPayTicketModal(payTicketCode), 400);
            }
        });
    </script>
</body>
</html>
