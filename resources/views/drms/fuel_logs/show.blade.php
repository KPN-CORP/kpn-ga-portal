@extends('layouts.app_car_sidebar')

@section('content')
<div class="max-w-3xl mx-auto bg-white p-6 rounded-lg shadow">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Detail Log @if($log->is_draft)<span class="ml-2 align-middle bg-amber-100 text-amber-800 px-2 py-1 rounded-full text-xs">📝 Draft</span>@endif</h1>
        <a href="{{ route('drms.fuel-logs.index') }}" class="text-blue-600 hover:underline">← Kembali</a>
    </div>

    <dl class="grid grid-cols-2 gap-4">
        <div><dt class="text-sm text-gray-500">Kendaraan</dt><dd class="font-medium">{{ $log->vehicle->plate_number }} - {{ $log->vehicle->type }}</dd></div>
        <div><dt class="text-sm text-gray-500">Driver</dt><dd class="font-medium">{{ $log->driver->name ?? '-' }}</dd></div>
        <div><dt class="text-sm text-gray-500">Tanggal Pengisian</dt><dd class="font-medium">{{ $log->filling_date->format('d M Y') }}</dd></div>
        <div><dt class="text-sm text-gray-500">Odometer Saat Ini</dt><dd class="font-medium">{{ number_format($log->odometer_start, 0, ',', '.') }} km</dd></div>
        <div><dt class="text-sm text-gray-500">Jenis Pengisian</dt><dd class="font-medium">{{ $log->vehicle->fuel_type ?? 'Bensin' }}</dd></div>
        <div><dt class="text-sm text-gray-500">Jumlah</dt><dd class="font-medium">{{ $log->fuel_liters !== null ? number_format($log->fuel_liters, 2, ',', '.') . ' ' . ($log->vehicle->fuel_type == 'Listrik' ? 'kWh' : 'Liter') : '-' }}</dd></div>
        <!-- <div><dt class="text-sm text-gray-500">Harga/unit (kalkulasi)</dt><dd class="font-medium">Rp {{ number_format($log->fuel_price_per_liter, 0, ',', '.') }}</dd></div> -->
        <div><dt class="text-sm text-gray-500">Total Biaya</dt><dd class="font-medium text-red-600">{{ $log->total_cost !== null ? 'Rp ' . number_format($log->total_cost, 0, ',', '.') : '-' }}</dd></div>
        <div><dt class="text-sm text-gray-500">Status</dt>
            <dd>
                @if($log->is_draft)
                    <span class="bg-amber-100 text-amber-800 px-2 py-1 rounded-full text-xs">📝 Draft</span>
                @elseif($log->is_verified)
                    <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">✅ Terverifikasi</span>
                @else
                    <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs">⏳ Pending</span>
                @endif
            </dd>
        </div>
        @if($log->is_verified)
        <div><dt class="text-sm text-gray-500">Diverifikasi Oleh</dt><dd class="font-medium">{{ $log->verifier->name ?? '-' }}</dd></div>
        <div><dt class="text-sm text-gray-500">Diverifikasi Pada</dt><dd class="font-medium">{{ $log->verified_at ? $log->verified_at->format('d M Y H:i') : '-' }}</dd></div>
        @endif
        <div class="col-span-2"><dt class="text-sm text-gray-500">Catatan</dt><dd class="font-medium">{{ $log->notes ?? '-' }}</dd></div>
        <div><dt class="text-sm text-gray-500">Foto Km dan Indikator BBM/Baterai Sebelum Pengisian</dt>
            <dd>
                @if($log->photo_dashboard_before)
                    <a href="{{ route('drms.private.image', $log->photo_dashboard_before) }}" target="_blank" class="text-blue-600 hover:underline">Lihat Foto</a>
                @else
                    <span class="font-medium">-</span>
                @endif
            </dd>
        </div>
        <div><dt class="text-sm text-gray-500">Foto Km dan Indikator BBM/Baterai Setelah Pengisian</dt>
            <dd>
                @if($log->photo_dashboard_after)
                    <a href="{{ route('drms.private.image', $log->photo_dashboard_after) }}" target="_blank" class="text-blue-600 hover:underline">Lihat Foto</a>
                @else
                    <span class="font-medium">-</span>
                @endif
            </dd>
        </div>
        @if($log->receipt_file)
        <div class="col-span-2"><dt class="text-sm text-gray-500">Struk</dt><dd><a href="{{ route('drms.private.image', $log->receipt_file) }}" target="_blank" class="text-blue-600 hover:underline">Lihat Struk</a></dd></div>
        @endif
    </dl>

    @if($log->is_draft)
        <div class="mt-6 flex justify-end">
            <a href="{{ route('drms.fuel-logs.edit', $log->id) }}" class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-lg text-sm font-semibold">Lengkapi Pengisian</a>
        </div>
    @endif
</div>
@endsection