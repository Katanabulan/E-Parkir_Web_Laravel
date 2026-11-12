@extends('layouts.admin')

@section('title', 'Manajemen Kartu RFID')
@section('header_title', 'Kelola Kartu RFID & Pengguna')
@section('header_subtitle', 'Pendaftaran kartu baru, penetapan plat nomor kendaraan, dan kontrol blokir kartu')

@section('content')
<div class="d-flex flex-column gap-4">

    <!-- ALERT NOTIFIKASI -->
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 rounded-3 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill fs-5"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 rounded-3 shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- STATS SUMMARY -->
    <div class="row g-3">
        <!-- Card 1: Total Kartu -->
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 d-flex flex-column justify-content-between">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-uppercase small fw-bold text-secondary">Total Kartu</span>
                    <span class="badge bg-primary p-2 rounded-3 text-white">
                        <i class="bi bi-credit-card-2-front-fill fs-6"></i>
                    </span>
                </div>
                <div class="my-3 d-flex align-items-baseline gap-2">
                    <h2 class="fw-bold text-dark mb-0 font-monospace">{{ $totalCards }}</h2>
                    <span class="text-secondary small">kartu</span>
                </div>
                <div class="pt-2 border-top text-secondary small">
                    Tersimpan dalam database sistem
                </div>
            </div>
        </div>

        <!-- Card 2: Kartu Aktif -->
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 d-flex flex-column justify-content-between">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-uppercase small fw-bold text-secondary">Kartu Aktif</span>
                    <span class="badge bg-success p-2 rounded-3 text-white">
                        <i class="bi bi-check-lg fs-6"></i>
                    </span>
                </div>
                <div class="my-3 d-flex align-items-baseline gap-2">
                    <h2 class="fw-bold text-dark mb-0 font-monospace">{{ $activeCards }}</h2>
                    <span class="badge bg-success-subtle text-success">aktif</span>
                </div>
                <div class="pt-2 border-top text-success small d-flex align-items-center gap-1">
                    <i class="bi bi-broadcast"></i> Akses diizinkan tap gate
                </div>
            </div>
        </div>

        <!-- Card 3: Kartu Diblokir -->
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 d-flex flex-column justify-content-between">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-uppercase small fw-bold text-secondary">Kartu Diblokir</span>
                    <span class="badge bg-danger p-2 rounded-3 text-white">
                        <i class="bi bi-slash-circle fs-6"></i>
                    </span>
                </div>
                <div class="my-3 d-flex align-items-baseline gap-2">
                    <h2 class="fw-bold text-dark mb-0 font-monospace">{{ $blockedCards }}</h2>
                    <span class="badge bg-danger-subtle text-danger">blokir</span>
                </div>
                <div class="pt-2 border-top text-danger small d-flex align-items-center gap-1">
                    <i class="bi bi-shield-x"></i> Akses dibekukan sementara
                </div>
            </div>
        </div>
    </div>

    <!-- PENDAFTAR MEMBER DARI MOBILE (MENUNGGU PEMBUATAN KARTU RFID) -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
        <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 mb-3">
            <div>
                <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-phone-fill text-primary"></i>
                    <span>Pendaftar Member dari Mobile</span>
                    <span class="badge {{ $pendingMembers->isNotEmpty() ? 'bg-warning text-dark' : 'bg-light text-secondary border' }}">
                        {{ $pendingMembers->count() }} Menunggu Kartu
                    </span>
                </h5>
                <small class="text-muted">Client yang sudah mengisi data diri melalui aplikasi mobile dan menunggu dibuatkan kartu member RFID oleh admin</small>
            </div>
        </div>

        @if ($pendingMembers->isEmpty())
            <div class="p-3 rounded-3 bg-light border text-muted small d-flex align-items-center gap-2">
                <i class="bi bi-check2-circle text-success fs-5"></i>
                <span>Semua pendaftar dari aplikasi mobile sudah memiliki kartu member RFID.</span>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="text-secondary small text-uppercase">
                            <th>Calon Member</th>
                            <th>Email</th>
                            <th>No. HP</th>
                            <th>Plat Nomor</th>
                            <th>Waktu Daftar</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pendingMembers as $m)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ $m->avatar_url }}"
                                             alt="{{ $m->name }}"
                                             class="rounded-circle object-fit-cover border shadow-sm flex-shrink-0"
                                             style="width: 36px; height: 36px;"
                                             onerror="this.onerror=null; this.src='{{ asset('assets/images/default-avatar.svg') }}';">
                                        <div>
                                            <strong class="text-dark d-block">{{ $m->name }}</strong>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size: 0.7rem;">Menunggu Kartu</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="small">{{ $m->email }}</td>
                                <td class="small font-monospace">{{ $m->phone ?: '-' }}</td>
                                <td>
                                    @if (!empty($m->license_plate))
                                        <span class="badge bg-light text-dark border font-monospace">{{ $m->license_plate }}</span>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-muted font-monospace">
                                        {{ $m->created_at ? $m->created_at->format('d M Y H:i') : '-' }}
                                    </small>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                        <button type="button" onclick='openAddCardModalForMember({!! json_encode([
                                            "id" => $m->id,
                                            "name" => $m->name,
                                            "email" => $m->email,
                                            "phone" => $m->phone ?? "",
                                            "license_plate" => $m->license_plate ?? "",
                                            "avatar" => $m->avatar ?? ""
                                        ]) !!})' class="btn btn-primary btn-sm rounded-3 d-flex align-items-center gap-1">
                                            <i class="bi bi-credit-card-2-front-fill"></i>
                                            <span>Buatkan Kartu</span>
                                        </button>
                                        <form method="POST" action="{{ route('admin.cards.destroy-pending', $m->id) }}" onsubmit="return confirm('Hapus data pendaftaran mobile atas nama {{ addslashes($m->name) }}?')">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-3" title="Hapus Pendaftar">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- CONTROLS & TABLE CARD -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
        <!-- Toolbar -->
        <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-between gap-3 mb-4">
            <!-- Search & Filter Form -->
            <form method="GET" action="{{ route('admin.cards.index') }}" class="d-flex flex-wrap align-items-center gap-2 flex-grow-1">
                <div class="input-group" style="max-width: 320px;">
                    <span class="input-group-text bg-light text-muted border-end-0">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" name="search" value="{{ $search }}"
                        placeholder="Cari UID atau nama pemilik..."
                        class="form-control bg-light border-start-0 ps-0">
                    @if (!empty($search))
                        <a href="{{ route('admin.cards.index', ['status' => $status]) }}" class="btn btn-light border text-muted">
                            <i class="bi bi-x-circle-fill"></i>
                        </a>
                    @endif
                </div>

                <select name="status" onchange="this.form.submit()" class="form-select bg-light" style="width: auto;">
                    <option value="">Semua Status</option>
                    <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Hanya Aktif</option>
                    <option value="blocked" {{ $status === 'blocked' ? 'selected' : '' }}>Hanya Diblokir</option>
                </select>

                <button type="submit" class="btn btn-secondary btn-sm px-3 rounded-3">Cari</button>

                @if (!empty($search) || !empty($status))
                    <a href="{{ route('admin.cards.index') }}" class="btn btn-link text-decoration-none text-danger btn-sm">Reset</a>
                @endif
            </form>

            <!-- Add Button -->
            <button onclick="openAddCardModal()" class="btn btn-primary rounded-3 d-flex align-items-center gap-2">
                <i class="bi bi-plus-lg"></i>
                <span>Daftarkan Kartu Baru</span>
            </button>
        </div>

        <!-- Table -->
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-secondary small text-uppercase">
                        <th>UID Kartu</th>
                        <th>Nama Pemilik</th>
                        <th>Saldo Kartu</th>
                        <th>Kontak / Email</th>
                        <th>Status Kartu</th>
                        <th>Tanggal Daftar</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($cards->isEmpty())
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-credit-card fs-1 d-block mb-2 text-secondary"></i>
                                Tidak ada data kartu RFID yang sesuai kriteria pencarian.
                            </td>
                        </tr>
                    @else
                        @foreach ($cards as $c)
                            <tr>
                                <td>
                                    <span class="badge bg-light text-dark border font-monospace fs-6">
                                        {{ $c->uid }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ $c->user ? $c->user->avatar_url : asset('assets/images/default-avatar.svg') }}" 
                                             alt="{{ $c->user->name ?? 'Pengendara' }}" 
                                             class="rounded-circle object-fit-cover border shadow-sm flex-shrink-0" 
                                             style="width: 38px; height: 38px;"
                                             onerror="this.onerror=null; this.src='{{ asset('assets/images/default-avatar.svg') }}';">
                                        <div>
                                            <strong class="text-dark d-block">{{ $c->user->name ?? 'Pengendara' }}</strong>
                                            @if (!empty($c->license_plate))
                                                <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.7rem;">{{ $c->license_plate }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle fs-6 font-monospace">
                                        Rp {{ number_format((int)($c->balance ?? 0), 0, ',', '.') }}
                                    </span>
                                </td>
                                <td>
                                    <div class="small">{{ $c->user->email ?? '-' }}</div>
                                    <small class="text-muted">{{ $c->user->phone ?? '-' }}</small>
                                </td>
                                <td>
                                    @if ($c->status === 'active')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            <i class="bi bi-check-circle-fill me-1"></i> AKTIF
                                        </span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                            <i class="bi bi-slash-circle me-1"></i> DIBLOKIR
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-muted font-monospace">
                                        {{ $c->created_at ? $c->created_at->format('d M Y H:i') : '-' }}
                                    </small>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                        <!-- Top Up Saldo Button -->
                                        <button onclick='openAdminTopupModal({!! json_encode([
                                            "id" => $c->id,
                                            "uid" => $c->uid,
                                            "user_name" => $c->user->name ?? "Pengendara",
                                            "balance" => (int) $c->balance
                                        ]) !!})' class="btn btn-outline-warning btn-sm rounded-3" title="Isi Saldo">
                                            <i class="bi bi-wallet2 text-dark"></i>
                                        </button>

                                        <!-- Toggle Status Form -->
                                        <form method="POST" action="{{ route('admin.cards.toggle-status') }}" onsubmit="return confirm('Apakah Anda yakin ingin mengubah status kartu ini?')">
                                            @csrf
                                            <input type="hidden" name="card_id" value="{{ $c->id }}">
                                            <input type="hidden" name="new_status" value="{{ $c->status === 'active' ? 'blocked' : 'active' }}">
                                            @if ($c->status === 'active')
                                                <button type="submit" class="btn btn-outline-danger btn-sm rounded-3" title="Blokir Kartu">
                                                    <i class="bi bi-slash-circle"></i>
                                                </button>
                                            @else
                                                <button type="submit" class="btn btn-outline-success btn-sm rounded-3" title="Aktifkan Kartu">
                                                    <i class="bi bi-check-circle"></i>
                                                </button>
                                            @endif
                                        </form>

                                        <!-- Edit Button -->
                                        <button onclick='openEditCardModal({!! json_encode([
                                            "id" => $c->id,
                                            "uid" => $c->uid,
                                            "license_plate" => $c->license_plate ?? ($c->user->license_plate ?? ""),
                                            "user_name" => $c->user->name ?? "",
                                            "email" => $c->user->email ?? "",
                                            "phone" => $c->user->phone ?? "",
                                            "avatar" => $c->user->avatar ?? "",
                                            "avatar_url" => $c->user ? $c->user->avatar_url : "",
                                            "status" => $c->status,
                                            "balance" => (int) $c->balance
                                        ]) !!})' class="btn btn-outline-secondary btn-sm rounded-3" title="Edit Data">
                                            <i class="bi bi-pencil-fill"></i>
                                        </button>

                                        <!-- Delete Form -->
                                        <form method="POST" action="{{ route('admin.cards.destroy', $c->id) }}" onsubmit="return confirm('Hapus kartu ini secara permanen dari sistem?')">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-3" title="Hapus Kartu">
                                                <i class="bi bi-trash-fill"></i>
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

