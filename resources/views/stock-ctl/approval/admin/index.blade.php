@extends('layouts.app_stock_sidebar')

@section('content')
<div class="space-y-6 text-sm text-gray-800 font-sans">
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Approval Admin</h2>
            @if($pendingCount > 0)
                <span class="mt-1 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                    {{ $pendingCount }} menunggu admin
                </span>
            @endif
        </div>
        <button id="toggleFilterBtn" type="button"
                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-200 transition">
            Filters
        </button>
    </div>

    @if(session('success'))
        <div class="px-4 py-3 rounded-lg bg-green-50 border border-green-200 text-green-800 text-sm">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">{{ $errors->first() }}</div>
    @endif

    {{-- TAB STATUS (sama dengan stock-ctl/permintaan) --}}
    <div class="flex flex-wrap gap-2">
        @foreach($statuses as $key => $label)
            <a href="{{ route('stock-ctl.approval.admin.index', array_merge(request()->except(['status', 'page']), ['status' => $key])) }}"
               class="px-4 py-2 rounded-lg text-sm font-semibold transition inline-flex items-center gap-2
                      {{ $status === $key ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                {{ $label }}
                <span class="px-1.5 py-0.5 rounded-full text-xs font-semibold
                             {{ $status === $key ? 'bg-white/20 text-white' : 'bg-white text-gray-600' }}">
                    {{ number_format($counts[$key] ?? 0) }}
                </span>
            </a>
        @endforeach
    </div>

    {{-- FILTER --}}
    @php
        $adaFilter = request()->hasAny(['search', 'pemohon', 'id_area_kerja', 'id_bisnis_unit', 'dari', 'sampai']);
        $kolom = 'lg:grid-cols-3';
    @endphp
    <div id="filterSection" class="bg-white border rounded-xl p-3 sm:p-4 {{ $adaFilter ? '' : 'hidden' }}">
        <form method="GET" action="{{ route('stock-ctl.approval.admin.index') }}"
              class="grid grid-cols-1 sm:grid-cols-2 {{ $kolom }} gap-3 sm:gap-4">
            {{-- tab status yang sedang aktif ikut terkirim --}}
            <input type="hidden" name="status" value="{{ $status }}">

            <div>
                <label class="text-xs sm:text-sm font-medium text-gray-600">Cari Barang</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama / kode barang"
                       class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="text-xs sm:text-sm font-medium text-gray-600">Nama Pemohon</label>
                <input type="text" name="pemohon" value="{{ request('pemohon') }}" placeholder="Cari nama pemohon"
                       class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>

            {{-- Unit bisnis: hanya superadmin --}}
            @if($bisnisUnits->isNotEmpty())
            <div>
                <label class="text-xs sm:text-sm font-medium text-gray-600">Unit Bisnis</label>
                <select name="id_bisnis_unit"
                        class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Unit</option>
                    @foreach($bisnisUnits as $bu)
                        <option value="{{ $bu->id_bisnis_unit }}" {{ request('id_bisnis_unit') == $bu->id_bisnis_unit ? 'selected' : '' }}>
                            {{ $bu->nama_bisnis_unit }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div>
                <label class="text-xs sm:text-sm font-medium text-gray-600">Area Kerja</label>
                <select name="id_area_kerja"
                        class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Area</option>
                    @foreach($areas as $area)
                        <option value="{{ $area->id_area_kerja }}" {{ request('id_area_kerja') == $area->id_area_kerja ? 'selected' : '' }}>
                            {{ $area->nama_area }}@if($bisnisUnits->isNotEmpty() && $area->bisnisUnit) ({{ $area->bisnisUnit->nama_bisnis_unit }})@endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs sm:text-sm font-medium text-gray-600">Dari Tanggal</label>
                <input type="date" name="dari" value="{{ request('dari') }}"
                       class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="text-xs sm:text-sm font-medium text-gray-600">Sampai Tanggal</label>
                <input type="date" name="sampai" value="{{ request('sampai') }}"
                       class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="sm:col-span-2 lg:col-span-3 flex flex-col sm:flex-row gap-2 justify-end">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700">Apply</button>
                <a href="{{ route('stock-ctl.approval.admin.index', ['status' => $status]) }}"
                   class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-300 text-center">Reset</a>
            </div>
        </form>
    </div>

    {{-- TABEL --}}
    @php
        $fmt = fn($d) => $d ? \Carbon\Carbon::parse($d)->timezone('Asia/Jakarta')->format('d M Y H:i') : null;
        $bolehAksi = $status === 'pending_admin';
        $judulProses = [
            'pending_l1'    => 'Menunggu Atasan (L1)',
            'pending_admin' => 'Approval L1',
            'disetujui'     => 'Riwayat Approval',
            'ditolak'       => 'Penolakan',
            'dibatalkan'    => 'Pembatalan',
        ][$status] ?? 'Proses';
    @endphp
    <div class="bg-white border rounded-xl overflow-x-auto">
        <table class="w-full text-sm min-w-[900px]">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">No. Permintaan</th>
                    <th class="px-4 py-3 text-left">Pemohon</th>
                    <th class="px-4 py-3 text-left">Area</th>
                    <th class="px-4 py-3 text-left">Barang</th>
                    <th class="px-4 py-3 text-left">Jumlah</th>
                    <th class="px-4 py-3 text-left">Keterangan</th>
                    <th class="px-4 py-3 text-left">{{ $judulProses }}</th>
                    @if($bolehAksi)
                        <th class="px-4 py-3 text-left">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($permintaan as $item)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-mono text-sm">{{ $item->no_tampil }}</td>
                    <td class="px-4 py-3">
                        <div class="flex flex-col">
                            <span class="font-medium text-gray-800">{{ $item->pemohon->name ?? '-' }}</span>
                            <span class="text-xs text-gray-500">{{ $fmt($item->tanggal_permintaan) }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        {{ $item->areaKerja->nama_area ?? '-' }}
                        @if($item->areaKerja && $item->areaKerja->bisnisUnit)
                            ({{ $item->areaKerja->bisnisUnit->nama_bisnis_unit }})
                        @endif
                    </td>
                    <td class="px-4 py-3">{{ $item->barang->nama_barang ?? '-' }}</td>
                    <td class="px-4 py-3">{{ number_format($item->jumlah) }} {{ $item->barang->satuan ?? '' }}</td>
                    <td class="px-4 py-3">{{ $item->keterangan ?? '-' }}</td>

                    {{-- Kolom proses: isinya menyesuaikan tab --}}
                    <td class="px-4 py-3">
                        @switch($status)
                            @case('pending_l1')
                                <div class="flex flex-col">
                                    <span class="font-medium text-gray-800">{{ $item->pemohon->profil->approver->name ?? '-' }}</span>
                                    <span class="text-xs text-gray-500">belum diproses atasan</span>
                                </div>
                                @break

                            @case('pending_admin')
                                @if($item->approverL1)
                                    <div class="flex flex-col">
                                        <span class="font-medium text-gray-800">{{ $item->approverL1->name }}</span>
                                        @if($item->approved_l1_at)
                                            <span class="text-xs text-gray-500">{{ $fmt($item->approved_l1_at) }}</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                                @break

                            @case('disetujui')
                                <div class="flex flex-col gap-1">
                                    <div>
                                        <span class="text-xs text-gray-500">L1:</span>
                                        <span class="font-medium text-gray-800">{{ $item->approverL1->name ?? '-' }}</span>
                                        @if($item->approved_l1_at)<span class="text-xs text-gray-500">({{ $fmt($item->approved_l1_at) }})</span>@endif
                                    </div>
                                    <div>
                                        <span class="text-xs text-gray-500">Admin:</span>
                                        <span class="font-medium text-gray-800">{{ $item->approverAdmin->name ?? '-' }}</span>
                                        @if($item->approved_admin_at)<span class="text-xs text-gray-500">({{ $fmt($item->approved_admin_at) }})</span>@endif
                                    </div>
                                    @if($item->catatan_admin)
                                        <span class="text-xs text-gray-600">Catatan: {{ $item->catatan_admin }}</span>
                                    @endif
                                </div>
                                @break

                            @case('ditolak')
                                <div class="flex flex-col">
                                    <span class="font-medium text-gray-800">{{ $item->rejectedBy->name ?? '-' }}</span>
                                    @if($item->rejected_at)<span class="text-xs text-gray-500">{{ $fmt($item->rejected_at) }}</span>@endif
                                    <span class="text-xs text-red-600">{{ $item->alasan_tolak ?? '-' }}</span>
                                </div>
                                @break

                            @case('dibatalkan')
                                <div class="flex flex-col">
                                    @if($item->rejected_at)<span class="font-medium text-gray-800">{{ $fmt($item->rejected_at) }}</span>@endif
                                    <span class="text-xs text-gray-500">{{ $item->alasan_tolak ?? 'Dibatalkan oleh pemohon' }}</span>
                                </div>
                                @break
                        @endswitch
                    </td>

                    @if($bolehAksi)
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
                    @endif
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $bolehAksi ? 8 : 7 }}" class="py-10 text-center text-gray-500">
                        @if($adaFilter)
                            Data tidak ditemukan
                        @else
                            Tidak ada permintaan {{ strtolower($statuses[$status]) }}
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($permintaan->hasPages())
        <div class="mt-4">{{ $permintaan->links() }}</div>
    @endif
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

document.getElementById('toggleFilterBtn')?.addEventListener('click', () => {
    document.getElementById('filterSection').classList.toggle('hidden');
});

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
            window.location.href = "{{ route('stock-ctl.approval.admin.index', ['status' => 'pending_admin']) }}";
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