@extends('layouts.admin')

@section('title', 'Audit Log Akses Gerbang')
@section('header_title', 'Log Aktivitas & Audit Akses')
@section('header_subtitle', 'Catatan kronologis seluruh interaksi sensor RFID, penolakan akses, dan aksi pembukaan manual')

@section('content')
<div class="d-flex flex-column gap-4">

    <!-- SUMMARY COUNTERS -->
    <div class="row g-3">
        <div class="col-12 col-sm-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 d-flex flex-column justify-content-between">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-uppercase small fw-bold text-secondary">Total Audit Log</span>
                    <span class="badge bg-light text-dark border p-2 rounded-3">
                        <i class="bi bi-list-task fs-6"></i>
                    </span>
                </div>
                <div class="my-3">
                    <h2 class="fw-bold text-dark mb-0 font-monospace">{{ $totalLogs }}</h2>
                    <small class="text-muted">Aktivitas tersimpan</small>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 d-flex flex-column justify-content-between">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-uppercase small fw-bold text-secondary">Akses Diterima (Granted)</span>
                    <span class="badge bg-success-subtle text-success p-2 rounded-3">
                        <i class="bi bi-check-circle-fill fs-6"></i>
                    </span>
                </div>
                <div class="my-3">
                    <h2 class="fw-bold text-success mb-0 font-monospace">{{ $grantedLogs }}</h2>
                    <small class="text-success d-flex align-items-center gap-1">
                        <i class="bi bi-check2"></i> Tap berhasil diverifikasi
                    </small>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 d-flex flex-column justify-content-between">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-uppercase small fw-bold text-secondary">Akses Ditolak (Denied)</span>
                    <span class="badge bg-danger-subtle text-danger p-2 rounded-3">
                        <i class="bi bi-x-circle-fill fs-6"></i>
                    </span>
                </div>
                <div class="my-3">
                    <h2 class="fw-bold text-danger mb-0 font-monospace">{{ $deniedLogs }}</h2>
                    <small class="text-danger d-flex align-items-center gap-1">
                        <i class="bi bi-shield-x"></i> Ditolak sistem / saldo kurang
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- CONTROLS & TABLE CARD -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
        <!-- Filter Toolbar -->
        <form method="GET" action="{{ route('admin.logs.index') }}" class="d-flex flex-wrap align-items-center gap-2 mb-4">
            <div class="input-group" style="max-width: 300px;">
                <span class="input-group-text bg-light text-muted border-end-0">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" name="search" value="{{ $search }}"
                    placeholder="Cari UID, error, gerbang..."
                    class="form-control bg-light border-start-0 ps-0">
                @if (!empty($search))
                    <a href="{{ route('admin.logs.index', array_merge(request()->except('search'))) }}" class="btn btn-light border text-muted">
                        <i class="bi bi-x-circle-fill"></i>
                    </a>
                @endif
            </div>

            <select name="status" onchange="this.form.submit()" class="form-select bg-light" style="width: auto;">
                <option value="">Semua Status</option>
                <option value="granted" {{ $filterStatus === 'granted' ? 'selected' : '' }}>Hanya Diterima</option>
                <option value="denied" {{ $filterStatus === 'denied' ? 'selected' : '' }}>Hanya Ditolak</option>
            </select>

            <select name="device_id" onchange="this.form.submit()" class="form-select bg-light" style="width: auto;">
                <option value="">Semua Gerbang</option>
                @foreach ($devicesList as $dev)
                    <option value="{{ $dev->id }}" {{ $filterDevice == $dev->id ? 'selected' : '' }}>
                        {{ $dev->device_code }}
                    </option>
                @endforeach
            </select>

            <select name="method" onchange="this.form.submit()" class="form-select bg-light" style="width: auto;">
                <option value="">Semua Metode</option>
                <option value="rfid" {{ $filterMethod === 'rfid' ? 'selected' : '' }}>RFID Tap</option>
                <option value="web_admin" {{ $filterMethod === 'web_admin' ? 'selected' : '' }}>Web Admin (Override)</option>
            </select>

            <button type="submit" class="btn btn-secondary btn-sm px-3 rounded-3">Filter</button>

            @if (!empty($search) || !empty($filterStatus) || !empty($filterDevice) || !empty($filterMethod))
                <a href="{{ route('admin.logs.index') }}" class="btn btn-link text-decoration-none text-danger btn-sm">Reset</a>
            @endif
        </form>

        <!-- Table -->
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-secondary small text-uppercase">
                        <th>Waktu Transaksi</th>
                        <th>Gerbang Titik Akses</th>
                        <th>UID Kartu Scan</th>
                        <th>Metode Akses</th>
                        <th>Status Hasil</th>
                        <th>Catatan &amp; Log Error</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($logs->isEmpty())
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-clock-history fs-1 d-block mb-2 text-secondary"></i>
                                Tidak ada log aktivitas yang cocok dengan filter yang dipilih.
                            </td>
                        </tr>
                    @else
                        @foreach ($logs as $l)
                            <tr>
                                <td class="font-monospace small text-muted">
                                    {{ $l->created_at ? $l->created_at->format('d M Y H:i:s') : '-' }}
                                </td>
                                <td>
                                    <strong class="text-dark d-block font-monospace">{{ $l->device->device_code ?? 'Gate-01' }}</strong>
                                    <small class="text-muted">{{ $l->device->location ?? '-' }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border font-monospace fs-6">
                                        {{ $l->scan_uid }}
                                    </span>
                                </td>
                                <td class="text-uppercase small fw-bold">
                                    @if ($l->access_method === 'rfid')
                                        <span class="text-primary"><i class="bi bi-wifi me-1"></i> RFID</span>
                                    @elseif ($l->access_method === 'web_admin')
                                        <span class="text-warning"><i class="bi bi-shield-lock me-1"></i> ADMIN</span>
                                    @else
                                        <span class="text-info"><i class="bi bi-phone me-1"></i> MOBILE</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($l->status === 'granted')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            <i class="bi bi-check-circle-fill me-1"></i> GRANTED
                                        </span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                            <i class="bi bi-x-circle-fill me-1"></i> DENIED
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex align-items-center justify-content-between gap-2">
                                        <span class="small text-dark">{{ $l->notes ?: '-' }}</span>
                                        @if ($l->status === 'denied' && str_contains($l->notes ?? '', 'tidak terdaftar'))
                                            <a href="{{ route('admin.cards.index', ['new_uid' => $l->scan_uid]) }}" class="btn btn-outline-primary btn-sm rounded-3 py-0 px-2" style="font-size: 0.75rem;">
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

</div>
@endsection
