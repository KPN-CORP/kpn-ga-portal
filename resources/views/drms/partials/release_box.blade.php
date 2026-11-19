{{-- Selesai hari ini (trip multi-hari). Dipakai di dashboard driver dan baris approval admin. Butuh $req. --}}
@php
    $canRelease = $req->canReleaseToday();
    $activeRelease = $canRelease ? $req->releaseToday() : null;
@endphp
@if($activeRelease)
    <div class="inline-flex items-center gap-2 bg-green-50 border border-green-200 text-green-800 rounded px-2 py-1 text-xs">
        <span>🟢 Slot dilepas {{ $activeRelease->free_from->format('H:i') }}, lanjut {{ $activeRelease->free_until->format('d M H:i') }}</span>
        <form action="{{ route('drms.releases.cancel', $activeRelease->id) }}" method="POST"
              onsubmit="return confirm('Batalkan pelepasan slot ini? Kalau sudah ada request lain di slot ini, admin akan diberi notifikasi.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="underline font-semibold">Batalkan</button>
        </form>
    </div>
@elseif($canRelease)
    <details class="inline-block text-xs">
        <summary class="cursor-pointer bg-amber-500 hover:bg-amber-600 text-white px-3 py-1 rounded font-semibold">🌙 Selesai hari ini</summary>
        <form action="{{ route('drms.requests.release.store', $req->id) }}" method="POST"
              class="mt-2 p-3 bg-white border rounded shadow-sm space-y-2 text-gray-700 w-64">
            @csrf
            <p class="text-gray-500">Driver dan mobil bebas sampai trip lanjut besok. Trip ini tetap berstatus Disetujui.</p>
            <label class="block">Selesai pukul
                <input type="time" name="free_from" value="{{ now()->format('H:i') }}" required class="border rounded px-2 py-1 w-full">
            </label>
            <label class="block">Lanjut besok pukul
                <input type="time" name="resume_time" value="{{ \Carbon\Carbon::parse($req->start_time)->format('H:i') }}" class="border rounded px-2 py-1 w-full">
            </label>
            <label class="flex items-center gap-2"><input type="checkbox" name="vehicle_released" value="1" checked> Mobil ikut dilepas</label>
            <label class="flex items-center gap-2"><input type="checkbox" name="at_home_city" value="1" checked> Driver di kota asal (slot boleh dipakai request lain)</label>
            <input type="text" name="note" maxlength="255" placeholder="Catatan (opsional)" class="border rounded px-2 py-1 w-full">
            <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white px-3 py-1.5 rounded font-semibold w-full">Lepas slot</button>
        </form>
    </details>
@endif
