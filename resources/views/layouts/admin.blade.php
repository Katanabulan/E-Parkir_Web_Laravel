<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel') - E-Parkir IoT (Sigma Boys)</title>
    <!-- Offline Vanilla CSS Engine -->
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/all.min.css') }}">
    <!-- Chart.js for reports -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: var(--c-white, #FFFFFF);
            color: var(--c-black, #000000);
            margin: 0;
            padding: 0;
        }
        .font-mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }
        .clean-border {
            border: 1px solid rgba(0, 0, 0, 0.10);
        }
        .clean-border-b {
            border-bottom: 1px solid rgba(0, 0, 0, 0.10);
        }
        .clean-border-r {
            border-right: 1px solid rgba(0, 0, 0, 0.10);
        }
        .clean-border-t {
            border-top: 1px solid rgba(0, 0, 0, 0.10);
        }
        /* Sidebar nav item with clean border hover & active (Border Saja, Tanpa BG Putih) */
        .sidebar-nav-item {
            border: 1px solid transparent;
            background-color: transparent !important;
            color: #FFFFFF !important;
            transition: border-color 0.15s ease, opacity 0.15s ease;
            opacity: 0.85;
        }
        .sidebar-nav-item:hover {
            border-color: #FFFFFF !important;
            background-color: transparent !important;
            color: #FFFFFF !important;
            opacity: 1;
        }
        .sidebar-nav-active {
            border-color: #FFFFFF !important;
            background-color: transparent !important;
            color: #FFFFFF !important;
            font-weight: 700 !important;
            opacity: 1;
        }
        /* Sidebar Desktop & Mobile Visibility */
        #sidebar {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        @media (min-width: 1024px) {
            #sidebar {
                transform: translateX(0) !important;
                position: sticky !important;
                top: 0 !important;
                display: flex !important;
                visibility: visible !important;
                opacity: 1 !important;
                left: 0 !important;
            }
        }
        @media (max-width: 1023px) {
            #sidebar.-translate-x-full {
                transform: translateX(-100%) !important;
            }
            #sidebar:not(.-translate-x-full) {
                transform: translateX(0) !important;
            }
        }
        /* Sticky Topbar Header Solid Opaque Background */
        #admin-topbar {
            background-color: #FFFFFF !important;
            background: #FFFFFF !important;
            opacity: 1 !important;
        }
        /* Solid User Dropdown Button with Gray Border */
        .user-pill-btn {
            background-color: #FFFFFF !important;
            border: 1px solid #E2E8F0 !important;
            color: #000000 !important;
            transition: all 0.15s ease;
        }
        .user-pill-btn:hover {
            border-color: #CBD5E1 !important;
            background-color: #F8FAFC !important;
        }
        /* Solid User Dropdown Menu Popover with Gray Border */
        #user-dropdown-menu {
            background-color: #FFFFFF !important;
            background: #FFFFFF !important;
            border: 1px solid #E2E8F0 !important;
            opacity: 1 !important;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08) !important;
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #FFFFFF;
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.20);
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(0, 0, 0, 0.35);
        }
        .print-only {
            display: none !important;
        }
        @media print {
            @page {
                size: landscape;
                margin: 8mm 10mm !important;
            }
            .no-print, #sidebar, #admin-topbar, #sidebar-backdrop, #emergency-modal, footer {
                display: none !important;
            }
            html, body {
                display: block !important;
                background: #FFFFFF !important;
                color: #000000 !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                min-width: 100% !important;
                overflow: visible !important;
                font-size: 8.5pt !important;
            }
            body {
                flex: none !important;
            }
            .flex, .flex-1, .flex-col, .min-h-screen {
                display: block !important;
                flex: none !important;
                width: 100% !important;
                max-width: 100% !important;
                min-height: auto !important;
                height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            main {
                display: block !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                box-sizing: border-box !important;
            }
            .overflow-x-auto, .overflow-y-auto {
                overflow: visible !important;
                display: block !important;
                width: 100% !important;
                max-width: 100% !important;
            }
            .clean-border, .clean-border-b, .clean-border-t, .clean-border-r, .shadow-sm {
                box-shadow: none !important;
                border: none !important;
            }
            .print-only {
                display: block !important;
            }
            .print\:block {
                display: block !important;
            }
            .print\:hidden {
                display: none !important;
            }
            table {
                width: 100% !important;
                max-width: 100% !important;
                table-layout: fixed !important;
                border-collapse: collapse !important;
                page-break-inside: auto !important;
                box-sizing: border-box !important;
            }
            th, td {
                word-wrap: break-word !important;
                overflow-wrap: break-word !important;
                box-sizing: border-box !important;
                padding: 4px 6px !important;
            }
            tr {
                page-break-inside: avoid !important;
                page-break-after: auto !important;
            }
            thead {
                display: table-row-group !important; /* Mencegah header tabel berulang di halaman 2 dan seterusnya */
            }
            tfoot {
                display: table-row-group !important;
            }
        }
    </style>
    @stack('styles')
