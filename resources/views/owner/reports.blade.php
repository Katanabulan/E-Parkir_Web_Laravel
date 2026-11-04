@extends('layouts.owner')

@section('title', 'Dashboard Eksekutif & Laporan Finansial')
@section('header_title', 'Dashboard & Laporan Finansial')
@section('header_subtitle', 'Ringkasan performa finansial, tren pendapatan, dan utilisasi parkir')

@section('content')
<div class="space-y-5">

    <!-- =========================================================================
         1. 3 SUMMARY METRIC CARDS (Ringkasan Keuangan Utama)
         ========================================================================= -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- 1. Total Pendapatan -->
        <div class="p-5 rounded-xl bg-white clean-border shadow-sm flex items-center justify-between">
            <div>
                <div class="text-[11px] font-bold text-black/50 uppercase tracking-wider">Total Pendapatan</div>
                <div class="text-2xl font-black text-black font-mono mt-1">
                    Rp {{ number_format($totalRevenue, 0, ',', '.') }}
                </div>
                <div class="text-[11px] font-semibold text-[#00719C] mt-1 flex items-center gap-1">
                    <i class="fa-solid fa-circle-check text-[10px]"></i>
                    <span>Arus kas riil terverifikasi</span>
                </div>
            </div>
            <div class="icon-box" style="width: 2.75rem; height: 2.75rem; font-size: 1.25rem; border: 1.5px solid #005677;">
                <i class="fa-solid fa-rupiah-sign"></i>
            </div>
        </div>

        <!-- 2. Transaksi Selesai -->
        <div class="p-5 rounded-xl bg-white clean-border shadow-sm flex items-center justify-between">
            <div>
                <div class="text-[11px] font-bold text-black/50 uppercase tracking-wider">Transaksi Selesai</div>
                <div class="text-2xl font-black text-black font-mono mt-1">
                    {{ number_format($completedCount, 0, ',', '.') }} <span class="text-xs font-bold text-black/40">Unit</span>
                </div>
                <div class="text-[11px] font-semibold text-emerald-700 mt-1 flex items-center gap-1">
                    <i class="fa-solid fa-check text-[10px]"></i>
                    <span>Kendaraan selesai &amp; keluar</span>
                </div>
            </div>
            <div class="icon-box" style="width: 2.75rem; height: 2.75rem; font-size: 1.25rem; border: 1.5px solid #005677;">
                <i class="fa-solid fa-car-side"></i>
            </div>
        </div>

        <!-- 3. Rata-rata Durasi & Biaya -->
        <div class="p-5 rounded-xl bg-white clean-border shadow-sm flex items-center justify-between">
            <div>
                <div class="text-[11px] font-bold text-black/50 uppercase tracking-wider">Rata-Rata Transaksi</div>
                <div class="text-2xl font-black text-black font-mono mt-1">
                    Rp {{ number_format($avgFee, 0, ',', '.') }}
                </div>
                <div class="text-[11px] font-semibold text-black/60 mt-1">
                    Durasi Rata-rata: <span class="font-bold text-black">{{ $avgMinutes }} Menit</span>
                </div>
            </div>
            <div class="icon-box" style="width: 2.75rem; height: 2.75rem; font-size: 1.25rem; border: 1.5px solid #005677;">
                <i class="fa-solid fa-calculator"></i>
            </div>
        </div>
    </div>

    <!-- =========================================================================
         2. TREN GRAFIK PENDAPATAN EKSEKUTIF (Chart.js)
         ========================================================================= -->
    <div class="p-5 rounded-xl bg-white clean-border shadow-sm">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between pb-3 clean-border-b mb-4 gap-3">
            <div>
                <h3 class="text-sm font-bold text-black uppercase tracking-wide flex items-center gap-2">
                    <i class="fa-solid fa-chart-column text-[#00719C]"></i>
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
                <p class="text-xs text-black/50">Grafik ritme kas pendapatan riil yang masuk ke dalam sistem.</p>
            </div>

            <!-- Filter Mode Grafik: Harian / Per Jam / Bulanan -->
            <div class="flex items-center gap-2">
                <div class="inline-flex rounded-lg p-0.5 bg-slate-100 clean-border">
                    <a href="{{ route('owner.reports.index', ['chart_mode' => 'daily']) }}" 
                       class="px-2.5 py-1 text-xs font-bold rounded-md transition {{ $chartMode === 'daily' ? 'bg-[#00719C] text-white shadow-sm' : 'text-black/60 hover:text-black' }}">
                        Harian
                    </a>
                    <a href="{{ route('owner.reports.index', ['chart_mode' => 'hourly']) }}" 
                       class="px-2.5 py-1 text-xs font-bold rounded-md transition {{ $chartMode === 'hourly' ? 'bg-[#00719C] text-white shadow-sm' : 'text-black/60 hover:text-black' }}">
                        Per Jam
                    </a>
                    <a href="{{ route('owner.reports.index', ['chart_mode' => 'monthly']) }}" 
                       class="px-2.5 py-1 text-xs font-bold rounded-md transition {{ $chartMode === 'monthly' ? 'bg-[#00719C] text-white shadow-sm' : 'text-black/60 hover:text-black' }}">
                        Bulanan
                    </a>
                </div>

                <span class="px-2.5 py-1 text-xs font-mono font-bold bg-[#FFD51E] text-black border border-white rounded shadow-sm">
                    Puncak: Rp {{ number_format($maxTrendValue, 0, ',', '.') }}
                </span>
            </div>
        </div>

        <div class="h-72 relative">
            <canvas id="revenueTrendChart"></canvas>
        </div>
    </div>

    <!-- =========================================================================
         3. WIDGET ANALITIK BISNIS & DISTRIBUSI OPERASIONAL
         ========================================================================= -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Distribusi Durasi Parkir Pengunjung -->
        <div class="p-5 rounded-xl bg-white clean-border shadow-sm">
            <h3 class="text-xs font-bold uppercase tracking-wider text-black flex items-center gap-2 mb-3 pb-2 clean-border-b">
                <i class="fa-solid fa-hourglass-half text-[#00719C]"></i>
                <span>Distribusi Durasi Parkir</span>
            </h3>
            <div class="space-y-3">
                @php
                    $maxBucket = max(array_values($durationBuckets)) ?: 1;
                @endphp
                @foreach ($durationBuckets as $label => $val)
                    @php
                        $percentage = round(($val / max(1, $completedCount)) * 100);
                        $barWidth = round(($val / $maxBucket) * 100);
                    @endphp
                    <div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="font-bold text-slate-700">{{ $label }}</span>
                            <span class="font-mono text-slate-500 font-bold">{{ $val }} unit ({{ $percentage }}%)</span>
                        </div>
                        <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-[#00719C] rounded-full transition-all duration-500" style="width: {{ $barWidth }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Banner Akses Cepat ke Halaman Rincian Transaksi -->
        <div class="p-5 rounded-xl bg-gradient-to-br from-[#00719C] to-[#005170] text-white shadow-sm flex flex-col justify-between">
            <div>
                <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center text-lg mb-3 border border-white/20">
                    <i class="fa-solid fa-table-list"></i>
                </div>
                <h3 class="text-base font-black tracking-tight">Audit &amp; Rincian Lengkap Transaksi</h3>
                <p class="text-xs text-white/80 mt-1 leading-relaxed">
                    Filter dan telusuri riwayat transaksi parkir lengkap per karcis, plat nomor kendaraan, dan lantai parkir (LT 1 &amp; LT 2) secara rinci di halaman khusus transaksi.
                </p>
            </div>
            <div class="pt-4">
                <a href="{{ route('owner.transactions.index') }}" 
                   class="inline-flex items-center gap-2 px-4 py-2 bg-[#FFD51E] hover:bg-[#e6be12] text-black text-xs font-black rounded-lg transition shadow-sm">
                    <span>Buka Rincian Transaksi</span>
                    <i class="fa-solid fa-arrow-right text-[11px]"></i>
                </a>
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
