@extends('layouts.admin')

@section('title', 'Status Slot & Ketersediaan Parkir')
@section('header_title', 'Status & Ketersediaan Slot Parkir')
@section('header_subtitle', 'Pemantauan realtime ketersediaan area parkir, okupansi slot, dan kendaraan yang sedang terparkir')

@section('content')
<div class="d-flex flex-column gap-4">

    <!-- 1. BIG HERO STATUS CARD -->
    <div id="status-hero-card" class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white position-relative overflow-hidden">
        <div id="status-hero-line" class="position-absolute top-0 start-0 end-0 bg-primary" style="height: 4px;"></div>

        <div class="row align-items-center g-4">
            <!-- Left: Icon & Info -->
            <div class="col-lg-8 d-flex flex-column flex-md-row align-items-center text-center text-md-start gap-4">
                <div id="status-hero-iconbox" class="badge bg-primary p-3 p-md-4 rounded-4 fs-1 shadow-sm">
                    <i id="status-hero-icon" class="bi bi-car-front-fill"></i>
                </div>
                <div>
                    <h3 id="status-hero-title" class="fw-bold text-dark mb-1">
                        Memeriksa Okupansi Area Parkir...
                    </h3>
                    <p id="status-hero-desc" class="text-secondary small mb-0">
                        Sinkronisasi otomatis dengan sensor gerbang ESP8266 dan database parkir aktif.
                    </p>
                </div>
            </div>

            <!-- Right: Slot Counter Box -->
            <div class="col-lg-4 text-center">
                <div class="card bg-light border p-3 rounded-4">
                    <span class="text-uppercase small fw-bold text-secondary">Kapasitas Slot Total</span>
                    <div class="d-flex align-items-baseline justify-content-center gap-1 my-1">
                        <span id="stat-hero-parked" class="display-5 fw-bold text-primary font-monospace">0</span>
                        <span class="text-muted fw-bold">/ <span id="stat-hero-capacity">{{ $totalCapacity }}</span></span>
                    </div>
                    <div>
                        <span id="stat-hero-available-tag" class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1">
                            {{ $totalCapacity }} Slot Kosong
                        </span>
                    </div>
                    <div class="d-flex justify-content-center gap-2 mt-2 pt-2 border-top font-monospace small">
                        <span class="badge bg-info-subtle text-info border border-info-subtle">LT1: <strong id="adm-lt1">{{ $parkedLt1 }}</strong>/10</span>
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle">LT2: <strong id="adm-lt2">{{ $parkedLt2 }}</strong>/10</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. STAT SUMMARY CARDS (4 Columns) -->
    <div class="row g-3">
        <!-- Slot Terisi -->
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <small class="text-uppercase fw-bold text-secondary">Slot Terpakai</small>
                    <span class="badge bg-primary-subtle text-primary p-2 rounded-3">
                        <i class="bi bi-car-front-fill fs-6"></i>
                    </span>
                </div>
                <div class="d-flex align-items-baseline gap-1 my-1">
                    <h3 id="stat-parked-count" class="fw-bold text-dark font-monospace mb-0">0</h3>
                    <small class="text-muted">kendaraan</small>
                </div>
                <small class="text-success mt-auto pt-2 border-top d-flex align-items-center gap-1">
                    <i class="bi bi-broadcast"></i> Okupansi realtime
                </small>
            </div>
        </div>

        <!-- Slot Kosong -->
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <small class="text-uppercase fw-bold text-secondary">Slot Kosong</small>
                    <span class="badge bg-success-subtle text-success p-2 rounded-3">
                        <i class="bi bi-check-circle-fill fs-6"></i>
                    </span>
                </div>
                <div class="d-flex align-items-baseline gap-1 my-1">
                    <h3 id="stat-available-count" class="fw-bold text-success font-monospace mb-0">{{ $totalCapacity }}</h3>
                    <small class="text-muted">slot kosong</small>
                </div>
                <small class="text-muted mt-auto pt-2 border-top">
                    Siap digunakan
                </small>
            </div>
        </div>

        <!-- Masuk Hari Ini -->
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <small class="text-uppercase fw-bold text-secondary">Masuk Hari Ini</small>
                    <span class="badge bg-info-subtle text-info p-2 rounded-3">
                        <i class="bi bi-box-arrow-in-right fs-6"></i>
                    </span>
                </div>
                <div class="d-flex align-items-baseline gap-1 my-1">
                    <h3 id="stat-today-in" class="fw-bold text-dark font-monospace mb-0">0</h3>
                    <small class="text-muted">tap-in</small>
                </div>
                <small class="text-muted mt-auto pt-2 border-top">
                    Total kedatangan
                </small>
            </div>
        </div>

        <!-- Keluar Hari Ini -->
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <small class="text-uppercase fw-bold text-secondary">Keluar Hari Ini</small>
                    <span class="badge bg-secondary-subtle text-secondary p-2 rounded-3">
                        <i class="bi bi-box-arrow-right fs-6"></i>
                    </span>
                </div>
                <div class="d-flex align-items-baseline gap-1 my-1">
                    <h3 id="stat-today-out" class="fw-bold text-dark font-monospace mb-0">0</h3>
                    <small class="text-muted">transaksi</small>
                </div>
                <small class="text-muted mt-auto pt-2 border-top">
                    Total kepulangan
                </small>
            </div>
        </div>
    </div>

    <!-- 3. LIVE VEHICLE LIST CONTAINER -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
        <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between pb-3 mb-4 border-bottom gap-2">
            <div>
                <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-list-stars text-primary"></i>
                    <span>Daftar Kendaraan yang Sedang Terparkir</span>
                </h5>
                <small class="text-muted">Pantau durasi berjalan dan estimasi biaya parkir setiap kendaraan secara realtime</small>
            </div>
            <span id="vehicles-badge-count" class="badge bg-light text-dark border font-monospace px-3 py-2">
                0 Kendaraan Aktif
            </span>
        </div>

        <!-- Vehicle Grid -->
        <div id="admin-vehicle-grid" class="row g-3">
            <!-- Diisi via JavaScript -->
        </div>

        <!-- Empty State Placeholder -->
        <div id="admin-empty-state" class="text-center py-5 d-none">
            <div class="mb-3">
                <span class="badge bg-success-subtle text-success fs-1 p-3 rounded-circle">
                    <i class="bi bi-check2-all"></i>
                </span>
            </div>
            <h5 class="fw-bold text-dark mb-1">Area Parkir Saat Ini Kosong</h5>
            <p class="text-muted small max-w-md mx-auto">
                Tidak ada kendaraan yang sedang terparkir di area parkir. Semua slot siap digunakan.
            </p>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    let adminVehicles = [];
    let adminTimerInterval = null;

    async function fetchAdminParkingStatus() {
        try {
            const res = await fetch('{{ route('status') }}?ajax=1');
            const data = await res.json();
            if (data.status === 'success') {
                renderAdminStatusView(data);
            }
        } catch (err) {
            console.error("Gagal sinkron status admin:", err);
        }
    }

    function renderAdminStatusView(data) {
        document.getElementById('stat-parked-count').innerText = data.parked_count;
        document.getElementById('stat-available-count').innerText = data.available_slots;
        document.getElementById('stat-today-in').innerText = data.today_in;
        document.getElementById('stat-today-out').innerText = data.today_out;
        document.getElementById('stat-hero-parked').innerText = data.parked_count;
        document.getElementById('stat-hero-capacity').innerText = data.capacity;
        document.getElementById('vehicles-badge-count').innerText = `${data.parked_count} Kendaraan Aktif`;
        
        if (document.getElementById('adm-lt1') && data.parked_lt1 !== undefined) {
            document.getElementById('adm-lt1').innerText = data.parked_lt1;
        }
        if (document.getElementById('adm-lt2') && data.parked_lt2 !== undefined) {
            document.getElementById('adm-lt2').innerText = data.parked_lt2;
        }

        const heroLine = document.getElementById('status-hero-line');
        const heroIconBox = document.getElementById('status-hero-iconbox');
        const heroTitle = document.getElementById('status-hero-title');
        const heroDesc = document.getElementById('status-hero-desc');
        const heroAvailTag = document.getElementById('stat-hero-available-tag');

        if (data.has_vehicles) {
            heroLine.className = 'position-absolute top-0 start-0 end-0 bg-warning';
            heroIconBox.className = 'badge bg-warning text-dark p-3 p-md-4 rounded-4 fs-1 shadow-sm';
            heroTitle.innerHTML = `Ada <span class="text-primary">${data.parked_count} Kendaraan</span> Sedang Parkir`;
            heroDesc.innerText = `Sisa ${data.available_slots} slot dari total kapasitas ${data.capacity} kendaraan. Tarif aktif berjalan Rp2.000 / Jam.`;
            heroAvailTag.className = 'badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-1';
            heroAvailTag.innerText = `Sisa ${data.available_slots} Slot Kosong`;
        } else {
            heroLine.className = 'position-absolute top-0 start-0 end-0 bg-success';
            heroIconBox.className = 'badge bg-success p-3 p-md-4 rounded-4 fs-1 shadow-sm';
            heroTitle.innerHTML = `Area Parkir Kosong &amp; Siap Digunakan`;
            heroDesc.innerText = `Tidak ada kendaraan di dalam area parkir. Semua ${data.capacity} slot dalam kondisi kosong.`;
            heroAvailTag.className = 'badge bg-success-subtle text-success border border-success-subtle px-3 py-1';
            heroAvailTag.innerText = `Semua ${data.capacity} Slot Kosong`;
        }

        const grid = document.getElementById('admin-vehicle-grid');
        const emptyState = document.getElementById('admin-empty-state');

        if (!data.has_vehicles) {
            grid.innerHTML = '';
            emptyState.classList.remove('d-none');
            adminVehicles = [];
        } else {
            emptyState.classList.add('d-none');
            adminVehicles = data.vehicles;
            renderAdminVehicleCards(data.vehicles);
        }
    }

    function renderAdminVehicleCards(vehicles) {
        const grid = document.getElementById('admin-vehicle-grid');
        const currentIds = Array.from(grid.querySelectorAll('[data-admin-v-id]')).map(el => parseInt(el.getAttribute('data-admin-v-id')));
        const newIds = vehicles.map(v => v.id);
        const isSame = currentIds.length === newIds.length && currentIds.every((id, idx) => id === newIds[idx]);

        if (!isSame) {
            grid.innerHTML = '';
            vehicles.forEach(v => {
                const col = document.createElement('div');
                col.className = 'col-12 col-md-6 col-lg-4';
                col.setAttribute('data-admin-v-id', v.id);

                const floorNum = parseInt(v.parking_floor) || 1;
                const floorBadge = floorNum === 2 ? '<span class="badge bg-warning text-dark">LT 2</span>' : '<span class="badge bg-info text-dark">LT 1</span>';

                col.innerHTML = `
                    <div class="card border rounded-4 p-3 bg-white h-100 shadow-sm d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center justify-content-between pb-3 border-bottom mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-primary p-2 rounded-3 text-white">
                                        <i class="bi bi-car-front-fill fs-6"></i>
                                    </span>
                                    <div>
                                        <h6 class="fw-bold mb-0 text-dark">\${escapeHtml(v.user_name)}</h6>
                                        <small class="text-muted font-monospace">UID: <strong>\${escapeHtml(v.uid)}</strong></small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-1">
                                    \${floorBadge}
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">PARKED</span>
                                </div>
                            </div>

                            <div class="row g-2 mb-3 text-center">
                                <div class="col-6">
                                    <div class="p-2 rounded-3 bg-light border">
                                        <small class="text-secondary text-uppercase d-block" style="font-size: 0.7rem;">Durasi Parkir</small>
                                        <div id="adm-timer-\${v.id}" class="font-monospace fw-bold fs-6 text-dark">00:00:00</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 rounded-3 bg-primary-subtle border border-primary-subtle">
                                        <small class="text-primary text-uppercase d-block fw-semibold" style="font-size: 0.7rem;">Estimasi Tarif</small>
                                        <div id="adm-fee-\${v.id}" class="font-monospace fw-bold fs-6 text-primary">\${v.fee_formatted}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex align-items-center justify-content-between small text-muted pb-3 mb-3 border-bottom">
                                <div class="d-flex align-items-center gap-1">
                                    <i class="bi bi-box-arrow-in-right text-primary"></i>
                                    <span>\${escapeHtml(v.gate_in)}</span>
                                </div>
                                <span class="font-monospace">Masuk: <strong>\${v.tap_in_formatted}</strong></span>
                            </div>
                        </div>

                        <button type="button" 
                                onclick="checkoutManualAdmin('\${escapeHtml(v.uid)}', '\${escapeHtml(v.user_name)}', this)"
                                class="btn btn-outline-danger btn-sm w-full py-2 rounded-3 d-flex align-items-center justify-content-center gap-2 btn-selesai-status">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Selesaikan Parkir</span>
                        </button>
                    </div>
                `;
                grid.appendChild(col);
            });
        }

        updateAdminTimers();
    }

    let isProcessingStatusCheckout = false;
    async function checkoutManualAdmin(uid, userName, btnElement = null) {
        if (isProcessingStatusCheckout) {
            if (typeof showToast === 'function') {
                showToast('Mohon Menunggu', 'Sedang memproses penyelesaian parkir...', 'warning');
            }
            return;
        }

        if (!confirm(`Selesaikan parkir untuk "\${userName}" (UID: \${uid})?\n\nOpsi ini digunakan jika gerbang keluar atau sensor RFID mengalami kendala.`)) {
            return;
        }

        isProcessingStatusCheckout = true;
        const allBtns = document.querySelectorAll('.btn-selesai-status');
        allBtns.forEach(b => b.disabled = true);

        let origHtml = '';
        if (btnElement) {
            origHtml = btnElement.innerHTML;
            btnElement.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menunggu...';
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
                    alert(`✅ Parkir Berhasil Diselesaikan!\n\n${data.message}`);
                }
                if (typeof playWebBuzzer === 'function') playWebBuzzer(2);
                fetchAdminParkingStatus();
            } else {
                if (typeof showToast === 'function') {
                    showToast('Gagal Selesaikan Parkir', data.message, 'danger');
                } else {
                    alert(`❌ Gagal: ${data.message}`);
                }
                if (typeof playWebBuzzer === 'function') playWebBuzzer(3, 'low');
            }
        } catch (err) {
            console.error('Error checkout manual:', err);
            if (typeof showToast === 'function') {
                showToast('Error', err.message, 'danger');
            }
        } finally {
            setTimeout(() => {
                isProcessingStatusCheckout = false;
                allBtns.forEach(b => b.disabled = false);
                if (btnElement && origHtml) {
                    btnElement.innerHTML = origHtml;
                }
                fetchAdminParkingStatus();
            }, 1500);
        }
    }

    function updateAdminTimers() {
        const now = Date.now();
        adminVehicles.forEach(v => {
            const elTimer = document.getElementById(`adm-timer-\${v.id}`);
            const elFee = document.getElementById(`adm-fee-\${v.id}`);
            const elHours = document.getElementById(`adm-hours-\${v.id}`);
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
            if (elHours) elHours.innerText = `${billedHours} Jam Dihitung`;
        });
    }

    if (!adminTimerInterval) {
        adminTimerInterval = setInterval(updateAdminTimers, 1000);
    }

    setInterval(fetchAdminParkingStatus, 1500);
    fetchAdminParkingStatus();

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.innerText = text;
        return div.innerHTML;
    }
</script>
@endpush
