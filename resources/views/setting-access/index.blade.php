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

    {{-- FORM EXPORT ALL (POST) menghindari URI terlalu panjang --}}
    <div class="mb-4 flex justify-end">
        <form id="exportAllForm" method="POST" action="{{ route('setting.access.exportAll') }}" class="inline">
            @csrf
            <input type="hidden" name="usernames" id="exportUsernames">
            <input type="hidden" name="group" id="exportGroup">
            <input type="hidden" name="area" id="exportArea">
            <input type="hidden" name="unit" id="exportUnit">
            <input type="hidden" name="search" id="exportSearch">
            <button type="submit" id="exportAllBtn" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded shadow transition">
                📎 Export All Users (sesuai filter)
            </button>
        </form>
    </div>

    @if($username)
    <form method="POST" action="{{ route('setting.access.store') }}">
        @csrf
        <input type="hidden" name="username" value="{{ $username }}">

        {{-- DASHBOARD --}}
        <div class="bg-white rounded shadow p-4 mb-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="font-bold text-blue-600">Dashboard Access</h2>
                <button type="button" class="select-all-dash text-sm bg-blue-100 hover:bg-blue-200 px-3 py-1 rounded">✅ Select All Dashboard</button>
            </div>
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
            <div class="flex justify-between items-center mb-4">
                <h2 class="font-bold text-green-600">Menu Access</h2>
                <button type="button" class="select-all-menu text-sm bg-green-100 hover:bg-green-200 px-3 py-1 rounded">✅ Select All Menu</button>
            </div>
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

        <div class="flex space-x-3">
            <button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded">💾 Simpan Akses</button>
            <a href="{{ route('setting.access.export', ['username' => $username]) }}" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded text-center inline-block">📎 Export User Ini</a>
        </div>
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
const exportAllForm = document.getElementById('exportAllForm');

// ========== Fungsi mendapatkan user yang terfilter ==========
function getFilteredUsers() {
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

// ========== Update dropdown suggestion ==========
function updateSuggestions() {
    const filtered = getFilteredUsers();
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

// Event listener untuk search & filter
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

// ========== Export All via POST (menghindari URI terlalu panjang) ==========
document.getElementById('exportAllBtn')?.addEventListener('click', function(e) {
    e.preventDefault(); // stop default submit, kita isi dulu hidden fields
    const filtered = getFilteredUsers();
    if (filtered.length === 0) {
        alert('Tidak ada user yang sesuai filter.');
        return;
    }
    const usernames = filtered.map(u => u.username_pelanggan);
    document.getElementById('exportUsernames').value = JSON.stringify(usernames);
    document.getElementById('exportGroup').value = filterGroup.value;
    document.getElementById('exportArea').value = filterArea.value;
    document.getElementById('exportUnit').value = filterUnit.value;
    document.getElementById('exportSearch').value = input.value.trim();
    exportAllForm.submit();
});

// ========== Toggle satu per satu ==========
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

// ========== SELECT ALL Dashboard ==========
document.querySelector('.select-all-dash')?.addEventListener('click', function() {
    const dashItems = document.querySelectorAll('.bg-white.rounded.shadow.p-4.mb-6:first-child .access-item');
    let allChecked = true;
    dashItems.forEach(item => {
        const cb = item.querySelector('.access-checkbox');
        if (!cb.checked) allChecked = false;
    });
    dashItems.forEach(item => {
        const cb = item.querySelector('.access-checkbox');
        if (allChecked) {
            if (cb.checked) {
                cb.checked = false;
                item.classList.remove('bg-blue-100', 'border-blue-400');
                item.classList.add('bg-gray-50', 'border-gray-300');
            }
        } else {
            if (!cb.checked) {
                cb.checked = true;
                item.classList.remove('bg-gray-50', 'border-gray-300');
                item.classList.add('bg-blue-100', 'border-blue-400');
            }
        }
    });
});

// ========== SELECT ALL Menu ==========
document.querySelector('.select-all-menu')?.addEventListener('click', function() {
    const menuItems = document.querySelectorAll('.bg-white.rounded.shadow.p-4.mb-6:last-child .access-item');
    let allChecked = true;
    menuItems.forEach(item => {
        const cb = item.querySelector('.access-checkbox');
        if (!cb.checked) allChecked = false;
    });
    menuItems.forEach(item => {
        const cb = item.querySelector('.access-checkbox');
        if (allChecked) {
            if (cb.checked) {
                cb.checked = false;
                item.classList.remove('bg-green-100', 'border-green-400');
                item.classList.add('bg-gray-50', 'border-gray-300');
            }
        } else {
            if (!cb.checked) {
                cb.checked = true;
                item.classList.remove('bg-gray-50', 'border-gray-300');
                item.classList.add('bg-green-100', 'border-green-400');
            }
        }
    });
});
</script>
@endsection