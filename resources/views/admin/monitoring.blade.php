@extends('layouts.admin')

@section('title', 'Gate Simulator & Monitoring IoT')
@section('header_title', 'Gate Simulator & Hardware IoT')
@section('header_subtitle', 'Simulasi visual pergerakan palang servo, lampu lalu lintas, audio buzzer, dan pengujian kartu RFID')

@section('content')
<!-- SIMULATOR CONTAINER -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

    <!-- LEFT COLUMN: VISUAL HARDWARE INTERACTION (7 Cols) -->
    <div class="lg:col-span-7 space-y-6">

        <!-- Visual Gate Display Card (White #FFFFFF Card) -->
        <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-sm relative overflow-hidden">
            <!-- Header bar of simulator -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-5 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div>
                        <div class="text-sm font-extrabold text-[#091530] flex items-center gap-2">
                            <span>Gerbang Fisik IoT</span>
                            <span id="gate-state-badge" class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-50 text-rose-600 border border-rose-200">
                                TERTUTUP (0°)
                            </span>
                        </div>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-xs text-slate-500 font-mono font-medium" id="current-device-display">Device: {{ $selectedDevice }}</span>
                            <span id="esp-status-badge" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">
                                Memeriksa ESP...
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Kontrol Printer Eppos EP589 V2 & Auto-Print -->
                <div class="flex items-center gap-2 flex-wrap">
                    <input type="hidden" id="device-select" value="{{ $devices->first()->device_code ?? 'Gate-01' }}">
                    <label class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-50 clean-border text-xs cursor-pointer select-none" title="Centang agar setiap tap-out langsung mencetak struk secara otomatis">
                        <input type="checkbox" id="autoprint-toggle" checked class="w-3.5 h-3.5 accent-[#00719C] rounded cursor-pointer">
                        <span class="font-bold text-black text-[11px]"><i class="fa-solid fa-print text-[#00719C] mr-1"></i>Auto-Cetak Struk</span>
                    </label>

                    <button type="button" onclick="connectBluetoothPrinter()" id="btn-quick-bt-connect" class="px-3 py-1.5 rounded-xl bg-[#00719C] hover:bg-sky-700 text-white text-[11px] font-bold flex items-center gap-1.5 shadow transition cursor-pointer" title="Hubungkan Bluetooth Eppos EP589 V2 untuk auto-print wireless otomatis tanpa popup">
                        <i class="fa-brands fa-bluetooth-b text-xs"></i>
                        <span id="bt-quick-label">Connect EP589</span>
                    </button>

                    <button type="button" onclick="testPrintBluetooth()" id="btn-quick-bt-test" class="px-3 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-[11px] font-bold flex items-center gap-1.5 shadow transition cursor-pointer" title="Cetak struk tes ke Eppos EP589 V2 untuk memastikan printer siap">
                        <i class="fa-solid fa-receipt text-xs"></i>
                        <span>Tes Cetak EP589</span>
                    </button>
                </div>
            </div>

            <!-- ANIMATED BARRIER & TRAFFIC LIGHT CANVAS (#091530 Deep Navy Canvas) -->
            <div class="my-6 p-6 bg-[#091530] rounded-3xl border border-[#0e2456] relative min-h-[380px] shadow-inner overflow-hidden select-none flex flex-col justify-between">

                <!-- TOP BAR: Traffic Light & Auto-Close Timer -->
                <div class="flex items-start justify-between w-full z-20">
                    <!-- Traffic Light 3-Warna (Red, Yellow, Green) -->
                    <div class="bg-slate-950/95 border border-slate-700/80 rounded-2xl p-2.5 shadow-2xl flex flex-col items-center gap-2 backdrop-blur-sm">
                        <div class="text-[8px] font-black text-slate-400 uppercase tracking-widest text-center px-1">LIGHT</div>
                        <!-- Red LED -->
                        <div id="led-red" style="width: 20px; height: 20px; border-radius: 50%; background-color: #ef4444; box-shadow: 0 0 16px #ef4444, 0 0 28px rgba(239, 68, 68, 0.7); border: 1px solid #fca5a5; transition: all 0.3s ease;"></div>
                        <!-- Yellow LED -->
                        <div id="led-yellow" style="width: 20px; height: 20px; border-radius: 50%; background-color: #451a03; border: 1px solid #78350f; opacity: 0.35; transition: all 0.3s ease;"></div>
                        <!-- Green LED -->
                        <div id="led-green" style="width: 20px; height: 20px; border-radius: 50%; background-color: #022c22; border: 1px solid #064e3b; opacity: 0.35; transition: all 0.3s ease;"></div>
                    </div>

                    <!-- Live Auto-close timer -->
                    <div id="timer-box" class="px-4 py-1.5 bg-[#FFD51E] rounded-full text-xs font-mono font-black text-[#091530] shadow-md hidden flex items-center gap-1.5 animate-pulse">
                        <svg class="w-3.5 h-3.5 text-[#091530]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <span>Palang menutup dalam <span id="timer-count" class="font-black text-sm">5</span>s</span>
                    </div>
                </div>

                <!-- BOTTOM AREA: REALISTIC BOOM BARRIER & ROAD SCHEMATIC -->
                <div class="relative w-full h-56 mt-4 flex items-end">
                    
                    <!-- 1. Full-Width Asphalt Road with Reflective Centerline -->
                    <div class="absolute bottom-0 left-0 right-0 h-10 bg-slate-900 border-t-2 border-slate-700 flex items-center justify-around px-4">
                        <div class="w-full h-1 flex justify-around items-center">
                            <span class="w-12 h-1 bg-[#FFD51E]/70 rounded"></span>
                            <span class="w-12 h-1 bg-[#FFD51E]/70 rounded"></span>
                            <span class="w-12 h-1 bg-[#FFD51E]/70 rounded"></span>
                            <span class="w-12 h-1 bg-[#FFD51E]/70 rounded"></span>
                        </div>
                    </div>

                    <!-- 2. Curb / Island Trotoar Kiri (Dudukan Gate Cabinet) -->
                    <div style="position: absolute; bottom: 24px; left: 24px; width: 144px; height: 16px;" class="bg-slate-800 rounded-t border-t border-slate-600 shadow-md"></div>

                    <!-- 3. Barrier Gate Housing Cabinet (GATE 01) -->
                    <div style="position: absolute; bottom: 40px; left: 48px; width: 80px; height: 160px;" class="bg-[#00719C] border-2 border-white/40 rounded-t-2xl shadow-2xl flex flex-col items-center justify-between py-3 z-10">
                        <div class="w-4 h-4 rounded-full bg-slate-900 border border-[#FFD51E] flex items-center justify-center">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#FFD51E]"></span>
                        </div>
                        <div class="text-[10px] font-black text-white tracking-wider uppercase text-center leading-tight">
                            GATE<br><span class="text-[9px] text-[#FFD51E] font-black">01</span>
                        </div>
                        <div class="w-10 h-1.5 bg-[#FFD51E] rounded-full"></div>

                        <!-- Servo Pivot Hub (Poros Putar Servo Motor di Sisi Kanan Kabinet) -->
                        <div style="position: absolute; right: -16px; top: 24px; width: 32px; height: 32px; border-radius: 50%; background-color: #0f172a; border: 2px solid #FFD51E; box-shadow: 0 4px 12px rgba(0,0,0,0.6); z-index: 25;">
                            <!-- Center pin of the servo hub -->
                            <div style="position: absolute; left: 10px; top: 10px; width: 8px; height: 8px; border-radius: 50%; background-color: #00719C;"></div>

                            <!-- THE MOVING BARRIER ARM (Strip Kuning & Putih) -->
                            <div id="gate-arm"
                                style="position: absolute; left: 14px; top: 9px; width: 230px; height: 14px; transform-origin: 0px 7px; transform: rotate(0deg); transition: transform 1.0s cubic-bezier(0.4, 0, 0.2, 1); z-index: 30; pointer-events: none;">
                                <div style="width: 100%; height: 100%; display: flex; border-radius: 0 4px 4px 0; border: 1px solid #0f172a; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.4);">
                                    <div style="flex: 1; background: #FFD51E;"></div>
                                    <div style="flex: 1; background: #FFFFFF;"></div>
                                    <div style="flex: 1; background: #FFD51E;"></div>
                                    <div style="flex: 1; background: #FFFFFF;"></div>
                                    <div style="flex: 1; background: #FFD51E;"></div>
                                    <div style="flex: 1; background: #FFFFFF;"></div>
                                    <div style="flex: 1; background: #FFD51E;"></div>
                                    <div style="flex: 1; background: #FFFFFF;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 4. RFID Reader Pedestal (Tiang Tap Driver di Sisi Kanan Jalan) -->
                    <div style="position: absolute; bottom: 40px; right: 40px; width: 44px; height: 112px;" class="bg-[#00719C] rounded-t-xl border-2 border-white/40 flex flex-col items-center justify-start pt-3 shadow-lg z-10">
                        <div class="w-8 h-8 bg-[#005a7d] border-2 border-white rounded-lg text-[9px] text-white font-black flex items-center justify-center text-center leading-none shadow-sm">
                            RFID
                        </div>
                        <div class="w-2 h-2 rounded-full bg-emerald-400 mt-3 shadow-[0_0_8px_#34d399]"></div>
                        <div class="text-[7px] font-bold text-white/90 mt-auto mb-2 uppercase">TAP HERE</div>
                    </div>
                </div>
            </div>

            <!-- Direct Override & Ticket Triggers from Simulator -->
            <div class="flex items-center justify-between pt-2 flex-wrap gap-2">
                <span class="text-xs font-bold text-slate-500">Aksi Cepat Gerbang:</span>
                <div class="flex items-center gap-2 flex-wrap">
                    <!-- Tombol Override Darurat -->
                    <button type="button" onclick="triggerEmergencySimulator()"
                        class="py-2.5 px-4 bg-[#FFD51E] hover:bg-[#e6be12] text-[#091530] border border-[#b39000]/30 rounded-2xl text-xs font-extrabold transition flex items-center gap-2 shadow-sm cursor-pointer">
                        <svg class="w-4 h-4 text-[#091530]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/></svg>
                        <span>Override Buka Manual</span>
                    </button>
                </div>
            </div>
        </div>

    </div>

    <!-- RIGHT COLUMN: STATUS PARKIR REAL-TIME (5 Cols) -->
    <div class="lg:col-span-5 space-y-6">

        <!-- Parking Status Card (Editorial Card) -->
        <div class="editorial-card p-6 bg-[#FFFFFF]">
            <div class="flex items-center justify-between pb-4 clean-border-b">
                <div class="flex items-center gap-2.5">
                    <div>
                        <h3 class="text-sm font-bold text-black">Status Parkir Real-Time</h3>
                        <p class="text-[11px] text-black/60">Ketersediaan slot &amp; kendaraan di dalam</p>
                    </div>
                </div>
                <div id="side-status-badge" class="px-2.5 py-0.5 rounded text-[11px] font-semibold clean-border text-black flex items-center gap-1.5">
                    <span class="status-dot-active"></span>
                    <span id="side-status-badge-text">{{ $parkedCount > 0 ? 'ADA KENDARAAN' : 'PARKIR KOSONG' }}</span>
                </div>
            </div>

            <!-- Capacity Progress & Counters -->
            <div class="py-4">
                <div class="flex items-center justify-between text-xs font-semibold mb-2">
                    <span class="text-black/60">Okupansi Slot</span>
                    <span class="text-black font-mono"><b id="side-parked-count" class="text-base text-[#00719C]">{{ $parkedCount }}</b> / <span id="side-total-capacity">{{ $totalCapacity }}</span> Kendaraan</span>
                </div>
                <!-- Progress Bar -->
                <div class="w-full h-2.5 bg-black/5 rounded-full overflow-hidden clean-border p-0.5">
                    <div id="side-progress-bar" class="h-full rounded-full transition-all duration-500 {{ $parkedCount >= $totalCapacity ? 'bg-rose-500' : 'bg-[#00719C]' }}"
                         style="width: {{ min(100, round(($parkedCount / $totalCapacity) * 100)) }}%"></div>
                </div>
                <div class="flex items-center justify-between text-[11px] text-black/60 font-medium mt-2">
                    <span id="side-available-text" class="text-black font-semibold flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-[#00719C]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                        <span>{{ $availableSlots }} Slot Tersedia</span>
                    </span>
                    <span id="side-occupancy-percent" class="text-black/50 font-mono">
                        {{ round(($parkedCount / $totalCapacity) * 100) }}% Terisi
                    </span>
                </div>

                <!-- Per-Floor Status Badges -->
                <div class="grid grid-cols-2 gap-2 mt-3 pt-3 clean-border-t">
                    <div class="p-2.5 rounded-md bg-[#F4F9FC] clean-border flex flex-col justify-between">
                        <div class="flex items-center justify-between text-[10px] font-bold text-[#00719C]">
                            <span>LANTAI 1 (LT 1)</span>
                            <span id="floor-badge-lt1" class="px-1.5 py-0.2 rounded text-[9px] font-mono {{ $parkedLt1 >= 10 ? 'bg-rose-600 text-white' : 'bg-[#00719C] text-white' }}">{{ $parkedLt1 >= 10 ? 'PENUH' : 'TERSEDIA' }}</span>
                        </div>
                        <div class="mt-1 flex items-baseline justify-between">
                            <span class="text-xs font-black text-black font-mono"><span id="side-lt1-parked">{{ $parkedLt1 }}</span> / 10</span>
                            <span class="text-[10px] text-black/60 font-medium">Sisa <b id="side-lt1-avail" class="text-[#00719C]">{{ $availableLt1 }}</b></span>
                        </div>
                    </div>
                    <div class="p-2.5 rounded-md bg-[#FFFBEB] clean-border flex flex-col justify-between">
                        <div class="flex items-center justify-between text-[10px] font-bold text-amber-800">
                            <span>LANTAI 2 (LT 2)</span>
                            <span id="floor-badge-lt2" class="px-1.5 py-0.2 rounded text-[9px] font-mono {{ $parkedLt2 >= 10 ? 'bg-rose-600 text-white' : 'bg-amber-600 text-white' }}">{{ $parkedLt2 >= 10 ? 'PENUH' : 'TERSEDIA' }}</span>
                        </div>
                        <div class="mt-1 flex items-baseline justify-between">
                            <span class="text-xs font-black text-black font-mono"><span id="side-lt2-parked">{{ $parkedLt2 }}</span> / 10</span>
                            <span class="text-[10px] text-black/60 font-medium">Sisa <b id="side-lt2-avail" class="text-amber-700">{{ $availableLt2 }}</b></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- List Container: Ada Kendaraan ATAU Parkiran Kosong -->
            <div class="pt-3 clean-border-t">
                <div class="text-[11px] font-semibold uppercase tracking-wider text-black/50 mb-3 flex items-center justify-between">
                    <span>Daftar Kendaraan Terparkir</span>
                    <span class="text-[10px] text-black/40 font-mono">Live Sync</span>
                </div>

                <!-- Empty State (Jika Kosong) -->
                <div id="side-empty-state" class="{{ $parkedCount === 0 ? 'flex' : 'hidden' }} flex-col items-center justify-center py-6 text-center clean-border rounded-md p-4 bg-[#FFFFFF]">
                    <div class="w-10 h-10 rounded-md bg-[#00719C] text-white flex items-center justify-center mb-2">
                        <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                    </div>
                    <div class="text-xs font-bold text-black">Tidak Ada Kendaraan Terparkir</div>
                    <p class="text-[11px] text-black/60 mt-1 max-w-[220px]">
                        Semua {{ $totalCapacity }} slot parkir saat ini kosong dan siap digunakan.
                    </p>
                </div>

                <!-- List Kendaraan (Jika Ada Kendaraan) -->
                <div id="side-vehicles-list" class="space-y-2.5 max-h-80 overflow-y-auto pr-1 {{ $parkedCount > 0 ? '' : 'hidden' }}">
                    @if ($parkedCount > 0)
                        @foreach ($parkedVehicles as $pv)
                            @php
                                $isTicket = !empty($pv->ticket_code);
                                $inTime = strtotime($pv->tap_in_time);
                                $diffMin = max(1, (int) round((time() - $inTime) / 60));
                                $billedHours = max(1, (int) ceil($diffMin / 60));
                                $fee = $billedHours * 2000;
                                $userBalance = $isTicket ? 0 : (int) ($pv->card ? $pv->card->balance : 0);
                                $scanUid = $isTicket ? $pv->ticket_code : ($pv->card->uid ?? '');
                                $userName = $isTicket ? 'Pengendara Umum (Karcis)' : ($pv->card->user->name ?? 'Pengendara');
                                $licensePlate = $isTicket ? '-' : ($pv->card->license_plate ?? 'NO-PLAT');
                            @endphp
                            <div class="p-3 rounded-md bg-[#FFFFFF] clean-border hover:border-[#00719C] transition flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-md {{ $isTicket ? 'bg-amber-600' : 'bg-[#00719C]' }} text-white flex items-center justify-center text-xs flex-shrink-0">
                                        @if($isTicket)
                                            <i class="fa-solid fa-ticket text-xs"></i>
                                        @else
                                            <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/></svg>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-1.5">
                                            @if($isTicket)
                                                <span class="text-xs font-bold font-mono text-amber-700">{{ $pv->ticket_code }}</span>
                                                <span class="text-[9px] px-1.5 py-0.2 rounded font-semibold clean-border bg-amber-50 text-amber-800 border-amber-300">TIKET</span>
                                            @else
                                                <span class="text-xs font-bold font-mono text-black">{{ $licensePlate }}</span>
                                                <span class="text-[9px] px-1.5 py-0.2 rounded font-semibold clean-border text-black">MEMBER</span>
                                            @endif
                                            <span class="text-[9px] px-1.5 py-0.2 rounded font-bold clean-border {{ ($pv->parking_floor ?? 1) == 2 ? 'bg-amber-100 text-amber-900 border-amber-300' : 'bg-sky-100 text-[#00719C] border-sky-300' }}">LT {{ $pv->parking_floor ?? 1 }}</span>
                                        </div>
                                        <div class="text-[11px] font-medium text-black/70 mt-0.5">{{ $userName }}</div>
                                        <div class="font-mono text-[10px] text-black/50 mt-0.5">
                                            Masuk: {{ date('H:i', strtotime($pv->tap_in_time)) }} &bull; <span class="text-[#00719C] font-semibold">{{ $diffMin }} mnt</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right flex flex-col items-end gap-1.5">
                                    <div>
                                        <span class="text-xs font-bold text-black font-mono">Rp {{ number_format($fee, 0, ',', '.') }}</span>
                                        <span class="text-[9px] font-medium text-black/50 block">{{ $billedHours }} Jam Parkir</span>
                                        @if($isTicket)
                                            <span class="text-[9px] text-amber-600 font-semibold block">Bayar Tunai</span>
                                        @else
                                            <span class="text-[9px] {{ ($userBalance >= $fee) ? 'text-emerald-600' : 'text-rose-600' }} font-semibold block">
                                                Saldo: Rp {{ number_format($userBalance, 0, ',', '.') }}
                                            </span>
                                        @endif
                                    </div>
                                    <button type="button" 
                                            onclick="checkoutManualAdmin('{{ $scanUid }}', '{{ addslashes($userName) }}', this)"
                                            title="Selesaikan parkir & bayar karcis/kartu"
                                            class="btn-selesai-checkout btn-accent inline-flex items-center gap-1 px-2 py-1 text-[10px] text-black cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                                        <svg class="w-3 h-3 text-black" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                        <span>Selesaikan</span>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                <!-- Tombol ke Halaman Status Lengkap -->
                <div class="mt-4 pt-3 clean-border-t">
                    <a href="{{ route('admin.status.index') }}" class="btn-accent w-full py-2 px-3 text-xs flex items-center justify-center gap-2 text-black font-semibold">
                        <svg class="w-4 h-4 text-black" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
                        <span>Buka Denah Slot Parkir Lengkap</span>
                        <svg class="w-3.5 h-3.5 text-black" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL STRUK PARKIR (EPPOS EP589 V2 / THERMAL 58MM) -->
<div id="receipt-modal" onclick="if(event.target===this) closeReceiptModal()" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-sm w-full p-5 shadow-2xl clean-border space-y-4 animate-in fade-in zoom-in-95 duration-200">
        <div class="flex items-center justify-between pb-3 clean-border-b">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-[#00719C] text-white flex items-center justify-center text-xs font-bold">
                    <i class="fa-solid fa-receipt"></i>
                </div>
                <div>
                    <h3 class="text-xs font-black text-black uppercase tracking-wider">Struk Parkir Tap-Out</h3>
                    <p class="text-[10px] text-black/60 font-medium">Printer Eppos EP589 V2 (58mm)</p>
                </div>
            </div>
            <button type="button" onclick="closeReceiptModal()" class="w-7 h-7 flex items-center justify-center rounded text-black/50 hover:text-black hover:bg-black/5 cursor-pointer">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Receipt Preview Box -->
        <div class="bg-slate-50 p-3.5 rounded-xl clean-border font-mono text-[11px] leading-relaxed max-h-72 overflow-y-auto whitespace-pre text-black/90 select-all border-dashed" id="receipt-preview-content">
Memuat struk parkir...
        </div>

        <!-- Print Actions Grid -->
        <div class="grid grid-cols-2 gap-2 pt-1">
            <button type="button" id="btn-modal-bt" onclick="printReceiptViaBluetooth()" class="py-2.5 px-3 rounded-xl bg-[#00719C] hover:bg-sky-700 text-white text-xs font-bold flex items-center justify-center gap-1.5 shadow transition cursor-pointer">
                <i class="fa-brands fa-bluetooth-b"></i>
                <span>Cetak Bluetooth</span>
            </button>

            <button type="button" onclick="printReceiptViaBrowser()" class="py-2.5 px-3 rounded-xl bg-[#FFD51E] hover:bg-yellow-400 text-black text-xs font-bold flex items-center justify-center gap-1.5 shadow transition cursor-pointer">
                <i class="fa-solid fa-print"></i>
                <span>Cetak Driver / USB</span>
            </button>
        </div>

        <div class="flex items-center justify-between text-[11px] text-black/60 pt-2 clean-border-t">
            <a id="btn-open-receipt-link" href="#" target="_blank" class="text-[#00719C] font-bold hover:underline flex items-center gap-1">
                <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i> Buka Tab Baru
            </a>
            <button type="button" onclick="closeReceiptModal()" class="text-black/60 hover:text-black font-semibold cursor-pointer">Tutup</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- JAVASCRIPT SIMULATOR LOGIC & ANIMATION -->
<script>
    let currentDevice = '{{ $selectedDevice }}';
    let gateTimer = null;
    let openPhase1Timer = null;
    let openPhase2Timer = null;
    let countdownInterval = null;
    let isGateCurrentlyOpen = false;
    let lastProcessedLogId = 0;
    let isHardwarePollerReady = false;

    function switchDevice(dev) {
        currentDevice = dev;
        document.getElementById('current-device-display').textContent = 'Device: ' + dev;
    }

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
            // Browser tidak tersambung langsung ke Wi-Fi ESP8266 192.168.4.1
        }
        return false;
    }

    // Polling sinkronisasi real-time dari ESP8266 fisik
    async function checkHardwareSync() {
        try {
            // Jalankan deteksi Wi-Fi ESP8266 dan data backend secara paralel
            const [statsRes, isWifiDirect] = await Promise.all([
                fetch('{{ route('admin.api.stats') }}').then(r => r.json()).catch(() => null),
                pingEspWifi()
            ]);

            const badge = document.getElementById('esp-status-badge');
            if (badge) {
                let isConnected = isWifiDirect || (statsRes && statsRes.esp_online);

                if (statsRes && statsRes.status === 'success' && statsRes.devices) {
                    const dev = statsRes.devices.find(d => d.device_code === currentDevice || (currentDevice === 'Gate-01' && (d.device_code === 'Gate-01' || d.device_code === 'GATE_MASUK_01')));
                    if (dev) {
                        const now = new Date((statsRes.server_time || '').replace(' ', 'T')).getTime();
                        const lastSeen = dev.last_seen ? new Date(dev.last_seen.replace(' ', 'T')).getTime() : 0;
                        const diffSec = Math.max(0, Math.round((now - lastSeen) / 1000));
                        if (isConnected || (lastSeen > 0 && diffSec <= 25)) {
                            isConnected = true;
                        }
                    }
                }

                if (isConnected) {
                    badge.className = 'px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-300 flex items-center gap-1.5';
                    badge.innerHTML = `<span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block animate-pulse"></span><span>Terhubung ke ESP8266</span>`;
                } else {
                    badge.className = 'px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-50 text-rose-700 border border-rose-300 flex items-center gap-1.5';
                    badge.innerHTML = `<span class="w-1.5 h-1.5 rounded-full bg-rose-500 inline-block"></span><span>Tidak Terhubung ke ESP8266</span>`;
                }
            }

            if (!statsRes || statsRes.status !== 'success') return;
            const data = statsRes;

            // Update Status Parkir Real-Time di Kolom Kanan
            if (data.active_parked_list !== undefined) {
                updateSideParkingStatus(data.active_parked_list, data.metrics ? data.metrics.parked_count : data.active_parked_list.length);
            }

            if (data.recent_logs && data.recent_logs.length > 0) {
                const latestLog = data.recent_logs[0];
                const currentId = parseInt(latestLog.id);

                if (!isHardwarePollerReady) {
                    lastProcessedLogId = currentId;
                    isHardwarePollerReady = true;
                    return;
                }

                if (currentId > lastProcessedLogId) {
                    lastProcessedLogId = currentId;

                    if (latestLog.access_method === 'web_admin') {
                        return;
                    }

                    const notes = (latestLog.notes || '').toLowerCase();
                    const isTapOut = notes.includes('tap-out') || notes.includes('dipotong') || notes.includes('selesai');
                    const isTicketLog = (latestLog.access_method === 'ticket') || notes.includes('karcis') || notes.includes('tiket');
                    const isTicketEntry = isTicketLog && !isTapOut;

                    if (latestLog.status === 'granted') {
                        if (isTicketEntry) {
                            // PENGENDARA MEMENCET TOMBOL KARCIS MASUK
                            console.log('[AUTO-PRINT] Terdeteksi tiket karcis masuk pada log ID:', currentId);
                            showToast('Karcis Masuk Dicetak!', 'Silakan ambil tiket karcis Anda. Palang akan terbuka sebentar lagi...', 'info');

                            // Status visual: Lampu kuning (Menunggu pengendara ambil karcis)
                            setTrafficLight('yellow');
                            const badge = document.getElementById('gate-state-badge');
                            if (badge) {
                                badge.className = 'px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-50 text-amber-600 border border-amber-200 animate-pulse';
                                badge.textContent = 'AMBIL TIKET...';
                            }

                            // PICU AUTO-PRINT TIKET KARCIS MASUK KE PRINTER EPPOS EP589 V2 / BROWSER (HANYA SEKALI)
                            fetch('{{ url("/receipt/latest-ticket/escpos") }}')
                                .then(r => r.json())
                                .then(d => {
                                    if (d && d.status === 'success' && d.raw_text) {
                                        if (d.transaction_id && printedTicketTrxIds.has(d.transaction_id)) {
                                            console.log('[AUTO-PRINT] Tiket #', d.ticket_code, 'sudah dicetak, lewati duplicate print.');
                                        } else {
                                            console.log('[AUTO-PRINT] Memulai auto-print tiket masuk karcis #', d.ticket_code);
                                            autoPrintTicketEntry(d.ticket_code, d.raw_text, d.transaction_id);
                                        }

                                        // Buka palang animasi hanya jika belum dibuka oleh simulator
                                        if (d.transaction_id && !openedTicketGates.has(d.transaction_id)) {
                                            openedTicketGates.add(d.transaction_id);
                                            setTimeout(() => {
                                                openBarrierGate(8000);
                                            }, 2500);
                                        }
                                    }
                                }).catch(err => {
                                    console.error('[AUTO-PRINT] Gagal fetch data tiket karcis:', err);
                                });

                        } else {
                            if (latestLog.device_code === currentDevice || isTapOut) {
                                showToast('Akses Hardware Diterima', latestLog.notes || 'Palang fisik dibuka!', 'success');
                                openBarrierGate(5000);
                            }

                            // JIKA TAP-OUT KARTU MEMBER: PICU AUTO-PRINT KE PRINTER EPPOS EP589 V2! (Karcis tanpa kartu tidak mencetak struk keluar)
                            if (isTapOut && !isTicketLog) {
                                console.log('[AUTO-PRINT] Terdeteksi tap-out kartu member pada log ID:', currentId);
                                fetch('{{ url("/receipt/latest/escpos") }}')
                                    .then(r => r.json())
                                    .then(d => {
                                        if (d && d.status === 'success' && d.receipt) {
                                            console.log('[AUTO-PRINT] Memulai auto-print untuk transaksi ID:', d.receipt.id);
                                            autoPrintReceipt(d.receipt.id, d.raw_text);
                                        }
                                    }).catch(err => {
                                        console.error('[AUTO-PRINT] Gagal fetch data struk:', err);
                                    });
                            }
                        }
                    } else if (latestLog.device_code === currentDevice) {
                        showToast('Akses Hardware Ditolak', latestLog.notes || 'Akses ditolak pada perangkat fisik!', 'error');
                        blinkRedLed();
                    }
                }
            }
        } catch (e) {
            // Ignored when offline
        }
    }
    setInterval(checkHardwareSync, 1500);
    checkHardwareSync();

    async function directOpenHardware() {
        try {
            const res = await fetch('http://192.168.4.1/open');
            const data = await res.json();
            if (data.status === 'success') {
                showToast('Palang Fisik Terbuka', 'Perintah diterima langsung oleh ESP8266!', 'success');
                openBarrierGate(data.duration_ms || 8000);
            }
        } catch (err) {
            triggerEmergencySimulator();
        }
    }

    function triggerEmergencySimulator() {
        openEmergencyModal(currentDevice);
    }

    // Simulasi Pengendara Memencet Tombol Karcis Tiket Masuk
    async function triggerTicketSimulator() {
        const btn = document.getElementById('btn-ticket-simulator');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin text-xs"></i><span>Mencetak Tiket...</span>`;
        }

        try {
            const res = await fetch('{{ route('admin.monitoring.ticket') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    device_code: currentDevice || 'GATE_MASUK_01',
                    action: 'ticket'
                })
            });

            const data = await res.json();
            if (data.status === 'success') {
                if (typeof showToast === 'function') {
                    showToast('Tiket Karcis Dicetak!', 'Silakan ambil tiket karcis Anda. Palang akan terbuka sebentar lagi...', 'info');
                } else {
                    alert('✅ ' + data.message);
                }

                // Tampilkan popup & picu auto-print karcis tiket masuk (catat ID agar tidak dicetak ulang oleh hardware sync)
                if (data.data && data.data.ticket_text) {
                    if (data.data.transaction_id) {
                        printedTicketTrxIds.add(data.data.transaction_id);
                        openedTicketGates.add(data.data.transaction_id);
                    }
                    autoPrintTicketEntry(data.data.ticket_code, data.data.ticket_text, data.data.transaction_id);
                }

                // Status visual: Lampu kuning (Menunggu pengendara ambil karcis)
                setTrafficLight('yellow');
                const badge = document.getElementById('gate-state-badge');
                if (badge) {
                    badge.className = 'px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-50 text-amber-600 border border-amber-200 animate-pulse';
                    badge.textContent = 'AMBIL TIKET...';
                }

                // Beri jeda 2.5 detik untuk mengambil tiket karcis sebelum palang dibuka selama 8 detik
                setTimeout(() => {
                    openBarrierGate(8000);
                }, 2500);
            } else {
                if (typeof showToast === 'function') {
                    showToast('Gagal Cetak Tiket', data.message || 'Tiket ditolak!', 'error');
                } else {
                    alert('❌ Gagal: ' + data.message);
                }
                blinkRedLed();
            }
        } catch (e) {
            console.error('Error triggerTicketSimulator:', e);
            alert('Gagal memproses tiket karcis: ' + e.message);
            blinkRedLed();
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = `<i class="fa-solid fa-ticket text-xs"></i><span>Pencet Tombol Karcis Masuk</span>`;
            }
        }
    }

    // Checkout Karcis Tiket melalui input kode atau barcode dengan Anti-Spam
    let isProcessingTicketCheckout = false;
    async function checkoutTicketByInput() {
        if (isProcessingTicketCheckout) {
            if (typeof showToast === 'function') {
                showToast('Mohon Menunggu', 'Sedang memproses penyelesaian karcis sebelumnya...', 'warning');
            }
            return;
        }

        const input = document.getElementById('ticket-checkout-input');
        const btn = document.getElementById('btn-checkout-ticket-input');
        if (!input) return;
        const code = input.value.trim().toUpperCase();
        if (!code) {
            alert('Silakan scan barcode atau ketik kode karcis tiket (Contoh: TKT-2610...)');
            input.focus();
            return;
        }

        isProcessingTicketCheckout = true;
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin text-xs"></i><span>Menunggu...</span>`;
        }
        if (input) input.disabled = true;

        try {
            const res = await fetch('{{ url('/api/scan') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    device_code: 'GATE_KELUAR_01',
                    scan_uid: code
                })
            });

            const data = await res.json();
            if (data.status === 'success') {
                if (typeof showToast === 'function') {
                    showToast('Karcis Selesai!', data.message, 'success');
                } else {
                    alert('✅ ' + data.message);
                }

                openBarrierGate(data.duration_ms || 5000);
                input.value = '';
                checkHardwareSync();
                // Pengendara karcis tanpa kartu tidak mencetak struk keluar / total lama parkir
            } else {
                if (typeof showToast === 'function') {
                    showToast('Akses Ditolak', data.message, 'error');
                } else {
                    alert('❌ ' + data.message);
                }
                blinkRedLed();
            }
        } catch (e) {
            console.error('Error checkoutTicketByInput:', e);
            alert('Gagal checkout tiket: ' + e.message);
        } finally {
            setTimeout(() => {
                isProcessingTicketCheckout = false;
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = `<i class="fa-solid fa-arrow-right-from-bracket"></i><span>Checkout Karcis</span>`;
                }
                if (input) {
                    input.disabled = false;
                    input.focus();
                }
            }, 1500);
        }
    }

    // Tampilkan modal karcis masuk
    function showTicketModal(ticketText, ticketCode, receiptUrl) {
        currentReceiptRawText = ticketText;
        const previewEl = document.getElementById('receipt-preview-content');
        if (previewEl) previewEl.textContent = ticketText;

        const modal = document.getElementById('receipt-modal');
        if (modal) modal.classList.remove('hidden');

        const openLink = document.getElementById('btn-open-receipt-link');
        if (openLink && receiptUrl) openLink.href = receiptUrl;
    }

    // Fungsi Pengendali Traffic Light 3 Warna (Red, Yellow, Green)
    function setTrafficLight(color) {
        const ledRed = document.getElementById('led-red');
        const ledYellow = document.getElementById('led-yellow');
        const ledGreen = document.getElementById('led-green');
        if (!ledRed || !ledYellow || !ledGreen) return;

        // Reset semua lampu ke kondisi mati / redup
        ledRed.style.backgroundColor = '#450a0a';
        ledRed.style.boxShadow = 'none';
        ledRed.style.borderColor = '#7f1d1d';
        ledRed.style.opacity = '0.35';

        ledYellow.style.backgroundColor = '#451a03';
        ledYellow.style.boxShadow = 'none';
        ledYellow.style.borderColor = '#78350f';
        ledYellow.style.opacity = '0.35';

        ledGreen.style.backgroundColor = '#022c22';
        ledGreen.style.boxShadow = 'none';
        ledGreen.style.borderColor = '#064e3b';
        ledGreen.style.opacity = '0.35';

        if (color === 'red') {
            ledRed.style.backgroundColor = '#ef4444';
            ledRed.style.boxShadow = '0 0 16px #ef4444, 0 0 28px rgba(239, 68, 68, 0.7)';
            ledRed.style.borderColor = '#fca5a5';
            ledRed.style.opacity = '1';
        } else if (color === 'yellow') {
            ledYellow.style.backgroundColor = '#FFD51E';
            ledYellow.style.boxShadow = '0 0 16px #FFD51E, 0 0 28px rgba(255, 213, 30, 0.85)';
            ledYellow.style.borderColor = '#fef08a';
            ledYellow.style.opacity = '1';
        } else if (color === 'green') {
            ledGreen.style.backgroundColor = '#10b981';
            ledGreen.style.boxShadow = '0 0 16px #10b981, 0 0 28px rgba(16, 185, 129, 0.85)';
            ledGreen.style.borderColor = '#6ee7b7';
            ledGreen.style.opacity = '1';
        }
    }

    function clearAllGateTimers() {
        if (gateTimer) clearTimeout(gateTimer);
        if (openPhase1Timer) clearTimeout(openPhase1Timer);
        if (openPhase2Timer) clearTimeout(openPhase2Timer);
        if (countdownInterval) clearInterval(countdownInterval);
        gateTimer = null;
        openPhase1Timer = null;
        openPhase2Timer = null;
        countdownInterval = null;
    }

    function openBarrierGate(durationMs = 5000) {
        clearAllGateTimers();

        const arm = document.getElementById('gate-arm');
        const badge = document.getElementById('gate-state-badge');
        const timerBox = document.getElementById('timer-box');
        const timerCount = document.getElementById('timer-count');

        if (isGateCurrentlyOpen) {
            setTrafficLight('green');
            if (badge) {
                badge.className = 'px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-600 border border-emerald-200';
                badge.textContent = 'TERBUKA (90°)';
            }
            if (arm) arm.style.transform = 'rotate(-90deg)';
            if (timerBox && timerCount) {
                timerBox.classList.remove('hidden');
                let remaining = Math.round(durationMs / 1000);
                timerCount.textContent = remaining;
                countdownInterval = setInterval(() => {
                    remaining--;
                    if (remaining >= 0) {
                        timerCount.textContent = remaining;
                    } else {
                        clearInterval(countdownInterval);
                    }
                }, 1000);
            }
            gateTimer = setTimeout(() => {
                closeBarrierGate();
            }, durationMs);
            return;
        }

        // FASE 1: PERINGATAN SEBELUM BERGERAK
        setTrafficLight('yellow');
        playWebBuzzer(2, 'high');
        if (badge) {
            badge.className = 'px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-50 text-amber-700 border border-amber-300';
            badge.textContent = 'PERSIAPAN BUKA...';
        }

        openPhase1Timer = setTimeout(() => {
            if (badge) {
                badge.textContent = 'MEMBUKA (NAIK)...';
            }
            if (arm) arm.style.transform = 'rotate(-90deg)';

            // FASE 2: TERBUKA PENUH
            openPhase2Timer = setTimeout(() => {
                isGateCurrentlyOpen = true;
                setTrafficLight('green');
                if (badge) {
                    badge.className = 'px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-600 border border-emerald-200';
                    badge.textContent = 'TERBUKA (90°)';
                }

                if (timerBox && timerCount) {
                    timerBox.classList.remove('hidden');
                    let remaining = Math.round(durationMs / 1000);
                    timerCount.textContent = remaining;

                    countdownInterval = setInterval(() => {
                        remaining--;
                        if (remaining >= 0) {
                            timerCount.textContent = remaining;
                        } else {
                            clearInterval(countdownInterval);
                        }
                    }, 1000);
                }

                gateTimer = setTimeout(() => {
                    closeBarrierGate();
                }, durationMs);

            }, 1000);
        }, 1020);
    }

    function closeBarrierGate() {
        clearAllGateTimers();

        const arm = document.getElementById('gate-arm');
        const badge = document.getElementById('gate-state-badge');
        const timerBox = document.getElementById('timer-box');

        // FASE 1: PERINGATAN SEBELUM MENUTUP
        setTrafficLight('yellow');
        playWebBuzzer(1, 'medium');
        if (badge) {
            badge.className = 'px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-50 text-amber-700 border border-amber-300';
            badge.textContent = 'PERSIAPAN TUTUP...';
        }

        openPhase1Timer = setTimeout(() => {
            if (badge) {
                badge.textContent = 'MENUTUP (TURUN)...';
            }
            if (arm) arm.style.transform = 'rotate(0deg)';

            // FASE 2: TERTUTUP PENUH
            openPhase2Timer = setTimeout(() => {
                isGateCurrentlyOpen = false;
                setTrafficLight('red');
                if (badge) {
                    badge.className = 'px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-50 text-rose-600 border border-rose-200';
                    badge.textContent = 'TERTUTUP (0°)';
                }

                if (timerBox) timerBox.classList.add('hidden');
            }, 1000);
        }, 1000);
    }

    function blinkRedLed() {
        setTrafficLight('none');
        const ledRed = document.getElementById('led-red');
        if (!ledRed) return;
        let count = 0;
        const blinker = setInterval(() => {
            if (count % 2 === 0) {
                ledRed.style.backgroundColor = '#450a0a';
                ledRed.style.boxShadow = 'none';
                ledRed.style.opacity = '0.3';
            } else {
                ledRed.style.backgroundColor = '#ef4444';
                ledRed.style.boxShadow = '0 0 20px #ef4444, 0 0 30px rgba(239, 68, 68, 0.8)';
                ledRed.style.borderColor = '#fca5a5';
                ledRed.style.opacity = '1';
            }
            count++;
            if (count > 5) {
                clearInterval(blinker);
                setTrafficLight('red');
            }
        }, 120);
    }

    function blinkYellowLed() {
        setTrafficLight('none');
        const ledYellow = document.getElementById('led-yellow');
        if (!ledYellow) return;
        let count = 0;
        const blinker = setInterval(() => {
            if (count % 2 === 0) {
                ledYellow.style.backgroundColor = '#451a03';
                ledYellow.style.boxShadow = 'none';
                ledYellow.style.opacity = '0.3';
            } else {
                ledYellow.style.backgroundColor = '#FFD51E';
                ledYellow.style.boxShadow = '0 0 20px #FFD51E, 0 0 30px rgba(255, 213, 30, 0.9)';
                ledYellow.style.borderColor = '#fef08a';
                ledYellow.style.opacity = '1';
            }
            count++;
            if (count > 5) {
                clearInterval(blinker);
                setTrafficLight('red');
            }
        }, 150);
    }

    function updateSideParkingStatus(list, count) {
        const totalCap = 20;
        const parked = parseInt(count) || 0;
        const available = Math.max(0, totalCap - parked);
        const pct = Math.min(100, Math.round((parked / totalCap) * 100));

        let countLt1 = 0;
        let countLt2 = 0;
        if (Array.isArray(list)) {
            list.forEach(v => {
                if (parseInt(v.parking_floor) === 2) {
                    countLt2++;
                } else {
                    countLt1++;
                }
            });
        }
        const availLt1 = Math.max(0, 10 - countLt1);
        const availLt2 = Math.max(0, 10 - countLt2);

        const badge = document.getElementById('side-status-badge');
        const badgeText = document.getElementById('side-status-badge-text');
        const parkedCountEl = document.getElementById('side-parked-count');
        const progressBar = document.getElementById('side-progress-bar');
        const availText = document.getElementById('side-available-text');
        const occPercent = document.getElementById('side-occupancy-percent');
        const emptyState = document.getElementById('side-empty-state');
        const listContainer = document.getElementById('side-vehicles-list');

        const lt1CountEl = document.getElementById('side-lt1-parked');
        const lt1AvailEl = document.getElementById('side-lt1-avail');
        const lt1Badge = document.getElementById('floor-badge-lt1');
        const lt2CountEl = document.getElementById('side-lt2-parked');
        const lt2AvailEl = document.getElementById('side-lt2-avail');
        const lt2Badge = document.getElementById('floor-badge-lt2');

        if (lt1CountEl) lt1CountEl.textContent = countLt1;
        if (lt1AvailEl) lt1AvailEl.textContent = availLt1;
        if (lt1Badge) {
            lt1Badge.textContent = countLt1 >= 10 ? 'PENUH' : 'TERSEDIA';
            lt1Badge.className = `px-1.5 py-0.2 rounded text-[9px] font-mono ${countLt1 >= 10 ? 'bg-rose-600 text-white' : 'bg-[#00719C] text-white'}`;
        }

        if (lt2CountEl) lt2CountEl.textContent = countLt2;
        if (lt2AvailEl) lt2AvailEl.textContent = availLt2;
        if (lt2Badge) {
            lt2Badge.textContent = countLt2 >= 10 ? 'PENUH' : 'TERSEDIA';
            lt2Badge.className = `px-1.5 py-0.2 rounded text-[9px] font-mono ${countLt2 >= 10 ? 'bg-rose-600 text-white' : 'bg-amber-600 text-white'}`;
        }

        if (parkedCountEl) parkedCountEl.textContent = parked;
        if (occPercent) occPercent.textContent = pct + '% Terisi';
        if (availText) {
            availText.innerHTML = `<svg class="w-3.5 h-3.5 text-emerald-600 inline mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>${available} Slot Tersedia`;
        }

        if (progressBar) {
            progressBar.style.width = pct + '%';
            if (parked >= totalCap) {
                progressBar.className = 'h-full rounded-full transition-all duration-500 bg-rose-500';
            } else if (parked > 0) {
                progressBar.className = 'h-full rounded-full transition-all duration-500 bg-[#00719C]';
            } else {
                progressBar.className = 'h-full rounded-full transition-all duration-500 bg-emerald-500';
            }
        }

        if (badge && badgeText) {
            badge.className = 'px-2.5 py-0.5 rounded text-[11px] font-semibold clean-border text-black flex items-center gap-1.5';
            if (parked > 0) {
                badge.innerHTML = `<span class="status-dot-active"></span><span id="side-status-badge-text">ADA KENDARAAN (${parked})</span>`;
            } else {
                badge.innerHTML = `<span id="side-status-badge-text">PARKIR KOSONG</span>`;
            }
        }

        if (parked === 0) {
            if (emptyState) {
                emptyState.classList.remove('hidden');
                emptyState.classList.add('flex');
            }
            if (listContainer) {
                listContainer.classList.add('hidden');
                listContainer.innerHTML = '';
            }
        } else {
            if (emptyState) {
                emptyState.classList.add('hidden');
                emptyState.classList.remove('flex');
            }
            if (listContainer) {
                listContainer.classList.remove('hidden');
                let html = '';
                list.forEach(v => {
                    const timeStr = v.tap_in_time ? v.tap_in_time.substring(11, 16) : '-';
                    const isTicket = v.is_ticket || Boolean(v.ticket_code);
                    const scanId = isTicket ? v.ticket_code : (v.uid || '');
                    const nameEscaped = (v.user_name || (isTicket ? 'Pengendara Umum' : 'Pengendara')).replace(/'/g, "\\'");
                    const floorNum = parseInt(v.parking_floor) || 1;
                    const floorBadge = `<span class="text-[9px] px-1.5 py-0.2 rounded font-bold clean-border ${floorNum === 2 ? 'bg-amber-100 text-amber-900 border-amber-300' : 'bg-sky-100 text-[#00719C] border-sky-300'}">LT ${floorNum}</span>`;
                    html += `
                        <div class="p-3 rounded-md bg-[#FFFFFF] clean-border hover:border-[#00719C] transition flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-md ${isTicket ? 'bg-amber-600' : 'bg-[#00719C]'} text-white flex items-center justify-center text-xs flex-shrink-0">
                                    ${isTicket ? '<i class="fa-solid fa-ticket text-xs"></i>' : '<svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/></svg>'}
                                </div>
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        ${isTicket 
                                            ? `<span class="text-xs font-bold font-mono text-amber-700">${v.ticket_code || scanId}</span><span class="text-[9px] px-1.5 py-0.2 rounded font-semibold clean-border bg-amber-50 text-amber-800 border-amber-300">TIKET</span>`
                                            : `<span class="text-xs font-bold font-mono text-black">${v.license_plate || 'NO-PLAT'}</span><span class="text-[9px] px-1.5 py-0.2 rounded font-semibold clean-border text-black">MEMBER</span>`
                                        }
                                        ${floorBadge}
                                    </div>
                                    <div class="text-[11px] font-medium text-black/70 mt-0.5">${v.user_name || (isTicket ? 'Pengendara Umum (Karcis)' : 'Pengendara')}</div>
                                    <div class="font-mono text-[10px] text-black/50 mt-0.5">
                                        Masuk: ${timeStr} &bull; <span class="text-[#00719C] font-semibold">${v.duration_minutes || 1} mnt</span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right flex flex-col items-end gap-1.5">
                                <div>
                                    <span class="text-xs font-bold text-black font-mono">${v.estimated_fee_formatted || 'Rp 2.000'}</span>
                                    <span class="text-[9px] font-medium text-black/50 block">${v.billed_hours || 1} Jam Parkir</span>
                                    ${isTicket ? '<span class="text-[9px] text-amber-600 font-semibold block">Bayar Tunai</span>' : (v.user_balance_formatted ? `<span class="text-[9px] ${v.is_balance_sufficient ? 'text-emerald-600' : 'text-rose-600'} font-semibold block">Saldo: ${v.user_balance_formatted}</span>` : '')}
                                </div>
                                <button type="button" 
                                        onclick="checkoutManualAdmin('${scanId}', '${nameEscaped}', this)"
                                        title="Selesaikan parkir & bayar karcis/kartu"
                                        class="btn-selesai-checkout btn-accent inline-flex items-center gap-1 px-2 py-1 text-[10px] text-black cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                                    <svg class="w-3 h-3 text-black" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                    <span>Selesaikan</span>
                                </button>
                            </div>
                        </div>
                    `;
                });
                listContainer.innerHTML = html;
            }
        }
    }

    // Checkout Manual jika Gerbang Keluar Error / Macet (Anti-Spam & Menunggu)
    let isProcessingCheckout = false;
    async function checkoutManualAdmin(uid, userName, btnElement = null) {
        if (isProcessingCheckout) {
            if (typeof showToast === 'function') {
                showToast('Mohon Menunggu', 'Sedang memproses penyelesaian parkir. Mohon tunggu sejenak...', 'warning');
            }
            return;
        }

        if (!confirm(`Selesaikan parkir untuk "${userName}" (UID: ${uid}) secara manual?\n\nGunakan opsi ini jika gerbang keluar atau sensor RFID bermasalah. Sistem akan menyelesaikan transaksi, mengosongkan slot parkir, dan membuka palang keluar.`)) {
            return;
        }

        isProcessingCheckout = true;
        const allBtns = document.querySelectorAll('.btn-selesai-checkout');
        allBtns.forEach(b => b.disabled = true);

        let origHtml = '';
        if (btnElement) {
            origHtml = btnElement.innerHTML;
            btnElement.innerHTML = `<i class="fa-solid fa-spinner fa-spin text-[10px]"></i><span>Menunggu...</span>`;
        }

        try {
            const res = await fetch('{{ url('/api/scan') }}', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    device_code: 'GATE_KELUAR_01',
                    scan_uid: uid
                })
            });
            const data = await res.json();
            if (data.status === 'success') {
                if (typeof showToast === 'function') {
                    showToast('Parkir Diselesaikan!', `${data.message} - Total Biaya: ${data.data?.total_fee_formatted || ''}`, 'success');
                } else {
                    alert(`✅ Parkir Berhasil Diselesaikan!\n\n${data.message}\nTotal Biaya: ${data.data?.total_fee_formatted || '-'}`);
                }
                if (typeof playWebBuzzer === 'function') playWebBuzzer(2);
                checkHardwareSync();

                // BUKA PALANG KELUAR OTOMATIS (Animasi Visual & Timer Gerbang)
                if (data.action === 'OPEN' || data.flow === 'TAP_OUT_TICKET' || data.flow === 'TAP_OUT_TICKET_COMPLETED') {
                    if (typeof openBarrierGate === 'function') {
                        openBarrierGate(data.duration_ms || 5000);
                    }
                }

                // Cetak otomatis Struk ke Eppos EP589 V2 HANYA jika kartu member (karcis tanpa kartu tidak mencetak struk keluar)
                if (data.data && data.data.receipt_id && !data.data.ticket_code && data.flow !== 'TAP_OUT_TICKET') {
                    autoPrintReceipt(data.data.receipt_id);
                }
            } else {
                if (typeof showToast === 'function') {
                    showToast('Gagal Selesaikan Parkir', data.message, 'error');
                } else {
                    alert(`❌ Gagal: ${data.message}`);
                }
                if (typeof playWebBuzzer === 'function') playWebBuzzer(3, 'low');
            }
        } catch (err) {
            console.error('Error checkout manual:', err);
            if (typeof showToast === 'function') {
                showToast('Error', err.message, 'error');
            } else {
                alert(`Error: ${err.message}`);
            }
        } finally {
            setTimeout(() => {
                isProcessingCheckout = false;
                allBtns.forEach(b => b.disabled = false);
                if (btnElement && origHtml) {
                    btnElement.innerHTML = origHtml;
                }
                checkHardwareSync();
            }, 1500);
        }
    }

    // ==========================================
    // INTEGRASI AUTO-PRINT THERMAL EPPOS EP589 V2 (58mm)
    // ==========================================
    let currentReceiptTrxId = null;
    let currentReceiptRawText = '';
    let btDevice = null;
    let btPrinterChar = null;

    // Helper penulisan byte BLE yang kompatibel dengan seluruh chip thermal
    async function writeBleData(characteristic, data) {
        if (!characteristic || !data) return;
        try {
            if (characteristic.writeValueWithoutResponse) {
                await characteristic.writeValueWithoutResponse(data);
            } else if (characteristic.writeValue) {
                await characteristic.writeValue(data);
            } else if (characteristic.writeValueWithResponse) {
                await characteristic.writeValueWithResponse(data);
            }
        } catch (e1) {
            console.warn('Fallback writeBleData:', e1);
            if (characteristic.writeValue) {
                await characteristic.writeValue(data);
            } else if (characteristic.writeValueWithResponse) {
                await characteristic.writeValueWithResponse(data);
            }
        }
    }

    // 1. Hubungkan Bluetooth Eppos EP589 V2
    async function connectBluetoothPrinter() {
        const label = document.getElementById('bt-quick-label');
        const btn = document.getElementById('btn-quick-bt-connect');

        if (!navigator.bluetooth) {
            alert('Browser ini belum mendukung Web Bluetooth. Untuk auto-print via Bluetooth, gunakan Google Chrome atau Microsoft Edge.\n\nJika menggunakan kabel USB, Anda tetap bisa auto-print menggunakan driver POS-58 Windows.');
            return;
        }

        try {
            label.innerText = 'Mencari...';
            btn.classList.add('animate-pulse');

            btDevice = await navigator.bluetooth.requestDevice({
                acceptAllDevices: true,
                optionalServices: [
                    '000018f0-0000-1000-8000-00805f9b34fb', // Standard Print Service
                    '0000ffe0-0000-1000-8000-00805f9b34fb', // Common BLE Serial (CC2540/HM-10)
                    '0000ff00-0000-1000-8000-00805f9b34fb', // Common POS-58
                    '0000fee7-0000-1000-8000-00805f9b34fb', // Tencent/Goojprt
                    '0000ae00-0000-1000-8000-00805f9b34fb',
                    '0000af00-0000-1000-8000-00805f9b34fb',
                    'e7810a71-73ae-499d-8c15-faa9aef0c3f2',
                    '49535343-fe7d-4ae5-8fa9-9fafd205e455'  // ISSC
                ]
            });

            label.innerText = 'Connecting...';
            const server = await btDevice.gatt.connect();

            // Cari characteristic tulis ESC/POS
            const services = await server.getPrimaryServices();
            btPrinterChar = null;
            for (const s of services) {
                try {
                    const chars = await s.getCharacteristics();
                    for (const c of chars) {
                        if (c.properties.writeWithoutResponse || c.properties.write) {
                            btPrinterChar = c;
                            break;
                        }
                    }
                } catch (e) {}
                if (btPrinterChar) break;
            }

            if (!btPrinterChar) throw new Error('Karakteristik Bluetooth Write tidak ditemukan pada perangkat printer ini.');

            label.innerText = 'EP589 Terhubung ✓';
            btn.className = 'px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold flex items-center gap-1.5 shadow transition cursor-pointer';
            btn.classList.remove('animate-pulse');

            if (typeof showToast === 'function') {
                showToast('Printer Terhubung!', 'Eppos EP589 V2 siap auto-print otomatis setiap tap-out', 'success');
            } else {
                alert('✅ Printer Eppos EP589 V2 Terhubung! Setiap tap-out akan langsung mencetak struk secara otomatis.');
            }

            // Dengarkan jika putus koneksi untuk auto-reconnect
            btDevice.addEventListener('gattserverdisconnected', () => {
                btPrinterChar = null;
                label.innerText = 'Connect EP589';
                btn.className = 'px-3 py-1.5 rounded-xl bg-[#00719C] hover:bg-sky-700 text-white text-[11px] font-bold flex items-center gap-1.5 shadow transition cursor-pointer';
                if (typeof showToast === 'function') {
                    showToast('Printer Terputus', 'Bluetooth Eppos EP589 terputus. Silakan hubungkan kembali.', 'error');
                }
            });

        } catch (err) {
            console.error(err);
            label.innerText = 'Connect EP589';
            btn.classList.remove('animate-pulse');
            if (err.name !== 'NotFoundError') {
                alert('Gagal menghubungkan Bluetooth: ' + err.message);
            }
        }
    }

    // Tes Cetak Langsung ke Bluetooth Eppos EP589 V2
    async function testPrintBluetooth() {
        if (!btPrinterChar) {
            const connectNow = confirm('Printer Eppos EP589 V2 belum terhubung ke browser ini.\n\nApakah Anda ingin menghubungkannya sekarang via Bluetooth?');
            if (connectNow) {
                await connectBluetoothPrinter();
            }
            if (!btPrinterChar) return;
        }

        const nowStr = new Date().toLocaleString('id-ID');
        const testText = 
"================================\n" +
"     TEST PRINTER EPPOS EP589   \n" +
"     KONEKSI BLUETOOTH OK!      \n" +
"================================\n" +
"Waktu : " + nowStr + "\n" +
"Status: SIAP AUTO-PRINT TAP-OUT\n" +
"Sistem: E-Parkir Laravel 12     \n" +
"================================\n" +
"   Koneksi Bluetooth Berhasil!  \n" +
"================================\n\n\n\n";

        try {
            if (typeof showToast === 'function') {
                showToast('Mencetak Tes...', 'Mengirim data ke Eppos EP589 V2...', 'info');
            }
            await sendEscposToBluetooth(testText);
            if (typeof showToast === 'function') {
                showToast('Tes Cetak Berhasil!', 'Struk tes berhasil keluar dari printer!', 'success');
            } else {
                alert('✅ Tes Cetak Berhasil!\n\nPrinter Eppos EP589 V2 merespons dan berhasil mencetak.');
            }
        } catch (e) {
            alert('Gagal tes cetak: ' + e.message);
        }
    }

    // Set pelacak ID transaksi yang sudah dicetak agar TIDAK PERNAH ter-print dobel (2x)
    const printedTicketTrxIds = new Set();
    const printedReceiptTrxIds = new Set();
    const openedTicketGates = new Set();
    let lastIframePrintedTrxId = null;

    // 1.5 Fungsi Auto-Print Khusus Karcis Tiket Masuk (Pengendara Tanpa Kartu)
    async function autoPrintTicketEntry(ticketCode, rawText, trxId) {
        if (trxId && printedTicketTrxIds.has(trxId)) {
            console.log('[AUTO-PRINT] Tiket #' + ticketCode + ' (TRX:' + trxId + ') sudah pernah dicetak. Melewati agar tidak ter-print dobel.');
            return;
        }
        if (trxId) printedTicketTrxIds.add(trxId);

        currentReceiptTrxId = trxId;
        currentReceiptRawText = rawText;
        const isAutoPrintActive = document.getElementById('autoprint-toggle')?.checked ?? true;

        const previewEl = document.getElementById('receipt-preview-content');
        if (previewEl && currentReceiptRawText) previewEl.textContent = currentReceiptRawText;

        // A. JIKA BLUETOOTH PRINTER TERHUBUNG: LANGSUNG KIRIM ESC/POS WIRELESS TANPA MERUSAK TAMPILAN
        if (btPrinterChar && isAutoPrintActive) {
            if (typeof showToast === 'function') {
                showToast('Mencetak Karcis Masuk...', 'Mengirim data ke Eppos EP589 V2...', 'info');
            }
            await sendEscposToBluetooth(currentReceiptRawText);
            if (typeof showToast === 'function') {
                showToast('Karcis Masuk Dicetak!', 'Tiket #' + ticketCode + ' berhasil dicetak ke EP589 V2', 'success');
            }
            return;
        }

        // B. JIKA BLUETOOTH BELUM TERHUBUNG: TAMPILKAN TOAST INFO (TIDAK MEMBEKUKAN ATAU MEREDUPKAN LAYAR)
        if (typeof showToast === 'function') {
            showToast('Karcis Dicetak', 'Karcis #' + ticketCode + ' siap. Klik Selesaikan/Cetak jika ingin mencetak ulang.', 'info');
        }
    }

    // 2. Fungsi Utama Auto-Print Struk saat Tap-Out Terjadi
    async function autoPrintReceipt(trxId, preloadedRawText = null) {
        if (trxId && printedReceiptTrxIds.has(trxId)) {
            console.log('[AUTO-PRINT] Struk TRX #' + trxId + ' sudah pernah dicetak. Melewati agar tidak ter-print dobel.');
            return;
        }
        if (trxId) printedReceiptTrxIds.add(trxId);

        currentReceiptTrxId = trxId;
        const isAutoPrintActive = document.getElementById('autoprint-toggle')?.checked ?? true;

        try {
            if (preloadedRawText) {
                currentReceiptRawText = preloadedRawText;
            } else {
                const res = await fetch('{{ url("/receipt") }}/' + trxId + '/escpos');
                const data = await res.json();
                if (data.status === 'success') {
                    currentReceiptRawText = data.raw_text;
                }
            }

            const previewEl = document.getElementById('receipt-preview-content');
            if (previewEl && currentReceiptRawText) previewEl.textContent = currentReceiptRawText;

            // A. JIKA BLUETOOTH PRINTER TERHUBUNG: LANGSUNG KIRIM ESC/POS WIRELESS
            if (btPrinterChar && isAutoPrintActive) {
                if (typeof showToast === 'function') {
                    showToast('Mencetak Struk...', 'Mengirim data ke Eppos EP589 V2...', 'info');
                }
                await sendEscposToBluetooth(currentReceiptRawText);
                if (typeof showToast === 'function') {
                    showToast('Struk Dicetak!', 'Struk transaksi #' + trxId + ' berhasil dicetak ke Eppos EP589 V2', 'success');
                }
                return;
            }

            // B. JIKA BLUETOOTH BELUM AKTIF: TAMPILKAN NOTIFIKASI
            if (typeof showToast === 'function') {
                showToast('Transaksi Selesai', 'Struk transaksi #' + trxId + ' siap.', 'success');
            }

        } catch (e) {
            console.error('Error auto-print:', e);
        }
    }

    // Kirim chunk byte ke Bluetooth Eppos EP589 V2
    async function sendEscposToBluetooth(text) {
        if (!btPrinterChar || !text) {
            console.warn('sendEscposToBluetooth: printer not connected or text is empty');
            return;
        }
        try {
            const encoder = new TextEncoder();
            // ESC @ (Inisialisasi printer)
            const initCmd = new Uint8Array([0x1B, 0x40]);
            await writeBleData(btPrinterChar, initCmd);
            await new Promise(r => setTimeout(r, 40));

            const textBytes = encoder.encode(text);
            const chunkSize = 20; // BLE default safe MTU
            for (let i = 0; i < textBytes.length; i += chunkSize) {
                const chunk = textBytes.slice(i, i + chunkSize);
                await writeBleData(btPrinterChar, chunk);
                await new Promise(r => setTimeout(r, 20));
            }

            // Line feeds agar kertas keluar melewati gerigi pemotong
            const feedCmd = new Uint8Array([0x0A, 0x0A, 0x0A, 0x0A]);
            await writeBleData(btPrinterChar, feedCmd);
            console.log('sendEscposToBluetooth: Struk sukses dikirim ke EP589 V2!');
        } catch (bleErr) {
            console.error('sendEscposToBluetooth error:', bleErr);
            alert('Gagal mengirim ke printer Bluetooth: ' + bleErr.message);
        }
    }

    // Cetak otomatis via invisible iframe (Anti-Dobel Print)
    function printSilentlyViaIframe(trxId) {
        if (lastIframePrintedTrxId === trxId) {
            console.log('[SILENT-PRINT] Iframe TRX #' + trxId + ' sedang aktif / sudah dikirim ke antrean cetak.');
            return;
        }
        lastIframePrintedTrxId = trxId;

        const oldIframe = document.getElementById('silent-print-frame');
        if (oldIframe) oldIframe.remove();

        const iframe = document.createElement('iframe');
        iframe.id = 'silent-print-frame';
        iframe.style.position = 'fixed';
        iframe.style.right = '0';
        iframe.style.bottom = '0';
        iframe.style.width = '1px';
        iframe.style.height = '1px';
        iframe.style.opacity = '0.01';
        iframe.style.border = '0';
        iframe.src = '{{ url("/receipt") }}/' + trxId + '?autoprint=1';
        document.body.appendChild(iframe);

        setTimeout(() => {
            if (iframe && iframe.parentNode) iframe.parentNode.removeChild(iframe);
            if (lastIframePrintedTrxId === trxId) lastIframePrintedTrxId = null;
        }, 15000);
    }

    async function openReceiptModal(trxId) {
        currentReceiptTrxId = trxId;
        const modal = document.getElementById('receipt-modal');
        const previewEl = document.getElementById('receipt-preview-content');
        const linkEl = document.getElementById('btn-open-receipt-link');
        
        if (linkEl) linkEl.href = '{{ url("/receipt") }}/' + trxId;
        if (modal) modal.classList.remove('hidden');

        if (!currentReceiptRawText || currentReceiptTrxId !== trxId) {
            if (previewEl) previewEl.textContent = 'Memuat struk parkir #' + trxId + '...';
            try {
                const res = await fetch('{{ url("/receipt") }}/' + trxId + '/escpos');
                const data = await res.json();
                if (data.status === 'success') {
                    currentReceiptRawText = data.raw_text;
                    if (previewEl) previewEl.textContent = data.raw_text;
                }
            } catch (e) {
                if (previewEl) previewEl.textContent = 'Gagal memuat struk: ' + e.message;
            }
        } else {
            if (previewEl) previewEl.textContent = currentReceiptRawText;
        }
    }

    function closeReceiptModal() {
        const modal = document.getElementById('receipt-modal');
        if (modal) modal.classList.add('hidden');
    }

    function printReceiptViaBrowser() {
        if (!currentReceiptTrxId) return;
        printSilentlyViaIframe(currentReceiptTrxId);
    }

    async function printReceiptViaBluetooth() {
        if (!btPrinterChar) {
            await connectBluetoothPrinter();
            if (!btPrinterChar) return;
        }
        if (!currentReceiptRawText) {
            alert('Data struk belum siap.');
            return;
        }
        await sendEscposToBluetooth(currentReceiptRawText);
        alert('✅ Struk berhasil dicetak ke Eppos EP589 V2!');
    }
</script>
@endpush
