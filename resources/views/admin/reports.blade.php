@extends('layouts.admin')

@section('title', 'Laporan Pendapatan & Transaksi')
@section('header_title', 'Laporan Pendapatan & Lalu Lintas')
@section('header_subtitle', 'Rekapitulasi total pendapatan, filter periode & waktu, serta audit transaksi')

@section('content')
<!-- =========================================================================
     A. OFFICIAL PRINT SECTION (Hanya Muncul Saat window.print() / Cetak Kertas)
     ========================================================================= -->
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

<div class="print-only mb-4 text-black">
    <!-- Kop Surat Resmi Laporan Parkir -->
    <div class="border-b-2 border-black pb-2.5 mb-3 flex items-center justify-between">
        <div>
            <div class="text-[10px] uppercase font-extrabold tracking-widest text-[#00719C]">E-Parkir IoT Platform &bull; Sigma Boys</div>
            <h1 class="text-xl font-black tracking-tight text-black mt-0.5">LAPORAN REKAPITULASI PENDAPATAN & TRANSAKSI PARKIR</h1>
            <p class="text-[11px] text-black/70 font-medium">Sistem Palang Pintu Otomatis & Pemotongan Saldo RFID Real-Time</p>
        </div>
        <div class="text-right text-[10px] font-mono">
            <div><strong>Tanggal Cetak:</strong> {{ date('d/m/Y H:i') }} WIB</div>
            <div><strong>Dicetak Oleh:</strong> {{ auth()->user()->name ?? 'Administrator' }} ({{ strtoupper(auth()->user()->role ?? 'Admin') }})</div>
            <div><strong>Status Sistem:</strong> Gate-01 Online</div>
        </div>
    </div>

    <!-- Parameter Filter Info Badge Print -->
    <div class="bg-slate-50 border border-black/20 p-2.5 rounded-lg mb-3 text-xs grid grid-cols-3 gap-2">
        <div>
            <span class="text-black/60 block font-semibold text-[9px] uppercase">Periode Laporan:</span>
            <span class="font-bold text-black font-mono text-[11px]">
                {{ $startDate ?: 'Semua' }} {{ $startTime ? "($startTime)" : '' }} 
                s/d 
                {{ $endDate ?: 'Semua' }} {{ $endTime ? "($endTime)" : '' }}
            </span>
        </div>
        <div>
            <span class="text-black/60 block font-semibold text-[9px] uppercase">Filter Status:</span>
            <span class="font-bold text-black font-mono text-[11px]">
                {{ $statusFilter === 'COMPLETED' ? 'Hanya Selesai (Tap-Out)' : ($statusFilter === 'PARKED' ? 'Hanya Sedang Parkir' : 'Semua Transaksi') }}
            </span>
        </div>
        <div>
            <span class="text-black/60 block font-semibold text-[9px] uppercase">Kata Kunci Pencarian:</span>
            <span class="font-bold text-black font-mono text-[11px]">{{ $search ?: 'Tidak ada (Semua)' }}</span>
        </div>
    </div>

    <!-- Summary Box Print -->
    <div class="grid grid-cols-4 gap-2 mb-3">
        <div class="border border-black/30 p-2 rounded-lg text-center">
            <div class="text-[9px] uppercase font-bold text-black/60">Total Pendapatan</div>
            <div class="text-base font-black text-black mt-0.5">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</div>
        </div>
        <div class="border border-black/30 p-2 rounded-lg text-center">
            <div class="text-[9px] uppercase font-bold text-black/60">Transaksi Selesai</div>
            <div class="text-base font-black text-black mt-0.5">{{ $completedCount }} Unit</div>
        </div>
        <div class="border border-black/30 p-2 rounded-lg text-center">
            <div class="text-[9px] uppercase font-bold text-black/60">Sedang Parkir</div>
            <div class="text-base font-black text-black mt-0.5">{{ $parkedCount }} Unit</div>
        </div>
        <div class="border border-black/30 p-2 rounded-lg text-center">
            <div class="text-[9px] uppercase font-bold text-black/60">Rata-rata Durasi</div>
            <div class="text-base font-black text-black mt-0.5">{{ $avgMinutes }} Menit</div>
        </div>
    </div>
</div>

