@extends('layouts.owner')

@section('title', 'Rincian Transaksi Parkir - Owner')
@section('header_title', 'Rincian Transaksi Parkir')
@section('header_subtitle', 'Audit dan monitoring lengkap data riil transaksi kendaraan masuk dan keluar')

@section('content')
<!-- =========================================================================
     A. KOP RESMI CETAK (Hanya Muncul Saat Klik "Cetak Laporan" / window.print)
     ========================================================================= -->
<style>
@media print {
    @page {
        size: landscape;
        margin: 8mm 10mm !important;
    }
    .no-print, #sidebar, #owner-topbar, #sidebar-backdrop, footer {
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

<div class="print-only mb-3 text-black">
    <!-- Kop Surat Resmi -->
    <div class="border-b-2 border-black pb-2 mb-2 flex items-center justify-between">
        <div>
            <div class="text-[9px] uppercase font-extrabold tracking-widest text-[#00719C]">E-Parkir IoT Platform &bull; Sigma Boys</div>
            <h1 class="text-lg font-black tracking-tight text-black mt-0.5">LAPORAN RINCIAN TRANSAKSI KENDARAAN PARKIR</h1>
            <p class="text-[10px] text-black/70 font-medium">Rekapitulasi Audit Riil Transaksi &bull; Portal Eksekutif Pemilik Usaha (Owner)</p>
        </div>
        <div class="text-right text-[9px] font-mono leading-tight">
            <div><strong>Waktu Cetak:</strong> {{ date('d/m/Y H:i') }} WIB</div>
            <div><strong>Dicetak Oleh:</strong> {{ auth()->user()->name ?? 'Owner' }} (OWNER)</div>
            <div><strong>Filter Tanggal:</strong> {{ $date ? \Carbon\Carbon::parse($date)->format('d/m/Y') : 'SEMUA TANGGAL' }}</div>
        </div>
    </div>
</div>

<div class="space-y-4">

    <!-- =========================================================================
         B. FILTER TOOLBAR LENGKAP DENGAN PRESET & PENCARIAN (no-print)
         ========================================================================= -->
    <div class="bg-white clean-border rounded-xl p-4 shadow-sm no-print">
        <form method="GET" action="{{ route('owner.transactions.index') }}" id="filter-form" class="space-y-3">
            <!-- BARIS ATAS: Preset Periode Cepat & Tombol Aksi (Export Excel & Cetak Dokumen) -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-black/10">
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="text-[11px] font-bold text-black/50 uppercase tracking-wider mr-1">
                        <i class="fa-solid fa-calendar-day text-[#00719C] me-1"></i>Pilih Cepat:
                    </span>
                    
                    <a href="{{ route('owner.transactions.index', array_merge(request()->except(['page', 'date']), ['preset' => 'today'])) }}" 
                       class="px-3 py-1 rounded-md text-xs font-semibold transition {{ $preset === 'today' || $date === date('Y-m-d') ? 'bg-[#00719C] text-white shadow-sm' : 'bg-slate-100 hover:bg-slate-200 text-black/80' }}">
                        Hari Ini
                    </a>

                    <a href="{{ route('owner.transactions.index', array_merge(request()->except(['page', 'date']), ['preset' => 'yesterday'])) }}" 
                       class="px-3 py-1 rounded-md text-xs font-semibold transition {{ $preset === 'yesterday' ? 'bg-[#00719C] text-white shadow-sm' : 'bg-slate-100 hover:bg-slate-200 text-black/80' }}">
                        Kemarin
                    </a>

                    <a href="{{ route('owner.transactions.index', array_merge(request()->except(['page', 'date']), ['preset' => 'all', 'date' => ''])) }}" 
                       class="px-3 py-1 rounded-md text-xs font-semibold transition {{ ($preset === 'all' || empty($preset)) && empty($date) ? 'bg-[#00719C] text-white shadow-sm' : 'bg-slate-100 hover:bg-slate-200 text-black/80' }}">
                        Semua Hari
                    </a>
                </div>

                <!-- Tombol Export Excel & Cetak Dokumen -->
                <div class="flex items-center gap-2">
                    <a href="{{ route('owner.reports.export', request()->all()) }}" 
                       title="Download data tabel dalam format spreadsheet Excel (.xls)" 
                       class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg transition flex items-center gap-1.5 shadow-sm">
                        <i class="fa-solid fa-file-excel text-sm"></i>
                        <span>Export Excel</span>
                    </a>

                    <button type="button" 
                            onclick="window.print()" 
                            title="Cetak laporan resmi ke format kertas A4 Landscape / Simpan PDF" 
                            class="px-3.5 py-1.5 bg-[#FFD51E] hover:bg-[#e6be12] text-black text-xs font-extrabold rounded-lg transition flex items-center gap-1.5 shadow-sm cursor-pointer border-none">
                        <i class="fa-solid fa-print text-sm"></i>
                        <span>Cetak Laporan</span>
                    </button>
                </div>
            </div>

            <!-- BARIS BAWAH: Input Pencarian, Filter Metode, Filter Lantai, Input Tanggal Tunggal, & Terapkan -->
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
                           placeholder="Cari kode tiket, plat nomor, nama pengendara, UID, atau ID...">
                    @if (!empty($search))
                        <a href="{{ route('owner.transactions.index', array_merge(request()->except(['search', 'page']))) }}" 
                           title="Hapus pencarian" 
                           class="search-clear-btn">
                            <i class="fa-solid fa-circle-xmark"></i>
                        </a>
                    @endif
                </div>

                <!-- Filter Tipe Akses (Kartu vs Karcis) -->
                <div class="flex items-center gap-1.5 bg-slate-50 p-1 rounded-lg clean-border shrink-0">
                    <span class="text-[10px] uppercase font-bold text-black/50 px-1.5">Metode:</span>
                    <select name="access_type" class="text-xs font-bold text-black px-2 py-1 bg-white rounded clean-border focus:outline-none focus:border-[#00719C]">
                        <option value="all" {{ $accessType === 'all' ? 'selected' : '' }}>Semua Akses</option>
                        <option value="ticket" {{ $accessType === 'ticket' ? 'selected' : '' }}>Karcis Tiket</option>
                        <option value="card" {{ $accessType === 'card' ? 'selected' : '' }}>Kartu RFID Member</option>
                    </select>
                </div>

                <!-- Filter Lantai Parkir (LT 1 vs LT 2) -->
                <div class="flex items-center gap-1.5 bg-slate-50 p-1 rounded-lg clean-border shrink-0">
                    <span class="text-[10px] uppercase font-bold text-black/50 px-1.5">Lantai:</span>
                    <select name="floor" class="text-xs font-bold text-black px-2 py-1 bg-white rounded clean-border focus:outline-none focus:border-[#00719C]">
                        <option value="all" {{ ($floorFilter ?? 'all') === 'all' ? 'selected' : '' }}>Semua Lantai</option>
                        <option value="1" {{ ($floorFilter ?? '') === '1' ? 'selected' : '' }}>Lantai 1 (LT 1)</option>
                        <option value="2" {{ ($floorFilter ?? '') === '2' ? 'selected' : '' }}>Lantai 2 (LT 2)</option>
                    </select>
                </div>

                <!-- Input Tanggal Tunggal (Single Date Picker) -->
                <div class="flex items-center gap-1.5 bg-slate-50 p-1 rounded-lg clean-border shrink-0">
                    <span class="text-[10px] uppercase font-bold text-black/50 px-1.5">
                        <i class="fa-regular fa-calendar text-[#00719C] me-1"></i>Tanggal:
                    </span>
                    <input type="date" 
                           name="date" 
                           value="{{ $date }}" 
                           title="Pilih Tanggal Transaksi" 
                           class="text-xs font-mono font-semibold text-black px-2.5 py-1 bg-white rounded clean-border focus:outline-none focus:border-[#00719C]">
                </div>

                <!-- Tombol Filter & Reset -->
                <div class="flex items-center gap-2 shrink-0">
                    <button type="submit" class="px-4 py-2 bg-[#00719C] hover:bg-[#005c80] text-white text-xs font-bold rounded-lg transition flex items-center gap-1.5 shadow-sm cursor-pointer border-none">
                        <i class="fa-solid fa-filter text-[10px]"></i>
                        <span>Filter</span>
                    </button>

                    @if(!empty($search) || !empty($date) || ($preset !== 'all' && !empty($preset)) || $accessType !== 'all' || ($floorFilter ?? 'all') !== 'all')
                        <a href="{{ route('owner.transactions.index') }}" 
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
         C. TABEL DATA RINCIAN TRANSAKSI PARKIR
         ========================================================================= -->
    <div class="bg-white clean-border rounded-xl shadow-sm overflow-hidden print:border-none print:shadow-none">
        
        <!-- Table Header Toolbar (no-print) -->
        <div class="p-4 clean-border-b flex items-center justify-between gap-3 bg-white no-print">
            <div class="flex items-center gap-2.5">
                <div class="icon-box" style="width: 2rem; height: 2rem; border: 1.5px solid #005677;">
                    <i class="fa-solid fa-table-list text-xs"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-black uppercase tracking-wide">Rincian Transaksi Parkir</h3>
                    <p class="text-xs text-black/50">
                        Total ditemukan <strong class="text-black font-mono font-bold">{{ $totalTransactionsCount }}</strong> rekaman transaksi riil.
                    </p>
                </div>
            </div>
        </div>

        <!-- Table Canvas -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="clean-border-b bg-slate-50 text-black/60 text-[10px] font-bold uppercase tracking-wider print:bg-slate-200 print:text-black">
                        <th class="py-2.5 px-2 text-center w-10">No</th>
                        <th class="py-2.5 px-2 w-16">ID</th>
                        <th class="py-2.5 px-3">Tipe Akses</th>
                        <th class="py-2.5 px-3">Lantai</th>
                        <th class="py-2.5 px-3">Identitas / Kode</th>
                        <th class="py-2.5 px-3">Pengendara</th>
                        <th class="py-2.5 px-3">Waktu</th>
                        <th class="py-2.5 px-2 text-center">Durasi</th>
                        <th class="py-2.5 px-3 text-right">Tarif</th>
                        <th class="py-2.5 px-2 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-black/10 text-black">
                    @if ($transactions->isEmpty())
                        <tr>
                            <td colspan="10" class="py-12 text-center text-black/40">
                                <i class="fa-solid fa-inbox text-3xl mb-2 text-black/20 block"></i>
                                <div class="font-bold text-xs text-black/60">Tidak ada data transaksi yang sesuai filter</div>
                                <div class="text-[11px] mt-0.5">Silakan sesuaikan kata kunci pencarian atau rentang periode filter.</div>
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
                            <tr class="hover:bg-slate-50/80 transition print:hover:bg-transparent">
                                <td class="py-2 px-2 text-center text-black/40 font-mono text-[10px] print:text-black">{{ $rowNo++ }}</td>
                                <td class="py-2 px-2 font-mono font-bold text-black text-[11px]">#{{ $t->id }}</td>
                                <td class="py-2 px-3">
                                    @if($isTicket)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded font-bold text-[10px] bg-amber-50 text-amber-800 border border-amber-300 print:border-black print:text-black">
                                            <i class="fa-solid fa-ticket text-amber-600 print:hidden"></i>
                                            <span>KARCIS</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded font-bold text-[10px] bg-sky-50 text-[#00719C] border border-[#00719C]/30 print:border-black print:text-black">
                                            <i class="fa-solid fa-id-card text-[#00719C] print:hidden"></i>
                                            <span>RFID</span>
                                        </span>
                                    @endif
                                </td>
                                <!-- Kolom Lantai Parkir -->
                                <td class="py-2 px-3">
                                    @if($floorNum == 2)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-black bg-amber-100 text-amber-800 border border-amber-300 print:border-black print:text-black">
                                            LT 2
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-black bg-sky-100 text-[#00719C] border border-sky-300 print:border-black print:text-black">
                                            LT 1
                                        </span>
                                    @endif
                                </td>
                                <td class="py-2 px-3 font-mono">
                                    @if($isTicket)
                                        <span class="font-bold text-amber-900 block text-xs leading-tight print:text-black">{{ $t->ticket_code }}</span>
                                        <span class="text-[9px] text-amber-700/70 block font-normal print:hidden">Karcis Masuk</span>
                                    @else
                                        <span class="font-bold text-black block text-xs leading-tight">{{ $t->card->license_plate ?? 'NON-PLAT' }}</span>
                                        <span class="text-[9px] text-black/50 block font-normal">UID: {{ $t->card->uid ?? '-' }}</span>
                                    @endif
                                </td>
                                <td class="py-2 px-3">
                                    <div class="font-bold text-black text-[11px] leading-tight truncate">
                                        {{ $isTicket ? 'Pengendara Umum' : ($t->user->name ?? 'Member Parkir') }}
                                    </div>
                                    @if (!$isTicket && !empty($t->user->phone))
                                        <div class="text-[9px] text-black/40 truncate print:hidden">{{ $t->user->phone }}</div>
                                    @endif
                                </td>
                                <td class="py-2 px-3 font-mono text-[10px] leading-tight text-black">
                                    <div class="font-bold">{{ $tapInTimeStr }}</div>
                                    @if ($tapOutTimeStr)
                                        <span class="text-[9px] text-black/60 block font-sans">s/d {{ \Carbon\Carbon::parse($t->tap_out_time)->format('H:i') }}</span>
                                    @else
                                        <span class="text-[9px] text-amber-700 block font-bold font-sans">Belum Keluar</span>
                                    @endif
                                </td>
                                <td class="py-2 px-2 text-black text-center">
                                    @if ($t->status === 'COMPLETED')
                                        <div class="font-bold text-[10px] leading-tight">{{ $durMin }} mnt</div>
                                        <span class="text-[9px] text-black/50 block font-normal">(&plusmn; {{ $billedHours }} Jam)</span>
                                    @else
                                        <span class="text-black/40 font-mono">-</span>
                                    @endif
                                </td>
                                <td class="py-2 px-3 text-right font-extrabold text-black font-mono text-[11px]">
                                    Rp {{ number_format($fee, 0, ',', '.') }}
                                    @if ($t->balance_after !== null)
                                        <span class="text-[9px] text-black/40 block font-normal font-sans print:hidden">Sisa: Rp {{ number_format((int)$t->balance_after, 0, ',', '.') }}</span>
                                    @endif
                                </td>
                                <td class="py-2 px-2 text-center">
                                    @if ($t->status === 'COMPLETED')
                                        <span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold bg-[#00719C]/10 text-[#00719C] border border-[#00719C]/30 print:border-black print:text-black print:bg-transparent">
                                            SELESAI
                                        </span>
                                        <a href="{{ route('receipt.show', $t->id) }}" target="_blank" title="Cetak Struk Thermal 58mm" class="no-print block text-[9px] text-[#00719C] hover:underline font-bold mt-1">
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

                        <!-- Baris Ringkasan Total Pendapatan Sesuai Filter (Tanpa Status, Font Besar) -->
                        <tr class="border-t-2 border-black/40 bg-slate-100 print:bg-slate-200" style="page-break-inside: avoid !important;">
                            <td colspan="7" class="py-3.5 px-4 text-right uppercase text-xs sm:text-sm text-black font-black tracking-wider">
                                TOTAL PENDAPATAN :
                            </td>
                            <td colspan="3" class="py-3.5 px-4 text-right font-mono text-base sm:text-2xl font-black text-[#00719C] print:text-black">
                                Rp {{ number_format($totalRevenueFiltered ?? 0, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Lembar Tanda Tangan Resmi di Bawah Cetak Dokumen (Hanya Muncul Saat Print A4) -->
        <div class="print-only mt-4 pt-3 border-t border-black/30 text-xs flex justify-end" style="page-break-inside: avoid !important;">
            <div class="text-right">
                <p class="text-[10px] text-black">Jakarta, {{ date('d F Y') }}</p>
                <p class="font-bold text-black mt-1">Pemilik Usaha (Owner),</p>
                <div class="h-14"></div>
                <p class="font-bold text-black underline">( {{ auth()->user()->name ?? 'Owner Eksekutif' }} )</p>
                <p class="text-[9px] text-black/60">ID Akun: {{ auth()->user()->email ?? 'owner@eparkir.local' }}</p>
            </div>
        </div>

        <!-- Pagination Controls (no-print) -->
        @if($transactions->hasPages())
            <div class="p-4 clean-border-t bg-slate-50 flex items-center justify-between no-print">
                <div class="text-xs text-slate-500 font-medium">
                    Menampilkan <strong class="text-slate-800">{{ $transactions->firstItem() }}</strong> - <strong class="text-slate-800">{{ $transactions->lastItem() }}</strong> dari <strong class="text-slate-800">{{ $transactions->total() }}</strong> total data
                </div>
                <div>
                    {{ $transactions->links() }}
                </div>
            </div>
        @endif
    </div>

</div>
@endsection
