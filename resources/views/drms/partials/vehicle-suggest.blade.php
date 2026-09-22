{{--
    Kotak pencarian bebas "Plat Nomor + Merek" dengan rekomendasi kendaraan yang muncul
    otomatis saat mengetik (typeahead) — BEDA dari partials/vehicle-search: di sini tidak
    memaksa memilih salah satu (klik rekomendasi hanya mengisi teksnya), karena field ini
    dikirim sebagai teks pencarian bebas (LIKE), bukan memilih 1 vehicle_id tertentu.

    Usage:
        @include('drms.partials.vehicle-suggest', [
            'vehicles'    => $vehicles,
            'name'        => 'search',
            'value'       => request('search'),
            'placeholder' => 'Plat nomor / merek (cth: B 1929 BYD)...',
            'uid'         => 'plate_brand',
        ])
--}}
@php
    $name = $name ?? 'search';
    $value = $value ?? '';
    $placeholder = $placeholder ?? 'Plat nomor / merek...';
    $uid = 'vsug_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $uid ?? $name);
@endphp

<div class="relative" data-vehicle-suggest="{{ $uid }}">
    <input
        type="text"
        name="{{ $name }}"
        id="{{ $uid }}_input"
        autocomplete="off"
        inputmode="search"
        value="{{ $value }}"
        placeholder="{{ $placeholder }}"
        class="w-full border border-gray-300 rounded-lg pl-9 pr-8 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
    >
    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">🔍</span>
    <button type="button" id="{{ $uid }}_clear" class="hidden absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-lg leading-none px-1" tabindex="-1">&times;</button>

    <div id="{{ $uid }}_dropdown" class="hidden absolute z-20 mt-1 w-full max-h-60 overflow-y-auto bg-white border rounded-lg shadow-lg">
        @foreach($vehicles as $v)
            <div class="vsug-option px-3 py-2.5 text-sm hover:bg-blue-50 active:bg-blue-100 cursor-pointer border-b last:border-b-0"
                 data-label="{{ $v->plate_number }} - {{ $v->type }}"
                 data-search="{{ strtolower($v->plate_number . ' ' . $v->type) }}">
                <span class="font-medium">{{ $v->plate_number }}</span>
                <span class="text-gray-400 text-xs block sm:inline sm:ml-1">{{ $v->type }}</span>
            </div>
        @endforeach
        <div class="vsug-empty hidden px-3 py-3 text-sm text-gray-400 text-center">Kendaraan tidak ditemukan</div>
    </div>
</div>

<script>
(function() {
    var uid = "{{ $uid }}";
    var input = document.getElementById(uid + '_input');
    var dropdown = document.getElementById(uid + '_dropdown');
    var clearBtn = document.getElementById(uid + '_clear');
    var options = dropdown.querySelectorAll('.vsug-option');
    var emptyMsg = dropdown.querySelector('.vsug-empty');

    function updateClearBtn() {
        clearBtn.classList.toggle('hidden', !input.value);
    }
    function openDropdown() {
        filterOptions();
        dropdown.classList.remove('hidden');
    }
    function closeDropdown() {
        dropdown.classList.add('hidden');
    }
    function filterOptions() {
        var q = input.value.trim().toLowerCase();
        var visibleCount = 0;
        options.forEach(function(opt) {
            // Cocokkan per kata (plat + merek, urutan bebas); plat juga cocok tanpa spasi.
            var hay = opt.dataset.search, hayCompact = hay.replace(/\s+/g, '');
            var match = q === '' || q.split(/\s+/).every(function (t) {
                return hay.indexOf(t) !== -1 || hayCompact.indexOf(t) !== -1;
            });
            opt.classList.toggle('hidden', !match);
            if (match) visibleCount++;
        });
        emptyMsg.classList.toggle('hidden', visibleCount > 0 || q === '');
    }

    updateClearBtn();

    input.addEventListener('focus', function() { if (input.value) openDropdown(); });
    input.addEventListener('input', function() {
        updateClearBtn();
        if (input.value) openDropdown(); else closeDropdown();
    });
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') { closeDropdown(); input.blur(); }
    });

    options.forEach(function(opt) {
        // mousedown (bukan click) supaya sempat jalan sebelum event blur pada input.
        opt.addEventListener('mousedown', function(e) {
            e.preventDefault();
            input.value = opt.dataset.label;
            updateClearBtn();
            closeDropdown();
        });
    });

    clearBtn.addEventListener('click', function() {
        input.value = '';
        updateClearBtn();
        input.focus();
        closeDropdown();
    });

    document.addEventListener('click', function(e) {
        if (!e.target.closest('[data-vehicle-suggest="' + uid + '"]')) {
            closeDropdown();
        }
    });
})();
</script>