<!-- =========================================================================
     B. SCREEN TOOLBAR: SEARCHBAR, FILTER PERIODE & METODE (no-print)
     ========================================================================= -->
<div class="bg-white clean-border rounded-xl p-4 shadow-sm no-print mb-6">
    <form method="GET" action="{{ route('admin.reports.index') }}" id="filter-form" class="space-y-3">
        <!-- BARIS ATAS: Preset Periode Cepat & Tombol Aksi (Export CSV & Cetak) -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-black/10">
            <div class="flex flex-wrap items-center gap-1.5">
                <span class="text-[11px] font-bold text-black/50 uppercase tracking-wider mr-1">
                    <i class="fa-solid fa-calendar-days text-[#00719C] me-1"></i>Periode:
                </span>
                
                <a href="{{ route('admin.reports.index', ['preset' => 'today', 'search' => $search]) }}" 
                   class="px-3 py-1 rounded-md text-xs font-semibold transition {{ $preset === 'today' ? 'bg-[#00719C] text-white shadow-sm' : 'bg-slate-100 hover:bg-slate-200 text-black/80' }}">
                    Hari Ini
                </a>

                <a href="{{ route('admin.reports.index', ['preset' => 'yesterday', 'search' => $search]) }}" 
                   class="px-3 py-1 rounded-md text-xs font-semibold transition {{ $preset === 'yesterday' ? 'bg-[#00719C] text-white shadow-sm' : 'bg-slate-100 hover:bg-slate-200 text-black/80' }}">
                    Kemarin
                </a>

                <a href="{{ route('admin.reports.index', ['preset' => 'week', 'search' => $search]) }}" 
                   class="px-3 py-1 rounded-md text-xs font-semibold transition {{ $preset === 'week' ? 'bg-[#00719C] text-white shadow-sm' : 'bg-slate-100 hover:bg-slate-200 text-black/80' }}">
                    7 Hari Terakhir
                </a>

                <a href="{{ route('admin.reports.index', ['preset' => 'month', 'search' => $search]) }}" 
                   class="px-3 py-1 rounded-md text-xs font-semibold transition {{ $preset === 'month' ? 'bg-[#00719C] text-white shadow-sm' : 'bg-slate-100 hover:bg-slate-200 text-black/80' }}">
                    Bulan Ini
                </a>

                <a href="{{ route('admin.reports.index', ['preset' => 'all', 'search' => $search]) }}" 
                   class="px-3 py-1 rounded-md text-xs font-semibold transition {{ $preset === 'all' && empty($startDate) ? 'bg-[#00719C] text-white shadow-sm' : 'bg-slate-100 hover:bg-slate-200 text-black/80' }}">
                    Semua
                </a>
            </div>

            <!-- Tombol Export & Cetak -->
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.reports.export', request()->all()) }}" 
                   title="Download data laporan dalam format CSV (Excel)"
                   class="px-3 py-1.5 bg-white hover:bg-slate-50 text-[#00719C] border border-[#00719C] text-xs font-bold rounded-lg transition flex items-center gap-1.5 shadow-sm">
                    <i class="fa-solid fa-file-csv text-sm"></i>
                    <span>Export CSV</span>
                </a>

                <button type="button" 
                        onclick="executePrint()" 
                        title="Cetak laporan resmi ke kertas A4 / PDF"
                        class="px-3.5 py-1.5 bg-[#FFD51E] hover:bg-[#e6be12] text-black text-xs font-extrabold rounded-lg transition flex items-center gap-1.5 shadow-sm cursor-pointer border-none">
                    <i class="fa-solid fa-print text-sm"></i>
                    <span>Cetak Laporan</span>
                </button>
            </div>
        </div>

        <!-- BARIS BAWAH: Input Pencarian, Filter Metode, Rentang Tanggal Simpel, & Terapkan -->
        <div class="flex flex-col lg:flex-row items-stretch lg:items-center gap-2.5">
            <!-- Pencarian Cepat -->
            <div class="search-wrapper-box flex-1 min-w-[200px]">
                <span class="search-icon">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" 
                       id="main-search-input"
                       name="search" 
                       value="{{ $search }}" 
                       placeholder="Cari kode tiket, nama pengendara, UID, plat nomor, atau ID...">
                @if (!empty($search))
                    <a href="{{ route('admin.reports.index', array_merge(request()->except('search'))) }}" 
                       title="Hapus pencarian"
                       class="search-clear-btn">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </a>
                @endif
            </div>

            <!-- Filter Tipe Akses (Kartu vs Karcis) -->
            <div class="flex items-center gap-1.5 bg-slate-50 p-1 rounded-lg clean-border shrink-0">
                <span class="text-[10px] uppercase font-bold text-black/50 px-1.5">Metode:</span>
                <select name="access_type" 
                        class="text-xs font-bold text-black px-2 py-1 bg-white rounded clean-border focus:outline-none focus:border-[#00719C]">
                    <option value="all" {{ $accessType === 'all' ? 'selected' : '' }}>Semua Akses</option>
                    <option value="ticket" {{ $accessType === 'ticket' ? 'selected' : '' }}>Karcis Tiket</option>
                    <option value="card" {{ $accessType === 'card' ? 'selected' : '' }}>Kartu RFID Member</option>
                </select>
            </div>

            <!-- Rentang Tanggal Simpel -->
            <div class="flex items-center gap-1.5 bg-slate-50 p-1 rounded-lg clean-border shrink-0">
                <span class="text-[10px] uppercase font-bold text-black/50 px-1.5">Tanggal:</span>
                <input type="date" 
                       name="start_date" 
                       value="{{ $startDate }}"
                       title="Tanggal Mulai"
                       class="text-xs font-mono font-semibold text-black px-2 py-1 bg-white rounded clean-border focus:outline-none focus:border-[#00719C]">
                <span class="text-xs text-black/40 font-bold">&ndash;</span>
                <input type="date" 
                       name="end_date" 
                       value="{{ $endDate }}"
                       title="Tanggal Selesai"
                       class="text-xs font-mono font-semibold text-black px-2 py-1 bg-white rounded clean-border focus:outline-none focus:border-[#00719C]">
            </div>

            <!-- Tombol Filter & Reset -->
            <div class="flex items-center gap-2 shrink-0">
                <button type="submit" 
                        class="px-4 py-2 bg-[#00719C] hover:bg-[#005c80] text-white text-xs font-bold rounded-lg transition flex items-center gap-1.5 shadow-sm cursor-pointer border-none">
                    <i class="fa-solid fa-filter text-[10px]"></i>
                    <span>Filter</span>
                </button>

                @if(!empty($search) || !empty($startDate) || !empty($endDate) || $preset !== 'all' || $accessType !== 'all')
                    <a href="{{ route('admin.reports.index') }}" 
                       title="Kembalikan filter ke semua data"
                       class="px-2.5 py-2 bg-slate-100 hover:bg-slate-200 text-rose-600 text-xs font-semibold rounded-lg transition flex items-center gap-1">
                        <i class="fa-solid fa-arrows-rotate text-[10px]"></i>
                        <span>Reset</span>
                    </a>
                @endif
            </div>
        </div>
    </form>
