@extends('layouts.app_car_sidebar')

@section('content')
<div class="space-y-6 text-sm text-gray-800 font-sans" x-data="approvalAdminModal()">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Approval Admin</h1>
            <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                Persetujuan GA
            </span>
        </div>
        <div class="flex gap-2 w-full sm:w-auto">
            <button id="toggleFilterBtn" class="flex-1 sm:flex-none px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-200 transition">
                Filters
            </button>
        </div>
    </div>

    {{-- Filter Section --}}
    <div id="filterSection" class="bg-white border rounded-xl p-4 hidden">
        <form method="GET" action="{{ route('drms.approval.admin.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <label class="text-sm font-medium text-gray-600">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari no. request / pemohon / tujuan" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Status History</label>
                <select name="status" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm">
                    <option value="">Semua Status</option>
                    <option value="approved_admin" {{ request('status')=='approved_admin' ? 'selected' : '' }}>Disetujui</option>
                    <option value="rejected_admin" {{ request('status')=='rejected_admin' ? 'selected' : '' }}>Ditolak</option>
                </select>
            </div>
            <div class="lg:col-span-3 flex flex-col sm:flex-row gap-2 justify-end">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700">Apply</button>
                <a href="{{ route('drms.approval.admin.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-300 text-center">Reset</a>
            </div>
        </form>
    </div>

    {{-- ==================== PENDING REQUESTS ==================== --}}
    <div class="bg-white border rounded-xl overflow-hidden shadow-sm">
        <div class="bg-gray-50 px-6 py-3 border-b">
            <h2 class="font-semibold text-gray-700">Permintaan Menunggu Persetujuan</h2>
        </div>

        {{-- Desktop Table --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-4 py-3 text-left">No. Request</th>
                        <th class="px-4 py-3 text-left">Pemohon</th>
                        <th class="px-4 py-3 text-left">Tanggal</th>
                        <th class="px-4 py-3 text-left">Perjalanan</th>
                        <th class="px-4 py-3 text-left">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($pendingRequests as $req)
                    @php
                        $startDateTime = \Carbon\Carbon::parse($req->usage_date)->format('d/m/Y') . ' ' . $req->start_time;
                        $endTime = $req->end_time;
                        $returnDate = $req->return_date ? \Carbon\Carbon::parse($req->return_date)->format('d/m/Y') . ' ' . $req->end_time : null;
                        $tujuanFull = $req->pickup_location . ' → ' . $req->destination;
                        $detailData = [
                            'request_no' => $req->request_no,
                            'requester' => ['name' => $req->requester->name],
                            'created_at' => $req->created_at ? \Carbon\Carbon::parse($req->created_at)->format('d/m/Y H:i') : null,
                            'trip_type' => $req->trip_type,
                            'usage_date' => \Carbon\Carbon::parse($req->usage_date)->format('d/m/Y'),
                            'start_time' => $req->start_time,
                            'return_date' => $req->return_date ? \Carbon\Carbon::parse($req->return_date)->format('d/m/Y') : null,
                            'end_time' => $req->end_time,
                            'pickup_location' => $req->pickup_location,
                            'pickup_maps_link' => $req->pickup_maps_link,
                            'destination' => $req->destination,
                            'destination_maps_link' => $req->destination_maps_link,
                            'purpose' => $req->purpose,
                            'status' => $req->status,
                            'approver_l1' => $req->approverL1 ? ['name' => $req->approverL1->name, 'approved_l1_at' => $req->approved_l1_at ? \Carbon\Carbon::parse($req->approved_l1_at)->format('d/m/Y H:i') : null] : null,
                            'admin' => $req->admin ? ['name' => $req->admin->name, 'approved_admin_at' => $req->approved_admin_at ? \Carbon\Carbon::parse($req->approved_admin_at)->format('d/m/Y H:i') : null] : null,
                            'rejection_reason' => $req->rejection_reason,
                            'transport_type' => $req->transport_type,
                            'driver' => $req->driver ? ['name' => $req->driver->name, 'phone' => $req->driver->phone] : null,
                            'vehicle' => $req->vehicle ? ['type' => $req->vehicle->type, 'plate_number' => $req->vehicle->plate_number] : null,
                            'voucher' => $req->voucher ? ['code' => $req->voucher->code, 'type' => $req->voucher->type, 'nominal' => $req->voucher->nominal] : null,
                        ];
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono">{{ $req->request_no }}</td>
                        <td class="px-4 py-3">{{ $req->requester->name }}</td>
                        <td class="px-4 py-3">
                            @if($req->trip_type === 'round_trip')
                                {{ $startDateTime }}
                                @if($returnDate)
                                    <br><span class="text-xs text-gray-500">kembali: {{ $returnDate }}</span>
                                @endif
                            @else
                                {{ $startDateTime . ' - ' . $endTime }}
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $tujuanFull }}</td>
                        <td class="px-4 py-3 space-x-2 whitespace-nowrap">
                            <a href="{{ route('drms.approval.admin.edit', $req->id) }}" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs font-semibold inline-block">Proses</a>
                            <button @click="openRejectModal({{ $req->id }})" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs font-semibold">Tolak</button>
                        </td>
                    </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-gray-500">Tidak ada permintaan yang perlu diproses.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile/Tablet Table --}}
        <div class="block md:hidden overflow-hidden">
            <table class="w-full text-xs border-collapse">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-3 py-2 text-left">No. Request</th>
                        <th class="px-3 py-2 text-left">Tanggal</th>
                        <th class="px-3 py-2 text-left">Tujuan</th>
                        <th class="px-3 py-2 text-left">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingRequests as $index => $req)
                    @php
                        $start = \Carbon\Carbon::parse($req->usage_date)->format('d/m/Y') . ' ' . $req->start_time;
                        $end = $req->return_date ? \Carbon\Carbon::parse($req->return_date)->format('d/m/Y') . ' ' . $req->end_time : $req->end_time;
                        $detailData = [
                            'request_no' => $req->request_no,
                            'requester' => ['name' => $req->requester->name],
                            'created_at' => $req->created_at ? \Carbon\Carbon::parse($req->created_at)->format('d/m/Y H:i') : null,
                            'trip_type' => $req->trip_type,
                            'usage_date' => \Carbon\Carbon::parse($req->usage_date)->format('d/m/Y'),
                            'start_time' => $req->start_time,
                            'return_date' => $req->return_date ? \Carbon\Carbon::parse($req->return_date)->format('d/m/Y') : null,
                            'end_time' => $req->end_time,
                            'pickup_location' => $req->pickup_location,
                            'pickup_maps_link' => $req->pickup_maps_link,
                            'destination' => $req->destination,
                            'destination_maps_link' => $req->destination_maps_link,
                            'purpose' => $req->purpose,
                            'status' => $req->status,
                            'approver_l1' => $req->approverL1 ? ['name' => $req->approverL1->name, 'approved_l1_at' => $req->approved_l1_at ? \Carbon\Carbon::parse($req->approved_l1_at)->format('d/m/Y H:i') : null] : null,
                            'admin' => $req->admin ? ['name' => $req->admin->name, 'approved_admin_at' => $req->approved_admin_at ? \Carbon\Carbon::parse($req->approved_admin_at)->format('d/m/Y H:i') : null] : null,
                            'rejection_reason' => $req->rejection_reason,
                            'transport_type' => $req->transport_type,
                            'driver' => $req->driver ? ['name' => $req->driver->name, 'phone' => $req->driver->phone] : null,
                            'vehicle' => $req->vehicle ? ['type' => $req->vehicle->type, 'plate_number' => $req->vehicle->plate_number] : null,
                            'voucher' => $req->voucher ? ['code' => $req->voucher->code, 'type' => $req->voucher->type, 'nominal' => $req->voucher->nominal] : null,
                        ];
                    @endphp
                    <tr class="border-t {{ $loop->first ? '' : 'border-t-2 border-gray-300' }}">
                        <td class="px-3 pt-3 pb-1 font-mono font-semibold">{{ $req->request_no }}</td>
                        <td class="px-3 pt-3 pb-1">{{ $start }}</td>
                        <td class="px-3 pt-3 pb-1">{{ $req->pickup_location }}</td>
                        <td class="px-3 pt-3 pb-1 text-right">
                            <a href="{{ route('drms.approval.admin.edit', $req->id) }}" class="bg-blue-500 text-white px-2 py-1 rounded text-xs">Proses</a>
                            <button @click="openRejectModal({{ $req->id }})" class="bg-red-500 text-white px-2 py-1 rounded text-xs">Tolak</button>
                        </td>
                    </tr>
                    <tr class="bg-gray-50 border-b">
                        <td class="px-3 pb-3 pt-0 text-gray-500 text-[11px]">
                            {{ $req->trip_type === 'round_trip' ? 'Pulang Pergi' : 'Sekali Jalan' }}
                        </td>
                        <td class="px-3 pb-3 pt-0 text-gray-500 text-[11px]">
                            {{ $end ?? '-' }}
                        </td>
                        <td class="px-3 pb-3 pt-0 text-gray-500 text-[11px]">
                            {{ $req->destination }}
                        </td>
                        <td class="px-3 pb-3 pt-0 text-right">
                            <span class="text-[11px] font-semibold text-yellow-600">Pending GA</span>
                        </td>
                    </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-6 text-center text-gray-500">Tidak ada permintaan pending</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ==================== HISTORY REQUESTS ==================== --}}
    <div class="bg-white border rounded-xl overflow-hidden shadow-sm mt-8">
        <div class="bg-gray-50 px-6 py-3 border-b">
            <h2 class="font-semibold text-gray-700">History Approval</h2>
        </div>

        {{-- Desktop Table --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-4 py-3 text-left">No. Request</th>
                        <th class="px-4 py-3 text-left">Pemohon</th>
                        <th class="px-4 py-3 text-left">Tanggal</th>
                        <th class="px-4 py-3 text-left">Perjalanan</th>
                        <th class="px-4 py-3 text-left">Jenis</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($historyRequests as $req)
                    @php
                        $startDateTime = \Carbon\Carbon::parse($req->usage_date)->format('d/m/Y') . ' ' . $req->start_time;
                        $endTime = $req->end_time;
                        $returnDate = $req->return_date ? \Carbon\Carbon::parse($req->return_date)->format('d/m/Y') . ' ' . $req->end_time : null;
                        $tujuanFull = $req->pickup_location . ' → ' . $req->destination;
                        $statusLabels = ['approved_admin'=>'Disetujui','rejected_admin'=>'Ditolak'];
                        $statusColors = ['approved_admin'=>'bg-green-100 text-green-800','rejected_admin'=>'bg-red-100 text-red-800'];
                        $detailData = [
                            'request_no' => $req->request_no,
                            'requester' => ['name' => $req->requester->name],
                            'created_at' => $req->created_at ? \Carbon\Carbon::parse($req->created_at)->format('d/m/Y H:i') : null,
                            'trip_type' => $req->trip_type,
                            'usage_date' => \Carbon\Carbon::parse($req->usage_date)->format('d/m/Y'),
                            'start_time' => $req->start_time,
                            'return_date' => $req->return_date ? \Carbon\Carbon::parse($req->return_date)->format('d/m/Y') : null,
                            'end_time' => $req->end_time,
                            'pickup_location' => $req->pickup_location,
                            'pickup_maps_link' => $req->pickup_maps_link,
                            'destination' => $req->destination,
                            'destination_maps_link' => $req->destination_maps_link,
                            'purpose' => $req->purpose,
                            'status' => $req->status,
                            'approver_l1' => $req->approverL1 ? ['name' => $req->approverL1->name, 'approved_l1_at' => $req->approved_l1_at ? \Carbon\Carbon::parse($req->approved_l1_at)->format('d/m/Y H:i') : null] : null,
                            'admin' => $req->admin ? ['name' => $req->admin->name, 'approved_admin_at' => $req->approved_admin_at ? \Carbon\Carbon::parse($req->approved_admin_at)->format('d/m/Y H:i') : null] : null,
                            'rejection_reason' => $req->rejection_reason,
                            'transport_type' => $req->transport_type,
                            'driver' => $req->driver ? ['name' => $req->driver->name, 'phone' => $req->driver->phone] : null,
                            'vehicle' => $req->vehicle ? ['type' => $req->vehicle->type, 'plate_number' => $req->vehicle->plate_number] : null,
                            'voucher' => $req->voucher ? ['code' => $req->voucher->code, 'type' => $req->voucher->type, 'nominal' => $req->voucher->nominal] : null,
                        ];
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono">{{ $req->request_no }}</td>
                        <td class="px-4 py-3">{{ $req->requester->name }}</td>
                        <td class="px-4 py-3">
                            @if($req->trip_type === 'round_trip')
                                {{ $startDateTime }}
                                @if($returnDate)
                                    <br><span class="text-xs text-gray-500">kembali: {{ $returnDate }}</span>
                                @endif
                            @else
                                {{ $startDateTime . ' - ' . $endTime }}
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $tujuanFull }}</td>
                        <td class="px-4 py-3">{{ $req->transport_type ? ucfirst(str_replace('_',' ',$req->transport_type)) : '-' }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $statusColors[$req->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ $statusLabels[$req->status] ?? $req->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <button @click="openDetailModal({{ json_encode($detailData) }})" class="text-blue-600 font-semibold hover:underline">Detail</button>
                        </td>
                    </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-gray-500">Belum ada history.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile/Tablet Table --}}
        <div class="block md:hidden overflow-hidden">
            <table class="w-full text-xs border-collapse">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-3 py-2 text-left">No. Request</th>
                        <th class="px-3 py-2 text-left">Tanggal</th>
                        <th class="px-3 py-2 text-left">Tujuan</th>
                        <th class="px-3 py-2 text-left">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($historyRequests as $index => $req)
                    @php
                        $start = \Carbon\Carbon::parse($req->usage_date)->format('d/m/Y') . ' ' . $req->start_time;
                        $end = $req->return_date ? \Carbon\Carbon::parse($req->return_date)->format('d/m/Y') . ' ' . $req->end_time : $req->end_time;
                        $statusLabels = ['approved_admin'=>'Disetujui','rejected_admin'=>'Ditolak'];
                        $statusColors = ['approved_admin'=>'bg-green-100 text-green-800','rejected_admin'=>'bg-red-100 text-red-800'];
                        $detailData = [
                            'request_no' => $req->request_no,
                            'requester' => ['name' => $req->requester->name],
                            'created_at' => $req->created_at ? \Carbon\Carbon::parse($req->created_at)->format('d/m/Y H:i') : null,
                            'trip_type' => $req->trip_type,
                            'usage_date' => \Carbon\Carbon::parse($req->usage_date)->format('d/m/Y'),
                            'start_time' => $req->start_time,
                            'return_date' => $req->return_date ? \Carbon\Carbon::parse($req->return_date)->format('d/m/Y') : null,
                            'end_time' => $req->end_time,
                            'pickup_location' => $req->pickup_location,
                            'pickup_maps_link' => $req->pickup_maps_link,
                            'destination' => $req->destination,
                            'destination_maps_link' => $req->destination_maps_link,
                            'purpose' => $req->purpose,
                            'status' => $req->status,
                            'approver_l1' => $req->approverL1 ? ['name' => $req->approverL1->name, 'approved_l1_at' => $req->approved_l1_at ? \Carbon\Carbon::parse($req->approved_l1_at)->format('d/m/Y H:i') : null] : null,
                            'admin' => $req->admin ? ['name' => $req->admin->name, 'approved_admin_at' => $req->approved_admin_at ? \Carbon\Carbon::parse($req->approved_admin_at)->format('d/m/Y H:i') : null] : null,
                            'rejection_reason' => $req->rejection_reason,
                            'transport_type' => $req->transport_type,
                            'driver' => $req->driver ? ['name' => $req->driver->name, 'phone' => $req->driver->phone] : null,
                            'vehicle' => $req->vehicle ? ['type' => $req->vehicle->type, 'plate_number' => $req->vehicle->plate_number] : null,
                            'voucher' => $req->voucher ? ['code' => $req->voucher->code, 'type' => $req->voucher->type, 'nominal' => $req->voucher->nominal] : null,
                        ];
                    @endphp
                    <tr class="border-t {{ $loop->first ? '' : 'border-t-2 border-gray-300' }}">
                        <td class="px-3 pt-3 pb-1 font-mono font-semibold">{{ $req->request_no }}</td>
                        <td class="px-3 pt-3 pb-1">{{ $start }}</td>
                        <td class="px-3 pt-3 pb-1">{{ $req->pickup_location }}</td>
                        <td class="px-3 pt-3 pb-1 text-right">
                            <button @click="openDetailModal({{ json_encode($detailData) }})" class="text-blue-600 font-semibold">Detail</button>
                        </td>
                    </tr>
                    <tr class="bg-gray-50 border-b">
                        <td class="px-3 pb-3 pt-0 text-gray-500 text-[11px]">
                            {{ $req->trip_type === 'round_trip' ? 'Pulang Pergi' : 'Sekali Jalan' }}
                        </td>
                        <td class="px-3 pb-3 pt-0 text-gray-500 text-[11px]">
                            {{ $end ?? '-' }}
                        </td>
                        <td class="px-3 pb-3 pt-0 text-gray-500 text-[11px]">
                            {{ $req->destination }}
                        </td>
                        <td class="px-3 pb-3 pt-0 text-right">
                            <span class="text-[11px] font-semibold {{ $statusColors[$req->status] ?? 'bg-gray-100 text-gray-800' }} px-2 py-1 rounded-full">
                                {{ $statusLabels[$req->status] ?? $req->status }}
                            </span>
                        </td>
                    </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-6 text-center text-gray-500">Belum ada history</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($historyRequests->hasPages())
        <div class="px-6 py-3 border-t">
            {{ $historyRequests->links() }}
        </div>
        @endif
    </div>

    {{-- MODAL REJECT --}}
    <div x-show="rejectModalOpen" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50" x-cloak>
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h2 class="text-lg font-semibold mb-4">Tolak Permintaan</h2>
            <form :action="`{{ url('drms/approval/admin') }}/${rejectRequestId}/reject`" method="POST">
                @csrf
                @method('PUT')
                <textarea name="rejection_reason" rows="3" class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Masukkan alasan penolakan..." required></textarea>
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" @click="rejectModalOpen = false" class="px-4 py-2 bg-gray-200 rounded-lg text-sm">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded-lg text-sm">Tolak</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL DETAIL --}}
    <div x-show="detailModalOpen" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50" x-cloak>
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-semibold mb-4 border-b pb-2">Detail Permintaan</h3>
            <table class="w-full text-sm border-collapse">
                <tbody>
                    <tr class="border-b border-gray-100"><td class="py-2 w-1/3 text-gray-500 font-medium">No. Request</td><td class="py-2 font-medium" x-text="detailItem.request_no"></td></tr>
                    <tr class="border-b border-gray-100"><td class="py-2 text-gray-500 font-medium">Pemohon</td><td class="py-2"><span x-text="detailItem.requester?.name ?? '-'"></span><span x-show="detailItem.created_at" class="text-gray-400 text-xs ml-1" x-text="'(' + detailItem.created_at + ')'"></span></td></tr>
                    <tr class="border-b border-gray-100"><td class="py-2 text-gray-500 font-medium">Tipe Perjalanan</td><td class="py-2" x-text="detailItem.trip_type === 'round_trip' ? 'Pulang Pergi' : 'Sekali Jalan'"></td></tr>
                    <tr class="border-b border-gray-100"><td class="py-2 text-gray-500 font-medium">Tanggal &amp; Jam</td>
                        <td class="py-2">
                            <template x-if="detailItem.trip_type === 'round_trip' && detailItem.return_date">
                                <span x-text="detailItem.usage_date + ' ' + detailItem.start_time + ' sampai ' + detailItem.return_date + ' ' + detailItem.end_time"></span>
                            </template>
                            <template x-if="detailItem.trip_type !== 'round_trip' || !detailItem.return_date">
                                <span x-text="detailItem.usage_date + ' ' + detailItem.start_time + ' - ' + detailItem.end_time"></span>
                            </template>
                        </td>
                    </tr>
                    <tr class="border-b border-gray-100"><td class="py-2 text-gray-500 font-medium">Lokasi Penjemputan</td><td class="py-2" x-text="detailItem.pickup_location"></td></tr>
                    <template x-if="detailItem.pickup_maps_link"><tr class="border-b border-gray-100"><td class="py-2 text-gray-500 font-medium">Link Maps Penjemputan</td><td class="py-2"><a :href="detailItem.pickup_maps_link" target="_blank" class="text-blue-600 underline">Buka Maps</a></td></tr></template>
                    <tr class="border-b border-gray-100"><td class="py-2 text-gray-500 font-medium">Tujuan</td><td class="py-2" x-text="detailItem.destination"></td></tr>
                    <template x-if="detailItem.destination_maps_link"><tr class="border-b border-gray-100"><td class="py-2 text-gray-500 font-medium">Link Maps Tujuan</td><td class="py-2"><a :href="detailItem.destination_maps_link" target="_blank" class="text-blue-600 underline">Buka Maps</a></td></tr></template>
                    <tr class="border-b border-gray-100"><td class="py-2 text-gray-500 font-medium">Keperluan</td><td class="py-2" x-text="detailItem.purpose ?? '-'"></td></tr>
                    <tr class="border-b border-gray-100"><td class="py-2 text-gray-500 font-medium">Status</td>
                        <td class="py-2">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold" :class="{
                                'bg-yellow-100 text-yellow-800': detailItem.status === 'pending_l1',
                                'bg-blue-100 text-blue-800': detailItem.status === 'approved_l1',
                                'bg-red-100 text-red-800': detailItem.status === 'rejected_l1' || detailItem.status === 'rejected_admin',
                                'bg-green-100 text-green-800': detailItem.status === 'approved_admin',
                                'bg-gray-100 text-gray-800': detailItem.status === 'completed'
                            }" x-text="{
                                'pending_l1': 'Pending L1',
                                'approved_l1': 'Disetujui Atasan',
                                'rejected_l1': 'Ditolak Atasan',
                                'approved_admin': 'Disetujui GA',
                                'rejected_admin': 'Ditolak GA',
                                'completed': 'Selesai'
                            }[detailItem.status] || detailItem.status"></span>
                        </td>
                    </tr>
                    <template x-if="detailItem.approver_l1"><tr class="border-b border-gray-100"><td class="py-2 text-gray-500 font-medium">Proses L1</td><td class="py-2" x-text="detailItem.approver_l1.name + (detailItem.approver_l1.approved_l1_at ? ' (' + detailItem.approver_l1.approved_l1_at + ')' : '')"></td></tr></template>
                    <template x-if="detailItem.admin"><tr class="border-b border-gray-100"><td class="py-2 text-gray-500 font-medium">Proses GA</td><td class="py-2" x-text="detailItem.admin.name + (detailItem.admin.approved_admin_at ? ' (' + detailItem.admin.approved_admin_at + ')' : '')"></td></tr></template>
                    <template x-if="detailItem.rejection_reason"><tr class="border-b border-gray-100"><td class="py-2 text-gray-500 font-medium">Catatan</td><td class="py-2 text-red-600" x-text="detailItem.rejection_reason"></td></tr></template>
                    <template x-if="detailItem.transport_type"><tr class="border-b border-gray-100"><td class="py-2 text-gray-500 font-medium">Jenis Transportasi</td><td class="py-2" x-text="detailItem.transport_type == 'company_driver' ? 'Driver Perusahaan' : (detailItem.transport_type == 'voucher' ? 'Voucher' : 'Rental')"></td></tr></template>
                    <template x-if="detailItem.driver"><tr class="border-b border-gray-100"><td class="py-2 text-gray-500 font-medium">Driver</td><td class="py-2" x-text="detailItem.driver.name + ' (' + (detailItem.driver.phone || '-') + ')'"></td></tr></template>
                    <template x-if="detailItem.vehicle"><tr class="border-b border-gray-100"><td class="py-2 text-gray-500 font-medium">Kendaraan</td><td class="py-2" x-text="detailItem.vehicle.type + ' - ' + detailItem.vehicle.plate_number"></td></tr></template>
                    <template x-if="detailItem.voucher"><tr class="border-b border-gray-100"><td class="py-2 text-gray-500 font-medium">Voucher</td><td class="py-2" x-text="detailItem.voucher.code + ' (' + detailItem.voucher.type + ') Rp ' + new Intl.NumberFormat('id-ID').format(detailItem.voucher.nominal)"></td></tr></template>
                </tbody>
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

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('toggleFilterBtn')?.addEventListener('click', () => {
        document.getElementById('filterSection').classList.toggle('hidden');
    });
});
</script>
@endsection