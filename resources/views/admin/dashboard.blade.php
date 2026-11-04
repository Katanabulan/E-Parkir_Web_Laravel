@extends('layouts.admin')

@section('title', 'Dashboard & Gate Monitoring')
@section('header_title', 'Dashboard & Gate')
@section('header_subtitle', 'Monitoring sistem palang pintu & transaksi parkir otomatis')

@section('content')
<!-- 1. STAT CARDS ROW: Clean-Cut 1px Borders, Teks Hitam & Icon Badges Biru/Putih -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
    <!-- Card 1: Kendaraan Parkir Saat Ini -->
    <div class="editorial-card p-6 bg-[#FFFFFF] flex flex-col justify-between">
        <div class="flex items-center justify-between">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-black/60">Sedang Terparkir</span>
            <!-- Icon with Blue Background & White Icon -->
            <div class="w-9 h-9 rounded-md bg-[#00719C] text-white flex items-center justify-center text-sm flex-shrink-0">
                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/></svg>
            </div>
        </div>
        <div class="mt-4 flex items-baseline gap-2">
            <span id="metric-parked-count" class="text-3xl font-bold text-black">{{ $parkedCount }}</span>
            <span class="text-xs font-medium text-black/60">/ 20 kendaraan</span>
        </div>
        <div class="mt-3 pt-3 clean-border-t text-xs text-black/70 flex items-center justify-between font-medium">
            <div class="flex items-center gap-1.5">
                <span class="status-dot-active"></span>
                <span>Real-time</span>
            </div>
            <div class="flex items-center gap-1 text-[11px] font-mono">
                <span class="px-1.5 py-0.5 rounded bg-sky-50 text-[#00719C] font-bold clean-border">LT1: <b id="dash-lt1">{{ $parkedLt1 }}</b>/10</span>
                <span class="px-1.5 py-0.5 rounded bg-amber-50 text-amber-800 font-bold clean-border">LT2: <b id="dash-lt2">{{ $parkedLt2 }}</b>/10</span>
            </div>
        </div>
    </div>

    <!-- Card 2: Saldo Kas Admin (Total Penerimaan Saldo Pengendara) -->
    <div class="editorial-card p-6 bg-[#FFFFFF] flex flex-col justify-between">
        <div class="flex items-center justify-between">
            <div>
                <span class="text-[11px] font-semibold uppercase tracking-wider text-black/60 block">Saldo Kas Admin</span>
                <span class="text-[9px] text-emerald-700 font-bold bg-emerald-50 px-1.5 py-0.5 rounded">Uang Masuk Dari Tap</span>
            </div>
            <!-- Icon with Blue Background & White Icon -->
            <div class="w-9 h-9 rounded-md bg-[#00719C] text-white flex items-center justify-center text-sm flex-shrink-0">
                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
            </div>
        </div>
        <div class="mt-4 flex items-baseline gap-2">
            <span id="metric-admin-cash" class="text-2xl lg:text-3xl font-bold text-black">Rp {{ number_format($totalAdminCash, 0, ',', '.') }}</span>
        </div>
        <div class="mt-3 pt-3 clean-border-t text-xs text-black/60 font-medium flex items-center justify-between">
            <span>Hari ini: <strong id="metric-today-revenue" class="text-black">Rp {{ number_format($todayRevenue, 0, ',', '.') }}</strong></span>
            <span class="text-[11px] text-black/50">(<span id="metric-today-completed">{{ $todayCompletedTx }}</span> selesai)</span>
        </div>
    </div>

    <!-- Card 3: Total Masuk Hari Ini -->
    <div class="editorial-card p-6 bg-[#FFFFFF] flex flex-col justify-between">
        <div class="flex items-center justify-between">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-black/60">Total Masuk Hari Ini</span>
            <!-- Icon with Blue Background & White Icon -->
            <div class="w-9 h-9 rounded-md bg-[#00719C] text-white flex items-center justify-center text-sm flex-shrink-0">
                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" x2="3" y1="12" y2="12"/></svg>
            </div>
        </div>
        <div class="mt-4 flex items-baseline gap-2">
            <span id="metric-today-in" class="text-3xl font-bold text-black">{{ $todayTotalIn }}</span>
            <span class="text-xs font-medium text-black/60">tap-in</span>
        </div>
        <div class="mt-3 pt-3 clean-border-t text-xs text-black/60 font-medium">
            Lalu lintas masuk hari ini
        </div>
    </div>

    <!-- Card 4: Kartu RFID Terdaftar -->
    <div class="editorial-card p-6 bg-[#FFFFFF] flex flex-col justify-between">
        <div class="flex items-center justify-between">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-black/60">Kartu RFID Terdaftar</span>
            <!-- Icon with Blue Background & White Icon -->
            <div class="w-9 h-9 rounded-md bg-[#00719C] text-white flex items-center justify-center text-sm flex-shrink-0">
                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
            </div>
        </div>
        <div class="mt-4 flex items-baseline gap-2">
            <span id="metric-active-cards" class="text-3xl font-bold text-black">{{ $activeCardsCount }}</span>
            <span class="text-xs font-medium text-black/60">kartu aktif</span>
        </div>
        <div class="mt-3 pt-3 clean-border-t text-xs font-semibold text-black">
            <a href="{{ route('admin.cards.index') }}" class="hover:underline flex items-center gap-1.5 text-black">
                <span>Kelola Kartu</span>
                <svg class="w-3 h-3 text-black" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
            </a>
        </div>
    </div>
