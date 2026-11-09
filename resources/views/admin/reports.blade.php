@extends('layouts.admin')

@section('title', 'Laporan Pendapatan & Transaksi')
@section('header_title', 'Laporan Pendapatan & Lalu Lintas')
@section('header_subtitle', 'Rekapitulasi total pendapatan, filter periode & waktu, serta audit transaksi')

@section('content')
<!-- PRINT SECTION -->
<style>
@media print {
    thead {
        display: table-row-group !important;
    }
    thead tr {
        page-break-inside: avoid !important;
        page-break-before: avoid !important;
    }
}
</style>

<div class="d-none d-print-block mb-4 text-dark">
    <!-- Kop Surat Resmi -->
    <div class="border-bottom border-2 pb-2 mb-3 d-flex align-items-center justify-content-between">
        <div>
            <small class="text-uppercase fw-bold text-primary">E-Parkir IoT Platform &bull; Sigma Boys</small>
            <h4 class="fw-bold mb-0">LAPORAN REKAPITULASI PENDAPATAN & TRANSAKSI PARKIR</h4>
            <small class="text-muted">Sistem Palang Pintu Otomatis & Pemotongan Saldo RFID Real-Time</small>
        </div>
        <div class="text-end font-monospace small">
            <div><strong>Tanggal Cetak:</strong> {{ date('d/m/Y H:i') }} WIB</div>
            <div><strong>Dicetak Oleh:</strong> {{ auth()->user()->name ?? 'Administrator' }}</div>
            <div><strong>Status Sistem:</strong> Gate-01 Online</div>
        </div>
    </div>

    <!-- Parameter Filter Info Badge Print -->
    <div class="card bg-light border p-2 mb-3">
        <div class="row g-2 small">
            <div class="col-4">
                <span class="text-muted text-uppercase d-block" style="font-size: 0.75rem;">Periode Laporan:</span>
                <strong class="font-monospace">{{ $startDate ?: 'Semua' }} {{ $startTime ? "($startTime)" : '' }} s/d {{ $endDate ?: 'Semua' }} {{ $endTime ? "($endTime)" : '' }}</strong>
            </div>
            <div class="col-4">
                <span class="text-muted text-uppercase d-block" style="font-size: 0.75rem;">Filter Status:</span>
                <strong class="font-monospace">{{ $statusFilter === 'COMPLETED' ? 'Hanya Selesai' : ($statusFilter === 'PARKED' ? 'Hanya Sedang Parkir' : 'Semua Transaksi') }}</strong>
            </div>
            <div class="col-4">
                <span class="text-muted text-uppercase d-block" style="font-size: 0.75rem;">Kata Kunci:</span>
                <strong class="font-monospace">{{ $search ?: 'Tidak ada (Semua)' }}</strong>
            </div>
        </div>
    </div>

    <!-- Summary Box Print -->
    <div class="row g-2 mb-3 text-center">
        <div class="col-3">
            <div class="border p-2 rounded">
                <small class="text-uppercase text-muted">Total Pendapatan</small>
                <div class="fw-bold fs-6">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</div>
            </div>
        </div>
        <div class="col-3">
            <div class="border p-2 rounded">
                <small class="text-uppercase text-muted">Transaksi Selesai</small>
                <div class="fw-bold fs-6">{{ $completedCount }} Unit</div>
            </div>
        </div>
        <div class="col-3">
            <div class="border p-2 rounded">
                <small class="text-uppercase text-muted">Sedang Parkir</small>
                <div class="fw-bold fs-6">{{ $parkedCount }} Unit</div>
            </div>
        </div>
        <div class="col-3">
            <div class="border p-2 rounded">
                <small class="text-uppercase text-muted">Rata-rata Durasi</small>
                <div class="fw-bold fs-6">{{ $avgMinutes }} Menit</div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex flex-column gap-4 no-print">

    <!-- TOOLBAR & FILTERS -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
        <form method="GET" action="{{ route('admin.reports.index') }}" id="filter-form" class="d-flex flex-column gap-3">
            <!-- Preset & Actions -->
            <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between pb-3 border-bottom gap-2">
                <div class="d-flex flex-wrap align-items-center gap-1">
                    <span class="small fw-bold text-secondary me-1">
                        <i class="bi bi-calendar-range text-primary me-1"></i>Periode:
                    </span>
                    <a href="{{ route('admin.reports.index', ['preset' => 'today', 'search' => $search]) }}" class="btn btn-sm {{ $preset === 'today' ? 'btn-primary' : 'btn-light' }}">Hari Ini</a>
                    <a href="{{ route('admin.reports.index', ['preset' => 'yesterday', 'search' => $search]) }}" class="btn btn-sm {{ $preset === 'yesterday' ? 'btn-primary' : 'btn-light' }}">Kemarin</a>
                    <a href="{{ route('admin.reports.index', ['preset' => 'week', 'search' => $search]) }}" class="btn btn-sm {{ $preset === 'week' ? 'btn-primary' : 'btn-light' }}">7 Hari</a>
                    <a href="{{ route('admin.reports.index', ['preset' => 'month', 'search' => $search]) }}" class="btn btn-sm {{ $preset === 'month' ? 'btn-primary' : 'btn-light' }}">Bulan Ini</a>
                    <a href="{{ route('admin.reports.index', ['preset' => 'all', 'search' => $search]) }}" class="btn btn-sm {{ $preset === 'all' && empty($startDate) ? 'btn-primary' : 'btn-light' }}">Semua</a>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('admin.reports.export', request()->all()) }}" class="btn btn-outline-primary btn-sm rounded-3 d-flex align-items-center gap-1">
                        <i class="bi bi-file-earmark-spreadsheet-fill"></i>
                        <span>Export CSV</span>
                    </a>
                    <button type="button" onclick="executePrint()" class="btn btn-warning btn-sm rounded-3 fw-bold d-flex align-items-center gap-1">
                        <i class="bi bi-printer-fill"></i>
                        <span>Cetak Laporan</span>
                    </button>
                </div>
            </div>

            <!-- Inputs -->
            <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center gap-2">
                <div class="input-group flex-grow-1" style="min-width: 220px;">
                    <span class="input-group-text bg-light text-muted border-end-0">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" name="search" value="{{ $search }}"
                           placeholder="Cari kode tiket, nama, UID, plat nomor..."
                           class="form-control bg-light border-start-0 ps-0">
                    @if (!empty($search))
                        <a href="{{ route('admin.reports.index', array_merge(request()->except('search'))) }}" class="btn btn-light border text-muted">
                            <i class="bi bi-x-circle-fill"></i>
                        </a>
                    @endif
                </div>

                <select name="access_type" class="form-select bg-light" style="width: auto;">
                    <option value="all" {{ $accessType === 'all' ? 'selected' : '' }}>Semua Akses</option>
                    <option value="ticket" {{ $accessType === 'ticket' ? 'selected' : '' }}>Karcis Tiket</option>
                    <option value="card" {{ $accessType === 'card' ? 'selected' : '' }}>Kartu RFID</option>
                </select>

                <div class="d-flex align-items-center gap-1 bg-light border rounded-3 px-2 py-1">
                    <span class="small text-muted me-1">Tgl:</span>
                    <input type="date" name="start_date" value="{{ $startDate }}" class="form-control form-control-sm border-0 bg-transparent p-0 font-monospace">
                    <span class="text-muted">&ndash;</span>
                    <input type="date" name="end_date" value="{{ $endDate }}" class="form-control form-control-sm border-0 bg-transparent p-0 font-monospace">
                </div>

                <div class="d-flex align-items-center gap-1">
                    <button type="submit" class="btn btn-primary btn-sm px-3 rounded-3">Filter</button>
                    @if(!empty($search) || !empty($startDate) || !empty($endDate) || $preset !== 'all' || $accessType !== 'all')
                        <a href="{{ route('admin.reports.index') }}" class="btn btn-link text-decoration-none text-danger btn-sm">Reset</a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- STATISTIK KPI CARDS -->
    <div class="row g-3">
        <!-- Total Pendapatan -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 d-flex flex-column justify-content-between">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-uppercase small fw-bold text-secondary">Total Pendapatan</span>
                    <span class="badge bg-primary p-2 rounded-3 text-white">
                        <i class="bi bi-cash-stack fs-6"></i>
                    </span>
                </div>
                <div class="my-3">
                    <h3 class="fw-bold text-primary mb-0 font-monospace">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</h3>
                    <small class="text-muted">Tarif Flat Rp2.000 / Jam</small>
                </div>
            </div>
        </div>

        <!-- Transaksi Selesai -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 d-flex flex-column justify-content-between">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-uppercase small fw-bold text-secondary">Transaksi Selesai</span>
                    <span class="badge bg-success p-2 rounded-3 text-white">
                        <i class="bi bi-check-circle-fill fs-6"></i>
                    </span>
                </div>
                <div class="my-3">
                    <h3 class="fw-bold text-dark mb-0 font-monospace">{{ $completedCount }} <span class="fs-6 text-muted fw-normal">unit keluar</span></h3>
                    <small class="text-muted">Selesai tap-out</small>
                </div>
            </div>
        </div>

        <!-- Sedang Terparkir -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 d-flex flex-column justify-content-between">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-uppercase small fw-bold text-secondary">Sedang Terparkir</span>
                    <span class="badge bg-warning p-2 rounded-3 text-dark">
                        <i class="bi bi-car-front-fill fs-6"></i>
                    </span>
                </div>
                <div class="my-3">
                    <h3 class="fw-bold text-dark mb-0 font-monospace">{{ $parkedCount }} <span class="fs-6 text-muted fw-normal">unit aktif</span></h3>
                    <small class="text-muted">Belum tap-out</small>
                </div>
            </div>
        </div>

        <!-- Rata-rata Durasi -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 d-flex flex-column justify-content-between">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-uppercase small fw-bold text-secondary">Rata-rata Durasi</span>
                    <span class="badge bg-info p-2 rounded-3 text-white">
                        <i class="bi bi-stopwatch-fill fs-6"></i>
                    </span>
                </div>
                <div class="my-3">
                    <h3 class="fw-bold text-dark mb-0 font-monospace">{{ $avgMinutes }} <span class="fs-6 text-muted fw-normal">menit</span></h3>
                    <small class="text-primary fw-semibold">Rata-rata: Rp {{ number_format($avgFee, 0, ',', '.') }}</small>
                </div>
            </div>
        </div>
    </div>

    <!-- CHARTS ROW -->
    <div class="row g-3">
        <!-- Trend Chart -->
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between mb-3 gap-2">
                    <div>
                        <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-bar-chart-fill text-primary"></i>
                            <span>Tren Pendapatan {{ $chartMode === 'hourly' ? '(Per Jam)' : ($chartMode === 'monthly' ? '(Bulanan)' : '(Harian)') }}</span>
                        </h6>
                        <small class="text-muted">Grafik kronologis kas pendapatan riil</small>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <div class="btn-group btn-group-sm" role="group">
                            <a href="{{ route('admin.reports.index', array_merge(request()->all(), ['chart_mode' => 'daily'])) }}" class="btn {{ $chartMode === 'daily' ? 'btn-primary' : 'btn-outline-secondary' }}">Harian</a>
                            <a href="{{ route('admin.reports.index', array_merge(request()->all(), ['chart_mode' => 'hourly'])) }}" class="btn {{ $chartMode === 'hourly' ? 'btn-primary' : 'btn-outline-secondary' }}">Per Jam</a>
                            <a href="{{ route('admin.reports.index', array_merge(request()->all(), ['chart_mode' => 'monthly'])) }}" class="btn {{ $chartMode === 'monthly' ? 'btn-primary' : 'btn-outline-secondary' }}">Bulanan</a>
                        </div>
                    </div>
                </div>

                <div style="height: 250px;" class="position-relative">
                    <canvas id="revenueTrendChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Duration Doughnut -->
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 d-flex flex-column justify-content-between">
                <div>
                    <h6 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                        <i class="bi bi-pie-chart-fill text-primary"></i>
                        <span>Distribusi Durasi Parkir</span>
                    </h6>
                    <small class="text-muted mb-3 d-block">Komposisi lama waktu parkir</small>

                    <div style="height: 180px;" class="position-relative d-flex align-items-center justify-content-center">
                        <canvas id="durationPieChart"></canvas>
                    </div>
                </div>

                <div class="row g-2 pt-3 border-top mt-2 small">
                    @foreach ($durationBuckets as $lbl => $val)
                        <div class="col-6">
                            <div class="p-2 bg-light rounded-3 d-flex justify-content-between">
                                <span class="text-muted">{{ $lbl }}</span>
                                <strong class="text-dark">{{ $val }} unit</strong>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

