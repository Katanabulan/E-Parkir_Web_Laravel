@extends('layouts.admin')

@section('title', 'Gate Simulator & Monitoring IoT')
@section('header_title', 'Gate Simulator & Hardware IoT')
@section('header_subtitle', 'Simulasi visual pergerakan palang servo, lampu indikator gerbang (Merah & Hijau), audio buzzer, dan pengujian kartu RFID')

@section('content')
<!-- SIMULATOR CONTAINER -->
<div class="row g-4">

    <!-- LEFT COLUMN: VISUAL HARDWARE INTERACTION (7 Cols) -->
    <div class="col-12 col-lg-7 d-flex flex-column gap-4">

        <!-- DUAL GATE CONTROLLER CARD -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white position-relative overflow-hidden">
            <!-- Header bar -->
            <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between pb-3 border-bottom gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <h5 class="fw-bold mb-0 text-dark">Gerbang Fisik IoT</h5>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                            <i class="bi bi-cpu me-1"></i>ESP8266
                        </span>
                        <span id="esp-status-badge" class="badge bg-light text-secondary border">
                            Memeriksa ESP...
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-2 mt-1">
                        <small class="text-muted font-monospace">
                            <i class="bi bi-arrow-down-circle-fill text-success me-1"></i>GATE_MASUK_01
                            &nbsp;·&nbsp;
                            <i class="bi bi-arrow-up-circle-fill text-danger me-1"></i>GATE_KELUAR_01
                        </small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <input type="hidden" id="device-select" value="GATE_MASUK_01">
                    <div class="form-check form-switch small">
                        <input class="form-check-input" type="checkbox" role="switch" id="autoprint-toggle" checked>
                        <label class="form-check-label small fw-semibold text-dark" for="autoprint-toggle">
                            <i class="bi bi-printer text-primary me-1"></i>Auto-Cetak
                        </label>
                    </div>
                    <button type="button" onclick="connectBluetoothPrinter()" id="btn-quick-bt-connect" class="btn btn-primary btn-sm rounded-3 d-flex align-items-center gap-1 shadow-sm">
                        <i class="bi bi-bluetooth"></i>
                        <span id="bt-quick-label">Connect EP589</span>
                    </button>
                    <button type="button" onclick="testPrintBluetooth()" id="btn-quick-bt-test" class="btn btn-warning btn-sm rounded-3 fw-bold d-flex align-items-center gap-1 shadow-sm">
                        <i class="bi bi-receipt"></i>
                        <span>Tes EP589</span>
                    </button>
                    <button type="button" onclick="openAllGates()" class="btn btn-danger btn-sm rounded-3 fw-bold d-flex align-items-center gap-1 shadow-sm" title="Buka Palang Masuk & Keluar Bersamaan">
                        <i class="bi bi-shield-lock-fill"></i>
                        <span>Buka Semua (ALL)</span>
                    </button>
                    <button type="button" onclick="closeBarrierGate()" class="btn btn-outline-secondary btn-sm rounded-3 fw-semibold d-flex align-items-center gap-1 shadow-sm" title="Tutup Semua Palang Gerbang">
                        <i class="bi bi-lock-fill"></i>
                        <span>Tutup Semua</span>
                    </button>
                </div>
            </div>

            <!-- DUAL GATE ROW -->
            <div class="row g-3 mt-1">

                <!-- ===== GATE IN (MASUK) ===== -->
                <div class="col-12 col-md-6">
                    <div class="rounded-4 p-3 h-100 d-flex flex-column gap-2" style="background: #0d1627; border: 1px solid #1e3a5f;">
                        <!-- Gate IN Header -->
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-success rounded-pill px-3 fw-bold" style="font-size: 0.75rem;">
                                    <i class="bi bi-arrow-down-circle-fill me-1"></i>GATE IN
                                </span>
                                <span id="gate-state-badge-in" class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.7rem;">
                                    TERTUTUP (0°)
                                </span>
                            </div>
                            <!-- Timer IN -->
                            <div id="timer-box-in" class="badge bg-warning text-dark px-2 py-1 rounded-pill font-monospace fw-bold d-none align-items-center gap-1" style="font-size: 0.7rem;">
                                <i class="bi bi-stopwatch"></i>
                                <span><span id="timer-count-in">5</span>s</span>
                            </div>
                        </div>

                        <!-- Gate IN Canvas -->
                        <div class="rounded-3 position-relative overflow-hidden user-select-none" style="background: #060d1a; height: 200px; border: 1px solid #1e293b;">
                            <!-- Traffic Light IN -->
                            <div class="position-absolute d-flex flex-column align-items-center gap-1 p-1 rounded-3" style="top: 8px; left: 8px; background: rgba(0,0,0,0.7); border: 1px solid #334155; z-index:5;">
                                <div style="font-size: 0.55rem;" class="text-secondary fw-bold">IN</div>
                                <div id="led-red-in" title="Merah: Tertutup" style="width: 16px; height: 16px; border-radius: 50%; background-color: #ef4444; box-shadow: 0 0 12px #ef4444, 0 0 20px rgba(239,68,68,0.7); border: 1px solid #fca5a5; transition: all 0.3s ease;"></div>
                                <div id="led-green-in" title="Hijau: Terbuka" style="width: 16px; height: 16px; border-radius: 50%; background-color: #022c22; border: 1px solid #064e3b; opacity: 0.35; transition: all 0.3s ease;"></div>
                            </div>

                            <!-- Road -->
                            <div class="position-absolute bottom-0 start-0 end-0 d-flex align-items-center justify-content-around px-2" style="height: 32px; background: #1e293b; border-top: 1px solid #475569;">
                                <span class="bg-warning rounded" style="width: 36px; height: 3px; opacity: 0.7;"></span>
                                <span class="bg-warning rounded" style="width: 36px; height: 3px; opacity: 0.7;"></span>
                                <span class="bg-warning rounded" style="width: 36px; height: 3px; opacity: 0.7;"></span>
                            </div>

                            <!-- Gate Cabinet IN -->
                            <div style="position: absolute; bottom: 32px; left: 28px; width: 54px; height: 110px; background: #0d6efd; border: 2px solid rgba(255,255,255,0.3); border-radius: 8px 8px 0 0; z-index: 10;" class="d-flex flex-column align-items-center justify-content-between py-2">
                                <div class="rounded-circle bg-dark border border-warning" style="width: 10px; height: 10px;"></div>
                                <div class="text-white text-center fw-bold text-uppercase" style="font-size: 0.55rem; line-height: 1.1;">MASUK<br><span class="text-warning">01</span></div>
                                <div class="bg-warning rounded-pill" style="width: 28px; height: 4px;"></div>
                                <!-- Pivot Hub IN -->
                                <div style="position: absolute; right: -12px; top: 16px; width: 24px; height: 24px; border-radius: 50%; background: #0f172a; border: 2px solid #ffc107; z-index: 20;">
                                    <div style="position: absolute; left: 7px; top: 7px; width: 6px; height: 6px; border-radius: 50%; background: #0d6efd;"></div>
                                    <!-- ARM IN -->
                                    <div id="gate-arm-in" style="position: absolute; left: 10px; top: 6px; width: 160px; height: 10px; transform-origin: 0px 5px; transform: rotate(0deg); transition: transform 1.0s cubic-bezier(0.4,0,0.2,1); z-index: 30; pointer-events: none;">
                                        <div style="width: 100%; height: 100%; display: flex; border-radius: 0 3px 3px 0; overflow: hidden; box-shadow: 0 3px 8px rgba(0,0,0,0.4);">
                                            <div style="flex:1; background:#ffc107;"></div>
                                            <div style="flex:1; background:#fff;"></div>
                                            <div style="flex:1; background:#ffc107;"></div>
                                            <div style="flex:1; background:#fff;"></div>
                                            <div style="flex:1; background:#ffc107;"></div>
                                            <div style="flex:1; background:#fff;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- RFID IN -->
                            <div style="position: absolute; bottom: 32px; right: 16px; width: 32px; height: 72px; background: #0d6efd; border: 2px solid rgba(255,255,255,0.3); border-radius: 6px 6px 0 0; z-index: 10;" class="d-flex flex-column align-items-center pt-2">
                                <div class="bg-dark border border-white rounded text-white fw-bold text-center" style="width: 22px; height: 22px; font-size: 0.45rem; line-height: 22px;">RFID</div>
                                <div class="rounded-circle bg-success mt-1" style="width: 6px; height: 6px; box-shadow: 0 0 6px #22c55e;"></div>
                            </div>
                        </div><!-- /Gate IN Canvas -->

                        <!-- Gate IN Action Buttons (Bootstrap Controls) -->
                        <div class="pt-2 border-top border-secondary border-opacity-25 mt-auto">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <small class="text-white-50 fw-semibold text-uppercase" style="font-size: 0.68rem;">Kontrol Gate Masuk:</small>
                                <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.65rem;">GATE_MASUK_01</span>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <button type="button" onclick="openBarrierGateIn(8000)" class="btn btn-success btn-sm rounded-3 fw-semibold d-flex align-items-center justify-content-center gap-1 flex-grow-1 shadow-sm" title="Buka Palang Masuk (8 detik)">
                                    <i class="bi bi-unlock-fill"></i>
                                    <span>Buka Palang</span>
                                </button>
                            </div>
                        </div>

                    </div><!-- /Gate IN Card -->
                </div><!-- /col-12 col-md-6 -->

                <!-- ===== GATE OUT (KELUAR) ===== -->
                <div class="col-12 col-md-6">
                    <div class="rounded-4 p-3 h-100 d-flex flex-column gap-2" style="background: #0d1627; border: 1px solid #3f1f5f;">
                        <!-- Gate OUT Header -->
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-danger rounded-pill px-3 fw-bold" style="font-size: 0.75rem;">
                                    <i class="bi bi-arrow-up-circle-fill me-1"></i>GATE OUT
                                </span>
                                <span id="gate-state-badge-out" class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.7rem;">
                                    TERTUTUP (0°)
                                </span>
                            </div>
                            <!-- Timer OUT -->
                            <div id="timer-box-out" class="badge bg-warning text-dark px-2 py-1 rounded-pill font-monospace fw-bold d-none align-items-center gap-1" style="font-size: 0.7rem;">
                                <i class="bi bi-stopwatch"></i>
                                <span><span id="timer-count-out">5</span>s</span>
                            </div>
                        </div>

                        <!-- Gate OUT Canvas -->
                        <div class="rounded-3 position-relative overflow-hidden user-select-none" style="background: #060d1a; height: 200px; border: 1px solid #1e293b;">
                            <!-- Traffic Light OUT -->
                            <div class="position-absolute d-flex flex-column align-items-center gap-1 p-1 rounded-3" style="top: 8px; left: 8px; background: rgba(0,0,0,0.7); border: 1px solid #334155; z-index:5;">
                                <div style="font-size: 0.55rem;" class="text-secondary fw-bold">OUT</div>
                                <div id="led-red-out" title="Merah: Tertutup" style="width: 16px; height: 16px; border-radius: 50%; background-color: #ef4444; box-shadow: 0 0 12px #ef4444, 0 0 20px rgba(239,68,68,0.7); border: 1px solid #fca5a5; transition: all 0.3s ease;"></div>
                                <div id="led-green-out" title="Hijau: Terbuka" style="width: 16px; height: 16px; border-radius: 50%; background-color: #022c22; border: 1px solid #064e3b; opacity: 0.35; transition: all 0.3s ease;"></div>
                            </div>

                            <!-- Road -->
                            <div class="position-absolute bottom-0 start-0 end-0 d-flex align-items-center justify-content-around px-2" style="height: 32px; background: #1e293b; border-top: 1px solid #475569;">
                                <span class="bg-warning rounded" style="width: 36px; height: 3px; opacity: 0.7;"></span>
                                <span class="bg-warning rounded" style="width: 36px; height: 3px; opacity: 0.7;"></span>
                                <span class="bg-warning rounded" style="width: 36px; height: 3px; opacity: 0.7;"></span>
                            </div>

                            <!-- Gate Cabinet OUT -->
                            <div style="position: absolute; bottom: 32px; left: 28px; width: 54px; height: 110px; background: #dc3545; border: 2px solid rgba(255,255,255,0.3); border-radius: 8px 8px 0 0; z-index: 10;" class="d-flex flex-column align-items-center justify-content-between py-2">
                                <div class="rounded-circle bg-dark border border-warning" style="width: 10px; height: 10px;"></div>
                                <div class="text-white text-center fw-bold text-uppercase" style="font-size: 0.55rem; line-height: 1.1;">KELUAR<br><span class="text-warning">01</span></div>
                                <div class="bg-warning rounded-pill" style="width: 28px; height: 4px;"></div>
                                <!-- Pivot Hub OUT -->
                                <div style="position: absolute; right: -12px; top: 16px; width: 24px; height: 24px; border-radius: 50%; background: #0f172a; border: 2px solid #ffc107; z-index: 20;">
                                    <div style="position: absolute; left: 7px; top: 7px; width: 6px; height: 6px; border-radius: 50%; background: #dc3545;"></div>
                                    <!-- ARM OUT -->
                                    <div id="gate-arm-out" style="position: absolute; left: 10px; top: 6px; width: 160px; height: 10px; transform-origin: 0px 5px; transform: rotate(0deg); transition: transform 1.0s cubic-bezier(0.4,0,0.2,1); z-index: 30; pointer-events: none;">
                                        <div style="width: 100%; height: 100%; display: flex; border-radius: 0 3px 3px 0; overflow: hidden; box-shadow: 0 3px 8px rgba(0,0,0,0.4);">
                                            <div style="flex:1; background:#ffc107;"></div>
                                            <div style="flex:1; background:#fff;"></div>
                                            <div style="flex:1; background:#ffc107;"></div>
                                            <div style="flex:1; background:#fff;"></div>
                                            <div style="flex:1; background:#ffc107;"></div>
                                            <div style="flex:1; background:#fff;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- RFID Reader Pedestal OUT -->
                            <div style="position: absolute; bottom: 32px; right: 16px; width: 32px; height: 72px; background: #dc3545; border: 2px solid rgba(255,255,255,0.3); border-radius: 6px 6px 0 0; z-index: 10;" class="d-flex flex-column align-items-center pt-2">
                                <div class="bg-dark border border-white rounded text-white fw-bold text-center" style="width: 22px; height: 22px; font-size: 0.45rem; line-height: 22px;">RFID</div>
                                <div class="rounded-circle bg-danger mt-1" style="width: 6px; height: 6px; box-shadow: 0 0 6px #ef4444;"></div>
                            </div>
                        </div><!-- /Gate OUT Canvas -->

                        <!-- Gate OUT Action Buttons (Bootstrap Controls) -->
                        <div class="pt-2 border-top border-secondary border-opacity-25 mt-auto">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <small class="text-white-50 fw-semibold text-uppercase" style="font-size: 0.68rem;">Kontrol Gate Keluar:</small>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.65rem;">GATE_KELUAR_01</span>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <button type="button" onclick="openBarrierGateOut(8000)" class="btn btn-danger btn-sm rounded-3 fw-semibold d-flex align-items-center justify-content-center gap-1 flex-grow-1 shadow-sm" title="Buka Palang Keluar (8 detik)">
                                    <i class="bi bi-unlock-fill"></i>
                                    <span>Buka Palang</span>
                                </button>
                            </div>
                        </div>

                    </div><!-- /Gate OUT Card -->
                </div><!-- /col-12 col-md-6 -->

            </div><!-- /row dual gate -->
        </div><!-- /DUAL GATE CONTROLLER CARD -->

    </div>

    <!-- RIGHT COLUMN: STATUS PARKIR REAL-TIME (5 Cols) -->
    <div class="col-12 col-lg-5 d-flex flex-column gap-4">

        <!-- Parking Status Card -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <div class="d-flex align-items-center justify-content-between pb-3 border-bottom">
                <div>
                    <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-speedometer2 text-primary"></i>
                        <span>Status Parkir Real-Time</span>
                    </h6>
                    <small class="text-muted">Ketersediaan slot & kendaraan di dalam</small>
                </div>
                <div id="side-status-badge" class="badge bg-light text-dark border">
                    <span id="side-status-badge-text">{{ $parkedCount > 0 ? 'ADA KENDARAAN' : 'PARKIR KOSONG' }}</span>
                </div>
            </div>

            <!-- Capacity Progress & Counters -->
            <div class="py-3">
                <div class="d-flex align-items-center justify-content-between small fw-semibold mb-2">
                    <span class="text-secondary">Okupansi Slot</span>
                    <span class="font-monospace"><strong id="side-parked-count" class="text-primary fs-6">{{ $parkedCount }}</strong> / <span id="side-total-capacity">{{ $totalCapacity }}</span> Kendaraan</span>
                </div>
                <div class="progress" style="height: 10px;">
                    <div id="side-progress-bar" class="progress-bar {{ $parkedCount >= $totalCapacity ? 'bg-danger' : 'bg-primary' }}" role="progressbar"
                         style="width: {{ min(100, round(($parkedCount / $totalCapacity) * 100)) }}%"></div>
                </div>
                <div class="d-flex align-items-center justify-content-between small text-muted mt-2">
                    <span id="side-available-text" class="text-dark fw-semibold d-flex align-items-center gap-1">
                        <i class="bi bi-check-circle text-primary"></i>
                        <span>{{ $availableSlots }} Slot Tersedia</span>
                    </span>
                    <span id="side-occupancy-percent" class="font-monospace">
                        {{ round(($parkedCount / $totalCapacity) * 100) }}% Terisi
                    </span>
                </div>

                <!-- Per-Floor Status Badges -->
                <div class="row g-2 mt-3 pt-3 border-top">
                    <div class="col-6">
                        <div class="card bg-light border p-2 rounded-3 h-100">
                            <div class="d-flex align-items-center justify-content-between small fw-bold text-primary">
                                <span>LANTAI 1 (LT 1)</span>
                                <span id="floor-badge-lt1" class="badge {{ $parkedLt1 >= 10 ? 'bg-danger' : 'bg-primary' }}">{{ $parkedLt1 >= 10 ? 'PENUH' : 'TERSEDIA' }}</span>
                            </div>
                            <div class="mt-2 d-flex align-items-baseline justify-content-between small">
                                <strong class="font-monospace text-dark"><span id="side-lt1-parked">{{ $parkedLt1 }}</span> / 10</strong>
                                <small class="text-muted">Sisa <strong id="side-lt1-avail" class="text-primary">{{ $availableLt1 }}</strong></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card bg-light border p-2 rounded-3 h-100">
                            <div class="d-flex align-items-center justify-content-between small fw-bold text-warning-emphasis">
                                <span>LANTAI 2 (LT 2)</span>
                                <span id="floor-badge-lt2" class="badge {{ $parkedLt2 >= 10 ? 'bg-danger' : 'bg-warning text-dark' }}">{{ $parkedLt2 >= 10 ? 'PENUH' : 'TERSEDIA' }}</span>
                            </div>
                            <div class="mt-2 d-flex align-items-baseline justify-content-between small">
                                <strong class="font-monospace text-dark"><span id="side-lt2-parked">{{ $parkedLt2 }}</span> / 10</strong>
                                <small class="text-muted">Sisa <strong id="side-lt2-avail" class="text-warning-emphasis">{{ $availableLt2 }}</strong></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- List Container: Ada Kendaraan ATAU Parkiran Kosong -->
            <div class="pt-3 border-top">
                <div class="d-flex align-items-center justify-content-between small text-secondary fw-semibold text-uppercase mb-3">
                    <span>Daftar Kendaraan Terparkir</span>
                    <small class="font-monospace text-muted">Live Sync</small>
                </div>

                <!-- Empty State -->
                <div id="side-empty-state" class="{{ $parkedCount === 0 ? 'd-flex' : 'd-none' }} flex-column align-items-center justify-content-center py-4 text-center border rounded-3 p-3 bg-light">
                    <span class="badge bg-success-subtle text-success fs-3 p-3 rounded-circle mb-2">
                        <i class="bi bi-check2-all"></i>
                    </span>
                    <div class="small fw-bold text-dark">Tidak Ada Kendaraan Terparkir</div>
                    <small class="text-muted mt-1 max-w-xs">
                        Semua {{ $totalCapacity }} slot parkir saat ini kosong dan siap digunakan.
                    </small>
                </div>

                <!-- List Kendaraan -->
                <div id="side-vehicles-list" class="d-flex flex-column gap-2 overflow-y-auto pe-1 {{ $parkedCount > 0 ? '' : 'd-none' }}" style="max-height: 320px;">
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
                            <div class="card border rounded-3 p-2.5 bg-white d-flex flex-row align-items-center justify-content-between gap-2 shadow-sm">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge {{ $isTicket ? 'bg-warning text-dark' : 'bg-primary' }} p-2 rounded-3 fs-6">
                                        <i class="bi {{ $isTicket ? 'bi-ticket-perforated-fill' : 'bi-car-front-fill' }}"></i>
                                    </span>
                                    <div>
                                        <div class="d-flex align-items-center gap-1">
                                            @if($isTicket)
                                                <strong class="font-monospace text-warning-emphasis small">{{ $pv->ticket_code }}</strong>
                                                <span class="badge bg-warning-subtle text-warning border" style="font-size: 0.65rem;">TIKET</span>
                                            @else
                                                <strong class="font-monospace text-dark small">{{ $licensePlate }}</strong>
                                                <span class="badge bg-light text-dark border" style="font-size: 0.65rem;">MEMBER</span>
                                            @endif
                                            <span class="badge {{ ($pv->parking_floor ?? 1) == 2 ? 'bg-warning text-dark' : 'bg-info text-dark' }}" style="font-size: 0.65rem;">LT {{ $pv->parking_floor ?? 1 }}</span>
                                        </div>
                                        <div class="small fw-semibold text-dark text-truncate" style="max-width: 140px;">{{ $userName }}</div>
                                        <small class="text-muted font-monospace">
                                            Masuk: {{ date('H:i', strtotime($pv->tap_in_time)) }} &bull; <span class="text-primary fw-bold">{{ $diffMin }} mnt</span>
                                        </small>
                                    </div>
                                </div>
                                <div class="text-end d-flex flex-column align-items-end gap-1">
                                    <strong class="font-monospace text-dark small">Rp {{ number_format($fee, 0, ',', '.') }}</strong>
                                    <small class="text-muted" style="font-size: 0.7rem;">{{ $billedHours }} Jam Parkir</small>
                                    <button type="button" 
                                            onclick="checkoutManualAdmin('{{ $scanUid }}', '{{ addslashes($userName) }}', this)"
                                            class="btn btn-outline-danger btn-sm py-0 px-2 rounded-2 btn-selesai-checkout" style="font-size: 0.75rem;">
                                        <i class="bi bi-box-arrow-right me-1"></i>Selesaikan
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                <!-- Tombol ke Halaman Status Lengkap -->
                <div class="mt-3 pt-3 border-top">
                    <a href="{{ route('admin.status.index') }}" class="btn btn-outline-primary btn-sm w-100 rounded-3 d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-grid-3x3-gap-fill"></i>
                        <span>Buka Denah Slot Parkir Lengkap</span>
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL STRUK PARKIR (EPPOS EP589 V2) -->
<div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom">
                <h6 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="receiptModalLabel">
                    <i class="bi bi-receipt text-primary"></i>
                    <span>Struk Parkir (58mm)</span>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="bg-light p-3 rounded-3 border border-dashed font-monospace small leading-relaxed user-select-all" id="receipt-preview-content" style="max-height: 250px; overflow-y: auto; white-space: pre;">
