<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Display Monitor Ketersediaan Parkir - E-Parkir IoT (Sigma Boys)</title>
    <!-- Offline Vanilla CSS Engine (100% Mandiri Tanpa Butuh Internet) -->
    <link rel="stylesheet" href="{{ asset('assets/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #060e22;
            background-image: 
                radial-gradient(at 0% 0%, rgba(35, 115, 244, 0.18) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(255, 213, 30, 0.12) 0px, transparent 50%),
                radial-gradient(at 50% 50%, rgba(35, 115, 244, 0.08) 0px, transparent 60%);
            min-height: 100vh;
        }
        .pulse-yellow {
            box-shadow: 0 0 0 0 rgba(255, 213, 30, 0.7);
            animation: pulse-yellow 2s infinite;
        }
        @keyframes pulse-yellow {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 213, 30, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 12px rgba(255, 213, 30, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 213, 30, 0); }
        }
        .pulse-green {
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            animation: pulse-green 2s infinite;
        }
        @keyframes pulse-green {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 12px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }
        .glass-card {
            background: rgba(15, 32, 72, 0.75);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="text-white antialiased selection:bg-brand-yellow selection:text-slate-900 min-h-screen flex flex-col justify-between p-4 sm:p-6 lg:p-8">

    <!-- TOP HEADER -->
    <header class="max-w-7xl mx-auto w-full mb-6">
        <div class="glass-card rounded-3xl p-4 sm:p-5 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-2xl">
            <!-- Left: Brand Logo & Title -->
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-[#00719C] text-white flex items-center justify-center text-2xl shadow-lg shadow-[#00719C]/40">
                    <i class="fa-solid fa-square-parking"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl sm:text-2xl font-black tracking-tight text-white">E-PARKIR <span class="text-[#FFD51E]">LIVE MONITOR</span></h1>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-[#FFD51E] text-[#091530]">SIGMA BOYS</span>
                    </div>
                    <p class="text-xs text-slate-400 font-medium">Status Okupansi Area Parkir &amp; Kendaraan Berjalan Realtime</p>
                </div>
            </div>

            <!-- Right: Status Badges & Clock -->
            <div class="flex items-center flex-wrap gap-2.5 sm:gap-3">
                <!-- Hardware Status Badge -->
                <div id="esp-badge" class="px-3.5 py-1.5 rounded-2xl text-xs font-bold flex items-center gap-2 bg-slate-800/80 border border-slate-700 text-slate-400">
                    <span class="w-2 h-2 rounded-full bg-slate-500"></span>
                    <span>Memeriksa Gerbang...</span>
                </div>

                <!-- Clock -->
                <div class="px-3.5 py-1.5 rounded-2xl bg-black/40 border border-white/10 font-mono text-xs font-bold text-slate-300 flex items-center gap-2">
                    <i class="fa-regular fa-clock text-[#FFD51E]"></i>
                    <span id="digital-clock">--:--:-- WIB</span>
                </div>

                <!-- Fullscreen Toggle -->
                <button onclick="toggleFullScreen()" title="Layar Penuh" class="p-2.5 rounded-2xl bg-white/10 hover:bg-white/20 border border-white/10 text-white transition cursor-pointer">
                    <i id="fullscreen-icon" class="fa-solid fa-expand text-xs"></i>
                </button>

                <!-- Shortcut ke Admin -->
                <a href="{{ route('admin.dashboard') }}" class="px-3.5 py-1.5 rounded-2xl bg-[#00719C] hover:bg-blue-600 text-white text-xs font-bold transition flex items-center gap-1.5 shadow">
                    <i class="fa-solid fa-lock"></i>
                    <span class="hidden md:inline">Panel Admin</span>
                </a>
            </div>
        </div>
    </header>

    <!-- MAIN DASHBOARD CONTENT -->
    <main class="max-w-7xl mx-auto w-full flex-1 space-y-6">

        <!-- 1. BIG HERO AVAILABILITY BANNER -->
        <div id="hero-banner" class="glass-card rounded-3xl p-6 sm:p-8 transition-all duration-500 relative overflow-hidden shadow-2xl border-2 border-white/10">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6 relative z-10">
                <div class="flex items-center gap-5 text-center md:text-left flex-col md:flex-row">
                    <div id="hero-icon-box" class="w-20 h-20 sm:w-24 sm:h-24 rounded-3xl flex items-center justify-center text-4xl sm:text-5xl shadow-2xl transition-all">
                        <i id="hero-icon" class="fa-solid fa-car"></i>
                    </div>
                    <div>
                        <div class="flex items-center justify-center md:justify-start gap-2 mb-1">
                            <span id="hero-pill" class="px-3 py-1 rounded-full text-xs font-extrabold uppercase tracking-wider">
                                MEMERIKSA STATUS...
                            </span>
                        </div>
                        <h2 id="hero-title" class="text-2xl sm:text-4xl font-black text-white tracking-tight">
                            Menghubungkan ke Sistem Parkir...
                        </h2>
                        <p id="hero-subtitle" class="text-xs sm:text-sm text-slate-300 mt-1 max-w-xl">
                            Memuat data kendaraan terparkir secara realtime dari sensor gerbang IoT.
                        </p>
                    </div>
                </div>

                <!-- Big Count Badge -->
                <div class="text-center bg-black/40 px-6 py-4 rounded-3xl border border-white/10 flex flex-col items-center justify-center min-w-[180px]">
                    <span class="text-[10px] uppercase font-bold tracking-widest text-slate-400">Kendaraan Terparkir</span>
                    <div class="flex items-baseline gap-1 my-1">
                        <span id="hero-parked-count" class="text-4xl sm:text-5xl font-black text-[#FFD51E] font-mono">0</span>
                        <span class="text-sm font-bold text-slate-400">/ <span id="hero-capacity">20</span></span>
                    </div>
                    <span id="hero-slot-info" class="text-xs font-semibold text-emerald-400">20 Slot Tersedia</span>
                    <div class="flex items-center gap-2 mt-2 pt-2 border-t border-white/10 text-[10px] font-mono">
                        <span class="px-2 py-0.5 rounded-full bg-sky-500/20 text-sky-300 border border-sky-500/30 font-bold">LT 1: <b id="status-lt1">{{ $parkedLt1 }}</b>/10</span>
                        <span class="px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30 font-bold">LT 2: <b id="status-lt2">{{ $parkedLt2 }}</b>/10</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. SUMMARY METRIC STATS -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
            <!-- Slot Terisi -->
            <div class="glass-card rounded-2xl p-4 sm:p-5 border border-white/10 shadow-sm relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Slot Terpakai</span>
                    <i class="fa-solid fa-car-side text-[#FFD51E] text-base"></i>
                </div>
                <div class="mt-2 flex items-baseline gap-1.5">
                    <span id="stat-parked" class="text-2xl sm:text-3xl font-extrabold text-white font-mono">0</span>
                    <span class="text-xs text-slate-400">Mobil/Motor</span>
                </div>
            </div>

            <!-- Slot Kosong -->
            <div class="glass-card rounded-2xl p-4 sm:p-5 border border-white/10 shadow-sm relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Slot Kosong</span>
                    <i class="fa-solid fa-circle-check text-emerald-400 text-base"></i>
                </div>
                <div class="mt-2 flex items-baseline gap-1.5">
                    <span id="stat-available" class="text-2xl sm:text-3xl font-extrabold text-emerald-400 font-mono">10</span>
                    <span class="text-xs text-slate-400">Siap Pakai</span>
                </div>
            </div>

            <!-- Total Masuk Hari Ini -->
            <div class="glass-card rounded-2xl p-4 sm:p-5 border border-white/10 shadow-sm relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Masuk Hari Ini</span>
                    <i class="fa-solid fa-arrow-right-to-bracket text-[#00719C] text-base"></i>
                </div>
                <div class="mt-2 flex items-baseline gap-1.5">
                    <span id="stat-today-in" class="text-2xl sm:text-3xl font-extrabold text-white font-mono">0</span>
                    <span class="text-xs text-slate-400">Tap-In</span>
                </div>
            </div>

            <!-- Total Keluar Hari Ini -->
            <div class="glass-card rounded-2xl p-4 sm:p-5 border border-white/10 shadow-sm relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Keluar Hari Ini</span>
                    <i class="fa-solid fa-arrow-right-from-bracket text-purple-400 text-base"></i>
                </div>
                <div class="mt-2 flex items-baseline gap-1.5">
                    <span id="stat-today-out" class="text-2xl sm:text-3xl font-extrabold text-white font-mono">0</span>
                    <span class="text-xs text-slate-400">Selesai</span>
                </div>
            </div>
        </div>

        <!-- 3. LIVE VEHICLE LIST CONTAINER -->
        <div class="space-y-4">
            <div class="flex items-center justify-between px-1">
                <h3 class="text-sm sm:text-base font-extrabold text-white flex items-center gap-2">
                    <span>Daftar Kendaraan yang Sedang Parkir</span>
                </h3>
                <span id="vehicle-count-tag" class="text-xs font-bold text-slate-400 font-mono">0 Kendaraan</span>
            </div>

            <!-- Grid Kendaraan -->
            <div id="vehicle-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Diisi via JavaScript secara dinamis -->
            </div>

            <!-- State Kosong Placeholder (Saat tidak ada kendaraan) -->
            <div id="empty-state" class="hidden glass-card rounded-3xl p-10 sm:p-14 text-center border-2 border-dashed border-white/15">
                <div class="w-20 h-20 mx-auto rounded-3xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 flex items-center justify-center text-3xl mb-4">
                    <i class="fa-solid fa-shield-cat"></i>
                </div>
                <h4 class="text-lg font-bold text-white mb-1">Area Parkir Saat Ini Kosong</h4>
                <p class="text-xs text-slate-400 max-w-md mx-auto leading-relaxed">
                    Tidak ada kendaraan yang sedang terparkir. Tempelkan kartu RFID pada gerbang masuk untuk mulai merekam waktu parkir.
                </p>
                <div class="mt-5 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white/5 border border-white/10 text-xs font-semibold text-slate-300">
                    <i class="fa-solid fa-tag text-[#FFD51E]"></i> Tarif Parkir: <strong class="text-white">Rp2.000 / Jam</strong> (Pembulatan Ke Atas)
                </div>
            </div>
        </div>

    </main>

    <!-- FOOTER -->
    <footer class="max-w-7xl mx-auto w-full mt-8 pt-4 border-t border-white/10 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-400 gap-2">
        <p>E-Parkir IoT Platform &copy; {{ date('Y') }} Kelompok Sigma Boys. Sistem Palang Pintu &amp; Tarif Parkir Otomatis.</p>
        <div class="flex items-center gap-3">
            <span class="flex items-center gap-1.5">Auto-Sync 1.5s</span>
            <span>&bull;</span>
            <a href="{{ route('client.dashboard') }}" class="text-slate-300 hover:text-[#FFD51E] font-medium">Portal Pengendara (Mobile)</a>
        </div>
    </footer>

    <!-- JAVASCRIPT REALTIME SYNC LOGIC -->
    <script>
        let liveVehicles = [];
        let timerTickInterval = null;

        // Digital Clock
        function updateDigitalClock() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('id-ID', { hour12: false }) + ' WIB';
            document.getElementById('digital-clock').innerText = timeStr;
        }
        setInterval(updateDigitalClock, 1000);
        updateDigitalClock();

        // Cek koneksi langsung ke Hotspot / Wi-Fi ESP8266 (192.168.4.1)
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
            } catch (e) {
                // Browser tidak tersambung langsung ke Wi-Fi ESP8266
            }
            return false;
        }

        // Polling Data Realtime dari Server
        async function fetchParkingStatus() {
            try {
                const [resAjax, isWifiDirect] = await Promise.all([
                    fetch('{{ url("status") }}?ajax=1').then(r => r.json()).catch(() => null),
                    pingEspWifi()
                ]);

                if (resAjax && resAjax.status === 'success') {
                    // Gabungkan hasil ping Wi-Fi ESP dan status backend
                    resAjax.esp_online = isWifiDirect || resAjax.esp_online;
                    renderDashboard(resAjax);
                }
            } catch (err) {
                console.error("Gagal sinkron data:", err);
            }
        }

        // Render Antarmuka Berdasarkan Data
        function renderDashboard(data) {
            // 1. ESP8266 Status Badge
            const espBadge = document.getElementById('esp-badge');
            if (data.esp_online) {
                espBadge.className = 'px-3.5 py-1.5 rounded-2xl text-xs font-extrabold flex items-center gap-2 bg-emerald-950/60 border border-emerald-500/40 text-emerald-300 shadow-sm';
                espBadge.innerHTML = `<span class="w-1.5 h-1.5 rounded-full bg-emerald-400 inline-block animate-pulse"></span><span>Terhubung ke ESP8266</span>`;
            } else {
                espBadge.className = 'px-3.5 py-1.5 rounded-2xl text-xs font-extrabold flex items-center gap-2 bg-rose-950/60 border border-rose-500/40 text-rose-300 shadow-sm';
                espBadge.innerHTML = `<span class="w-1.5 h-1.5 rounded-full bg-rose-400 inline-block"></span><span>Tidak Terhubung ke ESP8266</span>`;
            }

            // 2. Summary Stats
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

            // 3. Hero Availability Banner State
            const heroBanner = document.getElementById('hero-banner');
            const heroIconBox = document.getElementById('hero-icon-box');
            const heroIcon = document.getElementById('hero-icon');
            const heroPill = document.getElementById('hero-pill');
            const heroTitle = document.getElementById('hero-title');
            const heroSubtitle = document.getElementById('hero-subtitle');

            if (data.has_vehicles) {
                // Ada kendaraan yang sedang parkir
                heroBanner.style.borderColor = 'rgba(255, 213, 30, 0.35)';
                heroIconBox.className = 'w-20 h-20 sm:w-24 sm:h-24 rounded-3xl bg-[#FFD51E] text-[#091530] flex items-center justify-center text-4xl sm:text-5xl shadow-2xl';
                heroIcon.className = 'fa-solid fa-car-side';
                heroPill.className = 'px-3 py-1 rounded-full text-xs font-black bg-[#FFD51E] text-[#091530] flex items-center gap-1.5';
                heroPill.innerHTML = `AREA TERISI (${data.parked_count} KENDARAAN)`;
                heroTitle.innerHTML = `Ada <span class="text-[#FFD51E]">${data.parked_count} Kendaraan</span> Sedang Parkir`;
                heroSubtitle.innerText = `Sisa ${data.available_slots} slot dari total ${data.capacity} kapasitas. Tarif aktif berjalan Rp2.000 / Jam.`;
            } else {
                // Tidak ada kendaraan (Kosong)
                heroBanner.style.borderColor = 'rgba(16, 185, 129, 0.35)';
                heroIconBox.className = 'w-20 h-20 sm:w-24 sm:h-24 rounded-3xl bg-emerald-50 text-slate-950 flex items-center justify-center text-4xl sm:text-5xl shadow-2xl';
                heroIcon.className = 'fa-solid fa-circle-check';
                heroPill.className = 'px-3 py-1 rounded-full text-xs font-black bg-emerald-400 text-slate-950 flex items-center gap-1.5';
                heroPill.innerHTML = `SEMUA SLOT TERSEDIA`;
                heroTitle.innerHTML = `<span class="text-emerald-400">Area Parkir Kosong</span> &amp; Siap Digunakan`;
                heroSubtitle.innerText = `Semua ${data.capacity} slot parkir dalam kondisi kosong dan siap menerima kendaraan baru.`;
            }

            // 4. Vehicle Grid Rendering
            const grid = document.getElementById('vehicle-grid');
            const emptyState = document.getElementById('empty-state');

            if (!data.has_vehicles) {
                grid.innerHTML = '';
                emptyState.classList.remove('hidden');
                liveVehicles = [];
            } else {
                emptyState.classList.add('hidden');
                liveVehicles = data.vehicles;
                renderVehicleCards(data.vehicles);
            }
        }

        // Render Kartu Kendaraan
        function renderVehicleCards(vehicles) {
            const grid = document.getElementById('vehicle-grid');
            
            // Cek apakah item berubah agar tidak render ulang total jika tidak ada perubahan list
            const currentIds = Array.from(grid.querySelectorAll('[data-vehicle-id]')).map(el => parseInt(el.getAttribute('data-vehicle-id')));
            const newIds = vehicles.map(v => v.id);
            const isSameList = currentIds.length === newIds.length && currentIds.every((id, idx) => id === newIds[idx]);

            if (!isSameList) {
                grid.innerHTML = '';
                vehicles.forEach(v => {
                    const card = document.createElement('div');
                    card.setAttribute('data-vehicle-id', v.id);
                    card.setAttribute('data-in-time', v.tap_in_time);
                    card.className = 'glass-card rounded-3xl p-5 border border-white/10 hover:border-[#00719C] transition-all relative overflow-hidden group shadow-lg';
                    const floorNum = parseInt(v.parking_floor) || 1;
                    const floorBadge = `<span class="px-2 py-0.5 rounded-full text-[10px] font-black ${floorNum === 2 ? 'bg-amber-400 text-slate-950' : 'bg-sky-400 text-slate-950'}">LT ${floorNum}</span>`;

                    card.innerHTML = `
                        <!-- Top Accent Line -->
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#00719C] to-[#FFD51E]"></div>

                        <!-- Card Header -->
                        <div class="flex items-center justify-between pb-3 border-b border-white/10">
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 rounded-2xl bg-[#00719C]/20 border border-[#00719C]/40 text-[#00719C] flex items-center justify-center text-lg font-bold group-hover:scale-105 transition-transform">
                                    <i class="fa-solid fa-car"></i>
                                </div>
                                <div>
                                    <div class="text-sm font-extrabold text-white leading-tight">\${escapeHtml(v.user_name)}</div>
                                    <div class="text-[11px] font-mono text-slate-400 mt-0.5">UID: <span class="text-[#FFD51E] font-bold">\${escapeHtml(v.uid)}</span></div>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5">
                                \${floorBadge}
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-[#FFD51E]/20 text-[#FFD51E] border border-[#FFD51E]/30 flex items-center gap-1">
                                    PARKIR
                                </span>
                            </div>
                        </div>

                        <!-- Body: Stopwatch & Fee -->
                        <div class="my-4 grid grid-cols-2 gap-2 text-center">
                            <!-- Live Stopwatch -->
                            <div class="bg-black/40 p-3 rounded-2xl border border-white/5">
                                <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Durasi Parkir</span>
                                <div id="timer-\${v.id}" class="font-mono text-base sm:text-lg font-black text-white tracking-wider">
                                    00:00:00
                                </div>
                                <span class="text-[9px] text-slate-400 block mt-0.5">Jam:Menit:Detik</span>
                            </div>

                            <!-- Live Tariff -->
                            <div class="bg-gradient-to-br from-[#00719C]/20 to-blue-900/40 p-3 rounded-2xl border border-[#00719C]/30">
                                <span class="text-[10px] uppercase font-bold text-blue-200 block mb-1">Estimasi Tarif</span>
                                <div id="fee-\${v.id}" class="font-mono text-base sm:text-lg font-black text-[#FFD51E]">
                                    \${v.fee_formatted}
                                </div>
                                <span id="hours-\${v.id}" class="text-[9px] text-blue-200 block mt-0.5">\${v.billed_hours} Jam Dihitung</span>
                            </div>
                        </div>

                        <!-- Footer: Gate & Jam Masuk -->
                        <div class="pt-3 border-t border-white/10 flex items-center justify-between text-[11px] text-slate-400">
                            <div class="flex items-center gap-1.5 truncate">
                                <i class="fa-solid fa-arrow-right-to-bracket text-[#00719C]"></i>
                                <span class="font-semibold text-slate-300 truncate">\${escapeHtml(v.gate_in)}</span>
                            </div>
                            <div class="font-mono text-slate-400">
                                Masuk: <strong class="text-white">\${v.tap_in_formatted}</strong>
                            </div>
                        </div>
                    `;
                    grid.appendChild(card);
                });
            }

            // Jalankan updater detik stopwatch
            updateAllTimers();
        }

        // Update Stopwatch Tiap Detik
        function updateAllTimers() {
            const now = Date.now();
            liveVehicles.forEach(v => {
                const elTimer = document.getElementById(`timer-\${v.id}`);
                const elFee = document.getElementById(`fee-\${v.id}`);
                const elHours = document.getElementById(`hours-\${v.id}`);
                if (!elTimer) return;

                const inTime = new Date(v.tap_in_time).getTime();
                const diffMs = Math.max(0, now - inTime);
                const totalSeconds = Math.floor(diffMs / 1000);

                const hours = Math.floor(totalSeconds / 3600);
                const minutes = Math.floor((totalSeconds % 3600) / 60);
                const seconds = totalSeconds % 60;

                const pad = n => String(n).padStart(2, '0');
                elTimer.innerText = `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;

                // Update perhitungan tarif live jika berganti jam
                const durationMinutes = Math.max(1, Math.ceil(totalSeconds / 60));
                const billedHours = Math.ceil(durationMinutes / 60);
                const currentFee = billedHours * 2000;
                
                if (elFee) elFee.innerText = 'Rp ' + currentFee.toLocaleString('id-ID');
                if (elHours) elHours.innerText = `${billedHours} Jam Dihitung`;
            });
        }

        if (!timerTickInterval) {
            timerTickInterval = setInterval(updateAllTimers, 1000);
        }

        // Polling setiap 1.5 detik ke server
        setInterval(fetchParkingStatus, 1500);
        fetchParkingStatus();

        // Helper Fullscreen
        function toggleFullScreen() {
            const icon = document.getElementById('fullscreen-icon');
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
                icon.className = 'fa-solid fa-compress text-xs';
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                    icon.className = 'fa-solid fa-expand text-xs';
                }
            }
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.innerText = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
