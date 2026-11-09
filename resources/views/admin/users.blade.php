@extends('layouts.admin')

@section('title', 'Kelola Admin & Owner')
@section('header_title', 'Kelola Akun Admin & Owner')
@section('header_subtitle', 'Pusat manajemen hak akses, pembuatan akun administrator & pemilik usaha (owner)')

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

    @if (isset($errors) && $errors->any())
        <div class="alert alert-danger rounded-3 shadow-sm" role="alert">
            <div class="d-flex align-items-center gap-2 fw-bold mb-1">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>Terdapat kesalahan pengisian formulir:</span>
            </div>
            <ul class="mb-0 small ps-3">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- METRIK KPI RINGKAS -->
    <div class="row g-3">
        <!-- Card 1: Total Admin -->
        <div class="col-12 col-sm-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 d-flex flex-column justify-content-between">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-uppercase small fw-bold text-secondary">Administrator</span>
                    <span class="badge bg-primary p-2 rounded-3 text-white">
                        <i class="bi bi-shield-lock-fill fs-6"></i>
                    </span>
                </div>
                <div class="my-3">
                    <h2 class="fw-bold text-dark mb-0 font-monospace">{{ $adminCount }} <span class="fs-6 text-muted fw-normal">Akun</span></h2>
                    <small class="text-muted">Akses operasional gerbang & sistem</small>
                </div>
            </div>
        </div>

        <!-- Card 2: Total Owner -->
        <div class="col-12 col-sm-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 d-flex flex-column justify-content-between">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-uppercase small fw-bold text-secondary">Owner (Pemilik Usaha)</span>
                    <span class="badge bg-warning p-2 rounded-3 text-dark">
                        <i class="bi bi-award-fill fs-6"></i>
                    </span>
                </div>
                <div class="my-3">
                    <h2 class="fw-bold text-dark mb-0 font-monospace">{{ $ownerCount }} <span class="fs-6 text-muted fw-normal">Akun</span></h2>
                    <small class="text-muted">Akses eksekutif laporan finansial</small>
                </div>
            </div>
        </div>

        <!-- Card 3: Total Pengelola -->
        <div class="col-12 col-sm-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 d-flex flex-column justify-content-between">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-uppercase small fw-bold text-secondary">Total Pengelola</span>
                    <span class="badge bg-dark p-2 rounded-3 text-white">
                        <i class="bi bi-people-fill fs-6"></i>
                    </span>
                </div>
                <div class="my-3">
                    <h2 class="fw-bold text-primary mb-0 font-monospace">{{ $totalStaff }} <span class="fs-6 text-muted fw-normal">Personil</span></h2>
                    <small class="text-success d-flex align-items-center gap-1">
                        <i class="bi bi-check2-circle"></i> Tervalidasi & terenkripsi Bcrypt
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- TOOLBAR -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
        <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center justify-content-between gap-3 mb-4">
            <form action="{{ route('admin.users.index') }}" method="GET" class="d-flex flex-wrap align-items-center gap-2 flex-grow-1">
                <!-- Search Input -->
                <div class="input-group" style="max-width: 320px;">
                    <span class="input-group-text bg-light text-muted border-end-0">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" name="search" value="{{ $search }}"
                           placeholder="Cari nama, email, no telp..."
                           class="form-control bg-light border-start-0 ps-0">
                    @if (!empty($search))
                        <a href="{{ route('admin.users.index', ['role' => $roleFilter]) }}" class="btn btn-light border text-muted">
                            <i class="bi bi-x-circle-fill"></i>
                        </a>
                    @endif
                </div>

                <!-- Role Filter Pills -->
                <div class="btn-group btn-group-sm" role="group">
                    <a href="{{ route('admin.users.index', ['role' => 'all', 'search' => $search]) }}" 
                       class="btn {{ $roleFilter === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">
                        Semua
                    </a>
                    <a href="{{ route('admin.users.index', ['role' => 'admin', 'search' => $search]) }}" 
                       class="btn {{ $roleFilter === 'admin' ? 'btn-primary' : 'btn-outline-secondary' }}">
                        Admin
                    </a>
                    <a href="{{ route('admin.users.index', ['role' => 'owner', 'search' => $search]) }}" 
                       class="btn {{ $roleFilter === 'owner' ? 'btn-primary' : 'btn-outline-secondary' }}">
                        Owner
                    </a>
                </div>

                <button type="submit" class="btn btn-primary btn-sm px-3 rounded-3">Terapkan</button>
            </form>

            <button type="button" onclick="openCreateModal()" class="btn btn-primary rounded-3 fw-bold d-flex align-items-center gap-2">
                <i class="bi bi-person-plus-fill"></i>
                <span>Tambah Akun Baru</span>
            </button>
        </div>

        <!-- Table -->
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-secondary small text-uppercase">
                        <th class="text-center" style="width: 50px;">No</th>
                        <th>Nama Pengelola</th>
                        <th>Email Login</th>
                        <th>Nomor Telepon</th>
                        <th class="text-center">Peran (Role)</th>
                        <th>Waktu Dibuat</th>
                        <th class="text-center" style="width: 120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($users->isEmpty())
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-person-x fs-1 d-block mb-2 text-secondary"></i>
                                Tidak ada akun admin / owner yang sesuai pencarian.
                            </td>
                        </tr>
                    @else
                        @php $rowNo = 1; @endphp
                        @foreach ($users as $u)
                            @php $isCurrentUser = ($u->id === auth()->id()); @endphp
                            <tr class="{{ $isCurrentUser ? 'table-primary table-opacity-25' : '' }}">
                                <td class="text-center font-monospace small text-muted">{{ $rowNo++ }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ $u->avatar_url }}" alt="{{ $u->name }}" class="rounded-circle object-fit-cover border" style="width: 36px; height: 36px;">
                                        <div>
                                            <strong class="text-dark d-block">{{ $u->name }}</strong>
                                            @if ($isCurrentUser)
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.65rem;">Anda</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td><span class="font-monospace small text-dark">{{ $u->email }}</span></td>
                                <td><small class="text-muted">{{ $u->phone ?? '-' }}</small></td>
                                <td class="text-center">
                                    @if ($u->role === 'admin')
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                            <i class="bi bi-shield-lock-fill me-1"></i> ADMIN
                                        </span>
                                    @elseif ($u->role === 'owner')
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                            <i class="bi bi-award-fill me-1"></i> OWNER
                                        </span>
                                    @endif
                                </td>
                                <td><small class="text-muted font-monospace">{{ $u->created_at ? $u->created_at->format('d/m/Y H:i') : '-' }}</small></td>
                                <td class="text-center">
                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                        <button type="button" onclick="openEditModal({{ json_encode($u) }})" class="btn btn-outline-secondary btn-sm rounded-3" title="Edit Akun">
                                            <i class="bi bi-pencil-fill"></i>
                                        </button>
                                        @if ($isCurrentUser)
                                            <button type="button" disabled class="btn btn-light btn-sm rounded-3 text-muted" title="Akun Sendiri">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        @else
                                            <button type="button" onclick="confirmDelete({{ $u->id }}, '{{ addslashes($u->name) }}', '{{ strtoupper($u->role) }}')" class="btn btn-outline-danger btn-sm rounded-3" title="Hapus Akun">
                                                <i class="bi bi-trash-fill"></i>
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

