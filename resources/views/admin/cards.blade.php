@extends('layouts.admin')

@section('title', 'Manajemen Kartu RFID')
@section('header_title', 'Kelola Kartu RFID & Pengguna')
@section('header_subtitle', 'Pendaftaran kartu baru, penetapan plat nomor kendaraan, dan kontrol blokir kartu')

@section('content')
<!-- ALERT NOTIFIKASI -->
@if (session('success'))
    <div class="p-4 rounded-2xl border text-sm flex items-center justify-between shadow-sm bg-emerald-50 border-emerald-300 text-emerald-800 mb-6">
        <div class="flex items-center gap-3">
            <i class="fa-solid fa-circle-check text-emerald-600 text-lg"></i>
            <span class="font-bold">{{ session('success') }}</span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
@endif

@if (session('error'))
    <div class="p-4 rounded-2xl border text-sm flex items-center justify-between shadow-sm bg-rose-50 border-rose-300 text-rose-800 mb-6">
        <div class="flex items-center gap-3">
            <i class="fa-solid fa-triangle-exclamation text-rose-600 text-lg"></i>
            <span class="font-bold">{{ session('error') }}</span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
@endif

<!-- STATS SUMMARY: Kotak Kompak & Modern Grid (Clean Editorial) -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <!-- Card 1: Total Kartu -->
    <div class="editorial-card p-5 bg-[#FFFFFF] flex flex-col justify-between">
        <div class="flex items-center justify-between">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-black/60">Total Kartu</span>
            <div class="w-9 h-9 rounded-md bg-[#00719C] text-white flex items-center justify-center text-sm flex-shrink-0">
                <i class="fa-solid fa-credit-card"></i>
            </div>
        </div>
        <div class="mt-4 flex items-baseline gap-2">
            <span class="text-3xl font-bold text-black">{{ $totalCards }}</span>
            <span class="text-xs font-medium text-black/60">kartu</span>
        </div>
        <div class="mt-3 pt-3 clean-border-t text-xs text-black/60 font-medium">
            Tersimpan dalam database sistem
        </div>
    </div>

    <!-- Card 2: Kartu Aktif -->
    <div class="editorial-card p-5 bg-[#FFFFFF] flex flex-col justify-between">
        <div class="flex items-center justify-between">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-black/60">Kartu Aktif</span>
            <div class="w-9 h-9 rounded-md bg-[#00719C] text-white flex items-center justify-center text-sm flex-shrink-0">
                <i class="fa-solid fa-check"></i>
            </div>
        </div>
        <div class="mt-4 flex items-baseline gap-2">
            <span class="text-3xl font-bold text-black">{{ $activeCards }}</span>
            <span class="text-xs font-semibold text-emerald-600">aktif</span>
        </div>
        <div class="mt-3 pt-3 clean-border-t text-xs text-emerald-600 flex items-center gap-1.5 font-medium">
            <span class="status-dot-active"></span>
            <span>Akses diizinkan tap gate</span>
        </div>
    </div>

    <!-- Card 3: Kartu Diblokir -->
    <div class="editorial-card p-5 bg-[#FFFFFF] flex flex-col justify-between">
        <div class="flex items-center justify-between">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-black/60">Kartu Diblokir</span>
            <div class="w-9 h-9 rounded-md bg-[#EF4444] text-white flex items-center justify-center text-sm flex-shrink-0">
                <i class="fa-solid fa-ban"></i>
            </div>
        </div>
        <div class="mt-4 flex items-baseline gap-2">
            <span class="text-3xl font-bold text-black">{{ $blockedCards }}</span>
            <span class="text-xs font-semibold text-rose-600">blokir</span>
        </div>
        <div class="mt-3 pt-3 clean-border-t text-xs text-rose-600 flex items-center gap-1.5 font-medium">
            <span class="w-2 h-2 rounded-full bg-rose-500 inline-block"></span>
            <span>Akses dibekukan sementara</span>
        </div>
    </div>
</div>

