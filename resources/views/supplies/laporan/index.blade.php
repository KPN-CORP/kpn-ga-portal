@extends('layouts.app_supplies_sidebar')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap justify-between items-center gap-3">
        <h2 class="text-xl font-semibold text-gray-800">Laporan Mutasi Supplies</h2>
        <div class="flex flex-wrap gap-2">
            <form action="{{ route('supplies.laporan.export') }}" method="POST" target="_blank">
                @csrf
                <input type="hidden" name="tanggal_awal" value="{{ request('tanggal_awal', $tanggalAwal) }}">
                <input type="hidden" name="tanggal_akhir" value="{{ request('tanggal_akhir', $tanggalAkhir) }}">
                <input type="hidden" name="id_bisnis_unit" value="{{ request('id_bisnis_unit') }}">
                <input type="hidden" name="id_barang" value="{{ request('id_barang') }}">
                <input type="hidden" name="format" value="pdf">
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 md:px-4 rounded-lg text-sm transition"><i class="fas fa-file-pdf mr-1"></i> PDF</button>
            </form>
            <form action="{{ route('supplies.laporan.export') }}" method="POST" target="_blank">
                @csrf
                <input type="hidden" name="tanggal_awal" value="{{ request('tanggal_awal', $tanggalAwal) }}">
                <input type="hidden" name="tanggal_akhir" value="{{ request('tanggal_akhir', $tanggalAkhir) }}">
                <input type="hidden" name="id_bisnis_unit" value="{{ request('id_bisnis_unit') }}">
                <input type="hidden" name="id_barang" value="{{ request('id_barang') }}">
                <input type="hidden" name="format" value="excel">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 md:px-4 rounded-lg text-sm transition"><i class="fas fa-file-excel mr-1"></i> Excel</button>
            </form>
        </div>
    </div>

    <div class="bg-white border rounded-xl p-4">
        <form method="GET" action="{{ route('supplies.laporan.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <div>
                <label class="text-sm font-medium text-gray-600">Dari Tanggal</label>
                <input type="date" name="tanggal_awal" value="{{ request('tanggal_awal', $tanggalAwal) }}" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Sampai Tanggal</label>
                <input type="date" name="tanggal_akhir" value="{{ request('tanggal_akhir', $tanggalAkhir) }}" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Bisnis Unit</label>
                <select name="id_bisnis_unit" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua</option>
                    @foreach($bisnisUnits as $bu)
                    <option value="{{ $bu->id_bisnis_unit }}" {{ request('id_bisnis_unit')==$bu->id_bisnis_unit ? 'selected' : '' }}>{{ $bu->nama_bisnis_unit }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Barang</label>
                <select name="id_barang" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua</option>
                    @foreach($barang as $b)
                    <option value="{{ $b->id }}" {{ request('id_barang')==$b->id ? 'selected' : '' }}>{{ $b->kode_barang }} - {{ $b->nama_barang }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition">Filter</button>
                <a href="{{ route('supplies.laporan.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm transition">Reset</a>
            </div>
        </form>
    </div>

    <!-- Desktop Table -->
    <div class="bg-white border rounded-xl overflow-x-auto shadow-sm hidden md:block">
        <table class="w-full text-sm min-w-[1100px]">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-3">No-Request</th>
                    <th class="px-3 py-3">Tanggal</th>
                    <th class="px-3 py-3">Jenis</th>
                    <th class="px-3 py-3">Barang</th>
                    <th class="px-3 py-3 text-right">Jumlah</th>
                    <th class="px-3 py-3">Satuan</th>
                    <th class="px-3 py-3">Bisnis Unit</th>
                    <th class="px-3 py-3">Keperluan</th>
                    <th class="px-3 py-3">Request</th>
                    <th class="px-3 py-3">Noted Approve</th>
                    <th class="px-3 py-3">Approve</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($transaksi as $t)
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-2">{{ $t->no_ref ?? '-' }}</td>
                    <td class="px-3 py-2 whitespace-nowrap">{{ $t->tanggal->format('d/m/Y H:i') }}</td>
                    <td class="px-3 py-2"><span class="px-2 py-1 rounded-full text-xs {{ $t->jenis=='masuk' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">{{ ucfirst($t->jenis) }}</span></td>
                    <td class="px-3 py-2">{{ $t->barang->nama_barang }}</td>
                    <td class="px-3 py-2 text-right">{{ number_format($t->jumlah, 0, ',', '.') }} ({{ $t->barang->satuan }})</td>
                    <td class="px-3 py-2">{{ $t->barang->satuan }}</td>
                    <td class="px-3 py-2">{{ $t->bisnisUnit->nama_bisnis_unit ?? '-' }}</td>
                    <td class="px-3 py-2 break-words max-w-[150px]">{{ $t->permintaan->keterangan ?? '-' }}</td>
                    <td class="px-3 py-2 break-words max-w-[120px]">{{ $t->permintaan->pemohon->name ?? '-' }}</td>
                    <td class="px-3 py-2 break-words max-w-[200px]">{{ $t->keterangan ?? '-' }}</td>
                    <td class="px-3 py-2 break-words max-w-[120px]">{{ $t->permintaan->approver->name ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" class="py-10 text-center text-gray-500">Tidak ada data</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Mobile Cards (hanya menampilkan info penting) -->
    <div class="md:hidden space-y-4">
        @forelse($transaksi as $t)
        <div class="bg-white border rounded-xl p-4 shadow-sm">
            <div class="flex justify-between items-start">
                <div>
                    <p class="font-semibold">{{ $t->barang->nama_barang }}</p>
                    <p class="text-sm text-gray-500">{{ $t->no_ref ?? '-' }}</p>
                </div>
                <span class="px-2 py-1 rounded-full text-xs {{ $t->jenis=='masuk' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">{{ ucfirst($t->jenis) }}</span>
            </div>
            <div class="mt-2 text-sm text-gray-600 grid grid-cols-2 gap-1">
                <span>Jumlah: {{ number_format($t->jumlah, 0, ',', '.') }} {{ $t->barang->satuan }}</span>
                <span>Unit: {{ $t->bisnisUnit->nama_bisnis_unit ?? '-' }}</span>
                <span class="col-span-2">Tanggal: {{ $t->tanggal->format('d/m/Y H:i') }}</span>
                <span class="col-span-2">Keperluan: {{ $t->permintaan->keterangan ?? '-' }}</span>
            </div>
        </div>
        @empty
        <div class="py-10 text-center text-gray-500">Tidak ada data</div>
        @endforelse
    </div>

    {{ $transaksi->links() }}
</div>
@endsection