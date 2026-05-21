@extends('layouts.app_stock_sidebar')
@section('content')
<div class="space-y-6">
    <h2 class="text-xl font-semibold">Approval Permintaan Antar Unit</h2>
    @if($pendingCount > 0)
        <span class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs">{{ $pendingCount }} menunggu</span>
    @endif
    <div class="bg-white rounded-xl overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th>ID</th>
                    <th>Pemohon</th>
                    <th>Barang</th>
                    <th>Jumlah Diminta</th>
                    <th>Unit Penerima (Pemohon)</th>
                    <th>Unit Pengirim (Anda)</th>
                    <th>Keterangan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($requests as $r)
                <tr>
                    <td class="px-4 py-2">#{{ $r->id }}</td>
                    <td class="px-4 py-2">{{ $r->pemohon->name }}</td>
                    <td class="px-4 py-2">{{ $r->barang->nama_barang }}</td>
                    <td class="px-4 py-2">{{ number_format($r->jumlah) }} {{ $r->barang->satuan }}</td>
                    <td class="px-4 py-2">{{ $r->unitAsal->nama_bisnis_unit }} (pemohon)</td>
                    <td class="px-4 py-2">{{ $r->unitTujuan->nama_bisnis_unit }} (Anda)</td>
                    <td class="px-4 py-2">{{ $r->keterangan ?? '-' }}</td>
                    <td class="px-4 py-2 flex gap-2">
                        <button onclick="showApproveModal({{ $r->id }}, {{ $r->jumlah }}, '{{ $r->barang->satuan }}', {{ $r->id_barang }}, {{ $r->id_bisnis_unit_tujuan }})" 
                                class="bg-green-600 text-white px-3 py-1 rounded-lg text-xs hover:bg-green-700">
                            Setujui
                        </button>
                        <button onclick="showRejectModal({{ $r->id }})" 
                                class="bg-red-600 text-white px-3 py-1 rounded-lg text-xs hover:bg-red-700">
                            Tolak
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Approve -->
<div id="approveModal" class="fixed inset-0 bg-black bg-opacity-30 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4">Setujui Permintaan Antar Unit</h3>
        <p class="text-sm text-gray-600 mb-3">Pilih area di unit pengirim (Anda) yang akan diambil stoknya.</p>

        <div id="approveError" class="hidden mb-3 p-2 bg-red-100 text-red-800 rounded text-sm"></div>
        <div id="approveInfo" class="hidden mb-3 p-2 bg-blue-100 text-blue-800 rounded text-sm"></div>

        <form id="approveForm" method="POST">
            @csrf
            <input type="hidden" name="request_id" id="request_id">
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-600 mb-1">Jumlah Diminta</label>
                <input type="text" id="jumlah_diminta" class="w-full border rounded-lg px-3 py-2 bg-gray-100 text-sm" readonly>
            </div>

            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-600 mb-1">Pilih Area Pengirim (Unit Anda)</label>
                <select name="id_area_pengirim" id="id_area_pengirim" class="w-full border rounded-lg px-3 py-2 text-sm" required>
                    <option value="">-- Pilih Area --</option>
                </select>
                <span id="area_satuan" class="text-xs text-gray-500"></span>
            </div>

            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-600 mb-1">Stok Tersedia di Area Terpilih</label>
                <input type="text" id="stok_tersedia" class="w-full border rounded-lg px-3 py-2 bg-gray-100 text-sm" readonly>
                <span id="stok_satuan" class="text-xs text-gray-500"></span>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-600 mb-1">Jumlah Disetujui</label>
                <input type="number" step="0.01" name="jumlah_setuju" id="jumlah_setuju" class="w-full border rounded-lg px-3 py-2 text-sm" required>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-600 mb-1">Catatan (opsional)</label>
                <textarea name="catatan" rows="2" class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Tambahkan catatan untuk pemohon..."></textarea>
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeModal('approveModal')" class="px-4 py-2 bg-gray-200 rounded-lg">Batal</button>
                <button type="submit" id="approveSubmitBtn" class="px-4 py-2 bg-green-600 text-white rounded-lg">Setujui</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Reject -->
<div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-30 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4">Tolak Permintaan</h3>
        <form id="rejectForm" method="POST">
            @csrf
            <input type="hidden" name="request_id" id="reject_request_id">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-600 mb-1">Alasan Penolakan</label>
                <textarea name="alasan" rows="3" class="w-full border rounded-lg px-3 py-2 text-sm" required></textarea>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeModal('rejectModal')" class="px-4 py-2 bg-gray-200 rounded-lg">Batal</button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg">Tolak</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentIdBarang = null;
let currentSatuan = null;
let currentJumlahDiminta = null;

