@extends('layouts.app_stock_sidebar')

@section('content')
<div class="space-y-6 text-sm text-gray-800 font-sans max-w-2xl">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold text-gray-800">Barang Masuk</h2>
        <a href="{{ route('stock-ctl.stok.index') }}" class="text-blue-600 hover:underline">← Kembali</a>
    </div>

    <div class="bg-white border rounded-xl p-6">
        <form method="POST" action="{{ route('stock-ctl.transaksi.masuk.store') }}" id="form-barang-masuk">
            @csrf

            {{-- Area Tujuan --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-600 mb-1">Area Tujuan</label>
                <input type="text"
                       id="area_name"
                       name="area_name"
                       list="area-list"
                       value="{{ old('area_name') ?: ($areas->where('id_area_kerja', old('id_area_tujuan'))->first()->nama_area ?? '') }}"
                       class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 @error('id_area_tujuan') border-red-500 @enderror"
                       placeholder="Ketik nama area..."
                       autocomplete="off"
                       required>

                <input type="hidden" name="id_area_tujuan" id="id_area_tujuan" value="{{ old('id_area_tujuan') }}">

                <datalist id="area-list">
                    @foreach($areas as $area)
                        <option value="{{ $area->nama_area }}" data-id="{{ $area->id_area_kerja }}">
                    @endforeach
                </datalist>

                <div id="area-error" class="text-red-500 text-xs mt-1 hidden">Area tidak valid. Pilih dari daftar yang tersedia.</div>
                @error('id_area_tujuan') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Barang --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-600 mb-1">Barang</label>
                <input type="text"
                       id="barang_name"
                       name="barang_name"
                       list="barang-list"
                       value="{{ old('barang_name') ?: ($barang->where('id_barang', old('id_barang'))->first()->nama_barang ?? '') }}"
                       class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 @error('id_barang') border-red-500 @enderror"
                       placeholder="Ketik nama atau kode barang..."
                       autocomplete="off"
                       required>

                <input type="hidden" name="id_barang" id="id_barang" value="{{ old('id_barang') }}">

                <datalist id="barang-list">
                    @foreach($barang as $b)
                        <option value="{{ $b->nama_barang }}" data-id="{{ $b->id_barang }}" data-kode="{{ $b->kode_barang }}">
                    @endforeach
                </datalist>

                <div id="barang-error" class="text-red-500 text-xs mt-1 hidden">Barang tidak valid. Pilih dari daftar yang tersedia.</div>
                @error('id_barang') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Tampilkan stok tersedia di area tujuan --}}
            <div class="mb-4 p-3 bg-gray-50 rounded-lg">
                <span class="font-medium">Stok tersedia di area tujuan:</span>
                <span id="stok_tersedia" class="ml-2 text-blue-600 font-bold">-</span>
            </div>

            {{-- Jumlah --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-600 mb-1">Jumlah</label>
                <input type="number" step="0.01" name="jumlah" id="jumlah" value="{{ old('jumlah') }}" 
                       class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                @error('jumlah') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Nomor Referensi --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-600 mb-1">Nomor Referensi (Faktur)</label>
                <input type="text" name="no_ref" value="{{ old('no_ref') }}" 
                       class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>

            {{-- Keterangan --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-600 mb-1">Keterangan</label>
                <textarea name="keterangan" rows="3" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">{{ old('keterangan') }}</textarea>
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('stock-ctl.stok.index') }}" class="px-4 py-2 bg-gray-200 rounded-lg">Batal</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // ========== VALIDASI AREA ==========
        const areaInput = document.getElementById('area_name');
        const areaIdHidden = document.getElementById('id_area_tujuan');
        const areaDatalist = document.getElementById('area-list');
        const areaError = document.getElementById('area-error');

        function validateArea() {
            const typed = areaInput.value.trim();
            const options = areaDatalist.options;
            let found = false;
            let foundId = null;
            for (let opt of options) {
                if (opt.value === typed) {
                    found = true;
                    foundId = opt.dataset.id;
                    break;
                }
            }
            if (found && foundId) {
                areaIdHidden.value = foundId;
                areaInput.classList.remove('border-red-500');
                areaError.classList.add('hidden');
                return true;
            } else {
                if (typed === '') {
                    areaIdHidden.value = '';
                    areaInput.classList.remove('border-red-500');
                    areaError.classList.add('hidden');
                    return true;
                }
                areaIdHidden.value = '';
                areaInput.classList.add('border-red-500');
                areaError.classList.remove('hidden');
                return false;
            }
        }

        areaInput.addEventListener('input', validateArea);
        areaInput.addEventListener('blur', validateArea);

        // ========== VALIDASI BARANG ==========
        const barangInput = document.getElementById('barang_name');
        const barangIdHidden = document.getElementById('id_barang');
        const barangDatalist = document.getElementById('barang-list');
        const barangError = document.getElementById('barang-error');

        function validateBarang() {
            const typed = barangInput.value.trim();
            const options = barangDatalist.options;
            let found = false;
            let foundId = null;
            for (let opt of options) {
                if (opt.value === typed) {
                    found = true;
                    foundId = opt.dataset.id;
                    break;
                }
            }
            if (found && foundId) {
                barangIdHidden.value = foundId;
                barangInput.classList.remove('border-red-500');
                barangError.classList.add('hidden');
                return true;
            } else {
                if (typed === '') {
                    barangIdHidden.value = '';
                    barangInput.classList.remove('border-red-500');
                    barangError.classList.add('hidden');
                    return true;
                }
                barangIdHidden.value = '';
                barangInput.classList.add('border-red-500');
                barangError.classList.remove('hidden');
                return false;
            }
        }

        barangInput.addEventListener('input', validateBarang);
        barangInput.addEventListener('blur', validateBarang);

        // ========== CEK STOK ==========
        const stokSpan = document.getElementById('stok_tersedia');
        const jumlahInput = document.getElementById('jumlah');

        function fetchStok() {
            const idBarang = document.getElementById('id_barang').value;
            const idArea = document.getElementById('id_area_tujuan').value;

            if (!idBarang || !idArea) {
                stokSpan.innerText = '-';
                return;
            }

            stokSpan.innerText = 'Loading...';

            fetch(`{{ route('stock-ctl.cek-stok') }}?id_barang=${idBarang}&id_area=${idArea}`)
                .then(response => response.json())
                .then(data => {
                    stokSpan.innerText = data.stok;
                    // Validasi jumlah tidak melebihi stok
                    if (jumlahInput.value && parseFloat(jumlahInput.value) > data.stok) {
                        jumlahInput.setCustomValidity('Jumlah melebihi stok tersedia');
                    } else {
                        jumlahInput.setCustomValidity('');
                    }
                })
                .catch(() => {
                    stokSpan.innerText = 'Gagal ambil stok';
                });
        }

        areaInput.addEventListener('change', fetchStok);
        barangInput.addEventListener('change', fetchStok);

        // Event saat jumlah diubah
        jumlahInput.addEventListener('input', function() {
            const stok = parseFloat(stokSpan.innerText);
            if (!isNaN(stok) && this.value && parseFloat(this.value) > stok) {
                this.setCustomValidity('Jumlah melebihi stok tersedia');
            } else {
                this.setCustomValidity('');
            }
        });

        // ========== VALIDASI SAAT SUBMIT ==========
        const form = document.getElementById('form-barang-masuk');
        form.addEventListener('submit', function(e) {
            const areaValid = validateArea();
            const barangValid = validateBarang();
            if (!areaValid || !barangValid) {
                e.preventDefault();
                let msg = '';
                if (!areaValid) msg += 'Silakan pilih area dari daftar yang tersedia.\n';
                if (!barangValid) msg += 'Silakan pilih barang dari daftar yang tersedia.';
                alert(msg);
                if (!areaValid) areaInput.focus();
                else if (!barangValid) barangInput.focus();
            }
        });
    });
</script>
@endsection