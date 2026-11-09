@extends('layouts.owner')

@section('title', 'Rincian Transaksi Parkir - Owner')
@section('header_title', 'Rincian Transaksi Parkir')
@section('header_subtitle', 'Audit dan monitoring lengkap data riil transaksi kendaraan masuk dan keluar')

@section('content')
<style>
@media print {
    @page {
        size: landscape;
        margin: 8mm 10mm !important;
    }
    .d-print-none, #sidebar, #owner-topbar, #sidebar-backdrop, footer {
        display: none !important;
    }
    html, body {
        background: #FFFFFF !important;
        color: #000000 !important;
        font-size: 8pt !important;
    }
    thead {
        display: table-row-group !important;
    }
    tr {
        page-break-inside: avoid !important;
    }
}
</style>

<!-- =========================================================================
     A. KOP RESMI CETAK (Hanya Muncul Saat Klik "Cetak Laporan" / window.print)
     ========================================================================= -->
<div class="d-none d-print-block mb-3 text-dark">
    <div class="border-bottom border-2 border-dark pb-2 mb-2 d-flex align-items-center justify-content-between">
        <div>
            <div class="text-uppercase fw-bold text-primary small" style="font-size: 9px; letter-spacing: 0.1em;">E-Parkir IoT Platform &bull; Sigma Boys</div>
            <h1 class="h5 fw-bold text-dark mt-1 mb-0">LAPORAN RINCIAN TRANSAKSI KENDARAAN PARKIR</h1>
            <p class="small text-secondary mb-0" style="font-size: 10px;">Rekapitulasi Audit Riil Transaksi &bull; Portal Eksekutif Pemilik Usaha (Owner)</p>
        </div>
        <div class="text-end small font-monospace" style="font-size: 9px; line-height: 1.3;">
            <div><strong>Waktu Cetak:</strong> {{ date('d/m/Y H:i') }} WIB</div>
            <div><strong>Dicetak Oleh:</strong> {{ auth()->user()->name ?? 'Owner' }} (OWNER)</div>
            <div><strong>Filter Tanggal:</strong> {{ $date ? \Carbon\Carbon::parse($date)->format('d/m/Y') : 'SEMUA TANGGAL' }}</div>
        </div>
    </div>
</div>