</head>
<body class="min-h-screen flex antialiased bg-[#FFFFFF] text-black">

    <!-- Mobile Sidebar Backdrop -->
    <div id="sidebar-backdrop" onclick="toggleSidebar()" class="fixed inset-0 bg-black/10 z-40 hidden lg:hidden"></div>

    <!-- SIDEBAR: Royal Blue Canvas (#00719C) with Yellow Icon Badges (#FFD51E) -->
    <aside id="sidebar" class="no-print fixed lg:sticky top-0 left-0 h-screen w-72 flex-shrink-0 overflow-y-auto bg-[#00719C] text-white z-40 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 flex flex-col justify-between select-none border-r border-[#155ce0]">
        <div>
            <!-- Sidebar Header / Logo -->
            <div class="h-20 flex items-center justify-between px-6 border-b border-white/15">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3">
                    <!-- Icon with Blue Background, White Icon & White Border -->
                    <div class="icon-box" style="width: 2rem; height: 2rem; border: 1.5px solid #FFFFFF;">
                        <i class="fa-solid fa-square-parking text-sm"></i>
                    </div>
                    <div>
                        <div class="font-bold text-base tracking-tight text-white flex items-center gap-1.5">
                            <span>E-Parkir</span>
                            <span class="text-[9px] uppercase font-black px-1.5 py-0.5 rounded bg-[#FFD51E] text-black border border-white">IoT</span>
                        </div>
                        <div class="text-[11px] text-white/70 font-medium">Sistem Gerbang &amp; Slot</div>
                    </div>
                </a>
                <button onclick="toggleSidebar()" class="lg:hidden text-white/70 hover:text-white">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>

            <!-- Navigation Links -->
            <nav class="p-4 space-y-1.5">
                <div class="px-3 pt-2 pb-1 text-[10px] font-bold uppercase tracking-widest text-white/60">
                    Menu Utama
                </div>

                <!-- Dashboard & Monitoring -->
                <a href="{{ route('admin.dashboard') }}" 
                   class="sidebar-nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-md text-sm font-semibold transition-all {{ request()->routeIs('admin.dashboard') ? 'sidebar-nav-active' : 'text-white/90' }}">
                    <div class="icon-box" style="width: 1.75rem; height: 1.75rem; border: 1px solid rgba(255, 255, 255, 0.4);">
                        <i class="fa-solid fa-table-columns text-xs text-white"></i>
                    </div>
                    <span>Dashboard &amp; Gate</span>
                </a>

                <!-- Status Slot Parkir -->
                <a href="{{ route('admin.status.index') }}" 
                   class="sidebar-nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-md text-sm font-semibold transition-all {{ request()->routeIs('admin.status.*') ? 'sidebar-nav-active' : 'text-white/90' }}">
                    <div class="icon-box" style="width: 1.75rem; height: 1.75rem; border: 1px solid rgba(255, 255, 255, 0.4);">
                        <i class="fa-solid fa-square-parking text-xs text-white"></i>
                    </div>
                    <span>Status Slot Parkir</span>
                </a>

                <!-- Kelola Kartu RFID -->
                <a href="{{ route('admin.cards.index') }}" 
                   class="sidebar-nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-md text-sm font-semibold transition-all {{ request()->routeIs('admin.cards.*') ? 'sidebar-nav-active' : 'text-white/90' }}">
                    <div class="icon-box" style="width: 1.75rem; height: 1.75rem; border: 1px solid rgba(255, 255, 255, 0.4);">
                        <i class="fa-solid fa-id-card text-xs text-white"></i>
                    </div>
                    <span>Kelola Kartu RFID</span>
                </a>

                <!-- Live Gate Simulator -->
                <a href="{{ route('admin.monitoring') }}" 
                   class="sidebar-nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-md text-sm font-semibold transition-all {{ request()->routeIs('admin.monitoring') ? 'sidebar-nav-active' : 'text-white/90' }}">
                    <div class="icon-box" style="width: 1.75rem; height: 1.75rem; border: 1px solid rgba(255, 255, 255, 0.4);">
                        <i class="fa-solid fa-microchip text-xs text-white"></i>
                    </div>
                    <span>Gate Simulator IoT</span>
                    <span style="background-color: #EF4444 !important;" class="ml-auto text-[9px] font-black px-1.5 py-0.5 rounded bg-[#EF4444] text-white border border-white">Live</span>
                </a>

                <div class="px-3 pt-5 pb-1 text-[10px] font-bold uppercase tracking-widest text-white/60">
                    Laporan & Audit
                </div>

                <!-- Laporan Pendapatan -->
                <a href="{{ route('admin.reports.index') }}" 
                   class="sidebar-nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-md text-sm font-semibold transition-all {{ request()->routeIs('admin.reports.*') ? 'sidebar-nav-active' : 'text-white/90' }}">
                    <div class="icon-box" style="width: 1.75rem; height: 1.75rem; border: 1px solid rgba(255, 255, 255, 0.4);">
                        <i class="fa-solid fa-chart-line text-xs text-white"></i>
                    </div>
                    <span>Laporan Pendapatan</span>
                </a>

                <!-- Log Akses Gerbang -->
                <a href="{{ route('admin.logs.index') }}" 
                   class="sidebar-nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-md text-sm font-semibold transition-all {{ request()->routeIs('admin.logs.*') ? 'sidebar-nav-active' : 'text-white/90' }}">
                    <div class="icon-box" style="width: 1.75rem; height: 1.75rem; border: 1px solid rgba(255, 255, 255, 0.4);">
                        <i class="fa-solid fa-clock-rotate-left text-xs text-white"></i>
                    </div>
                    <span>Audit Log Akses</span>
                </a>

                <div class="px-3 pt-5 pb-1 text-[10px] font-bold uppercase tracking-widest text-white/60">
                    Data Master
                </div>

                <!-- Kelola Admin & Owner -->
                <a href="{{ route('admin.users.index') }}" 
                   class="sidebar-nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-md text-sm font-semibold transition-all {{ request()->routeIs('admin.users.*') ? 'sidebar-nav-active' : 'text-white/90' }}">
                    <div class="icon-box" style="width: 1.75rem; height: 1.75rem; border: 1px solid rgba(255, 255, 255, 0.4);">
                        <i class="fa-solid fa-user-shield text-xs text-white"></i>
                    </div>
                    <span>Kelola Admin &amp; Owner</span>
                </a>
            </nav>
        </div>

        <!-- Sidebar Footer Info -->
        <div class="p-4 border-t border-white/15 text-[11px] text-white/80 flex items-center justify-between">
            <span class="font-semibold">E-Parkir IoT &bull; v2.0</span>
        </div>
    </aside>

    <!-- MAIN WRAPPER -->
    <div class="flex-1 flex flex-col min-w-0 min-h-screen bg-[#FFFFFF]">

        <!-- TOPBAR: Crisp White #FFFFFF Canvas Solid Opaque -->
        <header id="admin-topbar" style="background-color: #FFFFFF !important;" class="h-20 bg-white clean-border-b sticky top-0 z-30 px-6 lg:px-8 flex items-center justify-between no-print">
            <div class="flex items-center gap-3">
                <button onclick="toggleSidebar()" class="lg:hidden p-2 text-black clean-border rounded-md hover:bg-black/5">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
                </button>
                <div>
                    <h2 class="text-lg font-bold text-black tracking-tight flex items-center gap-2">
                        @yield('header_title', 'Dashboard')
                    </h2>
                    <p class="text-xs text-black/60 hidden sm:block">
                        @yield('header_subtitle', 'Monitoring sistem palang pintu & transaksi parkir otomatis')
                    </p>
                </div>
            </div>

            <!-- Topbar Right Controls -->
            <div class="flex items-center gap-3 sm:gap-4">
                <!-- Clock Widget with Blue Icon -->
                <div class="hidden md:flex items-center gap-2 px-3 py-1.5 rounded-md text-white text-xs font-mono font-medium" style="background-color: #00719C; border: 1.5px solid #005677;">
                    <div class="w-5 h-5 rounded flex items-center justify-center text-[10px] text-white" style="border: 1px solid rgba(255, 255, 255, 0.4);">
                        <i class="fa-regular fa-clock"></i>
                    </div>
                    <span id="live-clock" class="text-white">--:--:-- WIB</span>
                </div>

                <!-- User Profile Dropdown Pill -->
                <div class="relative pl-2 sm:pl-4 sm:border-l sm:border-black/10">
                    <div class="user-pill-btn flex items-center justify-between md:justify-start gap-2.5 px-3 py-1.5 rounded-lg cursor-pointer select-none" id="user-dropdown-toggle" onclick="toggleUserDropdown(event)">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-[#00719C] text-white flex items-center justify-center font-bold text-sm overflow-hidden shrink-0">
                                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                            </div>
                            <span class="text-sm font-semibold text-black leading-tight">{{ explode(' ', auth()->user()->name ?? 'Admin')[0] }}</span>
                        </div>
                        <i class="fa-solid fa-chevron-down text-[11px] text-slate-500 transition-transform duration-200 shrink-0" id="user-dropdown-caret"></i>
                    </div>

                    <!-- User Dropdown Menu (Solid Opaque White) -->
                    <div id="user-dropdown-menu" style="background-color: #FFFFFF !important; opacity: 1 !important;" class="hidden absolute right-0 mt-2 w-52 bg-white rounded-md clean-border shadow-lg py-1 z-50">
                        <div class="px-4 py-2.5 border-b border-black/10 bg-white">
                            <p class="text-xs font-bold text-black truncate">{{ auth()->user()->name ?? 'Admin' }}</p>
                            <p class="text-[11px] text-black/60 truncate mb-1">{{ auth()->user()->email ?? '' }}</p>
                            <span class="inline-block text-[9px] font-bold uppercase px-2 py-0.5 rounded bg-blue-100 text-blue-800 border border-blue-200">
                                Role: Administrator
                            </span>
                        </div>
                        <form method="POST" action="{{ route('logout') }}" id="logout-form">
                            @csrf
                            <button type="submit" class="w-full text-left flex items-center gap-2 px-4 py-2 text-xs font-semibold text-rose-600 hover:bg-black/5 transition bg-white cursor-pointer border-none">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
                                <span>Keluar (Logout)</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <!-- MAIN CONTENT CONTAINER -->
        <main class="flex-1 p-6 lg:p-10 space-y-8 bg-[#FFFFFF]">
            @yield('content')
        </main>

        <!-- FOOTER: Crisp White #FFFFFF Canvas -->
        <footer class="mt-auto px-6 lg:px-10 py-5 clean-border-t bg-[#FFFFFF] text-center text-xs text-black/60 no-print">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3 max-w-7xl mx-auto">
                <p>&copy; 2026 <b class="text-black">E-Parkir IoT Platform</b> &bull; Industrial Gate &amp; Slot Management</p>
                <div class="flex items-center gap-2 text-[11px] font-medium text-black">
                    <span class="px-2 py-0.5 rounded clean-border">ESP8266 Gate</span>
                    <span class="px-2 py-0.5 rounded" style="background-color: var(--yellow-wash); border: 1px solid var(--yellow-border);">RC522 RFID</span>
                    <span class="px-2 py-0.5 rounded clean-border">MySQL Database</span>
                </div>
            </div>
        </footer>
    </div>

    @if (auth()->user()->role === 'admin')
    <!-- UNIVERSAL EMERGENCY OVERRIDE MODAL (Minimalist Clean-Cut Panel) -->
    <div id="emergency-modal" class="no-print fixed inset-0 bg-black/30 z-50 flex items-center justify-center p-4 hidden">
        <div class="modal-card max-w-md w-full p-6">
            <div class="flex items-center justify-between pb-3.5 clean-border-b mb-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded bg-[#00719C] text-white flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <h3 class="text-sm font-bold text-black uppercase tracking-wide">Emergency Override Gerbang</h3>
                </div>
                <button type="button" onclick="closeEmergencyModal()" class="w-7 h-7 flex items-center justify-center rounded text-black/50 hover:text-black hover:bg-black/5 transition text-sm">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            
            <p class="text-xs text-black/60 mb-5 leading-relaxed">
                Membuka paksa palang pintu gerbang dari jarak jauh untuk evakuasi darurat atau kendaraan prioritas.
            </p>

            <form id="emergency-form" onsubmit="submitEmergencyOverride(event)" class="space-y-4">
                <div>
                    <label class="block text-[11px] font-semibold text-black uppercase tracking-wider mb-2">
                        Pilih Gerbang Tujuan
                    </label>
                    <select id="emergency-device-code" required class="input-clean w-full text-xs font-semibold text-black">
                        <option value="Gate-01">Gate-01 (Palang Gerbang Utama)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-black uppercase tracking-wider mb-2">
                        Alasan Pembukaan Darurat
                    </label>
                    <textarea id="emergency-reason" rows="2" required placeholder="Contoh: Ambulans darurat / Palang macet"
                        class="input-clean w-full text-xs placeholder:text-black/40 text-black">Instruksi darurat manual administrator</textarea>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="button" onclick="closeEmergencyModal()"
                        class="flex-1 py-2.5 px-4 bg-[#FFFFFF] hover:bg-black/5 text-black text-xs font-semibold rounded-md clean-border transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" id="btn-submit-override"
                        class="flex-1 py-2.5 px-4 btn-primary text-xs flex items-center justify-center gap-2 cursor-pointer">
                        <div class="w-4 h-4 rounded bg-[#00719C] text-white flex items-center justify-center text-[10px]">
                            <svg class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                        </div>
                        <span>Buka Palang Sekarang</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- TOAST CONTAINER -->
    <div id="toast-container" class="toast-container"></div>

    <script>
        // Sidebar Toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            sidebar.classList.toggle('-translate-x-full');
            backdrop.classList.toggle('hidden');
        }

        // User Dropdown Toggle
        function toggleUserDropdown(e) {
            if (e) e.stopPropagation();
            const menu = document.getElementById('user-dropdown-menu');
            const caret = document.getElementById('user-dropdown-caret');
            if (menu) {
                const isHidden = menu.classList.contains('hidden');
                if (isHidden) {
                    menu.classList.remove('hidden');
                    if (caret) caret.classList.add('rotate-180');
                } else {
                    menu.classList.add('hidden');
                    if (caret) caret.classList.remove('rotate-180');
                }
            }
        }

        window.addEventListener('click', function(e) {
            const toggle = document.getElementById('user-dropdown-toggle');
            const menu = document.getElementById('user-dropdown-menu');
            const caret = document.getElementById('user-dropdown-caret');
            if (toggle && menu && !toggle.contains(e.target) && !menu.contains(e.target)) {
                menu.classList.add('hidden');
                if (caret) caret.classList.remove('rotate-180');
            }
        });

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

        // Emergency Modal
        function openEmergencyModal(deviceCode = null) {
            const modal = document.getElementById('emergency-modal');
            if (deviceCode) {
                document.getElementById('emergency-device-code').value = deviceCode;
            }
            modal.classList.remove('hidden');
        }

        function closeEmergencyModal() {
            document.getElementById('emergency-modal').classList.add('hidden');
        }

        async function submitEmergencyOverride(e) {
            e.preventDefault();
            const btn = document.getElementById('btn-submit-override');
            const deviceCode = document.getElementById('emergency-device-code').value;
            const reason = document.getElementById('emergency-reason').value;

            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memproses...';

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

                    // Langsung jalankan animasi buka palang di web seketika tanpa jeda
                    if (typeof openBarrierGate === 'function') {
                        openBarrierGate(8000);
                    }
                } else {
                    showToast('Gagal', data.message, 'error');
                }
            } catch (err) {
                showToast('Error', 'Gagal menghubungi server: ' + err.message, 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = `
                    <div class="w-4 h-4 rounded bg-[#00719C] text-white inline-flex items-center justify-center text-[10px] mr-1">
                        <svg class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    </div>
                    <span>Buka Palang Sekarang</span>
                `;
            }
        }

        // Toast Helper
        function showToast(title, message, type = 'info') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');

            const svgIcons = {
                success: '<div class="toast-badge-icon"><svg class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg></div>',
                error: '<div class="toast-badge-icon badge-error"><svg class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" x2="9" y1="9" y2="15"/><line x1="9" x2="15" y1="9" y2="15"/></svg></div>',
                info: '<div class="toast-badge-icon"><svg class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="16" y2="12"/><line x1="12" x2="12.01" y1="8" y2="8"/></svg></div>',
                warning: '<div class="toast-badge-icon badge-warning"><svg class="w-3.5 h-3.5 text-black" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 22 22 22 12 2"/><line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12.01" y1="17" y2="17"/></svg></div>'
            };

            const typeClass = 'toast-' + type;
            toast.className = `toast-item ${typeClass} transform -translate-y-2 opacity-0`;
            toast.innerHTML = `
                ${svgIcons[type] || svgIcons.info}
                <div class="flex-1">
                    <div class="toast-title">${title}</div>
                    <div class="toast-message">${message}</div>
                </div>
            `;
            container.appendChild(toast);

            setTimeout(() => {
                toast.classList.remove('-translate-y-2', 'opacity-0');
            }, 10);

            setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-x-full');
                setTimeout(() => toast.remove(), 300);
            }, 4000);
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
