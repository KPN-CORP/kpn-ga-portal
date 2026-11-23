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
                        {{-- "Magang Extend" tidak ditampilkan: dipilih lewat radio New/Extend di bawah --}}
                        @foreach(\Illuminate\Support\Arr::except(\App\Models\IDCard\RequestIdCard::KATEGORI_LABELS, ['magang_extend']) as $value => $label)
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

            {{-- Pilihan New / Extend (Magang & Visitor) --}}
            <div id="modeBox" class="hidden">
                <label class="block text-sm font-medium mb-1">Jenis Request *</label>
                <div class="flex flex-wrap gap-6 text-sm">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="mode" value="new" id="modeNew" checked>
                        <span id="modeNewLabel">New</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="mode" value="extend" id="modeExtend">
                        <span id="modeExtendLabel">Extend</span>
                    </label>
                </div>
            </div>

            {{-- Cari data lama (hanya tampil saat mode Extend) --}}
            <div id="extendBox" class="hidden border border-blue-200 bg-blue-50 rounded p-4">
                <label class="block text-sm font-medium mb-1">Cari data lama (nama / NIK / nomor kartu)</label>
                <div class="relative">
                    <input type="text" id="extendSearch" autocomplete="off" placeholder="Ketik minimal 2 huruf..." class="w-full border border-gray-300 p-2 rounded text-sm bg-white">
                    <div id="extendResults" class="hidden absolute z-20 left-0 right-0 mt-1 border border-gray-300 rounded bg-white shadow-lg max-h-64 overflow-auto divide-y text-sm"></div>
                </div>
                <p id="extendHint" class="text-xs text-gray-600 mt-1"></p>
                <div id="extendPicked" class="hidden mt-2 flex items-start justify-between gap-3 bg-white border border-green-300 text-green-800 text-sm rounded px-3 py-2">
                    <div id="extendPickedText"></div>
                    <button type="button" id="extendClear" class="text-red-600 hover:text-red-800 text-xs whitespace-nowrap">Ganti pilihan</button>
                </div>
                <input type="hidden" name="extend_from_id" id="extend_from_id" value="">
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
                <input type="text" name="keterangan" id="keterangan" value="{{ old('keterangan') }}" maxlength="255" class="w-full border border-gray-300 p-2 rounded text-sm" placeholder="Contoh: Lantai 42 (untuk Visitor: lantai yang dituju)" required>
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
    const idcardForm = document.getElementById('idcardForm');
    const kategoriSelect = document.getElementById('kategori');
    const kategoriExtra = document.getElementById('kategoriExtra');
    const tanggalJoinContainer = document.getElementById('tanggalJoinContainer');
    const tanggalJoinInput = document.getElementById('tanggal_join');
    const fotoContainer = document.getElementById('fotoContainer');
    const fotoInput = document.getElementById('foto');
    const nikInput = document.getElementById('nik');
    const namaInput = document.getElementById('nama');
    const buInput = document.getElementById('bisnis_unit_id');
    const ketInput = document.getElementById('keterangan');

    // ---- Mode New / Extend ----
    const modeBox = document.getElementById('modeBox');
    const modeNew = document.getElementById('modeNew');
    const modeExtend = document.getElementById('modeExtend');
    const modeNewLabel = document.getElementById('modeNewLabel');
    const modeExtendLabel = document.getElementById('modeExtendLabel');

    // ---- Pencarian data lama ----
    const extendBox = document.getElementById('extendBox');
    const extendSearch = document.getElementById('extendSearch');
    const extendResults = document.getElementById('extendResults');
    const extendHint = document.getElementById('extendHint');
    const extendPicked = document.getElementById('extendPicked');
    const extendPickedText = document.getElementById('extendPickedText');
    const extendClear = document.getElementById('extendClear');
    const extendFromId = document.getElementById('extend_from_id');
    const SEARCH_URL = @json(route('idcard.search-existing'));

    let searchTimer = null;
    let searchSeq = 0;

    function getMode() {
        return modeExtend.checked ? 'extend' : 'new';
    }

    function isPeriodeKategori(k) {
        return k === 'magang' || k === 'visitor';
    }

    function generateNikMagang() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const counter = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
        return `Intern${year}${month}${day}${counter}`;
    }

    function generateNikVisitor() {
        const now = new Date();
        const ymd = now.getFullYear() + String(now.getMonth() + 1).padStart(2, '0') + String(now.getDate()).padStart(2, '0');
        return `Visitor${ymd}${Math.floor(Math.random() * 1000).toString().padStart(3, '0')}`;
    }

    function generateNomorKartuMagang() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
        return `MAG${year}${month}${day}${random}`;
    }

    function lockNik(locked) {
        nikInput.readOnly = locked;
        nikInput.style.backgroundColor = locked ? '#f3f4f6' : '';
    }

    function fmtTanggal(ymd) {
        if (!ymd) return '-';
        const p = ymd.split('-');
        return p.length === 3 ? `${p[2]}-${p[1]}-${p[0]}` : ymd;
    }

    // ---- Extend: reset & pilih data lama ----
    function removeVisitorNomorHidden() {
        const h = document.getElementById('visitorNomorHidden');
        if (h) h.remove();
    }

    function resetPicked() {
        extendFromId.value = '';
        extendPicked.classList.add('hidden');
        extendPickedText.textContent = '';
        extendSearch.value = '';
        extendSearch.disabled = false;
        extendResults.classList.add('hidden');
        extendResults.innerHTML = '';
        removeVisitorNomorHidden();
    }

    function clearAutoFilled() {
        namaInput.value = '';
        ketInput.value = '';
        buInput.value = '';
    }

    function pickItem(item, kategori) {
        extendFromId.value = item.id;

        namaInput.value = item.nama || '';
        nikInput.value = item.nik || '';
        lockNik(true);
        if (item.bisnis_unit_id) buInput.value = String(item.bisnis_unit_id);
        ketInput.value = item.keterangan || '';

        const mulai = kategoriExtra.querySelector('input[name="masa_berlaku"]');
        const sampai = kategoriExtra.querySelector('input[name="sampai_tanggal"]');
        if (mulai) mulai.value = item.masa_berlaku_baru || '';
        if (sampai) { sampai.value = ''; sampai.min = item.masa_berlaku_baru || ''; sampai.focus(); }

        if (kategori === 'visitor') {
            // Nomor kartu visitor biasanya diisi saat approve; bawa nomor lama sebagai usulan
            removeVisitorNomorHidden();
            if (item.nomor_kartu) {
                const h = document.createElement('input');
                h.type = 'hidden';
                h.name = 'nomor_kartu';
                h.id = 'visitorNomorHidden';
                h.value = item.nomor_kartu;
                idcardForm.appendChild(h);
            }
        } else {
            const nomor = kategoriExtra.querySelector('input[name="nomor_kartu"]');
            if (nomor) nomor.value = item.nomor_kartu || '';
        }

        extendPickedText.textContent = '';
        const b = document.createElement('strong');
        b.textContent = `${item.nama} (${item.nik})`;
        extendPickedText.appendChild(b);
        extendPickedText.appendChild(document.createElement('br'));
        extendPickedText.appendChild(document.createTextNode(
            `Masa lama: ${fmtTanggal(item.masa_berlaku)} s/d ${fmtTanggal(item.sampai_tanggal)}` +
            (item.nomor_kartu ? ` | Kartu: ${item.nomor_kartu}` : '') +
            ` | Mulai baru disarankan: ${fmtTanggal(item.masa_berlaku_baru)}`
        ));

        extendPicked.classList.remove('hidden');
        extendResults.classList.add('hidden');
        extendSearch.value = '';
        extendSearch.disabled = true;
    }

    function renderResults(list, kategori) {
        extendResults.innerHTML = '';
        if (!list.length) {
            const empty = document.createElement('div');
            empty.className = 'px-3 py-2 text-gray-500';
            empty.textContent = 'Tidak ada data yang cocok (hanya yang sudah disetujui).';
            extendResults.appendChild(empty);
        } else {
            list.forEach(function (item) {
                const row = document.createElement('button');
                row.type = 'button';
                row.className = 'w-full text-left px-3 py-2 hover:bg-blue-50';

                const l1 = document.createElement('div');
                l1.className = 'font-medium';
                l1.textContent = `${item.nama} - ${item.nik}`;

                const l2 = document.createElement('div');
                l2.className = 'text-xs text-gray-600';
                l2.textContent = [
                    item.bisnis_unit_nama,
                    item.periode_range ? `Masa: ${item.periode_range}` : null,
                    item.nomor_kartu ? `Kartu: ${item.nomor_kartu}` : null,
                    item.periode_label
                ].filter(Boolean).join(' | ');

                row.appendChild(l1);
                row.appendChild(l2);
                row.addEventListener('click', function () { pickItem(item, kategori); });
                extendResults.appendChild(row);
            });
        }
        extendResults.classList.remove('hidden');
    }

    function doSearch() {
        const kategori = kategoriSelect.value;
        const jenis = kategori === 'visitor' ? 'visitor' : 'magang';
        const q = extendSearch.value.trim();
        if (q.length < 2) { extendResults.classList.add('hidden'); return; }

        const seq = ++searchSeq;
        fetch(`${SEARCH_URL}?jenis=${encodeURIComponent(jenis)}&q=${encodeURIComponent(q)}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
        .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
        .then(function (list) { if (seq === searchSeq) renderResults(list, kategori); })
        .catch(function () {
            if (seq !== searchSeq) return;
            extendResults.innerHTML = '<div class="px-3 py-2 text-red-600">Gagal mengambil data. Coba lagi.</div>';
            extendResults.classList.remove('hidden');
        });
    }

    extendSearch.addEventListener('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(doSearch, 300);
    });

    document.addEventListener('click', function (e) {
        if (!extendBox.contains(e.target)) extendResults.classList.add('hidden');
    });

    extendClear.addEventListener('click', function () {
        resetPicked();
        clearAutoFilled();
        nikInput.value = '';
        const nomor = kategoriExtra.querySelector('input[name="nomor_kartu"]');
        if (nomor) nomor.value = '';
        extendSearch.focus();
    });

    // ---- Render form sesuai kategori + mode ----
    function render() {
        const kategori = kategoriSelect.value;
        const periode = isPeriodeKategori(kategori);
        const extend = periode && getMode() === 'extend';

        kategoriExtra.innerHTML = '';
        tanggalJoinContainer.style.display = 'block';
        tanggalJoinInput.required = true;
        fotoContainer.style.display = 'block';
        fotoInput.required = true;
        lockNik(false);
        nikInput.placeholder = '';

        modeBox.classList.toggle('hidden', !periode);
        extendBox.classList.toggle('hidden', !extend);

        if (periode) {
            const nama = kategori === 'magang' ? 'Intern' : 'Visitor';
            modeNewLabel.textContent = 'New ' + nama;
            modeExtendLabel.textContent = 'Extend ' + nama;
            extendHint.textContent = kategori === 'visitor'
                ? 'Hanya data visitor yang sudah disetujui. NIK dipakai ulang, tinggal isi masa aktif baru.'
                : 'Hanya data intern yang sudah disetujui. Nama, NIK, bisnis unit, nomor kartu, dan lantai terisi otomatis.';

            tanggalJoinContainer.style.display = 'none';
            tanggalJoinInput.required = false;
            tanggalJoinInput.value = '';
            fotoContainer.style.display = 'none';
            fotoInput.required = false;
            fotoInput.value = '';
            resetFotoPreview();

            let nomorKartu = '';
            if (extend) {
                // NIK hanya boleh dari data lama yang dipilih
                nikInput.value = '';
                nikInput.placeholder = 'Terisi otomatis setelah memilih data lama';
                lockNik(true);
            } else if (kategori === 'visitor') {
                nikInput.value = generateNikVisitor();
                lockNik(true);
            } else {
                nikInput.value = generateNikMagang();
                nomorKartu = generateNomorKartuMagang();
                lockNik(true);
            }

            const nomorReadonly = (kategori === 'magang' && !extend);
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
                        ${kategori === 'visitor' ? '' : `
                        <div>
                            <label class="block text-sm font-medium mb-1">Nomor Kartu *</label>
                            <input type="text" name="nomor_kartu" class="w-full border border-gray-300 p-2 rounded text-sm" value="${nomorKartu}" ${nomorReadonly ? 'readonly style="background-color:#f3f4f6;"' : ''} required>
                        </div>`}
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
            // karyawan_baru, karyawan_mutasi
            // Reset
        }
    }

    function onKategoriChange() {
        if (extendFromId.value) clearAutoFilled();
        resetPicked();
        modeNew.checked = true;   // ganti kategori selalu kembali ke "New"
        render();
    }

    function onModeChange() {
        resetPicked();
        clearAutoFilled();
        render();
        if (getMode() === 'extend') extendSearch.focus();
    }

    modeNew.addEventListener('change', onModeChange);
    modeExtend.addEventListener('change', onModeChange);

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

    // ---- Cegah submit ganda + wajib pilih data lama saat Extend ----
    const submitBtn = idcardForm.querySelector('button[type="submit"]');
    idcardForm.addEventListener('submit', function (e) {
        if (isPeriodeKategori(kategoriSelect.value) && getMode() === 'extend' && !extendFromId.value) {
            e.preventDefault();
            alert('Untuk Extend, cari dan pilih dulu data lama yang sudah ada.');
            extendSearch.focus();
            return;
        }
        submitBtn.disabled = true;
        submitBtn.textContent = 'Menyimpan...';
    });
    window.addEventListener('pageshow', function () {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Simpan Request';
    });

    kategoriSelect.addEventListener('change', onKategoriChange);
    render();
});
</script>
@endsection