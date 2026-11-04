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
    <!-- Local FontAwesome & CSS Engine -->
    <link rel="stylesheet" href="{{ asset('assets/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <style>
        :root {
            --c-white: #FFFFFF;
            --c-blue: #00719C;
            --c-blue-dark: #005677;
            --c-yellow: #FFD51E;
            --c-black: #000000;
        }
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: #F8FAFC;
            color: #000000;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }
        .clean-border {
            border: 1px solid rgba(0, 0, 0, 0.08);
        }
        .clean-border-b {
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
        }
        .clean-border-t {
            border-top: 1px solid rgba(0, 0, 0, 0.08);
        }
        .editorial-card {
            background-color: #FFFFFF;
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 1rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }
        .icon-box {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: #00719C;
            color: #FFFFFF;
            border-radius: 0.5rem;
            flex-shrink: 0;
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
<body class="antialiased flex justify-center text-black">

    <!-- Container Smartphone Mobile-First (Maks 500px) -->
    <div class="w-full max-w-lg min-h-screen flex flex-col justify-between px-4 py-5 sm:py-6">

        <!-- Top Header & WiFi Info -->
        <header class="space-y-3">
            
            <!-- Top bar -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <!-- Icon Box E-Parkir Blue -->
                    <div class="icon-box w-10 h-10 text-base" style="border: 1.5px solid #005677;">
                        <i class="fa-solid fa-square-parking"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-base font-bold tracking-tight text-black">E-Parkir</span>
                            <span class="text-[9px] uppercase font-black px-1.5 py-0.5 rounded bg-[#FFD51E] text-black border border-black/10">IoT</span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-[#00719C]/10 text-[#00719C]">CLIENT</span>
                        </div>
                        <p class="text-[11px] text-black/60 font-medium">Smart Barrier Gate &amp; Live Billing</p>
                    </div>
                </div>
            </div>

            <!-- User Identity & Logout Bar -->
            <div class="editorial-card p-3.5 space-y-2.5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="icon-box w-8 h-8 font-bold text-xs" style="border: 1.5px solid #005677;">
                            <i class="fa-solid fa-user text-xs"></i>
                        </div>
                        <div>
                            <div class="text-xs font-bold text-black">{{ $currentUser->name ?? 'Pengendara' }}</div>
                            <div class="text-[11px] text-black/60">{{ $currentUser->email ?? '' }}</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] bg-[#FFD51E] text-black border border-black/10 px-2 py-0.5 rounded-full font-bold uppercase">
                            {{ ($currentUser->role === 'admin') ? 'ADMIN' : 'PENGENDARA' }}
                        </span>
                        <form method="POST" action="{{ route('logout') }}" id="client-logout-form" class="inline">
                            @csrf
                            <button type="submit" title="Keluar / Logout" class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-md text-xs font-bold clean-border transition flex items-center gap-1 cursor-pointer border-none">
                                <i class="fa-solid fa-right-from-bracket text-[10px]"></i>
                                <span>Keluar</span>
                            </button>
                        </form>
                    </div>
                </div>

                @if ($currentUser->role === 'admin')
                <div class="pt-2 clean-border-t flex items-center gap-2">
                    <span class="text-[11px] font-bold text-black/60 uppercase">Mode Admin:</span>
                    <select id="user-select" onchange="switchUser(this.value)" class="flex-1 bg-white clean-border rounded-md px-2.5 py-1.5 text-xs font-semibold text-black focus:outline-none focus:border-[#00719C]">
                        @foreach ($clients as $cl)
                            <option value="{{ $cl['id'] }}" {{ ($cl['id'] == $defaultUserId) ? 'selected' : '' }}>
                                {{ $cl['name'] }} (UID: {{ $cl['uid'] ?? '-' }})
                            </option>
                        @endforeach
                    </select>
                    <button onclick="refreshData(true)" title="Sinkronkan data" class="px-2.5 py-1.5 bg-[#00719C] hover:bg-[#005677] rounded-md text-white transition active:scale-95 text-xs font-bold cursor-pointer">
                        <i class="fa-solid fa-rotate"></i>
                    </button>
                </div>
                @else
                    <input type="hidden" id="user-select" value="{{ $currentUser->id }}">
                @endif
            </div>
        </header>

        <!-- Main Body: Active Status or Idle Status -->
        <main class="my-4 space-y-4">

            <!-- KARTU SALDO PENGENDARA (E-MONEY RFID BALANCE CARD) -->
            <div id="balance-card" class="editorial-card p-4 bg-white relative overflow-hidden shadow-sm">
                <div class="flex items-center justify-between pb-3 clean-border-b">
                    <div class="flex items-center gap-2.5">
                        <div class="icon-box w-10 h-10 text-base" style="border: 1.5px solid #005677; background-color: #00719C;">
                            <i class="fa-solid fa-wallet text-white"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs font-bold uppercase tracking-wider text-black">Saldo Kartu Pengendara</span>
                                <span id="card-status-badge" class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800">Aktif</span>
                            </div>
                            <div class="text-[11px] text-black/60 font-mono mt-0.5" id="user-card-uid-sub">UID: -</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button type="button" onclick="openPayTicketModal()" class="px-2.5 py-1.5 rounded-lg bg-[#00719C] hover:bg-[#005677] text-white text-xs font-bold transition active:scale-95 flex items-center gap-1 shadow-sm cursor-pointer border-none" title="Scan / Bayar Tiket Karcis via Saldo">
                            <i class="fa-solid fa-qrcode text-[11px]"></i>
                            <span>Bayar Karcis</span>
                        </button>
                        <button type="button" onclick="openTopupModal()" class="px-2.5 py-1.5 rounded-lg bg-[#FFD51E] hover:bg-[#ffe054] text-black text-xs font-bold border border-black/10 transition active:scale-95 flex items-center gap-1 shadow-sm cursor-pointer">
                            <i class="fa-solid fa-plus text-[10px]"></i>
                            <span>Isi Saldo</span>
                        </button>
                    </div>
                </div>

                <div class="pt-3 flex items-baseline justify-between">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-black/50 block">Saldo Saat Ini</span>
                        <div id="user-balance-display" class="font-mono text-2xl sm:text-3xl font-black text-black tracking-tight">
                            Rp 0
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] text-black/50 block font-medium">Batas Minimal Masuk</span>
                        <span class="font-mono text-xs font-bold text-black bg-slate-100 px-2 py-0.5 rounded clean-border inline-block mt-0.5">Rp 2.000</span>
                    </div>
                </div>

                <!-- Banner Informasi Keterangan Potong Saldo -->
                <div class="mt-3 pt-2.5 clean-border-t flex items-center justify-between text-[11px] text-black/60">
                    <span class="flex items-center gap-1.5">
                        <i class="fa-solid fa-circle-info text-[#00719C]"></i>
                        <span>Berkurang otomatis saat tap keluar &rarr; Masuk ke Admin</span>
                    </span>
                    <span class="text-emerald-700 font-bold text-[10px] bg-emerald-50 px-2 py-0.5 rounded">Real-Time</span>
                </div>
            </div>

            <!-- KETERSEDIAAN SLOT PARKIR REALTIME (SLOT AVAILABILITY HERO CARD) -->
            <div id="slot-availability-card" class="editorial-card p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div id="slot-status-iconbox" class="icon-box w-11 h-11 text-lg" style="border: 1.5px solid #005677;">
                            <i id="slot-icon" class="fa-solid fa-square-parking"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-black/60">Ketersediaan Slot</span>
                                <span id="slot-badge-status" class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $availableInitSlots > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                    {{ $availableInitSlots > 0 ? 'Tersedia' : 'Penuh' }}
                                </span>
                            </div>
                            <div class="text-xs font-medium text-black/60 mt-0.5" id="slot-subtext">
                                Kapasitas maksimal {{ $totalCapacity }} slot (LT 1 &amp; LT 2)
                            </div>
                        </div>
                    </div>

                    <!-- Angka Slot Tersisa -->
                    <div class="text-right pl-3 clean-border-l">
                        <div class="text-[10px] font-bold uppercase text-black/50">Total Tersisa</div>
                        <div class="flex items-baseline justify-end gap-1">
                            <span id="slot-available-count" class="font-mono text-2xl sm:text-3xl font-extrabold {{ $availableInitSlots > 0 ? 'text-[#00719C]' : 'text-rose-600' }}">
                                {{ $availableInitSlots }}
                            </span>
                            <span class="text-[11px] font-bold text-black/40">/ {{ $totalCapacity }}</span>
                        </div>
                    </div>
                </div>

                <!-- Breakdown Per Lantai: LT 1 & LT 2 -->
                <div class="grid grid-cols-2 gap-2 pt-2 clean-border-t">
                    <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full {{ ($floor1Available ?? 0) > 0 ? 'bg-emerald-500' : 'bg-rose-500' }}" id="lt1-indicator"></span>
                            <div>
                                <div class="text-[11px] font-bold text-slate-800 flex items-center gap-1.5">
                                    <span>Lantai 1</span>
                                    <span class="text-[9px] px-1.5 py-0.2 rounded font-black bg-sky-100 text-[#00719C]">LT 1</span>
                                </div>
                                <div class="text-[10px] text-slate-500" id="lt1-status-text">{{ ($floor1Available ?? 0) > 0 ? 'Sisa ' . ($floor1Available ?? 0) . ' slot' : 'Penuh' }}</div>
                            </div>
                        </div>
                        <div class="text-right font-mono text-xs font-bold text-slate-700">
                            <span id="lt1-parked-count">{{ $floor1Parked ?? 0 }}</span>/10
                        </div>
                    </div>

                    <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full {{ ($floor2Available ?? 0) > 0 ? 'bg-emerald-500' : 'bg-rose-500' }}" id="lt2-indicator"></span>
                            <div>
                                <div class="text-[11px] font-bold text-slate-800 flex items-center gap-1.5">
                                    <span>Lantai 2</span>
                                    <span class="text-[9px] px-1.5 py-0.2 rounded font-black bg-amber-100 text-amber-800">LT 2</span>
                                </div>
                                <div class="text-[10px] text-slate-500" id="lt2-status-text">{{ ($floor2Available ?? 0) > 0 ? 'Sisa ' . ($floor2Available ?? 0) . ' slot' : 'Penuh' }}</div>
                            </div>
                        </div>
                        <div class="text-right font-mono text-xs font-bold text-slate-700">
                            <span id="lt2-parked-count">{{ $floor2Parked ?? 0 }}</span>/10
                        </div>
                    </div>
                </div>
            </div>

            <!-- CARD UTAMA: STATUS PARKIR REALTIME -->
            <div id="main-status-card" class="editorial-card p-5 transition-all duration-300 relative overflow-hidden">
                <!-- Loading State placeholder -->
                <div id="loading-spinner" class="py-12 flex flex-col items-center justify-center text-center">
                    <div class="w-8 h-8 border-3 border-[#00719C] border-t-[#FFD51E] rounded-full animate-spin"></div>
                    <p class="text-xs text-black/60 mt-3 font-medium">Menghubungkan ke E-Parkir Server...</p>
                </div>

                <!-- STATE 1: SEDANG PARKIR (PARKED) -->
                <div id="state-parked" class="hidden space-y-4">
                    <!-- Top Status Badge -->
                    <div class="flex items-center justify-between pb-3 clean-border-b">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 pulse-live"></span>
                            <span class="text-xs font-bold tracking-wide uppercase text-black">KENDARAAN PARKIR AKTIF</span>
                        </div>
                        <span id="parked-card-uid" class="font-mono text-xs px-2.5 py-1 rounded-md bg-[#00719C]/10 text-[#00719C] font-bold clean-border">UID: -</span>
                    </div>

                    <!-- Live Duration Display (Editorial Clean) -->
                    <div class="text-center py-4 bg-slate-50 rounded-xl clean-border">
                        <p class="text-[11px] font-bold tracking-wider uppercase text-black/60 mb-1">DURASI PARKIR BERJALAN</p>
                        <div id="live-timer" class="font-mono text-3xl sm:text-4xl font-extrabold text-black tracking-wider">
                            00 : 00 : 00
                        </div>
                        <p class="text-[10px] text-black/50 mt-1">Format: Jam : Menit : Detik</p>
                    </div>

                    <!-- Estimated Tariff Ticker (Primary Blue Card) -->
                    <div class="rounded-xl p-4 text-white shadow-sm" style="background-color: #00719C; border: 1.5px solid #005677;">
                        <div class="flex items-center justify-between text-xs font-semibold mb-1">
                            <span class="text-white/90">Estimasi Tagihan</span>
                            <span class="px-2 py-0.5 rounded bg-[#FFD51E] text-black font-bold text-[10px]">Rp 2.000 / Jam</span>
                        </div>
                        <div class="flex items-baseline justify-between mt-2">
                            <div id="current-fee-display" class="text-3xl font-black text-white font-mono">
                                Rp 0
                            </div>
                            <div class="text-right">
                                <span id="billed-hours-display" class="text-xs font-bold text-white bg-black/20 px-2.5 py-1 rounded-md">1 Jam Dihitung</span>
                            </div>
                        </div>

                        <!-- Progress to next hour -->
                        <div class="mt-3 pt-3" style="border-top: 1px solid rgba(255, 255, 255, 0.2);">
                            <div class="flex justify-between text-[11px] text-white/90 mb-1.5 font-medium">
                                <span>Menuju jam berikutnya:</span>
                                <span id="next-hour-countdown" class="font-mono font-bold text-[#FFD51E]">-- mnt tersisa</span>
                            </div>
                            <div class="w-full bg-black/20 rounded-full h-2 overflow-hidden">
                                <div id="hourly-progress-bar" class="bg-[#FFD51E] h-2 rounded-full transition-all duration-300" style="width: 20%;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Live Balance Calculation Box saat parkir -->
                    <div class="p-3.5 bg-slate-50 rounded-xl clean-border text-xs space-y-2">
                        <div class="flex items-center justify-between text-black/70 font-medium">
                            <span class="flex items-center gap-1.5">
                                <i class="fa-solid fa-wallet text-[#00719C]"></i>
                                <span>Saldo Kartu Anda:</span>
                            </span>
                            <span id="parked-current-balance" class="font-mono font-bold text-black text-sm">Rp 0</span>
                        </div>
                        <div class="flex items-center justify-between text-black/70 font-medium">
                            <span class="flex items-center gap-1.5">
                                <i class="fa-solid fa-receipt text-rose-600"></i>
                                <span>Estimasi Tarif Parkir:</span>
                            </span>
                            <span id="parked-estimated-fee" class="font-mono font-bold text-rose-600 text-sm">- Rp 0</span>
                        </div>
                        <div class="pt-2 clean-border-t flex items-center justify-between font-bold">
                            <span class="text-black flex items-center gap-1.5">
                                <i class="fa-solid fa-coins text-amber-500"></i>
                                <span>Estimasi Sisa Saldo:</span>
                            </span>
                            <span id="parked-remaining-balance" class="font-mono text-emerald-700 text-sm font-extrabold">Rp 0</span>
                        </div>
                        <div id="insufficient-balance-warning" class="hidden mt-2 p-2.5 rounded-lg bg-rose-50 clean-border border-rose-300 text-rose-700 text-xs font-semibold flex items-center gap-2">
                            <i class="fa-solid fa-triangle-exclamation text-rose-600 text-sm flex-shrink-0"></i>
                            <div class="flex-1">
                                <div>Saldo Kurang untuk Keluar!</div>
                                <span class="text-[10px] text-rose-600 font-normal">Palang tidak akan terbuka saat tap-out. Segera isi saldo kartu Anda.</span>
                            </div>
                            <button type="button" onclick="openTopupModal()" class="px-2 py-1 rounded bg-rose-600 text-white text-[10px] font-bold cursor-pointer">Top Up</button>
                        </div>
                    </div>

                    <!-- Gate In & Tap Time Details -->
                    <div class="grid grid-cols-3 gap-2 text-xs">
                        <div class="bg-slate-50 p-2.5 rounded-xl clean-border">
                            <span class="text-black/50 block text-[9px] uppercase font-bold">Lantai Parkir</span>
                            <span id="parked-floor-badge" class="inline-block mt-0.5 px-2 py-0.5 rounded text-[11px] font-black bg-sky-100 text-[#00719C]">Lantai 1 (LT 1)</span>
                        </div>
                        <div class="bg-slate-50 p-2.5 rounded-xl clean-border">
                            <span class="text-black/50 block text-[9px] uppercase font-bold">Palang Masuk</span>
                            <span id="gate-in-name" class="font-bold text-black text-xs mt-0.5 block truncate">Gate-01</span>
                            <span id="gate-in-loc" class="text-[9px] text-black/50 block truncate">Pintu Utama</span>
                        </div>
                        <div class="bg-slate-50 p-2.5 rounded-xl clean-border">
                            <span class="text-black/50 block text-[9px] uppercase font-bold">Waktu Masuk</span>
                            <span id="tap-in-time-display" class="font-bold text-black text-xs mt-0.5 block font-mono">--:--:--</span>
                            <span class="text-[9px] text-emerald-600 block font-semibold">✓ Tercatat</span>
                        </div>
                    </div>
                </div>

                <!-- STATE 2: BELUM PARKIR / SIAP TAP-IN (NOT PARKED) -->
                <div id="state-idle" class="hidden space-y-4">
                    <!-- Status Badge -->
                    <div class="flex items-center justify-between pb-3 clean-border-b">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            <span class="text-xs font-bold tracking-wide uppercase text-black">STATUS: TIDAK DALAM PARKIR</span>
                        </div>
                        <span id="idle-card-uid" class="font-mono text-xs px-2.5 py-1 rounded-md bg-black/5 text-black/70 clean-border font-bold">UID: -</span>
                    </div>

                    <!-- Saldo Status info box -->
                    <div id="idle-balance-alert" class="p-3 rounded-xl clean-border text-xs flex items-center justify-between bg-emerald-50 border-emerald-200">
                        <div class="flex items-center gap-2">
                            <i id="idle-balance-icon" class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
                            <span id="idle-balance-text" class="text-emerald-900 font-medium">Saldo mencukupi untuk tap masuk (Min. Rp 2.000).</span>
                        </div>
                        <span id="idle-balance-val" class="font-mono font-bold text-emerald-800 text-xs">Rp 0</span>
                    </div>

                    <!-- Welcome Box -->
                    <div class="text-center py-6 px-4 bg-slate-50 rounded-xl clean-border space-y-2">
                        <div class="icon-box w-12 h-12 text-lg mx-auto mb-2" style="border: 1.5px solid #005677;">
                            <i class="fa-solid fa-credit-card"></i>
                        </div>
                        <h2 class="text-base font-bold text-black">Kartu RFID Siap Digunakan</h2>
                        <p class="text-xs text-black/60 max-w-xs mx-auto leading-relaxed">
                            Tempelkan kartu RFID Anda ke sensor scanner gerbang (<strong class="text-black font-bold">Gate-01</strong>) untuk membuka palang parkir secara otomatis.
                        </p>
                    </div>

                    <!-- Rate Card -->
                    <div class="p-3.5 rounded-xl bg-white clean-border flex items-center justify-between text-xs">
                        <div class="flex items-center gap-3">
                            <div class="icon-box w-8 h-8 font-bold text-xs" style="border: 1.5px solid #005677;">
                                <i class="fa-solid fa-tags"></i>
                            </div>
                            <div>
                                <span class="text-black/50 block text-[10px] font-bold uppercase">Tarif Flat Parkir</span>
                                <span class="font-bold text-black text-sm">Rp 2.000 <span class="text-black/50 text-xs font-normal">/ Jam</span></span>
                            </div>
                        </div>
                        <span class="text-[10px] text-black font-bold bg-[#FFD51E] px-2.5 py-1 rounded-md border border-black/10">Skema Bulat Atas</span>
                    </div>
                </div>
            </div>

            <!-- RIWAYAT PARKIR TERAKHIR -->
            <div class="editorial-card p-4 space-y-3">
                <div class="flex items-center justify-between pb-2.5 clean-border-b">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-black flex items-center gap-2">
                        <div class="icon-box w-5 h-5 text-[10px]" style="border: 1px solid #005677;">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </div>
                        <span>Riwayat Parkir Terakhir</span>
                    </h3>
                    <span id="trip-count-badge" class="text-[10px] bg-black/5 px-2.5 py-0.5 rounded-full text-black/70 font-bold clean-border">0 Sesi</span>
                </div>

                <!-- History List -->
                <div id="history-container" class="space-y-2">
                    <div class="text-center py-6 text-black/50 text-xs">Memuat riwayat transaksi...</div>
                </div>

                <!-- Total Stat Summary -->
                <div class="pt-3 clean-border-t flex items-center justify-between text-xs text-black/70">
                    <span class="font-semibold">Total Pengeluaran:</span>
                    <strong id="total-spent-display" class="text-black font-mono text-sm font-bold">Rp 0</strong>
                </div>
            </div>

        </main>

        <!-- Footer -->
        <footer class="pt-4 text-center clean-border-t text-[11px] text-black/50">
            <p class="font-medium">Sistem Palang Pintu &amp; Tarif Parkir Otomatis Berbasis IoT</p>
            <p class="text-black/40 mt-0.5">Kelompok Sigma Boys &copy; {{ date('Y') }}</p>
        </footer>

    </div>

    <!-- Notification Toast -->
    <div id="toast" class="fixed top-5 left-1/2 -translate-x-1/2 z-50 px-4 py-2.5 rounded-xl bg-white clean-border text-black text-xs font-bold shadow-xl transition-all duration-300 opacity-0 pointer-events-none flex items-center gap-2">
        <span id="toast-icon">⚡</span>
        <span id="toast-msg">Notifikasi</span>
    </div>

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
            const toast = document.getElementById('toast');
            document.getElementById('toast-msg').innerText = msg;
            document.getElementById('toast-icon').innerText = icon;
            toast.className = `fixed top-5 left-1/2 -translate-x-1/2 z-50 px-4 py-2.5 rounded-xl bg-white clean-border ${isSuccess ? 'text-black' : 'text-rose-600'} text-xs font-bold shadow-xl transition-all duration-300 opacity-100 flex items-center gap-2`;
            playChime(isSuccess);
            setTimeout(() => {
                toast.classList.replace('opacity-100', 'opacity-0');
            }, 3500);
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
                    document.getElementById('loading-spinner').classList.add('hidden');
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
                        countEl.className = 'font-mono text-2xl sm:text-3xl font-extrabold text-rose-600';
                    } else if (slots.available_slots <= 2) {
                        countEl.className = 'font-mono text-2xl sm:text-3xl font-extrabold text-amber-600';
                    } else {
                        countEl.className = 'font-mono text-2xl sm:text-3xl font-extrabold text-[#00719C]';
                    }
                }

                if (badgeEl) {
                    if (slots.available_slots <= 0) {
                        badgeEl.innerText = 'Penuh';
                        badgeEl.className = 'text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-100 text-rose-800';
                    } else if (slots.available_slots <= 2) {
                        badgeEl.innerText = 'Sisa Sedikit';
                        badgeEl.className = 'text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800';
                    } else {
                        badgeEl.innerText = 'Tersedia';
                        badgeEl.className = 'text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800';
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
                    if (lt1IndEl) lt1IndEl.className = `w-2 h-2 rounded-full ${lt1Avail > 0 ? 'bg-emerald-500' : 'bg-rose-500'}`;

                    if (lt2ParkedEl) lt2ParkedEl.innerText = lt2Parked;
                    if (lt2StatusEl) lt2StatusEl.innerText = lt2Avail > 0 ? `Sisa ${lt2Avail} slot` : 'Penuh';
                    if (lt2IndEl) lt2IndEl.className = `w-2 h-2 rounded-full ${lt2Avail > 0 ? 'bg-emerald-500' : 'bg-rose-500'}`;
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
                document.getElementById('state-parked').classList.add('hidden');
                document.getElementById('state-idle').classList.remove('hidden');
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
                    badgeStatus.className = 'text-[9px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800';
                } else {
                    badgeStatus.innerText = 'Diblokir';
                    badgeStatus.className = 'text-[9px] font-bold px-2 py-0.5 rounded-full bg-rose-100 text-rose-800';
                }
            }

            // User info & stats
            document.getElementById('trip-count-badge').innerText = `${data.stats.total_trips} Sesi`;
            document.getElementById('total-spent-display').innerText = data.stats.total_spent_formatted;

            if (isParked) {
                // Show PARKED state
                document.getElementById('state-idle').classList.add('hidden');
                document.getElementById('state-parked').classList.remove('hidden');

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
                        ? 'inline-block mt-0.5 px-2 py-0.5 rounded text-[11px] font-black bg-amber-100 text-amber-800' 
                        : 'inline-block mt-0.5 px-2 py-0.5 rounded text-[11px] font-black bg-sky-100 text-[#00719C]';
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

                document.getElementById('state-parked').classList.add('hidden');
                document.getElementById('state-idle').classList.remove('hidden');
                document.getElementById('idle-card-uid').innerText = `UID: ${currentUid || 'Tidak Ada Kartu'}`;

                // Update info kecukupan saldo di IDLE state
                const alertEl = document.getElementById('idle-balance-alert');
                const alertIcon = document.getElementById('idle-balance-icon');
                const alertText = document.getElementById('idle-balance-text');
                const alertVal = document.getElementById('idle-balance-val');
                if (alertVal) alertVal.innerText = `Rp ${currentBalance.toLocaleString('id-ID')}`;

                if (currentBalance >= 2000) {
                    if (alertEl) alertEl.className = 'p-3 rounded-xl clean-border text-xs flex items-center justify-between bg-emerald-50 border-emerald-200';
                    if (alertIcon) alertIcon.className = 'fa-solid fa-circle-check text-emerald-600 text-sm';
                    if (alertText) alertText.innerText = 'Saldo Anda mencukupi untuk tap masuk (Min. Rp 2.000).';
                } else {
                    if (alertEl) alertEl.className = 'p-3 rounded-xl clean-border text-xs flex items-center justify-between bg-rose-50 border-rose-200';
                    if (alertIcon) alertIcon.className = 'fa-solid fa-triangle-exclamation text-rose-600 text-sm';
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
                    pRemBal.className = 'font-mono text-emerald-700 text-sm font-extrabold';
                    if (pWarn) pWarn.classList.add('hidden');
                } else {
                    pRemBal.innerText = `- Rp ${Math.abs(remaining).toLocaleString('id-ID')} (Kurang)`;
                    pRemBal.className = 'font-mono text-rose-600 text-sm font-extrabold';
                    if (pWarn) pWarn.classList.remove('hidden');
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
                container.innerHTML = `<div class="text-center py-5 text-black/50 text-xs">Belum ada riwayat parkir yang selesai.</div>`;
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
                    ? '<span class="text-[9px] px-1.5 py-0.2 rounded font-black bg-amber-100 text-amber-800">LT 2</span>'
                    : '<span class="text-[9px] px-1.5 py-0.2 rounded font-black bg-sky-100 text-[#00719C]">LT 1</span>';
                const balAfter = item.balance_after !== null && item.balance_after !== undefined 
                    ? `<span class="block text-[10px] text-black/50 font-mono">Sisa Saldo: Rp ${parseInt(item.balance_after).toLocaleString('id-ID')}</span>` 
                    : '';

                html += `
                    <div class="p-3 rounded-xl bg-slate-50 clean-border flex items-center justify-between hover:bg-slate-100 transition">
                        <div class="space-y-0.5">
                            <div class="flex items-center gap-1.5">
                                <span class="font-bold text-black text-xs">${dateStr}</span>
                                ${floorBadge}
                                <span class="text-[10px] text-black/60 font-mono">(${timeInStr} - ${timeOutStr})</span>
                            </div>
                            <div class="text-[11px] text-black/60">
                                <span>Durasi: <strong class="text-black">${item.duration_minutes} mnt</strong></span>
                                <span class="mx-1">•</span>
                                <span>${item.gate_in_code || 'Gate-01'} &rarr; ${item.gate_out_code || 'Gate-01'}</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="font-mono font-bold text-xs text-rose-600">- Rp ${fee.toLocaleString('id-ID')}</span>
                            ${balAfter}
                            <span class="inline-block text-[9px] text-emerald-700 bg-emerald-100 px-1.5 py-0.2 rounded uppercase font-bold mt-0.5">Lunas (Admin)</span>
                            <div class="mt-1">
                                <a href="{{ url('receipt') }}/${item.id}" target="_blank" class="inline-flex items-center gap-1 text-[10px] font-bold text-[#00719C] hover:text-[#005a7d] hover:underline">
                                    <i class="fa-solid fa-receipt text-[9px]"></i> Struk 58mm
                                </a>
                            </div>
                        </div>
                    </div>
                `;
            });
            container.innerHTML = html;
        }

        // ================= TOP UP SALDO MODAL LOGIC =================
        function openTopupModal() {
            document.getElementById('modal-current-balance').innerText = `Rp ${currentBalance.toLocaleString('id-ID')}`;
            document.getElementById('topup-amount-input').value = '';
            const modal = document.getElementById('topup-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeTopupModal() {
            const modal = document.getElementById('topup-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
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
            btn.innerHTML = `<i class="fa-solid fa-spinner animate-spin"></i><span>Memproses...</span>`;
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

        // ================= BAYAR TIKET KARCIS VIA SALDO MEMBER =================
        function openPayTicketModal(initialCode = '') {
            const modal = document.getElementById('pay-ticket-modal');
            const input = document.getElementById('pay-ticket-input');
            document.getElementById('pay-modal-balance').innerText = `Rp ${currentBalance.toLocaleString('id-ID')}`;
            document.getElementById('ticket-preview-box').classList.add('hidden');
            document.getElementById('btn-confirm-pay-ticket').classList.add('hidden');
            
            if (initialCode) {
                input.value = initialCode;
                checkTicketCode();
            } else {
                input.value = '';
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => input.focus(), 100);
        }

        function closePayTicketModal() {
            const modal = document.getElementById('pay-ticket-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
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
            btn.innerHTML = `<i class="fa-solid fa-spinner animate-spin"></i>`;
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
                        warnEl.classList.add('hidden');
                        payBtn.classList.remove('hidden');
                        payBtn.disabled = false;
                    } else {
                        const kurang = result.data.total_fee - currentBalance;
                        warnEl.innerText = `Saldo Anda tidak mencukupi (Kurang Rp ${kurang.toLocaleString('id-ID')}). Silakan isi saldo terlebih dahulu!`;
                        warnEl.classList.remove('hidden');
                        payBtn.classList.add('hidden');
                    }

                    document.getElementById('ticket-preview-box').classList.remove('hidden');
                } else {
                    document.getElementById('ticket-preview-box').classList.add('hidden');
                    document.getElementById('btn-confirm-pay-ticket').classList.add('hidden');
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
            btn.innerHTML = `<i class="fa-solid fa-spinner animate-spin"></i><span>Memproses Pembayaran...</span>`;
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

    <!-- Modal 1: Top Up Saldo Pengendara -->
    <div id="topup-modal" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl clean-border p-5 max-w-sm w-full space-y-4 shadow-2xl">
            <div class="flex items-center justify-between pb-3 clean-border-b">
                <div class="flex items-center gap-2">
                    <div class="icon-box w-8 h-8 text-sm" style="background-color: #00719C;">
                        <i class="fa-solid fa-wallet text-white"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-black">Isi Ulang Saldo</h3>
                        <p class="text-[11px] text-black/60">Top Up Kartu Pengendara RFID</p>
                    </div>
                </div>
                <button type="button" onclick="closeTopupModal()" class="w-7 h-7 rounded-full bg-slate-100 hover:bg-slate-200 text-black flex items-center justify-center text-xs font-bold cursor-pointer border-none">&times;</button>
            </div>

            <!-- Saldo Saat Ini -->
            <div class="p-3 rounded-xl bg-slate-50 clean-border flex items-center justify-between text-xs">
                <span class="text-black/60 font-medium">Saldo Saat Ini:</span>
                <span id="modal-current-balance" class="font-mono font-bold text-black">Rp 0</span>
            </div>

            <!-- Pilihan Cepat Nominal -->
            <div class="space-y-1.5">
                <label class="text-[11px] font-bold uppercase text-black/60">Pilih Nominal Top Up Cepat</label>
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" onclick="selectTopupAmount(10000)" class="topup-quick-btn py-2 px-3 rounded-lg clean-border text-xs font-bold text-black hover:border-[#00719C] hover:bg-[#00719C]/5 active:scale-95 transition text-center cursor-pointer bg-white">+Rp 10.000</button>
                    <button type="button" onclick="selectTopupAmount(20000)" class="topup-quick-btn py-2 px-3 rounded-lg clean-border text-xs font-bold text-black hover:border-[#00719C] hover:bg-[#00719C]/5 active:scale-95 transition text-center cursor-pointer bg-white">+Rp 20.000</button>
                    <button type="button" onclick="selectTopupAmount(50000)" class="topup-quick-btn py-2 px-3 rounded-lg clean-border text-xs font-bold text-black hover:border-[#00719C] hover:bg-[#00719C]/5 active:scale-95 transition text-center cursor-pointer bg-white">+Rp 50.000</button>
                    <button type="button" onclick="selectTopupAmount(100000)" class="topup-quick-btn py-2 px-3 rounded-lg clean-border text-xs font-bold text-black hover:border-[#00719C] hover:bg-[#00719C]/5 active:scale-95 transition text-center cursor-pointer bg-white">+Rp 100.000</button>
                </div>
            </div>

            <!-- Input Kustom Nominal -->
            <div class="space-y-1">
                <label class="text-[11px] font-bold uppercase text-black/60">Atau Masukkan Nominal Sendiri</label>
                <div class="relative">
                    <span class="absolute left-3 top-2.5 text-xs font-bold text-black/50">Rp</span>
                    <input type="number" id="topup-amount-input" min="1000" step="1000" placeholder="Contoh: 25000" class="w-full pl-9 pr-3 py-2 text-xs font-mono font-bold rounded-lg clean-border focus:outline-none focus:border-[#00719C]">
                </div>
            </div>

            <div class="pt-2 flex items-center gap-2">
                <button type="button" onclick="closeTopupModal()" class="flex-1 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-black text-xs font-bold transition cursor-pointer border-none">Batal</button>
                <button type="button" id="btn-submit-topup" onclick="executeTopup()" class="flex-1 py-2 rounded-lg bg-[#00719C] hover:bg-[#005677] text-white text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-sm cursor-pointer border-none">
                    <span>Isi Sekarang</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal 2: Bayar Karcis Tiket Menggunakan Saldo Member (Scan QR) -->
    <div id="pay-ticket-modal" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl clean-border p-5 max-w-sm w-full space-y-4 shadow-2xl">
            <div class="flex items-center justify-between pb-3 clean-border-b">
                <div class="flex items-center gap-2">
                    <div class="icon-box w-8 h-8 text-sm" style="background-color: #00719C;">
                        <i class="fa-solid fa-qrcode text-white"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-black">Bayar Karcis Parkir</h3>
                        <p class="text-[11px] text-black/60">Gunakan Saldo Kartu RFID Member</p>
                    </div>
                </div>
                <button type="button" onclick="closePayTicketModal()" class="w-7 h-7 rounded-full bg-slate-100 hover:bg-slate-200 text-black flex items-center justify-center text-xs font-bold cursor-pointer border-none">&times;</button>
            </div>

            <!-- Info Saldo Member Saat Ini -->
            <div class="p-3 rounded-xl bg-slate-50 clean-border flex items-center justify-between text-xs">
                <span class="text-black/60 font-medium">Saldo Kartu Anda:</span>
                <span id="pay-modal-balance" class="font-mono font-bold text-black">Rp 0</span>
            </div>

            <!-- Input Kode Tiket Karcis -->
            <div class="space-y-1.5">
                <label class="text-[11px] font-bold uppercase text-black/60">Kode Tiket / Hasil Scan QR Karcis</label>
                <div class="flex gap-2">
                    <input type="text" id="pay-ticket-input" placeholder="Contoh: TKT-261102-1226" class="flex-1 px-3 py-2 text-xs font-mono font-bold rounded-lg clean-border uppercase focus:outline-none focus:border-[#00719C]">
                    <button type="button" id="btn-check-ticket" onclick="checkTicketCode()" class="px-3 py-2 bg-[#00719C] hover:bg-[#005677] text-white text-xs font-bold rounded-lg transition active:scale-95 cursor-pointer border-none">
                        <span>Cek</span>
                    </button>
                </div>
                <p class="text-[10px] text-black/50">Masukkan kode tiket atau scan QR yang tercetak pada karcis masuk.</p>
            </div>

            <!-- Preview Tagihan Tiket -->
            <div id="ticket-preview-box" class="hidden p-3.5 rounded-xl bg-slate-50 clean-border space-y-2 text-xs">
                <div class="flex justify-between items-center text-black/60 pb-1.5 clean-border-b">
                    <span>Kode Tiket</span>
                    <span id="ticket-prev-code" class="font-mono font-bold text-black"></span>
                </div>
                <div class="flex justify-between items-center text-black/60">
                    <span>Waktu Masuk</span>
                    <span id="ticket-prev-in" class="font-mono text-black"></span>
                </div>
                <div class="flex justify-between items-center text-black/60">
                    <span>Durasi Parkir</span>
                    <span id="ticket-prev-dur" class="text-black"></span>
                </div>
                <div class="flex justify-between items-center pt-1.5 clean-border-t">
                    <span class="font-bold text-black">Total Tagihan:</span>
                    <span id="ticket-prev-fee" class="font-mono font-black text-sm text-rose-600"></span>
                </div>
                <div id="ticket-balance-warning" class="hidden text-[11px] text-rose-600 font-bold bg-rose-50 p-2 rounded clean-border mt-1"></div>
            </div>

            <div class="pt-2 flex items-center gap-2">
                <button type="button" onclick="closePayTicketModal()" class="flex-1 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-black text-xs font-bold transition cursor-pointer border-none">Batal</button>
                <button type="button" id="btn-confirm-pay-ticket" onclick="executePayTicket()" class="hidden flex-1 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-sm cursor-pointer border-none">
                    <i class="fa-solid fa-check text-[11px]"></i>
                    <span>Bayar Pakai Saldo</span>
                </button>
            </div>
        </div>
    </div>
</body>
</html>
