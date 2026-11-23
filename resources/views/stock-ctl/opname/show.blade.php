@extends('layouts.app_stock_sidebar')

@section('content')
<div class="space-y-6 text-sm text-gray-800 font-sans">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold text-gray-800">Detail Opname #{{ $opname->id_opname }}</h2>
        <a href="{{ route('stock-ctl.opname.index') }}" class="text-blue-600 hover:underline">← Kembali</a>
    </div>

    <div class="bg-white border rounded-xl p-6">
        <table class="w-full mb-4">
            <tr>
                <td class="py-2 text-gray-600 w-1/4">Area</td>
                <td class="py-2 font-medium">{{ $opname->areaKerja->nama_area ?? '-' }}</td>
            </tr>
            <tr>
                <td class="py-2 text-gray-600">Tanggal Opname</td>
                <td class="py-2 font-medium">{{ \Carbon\Carbon::parse($opname->tanggal_opname)->format('d M Y') }}</td>
            </tr>
            <tr>
                <td class="py-2 text-gray-600">Dibuat Oleh</td>
                <td class="py-2 font-medium">{{ $opname->user->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="py-2 text-gray-600">Status</td>
                <td class="py-2">
                    <span class="px-2 py-1 rounded-full text-xs font-semibold
                        @if($opname->status == 'draft') bg-yellow-100 text-yellow-800
                        @else bg-green-100 text-green-800 @endif">
                        {{ ucfirst($opname->status) }}
                    </span>
                </td>
            </tr>
        </table>

        <h3 class="font-medium mb-2">Detail Barang</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left">Barang</th>
                        <th class="px-4 py-2 text-left">Stok Sistem</th>
                        <th class="px-4 py-2 text-left">Stok Fisik</th>
                        <th class="px-4 py-2 text-left">Selisih</th>
                        <th class="px-4 py-2 text-left">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($opname->details as $detail)
                    <tr>
                        <td class="px-4 py-2">{{ $detail->barang->nama_barang ?? '-' }}</td>
                        <td class="px-4 py-2">{{ number_format($detail->stok_sistem) }}</td>
                        <td class="px-4 py-2">{{ number_format($detail->stok_fisik) }}</td>
                        <td class="px-4 py-2 @if($detail->selisih != 0) font-semibold {{ $detail->selisih > 0 ? 'text-green-600' : 'text-red-600' }} @endif">
                            {{ number_format($detail->selisih) }}
                        </td>
                        <td class="px-4 py-2">{{ $detail->keterangan ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($opname->status == 'draft')
        <div class="mt-6 flex justify-end">
            <form action="{{ route('stock-ctl.opname.update', $opname->id_opname) }}" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="status" value="selesai">
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg" onclick="return confirm('Selesaikan opname? Stok akan disesuaikan.')">
                    Selesaikan Opname
                </button>
            </form>
        </div>
        @endif
    </div>
</div>
@endsection