</div>

<!-- MODAL PENDAFTARAN KARTU BARU -->
<div class="modal fade" id="addCardModal" tabindex="-1" aria-labelledby="addCardModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-dark" id="addCardModalLabel">Pendaftaran Kartu RFID Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="closeAddCardModal()"></button>
            </div>
            <div class="modal-body p-4">
                <!-- INFO CALON MEMBER DARI MOBILE (JIKA DIPILIH) -->
                <div id="selected-member-banner" class="alert alert-info d-none align-items-center justify-content-between p-3 rounded-3 mb-3">
                    <div class="small">
                        <strong class="d-block"><i class="bi bi-person-check-fill me-1"></i> Membuat Kartu Member untuk Pendaftar Mobile</strong>
                        <span id="selected-member-info">-</span>
                    </div>
                </div>

                <!-- TAHAP 1: STANDBY MENUNGGU TAP -->
                <div id="tap-waiting-section" class="text-center py-4 px-3 bg-light rounded-4 border my-2">
                    <div class="badge bg-primary p-3 rounded-4 fs-2 mb-3">
                        <i class="bi bi-wifi"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Silakan Tempelkan Kartu RFID</h5>
                    <p class="text-muted small max-w-xs mx-auto mb-3">
                        Dekatkan kartu ke sensor RFID reader (ESP8266). Sistem akan mendeteksi UID secara otomatis.
                    </p>
                    <div class="d-flex align-items-center justify-content-center gap-2 small text-primary fw-semibold mb-3">
                        <span class="spinner-border spinner-border-sm" role="status"></span>
                        <span id="scan-polling-status">Menunggu tap dari sensor...</span>
                    </div>

                    <div class="border-top pt-3 mt-2 text-start">
                        <label class="form-label small text-muted mb-1">Atau masukkan UID kartu secara manual:</label>
                        <div class="input-group input-group-sm">
                            <input type="text" id="manual-uid-input" placeholder="Contoh: A1B2C3D4" class="form-control font-monospace text-uppercase">
                            <button type="button" onclick="useManualUid()" class="btn btn-outline-primary">Gunakan UID</button>
                        </div>
                    </div>
                </div>

                <!-- TAHAP 2: FORM INPUT -->
                <form id="add-card-form" method="POST" action="{{ route('admin.cards.store') }}" class="d-none">
                    @csrf

                    <div class="alert alert-success d-flex align-items-center justify-content-between p-3 rounded-3 mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-check-circle-fill fs-5 text-success"></i>
                            <div>
                                <small class="fw-bold d-block">Kartu Berhasil Dideteksi!</small>
                                <span class="font-monospace fw-bold text-dark">UID: <span id="display-detected-uid">-</span></span>
                            </div>
                        </div>
                        <button type="button" onclick="resetToWaitingTap()" class="btn btn-sm btn-outline-success">
                            Ganti Kartu
                        </button>
                    </div>

                    <input type="hidden" id="add-uid" name="uid" required value="">
                    <input type="hidden" id="add-user-id" name="user_id" value="">

                    @if ($pendingMembers->isNotEmpty())
                        <div id="pending-member-picker-group" class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Pilih Pendaftar dari Mobile (Opsional)</label>
                            <select id="pending-member-select" onchange="onPendingMemberSelectChange(this)" class="form-select bg-light">
                                <option value="">-- Input Data Member Baru Manual --</option>
                                @foreach ($pendingMembers as $pm)
                                    <option value="{{ $pm->id }}"
                                        data-name="{{ $pm->name }}"
                                        data-email="{{ $pm->email }}"
                                        data-phone="{{ $pm->phone ?? '' }}"
                                        data-plate="{{ $pm->license_plate ?? '' }}"
                                        data-avatar="{{ $pm->avatar ?? '' }}">
                                        {{ $pm->name }} ({{ $pm->email }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Nama Pemilik / Label Kartu <span class="text-danger">*</span></label>
                        <input type="text" id="new-user-name" name="name" required placeholder="Contoh: Budi Santoso" class="form-control">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Email</label>
                            <input type="email" id="new-user-email" name="email" placeholder="budi@gmail.com" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">No. HP / WA</label>
                            <input type="text" id="new-user-phone" name="phone" placeholder="081234567890" class="form-control">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Plat Nomor Kendaraan (Opsional)</label>
                        <input type="text" id="new-user-plate" name="license_plate" placeholder="Contoh: B 1234 ABC" class="form-control font-monospace text-uppercase">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">URL Foto Profil Pengendara (Opsional)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-image"></i></span>
                            <input type="url" id="new-user-avatar" name="avatar" placeholder="https://images.unsplash.com/... atau URL foto" class="form-control" oninput="previewCardAvatar(this.value, 'new-avatar-preview')">
                        </div>
                        <div class="mt-2 d-flex align-items-center gap-2">
                            <img id="new-avatar-preview" src="" class="rounded-circle border object-fit-cover shadow-sm" style="width: 36px; height: 36px; display: none;">
                            <small class="text-muted" style="font-size: 0.75rem;">Masukkan URL gambar/foto untuk profil real pengendara.</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">
                            Password Akun Pengendara <span id="new-password-required-star" class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="text" id="new-user-password" name="password" required minlength="4" class="form-control font-monospace">
                            <button type="button" onclick="generateRandomPassword()" class="btn btn-outline-secondary btn-sm">Acak Sandi</button>
                        </div>
                        <small id="new-password-help" class="text-muted d-none" style="font-size: 0.75rem;">
                            Kosongkan agar pengendara tetap memakai password yang dibuat saat daftar di mobile.
                        </small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-secondary">Status Kartu</label>
                        <select name="status" class="form-select">
                            <option value="active">AKTIF</option>
                            <option value="blocked">DIBLOKIR</option>
                        </select>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" onclick="closeAddCardModal()" class="btn btn-secondary flex-grow-1">Batal</button>
                        <button type="submit" class="btn btn-primary flex-grow-1">Simpan Kartu Baru</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- MODAL EDIT KARTU -->
<div class="modal fade" id="editCardModal" tabindex="-1" aria-labelledby="editCardModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-dark" id="editCardModalLabel">Edit Data Pemilik Kartu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="edit-card-form" method="POST" action="">
                @csrf
                <div class="modal-body p-4">
                    <input type="hidden" id="edit-card-id" name="card_id" value="">

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">UID Kartu RFID</label>
                        <input type="text" id="edit-uid" readonly class="form-control bg-light font-monospace fw-bold">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Nama Pemilik <span class="text-danger">*</span></label>
                        <input type="text" id="edit-name" name="name" required class="form-control">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Email</label>
                            <input type="email" id="edit-email" name="email" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">No. HP</label>
                            <input type="text" id="edit-phone" name="phone" class="form-control">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Plat Nomor Kendaraan</label>
                        <input type="text" id="edit-plate" name="license_plate" placeholder="Contoh: B 1234 ABC" class="form-control font-monospace text-uppercase">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">URL Foto Profil Real (Avatar)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-image"></i></span>
                            <input type="text" id="edit-avatar" name="avatar" placeholder="https://images.unsplash.com/... atau URL foto" class="form-control" oninput="previewCardAvatar(this.value, 'edit-avatar-preview')">
                        </div>
                        <div class="mt-2 d-flex align-items-center gap-2">
                            <img id="edit-avatar-preview" src="" class="rounded-circle border object-fit-cover shadow-sm" style="width: 42px; height: 42px;">
                            <small class="text-muted" style="font-size: 0.75rem;">Pratinjau foto profil. Masukkan URL langsung gambar (JPG, PNG, WebP).</small>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Status Kartu</label>
                            <select id="edit-status" name="status" class="form-select">
                                <option value="active">AKTIF</option>
                                <option value="blocked">DIBLOKIR</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Saldo Kartu (Rp)</label>
                            <input type="number" id="edit-balance" name="balance" min="0" step="1000" class="form-control font-monospace">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Ubah Password Akun Pengendara</label>
                        <input type="text" id="edit-password" name="password" placeholder="Kosongkan jika tidak diubah" class="form-control font-monospace">
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL TOP UP SALDO DARI ADMIN -->
<div class="modal fade" id="adminTopupModal" tabindex="-1" aria-labelledby="adminTopupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="adminTopupModalLabel">
                    <i class="bi bi-wallet2 text-primary"></i>
                    <span>Isi Saldo Kartu RFID</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="admin-topup-form" method="POST" action="">
                @csrf
                <div class="modal-body p-3">
                    <input type="hidden" id="topup-card-id" name="card_id" value="">

                    <div class="p-3 rounded-3 bg-light border mb-3 small">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-secondary">Pemilik:</span>
                            <strong id="topup-user-name" class="text-dark">-</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-secondary">UID:</span>
                            <span id="topup-card-uid" class="font-monospace fw-bold text-dark">-</span>
                        </div>
                        <div class="d-flex justify-content-between pt-1 border-top">
                            <span class="text-secondary">Saldo Sekarang:</span>
                            <strong id="topup-current-bal" class="font-monospace text-success">-</strong>
                        </div>
                    </div>

                    <label class="form-label small fw-semibold text-secondary">Pilih Nominal Cepat</label>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><button type="button" onclick="setAdminTopup(10000)" class="btn btn-outline-primary btn-sm w-100">+10 rb</button></div>
                        <div class="col-6"><button type="button" onclick="setAdminTopup(20000)" class="btn btn-outline-primary btn-sm w-100">+20 rb</button></div>
                        <div class="col-6"><button type="button" onclick="setAdminTopup(50000)" class="btn btn-outline-primary btn-sm w-100">+50 rb</button></div>
                        <div class="col-6"><button type="button" onclick="setAdminTopup(100000)" class="btn btn-outline-primary btn-sm w-100">+100 rb</button></div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-secondary">Nominal Top Up (Rp)</label>
                        <input type="number" id="admin-topup-amount" name="amount" required min="1000" step="1000" placeholder="50000" class="form-control font-monospace fw-bold">
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">Tambah Saldo</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let tapPollInterval = null;
    let initialLastUid = null;
    let selectedPendingMember = null;

    function openAddCardModal() {
        selectedPendingMember = null;
        resetToWaitingTap();
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('addCardModal'));
        modal.show();
        startTapPolling();
    }

    function openAddCardModalForMember(member) {
        selectedPendingMember = member;
        resetToWaitingTap();
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('addCardModal'));
        modal.show();
        startTapPolling();
    }

    function closeAddCardModal() {
        stopTapPolling();
        selectedPendingMember = null;
        const modal = bootstrap.Modal.getInstance(document.getElementById('addCardModal'));
        if (modal) modal.hide();
    }

    function applyMemberToForm(member) {
        const userIdInput = document.getElementById('add-user-id');
        const nameInput = document.getElementById('new-user-name');
        const emailInput = document.getElementById('new-user-email');
        const phoneInput = document.getElementById('new-user-phone');
        const plateInput = document.getElementById('new-user-plate');
        const avInput = document.getElementById('new-user-avatar');
        const pwdInput = document.getElementById('new-user-password');
        const pwdStar = document.getElementById('new-password-required-star');
        const pwdHelp = document.getElementById('new-password-help');
        const banner = document.getElementById('selected-member-banner');
        const bannerInfo = document.getElementById('selected-member-info');
        const picker = document.getElementById('pending-member-select');

        if (member && member.id) {
            if (userIdInput) userIdInput.value = member.id;
            if (nameInput) nameInput.value = member.name || '';
            if (emailInput) emailInput.value = member.email || '';
            if (phoneInput) phoneInput.value = member.phone || '';
            if (plateInput) plateInput.value = member.license_plate || '';
            if (avInput) {
                avInput.value = member.avatar || '';
                previewCardAvatar(member.avatar || '', 'new-avatar-preview');
            }
            if (pwdInput) {
                pwdInput.value = '';
                pwdInput.required = false;
                pwdInput.placeholder = 'Kosongkan agar pakai password mobile';
            }
            if (pwdStar) pwdStar.classList.add('d-none');
            if (pwdHelp) pwdHelp.classList.remove('d-none');
            if (banner) {
                banner.classList.remove('d-none');
                banner.classList.add('d-flex');
            }
            if (bannerInfo) {
                bannerInfo.textContent = `${member.name} (${member.email})`;
            }
            if (picker) picker.value = String(member.id);
        } else {
            if (userIdInput) userIdInput.value = '';
            if (nameInput) nameInput.value = '';
            if (emailInput) emailInput.value = '';
            if (phoneInput) phoneInput.value = '';
            if (plateInput) plateInput.value = '';
            if (avInput) avInput.value = '';
            const avPrev = document.getElementById('new-avatar-preview');
            if (avPrev) avPrev.style.display = 'none';
            if (pwdInput) {
                pwdInput.value = '';
                pwdInput.required = true;
                pwdInput.placeholder = '';
            }
            if (pwdStar) pwdStar.classList.remove('d-none');
            if (pwdHelp) pwdHelp.classList.add('d-none');
            if (banner) {
                banner.classList.add('d-none');
                banner.classList.remove('d-flex');
            }
            if (picker) picker.value = '';
        }
    }

    function onPendingMemberSelectChange(selectEl) {
        const opt = selectEl.options[selectEl.selectedIndex];
        if (!selectEl.value) {
            selectedPendingMember = null;
            applyMemberToForm(null);
            generateRandomPassword();
            return;
        }
        selectedPendingMember = {
            id: parseInt(selectEl.value, 10),
            name: opt.getAttribute('data-name') || '',
            email: opt.getAttribute('data-email') || '',
            phone: opt.getAttribute('data-phone') || '',
            license_plate: opt.getAttribute('data-plate') || '',
            avatar: opt.getAttribute('data-avatar') || ''
        };
        applyMemberToForm(selectedPendingMember);
    }

    function useManualUid() {
        const manualInput = document.getElementById('manual-uid-input');
        const uid = manualInput ? manualInput.value.trim().toUpperCase() : '';
        if (!uid) {
            showToast('Peringatan', 'Masukkan UID kartu terlebih dahulu.', 'warning');
            return;
        }
        stopTapPolling();
        handleCardDetected(uid, false, '');
    }

    function resetToWaitingTap() {
        document.getElementById('tap-waiting-section').classList.remove('d-none');
        document.getElementById('add-card-form').classList.add('d-none');
        document.getElementById('add-uid').value = '';
        document.getElementById('display-detected-uid').textContent = '-';
        document.getElementById('scan-polling-status').textContent = 'Menunggu tap dari sensor...';
        const manualInput = document.getElementById('manual-uid-input');
        if (manualInput) manualInput.value = '';

        applyMemberToForm(selectedPendingMember);
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
        if (selectedPendingMember) {
            applyMemberToForm(selectedPendingMember);
        } else {
            if (existingName) {
                nameInput.value = existingName;
            } else if (!nameInput.value) {
                nameInput.placeholder = 'Pengendara (' + uid.slice(-4) + ')';
            }

            const pwdInput = document.getElementById('new-user-password');
            if (pwdInput && !pwdInput.value) {
                generateRandomPassword();
            }
        }

        document.getElementById('tap-waiting-section').classList.add('d-none');
        document.getElementById('add-card-form').classList.remove('d-none');

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

    function previewCardAvatar(url, previewId) {
        const preview = document.getElementById(previewId);
        if (!preview) return;
        if (url && (url.startsWith('http://') || url.startsWith('https://'))) {
            preview.src = url;
            preview.style.display = 'block';
        } else {
            if (previewId === 'new-avatar-preview') {
                preview.style.display = 'none';
            } else {
                preview.src = '{{ asset('assets/images/default-avatar.svg') }}';
                preview.style.display = 'block';
            }
        }
    }

    function openEditCardModal(card) {
        document.getElementById('edit-card-id').value = card.id;
        document.getElementById('edit-card-form').action = '{{ url('/admin/cards') }}/' + card.id;
        document.getElementById('edit-uid').value = card.uid;
        document.getElementById('edit-name').value = card.user_name || '';
        document.getElementById('edit-email').value = card.email || '';
        document.getElementById('edit-phone').value = card.phone || '';
        const editPlate = document.getElementById('edit-plate');
        if (editPlate) editPlate.value = card.license_plate || '';
        document.getElementById('edit-avatar').value = card.avatar || '';
        document.getElementById('edit-status').value = card.status;
        document.getElementById('edit-balance').value = card.balance || 0;
        const editPwd = document.getElementById('edit-password');
        if (editPwd) editPwd.value = '';

        const prev = document.getElementById('edit-avatar-preview');
        if (prev) {
            prev.src = card.avatar_url || '{{ asset('assets/images/default-avatar.svg') }}';
            prev.style.display = 'block';
        }

        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('editCardModal'));
        modal.show();
    }

    function closeEditCardModal() {
        const modal = bootstrap.Modal.getInstance(document.getElementById('editCardModal'));
        if (modal) modal.hide();
    }

    function openAdminTopupModal(card) {
        document.getElementById('topup-card-id').value = card.id;
        document.getElementById('admin-topup-form').action = '{{ url('/admin/cards') }}/' + card.id + '/topup';
        document.getElementById('topup-user-name').textContent = card.user_name || '-';
        document.getElementById('topup-card-uid').textContent = card.uid;
        document.getElementById('topup-current-bal').textContent = 'Rp ' + parseInt(card.balance || 0).toLocaleString('id-ID');
        document.getElementById('admin-topup-amount').value = '20000';
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('adminTopupModal'));
        modal.show();
    }

    function closeAdminTopupModal() {
        const modal = bootstrap.Modal.getInstance(document.getElementById('adminTopupModal'));
        if (modal) modal.hide();
    }

    function setAdminTopup(amount) {
        document.getElementById('admin-topup-amount').value = amount;
    }
</script>
@endpush
