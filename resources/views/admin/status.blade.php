@extends('layouts.admin')

@section('title', 'Status Slot & Ketersediaan Parkir')
@section('header_title', 'Status & Ketersediaan Slot Parkir')
@section('header_subtitle', 'Pemantauan realtime ketersediaan area parkir, okupansi slot, dan kendaraan yang sedang terparkir')

@section('content')
<div class="space-y-6">

    <!-- 1. BIG HERO STATUS CARD (Editorial Card) -->
    <div id="status-hero-card" class="editorial-card p-6 sm:p-8 relative overflow-hidden bg-white">
        <!-- Top Accent Line -->
        <div id="status-hero-line" class="absolute top-0 left-0 right-0 h-1 bg-[#00719C]"></div>

        <div class="flex flex-col md:flex-row items-center justify-between gap-6 relative z-10">
            <!-- Left: Icon & Info -->
            <div class="flex items-center gap-5 text-center md:text-left flex-col md:flex-row">
                <div id="status-hero-iconbox" class="w-16 h-16 sm:w-20 sm:h-20 rounded-lg flex items-center justify-center bg-[#00719C] text-white flex-shrink-0">
                    <svg id="status-hero-svg" class="w-8 h-8 sm:w-10 sm:h-10 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/></svg>
                </div>
                <div>
                    <h2 id="status-hero-title" class="text-xl sm:text-2xl font-bold text-black tracking-tight">
                        Memeriksa Okupansi Area Parkir...
                    </h2>
                    <p id="status-hero-desc" class="text-xs sm:text-sm text-black/60 mt-1 max-w-xl">
                        Sinkronisasi otomatis dengan sensor gerbang ESP8266 dan database parkir aktif.
                    </p>
                </div>
            </div>

            <!-- Right: Slot Counter Box -->
            <div class="text-center p-4 rounded-md clean-border bg-[#FFFFFF] flex flex-col items-center justify-center min-w-[190px]">
                <span class="text-[10px] uppercase font-semibold tracking-wider text-black/60">Kapasitas Slot Total</span>
                <div class="flex items-baseline gap-1 my-1">
                    <span id="stat-hero-parked" class="text-3xl sm:text-4xl font-bold text-[#00719C] font-mono">0</span>
                    <span class="text-xs font-semibold text-black/50">/ <span id="stat-hero-capacity">{{ $totalCapacity }}</span></span>
                </div>
                <span id="stat-hero-available-tag" class="text-xs font-semibold text-black clean-border px-2.5 py-0.5 rounded">
                    {{ $totalCapacity }} Slot Kosong
                </span>
                <div class="flex items-center gap-1.5 mt-2 pt-2 clean-border-t text-[10px] font-mono">
                    <span class="px-1.5 py-0.5 rounded bg-sky-50 text-[#00719C] font-bold clean-border">LT1: <b id="adm-lt1">{{ $parkedLt1 }}</b>/10</span>
                    <span class="px-1.5 py-0.5 rounded bg-amber-50 text-amber-800 font-bold clean-border">LT2: <b id="adm-lt2">{{ $parkedLt2 }}</b>/10</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. STAT SUMMARY CARDS (4 Columns) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Slot Terisi -->
        <div class="editorial-card p-5 bg-[#FFFFFF] flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-black/60">Slot Terpakai</span>
                <div class="w-8 h-8 rounded-md bg-[#00719C] text-white flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/></svg>
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-1.5">
                <span id="stat-parked-count" class="text-2xl lg:text-3xl font-bold text-black font-mono">0</span>
                <span class="text-xs text-black/50 font-medium">kendaraan</span>
            </div>
            <div class="mt-2 pt-2 clean-border-t text-xs text-black/60 font-medium flex items-center gap-1.5">
                <span class="status-dot-active"></span>
                <span>Okupansi realtime</span>
            </div>
        </div>

        <!-- Card 2: Slot Kosong -->
        <div class="editorial-card p-5 bg-[#FFFFFF] flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-black/60">Slot Kosong</span>
                <div class="w-8 h-8 rounded-md bg-[#00719C] text-white flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-1.5">
                <span id="stat-available-count" class="text-2xl lg:text-3xl font-bold text-black font-mono">{{ $totalCapacity }}</span>
                <span class="text-xs text-black/50 font-medium">slot kosong</span>
            </div>
            <div class="mt-2 pt-2 clean-border-t text-xs text-black/60 font-medium">
                Siap digunakan
            </div>
        </div>

        <!-- Card 3: Masuk Hari Ini -->
        <div class="editorial-card p-5 bg-[#FFFFFF] flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-black/60">Masuk Hari Ini</span>
                <div class="w-8 h-8 rounded-md bg-[#00719C] text-white flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" x2="3" y1="12" y2="12"/></svg>
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-1.5">
                <span id="stat-today-in" class="text-2xl lg:text-3xl font-bold text-black font-mono">0</span>
                <span class="text-xs text-black/50 font-medium">tap-in</span>
            </div>
            <div class="mt-2 pt-2 clean-border-t text-xs text-black/60 font-medium">
                Total kedatangan
            </div>
        </div>

        <!-- Card 4: Keluar Hari Ini -->
        <div class="editorial-card p-5 bg-[#FFFFFF] flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-black/60">Keluar Hari Ini</span>
                <div class="w-8 h-8 rounded-md bg-[#00719C] text-white flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-1.5">
                <span id="stat-today-out" class="text-2xl lg:text-3xl font-bold text-black font-mono">0</span>
                <span class="text-xs text-black/50 font-medium">transaksi</span>
            </div>
            <div class="mt-2 pt-2 clean-border-t text-xs text-black/60 font-medium">
                Total kepulangan
            </div>
        </div>
    </div>

    <!-- 3. LIVE VEHICLE LIST CONTAINER -->
    <div class="editorial-card p-6 bg-[#FFFFFF]">
        <div class="flex items-center justify-between pb-4 mb-4 clean-border-b">
            <div>
                <h3 class="text-base font-bold text-black flex items-center gap-2">
                    <span>Daftar Kendaraan yang Sedang Terparkir</span>
                </h3>
                <p class="text-xs text-black/60 mt-0.5">Pantau durasi berjalan dan estimasi biaya parkir setiap kendaraan secara realtime</p>
            </div>
            <span id="vehicles-badge-count" class="text-xs font-semibold px-2.5 py-1 rounded clean-border text-black font-mono">
                0 Kendaraan Aktif
            </span>
        </div>

        <!-- Vehicle Grid -->
        <div id="admin-vehicle-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Diisi secara dinamis via JavaScript -->
        </div>

        <!-- Empty State Placeholder -->
        <div id="admin-empty-state" class="hidden py-12 text-center">
            <div class="w-12 h-12 mx-auto rounded-md bg-[#00719C] text-white flex items-center justify-center mb-3">
                <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
            </div>
            <h4 class="text-sm font-bold text-black mb-1">Area Parkir Saat Ini Kosong</h4>
            <p class="text-xs text-black/60 max-w-md mx-auto">
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
        // 1. Stats Counter
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

        // 2. Hero Card Banner State
        const heroLine = document.getElementById('status-hero-line');
        const heroIconBox = document.getElementById('status-hero-iconbox');
        const heroTitle = document.getElementById('status-hero-title');
        const heroDesc = document.getElementById('status-hero-desc');
        const heroAvailTag = document.getElementById('stat-hero-available-tag');

        if (data.has_vehicles) {
            // Ada kendaraan
            heroLine.className = 'absolute top-0 left-0 right-0 h-1 bg-[#FFD51E]';
            heroIconBox.className = 'w-16 h-16 sm:w-20 sm:h-20 rounded-md bg-[#00719C] text-white flex items-center justify-center flex-shrink-0';
            heroTitle.innerHTML = `Ada <span class="text-[#00719C]">${data.parked_count} Kendaraan</span> Sedang Parkir`;
            heroDesc.innerText = `Sisa ${data.available_slots} slot dari total kapasitas ${data.capacity} kendaraan. Tarif aktif berjalan Rp2.000 / Jam.`;
            heroAvailTag.className = 'text-xs font-semibold text-black clean-border px-2.5 py-0.5 rounded';
            heroAvailTag.innerText = `Sisa ${data.available_slots} Slot Kosong`;
        } else {
            // Kosong
            heroLine.className = 'absolute top-0 left-0 right-0 h-1 bg-[#00719C]';
            heroIconBox.className = 'w-16 h-16 sm:w-20 sm:h-20 rounded-md bg-[#00719C] text-white flex items-center justify-center flex-shrink-0';
            heroTitle.innerHTML = `Area Parkir Kosong &amp; Siap Digunakan`;
            heroDesc.innerText = `Tidak ada kendaraan di dalam area parkir. Semua ${data.capacity} slot dalam kondisi kosong.`;
            heroAvailTag.className = 'text-xs font-semibold text-black clean-border px-2.5 py-0.5 rounded';
            heroAvailTag.innerText = `Semua ${data.capacity} Slot Kosong`;
        }

        // 3. Grid Rendering
        const grid = document.getElementById('admin-vehicle-grid');
        const emptyState = document.getElementById('admin-empty-state');

        if (!data.has_vehicles) {
            grid.innerHTML = '';
            emptyState.classList.remove('hidden');
            adminVehicles = [];
        } else {
            emptyState.classList.add('hidden');
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
                const card = document.createElement('div');
                card.setAttribute('data-admin-v-id', v.id);
                card.className = 'clean-border rounded-md p-4 bg-white flex flex-col justify-between hover:border-[#00719C] transition';
                const floorNum = parseInt(v.parking_floor) || 1;
                const floorBadge = `<span class="px-1.5 py-0.2 rounded text-[10px] font-bold clean-border ${floorNum === 2 ? 'bg-amber-100 text-amber-900 border-amber-300' : 'bg-sky-100 text-[#00719C] border-sky-300'}">LT ${floorNum}</span>`;

                card.innerHTML = `
                    <div>
                        <div class="flex items-center justify-between pb-3 clean-border-b">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-md bg-[#00719C] text-white flex items-center justify-center text-xs font-bold flex-shrink-0">
                                    <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/></svg>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-black">${escapeHtml(v.user_name)}</div>
                                    <div class="text-[11px] font-mono text-black/60">UID: <strong class="text-black">${escapeHtml(v.uid)}</strong></div>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5">
                                ${floorBadge}
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold clean-border text-black flex items-center gap-1">
                                    <span class="status-dot-active"></span>
                                    PARKED
                                </span>
                            </div>
                        </div>

                        <div class="my-3 grid grid-cols-2 gap-2 text-center">
                            <div class="p-2.5 rounded clean-border bg-[#FFFFFF]">
                                <span class="text-[10px] uppercase font-semibold text-black/60 block mb-0.5">Durasi Parkir</span>
                                <div id="adm-timer-${v.id}" class="font-mono text-sm font-bold text-black">
                                    00:00:00
                                </div>
                                <span class="text-[9px] text-black/40 block">Jam:Mnt:Dtk</span>
                            </div>

                            <div class="p-2.5 rounded clean-border bg-[#FFFFFF]">
                                <span class="text-[10px] uppercase font-semibold text-[#00719C] block mb-0.5">Estimasi Tarif</span>
                                <div id="adm-fee-${v.id}" class="font-mono text-sm font-bold text-black">
                                    ${v.fee_formatted}
                                </div>
                                <span id="adm-hours-${v.id}" class="text-[9px] text-black/50 block font-medium">${v.billed_hours} Jam Dihitung</span>
                            </div>
                        </div>

                        <div class="pt-2.5 clean-border-t flex items-center justify-between text-[11px] text-black/60 mb-3">
                            <div class="flex items-center gap-1.5 truncate">
                                <svg class="w-3.5 h-3.5 text-[#00719C]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" x2="3" y1="12" y2="12"/></svg>
                                <span class="font-semibold text-black truncate">${escapeHtml(v.gate_in)}</span>
                            </div>
                            <div class="font-mono">
                                Masuk: <strong class="text-black">${v.tap_in_formatted}</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Selesaikan Parkir Manual jika Gerbang Error -->
                    <button type="button" 
                            onclick="checkoutManualAdmin('${escapeHtml(v.uid)}', '${escapeHtml(v.user_name)}', this)"
                            title="Selesaikan transaksi parkir ini secara manual jika gerbang atau pemindai mengalami kendala"
                            class="btn-selesai-status btn-accent w-full py-2 px-3 text-xs flex items-center justify-center gap-2 text-black cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                        <div class="w-4 h-4 rounded bg-[#00719C] text-white flex items-center justify-center text-[9px] flex-shrink-0">
                            <svg class="w-3 h-3 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
                        </div>
                        <span>Selesaikan Parkir</span>
                    </button>
                `;
                grid.appendChild(card);
            });
        }

        updateAdminTimers();
    }

    // Checkout Manual jika Gerbang Keluar Error / Macet (Anti-Spam & Menunggu)
    let isProcessingStatusCheckout = false;
    async function checkoutManualAdmin(uid, userName, btnElement = null) {
        if (isProcessingStatusCheckout) {
            if (typeof showToast === 'function') {
                showToast('Mohon Menunggu', 'Sedang memproses penyelesaian parkir...', 'warning');
            }
            return;
        }

        if (!confirm(`Selesaikan parkir untuk "${userName}" (UID: ${uid})?\n\nOpsi ini digunakan jika gerbang keluar atau sensor RFID mengalami kendala. Transaksi akan diselesaikan, slot parkir dikosongkan, dan palang keluar dibuka.`)) {
            return;
        }

        isProcessingStatusCheckout = true;
        const allBtns = document.querySelectorAll('.btn-selesai-status');
        allBtns.forEach(b => b.disabled = true);

        let origHtml = '';
        if (btnElement) {
            origHtml = btnElement.innerHTML;
            btnElement.innerHTML = `<i class="fa-solid fa-spinner fa-spin text-xs"></i><span>Menunggu...</span>`;
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
                fetchAdminParkingStatus();
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
            const elTimer = document.getElementById(`adm-timer-${v.id}`);
            const elFee = document.getElementById(`adm-fee-${v.id}`);
            const elHours = document.getElementById(`adm-hours-${v.id}`);
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