</div>

<!-- =========================================================================
     C. STATISTIK AGREGAT RINGKAS (KPI CARDS)
     ========================================================================= -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 no-print">
    <!-- Total Pendapatan -->
    <div class="bg-white clean-border rounded-xl p-5 flex flex-col justify-between shadow-sm">
        <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold uppercase tracking-wider text-black/60">Total Pendapatan Terkumpul</span>
            <div class="w-8 h-8 rounded bg-[#00719C] text-white flex items-center justify-center text-xs">
                <i class="fa-solid fa-coins"></i>
            </div>
        </div>
        <div class="mt-3">
            <div class="text-2xl font-black text-[#00719C]">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</div>
            <div class="text-[11px] text-black/50 font-medium mt-1">Tarif Flat Rp2.000 / Jam pembulatan ke atas</div>
        </div>
    </div>

    <!-- Transaksi Selesai -->
    <div class="bg-white clean-border rounded-xl p-5 flex flex-col justify-between shadow-sm">
        <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold uppercase tracking-wider text-black/60">Transaksi Selesai</span>
            <div class="w-8 h-8 rounded bg-emerald-600 text-white flex items-center justify-center text-xs">
                <i class="fa-solid fa-circle-check"></i>
            </div>
        </div>
        <div class="mt-3">
            <div class="text-2xl font-black text-black">{{ $completedCount }} <span class="text-xs font-semibold text-black/50">unit keluar</span></div>
            <div class="text-[11px] text-black/50 font-medium mt-1">Selesai tap-out dan saldo terpotong</div>
        </div>
    </div>

    <!-- Kendaraan Sedang Parkir -->
    <div class="bg-white clean-border rounded-xl p-5 flex flex-col justify-between shadow-sm">
        <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold uppercase tracking-wider text-black/60">Sedang Terparkir</span>
            <div class="w-8 h-8 rounded bg-[#FFD51E] text-black flex items-center justify-center text-xs font-black">
                <i class="fa-solid fa-square-parking"></i>
            </div>
        </div>
        <div class="mt-3">
            <div class="text-2xl font-black text-black">{{ $parkedCount }} <span class="text-xs font-semibold text-black/50">unit aktif</span></div>
            <div class="text-[11px] text-black/50 font-medium mt-1">Belum melakukan tap-out</div>
        </div>
    </div>

    <!-- Rata-rata Durasi -->
    <div class="bg-white clean-border rounded-xl p-5 flex flex-col justify-between shadow-sm">
        <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold uppercase tracking-wider text-black/60">Rata-rata Durasi & Tarif</span>
            <div class="w-8 h-8 rounded bg-[#00719C] text-white flex items-center justify-center text-xs">
                <i class="fa-solid fa-stopwatch"></i>
            </div>
        </div>
        <div class="mt-3">
            <div class="text-2xl font-black text-black">{{ $avgMinutes }} <span class="text-xs font-semibold text-black/50">menit</span></div>
            <div class="text-[11px] text-[#00719C] font-bold mt-1">Rata-rata: Rp {{ number_format($avgFee, 0, ',', '.') }} / kendaraan</div>
        </div>
    </div>
