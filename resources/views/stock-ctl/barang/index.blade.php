@extends('layouts.app_stock_sidebar')

@section('content')
<div class="space-y-6 text-sm text-gray-800 font-sans" x-data="barangModal()">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h2 class="text-xl font-semibold text-gray-800">Master Barang</h2>
        <button @click="openCreateModal()" 
                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700">
            + Tambah Barang
        </button>
    </div>

    {{-- Filter/Search --}}
    <div class="bg-white border rounded-xl p-4">
        <form method="GET" class="flex gap-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari kode/nama barang" 
                   class="flex-1 border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm">Cari</button>
            <a href="{{ route('stock-ctl.barang.index') }}" class="px-4 py-2 bg-gray-200 rounded-lg text-sm">Reset</a>
        </form>
    </div>

    {{-- Tabel --}}
    <div class="bg-white border rounded-xl overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">Kode</th>
                    <th class="px-4 py-3 text-left">Nama Barang</th>
                    <th class="px-4 py-3 text-left">Satuan</th>
                    <th class="px-4 py-3 text-left">Harga</th>
                    <th class="px-4 py-3 text-left">Deskripsi</th>
                    <th class="px-4 py-3 text-left">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($barang as $item)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">{{ $item->kode_barang }}</td>
                    <td class="px-4 py-3">{{ $item->nama_barang }}</td>
                    <td class="px-4 py-3">{{ $item->satuan ?? '-' }}</td>
                    <td class="px-4 py-3">{{ $item->harga ? number_format($item->harga, 0, ',', '.') : '-' }}</td>
                    <td class="px-4 py-3">{{ $item->deskripsi ?? '-' }}</td>
                    <td class="px-4 py-3">
                        <button @click="openEditModal({{ $item->toJson() }})" class="text-blue-600 hover:underline mr-2">Edit</button>
                        <form action="{{ route('stock-ctl.barang.destroy', $item->id_barang) }}" method="POST" class="inline" onsubmit="return confirm('Hapus barang?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline">Hapus</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="py-10 text-center text-gray-500">Belum ada data barang</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($barang->hasPages())
        <div class="mt-4">{{ $barang->links() }}</div>
    @endif

    {{-- Modal Create --}}
    <div x-show="createModalOpen" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50" x-cloak>
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h3 class="text-lg font-semibold mb-4">Tambah Barang</h3>
            <form method="POST" action="{{ route('stock-ctl.barang.store') }}">
                @csrf
                @include('stock-ctl.barang._form', ['barang' => null])
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" @click="createModalOpen = false" class="px-4 py-2 bg-gray-200 rounded-lg">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Edit --}}
    <div x-show="editModalOpen" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50" x-cloak>
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h3 class="text-lg font-semibold mb-4">Edit Barang</h3>
            <form method="POST" :action="'{{ url('stock-ctl/barang') }}/' + editedItem.id_barang">
                @csrf
                @method('PUT')
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Kode Barang</label>
                    <input type="text" name="kode_barang" x-model="editedItem.kode_barang" 
                           class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Nama Barang</label>
                    <input type="text" name="nama_barang" x-model="editedItem.nama_barang" 
                           class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Satuan</label>
                    <select name="satuan" x-model="editedItem.satuan"
                        class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">

                        <option value="">Pilih satuan</option>
                        <option value="Pcs">Pcs</option>
                        <option value="Unit">Unit</option>
                        <option value="Box">Box</option>
                        <option value="Pack">Pack</option>
                        <option value="Set">Set</option>
                        <option value="Lusin">Lusin</option>
                        <option value="Rim">Rim</option>
                        <option value="Kg">Kg</option>
                        <option value="Gram">Gram</option>
                        <option value="Liter">Liter</option>
                        <option value="Meter">Meter</option>
                        <option value="Roll">Roll</option>

                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Harga</label>
                    <input type="number" step="100" name="harga" x-model="editedItem.harga" 
                           class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Deskripsi</label>
                    <textarea name="deskripsi" rows="3" x-model="editedItem.deskripsi" 
                              class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" @click="editModalOpen = false" class="px-4 py-2 bg-gray-200 rounded-lg">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function barangModal() {
    return {
        createModalOpen: false,
        editModalOpen: false,
        editedItem: {},
        openCreateModal() {
            this.createModalOpen = true;
        },
        openEditModal(item) {
            this.editedItem = item;
            this.editModalOpen = true;
        }
    }
}
</script>
@endpush
@endsection