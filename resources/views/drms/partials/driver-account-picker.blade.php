{{--
    Dropdown "Nama Driver" (searchable, dengan rekomendasi otomatis muncul saat mengetik) —
    menggantikan input teks bebas. Gaya & perilaku sama persis dengan partials/vehicle-search
    yang sudah dipakai di halaman lain, supaya konsisten dan pasti tampil di HP (tidak
    mengandalkan <datalist> bawaan browser yang di beberapa HP suggestion-nya tidak muncul).

    Nama, username & business unit diisi otomatis di server dari akun yang dipilih;
    teks yang diketik TIDAK pernah dikirim sebagai nama.

    Usage:
        @include('drms.partials.driver-account-picker', [
            'accounts'    => $accounts,                 // koleksi User (id, name, username)
            'selectedId'  => $selectedId ?? null,       // id akun yang sedang terpilih (edit)
            'currentName' => $driver->name ?? '',       // nama lama (driver lama yang belum punya username)
            'keepCurrent' => true,                      // edit: boleh submit tanpa memilih ulang
        ])
--}}
@php
    $selectedId  = old('user_id', $selectedId ?? null);
    $currentName = $currentName ?? '';
    $keepCurrent = $keepCurrent ?? false;
    $selected    = $selectedId ? $accounts->firstWhere('id', (int) $selectedId) : null;
    $initialLabel = $selected ? ($selected->name . ' (' . $selected->username . ')') : $currentName;
    $uid = 'driver_acc';
@endphp

<div class="relative" data-driver-account-search="{{ $uid }}">
    <input
        type="text"
        id="{{ $uid }}_input"
        autocomplete="off"
        inputmode="search"
        value="{{ $initialLabel }}"
        placeholder="Ketik nama driver untuk mencari..."
        class="w-full border rounded-lg pl-9 pr-8 py-2.5 text-base sm:text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        required aria-required="true"
    >
    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">🔍</span>
    <input type="hidden" name="user_id" id="{{ $uid }}_value" value="{{ $selectedId }}">
    <button type="button" id="{{ $uid }}_clear" class="hidden absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-lg leading-none px-1" tabindex="-1">&times;</button>

    <div id="{{ $uid }}_dropdown" class="hidden absolute z-20 mt-1 w-full max-h-60 overflow-y-auto bg-white border rounded-lg shadow-lg">
        @foreach($accounts as $a)
            <div class="da-option px-3 py-2.5 text-sm hover:bg-blue-50 active:bg-blue-100 cursor-pointer border-b last:border-b-0"
                 data-id="{{ $a->id }}"
                 data-label="{{ $a->name }} ({{ $a->username }})"
                 data-search="{{ strtolower($a->name . ' ' . $a->username) }}">
                <span class="font-medium">{{ $a->name }}</span>
                <span class="text-gray-400 text-xs block sm:inline sm:ml-1">{{ $a->username }}</span>
            </div>
        @endforeach
        <div class="da-empty hidden px-3 py-3 text-sm text-gray-400 text-center">Akun tidak ditemukan</div>
    </div>
    <p id="{{ $uid }}_info" class="text-xs text-gray-500 mt-1 {{ $selected ? '' : 'hidden' }}">
        Username: <span class="font-medium">{{ $selected->username ?? '' }}</span> — nama &amp; business unit terisi otomatis.
    </p>
    <p id="{{ $uid }}_warn" class="hidden text-red-500 text-xs mt-1">Pilih nama driver dari daftar rekomendasi, tidak bisa diketik bebas.</p>
    @if($accounts->isEmpty())
        <p class="text-xs text-amber-600 mt-1">Tidak ada akun yang tersedia (semua akun sudah terdaftar sebagai driver).</p>
    @endif
</div>

<script>
(function() {
    var uid = "{{ $uid }}";
    var input = document.getElementById(uid + '_input');
    var hidden = document.getElementById(uid + '_value');
    var dropdown = document.getElementById(uid + '_dropdown');
    var clearBtn = document.getElementById(uid + '_clear');
    var warn = document.getElementById(uid + '_warn');
    var info = document.getElementById(uid + '_info');
    var options = dropdown.querySelectorAll('.da-option');
    var emptyMsg = dropdown.querySelector('.da-empty');
    var initialLabel = @json($initialLabel);
    var keepCurrent = {{ $keepCurrent ? 'true' : 'false' }};

    function updateClearBtn() {
        clearBtn.classList.toggle('hidden', !input.value);
    }
    function clearWarning() {
        input.classList.remove('border-red-500', 'ring-2', 'ring-red-300');
        warn.classList.add('hidden');
    }
    function openDropdown() {
        filterOptions();
        dropdown.classList.remove('hidden');
    }
    function closeDropdown() {
        dropdown.classList.add('hidden');
    }
    function findOptionById(id) {
        for (var i = 0; i < options.length; i++) {
            if (options[i].dataset.id === id) return options[i];
        }
        return null;
    }
    function filterOptions() {
        var q = input.value.trim().toLowerCase();
        var visibleCount = 0;
        options.forEach(function(opt) {
            // Cocokkan per kata (nama / username, urutan bebas) — rekomendasi otomatis muncul saat mengetik.
            var hay = opt.dataset.search;
            var match = q === '' || q.split(/\s+/).every(function (t) { return hay.indexOf(t) !== -1; });
            opt.classList.toggle('hidden', !match);
            if (match) visibleCount++;
        });
        emptyMsg.classList.toggle('hidden', visibleCount > 0);
    }

    updateClearBtn();

    input.addEventListener('focus', openDropdown);
    input.addEventListener('input', function() {
        openDropdown();
        clearWarning();
        // Nilai lama tidak lagi valid begitu user mulai mengetik ulang.
        if (hidden.value) { hidden.value = ''; info.classList.add('hidden'); }
    });
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') { closeDropdown(); input.blur(); }
    });
    input.addEventListener('blur', function() {
        setTimeout(function() {
            if (hidden.value) {
                var opt = findOptionById(hidden.value);
                input.value = opt ? opt.dataset.label : input.value;
            } else if (!(keepCurrent && input.value === initialLabel)) {
                input.value = '';
            }
            closeDropdown();
        }, 150);
    });

    options.forEach(function(opt) {
        opt.addEventListener('click', function() {
            hidden.value = opt.dataset.id;
            input.value = opt.dataset.label;
            info.querySelector('span').textContent = opt.dataset.label.match(/\(([^()]*)\)\s*$/)[1];
            info.classList.remove('hidden');
            updateClearBtn();
            clearWarning();
            closeDropdown();
        });
    });

    clearBtn.addEventListener('click', function() {
        hidden.value = '';
        input.value = '';
        info.classList.add('hidden');
        updateClearBtn();
        input.focus();
        filterOptions();
    });

    document.addEventListener('click', function(e) {
        if (!e.target.closest('[data-driver-account-search="' + uid + '"]')) {
            closeDropdown();
        }
    });

    var form = input.closest('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            var unchanged = keepCurrent && input.value === initialLabel;
            if (!hidden.value && !unchanged) {
                e.preventDefault();
                input.classList.add('border-red-500', 'ring-2', 'ring-red-300');
                warn.classList.remove('hidden');
                input.focus();
            }
        });
    }
})();
</script>