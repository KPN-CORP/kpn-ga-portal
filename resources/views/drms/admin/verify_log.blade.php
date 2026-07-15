@extends('layouts.app_car_sidebar')

@section('content')
<div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow">
    <h1 class="text-2xl font-bold mb-4">🔍 Verifikasi Log Perjalanan</h1>

    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded">
        <p class="text-sm text-gray-700">
            <strong>Request:</strong> {{ $log->request->request_no }} &nbsp;|&nbsp;
            <strong>Driver:</strong> {{ $log->request->driver->name ?? '-' }}
        </p>
    </div>

    {{-- Informasi Perjalanan (Odometer) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="bg-gray-50 p-4 rounded-xl">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">📟 Odometer</h3>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <p class="text-xs text-gray-400">Start</p>
                    <p class="text-lg font-bold text-gray-800">{{ number_format($log->odometer_start, 0, ',', '.') }} km</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Finish</p>
                    <p class="text-lg font-bold text-gray-800">{{ number_format($log->odometer_finish, 0, ',', '.') }} km</p>
                </div>
            </div>
            <div class="mt-2 pt-2 border-t border-gray-200">
                <p class="text-xs text-gray-400">Jarak Tempuh</p>
                <p class="text-lg font-bold text-green-600">{{ number_format($log->distance, 1, ',', '.') }} km</p>
            </div>
        </div>

        {{-- Informasi Tambahan (jika ada) --}}
        <div class="bg-gray-50 p-4 rounded-xl">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">📝 Catatan Driver</h3>
            <p class="text-gray-700">{{ $log->notes ?? '-' }}</p>
            @if($log->is_submitted)
                <div class="mt-2 pt-2 border-t border-gray-200">
                    <p class="text-xs text-gray-400">Dikirim Pada</p>
                    <p class="font-medium">{{ $log->submitted_at ? $log->submitted_at->format('d M Y H:i') : '-' }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Foto-foto --}}
    <div class="bg-gray-50 p-4 rounded-xl mb-6">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">📸 Dokumentasi Speedometer</h3>
        <div class="grid grid-cols-2 gap-3">
            @if($log->photo_before)
                <a href="{{ route('drms.private.image', $log->photo_before) }}" target="_blank" class="block group relative rounded-xl overflow-hidden shadow-sm hover:shadow-md transition border border-gray-200 hover:border-blue-300">
                    <img src="{{ route('drms.private.image', $log->photo_before) }}" alt="Foto Sebelum" class="w-full h-32 object-cover group-hover:scale-105 transition duration-300">
                    <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent p-2">
                        <p class="text-xs text-white font-medium">📷 Sebelum</p>
                    </div>
                </a>
            @endif
            @if($log->photo_after)
                <a href="{{ route('drms.private.image', $log->photo_after) }}" target="_blank" class="block group relative rounded-xl overflow-hidden shadow-sm hover:shadow-md transition border border-gray-200 hover:border-blue-300">
                    <img src="{{ route('drms.private.image', $log->photo_after) }}" alt="Foto Sesudah" class="w-full h-32 object-cover group-hover:scale-105 transition duration-300">
                    <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent p-2">
                        <p class="text-xs text-white font-medium">📷 Sesudah</p>
                    </div>
                </a>
            @endif
            @if(!$log->photo_before && !$log->photo_after)
                <p class="col-span-2 text-sm text-gray-400 italic">Tidak ada foto yang diunggah.</p>
            @endif
        </div>
    </div>

    {{-- FORM VERIFIKASI --}}
    @php
        $isVerified = $log->is_verified;
        $isRevisionDeadlinePassed = $log->revision_requested_at && \Carbon\Carbon::now()->diffInDays($log->revision_requested_at) >= 7;
    @endphp

    <form method="POST" action="{{ route('drms.admin.verify.log.post', $log->id) }}" class="bg-gray-50 p-4 rounded-xl">
        @csrf
        <div class="mb-4">
            <label for="verification_notes" class="block text-sm font-medium text-gray-700 mb-1">📝 Catatan Verifikasi</label>
            <textarea name="verification_notes" id="verification_notes" rows="3" 
                      class="w-full border-0 border-b-2 border-gray-300 focus:border-blue-500 bg-transparent px-0 py-2 resize-none"
                      placeholder="Tambahkan catatan untuk driver (opsional)">{{ old('verification_notes', $log->verification_notes) }}</textarea>
        </div>

        <div class="flex flex-wrap gap-3 justify-between items-center">
            <a href="{{ route('drms.admin.monitoring.logs') }}" class="text-gray-600 hover:text-gray-800">← Kembali</a>
            @if($isVerified)
                <span class="text-green-600 font-semibold">✅ Log sudah diverifikasi</span>
            @elseif($isRevisionDeadlinePassed)
                <span class="text-red-600 font-semibold">⛔ Batas waktu revisi 7 hari telah lewat. Log tidak dapat diverifikasi.</span>
            @else
                <div class="flex flex-wrap gap-3">
                    <button type="submit" name="action" value="reject" 
                            class="px-6 py-2 bg-red-500 text-white rounded-full font-semibold shadow-md hover:bg-red-600 transition">
                        ❌ Tolak / Revisi
                    </button>
                    <button type="submit" name="action" value="approve" 
                            class="px-6 py-2 bg-green-500 text-white rounded-full font-semibold shadow-md hover:bg-green-600 transition">
                        ✅ Setujui
                    </button>
                </div>
            @endif
        </div>
    </form>
</div>
@endsection