@extends('layouts.app_stock_sidebar')

@section('content')
<div class="space-y-6 text-sm text-gray-800 font-sans" x-data="permintaanModal()">
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Permintaan ATK</h2>
            <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded-full
                         text-xs font-semibold bg-blue-100 text-blue-800">
                Personal Requests
            </span>
        </div>

        <div class="flex gap-2 w-full sm:w-auto">
            <button @click="openCreateModal()"
                    class="flex-1 sm:flex-none inline-flex items-center justify-center
                           px-4 py-2 bg-blue-600 text-white rounded-lg
                           text-sm font-semibold hover:bg-blue-700 transition">
                + Buat Permintaan
            </button>

            <button id="toggleFilterBtn"
                class="flex-1 sm:flex-none px-4 py-2 bg-gray-100 text-gray-700
                       rounded-lg text-sm font-semibold hover:bg-gray-200 transition">
                Filters
            </button>
        </div>
    </div>

    {{-- FILTER --}}
    <div id="filterSection" class="bg-white border rounded-xl p-4 hidden">
        <form method="GET" action="{{ route('stock-ctl.permintaan.index') }}"
              class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

            <div>
                <label class="text-sm font-medium text-gray-600">Search</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari barang"
                       class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="text-sm font-medium text-gray-600">Status</label>
                <select name="status"
                        class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Status</option>
                    <option value="pending_l1" {{ request('status')=='pending_l1'?'selected':'' }}>Pending L1</option>
                    <option value="pending_admin" {{ request('status')=='pending_admin'?'selected':'' }}>Pending Admin</option>
                    <option value="disetujui" {{ request('status')=='disetujui'?'selected':'' }}>Disetujui</option>
                    <option value="ditolak" {{ request('status')=='ditolak'?'selected':'' }}>Ditolak</option>
                </select>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-600">Dari Tanggal</label>
                <input type="date" name="dari" value="{{ request('dari') }}"
                       class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="text-sm font-medium text-gray-600">Sampai Tanggal</label>
                <input type="date" name="sampai" value="{{ request('sampai') }}"
                       class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="lg:col-span-3 flex flex-col sm:flex-row gap-2 justify-end">
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700">
                    Apply
                </button>
                <a href="{{ route('stock-ctl.permintaan.index') }}"
                   class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-300 text-center">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- TABLE --}}
    <div class="bg-white border rounded-xl overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">No. Permintaan</th>
                    <th class="px-4 py-3 text-left">Tanggal</th>
                    <th class="px-4 py-3 text-left">Barang</th>
                    <th class="px-4 py-3 text-left">Jumlah</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($permintaan as $item)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-mono text-sm">ATK-SC-{{ $item->id_permintaan }}</td>
                    <td class="px-4 py-3">{{ \Carbon\Carbon::parse($item->tanggal_permintaan)->timezone('Asia/Jakarta')->format('d M Y H:i') }}</td>
                    <td class="px-4 py-3">
                        <div class="font-medium">{{ $item->barang->nama_barang ?? '-' }}</div>
                        <div class="text-xs text-gray-500 sm:hidden">{{ number_format($item->jumlah) }} {{ $item->barang->satuan ?? '' }}</div>
                    </td>
                    <td class="px-4 py-3 hidden sm:table-cell">{{ number_format($item->jumlah) }} {{ $item->barang->satuan ?? '' }}</td>
                    <td class="px-4 py-3">
                        @php
                            $statusColors = [
                                'pending_l1' => 'bg-yellow-100 text-yellow-800',
                                'pending_admin' => 'bg-orange-100 text-orange-800',
                                'disetujui' => 'bg-green-100 text-green-800',
                                'ditolak' => 'bg-red-100 text-red-800'
                            ];
                            $statusLabels = [
                                'pending_l1' => 'Pending L1',
                                'pending_admin' => 'Pending Admin',
                                'disetujui' => 'Disetujui',
                                'ditolak' => 'Ditolak'
                            ];
                        @endphp
                        <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $statusColors[$item->status] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ $statusLabels[$item->status] ?? ucfirst($item->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <button @click="openDetailModal({{ $item->toJson() }})" class="text-blue-600 font-semibold hover:underline">Detail</button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-10 text-center text-gray-500">
                        @if(request()->hasAny(['search', 'status', 'dari', 'sampai']))
                        Data tidak ditemukan
                        @else
                        Belum ada permintaan
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($permintaan->hasPages())
        <div class="mt-4">{{ $permintaan->links() }}</div>
    @endif

    {{-- MODAL CREATE --}}
    <div x-show="createModalOpen" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50" x-cloak>
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h3 class="text-lg font-semibold mb-4">Ajukan Permintaan ATK</h3>
            <form method="POST" action="{{ route('stock-ctl.permintaan.store') }}">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Pilih Barang</label>
                    <select name="id_barang" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                        <option value="">-- Pilih Barang --</option>
                        @foreach($barang as $b)
                            <option value="{{ $b->id_barang }}">{{ $b->kode_barang }} - {{ $b->nama_barang }} ({{ $b->satuan }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Jumlah</label>
                    <input type="number" step="0.01" name="jumlah" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div class="mb-4">
                <label class="block text-sm font-medium text-gray-600 mb-1">
                    Keterangan <span class="text-red-500">*</span>
                </label>
                <textarea 
                    name="keterangan" 
                    rows="3" 
                    required
                    class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Wajib diisi"
                ></textarea>
            </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="createModalOpen = false" class="px-4 py-2 bg-gray-200 rounded-lg">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Ajukan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL DETAIL --}}
    <div x-show="detailModalOpen" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50" x-cloak>
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h3 class="text-lg font-semibold mb-4">Detail Permintaan</h3>
            <table class="w-full">
                <tr><td class="py-2 text-gray-600 w-1/3">No. Permintaan</td><td class="py-2 font-medium">ATK-SC-<span x-text="detailItem.id_permintaan"></span></td></tr>
                <tr><td class="py-2 text-gray-600">Tanggal</td><td class="py-2 font-medium" x-text="detailItem.tanggal_permintaan ? new Date(detailItem.tanggal_permintaan).toLocaleString('id-ID',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}) : '-'"></td></tr>
                <tr><td class="py-2 text-gray-600">Pemohon</td><td class="py-2 font-medium" x-text="detailItem.pemohon?.name ?? '-'"></td></tr>

                {{-- Unit Pemohon (ditampilkan hanya jika ada) --}}
                <template x-if="detailItem.pemohon?.profil?.unit">
                    <tr>
                        <td class="py-2 text-gray-600">Unit</td>
                        <td class="py-2 font-medium" x-text="detailItem.pemohon.profil.unit.split(' (')[0]"></td>
                    </tr>
                </template>

                <tr><td class="py-2 text-gray-600">Barang</td><td class="py-2 font-medium" x-text="(detailItem.barang?.nama_barang ?? '') + ' (' + (detailItem.barang?.kode_barang ?? '') + ')'"></td></tr>
                <tr><td class="py-2 text-gray-600">Jumlah</td><td class="py-2 font-medium" x-text="(detailItem.jumlah ?? '') + ' ' + (detailItem.barang?.satuan ?? '')"></td></tr>
                <tr><td class="py-2 text-gray-600">Keterangan</td><td class="py-2 font-medium" x-text="detailItem.keterangan ?? '-'"></td></tr>
                <tr><td class="py-2 text-gray-600">Status</td>
                    <td class="py-2">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold" :class="{
                            'bg-yellow-100 text-yellow-800': detailItem.status == 'pending_l1',
                            'bg-orange-100 text-orange-800': detailItem.status == 'pending_admin',
                            'bg-green-100 text-green-800': detailItem.status == 'disetujui',
                            'bg-red-100 text-red-800': detailItem.status == 'ditolak'
                        }" x-text="detailItem.status ? (detailItem.status == 'pending_l1' ? 'Pending L1' : (detailItem.status == 'pending_admin' ? 'Pending Admin' : detailItem.status.charAt(0).toUpperCase() + detailItem.status.slice(1))) : '-'"></span>
                    </td>
                </tr>

                {{-- Approval L1 --}}
<template x-if="detailItem.approved_l1_by">
    <tr>
        <td class="py-2 text-gray-600">Approval L1</td>
        <td class="py-2 font-medium">
            <span x-text="detailItem.approver_l1_name || 'User ' + detailItem.approved_l1_by"></span>
            <span x-show="detailItem.approved_l1_at" class="text-gray-500 text-xs ml-1">
                (<span x-text="new Date(detailItem.approved_l1_at).toLocaleString('id-ID',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'})"></span>)
            </span>
        </td>
    </tr>
</template>

{{-- Approval Admin --}}
<template x-if="detailItem.approved_admin_by">
    <tr>
        <td class="py-2 text-gray-600">Approval Admin</td>
        <td class="py-2 font-medium">
            <span x-text="detailItem.approver_admin_name || 'User ' + detailItem.approved_admin_by"></span>
            <span x-show="detailItem.approved_admin_at" class="text-gray-500 text-xs ml-1">
                (<span x-text="new Date(detailItem.approved_admin_at).toLocaleString('id-ID',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'})"></span>)
            </span>
        </td>
    </tr>
</template>

                {{-- Alasan Penolakan --}}
                <template x-if="detailItem.status == 'ditolak'">
                    <tr><td class="py-2 text-gray-600">Alasan Penolakan</td><td class="py-2 font-medium text-red-600" x-text="detailItem.alasan_tolak ?? '-'"></td></tr>
                </template>
            </table>
            <div class="mt-6 flex justify-end">
                <button type="button" @click="detailModalOpen = false" class="px-4 py-2 bg-gray-200 rounded-lg">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
function permintaanModal() {
    return {
        createModalOpen: false,
        detailModalOpen: false,
        detailItem: {},
        openCreateModal() { this.createModalOpen = true; },
        openDetailModal(item) { this.detailItem = item; this.detailModalOpen = true; }
    }
}
document.getElementById('toggleFilterBtn')?.addEventListener('click', () => {
    document.getElementById('filterSection').classList.toggle('hidden')
})
</script>
@endsection