</div>

<!-- =========================================================================
     D. CHARTS ROW: TREN PENDAPATAN & DISTRIBUSI DURASI (no-print)
     ========================================================================= -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 no-print">
    <!-- Trend Chart Container -->
    <div class="lg:col-span-8 bg-white clean-border rounded-xl p-5 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
            <div>
                <h3 class="text-sm font-extrabold text-black flex items-center gap-2">
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
                <p class="text-xs text-black/60">
                    Grafik kronologis ritme kas pendapatan riil dari sistem parkir.
                </p>
            </div>
            <div class="flex items-center gap-2 text-[11px] font-semibold text-black/70">
                <div class="inline-flex rounded-lg p-0.5 bg-slate-100 clean-border">
                    <a href="{{ route('admin.reports.index', array_merge(request()->all(), ['chart_mode' => 'daily'])) }}" 
                       class="px-2.5 py-1 text-xs font-bold rounded-md transition {{ $chartMode === 'daily' ? 'bg-[#00719C] text-white shadow-sm' : 'text-black/60 hover:text-black' }}">
                        Harian
                    </a>
                    <a href="{{ route('admin.reports.index', array_merge(request()->all(), ['chart_mode' => 'hourly'])) }}" 
                       class="px-2.5 py-1 text-xs font-bold rounded-md transition {{ $chartMode === 'hourly' ? 'bg-[#00719C] text-white shadow-sm' : 'text-black/60 hover:text-black' }}">
                        Per Jam
                    </a>
                    <a href="{{ route('admin.reports.index', array_merge(request()->all(), ['chart_mode' => 'monthly'])) }}" 
                       class="px-2.5 py-1 text-xs font-bold rounded-md transition {{ $chartMode === 'monthly' ? 'bg-[#00719C] text-white shadow-sm' : 'text-black/60 hover:text-black' }}">
                        Bulanan
                    </a>
                </div>
                <span class="px-2.5 py-1 rounded-md bg-[#00719C]/10 text-[#00719C] font-bold">
                    Puncak: Rp {{ number_format($maxTrendValue, 0, ',', '.') }}
                </span>
            </div>
        </div>

        <div class="h-64 relative">
            <canvas id="revenueTrendChart"></canvas>
        </div>
    </div>

    <!-- Duration Distribution Doughnut -->
    <div class="lg:col-span-4 bg-white clean-border rounded-xl p-5 shadow-sm flex flex-col justify-between">
        <div>
            <h3 class="text-sm font-extrabold text-black flex items-center gap-2 mb-1">
                <i class="fa-solid fa-chart-pie text-[#00719C]"></i>
                <span>Distribusi Durasi Parkir</span>
            </h3>
            <p class="text-xs text-black/60 mb-4">Komposisi lama waktu parkir pengguna</p>
            <div class="h-52 relative flex items-center justify-center">
                <canvas id="durationPieChart"></canvas>
            </div>
        </div>

        <!-- Duration Summary Badges -->
        <div class="grid grid-cols-2 gap-2 pt-3 border-t border-black/10 text-xs">
            @foreach ($durationBuckets as $lbl => $val)
                <div class="flex items-center justify-between px-2 py-1 bg-slate-50 rounded">
                    <span class="text-black/60 text-[11px] font-medium">{{ $lbl }}</span>
                    <strong class="text-black text-[11px]">{{ $val }} unit</strong>
                </div>
            @endforeach
        </div>
    </div>
