@extends('layouts.app_car_drive_sidebar')

@section('content')
<div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow">
    <h1 class="text-2xl font-bold mb-4">📝 Log Perjalanan #{{ $request->request_no }}</h1>
    <p class="text-gray-600 mb-4">Driver: {{ $request->driver->name ?? '-' }}</p>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
    @endif
    @if(session('info'))
        <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-4">{{ session('info') }}</div>
    @endif
    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- NOTIFIKASI REVISI DARI ADMIN --}}
    @if($log && $log->needsRevision() && $log->revision_note)
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 rounded">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-yellow-800">📌 Catatan Revisi dari Admin:</p>
                    <p class="text-sm text-yellow-700 mt-1">{{ $log->revision_note }}</p>
                    <p class="text-xs text-yellow-600 mt-2">Silakan perbaiki data di bawah ini, lalu kirim ulang.</p>
                </div>
            </div>
        </div>
    @endif

    {{-- INDIKATOR STATUS LOG --}}
    @if($log)
        <div class="mb-4 p-3 rounded-lg {{ $log->is_verified ? 'bg-green-100 border border-green-400' : ($log->is_submitted ? 'bg-blue-100 border border-blue-400' : 'bg-gray-100 border border-gray-400') }}">
            <p class="text-sm font-medium">
                Status Log: 
                @if($log->is_verified)
                    <span class="text-green-700">✅ Sudah Diverifikasi Admin</span>
                    <span class="text-xs text-gray-500 ml-2">(Tidak dapat diubah)</span>
                @elseif($log->is_submitted)
                    <span class="text-blue-700">⏳ Menunggu Verifikasi Admin</span>
                    <span class="text-xs text-gray-500 ml-2">(Tidak dapat diubah sampai admin memproses)</span>
                @elseif($log->needsRevision())
                    <span class="text-yellow-700">⚠️ Perlu Revisi - Silakan perbaiki dan kirim ulang</span>
                @else
                    <span class="text-gray-700">📝 Draft - Belum dikirim</span>
                @endif
            </p>
        </div>
    @endif

    <form method="POST" action="{{ route('drms.driver.trip.log.store', $request->id) }}" enctype="multipart/form-data">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Odometer Start (km)</label>
                <input type="number" name="odometer_start" value="{{ old('odometer_start', $log->odometer_start ?? '') }}"
                       class="w-full border rounded-lg px-3 py-2 mt-1 focus:ring-blue-500 focus:border-blue-500" min="0"
                       {{ ($log && ($log->is_verified || $log->is_submitted)) ? 'disabled' : '' }}>
                @if($log && ($log->is_verified || $log->is_submitted))
                    <p class="text-xs text-gray-400 mt-1">Field tidak dapat diubah karena log sudah dikirim/diverifikasi.</p>
                @endif
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Odometer Finish (km)</label>
                <input type="number" name="odometer_finish" value="{{ old('odometer_finish', $log->odometer_finish ?? '') }}"
                       class="w-full border rounded-lg px-3 py-2 mt-1 focus:ring-blue-500 focus:border-blue-500" min="0"
                       {{ ($log && ($log->is_verified || $log->is_submitted)) ? 'disabled' : '' }}>
                @if($log && ($log->is_verified || $log->is_submitted))
                    <p class="text-xs text-gray-400 mt-1">Field tidak dapat diubah karena log sudah dikirim/diverifikasi.</p>
                @endif
            </div>
        </div>

        <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700">📷 Foto Speedometer Sebelum</label>
            <input type="file" name="photo_before" accept="image/*" class="w-full border rounded-lg px-3 py-2 mt-1"
                   {{ ($log && ($log->is_verified || $log->is_submitted)) ? 'disabled' : '' }}>
            @if($log && $log->photo_before)
                <a href="{{ route('drms.private.image', $log->photo_before) }}" target="_blank" class="text-blue-600 text-sm">Lihat foto saat ini</a>
            @endif
            @if($log && ($log->is_verified || $log->is_submitted))
                <p class="text-xs text-gray-400 mt-1">Tidak dapat upload ulang karena log sudah dikirim/diverifikasi.</p>
            @endif
        </div>
        <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700">📷 Foto Speedometer Sesudah</label>
            <input type="file" name="photo_after" accept="image/*" class="w-full border rounded-lg px-3 py-2 mt-1"
                   {{ ($log && ($log->is_verified || $log->is_submitted)) ? 'disabled' : '' }}>
            @if($log && $log->photo_after)
                <a href="{{ route('drms.private.image', $log->photo_after) }}" target="_blank" class="text-blue-600 text-sm">Lihat foto saat ini</a>
            @endif
            @if($log && ($log->is_verified || $log->is_submitted))
                <p class="text-xs text-gray-400 mt-1">Tidak dapat upload ulang karena log sudah dikirim/diverifikasi.</p>
            @endif
        </div>

        <hr class="my-6">

        <h3 class="font-semibold text-lg mb-3">⛽ Pengisian BBM / Charge</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Jenis Pengisian</label>
                <select name="fuel_type" class="w-full border rounded-lg px-3 py-2 mt-1"
                        {{ ($log && ($log->is_verified || $log->is_submitted)) ? 'disabled' : '' }}>
                    <option value="">Pilih</option>
                    <option value="bensin" {{ old('fuel_type', $log->fuel_type ?? '')=='bensin'?'selected':'' }}>Bensin</option>
                    <option value="listrik" {{ old('fuel_type', $log->fuel_type ?? '')=='listrik'?'selected':'' }}>Listrik</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Volume (Liter / kWh)</label>
                <input type="number" name="fuel_volume" step="0.01" value="{{ old('fuel_volume', $log->fuel_volume ?? '') }}"
                       class="w-full border rounded-lg px-3 py-2 mt-1" min="0"
                       {{ ($log && ($log->is_verified || $log->is_submitted)) ? 'disabled' : '' }}>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Harga per Unit (Rp)</label>
                <input type="number" name="fuel_price_per_unit" step="0.01" value="{{ old('fuel_price_per_unit', $log->fuel_price_per_unit ?? '') }}"
                       class="w-full border rounded-lg px-3 py-2 mt-1" min="0"
                       {{ ($log && ($log->is_verified || $log->is_submitted)) ? 'disabled' : '' }}>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Total Biaya (Rp) <span class="text-xs text-gray-400">(otomatis jika volume & harga diisi)</span></label>
                <input type="number" name="fuel_cost" step="0.01" value="{{ old('fuel_cost', $log->fuel_cost ?? '') }}"
                       class="w-full border rounded-lg px-3 py-2 mt-1" min="0"
                       {{ ($log && ($log->is_verified || $log->is_submitted)) ? 'disabled' : '' }}>
            </div>
        </div>

        <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700">📷 Foto Struk BBM / Charge</label>
            <input type="file" name="photo_fuel_receipt" accept="image/*" class="w-full border rounded-lg px-3 py-2 mt-1"
                   {{ ($log && ($log->is_verified || $log->is_submitted)) ? 'disabled' : '' }}>
            @if($log && $log->photo_fuel_receipt)
                <a href="{{ route('drms.private.image', $log->photo_fuel_receipt) }}" target="_blank" class="text-blue-600 text-sm">Lihat foto saat ini</a>
            @endif
            @if($log && ($log->is_verified || $log->is_submitted))
                <p class="text-xs text-gray-400 mt-1">Tidak dapat upload ulang karena log sudah dikirim/diverifikasi.</p>
            @endif
        </div>

        <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700">📝 Catatan Tambahan</label>
            <textarea name="notes" rows="3" class="w-full border rounded-lg px-3 py-2 mt-1"
                      {{ ($log && ($log->is_verified || $log->is_submitted)) ? 'disabled' : '' }}>{{ old('notes', $log->notes ?? '') }}</textarea>
            @if($log && ($log->is_verified || $log->is_submitted))
                <p class="text-xs text-gray-400 mt-1">Catatan tidak dapat diubah karena log sudah dikirim/diverifikasi.</p>
            @endif
        </div>

        <div class="mt-6 flex justify-between items-center">
            <a href="{{ route('drms.driver.dashboard') }}" class="px-4 py-2 bg-gray-300 rounded-lg hover:bg-gray-400">← Kembali</a>
            <div class="space-x-2">
                @if(!$log || (!$log->is_verified && !$log->is_submitted))
                    <button type="submit" name="submit" value="0" class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600">
                        💾 Simpan Draft
                    </button>
                    <button type="submit" name="submit" value="1" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        {{ $log && $log->needsRevision() ? '📤 Kirim Ulang' : '📤 Kirim ke Admin' }}
                    </button>
                @else
                    <span class="text-sm text-gray-500 italic">Log sudah {{ $log->is_verified ? 'diverifikasi' : 'dikirim' }}, tidak dapat diubah.</span>
                @endif
            </div>
        </div>
    </form>
</div>
@endsection