</div>

<!-- 2. GERBANG IOT STATUS SECTION -->
<div class="editorial-card p-6 bg-[#FFFFFF]">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6 pb-4 clean-border-b">
        <div>
            <h3 class="text-base font-bold text-black flex items-center gap-2">
                <span>Status Gerbang ESP8266 IoT</span>
            </h3>
            <p class="text-xs text-black/60 mt-0.5">Kondisi konektivitas dan kontrol langsung setiap titik akses pintu gerbang</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('receipt.latest') }}" target="_blank" class="px-3 py-1.5 rounded-md clean-border text-xs flex items-center gap-1.5 text-black hover:bg-slate-50 transition" title="Cetak struk transaksi selesai terakhir pada printer Eppos EP589 V2">
                <i class="fa-solid fa-print text-[#00719C]"></i>
                <span class="hidden sm:inline">Struk Terakhir (58mm)</span>
            </a>
            <a href="{{ route('admin.monitoring') }}" class="btn-accent text-xs flex items-center gap-2 text-black">
                <div class="w-5 h-5 rounded bg-[#00719C] text-white flex items-center justify-center text-[10px]">
                    <svg class="w-3 h-3 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="6 3 20 12 6 21 6 3"/></svg>
                </div>
                <span>Buka Simulator Visual</span>
            </a>
        </div>
    </div>

    <div id="device-container" class="grid grid-cols-1 md:grid-cols-2 gap-5">
        @foreach ($devices as $dev)
            <div class="clean-border rounded-md p-5 flex flex-col justify-between bg-[#FFFFFF] hover:border-[#00719C] transition">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="font-mono text-xs font-bold text-white bg-[#00719C] px-2.5 py-1 rounded">
                            {{ $dev->device_code }}
                        </span>
                        <span class="flex items-center gap-1.5 text-xs text-black font-medium">
                            <span class="status-dot-active"></span> Online
                        </span>
                    </div>

                    <div class="text-sm font-bold text-black mt-1">
                        {{ $dev->location }}
                    </div>

                    <div class="mt-3 text-xs text-black/60 flex items-center gap-3 font-medium">
                        <span>Tipe: <b class="text-black">{{ $dev->gate_type }}</b></span>
                        <span>&bull;</span>
                        <span>Update: <span class="font-mono text-black">{{ $dev->last_seen ? \Carbon\Carbon::parse($dev->last_seen)->format('H:i:s') : date('H:i:s') }}</span></span>
                    </div>
                </div>

                <div class="mt-5 pt-3 clean-border-t flex items-center gap-2">
                    <button onclick="openEmergencyModal('{{ $dev->device_code }}')"
                        class="btn-accent flex-1 py-2 px-3 text-xs flex items-center justify-center gap-2 text-black cursor-pointer">
                        <div class="w-5 h-5 rounded bg-[#00719C] text-white flex items-center justify-center text-[10px]">
                            <svg class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/></svg>
                        </div>
                        <span>Override Buka</span>
                    </button>
                    <a href="{{ route('admin.monitoring', ['device' => $dev->device_code]) }}"
                        class="w-9 h-9 bg-[#00719C] text-white rounded-md text-xs font-semibold hover:opacity-90 transition flex items-center justify-center" title="Buka Simulasi">
                        <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                    </a>
                </div>
            </div>
        @endforeach
    </div>
</div>

