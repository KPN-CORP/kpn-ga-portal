@extends('layouts.app_car_sidebar')

@section('content')
<div class="max-w-3xl mx-auto bg-white p-6 rounded-lg shadow">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Detail Servis</h1>
        <a href="{{ route('drms.service-schedules.index') }}" class="text-blue-600 hover:underline">← Kembali</a>
    </div>

    <dl class="grid grid-cols-2 gap-4">
        <div><dt class="text-sm text-gray-500">Kendaraan</dt><dd class="font-medium">{{ $service->vehicle->plate_number }} - {{ $service->vehicle->type }}</dd></div>
        <div><dt class="text-sm text-gray-500">Tanggal Servis</dt><dd class="font-medium">{{ $service->service_date->format('d M Y') }}</dd></div>
        <div><dt class="text-sm text-gray-500">Odometer</dt><dd class="font-medium">{{ $service->odometer_at_service ?? '-' }} km</dd></div>
        <div><dt class="text-sm text-gray-500">Jenis Servis</dt><dd class="font-medium">{{ ucfirst(str_replace('_', ' ', $service->service_type)) }}</dd></div>
        <div><dt class="text-sm text-gray-500">Bengkel</dt><dd class="font-medium">{{ $service->workshop_name ?? '-' }}</dd></div>
        <div><dt class="text-sm text-gray-500">Biaya</dt><dd class="font-medium text-red-600">Rp {{ number_format($service->cost, 0, ',', '.') }}</dd></div>
        <div class="col-span-2"><dt class="text-sm text-gray-500">Servis Berikutnya</dt><dd class="font-medium">
            @if($service->next_service_date) {{ $service->next_service_date->format('d M Y') }}
            @elseif($service->next_service_odometer) {{ $service->next_service_odometer }} km
            @else - @endif
        </dd></div>
        <div class="col-span-2"><dt class="text-sm text-gray-500">Catatan</dt><dd class="font-medium">{{ $service->notes ?? '-' }}</dd></div>
        @if($service->invoice_file)
        <div class="col-span-2"><dt class="text-sm text-gray-500">Invoice</dt><dd><a href="{{ route('drms.private.image', $service->invoice_file) }}" target="_blank" class="text-blue-600 hover:underline">Lihat Invoice</a></dd></div>
        @endif
        <div class="col-span-2"><dt class="text-sm text-gray-500">Dibuat Oleh</dt><dd class="font-medium">{{ $service->creator->name ?? '-' }}</dd></div>
    </dl>
</div>
@endsection