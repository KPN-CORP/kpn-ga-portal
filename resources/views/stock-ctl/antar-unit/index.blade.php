@extends('layouts.app_stock_sidebar')
@section('content')
<div class="space-y-6">
    <div class="flex justify-between">
        <h2 class="text-xl font-semibold">Permintaan Antar Unit</h2>
        <a href="{{ route('stock-ctl.antar-unit.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg">+ Ajukan Permintaan</a>
    </div>
    <div class="bg-white rounded-xl overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr><th>ID</th><th>Barang</th><th>Jumlah</th><th>Unit Asal</th><th>Unit Tujuan</th><th>Status</th><th>Tgl Pengajuan</th><th>Aksi</th></tr></thead>
            <tbody>
                @foreach($requests as $r)
                <tr>
                    <td class="px-4 py-2">#{{ $r->id }}</td>
                    <td>{{ $r->barang->nama_barang }}</td>
                    <td>{{ number_format($r->jumlah) }} {{ $r->barang->satuan }}</td>
                    <td>{{ $r->unitAsal->nama_bisnis_unit }}</td>
                    <td>{{ $r->unitTujuan->nama_bisnis_unit }}</td>
                    <td>
                        @if($r->status == 'pending') <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs">Menunggu</span>
                        @elseif($r->status == 'disetujui') <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">Disetujui</span>
                        @else <span class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs">Ditolak</span> @endif
                    </td>
                    <td>{{ $r->created_at->format('d M Y H:i') }}</td>
                    <td><a href="{{ route('stock-ctl.antar-unit.show', $r->id) }}" class="text-blue-600">Detail</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        {{ $requests->links() }}
    </div>
</div>
@endsection