@extends('layouts.admin')

@section('title', 'Audit Log Akses Gerbang')
@section('header_title', 'Log Aktivitas & Audit Akses')
@section('header_subtitle', 'Catatan kronologis seluruh interaksi sensor RFID, penolakan akses, dan aksi pembukaan manual')

@section('content')
<!-- SUMMARY COUNTERS (White Cards) -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-sm flex items-center justify-between">
        <div>
            <div class="text-xs uppercase font-bold text-slate-500">Total Audit Log</div>
            <div class="text-3xl font-extrabold text-[#091530] mt-1">{{ $totalLogs }}</div>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-700 flex items-center justify-center text-xl shadow-sm">
            <i class="fa-solid fa-list"></i>
        </div>
    </div>
    <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-sm flex items-center justify-between">
        <div>
            <div class="text-xs uppercase font-bold text-slate-500">Akses Diterima (Granted)</div>
            <div class="text-3xl font-extrabold text-[#00719C] mt-1">{{ $grantedLogs }}</div>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-[#ebf2fe] text-[#00719C] flex items-center justify-center text-xl shadow-sm">
            <i class="fa-solid fa-circle-check"></i>
        </div>
    </div>
    <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-sm flex items-center justify-between">
        <div>
            <div class="text-xs uppercase font-bold text-slate-500">Akses Ditolak (Denied)</div>
            <div class="text-3xl font-extrabold text-rose-600 mt-1">{{ $deniedLogs }}</div>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl shadow-sm">
            <i class="fa-solid fa-circle-xmark"></i>
        </div>
    </div>
</div>

<!-- CONTROLS & TABLE CARD (White Card) -->
<div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-sm">
    <!-- Filter Toolbar -->
    <form method="GET" action="{{ route('admin.logs.index') }}" class="flex flex-wrap items-center gap-3 mb-6">
        <div class="search-wrapper-box flex-1 min-w-[200px]">
            <span class="search-icon">
                <i class="fa-solid fa-magnifying-glass"></i>
            </span>
            <input type="text" name="search" value="{{ $search }}"
                placeholder="Cari UID kartu, pesan error, gerbang...">
            @if (!empty($search))
                <a href="{{ route('admin.logs.index', array_merge(request()->except('search'))) }}" 
                   title="Hapus pencarian"
                   class="search-clear-btn">
                    <i class="fa-solid fa-circle-xmark"></i>
                </a>
            @endif
        </div>

        <select name="status" onchange="this.form.submit()"
            class="px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-2xl text-xs text-slate-800 font-bold focus:outline-none focus:border-[#00719C]">
            <option value="">Semua Status</option>
            <option value="granted" {{ $filterStatus === 'granted' ? 'selected' : '' }}>Hanya Diterima (Granted)</option>
            <option value="denied" {{ $filterStatus === 'denied' ? 'selected' : '' }}>Hanya Ditolak (Denied)</option>
        </select>

        <select name="device_id" onchange="this.form.submit()"
            class="px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-2xl text-xs text-slate-800 font-bold focus:outline-none focus:border-[#00719C]">
            <option value="">Semua Gerbang</option>
            @foreach ($devicesList as $dev)
                <option value="{{ $dev->id }}" {{ $filterDevice == $dev->id ? 'selected' : '' }}>
                    {{ $dev->device_code }}
                </option>
            @endforeach
        </select>

        <select name="method" onchange="this.form.submit()"
            class="px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-2xl text-xs text-slate-800 font-bold focus:outline-none focus:border-[#00719C]">
            <option value="">Semua Metode</option>
            <option value="rfid" {{ $filterMethod === 'rfid' ? 'selected' : '' }}>RFID Tap</option>
            <option value="web_admin" {{ $filterMethod === 'web_admin' ? 'selected' : '' }}>Web Admin (Override)</option>
        </select>

        <button type="submit" class="px-5 py-2.5 bg-[#091530] hover:bg-slate-800 text-xs font-bold text-white rounded-2xl transition cursor-pointer border-none">
            Filter
        </button>

        @if (!empty($search) || !empty($filterStatus) || !empty($filterDevice) || !empty($filterMethod))
            <a href="{{ route('admin.logs.index') }}" class="px-3 py-2.5 text-xs text-slate-500 hover:text-rose-600 font-bold">
                Reset
            </a>
        @endif
    </form>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
            <thead>
                <tr class="border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500 bg-[#f8fafc]">
                    <th class="py-3 px-4 rounded-l-2xl">Waktu Transaksi</th>
                    <th class="py-3 px-4">Gerbang Titik Akses</th>
                    <th class="py-3 px-4">UID Kartu Scan</th>
                    <th class="py-3 px-4">Metode Akses</th>
                    <th class="py-3 px-4">Status Hasil</th>
                    <th class="py-3 px-4 rounded-r-2xl">Catatan &amp; Log Error</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium">
                @if ($logs->isEmpty())
                    <tr>
                        <td colspan="6" class="py-10 text-center text-slate-400 text-xs">
                            Tidak ada log aktivitas yang cocok dengan filter yang dipilih.
                        </td>
                    </tr>
                @else
                    @foreach ($logs as $l)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3 px-4 font-mono text-slate-500 text-[11px]">
                                {{ $l->created_at ? $l->created_at->format('d M Y H:i:s') : '-' }}
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-mono font-bold text-[#091530]">{{ $l->device->device_code ?? 'Gate-01' }}</div>
                                <div class="text-[10px] text-slate-400 font-semibold">{{ $l->device->location ?? '-' }}</div>
                            </td>
                            <td class="py-3 px-4 font-mono font-bold text-slate-800">
                                <span class="bg-slate-100 px-2.5 py-1 rounded-xl border border-slate-200">
                                    {{ $l->scan_uid }}
                                </span>
                            </td>
                            <td class="py-3 px-4 uppercase text-[11px] font-bold text-slate-700">
                                @if ($l->access_method === 'rfid')
                                    <span class="text-[#00719C]"><i class="fa-solid fa-wifi mr-1"></i> RFID</span>
                                @elseif ($l->access_method === 'web_admin')
                                    <span class="text-[#b39000]"><i class="fa-solid fa-user-shield mr-1"></i> ADMIN</span>
                                @else
                                    <span class="text-blue-500"><i class="fa-solid fa-mobile-screen mr-1"></i> MOBILE</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                @if ($l->status === 'granted')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-[#ebf2fe] text-[#00719C] border border-[#00719C]/20">
                                        <i class="fa-solid fa-check mr-1"></i> GRANTED
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-50 text-rose-600 border border-rose-200">
                                        <i class="fa-solid fa-xmark mr-1"></i> DENIED
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-slate-700">
                                <div class="flex items-center justify-between gap-2">
                                    <span>{{ $l->notes ?: '-' }}</span>
                                    @if ($l->status === 'denied' && str_contains($l->notes ?? '', 'tidak terdaftar'))
                                        <a href="{{ route('admin.cards.index', ['new_uid' => $l->scan_uid]) }}"
                                            class="px-2.5 py-1 rounded-xl bg-[#00719C] hover:bg-[#155ce0] text-white text-[10px] font-bold transition whitespace-nowrap shadow-sm">
                                            + Daftarkan Kartu
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>
@endsection
