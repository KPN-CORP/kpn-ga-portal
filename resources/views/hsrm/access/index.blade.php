@extends('layouts.hsrm-app')
@section('title', 'Role Access')
@section('page-title', 'Role Access Management')

@section('content')
@if($errors->any())
    <div class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm">
        @foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach
    </div>
@endif

{{-- Form tambah akses --}}
<div class="bg-white rounded-xl shadow p-4 mb-6">
    <h3 class="font-semibold text-gray-700 mb-1">Assign Role</h3>
    <p class="text-xs text-gray-500 mb-3">
        Ketik nama atau username untuk mencari user. Jika user + area sudah punya akses, role-nya diperbarui.
        Management &amp; Superadmin berlaku global (area tidak perlu dipilih).
    </p>

    <form method="POST" action="{{ route('hsrm.superadmin.access.store') }}"
          class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end"
          x-data="{
              users: @js($users),
              query: '',
              open: false,
              selected: null,
              role: @js(old('role', 'pic')),
              get filtered() {
                  const q = this.query.toLowerCase().trim();
                  return this.users.filter(u => !q
                      || (u.name || '').toLowerCase().includes(q)
                      || (u.username || '').toLowerCase().includes(q)).slice(0, 50);
              },
              get isGlobal() { return ['management', 'superadmin'].includes(this.role); },
              pick(u) { this.selected = u; this.query = u.name; this.open = false; },
              init() {
                  const id = @js((string) old('user_id'));
                  if (id) {
                      const u = this.users.find(x => String(x.id) === id);
                      if (u) { this.selected = u; this.query = u.name; }
                  }
              }
          }"
          @submit="if (!selected) { $event.preventDefault(); open = true; alert('Pilih user dari daftar yang muncul.'); }">
        @csrf

        {{-- User: dropdown yang bisa diketik --}}
        <div class="relative" @click.outside="open = false">
            <label class="block text-sm text-gray-600 mb-1">User</label>
            <input type="hidden" name="user_id" :value="selected ? selected.id : ''">
            <input type="text" x-model="query" autocomplete="off" placeholder="Ketik nama / username..."
                   @focus="open = true" @input="open = true; selected = null"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            <ul x-show="open" x-cloak
                class="absolute z-20 mt-1 w-full max-h-56 overflow-auto bg-white border rounded-lg shadow-lg text-sm">
                <template x-for="u in filtered" :key="u.id">
                    <li @click="pick(u)" class="px-3 py-2 hover:bg-blue-50 cursor-pointer">
                        <span x-text="u.name"></span>
                        <span class="text-xs text-gray-400" x-text="u.username ? '(' + u.username + ')' : ''"></span>
                    </li>
                </template>
                <li x-show="filtered.length === 0" class="px-3 py-2 text-gray-500">User tidak ditemukan</li>
            </ul>
        </div>

        <div>
            <label class="block text-sm text-gray-600 mb-1">Role</label>
            <select name="role" x-model="role" required class="w-full border rounded-lg px-3 py-2 text-sm">
                @foreach($roles as $r)
                    <option value="{{ $r }}">{{ ucfirst($r) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm text-gray-600 mb-1">Area</label>
            <select name="area_id" :disabled="isGlobal" :required="!isGlobal"
                    class="w-full border rounded-lg px-3 py-2 text-sm disabled:bg-gray-100">
                <option value="">-- Select area --</option>
                @foreach($areas as $a)
                    <option value="{{ $a->id_area_kerja }}" {{ old('area_id') == $a->id_area_kerja ? 'selected' : '' }}>{{ $a->nama_area }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-col gap-1 text-sm text-gray-600">
            <label class="inline-flex items-center gap-2"><input type="checkbox" name="can_approve" value="1" {{ old('can_approve') ? 'checked' : '' }}> Can approve</label>
            <label class="inline-flex items-center gap-2"><input type="checkbox" name="email_notif" value="1" checked> Email notif</label>
        </div>
        <div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 w-full">
                <i class="fas fa-save mr-1"></i> Save
            </button>
        </div>
    </form>
</div>

{{-- Filter --}}
<form method="GET" class="flex flex-wrap gap-2 mb-3">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Search user name..." class="border rounded-lg px-3 py-2 text-sm">
    <select name="role" class="border rounded-lg px-3 py-2 text-sm">
        <option value="">All roles</option>
        @foreach($roles as $r)
            <option value="{{ $r }}" {{ request('role') === $r ? 'selected' : '' }}>{{ ucfirst($r) }}</option>
        @endforeach
    </select>
    <button class="bg-gray-100 border px-4 py-2 rounded-lg text-sm hover:bg-gray-200">Filter</button>
</form>

{{-- Daftar akses: Edit (ubah role) / Hapus --}}
<div class="bg-white rounded-xl shadow overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-600">
            <tr>
                <th class="p-3 text-left">User</th>
                <th class="p-3 text-left">Role</th>
                <th class="p-3 text-left">Area</th>
                <th class="p-3 text-center">Approve</th>
                <th class="p-3 text-center">Email</th>
                <th class="p-3 text-center">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($assignments as $row)
            @php
                $badge = [
                    'superadmin' => 'bg-purple-100 text-purple-700',
                    'admin' => 'bg-blue-100 text-blue-700',
                    'management' => 'bg-amber-100 text-amber-700',
                    'pic' => 'bg-gray-100 text-gray-700',
                ][$row->role] ?? 'bg-gray-100 text-gray-700';
                $fid = 'row-form-' . $row->id;
            @endphp
            <tr class="border-t"
                x-data="{
                    edit: false,
                    role: @js($row->role),
                    area: @js((string) $row->area_id),
                    approve: {{ $row->can_approve ? 'true' : 'false' }},
                    email: {{ $row->email_notif ? 'true' : 'false' }},
                    get isGlobal() { return ['management', 'superadmin'].includes(this.role); },
                    reset() {
                        this.edit = false;
                        this.role = @js($row->role);
                        this.area = @js((string) $row->area_id);
                        this.approve = {{ $row->can_approve ? 'true' : 'false' }};
                        this.email = {{ $row->email_notif ? 'true' : 'false' }};
                    }
                }">
                <td class="p-3">{{ $row->user->name ?? ('#'.$row->user_id) }}</td>

                {{-- Role --}}
                <td class="p-3">
                    <span x-show="!edit" class="px-2 py-1 rounded text-xs font-medium {{ $badge }}">{{ ucfirst($row->role) }}</span>
                    <select x-show="edit" x-cloak x-model="role" name="role" form="{{ $fid }}"
                            class="border rounded-lg px-2 py-1 text-sm">
                        @foreach($roles as $r)
                            <option value="{{ $r }}">{{ ucfirst($r) }}</option>
                        @endforeach
                    </select>
                </td>

                {{-- Area (pic/admin wajib; management/superadmin global) --}}
                <td class="p-3">
                    <span x-show="!edit">{{ $row->area->nama_area ?? 'All areas' }}</span>
                    <div x-show="edit" x-cloak>
                        <span x-show="isGlobal" class="text-gray-500">All areas</span>
                        <select x-show="!isGlobal" x-model="area" name="area_id" form="{{ $fid }}"
                                :required="edit && !isGlobal" class="border rounded-lg px-2 py-1 text-sm">
                            <option value="">-- Select area --</option>
                            @foreach($areas as $a)
                                <option value="{{ $a->id_area_kerja }}">{{ $a->nama_area }}</option>
                            @endforeach
                        </select>
                    </div>
                </td>

                {{-- Approve --}}
                <td class="p-3 text-center">
                    <span x-show="!edit">{{ $row->can_approve ? 'Yes' : '-' }}</span>
                    <input x-show="edit" x-cloak type="checkbox" x-model="approve" name="can_approve" value="1" form="{{ $fid }}">
                </td>

                {{-- Email --}}
                <td class="p-3 text-center">
                    <span x-show="!edit">{{ $row->email_notif ? 'On' : 'Off' }}</span>
                    <input x-show="edit" x-cloak type="checkbox" x-model="email" name="email_notif" value="1" form="{{ $fid }}">
                </td>

                {{-- Action --}}
                <td class="p-3 text-center whitespace-nowrap">
                    <form id="{{ $fid }}" method="POST" action="{{ route('hsrm.superadmin.access.update', $row->id) }}" class="hidden">
                        @csrf
                        @method('PUT')
                    </form>

                    <template x-if="!edit">
                        <span>
                            <button type="button" @click="edit = true" class="text-blue-600 hover:text-blue-800 mr-3" title="Edit role">
                                <i class="fas fa-pen"></i>
                            </button>
                            <form method="POST" action="{{ route('hsrm.superadmin.access.destroy', $row->id) }}" class="inline"
                                  onsubmit="return confirm('Hapus akses ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800" title="Hapus"><i class="fas fa-trash"></i></button>
                            </form>
                        </span>
                    </template>

                    <span x-show="edit" x-cloak>
                        <button type="submit" form="{{ $fid }}" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-xs mr-1">Save</button>
                        <button type="button" @click="reset()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-3 py-1 rounded text-xs">Cancel</button>
                    </span>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="p-4 text-center text-gray-500">No role assignments found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $assignments->links() }}</div>
@endsection