Memuat struk parkir...
                </div>
                <div class="row g-2 mt-3">
                    <div class="col-6">
                        <button type="button" id="btn-modal-bt" onclick="printReceiptViaBluetooth()" class="btn btn-primary btn-sm w-100 rounded-3 d-flex align-items-center justify-content-center gap-1">
                            <i class="bi bi-bluetooth"></i>
                            <span>Bluetooth</span>
                        </button>
                    </div>
                    <div class="col-6">
                        <button type="button" onclick="printReceiptViaBrowser()" class="btn btn-warning btn-sm w-100 rounded-3 fw-bold d-flex align-items-center justify-content-center gap-1">
                            <i class="bi bi-printer"></i>
                            <span>Driver/USB</span>
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top bg-light p-2 d-flex justify-content-between">
                <a id="btn-open-receipt-link" href="#" target="_blank" class="small text-decoration-none">Tab Baru</a>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
<!-- MODAL RFID SIMULATOR -->
<div class="modal fade" id="rfidSimModal" tabindex="-1" aria-labelledby="rfidSimModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom">
                <h6 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="rfidSimModalLabel">
                    <i class="bi bi-credit-card-2-front-fill text-primary"></i>
                    <span>Simulasi Tap Kartu RFID</span>
                    <span id="rfid-modal-target-badge" class="badge bg-primary ms-1">Gate IN</span>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <!-- Manual UID -->
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-muted text-uppercase">Input UID Manual:</label>
                    <div class="input-group">
                        <span class="input-group-text bg-dark border-secondary text-primary font-monospace">
                            <i class="bi bi-broadcast-pin"></i>
                        </span>
                        <input type="text" id="rfid-manual-uid" class="form-control bg-dark text-white border-secondary font-monospace"
                               placeholder="Contoh: A1B2C3D4" autocomplete="off">
                        <button type="button" onclick="tapManualUid()" class="btn btn-primary fw-bold d-flex align-items-center gap-1 px-3">
                            <i class="bi bi-wifi"></i>
                            <span>TAP!</span>
                        </button>
                    </div>
                </div>
                <!-- Registered Cards -->
                <div>
                    <small class="text-uppercase fw-bold text-secondary" style="font-size: 0.72rem;">Kartu Terdaftar (Klik untuk Simulasi Tap):</small>
                    <div class="d-flex flex-column gap-2 mt-2" style="max-height: 280px; overflow-y: auto;">
                        @forelse($testCards as $card)
                            <div class="card border rounded-3 p-2 bg-light d-flex flex-row align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-primary p-2 rounded-3">
                                        <i class="bi bi-person-fill"></i>
                                    </span>
                                    <div>
                                        <div class="fw-semibold small text-dark">{{ $card->user->name ?? 'Tanpa Pemilik' }}</div>
                                        <div class="font-monospace text-muted" style="font-size: 0.72rem;">UID: {{ $card->uid }}</div>
                                        @if($card->balance !== null)
                                            <div class="text-success" style="font-size: 0.7rem;">Saldo: Rp {{ number_format($card->balance, 0, ',', '.') }}</div>
                                        @endif
                                    </div>
                                </div>
                                <button type="button"
                                        onclick="simulateCardTap('{{ $card->uid }}')"
                                        class="btn btn-sm btn-primary rounded-3 d-flex align-items-center gap-1 fw-bold">
                                    <i class="bi bi-wifi"></i>
                                    <span style="font-size: 0.75rem;">TAP</span>
                                </button>
                            </div>
                        @empty
                            <div class="text-center text-muted small py-3">
                                <i class="bi bi-credit-card me-1"></i>Belum ada kartu RFID terdaftar
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top bg-light p-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // ── Device codes (fixed, tidak perlu dipilih) ─────────────────────
    const DEVICE_IN  = 'GATE_MASUK_01';
    const DEVICE_OUT = 'GATE_KELUAR_01';
    // backward-compat (beberapa fungsi lama masih pakai currentDevice)
    let currentDevice = DEVICE_IN;
    let isGateInOpen  = false;
    let isGateOutOpen = false;
    // timers IN
    let gateInTimer = null, gateInPhase1 = null, gateInPhase2 = null, gateInCountdown = null;
    // timers OUT
    let gateOutTimer = null, gateOutPhase1 = null, gateOutPhase2 = null, gateOutCountdown = null;
    // polling
    let lastProcessedLogId = 0;
    let isHardwarePollerReady = false;
    // RFID modal target ('IN' | 'OUT')
    let rfidModalTarget = 'IN';

    function switchDevice(dev) { /* deprecated — gate sudah fixed IN/OUT */ }
    async function pingEspWifi(espIp) {
        if (!espIp) return false;
        try {
            const ctrl = new AbortController();
            const tid = setTimeout(() => ctrl.abort(), 2000);
            await fetch(`http://${espIp}/api/status`, {
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

    let lastSyncedBackendIp = null;

    async function syncBackendIpToEsp(espIp, serverIp) {
        if (!espIp || !serverIp || serverIp === '127.0.0.1' || serverIp === 'localhost' || lastSyncedBackendIp === serverIp) return;
        try {
            const ctrl = new AbortController();
            const tid = setTimeout(() => ctrl.abort(), 2500);
            await fetch(`http://${espIp}/api/set_backend?ip=${encodeURIComponent(serverIp)}`, {
                method: 'GET',
                mode: 'no-cors',
                cache: 'no-store',
                signal: ctrl.signal
            });
            clearTimeout(tid);
            lastSyncedBackendIp = serverIp;
            console.log(`[IoT Auto-Sync] Berhasil memperbarui backend host ESP8266 ke: ${serverIp}`);
        } catch (e) {
            console.warn('[IoT Auto-Sync] Gagal push backend IP ke ESP:', e);
        }
    }

    async function checkHardwareSync() {
        try {
            const statsRes = await fetch('{{ route('admin.api.stats') }}').then(r => r.json()).catch(() => null);
            const isWifiDirect = (statsRes && statsRes.esp_ip) ? await pingEspWifi(statsRes.esp_ip) : false;

            if (statsRes && statsRes.esp_ip) {
                const targetServerIp = (statsRes.server_ip && statsRes.server_ip !== '127.0.0.1' && statsRes.server_ip !== '::1') 
                    ? statsRes.server_ip 
                    : window.location.hostname;
                syncBackendIpToEsp(statsRes.esp_ip, targetServerIp);
            }

            const badge = document.getElementById('esp-status-badge');
            if (badge) {
                let isConnected = isWifiDirect || (statsRes && statsRes.esp_online);

                if (statsRes && statsRes.status === 'success' && statsRes.devices) {
                    // Cek apakah salah satu dari kedua gate terhubung
                    const devIn  = statsRes.devices.find(d => d.device_code === DEVICE_IN);
                    const devOut = statsRes.devices.find(d => d.device_code === DEVICE_OUT);
                    const anyDev = devIn || devOut;
                    if (anyDev) {
                        const now = new Date((statsRes.server_time || '').replace(' ', 'T')).getTime();
                        const lastSeen = anyDev.last_seen ? new Date(anyDev.last_seen.replace(' ', 'T')).getTime() : 0;
                        const diffSec = Math.max(0, Math.round((now - lastSeen) / 1000));
                        if (isConnected || (lastSeen > 0 && diffSec <= 25)) {
                            isConnected = true;
                        }
                    }
                }

                if (isConnected) {
                    badge.className = 'badge bg-success-subtle text-success border border-success-subtle d-inline-flex align-items-center gap-1';
                    badge.innerHTML = `<span class="spinner-grow spinner-grow-sm text-success" style="width: 6px; height: 6px;" role="status"></span><span>Terhubung ke ESP8266</span>`;
                } else {
                    badge.className = 'badge bg-danger-subtle text-danger border border-danger-subtle d-inline-flex align-items-center gap-1';
                    badge.innerHTML = `<i class="bi bi-x-circle-fill text-danger"></i><span>Tidak Terhubung ke ESP8266</span>`;
                }
            }

            if (!statsRes || statsRes.status !== 'success') return;
            const data = statsRes;

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
                            showToast('Karcis Masuk Dicetak!', 'Silakan ambil tiket karcis Anda. Palang akan terbuka sebentar lagi...', 'info');

                            const badgeEl = document.getElementById('gate-state-badge-in');
                            if (badgeEl) {
                                badgeEl.className = 'badge bg-warning-subtle text-warning border border-warning-subtle animate-pulse';
                                badgeEl.textContent = 'AMBIL TIKET...';
                            }

                            fetch('{{ url("/receipt/latest-ticket/escpos") }}')
                                .then(r => r.json())
                                .then(d => {
                                    if (d && d.status === 'success' && d.raw_text) {
                                        if (!printedTicketTrxIds.has(d.transaction_id)) {
                                            autoPrintTicketEntry(d.ticket_code, d.raw_text, d.transaction_id);
                                        }

                                        if (d.transaction_id && !openedTicketGates.has(d.transaction_id)) {
                                            openedTicketGates.add(d.transaction_id);
                                            setTimeout(() => {
                                                openBarrierGateIn(8000);
                                            }, 2500);
                                        }
                                    }
                                }).catch(err => {
                                    console.error('[AUTO-PRINT] Gagal fetch data tiket karcis:', err);
                                });

                        } else {
                            // Tentukan gate mana berdasarkan device_code log, bukan currentDevice
                            const isGateOut = latestLog.device_code === DEVICE_OUT || isTapOut;

                            if (latestLog.device_code === DEVICE_IN || latestLog.device_code === DEVICE_OUT || isTapOut) {
                                showToast('Akses Hardware Diterima', latestLog.notes || 'Palang fisik dibuka!', 'success');
                                if (isGateOut) openBarrierGateOut(5000);
                                else            openBarrierGateIn(5000);
                            }

                            if (isTapOut && !isTicketLog) {
                                fetch('{{ url("/receipt/latest/escpos") }}')
                                    .then(r => r.json())
                                    .then(d => {
                                        if (d && d.status === 'success' && d.receipt) {
                                            autoPrintReceipt(d.receipt.id, d.raw_text);
                                        }
                                    }).catch(err => {
                                        console.error('[AUTO-PRINT] Gagal fetch data struk:', err);
                                    });
                            }
                        }
                    } else if (latestLog.device_code === DEVICE_IN || latestLog.device_code === DEVICE_OUT) {
                        showToast('Akses Hardware Ditolak', latestLog.notes || 'Akses ditolak pada perangkat fisik!', 'danger');
                        const isOut = latestLog.device_code === DEVICE_OUT;
                        blinkRedLed(isOut ? 'OUT' : 'IN');
                    }
                }
            }
        } catch (e) {}
    }
    setInterval(checkHardwareSync, 1500);
    checkHardwareSync();

    function triggerEmergencySimulator(target) {
        const gate = target || 'IN';
        openEmergencyModal(gate === 'OUT' ? DEVICE_OUT : DEVICE_IN);
    }

    function openAllGates() {
        openBarrierGate(8000, 'IN');
        setTimeout(() => openBarrierGate(8000, 'OUT'), 300);
    }

    // ── Traffic Light helpers ──────────────────────────────────────────
    function setTrafficLightIn(color) {
        const r = document.getElementById('led-red-in');
        const g = document.getElementById('led-green-in');
        if (!r || !g) return;
        r.style.backgroundColor = '#450a0a'; r.style.boxShadow = 'none'; r.style.borderColor = '#7f1d1d'; r.style.opacity = '0.35';
        g.style.backgroundColor = '#022c22'; g.style.boxShadow = 'none'; g.style.borderColor = '#064e3b'; g.style.opacity = '0.35';
        if (color === 'red')   { r.style.backgroundColor = '#ef4444'; r.style.boxShadow = '0 0 12px #ef4444, 0 0 20px rgba(239,68,68,0.7)'; r.style.borderColor = '#fca5a5'; r.style.opacity = '1'; }
        if (color === 'green') { g.style.backgroundColor = '#10b981'; g.style.boxShadow = '0 0 12px #10b981, 0 0 20px rgba(16,185,129,0.85)'; g.style.borderColor = '#6ee7b7'; g.style.opacity = '1'; }
    }

    function setTrafficLightOut(color) {
        const r = document.getElementById('led-red-out');
        const g = document.getElementById('led-green-out');
        if (!r || !g) return;
        r.style.backgroundColor = '#450a0a'; r.style.boxShadow = 'none'; r.style.borderColor = '#7f1d1d'; r.style.opacity = '0.35';
        g.style.backgroundColor = '#022c22'; g.style.boxShadow = 'none'; g.style.borderColor = '#064e3b'; g.style.opacity = '0.35';
        if (color === 'red')   { r.style.backgroundColor = '#ef4444'; r.style.boxShadow = '0 0 12px #ef4444, 0 0 20px rgba(239,68,68,0.7)'; r.style.borderColor = '#fca5a5'; r.style.opacity = '1'; }
        if (color === 'green') { g.style.backgroundColor = '#10b981'; g.style.boxShadow = '0 0 12px #10b981, 0 0 20px rgba(16,185,129,0.85)'; g.style.borderColor = '#6ee7b7'; g.style.opacity = '1'; }
    }

    // Legacy alias (backward compat dengan checkHardwareSync lama)
    function setTrafficLight(color) { setTrafficLightIn(color); setTrafficLightOut(color); }

    // ── Clear gate timers ─────────────────────────────────────────────
    function clearGateInTimers()  { [gateInTimer, gateInPhase1, gateInPhase2].forEach(t => t && clearTimeout(t));  if (gateInCountdown)  clearInterval(gateInCountdown);  gateInTimer = gateInPhase1 = gateInPhase2 = gateInCountdown = null; }
    function clearGateOutTimers() { [gateOutTimer, gateOutPhase1, gateOutPhase2].forEach(t => t && clearTimeout(t)); if (gateOutCountdown) clearInterval(gateOutCountdown); gateOutTimer = gateOutPhase1 = gateOutPhase2 = gateOutCountdown = null; }
    function clearAllGateTimers() { clearGateInTimers(); clearGateOutTimers(); }

    // ── Generic open/close helpers ────────────────────────────────────
    function _startCountdown(timerBoxId, timerCountId, durationMs, onDone) {
        const box = document.getElementById(timerBoxId);
        const cnt = document.getElementById(timerCountId);
        if (!box || !cnt) return null;
        box.classList.remove('d-none'); box.classList.add('d-flex');
        let rem = Math.round(durationMs / 1000);
        cnt.textContent = rem;
        const iv = setInterval(() => { rem--; if (rem >= 0) cnt.textContent = rem; else clearInterval(iv); }, 1000);
        return iv;
    }
    function _hideTimer(timerBoxId) {
        const box = document.getElementById(timerBoxId);
        if (box) { box.classList.add('d-none'); box.classList.remove('d-flex'); }
    }

    // ── Open Gate IN ──────────────────────────────────────────────────
    function openBarrierGateIn(durationMs = 5000) {
        clearGateInTimers();
        const arm   = document.getElementById('gate-arm-in');
        const badge = document.getElementById('gate-state-badge-in');

        if (isGateInOpen) {
            setTrafficLightIn('green');
            if (badge) { badge.className = 'badge bg-success-subtle text-success border border-success-subtle'; badge.textContent = 'TERBUKA (90°)'; }
            if (arm) arm.style.transform = 'rotate(-90deg)';
            gateInCountdown = _startCountdown('timer-box-in', 'timer-count-in', durationMs);
            gateInTimer = setTimeout(() => closeBarrierGateIn(), durationMs);
            return;
        }

        setTrafficLightIn('green');
        playWebBuzzer(2, 'high');
        if (badge) { badge.className = 'badge bg-warning-subtle text-warning border border-warning-subtle'; badge.textContent = 'MEMBUKA (NAIK)...'; }

        gateInPhase1 = setTimeout(() => {
            if (arm) arm.style.transform = 'rotate(-90deg)';
            gateInPhase2 = setTimeout(() => {
                isGateInOpen = true;
                setTrafficLightIn('green');
                if (badge) { badge.className = 'badge bg-success-subtle text-success border border-success-subtle'; badge.textContent = 'TERBUKA (90°)'; }
                gateInCountdown = _startCountdown('timer-box-in', 'timer-count-in', durationMs);
                gateInTimer = setTimeout(() => closeBarrierGateIn(), durationMs);
            }, 1000);
        }, 1020);
    }

    function closeBarrierGateIn() {
        clearGateInTimers();
        const arm   = document.getElementById('gate-arm-in');
        const badge = document.getElementById('gate-state-badge-in');
        playWebBuzzer(1, 'medium');
        if (badge) { badge.className = 'badge bg-warning-subtle text-warning border border-warning-subtle'; badge.textContent = 'MENUTUP (TURUN)...'; }
        gateInPhase1 = setTimeout(() => {
            if (arm) arm.style.transform = 'rotate(0deg)';
            gateInPhase2 = setTimeout(() => {
                isGateInOpen = false;
                setTrafficLightIn('red');
                _hideTimer('timer-box-in');
                if (badge) { badge.className = 'badge bg-danger-subtle text-danger border border-danger-subtle'; badge.textContent = 'TERTUTUP (0°)'; }
            }, 1000);
        }, 1000);
    }

    // ── Open Gate OUT ─────────────────────────────────────────────────
    function openBarrierGateOut(durationMs = 5000) {
        clearGateOutTimers();
        const arm   = document.getElementById('gate-arm-out');
        const badge = document.getElementById('gate-state-badge-out');

        if (isGateOutOpen) {
            setTrafficLightOut('green');
            if (badge) { badge.className = 'badge bg-success-subtle text-success border border-success-subtle'; badge.textContent = 'TERBUKA (90°)'; }
            if (arm) arm.style.transform = 'rotate(-90deg)';
            gateOutCountdown = _startCountdown('timer-box-out', 'timer-count-out', durationMs);
            gateOutTimer = setTimeout(() => closeBarrierGateOut(), durationMs);
            return;
        }

        setTrafficLightOut('green');
        playWebBuzzer(2, 'high');
        if (badge) { badge.className = 'badge bg-warning-subtle text-warning border border-warning-subtle'; badge.textContent = 'MEMBUKA (NAIK)...'; }

        gateOutPhase1 = setTimeout(() => {
            if (arm) arm.style.transform = 'rotate(-90deg)';
            gateOutPhase2 = setTimeout(() => {
                isGateOutOpen = true;
                setTrafficLightOut('green');
                if (badge) { badge.className = 'badge bg-success-subtle text-success border border-success-subtle'; badge.textContent = 'TERBUKA (90°)'; }
                gateOutCountdown = _startCountdown('timer-box-out', 'timer-count-out', durationMs);
                gateOutTimer = setTimeout(() => closeBarrierGateOut(), durationMs);
            }, 1000);
        }, 1020);
    }

    function closeBarrierGateOut() {
        clearGateOutTimers();
        const arm   = document.getElementById('gate-arm-out');
        const badge = document.getElementById('gate-state-badge-out');
        playWebBuzzer(1, 'medium');
        if (badge) { badge.className = 'badge bg-warning-subtle text-warning border border-warning-subtle'; badge.textContent = 'MENUTUP (TURUN)...'; }
        gateOutPhase1 = setTimeout(() => {
            if (arm) arm.style.transform = 'rotate(0deg)';
            gateOutPhase2 = setTimeout(() => {
                isGateOutOpen = false;
                setTrafficLightOut('red');
                _hideTimer('timer-box-out');
                if (badge) { badge.className = 'badge bg-danger-subtle text-danger border border-danger-subtle'; badge.textContent = 'TERTUTUP (0°)'; }
            }, 1000);
        }, 1000);
    }

    // ── Legacy alias: openBarrierGate(ms, target) ─────────────────────
    function openBarrierGate(durationMs = 5000, target) {
        const t = (target || 'IN').toUpperCase();
        if (t === 'OUT')  openBarrierGateOut(durationMs);
        else if (t === 'BOTH') { openBarrierGateIn(durationMs); setTimeout(() => openBarrierGateOut(durationMs), 300); }
        else openBarrierGateIn(durationMs);
    }
    function closeBarrierGate() { closeBarrierGateIn(); closeBarrierGateOut(); }

    // ── Blink red LED ─────────────────────────────────────────────────
    function blinkRedLed(target) {
        const gate = (target || 'IN').toUpperCase();
        const id = gate === 'OUT' ? 'led-red-out' : 'led-red-in';
        const setFn = gate === 'OUT' ? setTrafficLightOut : setTrafficLightIn;
        setFn('none');
        const ledRed = document.getElementById(id);
        if (!ledRed) return;
        let count = 0;
        const blinker = setInterval(() => {
            if (count % 2 === 0) { ledRed.style.backgroundColor = '#450a0a'; ledRed.style.boxShadow = 'none'; ledRed.style.opacity = '0.3'; }
            else { ledRed.style.backgroundColor = '#ef4444'; ledRed.style.boxShadow = '0 0 20px #ef4444, 0 0 30px rgba(239,68,68,0.8)'; ledRed.style.borderColor = '#fca5a5'; ledRed.style.opacity = '1'; }
            count++;
            if (count > 5) { clearInterval(blinker); setFn('red'); }
        }, 120);
    }

    // ── Ticket simulation (Gate IN) ───────────────────────────────────
    async function triggerTicketSimulation() {
        try {
            const res = await fetch('{{ route('admin.api.stats') }}').then(r => r.json()).catch(() => null);
            if (!res) { showToast('Simulasi Karcis', 'Koneksi server tidak tersedia', 'danger'); return; }
            showToast('Ambil Karcis Masuk', 'Memproses karcis parkir masuk...', 'info');
            const badge = document.getElementById('gate-state-badge-in');
            if (badge) { badge.className = 'badge bg-warning-subtle text-warning border border-warning-subtle animate-pulse'; badge.textContent = 'AMBIL TIKET...'; }
            setTimeout(() => openBarrierGateIn(8000), 2500);
        } catch (e) {
            showToast('Error', e.message, 'danger');
        }
    }

    // ── RFID Modal ────────────────────────────────────────────────────
    function openRfidModal(target) {
        rfidModalTarget = target || 'IN';
        const badge = document.getElementById('rfid-modal-target-badge');
        if (badge) {
            badge.textContent = rfidModalTarget === 'OUT' ? 'Gate OUT (Keluar)' : 'Gate IN (Masuk)';
            badge.className   = rfidModalTarget === 'OUT' ? 'badge bg-danger ms-1' : 'badge bg-success ms-1';
        }
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('rfidSimModal'));
        modal.show();
    }

    async function simulateCardTap(uid) {
        const deviceCode = rfidModalTarget === 'OUT' ? 'GATE_KELUAR_01' : 'GATE_MASUK_01';
        try {
            const res = await fetch('{{ url('/api/scan') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ device_code: deviceCode, scan_uid: uid })
            });
            const data = await res.json();
            if (data.status === 'success') {
                playWebBuzzer(2);
                showToast('✅ Kartu Diterima', data.message || 'Palang dibuka!', 'success');
                if (rfidModalTarget === 'OUT') openBarrierGateOut(data.duration_ms || 5000);
                else                           openBarrierGateIn(data.duration_ms || 5000);
                bootstrap.Modal.getOrCreateInstance(document.getElementById('rfidSimModal')).hide();
            } else {
                playWebBuzzer(3, 'low');
                showToast('❌ Kartu Ditolak', data.message || 'Akses ditolak', 'danger');
                blinkRedLed(rfidModalTarget);
            }
        } catch (err) {
            showToast('Error', err.message, 'danger');
        }
    }

    function tapManualUid() {
        const inp = document.getElementById('rfid-manual-uid');
        const uid = inp ? inp.value.trim() : '';
        if (!uid) { showToast('RFID Simulator', 'Masukkan UID terlebih dahulu', 'warning'); return; }
        simulateCardTap(uid);
        if (inp) inp.value = '';
    }

    function focusBarcodeScanner() {
        const inp = document.getElementById('barcode-scan-input');
        if (inp) { inp.focus(); inp.select(); }
        showToast('Scanner Siap', 'Arahkan barcode karcis ke scanner atau ketik kode karcis', 'info');
    }

    function showTicketModal(ticketText, ticketCode, receiptUrl) {
        currentReceiptRawText = ticketText;
        const previewEl = document.getElementById('receipt-preview-content');
        if (previewEl) previewEl.textContent = ticketText;

        const openLink = document.getElementById('btn-open-receipt-link');
        if (openLink && receiptUrl) openLink.href = receiptUrl;

        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('receiptModal'));
        modal.show();
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
            lt1Badge.className = `badge ${countLt1 >= 10 ? 'bg-danger' : 'bg-primary'}`;
        }

        if (lt2CountEl) lt2CountEl.textContent = countLt2;
        if (lt2AvailEl) lt2AvailEl.textContent = availLt2;
        if (lt2Badge) {
            lt2Badge.textContent = countLt2 >= 10 ? 'PENUH' : 'TERSEDIA';
            lt2Badge.className = `badge ${countLt2 >= 10 ? 'bg-danger' : 'bg-warning text-dark'}`;
        }

        if (parkedCountEl) parkedCountEl.textContent = parked;
        if (occPercent) occPercent.textContent = pct + '% Terisi';
        if (availText) {
            availText.innerHTML = `<i class="bi bi-check-circle text-primary me-1"></i>${available} Slot Tersedia`;
        }

        if (progressBar) {
            progressBar.style.width = pct + '%';
            if (parked >= totalCap) {
                progressBar.className = 'progress-bar bg-danger';
            } else if (parked > 0) {
                progressBar.className = 'progress-bar bg-primary';
            } else {
                progressBar.className = 'progress-bar bg-success';
            }
        }

        if (badge && badgeText) {
            if (parked > 0) {
                badge.className = 'badge bg-warning-subtle text-warning border border-warning-subtle';
                badge.innerHTML = `<span class="spinner-grow spinner-grow-sm text-warning me-1" style="width: 6px; height: 6px;" role="status"></span><span id="side-status-badge-text">ADA KENDARAAN (${parked})</span>`;
            } else {
                badge.className = 'badge bg-light text-dark border';
                badge.innerHTML = `<span id="side-status-badge-text">PARKIR KOSONG</span>`;
            }
        }

        if (parked === 0) {
            if (emptyState) {
                emptyState.classList.remove('d-none');
                emptyState.classList.add('d-flex');
            }
            if (listContainer) {
                listContainer.classList.add('d-none');
                listContainer.innerHTML = '';
            }
        } else {
            if (emptyState) {
                emptyState.classList.add('d-none');
                emptyState.classList.remove('d-flex');
            }
            if (listContainer) {
                listContainer.classList.remove('d-none');
                let html = '';
                list.forEach(v => {
                    const timeStr = v.tap_in_time ? v.tap_in_time.substring(11, 16) : '-';
                    const isTicket = v.is_ticket || Boolean(v.ticket_code);
                    const scanId = isTicket ? v.ticket_code : (v.uid || '');
                    const nameEscaped = (v.user_name || (isTicket ? 'Pengendara Umum' : 'Pengendara')).replace(/'/g, "\\'");
                    const floorNum = parseInt(v.parking_floor) || 1;
                    const floorBadge = `<span class="badge ${floorNum === 2 ? 'bg-warning text-dark' : 'bg-info text-dark'}" style="font-size: 0.65rem;">LT ${floorNum}</span>`;
                    html += `
                        <div class="card border rounded-3 p-2.5 bg-white d-flex flex-row align-items-center justify-content-between gap-2 shadow-sm">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge ${isTicket ? 'bg-warning text-dark' : 'bg-primary'} p-2 rounded-3 fs-6">
                                    <i class="bi ${isTicket ? 'bi-ticket-perforated-fill' : 'bi-car-front-fill'}"></i>
                                </span>
                                <div>
                                    <div class="d-flex align-items-center gap-1">
                                        ${isTicket 
                                            ? `<strong class="font-monospace text-warning-emphasis small">${v.ticket_code || scanId}</strong><span class="badge bg-warning-subtle text-warning border" style="font-size: 0.65rem;">TIKET</span>`
                                            : `<strong class="font-monospace text-dark small">${v.license_plate || 'NO-PLAT'}</strong><span class="badge bg-light text-dark border" style="font-size: 0.65rem;">MEMBER</span>`
                                        }
                                        ${floorBadge}
                                    </div>
                                    <div class="small fw-semibold text-dark text-truncate" style="max-width: 140px;">${v.user_name || (isTicket ? 'Pengendara Umum (Karcis)' : 'Pengendara')}</div>
                                    <small class="text-muted font-monospace">
                                        Masuk: ${timeStr} &bull; <span class="text-primary fw-bold">${v.duration_minutes || 1} mnt</span>
                                    </small>
                                </div>
                            </div>
                            <div class="text-end d-flex flex-column align-items-end gap-1">
                                <strong class="font-monospace text-dark small">${v.estimated_fee_formatted || 'Rp 2.000'}</strong>
                                <small class="text-muted" style="font-size: 0.7rem;">${v.billed_hours || 1} Jam Parkir</small>
                                <button type="button" 
                                        onclick="checkoutManualAdmin('${scanId}', '${nameEscaped}', this)"
                                        class="btn btn-outline-danger btn-sm py-0 px-2 rounded-2 btn-selesai-checkout" style="font-size: 0.75rem;">
                                    <i class="bi bi-box-arrow-right me-1"></i>Selesaikan
                                </button>
                            </div>
                        </div>
                    `;
                });
                listContainer.innerHTML = html;
            }
        }

        // Update Quick-Scan Barcode Karcis Aktif
        const quickTicketContainer = document.getElementById('quick-ticket-buttons');
        const quickTicketCountEl = document.getElementById('quick-ticket-count');
        if (quickTicketContainer) {
            const ticketList = Array.isArray(list) ? list.filter(v => v.is_ticket || Boolean(v.ticket_code)) : [];
            if (quickTicketCountEl) quickTicketCountEl.textContent = `${ticketList.length} Karcis`;

            if (ticketList.length === 0) {
                quickTicketContainer.innerHTML = '<span class="text-muted small fst-italic">Tidak ada kendaraan dengan karcis masuk saat ini.</span>';
            } else {
                quickTicketContainer.innerHTML = ticketList.map(t => {
                    const tCode = t.ticket_code || t.uid;
                    const floorNum = parseInt(t.parking_floor) || 1;
                    return `
                        <button type="button" onclick="scanTicketBarcode('${tCode}')" class="btn btn-outline-warning btn-sm rounded-pill font-monospace fw-bold d-flex align-items-center gap-1.5 py-1 px-2.5 shadow-sm" style="font-size: 0.75rem;" title="Klik untuk simulasi scan barcode kasir">
                            <i class="bi bi-upc-scan text-danger"></i>
                            <span>${tCode}</span>
                            <span class="badge bg-warning text-dark ms-1">LT ${floorNum}</span>
                        </button>
                    `;
                }).join('');
            }
        }
    }

    // =========================================================================
    // SIMULASI SCANNER BARCODE POS KASIR (GIMMICK SCAN KARCIS PARKIR)
    // =========================================================================
    function handleBarcodeScannerSubmit(e) {
        if (e) e.preventDefault();
        const input = document.getElementById('barcode-scan-input');
        if (!input) return;
        const code = input.value.trim();
        if (!code) {
            showToast('Scan Barcode', 'Silakan masukkan atau scan kode barcode karcis', 'warning');
            return;
        }
        scanTicketBarcode(code);
        input.value = '';
    }

    async function scanTicketBarcode(ticketCode) {
        if (!ticketCode) return;
        ticketCode = ticketCode.trim().toUpperCase();

        const beam = document.getElementById('sim-laser-beam');
        const statusText = document.getElementById('scanner-status-text');

        // Visual laser sweep gimmick
        if (beam) {
            beam.style.height = '4px';
            beam.style.boxShadow = '0 0 20px #ef4444, 0 0 35px rgba(255, 0, 0, 0.9)';
            beam.style.top = '25%';
            setTimeout(() => { if (beam) beam.style.top = '75%'; }, 180);
            setTimeout(() => { 
                if (beam) {
                    beam.style.top = '50%';
                    beam.style.height = '2px';
                    beam.style.boxShadow = '0 0 12px #ef4444, 0 0 20px rgba(239, 68, 68, 0.8)';
                }
            }, 380);
        }

        // Scanner Audio Beep
        playWebBuzzer(2, 'high');

        if (statusText) {
            statusText.innerHTML = `<span class="text-warning fw-bold font-monospace">[BEEP!] Memindai Barcode #${ticketCode}...</span>`;
        }

        showToast('Barcode Di-scan!', `Memindai karcis #${ticketCode}. Menghitung durasi & membuka palang...`, 'info');

        try {
            const res = await fetch('{{ url('/api/scan') }}', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    device_code: 'GATE_KELUAR_01',
                    scan_uid: ticketCode
                })
            });
            const data = await res.json();

            if (data.status === 'success') {
                playWebBuzzer(2);
                showToast('✅ Karcis Valid!', `${data.message} - Biaya: ${data.data?.total_fee_formatted || ''}`, 'success');
                if (statusText) {
                    statusText.innerHTML = `<span class="text-success fw-bold font-monospace">✓ Karcis #${ticketCode} Selesai (${data.data?.total_fee_formatted || ''}) - Palang Dibuka</span>`;
                }

                openBarrierGateOut(data.duration_ms || 6000);

                if (data.data && data.data.receipt_id) {
                    setTimeout(() => {
                        openReceiptModal(data.data.receipt_id);
                    }, 800);
                }

                checkHardwareSync();
            } else {
                playWebBuzzer(3, 'low');
                showToast('❌ Scan Gagal', data.message, 'danger');
                if (statusText) {
                    statusText.innerHTML = `<span class="text-danger fw-bold font-monospace">✗ ${data.message}</span>`;
                }
            }
        } catch (err) {
            console.error('Error scan barcode:', err);
            showToast('Error', err.message, 'danger');
            if (statusText) {
                statusText.innerHTML = `<span class="text-danger small font-monospace">Error: ${err.message}</span>`;
            }
        }
    }

    let isProcessingCheckout = false;
    async function checkoutManualAdmin(uid, userName, btnElement = null) {
        if (isProcessingCheckout) {
            showToast('Mohon Menunggu', 'Sedang memproses penyelesaian parkir...', 'warning');
            return;
        }

        if (!confirm(`Selesaikan parkir untuk "${userName}" (UID: ${uid}) secara manual?`)) {
            return;
        }

        isProcessingCheckout = true;
        const allBtns = document.querySelectorAll('.btn-selesai-checkout');
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
                showToast('Parkir Diselesaikan!', `${data.message} - Total Biaya: ${data.data?.total_fee_formatted || ''}`, 'success');
                playWebBuzzer(2);
                checkHardwareSync();

                if (data.action === 'OPEN' || data.flow === 'TAP_OUT_TICKET' || data.flow === 'TAP_OUT_TICKET_COMPLETED') {
                    openBarrierGateOut(data.duration_ms || 5000);
                }

                if (data.data && data.data.receipt_id && !data.data.ticket_code && data.flow !== 'TAP_OUT_TICKET') {
                    autoPrintReceipt(data.data.receipt_id);
                }
            } else {
                showToast('Gagal Selesaikan Parkir', data.message, 'danger');
                playWebBuzzer(3, 'low');
            }
        } catch (err) {
            showToast('Error', err.message, 'danger');
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

    let currentReceiptTrxId = null;
    let currentReceiptRawText = '';
    let btDevice = null;
    let btPrinterChar = null;

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

    async function connectBluetoothPrinter() {
        const label = document.getElementById('bt-quick-label');
        const btn = document.getElementById('btn-quick-bt-connect');

        if (!navigator.bluetooth) {
            alert('Browser ini belum mendukung Web Bluetooth. Gunakan Google Chrome atau Edge.');
            return;
        }

        try {
            label.innerText = 'Mencari...';
            btn.classList.add('placeholder-glow');

            btDevice = await navigator.bluetooth.requestDevice({
                acceptAllDevices: true,
                optionalServices: [
                    '000018f0-0000-1000-8000-00805f9b34fb',
                    '0000ffe0-0000-1000-8000-00805f9b34fb',
                    '0000ff00-0000-1000-8000-00805f9b34fb',
                    '0000fee7-0000-1000-8000-00805f9b34fb',
                    '0000ae00-0000-1000-8000-00805f9b34fb',
                    '0000af00-0000-1000-8000-00805f9b34fb',
                    'e7810a71-73ae-499d-8c15-faa9aef0c3f2',
                    '49535343-fe7d-4ae5-8fa9-9fafd205e455'
                ]
            });

            label.innerText = 'Connecting...';
            const server = await btDevice.gatt.connect();

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

            if (!btPrinterChar) throw new Error('Karakteristik Bluetooth Write tidak ditemukan.');

            label.innerText = 'EP589 Terhubung ✓';
            btn.className = 'btn btn-success btn-sm rounded-3 d-flex align-items-center gap-1 shadow-sm';
            btn.classList.remove('placeholder-glow');
            showToast('Printer Terhubung!', 'Eppos EP589 V2 siap auto-print otomatis', 'success');

            btDevice.addEventListener('gattserverdisconnected', () => {
                btPrinterChar = null;
                label.innerText = 'Connect EP589';
                btn.className = 'btn btn-primary btn-sm rounded-3 d-flex align-items-center gap-1 shadow-sm';
                showToast('Printer Terputus', 'Bluetooth Eppos EP589 terputus.', 'danger');
            });

        } catch (err) {
            console.error(err);
            label.innerText = 'Connect EP589';
            btn.classList.remove('placeholder-glow');
            if (err.name !== 'NotFoundError') {
                alert('Gagal menghubungkan Bluetooth: ' + err.message);
            }
        }
    }

    async function testPrintBluetooth() {
        if (!btPrinterChar) {
            const connectNow = confirm('Printer Eppos EP589 V2 belum terhubung.\n\nHubungkan sekarang via Bluetooth?');
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
"Sistem: E-Parkir Laravel 13     \n" +
"================================\n\n\n\n";

        try {
            showToast('Mencetak Tes...', 'Mengirim data ke Eppos EP589 V2...', 'info');
            await sendEscposToBluetooth(testText);
            showToast('Tes Cetak Berhasil!', 'Struk tes berhasil keluar dari printer!', 'success');
        } catch (e) {
            alert('Gagal tes cetak: ' + e.message);
        }
    }

    const printedTicketTrxIds = new Set();
    const printedReceiptTrxIds = new Set();
    const openedTicketGates = new Set();
    let lastIframePrintedTrxId = null;

    async function autoPrintTicketEntry(ticketCode, rawText, trxId) {
        if (trxId && printedTicketTrxIds.has(trxId)) return;
        if (trxId) printedTicketTrxIds.add(trxId);

        currentReceiptTrxId = trxId;
        currentReceiptRawText = rawText;
        const isAutoPrintActive = document.getElementById('autoprint-toggle')?.checked ?? true;

        const previewEl = document.getElementById('receipt-preview-content');
        if (previewEl && currentReceiptRawText) previewEl.textContent = currentReceiptRawText;

        if (btPrinterChar && isAutoPrintActive) {
            showToast('Mencetak Karcis...', 'Mengirim data ke Eppos EP589 V2...', 'info');
            await sendEscposToBluetooth(currentReceiptRawText);
            showToast('Karcis Masuk Dicetak!', 'Tiket #' + ticketCode + ' berhasil dicetak', 'success');
            return;
        }

        showToast('Karcis Dicetak', 'Karcis #' + ticketCode + ' siap.', 'info');
    }

    async function autoPrintReceipt(trxId, preloadedRawText = null) {
        if (trxId && printedReceiptTrxIds.has(trxId)) return;
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

            if (btPrinterChar && isAutoPrintActive) {
                showToast('Mencetak Struk...', 'Mengirim data ke Eppos EP589 V2...', 'info');
                await sendEscposToBluetooth(currentReceiptRawText);
                showToast('Struk Dicetak!', 'Struk transaksi #' + trxId + ' berhasil dicetak', 'success');
                return;
            }

            showToast('Transaksi Selesai', 'Struk transaksi #' + trxId + ' siap.', 'success');

        } catch (e) {
            console.error('Error auto-print:', e);
        }
    }

    async function sendEscposToBluetooth(text) {
        if (!btPrinterChar || !text) return;
        try {
            const encoder = new TextEncoder();
            const initCmd = new Uint8Array([0x1B, 0x40]);
            await writeBleData(btPrinterChar, initCmd);
            await new Promise(r => setTimeout(r, 40));

            const textBytes = encoder.encode(text);
            const chunkSize = 20;
            for (let i = 0; i < textBytes.length; i += chunkSize) {
                const chunk = textBytes.slice(i, i + chunkSize);
                await writeBleData(btPrinterChar, chunk);
                await new Promise(r => setTimeout(r, 20));
            }

            const feedCmd = new Uint8Array([0x0A, 0x0A, 0x0A, 0x0A]);
            await writeBleData(btPrinterChar, feedCmd);
        } catch (bleErr) {
            console.error('sendEscposToBluetooth error:', bleErr);
            alert('Gagal mengirim ke printer Bluetooth: ' + bleErr.message);
        }
    }

    function printSilentlyViaIframe(trxId) {
        if (lastIframePrintedTrxId === trxId) return;
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
        const previewEl = document.getElementById('receipt-preview-content');
        const linkEl = document.getElementById('btn-open-receipt-link');
        
        if (linkEl) linkEl.href = '{{ url("/receipt") }}/' + trxId;
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('receiptModal'));
        modal.show();

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
        showToast('Berhasil', 'Struk dicetak ke Bluetooth', 'success');
    }
</script>
@endpush
