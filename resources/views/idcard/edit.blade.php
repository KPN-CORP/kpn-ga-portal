@extends('layouts.app-sidebar-card')
@section('content')
<div class="bg-white shadow rounded-lg p-6">
    <h2 class="text-2xl font-semibold mb-4 text-left">Edit Request ID Card</h2>

    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form action="{{ route('idcard.update', $data->id) }}" method="POST" enctype="multipart/form-data">
        @csrf @method('PUT')

        @if(!empty($lantaiSaja))
            <div class="bg-blue-50 border border-blue-200 text-blue-800 text-sm px-4 py-3 rounded mb-4">
                Kartu ini sudah disetujui. Hanya <strong>Lantai Kerja / Keterangan</strong> yang dapat diubah.
            </div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4 text-sm">
                <div><p class="text-gray-500">Kategori</p><p class="font-medium">{{ $data->kategori_text }}</p></div>
                <div><p class="text-gray-500">Nama</p><p class="font-medium uppercase">{{ $data->nama }}</p></div>
                <div><p class="text-gray-500">NIK</p><p class="font-medium">{{ $data->nik ?? '-' }}</p></div>
                <div><p class="text-gray-500">Bisnis Unit</p><p class="font-medium">{{ optional($bisnisUnits->firstWhere('id_bisnis_unit', $data->bisnis_unit_id))->nama_bisnis_unit ?? '-' }}</p></div>
            </div>
        @else
        @php $kunciVisitor = $data->status !== 'pending'; @endphp
        @if($kunciVisitor)
            <div class="bg-blue-50 border border-blue-200 text-blue-800 text-sm px-4 py-3 rounded mb-4">
                Visitor ini masih aktif, sehingga Anda dapat mengubah lantai, masa aktif, dan nomor kartu. Kategori tidak dapat diubah.
            </div>
            <input type="hidden" name="kategori" value="visitor">
        @endif
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium mb-1">Kategori *</label>
                <select name="kategori" id="kategori" class="w-full border p-2 rounded" required {{ $kunciVisitor ? 'disabled' : '' }}>
                    <option value="">Pilih Kategori</option>
                    @foreach(\App\Models\IDCard\RequestIdCard::KATEGORI_LABELS as $value => $label)
                        <option value="{{ $value }}" {{ old('kategori', $data->kategori) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Nama *</label>
                <input type="text" name="nama" value="{{ old('nama', $data->nama) }}" class="w-full border p-2 rounded" required>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">NIK *</label>
                <input type="text" name="nik" value="{{ old('nik', $data->nik) }}" class="w-full border p-2 rounded {{ $kunciVisitor ? 'bg-gray-100' : '' }}" {{ $kunciVisitor ? 'readonly' : '' }} required>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Bisnis Unit *</label>
                <select name="bisnis_unit_id" class="w-full border p-2 rounded" required>
                    <option value="">Pilih</option>
                    @foreach($bisnisUnits as $unit)
                        <option value="{{ $unit->id_bisnis_unit }}" {{ old('bisnis_unit_id', $data->bisnis_unit_id) == $unit->id_bisnis_unit ? 'selected' : '' }}>{{ $unit->nama_bisnis_unit }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div id="dynamicFields" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4"></div>
        @endif

        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Lantai Kerja / Keterangan *</label>
            <input type="text" name="keterangan" value="{{ old('keterangan', $data->keterangan) }}" class="w-full border p-2 rounded" required>
        </div>

        <div class="text-center mt-6">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded">Simpan Perubahan</button>
            <a href="{{ route('idcard.detail', $data->id) }}" class="ml-2 bg-gray-500 text-white px-6 py-2 rounded">Batal</a>
        </div>
    </form>
</div>

<script>
    const kategoriSelect = document.getElementById('kategori');
    const dynamicFields = document.getElementById('dynamicFields');
    const data = @json($data);

    function renderFields() {
        const val = kategoriSelect.value;
        let html = '';

        if (['karyawan_baru','karyawan_mutasi','ganti_kartu'].includes(val)) {
            html += `
                <div>
                    <label class="block text-sm font-medium mb-1">Tanggal Join *</label>
                    <input type="date" name="tanggal_join" value="{{ old('tanggal_join', $data->tanggal_join) }}" class="w-full border p-2 rounded" required>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Foto</label>
                    <input type="file" name="foto" class="w-full border p-2 rounded" accept=".jpg,.jpeg,.png">
                    @if($data->foto)
                        <p class="text-xs text-gray-500 mt-1">Foto saat ini: <a href="{{ route('idcard.photo', $data->foto) }}" target="_blank" class="text-blue-600">Lihat</a></p>
                    @endif
                </div>
            `;
        }

        if (val === 'ganti_kartu') {
            html += `
                <div>
                    <label class="block text-sm font-medium mb-1">Bukti Bayar</label>
                    <input type="file" name="bukti_bayar" class="w-full border p-2 rounded" accept=".jpg,.jpeg,.png,.pdf">
                    @if($data->bukti_bayar)
                        <p class="text-xs text-gray-500 mt-1">File saat ini: <a href="{{ route('idcard.photo', $data->bukti_bayar) }}" target="_blank" class="text-blue-600">Lihat</a></p>
                    @endif
                </div>
            `;
        }

        if (val === 'magang' || val === 'magang_extend' || val === 'visitor') {
            html += `
                <div>
                    <label class="block text-sm font-medium mb-1">Masa Berlaku *</label>
                    <input type="date" name="masa_berlaku" value="{{ old('masa_berlaku', optional($data->masa_berlaku)->format('Y-m-d')) }}" class="w-full border p-2 rounded" required>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Sampai Tanggal *</label>
                    <input type="date" name="sampai_tanggal" value="{{ old('sampai_tanggal', optional($data->sampai_tanggal)->format('Y-m-d')) }}" class="w-full border p-2 rounded" required>
                </div>
                ${(val === 'visitor' && {{ $data->status === 'pending' ? 'true' : 'false' }}) ? '' : `
                <div>
                    <label class="block text-sm font-medium mb-1">Nomor Kartu *</label>
                    <input type="text" name="nomor_kartu" value="{{ old('nomor_kartu', $data->nomor_kartu) }}" class="w-full border p-2 rounded" required>
                </div>`}
            `;
        }

        dynamicFields.innerHTML = html;
    }

    if (kategoriSelect) {
        kategoriSelect.addEventListener('change', renderFields);
        renderFields();
    }

    // cegah submit ganda
    const editForm = document.querySelector('form[method="POST"]');
    editForm.addEventListener('submit', function () {
        const b = editForm.querySelector('button[type="submit"]');
        b.disabled = true;
        b.textContent = 'Menyimpan...';
    });
    window.addEventListener('pageshow', function () {
        const b = editForm.querySelector('button[type="submit"]');
        b.disabled = false;
        b.textContent = 'Simpan Perubahan';
    });
</script>
@endsection