</div>

<!-- MODAL TAMBAH AKUN BARU -->
<div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="createModalLabel">
                    <span class="badge bg-warning text-dark p-2 rounded-3"><i class="bi bi-person-plus-fill"></i></span>
                    <span>Tambah Akun Pengelola Baru</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.users.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="create_name" class="form-label small fw-semibold text-secondary">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" id="create_name" name="name" required placeholder="Contoh: Budi Santoso" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label for="create_email" class="form-label small fw-semibold text-secondary">Email Login <span class="text-danger">*</span></label>
                        <input type="email" id="create_email" name="email" required placeholder="budi@eparkir.local" class="form-control font-monospace">
                    </div>

                    <div class="mb-3">
                        <label for="create_phone" class="form-label small fw-semibold text-secondary">Nomor Telepon / WhatsApp</label>
                        <input type="text" id="create_phone" name="phone" placeholder="081234567890" class="form-control font-monospace">
                    </div>

                    <div class="mb-3">
                        <label for="create_password" class="form-label small fw-semibold text-secondary">Password Baru <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" id="create_password" name="password" required minlength="6" placeholder="Minimal 6 karakter" class="form-control font-monospace">
                            <button type="button" onclick="togglePasswordVisibility('create_password', this)" class="btn btn-outline-secondary">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted">Password akan di-hash otomatis dengan Bcrypt.</small>
                    </div>

                    <div class="mb-3">
                        <label for="create_role" class="form-label small fw-semibold text-secondary">Pilih Peran Akun (Role) <span class="text-danger">*</span></label>
                        <select id="create_role" name="role" required class="form-select">
                            <option value="admin">Administrator (Akses Penuh Operasional & Sistem)</option>
                            <option value="owner">Owner (Akses Eksekutif Laporan & Finansial)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Akun</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL EDIT AKUN -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="editModalLabel">
                    <span class="badge bg-primary p-2 rounded-3 text-white"><i class="bi bi-pencil-square"></i></span>
                    <span>Perbarui Data Akun</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label small fw-semibold text-secondary">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" id="edit_name" name="name" required class="form-control">
                    </div>

                    <div class="mb-3">
                        <label for="edit_email" class="form-label small fw-semibold text-secondary">Email Login <span class="text-danger">*</span></label>
                        <input type="email" id="edit_email" name="email" required class="form-control font-monospace">
                    </div>

                    <div class="mb-3">
                        <label for="edit_phone" class="form-label small fw-semibold text-secondary">Nomor Telepon / WhatsApp</label>
                        <input type="text" id="edit_phone" name="phone" class="form-control font-monospace">
                    </div>

                    <div class="mb-3">
                        <label for="edit_password" class="form-label small fw-semibold text-secondary">Ganti Password (Opsional)</label>
                        <div class="input-group">
                            <input type="password" id="edit_password" name="password" minlength="6" placeholder="Kosongkan jika tidak diubah" class="form-control font-monospace">
                            <button type="button" onclick="togglePasswordVisibility('edit_password', this)" class="btn btn-outline-secondary">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_role" class="form-label small fw-semibold text-secondary">Peran Akun (Role) <span class="text-danger">*</span></label>
                        <select id="edit_role" name="role" required class="form-select">
                            <option value="admin">Administrator (Akses Penuh Operasional & Sistem)</option>
                            <option value="owner">Owner (Akses Eksekutif Laporan & Finansial)</option>
                        </select>
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

<form id="deleteForm" method="POST" class="d-none">
    @csrf
    @method('DELETE')
</form>

<script>
    function openCreateModal() {
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('createModal'));
        modal.show();
    }

    function openEditModal(user) {
        document.getElementById('editForm').action = '/admin/users/' + user.id;
        document.getElementById('edit_name').value = user.name || '';
        document.getElementById('edit_email').value = user.email || '';
        document.getElementById('edit_phone').value = user.phone || '';
        document.getElementById('edit_password').value = '';
        document.getElementById('edit_role').value = user.role || 'admin';

        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('editModal'));
        modal.show();
    }

    function confirmDelete(userId, userName, roleName) {
        if (confirm('Apakah Anda yakin ingin menghapus akun ' + roleName + ' "' + userName + '"?\n\nTindakan ini tidak dapat dibatalkan.')) {
            const form = document.getElementById('deleteForm');
            form.action = '/admin/users/' + userId + '/delete';
            form.submit();
        }
    }

    function togglePasswordVisibility(inputId, btn) {
        const input = document.getElementById(inputId);
        const icon = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    }
</script>
@endsection
