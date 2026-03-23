@extends('layouts.app-sidebar')
@section('title', 'Setting Access')

@section('content')
<div class="max-w-7xl mx-auto px-6 py-6">

    <h1 class="text-2xl font-bold mb-1">Setting Akses User</h1>
    <p class="text-sm text-gray-500 mb-6">
        Gunakan filter di bawah, lalu ketik minimal 3 huruf untuk mencari user.
    </p>

    @if(session('success'))
        <div class="mb-4 p-3 rounded bg-green-100 text-green-700">
            {{ session('success') }}
        </div>
    @endif

    {{-- FILTER GROUP, AREA, UNIT --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
        <select id="filterGroup" class="border rounded px-3 py-2 bg-white">
            <option value="">Semua Group Company</option>
            @foreach($groupCompanies as $gc)
                <option value="{{ $gc }}">{{ $gc }}</option>
            @endforeach
        </select>

        <select id="filterArea" class="border rounded px-3 py-2 bg-white">
            <option value="">Semua Office Area</option>
            @foreach($officeAreas as $oa)
                <option value="{{ $oa }}">{{ $oa }}</option>
            @endforeach
        </select>

        <select id="filterUnit" class="border rounded px-3 py-2 bg-white">
            <option value="">Semua Unit</option>
            @foreach($units as $unit)
                <option value="{{ $unit }}">{{ $unit }}</option>
            @endforeach
        </select>

        <button id="resetFilter" type="button" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded transition">
            Reset Filter
        </button>
    </div>

    {{-- SEARCH --}}
    <div class="relative mb-6">
        <input
            type="text"
            id="searchUser"
            value="{{ $selectedUserName }}"
            placeholder="Cari nama / username..."
            class="w-full border rounded px-4 py-2 focus:ring focus:ring-blue-200"
            autocomplete="off"
        >
        <div id="suggestions" class="absolute z-10 w-full bg-white border rounded shadow hidden max-h-64 overflow-y-auto"></div>
    </div>

    @if($username)
    <form method="POST">
        @csrf
        <input type="hidden" name="username" value="{{ $username }}">

        {{-- DASHBOARD --}}
        <div class="bg-white rounded shadow p-4 mb-6">
            <h2 class="font-bold text-blue-600 mb-4">Dashboard Access</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                @foreach($dashCols as $c)
                    @continue(in_array($c->Field, ['id_access','username_access','bu_access']))
                    <label class="access-item flex items-center gap-2 p-3 rounded border cursor-pointer
                        {{ !empty($dashData->{$c->Field} ?? null) ? 'bg-blue-100 border-blue-400' : 'bg-gray-50 border-gray-300' }}">
                        <input type="checkbox" class="hidden access-checkbox" name="dash[{{ $c->Field }}]" {{ !empty($dashData->{$c->Field} ?? null) ? 'checked' : '' }}>
                        <span class="text-sm font-medium">{{ strtoupper(str_replace('_',' ',$c->Field)) }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- MENU --}}
        <div class="bg-white rounded shadow p-4 mb-6">
            <h2 class="font-bold text-green-600 mb-4">Menu Access</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                @foreach($menuCols as $c)
                    @continue(in_array($c->Field, ['id','username']))
                    <label class="access-item flex items-center gap-2 p-3 rounded border cursor-pointer
                        {{ !empty($menuData->{$c->Field} ?? null) ? 'bg-green-100 border-green-400' : 'bg-gray-50 border-gray-300' }}">
                        <input type="checkbox" class="hidden access-checkbox" name="menu[{{ $c->Field }}]" {{ !empty($menuData->{$c->Field} ?? null) ? 'checked' : '' }}>
                        <span class="text-sm font-medium">{{ strtoupper(str_replace('_',' ',$c->Field)) }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded">💾 Simpan Akses</button>
    </form>
    @endif
</div>

<script>
const users = @json($users);
const input = document.getElementById('searchUser');
const box = document.getElementById('suggestions');
const filterGroup = document.getElementById('filterGroup');
const filterArea = document.getElementById('filterArea');
const filterUnit = document.getElementById('filterUnit');
const resetBtn = document.getElementById('resetFilter');

function filterUsers() {
    const q = input.value.toLowerCase().trim();
    const group = filterGroup.value;
    const area = filterArea.value;
    const unit = filterUnit.value;

    return users.filter(u => {
        const matchesText = q.length < 3 || (u.nama_pelanggan + ' ' + u.username_pelanggan).toLowerCase().includes(q);
        const matchesGroup = !group || u.group_company === group;
        const matchesArea  = !area  || u.office_area === area;
        const matchesUnit  = !unit  || u.unit === unit;
        return matchesText && matchesGroup && matchesArea && matchesUnit;
    });
}

function updateSuggestions() {
    const filtered = filterUsers();
    box.innerHTML = '';

    if (filtered.length === 0) {
        box.classList.add('hidden');
        return;
    }

    filtered.slice(0, 50).forEach(u => {
        const div = document.createElement('div');
        div.className = 'px-4 py-2 hover:bg-gray-100 cursor-pointer border-b last:border-0';
        div.innerHTML = `<strong>${u.nama_pelanggan}</strong> <span class="text-xs text-gray-500">(${u.username_pelanggan})</span>
                         <div class="text-xs text-gray-400">${u.group_company ?? '-'} • ${u.office_area ?? '-'} • ${u.unit ?? '-'}</div>`;
        div.onclick = () => { window.location = '?username=' + encodeURIComponent(u.username_pelanggan); };
        box.appendChild(div);
    });

    if (filtered.length > 50) {
        const more = document.createElement('div');
        more.className = 'px-4 py-2 text-xs text-gray-400 italic';
        more.textContent = `dan ${filtered.length - 50} lainnya...`;
        box.appendChild(more);
    }

    box.classList.remove('hidden');
}

input.addEventListener('keyup', updateSuggestions);
filterGroup.addEventListener('change', updateSuggestions);
filterArea.addEventListener('change', updateSuggestions);
filterUnit.addEventListener('change', updateSuggestions);

resetBtn.addEventListener('click', function() {
    filterGroup.value = '';
    filterArea.value = '';
    filterUnit.value = '';
    input.value = '';
    updateSuggestions();
    input.focus();
});

document.addEventListener('click', function(e) {
    if (!input.contains(e.target) && !box.contains(e.target)) {
        box.classList.add('hidden');
    }
});

input.addEventListener('focus', function() {
    if (this.value.length >= 3 || filterGroup.value || filterArea.value || filterUnit.value) {
        updateSuggestions();
    }
});

// Checkbox toggle
document.querySelectorAll('.access-item').forEach(item => {
    const checkbox = item.querySelector('.access-checkbox');
    item.addEventListener('click', (e) => {
        e.preventDefault();
        checkbox.checked = !checkbox.checked;
        if (checkbox.checked) {
            item.classList.remove('bg-gray-50', 'border-gray-300');
            item.classList.add(item.closest('.text-green-600') ? 'bg-green-100' : 'bg-blue-100', 'border-blue-400');
        } else {
            item.classList.remove('bg-green-100', 'bg-blue-100', 'border-blue-400');
            item.classList.add('bg-gray-50', 'border-gray-300');
        }
    });
});
</script>
@endsection