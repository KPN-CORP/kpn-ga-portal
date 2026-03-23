@extends('layouts.app_car_sidebar')

@section('content')
<div class="space-y-6" x-data="approvalAdminModal()">
    <h1 class="text-2xl font-bold">Approval Admin</h1>

    {{-- Bagian Pending --}}
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="bg-gray-50 px-6 py-3 border-b">
            <h2 class="font-semibold">Permintaan Menunggu</h2>
        </div>
        <table class="min-w-full">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">No. Request</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Pemohon</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Tanggal</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Tujuan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($pendingRequests as $req)
                <tr>
                    <td class="px-6 py-4">{{ $req->request_no }}</td>
                    <td class="px-6 py-4">{{ $req->requester->name }}</td>
                    <td class="px-6 py-4">{{ \Carbon\Carbon::parse($req->usage_date)->format('d M Y') }}</td>
                    <td class="px-6 py-4">{{ $req->destination }}</td>
                    <td class="px-6 py-4">
                        <a href="{{ route('drms.approval.admin.edit', $req->id) }}" class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">Proses</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">Tidak ada permintaan yang perlu diproses.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Bagian History --}}
    <div class="bg-white shadow rounded-lg overflow-hidden mt-8">
        <div class="bg-gray-50 px-6 py-3 border-b">
            <h2 class="font-semibold">History Approval</h2>
        </div>
        <table class="min-w-full">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">No. Request</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Pemohon</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Tanggal</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Tujuan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Jenis</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($historyRequests as $req)
                <tr>
                    <td class="px-6 py-4">{{ $req->request_no }}</td>
                    <td class="px-6 py-4">{{ $req->requester->name }}</td>
                    <td class="px-6 py-4">{{ \Carbon\Carbon::parse($req->usage_date)->format('d M Y') }}</td>
                    <td class="px-6 py-4">{{ $req->destination }}</td>
                    <td class="px-6 py-4">{{ $req->transport_type ? ucfirst(str_replace('_',' ',$req->transport_type)) : '-' }}</td>
                    <td class="px-6 py-4">
                        @php
                            $statusLabels = [
                                'approved_admin' => 'Disetujui',
                                'rejected_admin' => 'Ditolak'
                            ];
                            $statusColors = [
                                'approved_admin' => 'bg-green-100 text-green-800',
                                'rejected_admin' => 'bg-red-100 text-red-800'
                            ];
                        @endphp
                        <span class="px-2 py-1 rounded-full text-xs {{ $statusColors[$req->status] }}">
                            {{ $statusLabels[$req->status] }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        {{-- Tombol Detail dengan modal --}}
                        <button @click="openDetailModal({{ json_encode($req->toArray()) }})" class="text-blue-600 hover:underline">Detail</button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">Belum ada history</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($historyRequests->hasPages())
            <div class="px-6 py-3 border-t">
                {{ $historyRequests->links() }}
            </div>
        @endif
    </div>

    {{-- MODAL DETAIL --}}
    <div x-show="detailModalOpen" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50" x-cloak>
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-semibold mb-4">Detail Permintaan</h3>
            <table class="w-full">
                <tr><td class="py-2 text-gray-600 w-1/3">No. Request</td><td class="py-2 font-medium" x-text="detailItem.request_no"></td></tr>
                <tr><td class="py-2 text-gray-600">Pemohon</td><td class="py-2 font-medium" x-text="detailItem.requester?.name ?? '-'"></td></tr>
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
                {{-- Voucher --}}
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
function approvalAdminModal() {
    return {
        detailModalOpen: false,
        detailItem: {},
        openDetailModal(item) {
            console.log('Detail item:', item); // untuk debugging
            this.detailItem = item;
            this.detailModalOpen = true;
        }
    }
}
</script>
@endsection