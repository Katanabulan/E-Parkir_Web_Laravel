<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk Administrator - E-Parkir IoT</title>
    <!-- Google Fonts for crisp typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Local FontAwesome 6 Icons & CSS Engine -->
    <link rel="stylesheet" href="{{ asset('assets/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <style>
        :root {
            --c-white: #FFFFFF;
            --c-blue: #00719C;
            --c-yellow: #FFD51E;
            --c-black: #000000;
            --yellow-wash: rgba(255, 213, 30, 0.15);
            --yellow-border: rgba(255, 213, 30, 0.35);
            --blue-wash: rgba(0, 113, 156, 0.08);
        }
        html {
            height: 100%;
        }
        body {
            min-height: 100%;
            background-color: var(--c-white, #FFFFFF);
            color: var(--c-black, #000000);
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
        }
        @media (min-width: 1024px) {
            body {
                flex-direction: row;
                height: 100vh;
                overflow: hidden;
            }
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
        .text-black\/60 { color: rgba(0, 0, 0, 0.60); }
        .text-black\/50 { color: rgba(0, 0, 0, 0.50); }
        .text-black\/70 { color: rgba(0, 0, 0, 0.70); }
        .text-black\/20 { color: rgba(0, 0, 0, 0.20); }

        .icon-box {
            width: 1.625rem;
            height: 1.625rem;
            border-radius: 0.375rem;
            background-color: #00719C;
            color: #FFFFFF;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 0.75rem;
        }
        .icon-box i {
            color: #FFFFFF !important;
        }

        .input-clean {
            width: 100%;
            padding-top: 0.625rem;
            padding-bottom: 0.625rem;
            background-color: #FFFFFF !important;
            border: 1px solid rgba(0, 0, 0, 0.15) !important;
            border-radius: 0.5rem;
            color: #000000 !important;
            font-size: 0.875rem;
            transition: all 0.15s ease;
            box-sizing: border-box;
        }
        .input-clean:focus {
            border-color: #00719C !important;
            outline: none;
            box-shadow: 0 0 0 1px #00719C !important;
        }
        .input-icon-left {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            left: 0.875rem;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            z-index: 10;
        }

        .input-clean.has-icon {
            padding-left: 3.25rem !important;
        }
        .input-clean.has-action {
            padding-right: 2.75rem !important;
        }
    </style>
</head>
<body class="min-h-screen w-screen overflow-x-hidden lg:overflow-hidden flex flex-col lg:flex-row select-none text-black bg-[#FFFFFF] antialiased">

    <!-- ============================================================== -->
    <!-- LEFT PANEL: EDITORIAL ARCHITECTURE SHOWCASE (58%)             -->
    <!-- Teks Hitam, Icon Wadah Biru (#00719C) & Icon Putih (#FFFFFF)   -->
    <!-- ============================================================== -->
    <div class="hidden lg:flex lg:w-7/12 min-h-screen flex-col justify-between p-10 xl:p-14 clean-border-r bg-[#FFFFFF] relative">
        
        <!-- TOP BAR: Live System Telemetry -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <!-- Icon with Blue Background & White Icon -->
                <div class="icon-box" style="width: 2.5rem; height: 2.5rem; font-size: 1.25rem; border-radius: 0.5rem; border: 1.5px solid #005677;">
                    <i class="fa-solid fa-square-parking"></i>
                </div>
                <div>
                    <div class="text-base font-bold tracking-tight text-black flex items-center gap-1.5">
                        <span>E-Parkir</span>
                        <span class="text-[9px] uppercase font-black px-1.5 py-0.5 rounded bg-[#FFD51E] text-black border border-black/10">IoT</span>
                    </div>
                    <div class="text-[12px] text-black/60 font-medium">Smart Barrier Gate &amp; Billing System</div>
                </div>
            </div>
        </div>

        <!-- CENTER STAGE: Architectural Blueprint & Gate Specs -->
        <div class="my-auto py-6 space-y-6 max-w-xl">
            
            <!-- Headline Header -->
            <div>
                <div class="text-[11px] font-semibold uppercase tracking-widest text-black/60 mb-2">
                    Dokumentasi Operasional &bull; Tim Sigma Boys
                </div>
                <h1 class="text-3xl xl:text-4xl font-semibold text-black leading-tight tracking-tight">
                    Otomasi Gerbang &amp; Pengelolaan Tarif Cerdas
                </h1>
                <p class="text-sm text-black/60 mt-3 leading-relaxed">
                    Sistem kendali otomatis RFID RC522 13.56 MHz, servo barrier gate presisi, dan integrasi penagihan flat <b class="text-black">Rp2.000 / Jam</b> tanpa jeda transaksi.
                </p>
            </div>

            <!-- Clean-Cut Gate Structural Diagram (Blue Card) -->
            <div class="p-6 rounded-lg text-white" style="background-color: #00719C; border: 1px solid rgba(255, 255, 255, 0.2);">
                <div class="flex items-center justify-between pb-4" style="border-bottom: 1px solid rgba(255, 255, 255, 0.15);">
                    <div class="flex items-center gap-3">
                        <!-- Icon with White Border -->
                        <div class="icon-box" style="width: 2rem; height: 2rem; font-size: 0.875rem; border: 1.5px solid #FFFFFF;">
                            <i class="fa-solid fa-microchip"></i>
                        </div>
                        <div>
                            <div class="text-xs font-bold text-white tracking-wide">GATE UNIT 01 &bull; RFID CONTROLLER</div>
                        </div>
                    </div>
                </div>

                <!-- Simulation Architecture Row -->
                <div class="mt-5 grid grid-cols-2 gap-4">
                    <!-- Palang State -->
                    <div class="p-3.5 rounded" style="border: 1px solid rgba(255, 255, 255, 0.2); background-color: rgba(255, 255, 255, 0.06);">
                        <div class="text-[11px] uppercase tracking-wider text-white/70 font-semibold">Posisi Palang Gerbang</div>
                        <div class="text-sm font-bold text-white mt-1 flex items-center gap-2">
                            <span>0&deg; (Palang Tertutup)</span>
                        </div>
                        <div class="text-[11px] text-white/60 mt-0.5">Sudut angkat operasi: 90&deg;</div>
                    </div>

                    <!-- Sensor Telemetry -->
                    <div class="p-3.5 rounded" style="border: 1px solid rgba(255, 255, 255, 0.2); background-color: rgba(255, 255, 255, 0.06);">
                        <div class="text-[11px] uppercase tracking-wider text-white/70 font-semibold">Status Traffic Light</div>
                        <div class="text-sm font-bold text-white mt-1 flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full inline-block" style="background-color: #EF4444;"></span>
                            <span>RED ON (Siaga Tap RFID)</span>
                        </div>
                        <div class="text-[11px] text-white/60 mt-0.5">Buzzer alert standby</div>
                    </div>
                </div>

                <!-- Metric Cells in Clean 1px Line Grid -->
                <div class="mt-5 pt-4" style="border-top: 1px solid rgba(255, 255, 255, 0.15);">
                    <div class="p-2.5 rounded text-center" style="border: 1px solid rgba(255, 255, 255, 0.2); background-color: rgba(255, 255, 255, 0.06);">
                        <div class="text-[10px] uppercase font-semibold tracking-wider text-white/70">Tarif Flat</div>
                        <div class="text-xs font-bold text-white mt-1">Rp2.000 / Jam</div>
                    </div>
                </div>
            </div>

        </div>

        <!-- BOTTOM BAR: Dev Team Info -->
        <div class="pt-4 clean-border-t flex items-center justify-between text-xs text-black/60">
            <div class="flex items-center gap-2">
                <span class="font-semibold text-black">Pengembang:</span>
                <span>Fardhan &bull; Fauzan &bull; Haikal &bull; Randy &bull; Krisna</span>
            </div>
        </div>

    </div>

    <!-- ============================================================== -->
    <!-- RIGHT PANEL: AUTHENTICATION FORM (42%)                        -->
    <!-- ============================================================== -->
    <div class="w-full lg:w-5/12 min-h-screen flex flex-col justify-between p-8 sm:p-12 lg:p-14 bg-[#FFFFFF]">
        
        <!-- Top Identity Bar (Mobile Only Branding) -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 lg:hidden">
                <!-- Icon with Blue Background & White Icon -->
                <div class="icon-box" style="width: 2rem; height: 2rem; border: 1.5px solid #005677;">
                    <i class="fa-solid fa-square-parking text-sm"></i>
                </div>
                <div class="font-bold text-black text-sm tracking-tight flex items-center gap-1.5">
                    <span>E-Parkir</span>
                    <span class="text-[9px] uppercase font-black px-1.5 py-0.5 rounded bg-[#FFD51E] text-black border border-black/10">IoT</span>
                </div>
            </div>
        </div>

        <!-- Center: The Login Form Core -->
        <div class="my-auto py-4 max-w-sm w-full mx-auto">
            
            <div class="mb-8">
                <h2 class="text-2xl sm:text-3xl font-bold text-black tracking-tight">
                    Masuk Akun E-Parkir
                </h2>
                <p class="text-xs text-black/60 mt-1.5 leading-relaxed font-normal">
                    Silakan masukkan email dan kata sandi Anda. Sistem akan mengarahkan otomatis ke Dashboard Admin atau Antarmuka Pengendara.
                </p>
            </div>

            <!-- Error Banner -->
            @if(session('error') || $errors->any())
                <div class="mb-5 p-3.5 rounded clean-border text-xs flex items-center gap-3" style="background-color: var(--yellow-wash); border-color: var(--yellow-border);">
                    <div class="icon-box" style="width: 1.5rem; height: 1.5rem; background-color: #EF4444;">
                        <i class="fa-solid fa-circle-exclamation"></i>
                    </div>
                    <div class="font-semibold text-black">
                        {{ session('error') ?? $errors->first() }}
                    </div>
                </div>
            @endif

            @if(session('success'))
                <div class="mb-5 p-3.5 rounded clean-border text-xs flex items-center gap-3" style="background-color: #ecfdf5; border-color: #a7f3d0;">
                    <div class="icon-box" style="width: 1.5rem; height: 1.5rem; background-color: #10b981;">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <div class="font-semibold text-black">
                        {{ session('success') }}
                    </div>
                </div>
            @endif

            <!-- Form -->
            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                
                <!-- Email Field -->
                <div>
                    <label for="email" class="block text-[11px] font-semibold text-black uppercase tracking-wider mb-2">
                        Alamat Email
                    </label>
                    <div class="relative">
                        <!-- Icon with Blue Background & White Icon -->
                        <div class="input-icon-left">
                            <div class="icon-box">
                                <i class="fa-solid fa-envelope"></i>
                            </div>
                        </div>
                        <input type="email" id="email" name="email" required
                            value="{{ old('email', '') }}"
                            placeholder="nama@email.com"
                            autocomplete="email"
                            class="input-clean has-icon font-medium text-black">
                    </div>
                </div>

                <!-- Password Field -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label for="password" class="block text-[11px] font-semibold text-black uppercase tracking-wider">
                            Kata Sandi
                        </label>
                    </div>
                    <div class="relative">
                        <!-- Icon with Blue Background & White Icon -->
                        <div class="input-icon-left">
                            <div class="icon-box">
                                <i class="fa-solid fa-key"></i>
                            </div>
                        </div>
                        <input type="password" id="password" name="password" required
                            value=""
                            placeholder="••••••••"
                            autocomplete="current-password"
                            class="input-clean has-icon has-action font-mono text-black">
                        <button type="button" onclick="togglePasswordVisibility()" 
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-black/50 hover:text-black cursor-pointer"
                            aria-label="Tampilkan atau sembunyikan kata sandi">
                            <i id="eye-icon" class="fa-solid fa-eye text-xs"></i>
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button type="submit"
                        class="btn-primary w-full py-3 px-5 text-xs sm:text-sm flex items-center justify-center gap-2">
                        <span>Masuk ke Sistem</span>
                        <i class="fa-solid fa-arrow-right text-xs text-white"></i>
                    </button>
                </div>
            </form>

        </div>

        <!-- Footer -->
        <div class="pt-4 clean-border-t text-[11px] text-black/50">
            <span>&copy; 2026 E-Parkir IoT Platform</span>
        </div>

    </div>

    <!-- Scripts -->
    <script>
        // Password Visibility Toggle
        function togglePasswordVisibility() {
            const pwd = document.getElementById('password');
            const icon = document.getElementById('eye-icon');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                pwd.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
