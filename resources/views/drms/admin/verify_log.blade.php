@extends('layouts.app_car_sidebar')

@section('content')
<div class="max-w-3xl mx-auto bg-white p-6 rounded-lg shadow">
    <h1 class="text-xl font-bold">Verifikasi Log</h1>
    <p>Request: {{ $log->request->request_no }}</p>
    <p>Driver: {{ $log->request->driver->name ?? '-' }}</p>
    <p>Odometer Start: {{ $log->odometer_start }} km</p>
    <p>Odometer Finish: {{ $log->odometer_finish }} km</p>
    <p>Jarak: {{ $log->distance }} km</p>
    <p>Efisiensi: {{ $log->efficiency }} {{ $log->fuel_type=='listrik'?'km/kWh':'km/liter' }}</p>
    <p>Total Biaya BBM: Rp {{ number_format($log->fuel_cost,0,',','.') }}</p>

    <div class="grid grid-cols-3 gap-2 my-4">
        @if($log->photo_before)
            <a href="{{ route('drms.private.image', $log->photo_before) }}" target="_blank" class="text-blue-600">📷 Foto Before</a>
        @endif
        @if($log->photo_after)
            <a href="{{ route('drms.private.image', $log->photo_after) }}" target="_blank" class="text-blue-600">📷 Foto After</a>
        @endif
        @if($log->photo_fuel_receipt)
            <a href="{{ route('drms.private.image', $log->photo_fuel_receipt) }}" target="_blank" class="text-blue-600">📷 Foto Struk</a>
        @endif
    </div>

    <form method="POST" action="{{ route('drms.admin.verify.log.post', $log->id) }}">
        @csrf
        <div class="mb-4">
            <label class="block text-sm font-medium">Catatan Verifikasi</label>
            <textarea name="verification_notes" rows="3" class="w-full border rounded p-2">{{ old('verification_notes', $log->verification_notes) }}</textarea>
        </div>
        <div class="flex gap-2">
            <button type="submit" name="action" value="approve" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Setujui</button>
            <button type="submit" name="action" value="reject" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Tolak / Minta Revisi</button>
            <a href="{{ route('drms.admin.monitoring.logs') }}" class="px-4 py-2 bg-gray-300 rounded">Kembali</a>
        </div>
    </form>
</div>
@endsection