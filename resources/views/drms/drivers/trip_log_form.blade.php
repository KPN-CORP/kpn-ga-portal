@extends('layouts.app_car_drive_sidebar')

@section('content')
<div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow">
    <h1 class="text-2xl font-bold mb-2">📝 Log Perjalanan</h1>
    <p class="text-gray-600 mb-6">No. Request: <strong>{{ $request->request_no }}</strong> &nbsp;|&nbsp; Driver: {{ $request->driver->name ?? '-' }}</p>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    @php
        $isLocked = false;
        $lockMessage = '';
        if ($log) {
            if ($log->is_verified) {
                $isLocked = true;
                $lockMessage = '✅ Log sudah diverifikasi, tidak dapat diubah.';
            } elseif ($log->is_submitted && !$log->is_verified && !$log->needsRevision()) {
                $isLocked = true;
                $lockMessage = '⏳ Log sedang menunggu verifikasi admin, tidak dapat diubah.';
            } elseif ($log->needsRevision()) {
                if ($log->revision_requested_at && \Carbon\Carbon::now()->diffInDays($log->revision_requested_at) >= 7) {
                    $isLocked = true;
                    $lockMessage = '⛔ Batas waktu revisi 7 hari telah lewat. Log tidak dapat diperbaiki lagi.';
                }
            }
        }
    @endphp

    @if($isLocked)
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 rounded">
            <p class="text-sm text-yellow-700">{{ $lockMessage }}</p>
        </div>
    @endif

    <form method="POST" action="{{ route('drms.driver.trip.log.store', $request->id) }}" enctype="multipart/form-data">
        @csrf

        {{-- ODOMETER --}}
        <div class="bg-gray-50 p-4 rounded-xl mb-6">
            <h3 class="font-semibold text-gray-700 mb-3">📟 Odometer</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs text-gray-500">Start (km)</label>
                    <input type="number" name="odometer_start" value="{{ old('odometer_start', $log->odometer_start ?? '') }}"
                           class="w-full border-0 border-b-2 border-gray-300 focus:border-blue-500 bg-transparent px-0 py-1 text-lg" 
                           min="0" step="any" placeholder="0" {{ $isLocked ? 'disabled' : '' }}>
                </div>
                <div>
                    <label class="block text-xs text-gray-500">Finish (km)</label>
                    <input type="number" name="odometer_finish" value="{{ old('odometer_finish', $log->odometer_finish ?? '') }}"
                           class="w-full border-0 border-b-2 border-gray-300 focus:border-blue-500 bg-transparent px-0 py-1 text-lg" 
                           min="0" step="any" placeholder="0" {{ $isLocked ? 'disabled' : '' }}>
                </div>
            </div>
        </div>

        {{-- FOTO SPEEDOMETER --}}
        <div class="bg-gray-50 p-4 rounded-xl mb-6">
            <h3 class="font-semibold text-gray-700 mb-3">📸 Foto Speedometer</h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="camera-card" onclick="{{ $isLocked ? '' : "document.getElementById('photo_before').click()" }}">
                    <div class="bg-white rounded-xl p-4 text-center shadow-sm hover:shadow-md transition cursor-pointer border-2 border-dashed border-gray-300 hover:border-blue-400 {{ $isLocked ? 'opacity-60 cursor-not-allowed' : '' }}">
                        <div class="text-5xl text-gray-400 mb-2">📷</div>
                        <p class="text-sm font-medium text-gray-600">Ambil Sebelum</p>
                        <input type="file" name="photo_before" id="photo_before" accept="image/*" capture="environment" class="hidden" onchange="handleFile(this, 'preview_before')" {{ $isLocked ? 'disabled' : '' }}>
                        <div id="preview_before" class="mt-2">
                            @if($log && $log->photo_before)
                                <img src="{{ route('drms.private.image', $log->photo_before) }}" class="w-full h-20 object-cover rounded-lg">
                            @endif
                        </div>
                    </div>
                </div>
                <div class="camera-card" onclick="{{ $isLocked ? '' : "document.getElementById('photo_after').click()" }}">
                    <div class="bg-white rounded-xl p-4 text-center shadow-sm hover:shadow-md transition cursor-pointer border-2 border-dashed border-gray-300 hover:border-blue-400 {{ $isLocked ? 'opacity-60 cursor-not-allowed' : '' }}">
                        <div class="text-5xl text-gray-400 mb-2">📷</div>
                        <p class="text-sm font-medium text-gray-600">Ambil Sesudah</p>
                        <input type="file" name="photo_after" id="photo_after" accept="image/*" capture="environment" class="hidden" onchange="handleFile(this, 'preview_after')" {{ $isLocked ? 'disabled' : '' }}>
                        <div id="preview_after" class="mt-2">
                            @if($log && $log->photo_after)
                                <img src="{{ route('drms.private.image', $log->photo_after) }}" class="w-full h-20 object-cover rounded-lg">
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- CATATAN --}}
        <div class="bg-gray-50 p-4 rounded-xl mb-6">
            <h3 class="font-semibold text-gray-700 mb-3">📝 Catatan</h3>
            <textarea name="notes" rows="3" class="w-full border-0 border-b-2 border-gray-300 focus:border-blue-500 bg-transparent px-0 py-1 resize-none" placeholder="Tambahkan catatan jika perlu..." {{ $isLocked ? 'disabled' : '' }}>{{ old('notes', $log->notes ?? '') }}</textarea>
        </div>

        {{-- TOMBOL AKSI --}}
        <div class="flex flex-col md:flex-row justify-between items-center gap-4 mt-8">
            <a href="{{ route('drms.driver.dashboard') }}" class="text-gray-600 hover:text-gray-800">← Kembali</a>
            @if(!$isLocked)
                <div class="flex flex-wrap gap-3">
                    <button type="submit" name="submit" value="0" class="px-6 py-3 bg-yellow-400 text-white rounded-full font-semibold shadow-md hover:bg-yellow-500 transition">
                        💾 Simpan Draft
                    </button>
                    <button type="submit" name="submit" value="1" class="px-6 py-3 bg-green-500 text-white rounded-full font-semibold shadow-md hover:bg-green-600 transition">
                        {{ $log && $log->needsRevision() ? '📤 Kirim Ulang' : '📤 Kirim ke Admin' }}
                    </button>
                </div>
            @else
                <span class="text-sm text-gray-400 italic">Form terkunci</span>
            @endif
        </div>
    </form>
</div>

<script>
    function handleFile(input, previewId) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById(previewId).innerHTML = `<img src="${e.target.result}" class="w-full h-20 object-cover rounded-lg">`;
            };
            reader.readAsDataURL(file);
        }
    }
</script>
@endsection