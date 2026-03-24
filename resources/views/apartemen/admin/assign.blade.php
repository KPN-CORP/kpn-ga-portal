@extends('layouts.app_apartadmin_sidebar')
@section('content')
<div class="p-4 md:p-6">

    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Penugasan Aktif</h1>
                <p class="text-gray-500 text-sm mt-1">Kelola penugasan penghuni apartemen</p>
            </div>
            <a href="{{ route('apartemen.admin.index') }}" 
               class="text-gray-600 hover:text-gray-800 flex items-center text-sm">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali ke Admin
            </a>
        </div>
    </div>

    {{-- KARTU KONTEN UTAMA --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">

        {{-- FILTER --}}
        <div class="p-6 border-b">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <h3 class="text-lg font-semibold text-gray-800">Daftar Penugasan Aktif</h3>
                <div class="flex flex-wrap gap-2">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" id="searchAssign" placeholder="Cari penghuni..." 
                               class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-64">
                    </div>
                    <button onclick="applySearch()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                        Cari
                    </button>
                    <button onclick="resetSearch()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium">
                        Reset
                    </button>
                </div>
            </div>
        </div>

        {{-- TABEL PENUGASAN --}}
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Apartemen & Unit</th>
                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Penghuni</th>
                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Periode</th>
                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($assignments as $assign)
                    <tr class="hover:bg-gray-50" data-search="{{ strtolower($assign->unit->apartemen->nama_apartemen . ' ' . $assign->unit->nomor_unit . ' ' . implode(' ', $assign->penghuniAktif->pluck('nama')->toArray())) }}">
                        <td class="py-4 px-4">
                            <div>
                                <div class="font-medium text-gray-900">{{ $assign->unit->apartemen->nama_apartemen ?? 'N/A' }}</div>
                                <div class="text-sm text-gray-500">Unit {{ $assign->unit->nomor_unit }}</div>
                                <div class="text-xs text-gray-400">Kapasitas: {{ $assign->unit->kapasitas }} orang</div>
                            </div>
                        </td>
                        <td class="py-4 px-4">
                            <div class="space-y-1">
                                @foreach($assign->penghuniAktif as $penghuni)
                                <div class="text-sm">
                                    <div class="font-medium text-gray-900">{{ $penghuni->nama }}</div>
                                    <div class="text-gray-500 text-xs">{{ $penghuni->id_karyawan }} • {{ $penghuni->unit_kerja ?? '-' }}</div>
                                </div>
                                @endforeach
                            </div>
                        </td>
                        <td class="py-4 px-4">
                            <div class="text-sm text-gray-900">{{ $assign->periode }}</div>
                            <div class="text-xs text-gray-500">
                                {{ \Carbon\Carbon::parse($assign->tanggal_mulai)->diffForHumans() }}
                            </div>
                        </td>
                        <td class="py-4 px-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Aktif
                            </span>
                            <div class="text-xs text-gray-500 mt-1">
                                {{ $assign->penghuniAktif->count() }} / {{ $assign->unit->kapasitas }} penghuni
                            </div>
                        </td>
                        <td class="py-4 px-4">
                            <div class="flex space-x-2">
                                <button onclick="showDetails({{ $assign->id }})" 
                                        class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                    Detail
                                </button>
                                <button onclick="completeAssignment({{ $assign->id }})" 
                                        class="text-green-600 hover:text-green-800 text-sm font-medium">
                                    Selesaikan
                                </button>
                                <button onclick="reassign({{ $assign->id }})" 
                                        class="text-yellow-600 hover:text-yellow-800 text-sm font-medium">
                                    Pindah
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-12 text-center">
                            <div class="flex flex-col items-center justify-center text-gray-400">
                                <svg class="w-12 h-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-10A2.5 2.5 0 1121 10.5 2.5 2.5 0 0118.5 8z" />
                                </svg>
                                <p class="text-lg font-medium">Tidak ada penugasan aktif</p>
                                <p class="text-sm mt-1">Semua penugasan telah selesai</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- PAGINATION --}}
        @if($assignments->hasPages())
        <div class="px-6 py-4 border-t">
            <div class="flex justify-between items-center">
                <div class="text-sm text-gray-500">
                    Menampilkan {{ $assignments->firstItem() }} - {{ $assignments->lastItem() }} dari {{ $assignments->total() }} data
                </div>
                <div class="flex space-x-2">
                    @if($assignments->previousPageUrl())
                        <a href="{{ $assignments->previousPageUrl() }}" class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50">
                            Previous
                        </a>
                    @endif
                    
                    @if($assignments->nextPageUrl())
                        <a href="{{ $assignments->nextPageUrl() }}" class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50">
                            Next
                        </a>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>

</div>

{{-- MODAL DETAIL --}}
<div id="detailModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Detail Penugasan</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div id="detailContent"></div>
            </div>
        </div>
    </div>
</div>

{{-- CONFIRMATION MODAL --}}
<div id="confirmModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4" id="confirmTitle"></h3>
                <p class="text-gray-600 mb-6" id="confirmMessage"></p>
                <div class="flex justify-end space-x-3">
                    <button onclick="closeConfirmModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Batal
                    </button>
                    <button onclick="processAction()" 
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium">
                        Lanjutkan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentAction = null;
