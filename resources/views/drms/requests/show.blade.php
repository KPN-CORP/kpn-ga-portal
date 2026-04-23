@extends('layouts.app_car_sidebar')

@section('content')
<div class="max-w-3xl mx-auto bg-white p-6 rounded-lg shadow">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Detail Permintaan #{{ $driverRequest->request_no }}</h1>
        <a href="{{ url()->previous() }}" class="text-blue-600 hover:underline">Kembali</a>
    </div>

    @php
        $statusLabels = [
            'pending_l1' => 'Menunggu Approval Atasan',
            'approved_l1' => 'Disetujui Atasan',
            'rejected_l1' => 'Ditolak Atasan',
            'approved_admin' => 'Disetujui GA',
            'rejected_admin' => 'Ditolak GA',
            'completed' => 'Selesai',
        ];
        $statusColors = [
            'pending_l1' => 'bg-yellow-100 text-yellow-800',
            'approved_l1' => 'bg-blue-100 text-blue-800',
            'rejected_l1' => 'bg-red-100 text-red-800',
            'approved_admin' => 'bg-green-100 text-green-800',
            'rejected_admin' => 'bg-red-100 text-red-800',
            'completed' => 'bg-gray-100 text-gray-800',
        ];
    @endphp

    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="col-span-2 md:col-span-1">
            <dt class="text-sm text-gray-500">No. Request</dt>
            <dd class="font-medium">{{ $driverRequest->request_no }}</dd>
        </div>
        <div class="col-span-2 md:col-span-1">
            <dt class="text-sm text-gray-500">Status</dt>
            <dd>
                <span class="px-2 py-1 rounded-full text-xs {{ $statusColors[$driverRequest->status] ?? 'bg-gray-100' }}">
                    {{ $statusLabels[$driverRequest->status] ?? $driverRequest->status }}
                </span>
            </dd>
        </div>
        <div class="col-span-2 md:col-span-1">
            <dt class="text-sm text-gray-500">Tipe Perjalanan</dt>
            <dd class="font-medium">
                @if($driverRequest->trip_type === 'round_trip')
                    <span class="px-2 py-1 rounded-full text-xs bg-purple-100 text-purple-800">Pulang Pergi</span>
                @else
                    <span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-800">Sekali Jalan</span>
                @endif
            </dd>
        </div>
        <div class="col-span-2 md:col-span-1">
            <dt class="text-sm text-gray-500">Tanggal Penggunaan</dt>
            <dd class="font-medium">{{ \Carbon\Carbon::parse($driverRequest->usage_date)->format('d M Y') }}</dd>
        </div>
        <div class="col-span-2 md:col-span-1">
            <dt class="text-sm text-gray-500">Jam Berangkat</dt>
            <dd class="font-medium">{{ \Carbon\Carbon::parse($driverRequest->start_time)->format('H:i') }}</dd>
        </div>
        <div class="col-span-2 md:col-span-1">
            <dt class="text-sm text-gray-500">Jam Selesai (Perkiraan)</dt>
            <dd class="font-medium">{{ \Carbon\Carbon::parse($driverRequest->end_time)->format('H:i') }}</dd>
        </div>

        @if($driverRequest->trip_type === 'round_trip')
        <div class="col-span-2 md:col-span-1">
            <dt class="text-sm text-gray-500">Tanggal Kembali</dt>
            <dd class="font-medium">{{ \Carbon\Carbon::parse($driverRequest->return_date)->format('d M Y') }}</dd>
        </div>
        <div class="col-span-2 md:col-span-1">
            <dt class="text-sm text-gray-500">Perkiraan Jam Kembali</dt>
            <dd class="font-medium">{{ \Carbon\Carbon::parse($driverRequest->return_time)->format('H:i') }}</dd>
        </div>
        @endif

        <div class="col-span-2">
            <dt class="text-sm text-gray-500">Lokasi Penjemputan</dt>
            <dd class="font-medium">{{ $driverRequest->pickup_location }}</dd>
        </div>
        <div class="col-span-2">
            <dt class="text-sm text-gray-500">Tujuan</dt>
            <dd class="font-medium">{{ $driverRequest->destination }}</dd>
        </div>
        <div class="col-span-2">
            <dt class="text-sm text-gray-500">Keperluan</dt>
            <dd class="font-medium">{{ $driverRequest->purpose ?? '-' }}</dd>
        </div>
        <div class="col-span-2 md:col-span-1">
            <dt class="text-sm text-gray-500">Pemohon</dt>
            <dd class="font-medium">{{ $driverRequest->requester->name ?? '-' }}</dd>
        </div>
        @if($driverRequest->created_at)
        <div class="col-span-2 md:col-span-1">
            <dt class="text-sm text-gray-500">Tanggal Dibuat</dt>
            <dd class="font-medium">{{ \Carbon\Carbon::parse($driverRequest->created_at)->timezone('Asia/Jakarta')->format('d M Y H:i') }}</dd>
        </div>
        @endif
        @if($driverRequest->approverL1)
        <div class="col-span-2 md:col-span-1">
            <dt class="text-sm text-gray-500">Disetujui/Ditolak Oleh (L1)</dt>
            <dd class="font-medium">{{ $driverRequest->approverL1->name }}
                @if($driverRequest->approved_l1_at)
                    <span class="text-gray-500 text-xs">({{ \Carbon\Carbon::parse($driverRequest->approved_l1_at)->format('d M Y H:i') }})</span>
                @endif
            </dd>
        </div>
        @endif
        @if($driverRequest->admin)
        <div class="col-span-2 md:col-span-1">
            <dt class="text-sm text-gray-500">Diproses Oleh (Admin)</dt>
            <dd class="font-medium">{{ $driverRequest->admin->name }}
                @if($driverRequest->approved_admin_at)
                    <span class="text-gray-500 text-xs">({{ \Carbon\Carbon::parse($driverRequest->approved_admin_at)->format('d M Y H:i') }})</span>
                @endif
            </dd>
        </div>
        @endif
        @if($driverRequest->rejection_reason)
        <div class="col-span-2">
            <dt class="text-sm text-gray-500">Alasan Penolakan</dt>
            <dd class="text-red-600">{{ $driverRequest->rejection_reason }}</dd>
        </div>
        @endif
        @if($driverRequest->transport_type)
        <div class="col-span-2 md:col-span-1">
            <dt class="text-sm text-gray-500">Jenis Transportasi</dt>
            <dd class="font-medium">
                @if($driverRequest->transport_type == 'company_driver')
                    Driver Perusahaan
                @elseif($driverRequest->transport_type == 'voucher')
                    Voucher
                @else
                    Rental
                @endif
            </dd>
        </div>
        @endif
        @if($driverRequest->driver)
        <div class="col-span-2 md:col-span-1">
            <dt class="text-sm text-gray-500">Driver</dt>
            <dd class="font-medium">{{ $driverRequest->driver->name }} ({{ $driverRequest->driver->phone }})</dd>
        </div>
        @endif
        @if($driverRequest->vehicle)
        <div class="col-span-2 md:col-span-1">
            <dt class="text-sm text-gray-500">Kendaraan</dt>
            <dd class="font-medium">{{ $driverRequest->vehicle->type }} - {{ $driverRequest->vehicle->plate_number }}</dd>
        </div>
        @endif
        @if($driverRequest->voucher)
        <div class="col-span-2 md:col-span-1">
            <dt class="text-sm text-gray-500">Voucher</dt>
            <dd class="font-medium">{{ $driverRequest->voucher->code }} ({{ ucfirst($driverRequest->voucher->type) }}) - Rp {{ number_format($driverRequest->voucher->nominal,0,',','.') }}</dd>
        </div>
        @endif
    </dl>
</div>
@endsection