@extends('layouts.app_stock_sidebar')

@section('content')
<div class="space-y-6 text-sm text-gray-800 font-sans" x-data="laporanPreview()">
    <div>
        <h2 class="text-xl font-semibold text-gray-800">Laporan</h2>
    </div>

    <div class="bg-white border rounded-xl p-6">
        <form method="POST" id="laporan-form" target="_blank">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Jenis Laporan</label>
                    <select name="jenis" id="jenis_laporan" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                        <option value="">-- Pilih --</option>
                        <option value="stok">Laporan Stok</option>
                        <option value="mutasi">Laporan Mutasi Barang</option>
                        <option value="permintaan">Laporan Permintaan</option>
                        <option value="kartu_stok">Kartu Stok (Saldo Berjalan per Barang)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Area Kerja</label>
                    <select name="id_area" id="id_area" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Area</option>
                        @foreach($areas as $area)
                            <option value="{{ $area->id_area_kerja }}">
                                {{ $area->nama_area }} ({{ $area->bisnisUnit->nama_bisnis_unit ?? '-' }})
                            </option>
                        @endforeach
                    </select>
                    <p id="area_hint" class="text-xs text-amber-600 mt-1 hidden">Kartu Stok wajib memilih 1 area (tidak boleh "Semua Area").</p>
                </div>
                <div id="barang_field" class="hidden">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Barang</label>
                    <select name="id_barang" id="id_barang" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Barang</option>
                        @foreach($barang as $b)
                            <option value="{{ $b->id_barang }}">{{ $b->kode_barang }} - {{ $b->nama_barang }}</option>
                        @endforeach
                    </select>
                    <p id="barang_hint" class="text-xs text-amber-600 mt-1 hidden">Kartu Stok wajib memilih 1 barang.</p>
                </div>
                <div id="jenis_mutasi_field" class="hidden">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Jenis Transaksi</label>
                    <select name="jenis_mutasi" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Jenis</option>
                        <option value="masuk">Barang Masuk</option>
                        <option value="keluar">Barang Keluar</option>
                        <option value="transfer">Transfer</option>
                        <option value="opname">Opname</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Periode Awal</label>
                    <input type="date" name="tanggal_awal" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Periode Akhir</label>
                    <input type="date" name="tanggal_akhir" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <button type="button" id="btn-preview" @click="lihatLaporan()" :disabled="loading"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg disabled:opacity-50">
                    <i class="fas fa-eye mr-1"></i>
                    <span x-text="loading ? 'Memuat...' : 'Lihat Laporan'"></span>
                </button>
                <button type="button" id="btn-excel" class="px-4 py-2 bg-green-600 text-white rounded-lg">
                    <i class="fas fa-file-excel mr-1"></i> Cetak Excel
                </button>
            </div>
        </form>

        {{-- Pesan error preview --}}
        <div x-show="errorMsg" x-cloak class="mt-4 text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2" x-text="errorMsg"></div>

        {{-- Hasil Preview --}}
        <div x-show="preview" x-cloak class="mt-6 border-t pt-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">
                <h3 class="font-semibold text-gray-800">
                    Preview Laporan
                    <span class="text-xs font-normal text-gray-500" x-show="preview">
                        (menampilkan <span x-text="preview ? preview.rows.length : 0"></span> dari <span x-text="preview ? preview.total_rows : 0"></span> baris)
                    </span>
                </h3>
                <button type="button" @click="downloadExcel()" class="self-start sm:self-auto px-3 py-1.5 bg-green-600 text-white rounded-lg text-xs">
                    <i class="fas fa-download mr-1"></i> Download Excel (semua baris)
                </button>
            </div>

            <p x-show="preview && preview.truncated" x-cloak class="text-xs text-amber-600 mb-2">
                Preview dibatasi 200 baris pertama agar ringan. File Excel yang didownload tetap berisi seluruh <span x-text="preview ? preview.total_rows : 0"></span> baris.
            </p>

            <div class="overflow-x-auto border rounded-lg">
                <table class="w-full text-xs">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <template x-for="h in (preview ? preview.headers : [])" :key="h">
                                <th class="px-3 py-2 text-left whitespace-nowrap" x-text="h"></th>
                            </template>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <template x-for="(row, i) in (preview ? preview.rows : [])" :key="i">
                            <tr class="hover:bg-gray-50">
                                <template x-for="(cell, j) in row" :key="j">
                                    <td class="px-3 py-2 whitespace-nowrap" x-text="cell"></td>
                                </template>
                            </tr>
                        </template>
                        <tr x-show="preview && preview.rows.length === 0" x-cloak>
                            <td class="px-3 py-6 text-center text-gray-500" :colspan="preview ? preview.headers.length : 1">
                                Tidak ada data untuk kombinasi filter ini.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Riwayat Cetak Terbaru --}}
    <div class="bg-white border rounded-xl p-6 mt-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Riwayat</h3>
            <a href="{{ route('stock-ctl.laporan.history') }}" class="text-blue-600 hover:underline text-sm">Lihat Semua</a>
        </div>

        @if($recentHistory->isEmpty())
            <p class="text-gray-500 text-center py-4">Belum ada riwayat cetak.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-2 text-left">Waktu</th>
                            <th class="px-4 py-2 text-left">Jenis</th>
                            <th class="px-4 py-2 text-left">Area</th>
                            <th class="px-4 py-2 text-left">Barang</th>
                            <th class="px-4 py-2 text-left">Periode</th>
                            <th class="px-4 py-2 text-left">User</th>
                         </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($recentHistory as $h)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2">{{ $h->dicetak_pada->timezone('Asia/Jakarta')->format('d M Y H:i') }}</td>
                            <td class="px-4 py-2">{{ $h->jenis_label }}</td>
                            <td class="px-4 py-2">
                                @if($h->area)
                                    {{ $h->area->nama_area }} ({{ $h->area->bisnisUnit->nama_bisnis_unit ?? '-' }})
                                @else
                                    Semua Area
                                @endif
                            </td>
                            <td class="px-4 py-2">{{ $h->barang->nama_barang ?? 'Semua Barang' }}</td>
                            <td class="px-4 py-2">
                                @if($h->tanggal_awal && $h->tanggal_akhir)
                                    {{ \Carbon\Carbon::parse($h->tanggal_awal)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($h->tanggal_akhir)->format('d/m/Y') }}
                                @elseif($h->tanggal_awal)
                                    Mulai {{ \Carbon\Carbon::parse($h->tanggal_awal)->format('d/m/Y') }}
                                @elseif($h->tanggal_akhir)
                                    Sampai {{ \Carbon\Carbon::parse($h->tanggal_akhir)->format('d/m/Y') }}
                                @else
                                    Semua
                                @endif
                            </td>
                            <td class="px-4 py-2">{{ $h->user->name ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <script>
        function laporanPreview() {
            return {
                loading: false,
                preview: null,
                errorMsg: '',

                // Validasi khusus Kartu Stok (sama seperti aturan di server)
                validasiKartuStok() {
                    if (jenisLaporan.value !== 'kartu_stok') return true;
                    const idArea = document.getElementById('id_area').value;
                    const idBarang = document.getElementById('id_barang').value;
                    if (!idArea || !idBarang) {
                        this.errorMsg = 'Kartu Stok wajib memilih 1 Area Kerja dan 1 Barang terlebih dahulu.';
                        return false;
                    }
                    return true;
                },

                async lihatLaporan() {
                    this.errorMsg = '';
                    if (!jenisLaporan.value) {
                        this.errorMsg = 'Pilih jenis laporan terlebih dahulu.';
                        return;
                    }
                    if (!this.validasiKartuStok()) return;

                    this.loading = true;
                    this.preview = null;
                    try {
                        const formData = new FormData(form);
                        const res = await fetch("{{ route('stock-ctl.laporan.preview') }}", {
                            method: 'POST',
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                            body: formData,
                        });

                        if (!res.ok) {
                            const err = await res.json().catch(() => null);
                            this.errorMsg = err?.message || 'Gagal memuat preview laporan.';
                            return;
                        }

                        this.preview = await res.json();
                    } catch (e) {
                        this.errorMsg = 'Terjadi kesalahan jaringan saat memuat preview.';
                    } finally {
                        this.loading = false;
                    }
                },

                downloadExcel() {
                    if (!this.validasiKartuStok()) return;
                    form.action = "{{ route('stock-ctl.laporan.excel') }}";
                    form.submit();
                }
            }
        }

        const jenisLaporan = document.getElementById('jenis_laporan');
        const barangField = document.getElementById('barang_field');
        const jenisMutasiField = document.getElementById('jenis_mutasi_field');
        const areaHint = document.getElementById('area_hint');
        const barangHint = document.getElementById('barang_hint');

        jenisLaporan.addEventListener('change', function() {
            const isMutasi = this.value === 'mutasi';
            const isKartuStok = this.value === 'kartu_stok';

            barangField.classList.toggle('hidden', !(isMutasi || isKartuStok));
            jenisMutasiField.classList.toggle('hidden', !isMutasi);
            areaHint.classList.toggle('hidden', !isKartuStok);
            barangHint.classList.toggle('hidden', !isKartuStok);
        });

        const form = document.getElementById('laporan-form');
        document.getElementById('btn-excel').addEventListener('click', function() {
            if (jenisLaporan.value === 'kartu_stok') {
                const idArea = document.getElementById('id_area').value;
                const idBarang = document.getElementById('id_barang').value;
                if (!idArea || !idBarang) {
                    alert('Kartu Stok wajib memilih 1 Area Kerja dan 1 Barang terlebih dahulu.');
                    return;
                }
            }
            if (!jenisLaporan.value) {
                alert('Pilih jenis laporan terlebih dahulu.');
                return;
            }
            form.action = "{{ route('stock-ctl.laporan.excel') }}";
            form.submit();
        });
    </script>
</div>
@endsection