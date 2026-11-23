@extends('layouts.app_supplies_sidebar')

@section('content')
<div class="max-w-2xl space-y-4">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold">Detail Permintaan #{{ $permintaan->id }}</h2>
        <a href="{{ route('supplies.permintaan.index') }}" class="text-blue-600 hover:underline">← Kembali</a>
    </div>

    <div class="bg-white rounded-xl border p-6">
        <table class="w-full">
            <tr class="border-b">
                <td class="py-2 w-1/3 text-gray-600">Pemohon</td>
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
                <td class="py-2 text-gray-600">Keterangan</td>
                <td class="py-2">{{ $permintaan->keterangan ?: '-' }}</td>
            </tr>
            <tr class="{{ $permintaan->status == 'ditolak' && $permintaan->alasan_tolak ? 'border-b' : '' }}">
                <td class="py-2 text-gray-600">Status</td>
                <td class="py-2">@include('supplies.permintaan._status', ['status' => $permintaan->status])</td>
            </tr>
            @if($permintaan->status == 'ditolak' && $permintaan->alasan_tolak)
            <tr>
                <td class="py-2 text-gray-600">Alasan Penolakan</td>
                <td class="py-2 text-red-600">{{ $permintaan->alasan_tolak }}</td>
            </tr>
            @endif
        </table>
    </div>

    <!-- Riwayat: kapan diajukan & kapan diproses -->
    <div class="bg-white rounded-xl border p-6">
        <h3 class="font-semibold mb-4">Riwayat Proses</h3>
        <ol class="relative border-l-2 border-gray-200 ml-2 space-y-6">
            <li class="ml-5">
                <span class="absolute -left-1.5 mt-1.5 w-3 h-3 rounded-full bg-blue-500"></span>
                <p class="font-medium">Diajukan</p>
                <p class="text-sm text-gray-600">{{ $permintaan->created_at->format('d M Y, H:i') }}</p>
                <p class="text-xs text-gray-400">oleh {{ $permintaan->pemohon->name ?? '-' }}</p>
            </li>

            @if($permintaan->approved_at)
            <li class="ml-5">
                <span class="absolute -left-1.5 mt-1.5 w-3 h-3 rounded-full {{ $permintaan->status == 'ditolak' ? 'bg-red-500' : 'bg-green-500' }}"></span>
                <p class="font-medium">{{ $permintaan->status == 'ditolak' ? 'Ditolak' : 'Disetujui' }}</p>
                <p class="text-sm text-gray-600">{{ $permintaan->approved_at->format('d M Y, H:i') }}</p>
                <p class="text-xs text-gray-400">oleh {{ $permintaan->approver->name ?? '-' }} &middot; diproses dalam {{ $permintaan->lama_proses }}</p>
            </li>
            @else
            <li class="ml-5">
                <span class="absolute -left-1.5 mt-1.5 w-3 h-3 rounded-full bg-gray-300"></span>
                <p class="font-medium text-gray-500">Menunggu persetujuan</p>
                <p class="text-xs text-gray-400">sudah menunggu {{ $permintaan->lama_proses }}</p>
            </li>
            @endif
        </ol>
    </div>
</div>
@endsection
