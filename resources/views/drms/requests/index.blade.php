@extends('layouts.app_car_sidebar')

@section('content')
<div class="space-y-6 text-sm text-gray-800 font-sans" x-data="requestModal()">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">My Driver Requests</h2>
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

    {{-- Filter --}}
    <div id="filterSection" class="bg-white border rounded-xl p-4 hidden">
        <form method="GET" action="{{ route('drms.requests.index') }}"
              class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <label class="text-sm font-medium text-gray-600">Search</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari tujuan / no. request"
                       class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Status</label>
                <select name="status"
                        class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Status</option>
                    <option value="pending_l1" {{ request('status')=='pending_l1'?'selected':'' }}>Pending L1</option>
                    <option value="approved_l1" {{ request('status')=='approved_l1'?'selected':'' }}>Disetujui Atasan</option>
                    <option value="rejected_l1" {{ request('status')=='rejected_l1'?'selected':'' }}>Ditolak Atasan</option>
                    <option value="approved_admin" {{ request('status')=='approved_admin'?'selected':'' }}>Disetujui GA</option>
                    <option value="rejected_admin" {{ request('status')=='rejected_admin'?'selected':'' }}>Ditolak GA</option>
                    <option value="completed" {{ request('status')=='completed'?'selected':'' }}>Selesai</option>
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
                <a href="{{ route('drms.requests.index') }}"
                   class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-300 text-center">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white border rounded-xl overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">No. Request</th>
                    <th class="px-4 py-3 text-left">Tanggal</th>
                    <th class="px-4 py-3 text-left">Tujuan</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($requests as $item)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-mono text-sm">{{ $item->request_no }}</td>
                    <td class="px-4 py-3">{{ \Carbon\Carbon::parse($item->usage_date)->format('d M Y') }} {{ \Carbon\Carbon::parse($item->start_time)->format('H:i') }}</td>
                    <td class="px-4 py-3">
                        <div class="font-medium">{{ $item->destination }}</div>
                        <div class="text-xs text-gray-500 sm:hidden">{{ $item->pickup_location }} → {{ $item->destination }}</div>
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $statusColors = [
                                'pending_l1' => 'bg-yellow-100 text-yellow-800',
                                'approved_l1' => 'bg-blue-100 text-blue-800',
                                'rejected_l1' => 'bg-red-100 text-red-800',
                                'approved_admin' => 'bg-green-100 text-green-800',
                                'rejected_admin' => 'bg-red-100 text-red-800',
                                'completed' => 'bg-gray-100 text-gray-800'
                            ];
                            $statusLabels = [
                                'pending_l1' => 'Pending L1',
                                'approved_l1' => 'Disetujui Atasan',
                                'rejected_l1' => 'Ditolak Atasan',
                                'approved_admin' => 'Disetujui GA',
                                'rejected_admin' => 'Ditolak GA',
                                'completed' => 'Selesai'
                            ];
                        @endphp
                        <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $statusColors[$item->status] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ $statusLabels[$item->status] ?? ucfirst($item->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        {{-- Kirim data dengan relasi menggunakan toArray() --}}
                        <button @click="openDetailModal({{ json_encode($item->toArray()) }})" class="text-blue-600 font-semibold hover:underline">Detail</button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="py-10 text-center text-gray-500">
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

    @if($requests->hasPages())
        <div class="mt-4">{{ $requests->links() }}</div>
    @endif

    {{-- Modal Create --}}
    <div x-show="createModalOpen" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50" x-cloak>
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h3 class="text-lg font-semibold mb-4">Buat Permintaan Driver</h3>
            <form method="POST" action="{{ route('drms.requests.store') }}">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Tanggal Penggunaan</label>
                    <input type="date" name="usage_date" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Jam Berangkat</label>
                    <input type="time" name="start_time" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Jam Selesai (Perkiraan)</label>
                    <input type="time" name="end_time" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Lokasi Penjemputan</label>
                    <input type="text" name="pickup_location" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Tujuan</label>
                    <input type="text" name="destination" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Keperluan <span class="text-red-500">*</span></label>
                    <textarea name="purpose" rows="3" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="createModalOpen = false" class="px-4 py-2 bg-gray-200 rounded-lg">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Ajukan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Detail LENGKAP --}}
    <div x-show="detailModalOpen" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50" x-cloak>
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-semibold mb-4">Detail Permintaan</h3>
            <table class="w-full">
                <tr><td class="py-2 text-gray-600 w-1/3">No. Request</td><td class="py-2 font-medium" x-text="detailItem.request_no"></td></tr>
                <tr>
                    <td class="py-2 text-gray-600">Pemohon</td>
                    <td class="py-2 font-medium">
                        <span x-text="detailItem.requester?.name ?? '-'"></span>
                        <template x-if="detailItem.created_at">
                            <span class="text-gray-500 text-xs ml-1" 
                                x-text="'(' + new Date(detailItem.created_at).toLocaleString('id-ID', {day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit'}) + ')'">
                            </span>
                        </template>
                    </td>
                </tr>
                <tr><td class="py-2 text-gray-600">Tanggal Penggunaan</td><td class="py-2 font-medium" x-text="detailItem.usage_date ? new Date(detailItem.usage_date).toLocaleDateString('id-ID',{day:'2-digit',month:'short',year:'numeric'}) : '-'"></td></tr>
                <tr><td class="py-2 text-gray-600">Jam</td><td class="py-2 font-medium" x-text="(detailItem.start_time || '') + ' - ' + (detailItem.end_time || '')"></td></tr>
                <tr><td class="py-2 text-gray-600">Lokasi Penjemputan</td><td class="py-2 font-medium" x-text="detailItem.pickup_location"></td></tr>
                <tr><td class="py-2 text-gray-600">Tujuan</td><td class="py-2 font-medium" x-text="detailItem.destination"></td></tr>
                <tr><td class="py-2 text-gray-600">Keperluan</td><td class="py-2 font-medium" x-text="detailItem.purpose ?? '-'"></td></tr>
                <tr>
                    <td class="py-2 text-gray-600">Status</td>
                    <td class="py-2">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold"
                              :class="{
                                  'bg-yellow-100 text-yellow-800': detailItem.status === 'pending_l1',
                                  'bg-blue-100 text-blue-800': detailItem.status === 'approved_l1',
                                  'bg-red-100 text-red-800': detailItem.status === 'rejected_l1' || detailItem.status === 'rejected_admin',
                                  'bg-green-100 text-green-800': detailItem.status === 'approved_admin',
                                  'bg-gray-100 text-gray-800': detailItem.status === 'completed'
                              }"
                              x-text="{
                                  'pending_l1': 'Pending L1',
                                  'approved_l1': 'Disetujui Atasan',
                                  'rejected_l1': 'Ditolak Atasan',
                                  'approved_admin': 'Disetujui GA',
                                  'rejected_admin': 'Ditolak GA',
                                  'completed': 'Selesai'
                              }[detailItem.status] || detailItem.status || '-'">
                        </span>
                    </td>
                </tr>
                {{-- Approval L1 --}}
                <template x-if="detailItem.approver_l1">
                    <tr>
                        <td class="py-2 text-gray-600">Approval L1</td>
                        <td class="py-2 font-medium">
                            <span x-text="detailItem.approver_l1.name"></span>
                            <span x-show="detailItem.approved_l1_at" class="text-gray-500 text-xs" x-text="'(' + new Date(detailItem.approved_l1_at).toLocaleString('id-ID',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}).replace(/\./g, ':') + ')'"></span>
                        </td>
                    </tr>
                </template>
                {{-- Approval Admin --}}
                <template x-if="detailItem.admin">
                    <tr>
                        <td class="py-2 text-gray-600">Approval Admin</td>
                        <td class="py-2 font-medium">
                            <span x-text="detailItem.admin.name"></span>
                            <span x-show="detailItem.approved_admin_at" class="text-gray-500 text-xs" x-text="'(' + new Date(detailItem.approved_admin_at).toLocaleString('id-ID',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}).replace(/\./g, ':') + ')'"></span>
                        </td>
                    </tr>
                </template>
                {{-- Catatan (rejection_reason) --}}
                <template x-if="detailItem.rejection_reason">
                    <tr><td class="py-2 text-gray-600">Catatan</td><td class="py-2 font-medium text-red-600" x-text="detailItem.rejection_reason"></td></tr>
                </template>
                {{-- Jenis Transportasi --}}
                <template x-if="detailItem.transport_type">
                    <tr><td class="py-2 text-gray-600">Jenis Transportasi</td>
                        <td class="py-2 font-medium" x-text="detailItem.transport_type == 'company_driver' ? 'Driver Perusahaan' : (detailItem.transport_type == 'voucher' ? 'Voucher' : 'Rental')"></td>
                    </tr>
                </template>
                {{-- Driver --}}
                <template x-if="detailItem.driver">
                    <tr><td class="py-2 text-gray-600">Driver</td>
                        <td class="py-2 font-medium" x-text="detailItem.driver.name + ' (' + (detailItem.driver.phone || '-') + ')'"></td>
                    </tr>
                </template>
                {{-- Kendaraan --}}
                <template x-if="detailItem.vehicle">
                    <tr><td class="py-2 text-gray-600">Kendaraan</td>
                        <td class="py-2 font-medium" x-text="detailItem.vehicle.type + ' - ' + detailItem.vehicle.plate_number"></td>
                    </tr>
                </template>
                {{-- Voucher (opsional) --}}
                <template x-if="detailItem.voucher">
                    <tr><td class="py-2 text-gray-600">Voucher</td>
                        <td class="py-2 font-medium" x-text="detailItem.voucher.code + ' (' + detailItem.voucher.type + ') Rp ' + new Intl.NumberFormat('id-ID').format(detailItem.voucher.nominal)"></td>
                    </tr>
                </template>
            </table>
            <div class="mt-6 flex justify-end">
                <button type="button" @click="detailModalOpen = false" class="px-4 py-2 bg-gray-200 rounded-lg">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
function requestModal() {
    return {
        createModalOpen: false,
        detailModalOpen: false,
        detailItem: {},
        openCreateModal() { this.createModalOpen = true; },
        openDetailModal(item) { 
            console.log('Detail item:', item); // untuk debugging
            this.detailItem = item; 
            this.detailModalOpen = true; 
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('toggleFilterBtn')?.addEventListener('click', () => {
        document.getElementById('filterSection').classList.toggle('hidden')
    });
});
</script>
@endsection