function showApproveModal(id, jumlahDiminta, satuan, idBarang, idUnitPengirim) {
    currentIdBarang = idBarang;
    currentSatuan = satuan;
    currentJumlahDiminta = jumlahDiminta;

    document.getElementById('request_id').value = id;
    document.getElementById('jumlah_diminta').value = number_format(jumlahDiminta) + ' ' + satuan;
    document.getElementById('jumlah_setuju').value = jumlahDiminta;
    document.getElementById('stok_satuan').innerText = 'Satuan: ' + satuan;
    document.getElementById('area_satuan').innerText = 'Satuan: ' + satuan;

    document.getElementById('approveError').classList.add('hidden');
    document.getElementById('approveInfo').classList.add('hidden');

    const areaSelect = document.getElementById('id_area_pengirim');
    areaSelect.innerHTML = '<option value="">-- Pilih Area --</option>';
    areaSelect.disabled = true;

    // URL manual untuk get-areas
    const urlAreas = "{{ url('stock-ctl/antar-unit/get-areas') }}" + "?id_bisnis_unit=" + idUnitPengirim;
    fetch(urlAreas)
        .then(res => res.json())
        .then(areas => {
            areaSelect.disabled = false;
            if (areas.length === 0) {
                areaSelect.innerHTML = '<option value="">-- Tidak ada area di unit ini --</option>';
                return;
            }
            areas.forEach(area => {
                areaSelect.innerHTML += `<option value="${area.id_area_kerja}">${area.nama_area}</option>`;
            });
        })
        .catch(err => {
            console.error('Gagal ambil area:', err);
            areaSelect.innerHTML = '<option value="">-- Gagal memuat area --</option>';
            areaSelect.disabled = false;
            const errorDiv = document.getElementById('approveError');
            errorDiv.innerText = 'Gagal memuat daftar area. Periksa koneksi atau hubungi admin.';
            errorDiv.classList.remove('hidden');
        });

    document.getElementById('stok_tersedia').value = '-';
    document.getElementById('approveModal').classList.remove('hidden');
    document.getElementById('approveModal').classList.add('flex');
}

function fetchStokByArea(idArea) {
    const stokInput = document.getElementById('stok_tersedia');
    const infoDiv = document.getElementById('approveInfo');
    const errorDiv = document.getElementById('approveError');
    stokInput.value = 'Loading...';
    infoDiv.classList.add('hidden');
    errorDiv.classList.add('hidden');

    if (!idArea || !currentIdBarang) {
        stokInput.value = '-';
        return;
    }

    // URL manual untuk cek-stok-unit
    const urlStok = "{{ url('stock-ctl/antar-unit/cek-stok-unit') }}" + "?id_barang=" + currentIdBarang + "&id_area=" + idArea;
    fetch(urlStok)
        .then(res => res.json())
        .then(data => {
            stokInput.value = number_format(data.stok) + ' ' + currentSatuan;
            if (data.stok < currentJumlahDiminta) {
                infoDiv.innerText = `⚠️ Peringatan: Stok tersedia hanya ${data.stok} ${currentSatuan}, kurang dari permintaan ${currentJumlahDiminta} ${currentSatuan}. Anda dapat mengurangi jumlah.`;
                infoDiv.classList.remove('hidden');
            } else {
                infoDiv.classList.add('hidden');
            }
        })
        .catch(err => {
            console.error('Fetch stok error:', err);
            stokInput.value = 'Gagal mengambil stok';
            errorDiv.innerText = 'Gagal mengambil stok. Periksa koneksi atau hubungi admin.';
            errorDiv.classList.remove('hidden');
        });
}

function showRejectModal(id) {
    document.getElementById('reject_request_id').value = id;
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

function number_format(num) {
    return new Intl.NumberFormat('id-ID').format(num);
}

document.getElementById('id_area_pengirim').addEventListener('change', function() {
    const selectedArea = this.value;
    if (selectedArea) {
        fetchStokByArea(selectedArea);
    } else {
        document.getElementById('stok_tersedia').value = '-';
    }
});

document.getElementById('approveForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const id = document.getElementById('request_id').value;
    const form = this;
    const url = "{{ url('stock-ctl/antar-unit') }}/" + id + "/approve";
    const formData = new FormData(form);
    const submitBtn = document.getElementById('approveSubmitBtn');
    const errorDiv = document.getElementById('approveError');
    const areaSelect = document.getElementById('id_area_pengirim');

    if (!areaSelect.value) {
        errorDiv.innerText = 'Pilih area pengirim terlebih dahulu.';
        errorDiv.classList.remove('hidden');
        return;
    }

    submitBtn.disabled = true;
    submitBtn.innerText = 'Memproses...';
    errorDiv.classList.add('hidden');

    fetch(url, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
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

document.getElementById('rejectForm').addEventListener('submit', function(e) {
    const id = document.getElementById('reject_request_id').value;
    this.action = "{{ url('stock-ctl/antar-unit') }}/" + id + "/reject";
});
</script>
@endsection