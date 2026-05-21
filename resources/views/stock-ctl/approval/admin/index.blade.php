@extends('layouts.app_stock_sidebar')

@section('content')
<div class="space-y-6 text-sm text-gray-800 font-sans">
    <div>
        <h2 class="text-xl font-semibold text-gray-800">Approval Admin</h2>
        @if($pendingCount > 0)
            <span class="mt-1 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                {{ $pendingCount }} menunggu
            </span>
        @endif
    </div>

    <div class="bg-white border rounded-xl overflow-x-auto">
        <table class="w-full text-sm min-w-[900px]">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">No. Permintaan</th>
                    <th class="px-4 py-3 text-left">Tanggal</th>
                    <th class="px-4 py-3 text-left">Pemohon</th>
                    <th class="px-4 py-3 text-left">Area</th>
                    <th class="px-4 py-3 text-left">Barang</th>
                    <th class="px-4 py-3 text-left">Jumlah</th>
                    <th class="px-4 py-3 text-left">Keterangan</th>
                    <th class="px-4 py-3 text-left">Disetujui L1</th>
                    <th class="px-4 py-3 text-left">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($permintaan as $item)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-mono text-sm">G-SC-{{ $item->id_permintaan }}</td>
                    <td class="px-4 py-3">{{ \Carbon\Carbon::parse($item->tanggal_permintaan)->format('d M Y H:i') }}</td>
                    <td class="px-4 py-3">{{ $item->pemohon->name ?? '-' }}</td>
                    <td class="px-4 py-3">
                        {{ $item->areaKerja->nama_area ?? '-' }}
                        @if($item->areaKerja && $item->areaKerja->bisnisUnit)
                            ({{ $item->areaKerja->bisnisUnit->nama_bisnis_unit }})
                        @endif
                    </td>
                    <td class="px-4 py-3">{{ $item->barang->nama_barang ?? '-' }}</td>
                    <td class="px-4 py-3">{{ number_format($item->jumlah) }} {{ $item->barang->satuan ?? '' }}</td>
                    <td class="px-4 py-3">{{ $item->keterangan ?? '-' }}</td>
                    <td class="px-4 py-3">{{ $item->approverL1->name ?? '-' }}</td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2">
                            <button onclick="showApproveModal({{ $item->id_permintaan }}, {{ $item->jumlah }}, '{{ addslashes($item->barang->satuan ?? '') }}')" 
                                    class="px-3 py-1 bg-green-600 text-white rounded-lg text-xs font-semibold hover:bg-green-700">
                                Setujui
                            </button>
                            <button onclick="showRejectModal({{ $item->id_permintaan }})" 
                                    class="px-3 py-1 bg-red-600 text-white rounded-lg text-xs font-semibold hover:bg-red-700">
                                Tolak
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="py-10 text-center text-gray-500">Tidak ada permintaan pending</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Modal Approve (AJAX version) --}}
<div id="approveModal" class="fixed inset-0 bg-black bg-opacity-30 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4">Setujui Permintaan</h3>
        <p class="text-sm text-gray-600 mb-3">Anda dapat mengubah jumlah yang disetujui.</p>
        
        <div id="approveError" class="hidden mb-3 p-2 bg-red-100 text-red-800 rounded text-sm"></div>
        
        <form id="approveForm">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-600 mb-1">Jumlah Disetujui</label>
                <input type="number" step="0.01" name="jumlah_setuju" id="approveJumlah" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                <span id="approveSatuan" class="text-xs text-gray-500"></span>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-600 mb-1">Catatan (opsional)</label>
                <textarea name="catatan" rows="2" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" placeholder="Tambahkan catatan untuk pemohon..."></textarea>
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" onclick="closeModal('approveModal')" class="px-4 py-2 bg-gray-200 rounded-lg">Batal</button>
                <button type="submit" id="approveSubmitBtn" class="px-4 py-2 bg-green-600 text-white rounded-lg">Setujui</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Reject --}}
<div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-30 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4">Tolak Permintaan</h3>
        <form id="rejectForm" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-600 mb-1">Alasan Penolakan</label>
                <textarea name="alasan" rows="3" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required></textarea>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeModal('rejectModal')" class="px-4 py-2 bg-gray-200 rounded-lg">Batal</button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg">Tolak</button>
            </div>
        </form>
    </div>
</div>

<script>
function showApproveModal(id, originalJumlah, satuan) {
    const form = document.getElementById('approveForm');
    form.action = "{{ url('stock-ctl/approval/admin') }}/" + id + "/approve";
    form.setAttribute('data-id', id);
    
    const jumlahInput = document.getElementById('approveJumlah');
    jumlahInput.value = originalJumlah;
    document.getElementById('approveSatuan').innerText = satuan ? 'Satuan: ' + satuan : '';
    
    const errorDiv = document.getElementById('approveError');
    errorDiv.classList.add('hidden');
    errorDiv.innerText = '';
    
    fetch("{{ url('stock-ctl/approval/admin/cek-stok') }}/" + id)
        .then(res => res.json())
        .then(data => {
            if (data.tersedia < originalJumlah) {
                errorDiv.innerText = `⚠️ Peringatan: Stok tersedia hanya ${data.tersedia} ${satuan}, tidak cukup untuk permintaan ${originalJumlah} ${satuan}. Anda dapat mengurangi jumlah.`;
                errorDiv.classList.remove('hidden');
            }
        })
        .catch(err => console.error('Gagal cek stok:', err));
    
    document.getElementById('approveModal').classList.remove('hidden');
    document.getElementById('approveModal').classList.add('flex');
}

function showRejectModal(id) {
    const form = document.getElementById('rejectForm');
    form.action = "{{ url('stock-ctl/approval/admin') }}/" + id + "/reject";
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

document.getElementById('approveForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = this;
    const url = form.action;
    const formData = new FormData(form);
    const submitBtn = document.getElementById('approveSubmitBtn');
    const errorDiv = document.getElementById('approveError');
    
    submitBtn.disabled = true;
    submitBtn.innerText = 'Memproses...';
    errorDiv.classList.add('hidden');
    
    fetch(url, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = "{{ route('stock-ctl.approval.admin.index') }}";
        } else {
            errorDiv.innerText = data.message || 'Terjadi kesalahan. Silakan coba lagi.';
            errorDiv.classList.remove('hidden');
            submitBtn.disabled = false;
            submitBtn.innerText = 'Setujui';
        }
    })
    .catch(error => {
        errorDiv.innerText = 'Error koneksi. Periksa jaringan Anda.';
        errorDiv.classList.remove('hidden');
        submitBtn.disabled = false;
        submitBtn.innerText = 'Setujui';
    });
});
</script>
@endsection