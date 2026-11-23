@extends('layouts.app_car_sidebar')

@section('content')
<div class="container mx-auto max-w-lg">
    <h1 class="text-2xl font-bold mb-2">Tambah Kendaraan Baru</h1>
    <p class="text-sm text-gray-500 mb-4">Kendaraan hanya bisa diambil dari data aset GA yang belum terdaftar di DRMS.</p>

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('drms.vehicles.store') }}" method="POST" class="bg-white p-6 rounded shadow">
        @csrf

        {{-- Picker terkunci: user HANYA bisa memilih salah satu opsi dari daftar
             $assets (dikirim controller dari db_asset_vehicles). Kalau user
             mengetik bebas tanpa memilih dari dropdown, hidden input asset_id
             tetap kosong dan form tidak akan bisa disubmit (lihat validasi JS
             di bagian submit paling bawah + validasi server di controller). --}}
        <div class="mb-1" data-asset-picker="asset">
            <label for="asset_input" class="block text-sm font-medium text-gray-700 mb-1">Kendaraan (dari Aset GA)</label>
            <div class="relative">
                <input type="text" id="asset_input" autocomplete="off" inputmode="search"
                       placeholder="Cari plat nomor / merek / model..."
                       class="w-full border rounded px-3 py-2 pr-8 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                <button type="button" id="asset_clear" class="hidden absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-lg leading-none px-1" tabindex="-1">&times;</button>

                {{-- Ini satu-satunya nilai yang benar-benar dikirim ke server untuk
                     identitas kendaraan. Tidak ada input teks bebas untuk
                     type/plate_number/fuel_type sama sekali di form ini. --}}
                <input type="hidden" name="asset_id" id="asset_value" value="{{ old('asset_id') }}">

                <div id="asset_dropdown" class="hidden absolute z-20 mt-1 w-full max-h-60 overflow-y-auto bg-white border rounded-lg shadow-lg">
                    @forelse($assets as $a)
                        <div class="as-option px-3 py-2.5 text-sm hover:bg-blue-50 active:bg-blue-100 cursor-pointer border-b last:border-b-0"
                             data-id="{{ $a->id }}"
                             data-label="{{ $a->registration_plates }} - {{ $a->display_type }}"
                             data-search="{{ strtolower($a->registration_plates.' '.$a->display_type) }}"
                             data-type="{{ $a->display_type }}"
                             data-fuel="{{ $a->mapped_fuel_type ?? '-' }}"
                             data-color="{{ $a->vehicle_color ?? '-' }}"
                             data-year="{{ $a->year_of_manufacture ?? '-' }}">
                            <span class="font-medium">{{ $a->registration_plates }}</span>
                            <span class="text-gray-400 text-xs block sm:inline sm:ml-1">{{ $a->display_type }}</span>
                        </div>
                    @empty
                        <div class="px-3 py-3 text-sm text-gray-400 text-center">Semua aset sudah terdaftar di DRMS.</div>
                    @endforelse
                    <div class="as-empty hidden px-3 py-3 text-sm text-gray-400 text-center">Kendaraan tidak ditemukan</div>
                </div>
            </div>
            <p id="asset_warn" class="hidden text-red-500 text-xs mt-1">Silakan pilih kendaraan dari daftar aset (klik salah satu opsi, jangan hanya mengetik).</p>
        </div>

        <div id="asset_preview" class="hidden my-4 bg-blue-50 border border-blue-200 rounded px-3 py-2 text-sm text-gray-700">
            <div><span class="font-medium">Tipe:</span> <span id="asset_preview_type"></span></div>
            <div><span class="font-medium">Bahan Bakar:</span> <span id="asset_preview_fuel"></span></div>
            <div><span class="font-medium">Warna / Tahun:</span> <span id="asset_preview_color"></span> / <span id="asset_preview_year"></span></div>
        </div>

        <div class="mb-4">
            <label for="capacity" class="block text-sm font-medium text-gray-700 mb-1">Kapasitas (orang)</label>
            <input type="number" name="capacity" id="capacity" value="{{ old('capacity', 4) }}" min="1"
                   class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="mb-4">
            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select name="status" id="status" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="available" {{ old('status')=='available'?'selected':'' }}>Available</option>
                <option value="in_use" {{ old('status')=='in_use'?'selected':'' }}>In Use</option>
                <option value="maintenance" {{ old('status')=='maintenance'?'selected':'' }}>Maintenance</option>
            </select>
        </div>

        <div class="mb-4">
            <label class="inline-flex items-center">
                <input type="checkbox" name="gps_enabled" value="1" {{ old('gps_enabled')?'checked':'' }} class="rounded border-gray-300 text-blue-600">
                <span class="ml-2 text-sm text-gray-700">Aktifkan GPS Tracking</span>
            </label>
        </div>

        <div class="flex justify-end space-x-2">
            <a href="{{ route('drms.vehicles.index') }}" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Batal</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Simpan</button>
        </div>
    </form>
