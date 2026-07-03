@extends('layouts.app-sidebar-card')
@section('content')
<div class="space-y-6 text-sm text-gray-800 font-sans">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Kartu Aktif</h2>
            <p class="text-xs text-gray-500">Kartu yang saat ini aktif (tidak dinonaktifkan manual)</p>
        </div>
    </div>

    <!-- Filter (selalu terlihat) -->
    <div class="bg-white border rounded-xl p-4">
        <form method="GET" action="{{ route('idcard.aktif') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <label class="text-sm font-medium text-gray-600">Cari</label>
                <input type="text" name="search" value="{{ request('search') }}" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" placeholder="Nama / NIK">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Kategori</label>
                <select name="kategori" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="all">Semua</option>
                    <option value="karyawan_baru" {{ request('kategori')=='karyawan_baru'?'selected':'' }}>Karyawan Baru</option>
                    <option value="karyawan_mutasi" {{ request('kategori')=='karyawan_mutasi'?'selected':'' }}>Karyawan Mutasi</option>
                    <option value="ganti_kartu" {{ request('kategori')=='ganti_kartu'?'selected':'' }}>Ganti Kartu</option>
                    <option value="magang" {{ request('kategori')=='magang'?'selected':'' }}>Magang</option>
                    <option value="magang_extend" {{ request('kategori')=='magang_extend'?'selected':'' }}>Magang Extend</option>
                    <option value="perubahan_lantai" {{ request('kategori')=='perubahan_lantai'?'selected':'' }}>Perubahan Lantai</option>
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Nomor Kartu</label>
                <input type="text" name="nomor_kartu" value="{{ request('nomor_kartu') }}" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" placeholder="Cari nomor kartu">
            </div>
            @if(isset($bisnisUnits) && $bisnisUnits->count() > 0)
            <div>
                <label class="text-sm font-medium text-gray-600">Bisnis Unit</label>
                <select name="bisnis_unit_id" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="all">Semua Unit</option>
                    @foreach($bisnisUnits as $unit)
                        <option value="{{ $unit->id_bisnis_unit }}" {{ request('bisnis_unit_id')==$unit->id_bisnis_unit?'selected':'' }}>{{ $unit->nama_bisnis_unit }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="lg:col-span-3 flex flex-col sm:flex-row gap-2 justify-end">
                <button class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold">Terapkan</button>
                <a href="{{ route('idcard.aktif') }}" class="px-4 py-2 bg-gray-200 rounded-lg text-sm font-semibold text-center">Reset</a>
            </div>
        </form>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
    @endif

    <div class="bg-white border rounded-xl overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">Nama</th>
                    <th class="px-4 py-3 text-left hidden sm:table-cell">NIK</th>
                    <th class="px-4 py-3 text-left hidden md:table-cell">No. Kartu</th>
                    <th class="px-4 py-3 text-left hidden lg:table-cell">Bisnis Unit</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($data as $item)
                <tr>
                    <td class="px-4 py-3 font-medium">{{ $item->nama }}</td>
                    <td class="px-4 py-3 hidden sm:table-cell">{{ $item->nik ?? '-' }}</td>
                    <td class="px-4 py-3 hidden md:table-cell"><span class="font-mono">{{ $item->nomor_kartu ?? '-' }}</span></td>
                    <td class="px-4 py-3 hidden lg:table-cell">{{ optional($bisnisUnits->firstWhere('id_bisnis_unit',$item->bisnis_unit_id))->nama_bisnis_unit ?? '-' }}</td>
                    <td class="px-4 py-3">
                        @if($item->is_active)
                            <span class="px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">Aktif</span>
                        @else
                            <span class="px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">Tidak Aktif</span>
                        @endif
                        @if($item->status == 'pending')
                            <span class="ml-2 px-2 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">Pending</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 flex flex-wrap gap-2">
                        <a href="{{ route('idcard.detail', $item->id) }}" class="text-blue-600 hover:underline">Detail</a>
                        @if(isset($canNonaktifkan) && $canNonaktifkan && $item->status == 'approved' && $item->is_active == 1)
                        <button onclick="openNonaktifModal({{ $item->id }}, '{{ $item->nama }}', '{{ $item->nomor_kartu }}')" class="text-red-600 hover:underline">Nonaktifkan</button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="py-10 text-center text-gray-500">Tidak ada kartu aktif</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($data->hasPages())
        <div class="mt-4">{{ $data->withQueryString()->links() }}</div>
    @endif
</div>

<!-- Modal Nonaktifkan -->
<div id="nonaktifModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg p-6 w-96 max-w-full">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Nonaktifkan Kartu</h3>
        <form id="nonaktifForm" method="POST">
            @csrf
            <p class="text-sm text-gray-600 mb-2">Nonaktifkan kartu untuk: <span id="nonaktifNama" class="font-semibold"></span></p>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Kartu (Opsional)</label>
                <input type="text" name="nomor_kartu" id="nonaktifNomorKartu" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" placeholder="Ubah nomor kartu jika perlu">
                <p class="text-xs text-gray-500 mt-1">Kosongkan jika tidak ingin mengubah</p>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-400">Batal</button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700">Nonaktifkan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openNonaktifModal(id, nama, nomorKartu) {
        const modal = document.getElementById('nonaktifModal');
        const form = document.getElementById('nonaktifForm');
        const namaSpan = document.getElementById('nonaktifNama');
        const nomorInput = document.getElementById('nonaktifNomorKartu');

        namaSpan.textContent = nama;
        nomorInput.value = nomorKartu || '';
        form.action = "{{ url('/idcard') }}/" + id + "/nonaktifkan";
        modal.classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('nonaktifModal').classList.add('hidden');
    }

    document.getElementById('nonaktifModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });
</script>
@endsection