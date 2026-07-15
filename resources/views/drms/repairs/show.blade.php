@extends('layouts.app_car_sidebar')

@section('content')
<div class="max-w-3xl mx-auto bg-white p-6 rounded-lg shadow">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Detail Perbaikan</h1>
        <a href="{{ route('drms.repairs.index') }}" class="text-blue-600 hover:underline">← Kembali</a>
    </div>

    <dl class="grid grid-cols-2 gap-4">
        <div><dt class="text-sm text-gray-500">Kendaraan</dt><dd class="font-medium">{{ $repair->vehicle->plate_number }} - {{ $repair->vehicle->type }}</dd></div>
        <div><dt class="text-sm text-gray-500">Tanggal Laporan</dt><dd class="font-medium">{{ $repair->report_date->format('d M Y') }}</dd></div>
        <div class="col-span-2"><dt class="text-sm text-gray-500">Keluhan</dt><dd class="font-medium">{{ $repair->complaint }}</dd></div>
        <div class="col-span-2"><dt class="text-sm text-gray-500">Diagnosa Bengkel</dt><dd class="font-medium">{{ $repair->diagnosis ?? '-' }}</dd></div>
        <div class="col-span-2"><dt class="text-sm text-gray-500">Sparepart Diganti</dt><dd class="font-medium">{{ $repair->parts_replaced ?? '-' }}</dd></div>
        <div><dt class="text-sm text-gray-500">Biaya Jasa</dt><dd class="font-medium">Rp {{ number_format($repair->labor_cost, 0, ',', '.') }}</dd></div>
        <div><dt class="text-sm text-gray-500">Biaya Sparepart</dt><dd class="font-medium">Rp {{ number_format($repair->parts_cost, 0, ',', '.') }}</dd></div>
        <div><dt class="text-sm text-gray-500">Total Biaya</dt><dd class="font-medium text-red-600">Rp {{ number_format($repair->total_cost, 0, ',', '.') }}</dd></div>
        <div><dt class="text-sm text-gray-500">Status</dt>
            <dd>
                <span class="px-2 py-1 rounded-full text-xs 
                    @if($repair->status == 'open') bg-yellow-100 text-yellow-800
                    @elseif($repair->status == 'progress') bg-blue-100 text-blue-800
                    @else bg-green-100 text-green-800 @endif">
                    {{ ucfirst($repair->status) }}
                </span>
            </dd>
        </div>
        <div class="col-span-2"><dt class="text-sm text-gray-500">Catatan</dt><dd class="font-medium">{{ $repair->notes ?? '-' }}</dd></div>
        <div><dt class="text-sm text-gray-500">Dilaporkan Oleh</dt><dd class="font-medium">{{ $repair->reporter->name ?? '-' }}</dd></div>
        @if($repair->completed_at)
        <div><dt class="text-sm text-gray-500">Selesai Pada</dt><dd class="font-medium">{{ $repair->completed_at->format('d M Y H:i') }}</dd></div>
        @endif
    </dl>
</div>
@endsection