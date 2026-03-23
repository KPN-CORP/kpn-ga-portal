@extends('layouts.app_car_sidebar')

@section('content')
<div class="space-y-6" x-data="approvalL1Modal()">
    <h1 class="text-2xl font-bold">Approval L1</h1>

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
                    <td class="px-6 py-4 space-x-2">
                        <form action="{{ route('drms.approval.l1.approve', $req->id) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600">Setujui</button>
                        </form>
                        <button @click="openRejectModal({{ $req->id }})" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">Tolak</button>
                        <button @click="openDetailModal({{ json_encode($req->load('requester', 'approverL1', 'admin', 'driver', 'vehicle', 'voucher')) }})" class="text-blue-600 hover:underline">Detail</button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">Tidak ada permintaan yang perlu disetujui.</td></tr>
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
                    <td class="px-6 py-4">
                        @php
                            $statusLabels = [
                                'approved_l1' => 'Disetujui (L1)',
                                'rejected_l1' => 'Ditolak (L1)',
                                'approved_admin' => 'Disetujui GA',
                                'rejected_admin' => 'Ditolak GA',
                            ];
                            $statusColors = [
                                'approved_l1' => 'bg-green-100 text-green-800',
                                'rejected_l1' => 'bg-red-100 text-red-800',
                                'approved_admin' => 'bg-blue-100 text-blue-800',
                                'rejected_admin' => 'bg-orange-100 text-orange-800',
                            ];
                        @endphp
                        <span class="px-2 py-1 rounded-full text-xs {{ $statusColors[$req->status] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ $statusLabels[$req->status] ?? ucfirst($req->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <button @click="openDetailModal({{ json_encode($req->load('requester', 'approverL1', 'admin', 'driver', 'vehicle', 'voucher')) }})" class="text-blue-600 hover:underline">Detail</button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">Belum ada history</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($historyRequests->hasPages())
            <div class="px-6 py-3 border-t">
                {{ $historyRequests->links() }}
            </div>
        @endif
    </div>

    {{-- Modal Reject --}}
    <div x-show="rejectModalOpen" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50" x-cloak>
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h2 class="text-xl font-bold mb-4">Catatan</h2>
            <form :action="`{{ url('drms/approval/l1') }}/${rejectRequestId}/reject`" method="POST">
                @csrf
                <textarea name="rejection_reason" rows="3" class="w-full border rounded p-2" placeholder="Masukkan alasan..." required></textarea>
                <div class="flex justify-end space-x-2 mt-4">
                    <button type="button" @click="rejectModalOpen = false" class="px-4 py-2 bg-gray-300 rounded">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded">Tolak</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Detail --}}
    <div x-show="detailModalOpen" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50" x-cloak>
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-semibold mb-4">Detail Permintaan</h3>
            <table class="w-full">
                <tr><td class="py-2 text-gray-600 w-1/3">No. Request</td><td class="py-2 font-medium" x-text="detailItem.request_no"></td></tr>
                <tr><td class="py-2 text-gray-600">Pemohon</td><td class="py-2 font-medium" x-text="detailItem.requester?.name ?? '-'"></td></tr>
                <tr><td class="py-2 text-gray-600">Tanggal</td><td class="py-2 font-medium" x-text="detailItem.usage_date ? new Date(detailItem.usage_date).toLocaleDateString('id-ID',{day:'2-digit',month:'short',year:'numeric'}) : '-'"></td></tr>
                <tr><td class="py-2 text-gray-600">Jam</td><td class="py-2 font-medium" x-text="(detailItem.start_time || '') + ' - ' + (detailItem.end_time || '')"></td></tr>
                <tr><td class="py-2 text-gray-600">Penjemputan</td><td class="py-2 font-medium" x-text="detailItem.pickup_location"></td></tr>
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
                <template x-if="detailItem.approver_l1">
                    <tr><td class="py-2 text-gray-600">Approval L1</td>
                        <td class="py-2 font-medium" x-text="detailItem.approver_l1.name + (detailItem.approved_l1_at ? ' (' + new Date(detailItem.approved_l1_at).toLocaleString('id-ID',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}) + ')' : '')"></td>
                    </tr>
                </template>
                <template x-if="detailItem.admin">
                    <tr><td class="py-2 text-gray-600">Approval Admin</td>
                        <td class="py-2 font-medium" x-text="detailItem.admin.name + (detailItem.approved_admin_at ? ' (' + new Date(detailItem.approved_admin_at).toLocaleString('id-ID',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}) + ')' : '')"></td>
                    </tr>
                </template>
                <template x-if="detailItem.rejection_reason">
                    <tr><td class="py-2 text-gray-600">Catatan</td><td class="py-2 font-medium text-red-600" x-text="detailItem.rejection_reason"></td></tr>
                </template>
                <template x-if="detailItem.transport_type">
                    <tr><td class="py-2 text-gray-600">Jenis Transport</td>
                        <td class="py-2 font-medium" x-text="detailItem.transport_type == 'company_driver' ? 'Driver Perusahaan' : (detailItem.transport_type == 'voucher' ? 'Voucher' : 'Rental')"></td>
                    </tr>
                </template>
                <template x-if="detailItem.driver">
                    <tr><td class="py-2 text-gray-600">Driver</td>
                        <td class="py-2 font-medium" x-text="detailItem.driver.name + ' (' + (detailItem.driver.phone || '-') + ')'"></td>
                    </tr>
                </template>
                <template x-if="detailItem.vehicle">
                    <tr><td class="py-2 text-gray-600">Kendaraan</td>
                        <td class="py-2 font-medium" x-text="detailItem.vehicle.type + ' - ' + detailItem.vehicle.plate_number"></td>
                    </tr>
                </template>
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
function approvalL1Modal() {
    return {
        rejectModalOpen: false,
        rejectRequestId: null,
        detailModalOpen: false,
        detailItem: {},
        openRejectModal(id) {
            this.rejectRequestId = id;
            this.rejectModalOpen = true;
        },
        openDetailModal(item) {
            this.detailItem = item;
            this.detailModalOpen = true;
        }
    }
}
</script>
@endsection