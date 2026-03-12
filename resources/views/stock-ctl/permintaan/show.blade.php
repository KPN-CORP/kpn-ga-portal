@extends('layouts.app_stock_sidebar')

@section('content')
<div class="space-y-6 text-sm text-gray-800 font-sans max-w-2xl">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold text-gray-800">Detail Permintaan #{{ $permintaan->id_permintaan }}</h2>
        <a href="{{ route('stock-ctl.permintaan.index') }}" class="text-blue-600 hover:underline">← Kembali</a>
    </div>

    <div class="bg-white border rounded-xl p-6">
        <table class="w-full">
            <tr>
                <td class="py-2 text-gray-600 w-1/3">Tanggal Permintaan</td>
                <td class="py-2 font-medium">{{ \Carbon\Carbon::parse($permintaan->tanggal_permintaan)->format('d M Y H:i') }}</td>
            </tr>
            <tr>
                <td class="py-2 text-gray-600">Pemohon</td>
                <td class="py-2 font-medium">{{ $permintaan->pemohon->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="py-2 text-gray-600">Barang</td>
                <td class="py-2 font-medium">{{ $permintaan->barang->nama_barang ?? '-' }} ({{ $permintaan->barang->kode_barang ?? '-' }})</td>
            </tr>
            <tr>
                <td class="py-2 text-gray-600">Jumlah</td>
                <td class="py-2 font-medium">{{ number_format($permintaan->jumlah) }} {{ $permintaan->barang->satuan ?? '' }}</td>
            </tr>
            <tr>
                <td class="py-2 text-gray-600">Keterangan</td>
                <td class="py-2 font-medium">{{ $permintaan->keterangan ?? '-' }}</td>
            </tr>
            <tr>
                <td class="py-2 text-gray-600">Status</td>
                <td class="py-2">
                    <span class="px-2 py-1 rounded-full text-xs font-semibold
                        @if($permintaan->status == 'pending') bg-yellow-100 text-yellow-800
                        @elseif($permintaan->status == 'disetujui') bg-green-100 text-green-800
                        @else bg-red-100 text-red-800 @endif">
                        {{ ucfirst($permintaan->status) }}
                    </span>
                </td>
            </tr>
            @if($permintaan->status != 'pending')
            <tr>
                <td class="py-2 text-gray-600">Approver</td>
                <td class="py-2 font-medium">{{ $permintaan->approver->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="py-2 text-gray-600">Tanggal Approval</td>
                <td class="py-2 font-medium">{{ $permintaan->tanggal_approval ? \Carbon\Carbon::parse($permintaan->tanggal_approval)->format('d M Y H:i') : '-' }}</td>
            </tr>
            @endif
            @if($permintaan->status == 'ditolak')
            <tr>
                <td class="py-2 text-gray-600">Alasan Penolakan</td>
                <td class="py-2 font-medium text-red-600">{{ $permintaan->alasan_tolak }}</td>
            </tr>
            @endif
        </table>
    </div>
</div>
@endsection