<!-- 3. KENDARAAN SEDANG PARKIR (ACTIVE SESSIONS) -->
<div class="editorial-card p-6 bg-[#FFFFFF]">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6 pb-4 clean-border-b">
        <div>
            <h3 class="text-base font-bold text-black flex items-center gap-2">
                <span>Kendaraan Sedang Terparkir</span>
                <span id="badge-parked-count" class="px-2 py-0.5 text-xs rounded clean-border font-mono font-bold text-black">{{ $parkedCount }}</span>
            </h3>
            <p class="text-xs text-black/60 mt-0.5">Daftar kendaraan aktif di dalam area parkir beserta hitungan estimasi biaya berjalan</p>
        </div>
        <div class="text-xs text-black/60 flex items-center gap-1.5 font-medium">
            <span class="status-dot-active"></span>
            <span>Live ticker diperbarui otomatis</span>
        </div>
    </div>

    <!-- Active Parked Table -->
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm border-collapse">
            <thead>
                <tr class="clean-border-b text-[11px] font-semibold uppercase tracking-wider text-black/60">
                    <th class="py-3 px-4">Nama Pengendara</th>
                    <th class="py-3 px-4">UID Kartu</th>
                    <th class="py-3 px-4">Saldo Pengendara</th>
                    <th class="py-3 px-4">Waktu Masuk</th>
                    <th class="py-3 px-4">Durasi Parkir</th>
                    <th class="py-3 px-4">Estimasi Tarif</th>
                    <th class="py-3 px-4 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody id="parked-tbody" class="divide-y divide-black/10 font-medium">
                <tr>
                    <td colspan="7" class="py-8 text-center text-black/40 text-xs">
                        <svg class="w-5 h-5 animate-spin inline mr-2 text-[#00719C]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10" stroke-opacity="0.25"/><path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"/></svg>
                        <span>Memuat data kendaraan aktif...</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- 4. LOG AUDIT TERKINI (LIVE ACCESS FEED) -->
<div class="editorial-card p-6 bg-[#FFFFFF]">
    <div class="flex items-center justify-between mb-5 pb-4 clean-border-b">
        <div>
            <h3 class="text-base font-bold text-black flex items-center gap-2">
                <span>Aktivitas Tap &amp; Log Audit Terakhir</span>
            </h3>
            <p class="text-xs text-black/60 mt-0.5">Rekaman riwayat verifikasi kartu pada gerbang masuk dan keluar</p>
        </div>
        <a href="{{ route('admin.logs.index') }}" class="text-xs text-black hover:underline font-semibold flex items-center gap-1">
            <span>Lihat Semua Log</span>
            <svg class="w-3 h-3 text-black" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
            <thead>
                <tr class="clean-border-b text-[11px] font-semibold uppercase tracking-wider text-black/60">
                    <th class="py-2.5 px-4">Waktu</th>
                    <th class="py-2.5 px-4">Gerbang</th>
                    <th class="py-2.5 px-4">UID Kartu</th>
                    <th class="py-2.5 px-4">Metode</th>
                    <th class="py-2.5 px-4">Status</th>
                    <th class="py-2.5 px-4">Catatan Aktivitas</th>
                </tr>
            </thead>
            <tbody id="logs-tbody" class="divide-y divide-black/10">
                <tr>
                    <td colspan="6" class="py-6 text-center text-black/40">Memuat log...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<!-- SCRIPT DASHBOARD LIVE POLLING -->