</div>

<script>
(function() {
    var input = document.getElementById('asset_input');
    var hidden = document.getElementById('asset_value');
    var dropdown = document.getElementById('asset_dropdown');
    var clearBtn = document.getElementById('asset_clear');
    var warn = document.getElementById('asset_warn');
    var options = dropdown.querySelectorAll('.as-option');
    var emptyMsg = dropdown.querySelector('.as-empty');
    var preview = document.getElementById('asset_preview');

    function updateClearBtn() { clearBtn.classList.toggle('hidden', !input.value); }
    function clearWarning() { input.classList.remove('border-red-500','ring-2','ring-red-300'); warn.classList.add('hidden'); }
    function openDropdown() { filterOptions(); dropdown.classList.remove('hidden'); }
    function closeDropdown() { dropdown.classList.add('hidden'); }
    function findOptionById(id) { for (var i=0;i<options.length;i++) if (options[i].dataset.id===id) return options[i]; return null; }
    function showPreview(opt) {
        if (!opt) { preview.classList.add('hidden'); return; }
        document.getElementById('asset_preview_type').textContent = opt.dataset.type;
        document.getElementById('asset_preview_fuel').textContent = opt.dataset.fuel;
        document.getElementById('asset_preview_color').textContent = opt.dataset.color;
        document.getElementById('asset_preview_year').textContent = opt.dataset.year;
        preview.classList.remove('hidden');
    }
    function filterOptions() {
        var q = input.value.trim().toLowerCase(), visible = 0;
        options.forEach(function(opt) {
            var hay = opt.dataset.search, hayCompact = hay.replace(/\s+/g,'');
            var match = q === '' || q.split(/\s+/).every(function(t){ return hay.indexOf(t)!==-1 || hayCompact.indexOf(t)!==-1; });
            opt.classList.toggle('hidden', !match);
            if (match) visible++;
        });
        emptyMsg.classList.toggle('hidden', visible > 0);
    }

    updateClearBtn();
    if (hidden.value) showPreview(findOptionById(hidden.value));

    input.addEventListener('focus', openDropdown);
    input.addEventListener('input', function() {
        openDropdown(); clearWarning();
        // Setiap kali user MENGETIK LAGI setelah memilih, asset_id dikosongkan lagi.
        // Jadi mengetik doang tidak pernah cukup untuk submit — harus klik opsi.
        if (hidden.value) { hidden.value=''; showPreview(null); }
    });
    input.addEventListener('keydown', function(e){ if (e.key==='Escape'){ closeDropdown(); input.blur(); } });
    input.addEventListener('blur', function() {
        setTimeout(function() {
            if (hidden.value) { var opt = findOptionById(hidden.value); input.value = opt ? opt.dataset.label : input.value; }
            else { input.value = ''; }
            closeDropdown();
        }, 150);
    });
    options.forEach(function(opt) {
        opt.addEventListener('click', function() {
            hidden.value = opt.dataset.id;
            input.value = opt.dataset.label;
            updateClearBtn(); clearWarning(); closeDropdown(); showPreview(opt);
        });
    });
    clearBtn.addEventListener('click', function() {
        hidden.value=''; input.value=''; updateClearBtn(); showPreview(null); input.focus(); filterOptions();
    });
    document.addEventListener('click', function(e) {
        if (!e.target.closest('[data-asset-picker="asset"]') && !dropdown.contains(e.target)) closeDropdown();
    });
    // Kunci terakhir: kalau asset_id kosong saat submit, form DIBLOKIR (preventDefault).
    input.closest('form').addEventListener('submit', function(e) {
        if (!hidden.value) { e.preventDefault(); input.classList.add('border-red-500','ring-2','ring-red-300'); warn.classList.remove('hidden'); input.focus(); }
    });
})();
</script>
@endsection