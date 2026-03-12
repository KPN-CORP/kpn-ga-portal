@extends('layouts.app_stock_sidebar')

@section('content')
<div class="space-y-6 text-sm text-gray-800 font-sans">
    <div>
        <h2 class="text-xl font-semibold text-gray-800">Laporan ATK</h2>
    </div>

    <div class="bg-white border rounded-xl p-6">
        <form method="POST" action="{{ route('stock-ctl.laporan.pdf') }}" target="_blank">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Jenis Laporan</label>
                    <select name="jenis" id="jenis_laporan" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                        <option value="">-- Pilih --</option>
                        <option value="stok">Laporan Stok</option>
                        <option value="mutasi">Laporan Mutasi Barang</option>
                        <option value="permintaan">Laporan Permintaan</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Area Kerja</label>
                    <select name="id_area" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Area</option>
                        @foreach($areas as $area)
                            <option value="{{ $area->id_area_kerja }}">{{ $area->nama_area }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="barang_field" class="hidden">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Barang</label>
                    <select name="id_barang" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Barang</option>
                        @foreach($barang as $b)
                            <option value="{{ $b->id_barang }}">{{ $b->kode_barang }} - {{ $b->nama_barang }}</option>
                        @endforeach
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
            <div class="mt-6 flex justify-end">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">
                    <i class="fas fa-print mr-1"></i> Cetak PDF
                </button>
            </div>
        </form>
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
                            <td class="px-4 py-2">{{ ucfirst($h->jenis) }}</td>
                            <td class="px-4 py-2">{{ $h->area->nama_area ?? 'Semua Area' }}</td>
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
        document.getElementById('jenis_laporan').addEventListener('change', function() {
            const barangField = document.getElementById('barang_field');
            if (this.value === 'mutasi') {
                barangField.classList.remove('hidden');
            } else {
                barangField.classList.add('hidden');
            }
        });
    </script>
</div>
@endsection