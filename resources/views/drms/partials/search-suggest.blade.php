{{--
    Kotak pencarian bebas dengan daftar rekomendasi (mis. nama driver). Begitu kotak
    di-klik/fokus, semua item langsung tampil sebagai rekomendasi, lalu tersaring saat
    mengetik. Klik rekomendasi hanya mengisi teksnya (boleh tetap ketik bebas) — field ini
    dikirim sebagai teks pencarian bebas (LIKE), bukan memilih 1 id tertentu.

    Usage:
        @include('drms.partials.search-suggest', [
            'items'       => $driverNames,           // koleksi string, atau array of ['label' => ..., 'search' => ...]
            'name'        => 'search',
            'value'       => request('search'),
            'placeholder' => 'Cari nama...',
            'emptyText'   => 'Tidak ditemukan',
            'uid'         => 'driver_search',
        ])
--}}
@php
    $name = $name ?? 'search';
    $value = $value ?? '';
    $placeholder = $placeholder ?? 'Ketik untuk mencari...';
    $emptyText = $emptyText ?? 'Tidak ditemukan';
    $uid = 'sug_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $uid ?? $name);
    $itemList = collect($items ?? [])->map(function ($it) {
        return is_array($it) ? $it : ['label' => $it, 'search' => strtolower($it)];
    });
@endphp

<div class="relative" data-search-suggest="{{ $uid }}">
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
        @foreach($itemList as $it)
            <div class="sug-option px-3 py-2.5 text-sm hover:bg-blue-50 active:bg-blue-100 cursor-pointer border-b last:border-b-0"
                 data-label="{{ $it['label'] }}"
                 data-search="{{ $it['search'] ?? strtolower($it['label']) }}">
                {{ $it['label'] }}
            </div>
        @endforeach
        <div class="sug-empty hidden px-3 py-3 text-sm text-gray-400 text-center">{{ $emptyText }}</div>
    </div>
</div>

<script>
(function() {
    var uid = "{{ $uid }}";
    var input = document.getElementById(uid + '_input');
    var dropdown = document.getElementById(uid + '_dropdown');
    var clearBtn = document.getElementById(uid + '_clear');
    var options = dropdown.querySelectorAll('.sug-option');
    var emptyMsg = dropdown.querySelector('.sug-empty');

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
            var hay = opt.dataset.search;
            var match = q === '' || q.split(/\s+/).every(function (t) {
                return hay.indexOf(t) !== -1;
            });
            opt.classList.toggle('hidden', !match);
            if (match) visibleCount++;
        });
        emptyMsg.classList.toggle('hidden', visibleCount > 0 || q === '');
    }

    updateClearBtn();

    // Diklik/fokus (walau masih kosong) -> langsung tampilkan semua rekomendasi.
    input.addEventListener('focus', openDropdown);
    input.addEventListener('input', function() {
        updateClearBtn();
        openDropdown();
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
        if (!e.target.closest('[data-search-suggest="' + uid + '"]')) {
            closeDropdown();
        }
    });
})();
</script>
