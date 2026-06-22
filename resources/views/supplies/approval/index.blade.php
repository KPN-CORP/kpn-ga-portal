@extends('layouts.app_supplies_sidebar')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold text-gray-800">Approval Permintaan Supplies</h2>
        @if($pendingCount > 0)
            <span class="bg-red-100 text-red-800 px-2.5 py-1 rounded-full text-sm font-medium">{{ $pendingCount }} pending</span>
        @endif
    </div>

    <div class="bg-white border rounded-xl overflow-x-auto shadow-sm">
        <table class="w-full text-sm min-w-[1000px]">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Pemohon</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Barang</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Unit</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Jumlah</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Keterangan</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Tgl Pengajuan</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-600">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($permintaan as $p)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3">{{ $p->pemohon->name }}</td>
                    <td class="px-4 py-3">{{ $p->barang->nama_barang }} ({{ $p->barang->kode_barang }})</td>
                    <td class="px-4 py-3">{{ $p->bisnisUnit->nama_bisnis_unit ?? '-' }}</td>
                    <td class="px-4 py-3 text-right font-medium">{{ number_format($p->jumlah) }} {{ $p->barang->satuan }}</td>
                    <td class="px-4 py-3">{{ $p->keterangan ?? '-' }}</td>
                    <td class="px-4 py-3 whitespace-nowrap">{{ $p->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-3 text-center">
                        <button onclick="showApproveModal({{ $p->id }}, {{ $p->jumlah }}, '{{ $p->barang->satuan }}')" 
                                class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition mr-1">
                            <i class="fas fa-check mr-1"></i> Setujui
                        </button>
                        <button onclick="showRejectModal({{ $p->id }})" 
                                class="bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition">
                            <i class="fas fa-times mr-1"></i> Tolak
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-10 text-center text-gray-500">
                        <i class="fas fa-inbox text-3xl mb-2 opacity-40 block"></i>
                        Tidak ada permintaan pending
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Approve -->
<div id="approveModal" class="fixed inset-0 bg-black bg-opacity-30 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4">Setujui Permintaan</h3>
        <p class="text-sm text-gray-600 mb-3">Anda dapat merevisi jumlah yang disetujui.</p>
        <form id="approveForm" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah Disetujui</label>
                <input type="number" step="0.01" name="jumlah_setuju" id="approveJumlah" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                <span id="approveSatuan" class="text-xs text-gray-500"></span>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan (opsional)</label>
                <textarea name="catatan" rows="2" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" placeholder="Tambahkan catatan untuk pemohon..."></textarea>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeModal('approveModal')" class="px-4 py-2 bg-gray-200 rounded-lg text-sm">Batal</button>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm">Setujui</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Reject -->
<div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-30 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4">Tolak Permintaan</h3>
        <form id="rejectForm" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Alasan Penolakan</label>
                <textarea name="alasan" rows="3" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required></textarea>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeModal('rejectModal')" class="px-4 py-2 bg-gray-200 rounded-lg text-sm">Batal</button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm">Tolak</button>
            </div>
        </form>
    </div>
</div>

<script>
function showApproveModal(id, maxJumlah, satuan) {
    document.getElementById('approveForm').action = '/supplies/approval/' + id + '/approve';
    document.getElementById('approveJumlah').value = maxJumlah;
    document.getElementById('approveSatuan').innerText = satuan ? 'Satuan: ' + satuan : '';
    document.getElementById('approveModal').classList.remove('hidden');
    document.getElementById('approveModal').classList.add('flex');
}

function showRejectModal(id) {
    document.getElementById('rejectForm').action = '/supplies/approval/' + id + '/reject';
    document.getElementById('rejectModal').classList.remove('hidden');
    document.getElementById('rejectModal').classList.add('flex');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
    document.getElementById(modalId).classList.remove('flex');
}

window.onclick = function(event) {
    const approveModal = document.getElementById('approveModal');
    const rejectModal = document.getElementById('rejectModal');
    if (event.target === approveModal) closeModal('approveModal');
    if (event.target === rejectModal) closeModal('rejectModal');
}
</script>
@endsection