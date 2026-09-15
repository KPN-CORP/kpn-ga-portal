{{--
    Reusable searchable "Kendaraan" picker (replaces <select> dropdowns).
    Usage:
        @include('drms.partials.vehicle-search', [
            'vehicles'    => $vehicles,
            'name'        => 'vehicle_id',        // form field name
            'selectedId'  => old('vehicle_id', $repair->vehicle_id ?? request('vehicle_id')),
            'placeholder' => 'Cari plat nomor / tipe kendaraan...',
            'required'    => true,                 // false for filter usage
            'allowAll'    => false,                // true shows "Semua Kendaraan" option (for filters)
            'uid'         => 'vehicle_id',          // optional unique key if used more than once on a page
        ])
--}}
@php
    $name = $name ?? 'vehicle_id';
    $selectedId = $selectedId ?? null;
    $placeholder = $placeholder ?? 'Cari plat nomor / tipe kendaraan...';
    $required = $required ?? false;
    $allowAll = $allowAll ?? false;
    $uid = 'vs_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $uid ?? $name);
    $selectedVehicle = $selectedId ? $vehicles->firstWhere('id', $selectedId) : null;
@endphp

<div class="relative" data-vehicle-search="{{ $uid }}">
    <input
        type="text"
        id="{{ $uid }}_input"
        autocomplete="off"
        inputmode="search"
        value="{{ $selectedVehicle ? $selectedVehicle->plate_number . ' - ' . $selectedVehicle->type : '' }}"
        placeholder="{{ $placeholder }}"
        class="w-full border rounded-lg pl-9 pr-8 py-2.5 text-base sm:text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        @if($required) required aria-required="true" @endif
    >
    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">🔍</span>
    <input type="hidden" name="{{ $name }}" id="{{ $uid }}_value" value="{{ $selectedId }}">
    <button type="button" id="{{ $uid }}_clear" class="hidden absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-lg leading-none px-1" tabindex="-1">&times;</button>

    <div id="{{ $uid }}_dropdown" class="hidden absolute z-20 mt-1 w-full max-h-60 overflow-y-auto bg-white border rounded-lg shadow-lg">
        @if($allowAll)
            <div class="vs-option px-3 py-2.5 text-sm text-gray-500 hover:bg-blue-50 active:bg-blue-100 cursor-pointer border-b" data-id="" data-label="" data-search="">
                Semua Kendaraan
            </div>
        @endif
        @foreach($vehicles as $v)
            <div class="vs-option px-3 py-2.5 text-sm hover:bg-blue-50 active:bg-blue-100 cursor-pointer border-b last:border-b-0"
                 data-id="{{ $v->id }}"
                 data-label="{{ $v->plate_number }} - {{ $v->type }}"
                 data-search="{{ strtolower($v->plate_number . ' ' . $v->type) }}">
                <span class="font-medium">{{ $v->plate_number }}</span>
                <span class="text-gray-400 text-xs block sm:inline sm:ml-1">{{ $v->type }}</span>
            </div>
        @endforeach
        <div class="vs-empty hidden px-3 py-3 text-sm text-gray-400 text-center">Kendaraan tidak ditemukan</div>
    </div>
    @if($required)
        <p id="{{ $uid }}_warn" class="hidden text-red-500 text-xs mt-1">Silakan pilih kendaraan dari daftar.</p>
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
    var options = dropdown.querySelectorAll('.vs-option');
    var emptyMsg = dropdown.querySelector('.vs-empty');
    var isRequired = {{ $required ? 'true' : 'false' }};

    function updateClearBtn() {
        clearBtn.classList.toggle('hidden', !input.value);
    }
    function clearWarning() {
        input.classList.remove('border-red-500', 'ring-2', 'ring-red-300');
        if (warn) warn.classList.add('hidden');
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
            var isAllOption = opt.dataset.id === '';
            var match = isAllOption || q === '' || opt.dataset.search.indexOf(q) !== -1;
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
    });
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDropdown();
            input.blur();
        }
    });
    input.addEventListener('blur', function() {
        setTimeout(function() {
            if (hidden.value) {
                var opt = findOptionById(hidden.value);
                input.value = opt ? opt.dataset.label : input.value;
            } else {
                input.value = '';
            }
            closeDropdown();
        }, 150);
    });

    options.forEach(function(opt) {
        opt.addEventListener('click', function() {
            hidden.value = opt.dataset.id;
            input.value = opt.dataset.id === '' ? '' : opt.dataset.label;
            updateClearBtn();
            clearWarning();
            closeDropdown();
            hidden.dispatchEvent(new Event('change'));
        });
    });

    clearBtn.addEventListener('click', function() {
        hidden.value = '';
        input.value = '';
        updateClearBtn();
        input.focus();
        filterOptions();
    });

    document.addEventListener('click', function(e) {
        if (!e.target.closest('[data-vehicle-search="' + uid + '"]')) {
            closeDropdown();
        }
    });

    if (isRequired) {
        var form = input.closest('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                if (!hidden.value) {
                    e.preventDefault();
                    input.classList.add('border-red-500', 'ring-2', 'ring-red-300');
                    if (warn) warn.classList.remove('hidden');
                    input.focus();
                }
            });
        }
    }
})();
</script>
