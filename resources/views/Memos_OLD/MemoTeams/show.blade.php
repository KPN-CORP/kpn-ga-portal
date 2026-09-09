@extends('layouts.app_memos')
@section('title', $team->team_name)
@section('content')
<div class="w-full px-2 md:px-4">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold">{{ $team->team_name }}</h2>
            <p class="text-sm text-gray-500">Atur siapa saja admin & anggota di tim ini</p>
        </div>
        <a href="{{ route('memo-teams.index') }}" class="text-sm text-gray-500 hover:underline">&larr; Kembali ke daftar tim</a>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-green-50 text-green-700 text-sm px-4 py-2 rounded-lg">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 bg-red-50 text-red-700 text-sm px-4 py-2 rounded-lg">{{ $errors->first() }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- ===== ADMIN ===== --}}
        <div class="bg-white rounded-xl shadow-sm p-5">
            <h3 class="font-bold mb-3">👤 Admin Tim ({{ $team->admins->count() }})</h3>

            <form method="POST" action="{{ route('memo-teams.admins.add', $team) }}"
                x-data="userSearchSelect(@js($users))" class="flex flex-col gap-2 mb-4">
                @csrf
                <div class="relative">
                    <input type="text" x-model="search" @focus="open = true" @input="open = true"
                        placeholder="Cari nama user..." autocomplete="off"
                        class="w-full border rounded-lg p-2 text-sm">
                    <input type="hidden" name="user_id" :value="selectedId" required>
                    <div x-show="open" x-cloak @click.outside="open = false"
                        class="absolute z-20 mt-1 w-full max-h-56 overflow-y-auto bg-white border rounded-lg shadow-lg">
                        <template x-for="u in filtered" :key="u.id">
                            <div @click="select(u)" class="px-3 py-2 text-sm hover:bg-blue-50 cursor-pointer"
                                x-text="u.name + (u.username || u.email ? ' (' + (u.username || u.email) + ')' : '')"></div>
                        </template>
                        <div x-show="filtered.length === 0" class="px-3 py-2 text-sm text-gray-400">Tidak ditemukan</div>
                    </div>
                </div>
                <input type="text" name="jabatan" placeholder="Jabatan (mis. Manager Finance)" class="border rounded-lg p-2 text-sm">
                <button class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm whitespace-nowrap">+ Tambah Admin</button>
            </form>
            <p class="text-xs text-gray-400 mb-3">Nama & jabatan admin di sini otomatis dipakai sebagai Penandatangan pada memo.</p>

            <div class="divide-y">
                @forelse($team->admins as $admin)
                <div class="flex items-center justify-between py-2 gap-2">
                    <span class="text-sm flex-1 min-w-0 truncate">{{ $admin->name }}</span>
                    <form method="POST" action="{{ route('memo-teams.admins.jabatan', [$team, $admin]) }}" class="flex items-center gap-1 shrink-0">
                        @csrf @method('PATCH')
                        <input type="text" name="jabatan" value="{{ $admin->pivot->jabatan }}" placeholder="Jabatan belum diisi"
                            class="text-xs border rounded p-1 w-36">
                        <button class="text-xs text-blue-600 hover:underline whitespace-nowrap">Simpan</button>
                    </form>
                    <form method="POST" action="{{ route('memo-teams.admins.remove', [$team, $admin]) }}" onsubmit="return confirm('Keluarkan {{ $admin->name }} dari admin tim ini?')" class="shrink-0">
                        @csrf @method('DELETE')
                        <button class="text-xs text-red-500 hover:underline whitespace-nowrap">Keluarkan</button>
                    </form>
                </div>
                @empty
                <p class="text-sm text-gray-400 italic py-2">Belum ada admin di tim ini.</p>
                @endforelse
            </div>
        </div>

        {{-- ===== ANGGOTA ===== --}}
        <div class="bg-white rounded-xl shadow-sm p-5">
            <h3 class="font-bold mb-3">🧑‍🤝‍🧑 Anggota Tim ({{ $team->members->count() }})</h3>

            <form method="POST" action="{{ route('memo-teams.members.add', $team) }}"
                x-data="userSearchSelect(@js($users))" class="flex flex-col gap-2 mb-4">
                @csrf
                <div class="relative">
                    <input type="text" x-model="search" @focus="open = true" @input="open = true"
                        placeholder="Cari nama user..." autocomplete="off"
                        class="w-full border rounded-lg p-2 text-sm">
                    <input type="hidden" name="user_id" :value="selectedId" required>
                    <div x-show="open" x-cloak @click.outside="open = false"
                        class="absolute z-20 mt-1 w-full max-h-56 overflow-y-auto bg-white border rounded-lg shadow-lg">
                        <template x-for="u in filtered" :key="u.id">
                            <div @click="select(u)" class="px-3 py-2 text-sm hover:bg-blue-50 cursor-pointer"
                                x-text="u.name + (u.username || u.email ? ' (' + (u.username || u.email) + ')' : '')"></div>
                        </template>
                        <div x-show="filtered.length === 0" class="px-3 py-2 text-sm text-gray-400">Tidak ditemukan</div>
                    </div>
                </div>
                <select name="responsible_admin_id" class="border rounded-lg p-2 text-sm">
                    <option value="">-- Admin acuan nomor memo (otomatis kalau admin cuma 1) --</option>
                    @foreach($team->admins as $admin)
                        <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                    @endforeach
                </select>
                <button class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm">+ Tambah Anggota</button>
            </form>

            <div class="divide-y">
                @forelse($team->members as $member)
                <div class="flex justify-between items-center py-2 gap-2">
                    <div>
                        <p class="text-sm">{{ $member->name }}</p>
                        <p class="text-xs text-gray-400">
                            Nomor memo ikut: {{ $member->pivot->responsible_admin_id ? optional($team->admins->firstWhere('id', $member->pivot->responsible_admin_id))->name : '-belum diatur-' }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($team->admins->count() > 1)
                        <form method="POST" action="{{ route('memo-teams.members.update-admin', [$team, $member]) }}" class="flex gap-1">
                            @csrf @method('PATCH')
                            <select name="responsible_admin_id" class="text-xs border rounded p-1" onchange="this.form.submit()">
                                <option value="">Pindah ke admin...</option>
                                @foreach($team->admins as $admin)
                                    <option value="{{ $admin->id }}" @selected($member->pivot->responsible_admin_id == $admin->id)>{{ $admin->name }}</option>
                                @endforeach
                            </select>
                        </form>
                        @endif
                        <form method="POST" action="{{ route('memo-teams.members.remove', [$team, $member]) }}" onsubmit="return confirm('Keluarkan {{ $member->name }} dari tim ini?')">
                            @csrf @method('DELETE')
                            <button class="text-xs text-red-500 hover:underline">Keluarkan</button>
                        </form>
                    </div>
                </div>
                @empty
                <p class="text-sm text-gray-400 italic py-2">Belum ada anggota di tim ini.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<script>
    function userSearchSelect(users) {
        return {
            users: users,
            search: '',
            open: false,
            selectedId: '',
            get filtered() {
                if (!this.search) return this.users;
                const q = this.search.toLowerCase();
                return this.users.filter(u =>
                    (u.name || '').toLowerCase().includes(q) ||
                    (u.username || '').toLowerCase().includes(q) ||
                    (u.email || '').toLowerCase().includes(q)
                );
            },
            select(u) {
                this.selectedId = u.id;
                this.search = u.name + (u.username || u.email ? ' (' + (u.username || u.email) + ')' : '');
                this.open = false;
            }
        }
    }
</script>
@endsection