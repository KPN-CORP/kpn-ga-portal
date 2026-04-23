@extends('layouts.app_car_sidebar')

@section('content')
<div class="space-y-6 text-sm text-gray-800 font-sans" 
     x-data="requestModal()" 
     x-init="if({{ $errors->any() ? 'true' : 'false' }}) createModalOpen = true">
     
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">My Driver Requests</h2>
            <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                Personal Requests
            </span>
        </div>
        <div class="flex gap-2 w-full sm:w-auto">
            <button @click="openCreateModal()" class="flex-1 sm:flex-none inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 transition">
                + Buat Permintaan
            </button>
            <button id="toggleFilterBtn" class="flex-1 sm:flex-none px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-200 transition">
                Filters
            </button>
        </div>
    </div>

    {{-- Filter --}}
    <div id="filterSection" class="bg-white border rounded-xl p-4 hidden">
        <form method="GET" action="{{ route('drms.requests.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <label class="text-sm font-medium text-gray-600">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari tujuan / no. request" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Status</label>
                <select name="status" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm">
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
                <input type="date" name="dari" value="{{ request('dari') }}" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Sampai Tanggal</label>
                <input type="date" name="sampai" value="{{ request('sampai') }}" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm">
            </div>
            <div class="lg:col-span-3 flex flex-col sm:flex-row gap-2 justify-end">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700">Apply</button>
                <a href="{{ route('drms.requests.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-300 text-center">Reset</a>
            </div>
        </form>
    </div>

    {{-- TABEL DESKTOP --}}
    <div class="hidden md:block bg-white border rounded-xl overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">No. Request</th>
                    <th class="px-4 py-3 text-left">Tipe</th>
                    <th class="px-4 py-3 text-left">Tanggal</th>
                    <th class="px-4 py-3 text-left">Perjalanan</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($requests as $item)
                @php
                    $usageFormatted = \Carbon\Carbon::parse($item->usage_date)->format('d/m/Y') . ' ' . $item->start_time;
                    $returnFormatted = $item->return_date ? \Carbon\Carbon::parse($item->return_date)->format('d/m/Y') . ' ' . $item->end_time : null;
                    $tujuanFull = $item->pickup_location . ' → ' . $item->destination;
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
                    $detailData = [
                        'request_no' => $item->request_no,
                        'requester' => ['name' => $item->requester->name],
                        'created_at' => $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('d/m/Y H:i') : null,
                        'trip_type' => $item->trip_type,
                        'usage_date' => \Carbon\Carbon::parse($item->usage_date)->format('d/m/Y'),
                        'start_time' => $item->start_time,
                        'return_date' => $item->return_date ? \Carbon\Carbon::parse($item->return_date)->format('d/m/Y') : null,
                        'end_time' => $item->end_time,
                        'pickup_location' => $item->pickup_location,
                        'pickup_maps_link' => $item->pickup_maps_link,
                        'destination' => $item->destination,
                        'destination_maps_link' => $item->destination_maps_link,
                        'purpose' => $item->purpose,
                        'status' => $item->status,
                        'approver_l1' => $item->approverL1 ? ['name' => $item->approverL1->name, 'approved_l1_at' => $item->approved_l1_at ? \Carbon\Carbon::parse($item->approved_l1_at)->format('d/m/Y H:i') : null] : null,
                        'admin' => $item->admin ? ['name' => $item->admin->name, 'approved_admin_at' => $item->approved_admin_at ? \Carbon\Carbon::parse($item->approved_admin_at)->format('d/m/Y H:i') : null] : null,
                        'rejection_reason' => $item->rejection_reason,
                        'transport_type' => $item->transport_type,
                        'driver' => $item->driver ? ['name' => $item->driver->name, 'phone' => $item->driver->phone] : null,
                        'vehicle' => $item->vehicle ? ['type' => $item->vehicle->type, 'plate_number' => $item->vehicle->plate_number] : null,
                        'voucher' => $item->voucher ? ['code' => $item->voucher->code, 'type' => $item->voucher->type, 'nominal' => $item->voucher->nominal] : null,
                    ];
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-mono text-sm">{{ $item->request_no }}</td>
                    <td class="px-4 py-3">
                        @if($item->trip_type === 'round_trip')
                            <span class="px-2 py-1 rounded-full text-xs bg-purple-100 text-purple-800">Pulang Pergi</span>
                        @else
                            <span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-800">Sekali Jalan</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        {{ $usageFormatted }}
                        @if($item->trip_type === 'round_trip' && $item->return_date)
                            <br><span class="text-xs text-gray-500"> → {{ $returnFormatted }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <!-- <div class="font-medium">{{ $item->destination }}</div>
                        <div class="text-xs text-gray-500">{{ $tujuanFull }}</div> -->
                        <div class="font-medium">{{ $tujuanFull }}</div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $statusColors[$item->status] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ $statusLabels[$item->status] ?? ucfirst($item->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <button @click="openDetailModal({{ json_encode($detailData) }})" class="text-blue-600 font-semibold hover:underline">Detail</button>
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

    {{-- ========== MOBILE & TABLET (TABLE DENGAN PEMISAH GRUP) ========== --}}
    <div class="block md:hidden bg-white border rounded-xl overflow-hidden">
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
                @forelse($requests as $index => $item)
                @php
                    $start = \Carbon\Carbon::parse($item->usage_date)->format('d/m/Y') . ' ' . $item->start_time;
                    $end = $item->return_date 
                        ? \Carbon\Carbon::parse($item->return_date)->format('d/m/Y') . ' ' . $item->end_time 
                        : null;
                    $statusLabels = [
                        'pending_l1' => 'Pending L1',
                        'approved_l1' => 'Disetujui Atasan',
                        'rejected_l1' => 'Ditolak Atasan',
                        'approved_admin' => 'Disetujui GA',
                        'rejected_admin' => 'Ditolak GA',
                        'completed' => 'Selesai'
                    ];
                    $detailData = [ /* sama seperti di atas, isi ulang untuk mobile */ 
                        'request_no' => $item->request_no,
                        'requester' => ['name' => $item->requester->name],
                        'created_at' => $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('d/m/Y H:i') : null,
                        'trip_type' => $item->trip_type,
                        'usage_date' => \Carbon\Carbon::parse($item->usage_date)->format('d/m/Y'),
                        'start_time' => $item->start_time,
                        'return_date' => $item->return_date ? \Carbon\Carbon::parse($item->return_date)->format('d/m/Y') : null,
                        'end_time' => $item->end_time,
                        'pickup_location' => $item->pickup_location,
                        'pickup_maps_link' => $item->pickup_maps_link,
                        'destination' => $item->destination,
                        'destination_maps_link' => $item->destination_maps_link,
                        'purpose' => $item->purpose,
                        'status' => $item->status,
                        'approver_l1' => $item->approverL1 ? ['name' => $item->approverL1->name, 'approved_l1_at' => $item->approved_l1_at ? \Carbon\Carbon::parse($item->approved_l1_at)->format('d/m/Y H:i') : null] : null,
                        'admin' => $item->admin ? ['name' => $item->admin->name, 'approved_admin_at' => $item->approved_admin_at ? \Carbon\Carbon::parse($item->approved_admin_at)->format('d/m/Y H:i') : null] : null,
                        'rejection_reason' => $item->rejection_reason,
                        'transport_type' => $item->transport_type,
                        'driver' => $item->driver ? ['name' => $item->driver->name, 'phone' => $item->driver->phone] : null,
                        'vehicle' => $item->vehicle ? ['type' => $item->vehicle->type, 'plate_number' => $item->vehicle->plate_number] : null,
                        'voucher' => $item->voucher ? ['code' => $item->voucher->code, 'type' => $item->voucher->type, 'nominal' => $item->voucher->nominal] : null,
                    ];
                @endphp
                {{-- Baris 1 --}}
                <tr class="border-t {{ $loop->first ? '' : 'border-t-2 border-gray-300' }}">
                    <td class="px-3 pt-3 pb-1 font-mono font-semibold">{{ $item->request_no }}</td>
                    <td class="px-3 pt-3 pb-1">{{ $start }}</td>
                    <td class="px-3 pt-3 pb-1">{{ $item->pickup_location }}</td>
                    <td class="px-3 pt-3 pb-1 text-right">
                        <button @click="openDetailModal({{ json_encode($detailData) }})" class="text-blue-600 font-semibold">Detail</button>
                    </td>
                </tr>
                {{-- Baris 2 --}}
                <tr class="bg-gray-50 border-b">
                    <td class="px-3 pb-3 pt-0 text-gray-500 text-[11px]">
                        {{ $item->trip_type === 'round_trip' ? 'Pulang Pergi' : 'Sekali Jalan' }}
                    </td>
                    <td class="px-3 pb-3 pt-0 text-gray-500 text-[11px]">
                        {{ $end ?? '-' }}
                    </td>
                    <td class="px-3 pb-3 pt-0 text-gray-500 text-[11px]">
                        {{ $item->destination }}
                    </td>
                    <td class="px-3 pb-3 pt-0 text-right">
                        <span class="text-[11px] font-semibold text-gray-700">
                            {{ $statusLabels[$item->status] ?? $item->status }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="py-6 text-center text-gray-500">Belum ada data</td>
                </tr>
                @endforelse
            </tbody>
         </table>
    </div>

    @if($requests->hasPages())
        <div class="mt-4">{{ $requests->links() }}</div>
    @endif

    {{-- MODAL CREATE --}}
    <div x-show="createModalOpen" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50" x-cloak>
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-semibold mb-4">Buat Permintaan Driver</h3>
            @if($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <ul class="list-disc pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form method="POST" action="{{ route('drms.requests.store') }}">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Tipe Perjalanan</label>
                    <div class="flex space-x-4">
                        <label class="inline-flex items-center">
                            <input type="radio" name="trip_type" value="one_way" x-model="tripType" @change="toggleRoundTrip(false)" checked> Sekali Jalan
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="trip_type" value="round_trip" x-model="tripType" @change="toggleRoundTrip(true)"> Pulang Pergi
                        </label>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Tanggal Penggunaan</label>
                        <input type="date" name="usage_date" value="{{ old('usage_date') }}" class="w-full border rounded-lg px-3 py-2 text-sm" required>
                    </div>
                    <div x-show="isRoundTrip" x-cloak>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Tanggal Kembali</label>
                        <input type="date" name="return_date" value="{{ old('return_date') }}" class="w-full border rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Jam Berangkat</label>
                        <div class="flex gap-2">
                            <select name="start_hour" class="w-1/2 border rounded-lg px-3 py-2 text-sm" required>
                                <option value="">Jam</option>
                                @for ($i = 0; $i <= 23; $i++)
                                    <option value="{{ $i }}" {{ old('start_hour') == (string)$i ? 'selected' : '' }}>{{ sprintf('%02d', $i) }}</option>
                                @endfor
                            </select>
                            <select name="start_minute" class="w-1/2 border rounded-lg px-3 py-2 text-sm" required>
                                <option value="">Menit</option>
                                @for ($i = 0; $i <= 59; $i++)
                                    <option value="{{ $i }}" {{ old('start_minute') == (string)$i ? 'selected' : '' }}>{{ sprintf('%02d', $i) }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Jam Selesai (Perkiraan)</label>
                        <div class="flex gap-2">
                            <select name="end_hour" class="w-1/2 border rounded-lg px-3 py-2 text-sm" required>
                                <option value="">Jam</option>
                                @for ($i = 0; $i <= 23; $i++)
                                    <option value="{{ $i }}" {{ old('end_hour') == (string)$i ? 'selected' : '' }}>{{ sprintf('%02d', $i) }}</option>
                                @endfor
                            </select>
                            <select name="end_minute" class="w-1/2 border rounded-lg px-3 py-2 text-sm" required>
                                <option value="">Menit</option>
                                @for ($i = 0; $i <= 59; $i++)
                                    <option value="{{ $i }}" {{ old('end_minute') == (string)$i ? 'selected' : '' }}>{{ sprintf('%02d', $i) }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                </div>
                <!-- Lokasi Penjemputan -->
                <div class="mb-4 mt-4">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Lokasi Penjemputan</label>
                    <div class="flex gap-2">
                        <input type="text" name="pickup_location" id="pickup_location" value="{{ old('pickup_location') }}" class="flex-1 border rounded-lg px-3 py-2 text-sm" placeholder="Ketik alamat lengkap" required>
                        <button type="button" onclick="openMaps(document.getElementById('pickup_location').value)" class="bg-gray-200 hover:bg-gray-300 px-3 py-2 rounded-lg text-sm" title="Buka Google Maps">🗺️ Maps</button>
                    </div>
                    <div class="mt-2 text-gray-500 text-xs flex items-center gap-1">
                        <span>🔗</span>
                        <input type="url" name="pickup_maps_link" value="{{ old('pickup_maps_link') }}" class="flex-1 border rounded-lg px-3 py-2 text-sm bg-gray-50" placeholder="Link Google Maps (opsional)">
                    </div>
                </div>
                <!-- Tujuan -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Tujuan</label>
                    <div class="flex gap-2">
                        <input type="text" name="destination" id="destination" value="{{ old('destination') }}" class="flex-1 border rounded-lg px-3 py-2 text-sm" placeholder="Ketik alamat lengkap" required>
                        <button type="button" onclick="openMaps(document.getElementById('destination').value)" class="bg-gray-200 hover:bg-gray-300 px-3 py-2 rounded-lg text-sm" title="Buka Google Maps">🗺️ Maps</button>
                    </div>
                    <div class="mt-2 text-gray-500 text-xs flex items-center gap-1">
                        <span>🔗</span>
                        <input type="url" name="destination_maps_link" value="{{ old('destination_maps_link') }}" class="flex-1 border rounded-lg px-3 py-2 text-sm bg-gray-50" placeholder="Link Google Maps (opsional)">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Keperluan <span class="text-red-500">*</span></label>
                    <textarea name="purpose" rows="3" class="w-full border rounded-lg px-3 py-2 text-sm" required>{{ old('purpose') }}</textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="createModalOpen = false" class="px-4 py-2 bg-gray-200 rounded-lg">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Ajukan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL DETAIL (diperbaiki, tidak ada @forelse di dalamnya) --}}
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
function requestModal() {
    return {
        createModalOpen: false,
        detailModalOpen: false,
        detailItem: {},
        tripType: 'one_way',
        isRoundTrip: false,
        openCreateModal() { this.createModalOpen = true; },
        openDetailModal(item) {
            this.detailItem = item;
            this.detailModalOpen = true;
        },
        toggleRoundTrip(val) {
            this.isRoundTrip = val;
        }
    }
}

function openMaps(address) {
    if (address && address.trim() !== '') {
        window.open(`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}`, '_blank');
    } else {
        alert('Masukkan alamat terlebih dahulu.');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('toggleFilterBtn')?.addEventListener('click', () => {
        document.getElementById('filterSection').classList.toggle('hidden');
    });
});
</script>
@endsection