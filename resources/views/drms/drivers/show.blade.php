@extends('layouts.app-sidebar')

@section('content')
<div class="max-w-3xl mx-auto bg-white p-6 rounded-lg shadow my-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Detail Permintaan #{{ $driverRequest->request_no }}</h1>
        <a href="{{ route('drms.driver.dashboard') }}" class="text-blue-600 hover:underline">← Kembali ke Dashboard</a>
    </div>

    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div><dt class="text-gray-500">Pemohon</dt><dd>{{ $driverRequest->requester->name ?? '-' }}</dd></div>
        <div><dt class="text-gray-500">Tipe Perjalanan</dt><dd>{{ $driverRequest->trip_type == 'round_trip' ? 'Pulang Pergi' : 'Sekali Jalan' }}</dd></div>
        <div><dt class="text-gray-500">Tanggal Penggunaan</dt><dd>{{ \Carbon\Carbon::parse($driverRequest->usage_date)->format('d M Y') }}</dd></div>
        <div><dt class="text-gray-500">Jam Berangkat</dt><dd>{{ $driverRequest->start_time }}</dd></div>
        <div><dt class="text-gray-500">Perkiraan Jam Selesai</dt><dd>{{ $driverRequest->end_time ?? '-' }}</dd></div>

        @if($driverRequest->trip_type == 'round_trip' && $driverRequest->return_date)
            <div><dt class="text-gray-500">Tanggal Kembali</dt><dd>{{ \Carbon\Carbon::parse($driverRequest->return_date)->format('d M Y') }}</dd></div>
            <div><dt class="text-gray-500">Perkiraan Jam Kembali</dt><dd>{{ $driverRequest->return_time ?? $driverRequest->end_time }}</dd></div>
        @endif

        <div class="col-span-2"><dt class="text-gray-500">Lokasi Penjemputan</dt><dd>{{ $driverRequest->pickup_location }}</dd></div>
        <div class="col-span-2"><dt class="text-gray-500">Tujuan</dt><dd>{{ $driverRequest->destination }}</dd></div>
        <div class="col-span-2"><dt class="text-gray-500">Keperluan</dt><dd>{{ $driverRequest->purpose ?? '-' }}</dd></div>

        @if($driverRequest->vehicle)
            <div><dt class="text-gray-500">Kendaraan</dt><dd>{{ $driverRequest->vehicle->type }} - {{ $driverRequest->vehicle->plate_number }}</dd></div>
        @endif
        @if($driverRequest->voucher)
            <div><dt class="text-gray-500">Voucher</dt><dd>{{ $driverRequest->voucher->code }} ({{ ucfirst($driverRequest->voucher->type) }}) - Rp {{ number_format($driverRequest->voucher->nominal,0,',','.') }}</dd></div>
        @endif

        <div><dt class="text-gray-500">Status</dt><dd>
            @php
                $statusText = [
                    'pending_l1' => 'Menunggu Atasan',
                    'approved_l1' => 'Disetujui Atasan',
                    'rejected_l1' => 'Ditolak Atasan',
                    'approved_admin' => 'Disetujui Admin',
                    'rejected_admin' => 'Ditolak Admin',
                    'completed' => 'Selesai'
                ][$driverRequest->status] ?? ucfirst($driverRequest->status);
            @endphp
            {{ $statusText }}
        </dd></div>

        @if($driverRequest->rejection_reason)
            <div class="col-span-2"><dt class="text-gray-500">Catatan</dt><dd class="text-red-600">{{ $driverRequest->rejection_reason }}</dd></div>
        @endif

        @if($driverRequest->pickup_maps_link)
            <div class="col-span-2">
                <dt class="text-gray-500">Link Maps Penjemputan</dt>
                <dd><a href="{{ $driverRequest->pickup_maps_link }}" target="_blank" class="text-blue-600">Buka Google Maps</a></dd>
            </div>
        @endif
        @if($driverRequest->destination_maps_link)
            <div class="col-span-2">
                <dt class="text-gray-500">Link Maps Tujuan</dt>
                <dd><a href="{{ $driverRequest->destination_maps_link }}" target="_blank" class="text-blue-600">Buka Google Maps</a></dd>
            </div>
        @endif
    </dl>
</div>
@endsection