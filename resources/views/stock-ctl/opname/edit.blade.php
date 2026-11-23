@extends('layouts.app_stock_sidebar')

@section('content')
<div class="space-y-6 text-sm text-gray-800 font-sans">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold text-gray-800">Edit Opname #{{ $opname->id_opname }}</h2>
        <a href="{{ route('stock-ctl.opname.index') }}" class="text-blue-600 hover:underline">← Kembali</a>
    </div>

    <div class="bg-white border rounded-xl p-6">
        <div class="mb-4">
            <p><strong>Area:</strong> {{ $opname->areaKerja->nama_area ?? '-' }}</p>
            <p><strong>Tanggal Opname:</strong> {{ \Carbon\Carbon::parse($opname->tanggal_opname)->format('d M Y') }}</p>
            <p><strong>Status:</strong> 
                <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $opname->status == 'draft' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' }}">
                    {{ ucfirst($opname->status) }}
                </span>
            </p>
        </div>

        <form method="POST" action="{{ route('stock-ctl.opname.update', $opname->id_opname) }}">
            @csrf
            @method('PUT')

            <div class="overflow-x-auto">
                <table class="w-full text-sm border">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left">Barang</th>
                            <th class="px-4 py-2 text-left">Stok Sistem</th>
                            <th class="px-4 py-2 text-left">Stok Fisik</th>
                            <th class="px-4 py-2 text-left">Selisih</th>
                            <th class="px-4 py-2 text-left">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stok as $item)
                        @php
                            $detail = $details[$item->id_barang] ?? null;
                        @endphp
                        <tr>
                            <td class="px-4 py-2">{{ $item->barang->nama_barang ?? '-' }}</td>
                            <td class="px-4 py-2">{{ number_format($item->jumlah) }} {{ $item->barang->satuan ?? '' }}</td>
                            <td class="px-4 py-2">
                                <input type="number" step="0.01" name="items[{{ $item->id_barang }}][stok_fisik]" 
                                       value="{{ old('items.'.$item->id_barang.'.stok_fisik', $detail->stok_fisik ?? $item->jumlah) }}"
                                       class="w-24 border rounded px-2 py-1 text-sm">
                                <input type="hidden" name="items[{{ $item->id_barang }}][id_barang]" value="{{ $item->id_barang }}">
                            </td>
                            <td class="px-4 py-2" id="selisih-{{ $item->id_barang }}">
                                {{ $detail ? number_format($detail->selisih) : '0' }}
                            </td>
                            <td class="px-4 py-2">
                                <input type="text" name="items[{{ $item->id_barang }}][keterangan]" 
                                       value="{{ old('items.'.$item->id_barang.'.keterangan', $detail->keterangan ?? '') }}"
                                       class="w-full border rounded px-2 py-1 text-sm">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <button type="submit" name="simpan" value="draft" class="px-4 py-2 bg-gray-600 text-white rounded-lg">
                    Simpan Draft
                </button>
                <button type="submit" name="selesai" value="selesai" class="px-4 py-2 bg-green-600 text-white rounded-lg" onclick="return confirm('Selesaikan opname? Stok akan disesuaikan.')">
                    Selesaikan Opname
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Optional: auto calculate selisih on input change
document.querySelectorAll('input[name^="items"][name$="[stok_fisik]"]').forEach(input => {
    input.addEventListener('input', function() {
        const row = this.closest('tr');
        const sistemCell = row.querySelector('td:nth-child(2)');
        const selisihCell = row.querySelector('td:nth-child(4)');
        if (sistemCell && selisihCell) {
            const sistem = parseFloat(sistemCell.innerText.replace(/[^0-9\-\.]/g, '')) || 0;
            const fisik = parseFloat(this.value) || 0;
            const selisih = fisik - sistem;
            selisihCell.innerText = selisih.toFixed(2);
        }
    });
});
</script>
@endsection