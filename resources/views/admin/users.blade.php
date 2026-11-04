@extends('layouts.admin')

@section('title', 'Kelola Admin & Owner')
@section('header_title', 'Kelola Akun Admin & Owner')
@section('header_subtitle', 'Pusat manajemen hak akses, pembuatan akun administrator & pemilik usaha (owner)')

@section('content')
<!-- ALERT NOTIFIKASI -->
@if (session('success'))
    <div class="p-4 rounded-xl border text-sm flex items-center justify-between shadow-sm bg-emerald-50 border-emerald-300 text-emerald-800 mb-6">
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
    <div class="p-4 rounded-xl border text-sm flex items-center justify-between shadow-sm bg-rose-50 border-rose-300 text-rose-800 mb-6">
        <div class="flex items-center gap-3">
            <i class="fa-solid fa-triangle-exclamation text-rose-600 text-lg"></i>
            <span class="font-bold">{{ session('error') }}</span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
@endif

@if (isset($errors) && $errors->any())
    <div class="p-4 rounded-xl border text-sm bg-rose-50 border-rose-300 text-rose-800 mb-6 shadow-sm">
        <div class="flex items-center gap-2 font-bold mb-1">
            <i class="fa-solid fa-circle-exclamation text-rose-600"></i>
            <span>Terdapat kesalahan pengisian formulir:</span>
        </div>
        <ul class="list-disc list-inside text-xs space-y-0.5 ml-4">
            @foreach ($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

<!-- METRIK KPI RINGKAS (Clean Editorial Card) -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <!-- Card 1: Total Admin -->
    <div class="bg-white clean-border rounded-xl p-5 shadow-sm flex flex-col justify-between">
        <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold uppercase tracking-wider text-black/60">Administrator</span>
            <div class="w-9 h-9 rounded-lg bg-[#00719C] text-white flex items-center justify-center text-sm shadow-sm">
                <i class="fa-solid fa-user-shield"></i>
            </div>
        </div>
        <div class="mt-4">
            <div class="text-3xl font-black text-black font-mono">{{ $adminCount }} <span class="text-xs font-semibold text-black/50">Akun</span></div>
            <div class="text-[11px] text-black/50 font-medium mt-1">Akses penuh operasional gerbang &amp; sistem</div>
        </div>
    </div>

    <!-- Card 2: Total Owner -->
    <div class="bg-white clean-border rounded-xl p-5 shadow-sm flex flex-col justify-between">
        <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold uppercase tracking-wider text-black/60">Pemilik Usaha (Owner)</span>
            <div class="w-9 h-9 rounded-lg bg-[#FFD51E] text-black flex items-center justify-center text-sm shadow-sm font-bold">
                <i class="fa-solid fa-crown"></i>
            </div>
        </div>
        <div class="mt-4">
            <div class="text-3xl font-black text-black font-mono">{{ $ownerCount }} <span class="text-xs font-semibold text-black/50">Akun</span></div>
            <div class="text-[11px] text-black/50 font-medium mt-1">Akses eksekutif laporan &amp; pendapatan</div>
        </div>
    </div>

    <!-- Card 3: Total Pengelola -->
    <div class="bg-white clean-border rounded-xl p-5 shadow-sm flex flex-col justify-between">
        <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold uppercase tracking-wider text-black/60">Total Pengelola Aktif</span>
            <div class="w-9 h-9 rounded-lg bg-slate-900 text-white flex items-center justify-center text-sm shadow-sm">
                <i class="fa-solid fa-users-gear"></i>
            </div>
        </div>
        <div class="mt-4">
            <div class="text-3xl font-black text-[#00719C] font-mono">{{ $totalStaff }} <span class="text-xs font-semibold text-black/50">Personil</span></div>
            <div class="text-[11px] text-emerald-700 font-semibold mt-1 flex items-center gap-1">
                <i class="fa-solid fa-circle-check text-[10px]"></i>
                <span>Tervalidasi &amp; terenkripsi Bcrypt</span>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================================
     TOOLBAR: PENCARIAN, FILTER ROLE, & TOMBOL TAMBAH AKUN
     ========================================================================= -->
<div class="bg-white clean-border rounded-xl p-4 shadow-sm mb-6">
    <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
        <!-- Form Pencarian & Filter Role -->
        <form action="{{ route('admin.users.index') }}" method="GET" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 flex-1">
            <!-- Search Input -->
            <div class="search-wrapper-box flex-1 min-w-[200px]">
                <span class="search-icon">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" 
                       name="search" 
                       value="{{ $search }}" 
                       placeholder="Cari nama, email, atau nomor telepon...">
                @if (!empty($search))
                    <a href="{{ route('admin.users.index', ['role' => $roleFilter]) }}" class="search-clear-btn" title="Hapus pencarian">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </a>
                @endif
            </div>

            <!-- Role Filter Pills -->
            <div class="inline-flex rounded-lg p-1 bg-slate-100 clean-border shrink-0 text-xs font-bold">
                <a href="{{ route('admin.users.index', ['role' => 'all', 'search' => $search]) }}" 
                   class="px-3 py-1.5 rounded-md transition {{ $roleFilter === 'all' ? 'bg-[#00719C] text-white shadow-sm' : 'text-black/60 hover:text-black' }}">
                    Semua
                </a>
                <a href="{{ route('admin.users.index', ['role' => 'admin', 'search' => $search]) }}" 
                   class="px-3 py-1.5 rounded-md transition {{ $roleFilter === 'admin' ? 'bg-[#00719C] text-white shadow-sm' : 'text-black/60 hover:text-black' }}">
                    Admin
                </a>
                <a href="{{ route('admin.users.index', ['role' => 'owner', 'search' => $search]) }}" 
                   class="px-3 py-1.5 rounded-md transition {{ $roleFilter === 'owner' ? 'bg-[#00719C] text-white shadow-sm' : 'text-black/60 hover:text-black' }}">
                    Owner
                </a>
            </div>

            <button type="submit" class="px-3 py-1.5 bg-[#00719C] text-white text-xs font-bold rounded-lg hover:bg-[#005c80] transition shadow-sm cursor-pointer border-none flex items-center justify-center gap-1.5">
                <i class="fa-solid fa-filter text-[10px]"></i>
                <span>Terapkan</span>
            </button>
        </form>

        <!-- Tombol Tambah Akun Modal -->
        <button type="button" 
                onclick="openCreateModal()" 
                class="px-4 py-2 bg-[#FFD51E] hover:bg-[#e6be12] text-black text-xs font-extrabold rounded-lg transition flex items-center justify-center gap-2 shadow-sm cursor-pointer border-none shrink-0">
            <i class="fa-solid fa-user-plus text-sm"></i>
            <span>Tambah Akun Baru</span>
        </button>
    </div>
</div>

<!-- =========================================================================
     TABEL PENGELOLA: ADMIN & OWNER
     ========================================================================= -->
<div class="bg-white clean-border rounded-xl shadow-sm overflow-hidden">
    <div class="p-4 clean-border-b flex items-center justify-between">
        <div>
            <h3 class="text-sm font-extrabold text-black uppercase tracking-wide flex items-center gap-2">
                <i class="fa-solid fa-users-gear text-[#00719C]"></i>
                <span>Daftar Pengelola Terdaftar</span>
                <span class="text-xs font-semibold px-2 py-0.5 rounded bg-slate-100 text-black/70">
                    {{ $users->count() }} Akun
                </span>
            </h3>
            <p class="text-xs text-black/50">Hanya menampilkan role Administrator dan Owner (User Client terkelola di menu RFID).</p>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
            <thead>
                <tr class="clean-border-b bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-black/70">
                    <th class="py-3 px-3 text-center w-12">No</th>
                    <th class="py-3 px-3">Nama Pengelola</th>
                    <th class="py-3 px-3">Email Login</th>
                    <th class="py-3 px-3">Nomor Telepon</th>
                    <th class="py-3 px-3 text-center">Peran (Role)</th>
                    <th class="py-3 px-3">Waktu Dibuat</th>
                    <th class="py-3 px-3 text-center w-32">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-black/10 font-medium text-black">
                @if ($users->isEmpty())
                    <tr>
                        <td colspan="7" class="py-12 text-center text-black/50">
                            <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3 text-slate-400 text-lg">
                                <i class="fa-solid fa-user-xmark"></i>
                            </div>
                            <p class="font-bold text-sm text-black">Tidak ada akun admin / owner yang sesuai</p>
                            <p class="text-xs text-black/50 mt-1">Coba sesuaikan kata kunci pencarian atau reset filter role.</p>
                            <a href="{{ route('admin.users.index') }}" class="inline-block mt-3 px-3 py-1 bg-[#00719C] text-white text-xs font-bold rounded-md hover:bg-[#005c80] transition">
                                Reset Pencarian
                            </a>
                        </td>
                    </tr>
                @else
                    @php $rowNo = 1; @endphp
                    @foreach ($users as $u)
                        @php
                            $isCurrentUser = ($u->id === auth()->id());
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition {{ $isCurrentUser ? 'bg-sky-50/40' : '' }}">
                            <td class="py-3 px-3 text-center text-black/40 font-mono text-[10px]">{{ $rowNo++ }}</td>
                            <td class="py-3 px-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold {{ $u->role === 'admin' ? 'bg-[#00719C] text-white' : 'bg-[#FFD51E] text-black' }}">
                                        {{ strtoupper(substr($u->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="font-extrabold text-black text-xs flex items-center gap-1.5">
                                            <span>{{ $u->name }}</span>
                                            @if ($isCurrentUser)
                                                <span class="text-[9px] px-1.5 py-0.2 rounded bg-sky-100 text-[#00719C] font-black border border-sky-300">
                                                    Anda
                                                </span>
                                            @endif
                                        </div>
                                        <span class="text-[10px] text-black/40 font-mono">ID: #{{ $u->id }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-3 font-mono text-xs text-black font-semibold">
                                {{ $u->email }}
                            </td>
                            <td class="py-3 px-3 text-xs text-black">
                                {{ $u->phone ?? '-' }}
                            </td>
                            <td class="py-3 px-3 text-center">
                                @if ($u->role === 'admin')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] font-extrabold bg-[#00719C]/10 text-[#00719C] border border-[#00719C]/30">
                                        <i class="fa-solid fa-user-shield text-[10px]"></i>
                                        <span>ADMINISTRATOR</span>
                                    </span>
                                @elseif ($u->role === 'owner')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] font-extrabold bg-[#FFD51E]/20 text-amber-900 border border-[#FFD51E]">
                                        <i class="fa-solid fa-crown text-[10px] text-amber-600"></i>
                                        <span>OWNER</span>
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-[11px] text-black/60 font-mono">
                                {{ $u->created_at ? $u->created_at->format('d/m/Y H:i') : '-' }}
                            </td>
                            <td class="py-3 px-3 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <!-- Tombol Edit -->
                                    <button type="button" 
                                            onclick="openEditModal({{ json_encode($u) }})"
                                            title="Edit akun"
                                            class="p-1.5 rounded-lg bg-slate-100 hover:bg-[#00719C] text-black hover:text-white transition cursor-pointer border-none text-xs">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>

                                    <!-- Tombol Hapus -->
                                    @if ($isCurrentUser)
                                        <button type="button" 
                                                disabled 
                                                title="Tidak dapat menghapus akun Anda sendiri"
                                                class="p-1.5 rounded-lg bg-slate-100 text-slate-300 cursor-not-allowed border-none text-xs">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    @else
                                        <button type="button" 
                                                onclick="confirmDelete({{ $u->id }}, '{{ addslashes($u->name) }}', '{{ strtoupper($u->role) }}')"
                                                title="Hapus akun"
                                                class="p-1.5 rounded-lg bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white transition cursor-pointer border-none text-xs">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
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

<!-- =========================================================================
     MODAL TAMBAH AKUN BARU (ADMIN / OWNER)
     ========================================================================= -->
<div id="createModal" class="fixed inset-0 z-50 hidden bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl clean-border shadow-2xl w-full max-w-lg overflow-hidden animate-fadeIn">
        <div class="px-6 py-4 bg-slate-50 clean-border-b flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-[#FFD51E] text-black flex items-center justify-center text-sm font-bold">
                    <i class="fa-solid fa-user-plus"></i>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-black">Tambah Akun Pengelola Baru</h3>
                    <p class="text-[11px] text-black/50">Buat akun untuk Administrator atau Pemilik Usaha</p>
                </div>
            </div>
            <button onclick="closeCreateModal()" class="text-black/40 hover:text-black text-lg p-1">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="{{ route('admin.users.store') }}" method="POST" class="p-6 space-y-4">
            @csrf

            <!-- Nama Lengkap -->
            <div>
                <label for="create_name" class="block text-xs font-bold text-black uppercase tracking-wider mb-1">Nama Lengkap <span class="text-rose-500">*</span></label>
                <input type="text" 
                       id="create_name" 
                       name="name" 
                       required 
                       placeholder="Contoh: Budi Santoso"
                       class="w-full text-xs font-semibold px-3 py-2.5 rounded-lg clean-border bg-slate-50 focus:bg-white focus:outline-none focus:border-[#00719C]">
            </div>

            <!-- Email Login -->
            <div>
                <label for="create_email" class="block text-xs font-bold text-black uppercase tracking-wider mb-1">Alamat Email Login <span class="text-rose-500">*</span></label>
                <input type="email" 
                       id="create_email" 
                       name="email" 
                       required 
                       placeholder="Contoh: budi@eparkir.local"
                       class="w-full text-xs font-mono font-semibold px-3 py-2.5 rounded-lg clean-border bg-slate-50 focus:bg-white focus:outline-none focus:border-[#00719C]">
            </div>

            <!-- Nomor Telepon -->
            <div>
                <label for="create_phone" class="block text-xs font-bold text-black uppercase tracking-wider mb-1">Nomor Telepon / WhatsApp</label>
                <input type="text" 
                       id="create_phone" 
                       name="phone" 
                       placeholder="Contoh: 081234567890"
                       class="w-full text-xs font-mono font-semibold px-3 py-2.5 rounded-lg clean-border bg-slate-50 focus:bg-white focus:outline-none focus:border-[#00719C]">
            </div>

            <!-- Password -->
            <div>
                <label for="create_password" class="block text-xs font-bold text-black uppercase tracking-wider mb-1">Password Baru <span class="text-rose-500">*</span></label>
                <div class="relative">
                    <input type="password" 
                           id="create_password" 
                           name="password" 
                           required 
                           minlength="6"
                           placeholder="Minimal 6 karakter"
                           class="w-full text-xs font-mono font-semibold px-3 py-2.5 rounded-lg clean-border bg-slate-50 focus:bg-white focus:outline-none focus:border-[#00719C] pr-10">
                    <button type="button" 
                            onclick="togglePasswordVisibility('create_password', this)"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-black/40 hover:text-black border-none bg-transparent cursor-pointer p-1">
                        <i class="fa-solid fa-eye text-xs"></i>
                    </button>
                </div>
                <p class="text-[10px] text-emerald-700 font-medium mt-1">
                    <i class="fa-solid fa-shield-halved"></i> Password akan di-hash otomatis dengan algoritma Bcrypt yang aman.
                </p>
            </div>

            <!-- Role Selection (Dropdown Select di Bagian Bawah) -->
            <div>
                <label for="create_role" class="block text-xs font-bold text-black uppercase tracking-wider mb-1">Pilih Peran Akun (Role) <span class="text-rose-500">*</span></label>
                <select id="create_role" 
                        name="role" 
                        required 
                        class="w-full text-xs font-bold px-3 py-2.5 rounded-lg clean-border bg-slate-50 focus:bg-white focus:outline-none focus:border-[#00719C] text-black cursor-pointer">
                    <option value="admin">Administrator (Akses Penuh Operasional &amp; Sistem)</option>
                    <option value="owner">Owner (Akses Eksekutif Laporan &amp; Finansial)</option>
                </select>
                <p class="text-[10px] text-black/50 mt-1">Pilih hak akses operasional untuk akun ini.</p>
            </div>

            <!-- Action Buttons -->
            <div class="pt-3 clean-border-t flex items-center justify-end gap-2">
                <button type="button" 
                        onclick="closeCreateModal()" 
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-black text-xs font-bold rounded-lg transition border-none cursor-pointer">
                    Batal
                </button>
                <button type="submit" 
                        class="px-5 py-2 bg-[#00719C] hover:bg-[#005c80] text-white text-xs font-extrabold rounded-lg transition shadow-sm border-none cursor-pointer flex items-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Simpan Akun</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- =========================================================================
     MODAL EDIT AKUN
     ========================================================================= -->
<div id="editModal" class="fixed inset-0 z-50 hidden bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl clean-border shadow-2xl w-full max-w-lg overflow-hidden animate-fadeIn">
        <div class="px-6 py-4 bg-slate-50 clean-border-b flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-[#00719C] text-white flex items-center justify-center text-sm font-bold">
                    <i class="fa-solid fa-user-pen"></i>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-black">Perbarui Data Akun</h3>
                    <p class="text-[11px] text-black/50" id="edit_modal_subtitle">Edit informasi administrator atau owner</p>
                </div>
            </div>
            <button onclick="closeEditModal()" class="text-black/40 hover:text-black text-lg p-1">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="editForm" method="POST" class="p-6 space-y-4">
            @csrf
            @method('PUT')

            <!-- Nama Lengkap -->
            <div>
                <label for="edit_name" class="block text-xs font-bold text-black uppercase tracking-wider mb-1">Nama Lengkap <span class="text-rose-500">*</span></label>
                <input type="text" 
                       id="edit_name" 
                       name="name" 
                       required 
                       class="w-full text-xs font-semibold px-3 py-2.5 rounded-lg clean-border bg-slate-50 focus:bg-white focus:outline-none focus:border-[#00719C]">
            </div>

            <!-- Email Login -->
            <div>
                <label for="edit_email" class="block text-xs font-bold text-black uppercase tracking-wider mb-1">Alamat Email Login <span class="text-rose-500">*</span></label>
                <input type="email" 
                       id="edit_email" 
                       name="email" 
                       required 
                       class="w-full text-xs font-mono font-semibold px-3 py-2.5 rounded-lg clean-border bg-slate-50 focus:bg-white focus:outline-none focus:border-[#00719C]">
            </div>

            <!-- Nomor Telepon -->
            <div>
                <label for="edit_phone" class="block text-xs font-bold text-black uppercase tracking-wider mb-1">Nomor Telepon / WhatsApp</label>
                <input type="text" 
                       id="edit_phone" 
                       name="phone" 
                       class="w-full text-xs font-mono font-semibold px-3 py-2.5 rounded-lg clean-border bg-slate-50 focus:bg-white focus:outline-none focus:border-[#00719C]">
            </div>

            <!-- Password Baru (Opsional) -->
            <div>
                <label for="edit_password" class="block text-xs font-bold text-black uppercase tracking-wider mb-1">Ganti Password (Opsional)</label>
                <div class="relative">
                    <input type="password" 
                           id="edit_password" 
                           name="password" 
                           minlength="6"
                           placeholder="Kosongkan jika tidak ingin mengubah password"
                           class="w-full text-xs font-mono font-semibold px-3 py-2.5 rounded-lg clean-border bg-slate-50 focus:bg-white focus:outline-none focus:border-[#00719C] pr-10">
                    <button type="button" 
                            onclick="togglePasswordVisibility('edit_password', this)"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-black/40 hover:text-black border-none bg-transparent cursor-pointer p-1">
                        <i class="fa-solid fa-eye text-xs"></i>
                    </button>
                </div>
                <p class="text-[10px] text-black/50 mt-1">Hanya isi bidang ini jika ingin mengatur ulang kata sandi akun.</p>
            </div>

            <!-- Role Selection (Dropdown Select di Bagian Bawah) -->
            <div>
                <label for="edit_role" class="block text-xs font-bold text-black uppercase tracking-wider mb-1">Peran Akun (Role) <span class="text-rose-500">*</span></label>
                <select id="edit_role" 
                        name="role" 
                        required 
                        class="w-full text-xs font-bold px-3 py-2.5 rounded-lg clean-border bg-slate-50 focus:bg-white focus:outline-none focus:border-[#00719C] text-black cursor-pointer">
                    <option value="admin">Administrator (Akses Penuh Operasional &amp; Sistem)</option>
                    <option value="owner">Owner (Akses Eksekutif Laporan &amp; Finansial)</option>
                </select>
                <p class="text-[10px] text-black/50 mt-1">Pilih peran dan hak akses akun ini.</p>
            </div>

            <!-- Action Buttons -->
            <div class="pt-3 clean-border-t flex items-center justify-end gap-2">
                <button type="button" 
                        onclick="closeEditModal()" 
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-black text-xs font-bold rounded-lg transition border-none cursor-pointer">
                    Batal
                </button>
                <button type="submit" 
                        class="px-5 py-2 bg-[#00719C] hover:bg-[#005c80] text-white text-xs font-extrabold rounded-lg transition shadow-sm border-none cursor-pointer flex items-center gap-2">
                    <i class="fa-solid fa-check"></i>
                    <span>Simpan Perubahan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- FORM TERSEMBUNYI UNTUK HAPUS AKUN -->
<form id="deleteForm" method="POST" class="hidden">
    @csrf
    @method('DELETE')
</form>

<script>
    // 1. Kontrol Modal Tambah
    function openCreateModal() {
        document.getElementById('createModal').classList.remove('hidden');
    }
    function closeCreateModal() {
        document.getElementById('createModal').classList.add('hidden');
    }

    // 2. Kontrol Modal Edit
    function openEditModal(user) {
        document.getElementById('editForm').action = '/admin/users/' + user.id;
        document.getElementById('edit_name').value = user.name || '';
        document.getElementById('edit_email').value = user.email || '';
        document.getElementById('edit_phone').value = user.phone || '';
        document.getElementById('edit_password').value = '';
        document.getElementById('edit_role').value = user.role || 'admin';

        document.getElementById('edit_modal_subtitle').innerText = 'Mengedit akun ' + user.name + ' (#' + user.id + ')';
        document.getElementById('editModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
    }

    // 3. Konfirmasi Hapus Akun
    function confirmDelete(userId, userName, roleName) {
        if (confirm('Apakah Anda yakin ingin menghapus akun ' + roleName + ' "' + userName + '"?\n\nTindakan ini tidak dapat dibatalkan.')) {
            const form = document.getElementById('deleteForm');
            form.action = '/admin/users/' + userId + '/delete';
            form.submit();
        }
    }

    // 4. Toggle Visibility Password
    function togglePasswordVisibility(inputId, btn) {
        const input = document.getElementById(inputId);
        const icon = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    // Tutup modal dengan tombol Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeCreateModal();
            closeEditModal();
        }
    });
</script>
@endsection
