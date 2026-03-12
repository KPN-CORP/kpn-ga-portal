{{-- resources/views/stock-ctl/superadmin/user_profil/index.blade.php --}}
@extends('layouts.app_stock_sidebar')

@section('content')
<div class="space-y-6 text-sm text-gray-800 font-sans">
    <div>
        <h2 class="text-xl font-semibold text-gray-800">Profil User ATK</h2>
    </div>

    {{-- Search Filter --}}
    <div class="bg-white border rounded-xl p-4">
        <form method="GET" action="{{ route('stock-ctl.user-profil.index') }}" class="flex gap-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau username" 
                   class="flex-1 border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm">Cari</button>
            <a href="{{ route('stock-ctl.user-profil.index') }}" class="px-4 py-2 bg-gray-200 rounded-lg text-sm">Reset</a>
        </form>
    </div>

    {{-- Tabel --}}
    <div class="bg-white border rounded-xl overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">Username</th>
                    <th class="px-4 py-3 text-left">Nama</th>
                    <th class="px-4 py-3 text-left">Bisnis Unit</th>
                    <th class="px-4 py-3 text-left">Area Kerja</th>
                    <th class="px-4 py-3 text-left">Approver</th>
                    <th class="px-4 py-3 text-left">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($profils as $profil)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">{{ $profil->user->username ?? '-' }}</td>
                    <td class="px-4 py-3">{{ $profil->user->name ?? '-' }}</td>
                    <td class="px-4 py-3">{{ $profil->bisnisUnit->nama_bisnis_unit ?? '-' }}</td>
                    <td class="px-4 py-3">{{ $profil->areaKerja->nama_area ?? '-' }}</td>
                    <td class="px-4 py-3">{{ $profil->approver->name ?? '-' }}</td>
                    <td class="px-4 py-3">
                        <button onclick="showEditModal({{ $profil->id_user }})" class="text-blue-600 hover:underline">Edit</button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="py-10 text-center text-gray-500">Belum ada data profil user</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($profils->hasPages())
        <div class="mt-4">{{ $profils->links() }}</div>
    @endif
</div>

{{-- Modal Edit Profil --}}
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-30 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4">Edit Profil User</h3>
        <form id="editForm" method="POST">
            @csrf
            @method('POST')
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-600 mb-1">Bisnis Unit</label>
                <select name="id_bisnis_unit" id="edit_bisnis" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Pilih --</option>
                    @foreach($bisnisUnits as $bu)
                        <option value="{{ $bu->id_bisnis_unit }}">{{ $bu->nama_bisnis_unit }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-600 mb-1">Area Kerja</label>
                <select name="id_area_kerja" id="edit_area" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Pilih --</option>
                    @foreach($areas as $area)
                        <option value="{{ $area->id_area_kerja }}">{{ $area->nama_area }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-600 mb-1">Approver</label>
                <select name="id_approver" id="edit_approver" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Tidak Ada --</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->username }})</option>
                    @endforeach
                </select>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-200 rounded-lg">Batal</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function showEditModal(userId) {
    // Fetch data user via AJAX (pastikan route GET sudah tersedia)
    fetch(`/stock-ctl/user-profil/${userId}`)
        .then(res => res.json())
        .then(data => {
            document.getElementById('edit_bisnis').value = data.id_bisnis_unit || '';
            document.getElementById('edit_area').value = data.id_area_kerja || '';
            document.getElementById('edit_approver').value = data.id_approver || '';
            document.getElementById('editForm').action = `/stock-ctl/user-profil/${userId}`;
            document.getElementById('editModal').classList.remove('hidden');
            document.getElementById('editModal').classList.add('flex');
        })
        .catch(err => console.error(err));
}

function closeModal() {
    document.getElementById('editModal').classList.add('hidden');
    document.getElementById('editModal').classList.remove('flex');
}
</script>
@endsection