@extends('layouts.admin')

@section('title', 'Dashboard & Gate Monitoring')
@section('header_title', 'Dashboard & Gate')
@section('header_subtitle', 'Monitoring sistem palang pintu & transaksi parkir otomatis')

@section('content')
<div class="d-flex flex-column gap-4">

    <!-- 1. STAT CARDS ROW -->
    <div class="row g-3">
        <!-- Card 1: Kendaraan Parkir Saat Ini -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 d-flex flex-column justify-content-between">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-uppercase small fw-bold text-secondary">Sedang Terparkir</span>
                    <span class="badge bg-primary p-2 rounded-3 text-white">
                        <i class="bi bi-car-front-fill fs-6"></i>
                    </span>
                </div>
                <div class="my-3 d-flex align-items-baseline gap-2">
                    <h2 id="metric-parked-count" class="fw-bold text-dark mb-0 font-monospace">{{ $parkedCount }}</h2>
                    <span class="text-secondary small">/ 20 kendaraan</span>
                </div>
                <div class="pt-2 border-top d-flex align-items-center justify-content-between small">
                    <span class="text-success d-flex align-items-center gap-1">
                        <i class="bi bi-broadcast"></i> Realtime
                    </span>
                    <div class="d-flex gap-1 font-monospace">
                        <span class="badge bg-info-subtle text-info border">LT1: <strong id="dash-lt1">{{ $parkedLt1 }}</strong>/10</span>
                        <span class="badge bg-warning-subtle text-warning border">LT2: <strong id="dash-lt2">{{ $parkedLt2 }}</strong>/10</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Saldo Kas Admin -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 d-flex flex-column justify-content-between">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase small fw-bold text-secondary d-block">Saldo Kas Admin</span>
                        <span class="badge bg-success-subtle text-success mt-1">Uang Masuk Tap</span>
                    </div>
                    <span class="badge bg-primary p-2 rounded-3 text-white">
                        <i class="bi bi-wallet2 fs-6"></i>
                    </span>
                </div>
                <div class="my-3">
                    <h3 id="metric-admin-cash" class="fw-bold text-dark mb-0 font-monospace">Rp {{ number_format($totalAdminCash, 0, ',', '.') }}</h3>
                </div>
                <div class="pt-2 border-top d-flex align-items-center justify-content-between small text-secondary">
                    <span>Hari ini: <strong id="metric-today-revenue" class="text-dark">Rp {{ number_format($todayRevenue, 0, ',', '.') }}</strong></span>
                    <span class="small">(<span id="metric-today-completed">{{ $todayCompletedTx }}</span> selesai)</span>
                </div>
            </div>
        </div>

        <!-- Card 3: Total Masuk Hari Ini -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 d-flex flex-column justify-content-between">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-uppercase small fw-bold text-secondary">Total Masuk Hari Ini</span>
                    <span class="badge bg-primary p-2 rounded-3 text-white">
                        <i class="bi bi-box-arrow-in-right fs-6"></i>
                    </span>
                </div>
                <div class="my-3 d-flex align-items-baseline gap-2">
                    <h2 id="metric-today-in" class="fw-bold text-dark mb-0 font-monospace">{{ $todayTotalIn }}</h2>
                    <span class="text-secondary small">tap-in</span>
                </div>
                <div class="pt-2 border-top text-secondary small">
                    Lalu lintas masuk hari ini
                </div>
            </div>
        </div>

        <!-- Card 4: Kartu RFID Terdaftar -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 d-flex flex-column justify-content-between">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-uppercase small fw-bold text-secondary">Kartu RFID Terdaftar</span>
                    <span class="badge bg-primary p-2 rounded-3 text-white">
                        <i class="bi bi-credit-card-2-front-fill fs-6"></i>
                    </span>
                </div>
                <div class="my-3 d-flex align-items-baseline gap-2">
                    <h2 id="metric-active-cards" class="fw-bold text-dark mb-0 font-monospace">{{ $activeCardsCount }}</h2>
                    <span class="text-secondary small">kartu aktif</span>
                </div>
                <div class="pt-2 border-top">
                    <a href="{{ route('admin.cards.index') }}" class="text-decoration-none small fw-semibold text-primary d-flex align-items-center gap-1">
                        <span>Kelola Kartu</span>
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. GERBANG IOT STATUS SECTION -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
        <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between pb-3 mb-4 border-bottom gap-3">
            <div>
                <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-router-fill text-primary"></i>
                    <span>Status Gerbang ESP8266 IoT</span>
                </h5>
                <small class="text-muted">Kondisi konektivitas dan kontrol langsung setiap titik akses pintu gerbang</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('receipt.latest') }}" target="_blank" class="btn btn-outline-secondary btn-sm rounded-3 d-flex align-items-center gap-2" title="Cetak struk 58mm">
                    <i class="bi bi-printer-fill text-primary"></i>
                    <span class="d-none d-sm-inline">Struk Terakhir (58mm)</span>
                </a>
                <a href="{{ route('admin.monitoring') }}" class="btn btn-primary btn-sm rounded-3 d-flex align-items-center gap-2">
                    <i class="bi bi-play-circle-fill"></i>
                    <span>Buka Simulator Visual</span>
                </a>
            </div>
        </div>

        <div id="device-container" class="row g-3">
            @foreach ($devices as $dev)
                <div class="col-12 col-md-6">
                    <div class="card border rounded-4 p-3 bg-white h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="badge bg-primary font-monospace px-2.5 py-1.5">
                                    {{ $dev->device_code }}
                                </span>
                                <span class="badge bg-success-subtle text-success border border-success-subtle d-flex align-items-center gap-1">
                                    <span class="spinner-grow spinner-grow-sm text-success" style="width: 8px; height: 8px;" role="status"></span>
                                    <span>Online</span>
                                </span>
                            </div>

                            <h6 class="fw-bold text-dark mt-2 mb-1">
                                {{ $dev->location }}
                            </h6>

                            <div class="small text-secondary d-flex align-items-center gap-2">
                                <span>Tipe: <strong class="text-dark">{{ $dev->gate_type }}</strong></span>
                                <span>&bull;</span>
                                <span>Update: <span class="font-monospace text-dark">{{ $dev->last_seen ? \Carbon\Carbon::parse($dev->last_seen)->format('H:i:s') : date('H:i:s') }}</span></span>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex align-items-center gap-2">
                            <button onclick="openEmergencyModal('{{ $dev->device_code }}')" class="btn btn-warning btn-sm rounded-3 flex-grow-1 d-flex align-items-center justify-content-center gap-2">
                                <i class="bi bi-shield-lock-fill"></i>
                                <span>Override Buka</span>
                            </button>
                            <a href="{{ route('admin.monitoring', ['device' => $dev->device_code]) }}" class="btn btn-outline-primary btn-sm rounded-3" title="Buka Simulasi">
                                <i class="bi bi-eye-fill"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- 3. KENDARAAN SEDANG PARKIR (ACTIVE SESSIONS) -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
        <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between pb-3 mb-3 border-bottom gap-2">
            <div>
                <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-car-front text-primary"></i>
                    <span>Kendaraan Sedang Terparkir</span>
                    <span id="badge-parked-count" class="badge bg-primary font-monospace">{{ $parkedCount }}</span>
                </h5>
                <small class="text-muted">Daftar kendaraan aktif beserta hitungan estimasi biaya berjalan</small>
            </div>
            <small class="text-success d-flex align-items-center gap-1">
                <i class="bi bi-broadcast"></i> Live ticker diperbarui otomatis
            </small>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-secondary small text-uppercase">
                        <th>Nama Pengendara</th>
                        <th>UID Kartu</th>
                        <th>Saldo Pengendara</th>
                        <th>Waktu Masuk</th>
                        <th>Durasi Parkir</th>
                        <th>Estimasi Tarif</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="parked-tbody">
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                            <span>Memuat data kendaraan aktif...</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 4. LOG AUDIT TERKINI -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
        <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
            <div>
                <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-clock-history text-primary"></i>
                    <span>Aktivitas Tap &amp; Log Audit Terakhir</span>
                </h5>
                <small class="text-muted">Rekaman riwayat verifikasi kartu pada gerbang masuk dan keluar</small>
            </div>
            <a href="{{ route('admin.logs.index') }}" class="btn btn-outline-primary btn-sm rounded-3 d-flex align-items-center gap-1">
                <span>Lihat Semua Log</span>
                <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-secondary small text-uppercase">
                        <th>Waktu</th>
                        <th>Gerbang</th>
                        <th>UID Kartu</th>
                        <th>Metode</th>
                        <th>Status</th>
                        <th>Catatan Aktivitas</th>
                    </tr>
                </thead>
                <tbody id="logs-tbody">
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">Memuat log...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    async function refreshDashboardData() {
        try {
            const res = await fetch('{{ route('admin.api.stats') }}');
            const data = await res.json();
            if (data.status !== 'success') return;

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

            const parkedTbody = document.getElementById('parked-tbody');
            if (!data.active_parked_list || data.active_parked_list.length === 0) {
                parkedTbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-1 text-secondary"></i>
                            Tidak ada kendaraan yang sedang terparkir saat ini.
                        </td>
                    </tr>
                `;
            } else {
                let html = '';
                data.active_parked_list.forEach((item) => {
                    const hours = Math.floor(item.duration_minutes / 60);
                    const mins = item.duration_minutes % 60;
                    const durationText = (hours > 0 ? `${hours} Jam ` : '') + `${mins} Menit`;

                    const floorBadge = (item.parking_floor === 2) 
                        ? '<span class="badge bg-warning text-dark">LT 2</span>'
                        : '<span class="badge bg-info text-dark">LT 1</span>';

                    html += `
                        <tr>
                            <td>
                                <div class="fw-bold text-dark d-flex align-items-center gap-1">
                                    <span>${item.user_name}</span>
                                    ${floorBadge}
                                </div>
                                <small class="text-muted">${item.phone || '-'}</small>
                            </td>
                            <td>
                                <span class="font-monospace fw-bold text-dark">${item.uid}</span>
                            </td>
                            <td>
                                <div class="font-monospace fw-bold text-dark">${item.user_balance_formatted}</div>
                                <span class="badge ${item.is_balance_sufficient ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'}">
                                    ${item.is_balance_sufficient ? '✓ Cukup' : '⚠️ Kurang'}
                                </span>
                            </td>
                            <td>
                                <div class="font-monospace small text-dark">${item.tap_in_time}</div>
                                <small class="text-muted">${item.gate_in}</small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border d-inline-flex align-items-center gap-1">
                                    <span class="spinner-grow spinner-grow-sm text-primary" style="width: 6px; height: 6px;" role="status"></span>
                                    ${durationText}
                                </span>
                            </td>
                            <td>
                                <strong class="text-dark font-monospace">${item.estimated_fee_formatted}</strong>
                                <small class="text-muted d-block font-monospace">(${item.billed_hours} Jam)</small>
                            </td>
                            <td class="text-center">
                                <button onclick="checkoutManual('${item.uid}')" class="btn btn-outline-danger btn-sm rounded-3 d-inline-flex align-items-center gap-1" title="Tap-out Manual">
                                    <i class="bi bi-box-arrow-right"></i>
                                    <span>Tap-Out</span>
                                </button>
                            </td>
                        </tr>
                    `;
                });
                parkedTbody.innerHTML = html;
            }

            const logsTbody = document.getElementById('logs-tbody');
            if (!data.recent_logs || data.recent_logs.length === 0) {
                logsTbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted">Belum ada riwayat log.</td></tr>`;
            } else {
                let logHtml = '';
                data.recent_logs.forEach(log => {
                    const isGranted = log.status === 'granted';
                    const badgeClass = isGranted ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger';

                    let methodBadge = '';
                    const m = (log.access_method || '').toLowerCase();
                    if (m === 'ticket' || m === 'karcis') {
                        methodBadge = '<span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="bi bi-ticket-perforated-fill me-1"></i> KARCIS</span>';
                    } else if (m === 'rfid') {
                        methodBadge = '<span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="bi bi-credit-card-2-front-fill me-1"></i> RFID</span>';
                    } else if (m === 'web_admin') {
                        methodBadge = '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"><i class="bi bi-shield-lock me-1"></i> ADMIN</span>';
                    } else if (m === 'mobile_app') {
                        methodBadge = '<span class="badge bg-info-subtle text-info border border-info-subtle"><i class="bi bi-phone me-1"></i> MOBILE</span>';
                    } else {
                        methodBadge = `<span class="badge bg-light text-dark border">${(log.access_method || '-').toUpperCase()}</span>`;
                    }

                    logHtml += `
                        <tr>
                            <td class="font-monospace small text-secondary">${log.created_at}</td>
                            <td class="font-monospace fw-bold text-dark">${log.device_code}</td>
                            <td class="font-monospace text-dark">${log.scan_uid}</td>
                            <td>${methodBadge}</td>
                            <td>
                                <span class="badge ${badgeClass}">
                                    ${isGranted ? 'GRANTED' : 'DENIED'}
                                </span>
                            </td>
                            <td class="small text-secondary">${log.notes || '-'}</td>
                        </tr>
                    `;
                });
                logsTbody.innerHTML = logHtml;
            }

        } catch (e) {
            console.error('Error polling dashboard stats:', e);
        }
    }

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
                showToast('Gagal Tap-Out', data.message, 'danger');
                playWebBuzzer(3, 'low');
            }
        } catch (e) {
            showToast('Error', e.message, 'danger');
        } finally {
            setTimeout(() => {
                isProcessingDashCheckout = false;
                refreshDashboardData();
            }, 1500);
        }
    }

    refreshDashboardData();
    setInterval(refreshDashboardData, 3500);
</script>
@endpush
