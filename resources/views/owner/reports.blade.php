@extends('layouts.owner')

@section('title', 'Dashboard Eksekutif & Laporan Finansial')
@section('header_title', 'Dashboard & Laporan Finansial')
@section('header_subtitle', 'Ringkasan performa finansial, tren pendapatan, dan utilisasi parkir')

@section('content')
<div class="d-flex flex-column gap-4">

    <!-- =========================================================================
         1. 3 SUMMARY METRIC CARDS (Ringkasan Keuangan Utama)
         ========================================================================= -->
    <div class="row g-3">
        <!-- 1. Total Pendapatan -->
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-uppercase fw-bold text-secondary small" style="font-size: 0.75rem; letter-spacing: 0.05em;">Total Pendapatan</div>
                        <div class="fs-4 fw-bold text-dark font-monospace mt-1">
                            Rp {{ number_format($totalRevenue, 0, ',', '.') }}
                        </div>
                        <div class="small fw-semibold text-primary mt-1 d-flex align-items-center gap-1">
                            <i class="bi bi-check-circle-fill text-primary" style="font-size: 0.8rem;"></i>
                            <span>Arus kas riil terverifikasi</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-center rounded-3 text-white" style="width: 2.75rem; height: 2.75rem; background-color: #00719C; font-size: 1.25rem;">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Transaksi Selesai -->
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-uppercase fw-bold text-secondary small" style="font-size: 0.75rem; letter-spacing: 0.05em;">Transaksi Selesai</div>
                        <div class="fs-4 fw-bold text-dark font-monospace mt-1">
                            {{ number_format($completedCount, 0, ',', '.') }} <span class="small fw-semibold text-secondary">Unit</span>
                        </div>
                        <div class="small fw-semibold text-success mt-1 d-flex align-items-center gap-1">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 0.8rem;"></i>
                            <span>Kendaraan selesai &amp; keluar</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-center rounded-3 text-white" style="width: 2.75rem; height: 2.75rem; background-color: #00719C; font-size: 1.25rem;">
                        <i class="bi bi-car-front-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Rata-rata Durasi & Biaya -->
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-uppercase fw-bold text-secondary small" style="font-size: 0.75rem; letter-spacing: 0.05em;">Rata-Rata Transaksi</div>
                        <div class="fs-4 fw-bold text-dark font-monospace mt-1">
                            Rp {{ number_format($avgFee, 0, ',', '.') }}
                        </div>
                        <div class="small fw-semibold text-secondary mt-1">
                            Durasi Rata-rata: <span class="fw-bold text-dark">{{ $avgMinutes }} Menit</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-center rounded-3 text-white" style="width: 2.75rem; height: 2.75rem; background-color: #00719C; font-size: 1.25rem;">
                        <i class="bi bi-calculator-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- =========================================================================
         2. TREN GRAFIK PENDAPATAN EKSEKUTIF (Chart.js)
         ========================================================================= -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between pb-3 border-bottom mb-4 gap-3">
                <div>
                    <h3 class="fs-6 fw-bold text-dark text-uppercase tracking-wide mb-1 d-flex align-items-center gap-2">
                        <i class="bi bi-bar-chart-fill text-primary"></i>
                        <span>
                            Tren Pendapatan 
                            @if($chartMode === 'hourly')
                                (Per Jam)
                            @elseif($chartMode === 'monthly')
                                (Bulanan)
                            @else
                                (Harian)
                            @endif
                        </span>
                    </h3>
                    <p class="small text-secondary mb-0">Grafik ritme kas pendapatan riil yang masuk ke dalam sistem.</p>
                </div>

                <!-- Filter Mode Grafik: Harian / Per Jam / Bulanan -->
                <div class="d-flex align-items-center gap-2">
                    <div class="btn-group btn-group-sm bg-light p-1 rounded-3 border">
                        <a href="{{ route('owner.reports.index', ['chart_mode' => 'daily']) }}" 
                           class="btn {{ $chartMode === 'daily' ? 'btn-primary' : 'btn-light border-0 text-secondary' }} rounded-2 fw-bold">
                            Harian
                        </a>
                        <a href="{{ route('owner.reports.index', ['chart_mode' => 'hourly']) }}" 
                           class="btn {{ $chartMode === 'hourly' ? 'btn-primary' : 'btn-light border-0 text-secondary' }} rounded-2 fw-bold">
                            Per Jam
                        </a>
                        <a href="{{ route('owner.reports.index', ['chart_mode' => 'monthly']) }}" 
                           class="btn {{ $chartMode === 'monthly' ? 'btn-primary' : 'btn-light border-0 text-secondary' }} rounded-2 fw-bold">
                            Bulanan
                        </a>
                    </div>

                    <span class="badge bg-warning text-dark border px-2.5 py-1.5 font-monospace fw-bold shadow-sm">
                        Puncak: Rp {{ number_format($maxTrendValue, 0, ',', '.') }}
                    </span>
                </div>
            </div>

            <div style="height: 300px; position: relative;">
                <canvas id="revenueTrendChart"></canvas>
            </div>
        </div>
    </div>

    <!-- =========================================================================
         3. WIDGET ANALITIK BISNIS & DISTRIBUSI OPERASIONAL
         ========================================================================= -->
    <div class="row g-4">
        <!-- Distribusi Durasi Parkir Pengunjung -->
        <div class="col-12 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <h3 class="fs-6 fw-bold text-uppercase tracking-wider text-dark d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                        <i class="bi bi-hourglass-split text-primary"></i>
                        <span>Distribusi Durasi Parkir</span>
                    </h3>
                    <div class="d-flex flex-column gap-3">
                        @php
                            $maxBucket = max(array_values($durationBuckets)) ?: 1;
                        @endphp
                        @foreach ($durationBuckets as $label => $val)
                            @php
                                $percentage = round(($val / max(1, $completedCount)) * 100);
                                $barWidth = round(($val / $maxBucket) * 100);
                            @endphp
                            <div>
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="fw-bold text-dark">{{ $label }}</span>
                                    <span class="font-monospace text-secondary fw-bold">{{ $val }} unit ({{ $percentage }}%)</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $barWidth }}%; transition: width 0.5s ease;" aria-valuenow="{{ $barWidth }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Banner Akses Cepat ke Halaman Rincian Transaksi -->
        <div class="col-12 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 text-white" style="background: linear-gradient(135deg, #00719C 0%, #004b68 100%);">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-inline-flex align-items-center justify-content-center rounded-3 bg-white bg-opacity-25 border border-white border-opacity-25 fs-5 mb-3" style="width: 2.75rem; height: 2.75rem;">
                            <i class="bi bi-table"></i>
                        </div>
                        <h3 class="fs-5 fw-bold mb-2">Audit &amp; Rincian Lengkap Transaksi</h3>
                        <p class="small text-white-50 leading-relaxed mb-0">
                            Filter dan telusuri riwayat transaksi parkir lengkap per karcis, plat nomor kendaraan, dan lantai parkir (LT 1 &amp; LT 2) secara rinci di halaman khusus transaksi.
                        </p>
                    </div>
                    <div class="pt-4">
                        <a href="{{ route('owner.transactions.index') }}" 
                           class="btn btn-warning text-dark fw-bold px-4 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                            <span>Buka Rincian Transaksi</span>
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    const trendLabels = {!! json_encode(array_keys($trendData)) !!};
    const trendValues = {!! json_encode(array_values($trendData)) !!};

    const ctxRevenue = document.getElementById('revenueTrendChart').getContext('2d');
    new Chart(ctxRevenue, {
        type: 'bar',
        data: {
            labels: trendLabels.length ? trendLabels : ['Tidak Ada Data'],
            datasets: [{
                label: 'Pendapatan (Rp)',
                data: trendValues.length ? trendValues : [0],
                backgroundColor: '#00719C',
                hoverBackgroundColor: '#005677',
                borderColor: '#005677',
                borderWidth: 1,
                borderRadius: 6,
                maxBarThickness: 42
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const val = context.raw || 0;
                            return ' Pendapatan: Rp ' + val.toLocaleString('id-ID');
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0, 0, 0, 0.06)' },
                    ticks: {
                        color: '#64748b',
                        font: { size: 10, weight: '600' },
                        callback: function(val) {
                            if (val >= 1000000) return 'Rp ' + (val / 1000000).toFixed(1) + 'M';
                            if (val >= 1000) return 'Rp ' + (val / 1000).toFixed(0) + 'k';
                            return 'Rp ' + val;
                        }
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: {
                        color: '#64748b',
                        font: { size: 10, weight: 'bold' }
                    }
                }
            }
        }
    });
</script>
@endpush