</div>

<!-- =========================================================================
     E. RINCIAN TABEL TRANSAKSI (TAMPIL DI LAYAR & TERCETAK RAPI DI KERTAS)
     ========================================================================= -->
<div class="bg-white clean-border rounded-xl p-5 shadow-sm print:clean-border-none print:shadow-none print:p-0">
    <!-- Header Tabel di Layar (no-print) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 no-print">
        <div>
            <h3 class="text-sm font-extrabold text-black flex items-center gap-2">
                <span>Rincian Seluruh Transaksi Parkir</span>
                <span class="text-xs font-semibold px-2 py-0.5 rounded bg-slate-100 text-black/70">
                    <span id="rendered-tx-count">{{ $transactions->count() }}</span> Transaksi Ditemukan
                </span>
            </h3>
            <p class="text-xs text-black/60">Daftar lengkap riwayat tap-in, tap-out, durasi, pemotongan saldo, dan kas masuk</p>
        </div>

        <!-- Client-Side Instant Table Filter -->
        <div class="search-wrapper-box w-full sm:w-64">
            <span class="search-icon">
                <i class="fa-solid fa-filter text-[10px]"></i>
            </span>
            <input type="text" 
                   id="quick-table-search" 
                   onkeyup="filterTableLive(this.value)"
                   placeholder="Ketik untuk filter instan...">
        </div>
    </div>

    <!-- TABEL TRANSAKSI -->
    <div class="overflow-x-auto print:overflow-visible">
        <table id="transactions-table" class="w-full text-left text-xs border-collapse print:text-black">
            <colgroup>
                <col style="width: 4%;">
                <col style="width: 6%;">
                <col style="width: 11%;">
                <col style="width: 14%;">
                <col style="width: 15%;">
                <col style="width: 14%;">
                <col style="width: 14%;">
                <col style="width: 8%;">
                <col style="width: 8%;">
                <col style="width: 6%;">
            </colgroup>
            <thead>
                <tr class="border-b border-black/15 text-[11px] font-bold uppercase tracking-wider text-black/70 bg-slate-50 print:bg-slate-200 print:text-black">
                    <th class="py-2.5 px-1 text-center">No</th>
                    <th class="py-2.5 px-1.5">ID</th>
                    <th class="py-2.5 px-2">Tipe Akses</th>
                    <th class="py-2.5 px-2">Identitas / Kode</th>
                    <th class="py-2.5 px-2">Pengendara</th>
                    <th class="py-2.5 px-1.5">Waktu Masuk</th>
                    <th class="py-2.5 px-1.5">Waktu Keluar</th>
                    <th class="py-2.5 px-1 text-center">Durasi</th>
                    <th class="py-2.5 px-2 text-right">Biaya (Rp)</th>
                    <th class="py-2.5 px-1 text-center">Status</th>
                </tr>
            </thead>
            <tbody id="transactions-tbody" class="divide-y divide-black/10 font-medium text-black">
                @if ($transactions->isEmpty())
                    <tr id="no-tx-row">
                        <td colspan="10" class="py-12 text-center text-black/50">
                            <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3 text-slate-400 text-lg">
                                <i class="fa-solid fa-receipt"></i>
                            </div>
                            <p class="font-bold text-sm text-black">Tidak ada transaksi ditemukan</p>
                            <p class="text-xs text-black/50 mt-1">Coba sesuaikan kata kunci pencarian atau rentang periode filter.</p>
                            <a href="{{ route('admin.reports.index') }}" class="inline-block mt-3 px-3 py-1 bg-[#00719C] text-white text-xs font-bold rounded-md hover:bg-[#005c80] transition">
                                Reset Semua Filter
                            </a>
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
                        <tr class="hover:bg-slate-50/80 transition print:hover:bg-transparent tx-row"
                            data-search="{{ strtolower(($t->user->name ?? '') . ' ' . ($t->ticket_code ?? '') . ' ' . ($t->card->license_plate ?? '') . ' ' . ($t->card->uid ?? '') . ' #' . $t->id . ' ' . ($t->deviceIn->device_code ?? '') . ' ' . ($t->deviceOut->device_code ?? '')) }}">
                            <td class="py-2.5 px-1 text-center text-black/40 font-mono text-[10px] print:text-black">{{ $rowNo++ }}</td>
                            <td class="py-2.5 px-1.5 font-mono font-bold text-black text-[11px]">#{{ $t->id }}</td>
                            <td class="py-2.5 px-2">
                                @if($isTicket)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded font-bold text-[10px] bg-amber-50 text-amber-800 border border-amber-300">
                                        <i class="fa-solid fa-ticket text-amber-600"></i>
                                        <span>KARCIS</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded font-bold text-[10px] bg-sky-50 text-[#00719C] border border-[#00719C]/30">
                                        <i class="fa-solid fa-id-card text-[#00719C]"></i>
                                        <span>KARTU RFID</span>
                                    </span>
                                @endif
                            </td>
                            <td class="py-2.5 px-2 font-mono">
                                @if($isTicket)
                                    <!-- Tiket: MURNI KODE SAJA (Tanpa No Plat) -->
                                    <span class="font-bold text-amber-900 block text-xs leading-tight">{{ $t->ticket_code }}</span>
                                    <span class="text-[9px] text-amber-700/70 block font-normal font-sans">Karcis Pengunjung</span>
                                @else
                                    <!-- Kartu RFID: Ada No Plat & UID -->
                                    <span class="font-bold text-black block text-xs leading-tight">{{ $t->card->license_plate ?? 'NON-PLAT' }}</span>
                                    <span class="text-[9px] text-black/50 block font-normal font-sans">UID: {{ $t->card->uid ?? '-' }}</span>
                                @endif
                            </td>
                            <td class="py-2.5 px-2">
                                <div class="font-bold text-black text-[11px] leading-tight truncate">
                                    {{ $isTicket ? 'Pengendara Umum' : ($t->user->name ?? 'Member Parkir') }}
                                </div>
                                @if (!$isTicket && !empty($t->user->phone))
                                    <div class="text-[9px] text-black/40 truncate">{{ $t->user->phone }}</div>
                                @endif
                            </td>
                            <td class="py-2.5 px-1.5 font-mono text-[10px] leading-tight text-black">
                                <div>{{ $tapInTimeStr }}</div>
                                <span class="text-[9px] text-[#00719C] block font-bold print:text-black">{{ $t->deviceIn->device_code ?? 'Gate-01' }}</span>
                            </td>
                            <td class="py-2.5 px-1.5 font-mono text-[10px] leading-tight text-black">
                                @if ($tapOutTimeStr)
                                    <div>{{ $tapOutTimeStr }}</div>
                                    <span class="text-[9px] text-emerald-700 block font-bold print:text-black">{{ $t->deviceOut->device_code ?? 'Gate-01' }}</span>
                                @else
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-50 text-amber-800 border border-amber-300 print:border-black print:text-black">
                                        Belum Keluar
                                    </span>
                                @endif
                            </td>
                            <td class="py-2.5 px-1 text-center text-black">
                                @if ($t->status === 'COMPLETED')
                                    <div class="font-bold text-[10px] leading-tight">{{ $durMin }} mnt</div>
                                    <span class="text-[9px] text-black/50 block font-normal">(&plusmn; {{ $billedHours }} Jam)</span>
                                @else
                                    <span class="text-black/40 font-mono">-</span>
                                @endif
                            </td>
                            <td class="py-2.5 px-2 text-right font-extrabold text-black font-mono text-[11px]">
                                Rp {{ number_format($fee, 0, ',', '.') }}
                                @if ($t->balance_after !== null && !$isTicket)
                                    <span class="text-[9px] text-black/40 block font-normal font-sans">Sisa: Rp {{ number_format((int)$t->balance_after, 0, ',', '.') }}</span>
                                @endif
                            </td>
                            <td class="py-2.5 px-1 text-center">
                                @if ($t->status === 'COMPLETED')
                                    <span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold bg-[#00719C]/10 text-[#00719C] border border-[#00719C]/30 print:border-black print:text-black print:bg-transparent">
                                        SELESAI
                                    </span>
                                    <a href="{{ route('receipt.show', $t->id) }}" target="_blank" title="Cetak Struk Thermal 58mm (Eppos EP589 V2)" class="no-print block text-[9px] text-[#00719C] hover:underline font-bold mt-1">
                                        <i class="fa-solid fa-receipt"></i> Struk 58mm
                                    </a>
                                @else
                                    <span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold bg-[#FFD51E]/20 text-amber-900 border border-[#FFD51E] print:border-black print:text-black print:bg-transparent">
                                        PARKIR
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    <!-- Baris Total Penutup -->
                    <tr class="border-t-2 border-black/30 font-bold bg-slate-50 print:bg-slate-100" style="page-break-inside: avoid !important;">
                        <td colspan="8" class="py-3 px-3 text-right uppercase text-[11px] text-black font-extrabold print:text-black">
                            TOTAL PENDAPATAN TERKUMPUL:
                        </td>
                        <td class="py-3 px-2 text-right font-mono text-sm font-black text-[#00719C] print:text-black">
                            Rp {{ number_format($totalRevenue, 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-1 text-center text-xs text-black font-extrabold print:text-black">
                            {{ $completedCount }} Selesai
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <!-- Lembar Tanda Tangan Resmi di Bawah Tabel Print (Hanya Muncul Saat Print) -->
    <div class="print-only mt-4 pt-3 border-t border-black/30 text-xs flex justify-end" style="page-break-inside: avoid !important;">
        <div class="text-right">
            <p class="text-[11px] text-black">Jakarta, {{ date('d F Y') }}</p>
            <p class="font-bold text-black mt-1">Penanggung Jawab / Administrator Parkir,</p>
            <div class="h-16"></div>
            <p class="font-bold text-black underline">( {{ auth()->user()->name ?? 'Administrator' }} )</p>
            <p class="text-[10px] text-black/60">NIP / ID: {{ auth()->user()->email ?? 'admin@eparkir.local' }}</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // 1. Data Trend Pendapatan Harian / Per Jam
    const trendLabels = {!! json_encode(array_keys($trendData)) !!};
    const trendValues = {!! json_encode(array_values($trendData)) !!};
    const durationBuckets = {!! json_encode($durationBuckets) !!};
    const trendType = {!! json_encode($trendType) !!};

    // Inisialisasi Revenue Trend Chart (Bar Chart Cantik & Responsif)
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

    // Inisialisasi Duration Doughnut Chart
    const ctxDuration = document.getElementById('durationPieChart').getContext('2d');
    new Chart(ctxDuration, {
        type: 'doughnut',
        data: {
            labels: Object.keys(durationBuckets),
            datasets: [{
                data: Object.values(durationBuckets),
                backgroundColor: [
                    '#00719C',
                    '#FFD51E',
                    '#091530',
                    '#94A3B8'
                ],
                borderWidth: 2,
                borderColor: '#FFFFFF'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#000000',
                        boxWidth: 10,
                        font: { size: 10, weight: 'bold' }
                    }
                }
            },
            cutout: '68%'
        }
    });

    // 2. Client-Side Instant Table Filter
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
        if (countBadge) {
            countBadge.innerText = visibleCount;
        }
    }

    // 3. Cetak Laporan Bersih (Hapus Judul / Header Bawaan Browser)
    function executePrint() {
        window._tempDocTitle = document.title;
        document.title = "";
        window.print();
        setTimeout(() => {
            document.title = window._tempDocTitle || 'Laporan Pendapatan & Transaksi';
        }, 1000);
    }

    window.addEventListener('beforeprint', function() {
        window._tempDocTitle = document.title;
        document.title = "";
    });

    window.addEventListener('afterprint', function() {
        if (window._tempDocTitle) {
            document.title = window._tempDocTitle;
        }
    });
</script>
@endpush