<script>
    // Live AJAX Polling Dashboard
    async function refreshDashboardData() {
        try {
            const res = await fetch('{{ route('admin.api.stats') }}');
            const data = await res.json();
            if (data.status !== 'success') return;

            // 1. Update Metrics
            document.getElementById('metric-parked-count').textContent = data.metrics.parked_count;
            document.getElementById('badge-parked-count').textContent = data.metrics.parked_count;
            if (document.getElementById('dash-lt1') && data.metrics.parked_lt1 !== undefined) {
                document.getElementById('dash-lt1').textContent = data.metrics.parked_lt1;
            }
            if (document.getElementById('dash-lt2') && data.metrics.parked_lt2 !== undefined) {
                document.getElementById('dash-lt2').textContent = data.metrics.parked_lt2;
            }
            if (document.getElementById('metric-admin-cash') && data.metrics.total_admin_cash_formatted) {
                document.getElementById('metric-admin-cash').textContent = data.metrics.total_admin_cash_formatted;
            }
            document.getElementById('metric-today-revenue').textContent = data.metrics.today_revenue_formatted;
            document.getElementById('metric-today-completed').textContent = data.metrics.today_completed_tx;
            document.getElementById('metric-today-in').textContent = data.metrics.today_total_in;
            document.getElementById('metric-active-cards').textContent = data.metrics.active_cards_count;

            // 2. Update Active Parked Table
            const parkedTbody = document.getElementById('parked-tbody');
            if (!data.active_parked_list || data.active_parked_list.length === 0) {
                parkedTbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="py-8 text-center text-black/40 text-xs">
                            <div class="w-10 h-10 mx-auto rounded-md bg-[#00719C] text-white flex items-center justify-center mb-2">
                                <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="3"/><path d="M9 17V7h4a3 3 0 0 1 0 6H9"/></svg>
                            </div>
                            Tidak ada kendaraan yang sedang terparkir saat ini.
                        </td>
                    </tr>
                `;
            } else {
                let html = '';
                data.active_parked_list.forEach((item, index) => {
                    const hours = Math.floor(item.duration_minutes / 60);
                    const mins = item.duration_minutes % 60;
                    const durationText = (hours > 0 ? `${hours} Jam ` : '') + `${mins} Menit`;

                    const floorBadge = (item.parking_floor === 2) 
                        ? '<span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-amber-100 text-amber-800 clean-border">LT 2</span>'
                        : '<span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-sky-100 text-[#00719C] clean-border">LT 1</span>';

                    html += `
                        <tr class="hover:bg-black/5 transition">
                            <td class="py-3 px-4">
                                <div class="font-bold text-black flex items-center gap-1.5">
                                    <span>${item.user_name}</span>
                                    ${floorBadge}
                                </div>
                                <div class="text-[11px] text-black/50">${item.phone || '-'}</div>
                            </td>
                            <td class="py-3 px-4 font-mono text-xs font-bold text-black">
                                ${item.uid}
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-mono text-xs font-bold text-black">${item.user_balance_formatted}</div>
                                <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-bold ${item.is_balance_sufficient ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'}">
                                    ${item.is_balance_sufficient ? '✓ Cukup' : '⚠️ Kurang'}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-xs text-black/70">
                                <div class="font-mono text-black">${item.tap_in_time}</div>
                                <div class="text-[11px] text-black/50 font-semibold">${item.gate_in}</div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2.5 py-1 rounded text-xs font-semibold clean-border inline-flex items-center gap-1.5 text-black">
                                    <span class="status-dot-active"></span>
                                    ${durationText}
                                </span>
                            </td>
                            <td class="py-3 px-4 font-bold text-black text-sm">
                                ${item.estimated_fee_formatted}
                                <span class="text-[10px] text-black/50 font-normal block">(${item.billed_hours} Jam)</span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <button onclick="checkoutManual('${item.uid}')"
                                    class="btn-primary py-1.5 px-3 text-xs inline-flex items-center gap-1.5 bg-[#00719C] text-white cursor-pointer" title="Tap-out Manual & Potong Saldo">
                                    <svg class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
                                    <span>Tap-Out</span>
                                </button>
                            </td>
                        </tr>
                    `;
                });
                parkedTbody.innerHTML = html;
            }

            // 3. Update Recent Logs Table
            const logsTbody = document.getElementById('logs-tbody');
            if (!data.recent_logs || data.recent_logs.length === 0) {
                logsTbody.innerHTML = `<tr><td colspan="6" class="py-4 text-center text-black/40">Belum ada riwayat log.</td></tr>`;
            } else {
                let logHtml = '';
                data.recent_logs.forEach(log => {
                    const isGranted = log.status === 'granted';
                    const statusText = isGranted ? 'GRANTED' : 'DENIED';

                    logHtml += `
                        <tr class="hover:bg-black/5 transition">
                            <td class="py-2.5 px-4 text-black/60 font-mono text-[11px]">${log.created_at}</td>
                            <td class="py-2.5 px-4 font-mono font-bold text-black">${log.device_code}</td>
                            <td class="py-2.5 px-4 font-mono text-black font-bold">${log.scan_uid}</td>
                            <td class="py-2.5 px-4 uppercase text-[11px] font-semibold text-black">${log.access_method}</td>
                            <td class="py-2.5 px-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold clean-border text-black">
                                    ${statusText}
                                </span>
                            </td>
                            <td class="py-2.5 px-4 text-black font-medium">${log.notes || '-'}</td>
                        </tr>
                    `;
                });
                logsTbody.innerHTML = logHtml;
            }

        } catch (e) {
            console.error('Error polling dashboard stats:', e);
        }
    }

    // Checkout Manual via Tap-Out API (Anti-Spam & Menunggu)
    let isProcessingDashCheckout = false;
    async function checkoutManual(uid) {
        if (isProcessingDashCheckout) {
            showToast('Mohon Menunggu', 'Sedang memproses checkout kendaraan...', 'warning');
            return;
        }

        if (!confirm(`Proses Tap-Out kendaraan dengan UID kartu ${uid}? Palang keluar akan dibuka dan biaya parkir akan ditagihkan.`)) {
            return;
        }

        isProcessingDashCheckout = true;
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
                showToast('Tap-Out Berhasil!', `${data.message} - Total Biaya: ${data.data.total_fee_formatted}`, 'success');
                playWebBuzzer(2);
                refreshDashboardData();
            } else {
                showToast('Gagal Tap-Out', data.message, 'error');
                playWebBuzzer(3, 'low');
            }
        } catch (e) {
            showToast('Error', e.message, 'error');
        } finally {
            setTimeout(() => {
                isProcessingDashCheckout = false;
                refreshDashboardData();
            }, 1500);
        }
    }

    // Poll every 3.5 seconds
    refreshDashboardData();
    setInterval(refreshDashboardData, 3500);
</script>
@endpush