<!-- CONTROLS & TABLE CARD -->
<div class="bg-white clean-border rounded-lg p-5">
    <!-- Toolbar -->
    <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3 mb-5">
        <!-- Search & Filter Form -->
        <form method="GET" action="{{ route('admin.cards.index') }}" class="flex flex-wrap items-center gap-2.5 flex-1 max-w-2xl">
            <div class="search-wrapper-box flex-1 min-w-[200px]">
                <span class="search-icon">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" name="search" value="{{ $search }}"
                    placeholder="Cari UID kartu atau nama pemilik...">
                @if (!empty($search))
                    <a href="{{ route('admin.cards.index', ['status' => $status]) }}" 
                       title="Hapus pencarian"
                       class="search-clear-btn">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </a>
                @endif
            </div>

            <select name="status" onchange="this.form.submit()"
                class="px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-2xl text-xs text-slate-700 font-bold focus:outline-none focus:border-[#00719C]">
                <option value="">Semua Status</option>
                <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Hanya Aktif</option>
                <option value="blocked" {{ $status === 'blocked' ? 'selected' : '' }}>Hanya Diblokir</option>
            </select>

            <button type="submit" class="px-4 py-2.5 bg-[#091530] hover:bg-slate-800 text-xs font-bold text-white rounded-2xl transition cursor-pointer">
                Cari
            </button>

            @if (!empty($search) || !empty($status))
                <a href="{{ route('admin.cards.index') }}" class="px-3 py-2.5 text-xs text-slate-500 hover:text-rose-600 font-bold">
                    Reset
                </a>
            @endif
        </form>

        <!-- Add Button (#00719C Blue with White Icon) -->
        <button onclick="openAddCardModal()"
            class="px-5 py-2.5 bg-[#00719C] hover:bg-[#155ce0] text-white text-xs font-bold rounded-2xl shadow-lg shadow-[#00719C]/30 transition flex items-center justify-center gap-2 cursor-pointer">
            <i class="fa-solid fa-plus text-white"></i>
            <span>Daftarkan Kartu Baru</span>
        </button>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
            <thead>
                <tr class="border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500 bg-[#f8fafc]">
                    <th class="py-3 px-4 rounded-l-2xl">UID Kartu</th>
                    <th class="py-3 px-4">Nama Pemilik</th>
                    <th class="py-3 px-4">Saldo Kartu</th>
                    <th class="py-3 px-4">Kontak / Email</th>
                    <th class="py-3 px-4">Status Kartu</th>
                    <th class="py-3 px-4">Tanggal Daftar</th>
                    <th class="py-3 px-4 rounded-r-2xl text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium">
                @if ($cards->isEmpty())
                    <tr>
                        <td colspan="7" class="py-10 text-center text-slate-400 text-xs">
                            <i class="fa-solid fa-id-card text-3xl mb-2 text-slate-300 block"></i>
                            Tidak ada data kartu RFID yang sesuai kriteria pencarian.
                        </td>
                    </tr>
                @else
                    @foreach ($cards as $c)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3 px-4">
                                <span class="font-mono font-bold text-slate-900 bg-slate-100 px-2 py-0.5 rounded border border-slate-300 text-[11px]">
                                    {{ $c->uid }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-slate-900 font-bold">
                                {{ $c->user->name ?? 'Pengendara' }}
                            </td>
                            <td class="py-3 px-4 font-mono font-bold text-black text-xs">
                                <span class="text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                    Rp {{ number_format((int)($c->balance ?? 0), 0, ',', '.') }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-slate-600">
                                <div>{{ $c->user->email ?? '-' }}</div>
                                <div class="text-[10px] text-slate-400 font-semibold">{{ $c->user->phone ?? '-' }}</div>
                            </td>
                            <td class="py-3 px-4">
                                @if ($c->status === 'active')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-[#ebf2fe] text-[#00719C] border border-[#00719C]/30">
                                        <i class="fa-solid fa-circle-check mr-1 text-[#00719C]"></i> AKTIF
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-50 text-rose-600 border border-rose-200">
                                        <i class="fa-solid fa-ban mr-1"></i> DIBLOKIR
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-slate-500 font-mono text-[11px]">
                                {{ $c->created_at ? $c->created_at->format('d M Y H:i') : '-' }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <!-- Top Up Saldo Button -->
                                    <button onclick='openAdminTopupModal({!! json_encode([
                                        "id" => $c->id,
                                        "uid" => $c->uid,
                                        "user_name" => $c->user->name ?? "Pengendara",
                                        "balance" => (int) $c->balance
                                    ]) !!})'
                                        class="p-2 bg-amber-50 hover:bg-amber-100 text-amber-700 rounded-xl transition cursor-pointer" title="Isi Saldo Kartu">
                                        <i class="fa-solid fa-wallet"></i>
                                    </button>

                                    <!-- Toggle Status Form -->
                                    <form method="POST" action="{{ route('admin.cards.toggle-status') }}" onsubmit="return confirm('Apakah Anda yakin ingin mengubah status kartu ini?')">
                                        @csrf
                                        <input type="hidden" name="card_id" value="{{ $c->id }}">
                                        <input type="hidden" name="new_status" value="{{ $c->status === 'active' ? 'blocked' : 'active' }}">
                                        @if ($c->status === 'active')
                                            <button type="submit" class="p-2 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-xl transition cursor-pointer border-none" title="Blokir Kartu">
                                                <i class="fa-solid fa-ban"></i>
                                            </button>
                                        @else
                                            <button type="submit" class="p-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 rounded-xl transition cursor-pointer border-none" title="Aktifkan Kartu">
                                                <i class="fa-solid fa-unlock"></i>
                                            </button>
                                        @endif
                                    </form>

                                    <!-- Edit Button -->
                                    <button onclick='openEditCardModal({!! json_encode([
                                        "id" => $c->id,
                                        "uid" => $c->uid,
                                        "user_name" => $c->user->name ?? "",
                                        "email" => $c->user->email ?? "",
                                        "phone" => $c->user->phone ?? "",
                                        "status" => $c->status,
                                        "balance" => (int) $c->balance
                                    ]) !!})'
                                        class="p-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition cursor-pointer" title="Edit Data & Saldo">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>

                                    <!-- Delete Form -->
                                    <form method="POST" action="{{ route('admin.cards.destroy', $c->id) }}" onsubmit="return confirm('Hapus kartu ini secara permanen dari sistem?')">
                                        @csrf
                                        <button type="submit" class="p-2 bg-slate-100 hover:bg-rose-50 text-slate-400 hover:text-rose-600 rounded-xl transition cursor-pointer border-none" title="Hapus Kartu">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL PENDAFTARAN KARTU BARU (ALUR: TAP DULU BARU MUNCUL FORM) -->
