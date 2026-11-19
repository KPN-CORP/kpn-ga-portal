@extends('layouts.app_stock_sidebar')

@section('content')
<div class="space-y-6 text-sm text-gray-800 font-sans max-w-3xl mx-auto">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold text-gray-800">Detail Permintaan Antar Unit</h2>
        <a href="{{ route('stock-ctl.antar-unit.index') }}" class="text-blue-600 hover:underline">← Kembali</a>
    </div>

    <div class="bg-white border rounded-xl overflow-hidden">
        <div class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4 pb-4 border-b">
                <div>
                    <span class="text-gray-500 text-xs uppercase">No. Permintaan</span>
                    <p class="font-mono text-lg font-semibold">#{{ $request->id }}</p>
                </div>
                <div>
                    <span class="text-gray-500 text-xs uppercase">Status</span>
                    <p>
                        @if($request->status == 'pending')
                            <span class="inline-flex px-2 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">Menunggu Approval</span>
                        @elseif($request->status == 'disetujui')
                            <span class="inline-flex px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">Disetujui</span>
                        @else
                            <span class="inline-flex px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">Ditolak</span>
                        @endif
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <span class="text-gray-500 text-xs uppercase">Pemohon</span>
                    <p class="font-medium">{{ $request->pemohon->name ?? '-' }}</p>
                </div>
                <div>
                    <span class="text-gray-500 text-xs uppercase">Tanggal Pengajuan</span>
                    <p>{{ $request->created_at->format('d M Y H:i') }}</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <span class="text-gray-500 text-xs uppercase">Barang</span>
                    <p>{{ $request->barang->nama_barang ?? '-' }} ({{ $request->barang->kode_barang ?? '-' }})</p>
                </div>
                <div>
                    <span class="text-gray-500 text-xs uppercase">Jumlah</span>
                    <p>{{ number_format($request->jumlah) }} {{ $request->barang->satuan ?? '' }}</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <span class="text-gray-500 text-xs uppercase">Unit Asal</span>
                    <p>{{ $request->unitAsal->nama_bisnis_unit ?? '-' }}</p>
                </div>
                <div>
                    <span class="text-gray-500 text-xs uppercase">Unit Tujuan</span>
                    <p>{{ $request->unitTujuan->nama_bisnis_unit ?? '-' }}</p>
                </div>
            </div>

            <div>
                <span class="text-gray-500 text-xs uppercase">Keterangan</span>
                <p class="bg-gray-50 p-3 rounded-lg mt-1">{{ $request->keterangan ?: '-' }}</p>
            </div>

            @if($request->status != 'pending')
                <div class="border-t pt-4 mt-2">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-gray-500 text-xs uppercase">
                                {{ $request->status == 'disetujui' ? 'Disetujui Oleh' : 'Ditolak Oleh' }}
                            </span>
                            <p>{{ $request->approver->name ?? $request->rejectedBy->name ?? '-' }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500 text-xs uppercase">Tanggal Proses</span>
                            <p>{{ $request->approved_at ? $request->approved_at->format('d M Y H:i') : ($request->rejected_at ? $request->rejected_at->format('d M Y H:i') : '-') }}</p>
                        </div>
                    </div>
                    @if($request->alasan_tolak)
                    <div class="mt-2">
                        <span class="text-gray-500 text-xs uppercase">Alasan Penolakan</span>
                        <p class="bg-red-50 p-3 rounded-lg mt-1 text-red-700">{{ $request->alasan_tolak }}</p>
                    </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
@endsection