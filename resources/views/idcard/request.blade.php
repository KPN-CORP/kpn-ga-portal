@extends('layouts.app-sidebar-card')
@section('content')
<div class="bg-white shadow rounded-lg p-6">
    <h2 class="text-2xl font-semibold mb-4 text-left">Request ID Card</h2>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form action="{{ route('idcard.store') }}" method="POST" enctype="multipart/form-data" id="idcardForm">
        @csrf

        <div class="space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Kategori *</label>
                    <select name="kategori" id="kategori" class="w-full border border-gray-300 p-2 rounded text-sm" required>
                        <option value="">Pilih Kategori</option>
                        @foreach(\App\Models\IDCard\RequestIdCard::KATEGORI_LABELS as $value => $label)
                            <option value="{{ $value }}" {{ old('kategori') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Nama *</label>
                    <input type="text" name="nama" id="nama" value="{{ old('nama') }}" maxlength="100" class="w-full border border-gray-300 p-2 rounded text-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">NIK *</label>
                    <input type="text" name="nik" id="nik" class="w-full border border-gray-300 p-2 rounded text-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Bisnis Unit *</label>
                    <select name="bisnis_unit_id" id="bisnis_unit_id" class="w-full border border-gray-300 p-2 rounded text-sm" required>
                        <option value="">Pilih Bisnis Unit</option>
                        @foreach($bisnisUnits as $unit)
                            <option value="{{ $unit->id_bisnis_unit }}" {{ old('bisnis_unit_id') == $unit->id_bisnis_unit ? 'selected' : '' }}>{{ $unit->nama_bisnis_unit }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                <div id="tanggalJoinContainer">
                    <label class="block text-sm font-medium mb-1">Tanggal Join *</label>
                    <input type="date" name="tanggal_join" id="tanggal_join" value="{{ old('tanggal_join') }}" class="w-full border border-gray-300 p-2 rounded text-sm" required>
                </div>
                <div id="fotoContainer">
                    <label class="block text-sm font-medium mb-1">Foto * <span class="text-red-500">(Maks: 10MB)</span></label>
                    <input type="file" name="foto" id="foto" class="w-full border border-gray-300 p-2 rounded text-sm" accept=".jpg,.jpeg,.png" required>
                    <p class="text-xs text-gray-500 mt-1">Format: jpg, jpeg, png (maksimal: 10MB)</p>
                    <p id="fotoError" class="hidden text-xs text-red-600 mt-1"></p>
                    <img id="fotoPreview" src="" alt="Preview foto" class="hidden mt-2 h-40 w-auto rounded border object-contain bg-gray-50">
                </div>
                <div id="kategoriExtra">
                    <!-- Dynamic fields akan muncul di sini -->
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Lantai Kerja / Keterangan *</label>
                <input type="text" name="keterangan" id="keterangan" value="{{ old('keterangan') }}" maxlength="255" class="w-full border border-gray-300 p-2 rounded text-sm" placeholder="Contoh: Lantai 42" required>
            </div>
        </div>

        <div class="text-center mt-8 pt-4 border-t">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded text-sm">Simpan Request</button>
            <a href="{{ route('idcard.index') }}" class="ml-2 bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded text-sm">Batal</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const kategoriSelect = document.getElementById('kategori');
    const kategoriExtra = document.getElementById('kategoriExtra');
    const tanggalJoinContainer = document.getElementById('tanggalJoinContainer');
    const tanggalJoinInput = document.getElementById('tanggal_join');
    const fotoContainer = document.getElementById('fotoContainer');
    const fotoInput = document.getElementById('foto');
    const nikInput = document.getElementById('nik');

    function generateNikMagang() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const counter = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
        return `Intern${year}${month}${day}${counter}`;
    }

    function generateNomorKartuMagang() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
        return `MAG${year}${month}${day}${random}`;
    }

    function handleKategoriChange() {
        const kategori = kategoriSelect.value;
        kategoriExtra.innerHTML = '';
        tanggalJoinContainer.style.display = 'block';
        tanggalJoinInput.required = true;
        fotoContainer.style.display = 'block';
        fotoInput.required = true;
        nikInput.readOnly = false;
        nikInput.style.backgroundColor = '';

        if (kategori === 'magang' || kategori === 'magang_extend') {
            tanggalJoinContainer.style.display = 'none';
            tanggalJoinInput.required = false;
            tanggalJoinInput.value = '';
            fotoContainer.style.display = 'none';
            fotoInput.required = false;
            fotoInput.value = '';
            resetFotoPreview();

            let nomorKartu = '';
            if (kategori === 'magang') {
                nikInput.value = generateNikMagang();
                nomorKartu = generateNomorKartuMagang();
                nikInput.readOnly = true;
                nikInput.style.backgroundColor = '#f3f4f6';
            } else {
                nikInput.value = '';
                nikInput.placeholder = 'Masukkan NIK Magang yang sudah ada';
            }

            kategoriExtra.innerHTML = `
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Masa Berlaku *</label>
                            <input type="date" name="masa_berlaku" class="w-full border border-gray-300 p-2 rounded text-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Sampai Tanggal *</label>
                            <input type="date" name="sampai_tanggal" class="w-full border border-gray-300 p-2 rounded text-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Nomor Kartu *</label>
                            <input type="text" name="nomor_kartu" class="w-full border border-gray-300 p-2 rounded text-sm" value="${nomorKartu}" ${kategori === 'magang' ? 'readonly style="background-color:#f3f4f6;"' : ''} required>
                        </div>
                    </div>
                </div>
            `;
        } else if (kategori === 'ganti_kartu') {
            kategoriExtra.innerHTML = `
                <div>
                    <label class="block text-sm font-medium mb-1">Bukti Bayar * <span class="text-red-500">(Maks: 10MB)</span></label>
                    <input type="file" name="bukti_bayar" class="w-full border border-gray-300 p-2 rounded text-sm" accept=".jpg,.jpeg,.png,.pdf" required>
                    <p class="text-xs text-gray-500 mt-1">Format: jpg, jpeg, png, pdf (maksimal: 10MB)</p>
                </div>
            `;
        } else {
            // karyawan_baru, karyawan_mutasi, perubahan_lantai
            // Reset
        }
    }

    // ---- Preview foto + cek ukuran di browser (maks 10MB) ----
    const fotoPreview = document.getElementById('fotoPreview');
    const fotoError   = document.getElementById('fotoError');

    function resetFotoPreview() {
        if (fotoPreview.src.startsWith('blob:')) URL.revokeObjectURL(fotoPreview.src);
        fotoPreview.removeAttribute('src');
        fotoPreview.classList.add('hidden');
        fotoError.classList.add('hidden');
    }

    fotoInput.addEventListener('change', function () {
        resetFotoPreview();
        const file = fotoInput.files[0];
        if (!file) return;
        if (file.size > 10 * 1024 * 1024) {
            fotoError.textContent = 'Ukuran file terlalu besar (maksimal 10MB).';
            fotoError.classList.remove('hidden');
            fotoInput.value = '';
            return;
        }
        fotoPreview.src = URL.createObjectURL(file);
        fotoPreview.classList.remove('hidden');
    });

    // ---- Cegah submit ganda ----
    const idcardForm = document.getElementById('idcardForm');
    const submitBtn  = idcardForm.querySelector('button[type="submit"]');
    idcardForm.addEventListener('submit', function () {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Menyimpan...';
    });
    window.addEventListener('pageshow', function () {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Simpan Request';
    });

    kategoriSelect.addEventListener('change', handleKategoriChange);
    handleKategoriChange();
});
</script>
@endsection