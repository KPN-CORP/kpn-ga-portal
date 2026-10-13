@extends('layouts.app_car_drive_sidebar')

@section('content')
<div class="max-w-3xl mx-auto bg-white p-6 rounded-lg shadow my-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Detail {{ $type === 'repair' ? 'Perbaikan' : 'Servis Rutin' }}</h1>
        <a href="{{ route('drms.driver.service.index') }}" class="text-blue-600 hover:underline">← Kembali</a>
    </div>

    <dl class="grid grid-cols-2 gap-4">
        <div>
            <dt class="text-sm text-gray-500">Kendaraan</dt>
            <dd class="font-medium">{{ $item->vehicle->plate_number ?? '-' }} - {{ $item->vehicle->type ?? '' }}</dd>
        </div>

        @if($type === 'service')
            <div>
                <dt class="text-sm text-gray-500">Rencana Tanggal Servis</dt>
                <dd class="font-medium">{{ $item->service_date->format('d M Y') }}</dd>
            </div>
            <div class="col-span-2">
                <dt class="text-sm text-gray-500">Jenis Servis</dt>
                <dd class="font-medium">{{ collect($item->service_type ?? [])->map(fn($t) => ucfirst(str_replace('_', ' ', $t)))->implode(', ') }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Odometer</dt>
                <dd class="font-medium">{{ $item->odometer_at_service !== null ? number_format($item->odometer_at_service, 0, ',', '.') . ' km' : '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Bengkel</dt>
                <dd class="font-medium">{{ $item->workshop_name ?? '-' }}</dd>
            </div>
            @if($item->status === 'done')
                <div>
                    <dt class="text-sm text-gray-500">Biaya</dt>
                    <dd class="font-medium">Rp {{ number_format($item->cost, 0, ',', '.') }}</dd>
                </div>
            @endif
        @else
            <div>
                <dt class="text-sm text-gray-500">Tanggal Laporan</dt>
                <dd class="font-medium">{{ $item->report_date->format('d M Y') }}</dd>
            </div>
            <div class="col-span-2">
                <dt class="text-sm text-gray-500">Keluhan</dt>
                <dd class="font-medium whitespace-pre-line">{{ $item->complaint }}</dd>
            </div>
            @if($item->status === 'done')
                <div class="col-span-2">
                    <dt class="text-sm text-gray-500">Diagnosa Bengkel</dt>
                    <dd class="font-medium whitespace-pre-line">{{ $item->diagnosis ?? '-' }}</dd>
                </div>
            @endif
        @endif

        <div class="col-span-2">
            <dt class="text-sm text-gray-500">Catatan</dt>
            <dd class="font-medium whitespace-pre-line">{{ $item->notes ?? '-' }}</dd>
        </div>
    </dl>

    @include('drms.partials.service-flow-panel', ['type' => $type, 'item' => $item])
</div>
@endsection
