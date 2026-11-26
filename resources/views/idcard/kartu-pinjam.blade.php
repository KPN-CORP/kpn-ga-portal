@extends('layouts.app-sidebar-card')
@section('content')
<style>[x-cloak]{display:none !important}</style>
@php
    $statusUi = [
        'digunakan' => ['bg-blue-100 text-blue-800', 'Digunakan'],
        'ada'       => ['bg-green-100 text-green-800', 'Ada'],
        'nonaktif'  => ['bg-gray-100 text-gray-700', 'Tidak beredar'],
    ];
    $fmt = fn ($d) => $d ? $d->format('d-m-Y') : '-';
@endphp
<div class="space-y-6 text-sm text-gray-800 font-sans"
     x-data="{
        add: false,
        edit: null,
        form: { id: '', nomor: '', jenis: 'umum', keterangan: '', beredar: true },
        openEdit(k) { this.form = k; this.edit = true; },
        close() { this.add = false; this.edit = null; }
     }"
     @keydown.escape.window="close()">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Kartu Pinjam</h2>
            <p class="text-xs text-gray-500 mt-1">Kartu fisik magang dan visitor. Hanya dicatat <b>Digunakan</b> atau <b>Ada</b>, tidak ikut hitungan stok.</p>
        </div>
        <button type="button" @click="add = true" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700">
            <i class="fas fa-plus mr-2"></i> Tambah Kartu
        </button>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white border rounded-xl p-4"><p class="text-xs text-gray-500">Total kartu</p><p class="text-2xl font-semibold">{{ $ringkas['total'] }}</p></div>
        <div class="bg-white border rounded-xl p-4"><p class="text-xs text-gray-500">Digunakan</p><p class="text-2xl font-semibold text-blue-700">{{ $ringkas['digunakan'] }}</p></div>
        <div class="bg-white border rounded-xl p-4"><p class="text-xs text-gray-500">Ada (bisa dipakai)</p><p class="text-2xl font-semibold text-green-700">{{ $ringkas['ada'] }}</p></div>
        <div class="bg-white border rounded-xl p-4"><p class="text-xs text-gray-500">Tidak beredar</p><p class="text-2xl font-semibold text-gray-600">{{ $ringkas['nonaktif'] }}</p></div>
    </div>

    <div class="bg-white border rounded-xl p-4">
        <form method="GET" action="{{ route('idcard.kartu-pinjam') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="text-sm font-medium text-gray-600">Cari nomor kartu</label>
                <input type="text" name="search" value="{{ request('search') }}" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Status</label>
                <select name="status" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm">
                    <option value="all">Semua</option>
                    @foreach($statusUi as $k => [$c, $l])
                        <option value="{{ $k }}" {{ request('status') === $k ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Jenis</label>
                <select name="jenis" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm">
                    <option value="all">Semua</option>
                    @foreach(\App\Models\IDCard\IdCardKartuPinjam::JENIS_LABELS as $k => $l)
                        <option value="{{ $k }}" {{ request('jenis') === $k ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold">Terapkan</button>
                <a href="{{ route('idcard.kartu-pinjam') }}" class="px-4 py-2 bg-gray-200 rounded-lg text-sm font-semibold">Reset</a>
            </div>
        </form>
    </div>

    <div class="bg-white border rounded-xl overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">Nomor Kartu</th>
                    <th class="px-4 py-3 text-left hidden md:table-cell">Jenis</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Dipakai oleh</th>
                    <th class="px-4 py-3 text-left hidden lg:table-cell">Sampai</th>
                    <th class="px-4 py-3 text-left hidden xl:table-cell">Keterangan</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($data as $row)
                    @php
                        [$cls, $lbl] = $statusUi[$row->status];
                        $p = $row->pemakai;
                        $payload = [
                            'id' => $row->kartu->id, 'nomor' => $row->kartu->nomor_kartu, 'jenis' => $row->kartu->jenis,
                            'keterangan' => $row->kartu->keterangan, 'beredar' => (bool) $row->kartu->is_active,
                        ];
                    @endphp
                    <tr>
                        <td class="px-4 py-3 font-mono font-medium">{{ $row->kartu->nomor_kartu }}</td>
                        <td class="px-4 py-3 hidden md:table-cell">{{ $row->kartu->jenis_text }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-block px-2 py-1 rounded-full text-xs font-semibold {{ $cls }}">{{ $lbl }}</span>
                            @if($p && ($p->return_status ?? 'none') === 'requested')
                                <div class="text-xs text-orange-700 mt-1">Menunggu konfirmasi GA</div>
                            @elseif($row->periode && $row->periode['state'] === 'lewat')
                                <div class="text-xs text-red-600 mt-1">Lewat masa aktif, belum kembali</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($p && !$isSuper && !in_array($p->bisnis_unit_id, $adminBU))
                                <span class="text-gray-500">Dipakai di bisnis unit lain</span>
                            @elseif($p)
                                <a href="{{ route('idcard.detail', $p->id) }}" class="text-blue-600 hover:underline font-medium">{{ $p->nama }}</a>
                                <div class="text-xs text-gray-500">{{ $p->kategori_text }} | {{ $p->nik ?? '-' }}@if(optional($p->bisnisUnit)->nama_bisnis_unit) | {{ $p->bisnisUnit->nama_bisnis_unit }}@endif</div>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 hidden lg:table-cell">{{ $p ? $fmt($p->sampai_tanggal) : '-' }}</td>
                        <td class="px-4 py-3 hidden xl:table-cell">{{ $row->kartu->keterangan ?? '-' }}</td>
                        <td class="px-4 py-3 text-right">
                            <button type="button" class="text-blue-600 hover:underline text-xs font-semibold"
                                x-on:click='openEdit(@json($payload, JSON_HEX_APOS | JSON_HEX_QUOT))'>Ubah</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-10 text-center text-gray-500">Belum ada kartu pinjam</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($data->hasPages())
        <div class="mt-4">{{ $data->withQueryString()->links() }}</div>
    @endif

    {{-- Modal tambah --}}
    <div x-show="add" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="close()">
        <form method="POST" action="{{ route('idcard.kartu-pinjam.store') }}" class="bg-white rounded-xl shadow-xl w-full max-w-md p-5 space-y-4">
            @csrf
            <h3 class="text-lg font-semibold">Tambah Kartu Pinjam</h3>
            <div>
                <label class="block text-sm font-medium mb-1">Nomor kartu *</label>
                <input type="text" name="nomor_kartu" maxlength="50" required class="w-full border rounded-lg px-3 py-2 text-sm font-mono">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Jenis *</label>
                <select name="jenis" class="w-full border rounded-lg px-3 py-2 text-sm">
                    @foreach(\App\Models\IDCard\IdCardKartuPinjam::JENIS_LABELS as $k => $l)
                        <option value="{{ $k }}" {{ $k === 'umum' ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Keterangan</label>
                <input type="text" name="keterangan" maxlength="255" class="w-full border rounded-lg px-3 py-2 text-sm">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" @click="close()" class="px-4 py-2 bg-gray-200 rounded-lg text-sm font-semibold">Batal</button>
                <button class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold">Simpan</button>
            </div>
        </form>
    </div>

    {{-- Modal ubah --}}
    <div x-show="edit" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="close()">
        <form method="POST" :action="'{{ url('/idcard/kartu-pinjam') }}/' + form.id" class="bg-white rounded-xl shadow-xl w-full max-w-md p-5 space-y-4">
            @csrf
            @method('PUT')
            <h3 class="text-lg font-semibold">Ubah Kartu <span class="font-mono" x-text="form.nomor"></span></h3>
            <div>
                <label class="block text-sm font-medium mb-1">Jenis *</label>
                <select name="jenis" x-model="form.jenis" class="w-full border rounded-lg px-3 py-2 text-sm">
                    @foreach(\App\Models\IDCard\IdCardKartuPinjam::JENIS_LABELS as $k => $l)
                        <option value="{{ $k }}">{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Keterangan</label>
                <input type="text" name="keterangan" maxlength="255" x-model="form.keterangan" class="w-full border rounded-lg px-3 py-2 text-sm">
            </div>
            <label class="flex items-start gap-2 text-sm">
                <input type="checkbox" name="is_active" value="1" x-model="form.beredar" class="mt-1">
                <span>Kartu masih beredar<br><span class="text-xs text-gray-500">Hilangkan centang bila kartu rusak atau hilang. Kartu yang sedang digunakan tidak bisa ditarik.</span></span>
            </label>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" @click="close()" class="px-4 py-2 bg-gray-200 rounded-lg text-sm font-semibold">Batal</button>
                <button class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
