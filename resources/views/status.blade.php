<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Display Monitor Ketersediaan Parkir - E-Parkir IoT (Sigma Boys)</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            background-color: #0d1117;
        }
    </style>
</head>
<body class="d-flex flex-column justify-content-between min-vh-100 p-3 p-md-4">

    <!-- TOP HEADER -->
    <header class="container-fluid mb-4">
        <div class="card border-0 shadow-lg bg-dark rounded-4 p-3 px-md-4">
            <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-3">
                <!-- Left Brand -->
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-primary fs-3 p-2 rounded-3">
                        <i class="bi bi-p-square-fill"></i>
                    </span>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h4 class="fw-bold mb-0 text-white">E-PARKIR <span class="text-warning">LIVE MONITOR</span></h4>
                            <span class="badge bg-warning text-dark fw-bold">SIGMA BOYS</span>
                        </div>
                        <small class="text-secondary">Status Okupansi Area Parkir & Kendaraan Berjalan Realtime</small>
                    </div>
                </div>

                <!-- Right Status & Controls -->
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <!-- Hardware ESP Badge -->
                    <div id="esp-badge" class="badge bg-secondary-subtle text-secondary border px-3 py-2 rounded-pill d-flex align-items-center gap-2">
                        <span class="spinner-grow spinner-grow-sm" role="status"></span>
                        <span>Memeriksa Gerbang...</span>
                    </div>

                    <!-- Live Digital Clock -->
                    <div class="badge bg-dark border text-light px-3 py-2 rounded-pill font-monospace d-flex align-items-center gap-2">
                        <i class="bi bi-clock-fill text-warning"></i>
                        <span id="digital-clock">--:--:-- WIB</span>
                    </div>

                    <!-- Fullscreen Toggle Button -->
                    <button onclick="toggleFullScreen()" title="Layar Penuh" class="btn btn-outline-light btn-sm rounded-circle p-2" style="width: 38px; height: 38px;">
                        <i id="fullscreen-icon" class="bi bi-arrows-fullscreen"></i>
                    </button>

                    <!-- Link to Admin -->
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-primary btn-sm rounded-pill px-3 py-2 fw-semibold d-flex align-items-center gap-1">
                        <i class="bi bi-shield-lock-fill"></i>
                        <span class="d-none d-md-inline">Panel Admin</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- MAIN CONTENT -->
    <main class="container-fluid flex-grow-1">

        @php
            $hasVehicles = $parkedCount > 0;
        @endphp

        <!-- 1. BIG HERO BANNER -->
        <div id="hero-banner" class="card border-0 shadow-lg bg-dark rounded-4 p-4 p-md-5 mb-4 position-relative overflow-hidden">
            <div class="row align-items-center g-4">
                <div class="col-lg-8 d-flex flex-column flex-md-row align-items-center text-center text-md-start gap-4">
                    <div id="hero-icon-box" class="badge {{ $hasVehicles ? 'bg-warning text-dark' : 'bg-success text-white' }} p-4 rounded-4 fs-1 shadow">
                        <i id="hero-icon" class="bi {{ $hasVehicles ? 'bi-car-front-fill' : 'bi-check-circle-fill' }}"></i>
                    </div>
                    <div>
                        <div class="mb-2">
                            <span id="hero-pill" class="badge {{ $hasVehicles ? 'bg-warning text-dark' : 'bg-success text-white' }} px-3 py-1 text-uppercase fw-bold">
                                @if ($hasVehicles)
                                    AREA TERISI ({{ $parkedCount }} KENDARAAN)
                                @else
                                    SEMUA SLOT TERSEDIA
                                @endif
                            </span>
                        </div>
                        <h2 id="hero-title" class="fw-bold text-white mb-2">
                            @if ($hasVehicles)
                                Ada <span class="text-warning">{{ $parkedCount }} Kendaraan</span> Sedang Parkir
                            @else
                                <span class="text-success">Area Parkir Kosong</span> &amp; Siap Digunakan
                            @endif
                        </h2>
                        <p id="hero-subtitle" class="text-secondary mb-0">
                            @if ($hasVehicles)
                                Sisa {{ $availableSlots }} slot dari total {{ $totalCapacity }} kapasitas. Tarif aktif berjalan Rp2.000 / Jam.
                            @else
                                Semua {{ $totalCapacity }} slot parkir dalam kondisi kosong dan siap menerima kendaraan baru.
                            @endif
                        </p>
                    </div>
                </div>

                <div class="col-lg-4 text-center">
                    <div class="card bg-black bg-opacity-50 border border-secondary border-opacity-25 p-4 rounded-4">
                        <span class="text-uppercase small fw-bold text-secondary">Kendaraan Terparkir</span>
                        <div class="d-flex align-items-baseline justify-content-center gap-1 my-2">
                            <span id="hero-parked-count" class="display-4 fw-bold {{ $hasVehicles ? 'text-warning' : 'text-success' }} font-monospace">{{ $parkedCount }}</span>
                            <span class="text-secondary fw-bold fs-5">/ <span id="hero-capacity">{{ $totalCapacity }}</span></span>
                        </div>
                        <span id="hero-slot-info" class="badge {{ $hasVehicles ? 'bg-warning-subtle text-warning' : 'bg-success-subtle text-success' }} fs-6 fw-bold">
                            {{ $availableSlots }} Slot Tersedia
                        </span>
                        <div class="d-flex justify-content-center gap-2 mt-3 pt-3 border-top border-secondary border-opacity-25 font-monospace small">
                            <span class="badge bg-info-subtle text-info border border-info-subtle">LT 1: <strong id="status-lt1">{{ $parkedLt1 }}</strong>/10</span>
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">LT 2: <strong id="status-lt2">{{ $parkedLt2 }}</strong>/10</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. SUMMARY METRICS ROW -->
        <div class="row g-3 mb-4">
            <!-- Slot Terpakai -->
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm bg-dark rounded-4 p-3 h-100">
                    <div class="d-flex align-items-center justify-content-between text-secondary mb-2">
                        <small class="text-uppercase fw-bold">Slot Terpakai</small>
                        <i class="bi bi-car-front-fill text-warning fs-5"></i>
                    </div>
                    <div class="d-flex align-items-baseline gap-2">
                        <h3 id="stat-parked" class="fw-bold text-white font-monospace mb-0">{{ $parkedCount }}</h3>
                        <small class="text-secondary">Kendaraan</small>
                    </div>
                </div>
            </div>

            <!-- Slot Kosong -->
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm bg-dark rounded-4 p-3 h-100">
                    <div class="d-flex align-items-center justify-content-between text-secondary mb-2">
                        <small class="text-uppercase fw-bold">Slot Kosong</small>
                        <i class="bi bi-check-circle-fill text-success fs-5"></i>
                    </div>
                    <div class="d-flex align-items-baseline gap-2">
                        <h3 id="stat-available" class="fw-bold text-success font-monospace mb-0">{{ $availableSlots }}</h3>
                        <small class="text-secondary">Siap Pakai</small>
                    </div>
                </div>
            </div>

            <!-- Masuk Hari Ini -->
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm bg-dark rounded-4 p-3 h-100">
                    <div class="d-flex align-items-center justify-content-between text-secondary mb-2">
                        <small class="text-uppercase fw-bold">Masuk Hari Ini</small>
                        <i class="bi bi-box-arrow-in-right text-info fs-5"></i>
                    </div>
                    <div class="d-flex align-items-baseline gap-2">
                        <h3 id="stat-today-in" class="fw-bold text-white font-monospace mb-0">0</h3>
                        <small class="text-secondary">Tap-In</small>
                    </div>
                </div>
            </div>

            <!-- Keluar Hari Ini -->
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm bg-dark rounded-4 p-3 h-100">
                    <div class="d-flex align-items-center justify-content-between text-secondary mb-2">
                        <small class="text-uppercase fw-bold">Keluar Hari Ini</small>
                        <i class="bi bi-box-arrow-right text-purple fs-5"></i>
                    </div>
                    <div class="d-flex align-items-baseline gap-2">
                        <h3 id="stat-today-out" class="fw-bold text-white font-monospace mb-0">0</h3>
                        <small class="text-secondary">Selesai</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. LIVE VEHICLE LIST CONTAINER -->
        <div class="mb-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h5 class="fw-bold text-white mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-list-columns-reverse text-primary"></i>
                    <span>Daftar Kendaraan yang Sedang Parkir</span>
                </h5>
                <span id="vehicle-count-tag" class="badge bg-secondary font-monospace">0 Kendaraan</span>
            </div>

            <!-- Grid Kendaraan -->
            <div id="vehicle-grid" class="row g-3">
                <!-- Diisi via JavaScript -->
            </div>

            <!-- State Kosong Placeholder -->
            <div id="empty-state" class="card border-secondary border-dashed bg-dark rounded-4 p-5 text-center d-none">
                <div class="mx-auto mb-3">
                    <span class="badge bg-success-subtle text-success fs-1 p-3 rounded-circle">
                        <i class="bi bi-check2-all"></i>
                    </span>
                </div>
                <h4 class="fw-bold text-white mb-1">Area Parkir Saat Ini Kosong</h4>
                <p class="text-secondary small mb-3">
                    Tidak ada kendaraan yang sedang terparkir. Tempelkan kartu RFID pada gerbang masuk untuk mulai merekam waktu parkir.
                </p>
                <div>
                    <span class="badge bg-dark border border-secondary text-secondary px-3 py-2">
                        <i class="bi bi-tag-fill text-warning me-1"></i> Tarif Flat: <strong class="text-white">Rp2.000 / Jam</strong>
                    </span>
                </div>
            </div>
        </div>

    </main>

    <!-- FOOTER -->
    <footer class="container-fluid mt-auto pt-3 border-top border-secondary border-opacity-25">
        <div class="d-flex flex-column flex-sm-row align-items-center justify-content-between gap-2 text-secondary small">
            <div>&copy; {{ date('Y') }} <strong>E-Parkir IoT Platform</strong> &bull; Kelompok Sigma Boys</div>
            <div class="d-flex align-items-center gap-3">
                <span>Auto-Sync 1.5s</span>
                <span>&bull;</span>
                <a href="{{ route('login') }}" class="text-decoration-none text-info">Portal Login</a>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5.3 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- JAVASCRIPT REALTIME SYNC LOGIC -->
    <script>
        let liveVehicles = [];
        let timerTickInterval = null;

        function updateDigitalClock() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('id-ID', { hour12: false }) + ' WIB';
            document.getElementById('digital-clock').innerText = timeStr;
        }
        setInterval(updateDigitalClock, 1000);
        updateDigitalClock();

        async function pingEspWifi() {
            try {
                const ctrl = new AbortController();
                const tid = setTimeout(() => ctrl.abort(), 2000);
                await fetch('http://192.168.4.1/api/status', {
                    method: 'GET',
                    mode: 'no-cors',
                    cache: 'no-store',
                    signal: ctrl.signal
                });
                clearTimeout(tid);
                return true;
            } catch (e) {}
            return false;
        }

        async function fetchParkingStatus() {
            try {
                const [resAjax, isWifiDirect] = await Promise.all([
                    fetch('{{ url("status") }}?ajax=1').then(r => r.json()).catch(() => null),
                    pingEspWifi()
                ]);

                if (resAjax && resAjax.status === 'success') {
                    resAjax.esp_online = isWifiDirect || resAjax.esp_online;
                    renderDashboard(resAjax);
                }
            } catch (err) {
                console.error("Gagal sinkron data:", err);
            }
        }

        function renderDashboard(data) {
            const espBadge = document.getElementById('esp-badge');
            if (data.esp_online) {
                espBadge.className = 'badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill d-flex align-items-center gap-2';
                espBadge.innerHTML = `<span class="spinner-grow spinner-grow-sm text-success" role="status"></span><span>Terhubung ke ESP8266</span>`;
            } else {
                espBadge.className = 'badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill d-flex align-items-center gap-2';
                espBadge.innerHTML = `<i class="bi bi-x-circle-fill text-danger"></i><span>Tidak Terhubung ke ESP8266</span>`;
            }

            document.getElementById('stat-parked').innerText = data.parked_count;
            document.getElementById('stat-available').innerText = data.available_slots;
            document.getElementById('stat-today-in').innerText = data.today_in;
            document.getElementById('stat-today-out').innerText = data.today_out;
            document.getElementById('hero-parked-count').innerText = data.parked_count;
            document.getElementById('hero-capacity').innerText = data.capacity;
            document.getElementById('hero-slot-info').innerText = `${data.available_slots} Slot Tersedia`;
            document.getElementById('vehicle-count-tag').innerText = `${data.parked_count} Kendaraan`;
            
            if (document.getElementById('status-lt1') && data.parked_lt1 !== undefined) {
                document.getElementById('status-lt1').innerText = data.parked_lt1;
            }
            if (document.getElementById('status-lt2') && data.parked_lt2 !== undefined) {
                document.getElementById('status-lt2').innerText = data.parked_lt2;
            }

            const heroIconBox = document.getElementById('hero-icon-box');
            const heroIcon = document.getElementById('hero-icon');
            const heroPill = document.getElementById('hero-pill');
            const heroTitle = document.getElementById('hero-title');
            const heroSubtitle = document.getElementById('hero-subtitle');

            if (data.has_vehicles) {
                heroIconBox.className = 'badge bg-warning text-dark p-4 rounded-4 fs-1 shadow';
                heroIcon.className = 'bi bi-car-front-fill';
                heroPill.className = 'badge bg-warning text-dark px-3 py-1 text-uppercase fw-bold';
                heroPill.innerHTML = `AREA TERISI (${data.parked_count} KENDARAAN)`;
                heroTitle.innerHTML = `Ada <span class="text-warning">${data.parked_count} Kendaraan</span> Sedang Parkir`;
                heroSubtitle.innerText = `Sisa ${data.available_slots} slot dari total ${data.capacity} kapasitas. Tarif aktif berjalan Rp2.000 / Jam.`;
            } else {
                heroIconBox.className = 'badge bg-success p-4 rounded-4 fs-1 shadow';
                heroIcon.className = 'bi bi-check-circle-fill';
                heroPill.className = 'badge bg-success px-3 py-1 text-uppercase fw-bold';
                heroPill.innerHTML = `SEMUA SLOT TERSEDIA`;
                heroTitle.innerHTML = `<span class="text-success">Area Parkir Kosong</span> & Siap Digunakan`;
                heroSubtitle.innerText = `Semua ${data.capacity} slot parkir dalam kondisi kosong dan siap menerima kendaraan baru.`;
            }

            const grid = document.getElementById('vehicle-grid');
            const emptyState = document.getElementById('empty-state');

            if (!data.has_vehicles) {
                grid.innerHTML = '';
                emptyState.classList.remove('d-none');
                liveVehicles = [];
            } else {
                emptyState.classList.add('d-none');
                liveVehicles = data.vehicles;
                renderVehicleCards(data.vehicles);
            }
        }

        function renderVehicleCards(vehicles) {
            const grid = document.getElementById('vehicle-grid');
            const currentIds = Array.from(grid.querySelectorAll('[data-vehicle-id]')).map(el => parseInt(el.getAttribute('data-vehicle-id')));
            const newIds = vehicles.map(v => v.id);
            const isSameList = currentIds.length === newIds.length && currentIds.every((id, idx) => id === newIds[idx]);

            if (!isSameList) {
                grid.innerHTML = '';
                vehicles.forEach(v => {
                    const col = document.createElement('div');
                    col.className = 'col-12 col-md-6 col-lg-4';
                    col.setAttribute('data-vehicle-id', v.id);

                    const floorNum = parseInt(v.parking_floor) || 1;
                    const floorBadge = floorNum === 2 ? '<span class="badge bg-warning text-dark">LT 2</span>' : '<span class="badge bg-info text-dark">LT 1</span>';

                    col.innerHTML = `
                        <div class="card border-0 shadow-sm bg-dark rounded-4 p-3 h-100 position-relative">
                            <div class="d-flex align-items-center justify-content-between pb-3 border-bottom border-secondary border-opacity-25">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-primary-subtle text-primary fs-4 p-2 rounded-3">
                                        <i class="bi bi-car-front-fill"></i>
                                    </span>
                                    <div>
                                        <h6 class="fw-bold mb-0 text-white text-truncate" style="max-width: 150px;">${escapeHtml(v.user_name)}</h6>
                                        <small class="text-secondary font-monospace">UID: <span class="text-warning fw-bold">${escapeHtml(v.uid)}</span></small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-1">
                                    ${floorBadge}
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">PARKIR</span>
                                </div>
                            </div>

                            <div class="row g-2 my-3 text-center">
                                <div class="col-6">
                                    <div class="bg-black bg-opacity-50 p-2 rounded-3 border border-secondary border-opacity-25">
                                        <small class="text-secondary text-uppercase d-block" style="font-size: 0.7rem;">Durasi</small>
                                        <div id="timer-${v.id}" class="font-monospace fw-bold fs-6 text-white">00:00:00</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="bg-primary-subtle p-2 rounded-3 border border-primary-subtle">
                                        <small class="text-primary text-uppercase d-block fw-semibold" style="font-size: 0.7rem;">Estimasi Biaya</small>
                                        <div id="fee-${v.id}" class="font-monospace fw-bold fs-6 text-primary">${escapeHtml(v.fee_formatted)}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex align-items-center justify-content-between pt-2 border-top border-secondary border-opacity-25 small text-secondary">
                                <div class="d-flex align-items-center gap-1">
                                    <i class="bi bi-box-arrow-in-right text-primary"></i>
                                    <span>${escapeHtml(v.gate_in)}</span>
                                </div>
                                <span class="font-monospace text-light">Masuk: ${escapeHtml(v.tap_in_formatted)}</span>
                            </div>
                        </div>
                    `;
                    grid.appendChild(col);
                });
            }

            updateAllTimers();
        }

        function updateAllTimers() {
            const now = Date.now();
            liveVehicles.forEach(v => {
                const elTimer = document.getElementById(`timer-${v.id}`);
                const elFee = document.getElementById(`fee-${v.id}`);
                if (!elTimer) return;

                const inTime = new Date(v.tap_in_time).getTime();
                const diffMs = Math.max(0, now - inTime);
                const totalSeconds = Math.floor(diffMs / 1000);

                const hours = Math.floor(totalSeconds / 3600);
                const minutes = Math.floor((totalSeconds % 3600) / 60);
                const seconds = totalSeconds % 60;

                const pad = n => String(n).padStart(2, '0');
                elTimer.innerText = `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;

                const durationMinutes = Math.max(1, Math.ceil(totalSeconds / 60));
                const billedHours = Math.ceil(durationMinutes / 60);
                const currentFee = billedHours * 2000;
                
                if (elFee) elFee.innerText = 'Rp ' + currentFee.toLocaleString('id-ID');
            });
        }

        if (!timerTickInterval) {
            timerTickInterval = setInterval(updateAllTimers, 1000);
        }

        setInterval(fetchParkingStatus, 1500);
        fetchParkingStatus();

        function toggleFullScreen() {
            const icon = document.getElementById('fullscreen-icon');
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
                icon.className = 'bi bi-fullscreen-exit';
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                    icon.className = 'bi bi-arrows-fullscreen';
                }
            }
        }

        function escapeHtml(text) {
            if (text === null || text === undefined) return '';
            return String(text)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }
    </script>
</body>
</html>