<div id="add-card-modal" class="fixed inset-0 bg-black/30 z-50 flex items-center justify-center p-4 hidden">
    <div class="modal-card max-w-lg w-full p-6">
        <div class="flex items-center justify-between pb-3.5 clean-border-b mb-4">
            <h3 class="text-sm font-bold text-black uppercase tracking-wide flex items-center gap-2">
                <span>Pendaftaran Kartu RFID Baru</span>
            </h3>
            <button onclick="closeAddCardModal()" class="w-7 h-7 flex items-center justify-center rounded text-black/50 hover:text-black hover:bg-black/5 transition text-sm">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>

        <!-- TAHAP 1: STANDBY MENUNGGU TAP KARTU PADA SCANNER / ESP8266 -->
        <div id="tap-waiting-section" class="py-6 px-4 text-center rounded-md bg-[#FFFFFF] clean-border my-2">
            <div class="w-12 h-12 mx-auto rounded-md bg-[#00719C] text-white flex items-center justify-center mb-3">
                <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12.55a11 11 0 0 1 14.08 0"></path>
                    <path d="M1.42 9a16 16 0 0 1 21.16 0"></path>
                    <path d="M8.53 16.11a6 6 0 0 1 6.95 0"></path>
                    <line x1="12" y1="20" x2="12.01" y2="20"></line>
                </svg>
            </div>
            <h4 class="text-sm font-extrabold text-[#091530]">Silakan Tempelkan Kartu RFID</h4>
            <p class="text-xs text-slate-500 mt-1 max-w-xs mx-auto">
                Dekatkan kartu ke sensor RFID reader (ESP8266). Sistem akan mendeteksi UID secara otomatis.
            </p>

            <div class="mt-4 flex items-center justify-center gap-2 text-[11px] font-bold text-[#00719C]">
                <svg class="w-4 h-4 animate-spin text-[#00719C]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle><path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path></svg>
                <span id="scan-polling-status">Menunggu tap dari sensor...</span>
            </div>
        </div>

        <!-- TAHAP 2: FORM INPUT (MUNCUL OTOMATIS SETELAH KARTU DI-TAP) -->
        <form id="add-card-form" method="POST" action="{{ route('admin.cards.store') }}" class="space-y-4 hidden">
            @csrf

            <!-- Banner Kartu Berhasil Terdeteksi -->
            <div class="p-3.5 bg-emerald-50 border border-emerald-300 rounded-2xl flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold text-xs">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-bold text-emerald-800">Kartu Berhasil Dideteksi!</div>
                        <div class="font-mono text-xs font-extrabold text-emerald-950">UID: <span id="display-detected-uid">-</span></div>
                    </div>
                </div>
                <button type="button" onclick="resetToWaitingTap()" class="text-[11px] font-bold text-[#00719C] hover:underline">
                    Ganti Kartu
                </button>
            </div>

            <!-- Input UID Tersembunyi tapi bernilai dari tap -->
            <input type="hidden" id="add-uid" name="uid" required value="">

            <!-- Nama Pengendara / Label Kartu ini -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">
                    Nama Pemilik / Label Kartu Ini <span class="text-rose-500">*</span>
                </label>
                <input type="text" id="new-user-name" name="name" required placeholder="Contoh: Budi Santoso atau Kendaraan Operasional"
                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-2xl text-slate-900 text-xs font-bold focus:border-[#00719C] focus:outline-none">
                <p class="text-[11px] text-slate-400 mt-1">Nama ini akan ditampilkan saat kendaraan melakukan tap masuk/keluar.</p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Email (Untuk Login Client)</label>
                    <input type="email" id="new-user-email" name="email" placeholder="budi@gmail.com"
                        class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-2xl text-slate-900 text-xs focus:border-[#00719C] focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">No. WhatsApp / HP</label>
                    <input type="number" name="phone" placeholder="081234567890"
                        class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-2xl text-slate-900 text-xs focus:border-[#00719C] focus:outline-none">
                </div>
            </div>

            <!-- Password Pengendara yang Diatur oleh Admin -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">
                    Password Akun Pengendara (Client) <span class="text-rose-500">* (Wajib Diisi)</span>
                </label>
                <div class="relative">
                    <input type="text" id="new-user-password" name="password" required minlength="4" value="" placeholder="Ketik kata sandi (min. 4 karakter)..."
                        class="w-full pl-4 pr-24 py-2.5 bg-slate-50 border border-slate-200 rounded-2xl text-slate-900 text-xs font-mono font-bold focus:border-[#00719C] focus:outline-none">
                    <button type="button" onclick="generateRandomPassword()" 
                        class="absolute right-2 top-1/2 -translate-y-1/2 px-2 py-1 bg-white hover:bg-slate-100 border border-slate-200 rounded-xl text-[10px] font-bold text-[#00719C] transition">
                        Acak Sandi
                    </button>
                </div>
                <p class="text-[11px] text-slate-500 mt-1">Kata sandi tidak boleh kosong. Tekan tombol <b class="text-[#00719C]">Acak Sandi</b> untuk mengisi otomatis atau buat sandi baru.</p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Status Kartu</label>
                    <select name="status" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-2xl text-slate-800 text-xs font-semibold focus:border-[#00719C] focus:outline-none">
                        <option value="active">AKTIF (Dapat Akses Masuk &amp; Keluar)</option>
                        <option value="blocked">DIBLOKIR (Akses Ditolak)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Saldo Awal Kartu (Rp)</label>
                    <input type="number" name="balance" value="50000" min="0" step="5000"
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-2xl text-slate-900 text-xs font-mono font-bold focus:border-[#00719C] focus:outline-none">
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="button" onclick="closeAddCardModal()"
                    class="flex-1 py-3 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-2xl transition">
                    Batal
                </button>
                <button type="submit"
                    class="flex-1 py-3 px-4 bg-[#00719C] hover:bg-[#155ce0] text-white text-xs font-bold rounded-2xl shadow-lg shadow-[#00719C]/30 transition flex items-center justify-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                    <span>Simpan Kartu Baru</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDIT KARTU (White Card) -->
<div id="edit-card-modal" class="fixed inset-0 bg-black/30 z-50 flex items-center justify-center p-4 hidden">
    <div class="modal-card max-w-md w-full p-6">
        <div class="flex items-center justify-between pb-3.5 clean-border-b mb-5">
            <h3 class="text-sm font-bold text-black uppercase tracking-wide flex items-center gap-2">
                <span>Edit Data Pemilik Kartu</span>
            </h3>
            <button onclick="closeEditCardModal()" class="w-7 h-7 flex items-center justify-center rounded text-black/50 hover:text-black hover:bg-black/5 transition text-sm">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="edit-card-form" method="POST" action="" class="space-y-4">
            @csrf
            <input type="hidden" id="edit-card-id" name="card_id" value="">

            <div>
                <label class="block text-[11px] font-semibold text-black uppercase tracking-wider mb-1">
                    UID Kartu RFID <span class="text-[10px] text-black/40 font-normal lowercase">(bawaan kartu - permanen)</span>
                </label>
                <div class="relative">
                    <input type="text" id="edit-uid" readonly
                        class="w-full pl-4 pr-24 py-2.5 bg-black/5 clean-border rounded-md text-black/70 font-mono font-bold text-xs uppercase cursor-not-allowed select-none">
                    <div class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] bg-black/10 text-black font-bold px-2 py-0.5 rounded flex items-center gap-1">
                        <i class="fa-solid fa-lock text-[9px]"></i> Kunci Fisik
                    </div>
                </div>
                <p class="text-[10px] text-black/50 mt-1">UID merupakan identitas unik fisik kartu RFID dan tidak dapat diubah.</p>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-black uppercase tracking-wider mb-1">
                    Nama Pemilik / Pengendara <span class="text-rose-500">*</span>
                </label>
                <input type="text" id="edit-name" name="name" required placeholder="Nama lengkap pemilik kartu"
                    class="input-clean w-full text-xs font-semibold text-black">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-semibold text-black uppercase tracking-wider mb-1">Email</label>
                    <input type="email" id="edit-email" name="email" placeholder="email@example.com"
                        class="input-clean w-full text-xs text-black">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-black uppercase tracking-wider mb-1">No. Telp / HP</label>
                    <input type="number" id="edit-phone" name="phone" placeholder="081234567890"
                        class="input-clean w-full text-xs text-black">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-semibold text-black uppercase tracking-wider mb-1">Status Kartu</label>
                    <select id="edit-status" name="status" class="input-clean w-full text-xs font-semibold text-black">
                        <option value="active">AKTIF</option>
                        <option value="blocked">DIBLOKIR</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-black uppercase tracking-wider mb-1">Saldo Kartu (Rp)</label>
                    <input type="number" id="edit-balance" name="balance" min="0" step="1000"
                        class="input-clean w-full text-xs font-mono font-bold text-black">
                </div>
            </div>

            <!-- Ganti Password Pengendara oleh Admin -->
            <div>
                <label class="block text-[11px] font-semibold text-black uppercase tracking-wider mb-1">
                    Ubah Password Akun Pengendara
                </label>
                <input type="text" id="edit-password" name="password" placeholder="Kosongkan jika tidak ingin mengubah password"
                    class="input-clean w-full text-xs font-mono text-black">
                <p class="text-[10px] text-black/50 mt-1">Isi hanya jika admin ingin mereset password akun login pengendara.</p>
            </div>

            <div class="flex items-center gap-3 pt-3">
                <button type="button" onclick="closeEditCardModal()"
                    class="flex-1 py-2.5 px-4 bg-[#FFFFFF] hover:bg-black/5 text-black text-xs font-semibold rounded-md clean-border transition cursor-pointer">
                    Batal
                </button>
                <button type="submit"
                    class="flex-1 py-2.5 px-4 btn-primary text-xs flex items-center justify-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-check text-white"></i>
                    <span>Simpan Perubahan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL TOP UP SALDO DARI ADMIN -->
<div id="admin-topup-modal" class="fixed inset-0 bg-black/30 z-50 flex items-center justify-center p-4 hidden">
    <div class="modal-card max-w-sm w-full p-6">
        <div class="flex items-center justify-between pb-3.5 clean-border-b mb-4">
            <h3 class="text-sm font-bold text-black uppercase tracking-wide flex items-center gap-2">
                <i class="fa-solid fa-wallet text-[#00719C]"></i>
                <span>Isi Saldo Kartu RFID</span>
            </h3>
            <button onclick="closeAdminTopupModal()" class="w-7 h-7 flex items-center justify-center rounded text-black/50 hover:text-black">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="admin-topup-form" method="POST" action="" class="space-y-4">
            @csrf
            <input type="hidden" id="topup-card-id" name="card_id" value="">

            <div class="p-3 rounded-md bg-slate-50 clean-border space-y-1">
                <div class="flex justify-between text-xs">
                    <span class="text-black/60 font-medium">Pemilik:</span>
                    <strong id="topup-user-name" class="text-black">-</strong>
                </div>
                <div class="flex justify-between text-xs">
                    <span class="text-black/60 font-medium">UID Kartu:</span>
                    <strong id="topup-card-uid" class="font-mono text-black">-</strong>
                </div>
                <div class="flex justify-between text-xs pt-1 clean-border-t">
                    <span class="text-black/60 font-medium">Saldo Sekarang:</span>
                    <strong id="topup-current-bal" class="font-mono text-emerald-700">-</strong>
                </div>
            </div>

            <!-- Tombol Nominal Cepat -->
            <div class="space-y-1.5">
                <label class="block text-[11px] font-semibold text-black uppercase tracking-wider">Pilih Nominal Cepat</label>
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" onclick="setAdminTopup(10000)" class="py-2 px-3 text-xs font-bold rounded clean-border hover:bg-[#00719C] hover:text-white transition cursor-pointer">+Rp 10.000</button>
                    <button type="button" onclick="setAdminTopup(20000)" class="py-2 px-3 text-xs font-bold rounded clean-border hover:bg-[#00719C] hover:text-white transition cursor-pointer">+Rp 20.000</button>
                    <button type="button" onclick="setAdminTopup(50000)" class="py-2 px-3 text-xs font-bold rounded clean-border hover:bg-[#00719C] hover:text-white transition cursor-pointer">+Rp 50.000</button>
                    <button type="button" onclick="setAdminTopup(100000)" class="py-2 px-3 text-xs font-bold rounded clean-border hover:bg-[#00719C] hover:text-white transition cursor-pointer">+Rp 100.000</button>
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-black uppercase tracking-wider mb-1">Nominal Top Up (Rp)</label>
                <input type="number" id="admin-topup-amount" name="amount" required min="1000" step="1000" placeholder="50000"
                    class="input-clean w-full text-xs font-mono font-bold text-black">
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="button" onclick="closeAdminTopupModal()"
                    class="flex-1 py-2 px-4 bg-[#FFFFFF] hover:bg-black/5 text-black text-xs font-semibold rounded-md clean-border transition cursor-pointer">
                    Batal
                </button>
                <button type="submit"
                    class="flex-1 py-2 px-4 btn-primary text-xs flex items-center justify-center gap-1.5 cursor-pointer">
                    <i class="fa-solid fa-plus text-white"></i>
                    <span>Tambah Saldo</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let tapPollInterval = null;
    let initialLastUid = null;

    function openAddCardModal() {
        resetToWaitingTap();
        document.getElementById('add-card-modal').classList.remove('hidden');
        startTapPolling();
    }

    function closeAddCardModal() {
        stopTapPolling();
        document.getElementById('add-card-modal').classList.add('hidden');
    }

    function resetToWaitingTap() {
        document.getElementById('tap-waiting-section').classList.remove('hidden');
        document.getElementById('add-card-form').classList.add('hidden');
        document.getElementById('add-uid').value = '';
        document.getElementById('display-detected-uid').textContent = '-';
        document.getElementById('scan-polling-status').textContent = 'Menunggu tap dari sensor...';
        const pwdInput = document.getElementById('new-user-password');
        if (pwdInput) pwdInput.value = '';
    }

    function startTapPolling() {
        stopTapPolling();

        fetch('{{ route('admin.cards.latest-scan') }}?action=poll')
            .then(res => res.json())
            .then(d => {
                if (d.has_tap) {
                    initialLastUid = d.uid + '_' + d.timestamp;
                }
            })
            .catch(() => {});

        tapPollInterval = setInterval(async () => {
            try {
                const res = await fetch('{{ route('admin.cards.latest-scan') }}?action=poll');
                const data = await res.json();
                if (data.status === 'success' && data.has_tap) {
                    const currentSig = data.uid + '_' + data.timestamp;
                    if (currentSig !== initialLastUid) {
                        stopTapPolling();
                        handleCardDetected(data.uid, data.is_registered, data.existing_name);
                    }
                }
            } catch (e) {
                console.error('Polling tap error:', e);
            }
        }, 1200);
    }

    function stopTapPolling() {
        if (tapPollInterval) {
            clearInterval(tapPollInterval);
            tapPollInterval = null;
        }
    }

    function handleCardDetected(uid, isRegistered = false, existingName = '') {
        playWebBuzzer(2, 'high');

        document.getElementById('add-uid').value = uid;
        document.getElementById('display-detected-uid').textContent = uid;

        const nameInput = document.getElementById('new-user-name');
        if (existingName) {
            nameInput.value = existingName;
        } else if (!nameInput.value) {
            nameInput.placeholder = 'Pengendara (' + uid.slice(-4) + ')';
        }

        const pwdInput = document.getElementById('new-user-password');
        if (pwdInput && !pwdInput.value) {
            generateRandomPassword();
        }

        document.getElementById('tap-waiting-section').classList.add('hidden');
        document.getElementById('add-card-form').classList.remove('hidden');

        setTimeout(() => {
            nameInput.focus();
        }, 150);

        if (isRegistered) {
            showToast('Peringatan', `Kartu UID ${uid} sudah pernah terdaftar atas nama ${existingName || 'pengguna lain'}.`, 'warning');
        } else {
            showToast('Kartu Terdeteksi', `UID: ${uid}. Silakan lengkapi formulir pendaftaran.`, 'success');
        }
    }

    function generateRandomPassword() {
        const chars = 'abcdefghjkmnpqrstuvwxyz23456789';
        let pwd = '';
        for (let i = 0; i < 6; i++) {
            pwd += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        const pwdInput = document.getElementById('new-user-password');
        if (pwdInput) {
            pwdInput.value = pwd;
            pwdInput.focus();
        }
    }

    function openEditCardModal(card) {
        document.getElementById('edit-card-id').value = card.id;
        document.getElementById('edit-card-form').action = '{{ url('/admin/cards') }}/' + card.id;
        document.getElementById('edit-uid').value = card.uid;
        document.getElementById('edit-name').value = card.user_name || '';
        document.getElementById('edit-email').value = card.email || '';
        document.getElementById('edit-phone').value = card.phone || '';
        document.getElementById('edit-status').value = card.status;
        document.getElementById('edit-balance').value = card.balance || 0;
        const editPwd = document.getElementById('edit-password');
        if (editPwd) editPwd.value = '';
        document.getElementById('edit-card-modal').classList.remove('hidden');
    }

    function closeEditCardModal() {
        document.getElementById('edit-card-modal').classList.add('hidden');
    }

    // Modal Admin Top Up Logic
    function openAdminTopupModal(card) {
        document.getElementById('topup-card-id').value = card.id;
        document.getElementById('admin-topup-form').action = '{{ url('/admin/cards') }}/' + card.id + '/topup';
        document.getElementById('topup-user-name').textContent = card.user_name || '-';
        document.getElementById('topup-card-uid').textContent = card.uid;
        document.getElementById('topup-current-bal').textContent = 'Rp ' + parseInt(card.balance || 0).toLocaleString('id-ID');
        document.getElementById('admin-topup-amount').value = '20000';
        document.getElementById('admin-topup-modal').classList.remove('hidden');
    }

    function closeAdminTopupModal() {
        document.getElementById('admin-topup-modal').classList.add('hidden');
    }

    function setAdminTopup(amount) {
        document.getElementById('admin-topup-amount').value = amount;
    }
</script>
@endpush