let currentAssignId = null;

function applySearch() {
    const searchTerm = document.getElementById('searchAssign').value.toLowerCase();
    const rows = document.querySelectorAll('tbody tr[data-search]');
    
    rows.forEach(row => {
        const searchData = row.getAttribute('data-search');
        if (searchData.includes(searchTerm)) {
            row.classList.remove('hidden');
        } else {
            row.classList.add('hidden');
        }
    });
}

function resetSearch() {
    document.getElementById('searchAssign').value = '';
    document.querySelectorAll('tbody tr').forEach(row => row.classList.remove('hidden'));
}

async function showDetails(id) {
    try {
        const response = await fetch(`/api/apartemen/assign/${id}`);
        const data = await response.json();
        
        document.getElementById('detailContent').innerHTML = `
            <div class="space-y-6">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-md font-semibold text-gray-700 mb-3">Informasi Unit</h4>
                        <div class="space-y-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Apartemen</label>
                                <p class="text-gray-900">${data.unit.apartemen.nama_apartemen}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Unit</label>
                                <p class="text-gray-900">${data.unit.nomor_unit}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Kapasitas</label>
                                <p class="text-gray-900">${data.unit.kapasitas} orang</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Status Unit</label>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    ${data.unit.status === 'TERISI' ? 'Terisi' : data.unit.status}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-md font-semibold text-gray-700 mb-3">Periode Penugasan</h4>
                        <div class="space-y-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Tanggal Mulai</label>
                                <p class="text-gray-900">${new Date(data.tanggal_mulai).toLocaleDateString('id-ID')}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Tanggal Selesai</label>
                                <p class="text-gray-900">${new Date(data.tanggal_selesai).toLocaleDateString('id-ID')}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Durasi</label>
                                <p class="text-gray-900">${data.duration} hari</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Status Penugasan</label>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    ${data.status === 'AKTIF' ? 'Aktif' : data.status}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="border-t pt-6">
                    <h4 class="text-md font-semibold text-gray-700 mb-3">Daftar Penghuni</h4>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-2 px-4 text-left text-xs font-medium text-gray-500">Nama</th>
                                    <th class="py-2 px-4 text-left text-xs font-medium text-gray-500">ID Karyawan</th>
                                    <th class="py-2 px-4 text-left text-xs font-medium text-gray-500">Unit Kerja</th>
                                    <th class="py-2 px-4 text-left text-xs font-medium text-gray-500">Golongan</th>
                                    <th class="py-2 px-4 text-left text-xs font-medium text-gray-500">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                ${data.penghuni.map(p => `
                                    <tr>
                                        <td class="py-2 px-4">${p.nama}</td>
                                        <td class="py-2 px-4">${p.id_karyawan}</td>
                                        <td class="py-2 px-4">${p.unit_kerja || '-'}</td>
                                        <td class="py-2 px-4">${p.gol || '-'}</td>
                                        <td class="py-2 px-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${p.status === 'AKTIF' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}">
                                                ${p.status === 'AKTIF' ? 'Aktif' : 'Selesai'}
                                            </span>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="border-t pt-6">
                    <h4 class="text-md font-semibold text-gray-700 mb-3">Informasi Permintaan</h4>
                    <div class="space-y-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Pemohon</label>
                            <p class="text-gray-900">${data.request.user.name}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Tanggal Pengajuan</label>
                            <p class="text-gray-900">${new Date(data.request.tanggal_pengajuan).toLocaleDateString('id-ID')}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Alasan</label>
                            <p class="text-gray-900">${data.request.alasan || '-'}</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.getElementById('detailModal').classList.remove('hidden');
    } catch (error) {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat memuat data.');
    }
}

function closeModal() {
    document.getElementById('detailModal').classList.add('hidden');
}

function completeAssignment(id) {
    currentAction = 'complete';
    currentAssignId = id;
    
    document.getElementById('confirmTitle').textContent = 'Selesaikan Penugasan';
    document.getElementById('confirmMessage').textContent = 'Apakah Anda yakin ingin menyelesaikan penugasan ini? Penghuni akan dipindahkan ke riwayat dan unit akan tersedia kembali.';
    
    document.getElementById('confirmModal').classList.remove('hidden');
}

function reassign(id) {
    currentAction = 'reassign';
    currentAssignId = id;
    window.location.href = `/apartemen/admin/reassign/${id}`;
}

function closeConfirmModal() {
    document.getElementById('confirmModal').classList.add('hidden');
    currentAction = null;
    currentAssignId = null;
}

async function processAction() {
    if (currentAction === 'complete') {
        try {
            const response = await fetch(`/apartemen/admin/assign/${currentAssignId}/complete`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            });
            
            if (response.ok) {
                closeConfirmModal();
                alert('Penugasan berhasil diselesaikan.');
                window.location.reload();
            } else {
                throw new Error('Gagal menyelesaikan penugasan.');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Terjadi kesalahan: ' + error.message);
        }
    }
}

// Close modals on outside click
document.getElementById('detailModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

document.getElementById('confirmModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeConfirmModal();
    }
});
</script>

<style>
#detailModal, #confirmModal {
    transition: opacity 0.3s ease;
}
</style>
@endsection