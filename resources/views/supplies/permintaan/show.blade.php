@extends('layouts.app_supplies_sidebar')

@section('content')
<div class="max-w-2xl">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-semibold">Detail Permintaan #{{ $permintaan->id }}</h2>
        <a href="{{ route('supplies.permintaan.index') }}" class="text-blue-600 hover:underline">← Kembali</a>
    </div>

    <div class="bg-white rounded-xl border p-6">
        <table class="w-full">
            <tr class="border-b">
                <td class="py-2 w-1/3 text-gray-600">Tanggal Pengajuan</td>
                <td class="py-2">{{ $permintaan->created_at->format('d M Y H:i') }}</td>
            </tr>
            <tr class="border-b">
                <td class="py-2 text-gray-600">Pemohon</td>
                <td class="py-2">{{ $permintaan->pemohon->name ?? '-' }}</td>
            </tr>
            <tr class="border-b">
                <td class="py-2 text-gray-600">Barang</td>
                <td class="py-2">{{ $permintaan->barang->nama_barang ?? '-' }} ({{ $permintaan->barang->kode_barang ?? '-' }})</td>
            </tr>
            <tr class="border-b">
                <td class="py-2 text-gray-600">Jumlah</td>
                <td class="py-2">{{ number_format($permintaan->jumlah) }} {{ $permintaan->barang->satuan ?? '' }}</td>
            </tr>
            <tr class="border-b">
                <td class="py-2 text-gray-600">Bisnis Unit Tujuan</td>
                <td class="py-2">{{ $permintaan->bisnisUnit->nama_bisnis_unit ?? '-' }}</td>
            </tr>
            <tr class="border-b">
                <td class="py-2 text-gray-600">Keterangan</td>
                <td class="py-2">{{ $permintaan->keterangan ?? '-' }}</td>
            </tr>
            <tr class="border-b">
                <td class="py-2 text-gray-600">Status</td>
                <td class="py-2">
                    @if($permintaan->status == 'pending')
                        <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs">Pending</span>
                    @elseif($permintaan->status == 'disetujui')
                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">Disetujui</span>
                    @else
                        <span class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs">Ditolak</span>
                    @endif
                </td>
            </tr>
            @if($permintaan->status != 'pending')
            <tr class="border-b">
                <td class="py-2 text-gray-600">Diproses Oleh</td>
                <td class="py-2">{{ $permintaan->approver->name ?? '-' }}</td>
            </tr>
            <tr class="border-b">
                <td class="py-2 text-gray-600">Tanggal Proses</td>
                <td class="py-2">{{ $permintaan->approved_at ? $permintaan->approved_at->format('d M Y H:i') : '-' }}</td>
            </tr>
            @endif
            @if($permintaan->status == 'ditolak' && $permintaan->alasan_tolak)
            <tr>
                <td class="py-2 text-gray-600">Alasan Penolakan</td>
                <td class="py-2 text-red-600">{{ $permintaan->alasan_tolak }}</td>
            </tr>
            @endif
        </table>
    </div>
</div>
@endsection