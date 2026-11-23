@extends('layouts.app_apartadmin_sidebar')
@section('content')
@php
    $apartemen->appends(request()->query());
    $hasFilter = request()->filled('search');
@endphp
<div class="w-full">

    @if(session('success'))
        <div class="ap-flash ok" role="status"><i class="fas fa-check-circle mt-0.5"></i><span>{{ session('success') }}</span></div>
    @endif
    @if(session('error'))
        <div class="ap-flash err" role="alert"><i class="fas fa-exclamation-circle mt-0.5"></i><span>{{ session('error') }}</span></div>
    @endif
    @if($errors->any())
        <div class="ap-flash err" role="alert"><i class="fas fa-exclamation-circle mt-0.5"></i>
            <ul class="list-disc pl-4">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <x-apartemen.page-header title="Manajemen Apartemen" subtitle="Kelola data apartemen dan unit tersedia">
        <button type="button" onclick="showAddModal()" class="ap-btn ap-btn-lg ap-btn-blue w-full sm:w-auto"><i class="fas fa-plus"></i>Tambah apartemen</button>
    </x-apartemen.page-header>

    {{-- RINGKASAN UNIT --}}
    @php
        $unitTotal = $unitStats['ready'] + $unitStats['terisi'] + $unitStats['maintenance'];
    @endphp
    <div class="ap-kpis c4">
        <x-apartemen.stat-card label="Total apartemen" :value="$apartemen->total()" color="blue"><i class="fas fa-building"></i></x-apartemen.stat-card>
        <x-apartemen.stat-card label="Unit tersedia" :value="$unitStats['ready']" :total="$unitTotal" color="green"><i class="fas fa-check"></i></x-apartemen.stat-card>
        <x-apartemen.stat-card label="Unit terisi" :value="$unitStats['terisi']" :total="$unitTotal" color="yellow"><i class="fas fa-bed"></i></x-apartemen.stat-card>
        <x-apartemen.stat-card label="Maintenance" :value="$unitStats['maintenance']" :total="$unitTotal" color="red"><i class="fas fa-tools"></i></x-apartemen.stat-card>
    </div>

    {{-- CARI --}}
    <form action="{{ route('apartemen.admin.apartemen') }}" method="GET" class="ap-card p-3 md:p-4 mb-4">
        <div class="ap-search max-w-xl">
            <i class="fas fa-search"></i>
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari nama apartemen atau penanggung jawab" class="ap-input" aria-label="Cari">
            <button type="submit" class="ap-search-go">Cari</button>
        </div>
        @if($hasFilter)
            <div class="ap-filter-row">
                <span class="text-xs text-gray-500 mr-1">Filter aktif</span>
                <a href="{{ route('apartemen.admin.apartemen') }}" class="chip">“{{ request('search') }}” <i class="fas fa-times"></i></a>
                <a href="{{ route('apartemen.admin.apartemen') }}" class="ap-link ml-auto">Reset semua</a>
            </div>
        @endif
    </form>

    {{-- DAFTAR --}}
    <div class="ap-card overflow-hidden">
        <div class="ap-listhead">
            <h3>Daftar apartemen</h3>
            <p><b>{{ number_format($apartemen->total()) }}</b> apartemen{{ $hasFilter ? ' sesuai pencarian' : '' }}</p>
        </div>

        @if($apartemen->count() > 0)
        <div class="md:overflow-x-auto">
            <table class="ap-table">
                <thead>
                    <tr>
                        <th>Apartemen</th>
                        <th>Penanggung jawab</th>
                        <th>Unit</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($apartemen as $item)
                    <tr class="ap-row">
                        <td class="c-person">
                            <div class="ap-person">
                                <span class="ap-avatar t-blue" style="background:var(--soft);color:var(--c)"><i class="fas fa-building"></i></span>
                                <div class="min-w-0">
                                    <div class="ap-name">{{ $item->nama_apartemen }}</div>
                                    <div class="ap-sub">{{ Str::limit($item->alamat, 60) }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="c-apt">
                            <div class="ap-cell-ico"><i class="far fa-user ap-ico"></i><div>
                                <div class="ap-strong">{{ $item->penanggung_jawab ?: '-' }}</div>
                                @if($item->kontak_darurat)<div class="ap-sub">{{ $item->kontak_darurat }}</div>@endif
                            </div></div>
                        </td>
                        <td class="c-status">
                            <div class="flex flex-wrap items-center gap-1.5 md:justify-start justify-end">
                                <span class="ap-tag t-gray">{{ $item->units_count }} unit</span>
                                <span class="ap-tag t-green">{{ $item->units_ready }} tersedia</span>
                                <span class="ap-tag t-yellow">{{ $item->units_terisi }} terisi</span>
                            </div>
                        </td>
                        <td class="c-act">
                            <div class="ap-actions">
                                <a href="{{ route('apartemen.admin.apartemen.detail', $item->id) }}" class="ap-btn ap-btn-ghost">Lihat unit<i class="fas fa-chevron-right text-[10px]"></i></a>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <x-apartemen.pager :paginator="$apartemen" />
        @else
            <x-apartemen.empty-state
                :title="$hasFilter ? 'Tidak ada apartemen yang cocok' : 'Belum ada data apartemen'"
                :text="$hasFilter ? 'Coba kata kunci lain atau reset pencarian.' : 'Tambahkan apartemen untuk memulai.'"
                :resetUrl="$hasFilter ? route('apartemen.admin.apartemen') : null" />
        @endif
    </div>
</div>

{{-- MODAL TAMBAH APARTEMEN --}}
<div id="addModal" class="ap-modal" role="dialog" aria-modal="true" aria-labelledby="addModalTitle">
    <div class="ap-panel">
        <form id="addForm" method="POST" action="{{ route('apartemen.admin.apartemen.store') }}">
            @csrf
            <div class="ap-panel-h">
                <span class="ap-tile t-blue"><i class="fas fa-building"></i></span>
                <div>
                    <h3 id="addModalTitle">Tambah apartemen</h3>
                    <p>Isi data dasar, unit bisa ditambahkan setelahnya</p>
                </div>
                <button type="button" class="ap-x" onclick="hideAddModal()" aria-label="Tutup"><i class="fas fa-times"></i></button>
            </div>
            <div class="ap-panel-b">
                <div class="ap-field">
                    <label class="ap-label" for="f_nama">Nama apartemen <span class="text-red-500">*</span></label>
                    <input id="f_nama" type="text" name="nama_apartemen" required class="ap-input" placeholder="Nama apartemen" value="{{ old('nama_apartemen') }}">
                    @error('nama_apartemen')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="ap-field">
                    <label class="ap-label" for="f_alamat">Alamat <span class="text-red-500">*</span></label>
                    <textarea id="f_alamat" name="alamat" rows="3" required class="ap-input" placeholder="Alamat lengkap apartemen">{{ old('alamat') }}</textarea>
                    @error('alamat')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="ap-field grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="ap-label" for="f_pj">Penanggung jawab</label>
                        <input id="f_pj" type="text" name="penanggung_jawab" class="ap-input" placeholder="Nama penanggung jawab" value="{{ old('penanggung_jawab') }}">
                    </div>
                    <div>
                        <label class="ap-label" for="f_kd">Kontak darurat</label>
                        <input id="f_kd" type="text" name="kontak_darurat" class="ap-input" placeholder="Telepon / HP darurat" value="{{ old('kontak_darurat') }}">
                    </div>
                </div>
                <div class="ap-field grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="ap-label" for="f_tel">Telepon</label>
                        <input id="f_tel" type="text" name="telepon" class="ap-input" placeholder="Telepon apartemen" value="{{ old('telepon') }}">
                    </div>
                    <div>
                        <label class="ap-label" for="f_email">Email</label>
                        <input id="f_email" type="email" name="email" class="ap-input" placeholder="Email apartemen" value="{{ old('email') }}">
                    </div>
                </div>
            </div>
            <div class="ap-panel-f">
                <button type="button" class="ap-btn ap-btn-lg ap-btn-ghost" onclick="hideAddModal()">Batal</button>
                <button type="submit" id="submitBtn" class="ap-btn ap-btn-lg ap-btn-blue">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const modal = document.getElementById('addModal');
    const form = document.getElementById('addForm');
    const btn = document.getElementById('submitBtn');

    window.showAddModal = function () {
        modal.classList.add('is-open');
        document.body.style.overflow = 'hidden';
        setTimeout(() => form.querySelector('input')?.focus(), 30);
    };
    window.hideAddModal = function () {
        modal.classList.remove('is-open');
        document.body.style.overflow = '';
        form.reset();
        btn.disabled = false;
        btn.classList.remove('is-loading');
        btn.textContent = 'Simpan';
    };

    modal.addEventListener('mousedown', (e) => { if (e.target === modal) hideAddModal(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && modal.classList.contains('is-open')) hideAddModal(); });

    form.addEventListener('submit', function () {
        btn.disabled = true;
        btn.classList.add('is-loading');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>Menyimpan';
    });
    window.addEventListener('pageshow', (e) => { if (e.persisted) location.reload(); });

    // Validasi server gagal → buka lagi modalnya (isian lama dipulihkan oleh old())
    @if($errors->has('nama_apartemen') || $errors->has('alamat'))
        showAddModal();
    @endif
})();
</script>
@endsection