</div>

<!-- TRANSACTIONS TABLE -->
<div class="card border-0 shadow-sm rounded-4 p-4 bg-white mt-4 print-card-full">
    <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between pb-3 mb-3 border-bottom gap-2 no-print">
        <div>
            <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-table text-primary"></i>
                <span>Rincian Seluruh Transaksi Parkir</span>
                <span class="badge bg-light text-dark border font-monospace" id="rendered-tx-count">{{ $transactions->count() }}</span>
            </h5>
            <small class="text-muted">Daftar lengkap riwayat tap-in, tap-out, durasi, dan pemotongan saldo</small>
        </div>

        <div class="input-group" style="max-width: 250px;">
            <span class="input-group-text bg-light text-muted border-end-0">
                <i class="bi bi-funnel"></i>
            </span>
            <input type="text" id="quick-table-search" onkeyup="filterTableLive(this.value)" placeholder="Filter instan di layar..." class="form-control form-control-sm bg-light border-start-0 ps-0">
        </div>
    </div>

    <div class="table-responsive">
        <table id="transactions-table" class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr class="text-secondary small text-uppercase">
                    <th class="text-center" style="width: 40px;">No</th>
                    <th>ID</th>
                    <th>Tipe Akses</th>
                    <th>Identitas / Kode</th>
                    <th>Pengendara</th>
                    <th>Waktu Masuk</th>
                    <th>Waktu Keluar</th>
                    <th class="text-center">Durasi</th>
                    <th class="text-end">Biaya (Rp)</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody id="transactions-tbody">
                @if ($transactions->isEmpty())
                    <tr id="no-tx-row">
                        <td colspan="10" class="text-center py-5 text-muted">
                            <i class="bi bi-receipt fs-1 d-block mb-2 text-secondary"></i>
                            Tidak ada transaksi ditemukan.
                        </td>
                    </tr>
                @else
                    @php $rowNo = 1; @endphp
                    @foreach ($transactions as $t)
                        @php
                            $durMin = (int)($t->duration_minutes ?? 0);
                            $billedHours = ceil(max(1, $durMin) / 60);
                            $fee = (int)($t->total_fee ?? 0);
                            $tapInTimeStr = $t->tap_in_time ? \Carbon\Carbon::parse($t->tap_in_time)->format('d/m/Y H:i') : '-';
                            $tapOutTimeStr = $t->tap_out_time ? \Carbon\Carbon::parse($t->tap_out_time)->format('d/m/Y H:i') : null;
                            $isTicket = !empty($t->ticket_code);
                        @endphp
                        <tr class="tx-row" data-search="{{ strtolower(($t->user->name ?? '') . ' ' . ($t->ticket_code ?? '') . ' ' . ($t->card->license_plate ?? '') . ' ' . ($t->card->uid ?? '') . ' #' . $t->id . ' ' . ($t->deviceIn->device_code ?? '') . ' ' . ($t->deviceOut->device_code ?? '')) }}">
                            <td class="text-center text-muted font-monospace small">{{ $rowNo++ }}</td>
                            <td><strong class="font-monospace text-dark">#{{ $t->id }}</strong></td>
                            <td>
                                @if($isTicket)
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                        <i class="bi bi-ticket-perforated-fill me-1"></i> KARCIS
                                    </span>
                                @else
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                        <i class="bi bi-credit-card-2-front-fill me-1"></i> RFID
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($isTicket)
                                    <span class="fw-bold font-monospace text-dark d-block">{{ $t->ticket_code }}</span>
                                    <small class="text-muted">Karcis Pengunjung</small>
                                @else
                                    <span class="fw-bold text-dark d-block">{{ $t->card->license_plate ?? 'NON-PLAT' }}</span>
                                    <small class="text-muted font-monospace">UID: {{ $t->card->uid ?? '-' }}</small>
                                @endif
                            </td>
                            <td>
                                <strong class="text-dark d-block">{{ $isTicket ? 'Pengendara Umum' : ($t->user->name ?? 'Member Parkir') }}</strong>
                                @if (!$isTicket && !empty($t->user->phone))
                                    <small class="text-muted">{{ $t->user->phone }}</small>
                                @endif
                            </td>
                            <td>
                                <div class="font-monospace small text-dark">{{ $tapInTimeStr }}</div>
                                <small class="text-primary fw-semibold">{{ $t->deviceIn->device_code ?? 'Gate-01' }}</small>
                            </td>
                            <td>
                                @if ($tapOutTimeStr)
                                    <div class="font-monospace small text-dark">{{ $tapOutTimeStr }}</div>
                                    <small class="text-success fw-semibold">{{ $t->deviceOut->device_code ?? 'Gate-01' }}</small>
                                @else
                                    <span class="badge bg-warning-subtle text-warning border">Belum Keluar</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($t->status === 'COMPLETED')
                                    <strong class="font-monospace text-dark small d-block">{{ $durMin }} mnt</strong>
                                    <small class="text-muted font-monospace">(&plusmn; {{ $billedHours }} Jam)</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <strong class="font-monospace text-dark fs-6 d-block">Rp {{ number_format($fee, 0, ',', '.') }}</strong>
                                @if ($t->balance_after !== null && !$isTicket)
                                    <small class="text-muted font-monospace">Sisa: Rp {{ number_format((int)$t->balance_after, 0, ',', '.') }}</small>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($t->status === 'COMPLETED')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">SELESAI</span>
                                    <a href="{{ route('receipt.show', $t->id) }}" target="_blank" class="no-print d-block small text-decoration-none mt-1">
                                        <i class="bi bi-printer"></i> 58mm
                                    </a>
                                @else
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">PARKIR</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    <tr class="table-light fw-bold">
                        <td colspan="8" class="text-end text-uppercase">TOTAL PENDAPATAN:</td>
                        <td class="text-end font-monospace text-primary fs-6">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</td>
                        <td class="text-center">{{ $completedCount }} Selesai</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <!-- Print Footer -->
    <div class="d-none d-print-block mt-4 pt-3 border-top small text-end">
        <p class="mb-1">Jakarta, {{ date('d F Y') }}</p>
        <p class="fw-bold mb-5">Penanggung Jawab / Administrator,</p>
        <p class="fw-bold mb-0 text-decoration-underline">( {{ auth()->user()->name ?? 'Administrator' }} )</p>
        <small class="text-muted">{{ auth()->user()->email ?? 'admin@eparkir.local' }}</small>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const trendLabels = {!! json_encode(array_keys($trendData)) !!};
    const trendValues = {!! json_encode(array_values($trendData)) !!};
    const durationBuckets = {!! json_encode($durationBuckets) !!};

    const ctxRevenue = document.getElementById('revenueTrendChart').getContext('2d');
    new Chart(ctxRevenue, {
        type: 'bar',
        data: {
            labels: trendLabels.length ? trendLabels : ['Tidak Ada Data'],
            datasets: [{
                label: 'Pendapatan (Rp)',
                data: trendValues.length ? trendValues : [0],
                backgroundColor: '#0d6efd',
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(val) {
                            if (val >= 1000) return 'Rp ' + (val / 1000).toFixed(0) + 'k';
                            return 'Rp ' + val;
                        }
                    }
                }
            }
        }
    });

    const ctxDuration = document.getElementById('durationPieChart').getContext('2d');
    new Chart(ctxDuration, {
        type: 'doughnut',
        data: {
            labels: Object.keys(durationBuckets),
            datasets: [{
                data: Object.values(durationBuckets),
                backgroundColor: ['#0d6efd', '#ffc107', '#212529', '#6c757d'],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            },
            cutout: '70%'
        }
    });

    function filterTableLive(keyword) {
        const query = keyword.toLowerCase().trim();
        const rows = document.querySelectorAll('.tx-row');
        let visibleCount = 0;

        rows.forEach(row => {
            const searchData = row.getAttribute('data-search') || '';
            if (searchData.includes(query)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        const countBadge = document.getElementById('rendered-tx-count');
        if (countBadge) countBadge.innerText = visibleCount;
    }

    function executePrint() {
        window.print();
    }
</script>
@endpush
