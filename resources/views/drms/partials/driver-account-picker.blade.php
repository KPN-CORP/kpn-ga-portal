{{--
    Dropdown "Nama Driver" (searchable) — menggantikan input teks bebas.
    Sumber data: akun terdaftar (users). Nama, username & business unit diisi otomatis
    dari akun yang dipilih di server (controller), jadi teks yang diketik TIDAK dikirim.

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
@endphp

<div id="driver_account_picker" data-initial-label="{{ $initialLabel }}" data-keep-current="{{ $keepCurrent ? '1' : '0' }}">
    <input type="text" id="account_input" list="account_options" autocomplete="off"
           value="{{ $initialLabel }}" required
           placeholder="Ketik untuk mencari, lalu pilih dari daftar..."
           class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
    <datalist id="account_options">
        @foreach($accounts as $a)
            <option value="{{ $a->name }} ({{ $a->username }})" data-id="{{ $a->id }}"></option>
        @endforeach
    </datalist>
    <input type="hidden" name="user_id" id="account_id" value="{{ $selectedId }}">
    <p id="account_username" class="text-xs text-gray-500 mt-1 {{ $selected ? '' : 'hidden' }}">
        Username: <span class="font-medium">{{ $selected->username ?? '' }}</span> — nama &amp; business unit terisi otomatis.
    </p>
    <p id="account_warn" class="hidden text-red-500 text-xs mt-1">Pilih nama driver dari daftar, tidak bisa diketik bebas.</p>
    @if($accounts->isEmpty())
        <p class="text-xs text-amber-600 mt-1">Tidak ada akun yang tersedia (semua akun sudah terdaftar sebagai driver).</p>
    @endif
</div>

<script>
(function () {
    var box = document.getElementById('driver_account_picker');
    var input = document.getElementById('account_input');
    var hidden = document.getElementById('account_id');
    var info = document.getElementById('account_username');
    var warn = document.getElementById('account_warn');
    var options = document.querySelectorAll('#account_options option');
    var initialLabel = box.dataset.initialLabel || '';
    var keepCurrent = box.dataset.keepCurrent === '1';

    function findOption(label) {
        for (var i = 0; i < options.length; i++) {
            if (options[i].value === label) return options[i];
        }
        return null;
    }

    input.addEventListener('input', function () {
        var opt = findOption(input.value);
        if (opt) {
            hidden.value = opt.dataset.id;
            var m = opt.value.match(/\(([^()]*)\)\s*$/);
            info.querySelector('span').textContent = m ? m[1] : '';
            info.classList.remove('hidden');
            warn.classList.add('hidden');
            input.classList.remove('border-red-500');
        } else {
            hidden.value = '';
            info.classList.add('hidden');
        }
    });

    var form = input.closest('form');
    if (form) {
        form.addEventListener('submit', function (e) {
            // Edit: nama lama yang tidak diubah boleh tetap disimpan tanpa memilih ulang.
            var unchanged = keepCurrent && input.value === initialLabel;
            if (!hidden.value && !unchanged) {
                e.preventDefault();
                warn.classList.remove('hidden');
                input.classList.add('border-red-500');
                input.focus();
            }
        });
    }
})();
</script>