<div class="d-flex flex-column gap-4">

    <!-- =========================================================================
         B. FILTER TOOLBAR LENGKAP DENGAN PRESET & PENCARIAN (d-print-none)
         ========================================================================= -->
    <div class="card border-0 shadow-sm rounded-4 d-print-none">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('owner.transactions.index') }}" id="filter-form" class="d-flex flex-column gap-3">
                <!-- BARIS ATAS: Preset Periode Cepat & Tombol Aksi (Export Excel & Cetak Dokumen) -->
                <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 pb-3 border-bottom">
                    <div class="d-flex flex-wrap align-items-center gap-1">
                        <span class="small fw-bold text-secondary text-uppercase me-2" style="font-size: 0.75rem;">
                            <i class="bi bi-calendar-event text-primary me-1"></i>Pilih Cepat:
                        </span>
                        
                        <a href="{{ route('owner.transactions.index', array_merge(request()->except(['page', 'date']), ['preset' => 'today'])) }}" 
                           class="btn btn-sm {{ $preset === 'today' || $date === date('Y-m-d') ? 'btn-primary' : 'btn-light text-secondary border' }} rounded-2 fw-semibold">
                            Hari Ini
                        </a>

                        <a href="{{ route('owner.transactions.index', array_merge(request()->except(['page', 'date']), ['preset' => 'yesterday'])) }}" 
                           class="btn btn-sm {{ $preset === 'yesterday' ? 'btn-primary' : 'btn-light text-secondary border' }} rounded-2 fw-semibold">
                            Kemarin
                        </a>

                        <a href="{{ route('owner.transactions.index', array_merge(request()->except(['page', 'date']), ['preset' => 'all', 'date' => ''])) }}" 
                           class="btn btn-sm {{ ($preset === 'all' || empty($preset)) && empty($date) ? 'btn-primary' : 'btn-light text-secondary border' }} rounded-2 fw-semibold">
                            Semua Hari
                        </a>
                    </div>

                    <!-- Tombol Export Excel & Cetak Dokumen -->
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('owner.reports.export', request()->all()) }}" 
                           title="Download data tabel dalam format spreadsheet Excel (.xls)" 
                           class="btn btn-success btn-sm fw-bold d-inline-flex align-items-center gap-1.5 rounded-3 shadow-sm">
                            <i class="bi bi-file-earmark-excel-fill"></i>
                            <span>Export Excel</span>
                        </a>

                        <button type="button" 
                                onclick="window.print()" 
                                title="Cetak laporan resmi ke format kertas A4 Landscape / Simpan PDF" 
                                class="btn btn-warning btn-sm fw-bold text-dark d-inline-flex align-items-center gap-1.5 rounded-3 shadow-sm">
                            <i class="bi bi-printer-fill"></i>
                            <span>Cetak Laporan</span>
                        </button>
                    </div>
                </div>

                <!-- BARIS BAWAH: Input Pencarian, Filter Metode, Filter Lantai, Input Tanggal Tunggal, & Terapkan -->
                <div class="row g-2 align-items-center">
                    <!-- Pencarian Cepat -->
                    <div class="col-12 col-lg-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0 text-secondary">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" 
                                   id="main-search-input" 
                                   name="search" 
                                   value="{{ $search }}" 
                                   class="form-control border-start-0" 
                                   placeholder="Cari kode tiket, plat, nama, UID...">
                            @if (!empty($search))
                                <a href="{{ route('owner.transactions.index', array_merge(request()->except(['search', 'page']))) }}" 
                                   title="Hapus pencarian" 
                                   class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle-fill"></i>
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- Filter Tipe Akses -->
                    <div class="col-6 col-sm-3 col-lg-2">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-secondary small" style="font-size: 0.7rem;">Tipe</span>
                            <select name="access_type" class="form-select form-select-sm fw-semibold">
                                <option value="all" {{ $accessType === 'all' ? 'selected' : '' }}>Semua</option>
                                <option value="ticket" {{ $accessType === 'ticket' ? 'selected' : '' }}>Karcis</option>
                                <option value="card" {{ $accessType === 'card' ? 'selected' : '' }}>RFID</option>
                            </select>
                        </div>
                    </div>

                    <!-- Filter Lantai -->
                    <div class="col-6 col-sm-3 col-lg-2">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-secondary small" style="font-size: 0.7rem;">Lantai</span>
                            <select name="floor" class="form-select form-select-sm fw-semibold">
                                <option value="all" {{ ($floorFilter ?? 'all') === 'all' ? 'selected' : '' }}>Semua</option>
                                <option value="1" {{ ($floorFilter ?? '') === '1' ? 'selected' : '' }}>LT 1</option>
                                <option value="2" {{ ($floorFilter ?? '') === '2' ? 'selected' : '' }}>LT 2</option>
                            </select>
                        </div>
                    </div>

                    <!-- Input Tanggal Tunggal -->
                    <div class="col-8 col-sm-4 col-lg-2">
                        <input type="date" 
                               name="date" 
                               value="{{ $date }}" 
                               title="Pilih Tanggal Transaksi" 
                               class="form-control form-control-sm font-monospace fw-semibold">
                    </div>

                    <!-- Tombol Filter & Reset -->
                    <div class="col-4 col-sm-2 col-lg-2 d-flex align-items-center gap-1">
                        <button type="submit" class="btn btn-primary btn-sm fw-bold d-inline-flex align-items-center gap-1 flex-grow-1">
                            <i class="bi bi-funnel-fill"></i>
                            <span>Filter</span>
                        </button>

                        @if(!empty($search) || !empty($date) || ($preset !== 'all' && !empty($preset)) || $accessType !== 'all' || ($floorFilter ?? 'all') !== 'all')
                            <a href="{{ route('owner.transactions.index') }}" 
                               title="Kembalikan filter ke semua data" 
                               class="btn btn-outline-danger btn-sm">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- =========================================================================
         C. TABEL DATA RINCIAN TRANSAKSI PARKIR
         ========================================================================= -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        
        <!-- Table Header Toolbar (d-print-none) -->
        <div class="card-header bg-white border-bottom p-4 d-flex align-items-center justify-content-between gap-3 d-print-none">
            <div class="d-flex align-items-center gap-3">
                <div class="d-flex align-items-center justify-content-center rounded-3 text-white" style="width: 2.25rem; height: 2.25rem; background-color: #00719C;">
                    <i class="bi bi-card-list"></i>
                </div>
                <div>
                    <h3 class="fs-6 fw-bold text-dark text-uppercase tracking-wide mb-0">Rincian Transaksi Parkir</h3>
                    <p class="small text-secondary mb-0">
                        Total ditemukan <strong class="text-dark font-monospace">{{ $totalTransactionsCount }}</strong> rekaman transaksi riil.
                    </p>
                </div>
            </div>
        </div>

        <!-- Table Canvas -->
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-nowrap">
                <thead class="table-light">
                    <tr class="small text-secondary text-uppercase fw-bold" style="font-size: 0.75rem;">
                        <th class="py-3 px-3 text-center" style="width: 40px;">No</th>
                        <th class="py-3 px-3" style="width: 60px;">ID</th>
                        <th class="py-3 px-3">Tipe Akses</th>
                        <th class="py-3 px-3">Lantai</th>
                        <th class="py-3 px-3">Identitas / Kode</th>
                        <th class="py-3 px-3">Pengendara</th>
                        <th class="py-3 px-3">Waktu</th>
                        <th class="py-3 px-3 text-center">Durasi</th>
                        <th class="py-3 px-3 text-end">Tarif</th>
                        <th class="py-3 px-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($transactions->isEmpty())
                        <tr>
                            <td colspan="10" class="py-5 text-center text-secondary">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-opacity-50"></i>
                                <div class="fw-bold small">Tidak ada data transaksi yang sesuai filter</div>
                                <div class="small text-muted mt-1">Silakan sesuaikan kata kunci pencarian atau rentang periode filter.</div>
                            </td>
                        </tr>
                    @else
                        @php 
                            $rowNo = $transactions->firstItem(); 
                        @endphp
                        @foreach ($transactions as $t)
                            @php
                                $durMin = (int)($t->duration_minutes ?? 0);
                                $billedHours = ceil(max(1, $durMin) / 60);
                                $fee = (int)($t->total_fee ?? 0);
                                $tapInTimeStr = $t->tap_in_time ? \Carbon\Carbon::parse($t->tap_in_time)->format('d/m/Y H:i') : '-';
                                $tapOutTimeStr = $t->tap_out_time ? \Carbon\Carbon::parse($t->tap_out_time)->format('d/m/Y H:i') : null;
                                $isTicket = !empty($t->ticket_code);
                                $floorNum = $t->parking_floor ?? 1;
                            @endphp
                            <tr>
                                <td class="py-2.5 px-3 text-center text-secondary font-monospace small">{{ $rowNo++ }}</td>
                                <td class="py-2.5 px-3 font-monospace fw-bold text-dark small">#{{ $t->id }}</td>
                                <td class="py-2.5 px-3">
                                    @if($isTicket)
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fw-bold">
                                            <i class="bi bi-ticket-perforated-fill me-1 d-print-none"></i>KARCIS
                                        </span>
                                    @else
                                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle fw-bold">
                                            <i class="bi bi-credit-card-2-front-fill me-1 d-print-none"></i>RFID
                                        </span>
                                    @endif
                                </td>
                                <!-- Kolom Lantai Parkir -->
                                <td class="py-2.5 px-3">
                                    @if($floorNum == 2)
                                        <span class="badge bg-warning text-dark fw-bold">LT 2</span>
                                    @else
                                        <span class="badge bg-primary text-white fw-bold">LT 1</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 font-monospace">
                                    @if($isTicket)
                                        <span class="fw-bold text-warning-emphasis d-block small">{{ $t->ticket_code }}</span>
                                        <span class="small text-muted d-block d-print-none" style="font-size: 0.7rem;">Karcis Masuk</span>
                                    @else
                                        <span class="fw-bold text-dark d-block small">{{ $t->card->license_plate ?? 'NON-PLAT' }}</span>
                                        <span class="small text-muted d-block" style="font-size: 0.7rem;">UID: {{ $t->card->uid ?? '-' }}</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3">
                                    <div class="fw-bold text-dark small">
                                        {{ $isTicket ? 'Pengendara Umum' : ($t->user->name ?? 'Member Parkir') }}
                                    </div>
                                    @if (!$isTicket && !empty($t->user->phone))
                                        <div class="small text-muted d-print-none" style="font-size: 0.7rem;">{{ $t->user->phone }}</div>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 font-monospace small">
                                    <div class="fw-bold">{{ $tapInTimeStr }}</div>
                                    @if ($tapOutTimeStr)
                                        <span class="small text-muted d-block font-sans" style="font-size: 0.7rem;">s/d {{ \Carbon\Carbon::parse($t->tap_out_time)->format('H:i') }}</span>
                                    @else
                                        <span class="badge bg-warning text-dark font-sans" style="font-size: 0.65rem;">Belum Keluar</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 text-center">
                                    @if ($t->status === 'COMPLETED')
                                        <div class="fw-bold small">{{ $durMin }} mnt</div>
                                        <span class="small text-muted d-block" style="font-size: 0.7rem;">(&plusmn; {{ $billedHours }} Jam)</span>
                                    @else
                                        <span class="text-secondary font-monospace">-</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 text-end font-monospace fw-bold text-dark small">
                                    Rp {{ number_format($fee, 0, ',', '.') }}
                                    @if ($t->balance_after !== null)
                                        <span class="small text-muted d-block fw-normal font-sans d-print-none" style="font-size: 0.7rem;">Sisa: Rp {{ number_format((int)$t->balance_after, 0, ',', '.') }}</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 text-center">
                                    @if ($t->status === 'COMPLETED')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold">
                                            SELESAI
                                        </span>
                                        <a href="{{ route('receipt.show', $t->id) }}" target="_blank" title="Cetak Struk Thermal 58mm" class="d-print-none d-block small text-primary fw-bold mt-1 text-decoration-none" style="font-size: 0.75rem;">
                                            <i class="bi bi-receipt"></i> Struk 58mm
                                        </a>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fw-bold">
                                            PARKIR
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach

                        <!-- Baris Ringkasan Total Pendapatan Sesuai Filter -->
                        <tr class="table-light border-top border-2 border-dark-subtle">
                            <td colspan="7" class="py-3 px-3 text-end text-uppercase small fw-bold text-dark">
                                TOTAL PENDAPATAN :
                            </td>
                            <td colspan="3" class="py-3 px-3 text-end font-monospace fs-5 fw-bold text-primary">
                                Rp {{ number_format($totalRevenueFiltered ?? 0, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Lembar Tanda Tangan Resmi di Bawah Cetak Dokumen (Hanya Muncul Saat Print A4) -->
        <div class="d-none d-print-flex justify-content-end mt-4 pt-3 border-top text-dark" style="page-break-inside: avoid !important;">
            <div class="text-end" style="width: 220px;">
                <p class="small mb-1">Jakarta, {{ date('d F Y') }}</p>
                <p class="fw-bold mb-4">Pemilik Usaha (Owner),</p>
                <div style="height: 50px;"></div>
                <p class="fw-bold text-decoration-underline mb-0">( {{ auth()->user()->name ?? 'Owner Eksekutif' }} )</p>
                <p class="small text-secondary mb-0" style="font-size: 8px;">ID Akun: {{ auth()->user()->email ?? 'owner@eparkir.local' }}</p>
            </div>
        </div>

        <!-- Pagination Controls (d-print-none) -->
        @if($transactions->hasPages())
            <div class="card-footer bg-light border-top p-3 d-flex flex-column flex-sm-row align-items-center justify-content-between gap-2 d-print-none">
                <div class="small text-secondary">
                    Menampilkan <strong>{{ $transactions->firstItem() }}</strong> - <strong>{{ $transactions->lastItem() }}</strong> dari <strong>{{ $transactions->total() }}</strong> total data
                </div>
                <div>
                    {{ $transactions->links() }}
                </div>
            </div>
        @endif
    </div>

</div>
@endsection
